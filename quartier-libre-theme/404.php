<?php
/**
 * 404 — page introuvable.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container" style="text-align:center;padding:4rem 0;">

    <p class="ql-archive-header__kicker" style="color:var(--ql-accent);font-weight:700;letter-spacing:.1em;">Erreur 404</p>
    <h1 style="font-size:clamp(2rem,5vw,3.5rem);">Cette page n'existe pas (ou plus).</h1>
    <p style="max-width:540px;margin:1rem auto 2rem;color:var(--ql-muted);font-size:1.05rem;">
        L'article a peut-être été déplacé, ou l'adresse comporte une erreur.
        Essayez la recherche ou revenez à l'accueil.
    </p>

    <div style="max-width:480px;margin:0 auto 2rem;"><?php get_search_form(); ?></div>

    <p>
        <a class="ql-btn ql-btn--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>
    </p>

</div>

<?php get_footer(); ?>
