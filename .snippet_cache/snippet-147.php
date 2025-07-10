<?php
/**
 * Snippet ID: 147
 * Name: Thème back-end Bloc woocommerce details de commande / remplacer par nouveau design
 * Description: 
 * @active true
 */

/**
 * Plugin Name: FS - Amélioration Détails Commande WooCommerce
 * Description: Redesign du bloc détails de commande dans l'admin WooCommerce
 * Version: 1.4
 * Author: Formation Stratégique
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute les styles CSS personnalisés pour le bloc de détails commande
 */
function fs_woocommerce_order_details_styles() {
    // Ne charger les styles que sur les pages d'édition de commande WooCommerce
    $screen = get_current_screen();
    if ($screen && $screen->id === 'shop_order') {
        ?>
        <style>
            /* Variables de couleurs cohérentes avec votre style existant */
            :root {
                --fs-primary-color: #4a6fdc;
                --fs-primary-hover: #3a5ecc;
                --fs-success-color: #28a745;
                --fs-warning-color: #ffc107;
                --fs-danger-color: #dc3545;
                --fs-info-color: #17a2b8;
                --fs-light-color: #f8f9fa;
                --fs-dark-color: #343a40;
                --fs-border-color: #dee2e6;
                --fs-text-color: #333;
            }
            
            /* Redesign UNIQUEMENT pour le bloc détails de commande */
            #order_data {
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                color: var(--fs-text-color);
                line-height: 1.4;
                margin-bottom: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                border-radius: 5px;
                overflow: hidden;
            }
            
            /* Titre du bloc */
            .woocommerce-order-data__heading {
                font-size: 16px;
                padding: 12px 15px;
                margin: 0;
                border-bottom: 2px solid var(--fs-primary-color);
                background-color: var(--fs-light-color);
                font-weight: 600;
            }
            
            /* Conteneur des colonnes - Forcer l'alignement horizontal */
            #order_data .order_data_column_container {
                display: flex;
                flex-wrap: nowrap; /* Empêche le passage à la ligne */
                width: 100%;
                padding: 0;
                background: white;
                border: 1px solid var(--fs-border-color);
                border-top: none;
            }
			
			#order_data {
    			padding: 0px;
				margin-bottom: 0px;
			}
            
            /* Style pour chaque colonne */
            #order_data .order_data_column {
                flex: 1; /* Chaque colonne prend 1/3 de l'espace */
                min-width: 0; /* Permet la réduction en dessous de sa taille naturelle */
                padding: 15px;
                border-right: 1px solid var(--fs-border-color);
                position: relative;
                box-sizing: border-box;
            }
            
            #order_data .order_data_column:last-child {
                border-right: none;
            }
            
            /* Codage couleur pour les colonnes */
            #order_data .order_data_column:nth-child(1) {
                border-left: 4px solid var(--fs-primary-color);
            }
            
            #order_data .order_data_column:nth-child(2) {
                border-left: 4px solid var(--fs-info-color);
            }
            
            #order_data .order_data_column:nth-child(3) {
                border-left: 4px solid var(--fs-success-color);
            }
            
            /* Style compact des titres */
            #order_data .order_data_column h3 {
                font-size: 15px;
                margin: 0 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 1px solid var(--fs-border-color);
                color: var(--fs-dark-color);
                font-weight: 600;
            }
            
            /* Remplacement du texte "Expédition" par "Lieu de la formation" via CSS */
            #order_data .order_data_column:nth-child(3) h3 {
                font-size: 0; /* Cache le texte original */
            }
            
            #order_data .order_data_column:nth-child(3) h3::before {
                content: "Lieu de la formation";
                font-size: 15px; /* Restaure la taille de police */
                font-weight: 600;
            }
            
            /* Style des boutons d'édition */
            #order_data .order_data_column h3 a.edit_address {
                font-size: 13px !important;
                margin-left: 8px;
                opacity: 1 !important;
                transition: opacity 0.2s;
                vertical-align: middle;
                display: inline-block !important;
                visibility: visible !important;
                color: var(--fs-primary-color) !important;
                text-decoration: none !important;
            }
            
            #order_data .order_data_column h3 a.edit_address:hover {
                opacity: 0.8 !important;
                color: var(--fs-primary-hover) !important;
            }
            
            /* Réduire l'espacement des éléments */
            #order_data .order_data_column p,
            #order_data .order_data_column .address {
                margin: 0 0 6px 0;
                line-height: 1.3;
                font-size: 12px;
            }
            
            /* Optimisation globale du conteneur principal */
            #order_data {
                padding: 0 !important;
            }
            
            /* Supprimer le padding de la metabox */
            #order_data .inside {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Supprimer le padding de la metabox WooCommerce */
            #woocommerce-order-data .inside,
            .postbox#woocommerce-order-data .inside {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Supprimer les marges par défaut de WordPress */
            .postbox#woocommerce-order-data {
                margin-bottom: 20px;
            }
            
            .postbox#woocommerce-order-data .postbox-header {
                border-bottom: 1px solid #c3c4c7;
            }
            
            #order_data .order_data_column_container {
                margin: 0;
                gap: 8px;
            }
            
            #order_data .order_data_column {
                padding: 8px 12px;
                margin: 0;
                border: none;
            }
            
            /* Assurer que les boutons d'édition sont toujours visibles */
            #order_data .order_data_column h3 .edit_address,
            #order_data .order_data_column h3 a[href*="edit"] {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                font-size: 12px !important;
                margin-left: 6px;
                color: var(--fs-primary-color) !important;
                padding-left: 10px !important;
                padding-bottom: 15px !important;
                min-width: 60px !important;
                background: rgb(213 235 255) !important;
            }
            
            #order_data .order_data_column h3 {
                margin: 0 0 8px 0;
                padding: 0;
                font-size: 14px;
                line-height: 1.3;
            }

            #order_data .order_data_column a.edit_address::after
 {
    position: relative;
    left: 5px;

}
            
            /* Style des adresses */
            #order_data .order_data_column .address {
                padding-left: 0;
                font-style: normal;
            }
            
            /* Style pour les liens */
            #order_data .order_data_column a {
                color: var(--fs-primary-color);
                text-decoration: none;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            #order_data .order_data_column a:hover {
                color: var(--fs-primary-hover);
                text-decoration: underline;
            }
            
            /* Style pour les détails client */
            #order_data .order_data_column .wc-customer-user {
                margin-bottom: 6px;
                font-weight: 500;
                font-size: 12px;
                line-height: 1.3;
            }
            
            /* Compacter les sélecteurs et dropdowns */
            #order_data .order_data_column .wc-customer-search,
            #order_data .order_data_column .select2-container {
                margin-bottom: 4px;
            }
            
            #order_data .order_data_column .select2-container .select2-selection {
                height: 26px;
                padding: 2px 6px;
                font-size: 12px;
            }
            
            #order_data .order_data_column .select2-container .select2-selection__rendered {
                line-height: 22px;
                font-size: 12px;
            }
            
            /* Style des étiquettes */
            #order_data .order_data_column label {
                display: block;
                font-size: 12px;
                color: #666;
                margin-bottom: 3px;
                font-weight: 500;
            }
            
            /* Style des champs inputs */
            #order_data .order_data_column input[type="text"],
            #order_data .order_data_column input[type="email"],
            #order_data .order_data_column select {
                width: 100%;
                padding: 4px 6px;
                margin-bottom: 6px;
                border: 1px solid var(--fs-border-color);
                border-radius: 3px;
                font-size: 12px;
                box-sizing: border-box;
                transition: all 0.2s ease;
                height: 26px;
            }
            
            /* Mise en évidence au focus */
            #order_data .order_data_column input[type="text"]:focus,
            #order_data .order_data_column input[type="email"]:focus,
            #order_data .order_data_column select:focus {
                border-color: var(--fs-primary-color);
                box-shadow: 0 0 0 2px rgba(74, 111, 220, 0.2);
                outline: none;
            }
            
            /* Style pour les informations téléphone et email */
            #order_data .order_data_column .form-field {
                margin-bottom: 4px;
            }
            
            /* Styles spécifiques pour les formulaires d'édition d'adresses */
            #order_data .order_data_column .edit_address {
                display: inline-block;
            }
            
            /* Réduction drastique des espaces dans les formulaires d'édition */
            #order_data .order_data_column .form-field p {
                margin: 0 0 4px 0;
            }
            
            #order_data .order_data_column .form-field label {
                margin-bottom: 2px;
                font-size: 11px;
                line-height: 1.2;
            }
            
            /* Compacter les champs de formulaire en mode édition */
            #order_data .order_data_column .form-field input,
            #order_data .order_data_column .form-field select,
            #order_data .order_data_column .form-field textarea {
                margin-bottom: 4px;
                padding: 3px 5px;
                font-size: 11px;
                height: 24px;
                line-height: 1.2;
            }
            
            #order_data .order_data_column .form-field textarea {
                height: auto;
                min-height: 40px;
                resize: vertical;
            }
            
            /* Réduire l'espacement entre les groupes de champs */
            #order_data .order_data_column .form-field-wide {
                margin-bottom: 6px;
            }
            
            /* Compacter les champs sur deux colonnes */
            #order_data .order_data_column .form-field-first,
            #order_data .order_data_column .form-field-last {
                width: 48%;
                display: inline-block;
                vertical-align: top;
                margin-bottom: 4px;
            }
            
            #order_data .order_data_column .form-field-first {
                margin-right: 4%;
            }
            
            /* Réduire l'espacement des paragraphes dans les adresses */
            #order_data .order_data_column .address p {
                margin: 0 0 2px 0;
                line-height: 1.3;
            }
            
            /* Aligner état et client sur la même ligne */
            #order_data .order_data_column .form-field-row {
                display: flex;
                flex-wrap: wrap;
                margin: 0 -3px;
                align-items: flex-start;
            }
            
            #order_data .order_data_column .form-field-row .form-field {
                flex: 1 1 47%;
                margin: 0 3px 6px;
                min-width: 120px;
            }
            
            /* Style pour les boutons et liens d'action */
            #order_data .order_data_column .button-group {
                margin-top: 6px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 4px;
            }
            
            #order_data .order_data_column .button-link {
                margin: 0;
                font-size: 11px;
                display: inline-flex;
                align-items: center;
                padding: 2px 6px;
                line-height: 1.2;
            }
            
            #order_data .order_data_column .button-link:before {
                content: '→';
                margin-right: 2px;
                font-size: 12px;
            }
            
            /* Compacter les boutons d'action dans les formulaires */
            #order_data .order_data_column .form-field .button,
            #order_data .order_data_column .form-field input[type="submit"],
            #order_data .order_data_column .form-field input[type="button"] {
                padding: 3px 8px;
                font-size: 11px;
                height: 24px;
                line-height: 1.2;
                margin: 2px 4px 2px 0;
            }
            
            /* Réduire l'espacement des actions de formulaire */
            #order_data .order_data_column .form-field .form-field-actions {
                margin-top: 4px;
                margin-bottom: 4px;
            }
            
            /* Optimiser l'affichage des liens d'édition */
            #order_data .order_data_column h3 .edit_address {
                font-size: 12px !important;
                margin-left: 6px;
                padding: 2px 6px;
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                background: rgba(74, 111, 220, 0.1);
                border-radius: 3px;
                color: var(--fs-primary-color) !important;
                text-decoration: none !important;
            }
            
            /* Forcer l'affichage des boutons d'édition avec sélecteurs plus spécifiques */
            .post-type-shop_order #order_data .order_data_column h3 a.edit_address,
            .post-type-shop_order #order_data .order_data_column h3 a[href*="edit"],
            .woocommerce-page #order_data .order_data_column h3 a.edit_address {
                display: inline-block !important;
                visibility: visible !important;
                opacity: 1 !important;
                font-size: 12px !important;
                color: var(--fs-primary-color) !important;
                text-decoration: none !important;
                margin-left: 8px;
                padding: 2px 6px;
                background: rgba(74, 111, 220, 0.1);
                border-radius: 3px;
                transition: all 0.2s ease;
            }
            
            .post-type-shop_order #order_data .order_data_column h3 a.edit_address:hover,
            .post-type-shop_order #order_data .order_data_column h3 a[href*="edit"]:hover,
            .woocommerce-page #order_data .order_data_column h3 a.edit_address:hover {
                background: rgba(74, 111, 220, 0.2);
                color: var(--fs-primary-hover) !important;
            }
            
            /* Date de création */
            #order_data .order_data_column .date-creation {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
            }
            
            #order_data .order_data_column .date-creation input[type="text"] {
                margin-bottom: 0;
                margin-right: 4px;
                font-size: 11px;
                height: 24px;
                padding: 2px 4px;
            }
            
            #order_data .order_data_column .date-creation span {
                margin: 0 3px;
                font-size: 11px;
            }
            
            /* Optimisation spécifique pour les formulaires d'édition d'adresses */
            #order_data .order_data_column .edit_address + div,
            #order_data .order_data_column .edit_address ~ div {
                margin-top: 4px;
            }
            
            /* Réduire l'espacement dans les formulaires d'édition */
            #order_data .order_data_column .form-field-wide,
            #order_data .order_data_column .form-field-first,
            #order_data .order_data_column .form-field-last {
                margin-bottom: 4px;
            }
            
            /* Compacter les champs de saisie dans les formulaires d'adresse */
            #order_data .order_data_column .form-field input[type="text"],
            #order_data .order_data_column .form-field input[type="email"],
            #order_data .order_data_column .form-field input[type="tel"],
            #order_data .order_data_column .form-field select,
            #order_data .order_data_column .form-field textarea {
                padding: 2px 4px;
                margin-bottom: 3px;
                font-size: 11px;
                height: 22px;
                line-height: 1.2;
            }
            
            #order_data .order_data_column .form-field textarea {
                height: auto;
                min-height: 36px;
            }
            
            /* Réduire l'espacement des labels dans les formulaires */
            #order_data .order_data_column .form-field label {
                margin-bottom: 1px;
                font-size: 10px;
                font-weight: 500;
                color: #666;
                line-height: 1.2;
            }
            
            /* Optimiser l'affichage des boutons de sauvegarde */
            #order_data .order_data_column .form-field .button.save_order {
                margin-top: 4px;
                margin-bottom: 2px;
                padding: 2px 6px;
                font-size: 10px;
                height: 22px;
            }
            
            /* Numéro de commande et titre */
            .commande-title {
                font-size: 14px;
                padding: 12px 15px;
                margin: 0;
                background-color: #d5ebff;
                border-bottom: 2px solid var(--fs-primary-color);
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
				color: #1d2327;
            }
            
            .commande-title .commande-numero {
                font-weight: bold;
                color: var(--fs-primary-color);
            }
            
            .commande-title .commande-ip {
                font-size: 13px;
                color: #666;
                font-weight: normal;
            }
            
            /* Styles supplémentaires pour une compacité maximale */
            #order_data .order_data_column .form-field br {
                display: none;
            }
            
            #order_data .order_data_column .form-field > p {
                margin: 0 0 2px 0;
            }
            
            /* Optimiser les espacements des éléments de formulaire */
            #order_data .order_data_column .form-field .description {
                font-size: 10px;
                margin: 1px 0 2px 0;
                line-height: 1.2;
                color: #666;
            }
            
            /* Réduire l'espacement des groupes de boutons */
            #order_data .order_data_column .form-field .button-group,
            #order_data .order_data_column .form-field .form-field-actions {
                margin: 2px 0;
                gap: 2px;
            }
            
            /* Compacter les liens et actions */
            #order_data .order_data_column a {
                line-height: 1.2;
                font-size: 11px;
            }
            
            /* Réduire l'espacement des dividers */
            #order_data .order_data_column hr {
                margin: 4px 0;
            }
            
            /* Optimiser l'affichage des notes et commentaires */
            #order_data .order_data_column .note,
            #order_data .order_data_column .order_note {
                margin: 2px 0;
                padding: 2px 4px;
                font-size: 10px;
                line-height: 1.2;
            }
            
            /* Adaptations mobiles */
            @media screen and (max-width: 782px) {
                #order_data .order_data_column_container {
                    flex-direction: column;
                }
                
                #order_data .order_data_column {
                    border-right: none;
                    border-bottom: 1px solid var(--fs-border-color);
                    padding: 6px 8px;
                }
                
                #order_data .order_data_column:last-child {
                    border-bottom: none;
                }
                
                #order_data .order_data_column .form-field-row .form-field {
                    flex: 1 1 100%;
                }
                
                #order_data .order_data_column h3 {
                    font-size: 13px;
                }
                
                #order_data .order_data_column .form-field input,
                #order_data .order_data_column .form-field select {
                    height: 20px;
                    font-size: 10px;
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Restructurer le contenu pour aligner état et client sur la même ligne
            var $etatField = $('#order_data .order_data_column:first-child').find('p').eq(2).prev('label').parent();
            var $clientField = $('#order_data .order_data_column:first-child').find('p').eq(3).prev('label').parent();
            
            // Créer un conteneur pour les deux champs
            var $rowContainer = $('<div class="form-field-row"></div>');
            
            // Convertir les paragraphes en div avec classe form-field
            $etatField.wrap('<div class="form-field"></div>');
            $clientField.wrap('<div class="form-field"></div>');
            
            // Déplacer les éléments dans le conteneur
            $etatField = $etatField.parent();
            $clientField = $clientField.parent();
            
            $etatField.add($clientField).wrapAll($rowContainer);
            
            // Restructurer le titre principal pour un meilleur design
            var headerTitle = $('.woocommerce-order-data__heading').text();
            var commandeNum = headerTitle.match(/n°(\d+)/);
            var ipAddress = $('p:contains("Adresse IP")').text();
            
            if (commandeNum && commandeNum[1]) {
                // Créer un nouveau titre avec style amélioré
                var titleHtml = '<div class="commande-title">' +
                                '<span class="commande-numero">Détails Commande n°' + commandeNum[1] + '</span>' +
                                '<span class="commande-ip">' + ipAddress + '</span>' +
                                '</div>';
                
                // Remplacer l'ancien titre
                $('.woocommerce-order-data__heading').replaceWith(titleHtml);
                $('p:contains("Adresse IP")').remove();
            }
            
            // Améliorer la présentation de la date de création
            var $dateCreation = $('#order_data .order_data_column:first-child').find('p').eq(0);
            var $dateLabel = $dateCreation.prev('label');
            
            if ($dateCreation.find('input').length) {
                $dateCreation.addClass('date-creation');
                $dateLabel.insertBefore($dateCreation);
            }
            
            // Ne plus modifier le texte Expédition en JS (maintenant fait en CSS)
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'fs_woocommerce_order_details_styles');

/**
 * Plugin Name: FS - Amélioration Détails Produits WooCommerce
 * Description: Redesign de la section produits dans les commandes WooCommerce
 * Version: 1.6
 * Author: Formation Stratégique
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute les styles CSS et JavaScript pour la section des produits de commande
 */
function fs_woocommerce_order_products_styles() {
    // Ne charger les styles que sur les pages d'édition de commande WooCommerce
    $screen = get_current_screen();
    if ($screen && $screen->id === 'shop_order') {
        ?>
        <style>
            /* Variables de couleurs cohérentes avec votre style existant */
            :root {
                --fs-primary-color: #4a6fdc;
                --fs-primary-hover: #3a5ecc;
                --fs-success-color: #28a745;
                --fs-warning-color: #ffc107;
                --fs-danger-color: #dc3545;
                --fs-info-color: #17a2b8;
                --fs-light-color: #f8f9fa;
                --fs-dark-color: #343a40;
                --fs-border-color: #dee2e6;
                --fs-text-color: #333;
                --fs-bg-hover: #f8f9fa;
                --fs-header-bg: #d5ebff;
            }
            
            /* Table des produits */
            .woocommerce_order_items_wrapper {
                margin-bottom: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                border-radius: 5px;
                overflow: hidden;
            }
            
            /* En-tête de la table - Fond bleu */
            table.woocommerce_order_items thead th {
                background-color: var(--fs-header-bg) !important;
                border-bottom: 1px solid var(--fs-border-color);
                padding: 8px 10px;
                font-weight: 600;
                font-size: 13px;
                text-align: left;
                color: var(--fs-dark-color);
                border-bottom: 2px solid #4a6fdc !important;
            }
            
            /* Conserver la colonne thumb mais la rendre invisible */
            table.woocommerce_order_items .thumb {
                width: 1px !important;
                padding: 0 !important;
                overflow: hidden;
                visibility: hidden;
            }
            
            /* Style des lignes */
            table.woocommerce_order_items td {
                padding: 3px;
                vertical-align: middle;
                border-bottom: 1px solid var(--fs-border-color);
                font-size: 13px;
            }
            
            table.woocommerce_order_items tr:last-child td {
                border-bottom: none;
            }
            
            /* Style compact pour les détails produit */
            table.woocommerce_order_items .item {
                padding: 3px;
            }
            
            /* Réduire le padding spécifiquement pour les lignes de frais */
            table.woocommerce_order_items tr.fee td {
                padding: 3px !important;
                line-height: 1.2;
            }
            
            /* Titre du produit */
            table.woocommerce_order_items .name a.wc-order-item-name {
                font-weight: 600;
                color: var(--fs-primary-color);
                text-decoration: none;
                display: block;
                margin-bottom: 3px;
            }
            
            table.woocommerce_order_items .name a.wc-order-item-name:hover {
                text-decoration: underline;
            }
            
            /* SKU et code */
            table.woocommerce_order_items .name .wc-order-item-sku,
            table.woocommerce_order_items .name .item-meta {
                font-size: 12px;
                color: #666;
                display: block;
                margin-bottom: 2px;
            }
            
            /* Organisation des informations de session en grille - 2 colonnes */
            table.woocommerce_order_items .name .session-details {
                display: grid;
                grid-template-columns: 1fr 2fr;
                grid-gap: 3px 5px;
                margin-top: 5px;
                font-size: 12px;
            }
            
            table.woocommerce_order_items .name .session-details > span {
                display: contents;
            }
            
            table.woocommerce_order_items .name .session-details .column-left,
            table.woocommerce_order_items .name .session-details .column-right {
                display: grid;
                grid-template-columns: 80px 1fr;
                grid-gap: 2px 3px;
            }
            
            table.woocommerce_order_items .name .session-details .label {
                font-weight: 600;
                color: #555;
            }
            
            table.woocommerce_order_items .name .session-details .value {
                color: #333;
            }
            
            /* Alignement des colonnes numériques */
            table.woocommerce_order_items .line_cost,
            table.woocommerce_order_items .quantity,
            table.woocommerce_order_items .line_tax {
                text-align: right;
                white-space: nowrap;
            }
            
            /* Mise en évidence au survol */
            table.woocommerce_order_items tbody tr:hover {
                background-color: var(--fs-bg-hover);
            }
            
            /* Style du pied de tableau - Design condensé sur une ligne */
            table.woocommerce_order_items tfoot {
                background-color: #f8f9fa;
                border-top: 2px solid var(--fs-primary-color);
            }
            
            /* Style pour l'affichage condensé des totaux */
            .wc-order-data-row {
                position: relative;
            }
            
            /* Masquer le contenu original de la dernière ligne de totaux */
            .wc-order-data-row:last-child {
                min-height: 50px;
            }
            
            /* Style pour l'affichage condensé personnalisé */
            .condensed-totals {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 4px 8px;
                background: #f8f9fa;
                border: none;
                border-radius: 0;
                margin: 5px 0;
                box-shadow: none;
            }
            
            .condensed-totals .details {
                font-size: 13px;
                color: var(--fs-primary-color);
                font-weight: 500;
            }
            
            .condensed-totals .total {
                font-size: 16px;
                font-weight: bold;
                color: #28a745;
            }
            
            /* Masquer les totaux originaux pour éviter la duplication */
            .wc-order-totals-items {
                display: none !important;
            }
            
            /* Masquer le tableau des totaux dans le footer */
            .wc-order-totals table,
            .woocommerce-order-totals table {
                display: none !important;
            }
            
            /* Masquer les lignes de totaux dans le tfoot du tableau principal */
            #woocommerce-order-items table.woocommerce_order_items tfoot {
                display: none !important;
            }
            
            /* Classe pour masquer les lignes de totaux en double */
            .hide-duplicate-totals {
                display: none !important;
            }
            
            /* Masquer visuellement les lignes individuelles des totaux mais les garder accessibles */
            table.woocommerce_order_items tfoot tr {
                visibility: hidden;
                height: 0;
                line-height: 0;
                padding: 0;
                border: none;
            }
            
            table.woocommerce_order_items tfoot tr th,
            table.woocommerce_order_items tfoot tr td {
                padding: 0;
                height: 0;
                line-height: 0;
                border: none;
            }
            
            /* Afficher uniquement la dernière ligne (total) mais la transformer */
            table.woocommerce_order_items tfoot tr:last-child {
                visibility: visible;
                height: auto;
                line-height: normal;
                background-color: #f8f9fa;
            }
            
            table.woocommerce_order_items tfoot tr:last-child th {
                text-align: left;
                padding: 12px 15px;
                font-weight: 600;
                font-size: 13px;
                border: none;
                position: relative;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            table.woocommerce_order_items tfoot tr:last-child td {
                text-align: right;
                padding: 12px 15px;
                font-weight: 700;
                font-size: 14px;
                border: none;
                position: relative;
                white-space: nowrap;
            }
            
            /* Style des boutons */            
            
            .wc-order-data-row .button.calculate-action {
                background-color: var(--fs-success-color);
                border-color: var(--fs-success-color);
                color: white;
            }
            
            .wc-order-data-row .button.calculate-action:hover {
                background-color: #218838;
                border-color: #1e7e34;
            }
            
            /* Réduire l'espacement vertical */
            #woocommerce-order-items .inside {
                padding: 0;
                margin: 0;
            }
            
            #woocommerce-order-items .wc-order-data-row {
                padding: 3px;
                line-height: 1.8em;
            }
            
            /* Factbox footer */
            .wc-order-data-row {
                display: flex;
                justify-content: space-between;
                padding: 6px;
                border-top: 1px solid var(--fs-success-color);
            }
            
            /* Compact view pour les infos */
            .compact-info {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                font-size: 12px;
            }
            
            .compact-info .info-group {
                display: flex;
                align-items: center;
            }
            
            .compact-info .info-label {
                font-weight: 600;
                margin-right: 5px;
                color: #555;
            }
            
            .compact-info .info-value {
                color: #333;
            }
            
            /* Correctif pour pied de page de commande (alignement) */
            .woocommerce-order-payment .label,
            .woocommerce-order-payment dt {
                text-align: right !important;
                padding-right: 5px !important;
            }
            
            .woocommerce-order-payment dd,
            .woocommerce-order-payment .total {
                text-align: right !important;
            }
            
            /* Traduction de "Paid" en "Payé" */
            .wc-order-totals tr:last-child th:contains("Paid") {
                text-align: right;
            }

            /* Masquer les métadonnées originales */
            .wc-order-item-meta {
                display: none !important;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Variables pour éviter les rechargements en boucle
            var isEnhancingOrderItems = false;
            var itemsEnhanced = {};
            var ajaxRequests = {};
            
            // Fonction pour obtenir l'ID de commande actuelle
            function getCurrentOrderId() {
                return $('.post-type-shop_order #post_ID').val() || 
                       $('.post-type-shop_order input[name="post_ID"]').val() || 
                       window.location.pathname.match(/post=(\d+)/)?.[1];
            }
            
            // Variable pour éviter les créations multiples
            var condensedTotalsCreated = false;
            
            // Fonction pour créer l'affichage condensé des totaux
            function createCondensedTotals() {
                // Éviter les créations multiples
                if (condensedTotalsCreated || $('.condensed-totals').length > 0) {
                    return;
                }
                
                condensedTotalsCreated = true;
                
                setTimeout(function() {
                    // Masquer les lignes de totaux en double
                    $('.wc-order-data-row').each(function() {
                        var $row = $(this);
                        var text = $row.text().toLowerCase();
                        
                        // Masquer les lignes contenant des totaux mais pas les boutons
                        if ((text.includes('sous-total') || text.includes('frais') || text.includes('tva') || text.includes('total de la commande')) && !$row.find('button, .button').length) {
                            $row.addClass('hide-duplicate-totals');
                        }
                    });
                    
                    var totalsData = {
                        subtotal: '',
                        fees: '',
                        tax: '',
                        total: ''
                    };
                    
                    // Récupérer les données depuis le texte visible de la page
                    var pageText = $('#woocommerce-order-items').text();
                    
                    // Extraire le sous-total
                    var subtotalMatch = pageText.match(/Sous-total des articles[\s\S]*?([0-9,]+[.,][0-9]{2})/i);
                    if (subtotalMatch) totalsData.subtotal = subtotalMatch[1] + '€';
                    
                    // Extraire les frais
                    var feesMatch = pageText.match(/Frais[\s\S]*?([0-9,]+[.,][0-9]{2})/i);
                    if (feesMatch) totalsData.fees = feesMatch[1] + '€';
                    
                    // Extraire la TVA
                    var taxMatch = pageText.match(/TVA[\s\S]*?([0-9,]+[.,][0-9]{2})/i);
                    if (taxMatch) totalsData.tax = taxMatch[1] + '€';
                    
                    // Extraire le total
                    var totalMatch = pageText.match(/Total de la commande[\s\S]*?([0-9,]+[.,][0-9]{2})/i);
                    if (totalMatch) totalsData.total = totalMatch[1] + '€';
                    
                    // Si on n'a pas trouvé le total, essayer une autre approche
                    if (!totalsData.total) {
                        var allAmounts = pageText.match(/([0-9,]+[.,][0-9]{2})/g);
                        if (allAmounts && allAmounts.length > 0) {
                            totalsData.total = allAmounts[allAmounts.length - 1] + '€';
                        }
                    }
                    
                    // Créer le contenu condensé avec couleurs
                    var condensedContent = '';
                    if (totalsData.subtotal) condensedContent += '<span style="color: var(--fs-primary-color);">Sous-total: ' + totalsData.subtotal + '</span>';
                    if (totalsData.fees) {
                        if (condensedContent) condensedContent += ' | ';
                        condensedContent += '<span style="color: var(--fs-primary-color);">Frais: ' + totalsData.fees + '</span>';
                    }
                    if (totalsData.tax) {
                        if (condensedContent) condensedContent += ' | ';
                        condensedContent += '<span style="color: var(--fs-primary-color);">TVA: ' + totalsData.tax + '</span>';
                    }
                    
                    // Créer et insérer l'affichage condensé
                    if (totalsData.total) {
                        var $condensedDiv = $('<div class="condensed-totals">');
                        $condensedDiv.html(
                            '<div class="details">' + (condensedContent || 'Détails de la commande') + '</div>' +
                            '<div class="total">Total: ' + totalsData.total + '</div>'
                        );
                        
                        // Insérer après le tableau des items
                        $('#woocommerce-order-items table.woocommerce_order_items').after($condensedDiv);
                    }
                }, 200);
            }
            
            // Fonction pour récupérer les métadonnées d'item depuis le serveur
            function getOrderItemMetadataFromServer(itemId, orderId, callback) {
                // Annuler la requête précédente si elle existe
                if (ajaxRequests[itemId]) {
                    ajaxRequests[itemId].abort();
                }
                
                // Créer une nouvelle requête
                ajaxRequests[itemId] = $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fs_get_order_item_details',
                        item_id: itemId,
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('fs-order-item-details'); ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        delete ajaxRequests[itemId];
                        callback(response);
                    },
                    error: function(xhr, status, error) {
                        delete ajaxRequests[itemId];
                        if (status !== 'abort') {
                            callback({success: false, error: error});
                        }
                    }
                });
                
                return ajaxRequests[itemId];
            }
            
            // Améliorer l'affichage des produits dans la commande
            function enhanceOrderItems() {
                // Éviter les appels récursifs
                if (isEnhancingOrderItems) {
                    return;
                }
                
                isEnhancingOrderItems = true;
                
                var orderId = getCurrentOrderId();
                if (!orderId) {
                    console.error('Impossible de déterminer l\'ID de commande');
                    isEnhancingOrderItems = false;
                    return;
                }
                
                // Masquer les métadonnées originales
                $('#woocommerce-order-items .wc-order-item-meta, #woocommerce-order-items .display_meta').hide();
                
                // Styliser le bouton Recalculer
                $('.wc-order-data-row .button[name="calc_totals"]').addClass('calculate-action');
                
                // Ajuster les couleurs de l'en-tête
                $('#woocommerce-order-items table.woocommerce_order_items thead th').css({
                    'background-color': '#d5ebff',
                    'border-bottom': '2px solid #4a6fdc',
                    'font-weight': '600',
                    'color': '#333'
                });
                
                // Réduire le padding des totaux
                $('#woocommerce-order-items table.woocommerce_order_items tfoot th, #woocommerce-order-items table.woocommerce_order_items tfoot td').css({
                    'padding': '5px 10px'
                });
                
                // Traduire "Paid" en "Payé"
                $('.wc-order-totals tr:last-child th:contains("Paid")').text('Payé:');
                
                // Créer l'affichage condensé des totaux
                createCondensedTotals();
                
                // Parcourir chaque ligne de produit
                $('#woocommerce-order-items table.woocommerce_order_items tbody tr.item').each(function() {
                    var $row = $(this);
                    var itemId = $row.attr('data-order_item_id');
                    
                    if (!itemId || itemsEnhanced[itemId]) {
                        return; // Item déjà traité ou sans ID
                    }
                    
                    // Marquer cet item comme traité
                    itemsEnhanced[itemId] = true;
                    
                    // Cacher la cellule d'image
                    $row.find('td.thumb').empty().css({
                        'width': '1px',
                        'padding': '0',
                        'visibility': 'hidden'
                    });
                    
                    // Supprimer les anciens conteneurs de détails s'ils existent
                    var $nameCell = $row.find('td.name');
                    $nameCell.find('.session-details').remove();
                    
                    // Ajouter un conteneur pour les détails de session
                    var sessionDetailsId = 'session-details-' + itemId;
                    $nameCell.append('<div id="' + sessionDetailsId + '" class="session-details"><div style="grid-column: span 2;">Chargement des détails...</div></div>');
                    
                    // Récupérer les métadonnées via AJAX
                    getOrderItemMetadataFromServer(itemId, orderId, function(response) {
                        if (response.success && response.data) {
                            var metadata = response.data;
                            var detailsHtml = '';
                            
                            // Organisation en 2 colonnes
                            var leftColumn = '';
                            var rightColumn = '';
                            
                            // Colonne gauche
                            if (metadata['Niveau']) {
                                leftColumn += '<span class="label">Niveau:</span>';
                                leftColumn += '<span class="value">' + metadata['Niveau'] + '</span>';
                            }
                            
                            if (metadata['Début']) {
                                leftColumn += '<span class="label">Début:</span>';
                                leftColumn += '<span class="value">' + metadata['Début'] + '</span>';
                            }
                            
                            if (metadata['Session n°']) {
                                leftColumn += '<span class="label">Session n°:</span>';
                                leftColumn += '<span class="value">' + metadata['Session n°'] + '</span>';
                            }
                            
                            // Colonne droite
                            if (metadata['Fin']) {
                                rightColumn += '<span class="label">Fin:</span>';
                                rightColumn += '<span class="value">' + metadata['Fin'] + '</span>';
                            }
                            
                            if (metadata['Adresse']) {
                                rightColumn += '<span class="label">Adresse:</span>';
                                rightColumn += '<span class="value">' + metadata['Adresse'] + '</span>';
                            }
                            
                            // Combiner les colonnes
                             detailsHtml = '<div class="column-left">' + leftColumn + '</div><div class="column-right">' + rightColumn + '</div>';
                             
                             if (detailsHtml === '<div class="column-left"></div><div class="column-right"></div>') {
                                detailsHtml = '<div style="grid-column: span 2;">Aucun détail supplémentaire disponible.</div>';
                            }
                            
                            $('#' + sessionDetailsId).html(detailsHtml);
                        } else {
                            $('#' + sessionDetailsId).html('<div style="grid-column: span 2;">Impossible de récupérer les détails.</div>');
                        }
                    });
                });
                
                isEnhancingOrderItems = false;
            }
            
            // Exécuter l'amélioration initiale
            setTimeout(function() {
                enhanceOrderItems();
            }, 300);
            
            // Réexécuter après certaines actions AJAX, mais pas toutes
            var ajaxCompleteTimeout;
            $(document).ajaxComplete(function(event, xhr, settings) {
                // Vérifier que c'est un AJAX spécifique qui modifie la commande
                if (settings.url && settings.url.indexOf('admin-ajax.php') > -1 && 
                   (settings.data && (
                    settings.data.indexOf('woocommerce_calc_line_taxes') > -1 ||
                    settings.data.indexOf('woocommerce_save_order_items') > -1 ||
                    settings.data.indexOf('woocommerce_add_order_item') > -1 ||
                    settings.data.indexOf('woocommerce_remove_order_item') > -1
                   ))) {
                    
                    // Annuler le timeout précédent s'il existe
                    clearTimeout(ajaxCompleteTimeout);
                    
                    // Réinitialiser les variables de contrôle
                    itemsEnhanced = {};
                    condensedTotalsCreated = false;
                    
                    // Supprimer l'ancien affichage condensé
                    $('.condensed-totals').remove();
                    
                    // Réappliquer après un délai
                    ajaxCompleteTimeout = setTimeout(function() {
                        enhanceOrderItems();
                    }, 300);
                }
            });
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'fs_woocommerce_order_products_styles');

/**
 * Fonction AJAX pour récupérer les détails d'un item de commande
 */
function fs_get_order_item_details() {
    check_ajax_referer('fs-order-item-details', 'security');
    
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }
    
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$item_id || !$order_id) {
        wp_send_json_error('Paramètres manquants');
        return;
    }
    
    // Récupérer la commande
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Commande introuvable');
        return;
    }
    
    // Récupérer l'item spécifique
    $item = $order->get_item($item_id);
    if (!$item) {
        wp_send_json_error('Item de commande introuvable');
        return;
    }
    
    // Récupérer toutes les métadonnées liées à cet item
    $metadata = $item->get_meta_data();
    $formatted_metadata = array();
    
    // Parcourir toutes les métadonnées et récupérer celles qui nous intéressent
    foreach ($metadata as $meta) {
        $data = $meta->get_data();
        $key = $data['key'];
        $value = $data['value'];
        
        // Correspondance directe des clés
        if ($key === 'fsbdd_niveau') {
            $formatted_metadata['Niveau'] = $value;
        } elseif ($key === 'we_startdate') {
            $formatted_metadata['Début'] = $value;
        } elseif ($key === 'we_enddate') {
            $formatted_metadata['Fin'] = $value;
        } elseif ($key === 'fsbdd_actionum') {
            $formatted_metadata['Session n°'] = $value;
        } elseif ($key === 'fsbdd_select_lieuforminter') {
            $formatted_metadata['Adresse'] = $value;
        }
    }
    
    // Si nous n'avons pas trouvé certaines métadonnées, essayer de les récupérer depuis le CPT
    if (!isset($formatted_metadata['Niveau']) || !isset($formatted_metadata['Début']) || 
        !isset($formatted_metadata['Fin']) || !isset($formatted_metadata['Session n°']) || 
        !isset($formatted_metadata['Adresse'])) {
        
        // Récupérer l'ID de l'action de formation associée
        $action_id = $item->get_meta('fsbdd_relsessaction_cpt_produit');
        
        if ($action_id) {
            // Récupérer les informations depuis le CPT
            if (!isset($formatted_metadata['Niveau'])) {
                $niveau = get_post_meta($action_id, 'fsbdd_select_niveausession', true);
                if ($niveau) {
                    $formatted_metadata['Niveau'] = $niveau;
                }
            }
            
            if (!isset($formatted_metadata['Début'])) {
                $startdate = get_post_meta($action_id, 'we_startdate', true);
                if ($startdate) {
                    $formatted_metadata['Début'] = date_i18n('j F Y', $startdate);
                }
            }
            
            if (!isset($formatted_metadata['Fin'])) {
                $enddate = get_post_meta($action_id, 'we_enddate', true);
                if ($enddate) {
                    $formatted_metadata['Fin'] = date_i18n('j F Y', $enddate);
                }
            }
            
            if (!isset($formatted_metadata['Session n°'])) {
                $numero = get_the_title($action_id);
                if ($numero) {
                    $formatted_metadata['Session n°'] = $numero;
                }
            }
            
            if (!isset($formatted_metadata['Adresse'])) {
                $lieu = get_post_meta($action_id, 'fsbdd_select_lieusession', true);
                if ($lieu) {
                    $formatted_metadata['Adresse'] = $lieu;
                }
            }
        }
    }
    
    // Renvoyer les métadonnées
    wp_send_json_success($formatted_metadata);
}
add_action('wp_ajax_fs_get_order_item_details', 'fs_get_order_item_details');