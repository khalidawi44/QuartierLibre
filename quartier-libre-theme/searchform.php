<?php
/**
 * Formulaire de recherche custom (utilisé par get_search_form()).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label for="ql-s" class="ql-visually-hidden">Rechercher</label>
    <input type="search" id="ql-s" name="s" placeholder="Rechercher un article, un quartier, un thème…" value="<?php echo esc_attr( get_search_query() ); ?>" required>
    <button type="submit" class="ql-btn ql-btn--ghost">Rechercher</button>
</form>
