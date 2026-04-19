<?php
/**
 * Template Name: À propos
 *
 * Page /a-propos/ — présentation du média, ligne éditoriale, rédaction,
 * appels à soutien et à rejoindre. Calque de contre-attaque.net/a-propos/
 * mais adapté à Quartier Libre : quartiers populaires de Nantes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$donation_url = get_option( 'ql_donorbox_url', 'https://www.helloasso.com/associations/quartier-libre-nantes' );
$contact_email = get_option( 'ql_contact_email', 'contact@quartierlibre.org' );
$paypal_client_id = get_option( 'ql_paypal_client_id', '' );
?>

<div class="ql-apropos-page">

    <!-- HERO : manifeste d'accroche -->
    <section class="ql-apropos-hero">
        <div class="ql-container">
            <span class="ql-apropos-hero__kicker">À propos</span>
            <h1 class="ql-apropos-hero__title">
                Par nous.<br>
                <span class="ql-apropos-hero__accent">Pour nous.</span>
            </h1>
            <p class="ql-apropos-hero__lede">
                Quartier Libre est un média indépendant, né dans les quartiers populaires de Nantes.
                Il existe <strong>sans publicité, sans actionnaire, sans subvention conditionnée</strong>.
                Pour relayer ce que les médias dominants préfèrent taire.
            </p>
        </div>
    </section>

    <!-- QUI NOUS SOMMES -->
    <section class="ql-apropos-block">
        <div class="ql-container ql-apropos-block__inner">
            <header class="ql-section__head">
                <h2 class="ql-section__title">Qui sommes-nous ?</h2>
            </header>

            <?php
            // Image à la une de la page /a-propos/ — mise par l'admin WP.
            // S'affiche en bloc pleine largeur au-dessus du texte.
            if ( has_post_thumbnail() ) : ?>
                <figure class="ql-apropos-image">
                    <?php the_post_thumbnail( 'ql-hero', array(
                        'class'   => 'ql-apropos-image__img',
                        'loading' => 'eager',
                        'decoding'=> 'async',
                    ) ); ?>
                    <?php $cap = get_the_post_thumbnail_caption();
                    if ( $cap ) : ?>
                        <figcaption><?php echo esc_html( $cap ); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>

            <div class="ql-apropos-text">
                <p class="ql-apropos-lead">
                    <strong>Quartier Libre</strong>, c'est une rédaction issue des quartiers HLM de Nantes
                    — Bellevue, Malakoff, Dervallières, Bottière-Pin Sec, Breil, Clos Toreau,
                    Bout des Landes, Port Boyer, Halvêque, Ranzay, Pilotière.
                </p>

                <p>
                    Nous écrivons depuis <em>nos</em> immeubles, <em>nos</em> halls, <em>nos</em> écoles,
                    <em>nos</em> associations. Nous racontons ce qui s'y passe <strong>vraiment</strong> — pas
                    ce que BFM en dit, pas ce que la mairie PS en laisse filtrer dans ses communiqués.
                </p>

                <p>
                    Nous existons parce qu'aucun média dominant ne parle des quartiers populaires
                    sans les caricaturer. <strong>Quartier Libre</strong> répare ce silence.
                    Enquêtes de terrain, témoignages anonymes, contre-récits, archives locales :
                    nous documentons, nous publions, nous relayons.
                </p>

                <p>
                    Notre ligne est <strong>militante de gauche, antiraciste, anticapitaliste, anticoloniale</strong>.
                    Nous ne prétendons pas à une neutralité qui n'existe pas : nous assumons qu'un média,
                    comme un quartier, a un camp — celui des habitant·es contre celui des puissant·es.
                </p>
            </div>
        </div>
    </section>

    <!-- FRISE / NOS RACINES -->
    <section class="ql-apropos-block ql-apropos-block--alt">
        <div class="ql-container ql-apropos-block__inner">
            <header class="ql-section__head">
                <h2 class="ql-section__title">Nos racines</h2>
            </header>

            <p class="ql-apropos-text" style="text-align:center;max-width:640px;margin:0 auto 3rem;">
                Quartier Libre n'est pas sorti de nulle part. Il vient d'une <strong>décennie de luttes,
                d'enquêtes et de presse militante nantaise</strong>.
            </p>

            <ol class="ql-timeline">

                <li class="ql-timeline__item">
                    <span class="ql-timeline__year">2012</span>
                    <div class="ql-timeline__body">
                        <h3>Naissance de Nantes Révoltée</h3>
                        <p>Un média indépendant émerge à Nantes. Reportages de rue, documentation
                           de la répression policière, relais des luttes locales — ZAD, mal-logement,
                           antifascisme, solidarité sans-papiers.</p>
                    </div>
                </li>

                <li class="ql-timeline__item">
                    <span class="ql-timeline__year">2015</span>
                    <div class="ql-timeline__body">
                        <h3>Khalid rejoint Nantes Révoltée</h3>
                        <p>Habitant d'un quartier populaire nantais, <strong>Khalid</strong> commence
                           à collaborer avec Nantes Révoltée. Reportages terrain, témoignages recueillis
                           dans les HLM, enquêtes sur Nantes Métropole Habitat et les violences
                           policières en cité.</p>
                    </div>
                </li>

                <li class="ql-timeline__item">
                    <span class="ql-timeline__year">2018<br>2020</span>
                    <div class="ql-timeline__body">
                        <h3>Gilets jaunes, mouvement retraites</h3>
                        <p>Des années de mobilisations majeures. Khalid et la rédaction de Nantes
                           Révoltée sont sur chaque manif, chaque blocage, chaque nuit de répression.
                           La parole des quartiers populaires trouve sa place.</p>
                    </div>
                </li>

                <li class="ql-timeline__item">
                    <span class="ql-timeline__year">2022</span>
                    <div class="ql-timeline__body">
                        <h3>Nantes Révoltée devient <em>Contre-Attaque</em></h3>
                        <p>Pour les 10 ans du média, l'équipe passe à l'échelle nationale. Le nom
                           change, l'audience s'envole. Mais une question reste :
                           <strong>qui parle spécifiquement des quartiers populaires de Nantes ?</strong></p>
                    </div>
                </li>

                <li class="ql-timeline__item ql-timeline__item--highlight">
                    <span class="ql-timeline__year">2024</span>
                    <div class="ql-timeline__body">
                        <h3>Naissance de Quartier Libre</h3>
                        <p>Khalid fonde <strong>Quartier Libre</strong>, média entièrement dédié
                           aux HLM nantais. Pas de concurrence avec Contre-Attaque — une
                           <em>complémentarité</em> : eux la France, nous le terrain. Eux le national,
                           nous Bellevue, Malakoff, Dervallières, le Breil, la Bottière.</p>
                    </div>
                </li>

                <li class="ql-timeline__item">
                    <span class="ql-timeline__year">2026</span>
                    <div class="ql-timeline__body">
                        <h3>Aujourd'hui — 13 journalistes, 11 quartiers</h3>
                        <p>Chaque quartier populaire de Nantes a désormais sa ou son journaliste
                           spécialisé·e. Des correspondant·es couvrent le national et l'international
                           (Gaza, Soudan, loi immigration). Le Bureau des plaintes reçoit des
                           centaines de témoignages. <strong>Le mouvement continue.</strong></p>
                    </div>
                </li>

            </ol>
        </div>
    </section>

    <!-- CHIFFRES / TIMELINE -->
    <section class="ql-apropos-stats">
        <div class="ql-container">
            <div class="ql-apropos-stats__grid">

                <div class="ql-apropos-stat">
                    <span class="ql-apropos-stat__number">13</span>
                    <span class="ql-apropos-stat__label">journalistes<br>dans la rédaction</span>
                </div>

                <div class="ql-apropos-stat">
                    <span class="ql-apropos-stat__number">11</span>
                    <span class="ql-apropos-stat__label">quartiers HLM<br>couverts en permanence</span>
                </div>

                <div class="ql-apropos-stat ql-apropos-stat--accent">
                    <span class="ql-apropos-stat__number">0&nbsp;€</span>
                    <span class="ql-apropos-stat__label">de pub.<br>0 € de subvention conditionnée.</span>
                </div>

                <div class="ql-apropos-stat">
                    <span class="ql-apropos-stat__number">100 %</span>
                    <span class="ql-apropos-stat__label">financé par<br>ses lectrices et lecteurs</span>
                </div>

            </div>
        </div>
    </section>

    <!-- LA RÉDACTION -->
    <section class="ql-apropos-block ql-apropos-block--dark">
        <div class="ql-container ql-apropos-block__inner">
            <header class="ql-section__head ql-section__head--light">
                <h2 class="ql-section__title">Une rédaction qui vit dans les quartiers</h2>
            </header>

            <div class="ql-apropos-text">
                <p>
                    Nos journalistes ne parachutent pas depuis Paris pour écrire un sujet « cité » avant de
                    rentrer à la maison. Chacun·e de nous est spécialisé·e sur <strong>un quartier précis</strong>
                    où il ou elle vit, enseigne, milite, élève ses enfants.
                </p>

                <div class="ql-apropos-roster">
                    <div class="ql-apropos-roster__col">
                        <h3>Quartiers populaires</h3>
                        <ul>
                            <li><strong>Aïssata Diallo</strong> — Bellevue</li>
                            <li><strong>Younes Boukhris</strong> — Malakoff</li>
                            <li><strong>Karima Benali</strong> — Dervallières</li>
                            <li><strong>Soraya Messaoudi</strong> — Clos Toreau</li>
                            <li><strong>Mehdi Haddad</strong> — Bottière&ndash;Pin Sec</li>
                            <li><strong>Fatou Traoré</strong> — Breil</li>
                            <li><strong>Samir Touré</strong> — Bout des Landes</li>
                            <li><strong>Léa Marchand</strong> — Port Boyer</li>
                            <li><strong>Naïma Ouédraogo</strong> — Halvêque</li>
                            <li><strong>Amadou Koné</strong> — Ranzay</li>
                            <li><strong>Sofia Bensalem</strong> — Pilotière</li>
                        </ul>
                    </div>
                    <div class="ql-apropos-roster__col">
                        <h3>Correspondant·es</h3>
                        <ul>
                            <li><strong>Rachida Ben Arfa</strong> — International<br><small>Gaza, Palestine, résistances globales</small></li>
                            <li><strong>Julien Moreau</strong> — National<br><small>Politique française, luttes sociales</small></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- POURQUOI NOUS EXISTONS -->
    <section class="ql-apropos-block">
        <div class="ql-container ql-apropos-block__inner">
            <header class="ql-section__head">
                <h2 class="ql-section__title">Pourquoi nous existons</h2>
            </header>

            <blockquote class="ql-apropos-quote">
                Jamais les médias dominants n'ont été autant inféodés aux puissant·es.
                Rarement les voix discordantes n'ont été si peu audibles.
            </blockquote>

            <div class="ql-apropos-text">
                <p>
                    Nantes compte 17 000 habitant·es à Bellevue, 15 000 à Malakoff, 10 000 aux Dervallières.
                    Chaque quartier populaire de la ville pèse l'équivalent d'une petite ville de banlieue.
                    Et pourtant, dans les journaux locaux, ils n'apparaissent que sous deux angles :
                    <strong>fait divers</strong> ou <strong>projet de rénovation urbaine</strong>.
                </p>

                <p>
                    Jamais comme des gens qui travaillent, aiment, résistent, créent, enterrent leurs mort·es,
                    organisent des fêtes de quartier. Jamais comme des <strong>citoyen·nes à part entière</strong>.
                </p>

                <p>
                    Nous avons construit Quartier Libre pour <strong>inverser le cadrage</strong>. Pour qu'il
                    existe, au moins en ligne, un espace où les habitant·es des quartiers écrivent leur propre
                    récit. Sans intermédiaire. Sans autorisation de la mairie. Sans le vernis
                    bienveillant-paternaliste des magazines municipaux.
                </p>
            </div>
        </div>
    </section>

    <!-- APPEL À SOUTIEN -->
    <section class="ql-apropos-cta">
        <div class="ql-container">
            <div class="ql-apropos-cta__inner">
                <h2>On a besoin de vous.</h2>
                <p>
                    Sans publicité, sans actionnaire, sans subvention conditionnée, nous dépendons
                    uniquement de celles et ceux qui lisent. <strong>Chaque euro finance une enquête,
                    un témoignage, une voix qui monte.</strong>
                </p>
                <div class="ql-apropos-cta__buttons">
                    <button type="button" class="ql-btn ql-btn--accent ql-btn--lg" data-open-don-modal>Faire un don</button>
                    <a class="ql-btn ql-btn--ghost" href="#ql-nl-email">S'abonner à la newsletter</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT + REJOINDRE -->
    <section class="ql-apropos-block ql-apropos-block--alt">
        <div class="ql-container ql-apropos-block__inner">

            <div class="ql-apropos-columns">

                <div class="ql-apropos-column">
                    <h3>Nous contacter</h3>
                    <p>
                        Un témoignage, un tuyau, une plainte, un droit de réponse ?<br>
                        <a href="mailto:<?php echo esc_attr( $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a>
                    </p>
                    <p>
                        Ou en anonyme via le
                        <a href="<?php echo esc_url( home_url( '/bureau-des-plaintes/' ) ); ?>"><strong>Bureau des plaintes</strong></a>.
                    </p>
                </div>

                <div class="ql-apropos-column">
                    <h3>Rejoindre la rédaction</h3>
                    <p>
                        Tu habites un quartier, tu enquêtes, tu écris, tu photographies, tu t'occupes d'un
                        collectif ? On peut publier ton travail, te former, t'intégrer à la rédaction.
                    </p>
                    <p>
                        <a class="ql-btn ql-btn--outline" href="mailto:<?php echo esc_attr( $contact_email ); ?>?subject=Je%20veux%20rejoindre%20Quartier%20Libre">Postuler →</a>
                    </p>
                </div>

                <div class="ql-apropos-column">
                    <h3>Nous diffuser</h3>
                    <p>
                        Le bouche-à-oreille est notre meilleur allié. Partagez les articles qui vous parlent,
                        imprimez-les, distribuez-les dans votre hall.
                    </p>
                    <p>
                        <a class="ql-btn ql-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Lire les articles</a>
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Contenu libre de la page (éditable dans WP admin) -->
    <?php while ( have_posts() ) : the_post();
        $content = get_the_content();
        if ( trim( $content ) ) : ?>
            <section class="ql-container ql-apropos-page__extra">
                <div class="ql-post__content"><?php the_content(); ?></div>
            </section>
        <?php endif;
    endwhile; ?>

</div>

<!-- ── MODAL DON (bouton 'Faire un don' l'ouvre) ──────────── -->
<div class="ql-don-modal" id="ql-don-modal" role="dialog" aria-modal="true" aria-labelledby="ql-don-modal-title" hidden>
    <div class="ql-don-modal__backdrop" data-close-don></div>
    <div class="ql-don-modal__panel">
        <button type="button" class="ql-don-modal__close" aria-label="Fermer" data-close-don>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="ql-don-modal__header">
            <span class="ql-don-modal__kicker">Soutenir</span>
            <h2 id="ql-don-modal-title">Faire un don à Quartier Libre</h2>
            <p class="ql-don-modal__subtitle">Paiement sécurisé via PayPal ou HelloAsso. 100 % de votre don finance la rédaction.</p>
        </div>

        <div class="ql-don-modal__body">

            <div class="ql-donation-tiers" role="radiogroup" aria-label="Montant du don">
                <button type="button" class="ql-donation-tier" data-amount="5"><span class="ql-donation-tier__amount">5 €</span><span class="ql-donation-tier__desc">Un café militant</span></button>
                <button type="button" class="ql-donation-tier ql-donation-tier--highlight is-active" data-amount="15" aria-checked="true"><span class="ql-donation-tier__badge">Le plus soutenu</span><span class="ql-donation-tier__amount">15 €</span><span class="ql-donation-tier__desc">Un article publié</span></button>
                <button type="button" class="ql-donation-tier" data-amount="30"><span class="ql-donation-tier__amount">30 €</span><span class="ql-donation-tier__desc">Une enquête</span></button>
                <button type="button" class="ql-donation-tier" data-amount="100"><span class="ql-donation-tier__amount">100 €</span><span class="ql-donation-tier__desc">Un média qui dure</span></button>
            </div>

            <div class="ql-donation-custom">
                <label for="ql-modal-custom-amount">Ou montant libre :</label>
                <div class="ql-donation-custom__wrap">
                    <input type="number" id="ql-modal-custom-amount" min="1" step="1" placeholder="Libre" inputmode="numeric">
                    <span class="ql-donation-custom__currency">€</span>
                </div>
            </div>

            <div id="ql-modal-paypal-container" class="ql-paypal-button"></div>

            <?php if ( get_option( 'ql_helloasso_client_id' ) && get_option( 'ql_helloasso_client_secret' ) ) : ?>
                <div class="ql-helloasso-wrap">
                    <span class="ql-helloasso-or">— ou —</span>
                    <button type="button" id="ql-modal-helloasso-btn" class="ql-btn ql-btn--helloasso ql-btn--lg">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="vertical-align:-5px;margin-right:.5rem;"><path d="M12 2 2 8.5v7L12 22l10-6.5v-7zm0 2.3 7.7 5L12 14.7 4.3 9.3z"/></svg>
                        Donner avec HelloAsso
                    </button>
                    <p id="ql-modal-helloasso-err" class="ql-helloasso-err" hidden></p>
                </div>
            <?php endif; ?>

            <p class="ql-don-modal__footer-link">
                <a href="<?php echo esc_url( home_url( '/soutenir/' ) ); ?>">Voir la page Soutenir complète (FAQ, virement, autres moyens) →</a>
            </p>
        </div>
    </div>
</div>

<?php if ( $paypal_client_id ) : ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr( $paypal_client_id ); ?>&currency=EUR&intent=capture&disable-funding=credit,card"></script>
<?php endif; ?>
<script>
(function(){
  var modal   = document.getElementById('ql-don-modal');
  var opens   = document.querySelectorAll('[data-open-don-modal]');
  var closes  = document.querySelectorAll('[data-close-don]');
  if (!modal || !opens.length) return;

  var DEFAULT_AMOUNT = 15;
  var selectedAmount = DEFAULT_AMOUNT;
  var paypalRendered = false;

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    if (!paypalRendered && window.paypal) renderPaypal();
  }
  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
  }
  opens.forEach(function(b){ b.addEventListener('click', openModal); });
  closes.forEach(function(b){ b.addEventListener('click', closeModal); });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  var tiers = modal.querySelectorAll('.ql-donation-tier');
  var input = document.getElementById('ql-modal-custom-amount');
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
  if (input) input.addEventListener('input', function(){
    if (this.value) setActive(this.value, true);
  });

  function renderPaypal() {
    if (!window.paypal) return;
    paypal.Buttons({
      style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'donate', height: 44 },
      createOrder: function(data, actions){
        return actions.order.create({
          purchase_units: [{
            amount: { value: selectedAmount.toFixed(2), currency_code: 'EUR' },
            description: 'Don Quartier Libre — média indépendant'
          }]
        });
      },
      onApprove: function(data, actions){
        return actions.order.capture().then(function(){
          window.location.href = '<?php echo esc_url( home_url( '/soutenir/?merci=1' ) ); ?>';
        });
      },
      onError: function(err){ console.error('PayPal:', err); }
    }).render('#ql-modal-paypal-container');
    paypalRendered = true;
  }

  var helloBtn = document.getElementById('ql-modal-helloasso-btn');
  var helloErr = document.getElementById('ql-modal-helloasso-err');
  if (helloBtn) {
    helloBtn.addEventListener('click', async function(){
      helloBtn.disabled = true;
      var origHtml = helloBtn.innerHTML;
      helloBtn.textContent = 'Création du paiement…';
      if (helloErr) helloErr.hidden = true;
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
        if (helloErr) { helloErr.textContent = 'Erreur : ' + e.message; helloErr.hidden = false; }
        helloBtn.disabled = false;
        helloBtn.innerHTML = origHtml;
      }
    });
  }
})();
</script>

<?php get_footer(); ?>
