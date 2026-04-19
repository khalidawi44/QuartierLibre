<?php
/**
 * Hero Carousel — « À la une »
 * Affiche les articles marqués _ql_une = 1 (frontmatter `une: true`).
 * Un article « une » par catégorie (le plus récent si plusieurs).
 * Fallback : si aucun article marqué, prend le dernier article de chacune
 * des catégories principales.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// 1. Récupérer tous les articles marqués « à la une »
$une_q = new WP_Query( array(
    'posts_per_page' => 12,
    'meta_query'     => array(
        array( 'key' => '_ql_une', 'value' => '1', 'compare' => '=' ),
    ),
    'no_found_rows'  => true,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

$slides = array();
$cat_seen = array(); // une seule slide par catégorie

if ( $une_q->have_posts() ) {
    while ( $une_q->have_posts() ) {
        $une_q->the_post();
        $cat = ql_primary_category();
        // Dédupe par catégorie RACINE (top-level) : un article « bellevue »
        // et un article « malakoff » (tous deux enfants d'infos-locale) ne
        // peuvent pas être tous les deux en une — un seul par top-level.
        $root = $cat ? ql_root_category( $cat ) : null;
        $cat_key = $root ? $root->term_id : ( $cat ? $cat->term_id : 0 );
        if ( isset( $cat_seen[ $cat_key ] ) ) continue;
        $cat_seen[ $cat_key ] = true;
        $slides[] = array(
            'id'     => get_the_ID(),
            'url'    => get_permalink(),
            'title'  => get_the_title(),
            'img'    => get_the_post_thumbnail_url( null, 'ql-hero' ) ?: get_the_post_thumbnail_url( null, 'full' ),
            'cat'    => $cat,
            'date'   => get_the_date(),
            'author' => get_the_author(),
        );
    }
    wp_reset_postdata();
}

// 2. Fallback : si moins de 3 articles « à la une », compléter avec le dernier
// article de chaque catégorie principale manquante.
if ( count( $slides ) < 3 ) {
    $main_cat_slugs = array( 'infos-locale', 'france', 'international', 'luttes' );
    foreach ( $main_cat_slugs as $slug ) {
        $term = get_term_by( 'slug', $slug, 'category' );
        if ( ! $term || isset( $cat_seen[ $term->term_id ] ) ) continue;

        $fallback = new WP_Query( array(
            'posts_per_page' => 1,
            'category__in'   => array( $term->term_id ),
            'no_found_rows'  => true,
            'meta_query'     => array(
                array( 'key' => '_thumbnail_id' ),
            ),
        ) );
        if ( $fallback->have_posts() ) {
            $fallback->the_post();
            $cat_seen[ $term->term_id ] = true;
            $slides[] = array(
                'id'     => get_the_ID(),
                'url'    => get_permalink(),
                'title'  => get_the_title(),
                'img'    => get_the_post_thumbnail_url( null, 'ql-hero' ) ?: get_the_post_thumbnail_url( null, 'full' ),
                'cat'    => $term,
                'date'   => get_the_date(),
                'author' => get_the_author(),
            );
            wp_reset_postdata();
        }
    }
}

if ( empty( $slides ) ) { return; }

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

    <?php if ( $slide_count > 1 ) : ?>
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
    <?php endif; ?>
</section>
