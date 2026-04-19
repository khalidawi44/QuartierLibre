<?php
/**
 * Template Name: Soutenir
 * Page de dons — PayPal intégré (SDK) + alternatives.
 *
 * Configuration :
 *   - ql_paypal_client_id : Client ID PayPal REST (défaut : celui de QL)
 *   - ql_donorbox_url     : URL HelloAsso/Donorbox de secours (bouton « montant libre »)
 *   - ql_rib_iban / ql_rib_bic : RIB pour virement bancaire (optionnel)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

// Client ID PayPal — public par design (utilisé dans le JS frontend)
$paypal_client_id = get_option( 'ql_paypal_client_id', 'AVyYRWTPC5wdtmdOCsjSrKp4_Em2kuQumBN2Mh9jBlbR8qcisZQj0yY8294PV0eWowqVS85ZOp1vjoN0' );

$donation_url = get_option( 'ql_donorbox_url', '' );
if ( empty( $donation_url ) ) {
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

$merci = isset( $_GET['merci'] ) && $_GET['merci'] === '1';
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

    <?php if ( $merci ) : ?>
    <section class="ql-donation-thanks">
        <div class="ql-container">
            <div class="ql-donation-thanks__box">
                <strong>Merci pour votre don.</strong>
                Votre soutien finance directement nos prochaines enquêtes.
                Un reçu PayPal vous a été envoyé par email.
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="ql-donation-block">
        <div class="ql-container">

            <header class="ql-donation-block__head">
                <h2>Faire un don</h2>
                <p>Choisissez un montant. Paiement sécurisé via PayPal (carte bancaire ou compte PayPal).</p>
            </header>

            <!-- Sélecteur de montant (QL styled) -->
            <div class="ql-donation-tiers" role="radiogroup" aria-label="Montant du don">
                <?php foreach ( $tiers as $t ) :
                    $hl       = ! empty( $t['highlight'] ) ? ' ql-donation-tier--highlight' : '';
                    $selected = ! empty( $t['highlight'] ) ? ' is-active' : '';
                ?>
                    <button type="button"
                            class="ql-donation-tier<?php echo $hl . $selected; ?>"
                            data-amount="<?php echo esc_attr( $t['amount'] ); ?>"
                            role="radio"
                            aria-checked="<?php echo ! empty( $t['highlight'] ) ? 'true' : 'false'; ?>">
                        <?php if ( ! empty( $t['highlight'] ) ) : ?>
                            <span class="ql-donation-tier__badge">Le plus soutenu</span>
                        <?php endif; ?>
                        <span class="ql-donation-tier__amount"><?php echo esc_html( $t['label'] ); ?></span>
                        <span class="ql-donation-tier__desc"><?php echo esc_html( $t['desc'] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Montant libre (input) -->
            <div class="ql-donation-custom">
                <label for="ql-custom-amount">Ou saisissez votre montant :</label>
                <div class="ql-donation-custom__wrap">
                    <input type="number" id="ql-custom-amount" min="1" step="1" placeholder="Libre" inputmode="numeric">
                    <span class="ql-donation-custom__currency">€</span>
                </div>
            </div>

            <!-- Bouton PayPal (rendu par le SDK) -->
            <div id="ql-paypal-button-container" class="ql-paypal-button"></div>

            <!-- Bouton HelloAsso (AJAX → checkout-intent → redirect) -->
            <?php if ( get_option( 'ql_helloasso_client_id' ) && get_option( 'ql_helloasso_client_secret' ) ) : ?>
                <div class="ql-helloasso-wrap">
                    <span class="ql-helloasso-or">— ou —</span>
                    <button type="button" id="ql-helloasso-btn" class="ql-btn ql-btn--helloasso ql-btn--lg">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="vertical-align:-5px;margin-right:.5rem;">
                            <path d="M12 2 2 8.5v7L12 22l10-6.5v-7zm0 2.3 7.7 5L12 14.7 4.3 9.3z"/>
                        </svg>
                        Donner avec HelloAsso
                        <span class="ql-helloasso-btn__sub">(reçu fiscal automatique)</span>
                    </button>
                    <p id="ql-helloasso-err" class="ql-helloasso-err" hidden></p>
                </div>
            <?php endif; ?>

            <!-- FAQ accordion (remplace l'ancien bloc fiscal) -->
            <div class="ql-donation-faq">
                <h3 class="ql-donation-faq__title">Vos questions avant de donner</h3>

                <details class="ql-faq-item">
                    <summary>Que finance mon don concrètement ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Tout. Hébergement du site, captation vidéo, déplacements pour les enquêtes (bus, TGV), achats matériels (dictaphones, vidéos), protection juridique en cas de poursuites, et rémunération modeste des journalistes qui consacrent leurs soirées à Quartier Libre.</p>
                        <p>Aucun dirigeant n'est payé. Aucun actionnaire n'existe. <strong>100 % de votre don sert la rédaction et les enquêtes.</strong></p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Mes informations personnelles sont-elles protégées ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Oui. Les dons PayPal/HelloAsso sont traités par nos partenaires de paiement — nous ne voyons que votre prénom et le montant. Votre adresse email reste confidentielle et ne sera jamais revendue, partagée, ou utilisée pour du marketing tiers.</p>
                        <p>Vous pouvez demander la suppression de toutes vos données à tout moment via <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">le formulaire de contact</a>.</p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Mon don est-il déductible d'impôts ?</summary>
                    <div class="ql-faq-item__content">
                        <p><strong>Non, pas encore.</strong> Quartier Libre n'est pas (pour le moment) reconnu d'intérêt général par l'administration fiscale. Nous ne pouvons donc pas émettre de reçu fiscal CERFA.</p>
                        <p>C'est un statut que nous cherchons à obtenir — il demande des démarches longues. Si c'est un critère essentiel pour vous, sachez que <strong>votre don reste précieux sans déduction</strong>, et que nous vous tiendrons informé·e dès que la reconnaissance fiscale sera effective.</p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Puis-je faire un don mensuel récurrent ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Pas encore via notre intégration directe, mais c'est possible via HelloAsso : cliquez sur le bouton "Donner avec HelloAsso" et choisissez "Don mensuel" sur leur page. Vous pouvez interrompre à tout moment depuis votre espace HelloAsso.</p>
                        <p>Un don mensuel de 5 € couvre plus que trois dons ponctuels de 15 € sur l'année — et ça nous permet de <strong>planifier nos enquêtes à plus long terme</strong>.</p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Qui gère l'argent ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Quartier Libre est porté juridiquement par une structure associative. La trésorerie est tenue par un collectif de deux personnes, dont les comptes sont audités annuellement et <strong>publiés en transparence sur ce site</strong> (bilan financier à venir dans la rubrique À propos).</p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Puis-je donner en liquide ou par chèque ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Oui. Pour un don en liquide, prenez rendez-vous via <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">le formulaire de contact</a> — on organise une rencontre dans votre quartier. Pour un chèque, précisez-le également via le contact et on vous envoie l'adresse postale.</p>
                    </div>
                </details>

                <details class="ql-faq-item">
                    <summary>Est-ce que je recevrai une newsletter ?</summary>
                    <div class="ql-faq-item__content">
                        <p>Seulement si vous vous inscrivez volontairement via <a href="#ql-nl-email">la newsletter du site</a>. Donner ne vous inscrit à rien. Vous pouvez donner anonymement sans aucun suivi marketing.</p>
                    </div>
                </details>
            </div>

        </div>
    </section>

    <?php if ( $paypal_client_id ) : ?>
    <!-- PayPal JS SDK — rendu des boutons + logique du montant dynamique -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr( $paypal_client_id ); ?>&currency=EUR&intent=capture&disable-funding=credit,card"></script>
    <script>
    (function(){
      if (!window.paypal) return;
      var DEFAULT_AMOUNT = 15;
      var selectedAmount = DEFAULT_AMOUNT;

      var tiers  = document.querySelectorAll('.ql-donation-tier');
      var input  = document.getElementById('ql-custom-amount');

      function setActive(amount, fromInput) {
        selectedAmount = Math.max(1, parseInt(amount, 10) || DEFAULT_AMOUNT);
        tiers.forEach(function(t){
          var match = parseInt(t.dataset.amount, 10) === selectedAmount && !fromInput;
          t.classList.toggle('is-active', match);
          t.setAttribute('aria-checked', match ? 'true' : 'false');
        });
      }

      tiers.forEach(function(t){
        t.addEventListener('click', function(){
          if (input) input.value = '';
          setActive(parseInt(t.dataset.amount, 10), false);
        });
      });
      if (input) {
        input.addEventListener('input', function(){
          if (this.value) setActive(this.value, true);
        });
      }

      paypal.Buttons({
        style: {
          layout: 'vertical',
          color:  'gold',
          shape:  'rect',
          label:  'donate',
          height: 48,
        },
        createOrder: function(data, actions){
          return actions.order.create({
            purchase_units: [{
              amount: {
                value: selectedAmount.toFixed(2),
                currency_code: 'EUR'
              },
              description: 'Don Quartier Libre — média indépendant'
            }]
          });
        },
        onApprove: function(data, actions){
          return actions.order.capture().then(function(){
            window.location.href = '<?php echo esc_url( add_query_arg( 'merci', '1', get_permalink() ) ); ?>';
          });
        },
        onError: function(err){
          console.error('PayPal error:', err);
          alert('Une erreur est survenue avec PayPal. Réessayez ou utilisez HelloAsso.');
        }
      }).render('#ql-paypal-button-container');

      // ── Bouton HelloAsso (AJAX checkout-intent) ──
      var hellobtn = document.getElementById('ql-helloasso-btn');
      var helloerr = document.getElementById('ql-helloasso-err');
      if (hellobtn) {
        hellobtn.addEventListener('click', async function(){
          hellobtn.disabled = true;
          hellobtn.textContent = 'Création du paiement…';
          if (helloerr) helloerr.hidden = true;
          try {
            var res = await fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
              body: 'action=ql_helloasso_checkout'
                  + '&nonce=<?php echo wp_create_nonce( 'ql_helloasso' ); ?>'
                  + '&amount=' + encodeURIComponent(selectedAmount),
            });
            var data = await res.json();
            if (data && data.success && data.data && data.data.url) {
              window.location.href = data.data.url;
              return;
            }
            throw new Error((data && data.data) || 'Erreur inconnue');
          } catch (e) {
            console.error('HelloAsso error:', e);
            if (helloerr) {
              helloerr.textContent = 'Erreur HelloAsso : ' + e.message + '. Essayez PayPal.';
              helloerr.hidden = false;
            }
            hellobtn.disabled = false;
            hellobtn.innerHTML = hellobtn.dataset.originalHtml || 'Donner avec HelloAsso';
          }
        });
        hellobtn.dataset.originalHtml = hellobtn.innerHTML;
      }
    })();
    </script>
    <?php endif; ?>

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
