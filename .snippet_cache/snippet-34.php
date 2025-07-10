<?php
/**
 * Snippet ID: 34
 * Name: afficher donnees metas UT categories commande
 * Description: 
 * @active false
 */

/**
 * Affiche les métadonnées détaillées avec diagnostic des UT manquantes
 */
add_action('woocommerce_admin_order_data_after_order_details', 'afficher_metas_detaillees_avec_diagnostic');
function afficher_metas_detaillees_avec_diagnostic($order) {
    echo '<div class="order-metas-detaillees" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3 style="margin-top: 0; color: #333;">📊 Détails de Formation</h3>';
    
    foreach ($order->get_items() as $item_id => $item) {
        // Récupérer les métadonnées existantes
        $choix_categorie = wc_get_order_item_meta($item_id, 'choix_categorie', true);
        $nombre_categories = wc_get_order_item_meta($item_id, 'nombre_categories', true);
        $ut_pratique = wc_get_order_item_meta($item_id, 'ut_pratique', true);
        $ut_theorique = wc_get_order_item_meta($item_id, 'ut_theorique', true);
        $caces_code = wc_get_order_item_meta($item_id, 'caces_code', true);
        $niveau = wc_get_order_item_meta($item_id, 'niveau', true);
        
        // Si pas de code CACES dans les metas, essayer de l'extraire du nom du produit
        if (!$caces_code) {
            $product_name = $item->get_name();
            $caces_code = get_caces_code_from_product_name($product_name);
        }
        
        echo '<div class="item-formation-details" style="margin-bottom: 15px; padding: 10px; background: white; border-left: 4px solid #0073aa;">';
        echo '<h4 style="margin: 0 0 10px 0; color: #0073aa;">🎯 ' . esc_html($item->get_name()) . '</h4>';
        
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        // Informations générales
        echo '<div class="info-block" style="border: 1px solid #e0e0e0; padding: 10px; border-radius: 4px; background: #fafafa;">';
        echo '<strong>📋 Formation :</strong><br>';
        if ($caces_code) {
            echo '• Code : <span style="color: #d63638; font-weight: bold;">' . esc_html($caces_code) . '</span><br>';
        } else {
            echo '• Code : <span style="color: #999;">Non détecté</span><br>';
        }
        if ($niveau) {
            echo '• Niveau : <span style="color: #00a32a; font-weight: bold;">' . esc_html(ucfirst($niveau)) . '</span>';
        } else {
            echo '• Niveau : <span style="color: #999;">Non spécifié</span>';
        }
        echo '</div>';
        
        // Catégories
        if ($choix_categorie) {
            echo '<div class="info-block" style="border: 1px solid #e0e0e0; padding: 10px; border-radius: 4px; background: #fafafa;">';
            echo '<strong>🏷️ Catégories :</strong><br>';
            $categories = explode(',', $choix_categorie);
            foreach ($categories as $cat) {
                echo '• <span style="background: #e1f5fe; padding: 2px 6px; border-radius: 3px; margin-right: 5px;">' . esc_html(trim($cat)) . '</span><br>';
            }
            if ($nombre_categories) {
                echo '<small style="color: #666;">Total : ' . esc_html($nombre_categories) . ' catégorie(s)</small>';
            }
            echo '</div>';
        }
        
        // UT avec diagnostic
        echo '<div class="info-block" style="border: 1px solid #e0e0e0; padding: 10px; border-radius: 4px; background: #fafafa;">';
        echo '<strong>⏱️ Unités de Temps :</strong><br>';
        
        if ($ut_pratique || $ut_theorique) {
            // UT trouvées dans les métadonnées
            if ($ut_pratique) {
                echo '• Pratique : <span style="color: #d63638; font-weight: bold;">' . esc_html($ut_pratique) . ' UT</span><br>';
            }
            if ($ut_theorique) {
                echo '• Théorique : <span style="color: #00a32a; font-weight: bold;">' . esc_html($ut_theorique) . ' UT</span><br>';
            }
            if ($ut_pratique && $ut_theorique) {
                $total_ut = floatval($ut_pratique) + floatval($ut_theorique);
                echo '• <strong>Total : <span style="color: #0073aa; font-weight: bold;">' . $total_ut . ' UT</span></strong>';
            }
        } else {
            // UT manquantes - Diagnostic
            echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px; border-radius: 4px; margin-top: 5px;">';
            echo '<strong style="color: #856404;">⚠️ UT non trouvées</strong><br>';
            echo '<small style="color: #856404;">';
            
            // Tentative de calcul en temps réel
            if ($caces_code && $choix_categorie && function_exists('get_formation_key_from_caces_code') && function_exists('get_ut_for_category')) {
                $formation_key = get_formation_key_from_caces_code($caces_code);
                if ($formation_key) {
                    $categories = array_map('trim', explode(',', $choix_categorie));
                    $total_ut_pratique_calc = 0;
                    $total_ut_theorique_calc = 0;
                    
                    foreach ($categories as $category) {
                        $ut_data = get_ut_for_category($formation_key, $category);
                        if ($ut_data) {
                            $total_ut_pratique_calc += floatval($ut_data['ut_pratique']);
                            $total_ut_theorique_calc += floatval($ut_data['ut_theorique']);
                        }
                    }
                    
                    if ($total_ut_pratique_calc || $total_ut_theorique_calc) {
                        echo 'Calcul en temps réel :<br>';
                        if ($total_ut_pratique_calc) echo '• Pratique : ' . $total_ut_pratique_calc . ' UT<br>';
                        if ($total_ut_theorique_calc) echo '• Théorique : ' . $total_ut_theorique_calc . ' UT<br>';
                        echo '<em>Ces UT n\'ont pas été enregistrées lors de la commande.</em>';
                    } else {
                        echo 'Aucune donnée UT configurée pour ' . $formation_key . ' / ' . $choix_categorie;
                    }
                } else {
                    echo 'Formation key non trouvée pour le code : ' . $caces_code;
                }
            } else {
                echo 'Données insuffisantes pour calculer les UT ou fonctions manquantes.';
            }
            echo '</small></div>';
        }
        echo '</div>';
        
        // Prix
        echo '<div class="info-block" style="border: 1px solid #e0e0e0; padding: 10px; border-radius: 4px; background: #fafafa;">';
        echo '<strong>💰 Tarification :</strong><br>';
        echo '• Prix unitaire : <span style="color: #d63638; font-weight: bold;">' . wc_price($item->get_total() / $item->get_quantity()) . '</span><br>';
        echo '• Quantité : ' . esc_html($item->get_quantity()) . '<br>';
        echo '• <strong>Total : <span style="color: #0073aa; font-weight: bold;">' . wc_price($item->get_total()) . '</span></strong>';
        echo '</div>';
        
        echo '</div>'; // Fin grid
        echo '</div>'; // Fin item-formation-details
    }
    
    echo '</div>';
}