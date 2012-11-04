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

// Create the lang array if it does not already exist
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Merge the following YubiKey language entries into the lang array
$lang = array_merge($lang, array(
	'ACCOUNT_YUBIKEY_NO_USER'		=> 'That user does not exist or does not have any YubiKeys registered to their account.',
	'ACCOUNT_YUBIKEY_RESET_ERROR'	=> 'A problem occurred while reseting the YubiKey preferences.',
	'ACCOUNT_YUBIKEY_RESET_SUCCESS'	=> 'Your YubiKey preferences have successfully been reset.',
	'ACCOUNT_YUBIKEY_WRONG_CONFIRM'	=> 'The YubiKey reset confirmation code is incorrect.',
	'ACP_DEL_YUBIKEYS'				=> 'Remove YubiKeys',
	'ACP_USER_YUBIKEY_LOGIN'		=> 'YubiKey Manager',
	'ACP_YUBIKEY_LOGIN'				=> 'YubiKey Login',
	'ACP_YUBIKEY_SETTINGS'			=> 'YubiKey Login Settings',
	'ACP_YUBIKEY_SETTINGS_EXPLAIN'	=> 'Here you are able to define YubiKey login related settings.',
	'ACP_YUBIKEY_USERS'				=> 'YubiKey User Manager',
	'ACP_YUBIKEYS'					=> 'Manage YubiKeys',
	'ACP_YUBIKEYS_EXPLAIN'			=> 'Manage YubiKeys that have been registered to users on this forum.',

	'FORM_YUBICO_API_ID'			=> 'Yubico API ID',
	'FORM_YUBICO_API_ID_EXPLAIN'	=> 'Your Yubico API client ID from the <a href="https://upgrade.yubico.com/getapikey/">Yubico API Key Generator</a>.',
	'FORM_YUBICO_API_KEY'			=> 'Yubico API Key',
	'FORM_YUBICO_API_KEY_EXPLAIN'	=> 'Your Yubico API secret key from the <a href="https://upgrade.yubico.com/getapikey/">Yubico API Key Generator</a>.',
	'FORM_YUBICO_API_TIMEOUT'		=> 'Yubico API Timeout',
	'FORM_YUBICO_API_TIMEOUT_EXPLAIN'	=> 'The longest that the YubiKey validation should wait before giving up.',
	'FORM_YUBICO_API_TOLERANCE'		=> 'Yubico API Timestamp Tolerance',
	'FORM_YUBICO_API_TOLERANCE_EXPLAIN'	=> 'The largest difference allowed between the Yubico servers\' timestamp and the current server time (converted to UTC/GMT).',
	'FORM_YUBIKEY_EMPTY_API_VALUE'	=> 'You must have a Yubico API Key and ID before enabling this mod.',
	'FORM_YUBIKEY_LOGIN'			=> 'YubiKey logins',
	'FORM_YUBIKEY_LOGIN_EXPLAIN'	=> 'Determines whether users can use their YubiKey to log into the forum.',

	'LOG_ADD_YUBIKEY'				=> '<strong>Added YubiKey</strong><br />» %1$s',
	'LOG_DELETE_YUBIKEY'			=> '<strong>Deleted YubiKey</strong><br />» %1$s',
	'LOG_DELETE_YUBIKEYS'			=> '<strong>Deleted YubiKeys</strong><br />» %1$s',
	'LOG_ERROR_DELETING_YUBIKEY'	=> '<strong>Failed to delete YubiKey</strong><br />» %1$s',
	'LOG_ERROR_DELETING_YUBIKEYS'	=> '<strong>Failed to delete YubiKeys</strong><br />» %1$s',
	'LOG_USER_RESET_YUBIKEY'		=> '<strong>Reset YubiKey preferences</strong><br />» %s',
	'LOG_YUBIKEY_SETTINGS'			=> '<strong>Altered YubiKey login settings</strong>',

	'LOGIN_ERROR_YUBIKEY_BAD_OTP'		=> 'You have specified an invalid YubiKey one time pass code. If you do not have a YubiKey attached to your account, leave this field blank.',
	'LOGIN_ERROR_YUBIKEY_BAD_OTP_SHORT'	=> 'You have specified an invalid YubiKey one time pass code.',
	'LOGIN_ERROR_YUBIKEY_BAD_SIG'		=> 'The signature returned by the Yubico validation servers is incorrect. Please contact the %sBoard Administrator%s if this problem persists.',
	'LOGIN_ERROR_YUBIKEY_BAD_TIME'		=> 'The timestamp returned by the Yubico validation servers is incorrect. Please contact the %sBoard Administrator%s if this problem persists.',
	'LOGIN_ERROR_YUBIKEY_CONFIG_KEY'	=> 'The Yubico API ID and Key have been incorrectly configured for this board. Please contact the %sBoard Administrator%s if this problem persists.',
	'LOGIN_ERROR_YUBIKEY_CONFIG_PARAM'	=> 'The parameters passed to the Yubico validation servers are incorrectly configured. Please contact the %sBoard Administrator%s if this problem persists.',
	'LOGIN_ERROR_YUBIKEY_CONFIG_USER'	=> 'It appears that your YubiKey is not registered to a valid user.',
	'LOGIN_ERROR_YUBIKEY_REQUIRED'		=> 'A YubiKey one time pass code is required to log in.',
	'LOGIN_ERROR_YUBIKEY_SERVER'		=> 'An error occurred while trying to validate your YubiKey one time pass code. Please contact the %sBoard Administrator%s if this problem persists.',
	
	'NO_YUBIKEYS'					=> 'There are no YubiKeys to display.',
	'NO_YUBIKEYS_ANY'				=> 'There are no YubiKeys registered to any users.',
	'NO_YUBIKEYS_USER'				=> 'There are no YubiKeys registered to this user.',
	'NO_YUBIKEYS_YOU'				=> 'You do not have any YubiKeys registered to your account.',
	
	'UCP_YUBIKEY_LOGIN'				=> 'YubiKey Manager',
	'UCP_YUBIKEY_MULTIFACTOR'		=> 'User multifactor authentication',
	'UCP_YUBIKEY_MULTIFACTOR_EXPLAIN'		=> 'Require username, password and a valid YubiKey OTP from one of my YubiKeys to log in.',
	'UCP_YUBIKEY_REQUIRE_USERNAME'	=> 'Deny YubiKey login without username',
	'UCP_YUBIKEY_REQUIRE_USERNAME_EXPLAIN'	=> 'Require a username along with the YubiKey OTP to log in.',
	'UCP_YUBIKEY_REQUIRE_USERNAME_NOTE'		=> 'A username is always required to log into the admin control panel',
	'UCP_YUBIKEY_REQUIRE_YUBIKEY'	=> 'Require YubiKey to log in.',
	'UCP_YUBIKEY_REQUIRE_YUBIKEY_EXPLAIN'	=> 'Require a valid YubiKey one time pass code from one of my YubiKeys to log in.',
	'UNINSTALL_SUCCESSFUL'			=> 'Uninstall successful.',

	'YUBIKEY'						=> 'YubiKey',
	'YUBIKEYS'						=> 'YubiKeys',
	'YUBIKEY_ADD'					=> 'Add YubiKey',
	'YUBIKEY_ADD_A'					=> 'Add a YubiKey',
	'YUBIKEY_ADDED'					=> 'YubiKey Added Successfully.',
	'YUBIKEY_CONFIRM'				=> 'Confirm YubiKey preferences reset',
	'YUBIKEY_DEL_CONF'				=> 'Are you sure you want to delete this YubiKey? ',
	'YUBIKEY_DELETE'				=> 'Delete',
	'YUBIKEY_DELETED'				=> 'YubiKey Successfully Deleted.',
	'YUBIKEY_ERROR_ADDING'			=> 'A problem occurred while trying to add the YubiKey to the database.',
	'YUBIKEY_ERROR_DEL_REQ'			=> 'ERROR: Invalid deletion requested. Please try again.',
	'YUBIKEY_ERROR_DELETING'		=> 'ERROR: There was a problem deleting the YubiKey.',
	'YUBIKEY_ERROR_EXISTS'			=> 'ERROR: The YubiKey already exists in the database.',
	'YUBIKEY_ERROR_INVALID'			=> 'ERROR: Invalid device submitted.',
	'YUBIKEY_ERROR_NO_YUBIKEY'		=> 'ERROR: You must have a YubiKey registered to your username before you can change the login settings.',
	'YUBIKEY_ALREADY_INSTALLED'		=> 'YubiKey Login mod has already been installed.  Please delete this file.',
	'YUBIKEY_NOT_INSTALLED'			=> 'YubiKey Login mod is not installed.  Please delete this file.',
	'YUBIKEY_INSTALL_COMPLETE'		=> 'YubiKey Login mod installation complete.  Please delete this file, enable YubiKey logins in the Admin Control Panel and <a href="https://upgrade.yubico.com/getapikey/">request an API key from Yubico</a>.',
	'YUBIKEY_LASTLOGIN'				=> 'Most recent YubiKey log in',
	'YUBIKEY_LIST'					=> 'Your YubiKeys',
	'YUBIKEY_LOGIN_DISABLED'		=> 'The administrator has chosen to disable YubiKey logins.',
	'YUBIKEY_LOGIN_SETTINGS'		=> 'YubiKey Login Settings',
	'YUBIKEY_NOT_REQUIRED'			=> 'A YubiKey is not required, please log in normally.',
	'YUBIKEY_OTP'					=> 'YubiKey OTP',
	'YUBIKEY_OTP_FULL'				=> 'YubiKey one time pass code',
	'YUBIKEY_OTP_FULL_EXPLAIN'		=> 'Enter your YubiKey one time pass code here.',
	'YUBIKEY_OTP_LINK'				=> '<a href="http://www.yubico.com/products/yubikey/">YubiKey OTP</a>',
	'YUBIKEY_RESET'					=> 'Reset YubiKey preferences.',
	'YUBIKEY_SEND'					=> 'Send YubiKey reset key',
	'YUBIKEY_SETTINGS_SAVE_SUCCESS'	=> 'Your YubiKey login settings have been updated.',
	'YUBIKEY_SETTINGS_SAVE_FAIL'	=> 'An error occurred while updating your YubiKey login settings.',
	'YUBIKEY_UPDATE_SUCCESSFUL'		=> 'The YubiKeys have successfully been deleted.',
	'YUBIKEY_UPDATE_FAILED'			=> 'Could not delete the selected YubiKeys.',
	'YUBIKEY_UPDATED'				=> 'A reset key has been sent to your registered e-mail address.',
	'YUBIKEY_USER'					=> 'YubiKey user',
));

?>