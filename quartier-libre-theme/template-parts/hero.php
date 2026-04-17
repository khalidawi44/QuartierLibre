<?php
/**
 * Hero de la une : 1 article principal plein format + 4 vignettes
 * disposées en grille 2×2 à côté (inspiration Reporterre / Basta!).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$hero_query = new WP_Query( array(
    'posts_per_page'      => 5,
    'ignore_sticky_posts' => false,
    'no_found_rows'       => true,
) );

if ( ! $hero_query->have_posts() ) { return; }

$hero_query->the_post();
$main_cat = ql_primary_category();
?>
<section class="ql-hero ql-hero--grid" aria-label="À la une">

    <article class="ql-hero__main">
        <?php if ( has_post_thumbnail() ) {
            the_post_thumbnail( 'ql-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) );
        } ?>
        <div class="ql-hero__main-body">
            <?php if ( $main_cat ) : ?>
                <a class="ql-card__cat ql-card__cat--static" href="<?php echo esc_url( get_term_link( $main_cat ) ); ?>"><?php echo esc_html( $main_cat->name ); ?></a>
            <?php endif; ?>
            <a href="<?php the_permalink(); ?>" class="ql-hero__main-link">
                <h2><?php the_title(); ?></h2>
            </a>
            <p class="ql-hero__main-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32, '…' ) ); ?></p>
            <div class="ql-hero__main-meta">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                <span aria-hidden="true">·</span>
                <span><?php the_author(); ?></span>
            </div>
        </div>
    </article>

    <div class="ql-hero__side">
        <?php while ( $hero_query->have_posts() ) : $hero_query->the_post();
            $c = ql_primary_category();
        ?>
            <article class="ql-hero__vignette">
                <?php if ( has_post_thumbnail() ) : ?>
                    <a class="ql-hero__vignette-media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                        <?php the_post_thumbnail( 'ql-thumb', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
                    </a>
                <?php endif; ?>
                <div class="ql-hero__vignette-body">
                    <?php if ( $c ) : ?>
                        <a class="ql-hero__vignette-cat" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
                    <?php endif; ?>
                    <h3 class="ql-hero__vignette-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                </div>
            </article>
        <?php endwhile; ?>
    </div>

</section>
<?php wp_reset_postdata(); ?>
