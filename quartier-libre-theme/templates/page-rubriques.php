<?php
/**
 * Template Name: Rubriques
 *
 * Page /rubriques/ — listing des rubriques (catégories top-level) avec
 * gros titres colorés style Contre-Attaque. Inspiration contre-attaque.net/rubriques/.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

// Définition des rubriques avec couleur et description courte
$rubriques = array(
    array(
        'slug'  => 'infos-locale',
        'label' => 'Infos locale',
        'color' => '#e02810',  // rouge QL
        'desc'  => 'Nantes, ses quartiers HLM, les luttes pour le logement, la mairie PS, la police.',
    ),
    array(
        'slug'  => 'france',
        'label' => 'France',
        'color' => '#ffcb05',  // jaune fluo
        'desc'  => 'Politique nationale, décomposition macroniste, loi immigration, justice à deux vitesses.',
    ),
    array(
        'slug'  => 'international',
        'label' => 'International',
        'color' => '#2d8a2d',  // vert (drapeaux palestinien, soudanais)
        'desc'  => 'Gaza, Soudan, Palestine, résistances globales, complicités occidentales.',
    ),
    array(
        'slug'  => 'luttes',
        'label' => 'Luttes',
        'color' => '#8b0000',  // rouge-sang, syndical
        'desc'  => 'Manifestations, grèves, répression policière, solidarité de rue.',
    ),
    array(
        'slug'  => 'histoire',
        'label' => 'Histoire',
        'color' => '#7a5f3a',  // sépia/ocre
        'desc'  => 'Racines des luttes d\'aujourd\'hui : philosophie politique, mouvements ouvriers, contre-histoires populaires.',
    ),
);

function ql_count_in_cat( $slug ) {
    $t = get_term_by( 'slug', $slug, 'category' );
    if ( ! $t || is_wp_error( $t ) ) return 0;
    // count inclut les enfants quand on coche include_children dans WP_Query
    $q = new WP_Query( array(
        'category_name'       => $slug,
        'posts_per_page'      => -1,
        'fields'              => 'ids',
        'no_found_rows'       => false,
        'ignore_sticky_posts' => true,
    ) );
    $n = (int) $q->found_posts;
    wp_reset_postdata();
    return $n;
}
?>

<div class="ql-rubriques-page">

    <section class="ql-rubriques-hero">
        <div class="ql-container">
            <h1 class="ql-rubriques-hero__title">Nos rubriques</h1>
            <p class="ql-rubriques-hero__lede">
                Parce que l'actualité est parfois trop dense et que nos lecteurs peuvent se perdre dans le
                flot des articles publiés, nous les avons regroupés par <strong>rubriques thématiques</strong>.
                N'hésitez pas à les explorer pour mieux comprendre notre angle d'attaque.
            </p>
        </div>
    </section>

    <div class="ql-container ql-rubriques-list">

        <?php foreach ( $rubriques as $r ) :
            $term  = get_term_by( 'slug', $r['slug'], 'category' );
            $url   = $term ? get_term_link( $term ) : home_url( '/category/' . $r['slug'] . '/' );
            $count = ql_count_in_cat( $r['slug'] );
        ?>
            <a class="ql-rubrique-block" href="<?php echo esc_url( $url ); ?>" style="--rubrique-color:<?php echo esc_attr( $r['color'] ); ?>;">
                <h2 class="ql-rubrique-block__title"><?php echo esc_html( strtoupper( $r['label'] ) ); ?></h2>
                <div class="ql-rubrique-block__body">
                    <p class="ql-rubrique-block__desc"><?php echo esc_html( $r['desc'] ); ?></p>
                    <p class="ql-rubrique-block__meta">
                        <span class="ql-rubrique-block__count"><?php echo esc_html( $count ); ?> article<?php echo $count > 1 ? 's' : ''; ?></span>
                        <span class="ql-rubrique-block__arrow" aria-hidden="true">→</span>
                    </p>
                </div>
            </a>
        <?php endforeach; ?>

    </div>

    <?php while ( have_posts() ) : the_post();
        $content = get_the_content();
        if ( trim( $content ) ) : ?>
            <section class="ql-container ql-rubriques-page__extra">
                <div class="ql-post__content"><?php the_content(); ?></div>
            </section>
        <?php endif;
    endwhile; ?>

</div>

<?php get_footer(); ?>
