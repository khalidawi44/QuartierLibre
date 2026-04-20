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
                // Cascade de résolution du logo (voir ql_resolve_logo_url dans
                // functions.php) : Customizer → recherche médiathèque par nom
                // → fichier thème → vide. On ajoute tous les attributs
                // anti-lazy-load (NitroPack, Jetpack, WP Rocket, etc.) pour
                // garantir que le logo s'affiche au-dessus de la ligne de flottaison.
                $logo_url = function_exists( 'ql_resolve_logo_url' ) ? ql_resolve_logo_url() : '';
                if ( $logo_url ) {
                    echo '<img src="' . esc_url( $logo_url ) . '"'
                       . ' alt="' . esc_attr( get_bloginfo( 'name' ) ) . '"'
                       . ' class="ql-brand__logo no-lazyload"'
                       . ' loading="eager" fetchpriority="high"'
                       . ' data-no-lazy="1" data-nitro-stealth-load="1" data-skip-lazy="1">';
                } else {
                    echo '<span class="ql-brand__wordmark">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
                }
                ?>
            </a>

            <button class="ql-burger" type="button" aria-expanded="false" aria-controls="ql-menu-primary" aria-label="Ouvrir le menu">
                <span></span><span></span><span></span>
            </button>

            <nav id="ql-menu-primary" class="ql-nav" aria-label="Menu principal">
                <?php
                // Menu principal = arbre de catégories (source unique : ql_categories_tree).
                // On ignore volontairement wp_nav_menu primary : le thème pilote la
                // hiérarchie lui-même pour garantir la cohérence avec les articles.
                echo '<ul class="ql-nav__list">';
                $tree = function_exists( 'ql_categories_tree' ) ? ql_categories_tree() : array();
                $cat_url = function ( $slug ) {
                    $t = get_term_by( 'slug', $slug, 'category' );
                    if ( $t && ! is_wp_error( $t ) ) return get_term_link( $t );
                    return home_url( '/category/' . $slug . '/' );
                };

                // Item ACCUEIL (lien vers la home)
                echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Accueil</a></li>';

                // Item RUBRIQUES (dropdown avec les 5 top-level catégories)
                $rubriques_page = get_page_by_path( 'rubriques' );
                $rubriques_url  = $rubriques_page ? get_permalink( $rubriques_page ) : home_url( '/rubriques/' );
                echo '<li class="menu-item-has-children">';
                echo '<a href="' . esc_url( $rubriques_url ) . '">Rubriques</a>';
                echo '<ul class="sub-menu">';
                foreach ( $tree as $parent_slug => $parent_data ) {
                    echo '<li><a href="' . esc_url( $cat_url( $parent_slug ) ) . '">' . esc_html( $parent_data['label'] ) . '</a></li>';
                }
                echo '</ul>';
                echo '</li>';

                // TOUS LES ARTICLES
                $ta = get_page_by_path( 'tous-les-articles' );
                $ta_url = $ta ? get_permalink( $ta ) : home_url( '/tous-les-articles/' );
                echo '<li><a href="' . esc_url( $ta_url ) . '">Tous les articles</a></li>';

                // À PROPOS
                $apropos = get_page_by_path( 'a-propos' );
                $apropos_url = $apropos ? get_permalink( $apropos ) : home_url( '/a-propos/' );
                echo '<li><a href="' . esc_url( $apropos_url ) . '">À propos</a></li>';

                echo '</ul>';
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
