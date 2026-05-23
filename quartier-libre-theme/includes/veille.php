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
        'manifestation OR rassemblement OR grève Nantes',
        'logement social OR HLM OR expulsion Nantes',
        'police OR contrôle au faciès OR sécurité quartiers Nantes',
        'mairie OR métropole OR transports OR Keolis Nantes',
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

        $max  = $feed->get_item_quantity( 8 );
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

            $items[] = array(
                'key'    => $key,
                'title'  => $title,
                'link'   => $link,
                'source' => $source,
                'query'  => $query,
                'date'   => $date ? (int) $date : time(),
                'found'  => time(),
                'used'   => 0,
            );
            $known[ $key ] = true;
            $added++;
        }
    }

    // Tri par date décroissante, plafond à 60 entrées
    usort( $items, function ( $a, $b ) { return $b['date'] <=> $a['date']; } );
    if ( count( $items ) > 60 ) { $items = array_slice( $items, 0, 60 ); }

    update_option( 'ql_veille_items', $items, false );
    update_option( 'ql_veille_last_run', time(), false );
    return $added;
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

    $body  = "<p><em>[Brouillon généré par la veille — à vérifier, réécrire et sourcer avant publication.]</em></p>\n";
    $body .= '<p><strong>Source repérée :</strong> <a href="' . esc_url( $found['link'] ) . '" target="_blank" rel="noopener">'
           . esc_html( $found['title'] . ( $found['source'] ? ' — ' . $found['source'] : '' ) ) . "</a></p>\n";
    $body .= "<p>⚠️ Vérifie les faits, recoupe les sources, écris avec l'angle Quartier Libre (par nous, pour nous), "
           . "puis remplis la fiche sources et ajoute une image à la une.</p>\n";

    $post_id = wp_insert_post( array(
        'post_title'   => $found['title'],
        'post_content' => $body,
        'post_status'  => 'draft',
        'post_type'    => 'post',
        'post_author'  => get_current_user_id(),
    ), true );

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
        echo '<div class="notice notice-success"><p>Réglages de la veille enregistrés.</p></div>';
    }

    if ( isset( $_POST['ql_veille_runnow'] ) && check_admin_referer( 'ql_veille_nonce' ) ) {
        $n = ql_veille_run();
        echo '<div class="notice notice-info"><p>Veille lancée : <strong>' . (int) $n . '</strong> nouveau(x) sujet(s) trouvé(s).</p></div>';
    }

    $enabled  = get_option( 'ql_veille_enabled', '1' ) === '1';
    $queries  = get_option( 'ql_veille_queries', '' );
    if ( $queries === '' ) { $queries = implode( "\n", ql_veille_default_queries() ); }
    $last_run = (int) get_option( 'ql_veille_last_run', 0 );
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
            <p>
                <button type="submit" name="ql_veille_save" class="button button-primary">Enregistrer</button>
                <button type="submit" name="ql_veille_runnow" class="button" style="margin-left:8px;">Lancer la veille maintenant</button>
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
    $pending = ql_veille_pending( 12 );
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
