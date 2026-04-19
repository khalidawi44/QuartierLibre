<?php
/**
 * Template Name: Connexion / Inscription
 *
 * Page /connexion/ (auto-créée par functions.php) — deux colonnes :
 * formulaire de connexion à gauche, d'inscription à droite. Les deux
 * formulaires postent vers wp-login.php (natif WordPress) ; ici on
 * n'habille que la présentation.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Si déjà connecté, on redirige vers l'accueil ou le profil.
if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

// Récupère un éventuel redirect après connexion (?redirect_to=…)
$redirect_to = ! empty( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : home_url( '/' );

// Message d'erreur/succès depuis wp-login.php
$login_err   = ! empty( $_GET['login'] )        ? sanitize_text_field( $_GET['login'] )        : '';
$register    = ! empty( $_GET['registration'] ) ? sanitize_text_field( $_GET['registration'] ) : '';
$checkemail  = ! empty( $_GET['checkemail'] )   ? sanitize_text_field( $_GET['checkemail'] )   : '';

get_header();
?>

<div class="ql-connexion-page">

    <section class="ql-connexion-hero">
        <div class="ql-container">
            <span class="ql-connexion-hero__kicker">Espace membres</span>
            <h1 class="ql-connexion-hero__title">Rejoindre Quartier Libre</h1>
            <p class="ql-connexion-hero__lede">
                Connectez-vous pour commenter, témoigner, participer.
                Un média, ça se fait <strong>avec</strong> ses lecteurs, pas juste pour eux.
            </p>
        </div>
    </section>

    <section class="ql-connexion-forms">
        <div class="ql-container">

            <?php if ( $login_err === 'failed' ) : ?>
                <div class="ql-connexion-notice ql-connexion-notice--error">
                    Identifiants incorrects. Réessayez ou <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">mot de passe oublié ?</a>
                </div>
            <?php elseif ( $register === 'disabled' ) : ?>
                <div class="ql-connexion-notice ql-connexion-notice--error">
                    L'inscription est désactivée par l'administrateur.
                </div>
            <?php elseif ( $checkemail === 'registered' ) : ?>
                <div class="ql-connexion-notice ql-connexion-notice--success">
                    Inscription validée ! Vérifiez votre email pour confirmer.
                </div>
            <?php endif; ?>

            <div class="ql-connexion-grid">

                <article class="ql-connexion-card ql-connexion-card--login">
                    <div class="ql-connexion-card__badge">Déjà inscrit·e</div>
                    <h2 class="ql-connexion-card__title">Connexion</h2>

                    <form class="ql-connexion-form" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">

                        <div class="ql-field">
                            <label for="ql_user_login">Identifiant ou email</label>
                            <input type="text" id="ql_user_login" name="log" autocomplete="username" required>
                        </div>

                        <div class="ql-field">
                            <label for="ql_user_pass">Mot de passe</label>
                            <input type="password" id="ql_user_pass" name="pwd" autocomplete="current-password" required>
                        </div>

                        <div class="ql-connexion-form__row">
                            <label class="ql-connexion-form__remember">
                                <input type="checkbox" name="rememberme" value="forever">
                                <span>Se souvenir de moi</span>
                            </label>
                            <a class="ql-connexion-form__forgot" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Oublié ?</a>
                        </div>

                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
                        <button type="submit" class="ql-btn ql-btn--accent ql-btn--lg ql-connexion-form__submit">
                            Se connecter
                        </button>
                    </form>
                </article>

                <article class="ql-connexion-card ql-connexion-card--register">
                    <div class="ql-connexion-card__badge ql-connexion-card__badge--accent">Nouveau</div>
                    <h2 class="ql-connexion-card__title">Créer un compte</h2>

                    <?php if ( ! get_option( 'users_can_register' ) ) : ?>
                        <p class="ql-connexion-form__note">
                            L'inscription publique est actuellement désactivée.<br>
                            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contactez la rédaction</a> pour obtenir un accès.
                        </p>
                    <?php else : ?>

                        <form class="ql-connexion-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post">

                            <div class="ql-field">
                                <label for="ql_reg_login">Pseudo (visible en commentaire)</label>
                                <input type="text" id="ql_reg_login" name="user_login" autocomplete="username" required>
                            </div>

                            <div class="ql-field">
                                <label for="ql_reg_email">Votre email</label>
                                <input type="email" id="ql_reg_email" name="user_email" autocomplete="email" required>
                                <span class="ql-field--hint">Le mot de passe vous sera envoyé par email.</span>
                            </div>

                            <div class="ql-field ql-field--check">
                                <label>
                                    <input type="checkbox" name="ql_rgpd" required>
                                    <span>J'accepte que mes données soient traitées pour la gestion de mon compte. <a href="<?php echo esc_url( home_url( '/politique-confidentialite/' ) ); ?>" target="_blank" rel="noopener">Confidentialité</a></span>
                                </label>
                            </div>

                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
                            <button type="submit" class="ql-btn ql-btn--ink ql-btn--lg ql-connexion-form__submit">
                                M'inscrire
                            </button>
                        </form>

                    <?php endif; ?>
                </article>

            </div>

            <p class="ql-connexion-page__footer">
                Problème ? <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Écrivez-nous</a>.
            </p>

        </div>
    </section>

</div>

<?php get_footer(); ?>
