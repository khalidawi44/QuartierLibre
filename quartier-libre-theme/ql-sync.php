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

    // Action : supprimer les catégories vides
    if ( isset( $_POST['ql_clean_empty_cats'] ) && check_admin_referer( 'ql_clean_empty_cats_nonce' ) ) {
        ql_do_clean_empty_categories();
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

        <form method="post" style="display:inline-block;margin-left:12px;" onsubmit="return confirm('Supprimer toutes les catégories vides (0 articles) ? Les catégories protégées (non-classé, a-la-une, infos-locale, en-france, international, luttes, histoire) sont conservées.');">
            <?php wp_nonce_field( 'ql_clean_empty_cats_nonce' ); ?>
            <input type="submit" name="ql_clean_empty_cats" class="button" style="background:#666;color:#fff;border-color:#666;padding:10px 16px;height:auto;" value="🧹 Nettoyer catégories vides">
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

// ════════════════════════════════════════════════════════════════
//  SYNC DE CONTENU — articles Markdown → WordPress posts
// ════════════════════════════════════════════════════════════════

// ── Roster de la rédaction (création idempotente) ─────────────
// Crée/met à jour les 13 auteurs fictionnels de la rédaction
// (11 spécialistes quartier + 2 correspondants national/international).
// Chaque entrée : login, display_name, email, bio.
// Les mots de passe sont générés aléatoirement — l'admin peut les
// réinitialiser via WP Admin > Utilisateurs si besoin.
function ql_authors_roster() {
    return array(
        // 11 spécialistes quartier HLM
        array( 'login' => 'aissata-diallo',   'display' => 'Aïssata Diallo',   'first' => 'Aïssata', 'last' => 'Diallo',    'email' => 'aissata.diallo@quartierlibre.org',   'bio' => 'Reporter terrain à Bellevue. Documente la politique sécuritaire, la vidéosurveillance, les témoignages des habitant·es face à la BAC et aux services publics absents.' ),
        array( 'login' => 'younes-boukhris',  'display' => 'Younes Boukhris',  'first' => 'Younes',  'last' => 'Boukhris',  'email' => 'younes.boukhris@quartierlibre.org',  'bio' => 'Malakoff. Spécialiste urbanisme et rénovation urbaine — décrypte les PRU/NPNRU et leurs effets de gentrification sur les quartiers populaires.' ),
        array( 'login' => 'karima-benali',    'display' => 'Karima Benali',    'first' => 'Karima',  'last' => 'Benali',    'email' => 'karima.benali@quartierlibre.org',    'bio' => 'Dervallières. Enquête sur l\'abandon des services publics — écoles, PMI, poste — et la solidarité qui s\'organise malgré tout.' ),
        array( 'login' => 'soraya-messaoudi', 'display' => 'Soraya Messaoudi', 'first' => 'Soraya',  'last' => 'Messaoudi', 'email' => 'soraya.messaoudi@quartierlibre.org', 'bio' => 'Clos Toreau. Logement indigne, bailleurs sociaux défaillants, punaises et moisissures — le quotidien que Nantes Métropole Habitat refuse de voir.' ),
        array( 'login' => 'mehdi-haddad',     'display' => 'Mehdi Haddad',     'first' => 'Mehdi',   'last' => 'Haddad',    'email' => 'mehdi.haddad@quartierlibre.org',     'bio' => 'Bottière–Pin Sec. Suit la destruction programmée du quartier au nom de la « rénovation urbaine » et la résistance des locataires.' ),
        array( 'login' => 'fatou-traore',     'display' => 'Fatou Traoré',     'first' => 'Fatou',   'last' => 'Traoré',    'email' => 'fatou.traore@quartierlibre.org',     'bio' => 'Breil. Violences policières, contrôles au faciès, justice sociale — donne la parole aux ados et aux familles.' ),
        array( 'login' => 'samir-toure',      'display' => 'Samir Touré',      'first' => 'Samir',   'last' => 'Touré',     'email' => 'samir.toure@quartierlibre.org',      'bio' => 'Bout des Landes. Transports publics, enclavement territorial, mobilités subies — comment on condamne un quartier en réduisant les bus.' ),
        array( 'login' => 'lea-marchand',     'display' => 'Léa Marchand',     'first' => 'Léa',     'last' => 'Marchand',  'email' => 'lea.marchand@quartierlibre.org',     'bio' => 'Port Boyer. Logement étudiant, marchands de sommeil, précarité jeune — le silence complice des institutions universitaires.' ),
        array( 'login' => 'naima-ouedraogo',  'display' => 'Naïma Ouédraogo',  'first' => 'Naïma',   'last' => 'Ouédraogo', 'email' => 'naima.ouedraogo@quartierlibre.org',  'bio' => 'Halvêque. Médias dominants, fabrique des « territoires perdus », contre-narratifs — rendre visible ce que le 20h efface.' ),
        array( 'login' => 'amadou-kone',      'display' => 'Amadou Koné',      'first' => 'Amadou',  'last' => 'Koné',      'email' => 'amadou.kone@quartierlibre.org',      'bio' => 'Ranzay. Vie de quartier, tissu associatif, liens sociaux — ce qui tient debout quand les institutions reculent.' ),
        array( 'login' => 'sofia-bensalem',   'display' => 'Sofia Bensalem',   'first' => 'Sofia',   'last' => 'Bensalem',  'email' => 'sofia.bensalem@quartierlibre.org',   'bio' => 'Pilotière. Auto-organisation, collectifs habitants, entraide — les quartiers qui se prennent en main.' ),
        // Correspondants
        array( 'login' => 'rachida-ben-arfa', 'display' => 'Rachida Ben Arfa', 'first' => 'Rachida', 'last' => 'Ben Arfa',  'email' => 'rachida.benarfa@quartierlibre.org',  'bio' => 'Correspondante internationale. Couvre Gaza, la Palestine, les résistances populaires au Maghreb et au Moyen-Orient. Relaye ce que les médias mainstream préfèrent taire.' ),
        array( 'login' => 'julien-moreau',    'display' => 'Julien Moreau',    'first' => 'Julien',  'last' => 'Moreau',    'email' => 'julien.moreau@quartierlibre.org',    'bio' => 'Correspondant national. Politique française, décomposition du PS, dérives autoritaires macronistes, luttes sociales — éclairage structurel depuis les quartiers.' ),
    );
}

function ql_create_authors() {
    $created = 0; $updated = 0;
    foreach ( ql_authors_roster() as $a ) {
        $user = get_user_by( 'login', $a['login'] );
        if ( ! $user ) {
            $user_id = wp_insert_user( array(
                'user_login'    => $a['login'],
                'user_pass'     => wp_generate_password( 16, true, false ),
                'user_email'    => $a['email'],
                'display_name'  => $a['display'],
                'first_name'    => $a['first'],
                'last_name'     => $a['last'],
                'nickname'      => $a['display'],
                'description'   => $a['bio'],
                'role'          => 'author',
            ) );
            if ( ! is_wp_error( $user_id ) ) { $created++; }
        } else {
            // Mise à jour légère (bio + display) — pas de reset du password
            wp_update_user( array(
                'ID'           => $user->ID,
                'display_name' => $a['display'],
                'first_name'   => $a['first'],
                'last_name'    => $a['last'],
                'description'  => $a['bio'],
                'nickname'     => $a['display'],
            ) );
            $updated++;
        }
    }
    return array( 'created' => $created, 'updated' => $updated );
}

function ql_do_content_sync() {
    // Crée/met à jour la rédaction (13 auteurs) avant tout upsert d'article
    $authors_result = ql_create_authors();
    if ( $authors_result['created'] > 0 || $authors_result['updated'] > 0 ) {
        echo '<div class="notice notice-info"><p>Rédaction : <strong>'
            . (int) $authors_result['created'] . '</strong> auteur(s) créé(s), <strong>'
            . (int) $authors_result['updated'] . '</strong> mis à jour.</p></div>';
    }

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
    if ( ! empty( $front['featured_image_url'] ) ) {
        // URL complète (ex: https://quartierlibre.org/wp-content/uploads/.../image.jpg)
        $thumb_id = ql_upload_image_from_url( $front['featured_image_url'], $images_count );
    } elseif ( ! empty( $front['featured_image'] ) ) {
        // Chemin relatif au repo (content/media/xxx.jpg)
        $thumb_id = ql_upload_image_from_repo( $front['featured_image'], $images_count );
    }

    $body_html = ql_markdown_to_html( $body_md, $images_count );

    $postarr = array(
        'post_title'   => $front['title'],
        'post_name'    => $slug,
        'post_content' => $body_html,
        'post_excerpt' => isset( $front['excerpt'] ) ? $front['excerpt'] : '',
        'post_type'    => 'post',
    );

    // ── Post status : ne JAMAIS retrograder un article déjà publié ──
    // - Nouvel article : on respecte le frontmatter (draft / publish)
    // - Article existant en 'draft' : on respecte le frontmatter (permet de promouvoir)
    // - Article existant publié/programmé/privé : on garde le status actuel
    //   (pour que le re-sync ne remette pas en brouillon un article que l'admin
    //    a publié manuellement dans WP)
    $front_wants_draft = ( isset( $front['status'] ) && $front['status'] === 'draft' );
    if ( ! empty( $existing ) ) {
        $current_status = $existing[0]->post_status;
        if ( $current_status === 'draft' ) {
            $postarr['post_status'] = $front_wants_draft ? 'draft' : 'publish';
        } else {
            $postarr['post_status'] = $current_status; // keep publish/future/private/pending
        }
    } else {
        $postarr['post_status'] = $front_wants_draft ? 'draft' : 'publish';
    }

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

    // Article sélectionné pour la Une (featured sur la home)
    // Frontmatter `une: true` → meta `_ql_une` = 1 (sinon supprimé)
    $is_une = false;
    if ( isset( $front['une'] ) ) {
        $v = $front['une'];
        $is_une = ( $v === true || $v === 1 || $v === '1' || strtolower( (string) $v ) === 'true' );
    }
    if ( $is_une ) {
        update_post_meta( $post_id, '_ql_une', 1 );
    } else {
        delete_post_meta( $post_id, '_ql_une' );
    }

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

// ── Upload d'une image depuis une URL quelconque (dédupliquée) ─
function ql_upload_image_from_url( $url, &$count ) {
    if ( empty( $url ) ) return 0;
    $hash_key = '_ql_media_' . md5( $url );

    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'meta_key'       => $hash_key,
        'posts_per_page' => 1,
    ) );
    if ( $existing ) return $existing[0]->ID;

    // Si l'URL pointe déjà sur notre propre médiathèque, on essaie de
    // retrouver l'attachment existant par URL (pas de re-download).
    $upload_dir = wp_upload_dir();
    if ( strpos( $url, $upload_dir['baseurl'] ) === 0 ) {
        $attach_id = attachment_url_to_postid( $url );
        if ( $attach_id ) {
            update_post_meta( $attach_id, $hash_key, 1 );
            return $attach_id;
        }
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( $url, 60 );
    if ( is_wp_error( $tmp ) ) return 0;

    $file_array = array( 'name' => basename( wp_parse_url( $url, PHP_URL_PATH ) ), 'tmp_name' => $tmp );
    $attach_id = media_handle_sideload( $file_array, 0 );
    if ( is_wp_error( $attach_id ) ) { @unlink( $tmp ); return 0; }

    update_post_meta( $attach_id, $hash_key, 1 );
    update_post_meta( $attach_id, '_ql_media_source', $url );
    $count++;
    return $attach_id;
}

// ── Upload d'une image depuis le repo (dédupliquée par ETag) ──
// On ne peut pas dédupliquer par URL seule : l'URL GitHub raw reste
// identique même quand le contenu du fichier change. On demande
// donc l'ETag (qui est un hash du contenu côté GitHub) et on crée
// une nouvelle attachment si l'ETag a changé.
function ql_upload_image_from_repo( $repo_path, &$count ) {
    $repo_path = ltrim( str_replace( '\\', '/', $repo_path ), '/' );
    $raw_url = sprintf(
        'https://raw.githubusercontent.com/%s/%s/%s/%s',
        QL_GH_OWNER, QL_GH_REPO, QL_GH_BRANCH, $repo_path
    );

    // HEAD pour récupérer l'ETag courant (hash du contenu GitHub)
    $head = wp_remote_head( $raw_url, array( 'timeout' => 15, 'redirection' => 5 ) );
    $etag = '';
    if ( ! is_wp_error( $head ) ) {
        $etag = (string) wp_remote_retrieve_header( $head, 'etag' );
        $etag = trim( $etag, '"' );
    }

    $meta_key = '_ql_repo_etag_' . md5( $raw_url );

    // Si on a un ETag et qu'une attachment avec ce même ETag existe,
    // on la réutilise (même fichier, déjà importé).
    if ( $etag ) {
        $existing = get_posts( array(
            'post_type'      => 'attachment',
            'meta_key'       => $meta_key,
            'meta_value'     => $etag,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( $existing ) return (int) $existing[0];
    }

    // Download + upload (nouveau fichier ou contenu modifié)
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( $raw_url, 60 );
    if ( is_wp_error( $tmp ) ) return 0;

    $file_array = array(
        'name'     => basename( wp_parse_url( $raw_url, PHP_URL_PATH ) ),
        'tmp_name' => $tmp,
    );
    $attach_id = media_handle_sideload( $file_array, 0 );
    if ( is_wp_error( $attach_id ) ) { @unlink( $tmp ); return 0; }

    if ( $etag ) update_post_meta( $attach_id, $meta_key, $etag );
    update_post_meta( $attach_id, '_ql_media_source', $raw_url );
    $count++;
    return (int) $attach_id;
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
// Nettoyer les catégories vides (count = 0)
// ════════════════════════════════════════════════════════════════
function ql_do_clean_empty_categories() {
    // Slugs à TOUJOURS conserver, même si vides
    $protected = array(
        'uncategorized',
        'non-classe',
        'non-classifie',
        'a-la-une',
        'infos-locale',
        'en-france',
        'france',
        'international',
        'luttes',
        'histoire',
        'local',
        'nos-quartiers',
    );

    $default_cat_id = (int) get_option( 'default_category' );

    $terms = get_terms( array(
        'taxonomy'   => 'category',
        'hide_empty' => false,
        'number'     => 0,
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        echo '<div class="notice notice-warning"><p>Aucune catégorie trouvée.</p></div>';
        return;
    }

    $deleted = 0; $kept = 0; $protected_kept = 0;
    foreach ( $terms as $t ) {
        // Skip si protégée
        if ( in_array( $t->slug, $protected, true ) ) { $protected_kept++; continue; }
        // Skip catégorie par défaut WP
        if ( (int) $t->term_id === $default_cat_id ) { $protected_kept++; continue; }
        // Skip si elle a des articles
        if ( (int) $t->count > 0 ) { $kept++; continue; }
        // Skip si elle a des catégories filles
        $children = get_term_children( $t->term_id, 'category' );
        if ( ! empty( $children ) ) { $kept++; continue; }

        // Supprimer
        $res = wp_delete_term( $t->term_id, 'category' );
        if ( $res && ! is_wp_error( $res ) ) {
            $deleted++;
        }
    }

    wp_cache_flush();

    $msg = sprintf(
        '%d catégorie(s) vide(s) supprimée(s). %d protégée(s) conservée(s). %d avec articles (intactes).',
        $deleted, $protected_kept, $kept
    );
    echo '<div class="notice notice-success"><p><strong>Nettoyage catégories.</strong> ' . esc_html( $msg ) . '</p></div>';
    ql_log_msg( 'Clean empty cats: ' . $msg );
}
