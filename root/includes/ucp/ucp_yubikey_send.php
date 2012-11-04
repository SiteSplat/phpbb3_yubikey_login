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

class ucp_yubikey_send
{
	var $u_action;

	function main($id, $mode)
	{
		global $phpbb_root_path, $phpEx;
		global $db, $user, $template;

		$username	= request_var('username', '', true);
		$email		= strtolower(request_var('email', ''));
		$submit		= (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			$sql = 'SELECT user_id, username, user_permissions, user_email, user_jabber, user_notify_type, user_type, user_lang, user_inactive_reason, user_yubikey_mask
				FROM ' . USERS_TABLE . "
				WHERE user_email_hash = '" . $db->sql_escape(phpbb_email_hash($email)) . "'
					AND username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
			$result = $db->sql_query($sql);
			$user_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$user_row)
			{
				trigger_error('NO_EMAIL_USER', E_USER_WARNING);
			}

			if ($user_row['user_type'] == USER_IGNORE)
			{
				trigger_error('NO_USER', E_USER_WARNING);
			}

			if ($user_row['user_type'] == USER_INACTIVE)
			{
				if ($user_row['user_inactive_reason'] == INACTIVE_MANUAL)
				{
					trigger_error('ACCOUNT_DEACTIVATED', E_USER_WARNING);
				}
				else
				{
					trigger_error('ACCOUNT_NOT_ACTIVATED', E_USER_WARNING);
				}
			}

			// check to make sure that the user is not allowed to log in with their current yubikey settings
			if ((int) $user_row['user_yubikey_mask'] < 2)
			{
				trigger_error('YUBIKEY_NOT_REQUIRED', E_USER_WARNING);
			}

			$server_url = generate_board_url();

			$key_len = 54 - strlen($server_url);
			$key_len = max(6, $key_len); // we want at least 6
			$actkey = substr(gen_rand_string(10), 0, $key_len);

			$sql = 'UPDATE ' . YUBIKEY_TABLE . "
				SET lost_key = '" . $db->sql_escape($actkey) . "'	WHERE user_id = " . (int) $user_row['user_id'];
			$db->sql_query($sql);
			
			if ($db->sql_affectedrows())
			{
				include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);

				$messenger = new messenger(false);

				$messenger->template('user_lost_yubikey', $user_row['user_lang']);

				$messenger->to($user_row['user_email'], $user_row['username']);
				$messenger->im($user_row['user_jabber'], $user_row['username']);

				$messenger->assign_vars(array(
					'USERNAME'		=> htmlspecialchars_decode($user_row['username']),
					'U_ACTIVATE'	=> "$server_url/ucp.$phpEx?mode=yubikey_confirm&u={$user_row['user_id']}&k=$actkey")
				);

				$messenger->send($user_row['user_notify_type']);

				meta_refresh(3, append_sid("{$phpbb_root_path}index.$phpEx"));

				$message = $user->lang['YUBIKEY_UPDATED'] . '<br /><br />' . sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');
				trigger_error($message);
			}
			else
			{
				trigger_error('NO_YUBIKEYS_USER', E_USER_WARNING);
			}
		}

		$template->assign_vars(array(
			'USERNAME'			=> $username,
			'EMAIL'				=> $email,
			'S_PROFILE_ACTION'	=> append_sid($phpbb_root_path . 'ucp.' . $phpEx, 'mode=yubikey_send'))
		);

		$this->tpl_name = 'ucp_yubikey_send';
		$this->page_title = 'YUBIKEY_SEND';
	}
}

?>