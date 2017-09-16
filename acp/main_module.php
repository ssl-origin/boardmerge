<?php
/**
 *
 * Forum Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\forummerge\acp;

/**
 * Forum Merge ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user, $db, $dbms, $dbhost, $dbname, $dbuser;

		$button_name_id = 'continue1';
		$button_text = 'CONTINUE_MERGE';
		$forum_merge_error = false;
		$this->tpl_name = 'acp_forummerge_body';
		$this->page_title = $user->lang['ACP_FORUM_MERGE_TITLE'];
		add_form_key('davidiq/forummerge');

		$source_db_name = $request->variable('source_db_name', '');
		$source_db_username = $request->variable('source_db_username', $dbuser);
		$source_db_password = $request->variable('source_db_password', '');
		$table_prefix = $request->variable('source_table_prefix', '');

		if (strpos($dbms, 'phpbb\db\driver') === false && class_exists('phpbb\db\driver\\' . $dbms))
		{
			$dbms = 'phpbb\db\driver\\' . $dbms;
		}

		if ($request->is_set_post('continue1'))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			if ($source_db_name == $dbname)
			{
				$forum_merge_error = $user->lang['SOURCE_DB_NAME_SAME_AS_TARGET'];
			}
			else
			{
				// Check database connection
				$dbal_source = new $dbms();
				$dbal_source->sql_return_on_error(true);
				if (is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
				{
					$sql_error = $dbal_source->sql_error();
					$forum_merge_error = sprintf($user->lang['SOURCE_DB_CONNECTION_ERROR'], $source_db_name, $sql_error['code'], $sql_error['message']);
				}
				else
				{
					$dbal_source->sql_return_on_error(false);

					// Check database version
					$table_prefix = $this->get_table_prefix($dbal_source);
					if (!$table_prefix)
					{
						$forum_merge_error = $user->lang['SOURCE_DB_INVALID'];
					}
					else
					{
						$result = $dbal_source->sql_query("SELECT config_value FROM {$table_prefix}config WHERE config_name = 'version'");
						$source_version = $dbal_source->sql_fetchfield('config_value');
						$dbal_source->sql_freeresult($result);
						if ($source_version != $config['version'])
						{
							$forum_merge_error = sprintf($user->lang['SOURCE_DB_VERSION_MISMATCH'], $source_version, $config['version']);
						}
						else
						{
							$factory = new \phpbb\db\tools\factory();
							$db_tools = $factory->get($dbal_source);

							if (!$db_tools->sql_column_exists($table_prefix . 'forums', 'target_forum_id'))
							{
								$db_tools->sql_column_add($table_prefix . 'forums', 'target_forum_id', array('UINT', 0), true);
							}

							if (!$db_tools->sql_column_exists($table_prefix . 'users', 'target_user_id'))
							{
								$db_tools->sql_column_add($table_prefix . 'users', 'target_user_id', array('UINT', 0), true);
							}

							if (!$db_tools->sql_column_exists($table_prefix . 'topics', 'target_topic_id'))
							{
								$db_tools->sql_column_add($table_prefix . 'topics', 'target_topic_id', array('UINT', 0), true);
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
		elseif ($request->is_set_post('continue2'))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$dbal_source = new $dbms();
			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$compare_results = $this->compare_users($dbal_source, $table_prefix, $db, false);

				// Let's show the forum breakdown and try to match what is in the source DB to what is in the target DB
				// Admin will select what the target forums will be.
				$target_forums = make_forum_select(false, false, true, true, true, false, true);
				$sql = "SELECT DISTINCT f.forum_id, f.forum_name
									FROM {$table_prefix}forums f
									JOIN {$table_prefix}topics t ON t.forum_id = f.forum_id
									WHERE f.forum_type = " . FORUM_POST;
				$result = $dbal_source->sql_query($sql);
				while ($row = $dbal_source->sql_fetchrow($result)) {
					$template->assign_block_vars('forummapping', array(
						'SOURCE_FORUM_NAME' => $row['forum_name'],
						'TARGET_FORUM_LIST' => $this->build_target_forum_list($target_forums, $row)
					));
				}
				$dbal_source->sql_freeresult($result);

				$template->assign_vars(array(
					'MATCHED_USERS' => $compare_results['matched_users'],
					'ADDING_USERS' => $compare_results['adding_users'],
					'S_MAPPING_PREP' => true,
				));

				$button_name_id = 'prepare';
				$button_text = 'PREPARE_MERGE';
			}
		}
		elseif ($request->is_set_post('prepare'))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$dbal_source = new $dbms();
			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$var_names = $request->variable_names();

				// Set the target forum IDs
				foreach ($var_names as $var_name)
				{
					if (strstr($var_name, 'target_forum_from_') !== false)
					{
						$forum_id = (int)str_replace('target_forum_from_', '', $var_name);
						$target_forum_id = $request->variable($var_name, 0);
						$sql = "UPDATE {$table_prefix}forums SET target_forum_id = $target_forum_id
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

		if (!$forum_merge_error && $request->variable('process', false))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			$topic_processing_complete = false;

			$dbal_source = new $dbms();
			if (!is_array($dbal_source->sql_connect($dbhost, $source_db_username, $source_db_password, $source_db_name)))
			{
				$factory = new \phpbb\db\tools\factory();
				$db_tools = $factory->get($db);

				if ($request->variable('process_users', false))
				{
					$this->compare_users($dbal_source, $table_prefix, $db, true);

					// Get some counts (the join ensures we have an accurate count)
					$sql = "SELECT COUNT(*) AS total_users
							FROM {$table_prefix}users
							WHERE user_lastvisit > 0 AND (user_posts > 0 OR user_new_privmsg > 0
												OR user_unread_privmsg > 0 OR user_last_privmsg > 0)";
					$result = $dbal_source->sql_query($sql);
					$total_users = (int) $dbal_source->sql_fetchfield('total_users');
					$dbal_source->sql_freeresult($result);

					$sql = "SELECT COUNT(*) AS num_processed
							FROM {$table_prefix}users
							WHERE target_user_id > 0";
					$result = $dbal_source->sql_query($sql);
					$number_processed = (int) $dbal_source->sql_fetchfield('num_processed');
					$dbal_source->sql_freeresult($result);

					$user_processing_complete = ($number_processed >= $total_users);
					$template->assign_vars(array(
						'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;process=1' . ($user_processing_complete ? '&amp;process_topics=1' : '&amp;process_users=1'),
						'PROCESSING_MESSAGE'	=> $user_processing_complete ? $user->lang('USER_PROCESSING_COMPLETE') : $user->lang('PROCESSING_USERS', $number_processed, $total_users),
						'S_PROCESSING'			=> true,
					));
				}
				else if ($request->variable('process_topics', false))
				{
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
							FROM {$table_prefix}topics t
							JOIN {$table_prefix}forums f ON f.forum_id = t.forum_id
							LEFT JOIN {$table_prefix}users u1 ON u1.user_id = t.topic_poster
							LEFT JOIN {$table_prefix}users u2 ON u2.user_id = t.topic_last_poster_id
							WHERE t.target_topic_id = 0";
					$result = $dbal_source->sql_query_limit($sql, 500);
					$source_topics = $dbal_source->sql_fetchrowset($result);

					foreach($source_topics as $row)
					{
						$sql_ary = $this->get_data_for_insert($row, $target_topics_columns);

						// First we take care of the topic
						$db->sql_query('INSERT INTO ' . TOPICS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
						$target_topic_id = $db->sql_nextid();

						$sql = "UPDATE {$table_prefix}topics
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
									p.post_username,
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
								FROM {$table_prefix}posts p
								JOIN {$table_prefix}forums f ON f.forum_id = p.forum_id
								LEFT JOIN {$table_prefix}users u1 ON u1.user_id = p.poster_id
								LEFT JOIN {$table_prefix}users u2 ON u2.user_id = p.post_edit_user
								LEFT JOIN {$table_prefix}users u3 ON u3.user_id = p.post_delete_user
								WHERE p.topic_id = {$row['source_topic_id']}
								ORDER BY p.post_time ASC";
						$result = $dbal_source->sql_query($sql);
						$first_post_id = $last_post_id = 0;

						// Let's take care of the posts for the topic
						while($post_row = $dbal_source->sql_fetchrow($result))
						{
							$sql_ary = $this->get_data_for_insert($post_row, $target_posts_columns);

							// Add this one separately in order to reduce the number of joins
							$sql_ary = array_merge($sql_ary, array(
								'topic_id'	=> $target_topic_id,
							));

							$db->sql_query('INSERT INTO ' . POSTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
							$last_post_id = $db->sql_nextid();

							if ($first_post_id === 0)
							{
								$first_post_id = $last_post_id;
								$sql = 'UPDATE ' . TOPICS_TABLE . "
										SET topic_first_post_id = {$first_post_id}
										WHERE topic_id = {$target_topic_id}";
								$db->sql_query($sql);
							}
						}
						$dbal_source->sql_freeresult($result);

						$sql = 'UPDATE ' . TOPICS_TABLE . "
								SET topic_last_post_id = {$last_post_id}
								WHERE topic_id = {$target_topic_id}";
						$db->sql_query($sql);
					}

					// Get some counts (the join ensures we have an accurate count)
					$sql = "SELECT COUNT(*) AS total_topics
							FROM {$table_prefix}topics t
							JOIN {$table_prefix}forums f ON f.forum_id = t.forum_id";
					$result = $dbal_source->sql_query($sql);
					$total_topics = (int) $dbal_source->sql_fetchfield('total_topics');
					$dbal_source->sql_freeresult($result);

					$sql = "SELECT COUNT(*) AS num_processed
							FROM {$table_prefix}topics
							WHERE target_topic_id > 0";
					$result = $dbal_source->sql_query($sql);
					$number_processed = (int) $dbal_source->sql_fetchfield('num_processed');
					$dbal_source->sql_freeresult($result);

					$topic_processing_complete = ($number_processed >= $total_topics);
					$template->assign_vars(array(
						'U_ACTION_PROCESSING'	=> $this->u_action . '&amp;process=1&amp;process_topics=1',
						'PROCESSING_MESSAGE'	=> $user->lang('PROCESSING_TOPICS', $number_processed, $total_topics),
						'S_PROCESSING'			=> true,
					));
				}
			}

			if ($topic_processing_complete)
			{
				trigger_error($user->lang('TOPIC_PROCESSING_COMPLETE') . adm_back_link($this->u_action));
			}
		}

		$template->assign_vars(array(
			'U_ACTION'					=> $this->u_action . ($request->is_set_post('prepare') ? '&amp;process=1&amp;process_users=1' : ''),
			'BUTTON_NAME_ID'			=> $button_name_id,
			'BUTTON_TEXT'				=> $user->lang[$button_text],
			'SOURCE_DB_NAME'			=> $source_db_name,
			'SOURCE_DB_USERNAME'		=> $source_db_username,
			'SOURCE_DB_PASSWORD'		=> $source_db_password,
			'SOURCE_TABLE_PREFIX'		=> $table_prefix,
			'TARGET_DB_NAME'			=> $dbname,
			'FORUM_MERGE_ERROR'			=> $forum_merge_error,
			'FORUM_MERGE_INSTRUCTIONS'	=> sprintf($user->lang['ACP_FORUM_MERGE_INSTRUCTIONS'], $config['version']),
		));
	}

	public function get_table_prefix($dbal)
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
		return false;
	}

	public function build_target_forum_list($target_forums, $source_forum)
	{
		$matching_forum = array_search($source_forum['forum_name'], array_column($target_forums, 'forum_name', 'forum_id'));
		$forum_list = '<select id="target_forum_from_' . $source_forum['forum_id'] . '" name="target_forum_from_' . $source_forum['forum_id'] . '">';
		foreach ($target_forums as $forum_id => $target_forum_info)
		{
			$selected = !$target_forum_info['disabled'] && $matching_forum && $matching_forum == $forum_id ? 'selected="selected"' : '';
			$forum_list .= '<option value="' . $forum_id . '" ' . (($target_forum_info['disabled']) ?
								'disabled="disabled" class="disabled-option"' : $selected) . '>' . $target_forum_info['padding']
								. $target_forum_info['forum_name'] . '</option>';
		}
		$forum_list .= '</select>';

		return $forum_list;
	}

	public function compare_users($dbal_source, $table_prefix, $db, $merge)
	{
		$registered_ug = 0;

		if ($merge)
		{
			$sql = 'SELECT group_id
					FROM ' . GROUPS_TABLE . "
					WHERE group_type = 3 AND group_name = 'REGISTERED'";
			$result = $db->sql_query($sql);
			$registered_ug = (int) $db->sql_fetchfield('group_id');
			$db->sql_freeresult($result);

			// We will need this for adding users
			if (!function_exists('group_user_add'))
			{
				global $phpEx, $phpbb_root_path;
				include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
			}
		}

		// Check users to see which ones match between the two databases
		$matched_users = $adding_users = 0;
		$result = $db->sql_query("SELECT user_id, username_clean, user_email FROM " . USERS_TABLE);
		$target_users = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$sql = "SELECT
					user_id AS source_user_id,
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
					user_new_privmsg,
					user_unread_privmsg,
					user_last_privmsg,
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
				FROM {$table_prefix}users
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
		$source_users = $dbal_source->sql_fetchrowset($result);
		$dbal_source->sql_freeresult($result);

		$factory = new \phpbb\db\tools\factory();
		$db_tools = $factory->get($db);
		$target_users_columns = $db_tools->sql_list_columns(USERS_TABLE);

		foreach ($source_users as $row)
		{
			$target_user_id = array_search($row['username_clean'], array_column($target_users, 'username_clean', 'user_id'));
			if ($target_user_id === false)
			{
				$target_user_id = array_search($row['user_email'], array_column($target_users, 'user_email', 'user_id'));
			}

			if ($target_user_id === false)
			{
				$adding_users++;
				if ($merge)
				{
					$sql_ary = $this->get_data_for_insert($row, $target_users_columns);
					$db->sql_query('INSERT INTO ' . USERS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
					$target_user_id = $db->sql_nextid();
					group_user_add($registered_ug, array($target_user_id), false, false, true);
				}
			}

			if ($target_user_id)
			{
				$matched_users++;
				if ($merge)
				{
					$dbal_source->sql_query("UPDATE {$table_prefix}users
											SET target_user_id = $target_user_id
											WHERE user_id = {$row['source_user_id']}");
				}
			}
		}

		return [
			'matched_users'		=> $matched_users,
			'adding_users'		=> $adding_users,
		];
	}

	private function get_data_for_insert($row, $column_list)
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
}
