<?php
/**
 * Snippet ID: 52
 * Name: SHORTCODE calendrier affichage sur cpt action de formation dans champ html
 * Description: 
 * @active false
 */

function display_planning_callback($field, $meta) {
    global $post;

    // Récupérer les données de `fsbdd_planning`
    $planning_data = get_post_meta($post->ID, 'fsbdd_planning', true);

    if (!is_array($planning_data)) {
        return '<p>Aucun planning trouvé.</p>';
    }

    // Construire le HTML du tableau
    $html = '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
    $html .= '<thead style="background-color: #f0f0f0;">';
    $html .= '<tr>
                <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Matin</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Après-midi</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Formateurs</th>
                <th style="border: 1px solid #ddd; padding: 8px;">État</th>
              </tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ($planning_data as $day) {
        $date = esc_html($day['fsbdd_planjour'] ?? '');
        $morning_start = esc_html($day['fsbdd_plannmatin'] ?? '');
        $morning_end = esc_html($day['fsbdd_plannmatinfin'] ?? '');
        $afternoon_start = esc_html($day['fsbdd_plannam'] ?? '');
        $afternoon_end = esc_html($day['fsbdd_plannamfin'] ?? '');

        // Gestion des formateurs
        $formateurs = '';
        if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
            foreach ($day['fsbdd_gpformatr'] as $formateur) {
                $formateur_id = esc_html($formateur['fsbdd_user_formateurrel'] ?? 'N/A');
                $dispo = esc_html($formateur['fsbdd_dispjourform'] ?? 'N/A');
                $etat = esc_html($formateur['fsbdd_okformatr'] ?? 'N/A');
                $formateurs .= "ID: $formateur_id ($dispo - $etat)<br>";
            }
        }

        // Gestion des états
        $etat = esc_html($day['fsbdd_gpformatr'][0]['fsbdd_okformatr'] ?? 'N/A');

        // Ajouter une ligne au tableau
        $html .= "<tr>
                    <td style='border: 1px solid #ddd; padding: 8px;'>$date</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>$morning_start - $morning_end</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>$afternoon_start - $afternoon_end</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>$formateurs</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>$etat</td>
                  </tr>";
    }

    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}
