<?php
/**
 *
 * Board Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\forummerge\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v320\v320');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOARD_MERGE_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOARD_MERGE_TITLE',
				array(
					'module_basename'	=> '\davidiq\forummerge\acp\main_module',
					'modes'				=> array('main'),
				),
			)),
		);
	}
}
