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

// ════════════════════════════════════════════════════════════════
//  1. PUBLICATION AUTO DES ARTICLES
// ════════════════════════════════════════════════════════════════

add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
    // Uniquement quand un article passe RÉELLEMENT en ligne
    if ( $new_status !== 'publish' || $old_status === 'publish' ) { return; }
    if ( ! $post || $post->post_type !== 'post' ) { return; }

    if ( get_option( 'ql_telegram_autopost', '1' ) !== '1' ) { return; }
    if ( ql_telegram_token() === '' ) { return; }

    $channel = trim( (string) get_option( 'ql_telegram_channel_id', '' ) );
    if ( $channel === '' ) { return; }

    // Jamais deux fois le même article (filet anti-doublon)
    if ( get_post_meta( $post->ID, '_ql_telegram_sent', true ) ) { return; }

    $title   = get_the_title( $post );
    $url     = get_permalink( $post );
    $excerpt = has_excerpt( $post )
        ? get_the_excerpt( $post )
        : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );

    $caption = '<b>' . esc_html( $title ) . "</b>\n\n"
             . esc_html( $excerpt ) . "\n\n"
             . $url;

    $thumb = get_the_post_thumbnail_url( $post, 'ql-card' );
    if ( $thumb ) {
        $res = ql_telegram_send_photo( $channel, $thumb, $caption );
    } else {
        $res = ql_telegram_send_message( $channel, $caption );
    }

    if ( is_array( $res ) && ! empty( $res['ok'] ) ) {
        update_post_meta( $post->ID, '_ql_telegram_sent', time() );
    }
}, 20, 3 );

// ════════════════════════════════════════════════════════════════
//  2. BOUTON « REJOINS-NOUS SUR TELEGRAM »
// ════════════════════════════════════════════════════════════════

function ql_telegram_channel_url() {
    return trim( (string) get_option( 'ql_telegram_channel_url', '' ) );
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

    $token       = (string) get_option( 'ql_telegram_bot_token', '' );
    $channel_id  = (string) get_option( 'ql_telegram_channel_id', '' );
    $channel_url = (string) get_option( 'ql_telegram_channel_url', '' );
    $admin_chat  = (string) get_option( 'ql_telegram_admin_chat_id', '' );
    $autopost    = get_option( 'ql_telegram_autopost', '1' ) === '1';
    $notify      = get_option( 'ql_telegram_notify_plaintes', '1' ) === '1';
    ?>
    <div class="wrap">
        <h1>Telegram — Quartier Libre</h1>

        <div style="background:#eef6ff;border:1px solid #b3d4fc;border-radius:6px;padding:16px 20px;margin:16px 0;max-width:780px;">
            <h2 style="margin-top:0;">Mise en route (une seule fois)</h2>
            <ol style="margin:0;padding-left:20px;line-height:1.7;">
                <li>Sur Telegram, ouvrez <strong>@BotFather</strong> → <code>/newbot</code> → suivez les étapes → copiez le <strong>token</strong> (ex : <code>123456:ABC-DEF...</code>).</li>
                <li>Ajoutez ce bot comme <strong>administrateur de votre canal</strong> (paramètres du canal → Administrateurs → Ajouter).</li>
                <li><strong>ID du canal</strong> : pour un canal public, mettez <code>@nomducanal</code>. Pour un canal privé, utilisez l'ID numérique (commence par <code>-100…</code>).</li>
                <li><strong>ID rédaction</strong> : créez un groupe privé avec votre bot, envoyez-y un message, puis récupérez l'ID (ou écrivez à <strong>@userinfobot</strong> pour votre ID perso).</li>
            </ol>
        </div>

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
            </p>
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
