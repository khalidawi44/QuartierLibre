<?php
/**
 * Section « Dossiers » — mosaïque des grands sujets.
 *
 * Comportement :
 *  - Récupère les TAGS préfixés `dossier-` (ex. dossier-gaza,
 *    dossier-logement, dossier-municipales-2026).
 *  - Fallback : 4 catégories secondaires (hors celles du menu principal).
 *  - Chaque carte : image du dernier article du dossier + titre + nb d'articles.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$dossiers = array();

// 1) Tags commençant par "dossier-"
$all_tags = get_tags( array( 'hide_empty' => true, 'number' => 50 ) );
foreach ( (array) $all_tags as $tag ) {
    if ( strpos( $tag->slug, 'dossier-' ) === 0 ) {
        $dossiers[] = array(
            'name'  => preg_replace( '/^dossier[- ]/i', '', $tag->name ),
            'link'  => get_term_link( $tag ),
            'count' => (int) $tag->count,
            'term'  => $tag,
        );
    }
}

// 2) Fallback : catégories hors top 4
if ( empty( $dossiers ) ) {
    $top_ids = wp_list_pluck( get_categories( array(
        'orderby' => 'count', 'order' => 'DESC', 'number' => 4, 'hide_empty' => true,
    ) ), 'term_id' );

    $secondary = get_categories( array(
        'orderby'   => 'count', 'order' => 'DESC', 'number' => 4, 'hide_empty' => true,
        'exclude'   => $top_ids,
    ) );
    foreach ( (array) $secondary as $cat ) {
        $dossiers[] = array(
            'name'  => $cat->name,
            'link'  => get_term_link( $cat ),
            'count' => (int) $cat->count,
            'term'  => $cat,
        );
    }
}

if ( empty( $dossiers ) ) { return; }
$dossiers = array_slice( $dossiers, 0, 4 );

// Helper : dernière image à la une du dossier
$get_cover = function ( $term ) {
    $q = new WP_Query( array(
        'posts_per_page'      => 1,
        'tax_query'           => array( array(
            'taxonomy' => $term->taxonomy,
            'field'    => 'term_id',
            'terms'    => array( $term->term_id ),
        ) ),
        'meta_query'          => array( array( 'key' => '_thumbnail_id' ) ),
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ) );
    if ( $q->have_posts() ) {
        $q->the_post();
        $url = get_the_post_thumbnail_url( null, 'ql-card' );
        wp_reset_postdata();
        return $url;
    }
    return '';
};
?>
<section class="ql-section" aria-label="Dossiers">
    <header class="ql-section__head">
        <h2 class="ql-section__title">Dossiers</h2>
    </header>
    <div class="ql-dossiers">
        <?php foreach ( $dossiers as $d ) :
            $cover = $get_cover( $d['term'] );
        ?>
            <a class="ql-dossier" href="<?php echo esc_url( $d['link'] ); ?>">
                <?php if ( $cover ) : ?>
                    <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy" decoding="async" class="ql-dossier__bg">
                <?php endif; ?>
                <div class="ql-dossier__content">
                    <span class="ql-dossier__count"><?php echo (int) $d['count']; ?> article<?php echo $d['count'] > 1 ? 's' : ''; ?></span>
                    <h3 class="ql-dossier__title"><?php echo esc_html( $d['name'] ); ?></h3>
                    <span class="ql-dossier__arrow" aria-hidden="true">→</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
