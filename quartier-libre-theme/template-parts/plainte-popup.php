<?php
/**
 * Popup Bureau des Plaintes — bouton fixe à droite, modal au clic.
 * Inclus dans footer.php pour être présent sur toutes les pages.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Pas sur la page Bureau des plaintes elle-même (éviter doublon)
if ( is_page() ) {
    $tpl = get_page_template_slug( get_queried_object_id() );
    if ( strpos( (string) $tpl, 'page-bureau-plaintes' ) !== false ) return;
}
?>

<button type="button" class="ql-plainte-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="ql-plainte-modal">
    <span class="ql-plainte-trigger__icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <line x1="12" y1="8" x2="12" y2="13"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
    </span>
    <span class="ql-plainte-trigger__label">Bureau des plaintes</span>
</button>

<div class="ql-plainte-modal" id="ql-plainte-modal" role="dialog" aria-modal="true" aria-labelledby="ql-plainte-modal-title" hidden>
    <div class="ql-plainte-modal__backdrop" data-close></div>
    <div class="ql-plainte-modal__panel">
        <button type="button" class="ql-plainte-modal__close" aria-label="Fermer" data-close>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="ql-plainte-modal__header">
            <span class="ql-plainte-modal__kicker">Parole libre</span>
            <h2 id="ql-plainte-modal-title">Bureau des plaintes</h2>
            <p class="ql-plainte-modal__subtitle">
                Problème de logement, démarche bloquée, violence, service public défaillant — racontez. La rédaction enquête, recoupe, publie.
            </p>
        </div>

        <form class="ql-plainte__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="ql_plainte">
            <?php wp_nonce_field( 'ql_plainte', 'ql_plainte_nonce' ); ?>

            <div class="ql-field">
                <label for="ql_type_popup">Type de problème <span class="ql-req">*</span></label>
                <select id="ql_type_popup" name="ql_type" required>
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
                    <label for="ql_quartier_popup">Votre quartier</label>
                    <input id="ql_quartier_popup" name="ql_quartier" type="text" placeholder="Bellevue, Malakoff…">
                </div>
                <div class="ql-field">
                    <label for="ql_nom_popup">Prénom / pseudo</label>
                    <input id="ql_nom_popup" name="ql_nom" type="text" autocomplete="given-name">
                </div>
            </div>

            <div class="ql-field">
                <label for="ql_email_popup">Email (pour réponse)</label>
                <input id="ql_email_popup" name="ql_email" type="email" autocomplete="email" placeholder="vous@exemple.fr">
                <span class="ql-field--hint">Jamais publié. Sert uniquement à vous répondre.</span>
            </div>

            <div class="ql-field">
                <label for="ql_message_popup">Votre message <span class="ql-req">*</span></label>
                <textarea id="ql_message_popup" name="ql_message" required rows="5" placeholder="Décrivez la situation : faits, dates, lieux…"></textarea>
            </div>

            <div class="ql-plainte__submit">
                <button type="submit" class="ql-btn ql-btn--accent ql-btn--lg">Envoyer</button>
                <p class="ql-field--hint">Jamais revendu. Suppression possible à tout moment.</p>
            </div>
        </form>
    </div>
</div>
