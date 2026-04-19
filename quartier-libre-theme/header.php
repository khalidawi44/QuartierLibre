<?php
/**
 * Header — Quartier Libre
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f0f0f">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="ql-skip" href="#content">Aller au contenu</a>

<div class="ql-topbar">
    <div class="ql-container ql-topbar__inner">
        <span class="ql-topbar__date"><?php echo esc_html( date_i18n( 'l j F Y' ) ); ?></span>
        <span class="ql-topbar__tagline"><strong>Par nous, pour nous.</strong> Les quartiers prennent la parole.</span>
    </div>
</div>

<header class="ql-header" role="banner">
    <div class="ql-header__bar">
        <div class="ql-container ql-header__inner">

            <a class="ql-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?> — accueil">
                <?php
                $logo_custom = get_theme_mod( 'custom_logo' );
                if ( $logo_custom ) {
                    the_custom_logo();
                } else {
                    $logo_paths = array( '/assets/images/logo.svg', '/assets/images/logo.png', '/assets/images/logo.webp' );
                    $found = '';
                    foreach ( $logo_paths as $p ) {
                        if ( file_exists( QL_THEME_DIR . $p ) ) { $found = QL_THEME_URI . $p; break; }
                    }
                    if ( $found ) {
                        echo '<img src="' . esc_url( $found ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="ql-brand__logo" width="200" height="60">';
                    } else {
                        echo '<span class="ql-brand__wordmark">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
                    }
                }
                ?>
            </a>

            <button class="ql-burger" type="button" aria-expanded="false" aria-controls="ql-menu-primary" aria-label="Ouvrir le menu">
                <span></span><span></span><span></span>
            </button>

            <nav id="ql-menu-primary" class="ql-nav" aria-label="Menu principal">
                <?php
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'ql-nav__list',
                        'fallback_cb'    => false,
                        'depth'          => 2,
                    ) );
                } else {
                    // Fallback : menu construit à partir de l'arborescence de
                    // catégories (parent + sous-menu des enfants).
                    echo '<ul class="ql-nav__list">';
                    $tree = function_exists( 'ql_categories_tree' ) ? ql_categories_tree() : array();
                    foreach ( $tree as $parent_slug => $parent_data ) {
                        $term = get_term_by( 'slug', $parent_slug, 'category' );
                        if ( ! $term ) continue;
                        $has_children = ! empty( $parent_data['children'] );
                        $li_class = $has_children ? ' class="menu-item-has-children"' : '';
                        echo '<li' . $li_class . '>';
                        echo '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $parent_data['label'] ) . '</a>';
                        if ( $has_children ) {
                            echo '<ul class="sub-menu">';
                            foreach ( $parent_data['children'] as $child_slug => $child_label ) {
                                $child = get_term_by( 'slug', $child_slug, 'category' );
                                if ( ! $child ) continue;
                                echo '<li><a href="' . esc_url( get_term_link( $child ) ) . '">' . esc_html( $child_label ) . '</a></li>';
                            }
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                ?>

                <div class="ql-nav__actions">
                    <button class="ql-search-toggle" type="button" aria-expanded="false" aria-controls="ql-search-panel" aria-label="Rechercher">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                    <a class="ql-btn ql-btn--accent" href="<?php
                        $plainte = get_page_by_path( 'bureau-des-plaintes' );
                        echo esc_url( $plainte ? get_permalink( $plainte ) : home_url( '/bureau-des-plaintes/' ) );
                    ?>">Bureau des plaintes</a>
                </div>
            </nav>

        </div>
    </div>

    <div id="ql-search-panel" class="ql-search-panel" hidden>
        <div class="ql-container">
            <?php get_search_form(); ?>
        </div>
    </div>

    <?php get_template_part( 'template-parts/marquee' ); ?>
</header>

<main id="content" class="ql-main" role="main">
