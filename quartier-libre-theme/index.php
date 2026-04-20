<?php
/**
 * Home / index — une du média.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container">

    <?php get_template_part( 'template-parts/hero' ); ?>

    <?php
    // Détection auto des 4 catégories les plus actives (slug dynamique).
    $top_cats = get_categories( array(
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 4,
        'hide_empty' => true,
    ) );
    foreach ( $top_cats as $cat ) {
        get_template_part( 'template-parts/section-category', null, array(
            'slug'  => $cat->slug,
            'label' => $cat->name,
            'count' => 3,
        ) );
    }
    ?>

    <section class="ql-section" aria-label="Derniers articles">
        <header class="ql-section__head">
            <h2 class="ql-section__title">Tous les articles</h2>
            <a class="ql-section__link" href="<?php echo esc_url( home_url( '/tous-les-articles/' ) ); ?>">Archives →</a>
        </header>

        <div class="ql-grid ql-grid--3">
            <?php
            $recent = new WP_Query( array(
                'posts_per_page' => 6,
                'offset'         => 4, // éviter doublons avec le hero
                'no_found_rows'  => true,
            ) );
            while ( $recent->have_posts() ) : $recent->the_post();
                get_template_part( 'template-parts/card-article' );
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </section>

</div>

<?php get_footer(); ?>
