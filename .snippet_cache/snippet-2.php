<?php
/**
 * Snippet ID: 2
 * Name: Désactiver la barre d’administration
 * Description: Désactive la barre d’administration de WordPress pour tout le monde sauf les administrateurs/administratrices.

Ceci est un exemple d’extrait. N’hésitez pas à l’utiliser, à le modifier ou à le supprimer.
 * @active false
 */

add_action( 'wp', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		show_admin_bar( false );
	}
} );