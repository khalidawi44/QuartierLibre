<?php
/**
 * Popup Bureau des Plaintes — bouton fixe à droite, modal au clic.
 * Inclus dans footer.php pour être présent sur toutes les pages.
 *
 * Le contenu de la modal s'adapte au contexte de l'article via le
 * système de variantes (includes/plainte-variants.php) :
 *   - default    : générique
 *   - immigration: aide juridique, urgence expulsion
 *   - police     : violences policières, contrôles
 *   - logement   : bailleur, punaises, insalubrité
 *
 * Variante choisie par post meta _ql_plainte_variant (frontmatter
 * `plainte_variant: immigration`) ou détectée par catégorie.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Pas sur la page Bureau des plaintes elle-même (éviter doublon)
if ( is_page() ) {
    $tpl = get_page_template_slug( get_queried_object_id() );
    if ( strpos( (string) $tpl, 'page-bureau-plaintes' ) !== false ) return;
}

$V = function_exists( 'ql_plainte_current_variant' ) ? ql_plainte_current_variant() : array(
    'label'    => 'Bureau des plaintes',
    'kicker'   => 'Parole libre',
    'subtitle' => 'Un témoignage à partager ? Racontez — on relaie, on enquête.',
    'type_options' => array(
        'Logement' => 'Logement / habitat',
        'Autre'    => 'Autre',
    ),
    'extra_fields'     => array(),
    'emergency_notice' => '',
);

// Libellé du bouton flottant adapté à la variante
$trigger_label = 'Bureau des plaintes';
if ( ! empty( $V['kicker'] ) ) {
    $kicker_lower = strtolower( $V['kicker'] );
    if ( $kicker_lower === 'urgence' )         $trigger_label = 'Aide & urgence';
    elseif ( $kicker_lower === 'témoignage' )   $trigger_label = 'Signaler — police';
    elseif ( $kicker_lower === 'habitat' )      $trigger_label = 'Signaler — logement';
    elseif ( $kicker_lower === 'panoptique' )   $trigger_label = 'Signaler — caméra';
    elseif ( $kicker_lower === 'solidarité' )   $trigger_label = 'Solidarité';
}
$variant_key = function_exists( 'ql_plainte_current_variant_key' ) ? ql_plainte_current_variant_key() : 'default';
?>

<button type="button" class="ql-plainte-trigger ql-plainte-trigger--<?php echo esc_attr( $variant_key ); ?>" aria-haspopup="dialog" aria-expanded="false" aria-controls="ql-plainte-modal">
    <span class="ql-plainte-trigger__icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <line x1="12" y1="8" x2="12" y2="13"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
    </span>
    <span class="ql-plainte-trigger__label"><?php echo esc_html( $trigger_label ); ?></span>
</button>

<div class="ql-plainte-modal" id="ql-plainte-modal" role="dialog" aria-modal="true" aria-labelledby="ql-plainte-modal-title" hidden>
    <div class="ql-plainte-modal__backdrop" data-close></div>
    <div class="ql-plainte-modal__panel">
        <button type="button" class="ql-plainte-modal__close" aria-label="Fermer" data-close>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="ql-plainte-modal__header">
            <span class="ql-plainte-modal__kicker"><?php echo esc_html( $V['kicker'] ); ?></span>
            <h2 id="ql-plainte-modal-title"><?php echo esc_html( $V['label'] ); ?></h2>
            <p class="ql-plainte-modal__subtitle">
                <?php echo wp_kses( $V['subtitle'], array( 'strong' => array(), 'em' => array() ) ); ?>
            </p>
        </div>

        <?php if ( ! empty( $V['emergency_notice'] ) ) : ?>
            <div class="ql-plainte-modal__emergency">
                ⚠ <?php echo wp_kses( $V['emergency_notice'], array( 'a' => array( 'href' => array(), 'class' => array() ) ) ); ?>
            </div>
        <?php endif; ?>

        <form class="ql-plainte__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="ql_plainte">
            <input type="hidden" name="ql_variant" value="<?php echo esc_attr( $variant_key ); ?>">
            <?php wp_nonce_field( 'ql_plainte', 'ql_plainte_nonce' ); ?>

            <div class="ql-field">
                <label for="ql_type_popup">Type <span class="ql-req">*</span></label>
                <select id="ql_type_popup" name="ql_type" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ( $V['type_options'] as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php // Champs extras (ex : date/lieu pour police, bailleur pour logement) ?>
            <?php if ( ! empty( $V['extra_fields'] ) ) : foreach ( $V['extra_fields'] as $field ) : ?>
                <div class="ql-field">
                    <label for="<?php echo esc_attr( $field['name'] ); ?>_popup"><?php echo esc_html( $field['label'] ); ?></label>
                    <?php if ( $field['type'] === 'select' ) : ?>
                        <select id="<?php echo esc_attr( $field['name'] ); ?>_popup" name="<?php echo esc_attr( $field['name'] ); ?>">
                            <?php foreach ( $field['options'] as $v => $l ) : ?>
                                <option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <input id="<?php echo esc_attr( $field['name'] ); ?>_popup"
                               name="<?php echo esc_attr( $field['name'] ); ?>"
                               type="<?php echo esc_attr( $field['type'] ); ?>"
                               <?php if ( ! empty( $field['placeholder'] ) ) : ?>placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"<?php endif; ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>

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
