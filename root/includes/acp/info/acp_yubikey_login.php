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
* @package module_install
*/
class acp_yubikey_login_info
{
	function module()
	{
		global $user;

		return array(
			'filename'	=> 'acp_yubikey_login',
			'title'		=> 'ACP_YUBIKEY_LOGIN',
			'version'	=> '1.0.2',
			'modes'		=> array(
				'yubikey_login_settings'	=> array('title' => 'ACP_YUBIKEY_SETTINGS', 'auth' => 'acl_a_server', 'cat' => array('ACP_DOT_MODS')),
				'yubikey_login_devices'		=> array('title' => 'ACP_YUBIKEYS', 'auth' => 'acl_a_userdel', 'cat' => array('ACP_DOT_MODS')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>