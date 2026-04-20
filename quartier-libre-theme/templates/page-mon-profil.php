<?php
/**
 * Template Name: Mon profil
 *
 * Espace personnel — remplace complètement /wp-admin/profile.php pour
 * les utilisateurs non-admin. Permet de :
 *   - Uploader une photo personnalisée (remplace l'avatar Gravatar)
 *   - Changer display name, prénom, nom, bio
 *   - Changer email + mot de passe
 *   - (Pour les admins : accès à la vraie admin WP via un lien)
 *
 * Soumission via admin-post.php?action=ql_profile_update (cf. functions.php).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Pas connecté → redirection vers /connexion/
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( '/mon-profil/' ) ), home_url( '/connexion/' ) ) );
    exit;
}

$current = wp_get_current_user();
$avatar_id  = (int) get_user_meta( $current->ID, 'ql_avatar_id', true );
$avatar_url = $avatar_id ? wp_get_attachment_image_url( $avatar_id, array( 200, 200 ) ) : get_avatar_url( $current->ID, array( 'size' => 200 ) );

// Messages (updated / err)
$updated   = ! empty( $_GET['updated'] );
$err       = ! empty( $_GET['err'] ) ? sanitize_key( $_GET['err'] ) : '';
$err_msg   = '';
switch ( $err ) {
    case 'pwd_short':     $err_msg = 'Le mot de passe doit faire au moins 8 caractères.'; break;
    case 'pwd_mismatch':  $err_msg = 'Les deux mots de passe ne correspondent pas.'; break;
    case 'update_failed': $err_msg = 'Échec de la mise à jour. Vérifiez vos données.'; break;
    case 'bad_image':     $err_msg = 'Format d\'image invalide. Utilisez JPG, PNG, WEBP ou GIF.'; break;
    case 'image_too_big': $err_msg = 'Image trop lourde (max 2 Mo).'; break;
    case 'upload_failed': $err_msg = 'Échec de l\'upload. Réessayez.'; break;
}

get_header();
?>

<div class="ql-profil-page">

    <section class="ql-profil-hero">
        <div class="ql-container">
            <span class="ql-profil-hero__kicker">Espace personnel</span>
            <h1 class="ql-profil-hero__title">Mon profil</h1>
            <p class="ql-profil-hero__lede">
                Gérez votre photo, votre nom public et vos informations de compte.
            </p>
        </div>
    </section>

    <section class="ql-profil-main">
        <div class="ql-container">

            <?php if ( $updated ) : ?>
                <div class="ql-connexion-notice ql-connexion-notice--success">
                    ✓ Profil mis à jour.
                </div>
            <?php endif; ?>
            <?php if ( $err_msg ) : ?>
                <div class="ql-connexion-notice ql-connexion-notice--error">
                    <?php echo esc_html( $err_msg ); ?>
                </div>
            <?php endif; ?>

            <form class="ql-profil-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ql_profile_update">
                <?php wp_nonce_field( 'ql_profile_update', 'ql_profile_nonce' ); ?>

                <div class="ql-profil-grid">

                    <!-- ── Colonne gauche : PHOTO ── -->
                    <section class="ql-profil-card ql-profil-card--photo">
                        <h2 class="ql-profil-card__title">Photo de profil</h2>

                        <div class="ql-profil-avatar-wrap">
                            <img id="ql-avatar-preview" src="<?php echo esc_url( $avatar_url ); ?>" alt="Votre avatar" class="ql-profil-avatar">
                        </div>

                        <div class="ql-profil-avatar-actions">
                            <label for="ql_avatar_input" class="ql-btn ql-btn--ink">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Changer
                            </label>
                            <input type="file" id="ql_avatar_input" name="ql_avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden>

                            <?php if ( $avatar_id ) : ?>
                                <label class="ql-profil-remove-check">
                                    <input type="checkbox" name="ql_avatar_remove" value="1">
                                    <span>Retirer la photo</span>
                                </label>
                            <?php endif; ?>
                        </div>

                        <p class="ql-field--hint">
                            JPG, PNG, WEBP ou GIF. 2 Mo max.<br>
                            Recommandé : image carrée, au moins 400×400 px.
                        </p>
                    </section>

                    <!-- ── Colonne droite : INFOS ── -->
                    <section class="ql-profil-card">
                        <h2 class="ql-profil-card__title">Informations publiques</h2>

                        <div class="ql-field">
                            <label for="display_name">Nom affiché publiquement <span class="ql-req">*</span></label>
                            <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $current->display_name ); ?>" required>
                            <span class="ql-field--hint">Ce nom apparaît sous vos commentaires et contributions.</span>
                        </div>

                        <div class="ql-field-row">
                            <div class="ql-field">
                                <label for="first_name">Prénom</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $current->first_name ); ?>">
                            </div>
                            <div class="ql-field">
                                <label for="last_name">Nom</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $current->last_name ); ?>">
                            </div>
                        </div>

                        <div class="ql-field">
                            <label for="description">Bio courte</label>
                            <textarea id="description" name="description" rows="3" maxlength="500"><?php echo esc_textarea( $current->description ); ?></textarea>
                            <span class="ql-field--hint">Quelques mots sur vous. Max 500 caractères.</span>
                        </div>
                    </section>

                    <!-- ── Compte ── -->
                    <section class="ql-profil-card ql-profil-card--wide">
                        <h2 class="ql-profil-card__title">Compte & sécurité</h2>

                        <div class="ql-field">
                            <label for="user_email">Adresse email</label>
                            <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $current->user_email ); ?>" autocomplete="email">
                            <span class="ql-field--hint">Utilisée pour la connexion et les notifications.</span>
                        </div>

                        <hr class="ql-profil-sep">

                        <p class="ql-profil-card__hint">Laissez vide pour ne pas changer le mot de passe.</p>

                        <div class="ql-field-row">
                            <div class="ql-field">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
                                <span class="ql-field--hint">8 caractères min.</span>
                            </div>
                            <div class="ql-field">
                                <label for="confirm_password">Confirmer</label>
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                    </section>

                </div>

                <div class="ql-profil-actions">
                    <button type="submit" class="ql-btn ql-btn--accent ql-btn--lg">Enregistrer les modifications</button>
                    <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="ql-profil-logout">Se déconnecter</a>
                </div>

                <?php if ( current_user_can( 'edit_posts' ) ) : ?>
                    <p class="ql-profil-admin-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="vertical-align:-2px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Vous avez des droits rédacteur/administrateur →
                        <a href="<?php echo esc_url( admin_url() ); ?>">Accéder à l'administration WordPress</a>
                    </p>
                <?php endif; ?>

            </form>

        </div>
    </section>

</div>

<script>
// Preview live de la photo avant upload
(function(){
  var input = document.getElementById('ql_avatar_input');
  var preview = document.getElementById('ql-avatar-preview');
  if (!input || !preview) return;
  input.addEventListener('change', function(){
    if (!input.files || !input.files[0]) return;
    var r = new FileReader();
    r.onload = function(e){ preview.src = e.target.result; };
    r.readAsDataURL(input.files[0]);
  });
})();
</script>

<?php get_footer(); ?>
