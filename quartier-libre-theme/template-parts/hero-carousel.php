<?php
/**
 * Hero Carousel — style Contre-Attaque.
 * 6 articles en carrousel plein-largeur : grosse image + titre en overlay.
 * CSS-only scroll-snap + boutons nav JS simples.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$q = new WP_Query( array(
    'posts_per_page'      => 6,
    'ignore_sticky_posts' => false,
    'no_found_rows'       => true,
    'meta_query'          => array(
        array( 'key' => '_thumbnail_id' ),  // uniquement articles avec image à la une
    ),
) );

if ( ! $q->have_posts() ) { return; }

$slides = array();
while ( $q->have_posts() ) {
    $q->the_post();
    $slides[] = array(
        'id'     => get_the_ID(),
        'url'    => get_permalink(),
        'title'  => get_the_title(),
        'img'    => get_the_post_thumbnail_url( null, 'ql-hero' ) ?: get_the_post_thumbnail_url( null, 'full' ),
        'cat'    => ql_primary_category(),
        'date'   => get_the_date(),
        'author' => get_the_author(),
    );
}
wp_reset_postdata();

$slide_count = count( $slides );
?>
<section class="ql-carousel" aria-label="À la une" data-total="<?php echo (int) $slide_count; ?>">
    <div class="ql-carousel__track">
        <?php foreach ( $slides as $i => $s ) : ?>
            <article class="ql-carousel__slide" id="ql-slide-<?php echo $i; ?>"
                     <?php if ( $s['img'] ) echo 'style="--slide-bg: url(\'' . esc_url( $s['img'] ) . '\');"'; ?>>
                <?php if ( $s['img'] ) : ?>
                    <img src="<?php echo esc_url( $s['img'] ); ?>"
                         alt=""
                         class="ql-carousel__img"
                         <?php echo $i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?>
                         decoding="async">
                <?php endif; ?>
                <div class="ql-carousel__overlay"></div>
                <a class="ql-carousel__link" href="<?php echo esc_url( $s['url'] ); ?>">
                    <div class="ql-carousel__body">
                        <?php if ( $s['cat'] ) : ?>
                            <span class="ql-carousel__cat"><?php echo esc_html( $s['cat']->name ); ?></span>
                        <?php endif; ?>
                        <h2 class="ql-carousel__title"><?php echo esc_html( $s['title'] ); ?></h2>
                        <p class="ql-carousel__meta">
                            <time datetime="<?php echo esc_attr( $s['date'] ); ?>"><?php echo esc_html( $s['date'] ); ?></time>
                            <?php if ( $s['author'] ) : ?>
                                <span aria-hidden="true">·</span>
                                <span>Par <?php echo esc_html( $s['author'] ); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <button class="ql-carousel__nav ql-carousel__nav--prev" type="button" aria-label="Article précédent">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button class="ql-carousel__nav ql-carousel__nav--next" type="button" aria-label="Article suivant">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
    </button>

    <div class="ql-carousel__dots" role="tablist" aria-label="Navigation des articles">
        <?php for ( $i = 0; $i < $slide_count; $i++ ) : ?>
            <button type="button" class="ql-carousel__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    role="tab"
                    aria-label="Aller à l'article <?php echo $i + 1; ?>"
                    data-index="<?php echo $i; ?>"></button>
        <?php endfor; ?>
    </div>
</section>
