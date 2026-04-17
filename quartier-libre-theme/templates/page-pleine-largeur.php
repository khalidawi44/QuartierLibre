<?php
/**
 * Template Name: Pleine largeur
 *
 * Pour les pages spéciales (campagnes, dossiers, qui-sommes-nous).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container" style="max-width:1100px;">
    <?php while ( have_posts() ) : the_post(); ?>
        <article <?php post_class( 'ql-article' ); ?> style="max-width:none;">
            <?php if ( get_the_title() ) : ?>
                <header class="ql-article__header" style="text-align:center;">
                    <h1 class="ql-article__title"><?php the_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="ql-article__body">
                <?php the_content(); ?>
                <?php wp_link_pages(); ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
