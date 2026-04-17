<?php
/**
 * Search — page résultats de recherche.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container">

    <header class="ql-archive-header">
        <span class="ql-archive-header__kicker">Recherche</span>
        <h1>« <?php echo esc_html( get_search_query() ); ?> »</h1>
        <p class="ql-archive-header__desc">
            <?php
            global $wp_query;
            $n = (int) $wp_query->found_posts;
            echo $n === 0 ? 'Aucun résultat.' : ( $n . ' résultat' . ( $n > 1 ? 's' : '' ) );
            ?>
        </p>
        <div style="max-width:480px;margin:1rem auto 0;"><?php get_search_form(); ?></div>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="ql-grid ql-grid--3">
            <?php while ( have_posts() ) : the_post();
                get_template_part( 'template-parts/card-article' );
            endwhile; ?>
        </div>

        <nav class="ql-pagination" aria-label="Pagination">
            <?php echo paginate_links( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?>
        </nav>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
