<?php
/**
 * Template Name: Bureau des Plaintes
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$notice   = isset( $_GET['plainte'] ) ? sanitize_text_field( wp_unslash( $_GET['plainte'] ) ) : '';
$hero_img = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'ql-hero' ) : '';
?>

<section class="ql-plainte-hero" <?php if ( $hero_img ) echo 'style="--hero-img: url(\'' . esc_url( $hero_img ) . '\');"'; ?>>
    <div class="ql-plainte-hero__overlay"></div>
    <div class="ql-container ql-plainte-hero__inner">
        <span class="ql-plainte-hero__kicker">Parole libre</span>
        <h1 class="ql-plainte-hero__title">Bureau des plaintes</h1>
        <p class="ql-plainte-hero__subtitle">
            Ici, on vous écoute. Logement insalubre, démarche bloquée, violences, services publics défaillants —
            racontez. La rédaction enquête, recoupe, publie.
        </p>
        <div class="ql-plainte-hero__stats">
            <div><strong><?php
                $n = (int) wp_count_posts()->publish;
                echo esc_html( $n );
            ?></strong><span>articles publiés</span></div>
            <div><strong>100%</strong><span>indépendant</span></div>
            <div><strong>0€</strong><span>subvention conditionnée</span></div>
        </div>
    </div>
</section>

<div class="ql-container ql-plainte-wrap">

    <?php while ( have_posts() ) : the_post(); ?>

    <div class="ql-plainte">

        <?php if ( $notice === 'envoye' ) : ?>
            <div class="ql-alert ql-alert--ok">
                <strong>Merci.</strong> Votre plainte a bien été envoyée à la rédaction. Nous revenons vers vous rapidement.
            </div>
        <?php elseif ( $notice === 'erreur' ) : ?>
            <div class="ql-alert ql-alert--ko">
                <strong>Oups.</strong> Il manque des informations. Merci de remplir au moins le type et le message.
            </div>
        <?php endif; ?>

        <?php
        $intro_content = get_the_content();
        if ( trim( wp_strip_all_tags( $intro_content ) ) ) : ?>
            <div class="ql-plainte__intro">
                <?php the_content(); ?>
            </div>
        <?php endif; ?>

        <form class="ql-plainte__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="ql_plainte">
            <?php wp_nonce_field( 'ql_plainte', 'ql_plainte_nonce' ); ?>

            <div class="ql-field">
                <label for="ql_type">Type de problème <span class="ql-req">*</span></label>
                <select id="ql_type" name="ql_type" required>
                    <option value="">— Choisir —</option>
                    <option value="Logement">Logement / habitat</option>
                    <option value="Administratif">Démarche administrative</option>
                    <option value="Sécurité & police">Sécurité &amp; police</option>
                    <option value="Services publics">Services publics / école</option>
                    <option value="Transports">Transports</option>
                    <option value="Emploi">Emploi</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>

            <div class="ql-field-row">
                <div class="ql-field">
                    <label for="ql_quartier">Votre quartier</label>
                    <input id="ql_quartier" name="ql_quartier" type="text" placeholder="Clos Toreau, Malakoff, Bellevue…">
                </div>

                <div class="ql-field">
                    <label for="ql_nom">Prénom / pseudo</label>
                    <input id="ql_nom" name="ql_nom" type="text" autocomplete="given-name">
                </div>
            </div>

            <div class="ql-field">
                <label for="ql_email">Email (si vous voulez une réponse)</label>
                <input id="ql_email" name="ql_email" type="email" autocomplete="email" placeholder="vous@exemple.fr">
                <span class="ql-field--hint">Jamais publié. Sert uniquement à vous répondre.</span>
            </div>

            <div class="ql-field">
                <label for="ql_message">Votre message <span class="ql-req">*</span></label>
                <textarea id="ql_message" name="ql_message" required placeholder="Décrivez la situation : faits, dates, lieux, personnes concernées…"></textarea>
            </div>

            <div class="ql-plainte__submit">
                <button type="submit" class="ql-btn ql-btn--accent ql-btn--lg">Envoyer ma plainte</button>
                <p class="ql-field--hint">
                    Vos données ne sont jamais revendues. Elles servent uniquement à la rédaction.
                    <br>Demande de suppression possible à tout moment.
                </p>
            </div>
        </form>

    </div>

    <?php endwhile; ?>

</div>

<?php get_footer(); ?>
