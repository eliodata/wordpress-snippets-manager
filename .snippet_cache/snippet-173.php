<?php
/**
 * Snippet ID: 173
 * Name: supprimer notes de commande
 * Description: 
 * @active false
 */


/**
 * Snippet pour supprimer toutes les notes de la commande 271849
 * ATTENTION: À exécuter une seule fois, puis supprimer ce code
 */

// Fonction pour supprimer les notes
function fsb_supprimer_notes_commande() {
    global $wpdb;
    
    // ID de la commande spécifique
    $order_id = 271849;
    
    // Récupérer toutes les notes de cette commande
    $notes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT comment_ID FROM {$wpdb->comments} 
            WHERE comment_post_ID = %d 
            AND comment_type = 'order_note'",
            $order_id
        )
    );
    
    $count = 0;
    
    // Supprimer chaque note
    if (!empty($notes)) {
        foreach ($notes as $note) {
            wp_delete_comment($note->comment_ID, true); // true = suppression permanente
            $count++;
        }
    }
    
    // Message de confirmation
    echo "<div class='notice notice-success'><p>$count notes supprimées pour la commande #$order_id.</p></div>";
}

// Exécuter lors du chargement d'admin
add_action('admin_init', 'fsb_supprimer_notes_commande');