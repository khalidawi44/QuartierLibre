<?php
/**
 * Category — page de rubrique avec article vedette + grille.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$term = get_queried_object();
$count = isset( $term->count ) ? (int) $term->count : 0;

// Premier article = vedette
$have_featured = false;
if ( have_posts() ) {
    the_post();
    $have_featured = true;
}
?>

<div class="ql-container">

    <header class="ql-cat-header">
        <span class="ql-archive-header__kicker">Rubrique</span>
        <h1 class="ql-cat-header__title"><?php single_cat_title(); ?></h1>
        <?php if ( $desc = term_description() ) : ?>
            <div class="ql-cat-header__desc"><?php echo wp_kses_post( $desc ); ?></div>
        <?php endif; ?>
        <p class="ql-cat-header__count"><?php echo esc_html( $count ); ?> article<?php echo $count > 1 ? 's' : ''; ?> publié<?php echo $count > 1 ? 's' : ''; ?></p>
    </header>

    <?php
    // Sur la rubrique "Info locale" : afficher la mosaïque des quartiers HLM
    $cat_slug = $term ? $term->slug : '';
    if ( 'infos-locale' === $cat_slug ) {
        get_template_part( 'template-parts/quartiers' );
    }
    ?>

    <?php if ( $have_featured ) : ?>
        <article class="ql-cat-featured">
            <?php if ( has_post_thumbnail() ) : ?>
                <a class="ql-cat-featured__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                    <?php the_post_thumbnail( 'ql-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
                </a>
            <?php endif; ?>
            <div class="ql-cat-featured__body">
                <span class="ql-card__cat ql-card__cat--static">À la une · <?php echo esc_html( single_cat_title( '', false ) ); ?></span>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <p class="ql-cat-featured__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 42, '…' ) ); ?></p>
                <div class="ql-card__meta">
                    <span>Par <?php the_author(); ?></span>
                    <span aria-hidden="true">·</span>
                    <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                </div>
            </div>
        </article>
    <?php endif; ?>

    <?php if ( have_posts() ) : ?>
        <section class="ql-section" aria-label="Autres articles">
            <header class="ql-section__head">
                <h2 class="ql-section__title">À lire aussi dans cette rubrique</h2>
            </header>
            <div class="ql-grid ql-grid--3">
                <?php while ( have_posts() ) : the_post();
                    get_template_part( 'template-parts/card-article' );
                endwhile; ?>
            </div>
        </section>

        <nav class="ql-pagination" aria-label="Pagination">
            <?php echo paginate_links( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?>
        </nav>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
