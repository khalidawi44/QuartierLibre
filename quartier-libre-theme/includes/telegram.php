<?php
/**
 * Quartier Libre — Intégration Telegram
 *
 * Fonctionnalités :
 *   1. Publication automatique des nouveaux articles sur le canal Telegram
 *   2. Bouton « Rejoins-nous sur Telegram » (rendu via ql_telegram_button())
 *   3. Notification de la rédaction quand un témoignage (Bureau des plaintes) arrive
 *
 * Réglages : Réglages → Telegram QL (page admin ci-dessous).
 * Prérequis : créer un bot via @BotFather (token) et l'ajouter comme
 * administrateur du canal pour qu'il puisse y publier.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ════════════════════════════════════════════════════════════════
//  HELPERS D'ENVOI (Bot API)
// ════════════════════════════════════════════════════════════════

function ql_telegram_token() {
    return trim( (string) get_option( 'ql_telegram_bot_token', '' ) );
}

/**
 * Appel générique à l'API Telegram. Retourne le tableau décodé ou false.
 */
function ql_telegram_api( $method, $params ) {
    $token = ql_telegram_token();
    if ( $token === '' ) { return false; }

    $resp = wp_remote_post( 'https://api.telegram.org/bot' . $token . '/' . $method, array(
        'timeout' => 15,
        'body'    => $params,
    ) );
    if ( is_wp_error( $resp ) ) { return false; }

    $data = json_decode( wp_remote_retrieve_body( $resp ), true );
    return is_array( $data ) ? $data : false;
}

function ql_telegram_send_message( $chat_id, $text, $opts = array() ) {
    if ( ! $chat_id ) { return false; }
    $params = array_merge( array(
        'chat_id'                  => $chat_id,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => false,
    ), $opts );
    return ql_telegram_api( 'sendMessage', $params );
}

function ql_telegram_send_photo( $chat_id, $photo_url, $caption, $opts = array() ) {
    if ( ! $chat_id || ! $photo_url ) { return false; }
    $params = array_merge( array(
        'chat_id'    => $chat_id,
        'photo'      => $photo_url,
        'caption'    => $caption,
        'parse_mode' => 'HTML',
    ), $opts );
    return ql_telegram_api( 'sendPhoto', $params );
}

/**
 * Nombre d'abonnés du canal (cache 1 h). Renvoie un int, ou null si
 * indisponible (token/canal non configurés ou bot non admin).
 */
function ql_telegram_subscriber_count( $force = false ) {
    $channel = trim( (string) get_option( 'ql_telegram_channel_id', '' ) );
    if ( $channel === '' || ql_telegram_token() === '' ) { return null; }

    $cached = get_transient( 'ql_tg_sub_count' );
    if ( ! $force && $cached !== false ) { return (int) $cached; }

    $res = ql_telegram_api( 'getChatMemberCount', array( 'chat_id' => $channel ) );
    if ( is_array( $res ) && ! empty( $res['ok'] ) && isset( $res['result'] ) ) {
        $n = (int) $res['result'];
        set_transient( 'ql_tg_sub_count', $n, HOUR_IN_SECONDS );
        return $n;
    }
    return ( $cached !== false ) ? (int) $cached : null;
}

/**
 * Liste les conversations récentes vues par le bot (via getUpdates), pour
 * récupérer facilement l'ID d'un canal ou d'un groupe (ex. « Bureau des
 * plaintes »). Le bot ne voit une conversation qu'après y avoir reçu un
 * message (poste un message dans le groupe / fais /start, puis détecte).
 * Retourne array de array( id, name, type ).
 */
function ql_telegram_recent_chats() {
    $res = ql_telegram_api( 'getUpdates', array( 'limit' => 100 ) );
    $chats = array();
    if ( is_array( $res ) && ! empty( $res['ok'] ) && ! empty( $res['result'] ) ) {
        foreach ( $res['result'] as $upd ) {
            foreach ( array( 'message', 'edited_message', 'channel_post', 'my_chat_member' ) as $k ) {
                if ( empty( $upd[ $k ]['chat'] ) ) { continue; }
                $c  = $upd[ $k ]['chat'];
                $id = isset( $c['id'] ) ? $c['id'] : null;
                if ( $id === null ) { continue; }
                $name = ! empty( $c['title'] )
                    ? $c['title']
                    : trim( ( $c['first_name'] ?? '' ) . ' ' . ( $c['last_name'] ?? '' ) );
                if ( $name === '' ) { $name = $c['username'] ?? ( '#' . $id ); }
                $chats[ (string) $id ] = array(
                    'id'   => $id,
                    'name' => $name,
                    'type' => $c['type'] ?? '',
                );
            }
        }
    }
    return array_values( $chats );
}

// ════════════════════════════════════════════════════════════════
//  PONT TELEGRAM → SITE (webhook) : enregistre les messages du
//  groupe « Bureau des plaintes » comme plaintes côté admin.
// ════════════════════════════════════════════════════════════════

function ql_telegram_webhook_secret() {
    $s = (string) get_option( 'ql_telegram_webhook_secret', '' );
    if ( $s === '' ) {
        $s = wp_generate_password( 32, false );
        update_option( 'ql_telegram_webhook_secret', $s, false );
    }
    return $s;
}

function ql_telegram_webhook_url() {
    return rest_url( 'quartierlibre/v1/telegram-webhook' );
}

function ql_telegram_set_webhook() {
    return ql_telegram_api( 'setWebhook', array(
        'url'             => ql_telegram_webhook_url(),
        'secret_token'    => ql_telegram_webhook_secret(),
        'allowed_updates' => wp_json_encode( array( 'message', 'edited_message' ) ),
    ) );
}

function ql_telegram_delete_webhook() {
    return ql_telegram_api( 'deleteWebhook', array() );
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'quartierlibre/v1', '/telegram-webhook', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'ql_telegram_webhook_handler',
    ) );
} );

function ql_telegram_webhook_handler( WP_REST_Request $req ) {
    // Vérifie le jeton secret envoyé par Telegram
    $secret = (string) $req->get_header( 'x-telegram-bot-api-secret-token' );
    if ( ! hash_equals( ql_telegram_webhook_secret(), $secret ) ) {
        return new WP_REST_Response( array( 'ok' => false ), 403 );
    }

    if ( get_option( 'ql_telegram_log_group', '0' ) !== '1' ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    $upd = $req->get_json_params();
    $msg = $upd['message'] ?? $upd['edited_message'] ?? null;
    if ( ! is_array( $msg ) ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    // Ignore les messages du bot lui-même (évite la boucle avec nos notifs)
    if ( ! empty( $msg['from']['is_bot'] ) ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    // Ignore les ARTICLES recopiés automatiquement depuis le canal vers le
    // groupe de discussion lié (sinon chaque article publié serait enregistré
    // comme une « plainte »). On exclut : recopie auto du canal, message posté
    // au nom d'un canal, message transféré depuis un canal, et le compte
    // technique Telegram (777000).
    if ( ! empty( $msg['is_automatic_forward'] )
      || ! empty( $msg['sender_chat'] )
      || ! empty( $msg['forward_from_chat'] )
      || ( isset( $msg['from']['id'] ) && (int) $msg['from']['id'] === 777000 ) ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    // Uniquement le groupe « plaintes » configuré
    $group   = trim( (string) get_option( 'ql_telegram_admin_chat_id', '' ) );
    $chat_id = isset( $msg['chat']['id'] ) ? (string) $msg['chat']['id'] : '';
    if ( $group === '' || $chat_id === '' || $chat_id !== $group ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    $text = trim( (string) ( $msg['text'] ?? $msg['caption'] ?? '' ) );
    if ( $text === '' ) {
        return new WP_REST_Response( array( 'ok' => true ), 200 );
    }

    $from = trim( ( $msg['from']['first_name'] ?? '' ) . ' ' . ( $msg['from']['last_name'] ?? '' ) );
    if ( $from === '' ) { $from = $msg['from']['username'] ?? 'Telegram'; }

    if ( function_exists( 'ql_plainte_store_entry' ) ) {
        ql_plainte_store_entry( array(
            'source'  => 'telegram',
            'message' => $text,
            'tg_from' => $from,
            'tg_chat' => $chat_id,
        ) );
    }
    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

// ════════════════════════════════════════════════════════════════
//  1. PUBLICATION AUTO DES ARTICLES (canal)
// ════════════════════════════════════════════════════════════════

/**
 * Vrai si l'URL pointe vers une image que Telegram accepte comme photo.
 * Telegram refuse les SVG : ils sont donc exclus.
 */
function ql_telegram_is_raster_image( $url ) {
    return (bool) preg_match( '/\.(jpe?g|png|webp|gif)(\?|#|$)/i', (string) $url );
}

/**
 * Convertit automatiquement un SVG (image à la une) en JPG partageable sur
 * Telegram, mis en cache dans /uploads (régénéré seulement si le SVG change).
 * Utilise Imagick si le serveur sait lire le SVG ; sinon renvoie '' (repli).
 * Entièrement protégé : ne casse jamais, au pire renvoie ''.
 */
function ql_svg_to_raster_url( $svg_url ) {
    if ( ! preg_match( '/\.svg(\?|#|$)/i', (string) $svg_url ) ) { return ''; }
    if ( ! class_exists( 'Imagick' ) ) { return ''; }
    try {
        if ( ! Imagick::queryFormats( 'SVG' ) ) { return ''; }
    } catch ( \Throwable $e ) { return ''; }

    // URL /uploads → chemin disque
    $uploads = wp_upload_dir();
    $clean   = preg_replace( '/[?#].*$/', '', $svg_url );
    if ( strpos( $clean, $uploads['baseurl'] ) !== 0 ) { return ''; }
    $svg_path = $uploads['basedir'] . substr( $clean, strlen( $uploads['baseurl'] ) );
    if ( ! is_readable( $svg_path ) ) { return ''; }

    $jpg_path = preg_replace( '/\.svg$/i', '-tg.jpg', $svg_path );
    $jpg_url  = preg_replace( '/\.svg$/i', '-tg.jpg', $clean );

    // Cache valide si le JPG est plus récent que le SVG source
    if ( file_exists( $jpg_path ) && filemtime( $jpg_path ) >= filemtime( $svg_path ) ) {
        return $jpg_url;
    }

    try {
        $im = new Imagick();
        $im->setBackgroundColor( new ImagickPixel( '#111111' ) );
        $im->readImage( $svg_path );
        $im->setImageFormat( 'jpeg' );
        $im->setImageCompressionQuality( 86 );
        if ( $im->getImageWidth() > 1280 ) {
            $im->resizeImage( 1280, 0, Imagick::FILTER_LANCZOS, 1 );
        }
        $im->writeImage( $jpg_path );
        $im->clear();
        $im->destroy();
    } catch ( \Throwable $e ) {
        return '';
    }
    return file_exists( $jpg_path ) ? $jpg_url : '';
}

/**
 * Meilleure image RASTER de l'article pour Telegram (qui ne gère pas le SVG).
 * Ordre : image à la une (si raster) → 1re image du contenu → 1re pièce
 * jointe image → '' (aucune → envoi en texte).
 */
function ql_telegram_article_image_url( $post ) {
    // 1. Image à la une (grande taille pour la qualité)
    $thumb = get_the_post_thumbnail_url( $post, 'large' );
    if ( ql_telegram_is_raster_image( $thumb ) ) { return $thumb; }
    // 1bis. Vignette SVG → conversion auto en JPG (Telegram ne gère pas le SVG)
    if ( $thumb ) {
        $raster = ql_svg_to_raster_url( $thumb );
        if ( $raster !== '' ) { return $raster; }
    }

    // 2. Première image raster dans le contenu
    if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $post->post_content, $m ) ) {
        foreach ( $m[1] as $src ) {
            if ( ql_telegram_is_raster_image( $src ) ) { return $src; }
        }
    }

    // 3. Première pièce jointe image raster
    foreach ( get_attached_media( 'image', $post->ID ) as $att ) {
        $u = wp_get_attachment_url( $att->ID );
        if ( ql_telegram_is_raster_image( $u ) ) { return $u; }
    }
    return '';
}

/**
 * Construit le message Telegram d'un article : image + légende HTML + clavier.
 * Le « bandeau » du bas = un bouton inline cliquable « Lire l'article ».
 */
function ql_telegram_build_article_payload( $post ) {
    $title   = get_the_title( $post );
    $url     = get_permalink( $post );
    $excerpt = has_excerpt( $post )
        ? get_the_excerpt( $post )
        : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 32, '…' );

    $caption = '📰 <b>' . esc_html( $title ) . "</b>\n\n"
             . esc_html( $excerpt ) . "\n\n"
             . "➖➖➖➖➖➖➖➖➖➖\n"
             . '🔴 <b>Quartier Libre</b> — média des quartiers populaires de Nantes';

    // Bouton « bandeau » en gros, en bas du message
    $rows = array(
        array( array( 'text' => '📖 Lire l’article', 'url' => $url ) ),
    );
    $chan = ql_telegram_public_url();
    if ( $chan !== '' ) {
        $rows[] = array( array( 'text' => '📣 Rejoindre Quartier Libre', 'url' => $chan ) );
    }

    return array(
        'image'   => ql_telegram_article_image_url( $post ),
        'caption' => $caption,
        'url'     => $url,
        'markup'  => wp_json_encode( array( 'inline_keyboard' => $rows ) ),
    );
}

/**
 * Publie (ou republie) un article sur le canal Telegram.
 * Photo + légende + bouton si une image raster existe ; sinon repli en
 * message texte (avec aperçu du lien). Si Telegram refuse la photo, repli
 * texte automatique. Retourne array('ok'=>bool, 'mode'=>…, 'error'=>…).
 */
function ql_telegram_post_article( $post, $force = false ) {
    if ( ql_telegram_token() === '' ) {
        return array( 'ok' => false, 'error' => 'token absent' );
    }
    $channel = trim( (string) get_option( 'ql_telegram_channel_id', '' ) );
    if ( $channel === '' ) {
        return array( 'ok' => false, 'error' => 'canal non configuré' );
    }
    if ( ! $force && get_post_meta( $post->ID, '_ql_telegram_sent', true ) ) {
        return array( 'ok' => false, 'error' => 'déjà envoyé' );
    }

    $p    = ql_telegram_build_article_payload( $post );
    $opts = array( 'reply_markup' => $p['markup'] );

    if ( $p['image'] !== '' ) {
        $res  = ql_telegram_send_photo( $channel, $p['image'], $p['caption'], $opts );
        $mode = 'photo + bouton';
        // Repli : Telegram a refusé l'image → on envoie en texte avec le lien
        if ( ! ( is_array( $res ) && ! empty( $res['ok'] ) ) ) {
            $res  = ql_telegram_send_message( $channel, $p['caption'] . "\n\n" . $p['url'], $opts );
            $mode = 'texte (image refusée)';
        }
    } else {
        $res  = ql_telegram_send_message( $channel, $p['caption'] . "\n\n" . $p['url'], $opts );
        $mode = 'texte (pas d’image raster)';
    }

    if ( is_array( $res ) && ! empty( $res['ok'] ) ) {
        update_post_meta( $post->ID, '_ql_telegram_sent', time() );
        return array( 'ok' => true, 'mode' => $mode );
    }
    $desc = is_array( $res ) && ! empty( $res['description'] ) ? $res['description'] : 'erreur inconnue (token ? bot admin du canal ?)';
    return array( 'ok' => false, 'error' => $desc, 'mode' => $mode );
}

add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
    // Uniquement quand un article passe RÉELLEMENT en ligne
    if ( $new_status !== 'publish' || $old_status === 'publish' ) { return; }
    if ( ! $post || $post->post_type !== 'post' ) { return; }
    if ( get_option( 'ql_telegram_autopost', '1' ) !== '1' ) { return; }
    ql_telegram_post_article( $post );
}, 20, 3 );

// ── Metabox « Partage Telegram » sur l'écran d'édition d'un article ──
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'ql_tg_share', '📣 Partage Telegram', 'ql_telegram_metabox_render', 'post', 'side', 'default' );
} );

function ql_telegram_metabox_render( $post ) {
    $has_token   = ql_telegram_token() !== '';
    $has_channel = trim( (string) get_option( 'ql_telegram_channel_id', '' ) ) !== '';
    $autopost    = get_option( 'ql_telegram_autopost', '1' ) === '1';
    $sent        = get_post_meta( $post->ID, '_ql_telegram_sent', true );
    $thumb       = get_the_post_thumbnail_url( $post, 'large' );
    $is_svg      = $thumb && preg_match( '/\.svg(\?|#|$)/i', $thumb );
    $raster      = ql_telegram_article_image_url( $post );

    echo '<div style="line-height:1.7;font-size:13px;">';

    if ( ! $has_token || ! $has_channel ) {
        echo '<p style="color:#b32d2e;"><strong>⚠ Non configuré.</strong> Renseigne le token + le canal dans <a href="'
           . esc_url( admin_url( 'options-general.php?page=ql-telegram' ) ) . '">Réglages → Telegram QL</a>.</p>';
    } else {
        echo '<p>✅ Bot + canal configurés.</p>';
        echo '<p>Partage auto à la publication : <strong>' . ( $autopost ? 'activé' : 'désactivé' ) . '</strong>.</p>';
    }

    echo $sent
        ? '<p>🟢 Déjà partagé le <strong>' . esc_html( date_i18n( 'j M Y \à H:i', (int) $sent ) ) . '</strong>.</p>'
        : '<p>⚪ Pas encore partagé.</p>';

    if ( $is_svg ) {
        echo '<p style="color:#996800;">ℹ Image à la une = <strong>SVG</strong> (refusé par Telegram). '
           . ( $raster ? 'Une image raster sera utilisée pour la photo.' : '<strong>Aucune image raster trouvée</strong> → envoi en texte. Mets une image à la une .jpg/.png pour une vraie photo.' )
           . '</p>';
    }

    if ( $has_token && $has_channel ) {
        $nonce = wp_create_nonce( 'ql_tg_share_' . $post->ID );
        $url   = admin_url( 'admin-post.php?action=ql_tg_share&post=' . $post->ID . '&_wpnonce=' . $nonce );
        $label = $sent ? '↻ Renvoyer sur Telegram' : '📣 Partager sur Telegram';
        echo '<p style="margin-top:12px;"><a href="' . esc_url( $url ) . '" class="button button-primary button-large" style="width:100%;text-align:center;">' . esc_html( $label ) . '</a></p>';
        if ( $post->post_status !== 'publish' ) {
            echo '<p style="color:#996800;">Publie l’article d’abord (lien public valide).</p>';
        }
    }
    echo '</div>';
}

add_action( 'admin_post_ql_tg_share', function () {
    $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) { wp_die( 'Non autorisé.' ); }
    check_admin_referer( 'ql_tg_share_' . $post_id );

    $post = get_post( $post_id );
    $res  = $post ? ql_telegram_post_article( $post, true ) : array( 'ok' => false, 'error' => 'article introuvable' );

    $back = add_query_arg( array(
        'ql_tg_shared' => $res['ok'] ? '1' : '0',
        'ql_tg_msg'    => rawurlencode( $res['ok'] ? ( $res['mode'] ?? '' ) : ( $res['error'] ?? '' ) ),
    ), get_edit_post_link( $post_id, 'raw' ) );
    wp_safe_redirect( $back );
    exit;
} );

add_action( 'admin_notices', function () {
    if ( ! isset( $_GET['ql_tg_shared'] ) ) { return; }
    $msg = isset( $_GET['ql_tg_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['ql_tg_msg'] ) ) : '';
    if ( $_GET['ql_tg_shared'] === '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>📣 Article partagé sur Telegram' . ( $msg ? ' (' . esc_html( $msg ) . ')' : '' ) . '.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Échec du partage Telegram — ' . esc_html( $msg ?: 'erreur inconnue' ) . '</p></div>';
    }
} );

// ════════════════════════════════════════════════════════════════
//  2. BOUTON « REJOINS-NOUS SUR TELEGRAM »
// ════════════════════════════════════════════════════════════════

function ql_telegram_channel_url() {
    return trim( (string) get_option( 'ql_telegram_channel_url', '' ) );
}

/**
 * Meilleure URL publique du canal pour les liens du site (widget, bouton).
 * Priorité : « Lien public du canal » → dérivé du @nom du canal → ''.
 */
function ql_telegram_public_url() {
    $url = ql_telegram_channel_url();
    if ( $url !== '' ) { return $url; }

    $id = trim( (string) get_option( 'ql_telegram_channel_id', '' ) );
    if ( $id !== '' && $id[0] === '@' ) {
        return 'https://t.me/' . ltrim( $id, '@' );
    }
    return '';
}

/**
 * Renvoie le HTML du bouton, ou '' si aucune URL de canal n'est configurée.
 */
function ql_telegram_button( $label = 'Rejoins-nous sur Telegram', $class = '' ) {
    $url = ql_telegram_channel_url();
    if ( $url === '' ) { return ''; }

    $svg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
         . '<path d="M21.94 4.66a1.5 1.5 0 0 0-1.6-.23L3.3 11.6c-1.16.47-1.13 2.13.05 2.55l4.13 1.46 1.6 5.04c.2.63 1 .82 1.47.34l2.3-2.34 4.2 3.08c.6.44 1.46.12 1.62-.6l3.2-15a1.5 1.5 0 0 0-.53-1.47zM9.7 14.3l8.2-5.06-6.77 6.1c-.16.15-.27.35-.3.57l-.24 1.95z"/>'
         . '</svg>';

    return '<a class="ql-tg-btn ' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">'
         . $svg . '<span>' . esc_html( $label ) . '</span></a>';
}

// ════════════════════════════════════════════════════════════════
//  3. NOTIFICATION RÉDACTION (Bureau des plaintes)
// ════════════════════════════════════════════════════════════════

add_action( 'ql_plainte_received', function ( $d ) {
    if ( get_option( 'ql_telegram_notify_plaintes', '1' ) !== '1' ) { return; }
    if ( ql_telegram_token() === '' ) { return; }

    $admin_chat = trim( (string) get_option( 'ql_telegram_admin_chat_id', '' ) );
    if ( $admin_chat === '' ) { return; }

    $text = "🚨 <b>Nouveau témoignage — Bureau des plaintes</b>\n\n"
          . '<b>Type :</b> '     . esc_html( $d['type']     ?? '—' ) . "\n"
          . '<b>Quartier :</b> ' . esc_html( $d['quartier'] ?: '—' ) . "\n"
          . '<b>Nom :</b> '      . esc_html( $d['nom']      ?: 'Anonyme' ) . "\n"
          . '<b>Email :</b> '    . esc_html( $d['email']    ?: '—' ) . "\n\n"
          . esc_html( $d['message'] ?? '' );

    ql_telegram_send_message( $admin_chat, $text );
} );

// ════════════════════════════════════════════════════════════════
//  PAGE DE RÉGLAGES — Réglages → Telegram QL
// ════════════════════════════════════════════════════════════════

add_action( 'admin_menu', function () {
    add_options_page(
        'Telegram Quartier Libre',
        'Telegram QL',
        'manage_options',
        'ql-telegram',
        'ql_telegram_settings_render'
    );
} );

function ql_telegram_settings_render() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Non.' ); }

    // Sauvegarde des réglages
    if ( isset( $_POST['ql_tg_save'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        update_option( 'ql_telegram_bot_token',      trim( wp_unslash( $_POST['ql_telegram_bot_token']   ?? '' ) ), false );
        update_option( 'ql_telegram_channel_id',     trim( wp_unslash( $_POST['ql_telegram_channel_id']  ?? '' ) ), false );
        update_option( 'ql_telegram_channel_url',    esc_url_raw( wp_unslash( $_POST['ql_telegram_channel_url'] ?? '' ) ), false );
        update_option( 'ql_telegram_admin_chat_id',  trim( wp_unslash( $_POST['ql_telegram_admin_chat_id'] ?? '' ) ), false );
        update_option( 'ql_telegram_autopost',         isset( $_POST['ql_telegram_autopost'] )         ? '1' : '0', false );
        update_option( 'ql_telegram_notify_plaintes',  isset( $_POST['ql_telegram_notify_plaintes'] )  ? '1' : '0', false );
        echo '<div class="notice notice-success"><p>Réglages Telegram enregistrés.</p></div>';
    }

    // Pont Telegram → site : ACTIVER (pose le webhook)
    if ( isset( $_POST['ql_tg_bridge_on'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        update_option( 'ql_telegram_log_group', '1', false );
        $res = ql_telegram_set_webhook();
        if ( is_array( $res ) && ! empty( $res['ok'] ) ) {
            echo '<div class="notice notice-success"><p>Pont activé : les messages du groupe « plaintes » seront enregistrés sur le site. (La détection des conversations est désactivée tant que le pont est actif.)</p></div>';
        } else {
            update_option( 'ql_telegram_log_group', '0', false );
            $d = is_array( $res ) && ! empty( $res['description'] ) ? $res['description'] : 'erreur inconnue (token ? site en HTTPS ?)';
            echo '<div class="notice notice-error"><p>Échec de l\'activation du pont — ' . esc_html( $d ) . '</p></div>';
        }
    }

    // Pont Telegram → site : DÉSACTIVER (retire le webhook)
    if ( isset( $_POST['ql_tg_bridge_off'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        update_option( 'ql_telegram_log_group', '0', false );
        ql_telegram_delete_webhook();
        echo '<div class="notice notice-info"><p>Pont désactivé (webhook retiré). La détection des conversations refonctionne.</p></div>';
    }

    // Test : envoyer un message au canal
    if ( isset( $_POST['ql_tg_test_channel'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        $chan = trim( (string) get_option( 'ql_telegram_channel_id', '' ) );
        $res  = ql_telegram_send_message( $chan, '✅ <b>Test Quartier Libre</b> — la connexion au canal fonctionne.' );
        ql_telegram_show_test_result( $res, 'canal' );
    }

    // Test : envoyer un message à la rédaction
    if ( isset( $_POST['ql_tg_test_admin'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        $chat = trim( (string) get_option( 'ql_telegram_admin_chat_id', '' ) );
        $res  = ql_telegram_send_message( $chat, '✅ <b>Test Quartier Libre</b> — les notifications rédaction fonctionnent.' );
        ql_telegram_show_test_result( $res, 'rédaction' );
    }

    // Détection des conversations (pour récupérer l'ID d'un canal / groupe)
    $detected = null;
    if ( isset( $_POST['ql_tg_detect'] ) && check_admin_referer( 'ql_tg_nonce' ) ) {
        $detected = ql_telegram_recent_chats();
    }

    $token       = (string) get_option( 'ql_telegram_bot_token', '' );
    $channel_id  = (string) get_option( 'ql_telegram_channel_id', '' );
    $channel_url = (string) get_option( 'ql_telegram_channel_url', '' );
    $admin_chat  = (string) get_option( 'ql_telegram_admin_chat_id', '' );
    $autopost    = get_option( 'ql_telegram_autopost', '1' ) === '1';
    $notify      = get_option( 'ql_telegram_notify_plaintes', '1' ) === '1';
    $bridge_on   = get_option( 'ql_telegram_log_group', '0' ) === '1';
    ?>
    <div class="wrap">
        <h1>Telegram — Quartier Libre</h1>

        <div style="background:#eef6ff;border:1px solid #b3d4fc;border-radius:6px;padding:16px 20px;margin:16px 0;max-width:780px;">
            <h2 style="margin-top:0;">Mise en route (une seule fois)</h2>
            <ol style="margin:0;padding-left:20px;line-height:1.7;">
                <li>Sur Telegram, ouvrez <strong>@BotFather</strong> → <code>/newbot</code> → suivez les étapes → copiez le <strong>token</strong> (ex : <code>123456:ABC-DEF...</code>).</li>
                <li>Ajoutez ce bot comme <strong>administrateur de votre canal</strong> (paramètres du canal → Administrateurs → Ajouter).</li>
                <li><strong>ID du canal</strong> : pour un canal public, mettez <code>@nomducanal</code>. Pour un canal privé, utilisez l'ID numérique (commence par <code>-100…</code>).</li>
                <li><strong>ID rédaction</strong> : créez un groupe privé avec votre bot (« Bureau des plaintes »), postez-y un message, puis cliquez <strong>« Détecter les conversations »</strong> ci-dessous pour récupérer l'ID automatiquement.</li>
            </ol>
        </div>

        <?php if ( is_array( $detected ) ) : ?>
            <div style="background:#f6f7f7;border:1px solid #ccc;border-radius:6px;padding:16px 20px;margin:16px 0;max-width:780px;">
                <h2 style="margin-top:0;">Conversations détectées</h2>
                <?php if ( empty( $detected ) ) : ?>
                    <p style="margin:0;">Aucune conversation vue par le bot. <strong>Poste d'abord un message</strong> dans ton groupe « Bureau des plaintes » (ou envoie <code>/start</code> au bot), puis reclique « Détecter ».</p>
                <?php else : ?>
                    <p style="margin:.2em 0 .8em;">Copie l'<strong>ID</strong> voulu dans le champ correspondant ci-dessous (canal = articles, groupe privé = plaintes), puis Enregistre.</p>
                    <table class="widefat striped" style="max-width:100%;">
                        <thead><tr><th>Nom</th><th>Type</th><th>ID (à copier)</th></tr></thead>
                        <tbody>
                        <?php foreach ( $detected as $c ) : ?>
                            <tr>
                                <td><?php echo esc_html( $c['name'] ); ?></td>
                                <td><?php echo esc_html( $c['type'] ); ?></td>
                                <td><code><?php echo esc_html( (string) $c['id'] ); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'ql_tg_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ql_telegram_bot_token">Token du bot</label></th>
                    <td>
                        <input type="text" id="ql_telegram_bot_token" name="ql_telegram_bot_token"
                               value="<?php echo esc_attr( $token ); ?>" class="regular-text" style="width:420px;max-width:100%;"
                               placeholder="123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxx">
                        <p class="description">Donné par @BotFather.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_telegram_channel_id">ID / @nom du canal</label></th>
                    <td>
                        <input type="text" id="ql_telegram_channel_id" name="ql_telegram_channel_id"
                               value="<?php echo esc_attr( $channel_id ); ?>" class="regular-text"
                               placeholder="@quartierlibre ou -1001234567890">
                        <p class="description">Là où les articles seront publiés automatiquement.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_telegram_channel_url">Lien public du canal</label></th>
                    <td>
                        <input type="url" id="ql_telegram_channel_url" name="ql_telegram_channel_url"
                               value="<?php echo esc_attr( $channel_url ); ?>" class="regular-text"
                               placeholder="https://t.me/quartierlibre">
                        <p class="description">Utilisé par le bouton « Rejoins-nous sur Telegram » du site.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ql_telegram_admin_chat_id">ID rédaction (privé)</label></th>
                    <td>
                        <input type="text" id="ql_telegram_admin_chat_id" name="ql_telegram_admin_chat_id"
                               value="<?php echo esc_attr( $admin_chat ); ?>" class="regular-text"
                               placeholder="-100... ou votre ID perso">
                        <p class="description">Reçoit les témoignages du Bureau des plaintes. Laissez vide pour désactiver.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Options</th>
                    <td>
                        <label><input type="checkbox" name="ql_telegram_autopost" <?php checked( $autopost ); ?>>
                            Publier automatiquement les nouveaux articles sur le canal</label><br>
                        <label><input type="checkbox" name="ql_telegram_notify_plaintes" <?php checked( $notify ); ?>>
                            Notifier la rédaction des nouveaux témoignages</label>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="ql_tg_save" class="button button-primary">Enregistrer</button>
                <button type="submit" name="ql_tg_test_channel" class="button" style="margin-left:8px;">Tester le canal</button>
                <button type="submit" name="ql_tg_test_admin" class="button">Tester la rédaction</button>
                <button type="submit" name="ql_tg_detect" class="button" style="margin-left:8px;">Détecter les conversations (IDs)</button>
            </p>

            <hr style="margin:24px 0;max-width:780px;">

            <h2>Pont Telegram → site (plaintes)</h2>
            <div style="background:#f6f7f7;border:1px solid #ccc;border-radius:6px;padding:16px 20px;max-width:780px;">
                <p style="margin-top:0;">
                    Quand le pont est <strong>activé</strong>, les messages écrits dans le groupe
                    « <strong>ID rédaction</strong> » ci-dessus sont enregistrés dans
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ql_plainte' ) ); ?>">Quartier Libre → Bureau des plaintes</a>.
                    Renseigne et enregistre d'abord l'ID rédaction (le groupe), puis active le pont.
                </p>
                <p style="margin:0;">
                    État : <strong><?php echo $bridge_on ? '🟢 actif' : '⚪ inactif'; ?></strong>
                    <?php if ( $bridge_on ) : ?>
                        — <em>la détection des conversations est désactivée tant que le pont tourne.</em>
                    <?php endif; ?>
                </p>
                <p style="margin:.8em 0 0;">
                    <?php if ( $bridge_on ) : ?>
                        <button type="submit" name="ql_tg_bridge_off" class="button">Désactiver le pont</button>
                    <?php else : ?>
                        <button type="submit" name="ql_tg_bridge_on" class="button button-primary">Activer le pont</button>
                    <?php endif; ?>
                </p>
            </div>
        </form>
    </div>
    <?php
}

function ql_telegram_show_test_result( $res, $cible ) {
    if ( is_array( $res ) && ! empty( $res['ok'] ) ) {
        echo '<div class="notice notice-success"><p>Message de test envoyé au ' . esc_html( $cible ) . ' avec succès.</p></div>';
    } else {
        $desc = is_array( $res ) && ! empty( $res['description'] ) ? $res['description'] : 'vérifiez le token, l\'ID et que le bot est admin du canal.';
        echo '<div class="notice notice-error"><p>Échec de l\'envoi au ' . esc_html( $cible ) . ' — ' . esc_html( $desc ) . '</p></div>';
    }
}
