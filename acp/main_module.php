<?php
/**
 *
 * Board Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\boardmerge\acp;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Board Merge ACP module.
 */
class main_module
{
	/** @var string */
	public $page_title;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $u_action;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $table_prefix;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $phpbb_root_path;

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var ContainerInterface */
	protected $phpbb_container;

	/** @var \phpbb\log\log_interface */
	protected $phpbb_log;

	/** @var \phpbb\user */
	protected $user;

	public function main($id, $mode)
	{
		global $phpbb_container, $auth, $cache, $config, $request, $template, $user, $db, $dbms, $dbhost, $dbname, $dbuser, $phpbb_log, $phpEx, $phpbb_root_path;

		$this->request = $request;
		$this->db = $db;
		$this->php_ext = $phpEx;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->config = $config;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->phpbb_container = $phpbb_container;
		$this->phpbb_log = $phpbb_log;
		$this->user = $user;
		unset($request, $db, $phpEx, $phpbb_root_path, $config, $cache, $auth, $phpbb_container, $phpbb_log, $user);

		$this->user->add_lang_ext('davidiq/boardmerge', 'boardmerge');

		$button_name_id = 'continue1';
		$button_text = 'CONTINUE_MERGE';
		$board_merge_error = false;
		$this->tpl_name = 'acp_boardmerge_body';
		$this->page_title = $this->user->lang('ACP_BOARD_MERGE_TITLE');
		add_form_key('davidiq/boardmerge');

		$source_db_name = $this->request->variable('source_db_name', '');
		$source_db_username = $this->request->variable('source_db_username', $dbuser);
		$source_db_password = $this->request->variable('source_db_password', '');
		$this->table_prefix = $this->request->variable('source_table_prefix', '');
		$user_merge_summary = $this->request->variable('user_merge_summary', false);

		/** @var \phpbb\db\driver\driver_interface $dbal_source */
		$dbal_source = null;

		if (strpos($dbms, 'phpbb\db\driver') === false && class_exists('phpbb\db\driver\\' . $dbms))
		{
			$dbms = 'phpbb\db\driver\\' . $dbms;
		}

		$get_table_prefix = $this->request->is_set('get_table_prefix');

		@set_time_limit(0);

		if ($get_table_prefix || $this->request->is_set_post('continue1'))
		{
			if (!check_form_key('davidiq/boardmerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			if (empty($source_db_name))
			{
				$board_merge_error = $this->user->lang('SOURCE_DB_NAME_REQUIRED');
			}
			else if ($source_db_name == $dbname)
			{
				$board_merge_error = $this->user->lang('SOURCE_DB_NAME_SAME_AS_TARGET');
			}
			else
			{
				// Check database connection
				$dbal_source = new $dbms();
				$dbal_source->sql_return_on_error(true);
				if (is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
				{
					$sql_error = $dbal_source->sql_error();
					$board_merge_error = $this->user->lang('SOURCE_DB_CONNECTION_ERROR', $source_db_name, $sql_error['code'], $sql_error['message']);
				}
				else
				{
					$source_db_table_is_set = $this->request->is_set('source_db_table_prefix', \phpbb\request\request_interface::POST);
					$dbal_source->sql_return_on_error($get_table_prefix || $source_db_table_is_set);

					if ($get_table_prefix)
					{
						$this->table_prefix = $this->get_table_prefix($dbal_source);
					}
					else
					{
						$this->table_prefix = $this->request->variable('source_db_table_prefix', '');
						if (empty($this->table_prefix))
						{
							$this->table_prefix = $this->get_table_prefix($dbal_source);
						}
					}

					if ($get_table_prefix)
					{
						$board_merge_error = $this->table_prefix ? '' : $this->user->lang('TABLE_PREFIX_NOT_DETERMINABLE');
					}
					else if (!$this->table_prefix)
					{
						$board_merge_error = $this->user->lang('SOURCE_DB_INVALID');
					}
					else
					{
						// Check database version
						$result = $dbal_source->sql_query("SELECT config_value FROM {$this->table_prefix}config WHERE config_name = 'version'");
						$source_version = $dbal_source->sql_fetchfield('config_value');
						$dbal_source->sql_freeresult($result);

						if (!phpbb_version_compare($source_version, '3.1.0', '>='))
						{
							$board_merge_error = $this->user->lang($source_db_table_is_set ? 'TABLE_PREFIX_INVALID' : 'SOURCE_DB_VERSION_TOO_OLD', $source_version);
						}
						else
						{
							$factory = new \phpbb\db\tools\factory();
							$db_tools = $factory->get($dbal_source);

							if (!$db_tools->sql_column_exists($this->table_prefix . 'forums', 'target_forum_id'))
							{
								$db_tools->sql_column_add($this->table_prefix . 'forums', 'target_forum_id', array('UINT', 0), true);
								$db_tools->sql_create_index($this->table_prefix . 'forums', 'tg_forum_id', array('target_forum_id'));
							}

							if (!$db_tools->sql_column_exists($this->table_prefix . 'users', 'target_user_id'))
							{
								$db_tools->sql_column_add($this->table_prefix . 'users', 'target_user_id', array('UINT', 0), true);
								$db_tools->sql_create_index($this->table_prefix . 'users', 'tg_user_id', array('target_user_id'));
							}

							if (!$db_tools->sql_column_exists($this->table_prefix . 'topics', 'target_topic_id'))
							{
								$db_tools->sql_column_add($this->table_prefix . 'topics', 'target_topic_id', array('UINT', 0), true);
								$db_tools->sql_create_index($this->table_prefix . 'topics', 'tg_topic_id', array('target_topic_id'));
							}

							$template->assign_vars(array(
								'S_CONNECTION_CHECK_PASSED'	=> true,
							));

							$button_name_id = 'continue2';
							$button_text = 'CONTINUE_MERGE';
						}
					}
				}
			}
		}
		elseif ($this->request->is_set_post('continue2'))
		{
			if (!check_form_key('davidiq/boardmerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$dbal_source = new $dbms();

			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$compare_results = $user_merge_summary ? $this->process_users($dbal_source, false) : false;

				// Let's show the forum breakdown and try to match what is in the source DB to what is in the target DB
				// Admin will select what the target forums will be.
				$target_forums = make_forum_select(false, false, true, true, true, false, true);
				$sql = "SELECT DISTINCT f.forum_id, f.forum_name, f.target_forum_id
									FROM {$this->table_prefix}forums f
									JOIN {$this->table_prefix}topics t ON t.forum_id = f.forum_id
									WHERE f.forum_type = " . FORUM_POST;
				$result = $dbal_source->sql_query($sql);
				while ($row = $dbal_source->sql_fetchrow($result))
				{
					$template->assign_block_vars('forummapping', array(
						'SOURCE_FORUM_NAME' => $row['forum_name'],
						'TARGET_FORUM_LIST' => $this->build_target_forum_list($target_forums, $row)
					));
				}
				$dbal_source->sql_freeresult($result);

				$template->assign_vars(array(
					'MATCHED_USERS' => $compare_results !== false ? $compare_results['matched_users'] : false,
					'USERS_TO_ADD' => $compare_results !== false ? $compare_results['users_to_add'] : false,
					'S_MAPPING_PREP' => true,
				));

				$button_name_id = 'prepare';
				$button_text = 'PREPARE_MERGE';
			}
		}
		elseif ($this->request->is_set_post('prepare'))
		{
			if (!check_form_key('davidiq/boardmerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$dbal_source = new $dbms();

			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$var_names = $this->request->variable_names();

				// Set the target forum IDs
				foreach ($var_names as $var_name)
				{
					if (strstr($var_name, 'target_forum_from_') !== false)
					{
						$forum_id = (int)str_replace('target_forum_from_', '', $var_name);
						$target_forum_id = $this->request->variable($var_name, 0);
						$sql = "UPDATE {$this->table_prefix}forums
								SET target_forum_id = $target_forum_id
								WHERE forum_id = $forum_id";
						$dbal_source->sql_query($sql);
					}
				}
			}

			$template->assign_vars(array(
				'S_MERGE_READY'		=> true
			));

			$button_name_id = 'run';
			$button_text = 'RUN_MERGE';
		}

		$action = $this->request->variable('action', '');

		if (!$board_merge_error && $action)
		{
			$button_name_id = 'processing';

			if (!check_form_key('davidiq/boardmerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			if (!function_exists('sync'))
			{
				include("{$this->phpbb_root_path}includes/functions_admin.{$this->php_ext}");
			}

			$processing_complete = false;

			$dbal_source = new $dbms();

			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$factory = new \phpbb\db\tools\factory();
				$db_tools = $factory->get($this->db);

				switch ($action)
				{
					case 'process_users':
						// Let's disable the forum as part of this first step, if not already done
						if (!$this->config['board_disable'])
						{
							$this->config->set('board_disable', 1);
							$this->config->set('board_disable_msg', $this->user->lang('FORUM_DISABLED_MESSAGE'));
						}

						$this->process_users($dbal_source, true);

						$total_users = $this->request->variable('total_users', 0);

						if (!$total_users)
						{
							// Get some counts (the join ensures we have an accurate count)
							$sql = "SELECT COUNT(*) AS total_users
							FROM {$this->table_prefix}users
							WHERE user_lastvisit > 0 AND (user_posts > 0 OR user_new_privmsg > 0
												OR user_unread_privmsg > 0 OR user_last_privmsg > 0)";
							$result = $dbal_source->sql_query($sql);
							$total_users = (int)$dbal_source->sql_fetchfield('total_users');
							$dbal_source->sql_freeresult($result);
						}

						$sql = "SELECT COUNT(*) AS num_processed
							FROM {$this->table_prefix}users
							WHERE target_user_id > 0";
						$result = $dbal_source->sql_query($sql);
						$number_processed = (int) $dbal_source->sql_fetchfield('num_processed');
						$dbal_source->sql_freeresult($result);

						$user_processing_complete = ($number_processed >= $total_users);
						$template->assign_vars(array(
							'TOTAL_USERS'			=> $total_users,
							'U_ACTION_PROCESSING'	=> $this->u_action . ($user_processing_complete ? '&amp;action=process_topics' : '&amp;action=process_users'),
							'PROCESSING_MESSAGE'	=> $user_processing_complete ? $this->user->lang('USER_PROCESSING_COMPLETE') : $this->user->lang('PROCESSING_USERS', $number_processed, $total_users),
						));

						break;

					case 'process_topics':
						$target_topics_columns = $db_tools->sql_list_columns(TOPICS_TABLE);
						$target_posts_columns = $db_tools->sql_list_columns(POSTS_TABLE);

						$sql = "SELECT
								t.topic_id AS source_topic_id,
								f.target_forum_id AS forum_id, 
								t.icon_id,
								t.topic_attachment,
								t.topic_reported,
								t.topic_title,
								u1.target_user_id AS topic_poster,
								t.topic_time,
								t.topic_time_limit,
								t.topic_views,
								t.topic_status,
								t.topic_type,
								t.topic_first_poster_name,
								t.topic_first_poster_colour,
								u2.target_user_id AS topic_last_poster_id, 
								t.topic_last_poster_name, 
								t.topic_last_poster_colour, 
								t.topic_last_post_subject, 
								t.topic_last_post_time,
								t.topic_last_view_time,
								t.topic_bumped, 
								t.topic_bumper,
								t.poll_title,
								t.poll_start,
								t.poll_length,
								t.poll_max_options, 
								t.poll_last_vote,
								t.poll_vote_change, 
								t.topic_visibility,
								t.topic_delete_time, 
								t.topic_delete_reason, 
								t.topic_delete_user,
								t.topic_posts_approved,
								t.topic_posts_unapproved, 
								t.topic_posts_softdeleted
							FROM {$this->table_prefix}topics t
							JOIN {$this->table_prefix}forums f ON f.forum_id = t.forum_id
							LEFT JOIN {$this->table_prefix}users u1 ON u1.user_id = t.topic_poster
							LEFT JOIN {$this->table_prefix}users u2 ON u2.user_id = t.topic_last_poster_id
							WHERE t.target_topic_id = 0";
						$result = $dbal_source->sql_query_limit($sql, 300);
						$source_topics = $dbal_source->sql_fetchrowset($result);

						// Keep track of how many posts were processed so we can later break out
						$posts_processed = 0;

						foreach($source_topics as $row)
						{
							$sql_ary = $this->get_data_for_insert($row, $target_topics_columns);

							// First we take care of the topic
							$this->db->sql_query('INSERT INTO ' . TOPICS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
							$target_topic_id = $this->db->sql_nextid();

							$sql = "UPDATE {$this->table_prefix}topics
								SET target_topic_id = $target_topic_id
								WHERE topic_id = {$row['source_topic_id']}";
							$dbal_source->sql_query($sql);

							// Now we take care of the posts
							$sql = "SELECT
									f.target_forum_id AS forum_id,
									u1.target_user_id AS poster_id,
									p.icon_id,
									p.poster_ip,
									p.post_time,
									p.post_reported,
									p.enable_bbcode,
									p.enable_smilies,
									p.enable_magic_url,
									p.enable_sig,
									u1.username AS post_username,
									p.post_username AS orig_post_username,
									p.post_subject,
									p.post_text,
									p.post_checksum,
									p.post_attachment,
									p.bbcode_bitfield,
									p.bbcode_uid,
									p.post_postcount,
									p.post_edit_time,
									p.post_edit_reason,
									u2.target_user_id AS post_edit_user,
									p.post_edit_count,
									p.post_edit_locked,
									p.post_visibility,
									p.post_delete_time,
									p.post_delete_reason,
									u3.target_user_id AS post_delete_user
								FROM {$this->table_prefix}posts p
								JOIN {$this->table_prefix}forums f ON f.forum_id = p.forum_id
								LEFT JOIN {$this->table_prefix}users u1 ON u1.user_id = p.poster_id
								LEFT JOIN {$this->table_prefix}users u2 ON u2.user_id = p.post_edit_user
								LEFT JOIN {$this->table_prefix}users u3 ON u3.user_id = p.post_delete_user
								WHERE p.topic_id = {$row['source_topic_id']}
								ORDER BY p.post_time ASC";
							$result = $dbal_source->sql_query($sql);
							$first_post_id = $last_post_id = 0;

							// Let's take care of the posts for the topic
							while($post_row = $dbal_source->sql_fetchrow($result))
							{
								$sql_ary = $this->get_data_for_insert($post_row, $target_posts_columns);

								// This might be missing and is needed
								if (!isset($sql_ary['post_text']))
								{
									$sql_ary = array_merge($sql_ary, array(
										'post_text'	=> '',
									));
								}

								if (!isset($sql_ary['post_username']) && !empty($post_row['orig_post_username']))
								{
									$sql_ary = array_merge($sql_ary, array(
										'post_username'	=> $post_row['orig_post_username'],
									));
								}

								// Add this one separately in order to reduce the number of joins
								$sql_ary = array_merge($sql_ary, array(
									'topic_id'	=> $target_topic_id,
								));

								$this->db->sql_query('INSERT INTO ' . POSTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
								$last_post_id = $this->db->sql_nextid();

								if ($first_post_id === 0)
								{
									$first_post_id = $last_post_id;
									$sql = 'UPDATE ' . TOPICS_TABLE . "
										SET topic_first_post_id = {$first_post_id}
										WHERE topic_id = {$target_topic_id}";
									$this->db->sql_query($sql);
								}

								$posts_processed++;
							}
							$dbal_source->sql_freeresult($result);

							$sql = 'UPDATE ' . TOPICS_TABLE . "
								SET topic_last_post_id = {$last_post_id}
								WHERE topic_id = {$target_topic_id}";
							$this->db->sql_query($sql);

							// Once we've processed this many let's break out to prevent a timeout
							if ($posts_processed > 2000)
							{
								break;
							}
						}

						$total_topics = $this->request->variable('total_topics', 0);

						if (!$total_topics)
						{
							// Get some counts (the join ensures we have an accurate count)
							$sql = "SELECT COUNT(*) AS total_topics
							FROM {$this->table_prefix}topics t
							JOIN {$this->table_prefix}forums f ON f.forum_id = t.forum_id";
							$result = $dbal_source->sql_query($sql);
							$total_topics = (int) $dbal_source->sql_fetchfield('total_topics');
							$dbal_source->sql_freeresult($result);
						}

						$sql = "SELECT COUNT(*) AS num_processed
							FROM {$this->table_prefix}topics
							WHERE target_topic_id > 0";
						$result = $dbal_source->sql_query($sql);
						$number_processed = (int) $dbal_source->sql_fetchfield('num_processed');
						$dbal_source->sql_freeresult($result);

						$topic_processing_complete = ($number_processed >= $total_topics);
						$template->assign_vars(array(
							'TOTAL_TOPICS'			=> $total_topics,
							'U_ACTION_PROCESSING'	=> $this->u_action . ($topic_processing_complete ? '&amp;action=resync_stats' : '&amp;action=process_topics'),
							'PROCESSING_MESSAGE'	=> $topic_processing_complete ? $this->user->lang('TOPIC_PROCESSING_COMPLETE') : $this->user->lang('PROCESSING_TOPICS', $number_processed, $total_topics),
						));

						break;

					case 'resync_stats':
						$this->resync_stats($dbal_source);

						$template->assign_vars(array(
							'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;action=resync_post_counts',
							'PROCESSING_MESSAGE'	=> $this->user->lang('STAT_SYNC_COMPLETE'),
						));

						break;

					case 'resync_post_counts':
						$this->resync_post_counts();

						$template->assign_vars(array(
							'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;action=resync_dotted_topics',
							'PROCESSING_MESSAGE'	=> $this->user->lang('POST_COUNTS_SYNC_COMPLETE'),
						));

						break;

					case 'resync_dotted_topics':
						$this->resync_dotted_topics();

						$template->assign_vars(array(
							'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;action=resync_forums',
							'PROCESSING_MESSAGE'	=> $this->user->lang('DOTTED_TOPICS_SYNC_COMPLETE'),
						));

					case 'resync_forums':
						$index = $this->request->variable('index', 0);

						$sql = "SELECT DISTINCT target_forum_id
							FROM {$this->table_prefix}forums
							WHERE target_forum_id > 0
							ORDER BY target_forum_id";
						$result = $dbal_source->sql_query($sql);
						$target_forums = $dbal_source->sql_fetchrowset($result);
						$dbal_source->sql_freeresult($result);

						$target_forum_id = $index < count($target_forums) ? (int) $target_forums[$index]['target_forum_id'] : 0;

						if ($target_forum_id)
						{
							$resync_result = $this->resync_forums($target_forum_id, $index);
							$changed_index = $index !== (int) $resync_result['index'];
							$index = (int) $resync_result['index'];
							$target_forum_id = $index < count($target_forums) ? (int) $target_forums[$index]['target_forum_id'] : 0;

							if ($target_forum_id)
							{
								// Get the new forum name
								if ($changed_index)
								{
									$result = $this->db->sql_query_limit('SELECT forum_name FROM ' . FORUMS_TABLE . " WHERE forum_id = $target_forum_id", 1);
									$resync_result['forum_name'] = $this->db->sql_fetchfield('forum_name');
									$this->db->sql_freeresult($result);
								}

								$u_action = '&amp;' . http_build_query([
										'process'		=> 1,
										'action'		=> 'resync_forums',
										'index'			=> $index,
										'start'			=> $changed_index ? 0 : $resync_result['start'],
										'topics_done'	=> $changed_index ? 0 : $resync_result['topics_done'],
										'total_topics'	=> $changed_index ? 0 : $resync_result['total'],
									]);

								$template->assign_vars(array(
									'U_ACTION_PROCESSING' => $this->u_action . $u_action,
									'PROCESSING_MESSAGE' => $this->user->lang('PROCESSING_FORUM_SYNC', $resync_result['forum_name'], $resync_result['topics_done'], $resync_result['total']),
								));
							}
						}

						if (!$target_forum_id)
						{
							$template->assign_vars(array(
								'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;action=cache_purge',
								'PROCESSING_MESSAGE'	=> $this->user->lang('FORUM_SYNC_COMPLETE'),
							));
						}

						break;

					case 'cache_purge':
						$this->config->increment('assets_version', 1);
						$this->cache->purge();
						$this->phpbb_container->get('text_formatter.cache')->tidy();
						$this->auth->acl_clear_prefetch();
						phpbb_cache_moderators($this->db, $this->cache, $this->auth);

						$template->assign_vars(array(
							'U_ACTION_PROCESSING'	=> $this->u_action,
							'PROCESSING_MESSAGE'	=> $this->user->lang('PURGING_CACHE_COMPLETE'),
						));
						$processing_complete = true;

						break;
				}
			}

			if ($processing_complete)
			{
				$message = $this->user->lang('SOURCE_DB_NAME') . $this->user->lang('COLON') . ' ' . $source_db_name;
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOARD_MERGE_COMPLETE', false, array($message));

				// Re-enable the board if it's disabled and the disable message is our default one
				if ($this->config['board_disable'] && $this->config['board_disable_msg'] == $this->user->lang('FORUM_DISABLED_MESSAGE'))
				{
					$this->config->set('board_disable', '0');
				}

				trigger_error($this->user->lang('MERGE_COMPLETE', $source_db_name, $this->get_processing_time()) . adm_back_link($this->u_action));
			}
		}

		$template->assign_vars(array(
			'U_ACTION'					=> $this->u_action . ($this->request->is_set_post('prepare') ? '&amp;action=process_users' : ''),
			'BUTTON_NAME_ID'			=> $button_name_id,
			'BUTTON_TEXT'				=> $this->user->lang($button_text),
			'SOURCE_DB_NAME'			=> $source_db_name,
			'SOURCE_DB_USERNAME'		=> $source_db_username,
			'SOURCE_DB_PASSWORD'		=> $source_db_password,
			'S_USER_MERGE_SUMMARY'		=> $user_merge_summary,
			'SOURCE_TABLE_PREFIX'		=> $this->table_prefix,
			'TARGET_DB_NAME'			=> $dbname,
			'BOARD_MERGE_ERROR'			=> $board_merge_error,
			'BOARD_MERGE_INSTRUCTIONS'	=> $this->user->lang('ACP_BOARD_MERGE_INSTRUCTIONS', $this->config['version']),
			'PROCESSING_START'			=> !empty($action) ? $this->request->variable('processing_start', time()) : false,
			'S_PROCESSING'				=> !empty($action),
			'PROCESSING_NO_INTERRUPT'	=> $this->user->lang('PROCESSING_NO_INTERRUPT', $this->get_processing_time()),
		));
	}

	/**
	 * Gets the table prefix for the source database
	 *
	 * @param $dbal \phpbb\db\driver\driver_interface The database access layer for the source database
	 * @return string The table prefix
	 */
	protected function get_table_prefix($dbal)
	{
		$source_db_tools = new \phpbb\db\tools\tools($dbal);
		$tables = $source_db_tools->sql_list_tables();
		foreach($tables as $table_entry => $table_name)
		{
			// Looking for config_text table
			if (substr($table_name, -strlen('config_text')) === 'config_text')
			{
				return str_replace('config_text', '', $table_name);
			}
		}
		return;
	}

	/**
	 * Gets the target forum list for selection of which forum from the source gets mapped to which forum in the target
	 *
	 * @param $target_forums array List of forums in the target database
	 * @param $source_forum array List of forums in the source database
	 * @return string The drop-down list for rendering on the page
	 */
	protected function build_target_forum_list($target_forums, $source_forum)
	{
		$matching_forum = array_search($source_forum['forum_name'], array_column($target_forums, 'forum_name', 'forum_id'));
		$forum_list = '<select id="target_forum_from_' . $source_forum['forum_id'] . '" name="target_forum_from_' . $source_forum['forum_id'] . '">';
		foreach ($target_forums as $forum_id => $target_forum_info)
		{
			$selected = !$target_forum_info['disabled'] && ($forum_id === (int) $source_forum['target_forum_id']) || ($matching_forum && $matching_forum == $forum_id) ? 'selected="selected"' : '';
			$forum_list .= '<option value="' . $forum_id . '" ' . (($target_forum_info['disabled']) ?
								'disabled="disabled" class="disabled-option"' : $selected) . '>' . $target_forum_info['padding']
								. $target_forum_info['forum_name'] . '</option>';
		}
		$forum_list .= '</select>';

		return $forum_list;
	}

	/**
	 * Compares users and does merge as well
	 *
	 * @param $dbal_source \phpbb\db\driver\driver_interface
	 * @param $merge bool
	 * @return array
	 */
	protected function process_users($dbal_source, $merge)
	{
		$registered_ug = 0;

		if ($merge)
		{
			$sql = 'SELECT group_id
					FROM ' . GROUPS_TABLE . "
					WHERE group_type = 3 AND group_name = 'REGISTERED'";
			$result = $this->db->sql_query($sql);
			$registered_ug = (int) $this->db->sql_fetchfield('group_id');
			$this->db->sql_freeresult($result);

			// We will need this for adding users
			if (!function_exists('group_user_add'))
			{
				include("{$this->phpbb_root_path}includes/functions_user.{$this->php_ext}");
			}
		}

		// Check users to see which ones match between the two databases
		$matched_users = $users_to_add = 0;
		$result = $this->db->sql_query('SELECT user_id, username_clean, user_email FROM ' . USERS_TABLE);
		$target_users = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$sql = "SELECT
					user_id AS source_user_id,
					target_user_id,
					user_type,
					user_permissions,
					user_perm_from,
					user_ip,
					user_regdate,
					username,
					username_clean,
					user_password,
					user_passchg,
					user_email,
					user_email_hash,
					user_birthday,
					user_lastvisit,
					user_lastmark,
					user_lastpost_time,
					user_lastpage,
					user_last_confirm_key,
					user_last_search,
					user_warnings,
					user_last_warning,
					user_login_attempts,
					user_inactive_reason,
					user_inactive_time,
					user_posts,
					user_lang,
					user_timezone,
					user_dateformat,
					user_style,
					user_rank,
					user_colour,
					user_message_rules,
					user_full_folder,
					user_emailtime,
					user_topic_show_days,
					user_topic_sortby_type,
					user_topic_sortby_dir,
					user_post_show_days,
					user_post_sortby_type,
					user_post_sortby_dir,
					user_notify,
					user_notify_pm,
					user_notify_type,
					user_allow_pm,
					user_allow_viewonline,
					user_allow_viewemail,
					user_allow_massemail,
					user_options,
					user_avatar,
					user_avatar_type,
					user_avatar_width,
					user_avatar_height,
					user_sig,
					user_sig_bbcode_uid,
					user_sig_bbcode_bitfield,
					user_jabber,
					user_actkey,
					user_newpasswd,
					user_form_salt,
					user_new,
					user_reminded,
					user_reminded_time
				FROM {$this->table_prefix}users
				WHERE user_lastvisit > 0 AND (user_posts > 0 OR user_new_privmsg > 0
					OR user_unread_privmsg > 0 OR user_last_privmsg > 0)";

		if ($merge)
		{
			$sql .= ' AND target_user_id = 0';
			$result = $dbal_source->sql_query_limit($sql, 200);
		}
		else
		{
			$result = $dbal_source->sql_query($sql);
		}

		$factory = new \phpbb\db\tools\factory();
		$db_tools = $factory->get($this->db);
		$target_users_columns = $db_tools->sql_list_columns(USERS_TABLE);

		while ($row = $dbal_source->sql_fetchrow($result))
		{
			$target_user_id = (int) $row['target_user_id'];

			if (!$target_user_id)
			{
				$target_user_id = array_search($row['username_clean'], array_column($target_users, 'username_clean', 'user_id'));
				if ($target_user_id === false)
				{
					$target_user_id = array_search($row['user_email'], array_column($target_users, 'user_email', 'user_id'));
				}
			}

			if ($target_user_id === false)
			{
				$users_to_add++;
				if ($merge)
				{
					$sql_ary = $this->get_data_for_insert($row, $target_users_columns);

					// This might be missing and is needed
					if (!isset($sql_ary['user_permissions']))
					{
						$sql_ary = array_merge($sql_ary, array(
							'user_permissions'	=> '',
						));
					}

					$this->db->sql_query('INSERT INTO ' . USERS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
					$target_user_id = $this->db->sql_nextid();
					group_user_add($registered_ug, array($target_user_id), false, false, true);
				}
			}

			if ($target_user_id)
			{
				$matched_users++;
				if ($merge)
				{
					$dbal_source->sql_query("UPDATE {$this->table_prefix}users
											SET target_user_id = $target_user_id
											WHERE user_id = {$row['source_user_id']}");
					$this->db->sql_query('UPDATE ' . USERS_TABLE . "
											SET user_regdate = {$row['user_regdate']}
											WHERE user_id = {$target_user_id}");
				}
			}
		}
		$dbal_source->sql_freeresult($result);

		return [
			'matched_users'		=> $matched_users,
			'users_to_add'		=> $users_to_add,
		];
	}

	/**
	 * Checks the columns in the row against the existing columns in the target database and appends each to the insert array if it exists.
	 *
	 * @param $row array The row of data to compare against the target database's column list
	 * @param $column_list array The columns in the target database
	 * @return array Data to be inserted to the target database
	 */
	protected function get_data_for_insert($row, $column_list)
	{
		$sql_ary = array();
		foreach ($row as $col_name => $value)
		{
			if (isset($column_list[$col_name]) && (!empty($value) || is_numeric($value)))
			{
				$sql_ary = array_merge($sql_ary, array(
					$col_name => $value,
				));
			}
		}
		return $sql_ary;
	}

	/**
	 * Re-synchronizes statistics
	 *
	 * @param $dbal_source \phpbb\db\driver\driver_interface
	 */
	protected function resync_stats($dbal_source)
	{
		// Let's re-sync stats
		$sql = 'SELECT COUNT(post_id) AS stat
							FROM ' . POSTS_TABLE . '
							WHERE post_visibility = ' . ITEM_APPROVED;
		$result = $this->db->sql_query($sql);
		$this->config->set('num_posts', (int) $this->db->sql_fetchfield('stat'), false);
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(topic_id) AS stat
							FROM ' . TOPICS_TABLE . '
							WHERE topic_visibility = ' . ITEM_APPROVED;
		$result = $this->db->sql_query($sql);
		$this->config->set('num_topics', (int) $this->db->sql_fetchfield('stat'), false);
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(user_id) AS stat
							FROM ' . USERS_TABLE . '
							WHERE user_type IN (' . USER_NORMAL . ',' . USER_FOUNDER . ')';
		$result = $this->db->sql_query($sql);
		$this->config->set('num_users', (int) $this->db->sql_fetchfield('stat'), false);
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(attach_id) as stat
							FROM ' . ATTACHMENTS_TABLE . '
							WHERE is_orphan = 0';
		$result = $this->db->sql_query($sql);
		$this->config->set('num_files', (int) $this->db->sql_fetchfield('stat'), false);
		$this->db->sql_freeresult($result);

		$sql = 'SELECT SUM(filesize) as stat
							FROM ' . ATTACHMENTS_TABLE . '
							WHERE is_orphan = 0';
		$result = $this->db->sql_query($sql);
		$this->config->set('upload_dir_size', (float) $this->db->sql_fetchfield('stat'), false);
		$this->db->sql_freeresult($result);

		// Grab the board start date from the old forum
		$sql = "SELECT config_value
				FROM {$this->table_prefix}config
				WHERE config_name = 'board_startdate'";
		$result = $dbal_source->sql_query($sql);
		$source_startdate = (int) $dbal_source->sql_fetchfield('config_value');
		$dbal_source->sql_freeresult($result);

		if ($source_startdate < (int) $this->config['board_startdate'])
		{
			$this->config->set('board_startdate', $source_startdate);
		}

		if (!function_exists('update_last_username'))
		{
			include("{$this->phpbb_root_path}includes/functions_user.{$this->php_ext}");
		}
		update_last_username();
	}

	/**
	 * Re-synchronize post counts
	 */
	protected function resync_post_counts()
	{
		// Re-sync post counts
		$start = $max_post_id = 0;

		// Find the maximum post ID, we can only stop the cycle when we've reached it
		$sql = 'SELECT MAX(forum_last_post_id) as max_post_id
							FROM ' . FORUMS_TABLE;
		$result = $this->db->sql_query($sql);
		$max_post_id = (int) $this->db->sql_fetchfield('max_post_id');
		$this->db->sql_freeresult($result);

		// No maximum post id? :o
		if (!$max_post_id)
		{
			$sql = 'SELECT MAX(post_id) as max_post_id
								FROM ' . POSTS_TABLE;
			$result = $this->db->sql_query($sql);
			$max_post_id = (int) $this->db->sql_fetchfield('max_post_id');
			$this->db->sql_freeresult($result);
		}

		if (!$max_post_id)
		{
			return;
		}

		$step = ($this->config['num_posts']) ? (max((int) ($this->config['num_posts'] / 5), 20000)) : 20000;
		$this->db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_posts = 0');

		while ($start < $max_post_id)
		{
			$sql = 'SELECT COUNT(post_id) AS num_posts, poster_id
								FROM ' . POSTS_TABLE . '
								WHERE post_id BETWEEN ' . ($start + 1) . ' AND ' . ($start + $step) . '
									AND post_postcount = 1 AND post_visibility = ' . ITEM_APPROVED . '
								GROUP BY poster_id';
			$result = $this->db->sql_query($sql);

			if ($row = $this->db->sql_fetchrow($result))
			{
				do
				{
					$sql = 'UPDATE ' . USERS_TABLE . " SET user_posts = user_posts + {$row['num_posts']} WHERE user_id = {$row['poster_id']}";
					$this->db->sql_query($sql);
				}
				while ($row = $this->db->sql_fetchrow($result));
			}
			$this->db->sql_freeresult($result);

			$start += $step;
		}
	}

	/**
	 * Re-synchronize dotted topics
	 */
	protected function resync_dotted_topics()
	{
		switch ($this->db->get_sql_layer())
		{
			case 'sqlite3':
				$this->db->sql_query('DELETE FROM ' . TOPICS_POSTED_TABLE);
				break;

			default:
				$this->db->sql_query('TRUNCATE TABLE ' . TOPICS_POSTED_TABLE);
				break;
		}

		$get_from_time = time() - (30 * 4 * 7 * 24 * 60 * 60);

		// Select forum ids, do not include categories
		$sql = 'SELECT forum_id
							FROM ' . FORUMS_TABLE . '
							WHERE forum_type <> ' . FORUM_CAT;
		$result = $this->db->sql_query($sql);

		$forum_ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$forum_ids[] = $row['forum_id'];
		}
		$this->db->sql_freeresult($result);

		// Any global announcements? ;)
		$forum_ids[] = 0;

		// Now go through the forums and get us some topics...
		foreach ($forum_ids as $forum_id)
		{
			$sql = 'SELECT p.poster_id, p.topic_id
								FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t
								WHERE t.forum_id = ' . $forum_id . '
									AND t.topic_moved_id = 0
									AND t.topic_last_post_time > ' . $get_from_time . '
									AND t.topic_id = p.topic_id
									AND p.poster_id <> ' . ANONYMOUS . '
								GROUP BY p.poster_id, p.topic_id';
			$result = $this->db->sql_query($sql);

			$posted = array();
			while ($row = $this->db->sql_fetchrow($result))
			{
				$posted[$row['poster_id']][] = $row['topic_id'];
			}
			$this->db->sql_freeresult($result);

			$sql_ary = array();
			foreach ($posted as $user_id => $topic_row)
			{
				foreach ($topic_row as $topic_id)
				{
					$sql_ary[] = array(
						'user_id'		=> (int) $user_id,
						'topic_id'		=> (int) $topic_id,
						'topic_posted'	=> 1,
					);
				}
			}
			unset($posted);

			if (sizeof($sql_ary))
			{
				$this->db->sql_multi_insert(TOPICS_POSTED_TABLE, $sql_ary);
			}
		}
	}

	/**
	 * Re-synchronize forums
	 *
	 * @param $forum_id int The forum ID for which the sync needs to be performed
	 * @param $index int The index number in the forum_id list to process
	 * @return array Results of re-synchronization
	 */
	protected function resync_forums($forum_id, $index)
	{
		$sql = 'SELECT forum_name, (forum_topics_approved + forum_topics_unapproved + forum_topics_softdeleted) AS total_topics
					FROM ' . FORUMS_TABLE . "
					WHERE forum_id = $forum_id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			$row = array();
		}

		if (isset($row['total_topics']) && $row['total_topics'])
		{
			$sql = 'SELECT MIN(topic_id) as min_topic_id, MAX(topic_id) as max_topic_id
						FROM ' . TOPICS_TABLE . '
						WHERE forum_id = ' . $forum_id;
			$result = $this->db->sql_query($sql);
			$row2 = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			// Typecast to int if there is no data available
			$row2['min_topic_id'] = (int) $row2['min_topic_id'];
			$row2['max_topic_id'] = (int) $row2['max_topic_id'];

			$start = $this->request->variable('start', $row2['min_topic_id']);

			$batch_size = 2000;
			$end = $start + $batch_size;

			// Sync all topics in batch mode...
			sync('topic', 'range', 'topic_id BETWEEN ' . $start . ' AND ' . $end, true, true);

			if ($end < $row2['max_topic_id'])
			{
				$sql = 'SELECT COUNT(topic_id) as num_topics
							FROM ' . TOPICS_TABLE . '
							WHERE forum_id = ' . $forum_id . '
								AND topic_id BETWEEN ' . $start . ' AND ' . $end;
				$result = $this->db->sql_query($sql);
				$topics_done = $this->request->variable('topics_done', 0) + (int) $this->db->sql_fetchfield('num_topics');
				$this->db->sql_freeresult($result);

				$start += $batch_size;

				return [
					'forum_name'	=> $row['forum_name'],
					'index'			=> $index,
					'start'			=> $start,
					'topics_done'	=> $topics_done,
					'total'			=> $row['total_topics'],
				];
			}

			sync('forum', 'forum_id', $forum_id, false, true);

			// The sync function goes off of the post_id instead of post_time to get latest post so we'll need to fix that
			$sql = 'SELECT 	topic_last_post_id,
							topic_last_poster_id,
							topic_last_post_subject,
							topic_last_poster_name,
							topic_last_poster_colour,
							MAX(topic_last_post_time) AS topic_last_post_time
					FROM ' . TOPICS_TABLE . "
					WHERE forum_id = $forum_id AND topic_visibility = " . ITEM_APPROVED;

			$result = $this->db->sql_query_limit($sql, 1);
			$latest_topic = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$sql = 'UPDATE ' . FORUMS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', array(
						'forum_last_post_id'		=> $latest_topic['topic_last_post_id'],
						'forum_last_poster_id'		=> $latest_topic['topic_last_poster_id'],
						'forum_last_post_subject'	=> $latest_topic['topic_last_post_subject'],
						'forum_last_post_time'		=> $latest_topic['topic_last_post_time'],
						'forum_last_poster_name'	=> $latest_topic['topic_last_poster_name'],
						'forum_last_poster_colour'	=> $latest_topic['topic_last_poster_colour'],
				)) . " WHERE forum_id = $forum_id";
			$this->db->sql_query($sql);
		}

		$index++;

		return [
			'forum_name'	=> isset($row['forum_name']) ? $row['forum_name'] : '',
			'index'			=> $index,
			'start'			=> 0,
			'topics_done'	=> 0,
			'total'			=> isset($row['total_topics']) ? $row['total_topics'] : '',
		];
	}

	/**
	 * Gets the elapsed processing time
	 *
	 * @return float The elapsed processing time
	 */
	protected function get_processing_time()
	{
		$processing_start = $this->request->variable('processing_start', 0);
		return round(abs(time() - $processing_start) / 60, 2);
	}
}
