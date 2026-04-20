<?php
/**
 * Page — template par défaut pour les pages WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container">
    <div class="ql-page-layout">

        <!-- SIDEBAR GAUCHE (30%) -->
        <?php get_template_part( 'template-parts/sidebar-home' ); ?>

        <div class="ql-page-main">
            <?php while ( have_posts() ) : the_post(); ?>
                <article <?php post_class( 'ql-article' ); ?>>
                    <header class="ql-article__header">
                        <h1 class="ql-article__title"><?php the_title(); ?></h1>
                    </header>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <figure class="ql-article__hero-media">
                            <?php the_post_thumbnail( 'ql-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
                        </figure>
                    <?php endif; ?>

                    <div class="ql-article__body">
                        <?php the_content(); ?>
                        <?php wp_link_pages(); ?>
                    </div>
                </article>

                <?php if ( comments_open() || get_comments_number() ) : ?>
                    <div class="ql-comments"><?php comments_template(); ?></div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>

    </div><!-- /.ql-page-layout -->
</div>

<?php get_footer(); ?>
