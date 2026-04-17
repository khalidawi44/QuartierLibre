<?php
/**
 * Section d'accueil pour une catégorie donnée.
 * Args : $args['slug'] (string), $args['label'] (string), $args['count'] (int)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$slug  = $args['slug']  ?? '';
$label = $args['label'] ?? '';
$count = $args['count'] ?? 3;

if ( ! $slug ) { return; }
$term = get_term_by( 'slug', $slug, 'category' );
if ( ! $term ) { return; }

$q = new WP_Query( array(
    'category_name'       => $slug,
    'posts_per_page'      => $count,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
) );

if ( ! $q->have_posts() ) { return; }
?>
<section class="ql-section" aria-label="<?php echo esc_attr( $label ); ?>">
    <header class="ql-section__head">
        <h2 class="ql-section__title"><?php echo esc_html( $label ?: $term->name ); ?></h2>
        <a class="ql-section__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">Voir tous →</a>
    </header>

    <div class="ql-grid ql-grid--3">
        <?php while ( $q->have_posts() ) : $q->the_post();
            get_template_part( 'template-parts/card-article' );
        endwhile; ?>
    </div>
</section>
<?php wp_reset_postdata(); ?>
