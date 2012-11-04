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
* @package ucp
*/
class ucp_yubikey_login_info
{
    function module()
    {
        return array(
            'filename'    => 'ucp_yubikey_login', // The module's filename
            'title'        => 'UCP_YUBIKEY_LOGIN', // The title (language string)
            'version'    => '1.0.2', // The module's version
            'modes'        => array( // This is where you add the mode(s)
				'view'        => array('title' => 'UCP_YUBIKEY_LOGIN', 'auth' => 'acl_u_', 'cat' => array('UCP_PROFILE')),
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