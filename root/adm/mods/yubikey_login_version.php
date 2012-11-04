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
* @package mod_version_check
*/
class yubikey_login_version
{
	function version()
	{
		return array(
			'author'	=> 'modelrockettier',
			'title'		=> 'YubiKey Login',
			'tag'		=> 'yubikey_login',
			'version'	=> '1.0.2',
			'file'		=> array('www.modelrockettier.com', 'projects/yubikey_login', 'version_check.xml'),
		);
	}
}

?>