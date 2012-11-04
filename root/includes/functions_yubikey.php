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

if (!function_exists('delete_yubikey'))
{
	function delete_yubikey($yubikeys, $action = null, $trigger_error = false, $admin = false)
	{
		global $db, $user;

		// can only be accomplished on admin pages
		if (is_array($yubikeys))
		{
			sort($yubikeys);

			$l_yubikey_list = implode(', ', $yubikeys);
			$users = array();
			$num_yubikeys = 0;

			// retrieve user ids
			$sql = 'SELECT deviceid, user_id FROM ' . YUBIKEY_TABLE . ' WHERE ' . $db->sql_in_set('deviceid', $yubikeys) . ' ORDER BY deviceid ASC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$users[] = (int) $row['user_id'];
				++$num_yubikeys;
			}
			$db->sql_freeresult($result);

			// delete YubiKeys
			$sql = 'DELETE FROM ' . YUBIKEY_TABLE . ' WHERE ' . $db->sql_in_set('deviceid', $yubikeys);
			$db->sql_query($sql);

			if ($db->sql_affectedrows() > 0)
			{
				$log_action = 'LOG_DELETE_YUBIKEY';
				$log_action .= ($num_yubikeys > 1) ? 'S' : '';
				add_log('admin', $log_action, $l_yubikey_list);

				if (sizeof($users))
				{
					// check that we didn't just delete the user's last YubiKey
					$sql = 'SELECT * FROM '. YUBIKEY_TABLE . ' WHERE ' . $db->sql_in_set('user_id', $users);
					$result = $db->sql_query($sql);

					$users_check = array();

					while ($row = $db->sql_fetchrow($result))
					{
						if (!in_array($row['user_id'], $users_check))
						{
							$users_check[] = $row['user_id'];
						}
					}

					$db->sql_freeresult($result);

					$delete_users = array();

					for ($i = 0; $i < $num_yubikeys; $i++)
					{
						// if we did just delete the user's last YubiKey
						if (!in_array($users[$i], $users_check))
						{
							$delete_users[] = $users[$i];
						}

						// log it
						add_log('user', $users[$i], 'LOG_DELETE_YUBIKEY', $yubikeys[$i]);
					}
					if (sizeof($delete_users))
					{
						$sql = 'UPDATE '. USERS_TABLE . ' SET user_yubikey_mask = 0 WHERE ' . $db->sql_in_set('user_id', $delete_users);
						$db->sql_query($sql);
					}

					yubikey_message('YUBIKEY_UPDATE_SUCCESSFUL', $action, $trigger_error, false, $admin);
				}
			}
			else
			{
				$log_action = 'LOG_ERROR_DELETING_YUBIKEY';
				$log_action .= ($num_yubikeys > 1) ? 'S' : '';
				add_log('admin', $log_action, $l_yubikey_list);

				for ($i = 0; $i < $num_yubikeys; ++$i)
				{
					add_log('user', $users[$i], 'LOG_ERROR_DELETING_YUBIKEY', $yubikeys[$i]);
				}

				yubikey_message('YUBIKEY_UPDATE_FAILED', $action, $trigger_error, true, $admin);
			}
		}
		else
		{
			$sql = 'SELECT user_id FROM ' . YUBIKEY_TABLE . ' WHERE deviceid = \'' . $db->sql_escape($yubikeys) . '\'';
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			$user_id = (int) $row['user_id'];

			if ($user_id != $user->data['user_id'] && !$admin)
			{
				yubikey_message('YUBIKEY_ERROR_INVALID', $action, $trigger_error, true, $admin);
			}

			// Delete the YubiKey
			$sql = 'DELETE FROM ' . YUBIKEY_TABLE . ' WHERE deviceid = \'' . $db->sql_escape($yubikeys) . '\'';
			$db->sql_query($sql);

			if ($db->sql_affectedrows())
			{
				// check that we didn't just delete the user's last YubiKey
				$sql = 'SELECT * FROM '. YUBIKEY_TABLE . ' WHERE user_id = ' . $user_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				// if we did just delete the user's last YubiKey
				if (!$row)
				{
					$sql = 'UPDATE '. USERS_TABLE . ' SET user_yubikey_mask = 0 WHERE user_id = ' . $user_id;
					$db->sql_query($sql);
				}

				// log it
				if ($admin)
				{
					add_log('admin', 'LOG_DELETE_YUBIKEY', $yubikeys);
				}
				add_log('user', $user_id, 'LOG_DELETE_YUBIKEY', $yubikeys);
				yubikey_message('YUBIKEY_DELETED', $action, $trigger_error, false, $admin);
			}
			else
			{
				add_log('user', $user_id, 'LOG_ERROR_DELETING_YUBIKEY', $yubikeys);
				yubikey_message('YUBIKEY_ERROR_DELETING', $action, $trigger_error, true, $admin);
			}
		}
	}

	function yubikey_message($message, $action = null, $trigger_error = true, $warn = false, $admin = false)
	{
		global $user;

		$message = (isset($user->lang[$message])) ? $user->lang[$message] : $message;
		if ($admin)
		{
			$action = ($action != null) ? adm_back_link($action) : '';
		}
		else
		{
			$action = ($action != null) ? '' : $user->page['page'];
		}

		if ($trigger_error)
		{
			if ($warn)
			{
				trigger_error($message . $action, E_USER_WARNING);
			}
			else
			{
				trigger_error($message . $action);
			}
		}
		else
		{
			add_message($message);
		}
	}

	function add_yubikey($otp, $user_id)
	{
		global $config, $db, $phpbb_root_path, $phpEx, $user;
		if (!function_exists('validate_yubikey'))
		{
			include $phpbb_root_path . 'includes/functions_yubikey_login.' . $phpEx;
		}

		$yk = validate_yubikey($otp); // false if success

		// validate YubiKey
		if ($yk['status'])
		{
			$err = $yk['error_msg'];
			// shorten the bad otp message
			$err .= ($err == 'LOGIN_ERROR_YUBIKEY_BAD_OTP') ? '_SHORT' : '';

			if (isset($user->lang[$err]))
			{
				$err = $user->lang[$err];
			}

			// Assign admin contact to some error messages
			if (substr_count($err, '%s') == 2)
			{
				$err = (!$config['board_contact']) ? sprintf($err, '', '') : sprintf($err, '<a href="mailto:' . htmlspecialchars($config['board_contact']) . '">', '</a>');
			}
			yubikey_message($err, null, false);
			return false;
		}
		else
		{
			$device = substr($otp, 0, 12);

			$sql = 'SELECT * FROM ' . YUBIKEY_TABLE . ' WHERE deviceid = \'' . $db->sql_escape($device) . '\'';
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($row)
			{
				yubikey_message('YUBIKEY_ERROR_EXISTS', null, false);
				return false;
			}
			else
			{
				$data = array(
					'deviceid'	=> $db->sql_escape($device),
					'user_id'	=> (int) $user_id,
					'lastseen'	=> 0
				);
				$sql = 'INSERT INTO ' . YUBIKEY_TABLE . ' ' . $db->sql_build_array('INSERT', $data);
				$db->sql_query($sql);

				$rows = $db->sql_affectedrows();

				if (!$rows)
				{
					yubikey_message('YUBIKEY_ERROR_ADDING', null, false);
					return false;
				}
				else
				{
					add_log('user', $user_id, 'LOG_ADD_YUBIKEY', $device);
					yubikey_message('YUBIKEY_ADDED', null, false);
					return true;
				}
			}
		}
	}
}

?>