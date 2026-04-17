<?php
/**
 * Template Name: Bureau des Plaintes
 *
 * Formulaire participatif pour signaler logement, démarches, sécurité, etc.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$notice = isset( $_GET['plainte'] ) ? sanitize_text_field( wp_unslash( $_GET['plainte'] ) ) : '';
?>

<div class="ql-container">

    <?php while ( have_posts() ) : the_post(); ?>

    <header class="ql-archive-header">
        <span class="ql-archive-header__kicker">Bureau des plaintes</span>
        <h1><?php the_title(); ?></h1>
    </header>

    <div class="ql-plainte">

        <?php if ( $notice === 'envoye' ) : ?>
            <div class="ql-alert ql-alert--ok">Merci. Votre plainte a bien été envoyée à la rédaction. Nous revenons vers vous rapidement.</div>
        <?php elseif ( $notice === 'erreur' ) : ?>
            <div class="ql-alert ql-alert--ko">Il manque des informations. Merci de remplir au moins le type et le message.</div>
        <?php endif; ?>

        <div class="ql-plainte__intro">
            <?php if ( get_the_content() ) {
                the_content();
            } else { ?>
                <p>Ici, la parole est aux habitants. Racontez-nous ce qui se passe dans votre quartier :
                problème de logement, démarche administrative bloquée, abus de pouvoir, difficulté d'accès aux services publics…
                La rédaction lit tout, recoupe, enquête, et publie.</p>
            <?php } ?>
        </div>

        <form class="ql-plainte__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="ql_plainte">
            <?php wp_nonce_field( 'ql_plainte', 'ql_plainte_nonce' ); ?>

            <div class="ql-field">
                <label for="ql_type">Type de problème *</label>
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

            <div class="ql-field">
                <label for="ql_quartier">Votre quartier / commune</label>
                <input id="ql_quartier" name="ql_quartier" type="text" placeholder="Ex. : Clos Toreau, Malakoff, Bellevue…">
            </div>

            <div class="ql-field">
                <label for="ql_nom">Votre prénom (ou pseudo)</label>
                <input id="ql_nom" name="ql_nom" type="text" autocomplete="given-name">
                <span class="ql-field--hint">Pour vous recontacter. Nous ne publions jamais d'identité sans accord.</span>
            </div>

            <div class="ql-field">
                <label for="ql_email">Email (si vous voulez une réponse)</label>
                <input id="ql_email" name="ql_email" type="email" autocomplete="email">
            </div>

            <div class="ql-field">
                <label for="ql_message">Votre message *</label>
                <textarea id="ql_message" name="ql_message" required placeholder="Décrivez la situation, les faits, les dates, les personnes concernées si vous le souhaitez…"></textarea>
            </div>

            <p>
                <button type="submit" class="ql-btn ql-btn--accent">Envoyer ma plainte</button>
            </p>

            <p class="ql-field--hint">Vos données ne sont jamais revendues. Elles servent uniquement à la rédaction pour enquêter. Vous pouvez demander à tout moment leur suppression.</p>
        </form>

    </div>

    <?php endwhile; ?>

</div>

<?php get_footer(); ?>
