<?php
/**
 * Front page — prioritaire sur page.php quand une page statique est
 * définie comme accueil. Affiche toujours la une du média.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

// Compte d'articles globaux, pour décider quoi afficher
$has_any = (int) wp_count_posts()->publish > 0;

?>

<div class="ql-container">

    <?php if ( $has_any ) : ?>

        <?php get_template_part( 'template-parts/hero-carousel' ); ?>

        <?php
        // Détection auto des 4 catégories principales (top-level uniquement,
        // les sous-catégories restent accessibles via le menu).
        $top_cats = get_categories( array(
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 4,
            'hide_empty' => false, // top-level peut être vide si tous les articles sont en sous-cat
            'parent'     => 0,     // top-level seulement
        ) );
        $i = 0;
        foreach ( $top_cats as $cat ) {
            get_template_part( 'template-parts/section-category', null, array(
                'slug'  => $cat->slug,
                'label' => $cat->name,
                'count' => 3,
            ) );
            $i++;
            // Après la 2e section, insérer le bloc Dossiers
            if ( $i === 2 ) {
                get_template_part( 'template-parts/dossiers' );
            }
        }

        // Appel aux dons
        get_template_part( 'template-parts/soutenir' );
        ?>

        <section class="ql-section" aria-label="Tous les articles">
            <header class="ql-section__head">
                <h2 class="ql-section__title">Tous les articles</h2>
            </header>

            <div class="ql-grid ql-grid--3">
                <?php
                $recent = new WP_Query( array(
                    'posts_per_page' => 9,
                    'no_found_rows'  => true,
                ) );
                while ( $recent->have_posts() ) : $recent->the_post();
                    get_template_part( 'template-parts/card-article' );
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </section>

    <?php else : ?>

        <?php // Site vide : carton d'accueil temporaire ?>
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

        <section class="ql-section" aria-label="Commencer">
            <header class="ql-section__head">
                <h2 class="ql-section__title">Démarrer la rédaction</h2>
            </header>
            <div class="ql-grid ql-grid--3">
                <article class="ql-card">
                    <div class="ql-card__body">
                        <h3 class="ql-card__title">1. Créer vos catégories</h3>
                        <p class="ql-card__excerpt">
                            Assurez-vous que les rubriques <em>local</em>, <em>france</em>,
                            <em>luttes</em> et <em>international</em> existent (slugs exacts).
                            Elles apparaîtront automatiquement en home.
                        </p>
                        <p><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ); ?>">Gérer les catégories →</a></p>
                    </div>
                </article>
                <article class="ql-card">
                    <div class="ql-card__body">
                        <h3 class="ql-card__title">2. Publier vos articles</h3>
                        <p class="ql-card__excerpt">
                            Chaque article doit être rangé dans une catégorie et avoir une <strong>image à la une</strong>
                            pour s'afficher correctement en hero et en cartes.
                        </p>
                        <p><a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">Nouvel article →</a></p>
                    </div>
                </article>
                <article class="ql-card">
                    <div class="ql-card__body">
                        <h3 class="ql-card__title">3. Configurer le menu</h3>
                        <p class="ql-card__excerpt">
                            Dans <em>Apparence → Menus</em>, créez un menu avec vos rubriques et
                            assignez-le à l'emplacement « Menu principal ».
                        </p>
                        <p><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Éditer les menus →</a></p>
                    </div>
                </article>
            </div>
        </section>

    <?php endif; ?>

</div>

<?php get_footer(); ?>
