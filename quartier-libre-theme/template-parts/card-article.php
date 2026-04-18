<?php
/**
 * Carte d'article.
 * Args : $args['compact'] (bool), $args['hide_excerpt'] (bool)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$compact = ! empty( $args['compact'] );
$hide_excerpt = ! empty( $args['hide_excerpt'] );
$cat = ql_primary_category();
?>
<article <?php post_class( 'ql-card' . ( $compact ? ' ql-card--compact' : '' ) ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <a class="ql-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
            <?php the_post_thumbnail( $compact ? 'ql-thumb' : 'ql-card', array(
                'loading'  => 'lazy',
                'decoding' => 'async',
            ) ); ?>
            <?php if ( $cat && ! $compact ) : ?>
                <span class="ql-card__cat"><?php echo esc_html( $cat->name ); ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <div class="ql-card__body">
        <?php if ( $cat && $compact ) : ?>
            <a class="ql-card__cat ql-card__cat--inline" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
        <?php endif; ?>

        <h3 class="ql-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <div class="ql-card__meta">
            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
            <span aria-hidden="true">·</span>
            <span><?php the_author(); ?></span>
        </div>

        <?php if ( ! $hide_excerpt && ! $compact ) : ?>
            <p class="ql-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
        <?php endif; ?>
    </div>
</article>
