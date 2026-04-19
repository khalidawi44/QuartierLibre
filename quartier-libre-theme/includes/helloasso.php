<?php
/**
 * Intégration HelloAsso API v5 — OAuth2 client_credentials + checkout intents
 *
 * Documentation : https://dev.helloasso.com/docs
 *
 * Flux :
 *   1. Frontend clique sur le bouton « Payer par HelloAsso »
 *   2. Fetch AJAX vers /wp-admin/admin-ajax.php?action=ql_helloasso_checkout
 *   3. Serveur obtient un bearer token (cache 30min via transient)
 *   4. Serveur crée une checkout-intent et récupère redirectUrl
 *   5. Frontend redirige le visiteur vers redirectUrl (page HelloAsso)
 *   6. Après paiement : HelloAsso redirige sur returnUrl (/soutenir/?merci-helloasso=1)
 *
 * Configuration dans wp_options :
 *   - ql_helloasso_client_id     : OAuth client ID (public)
 *   - ql_helloasso_client_secret : OAuth client secret (privé)
 *   - ql_helloasso_org_slug      : slug de l'association sur helloasso.com
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'QL_HELLOASSO_API', 'https://api.helloasso.com' );

/**
 * Récupère un bearer token HelloAsso (avec cache).
 * Retourne false en cas d'échec.
 */
function ql_helloasso_get_token() {
    $cached = get_transient( 'ql_helloasso_token' );
    if ( $cached ) return $cached;

    $client_id     = get_option( 'ql_helloasso_client_id' );
    $client_secret = get_option( 'ql_helloasso_client_secret' );
    if ( empty( $client_id ) || empty( $client_secret ) ) return false;

    $response = wp_remote_post( QL_HELLOASSO_API . '/oauth2/token', array(
        'body'    => array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ),
        'timeout' => 15,
    ) );

    if ( is_wp_error( $response ) ) return false;
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) return false;

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['access_token'] ) ) return false;

    // Cache : on retire 60s de marge avant expiration
    $ttl = max( 60, (int) ( $data['expires_in'] ?? 1800 ) - 60 );
    set_transient( 'ql_helloasso_token', $data['access_token'], $ttl );
    return $data['access_token'];
}

/**
 * Crée une checkout-intent pour un don ponctuel.
 * Retourne l'URL de redirection HelloAsso ou WP_Error.
 */
function ql_helloasso_create_checkout( $amount_euros, $payer = array() ) {
    $token = ql_helloasso_get_token();
    if ( ! $token ) {
        return new WP_Error( 'ha_no_token', 'Authentification HelloAsso impossible. Vérifiez client_id et client_secret.' );
    }

    $slug = get_option( 'ql_helloasso_org_slug', 'quartier-libre-nantes' );
    if ( empty( $slug ) ) {
        return new WP_Error( 'ha_no_slug', 'Slug de l\'organisation HelloAsso non configuré.' );
    }

    $amount_cents = (int) round( $amount_euros * 100 );
    if ( $amount_cents < 100 || $amount_cents > 1000000 ) {
        return new WP_Error( 'ha_bad_amount', 'Montant hors plage (1 € - 10 000 €).' );
    }

    $body = array(
        'totalAmount'      => $amount_cents,
        'initialAmount'    => $amount_cents,
        'itemName'         => 'Don à Quartier Libre',
        'backUrl'          => add_query_arg( 'ha-annule', '1', home_url( '/soutenir/' ) ),
        'errorUrl'         => add_query_arg( 'ha-erreur', '1', home_url( '/soutenir/' ) ),
        'returnUrl'        => add_query_arg( 'merci-helloasso', '1', home_url( '/soutenir/' ) ),
        'containsDonation' => true,
    );

    // Données payeur optionnelles
    if ( ! empty( $payer['firstName'] ) && ! empty( $payer['lastName'] ) && ! empty( $payer['email'] ) ) {
        $body['payer'] = array(
            'firstName' => sanitize_text_field( $payer['firstName'] ),
            'lastName'  => sanitize_text_field( $payer['lastName'] ),
            'email'     => sanitize_email( $payer['email'] ),
        );
    }

    $response = wp_remote_post( QL_HELLOASSO_API . '/v5/organizations/' . rawurlencode( $slug ) . '/checkout-intents', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 20,
    ) );

    if ( is_wp_error( $response ) ) return $response;
    $code = wp_remote_retrieve_response_code( $response );
    $raw  = wp_remote_retrieve_body( $response );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'ha_http_' . $code, 'HelloAsso API ' . $code . ' : ' . substr( $raw, 0, 300 ) );
    }
    $data = json_decode( $raw, true );
    if ( empty( $data['redirectUrl'] ) ) {
        return new WP_Error( 'ha_no_redirect', 'Réponse HelloAsso sans redirectUrl : ' . substr( $raw, 0, 300 ) );
    }
    return $data['redirectUrl'];
}

// ── Endpoint AJAX (front + back) ────────────────────────────────
add_action( 'wp_ajax_ql_helloasso_checkout',        'ql_handle_helloasso_checkout' );
add_action( 'wp_ajax_nopriv_ql_helloasso_checkout', 'ql_handle_helloasso_checkout' );

function ql_handle_helloasso_checkout() {
    // Nonce requis pour bloquer CSRF
    if ( ! check_ajax_referer( 'ql_helloasso', 'nonce', false ) ) {
        wp_send_json_error( 'Nonce invalide. Rechargez la page.', 403 );
    }

    $amount = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0;
    if ( $amount < 1 || $amount > 10000 ) {
        wp_send_json_error( 'Montant invalide (1 € à 10 000 €).', 400 );
    }

    $payer = array(
        'firstName' => isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '',
        'lastName'  => isset( $_POST['last_name'] )  ? wp_unslash( $_POST['last_name'] )  : '',
        'email'     => isset( $_POST['email'] )      ? wp_unslash( $_POST['email'] )      : '',
    );

    $result = ql_helloasso_create_checkout( $amount, $payer );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message(), 500 );
    }
    wp_send_json_success( array( 'url' => $result ) );
}
