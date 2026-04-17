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
define( 'QL_GH_THEME_PATH',    'quartier-libre-theme' );
define( 'QL_GH_CONTENT_PATH',  'content' );
define( 'QL_GH_ARTICLES_PATH', 'content/articles' );
define( 'QL_GH_MEDIA_PATH',    'content/media' );

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
    global $wpdb;

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

    if ( isset( $_POST['ql_go_content'] ) && check_admin_referer( 'ql_content_nonce' ) ) {
        ql_do_content_sync();
    }

    // Action : preview du nettoyage (sans modification)
    if ( isset( $_POST['ql_preview_clean'] ) && check_admin_referer( 'ql_preview_clean_nonce' ) ) {
        ql_do_preview_clean();
    }

    // Action : nettoyer les articles (strip Kadence/Blockspare/shape-dividers)
    if ( isset( $_POST['ql_clean_articles'] ) && check_admin_referer( 'ql_clean_articles_nonce' ) ) {
        ql_do_clean_articles();
    }

    // Action : restaurer les articles depuis le backup
    if ( isset( $_POST['ql_restore_articles'] ) && check_admin_referer( 'ql_restore_articles_nonce' ) ) {
        ql_do_restore_articles();
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

        <form method="post" style="display:inline-block;">
            <?php wp_nonce_field( 'ql_sync_nonce' ); ?>
            <input type="submit" name="ql_go" class="button button-primary button-hero ql-sync-btn" value="Synchroniser le thème">
        </form>

        <form method="post" style="display:inline-block;margin-left:12px;">
            <?php wp_nonce_field( 'ql_content_nonce' ); ?>
            <input type="submit" name="ql_go_content" class="button button-primary button-hero ql-sync-btn" style="background:#0f0f0f !important;border-color:#0f0f0f !important;" value="Synchroniser les articles (.md)">
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
    // Carte nettoyage articles
    $backup_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ql_content_backup'" );
    ?>
    <div class="ql-sync-card" style="border-color:#0f0f0f;background:linear-gradient(135deg, #fff8e1 0%, #ffe5df 100%);">
        <h2>Nettoyer les parasites dans les articles (mode SAFE)</h2>
        <p>
            <strong>Version corrigée</strong> : ne touche qu'aux commentaires Gutenberg
            <code>&lt;!-- wp:kadence/... --&gt;</code> et aux styles inline sur h1-h6.
            N'altère <strong>jamais</strong> les balises <code>&lt;div&gt;</code>
            (pour ne pas casser les structures imbriquées).
            Le masquage visuel des widgets décoratifs (progressbar, countdown, shape-divider)
            est désormais fait en CSS, pas en retirant le HTML.
        </p>
        <p>
            <strong>👉 Conseil</strong> : cliquer d'abord sur <em>Prévisualiser</em> pour voir
            ce qui serait modifié, AVANT de lancer le nettoyage en base.
        </p>

        <form method="post" style="display:inline-block;">
            <?php wp_nonce_field( 'ql_preview_clean_nonce' ); ?>
            <input type="submit" name="ql_preview_clean" class="button button-secondary" style="padding:10px 18px;height:auto;" value="👁 Prévisualiser (dry-run)">
        </form>

        <form method="post" style="display:inline-block;margin-left:12px;" onsubmit="return confirm('Modifier en base TOUS les articles ? (backup automatique)');">
            <?php wp_nonce_field( 'ql_clean_articles_nonce' ); ?>
            <input type="submit" name="ql_clean_articles" class="button button-primary button-hero ql-sync-btn" style="background:#0f0f0f !important;border-color:#0f0f0f !important;" value="Nettoyer tous les articles">
        </form>

        <?php if ( $backup_count > 0 ) : ?>
            <form method="post" style="display:inline-block;margin-left:12px;" onsubmit="return confirm('Restaurer les <?php echo (int) $backup_count; ?> articles depuis leur backup ?');">
                <?php wp_nonce_field( 'ql_restore_articles_nonce' ); ?>
                <input type="submit" name="ql_restore_articles" class="button button-secondary" style="padding:10px 18px;height:auto;background:#2e8a3d;color:#fff;border-color:#2e8a3d;" value="↺ Restaurer depuis backup">
            </form>
        <?php endif; ?>

        <div class="ql-sync-meta">
            <p>
                Backup présent pour <strong><?php echo (int) $backup_count; ?></strong> article(s).
                Chaque article n'est sauvegardé qu'une fois (le premier nettoyage).
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

// ════════════════════════════════════════════════════════════════
//  SYNC DE CONTENU — articles Markdown → WordPress posts
// ════════════════════════════════════════════════════════════════

function ql_do_content_sync() {
    $files = ql_gh_list_content_files( QL_GH_ARTICLES_PATH );
    if ( ! is_array( $files ) ) {
        echo '<div class="notice notice-error"><p>Impossible de lister le dossier <code>content/articles/</code> sur GitHub.</p></div>';
        return;
    }

    $md_files = array_filter( $files, function ( $f ) {
        return substr( $f['path'], -3 ) === '.md' && basename( $f['path'] ) !== 'README.md';
    } );

    if ( empty( $md_files ) ) {
        echo '<div class="notice notice-warning"><p>Aucun fichier <code>.md</code> trouvé dans <code>content/articles/</code>.</p></div>';
        return;
    }

    $created = 0; $updated = 0; $images = 0; $errors = 0;

    foreach ( $md_files as $f ) {
        $raw = ql_gh_raw( $f['download'] );
        if ( $raw === false ) { $errors++; continue; }

        $parsed = ql_parse_frontmatter( $raw );
        if ( empty( $parsed['front']['title'] ) ) {
            ql_log_msg( 'SKIP: ' . basename( $f['path'] ) . ' — pas de title' );
            $errors++;
            continue;
        }

        $result = ql_upsert_article( $parsed['front'], $parsed['body'], $images );
        if ( $result === 'created' ) { $created++; }
        elseif ( $result === 'updated' ) { $updated++; }
        else { $errors++; }
    }

    $msg = sprintf(
        '%d article%s créé · %d mis à jour · %d image%s · %d erreur%s',
        $created, $created > 1 ? 's' : '',
        $updated,
        $images, $images > 1 ? 's' : '',
        $errors, $errors > 1 ? 's' : ''
    );

    $class = $errors ? 'notice-warning' : 'notice-success';
    echo '<div class="notice ' . esc_attr( $class ) . '"><p><strong>Sync contenu.</strong> ' . esc_html( $msg ) . '</p></div>';
    ql_log_msg( 'Contenu: ' . $msg );
}

// ── Lister récursivement des fichiers dans un chemin donné ────
function ql_gh_list_content_files( $path ) {
    $url = sprintf(
        'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
        QL_GH_OWNER, QL_GH_REPO, rawurlencode( $path ), QL_GH_BRANCH
    );
    $url = str_replace( '%2F', '/', $url );

    $resp = wp_remote_get( $url, array(
        'timeout' => 20, 'sslverify' => true,
        'headers' => array(
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'QuartierLibre-Sync/1.0',
        ),
    ) );
    if ( is_wp_error( $resp ) ) return false;
    if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) return false;

    $data = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $data ) ) return false;

    $files = array();
    foreach ( $data as $item ) {
        if ( $item['type'] === 'file' ) {
            $files[] = array(
                'path' => $item['path'], 'name' => $item['name'],
                'size' => (int) $item['size'], 'download' => $item['download_url'],
            );
        } elseif ( $item['type'] === 'dir' ) {
            $sub = ql_gh_list_content_files( $item['path'] );
            if ( is_array( $sub ) ) { $files = array_merge( $files, $sub ); }
        }
    }
    return $files;
}

// ── YAML frontmatter + body ───────────────────────────────────
function ql_parse_frontmatter( $raw ) {
    $raw = preg_replace( "/^\xEF\xBB\xBF/", '', $raw );
    $raw = str_replace( "\r\n", "\n", $raw );
    $front = array(); $body = $raw;
    if ( strpos( $raw, "---\n" ) === 0 ) {
        $end = strpos( $raw, "\n---\n", 4 );
        if ( $end !== false ) {
            $yaml = substr( $raw, 4, $end - 4 );
            $body = substr( $raw, $end + 5 );
            $front = ql_parse_simple_yaml( $yaml );
        }
    }
    return array( 'front' => $front, 'body' => $body );
}
function ql_parse_simple_yaml( $yaml ) {
    $out = array(); $current = null;
    foreach ( explode( "\n", $yaml ) as $line ) {
        if ( trim( $line ) === '' || ( isset( $line[0] ) && $line[0] === '#' ) ) continue;
        if ( preg_match( '/^\s*-\s+(.+)$/', $line, $m ) && $current ) {
            if ( ! is_array( $out[ $current ] ?? null ) ) $out[ $current ] = array();
            $out[ $current ][] = ql_yaml_unquote( trim( $m[1] ) );
            continue;
        }
        if ( preg_match( '/^([a-zA-Z0-9_\-]+)\s*:\s*(.*)$/', $line, $m ) ) {
            $key = $m[1]; $val = trim( $m[2] );
            if ( $val === '' ) { $out[ $key ] = array(); $current = $key; }
            else { $out[ $key ] = ql_yaml_unquote( $val ); $current = $key; }
        }
    }
    return $out;
}
function ql_yaml_unquote( $s ) {
    if ( preg_match( '/^"(.*)"$/', $s, $m ) ) return $m[1];
    if ( preg_match( "/^'(.*)'$/", $s, $m ) ) return $m[1];
    return $s;
}

// ── Créer / mettre à jour un article ──────────────────────────
function ql_upsert_article( $front, $body_md, &$images_count ) {
    $slug = ! empty( $front['slug'] ) ? sanitize_title( $front['slug'] ) : sanitize_title( $front['title'] );

    $existing = get_posts( array(
        'name'           => $slug,
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
        'posts_per_page' => 1,
    ) );

    $thumb_id = 0;
    if ( ! empty( $front['featured_image'] ) ) {
        $thumb_id = ql_upload_image_from_repo( $front['featured_image'], $images_count );
    }

    $body_html = ql_markdown_to_html( $body_md, $images_count );

    $postarr = array(
        'post_title'   => $front['title'],
        'post_name'    => $slug,
        'post_content' => $body_html,
        'post_excerpt' => isset( $front['excerpt'] ) ? $front['excerpt'] : '',
        'post_status'  => ( isset( $front['status'] ) && $front['status'] === 'draft' ) ? 'draft' : 'publish',
        'post_type'    => 'post',
    );

    if ( ! empty( $front['date'] ) ) {
        $ts = strtotime( $front['date'] );
        if ( $ts ) {
            $postarr['post_date']     = date( 'Y-m-d H:i:s', $ts );
            $postarr['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $ts );
        }
    }

    if ( ! empty( $front['author'] ) ) {
        $user = get_user_by( 'login', $front['author'] );
        if ( $user ) $postarr['post_author'] = $user->ID;
    }

    if ( ! empty( $existing ) ) {
        $postarr['ID'] = $existing[0]->ID;
        $post_id = wp_update_post( $postarr, true );
        $action = 'updated';
    } else {
        $post_id = wp_insert_post( $postarr, true );
        $action = 'created';
    }
    if ( is_wp_error( $post_id ) || ! $post_id ) return false;

    if ( ! empty( $front['category'] ) ) {
        $slug_c = sanitize_title( $front['category'] );
        $cat = get_term_by( 'slug', $slug_c, 'category' );
        if ( ! $cat ) {
            $r = wp_insert_term( $front['category'], 'category', array( 'slug' => $slug_c ) );
            $cat_id = ! is_wp_error( $r ) ? $r['term_id'] : 0;
        } else {
            $cat_id = $cat->term_id;
        }
        if ( ! empty( $cat_id ) ) wp_set_post_categories( $post_id, array( (int) $cat_id ) );
    }

    if ( ! empty( $front['tags'] ) && is_array( $front['tags'] ) ) {
        wp_set_post_tags( $post_id, $front['tags'], false );
    }

    if ( $thumb_id ) set_post_thumbnail( $post_id, $thumb_id );

    // Source externe (ex. pour republier un article de Contre-Attaque, etc.)
    if ( ! empty( $front['source_name'] ) ) {
        update_post_meta( $post_id, '_ql_source_name', sanitize_text_field( $front['source_name'] ) );
    } else {
        delete_post_meta( $post_id, '_ql_source_name' );
    }
    if ( ! empty( $front['source_url'] ) ) {
        update_post_meta( $post_id, '_ql_source_url', esc_url_raw( $front['source_url'] ) );
    } else {
        delete_post_meta( $post_id, '_ql_source_url' );
    }

    update_post_meta( $post_id, '_ql_synced', 1 );
    update_post_meta( $post_id, '_ql_sync_at', time() );

    return $action;
}

// ── Upload d'une image depuis le repo (dédupliquée) ───────────
function ql_upload_image_from_repo( $repo_path, &$count ) {
    $repo_path = ltrim( str_replace( '\\', '/', $repo_path ), '/' );
    $hash_key  = '_ql_media_' . md5( $repo_path );

    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'meta_key'       => $hash_key,
        'posts_per_page' => 1,
    ) );
    if ( $existing ) return $existing[0]->ID;

    $raw_url = sprintf(
        'https://raw.githubusercontent.com/%s/%s/%s/%s',
        QL_GH_OWNER, QL_GH_REPO, QL_GH_BRANCH, $repo_path
    );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( $raw_url, 60 );
    if ( is_wp_error( $tmp ) ) return 0;

    $file_array = array( 'name' => basename( $repo_path ), 'tmp_name' => $tmp );
    $attach_id = media_handle_sideload( $file_array, 0 );
    if ( is_wp_error( $attach_id ) ) { @unlink( $tmp ); return 0; }

    update_post_meta( $attach_id, $hash_key, 1 );
    update_post_meta( $attach_id, '_ql_media_source', $repo_path );
    $count++;
    return $attach_id;
}

// ── Markdown → HTML (parser minimal) ──────────────────────────
function ql_markdown_to_html( $md, &$images_count ) {
    // Images content/media/... → URL médiathèque
    $md = preg_replace_callback(
        '/!\[([^\]]*)\]\((content\/media\/[^\)]+)\)/',
        function ( $m ) use ( &$images_count ) {
            $id = ql_upload_image_from_repo( $m[2], $images_count );
            if ( $id ) {
                $url = wp_get_attachment_url( $id );
                return '![' . $m[1] . '](' . $url . ')';
            }
            return $m[0];
        }, $md
    );

    $md = str_replace( "\r\n", "\n", trim( $md ) );
    $out = array();
    $in_ul = false; $in_ol = false; $in_quote = false;

    $flush = function() use ( &$out, &$in_ul, &$in_ol, &$in_quote ) {
        if ( $in_ul ) { $out[] = '</ul>'; $in_ul = false; }
        if ( $in_ol ) { $out[] = '</ol>'; $in_ol = false; }
        if ( $in_quote ) { $out[] = '</blockquote>'; $in_quote = false; }
    };

    $inline = function ( $line ) {
        $line = preg_replace_callback( '/!\[([^\]]*)\]\(([^\)]+)\)/', function ( $m ) {
            $caption = $m[1] ? '<figcaption>' . esc_html( $m[1] ) . '</figcaption>' : '';
            return '<figure><img src="' . esc_url( $m[2] ) . '" alt="' . esc_attr( $m[1] ) . '" loading="lazy" decoding="async">' . $caption . '</figure>';
        }, $line );
        $line = preg_replace_callback( '/\[([^\]]+)\]\(([^\)]+)\)/', function ( $m ) {
            $ext = preg_match( '#^https?://#', $m[2] ) && strpos( $m[2], home_url() ) === false;
            $attrs = $ext ? ' target="_blank" rel="noopener"' : '';
            return '<a href="' . esc_url( $m[2] ) . '"' . $attrs . '>' . esc_html( $m[1] ) . '</a>';
        }, $line );
        $line = preg_replace( '/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $line );
        $line = preg_replace( '/\*([^\*\n]+)\*/', '<em>$1</em>', $line );
        return $line;
    };

    foreach ( explode( "\n", $md ) as $line ) {
        if ( trim( $line ) === '' ) { $flush(); continue; }
        if ( preg_match( '/^(#{2,6})\s+(.+)$/', $line, $m ) ) {
            $flush();
            $lvl = strlen( $m[1] );
            $out[] = '<h' . $lvl . '>' . $inline( trim( $m[2] ) ) . '</h' . $lvl . '>';
            continue;
        }
        if ( preg_match( '/^-{3,}$/', trim( $line ) ) ) { $flush(); $out[] = '<hr>'; continue; }
        if ( preg_match( '/^>\s?(.*)$/', $line, $m ) ) {
            if ( $in_ul ) { $out[] = '</ul>'; $in_ul = false; }
            if ( $in_ol ) { $out[] = '</ol>'; $in_ol = false; }
            if ( ! $in_quote ) { $out[] = '<blockquote>'; $in_quote = true; }
            $out[] = '<p>' . $inline( $m[1] ) . '</p>';
            continue;
        }
        if ( $in_quote ) { $out[] = '</blockquote>'; $in_quote = false; }
        if ( preg_match( '/^[-*+]\s+(.+)$/', $line, $m ) ) {
            if ( $in_ol ) { $out[] = '</ol>'; $in_ol = false; }
            if ( ! $in_ul ) { $out[] = '<ul>'; $in_ul = true; }
            $out[] = '<li>' . $inline( $m[1] ) . '</li>';
            continue;
        }
        if ( preg_match( '/^\d+\.\s+(.+)$/', $line, $m ) ) {
            if ( $in_ul ) { $out[] = '</ul>'; $in_ul = false; }
            if ( ! $in_ol ) { $out[] = '<ol>'; $in_ol = true; }
            $out[] = '<li>' . $inline( $m[1] ) . '</li>';
            continue;
        }
        $flush();
        $out[] = '<p>' . $inline( $line ) . '</p>';
    }
    $flush();
    return implode( "\n", $out );
}


// ════════════════════════════════════════════════════════════════
//  BULK CLEAN ARTICLES — strip Kadence/Blockspare/shape-dividers
// ════════════════════════════════════════════════════════════════

function ql_do_clean_articles() {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
        'fields'         => 'all',
    ) );

    if ( empty( $posts ) ) {
        echo '<div class="notice notice-warning"><p>Aucun article trouvé.</p></div>';
        return;
    }

    $scanned = 0; $changed = 0; $backed_up = 0;
    foreach ( $posts as $post ) {
        $scanned++;
        $original = $post->post_content;

        // Backup une seule fois (pour pouvoir restaurer)
        if ( ! get_post_meta( $post->ID, '_ql_content_backup', true ) ) {
            update_post_meta( $post->ID, '_ql_content_backup', $original );
            $backed_up++;
        }

        $cleaned = ql_clean_article_content( $original );

        if ( $cleaned !== $original ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->posts,
                array( 'post_content' => $cleaned ),
                array( 'ID' => $post->ID )
            );
            clean_post_cache( $post->ID );
            $changed++;
        }
    }

    $msg = sprintf( '%d articles scannés · %d nettoyés · %d backups créés', $scanned, $changed, $backed_up );
    echo '<div class="notice notice-success"><p><strong>Nettoyage terminé.</strong> ' . esc_html( $msg ) . '</p></div>';
    ql_log_msg( 'Clean articles: ' . $msg );

    wp_cache_flush();
    if ( function_exists( 'opcache_reset' ) ) { @opcache_reset(); }
}

function ql_do_restore_articles() {
    global $wpdb;
    $metas = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_ql_content_backup'"
    );

    $restored = 0;
    foreach ( $metas as $m ) {
        $wpdb->update(
            $wpdb->posts,
            array( 'post_content' => $m->meta_value ),
            array( 'ID' => (int) $m->post_id )
        );
        clean_post_cache( (int) $m->post_id );
        $restored++;
    }

    echo '<div class="notice notice-success"><p><strong>Restauration.</strong> ' . (int) $restored . ' articles restaurés depuis leur backup.</p></div>';
    ql_log_msg( 'Restore articles: ' . $restored );
    wp_cache_flush();
}

/**
 * Nettoie le contenu d'un article : retire Kadence, Blockspare,
 * shape-dividers, styles inline casseurs, tout en conservant le texte
 * et les images.
 */

/**
 * ═══════════════════════════════════════════════════════════════
 * CLEANER SAFE — ne touche JAMAIS aux <div>, uniquement :
 *   - commentaires Gutenberg <!-- wp:kadence/... -->
 *   - shortcodes orphelins
 *   - styles inline sur h1-h6
 *   - classes kadence-* / blockspare-* sur h1-h6
 * Le masquage visuel des widgets (progressbar, countdown, shape-divider)
 * est fait en CSS (plus sûr que regex HTML imbriqué).
 * ═══════════════════════════════════════════════════════════════
 */
function ql_clean_article_content( $content ) {
    if ( ! is_string( $content ) || $content === '' ) { return $content; }

    $original = $content;

    // 1. Retirer UNIQUEMENT les commentaires de blocs Kadence/Blockspare
    //    (garder toutes les <div> et leur contenu intacts)
    $content = preg_replace(
        '#<!--\s*/?wp:(?:kadence|blockspare|kb)/[^>]*-->#is',
        '',
        $content
    );

    // 2. Shortcodes orphelins de plugins virés
    $content = preg_replace(
        '/\[(login-form|register-form|loginform|loginpress[^\]]*|user_registration[^\]]*|um_loggedin[^\]]*|um_loggedout[^\]]*|kadence[^\]]*|blockspare[^\]]*)[^\]]*\]/i',
        '',
        $content
    );

    // 3. Formulaires wp-login / register embarqués (balises autonomes, pas nested)
    $content = preg_replace(
        '#<form[^>]*action="[^"]*wp-login[^"]*"[^>]*>.*?</form>#is',
        '',
        $content
    );
    $content = preg_replace(
        '#<form[^>]*id="(?:loginform|registerform)"[^>]*>.*?</form>#is',
        '',
        $content
    );

    // 4. Strip styles inline + classes cassantes sur h1-h6 UNIQUEMENT
    $content = preg_replace_callback(
        '#<(h[1-6])([^>]*)>#i',
        function ( $m ) {
            $attrs = $m[2];
            // strip style=""
            $attrs = preg_replace( '/\s+style\s*=\s*"[^"]*"/i', '', $attrs );
            // strip classes Kadence / Blockspare / has-background / is-style-*
            $attrs = preg_replace_callback(
                '/\sclass="([^"]*)"/i',
                function ( $cm ) {
                    $kept = array();
                    foreach ( preg_split( '/\s+/', trim( $cm[1] ) ) as $c ) {
                        if ( $c === '' ) continue;
                        if ( preg_match( '/^(kadence|blockspare|kb-|has-background|has-text-align|is-style-|wp-block-heading$)/i', $c ) ) continue;
                        $kept[] = $c;
                    }
                    return empty( $kept ) ? '' : ' class="' . esc_attr( implode( ' ', $kept ) ) . '"';
                },
                $attrs
            );
            return '<' . $m[1] . $attrs . '>';
        },
        $content
    );

    // 5. Nettoyer lignes vides multiples
    $content = preg_replace( "/\n{3,}/", "\n\n", $content );

    // Sécurité : si le résultat est VIDE ou beaucoup plus court que l'original,
    // on annule et on retourne l'original.
    if ( trim( $content ) === '' ) { return $original; }
    if ( mb_strlen( $content ) < ( mb_strlen( $original ) * 0.3 ) ) {
        return $original;
    }

    return trim( $content );
}

/**
 * Preview : compare avant/après sur un article échantillon, sans rien modifier.
 */
function ql_do_preview_clean() {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post_status'    => array( 'publish' ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        's'              => 'kadence',  // cible des articles qui contiennent le mot "kadence"
    ) );

    if ( empty( $posts ) ) {
        // Fallback : les 3 derniers articles
        $posts = get_posts( array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'post_status'    => array( 'publish' ),
        ) );
    }

    echo '<div style="background:#fff;padding:20px;border:2px solid #0f0f0f;border-radius:8px;margin:20px 0;">';
    echo '<h3 style="margin-top:0;">Prévisualisation du nettoyage (AUCUNE modification en base)</h3>';

    foreach ( $posts as $post ) {
        $before = $post->post_content;
        $after  = ql_clean_article_content( $before );
        $diff_chars = mb_strlen( $before ) - mb_strlen( $after );
        $changed = ( $after !== $before );

        echo '<div style="border-top:1px solid #ddd;margin:15px 0;padding-top:15px;">';
        echo '<strong>' . esc_html( $post->post_title ) . '</strong> (ID ' . (int) $post->ID . ')<br>';
        if ( $changed ) {
            echo '<span style="color:#2e8a3d;font-weight:600;">Serait modifié</span> — ';
        } else {
            echo '<span style="color:#666;">Aucun changement</span> — ';
        }
        echo 'avant : ' . number_format_i18n( mb_strlen( $before ) ) . ' car. · ';
        echo 'après : ' . number_format_i18n( mb_strlen( $after ) ) . ' car. · ';
        echo 'delta : ' . ( $diff_chars > 0 ? '-' . number_format_i18n( $diff_chars ) : '+' . number_format_i18n( abs( $diff_chars ) ) ) . ' car.';
        echo '</div>';
    }

    echo '</div>';
}
