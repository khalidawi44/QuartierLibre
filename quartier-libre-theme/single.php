<?php
/**
 * Single — page article. Architecture v3.
 * Banner image plein largeur, titre centré au-dessus de la colonne lecture.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-reading-progress" aria-hidden="true"><span></span></div>

<?php while ( have_posts() ) : the_post();
    $cat = ql_primary_category();
    $share_url   = urlencode( get_permalink() );
    $share_title = urlencode( get_the_title() );
    $has_img     = has_post_thumbnail();
?>

<article class="ql-post">

    <header class="ql-post__header">
        <div class="ql-post__header-inner">
            <?php if ( $cat ) : ?>
                <a class="ql-post__cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
            <?php endif; ?>

            <h1 class="ql-post__title"><?php the_title(); ?></h1>

            <?php if ( $has_img ) : ?>
                <div class="ql-post__banner">
                    <?php the_post_thumbnail( 'ql-hero', array(
                        'loading'       => 'eager',
                        'fetchpriority' => 'high',
                        'decoding'      => 'async',
                        'class'         => 'ql-post__banner-img',
                    ) ); ?>
                    <?php $cap = get_the_post_thumbnail_caption();
                    if ( $cap ) : ?>
                        <div class="ql-post__banner-caption"><?php echo esc_html( $cap ); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( has_excerpt() ) : ?>
                <p class="ql-post__lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <?php endif; ?>

            <div class="ql-post__meta">
                <span>Par <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></span>
                <span class="ql-post__dot" aria-hidden="true">·</span>
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                <?php $mins = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ); ?>
                <span class="ql-post__dot" aria-hidden="true">·</span>
                <span><?php echo esc_html( $mins ); ?> min de lecture</span>
            </div>
        </div>
    </header>

    <div class="ql-article-layout">

        <!-- SIDEBAR GAUCHE (30%) — mêmes widgets que la home -->
        <?php get_template_part( 'template-parts/sidebar-home' ); ?>

        <div class="ql-post__body">
        <div class="ql-post__content">
            <?php the_content(); ?>
            <?php wp_link_pages( array( 'before' => '<nav class="ql-pagination">Pages :', 'after' => '</nav>' ) ); ?>
        </div>

        <?php
        $src_name = get_post_meta( get_the_ID(), '_ql_source_name', true );
        $src_url  = get_post_meta( get_the_ID(), '_ql_source_url',  true );
        if ( $src_name || $src_url ) : ?>
            <aside class="ql-post__source">
                <span class="ql-post__source-label">Source</span>
                <?php if ( $src_url ) : ?>
                    <a href="<?php echo esc_url( $src_url ); ?>" target="_blank" rel="noopener nofollow">
                        <?php echo esc_html( $src_name ?: $src_url ); ?>
                        <span aria-hidden="true">↗</span>
                    </a>
                <?php else : ?>
                    <span><?php echo esc_html( $src_name ); ?></span>
                <?php endif; ?>
            </aside>
        <?php endif; ?>

        <?php $tags = get_the_tags(); if ( $tags ) : ?>
            <div class="ql-post__tags">
                <?php foreach ( $tags as $t ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="ql-post__share" aria-label="Partager">
            <span>Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="Facebook">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 22v-8h3l1-4h-4V7.5c0-1 .3-1.5 1.8-1.5H17V2.2c-.3 0-1.5-.2-2.8-.2-2.8 0-4.2 1.6-4.2 4.5V10H7v4h3v8h3z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener" aria-label="X">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://wa.me/?text=<?php echo $share_title; ?>%20<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2m5.1 14.3c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.5-.5-2.7-1.2-4.4-3.9-4.6-4-.1-.2-1-1.3-1-2.5s.6-1.8.9-2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4s.7 1.7.8 1.8c.1.2.1.3 0 .5 0 .2-.1.3-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1 .9 1.9 1.2 2.2 1.3.3.2.4.1.6-.1l.8-1c.2-.3.4-.2.7-.1s1.6.7 1.9.9c.3.2.5.2.5.4.1.2.1.7-.1 1.3"/></svg>
            </a>
            <a href="mailto:?subject=<?php echo $share_title; ?>&body=<?php echo $share_url; ?>" aria-label="Email">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
            </a>
        </div>

        <?php
        $author_id   = get_the_author_meta( 'ID' );
        $author_bio  = get_the_author_meta( 'description', $author_id );
        if ( $author_bio ) : ?>
            <aside class="ql-post__author">
                <div class="ql-post__author-avatar"><?php echo get_avatar( $author_id, 72 ); ?></div>
                <div class="ql-post__author-body">
                    <p class="ql-post__author-kicker">L'auteur·ice</p>
                    <h3 class="ql-post__author-name"><a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></a></h3>
                    <p class="ql-post__author-bio"><?php echo wp_kses_post( wpautop( $author_bio ) ); ?></p>
                </div>
            </aside>
        <?php endif; ?>

    </div><!-- /.ql-post__body -->
    </div><!-- /.ql-article-layout -->

    <?php // ── Commentaires : juste sous l'article, avant les articles liés ── ?>
    <?php if ( comments_open() || get_comments_number() ) : ?>
        <div class="ql-container ql-comments-wrap">
            <div class="ql-comments"><?php comments_template(); ?></div>
        </div>
    <?php endif; ?>

    <?php
    // ── Articles liés au sujet : priorité aux tags partagés ─────
    $current_id = get_the_ID();
    $tags       = wp_get_post_tags( $current_id, array( 'fields' => 'ids' ) );

    $related_args = array(
        'post_type'           => 'post',
        'posts_per_page'      => 3,
        'post__not_in'        => array( $current_id ),
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );

    // 1. D'abord : articles partageant au moins un tag (même sujet)
    if ( ! empty( $tags ) ) {
        $related_args['tag__in'] = $tags;
    }
    $related = new WP_Query( $related_args );

    // 2. Fallback : articles de la même catégorie
    if ( ! $related->have_posts() && $cat ) {
        unset( $related_args['tag__in'] );
        $related_args['category__in'] = array( $cat->term_id );
        $related = new WP_Query( $related_args );
    }

    if ( $related->have_posts() ) : ?>
        <section class="ql-post__related">
            <div class="ql-container">
                <header class="ql-section__head">
                    <h2 class="ql-section__title">Sur le même sujet</h2>
                </header>
                <div class="ql-grid ql-grid--3">
                    <?php while ( $related->have_posts() ) : $related->the_post();
                        get_template_part( 'template-parts/card-article' );
                    endwhile; ?>
                </div>
            </div>
        </section>
    <?php endif;
    wp_reset_postdata();
    ?>


</article>

<?php endwhile; ?>

<?php get_footer(); ?>
