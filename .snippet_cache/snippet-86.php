<?php
/**
 * Snippet ID: 86
 * Name: Styles CSS tableau planning pixtral
 * Description: 
 * @active false
 */

	
	/* Styles généraux pour le tableau */
.wrap table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.wrap th, .wrap td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.wrap th {
    background-color: #f4f4f4;
    font-weight: bold;
}

.wrap tr:nth-child(even) {
    background-color: #f9f9f9;
}

/* Styles pour les boutons
.wrap .button {
    padding: 8px 12px;
    margin: 2px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    color: #fff;
    transition: background-color 0.3s;
}

.wrap .add-formateur-button, .wrap .add-fournisseur-button {
    background-color: #28a745;
}

.wrap .add-formateur-button:hover, .wrap .add-fournisseur-button:hover {
    background-color: #218838;
}

.wrap .remove-button, .wrap .remove-day-button {
    background-color: #dc3545;
}

.wrap .remove-button:hover, .wrap .remove-day-button:hover {
    background-color: #c82333;
}

.wrap .add-day-button {
    background-color: #007bff;
}

.wrap .add-day-button:hover {
    background-color: #0056b3;
}
 */
/* Styles pour les champs de saisie */
.wrap .date-input, .wrap .nom-select, .wrap .dispo-select, .wrap .etat-select {
    width: 100%;
    padding: 8px;
    margin: 2px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Styles pour les actions */
.wrap .action-link {
    text-decoration: none;
    color: #0073e6;
}

.wrap .action-link:hover {
    text-decoration: underline;
}

/* Styles pour les messages de confirmation */
.updated {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 10px;
    margin-top: 20px;
    border-radius: 4px;
}
