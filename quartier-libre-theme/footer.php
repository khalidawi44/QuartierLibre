<?php
/**
 * Footer — Quartier Libre
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
</main><!-- /#content -->

<?php get_template_part( 'template-parts/plainte-popup' ); ?>

<footer class="ql-footer" role="contentinfo">
    <div class="ql-container ql-footer__grid">

        <div class="ql-footer__col ql-footer__brand">
            <?php
            // Même cascade que le header (cf. ql_resolve_logo_url)
            $logo_url = function_exists( 'ql_resolve_logo_url' ) ? ql_resolve_logo_url() : '';
            if ( $logo_url ) {
                echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="ql-footer__brand-link" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . ' — accueil">'
                   . '<img src="' . esc_url( $logo_url ) . '"'
                   . ' alt="' . esc_attr( get_bloginfo( 'name' ) ) . '"'
                   . ' class="ql-footer__logo no-lazyload"'
                   . ' loading="lazy" data-no-lazy="1" data-nitro-stealth-load="1" data-skip-lazy="1">'
                   . '</a>';
            } else {
                echo '<p class="ql-footer__wordmark"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></p>';
            }
            ?>
            <p class="ql-footer__tag">Par nous, pour nous. Les quartiers prennent la parole.</p>
        </div>

        <div class="ql-footer__col">
            <h3 class="ql-footer__title">Rubriques</h3>
            <ul class="ql-footer__list">
                <?php
                $cats = get_categories( array(
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'number'     => 8,
                    'hide_empty' => true,
                ) );
                foreach ( $cats as $cat ) {
                    echo '<li><a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a></li>';
                }
                if ( empty( $cats ) ) {
                    echo '<li class="ql-muted">Aucune rubrique pour l\'instant.</li>';
                }
                ?>
            </ul>
        </div>

        <div class="ql-footer__col">
            <h3 class="ql-footer__title">Agir</h3>
            <ul class="ql-footer__list">
                <li><a href="<?php echo esc_url( home_url( '/bureau-des-plaintes/' ) ); ?>">Bureau des plaintes</a></li>
                <li><a href="<?php echo esc_url( home_url( '/soutenir/' ) ); ?>">Soutenir Quartier Libre</a></li>
                <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Nous contacter</a></li>
                <li><a href="<?php echo esc_url( get_feed_link() ); ?>">Flux RSS</a></li>
            </ul>
        </div>

        <div class="ql-footer__col">
            <h3 class="ql-footer__title">Lettre d'info</h3>
            <p class="ql-footer__blurb">Recevez les articles de la semaine, directement dans votre boîte mail.</p>
            <form class="ql-newsletter" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="ql_newsletter">
                <?php wp_nonce_field( 'ql_newsletter', 'ql_newsletter_nonce' ); ?>
                <label for="ql-nl-email" class="ql-visually-hidden">Votre email</label>
                <input id="ql-nl-email" type="email" name="email" placeholder="votre@email.fr" required>
                <button type="submit" class="ql-btn ql-btn--accent">S'abonner</button>
            </form>
        </div>

    </div>

    <div class="ql-footer__meta">
        <div class="ql-container ql-footer__meta-inner">
            <p>
                &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> — Tous droits réservés.
                <span class="ql-footer__credit">
                    Fièrement créé par
                    <a href="https://alliancegroupe-inc.com/" target="_blank" rel="noopener">Alliance Groupe-inc</a>.
                </span>
            </p>
            <ul class="ql-footer__legal">
                <li><a href="<?php echo esc_url( home_url( '/mentions-legales/' ) ); ?>">Mentions légales</a></li>
                <li><a href="<?php echo esc_url( home_url( '/politique-confidentialite/' ) ); ?>">Confidentialité</a></li>
                <li><a href="<?php echo esc_url( home_url( '/qui-sommes-nous/' ) ); ?>">Qui sommes-nous</a></li>
            </ul>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
