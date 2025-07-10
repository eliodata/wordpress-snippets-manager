<?php
/**
 * Snippet ID: 199
 * Name: CORRECTIF SAISIE COUTS AUTO FORMATEURS FOURNISEURS nombres AVEC VIRGULES
 * Description: 
 * @active false
 */

function normalize_price_field($value) {
    // Remplacer les virgules par des points
    $value = str_replace(',', '.', $value);
    // S'assurer que c'est un nombre valide
    return is_numeric($value) ? $value : '0';
}