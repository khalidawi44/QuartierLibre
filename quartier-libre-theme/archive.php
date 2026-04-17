<?php
/**
 * Archive — listing par catégorie, tag, auteur, date.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

<div class="ql-container">

    <header class="ql-archive-header">
        <span class="ql-archive-header__kicker"><?php
            if ( is_category() )    echo 'Rubrique';
            elseif ( is_tag() )     echo 'Mot-clé';
            elseif ( is_author() )  echo 'Auteur·ice';
            elseif ( is_date() )    echo 'Archives';
            else                    echo 'Articles';
        ?></span>
        <h1><?php
            if ( is_category() || is_tag() ) single_term_title();
            elseif ( is_author() )           the_author();
            elseif ( is_year() )             echo esc_html( get_the_date( 'Y' ) );
            elseif ( is_month() )            echo esc_html( get_the_date( 'F Y' ) );
            elseif ( is_day() )              echo esc_html( get_the_date() );
            else                             post_type_archive_title();
        ?></h1>
        <?php
        $desc = term_description();
        if ( $desc ) echo '<p class="ql-archive-header__desc">' . wp_kses_post( $desc ) . '</p>';
        ?>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="ql-grid ql-grid--3">
            <?php while ( have_posts() ) : the_post();
                get_template_part( 'template-parts/card-article' );
            endwhile; ?>
        </div>

        <nav class="ql-pagination" aria-label="Pagination">
            <?php echo paginate_links( array(
                'prev_text' => '←',
                'next_text' => '→',
            ) ); ?>
        </nav>
    <?php else : ?>
        <p>Aucun article pour l'instant dans cette rubrique.</p>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
