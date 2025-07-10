<?php
/**
 * Snippet ID: 67
 * Name: TAMPON VALIDÉ CERTIFIE SUR DOCS DEPUIS CPT ACTION DE FORMATION
 * Description: 
 * @active true
 */

// Ajouter un tampon sur les images
function apply_stamp_to_image($file_path) {
    try {
        // Vérifier si le fichier existe
        if (!file_exists($file_path)) {
            error_log('Tampon : Fichier image introuvable - ' . $file_path);
            return false;
        }
        
        // Vérifier si Imagick est disponible
        if (!class_exists('Imagick')) {
            error_log('Tampon : Extension Imagick non disponible');
            return false;
        }
        
        // Créer l'objet Imagick
        $imagick = new \Imagick($file_path);
        
        // Récupérer les dimensions de l'image
        $image_width = $imagick->getImageWidth();
        $image_height = $imagick->getImageHeight();
        
        error_log("Tampon image : Dimensions détectées - {$image_width}x{$image_height}");
        
        // Paramètres ajustés pour le tampon - TAILLE AUGMENTÉE DE 50%
        $stamp_width = max(600, min($image_width * 0.9, 1200));
        $stamp_height = max(240, min($image_height * 0.18, 300));
        
        // Taille de la police légèrement augmentée
        $font_size = max(32, min(42, $image_height * 0.03));
        
        // Informations dynamiques - AJOUT DU NOM COMPLET avec vérifications
        $current_date = date('d/m/Y');
        $author = wp_get_current_user();
        
        // Construire le nom de l'auteur de manière sécurisée
        $author_name = '';
        if (!empty($author->first_name) || !empty($author->last_name)) {
            $author_name = trim($author->first_name . ' ' . $author->last_name);
        }
        if (empty($author_name) && !empty($author->display_name)) {
            $author_name = $author->display_name;
        }
        if (empty($author_name)) {
            $author_name = 'Utilisateur';
        }
        
        $text = "Certifié conforme le : $current_date, par $author_name";
        error_log("Tampon image : Texte généré - $text");
        
        // Créer le tampon AVEC FOND COMPLÈTEMENT TRANSPARENT
        $stamp = new \Imagick();
        $stamp->newImage($stamp_width, $stamp_height, new \ImagickPixel('transparent'));
        $stamp->setImageFormat('png');
        
        // AJOUT D'UN CADRE FIN AUTOUR DU TAMPON
        $border = new \ImagickDraw();
        $border->setStrokeColor(new \ImagickPixel('red'));
        $border->setStrokeWidth(1);
        $border->setFillColor(new \ImagickPixel('transparent'));
        $border->rectangle(0, 0, $stamp_width - 1, $stamp_height - 1);
        $stamp->drawImage($border);
        
        // Charger l'image du tampon avec plusieurs chemins possibles
        $stamp_image_paths = [
            ABSPATH . '/wp-content/uploads/Tampon-Signature-FS.png',
            WP_CONTENT_DIR . '/uploads/Tampon-Signature-FS.png',
            ABSPATH . 'wp-content/uploads/Tampon-Signature-FS.png'
        ];
        
        $stamp_image_loaded = false;
        foreach ($stamp_image_paths as $stamp_image_path) {
            if (file_exists($stamp_image_path)) {
                try {
                    $stamp_image = new \Imagick($stamp_image_path);
                    
                    // Redimensionner l'image du tampon (TAILLE AUGMENTÉE DE 50%)
                    $stamp_image_height = $stamp_height * 0.7; // Augmenté de 0.6 à 0.7
                    $stamp_image->resizeImage(0, $stamp_image_height, \Imagick::FILTER_LANCZOS, 1);
                    
                    // ALIGNER À DROITE du tampon
                    $stamp_image_x = $stamp_width - $stamp_image->getImageWidth() - 20;
                    $stamp_image_y = 15;
                    
                    // Positionner l'image du tampon
                    $stamp->compositeImage($stamp_image, \Imagick::COMPOSITE_OVER, 
                                          $stamp_image_x, $stamp_image_y);
                    $stamp_image->clear();
                    $stamp_image_loaded = true;
                    error_log("Tampon image : Image du tampon chargée depuis - $stamp_image_path");
                    break;
                } catch (Exception $e) {
                    error_log("Tampon image : Erreur lors du chargement de l'image du tampon depuis $stamp_image_path - " . $e->getMessage());
                }
            }
        }
        
        if (!$stamp_image_loaded) {
            error_log("Tampon image : Aucune image de tampon trouvée, continuation sans image");
        }
        
        // PLACER LE TEXTE SOUS L'IMAGE DU TAMPON
        $draw = new \ImagickDraw();
        $draw->setFillColor('red');
        $draw->setFontSize($font_size);
        $draw->setFontWeight(700);
        
        // Mesurer la largeur approximative du texte (estimation)
        $text_width = $font_size * strlen($text) * 0.5;
        
        // Centrer le texte dans le tampon
        $text_x = max(15, ($stamp_width - $text_width) / 2);
        $text_y = $stamp_height - 35; // Positionné en bas du tampon
        
        $draw->annotation($text_x, $text_y, $text);
        $stamp->drawImage($draw);
        
        // POSITIONNER LE TAMPON EN BAS À DROITE
        $x_position = $image_width - $stamp_width - 50;
        $y_position = $image_height - $stamp_height - 50;
        
        // Appliquer le tampon
        $imagick->compositeImage($stamp, \Imagick::COMPOSITE_OVER, $x_position, $y_position);
        
        // Sauvegarde
        $imagick->writeImage($file_path);
        
        // Libérer les ressources
        $imagick->clear();
        $stamp->clear();
        
        error_log("Tampon image : Tampon appliqué avec succès sur $file_path");
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'ajout du tampon sur l\'image : ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return false;
    }
}

// Ajouter un tampon sur les PDFs
function apply_stamp_to_pdf($file_path) {
    try {
        // Vérifier si le fichier existe
        if (!file_exists($file_path)) {
            error_log('Tampon : Fichier PDF introuvable - ' . $file_path);
            return false;
        }
        
        // Vérifier si Imagick est disponible
        if (!class_exists('Imagick')) {
            error_log('Tampon : Extension Imagick non disponible');
            return false;
        }
        
        // Augmenter la limite mémoire temporairement
        $original_memory_limit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        
        // Initialiser Imagick avec résolution réduite pour économiser la mémoire
        $imagick = new \Imagick();
        $imagick->setResolution(150, 150); // Réduit de 300 à 150 pour économiser la mémoire
        $imagick->readImage($file_path);
        
        $page_count = $imagick->getNumberImages();
        error_log("Tampon PDF : Traitement de $page_count page(s)");
        
        // Informations dynamiques - AJOUT DU NOM COMPLET avec vérifications
        $current_date = date('d/m/Y');
        $author = wp_get_current_user();
        
        // Construire le nom de l'auteur de manière sécurisée
        $author_name = '';
        if (!empty($author->first_name) || !empty($author->last_name)) {
            $author_name = trim($author->first_name . ' ' . $author->last_name);
        }
        if (empty($author_name) && !empty($author->display_name)) {
            $author_name = $author->display_name;
        }
        if (empty($author_name)) {
            $author_name = 'Utilisateur';
        }
        
        $text = "Certifié conforme le : $current_date, par $author_name";
        error_log("Tampon PDF : Texte généré - $text");
        
        // Charger l'image du tampon avec plusieurs chemins possibles
        $stamp_image_paths = [
            ABSPATH . '/wp-content/uploads/Tampon-Signature-FS.png',
            WP_CONTENT_DIR . '/uploads/Tampon-Signature-FS.png',
            ABSPATH . 'wp-content/uploads/Tampon-Signature-FS.png'
        ];
        
        $base_stamp_image = null;
        foreach ($stamp_image_paths as $stamp_image_path) {
            if (file_exists($stamp_image_path)) {
                try {
                    $base_stamp_image = new \Imagick($stamp_image_path);
                    error_log("Tampon PDF : Image du tampon chargée depuis - $stamp_image_path");
                    break;
                } catch (Exception $e) {
                    error_log("Tampon PDF : Erreur lors du chargement de l'image du tampon depuis $stamp_image_path - " . $e->getMessage());
                }
            }
        }
        
        if (!$base_stamp_image) {
            error_log("Tampon PDF : Aucune image de tampon trouvée, continuation sans image");
        }
        
        // Créer un nouvel objet Imagick pour le résultat
        $result = new \Imagick();
        
        // Traiter chaque page individuellement
        for ($i = 0; $i < $page_count; $i++) {
            try {
                // Récupérer une page spécifique
                $imagick->setIteratorIndex($i);
                $page = $imagick->getImage();
                $page->setImageFormat('pdf');
                
                // Récupérer les dimensions de la page
                $page_width = $page->getImageWidth();
                $page_height = $page->getImageHeight();
                
                error_log("Tampon PDF : Page " . ($i + 1) . " - Dimensions {$page_width}x{$page_height}");
                
                // Paramètres ajustés pour le tampon - TAILLE RÉDUITE pour économiser la mémoire
                $stamp_width = max(400, min($page_width * 0.6, 800));
                $stamp_height = max(160, min($page_height * 0.12, 200));
                
                // Taille de la police réduite
                $font_size = max(20, min(28, $page_height * 0.02));
                
                // Créer le tampon AVEC FOND COMPLÈTEMENT TRANSPARENT
                $stamp = new \Imagick();
                $stamp->newImage($stamp_width, $stamp_height, new \ImagickPixel('transparent'));
                $stamp->setImageFormat('png');
                
                // AJOUT D'UN CADRE FIN AUTOUR DU TAMPON
                $border = new \ImagickDraw();
                $border->setStrokeColor(new \ImagickPixel('red'));
                $border->setStrokeWidth(1);
                $border->setFillColor(new \ImagickPixel('transparent'));
                $border->rectangle(0, 0, $stamp_width - 1, $stamp_height - 1);
                $stamp->drawImage($border);
                
                // Ajouter l'image du tampon si disponible
                if ($base_stamp_image) {
                    $stamp_image_clone = clone $base_stamp_image;
                    
                    // Redimensionner l'image du tampon
                    $stamp_image_height = $stamp_height * 0.6;
                    $stamp_image_clone->resizeImage(0, $stamp_image_height, \Imagick::FILTER_LANCZOS, 1);
                    
                    // ALIGNER À DROITE du tampon
                    $stamp_image_x = $stamp_width - $stamp_image_clone->getImageWidth() - 15;
                    $stamp_image_y = 10;
                    
                    // Positionner l'image
                    $stamp->compositeImage($stamp_image_clone, \Imagick::COMPOSITE_OVER, 
                                          $stamp_image_x, $stamp_image_y);
                    $stamp_image_clone->destroy();
                }
                
                // PLACER LE TEXTE SOUS L'IMAGE DU TAMPON
                $draw = new \ImagickDraw();
                $draw->setFillColor('red');
                $draw->setFontSize($font_size);
                $draw->setFontWeight(700);
                
                // Mesurer la largeur approximative du texte
                $text_width = $font_size * strlen($text) * 0.5;
                
                // Centrer le texte dans le tampon
                $text_x = max(10, ($stamp_width - $text_width) / 2);
                $text_y = $stamp_height - 25; // Positionné en bas du tampon
                
                $draw->annotation($text_x, $text_y, $text);
                $stamp->drawImage($draw);
                
                // POSITIONNER LE TAMPON EN BAS À DROITE
                $x_position = $page_width - $stamp_width - 30;
                $y_position = $page_height - $stamp_height - 30;
                
                // Appliquer le tampon
                $page->compositeImage($stamp, \Imagick::COMPOSITE_OVER, $x_position, $y_position);
                
                // Ajouter la page au résultat
                $result->addImage($page);
                
                // Libérer les ressources de cette page
                $stamp->destroy();
                $page->destroy();
                
                // Forcer le garbage collection toutes les 5 pages
                if (($i + 1) % 5 === 0) {
                    gc_collect_cycles();
                    error_log("Tampon PDF : Nettoyage mémoire après page " . ($i + 1));
                }
                
            } catch (Exception $e) {
                error_log("Tampon PDF : Erreur sur la page " . ($i + 1) . " - " . $e->getMessage());
                // Continuer avec les autres pages même si une échoue
                continue;
            }
        }
        
        // Sauvegarder le résultat
        $result->setImageFormat('pdf');
        $result->writeImages($file_path, true);
        
        // Libérer toutes les ressources
        if ($base_stamp_image) {
            $base_stamp_image->destroy();
        }
        $imagick->destroy();
        $result->destroy();
        
        // Restaurer la limite mémoire originale
        ini_set('memory_limit', $original_memory_limit);
        
        error_log("Tampon PDF : Tampon appliqué avec succès sur $file_path ($page_count pages)");
        return true;
        
    } catch (Exception $e) {
        // Restaurer la limite mémoire en cas d'erreur
        if (isset($original_memory_limit)) {
            ini_set('memory_limit', $original_memory_limit);
        }
        
        error_log('Erreur lors de l\'ajout du tampon sur le PDF : ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return false;
    }
}

// Fonction de test pour vérifier la disponibilité d'Imagick
function test_imagick_availability() {
    if (!class_exists('Imagick')) {
        error_log('Test Imagick : Extension Imagick non disponible');
        return false;
    }
    
    try {
        $test = new \Imagick();
        $test->newImage(100, 100, new \ImagickPixel('white'));
        $test->setImageFormat('png');
        $test->clear();
        error_log('Test Imagick : Extension Imagick disponible et fonctionnelle');
        return true;
    } catch (Exception $e) {
        error_log('Test Imagick : Erreur lors du test - ' . $e->getMessage());
        return false;
    }
}