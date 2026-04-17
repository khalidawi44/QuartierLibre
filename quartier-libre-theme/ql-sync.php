<?php
/**
 * Quartier Libre — Sync GitHub (admin page)
 *
 * Page admin : Outils → Sync Quartier Libre
 * Un clic = télécharge tous les fichiers du thème depuis GitHub.
 *
 * Pattern inspiré du ag-import.php d'Alliance Groupe.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( defined( 'QL_SYNC_LOADED' ) ) return;
define( 'QL_SYNC_LOADED', true );

// ── Configuration du repo GitHub ────────────────────────────────
define( 'QL_GH_OWNER',  'khalidawi44' );
define( 'QL_GH_REPO',   'QuartierLibre' );
define( 'QL_GH_BRANCH', 'main' );
define( 'QL_GH_THEME_PATH', 'quartier-libre-theme' );

// ── Menu admin ──────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_management_page(
        'Sync Quartier Libre',
        'Sync QL',
        'manage_options',
        'ql-sync',
        'ql_sync_render'
    );
} );

// ── Page admin ──────────────────────────────────────────────────
function ql_sync_render() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Non.' );

    $last = (int) get_option( 'ql_last_sync', 0 );

    echo '<div class="wrap"><h1>Quartier Libre — Synchronisation GitHub</h1>';

    // Action : sync
    if ( isset( $_POST['ql_go'] ) && check_admin_referer( 'ql_sync_nonce' ) ) {
        if ( $last && ( time() - $last ) < 60 ) {
            echo '<div class="notice notice-error"><p>Patientez 1 minute entre deux synchros.</p></div>';
        } else {
            ql_do_github_sync();
            update_option( 'ql_last_sync', time() );
        }
    }

    // Action : clear logs
    if ( isset( $_POST['ql_clear'] ) && check_admin_referer( 'ql_clear_nonce' ) ) {
        delete_option( 'ql_sync_log' );
        echo '<div class="notice notice-success"><p>Journal vidé.</p></div>';
    }

    ?>
    <style>
        .ql-sync-card {
            background: linear-gradient(135deg, #fafaf7 0%, #f1efe8 100%);
            padding: 32px; border: 2px solid #e63312;
            border-radius: 10px; margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
        }
        .ql-sync-card h2 {
            margin-top: 0; color: #0f0f0f;
            font-size: 1.6rem;
        }
        .ql-sync-card p { color: #444; font-size: 1rem; line-height: 1.6; }
        .ql-sync-card .ql-sync-btn {
            background: #e63312 !important; border-color: #e63312 !important;
            color: #fff !important;
            padding: 14px 32px !important;
            font-size: 16px !important; font-weight: 700 !important;
            height: auto !important; line-height: 1.2 !important;
            border-radius: 4px !important;
            text-transform: uppercase; letter-spacing: .03em;
        }
        .ql-sync-btn:hover { background: #b5250a !important; border-color: #b5250a !important; }
        .ql-sync-meta { color: #5a5a5a; font-size: .9rem; margin-top: 1rem; }
        .ql-sync-meta code {
            background: #0f0f0f; color: #fafaf7;
            padding: 2px 6px; border-radius: 3px; font-size: .8rem;
        }
    </style>

    <div class="ql-sync-card">
        <h2>Synchroniser le thème depuis GitHub</h2>
        <p>
            Télécharge <strong>tous les fichiers</strong> du thème Quartier Libre depuis
            le dépôt GitHub et les écrit sur le serveur. Utile quand vous avez
            modifié le thème en local et poussé sur GitHub : un clic ici et c'est
            en ligne, pas besoin d'FTP.
        </p>

        <form method="post">
            <?php wp_nonce_field( 'ql_sync_nonce' ); ?>
            <input type="submit" name="ql_go" class="button button-primary button-hero ql-sync-btn" value="Synchroniser maintenant">
        </form>

        <div class="ql-sync-meta">
            <p>
                Repo : <code><?php echo esc_html( QL_GH_OWNER . '/' . QL_GH_REPO ); ?></code> ·
                Branche : <code><?php echo esc_html( QL_GH_BRANCH ); ?></code> ·
                Source : <code><?php echo esc_html( QL_GH_THEME_PATH ); ?>/</code>
            </p>
            <?php if ( $last ) : ?>
                <p>Dernière synchro : <strong><?php echo esc_html( date_i18n( 'd/m/Y H:i', $last ) ); ?></strong>
                   (<?php echo esc_html( human_time_diff( $last ) ); ?>)</p>
            <?php else : ?>
                <p><em>Aucune synchronisation effectuée pour l'instant.</em></p>
            <?php endif; ?>
            <p>
                <a href="https://github.com/<?php echo esc_attr( QL_GH_OWNER . '/' . QL_GH_REPO ); ?>" target="_blank" rel="noopener">
                    Voir le repo sur GitHub →
                </a>
            </p>
        </div>
    </div>

    <?php
    // Journal
    $logs = get_option( 'ql_sync_log', array() );
    if ( ! empty( $logs ) ) {
        echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:4px;margin:20px 0;">';
        echo '<div style="display:flex;justify-content:space-between;align-items:center;">';
        echo '<h3 style="margin:0;">Journal des synchronisations</h3>';
        echo '<form method="post">';
        wp_nonce_field( 'ql_clear_nonce' );
        echo '<button type="submit" name="ql_clear" class="button button-small">Vider le journal</button>';
        echo '</form></div>';
        echo '<table class="widefat striped" style="margin-top:12px;"><tbody>';
        foreach ( array_reverse( $logs ) as $l ) {
            echo '<tr><td style="width:160px;">' . esc_html( $l['d'] ) . '</td><td>' . esc_html( $l['m'] ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    echo '</div>';
}

// ── Log helper ──────────────────────────────────────────────────
function ql_log_msg( $msg ) {
    $logs   = get_option( 'ql_sync_log', array() );
    $logs[] = array( 'd' => date_i18n( 'd/m/Y H:i:s' ), 'm' => $msg );
    if ( count( $logs ) > 60 ) { $logs = array_slice( $logs, -60 ); }
    update_option( 'ql_sync_log', $logs, false );
}

// ── API GitHub : lister récursivement les fichiers ─────────────
function ql_gh_list_files( $path = '' ) {
    $url = sprintf(
        'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
        QL_GH_OWNER, QL_GH_REPO,
        rawurlencode( QL_GH_THEME_PATH . ( $path ? '/' . $path : '' ) ),
        QL_GH_BRANCH
    );
    // Nettoyage : rawurlencode met des %2F sur les /, on les remet
    $url = str_replace( '%2F', '/', $url );

    $resp = wp_remote_get( $url, array(
        'timeout'   => 20,
        'sslverify' => true,
        'headers'   => array(
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'QuartierLibre-Sync/1.0',
        ),
    ) );

    if ( is_wp_error( $resp ) ) return false;
    $code = wp_remote_retrieve_response_code( $resp );
    if ( $code !== 200 ) return false;

    $data = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $data ) ) return false;

    $files = array();
    foreach ( $data as $item ) {
        if ( $item['type'] === 'file' ) {
            $rel = ltrim( substr( $item['path'], strlen( QL_GH_THEME_PATH ) ), '/' );
            $files[] = array(
                'path'     => $rel,                    // relatif au thème
                'size'     => (int) $item['size'],
                'download' => $item['download_url'],   // URL raw
            );
        } elseif ( $item['type'] === 'dir' ) {
            $sub = $path ? $path . '/' . $item['name'] : $item['name'];
            $subfiles = ql_gh_list_files( $sub );
            if ( is_array( $subfiles ) ) {
                $files = array_merge( $files, $subfiles );
            }
        }
    }
    return $files;
}

// ── Fetch raw file depuis GitHub ────────────────────────────────
function ql_gh_raw( $url ) {
    $resp = wp_remote_get( $url, array(
        'timeout'   => 60,
        'sslverify' => true,
        'headers'   => array( 'User-Agent' => 'QuartierLibre-Sync/1.0' ),
    ) );
    if ( is_wp_error( $resp ) ) return false;
    if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) return false;
    return wp_remote_retrieve_body( $resp );
}

// ── Sync principal ──────────────────────────────────────────────
function ql_do_github_sync() {
    $theme_dir = get_stylesheet_directory();

    // 1. Lister les fichiers via API GitHub
    $files = ql_gh_list_files();
    if ( ! is_array( $files ) || empty( $files ) ) {
        echo '<div class="notice notice-error"><p><strong>Impossible de lister les fichiers</strong> depuis GitHub. '
           . 'Vérifiez que le repo <code>' . esc_html( QL_GH_OWNER . '/' . QL_GH_REPO ) . '</code> '
           . 'existe et qu\'il est <strong>public</strong> (ou que l\'API GitHub n\'a pas atteint sa limite horaire).</p></div>';
        ql_log_msg( 'ERREUR: impossible de lister les fichiers GitHub' );
        return;
    }

    $ok = 0; $fail = 0; $skipped = 0;
    $self_basename = basename( __FILE__ );
    $self_queued   = false;
    $self_content  = null;

    // 2. Télécharger chaque fichier
    foreach ( $files as $f ) {
        $rel = $f['path'];

        // Sécurité : empêcher path traversal
        if ( strpos( $rel, '..' ) !== false || preg_match( '#^(/|[a-zA-Z]:)#', $rel ) ) {
            $skipped++;
            continue;
        }

        $local = $theme_dir . '/' . $rel;
        $dir   = dirname( $local );
        if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }

        $content = ql_gh_raw( $f['download'] );
        if ( $content === false ) {
            $fail++;
            continue;
        }

        // ql-sync.php lui-même : on le met à jour en dernier (évite de couper la sync)
        if ( basename( $rel ) === $self_basename ) {
            $self_queued  = $local;
            $self_content = $content;
            continue;
        }

        if ( file_put_contents( $local, $content ) !== false ) {
            $ok++;
        } else {
            $fail++;
        }
    }

    // 3. Auto-update de ql-sync.php en dernier
    if ( $self_queued && $self_content !== null ) {
        file_put_contents( $self_queued, $self_content );
        $ok++;
    }

    // 4. Résumé
    $msg = sprintf(
        '%d fichier%s mis à jour · %d échec%s · %d ignoré%s',
        $ok, $ok > 1 ? 's' : '',
        $fail, $fail > 1 ? 's' : '',
        $skipped, $skipped > 1 ? 's' : ''
    );

    if ( $fail === 0 ) {
        echo '<div class="notice notice-success"><p><strong>Synchronisation terminée.</strong> ' . esc_html( $msg ) . '</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p><strong>Synchronisation partielle.</strong> ' . esc_html( $msg ) . '</p></div>';
    }

    ql_log_msg( $msg );

    // 5. Invalider caches OPcache / objets
    if ( function_exists( 'opcache_reset' ) ) {
        @opcache_reset();
        ql_log_msg( 'OPcache vidé' );
    }
    wp_cache_flush();
}
