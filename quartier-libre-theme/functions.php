<?php
/**
 * Quartier Libre — functions.php
 * Thème autonome, orienté performance.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QL_THEME_VERSION', '1.0.0' );
define( 'QL_THEME_DIR', get_stylesheet_directory() );
define( 'QL_THEME_URI', get_stylesheet_directory_uri() );

// ── 0. Sync GitHub + helpers (toujours chargé) ──────────────────
// ql-sync.php contient :
//   - La page admin « Outils → Sync QL » (guardée par add_action admin_menu)
//   - Les helpers ql_categories_tree() / ql_ensure_categories() utilisés
//     par le frontend (header.php) pour générer le menu
// Donc on charge TOUJOURS (avant on faisait `if (is_admin())` ce qui
// cassait le menu sur le frontend car ql_categories_tree() n'existait pas).
$ql_sync_file = QL_THEME_DIR . '/ql-sync.php';
if ( file_exists( $ql_sync_file ) ) {
    require_once $ql_sync_file;
}

// Intégration HelloAsso API v5 (OAuth + checkout intents + endpoint AJAX)
$ql_helloasso_file = QL_THEME_DIR . '/includes/helloasso.php';
if ( file_exists( $ql_helloasso_file ) ) {
    require_once $ql_helloasso_file;
}

// Variantes du Bureau des plaintes (adaptées au contexte de l'article)
$ql_plainte_variants_file = QL_THEME_DIR . '/includes/plainte-variants.php';
if ( file_exists( $ql_plainte_variants_file ) ) {
    require_once $ql_plainte_variants_file;
}

// ── 1. Enqueue styles & scripts ─────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {

    // style.css WordPress (obligatoire, sert d'ancre)
    wp_enqueue_style(
        'ql-theme-style',
        get_stylesheet_uri(),
        array(),
        QL_THEME_VERSION
    );

    // Google Fonts — une seule requête, preconnect fait dans header.php
    wp_enqueue_style(
        'ql-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,700;9..144,900&display=swap',
        array(),
        null
    );

    // CSS principal (design system)
    $main_css = QL_THEME_DIR . '/assets/css/main.css';
    wp_enqueue_style(
        'ql-main',
        QL_THEME_URI . '/assets/css/main.css',
        array( 'ql-theme-style' ),
        file_exists( $main_css ) ? filemtime( $main_css ) : QL_THEME_VERSION
    );

    // JS principal (menu mobile, lazy helpers, etc.)
    $main_js = QL_THEME_DIR . '/assets/js/main.js';
    wp_enqueue_script(
        'ql-main',
        QL_THEME_URI . '/assets/js/main.js',
        array(),
        file_exists( $main_js ) ? filemtime( $main_js ) : QL_THEME_VERSION,
        true
    );

    // Commentaires : chargé uniquement si nécessaire
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }

}, 10 );

// ── 2. Supports thème ───────────────────────────────────────────
add_action( 'after_setup_theme', function () {

    register_nav_menus( array(
        'primary' => __( 'Menu principal', 'quartier-libre' ),
        'footer'  => __( 'Menu pied de page', 'quartier-libre' ),
    ) );

    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'customize-selective-refresh-widgets' );

    // Tailles d'image dédiées
    add_image_size( 'ql-hero',    1600, 900, true );  // une / hero
    add_image_size( 'ql-card',     800, 520, true );  // cartes articles
    add_image_size( 'ql-thumb',    400, 260, true );  // miniatures listes

    // Éditeur : largeur max
    add_theme_support( 'align-wide' );

}, 5 );

// ── 3. Favicon auto depuis assets/images/logo.{png,svg,webp} ────
add_action( 'wp_head', function () {
    $dir = QL_THEME_DIR . '/assets/images/';
    $uri = QL_THEME_URI . '/assets/images/';
    foreach ( array( 'svg', 'png', 'webp', 'jpg', 'jpeg', 'ico' ) as $ext ) {
        $file = $dir . 'favicon.' . $ext;
        if ( file_exists( $file ) ) {
            $type = $ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext;
            echo '<link rel="icon" type="' . esc_attr( $type ) . '" href="' . esc_url( $uri . 'favicon.' . $ext ) . '">' . "\n";
            echo '<link rel="apple-touch-icon" href="' . esc_url( $uri . 'favicon.' . $ext ) . '">' . "\n";
            return;
        }
    }
    // Fallback : logo
    foreach ( array( 'svg', 'png', 'webp' ) as $ext ) {
        $file = $dir . 'logo.' . $ext;
        if ( file_exists( $file ) ) {
            $url = $uri . 'logo.' . $ext;
            echo '<link rel="icon" href="' . esc_url( $url ) . '">' . "\n";
            return;
        }
    }
}, 1 );

// ── 3b. Pages par défaut (Contact / Soutenir / Mentions, etc.) ─
add_action( 'init', function () {
    $last = (int) get_option( 'ql_default_pages_init', 0 );
    if ( $last && ( time() - $last ) < DAY_IN_SECONDS ) return;

    $pages = array(
        'soutenir' => array(
            'title'   => 'Soutenir Quartier Libre',
            'content' => "<p>Quartier Libre vit grâce à vous. Pas de publicité, pas d'actionnaire, pas de subvention conditionnée.</p>\n<p>Chaque don, même modeste, c'est un article en plus, une enquête qui sort.</p>",
        ),
        'contact' => array(
            'title'   => 'Nous contacter',
            'content' => "<p>La rédaction est joignable par mail : <a href=\"mailto:contact@quartierlibre.org\">contact@quartierlibre.org</a></p>\n<p>Pour un témoignage, une information, une enquête, préférez le <a href=\"/bureau-des-plaintes/\">Bureau des plaintes</a>.</p>",
        ),
        'qui-sommes-nous' => array(
            'title'   => 'Qui sommes-nous',
            'content' => "<p>Quartier Libre est un média militant, local et indépendant, ancré dans les quartiers populaires de Nantes.</p>\n<p>Par nous, pour nous. Les quartiers prennent la parole.</p>",
        ),
        'mentions-legales' => array(
            'title'   => 'Mentions légales',
            'content' => "<h2>Éditeur</h2>\n<p>Le site quartierlibre.org est édité par l'association Quartier Libre.</p>\n<h2>Hébergement</h2>\n<p>Hostinger.</p>\n<h2>Contact</h2>\n<p><a href=\"mailto:contact@quartierlibre.org\">contact@quartierlibre.org</a></p>",
        ),
        'politique-confidentialite' => array(
            'title'   => 'Politique de confidentialité',
            'content' => "<p>Quartier Libre ne vend ni ne transmet vos données personnelles à des tiers.</p>\n<p>Les données collectées via le <a href=\"/bureau-des-plaintes/\">Bureau des plaintes</a> servent uniquement à la rédaction pour enquêter. Une demande de suppression peut être faite à tout moment par mail à contact@quartierlibre.org.</p>",
        ),
    );

    foreach ( $pages as $slug => $info ) {
        $existing = get_page_by_path( $slug );
        if ( ! $existing ) {
            wp_insert_post( array(
                'post_title'   => $info['title'],
                'post_name'    => $slug,
                'post_content' => $info['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ) );
        }
    }
    update_option( 'ql_default_pages_init', time(), false );
}, 25 );

// ── 3c. Filtre menu : retirer les items de compte utilisateur ───
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
    if ( empty( $args->theme_location ) || $args->theme_location !== 'primary' ) return $items;

    $patterns = array(
        '/wp-login\.php/i',
        '/\?action=logout/i',
        '/^#loginpress-register#?$/i',
        '#/account/?$#i',
        '#/mon-compte/?$#i',
        '#/mon-profil/?$#i',
        '#/connexion/?$#i',
        '#/inscription/?$#i',
    );

    return array_values( array_filter( $items, function ( $item ) use ( $patterns ) {
        $url = isset( $item->url ) ? $item->url : '';
        foreach ( $patterns as $p ) {
            if ( preg_match( $p, $url ) ) return false;
        }
        return true;
    } ) );
}, 10, 2 );

// ── 4. Catégories par défaut — 5 parents + sous-catégories ─────
// L'arborescence réelle est définie dans ql_categories_tree() (ql-sync.php)
// et créée via ql_ensure_categories(). Ici on ne fait que déclencher
// cette création à l'activation du thème.
add_action( 'after_switch_theme', function () {
    if ( function_exists( 'ql_ensure_categories' ) ) {
        ql_ensure_categories();
    } else {
        // Fallback minimal si ql-sync.php pas encore chargé
        $cats = array(
            'infos-locale'  => 'Info locale',
            'france'        => 'France',
            'international' => 'International',
            'luttes'        => 'Luttes',
            'histoire'      => 'Histoire',
        );
        foreach ( $cats as $slug => $name ) {
            if ( ! term_exists( $slug, 'category' ) ) {
                wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
            }
        }
    }
} );

// ── 4b. Tags quartiers HLM de Nantes (auto-création, idempotent) ─
add_action( 'init', function () {
    // Check une seule fois par jour pour éviter charge
    $last = (int) get_option( 'ql_quartiers_init', 0 );
    if ( $last && ( time() - $last ) < DAY_IN_SECONDS ) return;

    $quartiers = array(
        'quartier-bellevue'         => 'Quartier Bellevue',
        'quartier-malakoff'         => 'Quartier Malakoff',
        'quartier-dervallieres'     => 'Quartier Dervallières',
        'quartier-clos-toreau'      => 'Quartier Clos Toreau',
        'quartier-bottiere-pin-sec' => 'Quartier Bottière - Pin Sec',
        'quartier-breil'            => 'Quartier Breil',
        'quartier-bout-des-landes'  => 'Quartier Bout des Landes',
        'quartier-port-boyer'       => 'Quartier Port Boyer',
        'quartier-halveque'         => 'Quartier Halvêque',
        'quartier-ranzay'           => 'Quartier Ranzay',
        'quartier-pilotiere'        => 'Quartier Pilotière',
    );
    foreach ( $quartiers as $slug => $name ) {
        if ( ! term_exists( $slug, 'post_tag' ) ) {
            wp_insert_term( $name, 'post_tag', array( 'slug' => $slug ) );
        }
    }
    update_option( 'ql_quartiers_init', time(), false );
}, 20 );

// ── 5. Templates de page déclarés ───────────────────────────────
add_filter( 'theme_page_templates', function ( $templates ) {
    $templates['templates/page-bureau-plaintes.php'] = 'Bureau des Plaintes';
    $templates['templates/page-soutenir.php']        = 'Soutenir (dons)';
    $templates['templates/page-connexion.php']       = 'Connexion / Inscription';
    $templates['templates/page-a-propos.php']        = 'À propos';
    $templates['templates/page-tous-articles.php']   = 'Tous les articles';
    $templates['templates/page-rubriques.php']       = 'Rubriques';
    $templates['templates/page-pleine-largeur.php']  = 'Pleine largeur';
    return $templates;
} );

// Auto-création de la page /rubriques/ avec son template
add_action( 'init', function () {
    $existing = get_page_by_path( 'rubriques' );
    if ( $existing ) {
        if ( ! get_page_template_slug( $existing->ID ) ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'templates/page-rubriques.php' );
        }
        return;
    }
    $pid = wp_insert_post( array(
        'post_title'   => 'Rubriques',
        'post_name'    => 'rubriques',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'templates/page-rubriques.php' );
    }
}, 32 );

// Auto-création de la page /tous-les-articles/ avec son template
add_action( 'init', function () {
    $existing = get_page_by_path( 'tous-les-articles' );
    if ( $existing ) {
        if ( ! get_page_template_slug( $existing->ID ) ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'templates/page-tous-articles.php' );
        }
        return;
    }
    $pid = wp_insert_post( array(
        'post_title'   => 'Tous les articles',
        'post_name'    => 'tous-les-articles',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'templates/page-tous-articles.php' );
    }
}, 31 );

// ── Configuration paiements (init une fois, puis éditable admin) ──
// Les clés sont stockées en wp_options pour être modifiables via
// l'admin WP → Options → General ou directement en base. Les valeurs
// ici sont les DÉFAUTS initiaux. L'admin peut les écraser à tout moment.
//
// SÉCURITÉ : le Client ID PayPal est public par design (utilisé dans
// le SDK JS côté frontend). Le client_secret HelloAsso est privé — il
// ne doit jamais sortir côté front ; on l'utilise uniquement dans des
// appels serveur→serveur (endpoint OAuth v5).
add_action( 'init', function () {
    // PayPal Client ID (frontend — peut être visible dans le HTML)
    if ( ! get_option( 'ql_paypal_client_id' ) ) {
        add_option( 'ql_paypal_client_id', 'AVyYRWTPC5wdtmdOCsjSrKp4_Em2kuQumBN2Mh9jBlbR8qcisZQj0yY8294PV0eWowqVS85ZOp1vjoN0', '', 'no' );
    }
    // HelloAsso client_id (public par design — OAuth2 client_credentials)
    if ( ! get_option( 'ql_helloasso_client_id' ) ) {
        add_option( 'ql_helloasso_client_id', 'c128914df736404aa609faa9d697afc8', '', 'no' );
    }
    // HelloAsso client_secret (PRIVÉ — serveur uniquement).
    // À récupérer sur dev.helloasso.com > Mes applications > secret affiché
    // une seule fois à la création. Vide par défaut.
    if ( ! get_option( 'ql_helloasso_client_secret' ) ) {
        add_option( 'ql_helloasso_client_secret', '', '', 'no' );
    }

    // MIGRATION v2 : le client_id de HelloAsso avait été stocké par erreur
    // dans ql_helloasso_client_secret (commit précédent). On corrige :
    // on déplace la valeur vers client_id et on vide le secret.
    $cfg_ver = (int) get_option( 'ql_payment_cfg_ver', 0 );
    if ( $cfg_ver < 2 ) {
        $stored_secret = get_option( 'ql_helloasso_client_secret' );
        if ( $stored_secret === 'c128914df736404aa609faa9d697afc8' ) {
            update_option( 'ql_helloasso_client_id', $stored_secret );
            update_option( 'ql_helloasso_client_secret', '' );
        }
        update_option( 'ql_payment_cfg_ver', 2 );
    }

    // MIGRATION v3 : initialisation du client_secret HelloAsso si vide.
    // ⚠ ALERTE SÉCURITÉ : Ce secret est visible dans le code source public
    // sur GitHub. L'admin doit IMPERATIVEMENT rotater ce secret après la
    // première mise en production (dev.helloasso.com → régénérer la clé
    // secrète de l'application), puis remplacer la valeur ici OU via
    // wp-admin > options.php > ql_helloasso_client_secret.
    if ( $cfg_ver < 3 ) {
        if ( get_option( 'ql_helloasso_client_secret' ) === '' ) {
            update_option( 'ql_helloasso_client_secret', 'cVU8bfABNHZI2QMrOewNp7ZC9eyRRCre' );
        }
        update_option( 'ql_payment_cfg_ver', 3 );
    }
    if ( ! get_option( 'ql_helloasso_org_slug' ) ) {
        add_option( 'ql_helloasso_org_slug', 'quartier-libre-nantes', '', 'yes' );
    }
}, 5 );

// Auto-création de la page /a-propos/ avec son template
add_action( 'init', function () {
    $existing = get_page_by_path( 'a-propos' );
    if ( $existing ) {
        if ( ! get_page_template_slug( $existing->ID ) ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'templates/page-a-propos.php' );
        }
        return;
    }
    $pid = wp_insert_post( array(
        'post_title'   => 'À propos',
        'post_name'    => 'a-propos',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'templates/page-a-propos.php' );
    }
}, 29 );

// Auto-création de la page /connexion/ et assignation du template
add_action( 'init', function () {
    $existing = get_page_by_path( 'connexion' );
    if ( $existing ) {
        if ( ! get_page_template_slug( $existing->ID ) ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'templates/page-connexion.php' );
        }
        return;
    }
    $pid = wp_insert_post( array(
        'post_title'   => 'Connexion',
        'post_name'    => 'connexion',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => 'Espace membres — connexion et inscription.',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'templates/page-connexion.php' );
    }
}, 28 );

// Redirige wp-login.php vers /connexion/ (sauf si déjà un POST de
// connexion en cours OU un admin qui se connecte)
add_action( 'login_init', function () {
    // Laisser passer POST (soumission du formulaire) et les actions spéciales
    if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) return;
    $action = isset( $_GET['action'] ) ? $_GET['action'] : '';
    // Actions à laisser sur wp-login.php (réinitialisation mdp, logout, confirm, etc.)
    $pass_through = array( 'logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass', 'confirm_admin_email' );
    if ( in_array( $action, $pass_through, true ) ) return;
    // Sinon, on renvoie vers /connexion/ en conservant les paramètres
    $connexion = get_page_by_path( 'connexion' );
    if ( ! $connexion ) return;
    $url = get_permalink( $connexion );
    if ( ! empty( $_GET ) ) {
        $url = add_query_arg( $_GET, $url );
    }
    wp_safe_redirect( $url );
    exit;
} );

// Auto-assigne le template Soutenir à la page /soutenir/ si elle existe
// et n'a pas encore de template défini.
add_action( 'init', function () {
    $soutenir = get_page_by_path( 'soutenir' );
    if ( ! $soutenir ) return;
    $current = get_page_template_slug( $soutenir->ID );
    if ( empty( $current ) ) {
        update_post_meta( $soutenir->ID, '_wp_page_template', 'templates/page-soutenir.php' );
    }
}, 25 );

// Crée l'arborescence de catégories si elle n'existe pas encore.
// Check léger (vérifie un seul term) et ne s'exécute qu'une fois par
// jour pour éviter les appels inutiles.
add_action( 'init', function () {
    if ( ! function_exists( 'ql_ensure_categories' ) ) return;
    $last = (int) get_option( 'ql_cats_init', 0 );
    if ( $last && ( time() - $last ) < DAY_IN_SECONDS ) return;
    ql_ensure_categories();
    update_option( 'ql_cats_init', time(), false );
}, 30 );

// ── 6. Extrait + "Lire la suite" sobres ─────────────────────────
add_filter( 'excerpt_length', function () { return 28; }, 999 );
add_filter( 'excerpt_more',  function () { return '…'; } );

// ── 7. Body class (utilitaire pour le CSS) ──────────────────────
add_filter( 'body_class', function ( $classes ) {
    if ( is_singular( 'post' ) ) { $classes[] = 'ql-single'; }
    if ( is_front_page() || is_home() ) { $classes[] = 'ql-home'; }
    if ( is_archive() || is_category() ) { $classes[] = 'ql-archive'; }
    return $classes;
} );

// ── 8-SEO. Améliorations SEO/perf complémentaires à Yoast ───────
// (Yoast gère déjà : meta description, Open Graph, Twitter Card,
//  JSON-LD, canonical, sitemap, robots.txt. On ajoute ce qui reste.)

// Preload du featured image sur les single posts — gain LCP
add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) || ! has_post_thumbnail() ) return;
    $src = get_the_post_thumbnail_url( null, 'ql-hero' );
    if ( ! $src ) return;
    echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '" fetchpriority="high">' . "\n";
}, 3 );

// ── Image de fond des blockquotes (témoignages) — par article ───
// Injecte une variable CSS --ql-bq-bg pointant sur une image thématique
// propre à l'article. Priorité :
//   1. meta _ql_bq_bg définie par frontmatter bq_background
//   2. featured_image si c'est un raster (jpg/png/webp — pas svg)
//   3. sinon : fallback CSS sur l'image partagée dans le thème
add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) ) return;
    $bq_bg = get_post_meta( get_the_ID(), '_ql_bq_bg', true );
    if ( ! $bq_bg && has_post_thumbnail() ) {
        $tid  = get_post_thumbnail_id();
        $mime = get_post_mime_type( $tid );
        if ( $mime && $mime !== 'image/svg+xml' ) {
            $bq_bg = wp_get_attachment_url( $tid );
        }
    }
    if ( $bq_bg ) {
        echo '<style>.ql-single{--ql-bq-bg:url("' . esc_url( $bq_bg ) . '")}</style>' . "\n";
    }
}, 4 );

// Alt text de secours si vide : utilise le titre de l'article
add_filter( 'wp_get_attachment_image_attributes', function ( $attrs, $attachment ) {
    if ( empty( $attrs['alt'] ) ) {
        $post = get_post( $attachment->post_parent );
        if ( $post ) {
            $attrs['alt'] = wp_strip_all_tags( get_the_title( $post ) );
        }
    }
    return $attrs;
}, 10, 2 );

// ── 8. Nettoyage wp_head (perf & propreté) ──────────────────────
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

// Supprimer les emojis WP (économise ~15 kB JS sur chaque page)
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// Préconnexion Google Fonts (gain LCP)
add_filter( 'wp_resource_hints', function ( $hints, $relation ) {
    if ( $relation === 'preconnect' ) {
        $hints[] = 'https://fonts.googleapis.com';
        $hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
    }
    return $hints;
}, 10, 2 );

// ── 8a. Liens externes dans les articles ─────────────────────
// Distingue automatiquement les liens internes (même domaine) des
// liens externes : les externes reçoivent target="_blank",
// rel="noopener" et la classe `ql-external` (utilisée pour
// l'indicateur visuel ↗ et le JS popup).
add_filter( 'the_content', function ( $content ) {
    if ( empty( $content ) || ! is_singular() ) return $content;

    $home = parse_url( home_url(), PHP_URL_HOST );
    if ( ! $home ) return $content;

    return preg_replace_callback(
        '#<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>#i',
        function ( $m ) use ( $home ) {
            $before = $m[1]; $href = $m[3]; $after = $m[4];
            // Ignore les ancres, mailto, tel:, javascript:
            if ( preg_match( '#^(#|mailto:|tel:|javascript:)#i', $href ) ) return $m[0];
            // Relatif → interne, pas touché
            if ( ! preg_match( '#^https?://#i', $href ) ) return $m[0];
            $link_host = parse_url( $href, PHP_URL_HOST );
            if ( $link_host === $home ) return $m[0]; // interne

            // Externe : ajouter target, rel, class si absents
            $attrs = $before . $after;
            if ( ! preg_match( '/\btarget=/i', $attrs ) ) {
                $after .= ' target="_blank"';
            }
            if ( ! preg_match( '/\brel=/i', $attrs ) ) {
                $after .= ' rel="noopener nofollow"';
            }
            if ( preg_match( '/\bclass=(["\'])([^"\']*)\1/i', $attrs, $cm ) ) {
                if ( strpos( $cm[2], 'ql-external' ) === false ) {
                    $new_class = trim( $cm[2] . ' ql-external' );
                    $replacement = 'class=' . $cm[1] . $new_class . $cm[1];
                    $before = preg_replace( '/\bclass=(["\'])[^"\']*\1/i', $replacement, $before, 1 );
                    $after  = preg_replace( '/\bclass=(["\'])[^"\']*\1/i', $replacement, $after, 1 );
                }
            } else {
                $after .= ' class="ql-external"';
            }
            return '<a ' . trim( $before ) . ' href="' . esc_url( $href ) . '"' . $after . '>';
        },
        $content
    );
}, 20 );

// ── 8b. Support upload SVG (réservé aux admins pour raisons de sécurité) ─
add_filter( 'upload_mimes', function ( $mimes ) {
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
} );
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
    if ( ! current_user_can( 'manage_options' ) ) return $data;
    if ( substr( $filename, -4 ) === '.svg' ) {
        $data['ext']             = 'svg';
        $data['type']            = 'image/svg+xml';
        $data['proper_filename'] = $filename;
    }
    return $data;
}, 10, 4 );
// Fix affichage SVG dans la médiathèque (calcul de dimensions)
add_filter( 'wp_get_attachment_image_src', function ( $image, $attachment_id ) {
    if ( ! $image ) return $image;
    $mime = get_post_mime_type( $attachment_id );
    if ( $mime === 'image/svg+xml' && empty( $image[1] ) ) {
        $image[1] = 1600;
        $image[2] = 900;
    }
    return $image;
}, 10, 2 );

// ── 9. Lazy-loading natif + décodage async des images du contenu ─
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
    if ( ! isset( $attr['loading'] ) )  { $attr['loading']  = 'lazy'; }
    if ( ! isset( $attr['decoding'] ) ) { $attr['decoding'] = 'async'; }
    return $attr;
} );

// ── 9b. Sanitizer le_content : retirer parasites visuels ─────────
/**
 * Nettoie le contenu des articles avant rendu :
 *  - Retire styles inline dangereux (width/height/position/transform/clip-path…)
 *  - Retire les SVG décoratifs pleins (vagues, formes) embarqués
 *  - Retire les shortcodes de login/register
 *  - Retire les widgets de newsletter dans l'article
 * S'applique aux articles en affichage single, pas à l'éditeur.
 */
add_filter( 'the_content', function ( $content ) {
    if ( ! is_singular() || is_admin() || wp_doing_ajax() ) { return $content; }

    // 1. Retire les SVG décoratifs pleins (vagues noires typiques du vieux thème)
    //    Critère : SVG avec viewBox et sans <text> à l'intérieur.
    $content = preg_replace_callback(
        '#<svg\b[^>]*>(.*?)</svg>#is',
        function ( $m ) {
            // Conserver les SVG utiles (petits pictos avec du <text> ou très petits)
            if ( stripos( $m[1], '<text' ) !== false ) return $m[0];
            // Si le SVG fait > 200 unités de haut (vague décorative), on vire
            if ( preg_match( '/viewBox="[^"]*\s(\d+)(?:\s|")/', $m[0], $vb ) && (int) $vb[1] > 100 ) {
                return '';
            }
            if ( preg_match( '/height="(\d+)/', $m[0], $h ) && (int) $h[1] > 80 ) {
                return '';
            }
            return $m[0];
        },
        $content
    );

    // 2. Retire les shortcodes de login/register qui pourraient trainer
    $content = preg_replace( '/\[(login-form|register-form|loginform|loginpress[^\]]*|user_registration[^\]]*|um_loggedin[^\]]*|um_loggedout[^\]]*)[^\]]*\]/i', '', $content );

    // 3. Retire les blocs HTML <form action="...wp-login..."> embarqués
    $content = preg_replace( '#<form[^>]*action="[^"]*wp-login[^"]*"[^>]*>.*?</form>#is', '', $content );

    // 4. Retire width/height HTML attributes (forcent tailles rigides)
    $content = preg_replace( '/\s(width|height)="\d+"/i', '', $content );

    // 4b. Strip TOUS les style="" sur les headings (h1-h6) + groupes — ils cassent le layout
    $content = preg_replace_callback(
        '#<(h[1-6]|div|section)(\s+[^>]*?class="[^"]*(?:wp-block-heading|wp-block-group|has-background|has-text-align)[^"]*")[^>]*>#is',
        function ( $m ) {
            // Retire style=".." complet sur ces elements
            return preg_replace( '/\s+style\s*=\s*"[^"]*"/i', '', $m[0] );
        },
        $content
    );

    // 4c. Strip aussi tous les style sur les h1-h6 quoi qu'il arrive
    $content = preg_replace_callback(
        '#<(h[1-6])([^>]*)>#i',
        function ( $m ) {
            $attrs = preg_replace( '/\s+style\s*=\s*"[^"]*"/i', '', $m[2] );
            return '<' . $m[1] . $attrs . '>';
        },
        $content
    );

    // 4d. Retire les classes Gutenberg cassantes (has-background-color, has-*-color, etc.)
    $content = preg_replace_callback(
        '#class="([^"]*)"#i',
        function ( $m ) {
            $classes = preg_split( '/\s+/', $m[1] );
            $kept    = array();
            foreach ( $classes as $c ) {
                // On garde wp-block-image (gère l'image), alignleft/right, aligncenter
                // On vire tout ce qui touche background/couleur/position
                $blacklist_patterns = array(
                    '/^has-.*(background|text-align|color).*/',
                    '/^is-style-.*/',
                    '/^wp-block-heading$/',
                );
                $skip = false;
                foreach ( $blacklist_patterns as $pat ) {
                    if ( preg_match( $pat, $c ) ) { $skip = true; break; }
                }
                if ( ! $skip ) $kept[] = $c;
            }
            return 'class="' . esc_attr( implode( ' ', $kept ) ) . '"';
        },
        $content
    );

    // 5. Nettoie les style="..." dangereux
    $content = preg_replace_callback(
        '/style\s*=\s*"([^"]*)"/i',
        function ( $m ) {
            $rules = array_filter( array_map( 'trim', explode( ';', $m[1] ) ) );
            $keep  = array();
            foreach ( $rules as $rule ) {
                if ( ! $rule ) continue;
                $prop = strtolower( trim( explode( ':', $rule, 2 )[0] ?? '' ) );
                $blacklist = array(
                    'width', 'height', 'min-width', 'min-height', 'max-width', 'max-height',
                    'position', 'top', 'left', 'right', 'bottom',
                    'transform', 'clip-path', 'float',
                    'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
                    'z-index',
                );
                if ( ! in_array( $prop, $blacklist, true ) ) {
                    $keep[] = $rule;
                }
            }
            return empty( $keep ) ? '' : 'style="' . esc_attr( implode( '; ', $keep ) ) . '"';
        },
        $content
    );

    return $content;
}, 99 );

// ── 10. Widget areas ────────────────────────────────────────────
add_action( 'widgets_init', function () {
    register_sidebar( array(
        'name'          => __( 'Colonne latérale article', 'quartier-libre' ),
        'id'            => 'ql-sidebar',
        'before_widget' => '<div class="ql-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="ql-widget__title">',
        'after_title'   => '</h3>',
    ) );
} );

// ── 11. Helper : catégorie principale d'un article ──────────────
/**
 * Affiche le logo Quartier Libre (helper réutilisable).
 * Priorité :
 *   1. Logo custom défini dans WP Customizer (option la plus simple pour l'admin)
 *   2. Fichier local assets/images/logo.svg|png|webp
 *   3. Wordmark texte en fallback
 *
 * @param array $args {
 *   class     : classes CSS additionnelles (défaut '')
 *   link      : true pour wrapper dans un <a> vers l'accueil (défaut true)
 *   alt       : texte alternatif (défaut : nom du site)
 *   size      : taille WP (custom_logo seulement, défaut 'medium')
 *   loading   : 'lazy' ou 'eager' (défaut 'lazy')
 * }
 */
function ql_logo( $args = array() ) {
    $args = wp_parse_args( $args, array(
        'class'   => '',
        'link'    => true,
        'alt'     => get_bloginfo( 'name' ),
        'size'    => 'medium',
        'loading' => 'lazy',
    ) );

    $class  = 'ql-logo ' . $args['class'];
    $alt    = esc_attr( $args['alt'] );
    $img    = '';

    $custom = get_theme_mod( 'custom_logo' );
    if ( $custom ) {
        $img = wp_get_attachment_image( $custom, $args['size'], false, array(
            'class'   => $class,
            'alt'     => $alt,
            'loading' => $args['loading'],
        ) );
    } else {
        $files = array( '/assets/images/logo.svg', '/assets/images/logo.png', '/assets/images/logo.webp' );
        $found = '';
        foreach ( $files as $p ) {
            if ( file_exists( QL_THEME_DIR . $p ) ) { $found = QL_THEME_URI . $p; break; }
        }
        if ( $found ) {
            $img = '<img src="' . esc_url( $found ) . '" alt="' . $alt . '" class="' . esc_attr( $class ) . '" loading="' . esc_attr( $args['loading'] ) . '">';
        } else {
            // Fallback wordmark texte
            $img = '<span class="ql-logo ql-logo--wordmark ' . esc_attr( $args['class'] ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
        }
    }

    if ( $args['link'] ) {
        $img = '<a href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . $alt . ' — accueil">' . $img . '</a>';
    }

    return $img;
}

function ql_primary_category( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();
    $cats    = get_the_category( $post_id );
    if ( empty( $cats ) ) { return null; }
    // On privilégie les sous-catégories (parent > 0 = plus spécifiques).
    // Ex : un article dans « bellevue » (enfant d'« infos-locale »)
    // affiche « Bellevue » comme badge, pas « Info locale ».
    foreach ( $cats as $c ) {
        if ( (int) $c->parent > 0 ) return $c;
    }
    return $cats[0];
}

/**
 * Remonte à la catégorie racine (top-level) depuis n'importe quelle
 * sous-catégorie. Utile pour grouper : l'article « bellevue » a pour
 * racine « infos-locale ».
 */
function ql_root_category( $cat ) {
    if ( ! $cat || is_wp_error( $cat ) ) return null;
    $guard = 0;
    while ( (int) $cat->parent > 0 && $guard++ < 10 ) {
        $parent = get_term( $cat->parent, 'category' );
        if ( ! $parent || is_wp_error( $parent ) ) break;
        $cat = $parent;
    }
    return $cat;
}

// ── 12. Traitement du formulaire Bureau des Plaintes ───────────
add_action( 'admin_post_nopriv_ql_plainte', 'ql_handle_plainte' );
add_action( 'admin_post_ql_plainte',        'ql_handle_plainte' );
function ql_handle_plainte() {
    if ( ! isset( $_POST['ql_plainte_nonce'] ) || ! wp_verify_nonce( $_POST['ql_plainte_nonce'], 'ql_plainte' ) ) {
        wp_die( 'Jeton de sécurité invalide.' );
    }

    $type    = sanitize_text_field( wp_unslash( $_POST['ql_type']    ?? '' ) );
    $quartier= sanitize_text_field( wp_unslash( $_POST['ql_quartier']?? '' ) );
    $nom     = sanitize_text_field( wp_unslash( $_POST['ql_nom']     ?? '' ) );
    $email   = sanitize_email(     wp_unslash( $_POST['ql_email']    ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['ql_message'] ?? '' ) );

    if ( empty( $message ) || empty( $type ) ) {
        wp_safe_redirect( add_query_arg( 'plainte', 'erreur', wp_get_referer() ?: home_url() ) );
        exit;
    }

    $to      = get_option( 'admin_email' );
    $subject = '[Bureau des Plaintes] ' . $type . ' — ' . $quartier;
    $body    = "Type : {$type}\nQuartier : {$quartier}\nNom : {$nom}\nEmail : {$email}\n\n---\n{$message}\n";
    $headers = array();
    if ( $email ) { $headers[] = 'Reply-To: ' . $email; }

    wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'plainte', 'envoye', wp_get_referer() ?: home_url() ) );
    exit;
}
