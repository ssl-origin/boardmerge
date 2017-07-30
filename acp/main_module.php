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
		global $config, $request, $template, $user, $db, $dbms, $dbhost, $dbname, $dbuser, $phpbb_container;

		$button_name_id = 'continue';
		$button_text = $user->lang['CONTINUE_MERGE'];
		$forum_merge_error = false;
		$this->tpl_name = 'acp_forummerge_body';
		$this->page_title = $user->lang['ACP_FORUM_MERGE_TITLE'];
		add_form_key('davidiq/forummerge');

		$source_db_name = $request->variable('source_db_name', '');
		$source_db_username = $request->variable('source_db_username', $dbuser);
		$source_db_password = $request->variable('source_db_password', '');
		$table_prefix = $request->variable('table_prefix', '');

		if (strpos($dbms, 'phpbb\db\driver') === false && class_exists('phpbb\db\driver\\' . $dbms))
		{
			$dbms = 'phpbb\db\driver\\' . $dbms;
		}

		if ($request->is_set_post('continue'))
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
						$result = $dbal_source->sql_query("SELECT * FROM {$table_prefix}config WHERE config_name = 'version'");
						$source_version = $dbal_source->sql_fetchfield('config_value');
						$dbal_source->sql_freeresult($result);
						if ($source_version != $config['version'])
						{
							$forum_merge_error = sprintf($user->lang['SOURCE_DB_VERSION_MISMATCH'], $source_version, $config['version']);
						}
						else
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
							while ($row = $dbal_source->sql_fetchrow($result))
							{
								$template->assign_block_vars('forummapping', array(
									'SOURCE_FORUM_NAME'		=> $row['forum_name'],
									'TARGET_FORUM_LIST'		=> $this->build_target_forum_list($target_forums, $row)
								));
							}
							$dbal_source->sql_freeresult($result);

							$template->assign_vars(array(
								'MATCHED_USERS'				=> $compare_results['matched_users'],
								'ADDING_USERS'				=> $compare_results['adding_users'],
								'S_CONNECTION_CHECK_PASSED'	=> true,
							));

							$button_name_id = 'prepare';
							$button_text = $user->lang['PREPARE_MERGE'];
						}
					}
				}
			}
		}
		elseif ($request->is_set_post('prepare'))
		{
			// TODO: Add target_forum_id column to source db forums table
			// TODO: Add target_user_id column to source db users table
			// TODO: Get the form keys for mapping the source forum to the target forum
			// TODO: Assign target_forum_id and target_user_id

			$template->assign_vars(array(
				'S_MERGE_READY'		=> true
			));

			$button_name_id = 'run';
			$button_text = $user->lang['RUN_MERGE'];
		}

		if (!$forum_merge_error && $request->is_set_post('run'))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			trigger_error($user->lang['ACP_FORUM_MERGE_COMPLETED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'U_ACTION'					=> $this->u_action,
			'BUTTON_NAME_ID'			=> $button_name_id,
			'BUTTON_TEXT'				=> $button_text,
			'SOURCE_DB_NAME'			=> $source_db_name,
			'SOURCE_DB_USERNAME'		=> $source_db_username,
			'SOURCE_DB_PASSWORD'		=> $source_db_password,
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

	public function compare_users($dbal_source, $table_prefix, $db, $prepare)
	{
		// Check users to see which ones match between the two databases
		$matched_users = $adding_users = 0;
		$result = $db->sql_query("SELECT user_id, username_clean, user_email FROM " . USERS_TABLE);
		$target_users = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$result = $dbal_source->sql_query("SELECT *
											FROM {$table_prefix}users
											WHERE user_lastvisit > 0 AND (user_posts > 0 OR user_new_privmsg > 0
												OR user_unread_privmsg > 0 OR user_last_privmsg > 0)");

		while ($row = $dbal_source->sql_fetchrow($result))
		{
			$target_user_id = array_search($row['username_clean'], array_column($target_users, 'username_clean', 'user_id'));
			if ($target_user_id === false)
			{
				$target_user_id = array_search($row['user_email'], array_column($target_users, 'user_email', 'user_id'));
			}
			if ($target_user_id !== false)
			{
				$matched_users++;
				if ($prepare)
				{
					$dbal_source->sql_query("UPDATE {$table_prefix}users
											SET target_user_id = $target_user_id
											WHERE user_id = {$row['user_id']}");
				}
			}
			else
			{
				$adding_users++;
				if ($prepare)
				{
					// TODO: Insert user into target db (user_add function in functions_user.php) and retrieve inserted user_id
					// TODO: Update user in source db as above
				}
			}
		}
		$dbal_source->sql_freeresult($result);

		return [
			'matched_users'		=> $matched_users,
			'adding_users'		=> $adding_users,
		];
	}
}
