<?php
/**
 * Comments — template simple et sobre.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( post_password_required() ) { return; }
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>
        <h2>
            <?php
            $n = get_comments_number();
            printf(
                _n( '%s commentaire', '%s commentaires', $n, 'quartier-libre' ),
                number_format_i18n( $n )
            );
            ?>
        </h2>

        <ol class="comment-list">
            <?php wp_list_comments( array(
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size'=> 40,
            ) ); ?>
        </ol>

        <?php the_comments_navigation( array(
            'prev_text' => '← plus anciens',
            'next_text' => 'plus récents →',
        ) ); ?>

    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="ql-muted">Les commentaires sont fermés.</p>
    <?php endif; ?>

    <?php comment_form( array(
        'title_reply'        => 'Laisser un commentaire',
        'class_submit'       => 'ql-btn ql-btn--accent',
        'comment_field'      => '<p class="ql-field"><label for="comment">Votre commentaire</label><textarea id="comment" name="comment" required></textarea></p>',
        'fields'             => array(
            'author' => '<p class="ql-field"><label for="author">Nom *</label><input id="author" name="author" type="text" required></p>',
            'email'  => '<p class="ql-field"><label for="email">Email (non publié) *</label><input id="email" name="email" type="email" required></p>',
        ),
    ) ); ?>

</div>
