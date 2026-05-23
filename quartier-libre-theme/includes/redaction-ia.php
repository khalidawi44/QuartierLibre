<?php
/**
 * Quartier Libre — Rédaction assistée par IA (Claude / Anthropic)
 *
 * Utilisé par le robot de veille pour transformer un sujet repéré en
 * brouillon d'article complet, RÉDIGÉ À PARTIR DE LA VRAIE SOURCE.
 *
 * Règle absolue (cf. ONBOARDING.md) : ne jamais rien inventer. L'IA n'écrit
 * qu'à partir du matériel source récupéré ; si la source est trop maigre, on
 * ne rédige pas (fallback brouillon simple). Tout point non sûr est marqué
 * 👤 dans la fiche source → la publication reste BLOQUÉE tant que la
 * rédaction n'a pas validé (système de vérif existant dans functions.php).
 *
 * Réglages : Quartier Libre → Robot de veille (clé API, modèle, options).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ql_ia_api_key() {
    return trim( (string) get_option( 'ql_ia_anthropic_key', '' ) );
}

function ql_ia_model() {
    $m = trim( (string) get_option( 'ql_ia_model', '' ) );
    return $m !== '' ? $m : 'claude-sonnet-4-6';
}

/** Rédaction auto active = clé présente ET option cochée. */
function ql_ia_enabled() {
    return ql_ia_api_key() !== '' && get_option( 'ql_ia_autoredac', '1' ) === '1';
}

/**
 * Appel à l'API Messages d'Anthropic. Retourne le texte de la réponse
 * (string) ou WP_Error.
 */
function ql_ia_call_claude( $system, $user_msg, $max_tokens = 4000 ) {
    $key = ql_ia_api_key();
    if ( $key === '' ) {
        return new WP_Error( 'no_key', 'Clé API Anthropic absente.' );
    }

    $resp = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 90,
        'headers' => array(
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ),
        'body'    => wp_json_encode( array(
            'model'      => ql_ia_model(),
            'max_tokens' => (int) $max_tokens,
            'system'     => $system,
            'messages'   => array(
                array( 'role' => 'user', 'content' => $user_msg ),
            ),
        ) ),
    ) );

    if ( is_wp_error( $resp ) ) {
        return $resp;
    }

    $code = wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );

    if ( $code !== 200 || ! is_array( $body ) ) {
        $msg = is_array( $body ) && isset( $body['error']['message'] )
            ? $body['error']['message']
            : ( 'HTTP ' . $code );
        return new WP_Error( 'api_error', 'Anthropic : ' . $msg );
    }

    if ( empty( $body['content'][0]['text'] ) ) {
        return new WP_Error( 'empty', 'Réponse IA vide.' );
    }
    return (string) $body['content'][0]['text'];
}

/**
 * Récupère le texte brut de l'article source (best-effort). Suit les
 * redirections, isole le <article> si présent, plafonne la longueur.
 * Retourne '' si rien d'exploitable.
 */
function ql_ia_fetch_source_text( $url ) {
    if ( ! $url ) { return ''; }

    $resp = wp_remote_get( $url, array(
        'timeout'     => 20,
        'redirection' => 5,
        'user-agent'  => 'Mozilla/5.0 (compatible; QuartierLibreBot/1.0; +https://quartierlibre.org)',
    ) );
    if ( is_wp_error( $resp ) ) { return ''; }
    if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) { return ''; }

    $html = wp_remote_retrieve_body( $resp );
    if ( ! $html ) { return ''; }

    // Retire les blocs non-éditoriaux
    $html = preg_replace( '#<(script|style|noscript|nav|header|footer|aside|form)\b[^>]*>.*?</\1>#is', ' ', $html );

    // Privilégie le contenu de <article> s'il existe
    if ( preg_match( '#<article\b[^>]*>(.*?)</article>#is', $html, $m ) ) {
        $html = $m[1];
    }

    $text = wp_strip_all_tags( $html );
    $text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
    $text = preg_replace( '/\s+/u', ' ', (string) $text );
    $text = trim( $text );

    if ( mb_strlen( $text ) > 8000 ) {
        $text = mb_substr( $text, 0, 8000 );
    }
    return $text;
}

/**
 * Génère un brouillon complet à partir d'un item de veille.
 * Retourne array( title, excerpt, primary_category, body_html, sources_md )
 * ou WP_Error. Ne rédige PAS si la source est trop maigre (anti-invention).
 */
function ql_ia_generate_from_item( $item ) {
    $src_text = ql_ia_fetch_source_text( $item['link'] ?? '' );
    $material = trim( ( $item['summary'] ?? '' ) . "\n\n" . $src_text );

    if ( mb_strlen( $material ) < 500 ) {
        return new WP_Error( 'thin_source', 'Source trop maigre pour rédiger sans inventer.' );
    }

    $cats = 'infos-locale, bellevue, malakoff, dervallieres, clos-toreau, bottiere-pin-sec, '
          . 'breil, bout-des-landes, port-boyer, halveque, ranzay, pilotiere, transports, '
          . 'autres-villes, politique, justice, fait-divers, economie, societe, guerre, '
          . 'genocide, famine, resistance, mobilisations, repression, solidarite, logement, histoire';

    $source_label = $item['source'] ?: 'source';
    $link         = $item['link'] ?? '';

    $system = "Tu es journaliste pour Quartier Libre (quartierlibre.org), média militant sur "
        . "les quartiers populaires de Nantes. Ton : inspiré de Contre-Attaque — rouge/noir, "
        . "percutant, militant, du côté des habitant·es, contre les violences policières, le "
        . "mal-logement, le racisme et la répression. Tu écris en FRANÇAIS.\n\n"
        . "RÈGLE ABSOLUE : tu n'inventes RIEN. Tu n'utilises QUE les faits présents dans le "
        . "matériel source fourni. Interdiction d'inventer une date, un chiffre, une citation, "
        . "un nom ou un lieu. Si une information manque, ne l'écris pas — ou signale-la comme à "
        . "vérifier (👤). Tu produis un BROUILLON destiné à une relecture humaine obligatoire.";

    $user = "MATÉRIEL SOURCE (n'utilise que ça) :\n"
        . 'Titre repéré : ' . $item['title'] . "\n"
        . 'Source : ' . $source_label . "\n"
        . 'Lien : ' . $link . "\n"
        . 'Date : ' . date_i18n( 'Y-m-d', (int) ( $item['date'] ?? time() ) ) . "\n\n"
        . "CONTENU :\n" . $material . "\n\n"
        . "---\n"
        . "Rédige un article Quartier Libre à partir de CE matériel uniquement.\n"
        . 'Catégories valides (choisis la plus pertinente) : ' . $cats . "\n\n"
        . "Réponds UNIQUEMENT avec un objet JSON valide (aucun texte autour, pas de ```), clés :\n"
        . "{\n"
        . '  "title": "titre percutant en français",' . "\n"
        . '  "excerpt": "résumé 1-2 phrases (chapô / meta description)",' . "\n"
        . '  "primary_category": "un slug de la liste",' . "\n"
        . '  "body_html": "l\'article en HTML (<p>, <h2>, <blockquote>), ton QL, UNIQUEMENT des faits du matériel, et un dernier paragraphe Source renvoyant au lien",' . "\n"
        . '  "sources_md": "fiche source markdown (format ci-dessous)"' . "\n"
        . "}\n\n"
        . "FORMAT de sources_md :\n"
        . "## ✓ Sources vérifiées\n- [affirmation paraphrasée] → [" . $source_label . "](" . $link . ")\n\n"
        . "## 👤 À valider par la rédaction\n- « passage EXACT recopié de body_html » — **Action** : confirmer / vérifier\n\n"
        . "IMPÉRATIF : mets AU MOINS un point 👤 (sinon la publication ne sera pas verrouillée "
        . "pour relecture). Dans chaque 👤, recopie entre « » le passage EXACT tel qu'il "
        . "apparaît dans body_html.";

    $raw = ql_ia_call_claude( $system, $user, 4000 );
    if ( is_wp_error( $raw ) ) { return $raw; }

    // Nettoie d'éventuelles clôtures de code
    $raw = trim( $raw );
    $raw = preg_replace( '/^```(json)?/i', '', $raw );
    $raw = preg_replace( '/```$/', '', $raw );
    $raw = trim( $raw );

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data['title'] ) || empty( $data['body_html'] ) ) {
        return new WP_Error( 'parse', 'Réponse IA non exploitable.' );
    }
    return $data;
}
