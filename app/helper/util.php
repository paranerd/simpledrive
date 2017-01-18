<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Util {
	/**
	 * Send mail using smtp
	 * @param subject
	 * @param recipient
	 * @param msg
	 * @return string on error | null on success
	 */

	public function send_mail($subject, $recipient, $msg) {
		// Check if phpmailer plugin is installed
		if (!file_exists('plugins/phpmailer/PHPMailerAutoload.php')) {
			return null;
		}

		require_once 'plugins/phpmailer/PHPMailerAutoload.php';
		$config = json_decode(file_get_contents('config/config.json'), true);

		$mail = new PHPMailer;

		$mail->SMTPDebug = 0;
		$mail->isSMTP();										// Set mailer to use SMTP
		$mail->Host = 'smtp.gmail.com';							// Specify main and backup SMTP servers
		$mail->SMTPAuth = true;									// Enable SMTP authentication
		$mail->Username = $config['mailuser'];					// SMTP username
		$mail->Password = $config['mailpass'];					// SMTP password
		$mail->SMTPSecure = 'tls';								// Enable TLS encryption, `ssl` also accepted
		$mail->Port = 587;										// TCP port to connect to

		$mail->setFrom($config['mailuser'], 'simpleDrive');
		$mail->addAddress($recipient);							// Add a recipient
		$mail->addReplyTo($config['mailuser'], 'simpleDrive');

		//$mail->addAttachment('/var/tmp/file.tar.gz');			// Add attachments
		$mail->isHTML(true);									// Set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $msg;
		$mail->AltBody = $msg;

		if($mail->send()) {
			return null;
		} else {
			return $mail->ErrorInfo;
		}
	}

	/**
	 * Recursively deletes directory
	 * @param string $path
	 * @param string $owner owner of the directory to delete
	 */

	public function delete_dir($path) {
		$objects = scandir($path);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($path . "/" . $object) == "dir") {
					self::delete_dir($path . "/" . $object);
				}
				else {
					unlink($path . "/" . $object);
				}
			}
		}
		reset($objects);
		rmdir($path);
		return true;
	}

	/**
	 * Recursively copies a directory to the specified target
	 * @param string $sourcepath
	 * @param string $targetpath
	 */

	public function copy_dir($sourcepath, $targetpath) {
		mkdir($targetpath);
		$objects = scandir($sourcepath);

		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($sourcepath . "/" . $object) == "dir") {
					self::copy_dir($sourcepath . "/" . $object, $targetpath . "/" . $object);
				}
				else {
					copy($sourcepath . "/" . $object, $targetpath . "/" . $object);
				}
			}
		}

		reset($objects);
		return true;
	}

	/**
	 * Takes a size string with optional "G", "M" or "K" suffix and converts it into the byte-size
	 */

	public function convert_size($size_string) {
		switch (substr($size_string, -1)) {
			case 'G':
				return floatval($size_string) * 1024 * 1024 * 1024;
			case 'M':
				return floatval($size_string) * 1024 * 1024;
			case 'K':
				return floatval($size_string) * 1024;
			default:
				return floatval($size_string);
		}
	}

	public function bytes_to_string($byte_string) {
		$size = $byte_string;
		if ($size > (1024 * 1024 * 1024)) {
			return floor(($size / (1024 * 1024 * 1024)) * 100) / 100 . " GB";
		}
		else if ($size > (1024 * 1024)) {
			return floor(($size / (1024 * 1024)) * 100) / 100 . " MB";
		}
		else if ($size > 1024) {
			return floor(($size / 1024) * 100) / 100 . " KB";
		}
		else {
			return $size . " Byte";
		}
	}

	/**
	 * Searches an array of files for a specified path
	 * @param string $path needle
	 * @param array $array haystack
	 * @return array|null index and md5-sum if found
	 */

	public function search_in_array_2D($array, $key, $value) {
		foreach ($array as $x => $arr) {
			if ($arr && array_key_exists($key, $arr) && $arr[$key] == $value) {
				return $x;
			}
		}

		return null;
	}

	public function search_in_array_1D($array, $value) {
		foreach ($array as $key => $elem) {
			if ($elem == $value) {
				return $key;
			}
		}
	}

	public function dir_size($path) {
		$size = 0;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
			$size += $file->getSize();
		}

		return $size;
	}

	public function array_has_keys($arr, $keys) {
		foreach ($keys as $key) {
			if (!isset($arr[$key])) {
				return $key;
			}
		}
	}
}