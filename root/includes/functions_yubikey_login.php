<?php
/**
*
* @package YubiKey Login
* @version 1.0.2
* @copyright (c) 2012 Tim Schlueter
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/*********************************************
original class credits:

Class: YubiKey Authentication
Author: Tom Corwine (yubico@corwine.org)
License: GPL-2
Version: 0.96

Class should be instantiated with your Yubico API id and, optionally, the signature key.

Example:
$var = new Yubikey(int id, [string signature key]);

If you don't specifiy a signature key, the signature verification steps are skipped.

Methods:

->verify(string) - Accepts otp from YubiKey. Returns TRUE for authentication success, otherwise FALSE.
->setTimestampTolerance(int) - Sets the tolerance (in seconds, 0-86400) - default 600 (10 minutes).
	Returns TRUE on success and FALSE on failure.
->setTimeout(int) - Sets the timeout (in seconds, 0-600, 0 means indefinitely) - default 10.
	Returns TRUE on success and FALSE on failure.

*********************************************/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class Yubikey
{
	/****************************************************************************
	Methods
	****************************************************************************/
	
	// php5 constructor
	function __construct($id, $signatureKey = null)
	{
		if (phpversion() < 5)
		{
			$signatureKey = null;
		}

		if (is_int($id) && $id > 0)
		{
			$this->_id = $id;
		}
		else
		{
			$this->_id = 0;
		}

		if (strlen($signatureKey) == 28)
		{
			$this->_signatureKey = base64_decode ($signatureKey);
		}
		else
		{
			$signatureKey = null;
		}

		// Set defaults
		$this->_timestampTolerance = 600; //Seconds
		$this->_timeout = 10; //Seconds
		$this->_error = 0;
	}

	// php4 constructor
	function Yubikey($id, $signatureKey = null)
	{	
		$this->__construct ($id, $signatureKey);
	}

	function setTimestampTolerance($int)
	{
		if ($int > 0 && $int < 86400)
		{
			$this->_timestampTolerance = $int;
			return true;
		}
		else
		{
			return false;
		}
	}

	function setTimeout($int)
	{
		if ($int > 0 && $int < 600)
		{
			$this->_timeout = $int;
			return true;
		}
		else
		{
			return false;
		}
	}

	function verify($otp)
	{
		unset ($this->_response);
		unset ($this->_result);
		unset ($this->_error);

		$otp = strtolower ($otp);

		if (!isset($this->_id))
		{
			$this->_response = 'LOGIN_ERROR_YUBIKEY_CONFIG_KEY';
			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		if (!$this->otpIsProperLength($otp))
		{
			$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_OTP';
			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		if (!$this->otpIsModhex($otp))
		{
			$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_OTP';
			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		$urlParams = "id=".$this->_id."&otp=".$otp;

		$url = $this->createSignedRequest($urlParams);

		if (function_exists('curl_init'))
		{
			if ($this->curlRequest($url)) //Returns 0 on success
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_SERVER';
				$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
				return false;
			}
		}
		elseif (function_exists('openssl_open'))
		{
			if ($this->fsockRequest($url)) //Returns 0 on success
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_SERVER';
				$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
				return false;
			}
		}
		else
		{
			str_replace('https://', 'http://', $url);
			if ($this->fsockRequest($url)) //Returns 0 on success
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_SERVER';
				$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
				return false;
			}
		}

		foreach ($this->_result as $param)
		{
			if (substr ($param, 0, 2) == "h=") $signature = substr (trim ($param), 2);
			if (substr ($param, 0, 2) == "t=") $timestamp = substr (trim ($param), 2);
			if (substr ($param, 0, 7) == "status=") $status = substr (trim ($param), 7);
		}

		// Concatenate string for signature verification
		$signedMessage = "status=".$status."&t=".$timestamp;

		if (!$this->resultSignatureIsGood($signedMessage, $signature))
		{
			$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_SIG';
			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		if (!$this->resultTimestampIsGood($timestamp))
		{
			$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_TIME';
			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		if ($status != "OK")
		{
			if($status == 'NO_SUCH_CLIENT' || $status == 'OPERATION_NOT_ALLOWED')
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_CONFIG_KEY';
			}
			elseif($status == 'MISSING_PARAMETER')
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_CONFIG_PARAM';
			}
			elseif($status == 'BAD_SIGNATURE')
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_SIG';
			}
			elseif($status == 'BACKEND_ERROR' || $status == 'NOT_ENOUGH_ANSWERS')
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_SERVER';
			}
			else
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_BAD_OTP';
			}

			$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			return false;
		}

		// Everything went well - We pass
		$this->_response = "OK";
		return true;
	}

	function createSignedRequest($urlParams)
	{
		if ($this->_signatureKey)
		{
			$hash = urlencode (base64_encode (hash_hmac ("sha1", $urlParams, $this->_signatureKey, true)));
			return "https://api.yubico.com/wsapi/verify?".$urlParams."&h=".$hash;
		}
		else
		{
			return "https://api.yubico.com/wsapi/verify?".$urlParams;
		}
	}

	function curlRequest($url)
	{
		$ch = curl_init ($url);

		curl_setopt ($ch, CURLOPT_TIMEOUT, $this->_timeout);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $this->_timeout);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, true);

		$this->_result = explode ("\n", curl_exec($ch));

		if(curl_error($ch))
		{
			$this->_response = curl_error ($ch);
			$this->_error = curl_errno ($ch);
		}

		curl_close ($ch);

		$result = (isset($this->_error))? $this->_error : 0;
		return $result;
	}
	
	function fsockRequest($url)
	{
		if (substr($url, 0, 5) == "https")
		{
			$prefix = 'ssl://';
			$port = 443;
			$host = substr($url, 8, strpos($url, '/', 8) - 8);
			$path = substr($url, strpos($url, '/', 10));
		}
		else
		{
			$prefix = '';
			$port = 80;
			$host = substr($url, 7, strpos($url, '/', 7) - 7);
			$path = substr($url, strpos($url, '/', 10));
		}

		$errno = 0;
		$errstr = '';
		$fh = fsockopen($prefix . $host, $port, $errno, $errstr, $this->_timeout);

		if ($fh)
		{
			$out = "GET $path HTTP/1.1\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "\r\n";
			fwrite($fh, $out);
			stream_set_timeout($fh, $this->_timeout);
			$info = stream_get_meta_data($fh);
			if ($info['timed_out'])
			{
				$this->_response = 'LOGIN_ERROR_YUBIKEY_SERVER';
				$this->_error = LOGIN_ERROR_EXTERNAL_AUTH;
			}
			else
			{
				$haystack = "";
				while (!feof($fh))
				{
					$haystack .= fgets($fh, 4096);
				}
			}
			
			$this->_result = strstr($haystack, "\r\n\r\n");
			$this->_result = explode("\n", $this->_result);
			
			fclose($fh);
		}
		else
		{
			$this->error = $errstr;
		}
		
		if (isset($this->_error))
		{
			$result = $this->_error;
		}
		else if ($errno)
		{
			$result = $this->_error = $errstr;
		}
		else
		{
			$result = 0;
		}
		
		return $result;
	}

	function otpIsProperLength($otp)
	{
		if (strlen ($otp) == 44)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function otpIsModhex($otp)
	{
		$modhexChars = array ("c","b","d","e","f","g","h","i","j","k","l","n","r","t","u","v");

		if (phpversion() < 5)
		{
			foreach ($otp as $char)
			{
				if (!in_array ($char, $modhexChars)) return false;
			}
		}
		else
		{
			foreach (str_split ($otp) as $char)
			{
				if (!in_array ($char, $modhexChars)) return false;
			}
		}

		return true;
	}

	function resultTimestampIsGood($timestamp)
	{
		// find the current local timestamp and convert it to UTC/GMT
		$now = time() - date('Z');
		$timestampSeconds = substr ($timestamp, 0, -5);
		$timestampSeconds = str_replace("T", " ", $timestampSeconds);
		$timestampSeconds = strtotime($timestampSeconds);

		// If date() functions above fail for any reason, so do we
		if (!$timestamp || !$now) return false;
		
		if (($timestampSeconds + $this->_timestampTolerance) > $now &&
		    ($timestampSeconds - $this->_timestampTolerance) < $now)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function resultSignatureIsGood($signedMessage, $signature)
	{
		if (!$this->_signatureKey) return true;

		if (base64_encode (hash_hmac ("sha1", $signedMessage, $this->_signatureKey, true)) == $signature)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

// login functions
function yubikey_increase_logins($user, $device = true)
{
	global $db;

	if ($device)
	{
		$sql = 'SELECT user_id FROM ' . YUBIKEY_TABLE . " WHERE deviceid = '" . $db->sql_escape($user) . "'";
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$user = (isset($row['user_id'])) ? $row['user_id'] : false;
	}

	$affected = 0;
	if ($user)
	{
		// increase the number of login attempts
		$sql = 'UPDATE ' . USERS_TABLE . ' SET user_login_attempts = user_login_attempts + 1 WHERE user_id = \'' . (int) $user . '\'';
		$db->sql_query($sql);
		$affected = $db->sql_affectedrows();
	}

	return $affected;
}

function yubikey_check_required($username, $yubikey_otp)
{
	global $db;
	if (!$yubikey_otp)
	{
		if (!$username)
		{
			return array(
				'status'		=> LOGIN_ERROR_USERNAME,
				'error_msg'		=> 'LOGIN_ERROR_USERNAME',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}

		$sql = 'SELECT user_yubikey_mask FROM ' . USERS_TABLE . ' WHERE username_clean = \'' . $db->sql_escape(utf8_clean_string($username)) . '\'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		if (!$row)
		{
			return array(
				'status'		=> LOGIN_ERROR_USERNAME,
				'error_msg'		=> 'LOGIN_ERROR_USERNAME',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}
		elseif ($row['user_yubikey_mask'] & 2)
		{
			return array(
				'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
				'error_msg'		=> 'LOGIN_ERROR_YUBIKEY_REQUIRED',
				'user_row'		=> array('user_id' => ANONYMOUS),
			);
		}
	}
	
	return false;
}

function validate_yubikey($otp)
{
	global $config;
	$yk = new Yubikey ((int) $config['yubico_api_id'], $config['yubico_api_key']);
	$yk->setTimeout ((int) $config['yubico_api_timeout']);
	$yk->setTimestampTolerance ((int) $config['yubico_api_tolerance']);

	$yk->verify($otp);
	if (isset($yk->_error) && $yk->_error)
	{
		return array(
			'status'	=> $yk->_error,
			'error_msg'	=> $yk->_response,
			'user_row'	=> array('user_id' => ANONYMOUS),
		);
	}
	else
	{
		return false;
	}
}

function yubikey_login($otp, $username, &$multifactor)
{
	global $config, $db;
	$device = $db->sql_escape(substr($otp, 0, 12));
	$sql = 'SELECT * FROM ' . YUBIKEY_TABLE . " WHERE deviceid = '" . $db->sql_escape($device) . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	$user_id = (int) $row['user_id'];

	// yubikey exists in the database
	if ($row)
	{
		$yk = validate_yubikey($otp);
		
		if ($yk['status'])
		{
			yubikey_increase_logins($device);

			return $yk;
		}
		else
		{
			// fetch the user's information
			$sql = 'SELECT user_id, username, username_clean, user_password, user_passchg, user_pass_convert, user_email,
				user_type, user_login_attempts, user_yubikey_mask FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
			
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			if (!$row)
			{
				yubikey_increase_logins($user_id, false);

				return array(
					'status'	=> LOGIN_ERROR_EXTERNAL_AUTH,
					'error_msg'	=> 'LOGIN_ERROR_YUBIKEY_CONFIG_USER',
					'user_row'	=> array('user_id' => ANONYMOUS)
				);
			}

			$req_name = ($row['user_yubikey_mask'] & 1) | (!empty($username));
			$multifactor = $row['user_yubikey_mask'] & 4;
			
			if ($req_name && $row['username_clean'] != utf8_clean_string($username))
			{
				return array(
					'status'		=> LOGIN_ERROR_USERNAME,
					'error_msg'		=> 'LOGIN_ERROR_USERNAME',
					'user_row'		=> array('user_id' => ANONYMOUS),
				);
			}

			// update database
			$sql = 'UPDATE ' . YUBIKEY_TABLE . ' SET lastseen = ' . time() . ' WHERE deviceid = \'' . $db->sql_escape($device) . '\'';
			$db->sql_query($sql);

			// if we're using multifactor authentication
			if (!$multifactor)
			{
				$login = array(
					'status'	=> LOGIN_SUCCESS,
					'error_msg'	=> false,
					'user_row'	=> $row,
				);
			}
			else
			{
				$login = false;
			}
		}
		
		$multifactor = $row['user_yubikey_mask'] & 4;
		return $login;
	}
	else
	{
		return array(
			'status'		=> LOGIN_ERROR_EXTERNAL_AUTH,
			'error_msg'		=> 'LOGIN_ERROR_YUBIKEY_BAD_OTP',
			'user_row'		=> array('user_id' => ANONYMOUS),
		);
	}
}

?>