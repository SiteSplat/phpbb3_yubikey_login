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
define('IN_PHPBB', true);
define('IN_INSTALL', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require($phpbb_root_path . 'common.' . $phpEx);
require($phpbb_root_path . 'includes/functions_display.' . $phpEx);
require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
require($phpbb_root_path . 'includes/acp/acp_modules.' . $phpEx);

$action = request_var('action', '');
$version = request_var('version', '');
// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup(array('acp/common'));

function set_modules($install = true)
{
	global $db, $user;
	
	// Lets make sure this module does not get added a second time by accident
	$sql = 'SELECT module_id
		FROM ' . MODULES_TABLE . "
		WHERE module_langname = 'ACP_YUBIKEY_LOGIN' OR module_langname = 'ACP_YUBIKEY_USERS' OR module_basename = 'yubikey_login'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	
	if ($row)
	{
		$sql = 'DELETE
			FROM ' . MODULES_TABLE . "
			WHERE module_langname = 'ACP_YUBIKEY_LOGIN' OR module_langname = 'ACP_YUBIKEY_USERS' OR module_basename = 'yubikey_login'";
		$db->sql_query($sql);
	}
	
	if ($install)
	{
		// Lets get the .MOD module ID
		$sql = 'SELECT module_id
			FROM ' . MODULES_TABLE . "
			WHERE module_langname = 'ACP_CAT_DOT_MODS'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$_module = new acp_modules();
		
		// So lets add the main category
		$main = array(
			'module_basename'	=> '',
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'parent_id'			=> (int) $row['module_id'],
			'module_class'		=> 'acp',
			'module_langname'	=> 'ACP_YUBIKEY_LOGIN',
			'module_mode'		=> '',
			'module_auth'		=> '',
		);
		$_module->update_module_data($main);

		// Now the subcategories
		$settings = array(
			'module_basename'	=> 'yubikey_login',
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'parent_id'			=> (int) $main['module_id'],
			'module_class'		=> 'acp',
			'module_langname'	=> 'ACP_YUBIKEY_SETTINGS',
			'module_mode'		=> 'yubikey_login_settings',
			'module_auth'		=> 'acl_a_server',
		);
		$_module->update_module_data($settings);

		$manager = array(
			'module_basename'	=> 'yubikey_login',
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'parent_id'			=> (int) $main['module_id'],
			'module_class'		=> 'acp',
			'module_langname'	=> 'ACP_YUBIKEYS',
			'module_mode'		=> 'yubikey_login_devices',
			'module_auth'		=> 'acl_a_userdel',
		);
		$_module->update_module_data($manager);

		
		// Lets get the Users module id
		$sql = 'SELECT module_id
			FROM ' . MODULES_TABLE . "
			WHERE module_langname = 'ACP_CAT_USERS'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		// now for the acp user module
		$manager = array(
			'module_basename'	=> 'users',
			'module_enabled'	=> 1,
			'module_display'	=> 0,
			'parent_id'			=> (int) $row['module_id'],
			'module_class'		=> 'acp',
			'module_langname'	=> 'ACP_YUBIKEY_USERS',
			'module_mode'		=> 'yubikey_login',
			'module_auth'		=> 'acl_a_user',
		);
		$_module->update_module_data($manager);

		// Lets get the Users module id
		$sql = 'SELECT module_id
			FROM ' . MODULES_TABLE . "
			WHERE module_langname = 'UCP_PROFILE'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		// now add the ucp module
		$settings = array(
			'module_basename'	=> 'yubikey_login',
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'parent_id'			=> (int) $row['module_id'],
			'module_class'		=> 'ucp',
			'module_langname'	=> 'UCP_YUBIKEY_LOGIN',
			'module_mode'		=> 'view',
			'module_auth'		=> '',
		);
		$_module->update_module_data($settings);
	}
}

// make sure that it hasn't already been installed/uninstalled
$sql = 'SELECT * FROM ' . CONFIG_TABLE . " WHERE config_name = 'allow_yubikey_login'";
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if ($action == 'uninstall')
{
	if ($row)
	{
		set_modules(false);
		
		// delete config items
		$sql = 'DELETE FROM ' . CONFIG_TABLE . " WHERE config_name = 'allow_yubikey_login'";
		$db->sql_query($sql);
		$sql = 'DELETE FROM ' . CONFIG_TABLE . " WHERE config_name LIKE 'yubico_api_%'";
		$db->sql_query($sql);

		// make sure we delete the table
		if(!defined('YUBIKEY_TABLE'))
		{
			define('YUBIKEY_TABLE', $table_prefix . 'yubikey');
		}

		$sql = 'DROP TABLE IF EXISTS ' . YUBIKEY_TABLE;
		$db->sql_query($sql);

		$sql = 'ALTER TABLE ' . USERS_TABLE . ' DROP user_yubikey_mask;';
		$db->sql_query($sql);

		// clear the auth options
		$cache->destroy('_acl_options');
		$auth->acl_clear_prefetch();
		// clear the cache
		$cache->purge();

		$message = (isset($user->lang['UNINSTALL_SUCCESSFUL'])) ? $user->lang['UNINSTALL_SUCCESSFUL'] : 'UNINSTALL_SUCCESSFUL';
		trigger_error($message);
	}
	else
	{
		$message = (isset($user->lang['YUBIKEY_NOT_INSTALLED'])) ? $user->lang['YUBIKEY_NOT_INSTALLED'] : 'YUBIKEY_NOT_INSTALLED';
		trigger_error($message);
	}
}
elseif ($action == 'update')
{
	if ($version == '1.0.0b')
	{
		// update logged yubikey settings updates
		$sql = 'UPDATE ' . LOG_TABLE . " SET log_operation = 'LOG_YUBIKEY_SETTINGS' WHERE log_operation = 'LOG_CONFIG_YUBIKEY_LOGIN_SETTINGS'";
		$db->sql_query($sql);
	}
	elseif ($version == '1.0.1')
	{
		// rename the identity column to deviceid
		$sql = 'ALTER TABLE ' . YUBIKEY_TABLE . ' CHANGE identity deviceid CHAR(12)';
		$db->sql_query($sql);
	}
	elseif ($version != '1.0.0') // no database, or similar changes are needed from version 1.0.0
	{
		trigger_error('UNKNOWN_VERSION');
	}
	
	$user->add_lang('install');
	trigger_error('UPDATE_COMPLETED');
}
else //if ($action == 'install')
{
	if (!file_exists($phpbb_root_path . "includes/functions_yubikey.$phpEx"))
	{
		$message = (isset($user->lang['YUBIKEY_NOT_INSTALLED'])) ? $user->lang['YUBIKEY_NOT_INSTALLED'] : 'YUBIKEY_NOT_INSTALLED';
		trigger_error($message);
	}
	elseif ($row)
	{
		trigger_error($user->lang['YUBIKEY_ALREADY_INSTALLED']);
	}
	// make sure that the mod hasn't already been installed and that the mod is installed
	elseif (defined('YUBIKEY_TABLE'))
	{
		$sql = 'CREATE TABLE IF NOT EXISTS ' . YUBIKEY_TABLE . ' (
		deviceid CHAR(12) NOT NULL PRIMARY KEY,
		user_id INTEGER(8) UNSIGNED NOT NULL,
		lastseen INTEGER(10) UNSIGNED NOT NULL DEFAULT 0,
		lost_key VARCHAR(10) NULL
		);';
		$db->sql_query($sql);


		$sql = 'ALTER TABLE ' . USERS_TABLE . ' ADD user_yubikey_mask INTEGER(3) NOT NULL DEFAULT 0;';
		$db->sql_query($sql);

		set_modules();
		
		$sql_ary = array();
		
		$keys = array('allow_yubikey_login', 'yubico_api_id', 'yubico_api_key', 'yubico_api_timeout', 'yubico_api_tolerance');
		$values = array(0, '', '', 10, 600);
		
		for($i = 0; $i < 5; ++$i)
		{
			$sql_ary[] = array(
				'config_name'	=> $keys[$i],
				'config_value'	=> $values[$i],
				'is_dynamic'	=> 0
			);
		}

		$db->sql_multi_insert(CONFIG_TABLE, $sql_ary);

		// clear the auth options
		$cache->destroy('_acl_options');
		$auth->acl_clear_prefetch();
		// clear the cache
		$cache->purge();

		trigger_error($user->lang['YUBIKEY_INSTALL_COMPLETE']);
	}
	else
	{
		$message = (isset($user->lang['YUBIKEY_NOT_INSTALLED'])) ? $user->lang['YUBIKEY_NOT_INSTALLED'] : 'YUBIKEY_NOT_INSTALLED';
		trigger_error($message);
	}
}

?>