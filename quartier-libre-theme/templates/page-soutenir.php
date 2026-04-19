<?php
/**
 * Template Name: Soutenir
 * Page de dons — tiered buttons + alternatives.
 *
 * Configuration : WP Admin > Apparence > Personnaliser, ou directement
 * dans wp_options :
 *   - ql_donorbox_url : URL cible des boutons (HelloAsso, Donorbox, …).
 *     Le paramètre ?amount= est automatiquement ajouté pour les tiers.
 *   - ql_rib_iban      : IBAN pour virement bancaire (optionnel)
 *   - ql_rib_bic       : BIC (optionnel)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$donation_url = get_option( 'ql_donorbox_url', '' );
if ( empty( $donation_url ) ) {
    // Placeholder — l'admin doit configurer l'URL réelle (HelloAsso recommandé en FR)
    $donation_url = 'https://www.helloasso.com/associations/quartier-libre-nantes';
}

$rib_iban = trim( (string) get_option( 'ql_rib_iban', '' ) );
$rib_bic  = trim( (string) get_option( 'ql_rib_bic', '' ) );

$tiers = array(
    array( 'amount' => 5,   'label' => '5 €',   'desc' => 'Un café militant' ),
    array( 'amount' => 15,  'label' => '15 €',  'desc' => 'Un article publié', 'highlight' => true ),
    array( 'amount' => 30,  'label' => '30 €',  'desc' => 'Une enquête' ),
    array( 'amount' => 100, 'label' => '100 €', 'desc' => 'Un média qui dure' ),
);

function ql_donation_link( $base, $amount = null, $recurring = false ) {
    $sep = ( strpos( $base, '?' ) !== false ) ? '&' : '?';
    $args = array();
    if ( $amount ) $args[] = 'amount=' . (int) $amount;
    if ( $recurring ) $args[] = 'recurring=1';
    return $base . ( ! empty( $args ) ? $sep . implode( '&', $args ) : '' );
}
?>

<div class="ql-soutenir-page">

    <section class="ql-soutenir-hero">
        <div class="ql-container">
            <span class="ql-soutenir-hero__kicker">Soutenir</span>
            <h1 class="ql-soutenir-hero__title">Sans vous, pas de&nbsp;Quartier Libre.</h1>
            <p class="ql-soutenir-hero__lede">
                Pas de publicité. Pas d'actionnaire. Pas de subvention conditionnée.
                Seulement des lecteur·ices qui décident que ce média doit exister.
                <strong>Chaque euro finance une enquête, un témoignage, une voix qui monte.</strong>
            </p>
        </div>
    </section>

    <section class="ql-donation-block">
        <div class="ql-container">

            <header class="ql-donation-block__head">
                <h2>Faire un don</h2>
                <p>Choisissez un montant. Paiement sécurisé via notre plateforme partenaire.</p>
            </header>

            <div class="ql-donation-tiers">
                <?php foreach ( $tiers as $t ) :
                    $url   = ql_donation_link( $donation_url, $t['amount'] );
                    $hl    = ! empty( $t['highlight'] ) ? ' ql-donation-tier--highlight' : '';
                ?>
                    <a class="ql-donation-tier<?php echo $hl; ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
                        <?php if ( ! empty( $t['highlight'] ) ) : ?>
                            <span class="ql-donation-tier__badge">Le plus soutenu</span>
                        <?php endif; ?>
                        <span class="ql-donation-tier__amount"><?php echo esc_html( $t['label'] ); ?></span>
                        <span class="ql-donation-tier__desc"><?php echo esc_html( $t['desc'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="ql-donation-block__custom">
                <a class="ql-btn ql-btn--outline ql-btn--lg" href="<?php echo esc_url( $donation_url ); ?>" target="_blank" rel="noopener">
                    Montant libre →
                </a>
                <span class="ql-donation-block__recurring">
                    ou <a href="<?php echo esc_url( ql_donation_link( $donation_url, null, true ) ); ?>" target="_blank" rel="noopener">mettre en place un don mensuel</a>
                </span>
            </div>

            <p class="ql-donation-block__tax">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="vertical-align:-2px;margin-right:.3rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <strong>Réduction fiscale 66 %</strong> si éligible — un don de 15 € vous coûte réellement 5,10 €.
            </p>

        </div>
    </section>

    <section class="ql-donation-alt">
        <div class="ql-container">
            <h2>Autres façons de soutenir</h2>

            <div class="ql-donation-alt__grid">

                <article class="ql-donation-alt__card">
                    <div class="ql-donation-alt__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    </div>
                    <h3>Virement bancaire</h3>
                    <?php if ( $rib_iban ) : ?>
                        <p>Effectuez un virement direct :</p>
                        <code class="ql-rib">IBAN : <?php echo esc_html( $rib_iban ); ?><?php if ( $rib_bic ) echo '<br>BIC : ' . esc_html( $rib_bic ); ?></code>
                    <?php else : ?>
                        <p>Pour un don par virement (associations, gros montants), contactez-nous <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">via le formulaire de contact</a> — on vous transmet le RIB.</p>
                    <?php endif; ?>
                </article>

                <article class="ql-donation-alt__card">
                    <div class="ql-donation-alt__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <h3>Lettre d'info gratuite</h3>
                    <p>Recevez les articles de la semaine. Aucun tracker, pas de revente de données. C'est déjà un soutien.</p>
                    <a class="ql-btn ql-btn--ghost" href="#ql-nl-email">S'abonner →</a>
                </article>

                <article class="ql-donation-alt__card">
                    <div class="ql-donation-alt__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                    </div>
                    <h3>Partagez</h3>
                    <p>Un article qui vous parle ? Diffusez-le. Le bouche-à-oreille reste notre meilleur allié face aux algorithmes.</p>
                </article>

                <article class="ql-donation-alt__card">
                    <div class="ql-donation-alt__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <h3>Témoignez</h3>
                    <p>Une injustice, une galère administrative, un abus ? Racontez-nous via le <a href="<?php echo esc_url( home_url( '/bureau-des-plaintes/' ) ); ?>">Bureau des plaintes</a>. On relaie, on enquête.</p>
                </article>

            </div>
        </div>
    </section>

    <?php while ( have_posts() ) : the_post();
        $content = get_the_content();
        if ( trim( $content ) ) : ?>
            <section class="ql-container ql-soutenir-page__extra">
                <div class="ql-post__content"><?php the_content(); ?></div>
            </section>
        <?php endif;
    endwhile; ?>

</div>

<?php get_footer(); ?>
