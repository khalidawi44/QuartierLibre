<?php
/**
 * Sidebar — colonne latérale des articles (optionnelle).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_active_sidebar( 'ql-sidebar' ) ) { return; }
?>
<aside class="ql-sidebar" role="complementary">
    <?php dynamic_sidebar( 'ql-sidebar' ); ?>
</aside>
