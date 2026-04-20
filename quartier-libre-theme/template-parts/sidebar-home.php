<?php
/**
 * Sidebar homepage — 30% droite, layout 70/30.
 * Ordre fixe demandé :
 *   1. Recherche d'article
 *   2. Nos rubriques (arbre de catégories)
 *   3. Cagnotte (mini formulaire dons)
 *   4. Rendez-vous futurs (articles mobilisations récents)
 *   5. Réseaux sociaux
 *
 * Chaque widget est un <aside> distinct pour la sémantique + le CSS
 * sticky (seul le widget "nos rubriques" est sticky, pas toute la
 * sidebar — sinon ça masque les autres).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$paypal_client_id = get_option( 'ql_paypal_client_id', 'AVyYRWTPC5wdtmdOCsjSrKp4_Em2kuQumBN2Mh9jBlbR8qcisZQj0yY8294PV0eWowqVS85ZOp1vjoN0' );
$has_helloasso    = get_option( 'ql_helloasso_client_id' ) && get_option( 'ql_helloasso_client_secret' );
$soutenir_url     = home_url( '/soutenir/' );

// Réseaux sociaux — lus depuis les options WP (option_name => label + icône).
// Définir par wp-cli : wp option update ql_social_mastodon "https://..."
$socials = array(
    'mastodon'  => array( 'label' => 'Mastodon',   'url' => get_option( 'ql_social_mastodon',  '' ) ),
    'twitter'   => array( 'label' => 'X / Twitter','url' => get_option( 'ql_social_twitter',   '' ) ),
    'instagram' => array( 'label' => 'Instagram',  'url' => get_option( 'ql_social_instagram', '' ) ),
    'facebook'  => array( 'label' => 'Facebook',   'url' => get_option( 'ql_social_facebook',  'https://www.facebook.com/profile.php?id=61578685711984' ) ),
    'telegram'  => array( 'label' => 'Telegram',   'url' => get_option( 'ql_social_telegram',  '' ) ),
    'rss'       => array( 'label' => 'Flux RSS',   'url' => get_feed_link() ),
);
?>
<aside class="ql-sidebar ql-sidebar--home" role="complementary">

    <!-- 1. RECHERCHE ─────────────────────────────────────────────── -->
    <section class="ql-widget ql-widget--search" aria-label="Rechercher un article">
        <h3 class="ql-widget__title">Rechercher un article</h3>
        <form class="ql-widget-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <label for="ql-sidebar-s" class="ql-visually-hidden">Rechercher</label>
            <input id="ql-sidebar-s" type="search" name="s" placeholder="Mots-clés, quartier, thème…" value="<?php echo esc_attr( get_search_query() ); ?>" required>
            <button type="submit" aria-label="Lancer la recherche">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </form>
    </section>

    <!-- 2. NOS RUBRIQUES ──────────────────────────────────────────── -->
    <section class="ql-widget ql-widget--rubriques" aria-label="Nos rubriques">
        <h3 class="ql-widget__title">Nos rubriques</h3>
        <ul class="ql-widget-rubriques">
            <?php
            $tree = function_exists( 'ql_categories_tree' ) ? ql_categories_tree() : array();
            foreach ( $tree as $slug => $data ) :
                $term = get_term_by( 'slug', $slug, 'category' );
                if ( ! $term || is_wp_error( $term ) ) continue;
                $count = (int) $term->count;
                $url   = get_term_link( $term );
                ?>
                <li class="ql-widget-rubrique ql-widget-rubrique--<?php echo esc_attr( $slug ); ?>">
                    <a href="<?php echo esc_url( $url ); ?>">
                        <span class="ql-widget-rubrique__label"><?php echo esc_html( $data['label'] ); ?></span>
                        <?php if ( $count ) : ?>
                            <span class="ql-widget-rubrique__count"><?php echo esc_html( $count ); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="ql-widget__more">
            <a href="<?php echo esc_url( home_url( '/rubriques/' ) ); ?>">Toutes les rubriques →</a>
        </p>
    </section>

    <!-- 3. CAGNOTTE ───────────────────────────────────────────────── -->
    <section class="ql-widget ql-widget--cagnotte" aria-label="Cagnotte Quartier Libre">
        <h3 class="ql-widget__title">Cagnotte</h3>
        <p class="ql-widget-cagnotte__blurb">
            Pas de pub, pas d'actionnaire, pas de subvention conditionnée.
            <strong>Chaque don finance une enquête.</strong>
        </p>

        <div class="ql-widget-cagnotte__tiers" role="radiogroup" aria-label="Montant du don">
            <button type="button" class="ql-cagnotte-tier" data-amount="5">5 €</button>
            <button type="button" class="ql-cagnotte-tier is-active" data-amount="15" aria-checked="true">15 €</button>
            <button type="button" class="ql-cagnotte-tier" data-amount="30">30 €</button>
            <button type="button" class="ql-cagnotte-tier" data-amount="50">50 €</button>
        </div>

        <div class="ql-widget-cagnotte__custom">
            <input type="number" id="ql-cagnotte-amount" min="1" step="1" placeholder="Libre" inputmode="numeric" aria-label="Montant libre">
            <span>€</span>
        </div>

        <div id="ql-cagnotte-paypal" class="ql-widget-cagnotte__paypal"></div>

        <?php if ( $has_helloasso ) : ?>
            <button type="button" id="ql-cagnotte-ha" class="ql-widget-cagnotte__ha">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 2 8.5v7L12 22l10-6.5v-7zm0 2.3 7.7 5L12 14.7 4.3 9.3z"/></svg>
                HelloAsso
            </button>
            <p id="ql-cagnotte-err" class="ql-widget-cagnotte__err" hidden></p>
        <?php endif; ?>

        <a class="ql-widget__more" href="<?php echo esc_url( $soutenir_url ); ?>">Virement, chèque, FAQ →</a>
    </section>

    <!-- 4. RENDEZ-VOUS FUTURS ─────────────────────────────────────── -->
    <?php
    // On affiche UNIQUEMENT les articles avec event_date >= aujourd'hui.
    // Les articles passés (ex: Bloquons tout 09/2025) sont exclus
    // même s'ils sont dans la catégorie mobilisations.
    $today_str = current_time( 'Y-m-d' );
    $rdv_query = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => 4,
        'no_found_rows'  => true,
        'meta_key'       => '_ql_event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_ql_event_date',
                'value'   => $today_str,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    ) );
    ?>
    <section class="ql-widget ql-widget--rdv" aria-label="Rendez-vous militants">
        <h3 class="ql-widget__title">Rendez-vous à venir</h3>
        <?php if ( $rdv_query->have_posts() ) : ?>
            <ul class="ql-widget-rdv">
                <?php while ( $rdv_query->have_posts() ) : $rdv_query->the_post();
                    $event_date = get_post_meta( get_the_ID(), '_ql_event_date', true );
                    $event_ts   = $event_date ? strtotime( $event_date ) : 0;
                ?>
                    <li class="ql-widget-rdv__item">
                        <a href="<?php the_permalink(); ?>">
                            <time class="ql-widget-rdv__date" datetime="<?php echo esc_attr( $event_date ); ?>">
                                <span class="ql-widget-rdv__day"><?php echo $event_ts ? esc_html( date_i18n( 'd', $event_ts ) ) : '—'; ?></span>
                                <span class="ql-widget-rdv__month"><?php echo $event_ts ? esc_html( mb_strtoupper( date_i18n( 'M', $event_ts ) ) ) : ''; ?></span>
                            </time>
                            <span class="ql-widget-rdv__title"><?php echo esc_html( wp_trim_words( get_the_title(), 12, '…' ) ); ?></span>
                        </a>
                    </li>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
        <?php else : ?>
            <p class="ql-widget-rdv__empty">
                Aucun rendez-vous militant programmé pour le moment.<br>
                <small>Ajoutez <code>event_date: "YYYY-MM-DD"</code> dans le frontmatter d'un article pour l'afficher ici.</small>
            </p>
        <?php endif; ?>
        <p class="ql-widget__more">
            <a href="<?php echo esc_url( home_url( '/category/mobilisations/' ) ); ?>">Toutes les mobilisations →</a>
        </p>
    </section>

    <!-- 5. RÉSEAUX SOCIAUX ────────────────────────────────────────── -->
    <section class="ql-widget ql-widget--social" aria-label="Réseaux sociaux">
        <h3 class="ql-widget__title">Nous suivre</h3>
        <ul class="ql-widget-social">
            <?php foreach ( $socials as $key => $s ) :
                if ( empty( $s['url'] ) ) continue; ?>
                <li>
                    <a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $s['label'] ); ?>">
                        <span class="ql-widget-social__icon ql-widget-social__icon--<?php echo esc_attr( $key ); ?>" aria-hidden="true">
                            <?php echo ql_social_icon_svg( $key ); // helper dans functions.php ?>
                        </span>
                        <span class="ql-widget-social__label"><?php echo esc_html( $s['label'] ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ( empty( array_filter( array_column( $socials, 'url' ) ) ) ) : ?>
            <p class="ql-muted" style="font-size:.85rem;">
                Configurez les liens sociaux via <code>wp option update ql_social_mastodon "..."</code> (clés : mastodon, twitter, instagram, facebook, telegram).
            </p>
        <?php endif; ?>
    </section>

</aside>

<?php if ( $paypal_client_id ) : ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr( $paypal_client_id ); ?>&currency=EUR&intent=capture&disable-funding=credit,card" data-no-lazy="1" data-cfasync="false"></script>
<?php endif; ?>
<script>
(function(){
  var DEFAULT = 15, selected = DEFAULT;
  var widget = document.querySelector('.ql-widget--cagnotte');
  if (!widget) return;
  var tiers = widget.querySelectorAll('.ql-cagnotte-tier');
  var input = document.getElementById('ql-cagnotte-amount');

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

  // PayPal SDK peut ne pas être prêt au moment où ce script s'exécute
  // (NitroPack/async defer, réseau lent…). On attend jusqu'à 10s.
  var paypalTarget = document.getElementById('ql-cagnotte-paypal');
  var attempts = 0, maxAttempts = 100; // 100 × 100ms = 10s
  function tryRenderPaypal() {
    if (!paypalTarget) return;
    if (window.paypal && window.paypal.Buttons) {
      paypal.Buttons({
        style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'donate', height: 38 },
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
      }).render('#ql-cagnotte-paypal');
      return;
    }
    if (attempts++ < maxAttempts) setTimeout(tryRenderPaypal, 100);
    else paypalTarget.innerHTML = '<a href="<?php echo esc_url( $soutenir_url ); ?>" class="ql-cagnotte-fallback">Faire un don →</a>';
  }
  tryRenderPaypal();

  <?php if ( $has_helloasso ) : ?>
  var haBtn = document.getElementById('ql-cagnotte-ha');
  var haErr = document.getElementById('ql-cagnotte-err');
  if (haBtn) {
    haBtn.addEventListener('click', function(){
      haBtn.disabled = true;
      if (haErr) haErr.hidden = true;
      fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'action=ql_helloasso_checkout&nonce=<?php echo wp_create_nonce( 'ql_helloasso' ); ?>&amount=' + encodeURIComponent(selected)
      })
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j && j.redirect_url) window.location.href = j.redirect_url;
        else { if (haErr) { haErr.textContent = (j && j.error) ? j.error : 'Erreur HelloAsso'; haErr.hidden = false; } haBtn.disabled = false; }
      })
      .catch(function(){ if (haErr) { haErr.textContent = 'Erreur réseau'; haErr.hidden = false; } haBtn.disabled = false; });
    });
  }
  <?php endif; ?>
})();
</script>
