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
	'ACP_BOARD_MERGE_TITLE'			=> 'Fusion du panneau dadministration',
	'ACP_BOARD_MERGE_MAIN'			=> 'Paramètres',

	'LOG_BOARD_MERGE_COMPLETE'		=> 'Fusion du forum terminée',
));
