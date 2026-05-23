<?php
/**
 * Quartier Libre — Robot de veille (sans IA)
 *
 * Surveille des requêtes ciblées via le flux RSS de Google Actualités
 * (endpoint stable : news.google.com/rss/search) et collecte les sujets
 * récents correspondant aux thèmes de Quartier Libre (manifs, logement/HLM,
 * sécurité, politique locale nantaise).
 *
 * Les sujets trouvés sont affichés dans le Tableau de bord. D'un clic, on
 * crée un BROUILLON pré-rempli (titre + lien source + rappel de relecture) :
 * pas de publication automatique, la rédaction garde la main.
 *
 * Pas de clé API requise. Tourne 2×/jour en cron + bouton « Lancer maintenant ».
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Requêtes par défaut (modifiables dans l'admin) ─────────────
function ql_veille_default_queries() {
    return array(
        // Mobilisations / luttes
        'manifestation OR rassemblement OR grève Nantes',
        'syndicat OR CGT OR Solidaires OR intersyndicale Nantes',
        'blocage OR occupation OR piquet de grève Nantes',
        // Logement / bailleurs
        'logement social OR HLM OR bailleur Nantes',
        'expulsion locative OR mal-logement OR squat Nantes',
        'rénovation urbaine OR ANRU OR relogement Nantes',
        // Police / répression / surveillance
        'violences policières OR bavure OR IGPN Nantes',
        'contrôle au faciès OR racisme OR discrimination Nantes',
        'surveillance OR vidéosurveillance OR fichage Nantes',
        // Quartiers populaires (par nom)
        'Bellevue OR Malakoff OR Dervallières Nantes',
        '"Clos Toreau" OR "Bottière" OR "Pin Sec" OR Breil Nantes',
        '"Bout des Landes" OR "Port Boyer" OR Halvêque OR Ranzay Nantes',
        // Services publics / social
        'hôpital OR école OR services publics quartiers Nantes',
        'sans-papiers OR migrants OR CRA OR préfecture Nantes',
        'transports OR Keolis OR Tan OR métropole Nantes',
        // National / international (lignes QL)
        'loi immigration OR Darmanin OR Retailleau',
        'Gaza OR Palestine OR solidarité Nantes',
        'extrême droite OR antifascisme Nantes',
    );
}

function ql_veille_queries() {
    $q = get_option( 'ql_veille_queries', '' );
    if ( is_string( $q ) && trim( $q ) !== '' ) {
        $lines = array_filter( array_map( 'trim', explode( "\n", $q ) ) );
        if ( ! empty( $lines ) ) { return $lines; }
    }
    return ql_veille_default_queries();
}

// ── Cron 2×/jour ───────────────────────────────────────────────
add_action( 'init', function () {
    $enabled   = get_option( 'ql_veille_enabled', '1' ) === '1';
    $scheduled = wp_next_scheduled( 'ql_veille_cron' );
    if ( $enabled && ! $scheduled ) {
        wp_schedule_event( time() + 120, 'twicedaily', 'ql_veille_cron' );
    } elseif ( ! $enabled && $scheduled ) {
        wp_unschedule_event( $scheduled, 'ql_veille_cron' );
    }
} );
add_action( 'ql_veille_cron', 'ql_veille_run' );
add_action( 'switch_theme', function () {
    $ts = wp_next_scheduled( 'ql_veille_cron' );
    if ( $ts ) { wp_unschedule_event( $ts, 'ql_veille_cron' ); }
} );

// ── Collecte : interroge chaque requête, dédoublonne, stocke ───
function ql_veille_run() {
    if ( ! function_exists( 'fetch_feed' ) ) {
        include_once ABSPATH . WPINC . '/feed.php';
    }

    $items = get_option( 'ql_veille_items', array() );
    if ( ! is_array( $items ) ) { $items = array(); }

    // Index des clés déjà connues (vues ou utilisées)
    $known = array();
    foreach ( $items as $it ) { $known[ $it['key'] ] = true; }

    $added = 0;
    foreach ( ql_veille_queries() as $query ) {
        $url  = 'https://news.google.com/rss/search?q=' . urlencode( $query ) . '&hl=fr&gl=FR&ceid=FR:fr';
        $feed = fetch_feed( $url );
        if ( is_wp_error( $feed ) ) { continue; }

        $max  = $feed->get_item_quantity( 20 );
        $list = $feed->get_items( 0, $max );
        foreach ( $list as $item ) {
            $title = trim( wp_strip_all_tags( (string) $item->get_title() ) );
            $link  = esc_url_raw( (string) $item->get_permalink() );
            if ( $title === '' || $link === '' ) { continue; }

            $key = md5( $link );
            if ( isset( $known[ $key ] ) ) { continue; }

            // Source : Google met souvent « Titre - Source » → on isole la source
            $source = '';
            if ( strrpos( $title, ' - ' ) !== false ) {
                $source = trim( substr( $title, strrpos( $title, ' - ' ) + 3 ) );
                $title  = trim( substr( $title, 0, strrpos( $title, ' - ' ) ) );
            }
            $date = $item->get_date( 'U' );

            // Extrait de la source (sert d'amorce à la rédaction IA)
            $summary = trim( wp_strip_all_tags( (string) $item->get_description() ) );
            if ( mb_strlen( $summary ) > 1200 ) { $summary = mb_substr( $summary, 0, 1200 ); }

            $items[] = array(
                'key'     => $key,
                'title'   => $title,
                'link'    => $link,
                'source'  => $source,
                'summary' => $summary,
                'query'   => $query,
                'date'    => $date ? (int) $date : time(),
                'found'   => time(),
                'used'    => 0,
            );
            $known[ $key ] = true;
            $added++;
        }
    }

    // Tri par date décroissante, plafond à 250 entrées
    usort( $items, function ( $a, $b ) { return $b['date'] <=> $a['date']; } );
    if ( count( $items ) > 250 ) { $items = array_slice( $items, 0, 250 ); }

    update_option( 'ql_veille_items', $items, false );
    update_option( 'ql_veille_last_run', time(), false );

    // Rédaction IA automatique des sujets récents (si activée + clé présente)
    if ( function_exists( 'ql_ia_enabled' ) && ql_ia_enabled() ) {
        ql_veille_autoredac();
    }

    return $added;
}

/**
 * Crée un brouillon à partir d'un sujet de veille. Tente la rédaction IA
 * (à partir de la vraie source) ; sinon, repli sur un brouillon simple.
 * Retourne l'ID du post ou WP_Error.
 */
function ql_veille_make_draft( $found, $author_id = 0 ) {
    if ( ! $author_id ) { $author_id = get_current_user_id(); }
    if ( ! $author_id ) {
        $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
        $author_id = ! empty( $admins ) ? (int) $admins[0] : 0;
    }

    $ai = null;
    if ( function_exists( 'ql_ia_generate_from_item' ) && function_exists( 'ql_ia_api_key' ) && ql_ia_api_key() !== '' ) {
        $ai = ql_ia_generate_from_item( $found );
    }

    // ── Cas 1 : rédaction IA réussie ──
    if ( is_array( $ai ) ) {
        $note = "<p><em>[Brouillon rédigé par l'IA à partir de la source — à RELIRE, "
              . "vérifier les faits et traiter les points 👤 avant publication.]</em></p>\n";
        $body = wp_kses_post( $note . $ai['body_html'] );

        $post_id = wp_insert_post( array(
            'post_title'   => wp_strip_all_tags( $ai['title'] ),
            'post_content' => $body,
            'post_excerpt' => isset( $ai['excerpt'] ) ? wp_strip_all_tags( $ai['excerpt'] ) : '',
            'post_status'  => 'draft',
            'post_type'    => 'post',
            'post_author'  => $author_id,
        ), true );

        if ( ! is_wp_error( $post_id ) && $post_id ) {
            if ( ! empty( $ai['sources_md'] ) ) {
                update_post_meta( $post_id, '_ql_sources_md', sanitize_textarea_field( $ai['sources_md'] ) );
            }
            if ( ! empty( $ai['primary_category'] ) ) {
                $term = get_term_by( 'slug', sanitize_title( $ai['primary_category'] ), 'category' );
                if ( $term ) { wp_set_post_categories( $post_id, array( (int) $term->term_id ) ); }
            }
            return $post_id;
        }
    }

    // ── Cas 2 : repli brouillon simple (source maigre / IA indispo) ──
    $reason = is_wp_error( $ai ) ? $ai->get_error_message() : '';
    $body  = "<p><em>[Brouillon généré par la veille — à vérifier, réécrire et sourcer avant publication.]</em></p>\n";
    if ( $reason ) {
        $body .= '<p><em>(Rédaction IA non effectuée : ' . esc_html( $reason ) . ' — à rédiger à la main.)</em></p>' . "\n";
    }
    $body .= '<p><strong>Source repérée :</strong> <a href="' . esc_url( $found['link'] ) . '" target="_blank" rel="noopener">'
           . esc_html( $found['title'] . ( $found['source'] ? ' — ' . $found['source'] : '' ) ) . "</a></p>\n";
    $body .= "<p>⚠️ Vérifie les faits, recoupe les sources, écris avec l'angle Quartier Libre, "
           . "puis remplis la fiche sources et ajoute une image à la une.</p>\n";

    return wp_insert_post( array(
        'post_title'   => $found['title'],
        'post_content' => $body,
        'post_status'  => 'draft',
        'post_type'    => 'post',
        'post_author'  => $author_id,
    ), true );
}

/**
 * Rédige automatiquement les N sujets non traités les plus récents.
 * Plafond réglable (coût). Retourne le nombre de brouillons créés.
 */
function ql_veille_autoredac() {
    $max = (int) get_option( 'ql_ia_max_per_run', 2 );
    if ( $max < 1 ) { $max = 1; }

    $items = get_option( 'ql_veille_items', array() );
    if ( ! is_array( $items ) ) { return 0; }

    $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
    $author = ! empty( $admins ) ? (int) $admins[0] : 0;

    $done = 0;
    foreach ( $items as &$it ) {
        if ( $done >= $max ) { break; }
        if ( ! empty( $it['used'] ) ) { continue; }

        $post_id = ql_veille_make_draft( $it, $author );
        $it['used'] = time();
        if ( ! is_wp_error( $post_id ) && $post_id ) { $it['post_id'] = (int) $post_id; }
        $done++;
    }
    unset( $it );

    update_option( 'ql_veille_items', $items, false );
    return $done;
}

// ── Liste des suggestions non encore utilisées ─────────────────
function ql_veille_pending( $limit = 12 ) {
    $items = get_option( 'ql_veille_items', array() );
    if ( ! is_array( $items ) ) { return array(); }
    $out = array_filter( $items, function ( $i ) { return empty( $i['used'] ); } );
    return array_slice( array_values( $out ), 0, $limit );
}

// ── Créer un brouillon à partir d'une suggestion ───────────────
add_action( 'admin_post_ql_veille_draft', function () {
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }
    check_admin_referer( 'ql_veille_draft' );

    $key = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
    $items = get_option( 'ql_veille_items', array() );
    $found = null;
    foreach ( $items as &$it ) {
        if ( $it['key'] === $key ) { $found = $it; $it['used'] = time(); break; }
    }
    unset( $it );
    if ( ! $found ) { wp_safe_redirect( admin_url( 'admin.php?page=ql-dashboard' ) ); exit; }

    $post_id = ql_veille_make_draft( $found, get_current_user_id() );

    update_option( 'ql_veille_items', $items, false );

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ql-dashboard' ) );
        exit;
    }
    wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
    exit;
} );

// ── Ignorer une suggestion ─────────────────────────────────────
add_action( 'admin_post_ql_veille_skip', function () {
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }
    check_admin_referer( 'ql_veille_skip' );
    $key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
    $items = get_option( 'ql_veille_items', array() );
    foreach ( $items as &$it ) {
        if ( $it['key'] === $key ) { $it['used'] = time(); break; }
    }
    unset( $it );
    update_option( 'ql_veille_items', $items, false );
    wp_safe_redirect( admin_url( 'admin.php?page=ql-dashboard' ) );
    exit;
} );

// ── Sous-page admin : réglages de la veille ────────────────────
add_action( 'admin_menu', function () {
    add_submenu_page(
        'ql-dashboard',
        'Robot de veille',
        'Robot de veille',
        'edit_posts',
        'ql-veille',
        'ql_veille_settings_render'
    );
}, 20 );

function ql_veille_settings_render() {
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }

    if ( isset( $_POST['ql_veille_save'] ) && check_admin_referer( 'ql_veille_nonce' ) ) {
        update_option( 'ql_veille_enabled', isset( $_POST['ql_veille_enabled'] ) ? '1' : '0', false );
        update_option( 'ql_veille_queries', sanitize_textarea_field( wp_unslash( $_POST['ql_veille_queries'] ?? '' ) ), false );
        // Réglages rédaction IA (Claude)
        update_option( 'ql_ia_anthropic_key', trim( wp_unslash( $_POST['ql_ia_anthropic_key'] ?? '' ) ), false );
        update_option( 'ql_ia_model', sanitize_text_field( wp_unslash( $_POST['ql_ia_model'] ?? '' ) ), false );
        update_option( 'ql_ia_autoredac', isset( $_POST['ql_ia_autoredac'] ) ? '1' : '0', false );
        update_option( 'ql_ia_max_per_run', max( 1, (int) ( $_POST['ql_ia_max_per_run'] ?? 2 ) ), false );
        echo '<div class="notice notice-success"><p>Réglages de la veille enregistrés.</p></div>';
    }

    if ( isset( $_POST['ql_veille_runnow'] ) && check_admin_referer( 'ql_veille_nonce' ) ) {
        $n = ql_veille_run();
        echo '<div class="notice notice-info"><p>Veille lancée : <strong>' . (int) $n . '</strong> nouveau(x) sujet(s) trouvé(s).</p></div>';
    }

    // Test de connexion à l'IA (sauve d'abord clé + modèle saisis)
    if ( isset( $_POST['ql_ia_test'] ) && check_admin_referer( 'ql_veille_nonce' ) ) {
        if ( isset( $_POST['ql_ia_anthropic_key'] ) ) {
            update_option( 'ql_ia_anthropic_key', trim( wp_unslash( $_POST['ql_ia_anthropic_key'] ) ), false );
        }
        if ( isset( $_POST['ql_ia_model'] ) ) {
            update_option( 'ql_ia_model', sanitize_text_field( wp_unslash( $_POST['ql_ia_model'] ) ), false );
        }
        if ( function_exists( 'ql_ia_call_claude' ) ) {
            $r = ql_ia_call_claude( 'Réponds en un seul mot.', 'Réponds exactement : OK', 20 );
            if ( is_wp_error( $r ) ) {
                echo '<div class="notice notice-error"><p>IA : ' . esc_html( $r->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>IA Claude connectée ✔ (réponse : ' . esc_html( wp_trim_words( $r, 6 ) ) . ')</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Module IA non chargé (includes/redaction-ia.php).</p></div>';
        }
    }

    $enabled  = get_option( 'ql_veille_enabled', '1' ) === '1';
    $queries  = get_option( 'ql_veille_queries', '' );
    if ( $queries === '' ) { $queries = implode( "\n", ql_veille_default_queries() ); }
    $last_run = (int) get_option( 'ql_veille_last_run', 0 );

    $ia_key   = (string) get_option( 'ql_ia_anthropic_key', '' );
    $ia_model = (string) get_option( 'ql_ia_model', '' );
    if ( $ia_model === '' ) { $ia_model = 'claude-sonnet-4-6'; }
    $ia_auto  = get_option( 'ql_ia_autoredac', '1' ) === '1';
    $ia_max   = (int) get_option( 'ql_ia_max_per_run', 2 );
    ?>
    <div class="wrap">
        <h1>🤖 Robot de veille — manifs & faits divers</h1>
        <p style="max-width:760px;color:#555;">
            Le robot interroge <strong>Google Actualités</strong> avec tes requêtes (une par ligne) et
            collecte les sujets récents. Tu les retrouves dans le <a href="<?php echo esc_url( admin_url( 'admin.php?page=ql-dashboard' ) ); ?>">Tableau de bord</a>,
            où tu peux créer un <strong>brouillon</strong> en un clic. Aucune publication automatique.
        </p>

        <form method="post">
            <?php wp_nonce_field( 'ql_veille_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Activer la veille</th>
                    <td><label><input type="checkbox" name="ql_veille_enabled" <?php checked( $enabled ); ?>> Surveiller automatiquement 2×/jour</label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_veille_queries">Requêtes surveillées</label></th>
                    <td>
                        <textarea id="ql_veille_queries" name="ql_veille_queries" rows="6" style="width:560px;max-width:100%;font-family:monospace;"><?php echo esc_textarea( $queries ); ?></textarea>
                        <p class="description">Une requête par ligne. Tu peux utiliser <code>OR</code> (ex : <code>manifestation OR grève Nantes</code>).
                        Ajoute des noms de quartiers (Bellevue, Malakoff…) pour cibler.</p>
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:1.6em;">✍️ Rédaction automatique par IA (Claude)</h2>
            <p style="max-width:760px;color:#555;">
                Si activée, le robot rédige un <strong>brouillon complet à partir de la vraie source</strong>
                (faits, date, analyse au ton QL). <strong>Jamais publié automatiquement</strong> : les points
                à confirmer sont marqués 👤, et la publication reste bloquée tant que tu n'as pas relu.
                ⚠️ Clé API <strong>payante à l'usage</strong>.
            </p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ql_ia_anthropic_key">Clé API Anthropic</label></th>
                    <td>
                        <input type="password" id="ql_ia_anthropic_key" name="ql_ia_anthropic_key"
                               value="<?php echo esc_attr( $ia_key ); ?>" class="regular-text" autocomplete="off"
                               style="width:420px;max-width:100%;" placeholder="sk-ant-...">
                        <p class="description">Crée-la sur <code>console.anthropic.com</code> → API Keys. Rien ne tourne tant qu'elle est vide.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_ia_model">Modèle</label></th>
                    <td>
                        <input type="text" id="ql_ia_model" name="ql_ia_model"
                               value="<?php echo esc_attr( $ia_model ); ?>" class="regular-text">
                        <p class="description"><code>claude-sonnet-4-6</code> (bon équilibre) · <code>claude-haiku-4-5-20251001</code> (moins cher) · <code>claude-opus-4-7</code> (meilleure qualité, plus cher).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Rédaction auto</th>
                    <td>
                        <label><input type="checkbox" name="ql_ia_autoredac" <?php checked( $ia_auto ); ?>>
                            Rédiger automatiquement les nouveaux sujets trouvés (en brouillon)</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_ia_max_per_run">Max par passage</label></th>
                    <td>
                        <input type="number" id="ql_ia_max_per_run" name="ql_ia_max_per_run" min="1" max="20"
                               value="<?php echo esc_attr( $ia_max ); ?>" class="small-text">
                        <p class="description">Nombre de brouillons rédigés à chaque passage du robot (contrôle le coût). La veille tourne 2×/jour.</p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="ql_veille_save" class="button button-primary">Enregistrer</button>
                <button type="submit" name="ql_veille_runnow" class="button" style="margin-left:8px;">Lancer la veille maintenant</button>
                <button type="submit" name="ql_ia_test" class="button">Tester l'IA</button>
            </p>
        </form>

        <?php if ( $last_run ) : ?>
            <p style="color:#666;">Dernière veille : <strong><?php echo esc_html( date_i18n( 'd/m/Y H:i', $last_run ) ); ?></strong>
               (<?php echo esc_html( human_time_diff( $last_run ) ); ?>)</p>
        <?php endif; ?>
    </div>
    <?php
}

// ── Rendu du panneau de suggestions (utilisé par le dashboard) ─
function ql_veille_render_panel() {
    $pending = ql_veille_pending( 40 );
    ?>
    <div style="background:#fffdf0;border:1px solid #e6d56a;border-radius:8px;padding:22px;margin-top:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h2 style="margin:0;">🤖 Robot de veille — sujets repérés</h2>
            <a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=ql-veille' ) ); ?>">Réglages</a>
        </div>
        <?php if ( empty( $pending ) ) : ?>
            <p style="margin:.8em 0 0;color:#665c00;">
                Aucun sujet pour l'instant. Va dans
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ql-veille' ) ); ?>">Réglages</a>
                et clique « Lancer la veille maintenant ».
            </p>
        <?php else : ?>
            <ul style="list-style:none;margin:14px 0 0;padding:0;display:flex;flex-direction:column;gap:10px;">
                <?php foreach ( $pending as $it ) :
                    $draft_url = wp_nonce_url( admin_url( 'admin-post.php?action=ql_veille_draft&key=' . rawurlencode( $it['key'] ) ), 'ql_veille_draft' );
                    $skip_url  = wp_nonce_url( admin_url( 'admin-post.php?action=ql_veille_skip&key=' . rawurlencode( $it['key'] ) ), 'ql_veille_skip' );
                ?>
                    <li style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border:1px solid #eee;border-radius:6px;">
                        <div style="flex:1;min-width:0;">
                            <a href="<?php echo esc_url( $it['link'] ); ?>" target="_blank" rel="noopener" style="font-weight:700;text-decoration:none;color:#1d2327;">
                                <?php echo esc_html( $it['title'] ); ?>
                            </a>
                            <div style="font-size:.82em;color:#777;margin-top:2px;">
                                <?php echo esc_html( $it['source'] ?: 'Source web' ); ?> ·
                                <?php echo esc_html( human_time_diff( $it['date'] ) ); ?>
                            </div>
                        </div>
                        <a class="button button-primary button-small" href="<?php echo esc_url( $draft_url ); ?>">Créer un brouillon</a>
                        <a class="button button-small" href="<?php echo esc_url( $skip_url ); ?>" title="Ignorer">✕</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}
