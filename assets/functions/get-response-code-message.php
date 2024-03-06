<?php
/**
 * This file is declaring the function 'wws_get_response_code_message'.
 *
 * @package WWS.
 */

/**
 * Return the text message base on the code number defined by Worldline.
 *
 * @param string $wws_code  Message code.
 *
 * @return string
 */
function wws_get_response_code_message( $wws_code ) {
	switch ( $wws_code ) {
		case '00':
			return 'Transaction/opération acceptée';
		case '01':
			return 'Pour les méthodes panToToken et tokenToPan, succès partiel';
		case '02':
			return 'Demande d\'autorisation par téléphone à la banque à cause d\'un dépassement du plafond d\'autorisation sur la carte';
		case '03':
			return 'Contrat commerçant invalide';
		case '05':
			return 'Autorisation refusée';
		case '11':
			return 'Utilisé dans le cas d\'un contrôle différé. Le PAN est en opposition';
		case '12':
			return 'Transaction invalide, vérifier les paramètres transférés dans la requête';
		case '14':
			return 'Coordonnées du moyen de paiement invalides (ex : n° de carte ou cryptogramme visuel de la carte) ou vérification AVS échouée';
		case '17':
			return 'Annulation de l\'acheteur';
		case '24':
			return 'En réponse d\'une opération de gestion de caisse : opération impossible. L\'opération que vous souhaitez réaliser n\'est pas compatible avec l\'état de la transaction ou une autre opération de caisse est en cours sur la transaction au même moment.
                    En réponse d\'une création de paiement : opération rejetée, requête déjà effectuée avec les mêmes données et les mêmes paramètres';
		case '25':
			return 'Transaction inconnue de WL SIPS';
		case '30':
			return 'Erreur de format';
		case '34':
			return 'Suspicion de fraude (seal erroné)';
		case '40':
			return 'Fonction non supportée : l\'opération que vous souhaitez réaliser ne fait pas partie de la liste des opérations auxquelles vous êtes autorisés';
		case '51':
			return 'Montant trop élevé';
		case '54':
			return 'Date de validité du moyen de paiement dépassée';
		case '55':
			return 'Cartes prépayées non acceptées';
		case '57':
			return 'Remboursement refusé car la transaction d\'origine a fait l\'objet d\'un impayé';
		case '60':
			return 'Transaction en attente';
		case '62':
			return 'En attente de confirmation pour la transaction (utilisé par PayPal 1.0)';
		case '63':
			return 'Règles de sécurité non respectées, transaction arrêtée';
		case '75':
			return 'Nombre de tentatives de saisie des coordonnées du moyen de paiement sous SIPS Paypage dépassé';
		case '90':
			return 'Service temporairement indisponible';
		case '94':
			return 'Transaction dupliquée : le transactionReference de la transaction est déjà utilisé';
		case '97':
			return 'Session expirée (aucune action de l\'utilisateur pendant 15 minutes), transaction refusée';
		case '99':
			return 'Problème temporaire du serveur de paiement.';
		default:
			return 'Une erreur est survenue.';
	}
}
