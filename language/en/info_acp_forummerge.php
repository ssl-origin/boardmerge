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
	'SOURCE_CONNECTION_SUCCESSFUL'	=> '<p>Successfully connected to source database and added columns needed for merging to forums, users, and topics tables.</p><p>Click <em>Continue</em> to map forums and users.</p>',
	'FORUM_MAPPING'					=> 'Forum Mapping',
	'FORUM_MAPPING_INSTRUCTIONS'	=> 'Map the forum name in the source forum to the applicable forum name in the target forum. This will be used to identify what forum posts belong in.',
	'SOURCE_FORUM_NAME'				=> 'Source forum name',
	'TARGET_FORUM_NAME'				=> 'Target forum name',
	'USER_MAPPING_SUMMARY'			=> 'User mapping summary',
	'USER_MAPPING_SUMMARY_INSTRUCTIONS'	=> 'This is a summary of the results of the user matching between the two forums. Matching is done on username and email address.',
	'MATCHED_USERS'					=> 'Matched users',
	'USERS_TO_ADD'					=> 'Users to add',
	'MERGE_IS_READY'				=> 'The forum merge is ready to run. Once started this process should NOT be stopped, otherwise you will have incomplete/missing merged data.',
	'PROCESSING_NO_INTERRUPT'		=> 'Merge is currently being processed. <strong>DO NOT EXIT OR MANUALLY REFRESH THIS PAGE!</strong>',
	'PROCESSING_USERS'				=> '%d of %d users have been imported.',
	'USER_PROCESSING_COMPLETE'		=> 'User processing complete. Preparing to process topics and posts.',
	'PROCESSING_TOPICS'				=> '%d of %d topics and their posts have been imported.',
	'TOPIC_PROCESSING_COMPLETE'		=> 'Topic processing complete. Preparing to re-synchronize stats.',
	'STAT_SYNC_COMPLETE'			=> 'Statistics have been re-synchronized. Preparing to re-synchronize post counts.',
	'POST_COUNTS_SYNC_COMPLETE'		=> 'Post counts have been re-synchronized. Preparing to re-synchronize dotted topics.',
	'DOTTED_TOPICS_SYNC_COMPLETE'	=> 'Dotted topics have been re-synchronized. Preparing to re-synchronize forums.',
	'PROCESSING_FORUM_SYNC'			=> 'Re-synchronizing <i>%s</i>.<p>%s of %s topics have been synchronized for this forum.</p>',
	'FORUM_SYNC_COMPLETE'			=> 'Forum sync complete. Users, topics and their posts from %s have been imported and data re-synchronized.',

	'CONTINUE_MERGE'				=> 'Continue',
	'PREPARE_MERGE'					=> 'Prepare Merge',
	'RUN_MERGE'						=> 'Run Merge',
));
