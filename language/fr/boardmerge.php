<?php
/**
 *
 * Board Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @copyright (c) 2022, French translation by Fred Rimbert https://forums.caforum.fr
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_BOARD_MERGE_INSTRUCTIONS'	=> 'Utilisez cet outil pour fusionner la base de données actuelle du forum %s dans le forum actuel d‘où l‘outil est utilisé.',

	'SOURCE_DB_NAME'				=> 'Nom de la base de données source',
	'SOURCE_DB_NAME_REQUIRED'		=> 'Le nom de la base de données source est requis',
	'SOURCE_DB_NAME_EXPLAIN'		=> 'Nom de la base de données sur le serveur à partir de laquelle copier les données.',
	'SOURCE_DB_USERNAME'			=> 'Nom d‘utilisateur de la base de données source',
	'SOURCE_DB_USERNAME_EXPLAIN'	=> 'Utilisateur de base de données pouvant se connecter à la base de données source. Utilisateur de la base de données cible préchargé pour plus de commodité.',
	'SOURCE_DB_PASSWORD'			=> 'Mot de passe de la base de données source',
	'SOURCE_DB_PASSWORD_EXPLAIN'	=> 'Le mot de passe de l‘utilisateur de la base de données pour se connecter à la base de données source.',
	'SOURCE_DB_TABLE_PREFIX'		=> 'Préfixe de la table de la base de données source',
	'SOURCE_DB_TABLE_PREFIX_EXPLAIN'=> 'Entrez le préfixe de la table de la base de données source ou cliquez sur le bouton <i>Obtenir le préfixe de la table</i> pour tenter de le déterminer automatiquement si vous ne le connaissez pas.',
	'GET_TABLE_PREFIX'				=> 'Obtenir le préfixe de la table',
	'TABLE_PREFIX_NOT_DETERMINABLE'	=> 'Le préfixe de la table n‘a pas pu être déterminé.',
	'TABLE_PREFIX_INVALID'			=> 'Le préfixe de table entré n‘est pas valide.',
	'TARGET_DB_NAME'				=> 'Nom de la base de données cible',
	'USER_MERGE_SUMMARY'			=> 'Afficher le résumé de la fusion des utilisateurs',
	'USER_MERGE_SUMMARY_EXPLAIN'	=> 'Décochez cette option si vous rencontrez un délai d‘expiration de l‘itinéraire lorsque vous cliquez sur <em>Continuer</em>.',
	'TARGET_DB_NAME_EXPLAIN'		=> 'Ceci est le nom de la base de données du forum actuel (celui sur lequel vous lisez ceci) et est fixé tout au long de ce processus.',
	'SOURCE_DB_CONNECTION_ERROR'	=> '<p>Impossible de se connecter à la base de données %s.</p><p>Message d‘erreur renvoyé : [%s] %s</p>',
	'SOURCE_DB_INVALID'				=> 'La base de données source n‘est pas une base de données phpBB valide ou la version de la base de données est inférieure à 3.1',
	'SOURCE_DB_NAME_SAME_AS_TARGET'	=> 'Le nom de la base de données source ne peut pas être le même que le nom de la base de données cible',
	'SOURCE_DB_VERSION_TOO_OLD'		=> 'La version de la base de données source (%s) est trop ancienne. Seules les bases de données 3.1.0 ou plus récentes peuvent être fusionnées. Une mise à niveau devra d‘abord être effectuée sur cette base de données en utilisant un <a href="https://www.phpbb.com/downloads/" target="_blank">paquet phpBB</a> d‘origine.',
	'SOURCE_CONNECTION_SUCCESSFUL'	=> '<p>Connexion réussie à la base de données source et ajout des colonnes nécessaires à la fusion avec les forums, les utilisateurs et les tables de sujets.</p><p>Cliquez sur <em>Continuer</em> pour associer les forums et les utilisateurs.</p>',
	'FORUM_MAPPING'					=> 'Associez les forums',
	'FORUM_MAPPING_INSTRUCTIONS'	=> 'Associez le nom du forum dans le tableau source au nom du forum applicable dans le tableau cible. Cela sera utilisé pour identifier à quels messages du forum appartiennent.',
	'SOURCE_FORUM_NAME'				=> 'Nom du forum source',
	'TARGET_FORUM_NAME'				=> 'Nom du forum cible',
	'USER_MAPPING_SUMMARY'			=> 'Résumé du mappage utilisateur',
	'USER_MAPPING_SUMMARY_INSTRUCTIONS'	=> 'Ceci est un résumé des résultats de l‘appariement des utilisateurs entre les deux forums. La correspondance se fait sur le nom d‘utilisateur et l‘adresse e-mail.',
	'MATCHED_USERS'					=> 'Utilisateurs correspondants',
	'USERS_TO_ADD'					=> 'Utilisateurs à ajouter',
	'MERGE_IS_READY'				=> 'La fusion de cartes est prête à être exécutée. Une fois démarré, ce processus ne doit PAS être arrêté, sinon vous aurez des données fusionnées incomplètes/manquantes.',
	'BEFORE_CONTINUING_MERGE'		=> '<strong>AVIS FINAL :</strong> ce processus prendra beaucoup de temps. Votre carte sera automatiquement désactivée avec un message "Maintenance" affiché. Il est recommandé de faire une sauvegarde de votre base de données avant de continuer par précaution.',
	'FORUM_DISABLED_MESSAGE'		=> 'Maintenance du forum en cours.',
	'PROCESSING_NO_INTERRUPT'		=> 'La fusion est en cours de traitement. <strong>NE PAS QUITTER OU RAFRAÎCHIR MANUELLEMENT CETTE PAGE !</strong><p>Durée d‘exécution écoulée : %d minute(s)</p>',
	'PROCESSING_USERS'				=> '%d utilisateurs sur %d ont été importés.',
	'USER_PROCESSING_COMPLETE'		=> 'Traitement de l‘utilisateur terminé. Préparation au traitement des sujets et des messages.',
	'PROCESSING_TOPICS'				=> '%d sujets sur %d et leurs messages ont été importés.',
	'TOPIC_PROCESSING_COMPLETE'		=> 'Traitement du sujet terminé. Resynchronisation des statistiques.',
	'STAT_SYNC_COMPLETE'			=> 'Les statistiques ont été resynchronisées. Resynchroniser le nombre de publications des utilisateurs.',
	'POST_COUNTS_SYNC_COMPLETE'		=> 'Le nombre de publications des utilisateurs a été resynchronisé. Re-synchroniser les sujets en pointillés.',
	'DOTTED_TOPICS_SYNC_COMPLETE'	=> 'Les sujets en pointillés ont été resynchronisés. Se préparer à resynchroniser les forums.',
	'PROCESSING_FORUM_SYNC'			=> 'Re-synchronisation du forum <i>%s</i>.<br />Synchronisation actuelle de la plage de sujets %d/%d',
	'FORUM_SYNC_COMPLETE'			=> 'Synchronisation du forum terminée. Purger le cache du forum.',
	'PURGING_CACHE_COMPLETE'		=> 'Cache purgé.',
	'MERGE_COMPLETE'				=> 'Les utilisateurs, les sujets et leurs publications de %s ont été importés et les données resynchronisées.<p>Durée totale : %d minute(s)</p>',

	'CONTINUE_MERGE'				=> 'Continuer',
	'PREPARE_MERGE'					=> 'Préparer la fusion',
	'RUN_MERGE'						=> 'Exécuter la fusion',
	'MERGE_PROGRESS'				=> 'Progression de la fusion',

));
