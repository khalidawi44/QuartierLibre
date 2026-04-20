<?php
/**
 * Front page — layout 70/30 avec sidebar droite.
 *
 * Structure :
 *   [ Hero carousel (pleine largeur) ]
 *   ┌──────────────────────────┬─────────────┐
 *   │ 70% : rubriques + cards   │ 30% sidebar │
 *   │   - Infos locale          │  1 recherche│
 *   │   - France                │  2 rubriques│
 *   │   - International         │  3 cagnotte │
 *   │   - Luttes                │  4 rendez-v.│
 *   │   - Rendez-vous           │  5 socials  │
 *   │   - Dossiers              │             │
 *   └──────────────────────────┴─────────────┘
 *
 * Sur mobile/<900px : la sidebar passe sous le contenu (stack vertical).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$has_any = (int) wp_count_posts()->publish > 0;
?>

<div class="ql-container">

    <?php if ( $has_any ) : ?>

        <?php // Hero carousel reste pleine largeur au-dessus du split 70/30 ?>
        <?php get_template_part( 'template-parts/hero-carousel' ); ?>

        <div class="ql-home-layout">

            <!-- COLONNE PRINCIPALE (70%) : les rubriques -->
            <main class="ql-home-main">
                <?php
                $sections = array(
                    array( 'slug' => 'infos-locale',  'label' => 'Infos locale' ),
                    array( 'slug' => 'france',        'label' => 'France' ),
                    array( 'slug' => 'international', 'label' => 'International' ),
                    array( 'slug' => 'luttes',        'label' => 'Luttes' ),
                );
                foreach ( $sections as $s ) {
                    get_template_part( 'template-parts/section-category', null, array(
                        'slug'  => $s['slug'],
                        'label' => $s['label'],
                        'count' => 3,
                    ) );
                }

                // Rendez-vous militants (sous-catégorie mobilisations)
                get_template_part( 'template-parts/section-category', null, array(
                    'slug'  => 'mobilisations',
                    'label' => 'Rendez-vous — manifs & mobilisations',
                    'count' => 3,
                ) );

                // Dossiers
                get_template_part( 'template-parts/dossiers' );
                ?>
            </main>

            <!-- COLONNE LATÉRALE (30%) : widgets dans l'ordre demandé -->
            <?php get_template_part( 'template-parts/sidebar-home' ); ?>

        </div><!-- /.ql-home-layout -->

    <?php else : ?>

        <?php // Site vide : carton d'accueil temporaire (inchangé) ?>
        <section class="ql-hero" style="grid-template-columns:1fr;">
            <article class="ql-hero__main" style="min-height:380px;">
                <div class="ql-hero__main-body" style="max-width:720px;">
                    <span class="ql-card__cat" style="position:static;display:inline-block;margin-bottom:1rem;background:var(--ql-accent);">Bienvenue</span>
                    <h2 style="font-size:clamp(2rem,4vw,3rem);">Quartier Libre — la voix des quartiers.</h2>
                    <p style="color:#ddd;font-size:1.1rem;line-height:1.6;">
                        Par nous, pour nous. Les quartiers prennent la parole : violences sociales et policières,
                        logement, luttes, urbanisme, services publics. Information locale et indépendante.
                    </p>
                    <p style="margin-top:1.5rem;">
                        <a class="ql-btn ql-btn--accent" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">Publier un premier article</a>
                        &nbsp;
                        <a class="ql-btn ql-btn--ghost" style="color:#fff;border-color:#fff;" href="<?php echo esc_url( home_url( '/bureau-des-plaintes/' ) ); ?>">Bureau des plaintes</a>
                    </p>
                </div>
            </article>
        </section>

    <?php endif; ?>

</div>

<?php get_footer(); ?>
