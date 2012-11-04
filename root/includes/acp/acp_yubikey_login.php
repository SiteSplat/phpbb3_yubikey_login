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

/**
 * @package acp
 */
class acp_yubikey_login
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $template;
		global $config, $phpbb_root_path, $phpEx;

		$user->add_lang('mods/yubikey_login');

		/**
		*	Validation types are:
		*		string, int, bool,
		*		script_path (absolute path in url - beginning with / and no trailing slash),
		*		rpath (relative), rwpath (realtive, writable), path (relative path, but able to escape the root), wpath (writable)
		*/
		switch ($mode)
		{
			case 'yubikey_login_settings':
				$display_vars = array(
					'title'	=> 'ACP_YUBIKEY_SETTINGS',
					'vars'	=> array(
						'legend1'				=> 'ACP_YUBIKEY_SETTINGS',
						'allow_yubikey_login'	=> array('lang' => 'FORM_YUBIKEY_LOGIN',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'yubico_api_id'			=> array('lang' => 'FORM_YUBICO_API_ID',	'validate' => 'string',	'type' => 'text:5:5', 'explain' => true),
						'yubico_api_key'		=> array('lang' => 'FORM_YUBICO_API_KEY',	'validate' => 'string',	'type' => 'text:29:28', 'explain' => true),
						'yubico_api_timeout'	=> array('lang' => 'FORM_YUBICO_API_TIMEOUT',	'validate' => 'int:0',	'type' => 'text:3:3', 'explain' => true, 'append' => ' ' . $user->lang['SECONDS']),
						'yubico_api_tolerance'	=> array('lang' => 'FORM_YUBICO_API_TOLERANCE',	'validate' => 'int:0',	'type' => 'text:4:4', 'explain' => true, 'append' => ' ' . $user->lang['SECONDS']),
					)
				);
				$this->yubikey_settings($display_vars);
			break;
			
			case 'yubikey_login_devices':
				$this->yubikey_devices();
			break;
			
			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}
	}
	
	function yubikey_settings($display_vars)
	{
		global $user, $template, $config;
		
		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'acp_yubikey';
		add_form_key($form_key);
		
		if (isset($display_vars['lang']))
		{
			$user->add_lang($display_vars['lang']);
		}

		$this->new_config = $config;
		$cfg_array = (isset($_POST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if wished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}
		
		// do not enable the mod if the API ID or key are empty
		if ($cfg_array['allow_yubikey_login'] != 0 && ($cfg_array['yubico_api_id'] == '' || $cfg_array['yubico_api_key'] == ''))
		{
			$error[] = $user->lang['FORM_YUBIKEY_EMPTY_API_VALUE'];
			$cfg_array['allow_yubikey_login'] = 0;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				set_config($config_name, $config_value);
			}
		}

		if ($submit && count($error) == 0)
		{
			add_log('admin', 'LOG_YUBIKEY_SETTINGS');

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$this->tpl_name = 'acp_board';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action)
		);

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
				)
			);

			unset($display_vars['vars'][$config_key]);
		}
	}
	
	function yubikey_devices()
	{
		global $db, $user, $template, $config, $phpbb_root_path, $phpEx;
		
		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'acp_delyubikey';
		add_form_key($form_key);
		
		$error = array();
		
		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// retrieve YubiKey
		$yubikeys = request_var('del_yubikey', array(''));

		if ($submit && $yubikeys)
		{
			include "{$phpbb_root_path}includes/functions_yubikey.$phpEx";
			delete_yubikey($yubikeys, ($this->u_action)?$this->u_action:null, true, true);
		}

		$this->tpl_name = 'acp_yubikeys';
		
		$yubikey_options = '';
		$yubikey_users = array();
		$yubikey_logins = array();

		// retrieve YubiKeys
		$sql = 'SELECT deviceid, user_id, lastseen FROM ' . YUBIKEY_TABLE;
		$result = $db->sql_query_limit($sql, 250);
		$num_yubikeys = 0;
		
		while ($row = $db->sql_fetchrow($result))
		{
			$yubikey_options .= '<option title="' . $num_yubikeys . '" ' . (($row['deviceid']) ? ' class="sep"' : '') . ' value="' . $row['deviceid'] . '">' . $row['deviceid'] . '</option>';
			$yubikey_users[] = (int) $row['user_id'];
			$yubikey_logins[] = $row['lastseen'];
			++$num_yubikeys;
		}
		$db->sql_freeresult($result);
		
		$this->page_title = $user->lang['ACP_YUBIKEYS'];

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang['ACP_YUBIKEYS'],
			'L_TITLE_EXPLAIN'	=> $user->lang['ACP_YUBIKEYS_EXPLAIN'],
			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action,
			'S_YUBIKEY_OPTIONS'	=> ($yubikey_options) ? true : false,
			'YUBIKEY_OPTIONS'	=> $yubikey_options
		));
		
		if ($num_yubikeys)
		{
			// fetch usernames
			$sql = 'SELECT username, user_id FROM ' . USERS_TABLE . ' WHERE ' . $db->sql_in_set('user_id', $yubikey_users);
			$result = $db->sql_query($sql);
			
			$ids = array();
			$names = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$ids[] = $row['user_id'];
				$names[] = $row['username'];
			}
			
			$db->sql_freeresult($result);

			$names = array_combine($ids, $names);
			
			for ($i = 0; $i < $num_yubikeys; ++$i)
			{
				$username = ($names[$yubikey_users[$i]]) ? $names[$yubikey_users[$i]] : $user->lang['NO_USER'];
				
				if ($yubikey_logins[$i] == 0)
				{
					$time = $user->lang['NEVER'];
				}
				elseif (time() - $yubikey_logins[$i] < 86400)
				{
					$time = date("g:i a", $yubikey_logins[$i]);
				}
				else
				{
					$time = date("F j, Y", $yubikey_logins[$i]);
				}
				
				$template->assign_block_vars('yubikey_usr', array(
						'YK_ID'		=> $i,
						'USER_ID'	=> $yubikey_users[$i],
						'USER_NAME'	=> $username,
						'USER_LASTLOGIN'	=> $time,
					));
			}
		}
	}

}

?>