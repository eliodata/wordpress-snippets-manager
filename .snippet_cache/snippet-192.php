<?php
/**
 * Snippet ID: 192
 * Name: FERMETURE metabox bug corrigé
 * Description: 
 * @active true
 */

function fix_metabox_toggle() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Réinitialise les handlers des metabox
        $('.postbox .hndle, .postbox .handlediv').off('click.postboxes');
        
        // Reattache les événements proprement
        $('.postbox .hndle, .postbox .handlediv').on('click.postboxes', function() {
            var $postbox = $(this).closest('.postbox');
            var $inside = $postbox.find('.inside');
            
            if ($inside.is(':visible')) {
                $inside.slideUp('fast');
                $postbox.addClass('closed');
            } else {
                $inside.slideDown('fast');
                $postbox.removeClass('closed');
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'fix_metabox_toggle');
add_action('admin_footer-post-new.php', 'fix_metabox_toggle');