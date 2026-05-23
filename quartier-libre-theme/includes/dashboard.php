<?php
/**
 * Quartier Libre — Tableau de bord central (admin)
 *
 * Une seule page qui regroupe :
 *   - Les chiffres clés (articles, brouillons, commentaires, abonnés Telegram)
 *   - Le « chef de rédaction » : priorités calculées sur l'état réel du site
 *   - Des liens directs vers tous les outils du thème
 *
 * Menu : « Quartier Libre » (tout en haut de l'admin).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
    add_menu_page(
        'Quartier Libre — Tableau de bord',
        'Quartier Libre',
        'edit_posts',
        'ql-dashboard',
        'ql_dashboard_render',
        'dashicons-megaphone',
        2
    );
    add_submenu_page( 'ql-dashboard', 'Tableau de bord', 'Tableau de bord', 'edit_posts', 'ql-dashboard', 'ql_dashboard_render' );
} );

// ── Calcul des priorités (le « chef de rédaction ») ────────────
function ql_dashboard_priorities() {
    $prios = array();

    // Articles en brouillon à finir
    $drafts = (int) wp_count_posts( 'post' )->draft;
    if ( $drafts > 0 ) {
        $prios[] = array(
            'urgent' => false,
            'icon'   => '✍️',
            'text'   => $drafts . ' brouillon' . ( $drafts > 1 ? 's' : '' ) . ' à finir et publier',
            'url'    => admin_url( 'edit.php?post_status=draft&post_type=post' ),
            'cta'    => 'Voir',
        );
    }

    // Commentaires à modérer
    $pending = (int) get_comments( array( 'status' => 'hold', 'count' => true ) );
    if ( $pending > 0 ) {
        $prios[] = array(
            'urgent' => true,
            'icon'   => '💬',
            'text'   => $pending . ' commentaire' . ( $pending > 1 ? 's' : '' ) . ' en attente de modération',
            'url'    => admin_url( 'edit-comments.php?comment_status=moderated' ),
            'cta'    => 'Modérer',
        );
    }

    // Articles publiés sans image à la une
    $no_thumb = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ),
    ) );
    if ( ! empty( $no_thumb ) ) {
        $n = count( $no_thumb );
        $prios[] = array(
            'urgent' => false,
            'icon'   => '🖼️',
            'text'   => $n . ' article' . ( $n > 1 ? 's' : '' ) . ' sans image à la une (mauvais rendu en home et sur Telegram)',
            'url'    => admin_url( 'edit.php?post_type=post' ),
            'cta'    => 'Corriger',
        );
    }

    // Articles publiés sans fiche sources
    $no_src = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => '_ql_sources_md', 'compare' => 'NOT EXISTS' ) ),
    ) );
    if ( ! empty( $no_src ) ) {
        $n = count( $no_src );
        $prios[] = array(
            'urgent' => false,
            'icon'   => '📋',
            'text'   => $n . ' article' . ( $n > 1 ? 's' : '' ) . ' sans fiche sources (traçabilité éditoriale)',
            'url'    => admin_url( 'edit.php?post_type=post' ),
            'cta'    => 'Voir',
        );
    }

    // Telegram configuré ?
    if ( function_exists( 'ql_telegram_token' ) && ql_telegram_token() === '' ) {
        $prios[] = array(
            'urgent' => false,
            'icon'   => '📣',
            'text'   => 'Telegram pas encore connecté — branche ton canal pour publier en automatique',
            'url'    => admin_url( 'options-general.php?page=ql-telegram' ),
            'cta'    => 'Configurer',
        );
    }

    // Aucun article publié récemment (>7 j) → rappel de cadence
    $last = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids' ) );
    if ( ! empty( $last ) ) {
        $last_time = get_post_time( 'U', true, $last[0] );
        if ( $last_time && ( time() - $last_time ) > 7 * DAY_IN_SECONDS ) {
            $days = floor( ( time() - $last_time ) / DAY_IN_SECONDS );
            $prios[] = array(
                'urgent' => true,
                'icon'   => '⏰',
                'text'   => 'Aucun article publié depuis ' . $days . ' jours — le rythme retombe',
                'url'    => admin_url( 'post-new.php' ),
                'cta'    => 'Écrire',
            );
        }
    }

    return $prios;
}

// ── Rendu de la page ───────────────────────────────────────────
function ql_dashboard_render() {
    if ( ! current_user_can( 'edit_posts' ) ) { wp_die( 'Non.' ); }

    $published = (int) wp_count_posts( 'post' )->publish;
    $drafts    = (int) wp_count_posts( 'post' )->draft;
    $pending   = (int) get_comments( array( 'status' => 'hold', 'count' => true ) );
    $tg_subs   = function_exists( 'ql_telegram_subscriber_count' ) ? ql_telegram_subscriber_count() : null;
    $prios     = ql_dashboard_priorities();
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:.5rem;">📰 Quartier Libre — Tableau de bord</h1>
        <p style="font-size:1.05em;color:#555;">Tout au même endroit : tes chiffres, tes priorités, tes outils.</p>

        <!-- CHIFFRES CLÉS -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:24px 0;">
            <?php
            ql_dash_stat_card( '📄', $published, 'Articles publiés', admin_url( 'edit.php?post_type=post' ) );
            ql_dash_stat_card( '✍️', $drafts, 'Brouillons', admin_url( 'edit.php?post_status=draft&post_type=post' ) );
            ql_dash_stat_card( '💬', $pending, 'Commentaires à modérer', admin_url( 'edit-comments.php?comment_status=moderated' ) );
            ql_dash_stat_card( '📣', ( $tg_subs === null ? '—' : number_format_i18n( $tg_subs ) ), 'Abonnés Telegram', admin_url( 'options-general.php?page=ql-telegram' ) );
            ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

            <!-- CHEF DE RÉDACTION : PRIORITÉS -->
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:22px;">
                <h2 style="margin-top:0;">🧭 Chef de rédaction — priorités du moment</h2>
                <?php if ( empty( $prios ) ) : ?>
                    <p style="color:#1a7f37;font-weight:600;">Tout est en ordre. Bravo ! Tu peux te concentrer sur l'écriture.</p>
                <?php else : ?>
                    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;">
                        <?php foreach ( $prios as $p ) : ?>
                            <li style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:6px;background:<?php echo $p['urgent'] ? '#fff1ef' : '#f6f7f7'; ?>;border-left:4px solid <?php echo $p['urgent'] ? '#e63312' : '#bbb'; ?>;">
                                <span style="font-size:1.3em;"><?php echo esc_html( $p['icon'] ); ?></span>
                                <span style="flex:1;"><?php echo esc_html( $p['text'] ); ?></span>
                                <a class="button button-small" href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['cta'] ); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- TOUS MES OUTILS -->
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:22px;">
                <h2 style="margin-top:0;">🧰 Tous mes outils</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <?php
                    ql_dash_tool( '✏️', 'Nouvel article', admin_url( 'post-new.php' ) );
                    ql_dash_tool( '📚', 'Tous les articles', admin_url( 'edit.php?post_type=post' ) );
                    ql_dash_tool( '🔄', 'Sync GitHub', admin_url( 'tools.php?page=ql-sync' ) );
                    ql_dash_tool( '📣', 'Réglages Telegram', admin_url( 'options-general.php?page=ql-telegram' ) );
                    ql_dash_tool( '💬', 'Commentaires', admin_url( 'edit-comments.php' ) );
                    ql_dash_tool( '🖼️', 'Médiathèque', admin_url( 'upload.php' ) );
                    ql_dash_tool( '🗂️', 'Catégories', admin_url( 'edit-tags.php?taxonomy=category' ) );
                    ql_dash_tool( '🧭', 'Menus', admin_url( 'nav-menus.php' ) );
                    ql_dash_tool( '🎨', 'Personnaliser', admin_url( 'customize.php' ) );
                    ql_dash_tool( '🚨', 'Bureau des plaintes', home_url( '/bureau-des-plaintes/' ) );
                    ?>
                </div>
            </div>

        </div>

        <!-- ROBOT DE VEILLE -->
        <?php
        if ( function_exists( 'ql_veille_render_panel' ) ) {
            ql_veille_render_panel();
        }
        ?>

    </div>
    <?php
}

function ql_dash_stat_card( $icon, $value, $label, $url ) {
    echo '<a href="' . esc_url( $url ) . '" style="text-decoration:none;color:inherit;">';
    echo '<div style="background:#0f0f0f;color:#fff;border-radius:8px;padding:20px;text-align:center;transition:transform .15s;">';
    echo '<div style="font-size:1.6em;">' . esc_html( $icon ) . '</div>';
    echo '<div style="font-size:2em;font-weight:800;margin:.2em 0;">' . esc_html( $value ) . '</div>';
    echo '<div style="font-size:.85em;color:#bbb;">' . esc_html( $label ) . '</div>';
    echo '</div></a>';
}

function ql_dash_tool( $icon, $label, $url ) {
    echo '<a href="' . esc_url( $url ) . '" style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:#f6f7f7;border-radius:6px;text-decoration:none;color:#1d2327;font-weight:600;">';
    echo '<span style="font-size:1.2em;">' . esc_html( $icon ) . '</span>';
    echo '<span>' . esc_html( $label ) . '</span>';
    echo '</a>';
}
