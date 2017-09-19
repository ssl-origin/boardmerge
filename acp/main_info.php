<?php
/**
 *
 * Board Merge. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, David Colón, https://www.davidiq.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\forummerge\acp;

/**
 * Board Merge ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\davidiq\boardmerge\acp\main_module',
			'title'		=> 'ACP_BOARD_MERGE_TITLE',
			'modes'		=> array(
				'main'	=> array(
					'title'	=> 'ACP_BOARD_MERGE_MAIN',
					'auth'	=> 'ext_davidiq/forummerge && acl_a_board',
					'cat'	=> array('ACP_BOARD_MERGE_TITLE')
				),
			),
		);
	}
}
