<?php
/**
 * Snippet ID: 134
 * Name: entete tableau commandes cote client mon compte
 * Description: 
 * @active true
 */

/**
 * Personnalise le tableau des commandes WooCommerce sur la page Mon Compte
 * Placez ce code dans Code Snippets ou dans le fichier functions.php de votre thème enfant
 */
add_filter( 'woocommerce_account_orders_columns', 'custom_account_orders_columns' );
function custom_account_orders_columns( $columns ) {

    // On reconstruit l'ordre des colonnes :
    $new_columns = array();

    // 1. Date
    $new_columns['order-date'] = __( 'Date', 'woocommerce' );

    // 2. Numéro de commande
    $new_columns['order-number'] = __( 'Commande', 'woocommerce' );

    // 3. Formation (produit principal)
    $new_columns['order-formation'] = __( 'Formation', 'woocommerce' );

    // 4. État
    $new_columns['order-status'] = __( 'État', 'woocommerce' );

    // 5. Actions (on reprend la colonne existante)
    $new_columns['order-actions'] = $columns['order-actions'];

    return $new_columns;
}

/**
 * Affiche le nom du premier produit dans la colonne "Formation"
 */
add_action( 'woocommerce_my_account_my_orders_column_order-formation', 'custom_account_orders_column_formation' );
function custom_account_orders_column_formation( $order ) {
    // Récupération de l'objet Order
    $items = $order->get_items();
    $first_item = current( $items );

    if ( $first_item ) {
        echo esc_html( $first_item->get_name() );
    }
}

/**
 * Ajout d'un style personnalisé pour le tableau des commandes
 */
add_action( 'wp_head', 'custom_my_account_orders_style' );
function custom_my_account_orders_style() {
    ?>
    <style>
    /* Conteneur du tableau : empêche le débordement horizontal */
    .woocommerce-account .woocommerce-orders-table {
        width: 100%;
        table-layout: fixed; /* Fixe la largeur des colonnes pour éviter les déformations */
        overflow-x: auto; /* Permet le scroll horizontal seulement si nécessaire */
        display: block; /* Évite les débordements sur des écrans plus petits */
    }

    /* Colonnes spécifiques */
    .woocommerce-account .woocommerce-orders-table th,
    .woocommerce-account .woocommerce-orders-table td {
        padding: 6px !important;
        font-size: 14px; /* Ajuste la taille pour plus de lisibilité */
        white-space: nowrap; /* Évite le retour à la ligne intempestif */
    }

    /* Ajustement des largeurs de colonnes */
    .woocommerce-account .woocommerce-orders-table th:nth-child(1),
    .woocommerce-account .woocommerce-orders-table td:nth-child(1) {
        width: 12%; /* Date */
    }

    .woocommerce-account .woocommerce-orders-table th:nth-child(2),
    .woocommerce-account .woocommerce-orders-table td:nth-child(2) {
        width: 10%; /* Commande */
    }

    .woocommerce-account .woocommerce-orders-table th:nth-child(3),
    .woocommerce-account .woocommerce-orders-table td:nth-child(3) {
        width: 35%; /* Formation */
    }

    .woocommerce-account .woocommerce-orders-table th:nth-child(4),
    .woocommerce-account .woocommerce-orders-table td:nth-child(4) {
        width: 18%; /* État */
    }

    .woocommerce-account .woocommerce-orders-table th:nth-child(5),
    .woocommerce-account .woocommerce-orders-table td:nth-child(5) {
        width: 15%; /* Actions */
    }

    /* Style de l'en-tête */
    .woocommerce-account .woocommerce-orders-table thead th {
        background-color: #314150;
        color: #ffffff;
    }

    /* S'assurer que le bouton \"Suivant\" ne dépasse pas */
    .woocommerce-pagination {
        text-align: center;
        margin-top: 10px;
    }
    </style>
    <?php
}