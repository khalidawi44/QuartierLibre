<?php
/**
 * Section « Soutenir Quartier Libre » — appel aux dons (Donorbox).
 *
 * Configuration dans WP : Apparence → Personnaliser → (ou via
 * les options ci-dessous). Par défaut, on utilise un lien vers
 * la page /soutenir/ ; si l'option `ql_donorbox_url` est définie
 * (URL de campagne Donorbox), le bouton pointe directement dessus.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$donorbox_url = get_option( 'ql_donorbox_url', '' );
if ( empty( $donorbox_url ) ) {
    $soutenir = get_page_by_path( 'soutenir' );
    $donorbox_url = $soutenir ? get_permalink( $soutenir ) : home_url( '/soutenir/' );
}
?>
<section class="ql-section ql-soutenir" aria-label="Soutenir Quartier Libre">
    <div class="ql-soutenir__inner">

        <div class="ql-soutenir__icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </div>

        <div class="ql-soutenir__body">
            <span class="ql-soutenir__kicker">Média indépendant</span>
            <h2 class="ql-soutenir__title">Sans vous, pas de Quartier Libre.</h2>
            <p class="ql-soutenir__blurb">
                Pas de publicité, pas d'actionnaire, pas de subvention conditionnée.
                Quartier Libre vit grâce à ses lectrices et ses lecteurs.
                Un don, même petit, c'est un article en plus, une enquête qui sort.
            </p>
        </div>

        <div class="ql-soutenir__cta">
            <a class="ql-btn ql-btn--accent ql-btn--lg" href="<?php echo esc_url( $donorbox_url ); ?>"<?php echo strpos( $donorbox_url, 'donorbox' ) !== false ? ' target="_blank" rel="noopener"' : ''; ?>>
                Faire un don
            </a>
            <small class="ql-muted ql-soutenir__note">Paiement sécurisé · Déductible 66 % si éligible</small>
        </div>

    </div>
</section>
