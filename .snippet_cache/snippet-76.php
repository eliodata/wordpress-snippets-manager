<?php
/**
 * Snippet ID: 76
 * Name: METABOX ENTETE CPT STAGIAIRES
 * Description: 
 * @active true
 */

add_action('add_meta_boxes', 'fsbdd_stagiaire_formations_metabox');
function fsbdd_stagiaire_formations_metabox() {
    add_meta_box(
        'fsbdd_stagiaire_formations_metabox',
        'Historique des formations',
        'fsbdd_stagiaire_formations_metabox_callback',
        'stagiaire', // Type de publication
        'normal', // Contexte
        'high' // Priorité
    );
}

function fsbdd_stagiaire_formations_metabox_callback($post) {
    // Récupérer les formations liées
    $formations_serialized = get_post_meta($post->ID, 'fsbdd_related_formations', true);
    $formations = maybe_unserialize($formations_serialized);

    echo '<div class="fsbdd-metabox-content">';

    if (empty($formations) || !is_array($formations)) {
        echo '<p>Aucune formation enregistrée pour ce stagiaire.</p>';
        echo '</div>';
        return;
    }

    // Construire le tableau
    echo '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
    echo '<thead>';
    echo '<tr style="border-bottom: 2px solid #ddd;">';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Formation</th>';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Session</th>';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Début</th>';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Fin</th>';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Lieu</th>';
    echo '<th style="text-align: left; padding: 5px; border: 1px solid #ddd;">Type de session</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($formations as $formation) {
        $session_edit_link = get_edit_post_link($formation['id']); // Lien vers l'édition du CPT action-de-formation
        $type_session_value_or_label = get_type_session($formation['type_session']); // Fonction pour afficher la valeur ou le label

        echo '<tr>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html(mb_strimwidth($formation['product_title'] ?? 'Produit non défini', 0, 40, '...')) . '</td>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;"><a href="' . esc_url($session_edit_link) . '" target="_blank">' . esc_html($formation['title'] ?? 'Session non définie') . '</a></td>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($formation['start_date'] ?? 'Non défini') . '</td>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($formation['end_date'] ?? 'Non défini') . '</td>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($formation['location'] ?? 'Non défini') . '</td>';
        echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($type_session_value_or_label) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function get_type_session($type_session) {
    // Exemple de correspondance valeur-label
    $type_session_labels = [
        '1' => 'INTER',
        '2' => 'INTER à définir',
        '3' => 'INTRA',
    ];

    // Retourner la valeur ou le label
    return $type_session_labels[$type_session] ?? $type_session;
}
