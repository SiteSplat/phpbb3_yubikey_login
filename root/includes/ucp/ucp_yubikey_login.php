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

include $phpbb_root_path . 'includes/functions_yubikey.' . $phpEx;

/**
* @package ucp
*/
class ucp_yubikey_login
{
    var $u_action;
    var $tpl_path;
    var $page_title;

    function main($id, $mode)
    {
		global $db, $config, $phpbb_root_path, $phpEx, $user, $template;

		include($phpbb_root_path . "includes/functions_yubikey_login." . $phpEx);

		$this->tpl_name	= 'ucp_yubikey_login';
		$this->page_title	= 'UCP_YUBIKEY_LOGIN';
		$action = isset($_GET['action']) ? request_var('action', '') : false;
		$delete = ($action == "DelKey") ? true : false;
		$addkey = ($action == "AddKey") ? true : false;
		$settings = ($action == "Settings") ? true : false;
		
		// check it we're using the glitched subsilver version
		$submit = request_var('submit', '');
		if ($submit == $user->lang['YUBIKEY_ADD'])
		{
			$addkey = true;
			$delete = false;
			$settings = false;
		}
		elseif ($submit == $user->lang['SUBMIT'])
		{
			$addkey = false;
			$delete = false;
			$settings = true;
		}

		$ucp_i = request_var('i', '');
		$user_id = (int) $user->data['user_id'];
		
		$form_name = 'yubikey_login_ucp';
		add_form_key($form_name);

		if (($addkey || $settings) && !check_form_key($form_name))
		{
			$url_back = append_sid($user->page['page_name'], "i=$ucp_i");
			$link_back = '<br /><br /><a href="' . $url_back . '">&laquo; ' . $user->lang['BACK_TO_PREV'] . '</a>';
			trigger_error($user->lang['FORM_INVALID'] . $link_back, E_USER_WARNING);
		}

		// Add YubiKey
		if ($addkey)
		{
			// get OTP
			$otp = request_var('yubikey_otp', '');
			
			// Add it to database
			add_yubikey($otp, $user_id);
		}
		
		// Change YubiKey settings
		elseif ($settings)
		{
			// get the three variables that make up the mask, and make sure that they are only 1 or 0
			$req_usr = (int) request_var('req_username', false);
			$req_yk = (int) request_var('req_yubikey', false);
			$multi = (int) request_var('multifactor', false);
			
			// check to see if the user has at least 1 YubiKey
			$sql = 'SELECT deviceid FROM ' . YUBIKEY_TABLE . ' WHERE user_id = ' . $user_id;
			$result = $db->sql_query_limit($sql, 1);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			// if the user does, change the settings
			if	($row)
			{
				// compose mask, we can't allow multifactor without requiring the yubikey, so multi is * 6 to set both multifactor and require yubikey
				$mask = $req_usr | ($req_yk * 2) | ($multi * 7);
				
				// add those changes to the database
				$sql = 'UPDATE ' . USERS_TABLE . ' SET user_yubikey_mask = ' . $mask . ' WHERE user_id = ' . $user_id;
				$db->sql_query($sql);
				
				// check if update was successful
				if($db->sql_affectedrows())
				{
					add_message($user->lang['YUBIKEY_SETTINGS_SAVE_SUCCESS']);
				}
				else
				{
					add_message($user->lang['YUBIKEY_SETTINGS_SAVE_FAIL']);
				}
			}
			else
			{
				add_message($user->lang['YUBIKEY_ERROR_NO_YUBIKEY']);
			}
		}

		// Delete YubiKey
		elseif ($delete)
		{
			// See if confirmation set
			$confirm = isset($_GET['confirm']) ? true : false;
			$device = request_var('device', '');

			// Make sure the device belongs to this user & get url
			$sql = 'SELECT * FROM ' . YUBIKEY_TABLE . ' WHERE deviceid = \'' . $db->sql_escape($device) . '\' AND user_id = ' . $user_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			$device = $row['deviceid'];
			
			if (!$device)
			{
				$err = 'YUBIKEY_ERROR_DEL_REQ';
				
				// assign admin contact
				$err = (!$config['board_contact']) ? sprintf($user->lang[$err], '', '') : sprintf($user->lang[$err], '<a href="mailto:' . htmlspecialchars($config['board_contact']) . '">', '</a>');
				
				add_message($err);
			}
			else
			{
				// Was deletion confirmed?
				// If not, confirm
				if (!$confirm)
				{
					$vars = array(
						'i' => request_var('i', ''),
						'action' => 'DelKey',
						'device' => $device,
						'confirm' => 'yes',
					);
					add_message($user->lang['YUBIKEY_DEL_CONF'] . $device . ' <a href="' . append_sid("{$phpbb_root_path}ucp.$phpEx", $vars) . '">' . $user->lang['YUBIKEY_DELETE'] . "</a> | <a href='" . append_sid("{$phpbb_root_path}ucp.$phpEx", "i=$ucp_i") . "'>" . $user->lang['CANCEL'] . "</a>");
				}
				else
				{
					delete_yubikey($device, $this->u_action, false, false);
				}
			}
		}

		// List YubiKeys
		if ($mode == "view")
		{
			// Get user's settings
			$sql = 'SELECT user_yubikey_mask FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$mask = $row['user_yubikey_mask'];

			// Get YubiKeys
			$sql = 'SELECT deviceid FROM ' . YUBIKEY_TABLE . ' WHERE 
					user_id = ' . $user_id;

			$result = $db->sql_query_limit($sql, 50);
			$delete_link = append_sid("{$phpbb_root_path}ucp.$phpEx", "i=$ucp_i&amp;action=DelKey");

			while ($row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('yubikey', array(
					'DEVICE'		=> $row['deviceid'],
					'DELETE_LINK'	=> $delete_link . "&amp;device=" . $row['deviceid'],
				));
			}
			$db->sql_freeresult($result);
		}
		
		$template->assign_vars(array(
			'L_TITLE'	=> (isset($user->lang[$this->page_title])) ? $user->lang[$this->page_title] : $this->page_title,
			'U_ACTION'	=> append_sid("{$phpbb_root_path}ucp.$phpEx", array('i' => $ucp_i, 'action' => 'AddKey')),
			'U_ACTION_SETTINGS'	=> append_sid("{$phpbb_root_path}ucp.$phpEx", array('i' => $ucp_i, 'action' => 'Settings')),
			'S_REQ_USERNAME'	=> $mask & 1,
			'S_REQ_YUBIKEY'		=> $mask & 2,
			'S_MULTIFACTOR'		=> $mask & 4,
			'S_MODE'	=> $mode,
		));
    }
}

function add_message($text)
{
	global $template;
	$template->assign_block_vars('yubikey_messages', array('MESSAGE'	=> $text));
}
?>