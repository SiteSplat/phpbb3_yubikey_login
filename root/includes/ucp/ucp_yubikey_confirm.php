<?php
/**
*
* @package YubiKey Login
* @version 1.0.2
* @copyright (c) 2012 Tim Schlueter
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class ucp_yubikey_confirm
{
	var $u_action;

	function main($id, $mode)
	{
		global $phpbb_root_path, $phpEx;
		global $db, $user;

		$user_id = request_var('u', 0);
		$key = request_var('k', '');

		$sql = 'SELECT * FROM ' . YUBIKEY_TABLE . "	WHERE user_id = " . (int) $user_id;
		$result = $db->sql_query($sql);
		$user_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$user_row)
		{
			trigger_error('ACCOUNT_YUBIKEY_NO_USER', E_USER_WARNING);
		}

		if (!$user_row['lost_key'])
		{
			meta_refresh(3, append_sid("{$phpbb_root_path}index.$phpEx"));
			trigger_error('ACCOUNT_YUBIKEY_WRONG_CONFIRM', E_USER_WARNING);
		}

		if ($user_row['lost_key'] != $key)
		{
			trigger_error('ACCOUNT_YUBIKEY_WRONG_CONFIRM', E_USER_WARNING);
		}

		// reset the login preferences
		$sql = 'UPDATE ' . USERS_TABLE . ' SET user_yubikey_mask = user_yubikey_mask & 1 WHERE user_id = ' . (int) $user_row['user_id'];
		$db->sql_query($sql);
		$affected[0] = $db->sql_affectedrows();

		// reset the lost yubikey tokens
		$sql = 'UPDATE ' . YUBIKEY_TABLE . ' SET lost_key = NULL WHERE user_id = ' . (int) $user_row['user_id'];
		$db->sql_query($sql);
		$affected[1] = $db->sql_affectedrows();

		if ($affected[0] > 0 && $affected[1] > 0)
		{
			add_log('user', $user_row['user_id'], 'LOG_USER_RESET_YUBIKEY', $user_row['username']);
			$message = 'ACCOUNT_YUBIKEY_RESET_SUCCESS';
		}
		else
		{
			$message = 'ACCOUNT_YUBIKEY_RESET_ERROR';
		}

		meta_refresh(5, append_sid("{$phpbb_root_path}index.$phpEx"));
		trigger_error($user->lang[$message]);
	}
}

?>