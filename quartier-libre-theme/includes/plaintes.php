<?php
/**
 * Quartier Libre — Registre des plaintes (Bureau des plaintes)
 *
 * Stocke au même endroit (admin) toutes les plaintes, quelle que soit leur
 * origine :
 *   - le formulaire « Bureau des plaintes » du site (action ql_plainte_received)
 *   - les messages du groupe Telegram privé (via le webhook dans telegram.php)
 *
 * Menu : Quartier Libre → Bureau des plaintes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Type de contenu privé « ql_plainte » ───────────────────────
add_action( 'init', function () {
    register_post_type( 'ql_plainte', array(
        'labels' => array(
            'name'          => 'Plaintes',
            'singular_name' => 'Plainte',
            'menu_name'     => 'Bureau des plaintes',
            'all_items'     => 'Toutes les plaintes',
            'edit_item'     => 'Plainte',
            'search_items'  => 'Rechercher une plainte',
            'not_found'     => 'Aucune plainte',
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'ql-dashboard',
        'show_in_rest'        => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array( 'title', 'editor' ),
        'menu_icon'           => 'dashicons-megaphone',
    ) );
} );

/**
 * Enregistre une plainte. $data : message (requis), + type, quartier, nom,
 * email, source ('site'|'telegram'), tg_from, tg_chat. Retourne l'ID ou 0.
 */
function ql_plainte_store_entry( $data ) {
    $source   = $data['source']   ?? 'site';
    $type     = sanitize_text_field( $data['type']     ?? '' );
    $quartier = sanitize_text_field( $data['quartier'] ?? '' );
    $nom      = sanitize_text_field( $data['nom']      ?? '' );
    $email    = sanitize_email(      $data['email']    ?? '' );
    $message  = trim( wp_strip_all_tags( (string) ( $data['message'] ?? '' ) ) );
    if ( $message === '' ) { return 0; }

    if ( $source === 'telegram' ) {
        $who   = sanitize_text_field( $data['tg_from'] ?? 'Telegram' );
        $title = '[Telegram] ' . $who . ' — ' . wp_trim_words( $message, 8, '…' );
    } else {
        $label = trim( ( $type ?: 'Plainte' ) . ( $quartier ? ' — ' . $quartier : '' ) );
        $title = $label !== '' ? $label : wp_trim_words( $message, 8, '…' );
    }

    $post_id = wp_insert_post( array(
        'post_type'    => 'ql_plainte',
        'post_status'  => 'publish',
        'post_title'   => wp_strip_all_tags( $title ),
        'post_content' => $message,
    ), true );

    if ( is_wp_error( $post_id ) || ! $post_id ) { return 0; }

    update_post_meta( $post_id, '_ql_p_source', $source );
    if ( $type )     { update_post_meta( $post_id, '_ql_p_type', $type ); }
    if ( $quartier ) { update_post_meta( $post_id, '_ql_p_quartier', $quartier ); }
    if ( $nom )      { update_post_meta( $post_id, '_ql_p_nom', $nom ); }
    if ( $email )    { update_post_meta( $post_id, '_ql_p_email', $email ); }
    if ( ! empty( $data['tg_from'] ) ) { update_post_meta( $post_id, '_ql_p_tg_from', sanitize_text_field( $data['tg_from'] ) ); }
    if ( ! empty( $data['tg_chat'] ) ) { update_post_meta( $post_id, '_ql_p_tg_chat', sanitize_text_field( (string) $data['tg_chat'] ) ); }

    return $post_id;
}

// ── Stocke les plaintes venues du formulaire du site ───────────
// (le webhook Telegram appelle ql_plainte_store_entry() directement, avec
//  source 'telegram', pour NE PAS re-déclencher l'envoi vers Telegram.)
add_action( 'ql_plainte_received', function ( $d ) {
    $d['source'] = 'site';
    ql_plainte_store_entry( $d );
}, 5 );

// ── Colonnes de la liste admin ─────────────────────────────────
add_filter( 'manage_ql_plainte_posts_columns', function ( $cols ) {
    return array(
        'cb'          => $cols['cb'] ?? '<input type="checkbox" />',
        'title'       => 'Sujet',
        'ql_source'   => 'Source',
        'ql_quartier' => 'Quartier',
        'ql_contact'  => 'Contact',
        'date'        => 'Date',
    );
} );

add_action( 'manage_ql_plainte_posts_custom_column', function ( $col, $post_id ) {
    if ( $col === 'ql_source' ) {
        $s = get_post_meta( $post_id, '_ql_p_source', true );
        echo $s === 'telegram'
            ? '<span style="color:#1c93e3;font-weight:600;">Telegram</span>'
            : '<span style="color:#2a8a2a;font-weight:600;">Site</span>';
    } elseif ( $col === 'ql_quartier' ) {
        echo esc_html( get_post_meta( $post_id, '_ql_p_quartier', true ) ?: '—' );
    } elseif ( $col === 'ql_contact' ) {
        $nom = get_post_meta( $post_id, '_ql_p_nom', true );
        $tg  = get_post_meta( $post_id, '_ql_p_tg_from', true );
        $em  = get_post_meta( $post_id, '_ql_p_email', true );
        echo esc_html( $nom ?: ( $tg ?: 'Anonyme' ) );
        if ( $em ) {
            echo '<br><a href="mailto:' . esc_attr( $em ) . '">' . esc_html( $em ) . '</a>';
        }
    }
}, 10, 2 );
