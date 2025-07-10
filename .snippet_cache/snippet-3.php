<?php
/**
 * Snippet ID: 3
 * Name: Autoriser les smileys
 * Description: Permet la conversion des smileys dans les endroits obscurs.

Ceci est un exemple d’extrait. N’hésitez pas à l’utiliser, à le modifier ou à le supprimer.
 * @active false
 */

add_filter( 'widget_text', 'convert_smilies' );
add_filter( 'the_title', 'convert_smilies' );
add_filter( 'wp_title', 'convert_smilies' );
add_filter( 'get_bloginfo', 'convert_smilies' );