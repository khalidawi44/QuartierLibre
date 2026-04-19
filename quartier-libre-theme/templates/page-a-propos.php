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
                    <a class="ql-btn ql-btn--accent ql-btn--lg" href="<?php echo esc_url( home_url( '/soutenir/' ) ); ?>">Faire un don</a>
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

<?php get_footer(); ?>
