<?php
/**
 * 404 — page introuvable.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container ql-404">

    <p class="ql-archive-header__kicker ql-404__kicker">Erreur 404</p>
    <h1 class="ql-404__title">Cette page n'existe pas (ou plus).</h1>
    <p class="ql-404__desc">
        L'article a peut-être été déplacé, ou l'adresse comporte une erreur.
        Essayez la recherche ou revenez à l'accueil.
    </p>

    <div class="ql-404__search"><?php get_search_form(); ?></div>

    <p>
        <a class="ql-btn ql-btn--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>
    </p>

</div>

<?php get_footer(); ?>
