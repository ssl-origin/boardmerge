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
	'ACP_FORUM_MERGE_COMPLETED'		=> 'Forum merging has completed!',
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
	'FORUM_MAPPING'					=> 'Forum Mapping',
	'FORUM_MAPPING_INSTRUCTIONS'	=> 'Map the forum name in the source forum to the applicable forum name in the target forum. This will be used to identify what forum posts belong in.',
	'SOURCE_FORUM_NAME'				=> 'Source forum name',
	'TARGET_FORUM_NAME'				=> 'Target forum name',
	'USER_MAPPING_SUMMARY'			=> 'User mapping summary',
	'USER_MAPPING_SUMMARY_INSTRUCTIONS'	=> 'This is a summary of the results of the user matching between the two forums. Matching is done on username and email address.',
	'MATCHED_USERS'					=> 'Matched users',
	'USERS_TO_ADD'					=> 'Users to add',
	'MERGE_IS_READY'				=> 'The forum merge is ready to run. Once started this process should NOT be stopped, otherwise you will have incomplete/missing merged data.',

	'CONTINUE_MERGE'				=> 'Continue',
	'PREPARE_MERGE'					=> 'Prepare Merge',
	'RUN_MERGE'						=> 'Run Merge',
));
