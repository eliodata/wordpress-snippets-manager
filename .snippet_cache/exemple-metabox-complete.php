<?php
/**
 * Exemple complet d'utilisation du snippet d'optimisation Meta Box
 * 
 * Ce fichier démontre comment créer une metabox complète avec tous les types
 * de champs supportés et comment tirer parti des styles optimisés.
 * 
 * À utiliser avec le snippet-metabox-styles.php
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Création d'une metabox d'exemple avec tous les types de champs
 */
add_filter('rwmb_meta_boxes', 'exemple_metabox_complete');

function exemple_metabox_complete($meta_boxes) {
    $meta_boxes[] = [
        'title' => 'Exemple Complet - Metabox Optimisée',
        'id' => 'exemple-metabox-complete',
        'post_types' => ['post', 'page'], // Ajustez selon vos besoins
        'context' => 'normal',
        'priority' => 'high',
        'autosave' => true,
        
        'fields' => [
            // =================================================================
            // SECTION : INFORMATIONS DE BASE
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Informations de Base',
                'desc' => 'Champs texte et informations principales'
            ],
            
            // Champ texte simple requis
            [
                'name' => 'Titre Principal',
                'id' => 'titre_principal',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Saisissez le titre principal...',
                'desc' => 'Ce champ est requis et bénéficie de la validation en temps réel',
                'size' => 50
            ],
            
            // Champ email avec validation
            [
                'name' => 'Email de Contact',
                'id' => 'email_contact',
                'type' => 'email',
                'placeholder' => 'exemple@domaine.com',
                'desc' => 'Adresse email avec validation automatique',
                'label_description' => 'Utilisé pour les notifications'
            ],
            
            // Champ URL
            [
                'name' => 'Site Web',
                'id' => 'site_web',
                'type' => 'url',
                'placeholder' => 'https://exemple.com',
                'desc' => 'URL complète avec protocole'
            ],
            
            // Champ numérique
            [
                'name' => 'Prix (€)',
                'id' => 'prix_produit',
                'type' => 'number',
                'min' => 0,
                'step' => 0.01,
                'placeholder' => '0.00',
                'desc' => 'Prix en euros avec 2 décimales'
            ],
            
            // Zone de texte
            [
                'name' => 'Description Détaillée',
                'id' => 'description_detaillee',
                'type' => 'textarea',
                'rows' => 5,
                'placeholder' => 'Décrivez en détail...',
                'desc' => 'Description complète du contenu'
            ],
            
            // =================================================================
            // SECTION : SÉLECTIONS ET CHOIX
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Sélections et Choix',
                'desc' => 'Listes déroulantes, cases à cocher et boutons radio'
            ],
            
            // Liste déroulante simple
            [
                'name' => 'Catégorie',
                'id' => 'categorie_produit',
                'type' => 'select',
                'placeholder' => 'Choisissez une catégorie...',
                'options' => [
                    '' => '-- Sélectionner --',
                    'electronique' => 'Électronique',
                    'vetements' => 'Vêtements',
                    'maison' => 'Maison & Jardin',
                    'sport' => 'Sport & Loisirs',
                    'livres' => 'Livres & Médias'
                ],
                'desc' => 'Catégorie principale du produit'
            ],
            
            // Liste déroulante multiple
            [
                'name' => 'Tags Associés',
                'id' => 'tags_associes',
                'type' => 'select',
                'multiple' => true,
                'options' => [
                    'nouveau' => 'Nouveau',
                    'populaire' => 'Populaire',
                    'promo' => 'En Promotion',
                    'limite' => 'Édition Limitée',
                    'eco' => 'Écologique',
                    'premium' => 'Premium'
                ],
                'desc' => 'Maintenez Ctrl/Cmd pour sélectionner plusieurs options'
            ],
            
            // Cases à cocher
            [
                'name' => 'Options Disponibles',
                'id' => 'options_disponibles',
                'type' => 'checkbox_list',
                'options' => [
                    'livraison_gratuite' => 'Livraison Gratuite',
                    'garantie_etendue' => 'Garantie Étendue',
                    'support_24h' => 'Support 24h/24',
                    'retour_gratuit' => 'Retour Gratuit 30 jours'
                ],
                'desc' => 'Sélectionnez toutes les options applicables'
            ],
            
            // Boutons radio
            [
                'name' => 'Niveau de Priorité',
                'id' => 'niveau_priorite',
                'type' => 'radio',
                'options' => [
                    'basse' => 'Basse',
                    'normale' => 'Normale',
                    'haute' => 'Haute',
                    'urgente' => 'Urgente'
                ],
                'std' => 'normale',
                'desc' => 'Définit l\'importance de cet élément'
            ],
            
            // Case à cocher simple
            [
                'name' => 'Publier Immédiatement',
                'id' => 'publier_immediatement',
                'type' => 'checkbox',
                'desc' => 'Cochez pour publier automatiquement',
                'std' => 0
            ],
            
            // =================================================================
            // SECTION : DATES ET HEURES
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Dates et Heures',
                'desc' => 'Gestion des dates, heures et périodes'
            ],
            
            // Date simple
            [
                'name' => 'Date de Publication',
                'id' => 'date_publication',
                'type' => 'date',
                'desc' => 'Date de mise en ligne prévue'
            ],
            
            // Date et heure
            [
                'name' => 'Date et Heure d\'Événement',
                'id' => 'datetime_evenement',
                'type' => 'datetime',
                'desc' => 'Date et heure précises de l\'événement'
            ],
            
            // Heure seule
            [
                'name' => 'Heure d\'Ouverture',
                'id' => 'heure_ouverture',
                'type' => 'time',
                'desc' => 'Heure d\'ouverture quotidienne'
            ],
            
            // =================================================================
            // SECTION : FICHIERS ET MÉDIAS
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Fichiers et Médias',
                'desc' => 'Upload d\'images, fichiers et galeries'
            ],
            
            // Image unique
            [
                'name' => 'Image Principale',
                'id' => 'image_principale',
                'type' => 'image_advanced',
                'max_file_uploads' => 1,
                'desc' => 'Image principale à afficher (format recommandé: 1200x800px)'
            ],
            
            // Galerie d'images
            [
                'name' => 'Galerie Photos',
                'id' => 'galerie_photos',
                'type' => 'image_advanced',
                'max_file_uploads' => 10,
                'desc' => 'Jusqu\'à 10 images pour la galerie'
            ],
            
            // Fichier unique
            [
                'name' => 'Document PDF',
                'id' => 'document_pdf',
                'type' => 'file_advanced',
                'max_file_uploads' => 1,
                'mime_type' => 'application/pdf',
                'desc' => 'Document PDF à télécharger (max 10MB)'
            ],
            
            // =================================================================
            // SECTION : CHAMPS AVANCÉS
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Champs Avancés',
                'desc' => 'Champs spécialisés et fonctionnalités avancées'
            ],
            
            // Éditeur WYSIWYG
            [
                'name' => 'Contenu Riche',
                'id' => 'contenu_riche',
                'type' => 'wysiwyg',
                'options' => [
                    'textarea_rows' => 8,
                    'teeny' => false,
                    'media_buttons' => true
                ],
                'desc' => 'Éditeur complet avec formatage'
            ],
            
            // Sélecteur de couleur
            [
                'name' => 'Couleur Principale',
                'id' => 'couleur_principale',
                'type' => 'color',
                'std' => '#0073aa',
                'desc' => 'Couleur utilisée pour le thème'
            ],
            
            // Slider/Range
            [
                'name' => 'Niveau de Qualité',
                'id' => 'niveau_qualite',
                'type' => 'range',
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'std' => 5,
                'desc' => 'Évaluez la qualité de 1 à 10'
            ],
            
            // =================================================================
            // SECTION : CHAMPS CLONABLES
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Champs Répétables',
                'desc' => 'Champs que vous pouvez dupliquer autant de fois que nécessaire'
            ],
            
            // Groupe de champs clonables
            [
                'name' => 'Caractéristiques Techniques',
                'id' => 'caracteristiques_techniques',
                'type' => 'group',
                'clone' => true,
                'sort_clone' => true,
                'max_clone' => 10,
                'add_button' => '+ Ajouter une Caractéristique',
                'fields' => [
                    [
                        'name' => 'Nom',
                        'id' => 'nom_caracteristique',
                        'type' => 'text',
                        'size' => 30,
                        'placeholder' => 'ex: Poids, Dimensions...'
                    ],
                    [
                        'name' => 'Valeur',
                        'id' => 'valeur_caracteristique',
                        'type' => 'text',
                        'size' => 30,
                        'placeholder' => 'ex: 2.5kg, 30x20x10cm...'
                    ],
                    [
                        'name' => 'Unité',
                        'id' => 'unite_caracteristique',
                        'type' => 'select',
                        'options' => [
                            '' => 'Aucune',
                            'kg' => 'Kilogrammes',
                            'g' => 'Grammes',
                            'cm' => 'Centimètres',
                            'm' => 'Mètres',
                            'l' => 'Litres',
                            'ml' => 'Millilitres'
                        ]
                    ]
                ],
                'desc' => 'Ajoutez autant de caractéristiques que nécessaire'
            ],
            
            // Champ texte clonable simple
            [
                'name' => 'Mots-clés SEO',
                'id' => 'mots_cles_seo',
                'type' => 'text',
                'clone' => true,
                'sort_clone' => true,
                'max_clone' => 5,
                'add_button' => '+ Ajouter un Mot-clé',
                'placeholder' => 'Mot-clé important...',
                'desc' => 'Ajoutez jusqu\'à 5 mots-clés pour le SEO'
            ],
            
            // =================================================================
            // SECTION : VALIDATION ET CONDITIONS
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Validation et Conditions',
                'desc' => 'Champs avec validation et affichage conditionnel'
            ],
            
            // Champ avec validation personnalisée
            [
                'name' => 'Code Produit',
                'id' => 'code_produit',
                'type' => 'text',
                'required' => true,
                'pattern' => '[A-Z]{2}[0-9]{4}',
                'placeholder' => 'ex: AB1234',
                'desc' => 'Format requis: 2 lettres majuscules + 4 chiffres',
                'label_description' => 'Code unique d\'identification'
            ],
            
            // Champ conditionnel
            [
                'name' => 'Type de Produit',
                'id' => 'type_produit',
                'type' => 'select',
                'options' => [
                    'physique' => 'Produit Physique',
                    'numerique' => 'Produit Numérique',
                    'service' => 'Service'
                ],
                'desc' => 'Sélectionnez le type de produit'
            ],
            
            // Champ affiché conditionnellement
            [
                'name' => 'Poids (kg)',
                'id' => 'poids_produit',
                'type' => 'number',
                'step' => 0.1,
                'min' => 0,
                'visible' => [
                    'when' => [['type_produit', '=', 'physique']],
                    'relation' => 'or'
                ],
                'desc' => 'Poids en kilogrammes (affiché uniquement pour les produits physiques)'
            ],
            
            // =================================================================
            // SECTION : INFORMATIONS SYSTÈME
            // =================================================================
            [
                'type' => 'heading',
                'name' => 'Informations Système',
                'desc' => 'Champs en lecture seule et informations automatiques'
            ],
            
            // Champ en lecture seule
            [
                'name' => 'Date de Création',
                'id' => 'date_creation_auto',
                'type' => 'datetime',
                'readonly' => true,
                'std' => date('Y-m-d H:i:s'),
                'desc' => 'Date de création automatique (lecture seule)'
            ],
            
            // Champ HTML personnalisé
            [
                'name' => 'Statut du Système',
                'id' => 'statut_systeme',
                'type' => 'custom_html',
                'std' => '<div class="notice notice-success inline"><p><strong>✓ Système opérationnel</strong><br>Tous les champs sont fonctionnels et optimisés.</p></div>',
                'desc' => 'Informations sur l\'état du système'
            ]
        ]
    ];
    
    return $meta_boxes;
}

/**
 * Validation personnalisée pour les champs
 */
add_filter('rwmb_validate', 'exemple_validation_personnalisee', 10, 3);

function exemple_validation_personnalisee($ret, $field, $new) {
    // Validation du code produit
    if ($field['id'] === 'code_produit' && !empty($new)) {
        if (!preg_match('/^[A-Z]{2}[0-9]{4}$/', $new)) {
            $ret = new WP_Error('invalid_code', 'Le code produit doit respecter le format: 2 lettres majuscules + 4 chiffres (ex: AB1234)');
        }
    }
    
    // Validation du prix
    if ($field['id'] === 'prix_produit' && !empty($new)) {
        if ($new < 0) {
            $ret = new WP_Error('invalid_price', 'Le prix ne peut pas être négatif');
        }
        if ($new > 999999) {
            $ret = new WP_Error('price_too_high', 'Le prix ne peut pas dépasser 999 999 €');
        }
    }
    
    return $ret;
}

/**
 * Sauvegarde personnalisée avec traitement des données
 */
add_action('rwmb_after_save_post', 'exemple_traitement_apres_sauvegarde');

function exemple_traitement_apres_sauvegarde($post_id) {
    // Générer automatiquement un slug basé sur le titre principal
    $titre_principal = get_post_meta($post_id, 'titre_principal', true);
    if (!empty($titre_principal)) {
        $slug = sanitize_title($titre_principal);
        update_post_meta($post_id, 'slug_auto', $slug);
    }
    
    // Calculer un score basé sur les données saisies
    $prix = (float) get_post_meta($post_id, 'prix_produit', true);
    $niveau_qualite = (int) get_post_meta($post_id, 'niveau_qualite', true);
    $options = get_post_meta($post_id, 'options_disponibles', true);
    
    $score = 0;
    if ($prix > 0) $score += 20;
    if ($niveau_qualite >= 7) $score += 30;
    if (is_array($options) && count($options) >= 2) $score += 25;
    if (!empty(get_post_meta($post_id, 'description_detaillee', true))) $score += 25;
    
    update_post_meta($post_id, 'score_completude', $score);
}

/**
 * Affichage des données sur le front-end
 */
function afficher_donnees_metabox($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    }
    
    $titre = get_post_meta($post_id, 'titre_principal', true);
    $prix = get_post_meta($post_id, 'prix_produit', true);
    $description = get_post_meta($post_id, 'description_detaillee', true);
    $caracteristiques = get_post_meta($post_id, 'caracteristiques_techniques', true);
    
    if (!empty($titre)) {
        echo '<h3>' . esc_html($titre) . '</h3>';
    }
    
    if (!empty($prix)) {
        echo '<p class="prix"><strong>Prix: ' . number_format($prix, 2, ',', ' ') . ' €</strong></p>';
    }
    
    if (!empty($description)) {
        echo '<div class="description">' . wpautop(esc_html($description)) . '</div>';
    }
    
    if (!empty($caracteristiques) && is_array($caracteristiques)) {
        echo '<h4>Caractéristiques Techniques:</h4>';
        echo '<ul class="caracteristiques">';
        foreach ($caracteristiques as $carac) {
            if (!empty($carac['nom_caracteristique']) && !empty($carac['valeur_caracteristique'])) {
                echo '<li><strong>' . esc_html($carac['nom_caracteristique']) . ':</strong> ';
                echo esc_html($carac['valeur_caracteristique']);
                if (!empty($carac['unite_caracteristique'])) {
                    echo ' ' . esc_html($carac['unite_caracteristique']);
                }
                echo '</li>';
            }
        }
        echo '</ul>';
    }
}

/**
 * Shortcode pour afficher les données
 */
add_shortcode('metabox_donnees', 'shortcode_metabox_donnees');

function shortcode_metabox_donnees($atts) {
    $atts = shortcode_atts([
        'post_id' => get_the_ID()
    ], $atts);
    
    ob_start();
    afficher_donnees_metabox($atts['post_id']);
    return ob_get_clean();
}

/**
 * Styles CSS pour l'affichage front-end
 */
add_action('wp_head', 'exemple_styles_frontend');

function exemple_styles_frontend() {
    echo '
    <style>
        .prix {
            color: #0073aa;
            font-size: 1.2em;
            margin: 10px 0;
        }
        
        .caracteristiques {
            list-style: none;
            padding: 0;
        }
        
        .caracteristiques li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .caracteristiques li:last-child {
            border-bottom: none;
        }
    </style>
    ';
}

?>