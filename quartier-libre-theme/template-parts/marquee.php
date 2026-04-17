<?php
/**
 * Bandeau d'alertes déroulant.
 *
 * Comportement :
 *  - Affiche les articles de la catégorie « alerte » (slug `alerte`),
 *    du tag « alerte », OU les 5 plus récents par défaut.
 *  - Masqué si aucun article.
 *  - Pause au survol (CSS), accessible (prefers-reduced-motion).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// 1) D'abord catégorie "alerte", sinon tag "alerte", sinon latest
$args_priority = array(
    'posts_per_page'      => 6,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
);

$items = array();

$alerte_cat = get_term_by( 'slug', 'alerte', 'category' );
if ( $alerte_cat ) {
    $q = new WP_Query( array_merge( $args_priority, array( 'cat' => $alerte_cat->term_id ) ) );
    while ( $q->have_posts() ) { $q->the_post(); $items[] = array( 'title' => get_the_title(), 'url' => get_permalink(), 'date' => get_the_date() ); }
    wp_reset_postdata();
}

if ( empty( $items ) ) {
    $alerte_tag = get_term_by( 'slug', 'alerte', 'post_tag' );
    if ( $alerte_tag ) {
        $q = new WP_Query( array_merge( $args_priority, array( 'tag_id' => $alerte_tag->term_id ) ) );
        while ( $q->have_posts() ) { $q->the_post(); $items[] = array( 'title' => get_the_title(), 'url' => get_permalink(), 'date' => get_the_date() ); }
        wp_reset_postdata();
    }
}

if ( empty( $items ) ) {
    $q = new WP_Query( $args_priority );
    while ( $q->have_posts() ) { $q->the_post(); $items[] = array( 'title' => get_the_title(), 'url' => get_permalink(), 'date' => get_the_date() ); }
    wp_reset_postdata();
}

if ( empty( $items ) ) { return; }

// Dupliquer pour boucle visuelle continue
$loop = array_merge( $items, $items );
?>
<aside class="ql-marquee" aria-label="Fil d'actualité">
    <div class="ql-marquee__label">
        <span class="ql-marquee__pulse" aria-hidden="true"></span>
        EN DIRECT
    </div>
    <div class="ql-marquee__track-wrap">
        <div class="ql-marquee__track" role="list">
            <?php foreach ( $loop as $i => $it ) : ?>
                <a class="ql-marquee__item" role="listitem" href="<?php echo esc_url( $it['url'] ); ?>">
                    <span class="ql-marquee__date"><?php echo esc_html( $it['date'] ); ?></span>
                    <span class="ql-marquee__sep" aria-hidden="true">·</span>
                    <span class="ql-marquee__title"><?php echo esc_html( $it['title'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</aside>
