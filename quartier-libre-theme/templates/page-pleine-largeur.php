<?php
/**
 * Template Name: Pleine largeur
 *
 * Pour les pages spéciales (campagnes, dossiers, qui-sommes-nous).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container ql-container--wide">
    <?php while ( have_posts() ) : the_post(); ?>
        <article <?php post_class( 'ql-page ql-page--wide' ); ?>>
            <?php if ( get_the_title() ) : ?>
                <header class="ql-page__header ql-page__header--centered">
                    <h1 class="ql-page__title"><?php the_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="ql-page__body">
                <?php the_content(); ?>
                <?php wp_link_pages(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
