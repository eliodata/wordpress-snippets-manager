# Snippet d'Optimisation des Styles Meta Box

## Description

Ce snippet PHP optimise l'apparence et l'ergonomie des metabox créées avec le plugin Meta Box. Il s'appuie sur la documentation officielle de metabox.io et utilise les hooks et classes CSS recommandés.

## Fonctionnalités

### 🎨 Optimisations Visuelles
- **Espacement amélioré** : Meilleure densité et lisibilité des champs
- **Styles cohérents** : Uniformisation de l'apparence sur tous les types de champs
- **Design moderne** : Interface plus propre et professionnelle
- **Responsive** : Adaptation automatique aux différentes tailles d'écran

### 📝 Types de Champs Supportés
- Champs texte (text, email, url, password, number, tel)
- Zones de texte (textarea)
- Listes déroulantes (select)
- Cases à cocher (checkbox)
- Boutons radio (radio)
- Champs de fichiers/images
- Champs clonables (répétables)
- Champs de date et heure

### 🔧 Améliorations Techniques
- **Validation en temps réel** : Feedback visuel immédiat
- **États visuels** : Focus, erreur, succès
- **Accessibilité** : Meilleure navigation au clavier
- **Performance** : CSS optimisé et léger

## Installation

1. Copiez le contenu du fichier `snippet-metabox-styles.php`
2. Ajoutez-le à votre thème via :
   - Le fichier `functions.php` de votre thème
   - Un plugin personnalisé
   - Le plugin "Code Snippets"

## Utilisation

### Activation Automatique
Le snippet s'active automatiquement dès qu'il est chargé. Il utilise les hooks Meta Box :
- `rwmb_enqueue_scripts` : Pour charger les styles CSS
- `rwmb_html` : Pour personnaliser le HTML des champs
- `admin_head` : Pour les styles conditionnels

### Classes CSS Disponibles

#### Classes Principales
```css
.rwmb-meta-box          /* Container principal */
.rwmb-field             /* Wrapper de chaque champ */
.rwmb-label             /* Container du label */
.rwmb-input             /* Container de l'input */
.rwmb-required          /* Indicateur de champ requis */
```

#### Classes par Type de Champ
```css
.rwmb-text-wrapper      /* Champs texte */
.rwmb-select-wrapper    /* Listes déroulantes */
.rwmb-checkbox-wrapper  /* Cases à cocher */
.rwmb-radio-wrapper     /* Boutons radio */
.rwmb-file-wrapper      /* Champs de fichiers */
.rwmb-clone             /* Champs clonables */
```

#### Classes d'État
```css
.focused                /* Champ en focus */
.error                  /* État d'erreur */
.success                /* État de succès */
.has-error              /* Wrapper avec erreur */
```

## Personnalisation

### Modifier les Couleurs
```css
/* Variables CSS personnalisables */
:root {
    --metabox-primary: #0073aa;
    --metabox-error: #d63638;
    --metabox-success: #00a32a;
    --metabox-border: #ddd;
    --metabox-background: #fff;
}
```

### Ajouter des Styles Personnalisés
```php
// Hook pour ajouter vos propres styles
add_action('rwmb_enqueue_scripts', 'my_custom_metabox_styles');

function my_custom_metabox_styles() {
    wp_add_inline_style('wp-admin', '
        .rwmb-field.my-custom-field {
            background: #f0f8ff;
            border-left: 4px solid #0073aa;
        }
    ');
}
```

### Personnaliser le HTML des Champs
```php
// Utiliser le filtre rwmb_html
add_filter('rwmb_html', 'my_custom_field_html', 10, 3);

function my_custom_field_html($html, $field, $value) {
    if ($field['type'] === 'text' && $field['id'] === 'special_field') {
        // Personnaliser le HTML pour un champ spécifique
        $html = str_replace('class="rwmb-text"', 'class="rwmb-text special-styling"', $html);
    }
    return $html;
}
```

## Responsive Design

Le snippet inclut des optimisations responsive :

### Tablettes (< 1200px)
- Espacement réduit
- Padding optimisé

### Mobiles (< 782px)
- Taille de police augmentée (évite le zoom)
- Espacement adapté au tactile
- Labels plus grands

## Compatibilité

### Versions Supportées
- **WordPress** : 5.0+
- **Meta Box** : 5.0+
- **PHP** : 7.4+

### Extensions Meta Box Compatibles
- MB Builder
- MB Frontend Submission
- MB REST API
- MB Views
- MB Settings Page
- Toutes les extensions officielles

## Hooks et Filtres Disponibles

### Actions
```php
// Avant le chargement des styles
do_action('metabox_before_styles_enqueue');

// Après le chargement des styles
do_action('metabox_after_styles_enqueue');
```

### Filtres
```php
// Modifier les styles CSS
apply_filters('metabox_custom_css', $css);

// Modifier les classes personnalisées
apply_filters('metabox_custom_classes', $classes, $field_type);
```

## Exemples d'Utilisation

### Créer une Metabox Stylée
```php
add_filter('rwmb_meta_boxes', 'my_styled_metabox');

function my_styled_metabox($meta_boxes) {
    $meta_boxes[] = [
        'title' => 'Informations Produit',
        'post_types' => 'product',
        'fields' => [
            [
                'name' => 'Prix',
                'id' => 'product_price',
                'type' => 'number',
                'required' => true,
                'desc' => 'Prix en euros'
            ],
            [
                'name' => 'Description',
                'id' => 'product_description',
                'type' => 'textarea',
                'rows' => 4
            ],
            [
                'name' => 'Catégorie',
                'id' => 'product_category',
                'type' => 'select',
                'options' => [
                    'electronics' => 'Électronique',
                    'clothing' => 'Vêtements',
                    'books' => 'Livres'
                ]
            ]
        ]
    ];
    return $meta_boxes;
}
```

### Validation Personnalisée
```php
// Ajouter une validation JavaScript personnalisée
add_action('rwmb_enqueue_scripts', 'my_custom_validation');

function my_custom_validation() {
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $("#product_price").on("blur", function() {
                var price = parseFloat($(this).val());
                if (price < 0) {
                    $(this).addClass("error");
                    alert("Le prix ne peut pas être négatif");
                } else {
                    $(this).removeClass("error").addClass("success");
                }
            });
        });
    ');
}
```

## Dépannage

### Styles Non Appliqués
1. Vérifiez que Meta Box est installé et activé
2. Assurez-vous que le snippet est bien chargé
3. Vérifiez la console pour les erreurs CSS
4. Utilisez `!important` si nécessaire pour surcharger d'autres styles

### Conflits avec le Thème
```css
/* Forcer l'application des styles */
.rwmb-meta-box .rwmb-input input {
    /* vos styles */ !important;
}
```

### Performance
- Le CSS est inliné pour éviter les requêtes HTTP supplémentaires
- Les scripts sont minifiés automatiquement
- Utilisation des hooks WordPress optimaux

## Support

Pour plus d'informations :
- [Documentation Meta Box](https://docs.metabox.io/)
- [Guide de styling CSS](https://metabox.io/style-meta-boxs-custom-fields-using-css/)
- [Hooks et filtres](https://docs.metabox.io/filters/)

## Changelog

### Version 1.0
- Styles de base pour tous les types de champs
- Responsive design
- Validation en temps réel
- États visuels (focus, erreur, succès)
- Champs clonables
- Intégration WordPress

---

*Ce snippet est basé sur la documentation officielle de metabox.io et suit les meilleures pratiques recommandées.*