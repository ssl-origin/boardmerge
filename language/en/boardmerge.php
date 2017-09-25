<?php
/**
 *
 * Board Merge. An extension for the phpBB Forum Software package.
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
	'ACP_BOARD_MERGE_INSTRUCTIONS'	=> 'Use this tool to merge the current %s forum`s database into the current forum where the tool is being used from.',

	'SOURCE_DB_NAME'				=> 'Source database name',
	'SOURCE_DB_NAME_REQUIRED'		=> 'Source database name is required',
	'SOURCE_DB_NAME_EXPLAIN'		=> 'The name of the database on the server from which to copy data from.',
	'SOURCE_DB_USERNAME'			=> 'Source database username',
	'SOURCE_DB_USERNAME_EXPLAIN'	=> 'Database user that can connect to the source database. Target database user pre-loaded for convenience.',
	'SOURCE_DB_PASSWORD'			=> 'Source database password',
	'SOURCE_DB_PASSWORD_EXPLAIN'	=> 'The password for the database user for connecting to the source database.',
	'SOURCE_DB_TABLE_PREFIX'		=> 'Source database table prefix',
	'SOURCE_DB_TABLE_PREFIX_EXPLAIN'=> 'Enter the source database table prefix or click on the <i>Get table prefix</i> button to attempt to automatically determine it if you don`t know it.',
	'GET_TABLE_PREFIX'				=> 'Get table prefix',
	'TABLE_PREFIX_NOT_DETERMINABLE'	=> 'Table prefix could not be determined.',
	'TABLE_PREFIX_INVALID'			=> 'Table prefix entered is not valid.',
	'TARGET_DB_NAME'				=> 'Target database name',
	'USER_MERGE_SUMMARY'			=> 'Show user merge summary',
	'USER_MERGE_SUMMARY_EXPLAIN'	=> 'Uncheck this option if you experience a route timeout when clicking <em>Continue</em>.',
	'TARGET_DB_NAME_EXPLAIN'		=> 'This is the current forum`s (the one you are reading this on) database name and is fixed throughout this process.',
	'SOURCE_DB_CONNECTION_ERROR'	=> '<p>Could not connect to the %s database.</p><p>Error message returned: [%s] %s</p>',
	'SOURCE_DB_INVALID'				=> 'The source database is either not a valid phpBB database or the database version is lower than 3.1',
	'SOURCE_DB_NAME_SAME_AS_TARGET'	=> 'The source database name cannot be the same as the target database name',
	'SOURCE_DB_VERSION_TOO_OLD'		=> 'The source database version (%s) is too old. Only 3.1.0 or newer databases can be merged. An upgrade will first need to be performed on that database by using an original <a href="https://www.phpbb.com/downloads/" target="_blank">phpBB package</a>.',
	'SOURCE_CONNECTION_SUCCESSFUL'	=> '<p>Successfully connected to source database and added columns needed for merging to forums, users, and topics tables.</p><p>Click <em>Continue</em> to map forums and users.</p>',
	'FORUM_MAPPING'					=> 'Forum Mapping',
	'FORUM_MAPPING_INSTRUCTIONS'	=> 'Map the forum name in the source board to the applicable forum name in the target board. This will be used to identify what forum posts belong in.',
	'SOURCE_FORUM_NAME'				=> 'Source forum name',
	'TARGET_FORUM_NAME'				=> 'Target forum name',
	'USER_MAPPING_SUMMARY'			=> 'User mapping summary',
	'USER_MAPPING_SUMMARY_INSTRUCTIONS'	=> 'This is a summary of the results of the user matching between the two forums. Matching is done on username and email address.',
	'MATCHED_USERS'					=> 'Matched users',
	'USERS_TO_ADD'					=> 'Users to add',
	'MERGE_IS_READY'				=> 'The board merge is ready to run. Once started this process should NOT be stopped, otherwise you will have incomplete/missing merged data.',
	'BEFORE_CONTINUING_MERGE'		=> '<strong>FINAL NOTICE:</strong> This process will take a long time. Your board will be automatically disabled with a `Maintenance` message displayed. It is recommended that you take a backup of your database prior to continuing as a precaution.',
	'FORUM_DISABLED_MESSAGE'		=> 'Forum maintenance currently in progress.',
	'PROCESSING_NO_INTERRUPT'		=> 'Merge is currently being processed. <strong>DO NOT EXIT OR MANUALLY REFRESH THIS PAGE!</strong><p>Elapsed run time: %d minute(s)</p>',
	'PROCESSING_USERS'				=> '%d of %d users have been imported.',
	'USER_PROCESSING_COMPLETE'		=> 'User processing complete. Preparing to process topics and posts.',
	'PROCESSING_TOPICS'				=> '%d of %d topics and their posts have been imported.',
	'TOPIC_PROCESSING_COMPLETE'		=> 'Topic processing complete. Re-synchronizing stats.',
	'STAT_SYNC_COMPLETE'			=> 'Statistics have been re-synchronized. Re-synchronizing user post counts.',
	'POST_COUNTS_SYNC_COMPLETE'		=> 'User post counts have been re-synchronized. Re-synchronizing dotted topics.',
	'DOTTED_TOPICS_SYNC_COMPLETE'	=> 'Dotted topics have been re-synchronized. Preparing to re-synchronize forums.',
	'PROCESSING_FORUM_SYNC'			=> 'Re-synchronizing <i>%s</i> forum.<br />Currently synchronizing topic range %d/%d',
	'FORUM_SYNC_COMPLETE'			=> 'Forum sync complete. Purging forum cache.',
	'PURGING_CACHE_COMPLETE'		=> 'Cache purged.',
	'MERGE_COMPLETE'				=> 'Users, topics and their posts from %s have been imported and data re-synchronized.<p>Total run time: %d minute(s)</p>',

	'CONTINUE_MERGE'				=> 'Continue',
	'PREPARE_MERGE'					=> 'Prepare Merge',
	'RUN_MERGE'						=> 'Run Merge',
	'MERGE_PROGRESS'				=> 'Merge progress',

));
