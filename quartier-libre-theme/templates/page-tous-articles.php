<?php
/**
 * Template Name: Tous les articles
 *
 * Page /tous-les-articles/ — liste paginée de tous les articles du site
 * avec un filtre rapide par catégorie principale (tabs).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

// Filtre actif via ?cat=slug (default = tous)
$active_cat = isset( $_GET['cat'] ) ? sanitize_title( $_GET['cat'] ) : '';
$paged      = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 1 ) );

// Catégories principales à proposer comme filtres
$filter_tabs = array(
    ''              => 'Tous',
    'infos-locale'  => 'Infos locale',
    'france'        => 'France',
    'international' => 'International',
    'luttes'        => 'Luttes',
    'histoire'      => 'Histoire',
);

// Query principale avec pagination
$query_args = array(
    'post_type'      => 'post',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'ignore_sticky_posts' => true,
);
if ( $active_cat ) {
    $query_args['category_name'] = $active_cat;
}
$all_articles = new WP_Query( $query_args );
$total_count  = (int) $all_articles->found_posts;
?>

<div class="ql-ta-page">

    <section class="ql-ta-hero">
        <div class="ql-container">
            <span class="ql-ta-hero__kicker">Archives</span>
            <h1 class="ql-ta-hero__title">Tous les articles</h1>
            <p class="ql-ta-hero__lede">
                <strong><?php echo esc_html( $total_count ); ?></strong> article<?php echo $total_count > 1 ? 's' : ''; ?>
                <?php if ( $active_cat ) : ?>
                    dans <strong><?php echo esc_html( $filter_tabs[ $active_cat ] ?? $active_cat ); ?></strong>
                <?php else : ?>
                    au total, toutes catégories confondues.
                <?php endif; ?>
                Du plus récent au plus ancien.
            </p>
        </div>
    </section>

    <div class="ql-container ql-ta-container">

        <!-- Filtres -->
        <nav class="ql-ta-filters" aria-label="Filtre par catégorie">
            <?php foreach ( $filter_tabs as $slug => $label ) :
                $url = $slug
                    ? add_query_arg( 'cat', $slug, get_permalink() )
                    : remove_query_arg( 'cat', get_permalink() );
                $is_active = ( $active_cat === $slug );
            ?>
                <a class="ql-ta-filter<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ( $all_articles->have_posts() ) : ?>

            <div class="ql-grid ql-grid--3 ql-ta-grid">
                <?php while ( $all_articles->have_posts() ) : $all_articles->the_post();
                    get_template_part( 'template-parts/card-article' );
                endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php
            $total_pages = (int) $all_articles->max_num_pages;
            if ( $total_pages > 1 ) : ?>
                <nav class="ql-ta-pagination" aria-label="Pagination">
                    <?php
                    $big = 999999999;
                    echo paginate_links( array(
                        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'    => '?paged=%#%',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'prev_text' => '← Précédent',
                        'next_text' => 'Suivant →',
                        'add_args'  => $active_cat ? array( 'cat' => $active_cat ) : false,
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

        <?php else : ?>
            <div class="ql-ta-empty">
                <p>Aucun article pour cette catégorie pour le moment.</p>
                <a class="ql-btn ql-btn--accent" href="<?php echo esc_url( remove_query_arg( 'cat', get_permalink() ) ); ?>">Voir tous les articles →</a>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

    </div>

    <?php // Contenu libre éditable en WP admin (optionnel)
    while ( have_posts() ) : the_post();
        $content = get_the_content();
        if ( trim( $content ) ) : ?>
            <section class="ql-container ql-ta-page__extra">
                <div class="ql-post__content"><?php the_content(); ?></div>
            </section>
        <?php endif;
    endwhile; ?>

</div>

<?php get_footer(); ?>
