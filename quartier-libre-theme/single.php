<?php
/**
 * Single — page article.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-reading-progress" aria-hidden="true"><span></span></div>

<div class="ql-container">

    <?php while ( have_posts() ) : the_post();
        $cat = ql_primary_category();
        $share_url   = urlencode( get_permalink() );
        $share_title = urlencode( get_the_title() );
    ?>

    <article <?php post_class( 'ql-article' ); ?>>

        <header class="ql-article__header">
            <?php if ( $cat ) : ?>
                <a class="ql-article__cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
            <?php endif; ?>

            <h1 class="ql-article__title"><?php the_title(); ?></h1>

            <?php if ( has_excerpt() ) : ?>
                <p class="ql-article__lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <?php endif; ?>

            <div class="ql-article__meta">
                <span>Par <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></span>
                <span aria-hidden="true">·</span>
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                <?php $mins = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ); ?>
                <span aria-hidden="true">·</span>
                <span><?php echo esc_html( $mins ); ?> min de lecture</span>
            </div>
        </header>

        <?php if ( has_post_thumbnail() ) : ?>
            <figure class="ql-article__hero-media">
                <?php the_post_thumbnail( 'ql-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
                <?php $caption = get_the_post_thumbnail_caption();
                if ( $caption ) : ?><figcaption class="ql-muted" style="font-size:.85rem;padding:.5rem;"><?php echo esc_html( $caption ); ?></figcaption><?php endif; ?>
            </figure>
        <?php endif; ?>

        <div class="ql-article__body">
            <?php the_content(); ?>
            <?php wp_link_pages( array( 'before' => '<nav class="ql-pagination">Pages :', 'after' => '</nav>' ) ); ?>
        </div>

        <?php
        // Encart Source externe (affiché si meta _ql_source_name ou _ql_source_url présentes)
        $src_name = get_post_meta( get_the_ID(), '_ql_source_name', true );
        $src_url  = get_post_meta( get_the_ID(), '_ql_source_url',  true );
        if ( $src_name || $src_url ) : ?>
            <aside class="ql-source">
                <span class="ql-source__label">Source</span>
                <?php if ( $src_url ) : ?>
                    <a href="<?php echo esc_url( $src_url ); ?>" target="_blank" rel="noopener nofollow">
                        <?php echo esc_html( $src_name ?: $src_url ); ?>
                        <span class="ql-source__ext" aria-hidden="true">↗</span>
                    </a>
                <?php else : ?>
                    <span><?php echo esc_html( $src_name ); ?></span>
                <?php endif; ?>
            </aside>
        <?php endif; ?>

        <?php $tags = get_the_tags(); if ( $tags ) : ?>
            <div class="ql-article__tags">
                <?php foreach ( $tags as $t ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $t ) ); ?>">#<?php echo esc_html( $t->name ); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        // Bio de l'auteur (si description renseignée dans le profil WP)
        $author_id    = get_the_author_meta( 'ID' );
        $author_bio   = get_the_author_meta( 'description', $author_id );
        $author_name  = get_the_author_meta( 'display_name', $author_id );
        $author_link  = get_author_posts_url( $author_id );
        if ( $author_bio ) : ?>
            <aside class="ql-author-box">
                <div class="ql-author-box__avatar"><?php echo get_avatar( $author_id, 72 ); ?></div>
                <div class="ql-author-box__body">
                    <p class="ql-author-box__kicker">Par</p>
                    <h3 class="ql-author-box__name"><a href="<?php echo esc_url( $author_link ); ?>"><?php echo esc_html( $author_name ); ?></a></h3>
                    <p class="ql-author-box__bio"><?php echo wp_kses_post( wpautop( $author_bio ) ); ?></p>
                </div>
            </aside>
        <?php endif; ?>

        <div class="ql-article__share" aria-label="Partager">
            <span>Partager :</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="Partager sur Facebook">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 22v-8h3l1-4h-4V7.5c0-1 .3-1.5 1.8-1.5H17V2.2c-.3 0-1.5-.2-2.8-.2-2.8 0-4.2 1.6-4.2 4.5V10H7v4h3v8h3z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener" aria-label="Partager sur X/Twitter">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://wa.me/?text=<?php echo $share_title; ?>%20<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="Partager sur WhatsApp">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2m5.1 14.3c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.5-.5-2.7-1.2-4.4-3.9-4.6-4-.1-.2-1-1.3-1-2.5s.6-1.8.9-2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4s.7 1.7.8 1.8c.1.2.1.3 0 .5 0 .2-.1.3-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1 .9 1.9 1.2 2.2 1.3.3.2.4.1.6-.1l.8-1c.2-.3.4-.2.7-.1s1.6.7 1.9.9c.3.2.5.2.5.4.1.2.1.7-.1 1.3"/></svg>
            </a>
            <a href="mailto:?subject=<?php echo $share_title; ?>&body=<?php echo $share_url; ?>" aria-label="Partager par email">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
            </a>
        </div>

    </article>

    <?php
    // Articles liés (même catégorie)
    if ( $cat ) :
        $related = new WP_Query( array(
            'post_type'           => 'post',
            'posts_per_page'      => 3,
            'category__in'        => array( $cat->term_id ),
            'post__not_in'        => array( get_the_ID() ),
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ) );
        if ( $related->have_posts() ) : ?>
            <section class="ql-section" aria-label="À lire aussi">
                <header class="ql-section__head">
                    <h2 class="ql-section__title">À lire aussi</h2>
                </header>
                <div class="ql-grid ql-grid--3">
                    <?php while ( $related->have_posts() ) : $related->the_post();
                        get_template_part( 'template-parts/card-article' );
                    endwhile; ?>
                </div>
            </section>
        <?php endif;
        wp_reset_postdata();
    endif;
    ?>

    <?php if ( comments_open() || get_comments_number() ) : ?>
        <div class="ql-comments"><?php comments_template(); ?></div>
    <?php endif; ?>

    <?php endwhile; ?>

</div>

<?php get_footer(); ?>
