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

		$forum_merge_error = false;
		$db_source = false;
		$this->tpl_name = 'acp_forummerge_body';
		$this->page_title = $user->lang['ACP_FORUM_MERGE_TITLE'];
		add_form_key('davidiq/forummerge');

		$source_db_name = $request->variable('source_db_name', '');
		$source_db_username = $request->variable('source_db_username', $dbuser);
		$source_db_password = $request->variable('source_db_password', '');

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
					// Check database version
					$table_prefix = $this->get_table_prefix($dbal_source);
					if (!$table_prefix)
					{
						$forum_merge_error = $user->lang['SOURCE_DB_INVALID'];
					}
					else
					{
						$dbal_source->sql_query("SELECT * FROM {$table_prefix}config WHERE config_name = 'version'");
						$source_version = $dbal_source->sql_fetchfield('config_value');
						if ($source_version != $config['version'])
						{
							$forum_merge_error = sprintf($user->lang['SOURCE_DB_VERSION_MISMATCH'], $source_version, $config['version']);
						}
					}
				}
			}

			if (!$forum_merge_error)
			{
				// Let's show the forum breakdown and try to match what is in the source DB to what is in the target DB
				// Admin will select what the target forums will be.

				$template->assign_vars(array(
					'S_CONNECTION_SUCCESSFUL'		=> true,
				));
			}
		}

		if (!$forum_merge_error && $request->is_set_post('submit'))
		{
			if (!check_form_key('davidiq/forummerge'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}

			//trigger_error($user->lang['ACP_FORUM_MERGE_COMPLETED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'U_ACTION'					=> $this->u_action,
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
}
