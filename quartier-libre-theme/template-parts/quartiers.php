<?php
/**
 * Mosaïque des quartiers HLM de Nantes.
 * Affiche les tags préfixés `quartier-` en grille cliquable.
 * Affiché par défaut sur la page catégorie « Infos Locale ».
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Récupère tous les tags `quartier-*`, même sans articles, pour afficher la mosaïque complète
$all_tags = get_tags( array(
    'hide_empty' => false,
    'number'     => 50,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );

$quartiers = array();
foreach ( (array) $all_tags as $tag ) {
    if ( strpos( $tag->slug, 'quartier-' ) === 0 ) {
        $nom = preg_replace( '/^quartier[- ]/i', '', $tag->name );
        $quartiers[] = array(
            'name'  => $nom,
            'link'  => get_term_link( $tag ),
            'count' => (int) $tag->count,
            'term'  => $tag,
        );
    }
}

if ( empty( $quartiers ) ) { return; }

// Helper : dernière image d'un quartier
$get_cover = function ( $term ) {
    $q = new WP_Query( array(
        'posts_per_page' => 1,
        'tax_query' => array( array(
            'taxonomy' => $term->taxonomy,
            'field'    => 'term_id',
            'terms'    => array( $term->term_id ),
        ) ),
        'meta_query' => array( array( 'key' => '_thumbnail_id' ) ),
        'no_found_rows' => true,
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
<section class="ql-section ql-quartiers" aria-label="Quartiers">
    <header class="ql-section__head">
        <h2 class="ql-section__title">Les quartiers</h2>
        <span class="ql-section__link"><?php echo (int) count( $quartiers ); ?> quartiers couverts</span>
    </header>

    <div class="ql-quartiers__grid">
        <?php foreach ( $quartiers as $q ) :
            $cover = $get_cover( $q['term'] );
            $empty_class = $q['count'] === 0 ? ' ql-quartier--empty' : '';
        ?>
            <a class="ql-quartier<?php echo esc_attr( $empty_class ); ?>" href="<?php echo esc_url( $q['link'] ); ?>">
                <?php if ( $cover ) : ?>
                    <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy" decoding="async" class="ql-quartier__bg">
                <?php endif; ?>
                <div class="ql-quartier__content">
                    <h3 class="ql-quartier__name"><?php echo esc_html( $q['name'] ); ?></h3>
                    <span class="ql-quartier__count">
                        <?php if ( $q['count'] === 0 ) : ?>
                            Aucun article
                        <?php else : ?>
                            <?php echo (int) $q['count']; ?> article<?php echo $q['count'] > 1 ? 's' : ''; ?>
                        <?php endif; ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
