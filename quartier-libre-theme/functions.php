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

    // Logo personnalisé (Apparence → Personnaliser → Identité du site → Logo).
    // Sans cette déclaration, l'option custom_logo n'est PAS exposée dans
    // le Customizer → le thème ne peut pas lire get_theme_mod('custom_logo').
    add_theme_support( 'custom-logo', array(
        'height'      => 120,
        'width'       => 480,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

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

    // MIGRATION v4 : le slug HelloAsso par défaut était 'quartier-libre-nantes'
    // (placeholder faux). Le vrai slug est 'union-des-quartiers-libres'
    // (trouvé via l'API HelloAsso /v5/users/me/organizations).
    if ( $cfg_ver < 4 ) {
        if ( get_option( 'ql_helloasso_org_slug' ) === 'quartier-libre-nantes' ) {
            update_option( 'ql_helloasso_org_slug', 'union-des-quartiers-libres' );
        }
        update_option( 'ql_payment_cfg_ver', 4 );
    }
    if ( ! get_option( 'ql_helloasso_org_slug' ) ) {
        add_option( 'ql_helloasso_org_slug', 'union-des-quartiers-libres', '', 'yes' );
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

// Auto-création de la page /mon-profil/ (espace utilisateur custom,
// sans accès à wp-admin)
add_action( 'init', function () {
    $existing = get_page_by_path( 'mon-profil' );
    if ( $existing ) {
        if ( ! get_page_template_slug( $existing->ID ) ) {
            update_post_meta( $existing->ID, '_wp_page_template', 'templates/page-mon-profil.php' );
        }
        return;
    }
    $pid = wp_insert_post( array(
        'post_title'   => 'Mon profil',
        'post_name'    => 'mon-profil',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => 'Espace personnel — gérez votre compte, photo, préférences.',
    ) );
    if ( $pid && ! is_wp_error( $pid ) ) {
        update_post_meta( $pid, '_wp_page_template', 'templates/page-mon-profil.php' );
    }
}, 28 );

// ── Blocage total de wp-admin pour les utilisateurs non-éditeurs ──
// Les abonnés (subscriber) n'ont RIEN à faire dans le back-office WP.
// On redirige toute tentative d'accès à /wp-admin/* vers /mon-profil/.
// Les rôles avec edit_posts (author, editor, administrator) passent —
// seul l'admin réel touche à l'admin WP.
add_action( 'admin_init', function () {
    // Laisser passer les appels AJAX (nombreux plugins en dépendent)
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    // Laisser passer les admin-post.php qu'on utilise pour nos forms
    if ( isset( $_POST['action'] ) && strpos( (string) $_POST['action'], 'ql_' ) === 0 ) return;
    // Laisser passer les rôles qui savent écrire
    if ( current_user_can( 'edit_posts' ) ) return;

    // Rediriger vers /mon-profil/
    $profile_page = get_page_by_path( 'mon-profil' );
    $redirect = $profile_page ? get_permalink( $profile_page ) : home_url( '/mon-profil/' );
    wp_safe_redirect( $redirect );
    exit;
} );

// Masquer la barre d'admin WP pour tous les non-éditeurs
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'edit_posts' ) ) show_admin_bar( false );
} );

// Le lien "Éditer le profil" WP redirige vers /mon-profil/ (évite que
// d'autres plugins/emails linkent vers /wp-admin/profile.php)
add_filter( 'edit_profile_url', function ( $url, $user_id ) {
    if ( user_can( $user_id, 'edit_posts' ) ) return $url; // admins gardent la vraie
    $profile_page = get_page_by_path( 'mon-profil' );
    return $profile_page ? get_permalink( $profile_page ) : home_url( '/mon-profil/' );
}, 10, 2 );

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
/**
 * Résout l'URL du logo avec cascade ultra-robuste :
 *   1. custom_logo (Customizer WP — la voie officielle)
 *   2. Recherche médiathèque pour un attachment dont le nom contient "logo"
 *      (gère le cas où l'admin a uploadé directement via Médias sans
 *      passer par le Customizer, ex : logoA4.png, logo-quartier.svg…)
 *   3. Fichier dans le thème : assets/images/logo.{svg,png,webp}
 *   4. Chaîne vide = le template doit utiliser le wordmark texte
 *
 * Résultat mis en cache 1h dans un transient — si l'admin change le logo
 * il peut vider via le bouton ou attendre l'expiration.
 */
function ql_resolve_logo_url() {
    $cached = get_transient( 'ql_logo_url_v2' );
    if ( $cached !== false ) return $cached; // peut être '' si aucun trouvé

    $url = '';

    // 0. URL explicite (option ql_logo_url) — valeur par défaut ci-dessous,
    //    permet de piloter le logo sans passer par le Customizer.
    //    Changer via : wp option update ql_logo_url "https://..."
    $explicit = get_option( 'ql_logo_url', 'https://quartierlibre.org/wp-content/uploads/2026/04/logo_home.png' );
    if ( $explicit ) {
        $url = $explicit;
    }

    // 1. custom_logo via Customizer — écrase seulement si on a explicitement
    //    uploadé un logo via Apparence → Personnaliser (signal fort de l'admin)
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $src = wp_get_attachment_image_url( $logo_id, 'full' );
        if ( $src ) $url = $src;
    }

    // 2. Recherche médiathèque par nom de fichier
    if ( ! $url ) {
        $q = new WP_Query( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => array( 'image/png', 'image/jpeg', 'image/svg+xml', 'image/webp' ),
            'posts_per_page' => 20,
            's'              => 'logo',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        if ( ! empty( $q->posts ) ) {
            // Préférence : nom de fichier qui commence par "logo"
            $best = 0;
            foreach ( $q->posts as $id ) {
                $file = get_attached_file( $id );
                if ( ! $file ) continue;
                $basename = strtolower( basename( $file ) );
                if ( strpos( $basename, 'logo' ) === 0 ) { $best = $id; break; }
                if ( ! $best ) $best = $id;
            }
            if ( $best ) {
                $src = wp_get_attachment_image_url( $best, 'full' );
                if ( $src ) $url = $src;
            }
        }
    }

    // 3. Fichier thème
    if ( ! $url ) {
        $files = array( '/assets/images/logo.svg', '/assets/images/logo.png', '/assets/images/logo.webp' );
        foreach ( $files as $p ) {
            if ( file_exists( QL_THEME_DIR . $p ) ) { $url = QL_THEME_URI . $p; break; }
        }
    }

    set_transient( 'ql_logo_url_v2', $url, HOUR_IN_SECONDS );
    return $url;
}

/**
 * Vide le cache du logo quand l'admin change de logo dans le Customizer
 * ou upload un nouveau fichier média.
 */
add_action( 'customize_save_after', function() { delete_transient( 'ql_logo_url_v2' ); delete_transient( 'ql_logo_url_v1' ); } );
add_action( 'add_attachment',       function() { delete_transient( 'ql_logo_url_v2' ); delete_transient( 'ql_logo_url_v1' ); } );
add_action( 'delete_attachment',    function() { delete_transient( 'ql_logo_url_v2' ); delete_transient( 'ql_logo_url_v1' ); } );
// Cleanup des vieux transients au bump de version ql_logo_url_v1 → v2
add_action( 'init', function() {
    if ( get_option( 'ql_logo_cache_bump' ) !== 'v2' ) {
        delete_transient( 'ql_logo_url_v1' );
        delete_transient( 'ql_logo_url_v2' );
        update_option( 'ql_logo_cache_bump', 'v2', false );
    }
}, 5 );

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

/**
 * Choisit LA catégorie à afficher comme badge principal d'un article.
 *
 * Cascade de priorité :
 *   1. Override explicite dans le frontmatter via `primary_category: slug`
 *      (stocké en post_meta _ql_primary_category).
 *   2. Si l'article touche PLUSIEURS quartiers (≥ 2 sous-cats d'infos-locale)
 *      → c'est un article TRANSVERSAL. On prend la première sous-cat
 *      transversale (luttes/france/international) pour refléter le vrai
 *      sujet, pas un quartier arbitraire.
 *   3. Sinon (0 ou 1 quartier) : première sous-cat trouvée = quartier
 *      unique si présent, sinon thème transversal.
 *   4. Fallback : première catégorie top-level.
 */
function ql_primary_category( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();
    $cats    = get_the_category( $post_id );
    if ( empty( $cats ) ) { return null; }

    // 1. Override manuel ?
    $override_slug = get_post_meta( $post_id, '_ql_primary_category', true );
    if ( $override_slug ) {
        foreach ( $cats as $c ) {
            if ( $c->slug === $override_slug ) return $c;
        }
    }

    // Trouver les IDs des parents "quartier" (infos-locale) et "transversal"
    $infos_locale = get_term_by( 'slug', 'infos-locale', 'category' );
    $infos_id     = $infos_locale ? (int) $infos_locale->term_id : 0;

    // Sépare sous-cats quartiers vs sous-cats transversales
    $quartier_cats    = array();
    $transversal_cats = array();
    foreach ( $cats as $c ) {
        if ( (int) $c->parent === 0 ) continue; // top-level, on ignore ici
        if ( $infos_id && (int) $c->parent === $infos_id ) {
            $quartier_cats[] = $c;
        } else {
            $transversal_cats[] = $c;
        }
    }

    // 2. Multi-quartier → prend le transversal
    if ( count( $quartier_cats ) >= 2 && ! empty( $transversal_cats ) ) {
        return $transversal_cats[0];
    }

    // 3. Sinon : priorité à la première sous-cat (quartier unique ou transversal)
    if ( ! empty( $quartier_cats ) )    return $quartier_cats[0];
    if ( ! empty( $transversal_cats ) ) return $transversal_cats[0];

    // 4. Fallback : première top-level
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

/**
 * Filtre get_avatar_url/get_avatar pour renvoyer une photo custom
 * quand l'utilisateur a uploadé une image via /mon-profil/.
 * La photo est stockée comme attachment + ID dans user meta `ql_avatar_id`.
 */
add_filter( 'get_avatar_url', function ( $url, $id_or_email, $args ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    } elseif ( is_string( $id_or_email ) ) {
        $u = get_user_by( 'email', $id_or_email );
        if ( $u ) $user_id = $u->ID;
    }
    if ( ! $user_id ) return $url;

    $avatar_id = (int) get_user_meta( $user_id, 'ql_avatar_id', true );
    if ( ! $avatar_id ) return $url;

    $size = isset( $args['size'] ) ? (int) $args['size'] : 96;
    $custom = wp_get_attachment_image_url( $avatar_id, array( $size, $size ) );
    return $custom ? $custom : $url;
}, 10, 3 );

/**
 * Handler : mise à jour du profil utilisateur depuis /mon-profil/.
 * Gère : display_name, first_name, last_name, description, email, photo,
 * changement de mot de passe. Toujours soumis en POST via admin-post.php.
 */
add_action( 'admin_post_ql_profile_update', 'ql_handle_profile_update' );
function ql_handle_profile_update() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/connexion/' ) );
        exit;
    }
    if ( ! isset( $_POST['ql_profile_nonce'] ) || ! wp_verify_nonce( $_POST['ql_profile_nonce'], 'ql_profile_update' ) ) {
        wp_die( 'Jeton de sécurité invalide. Retour en arrière et réessayez.' );
    }

    $user_id = get_current_user_id();
    $redirect = add_query_arg( 'updated', '1', home_url( '/mon-profil/' ) );

    // ── 1. Infos de base ──
    $data = array( 'ID' => $user_id );
    if ( isset( $_POST['display_name'] ) ) {
        $data['display_name'] = sanitize_text_field( wp_unslash( $_POST['display_name'] ) );
    }
    if ( isset( $_POST['first_name'] ) ) {
        $data['first_name'] = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
    }
    if ( isset( $_POST['last_name'] ) ) {
        $data['last_name'] = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
    }
    if ( isset( $_POST['description'] ) ) {
        $data['description'] = wp_kses_post( wp_unslash( $_POST['description'] ) );
    }
    if ( isset( $_POST['user_email'] ) ) {
        $new_email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
        if ( $new_email && is_email( $new_email ) ) {
            $data['user_email'] = $new_email;
        }
    }

    // ── 2. Mot de passe ──
    $new_pass = isset( $_POST['new_password'] ) ? (string) $_POST['new_password'] : '';
    $confirm  = isset( $_POST['confirm_password'] ) ? (string) $_POST['confirm_password'] : '';
    if ( $new_pass !== '' ) {
        if ( strlen( $new_pass ) < 8 ) {
            $redirect = add_query_arg( 'err', 'pwd_short', home_url( '/mon-profil/' ) );
            wp_safe_redirect( $redirect ); exit;
        }
        if ( $new_pass !== $confirm ) {
            $redirect = add_query_arg( 'err', 'pwd_mismatch', home_url( '/mon-profil/' ) );
            wp_safe_redirect( $redirect ); exit;
        }
        $data['user_pass'] = $new_pass;
    }

    $res = wp_update_user( $data );
    if ( is_wp_error( $res ) ) {
        $redirect = add_query_arg( 'err', 'update_failed', home_url( '/mon-profil/' ) );
        wp_safe_redirect( $redirect ); exit;
    }

    // ── 3. Upload de photo ──
    if ( ! empty( $_FILES['ql_avatar']['name'] ) && empty( $_FILES['ql_avatar']['error'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $allowed = array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );
        $filetype = wp_check_filetype_and_ext( $_FILES['ql_avatar']['tmp_name'], $_FILES['ql_avatar']['name'] );
        if ( ! in_array( $filetype['type'], $allowed, true ) ) {
            $redirect = add_query_arg( 'err', 'bad_image', home_url( '/mon-profil/' ) );
            wp_safe_redirect( $redirect ); exit;
        }
        if ( $_FILES['ql_avatar']['size'] > 2 * 1024 * 1024 ) {
            $redirect = add_query_arg( 'err', 'image_too_big', home_url( '/mon-profil/' ) );
            wp_safe_redirect( $redirect ); exit;
        }

        $attach_id = media_handle_upload( 'ql_avatar', 0 );
        if ( is_wp_error( $attach_id ) ) {
            $redirect = add_query_arg( 'err', 'upload_failed', home_url( '/mon-profil/' ) );
            wp_safe_redirect( $redirect ); exit;
        }

        // Supprime l'ancienne photo si elle existait
        $old_id = (int) get_user_meta( $user_id, 'ql_avatar_id', true );
        if ( $old_id && $old_id !== $attach_id ) {
            wp_delete_attachment( $old_id, true );
        }

        update_user_meta( $user_id, 'ql_avatar_id', $attach_id );
    }

    // ── 4. Suppression de photo ──
    if ( ! empty( $_POST['ql_avatar_remove'] ) ) {
        $old_id = (int) get_user_meta( $user_id, 'ql_avatar_id', true );
        if ( $old_id ) { wp_delete_attachment( $old_id, true ); }
        delete_user_meta( $user_id, 'ql_avatar_id' );
    }

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Boutons de connexion sociale (Google / Facebook / Apple).
 *
 * Compatible avec le plugin **Nextend Social Login** (gratuit) : les URLs
 * générées sont `/wp-login.php?loginSocial=<provider>`, format Nextend.
 * Dès que le plugin est activé côté admin (Extensions → Ajouter → Nextend
 * Social Login → configurer les Client ID/Secret de chaque provider),
 * ces boutons fonctionnent sans modification de code.
 *
 * Si le plugin n'est pas actif : les boutons renvoient à wp-login.php qui
 * ignore le paramètre loginSocial (fallback gracieux — l'utilisateur voit
 * le form login WP classique).
 *
 * Un admin verra un avertissement discret en haut de la page connexion.
 */
function ql_social_login_buttons( $redirect_to = '' ) {
    $providers = array(
        'google' => array(
            'label' => 'Continuer avec Google',
            'brand' => '#ea4335',
            'icon'  => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A10.98 10.98 0 0 0 12 23z"/><path fill="#fbbc04" d="M5.84 14.09A6.6 6.6 0 0 1 5.48 12c0-.72.13-1.43.36-2.09V7.07H2.18A10.98 10.98 0 0 0 1 12c0 1.77.42 3.44 1.18 4.93z"/><path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A10.98 10.98 0 0 0 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>',
        ),
        'facebook' => array(
            'label' => 'Continuer avec Facebook',
            'brand' => '#1877f2',
            'icon'  => '<svg viewBox="0 0 24 24" fill="#fff" width="18" height="18"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>',
        ),
        'apple' => array(
            'label' => 'Continuer avec Apple',
            'brand' => '#000',
            'icon'  => '<svg viewBox="0 0 24 24" fill="#fff" width="18" height="18"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>',
        ),
    );

    $out = '<div class="ql-social-login" role="group" aria-label="Connexion via réseaux sociaux">';
    $out .= '<div class="ql-social-login__separator"><span>ou</span></div>';

    foreach ( $providers as $key => $p ) {
        $url = add_query_arg( array(
            'loginSocial' => $key,
            'redirect_to' => $redirect_to ?: home_url( '/' ),
        ), site_url( 'wp-login.php' ) );

        $out .= sprintf(
            '<a href="%s" class="ql-social-login__btn ql-social-login__btn--%s" data-provider="%s">'
          . '<span class="ql-social-login__icon">%s</span>'
          . '<span class="ql-social-login__label">%s</span>'
          . '</a>',
            esc_url( $url ),
            esc_attr( $key ),
            esc_attr( $key ),
            $p['icon'],
            esc_html( $p['label'] )
        );
    }

    // Avertissement admin si Nextend pas actif
    if ( current_user_can( 'manage_options' ) && ! class_exists( 'NextendSocialLogin' ) && ! class_exists( '\\NSL\\REST\\Nextend_REST' ) ) {
        $out .= '<p class="ql-social-login__admin-note">'
              . '<strong>Admin :</strong> installez et activez le plugin <a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&s=nextend+social+login' ) ) . '" target="_blank">Nextend Social Login</a> pour activer réellement ces boutons. Tant qu\'il n\'est pas actif, les boutons renvoient à la page wp-login.php standard.'
              . '</p>';
    }

    $out .= '</div>';
    return $out;
}

/**
 * SVG d'icône de réseau social (inline, couleur currentColor).
 * Source des paths : Feather Icons + simplicite icons — licence MIT.
 */
function ql_social_icon_svg( $key ) {
    $icons = array(
        'mastodon'  => '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M21.58 13.91c-.29 1.49-2.6 3.12-5.25 3.44-1.38.16-2.74.32-4.19.25-2.37-.11-4.24-.57-4.24-.57 0 .23.02.45.04.66.31 2.33 2.31 2.47 4.2 2.53 1.91.07 3.61-.47 3.61-.47l.08 1.73s-1.33.72-3.71.85c-1.31.07-2.94-.03-4.83-.53-4.1-1.08-4.81-5.47-4.92-9.92-.03-1.32-.01-2.57-.01-3.61 0-4.55 2.98-5.88 2.98-5.88 1.5-.69 4.08-.98 6.76-1h.07c2.68.02 5.26.31 6.77 1 0 0 2.98 1.33 2.98 5.88 0 0 .04 3.35-.42 5.67m-3.1-5.08v5.53h-2.19V8.99c0-1.14-.48-1.72-1.44-1.72-1.06 0-1.59.69-1.59 2.04v2.96h-2.18V9.31c0-1.35-.53-2.04-1.59-2.04-.96 0-1.44.58-1.44 1.72v5.37H5.86V8.83c0-1.14.29-2.04.87-2.71.6-.67 1.39-1.01 2.37-1.01 1.13 0 1.99.44 2.55 1.31L12.2 7l.55-.58c.56-.87 1.42-1.31 2.55-1.31.98 0 1.77.34 2.37 1.01.58.67.87 1.57.87 2.71"/></svg>',
        'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>',
        'telegram'  => '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M21.58 3.18 2.51 10.54c-1.3.52-1.3 1.27-.24 1.59l4.89 1.53 11.32-7.14c.54-.33 1.03-.15.62.21l-9.17 8.28h-.02l.02.01-.34 5.04c.5 0 .72-.23 1-.5l2.4-2.33 4.98 3.68c.92.5 1.58.25 1.81-.85l3.28-15.47c.34-1.35-.51-1.96-1.48-1.41z"/></svg>',
        'snapchat'  => '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.224-.061.524.12.868l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.03c-.135 0-.313-.031-.538-.074-.36-.075-.765-.135-1.273-.135-.3 0-.599.015-.913.074-.6.104-1.123.464-1.723.884-.853.599-1.826 1.288-3.294 1.288-.06 0-.119-.015-.18-.015h-.149c-1.468 0-2.427-.675-3.279-1.288-.599-.42-1.107-.779-1.707-.884-.314-.045-.629-.074-.928-.074-.54 0-.958.089-1.272.149-.211.043-.391.074-.54.074-.374 0-.523-.224-.583-.42-.061-.192-.09-.389-.135-.567-.046-.181-.105-.494-.166-.57-1.918-.222-2.95-.642-3.189-1.226-.031-.063-.052-.15-.055-.225-.015-.243.165-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.224-.645.119-.869-.195-.434-.884-.658-1.332-.809-.121-.029-.24-.074-.346-.119-1.107-.435-1.257-.93-1.197-1.273.09-.479.674-.793 1.168-.793.146 0 .27.029.383.074.42.194.789.3 1.104.3.234 0 .384-.06.465-.105l-.046-.569c-.098-1.626-.225-3.651.307-4.837C7.392 1.077 10.739.807 11.727.807l.419-.015z"/></svg>',
        'rss'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>',
    );
    return isset( $icons[ $key ] ) ? $icons[ $key ] : '';
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

// ══════════════════════════════════════════════════════════════
// FICHES SOURCES ÉDITORIALES — accessible depuis WP admin
// ══════════════════════════════════════════════════════════════
// Chaque article synchronisé depuis GitHub peut avoir un fichier
// frère dans `content/sources/<slug>.md` qui liste les sources de
// chaque affirmation factuelle. ql-sync.php fetch ces fichiers et
// les stocke en post_meta `_ql_sources_md`. On les affiche ici :
//   1. Meta-box « Sources & vérification » dans l'éditeur d'article
//   2. Colonne dans la liste des articles (✔ sourcé / ⚠ sans source)

// 1. Meta-box dans l'éditeur
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'ql_sources_metabox',
        '📋 Sources & vérification éditoriale',
        'ql_render_sources_metabox',
        'post',
        'normal',
        'high'
    );
} );

function ql_render_sources_metabox( $post ) {
    $sources_md = get_post_meta( $post->ID, '_ql_sources_md', true );

    if ( empty( $sources_md ) ) {
        echo '<div style="padding:20px;background:#fff1ef;border:2px solid #e63312;border-radius:6px;text-align:center;">';
        echo '<p style="margin:0 0 10px;font-weight:800;color:#7a2010;font-size:1.1em;">⚠ AUCUNE FICHE SOURCE</p>';
        echo '<p style="margin:0;color:#5a5a5a;">';
        echo 'Cet article n\'a pas de fiche <code>content/sources/' . esc_html( $post->post_name ) . '.md</code>.';
        echo '<br>Chaque article publié doit avoir une fiche sources pour traçabilité éditoriale.';
        echo '</p>';
        echo '</div>';
        return;
    }

    // Parse les sections ✓ / ⚠ / ✗ / 👤 pour faire le résumé chiffré
    $parsed = ql_parse_sources_sections( $sources_md );
    $v = count( $parsed['verified'] );
    $i = count( $parsed['imprecise'] );
    $m = count( $parsed['missing'] );
    $c = count( $parsed['tocheck'] );
    $total = $v + $i + $m + $c;

    echo '<style>
        .ql-src-dash { margin: 0 0 1em; }
        .ql-src-dash__cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 1.2em; }
        .ql-src-card { padding: 14px 16px; border-radius: 6px; text-align: center; border: 2px solid; }
        .ql-src-card--ok { background: #e8f5e8; border-color: #2a8a2a; color: #1a5f1a; }
        .ql-src-card--warn { background: #fff8e1; border-color: #f2a000; color: #7a5c00; }
        .ql-src-card--missing { background: #fff1ef; border-color: #e63312; color: #7a2010; }
        .ql-src-card--check { background: #e6f2ff; border-color: #0d5ba8; color: #0d3e75; }
        .ql-src-card__num { font-size: 2em; font-weight: 900; line-height: 1; font-family: serif; }
        .ql-src-card__lbl { font-size: .82em; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
        .ql-src-card__desc { font-size: .78em; margin-top: 2px; opacity: .8; }

        .ql-src-section { margin: 0 0 1em; border-radius: 6px; overflow: hidden; }
        .ql-src-section--ok { border: 1px solid #b0d6b0; }
        .ql-src-section--warn { border: 1px solid #f2c94c; }
        .ql-src-section--missing { border: 1px solid #e63312; }
        .ql-src-section--check { border: 1px solid #6aaae4; }
        .ql-src-section summary { padding: 10px 14px; font-weight: 700; cursor: pointer; user-select: none; }
        .ql-src-section--ok > summary { background: #e8f5e8; color: #1a5f1a; }
        .ql-src-section--warn > summary { background: #fff8e1; color: #7a5c00; }
        .ql-src-section--missing > summary { background: #fff1ef; color: #7a2010; }
        .ql-src-section--check > summary { background: #e6f2ff; color: #0d3e75; }
        .ql-src-section ul { margin: 0; padding: 10px 14px 14px 36px; background: #fff; }
        .ql-src-section li { margin: 0 0 .5em; line-height: 1.5; }
        .ql-src-section li:last-child { margin-bottom: 0; }
        .ql-src-section li a { color: #e63312; font-weight: 600; word-break: break-word; }
        .ql-src-section li strong { background: #fff1ef; padding: 1px 5px; border-radius: 2px; color: #7a2010; font-size: .9em; }

        .ql-src-raw { margin-top: 1em; }
        .ql-src-raw summary { color: #5a5a5a; font-size: .88em; cursor: pointer; padding: 6px 0; }
        .ql-src-raw pre { background: #fafaf7; border: 1px solid #e8e5db; padding: 12px; border-radius: 4px; font-size: .82em; max-height: 400px; overflow: auto; white-space: pre-wrap; }

        .ql-src-foot { color: #5a5a5a; font-size: .85em; margin: 1em 0 0; padding-top: .8em; border-top: 1px solid #e8e5db; }
        .ql-src-foot code { background: #f1efe8; padding: 1px 4px; border-radius: 2px; }
    </style>';

    echo '<div class="ql-src-dash">';

    // 4 cases de résumé
    echo '<div class="ql-src-dash__cards">';
    echo '<div class="ql-src-card ql-src-card--ok">';
    echo '<div class="ql-src-card__num">' . (int) $v . '</div>';
    echo '<div class="ql-src-card__lbl">✓ Vérifié</div>';
    echo '<div class="ql-src-card__desc">sources avec URL précise</div>';
    echo '</div>';

    echo '<div class="ql-src-card ql-src-card--warn">';
    echo '<div class="ql-src-card__num">' . (int) $i . '</div>';
    echo '<div class="ql-src-card__lbl">⚠ Imprécis</div>';
    echo '<div class="ql-src-card__desc">landing page au lieu de source exacte</div>';
    echo '</div>';

    echo '<div class="ql-src-card ql-src-card--missing">';
    echo '<div class="ql-src-card__num">' . (int) $m . '</div>';
    echo '<div class="ql-src-card__lbl">✗ Manquant</div>';
    echo '<div class="ql-src-card__desc">aucune source / à retirer</div>';
    echo '</div>';

    echo '<div class="ql-src-card ql-src-card--check">';
    echo '<div class="ql-src-card__num">' . (int) $c . '</div>';
    echo '<div class="ql-src-card__lbl">👤 À valider</div>';
    echo '<div class="ql-src-card__desc">témoignages, points éditoriaux</div>';
    echo '</div>';
    echo '</div>';

    // Section VÉRIFIÉES (fermée par défaut)
    if ( $v > 0 ) {
        echo '<details class="ql-src-section ql-src-section--ok">';
        echo '<summary>✓ ' . (int) $v . ' affirmation' . ( $v > 1 ? 's' : '' ) . ' vérifiée' . ( $v > 1 ? 's' : '' ) . ' (clic pour détail)</summary>';
        echo '<ul>';
        foreach ( $parsed['verified'] as $item ) {
            echo '<li>' . $item . '</li>';
        }
        echo '</ul>';
        echo '</details>';
    }

    // Section IMPRÉCISES (ouverte par défaut si contenu)
    if ( $i > 0 ) {
        echo '<details class="ql-src-section ql-src-section--warn" open>';
        echo '<summary>⚠ ' . (int) $i . ' source' . ( $i > 1 ? 's' : '' ) . ' imprécise' . ( $i > 1 ? 's' : '' ) . ' — à corriger</summary>';
        echo '<ul>';
        foreach ( $parsed['imprecise'] as $item ) {
            echo '<li>' . $item . '</li>';
        }
        echo '</ul>';
        echo '</details>';
    }

    // Section MANQUANTES (ouverte par défaut si contenu)
    if ( $m > 0 ) {
        echo '<details class="ql-src-section ql-src-section--missing" open>';
        echo '<summary>✗ ' . (int) $m . ' affirmation' . ( $m > 1 ? 's' : '' ) . ' sans source — décision requise</summary>';
        echo '<ul>';
        foreach ( $parsed['missing'] as $item ) {
            echo '<li>' . $item . '</li>';
        }
        echo '</ul>';
        echo '</details>';
    }

    // Section À VALIDER par la rédaction (ouverte par défaut)
    // Éléments non web-vérifiables (témoignages anonymes, points politiques
    // éditoriaux, détails géographiques locaux) que Khalid doit confirmer.
    if ( $c > 0 ) {
        echo '<details class="ql-src-section ql-src-section--check" open>';
        echo '<summary>👤 ' . (int) $c . ' point' . ( $c > 1 ? 's' : '' ) . ' à valider par la rédaction</summary>';
        echo '<ul>';
        foreach ( $parsed['tocheck'] as $item ) {
            echo '<li>' . $item . '</li>';
        }
        echo '</ul>';
        echo '</details>';
    }

    // Si fiche existe mais aucune section standard trouvée → fallback affichage markdown brut
    if ( $total === 0 ) {
        echo '<div style="padding:14px;background:#fff8e1;border-left:4px solid #f2a000;border-radius:4px;margin:0 0 1em;">';
        echo '<p style="margin:0;color:#7a5c00;font-size:.92em;">';
        echo '<strong>⚠ Format ancien</strong> — cette fiche source n\'utilise pas encore la structure standard à 3 sections (✓ / ⚠ / ✗). Voir le rendu complet ci-dessous.';
        echo '</p></div>';
        echo '<div style="max-height:400px;overflow-y:auto;padding:1rem;background:#fafaf7;border:1px solid #e8e5db;border-radius:4px;font-size:.9em;">';
        echo ql_render_sources_md_to_html( $sources_md );
        echo '</div>';
    }

    // Détail brut expandable
    echo '<details class="ql-src-raw">';
    echo '<summary>📄 Voir le markdown brut de la fiche complète</summary>';
    echo '<pre>' . esc_html( $sources_md ) . '</pre>';
    echo '</details>';

    echo '<p class="ql-src-foot">';
    echo 'Fiche <code>content/sources/' . esc_html( $post->post_name ) . '.md</code> — pour modifier : édite le fichier sur GitHub puis <em>Sync QL → Synchroniser les articles</em>.';
    echo '</p>';

    echo '</div>'; // /.ql-src-dash
}

/**
 * Parse une fiche source pour en extraire les 3 sections :
 * ✓ Vérifiées, ⚠ Imprécises, ✗ Manquantes.
 *
 * Format attendu (structure standard inscrite dans CLAUDE.md) :
 *   ## ✓ Sources vérifiées
 *   - [Claim] → [Titre](url)
 *   ## ⚠ Sources imprécises
 *   - [Claim] — ...
 *   ## ✗ Affirmations sans source
 *   - [Claim] — ...
 *
 * Retourne un array : ['verified' => [...], 'imprecise' => [...], 'missing' => [...]]
 * Chaque item est le HTML rendu de la ligne (liens cliquables).
 */
function ql_parse_sources_sections( $md ) {
    $result = array(
        'verified'  => array(),
        'imprecise' => array(),
        'missing'   => array(),
        'tocheck'   => array(), // 👤 À valider humainement
    );
    if ( ! $md ) return $result;

    $md = str_replace( "\r\n", "\n", $md );
    $lines = explode( "\n", $md );

    $current = null;  // 'verified' | 'imprecise' | 'missing' | 'tocheck' | null
    foreach ( $lines as $line ) {
        $trimmed = trim( $line );

        // Détection des titres de section ## ... (h2 ou h3)
        if ( preg_match( '/^#{2,3}\s+(.+)$/', $trimmed, $m ) ) {
            $title = strtolower( $m[1] );
            if ( strpos( $title, '👤' ) !== false
              || strpos( $title, 'à valider' ) !== false
              || strpos( $title, 'a valider' ) !== false
              || strpos( $title, 'à confirmer' ) !== false
              || strpos( $title, 'points à surveiller' ) !== false
              || strpos( $title, 'points a surveiller' ) !== false
              || strpos( $title, 'points d\'attention' ) !== false
              || strpos( $title, 'rédaction' ) !== false ) {
                $current = 'tocheck';
            } elseif ( strpos( $title, '✓' ) !== false
              || strpos( $title, 'vérifi' ) !== false
              || strpos( $title, 'sourcé' ) !== false ) {
                $current = 'verified';
            } elseif ( strpos( $title, '⚠' ) !== false
                    || strpos( $title, 'imprécis' ) !== false
                    || strpos( $title, 'impreci' ) !== false
                    || strpos( $title, 'à vérifier' ) !== false
                    || strpos( $title, 'scénario' ) !== false
                    || strpos( $title, 'flagger' ) !== false ) {
                $current = 'imprecise';
            } elseif ( strpos( $title, '✗' ) !== false
                    || strpos( $title, 'manquant' ) !== false
                    || strpos( $title, 'sans source' ) !== false
                    || strpos( $title, 'inventé' ) !== false
                    || strpos( $title, 'fiction' ) !== false
                    || strpos( $title, 'à retirer' ) !== false
                    || strpos( $title, 'retirée' ) !== false ) {
                $current = 'missing';
            } else {
                // Section autre (ex. « Où vérifier » → on arrête le collect)
                $current = null;
            }
            continue;
        }

        // Collecte des items de liste
        if ( $current && preg_match( '/^[-*]\s+(.+)$/', $trimmed, $m ) ) {
            // Rendu inline basique : [text](url) → <a>, **bold**, `code`
            $item = $m[1];
            $item = preg_replace_callback( '/\[([^\]]+)\]\(([^\)]+)\)/', function ( $x ) {
                return '<a href="' . esc_url( $x[2] ) . '" target="_blank" rel="noopener">' . esc_html( $x[1] ) . '</a>';
            }, $item );
            $item = preg_replace( '/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $item );
            $item = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $item );
            $result[ $current ][] = $item;
        }
    }

    return $result;
}

// Rendu markdown simple pour les fiches sources — supporte titres,
// listes, liens, code inline, blockquote, tableaux GFM, hr.
function ql_render_sources_md_to_html( $md ) {
    if ( ! $md ) return '';
    $md = str_replace( "\r\n", "\n", $md );
    $lines = explode( "\n", $md );
    $out = array();
    $in_table = false; $in_list = false;

    $inline = function ( $s ) {
        // Liens [text](url)
        $s = preg_replace_callback( '/\[([^\]]+)\]\(([^\)]+)\)/', function ( $m ) {
            return '<a href="' . esc_url( $m[2] ) . '" target="_blank" rel="noopener">' . esc_html( $m[1] ) . '</a>';
        }, $s );
        // Gras **text**
        $s = preg_replace( '/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $s );
        // Code inline `code`
        $s = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $s );
        return $s;
    };

    foreach ( $lines as $line ) {
        $trimmed = trim( $line );

        // Table GFM : détection "| col1 | col2 |" avec ligne séparateur
        if ( strpos( $trimmed, '|' ) === 0 ) {
            if ( preg_match( '/^\|[\s\-:|]+\|$/', $trimmed ) ) { continue; } // séparateur
            $cells = array_map( 'trim', explode( '|', trim( $trimmed, '|' ) ) );
            if ( ! $in_table ) { $out[] = '<table>'; $in_table = true; $is_header = true; }
            else { $is_header = false; }
            $tag = $is_header && count( $out ) && strpos( end( $out ), '<table>' ) !== false ? 'th' : 'td';
            $row = '<tr>';
            foreach ( $cells as $c ) { $row .= '<' . $tag . '>' . $inline( $c ) . '</' . $tag . '>'; }
            $row .= '</tr>';
            $out[] = $row;
            continue;
        } elseif ( $in_table ) {
            $out[] = '</table>';
            $in_table = false;
        }

        if ( $trimmed === '' ) { if ( $in_list ) { $out[] = '</ul>'; $in_list = false; } continue; }
        if ( preg_match( '/^### (.+)$/', $trimmed, $m ) ) { if($in_list){$out[]='</ul>';$in_list=false;} $out[] = '<h3>' . $inline( $m[1] ) . '</h3>'; continue; }
        if ( preg_match( '/^## (.+)$/', $trimmed, $m ) )  { if($in_list){$out[]='</ul>';$in_list=false;} $out[] = '<h2>' . $inline( $m[1] ) . '</h2>'; continue; }
        if ( preg_match( '/^# (.+)$/', $trimmed, $m ) )   { if($in_list){$out[]='</ul>';$in_list=false;} $out[] = '<h1>' . $inline( $m[1] ) . '</h1>'; continue; }
        if ( preg_match( '/^---+$/', $trimmed ) )         { if($in_list){$out[]='</ul>';$in_list=false;} $out[] = '<hr>'; continue; }
        if ( preg_match( '/^> (.+)$/', $trimmed, $m ) )   { if($in_list){$out[]='</ul>';$in_list=false;} $out[] = '<blockquote>' . $inline( $m[1] ) . '</blockquote>'; continue; }
        if ( preg_match( '/^[-*]\s+(.+)$/', $trimmed, $m ) ) {
            if ( ! $in_list ) { $out[] = '<ul>'; $in_list = true; }
            $out[] = '<li>' . $inline( $m[1] ) . '</li>';
            continue;
        }
        if ( $in_list ) { $out[] = '</ul>'; $in_list = false; }
        $out[] = '<p>' . $inline( $trimmed ) . '</p>';
    }
    if ( $in_list )  { $out[] = '</ul>'; }
    if ( $in_table ) { $out[] = '</table>'; }
    return implode( "\n", $out );
}

// 2. Colonne « Sources » dans la liste des articles admin
add_filter( 'manage_post_posts_columns', function ( $cols ) {
    $new = array();
    foreach ( $cols as $k => $v ) {
        $new[ $k ] = $v;
        if ( $k === 'title' ) {
            $new['ql_sources'] = 'Sources';
        }
    }
    return $new;
} );
add_action( 'manage_post_posts_custom_column', function ( $col, $post_id ) {
    if ( $col !== 'ql_sources' ) return;
    $has = get_post_meta( $post_id, '_ql_sources_md', true );
    if ( $has ) {
        echo '<span title="Fiche sources disponible" style="color:#2a8a2a;font-size:1.2em;">✔</span>';
    } else {
        echo '<span title="Pas de fiche sources — vérifier avant publication" style="color:#c94d18;font-size:1.2em;">⚠</span>';
    }
}, 10, 2 );

// 3. Page admin « Outils → Sources QL » pour voir toutes les fiches d'un coup
add_action( 'admin_menu', function () {
    add_management_page(
        'Sources éditoriales QL',
        'Sources QL',
        'edit_posts',
        'ql-sources-index',
        'ql_render_sources_index'
    );
} );

function ql_render_sources_index() {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    // Agréger les scores ✓ / ⚠ / ✗ / 👤 pour chaque article
    $summary = array();
    $totals  = array( 'verified' => 0, 'imprecise' => 0, 'missing' => 0, 'tocheck' => 0, 'no_sheet' => 0, 'articles' => 0 );
    foreach ( $posts as $p ) {
        $md = get_post_meta( $p->ID, '_ql_sources_md', true );
        $parsed = $md ? ql_parse_sources_sections( $md ) : array( 'verified' => array(), 'imprecise' => array(), 'missing' => array(), 'tocheck' => array() );
        $has_sheet = ! empty( $md );
        $has_standard_format = $has_sheet && ( count( $parsed['verified'] ) + count( $parsed['imprecise'] ) + count( $parsed['missing'] ) + count( $parsed['tocheck'] ) > 0 );

        // Score global = 0 (rouge) si pas de fiche ou tout manquant
        //              1 (orange) si imprécisions ou fiche ancien format
        //              2 (vert) si 100% vérifié
        if ( ! $has_sheet ) {
            $score = 'red';
        } elseif ( ! $has_standard_format ) {
            $score = 'gray'; // fiche existe mais ancien format
        } elseif ( count( $parsed['missing'] ) > 0 ) {
            $score = 'red';
        } elseif ( count( $parsed['imprecise'] ) > 0 ) {
            $score = 'orange';
        } else {
            $score = 'green';
        }

        $summary[ $p->ID ] = array(
            'post'        => $p,
            'has_sheet'   => $has_sheet,
            'has_std'     => $has_standard_format,
            'verified'    => count( $parsed['verified'] ),
            'imprecise'   => count( $parsed['imprecise'] ),
            'missing'     => count( $parsed['missing'] ),
            'tocheck'     => count( $parsed['tocheck'] ),
            'parsed'      => $parsed, // conservé pour la vue détaillée
            'score'       => $score,
        );

        $totals['articles']++;
        if ( ! $has_sheet ) $totals['no_sheet']++;
        $totals['verified']  += count( $parsed['verified'] );
        $totals['imprecise'] += count( $parsed['imprecise'] );
        $totals['missing']   += count( $parsed['missing'] );
        $totals['tocheck']   += count( $parsed['tocheck'] );
    }

    // Stats globales pour le dashboard
    $articles_ok       = 0; // 100% vérifié
    $articles_partial  = 0; // imprécis ou ancien format
    $articles_problem  = 0; // manquant ou pas de fiche
    foreach ( $summary as $s ) {
        if ( $s['score'] === 'green' ) $articles_ok++;
        elseif ( in_array( $s['score'], array( 'orange', 'gray' ), true ) ) $articles_partial++;
        else $articles_problem++;
    }

    // Filtre par état (query param ?filter=ok|partial|problem|all|todo)
    $filter = isset( $_GET['filter'] ) ? sanitize_key( $_GET['filter'] ) : 'all';
    $view   = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'table';

    ?>
    <style>
        .qls-wrap h1 { margin-bottom: 1em; }
        .qls-dash { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 1em 0 1.5em; }
        .qls-card { padding: 18px 20px; border-radius: 6px; border: 2px solid; text-align: center; text-decoration: none; display: block; transition: transform .1s; }
        .qls-card:hover { transform: translateY(-2px); text-decoration: none; }
        .qls-card.is-active { box-shadow: 0 0 0 3px rgba(0,0,0,.15); }
        .qls-card--all { background: #f0f0f1; border-color: #8c8f94; color: #1d2327; }
        .qls-card--ok { background: #e8f5e8; border-color: #2a8a2a; color: #1a5f1a; }
        .qls-card--partial { background: #fff8e1; border-color: #f2a000; color: #7a5c00; }
        .qls-card--problem { background: #fff1ef; border-color: #e63312; color: #7a2010; }
        .qls-card__num { font-size: 2.4em; font-weight: 900; line-height: 1; font-family: serif; }
        .qls-card__lbl { font-size: .88em; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-top: 6px; }
        .qls-card__desc { font-size: .78em; margin-top: 3px; opacity: .85; }

        .qls-total { background: #fafaf7; border: 1px solid #e8e5db; border-radius: 6px; padding: 10px 16px; margin: 0 0 1.2em; display: flex; gap: 2em; align-items: center; font-size: .9em; }
        .qls-total strong { font-size: 1.15em; }
        .qls-total .sep { color: #d5d0c4; }

        .qls-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e8e5db; border-radius: 6px; overflow: hidden; }
        .qls-table th { background: #f1efe8; padding: 10px 12px; text-align: left; font-weight: 700; font-size: .88em; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid #e8e5db; }
        .qls-table td { padding: 12px; border-top: 1px solid #f1efe8; vertical-align: middle; }
        .qls-table tr:hover td { background: #fafaf7; }

        .qls-score { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: .82em; }
        .qls-score--green { background: #e8f5e8; color: #1a5f1a; border: 1px solid #2a8a2a; }
        .qls-score--orange { background: #fff8e1; color: #7a5c00; border: 1px solid #f2a000; }
        .qls-score--red { background: #fff1ef; color: #7a2010; border: 1px solid #e63312; }
        .qls-score--gray { background: #f0f0f1; color: #5a5a5a; border: 1px solid #8c8f94; }

        .qls-counts { font-family: monospace; font-size: .88em; color: #5a5a5a; letter-spacing: .5px; white-space: nowrap; }
        .qls-counts .v { color: #2a8a2a; font-weight: 700; }
        .qls-counts .w { color: #f2a000; font-weight: 700; }
        .qls-counts .m { color: #e63312; font-weight: 700; }

        .qls-status { font-family: monospace; font-size: .75em; padding: 2px 8px; border-radius: 3px; background: #f0f0f1; color: #5a5a5a; text-transform: uppercase; }
        .qls-status.is-publish { background: #e8f5e8; color: #1a5f1a; }
        .qls-status.is-pending { background: #fff8e1; color: #7a5c00; }
    </style>

    <div class="wrap qls-wrap">
        <h1>📋 Sources éditoriales — Tableau de bord</h1>
        <p style="max-width:820px;color:#5a5a5a;">
            Pour chaque article, le nombre d'affirmations <strong>vérifiées</strong> (URL précise),
            <strong>imprécises</strong> (lien trop général), <strong>sans source</strong>,
            et <strong>à valider humainement</strong> (témoignages, points politiques).
            Objectif : <strong>0 rouge, 0 orange</strong> avant publication. Les points 👤 bleus sont à faire valider par la rédaction.
        </p>

        <p style="margin:1em 0;">
            <a class="button <?php echo $view === 'table' ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'tools.php?page=ql-sources-index' ) ); ?>">📊 Tableau</a>
            <a class="button <?php echo $view === 'todo' ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'view', 'todo', admin_url( 'tools.php?page=ql-sources-index' ) ) ); ?>">📝 Tout ce qui reste à faire</a>
        </p>

        <?php if ( $view === 'todo' ) : ?>
            <!-- Vue TODO consolidée : tous les points à surveiller, article par article -->
            <style>
                .qls-todo { background: #fff; border: 1px solid #e8e5db; border-radius: 6px; padding: 1.4em; }
                .qls-todo h2 { margin: 0 0 1em; font-size: 1.2em; padding-bottom: .5em; border-bottom: 2px solid #e63312; }
                .qls-todo-art { margin: 0 0 1.6em; padding: 0 0 1.2em; border-bottom: 1px solid #f1efe8; }
                .qls-todo-art:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
                .qls-todo-art__title { font-size: 1.02em; font-weight: 700; margin: 0 0 .4em; }
                .qls-todo-art__title a { color: #0f0f0f; text-decoration: none; }
                .qls-todo-art__title a:hover { color: #e63312; }
                .qls-todo-art__meta { font-size: .82em; color: #5a5a5a; margin: 0 0 .8em; }
                .qls-todo-group { margin: .6em 0 0; padding: .5em .8em .5em 2em; border-radius: 4px; }
                .qls-todo-group--warn { background: #fff8e1; border-left: 3px solid #f2a000; }
                .qls-todo-group--missing { background: #fff1ef; border-left: 3px solid #e63312; }
                .qls-todo-group--check { background: #e6f2ff; border-left: 3px solid #0d5ba8; }
                .qls-todo-group__title { margin: 0 -.6em .3em -1.4em; padding-left: 1em; font-weight: 700; font-size: .92em; }
                .qls-todo-group ul { margin: 0; padding-left: 1.2em; font-size: .92em; }
                .qls-todo-group li { margin-bottom: .25em; line-height: 1.5; }
                .qls-todo-group li a { color: #e63312; font-weight: 600; }
                .qls-empty-note { padding: 2em; text-align: center; color: #5a5a5a; background: #e8f5e8; border-radius: 6px; }
            </style>
            <div class="qls-todo">
                <h2>📝 Tous les points à traiter — par article</h2>
                <?php
                $has_anything = false;
                foreach ( $summary as $id => $s ) {
                    $p = $s['post'];
                    $i_cnt = $s['imprecise'];
                    $m_cnt = $s['missing'];
                    $c_cnt = $s['tocheck'];
                    if ( $i_cnt === 0 && $m_cnt === 0 && $c_cnt === 0 ) continue;
                    $has_anything = true;

                    echo '<div class="qls-todo-art">';
                    echo '<p class="qls-todo-art__title"><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a></p>';
                    echo '<p class="qls-todo-art__meta">' . esc_html( $p->post_status ) . ' · ' . esc_html( get_the_date( 'd/m/Y', $p ) ) . '</p>';

                    if ( $m_cnt > 0 ) {
                        echo '<div class="qls-todo-group qls-todo-group--missing">';
                        echo '<p class="qls-todo-group__title">✗ ' . (int) $m_cnt . ' affirmation' . ( $m_cnt > 1 ? 's' : '' ) . ' sans source — décision requise</p>';
                        echo '<ul>';
                        foreach ( $s['parsed']['missing'] as $item ) echo '<li>' . $item . '</li>';
                        echo '</ul></div>';
                    }
                    if ( $i_cnt > 0 ) {
                        echo '<div class="qls-todo-group qls-todo-group--warn">';
                        echo '<p class="qls-todo-group__title">⚠ ' . (int) $i_cnt . ' source' . ( $i_cnt > 1 ? 's' : '' ) . ' imprécise' . ( $i_cnt > 1 ? 's' : '' ) . ' — à corriger</p>';
                        echo '<ul>';
                        foreach ( $s['parsed']['imprecise'] as $item ) echo '<li>' . $item . '</li>';
                        echo '</ul></div>';
                    }
                    if ( $c_cnt > 0 ) {
                        echo '<div class="qls-todo-group qls-todo-group--check">';
                        echo '<p class="qls-todo-group__title">👤 ' . (int) $c_cnt . ' point' . ( $c_cnt > 1 ? 's' : '' ) . ' à valider par la rédaction</p>';
                        echo '<ul>';
                        foreach ( $s['parsed']['tocheck'] as $item ) echo '<li>' . $item . '</li>';
                        echo '</ul></div>';
                    }
                    echo '</div>';
                }
                if ( ! $has_anything ) {
                    echo '<p class="qls-empty-note">🎉 <strong>Rien à traiter.</strong> Tous les articles sont 100 % vérifiés.</p>';
                }
                ?>
            </div>
        </div>
        <?php return; // on s'arrête là pour la vue todo
        endif; ?>

        <!-- 4 cards filtres -->
        <div class="qls-dash">
            <a href="<?php echo esc_url( admin_url( 'tools.php?page=ql-sources-index' ) ); ?>" class="qls-card qls-card--all <?php echo $filter === 'all' ? 'is-active' : ''; ?>">
                <div class="qls-card__num"><?php echo (int) $totals['articles']; ?></div>
                <div class="qls-card__lbl">Total articles</div>
                <div class="qls-card__desc">tous statuts confondus</div>
            </a>
            <a href="<?php echo esc_url( add_query_arg( 'filter', 'ok', admin_url( 'tools.php?page=ql-sources-index' ) ) ); ?>" class="qls-card qls-card--ok <?php echo $filter === 'ok' ? 'is-active' : ''; ?>">
                <div class="qls-card__num"><?php echo (int) $articles_ok; ?></div>
                <div class="qls-card__lbl">✓ 100 % vérifiés</div>
                <div class="qls-card__desc">prêts à publier</div>
            </a>
            <a href="<?php echo esc_url( add_query_arg( 'filter', 'partial', admin_url( 'tools.php?page=ql-sources-index' ) ) ); ?>" class="qls-card qls-card--partial <?php echo $filter === 'partial' ? 'is-active' : ''; ?>">
                <div class="qls-card__num"><?php echo (int) $articles_partial; ?></div>
                <div class="qls-card__lbl">⚠ À corriger</div>
                <div class="qls-card__desc">liens imprécis ou ancien format</div>
            </a>
            <a href="<?php echo esc_url( add_query_arg( 'filter', 'problem', admin_url( 'tools.php?page=ql-sources-index' ) ) ); ?>" class="qls-card qls-card--problem <?php echo $filter === 'problem' ? 'is-active' : ''; ?>">
                <div class="qls-card__num"><?php echo (int) $articles_problem; ?></div>
                <div class="qls-card__lbl">✗ Problème</div>
                <div class="qls-card__desc">pas de fiche ou source manquante</div>
            </a>
        </div>

        <!-- Totaux affirmations -->
        <div class="qls-total">
            <span>Total des affirmations auditées :</span>
            <span class="sep">·</span>
            <span><span class="qls-counts"><span class="v">✓ <?php echo (int) $totals['verified']; ?></span></span> vérifiées</span>
            <span class="sep">·</span>
            <span><span class="qls-counts"><span class="w">⚠ <?php echo (int) $totals['imprecise']; ?></span></span> imprécises</span>
            <span class="sep">·</span>
            <span><span class="qls-counts"><span class="m">✗ <?php echo (int) $totals['missing']; ?></span></span> manquantes</span>
            <?php if ( $totals['tocheck'] > 0 ) : ?>
                <span class="sep">·</span>
                <span style="color:#0d5ba8;font-weight:700;">👤 <?php echo (int) $totals['tocheck']; ?></span>
                <span style="color:#0d5ba8;">à valider par la rédaction</span>
            <?php endif; ?>
            <?php if ( $totals['no_sheet'] > 0 ) : ?>
                <span class="sep">·</span>
                <span style="color:#e63312;font-weight:700;"><?php echo (int) $totals['no_sheet']; ?> article<?php echo $totals['no_sheet']>1?'s':''; ?> sans fiche</span>
            <?php endif; ?>
        </div>

        <!-- Tableau -->
        <table class="qls-table">
            <thead>
                <tr>
                    <th style="width:100px;">Verdict</th>
                    <th>Article</th>
                    <th style="width:180px;">Détail</th>
                    <th style="width:90px;">Statut</th>
                    <th style="width:100px;">Date</th>
                    <th style="width:90px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $summary as $id => $s ) :
                // Filtre
                if ( $filter === 'ok'      && $s['score'] !== 'green' ) continue;
                if ( $filter === 'partial' && ! in_array( $s['score'], array( 'orange', 'gray' ), true ) ) continue;
                if ( $filter === 'problem' && $s['score'] !== 'red' ) continue;

                $p = $s['post'];
                $label = array(
                    'green'  => 'Vérifié',
                    'orange' => 'À corriger',
                    'red'    => $s['has_sheet'] ? 'Source manquante' : 'Sans fiche',
                    'gray'   => 'Ancien format',
                );
                ?>
                <tr>
                    <td><span class="qls-score qls-score--<?php echo esc_attr( $s['score'] ); ?>"><?php echo esc_html( $label[ $s['score'] ] ); ?></span></td>
                    <td><strong><?php echo esc_html( $p->post_title ); ?></strong></td>
                    <td>
                        <?php if ( $s['has_sheet'] && $s['has_std'] ) : ?>
                            <span class="qls-counts">
                                <span class="v">✓<?php echo (int) $s['verified']; ?></span>
                                <span class="w">⚠<?php echo (int) $s['imprecise']; ?></span>
                                <span class="m">✗<?php echo (int) $s['missing']; ?></span>
                                <?php if ( $s['tocheck'] > 0 ) : ?>
                                    <span style="color:#0d5ba8;font-weight:700;">👤<?php echo (int) $s['tocheck']; ?></span>
                                <?php endif; ?>
                            </span>
                        <?php elseif ( $s['has_sheet'] ) : ?>
                            <em style="color:#5a5a5a;font-size:.85em;">fiche ancien format</em>
                        <?php else : ?>
                            <em style="color:#e63312;font-size:.85em;">pas de fiche</em>
                        <?php endif; ?>
                    </td>
                    <td><span class="qls-status <?php echo esc_attr( 'is-' . $p->post_status ); ?>"><?php echo esc_html( $p->post_status ); ?></span></td>
                    <td><?php echo esc_html( get_the_date( 'd/m/Y', $p ) ); ?></td>
                    <td><a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">Éditer</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
