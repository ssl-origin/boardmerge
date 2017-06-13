<?php
/**
 *
 * Forum Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
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
	'ACP_FORUM_MERGE_TITLE'			=> 'Forum Merge',
	'ACP_FORUM_MERGE_MAIN'			=> 'Main',

	'ACP_FORUM_MERGE_INSTRUCTIONS'	=> 'Use this tool to merge a %s forum`s database (versions must match) into the current forum where the tool is being used from.',
	'SOURCE_DB_NAME'				=> 'Source database name',
	'SOURCE_DB_NAME_EXPLAIN'		=> 'The name of the database on the server from which to copy data from.',
	'SOURCE_DB_USERNAME'			=> 'Source database username',
	'SOURCE_DB_USERNAME_EXPLAIN'	=> 'Database user that can connect to the source database. Target database user pre-loaded for convenience.',
	'SOURCE_DB_PASSWORD'			=> 'Source database password',
	'SOURCE_DB_PASSWORD_EXPLAIN'	=> 'The password for the database user for connecting to the source database.',
	'TARGET_DB_NAME'				=> 'Target database name',
	'SOURCE_DB_CONNECTION_ERROR'	=> '<p>Could not connect to the %s database.</p><p>Error message returned: [%s] %s</p>',
	'SOURCE_DB_INVALID'				=> 'The source database is either not a valid phpBB database or the database version is lower than 3.1',
	'SOURCE_DB_NAME_SAME_AS_TARGET'	=> 'The source database name cannot be the same as the target database name',
	'SOURCE_DB_VERSION_MISMATCH'	=> 'The source database version (%s) does not match the target database version (%s)',
	'SOURCE_CONNECTION_SUCCESSFUL'	=> 'Successfully connected to source database',

	'CONTINUE_MERGE'				=> 'Continue',
));
