<?php
/**
 * Snippet ID: 1
 * Name: Mettre les noms des fichiers téléversés en minuscule
 * Description: Vérifie que les images et les fichiers téléversés ont des noms en minuscule.

Ceci est un exemple d’extrait. N’hésitez pas à l’utiliser, à le modifier ou à le supprimer.
 * @active false
 */

add_filter( 'sanitize_file_name', 'mb_strtolower' );