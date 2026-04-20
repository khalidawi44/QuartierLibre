<?php
/**
 * Section « Soutenir Quartier Libre » — encart avec formulaire inline.
 *
 * Affiché sur la home (cf. front-page.php) et partout où le thème
 * appelle get_template_part('template-parts/soutenir').
 *
 * Comportement :
 *   - Sélecteur de montant (5/15/30/100 €) + montant libre
 *   - Bouton PayPal (SDK JS rendu dynamiquement)
 *   - Bouton HelloAsso (si les clés API sont configurées)
 *   - Lien "FAQ complète" vers /soutenir/ pour les détails
 *
 * Pas de mention « déductible » : Quartier Libre n'est pas (encore)
 * reconnu d'intérêt général → pas de reçu fiscal, pas de déduction.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$paypal_client_id  = get_option( 'ql_paypal_client_id', 'AVyYRWTPC5wdtmdOCsjSrKp4_Em2kuQumBN2Mh9jBlbR8qcisZQj0yY8294PV0eWowqVS85ZOp1vjoN0' );
$has_helloasso     = get_option( 'ql_helloasso_client_id' ) && get_option( 'ql_helloasso_client_secret' );
$soutenir_page     = get_page_by_path( 'soutenir' );
$soutenir_url      = $soutenir_page ? get_permalink( $soutenir_page ) : home_url( '/soutenir/' );
?>
<section class="ql-section ql-soutenir ql-soutenir--embed" aria-label="Soutenir Quartier Libre">
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

        <div class="ql-soutenir__cta ql-soutenir-embed">
            <div class="ql-soutenir-embed__tiers" role="radiogroup" aria-label="Montant du don">
                <button type="button" class="ql-soutenir-tier" data-amount="5"><span>5 €</span></button>
                <button type="button" class="ql-soutenir-tier is-active" data-amount="15" aria-checked="true"><span>15 €</span></button>
                <button type="button" class="ql-soutenir-tier" data-amount="30"><span>30 €</span></button>
                <button type="button" class="ql-soutenir-tier" data-amount="100"><span>100 €</span></button>
            </div>

            <div class="ql-soutenir-embed__custom">
                <input type="number" id="ql-soutenir-embed-amount" min="1" step="1" placeholder="Libre" inputmode="numeric" aria-label="Montant libre">
                <span>€</span>
            </div>

            <div id="ql-soutenir-embed-paypal" class="ql-soutenir-embed__paypal"></div>

            <?php if ( $has_helloasso ) : ?>
                <button type="button" id="ql-soutenir-embed-ha" class="ql-btn ql-btn--helloasso ql-soutenir-embed__ha">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 2 8.5v7L12 22l10-6.5v-7zm0 2.3 7.7 5L12 14.7 4.3 9.3z"/></svg>
                    HelloAsso
                </button>
                <p id="ql-soutenir-embed-err" class="ql-soutenir-embed__err" hidden></p>
            <?php endif; ?>

            <a class="ql-soutenir-embed__more" href="<?php echo esc_url( $soutenir_url ); ?>">Plus d'options → /soutenir/</a>
        </div>

    </div>
</section>

<?php if ( $paypal_client_id ) : ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr( $paypal_client_id ); ?>&currency=EUR&intent=capture&disable-funding=credit,card" data-no-lazy="1"></script>
<?php endif; ?>
<script>
(function(){
  if (!window.paypal) return;
  var DEFAULT = 15, selected = DEFAULT;
  var embed = document.querySelector('.ql-soutenir--embed');
  if (!embed) return;
  var tiers = embed.querySelectorAll('.ql-soutenir-tier');
  var input = document.getElementById('ql-soutenir-embed-amount');

  function setActive(amount, fromInput) {
    selected = Math.max(1, parseInt(amount, 10) || DEFAULT);
    tiers.forEach(function(t){
      var match = parseInt(t.dataset.amount, 10) === selected && !fromInput;
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
  if (input) input.addEventListener('input', function(){
    if (this.value) setActive(this.value, true);
  });

  paypal.Buttons({
    style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'donate', height: 40 },
    createOrder: function(data, actions){
      return actions.order.create({
        purchase_units: [{
          amount: { value: selected.toFixed(2), currency_code: 'EUR' },
          description: 'Don Quartier Libre — média indépendant'
        }]
      });
    },
    onApprove: function(data, actions){
      return actions.order.capture().then(function(){
        window.location.href = '<?php echo esc_url( $soutenir_url ); ?>?merci=1';
      });
    }
  }).render('#ql-soutenir-embed-paypal');

  <?php if ( $has_helloasso ) : ?>
  var haBtn = document.getElementById('ql-soutenir-embed-ha');
  var haErr = document.getElementById('ql-soutenir-embed-err');
  if (haBtn) {
    haBtn.addEventListener('click', function(){
      haBtn.disabled = true;
      haErr.hidden = true;
      fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'action=ql_helloasso_checkout'
            + '&nonce=<?php echo wp_create_nonce( 'ql_helloasso' ); ?>'
            + '&amount=' + encodeURIComponent(selected)
      })
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j && j.redirect_url) { window.location.href = j.redirect_url; }
        else {
          haErr.textContent = (j && j.error) ? j.error : 'Erreur HelloAsso. Réessayez.';
          haErr.hidden = false;
          haBtn.disabled = false;
        }
      })
      .catch(function(){
        haErr.textContent = 'Erreur réseau. Réessayez.';
        haErr.hidden = false;
        haBtn.disabled = false;
      });
    });
  }
  <?php endif; ?>
})();
</script>
