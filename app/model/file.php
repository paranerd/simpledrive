<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

require_once 'app/helper/ogg.class.php';
require_once 'app/helper/sync.php';
require_once 'app/model/user.php';

class File_Model {
	static $PERMISSION_NONE		= 0;
	static $PERMISSION_READ		= 1;
	static $PERMISSION_WRITE	= 2;
	static $FILES				= "/files";
	static $TRASH				= "/trash/";
	static $TEMP				= "/tmp/";
	static $THUMB				= "/thumbnails/";
	static $LOCK				= "/lock/";
	static $PUBLIC_USER_ID		= 1;

	/**
	 * Constructor, links db-connection, sets current user and config array
	 */

	public function __construct($token) {
		$this->token		= $token;
		$this->config		= json_decode(file_get_contents('config/config.json'), true);
		$this->db			= Database::getInstance();
		$this->user			= ($this->db) ? $this->db->user_get_by_token($token) : null;
		$this->uid			= ($this->user) ? $this->user['id'] : self::$PUBLIC_USER_ID;
		$this->username		= ($this->user) ? $this->user['username'] : "";

		$this->init();
	}

	private function init() {
		if ($this->username) {
			$base = $this->config['datadir'] . $this->username;
			$dirs = array(
				self::$FILES,
				self::$TRASH,
				self::$TEMP,
				self::$THUMB,
				self::$LOCK
			);

			foreach ($dirs as $dir) {
				if (!file_exists($base . $dir)) {
					mkdir($base . $dir, 0777, true);
				}
			}
		}
	}

	/**
	 * Returns type of path, e.g. "folder", "audio", "pdf"
	 * @param string $path
	 * @return string
	 */

	public static function type($path) {
		if (extension_loaded('fileinfo')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $path);
			finfo_close($finfo);
			$mime_sup = substr($mime, 0, strpos($mime, '/'));
			$ext = pathinfo($path, PATHINFO_EXTENSION);

			$supported = array(
				'folder'	=> array('directory'),
				'encrypted'	=> array('enc'),
				'image'		=> array('image/png', 'image/jpeg', 'image/gif'),
				'text'		=> array('text', 'inode/x-empty'),
				'pdf'		=> array('application/pdf', 'pdf'),
				'audio'		=> array('audio/mpeg', 'audio/ogg', 'mp3'),
				'video'		=> array('video/ogg', 'video/mp4'),
				'archive'	=> array('application/zip'),
				'odt'		=> array('odt')
			);

			foreach ($supported as $key => $value) {
				if (in_array($mime, $value) || in_array($mime_sup, $value) || in_array($ext, $value)) {
					// Check if webodf plugin is installed
					if ($key == 'odt' && !file_exists('plugins/webodf')) {
						return "unknown";
					}
					return $key;
				}
			}
		}

		if ($mime == "application/ogg") {
			$tag = new Ogg($path,UPDATECACHE);
			return ($tag->LastError) ? "unknown" : (isset($tag->Streams['theora'])) ? "video" : "audio";
		}

		return "unknown";
	}

	/**
	 * Returns filesize for files, filecount for directories
	 * @param string $path
	 * @return string
	 */

	public static function info($path) {
		return (is_dir($path)) ? (count(scandir($path)) - 2) : filesize($path);
	}

	/**
	 * Removes all image-thumbnails (recursively if $file is a directory)
	 * @param array file
	 */

	private function remove_thumbnail($file) {
		$temp = $this->get_thumbnail_dir($file);

		if ($file['type'] == 'folder') {
			$children = $this->db->cache_children_rec($file['id'], $this->uid, $file['ownerid']);

			foreach ($children as $child) {
				if ($child['type'] == 'image') {
					unlink($temp . $child['id']);
				}
			}
		}
		else {
			if ($file['type'] == 'image') {
				unlink($temp . $file['id']);
			}
		}
	}

	/**
	 * Recursively deletes directory
	 * @param integer ownerid
	 * @param string id
	 * @param string path
	 */

	public function recursive_remove($ownerid, $id, $path) {
		$files = scandir($path);

		foreach ($files as $file) {
			if ($file != "." && $file != "..") {
				$id = $this->db->cache_has_child($ownerid, $id, $file);

				if (filetype($path . "/" . $file) == "dir") {
					$this->recursive_remove($ownerid, $id, $path . "/" . $file);
				}
				else if (unlink($path . "/" . $file) && $id) {
					$this->db->cache_remove($id);
				}
			}
		}

		reset($files);
		if (rmdir($path)) {
			$this->db->cache_remove($id);
		}
		return true;
	}

	private function get_temp_dir($file) {
		$temp = $this->config['datadir'] . $file['owner'] . self::$TEMP;

		if ($file['owner'] != "" && !file_exists($temp)) {
			mkdir($temp);
		}

		return $temp;
	}

	private function get_thumbnail_dir($file) {
		$temp = $this->config['datadir'] . $file['owner'] . self::$THUMB;

		if ($file['owner'] != "" && !file_exists($temp)) {
			mkdir($temp);
		}

		return $temp;
	}

	/**
	 * Creates a thumbnail from a pdf or scales an image so that its biggest size is smaller/equal to the biggest size of the target-dimensions while keeping the ratio
	 * @param array file
	 * @param integer Width of the container
	 * @param integer Height of the container
	 * @param boolean Used to differentiate where to store the scaled image
	 * @return string Path of the scaled image
	 */

	private function scale_image($file, $target_width, $target_height, $is_thumb) {
		$src = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
		$temp = ($is_thumb) ? $this->get_thumbnail_dir($file) : $this->get_temp_dir($file);
		$destination = $temp . $file['id'];

		if (!file_exists($temp)) {
			return null;
		}

		// PDF
		if (mime_content_type($src) == "application/pdf") {
			if (!file_exists("/usr/bin/convert")) {
				return null;
			}

			if (!file_exists($destination)) {
				$location   = "/usr/bin/convert";
				$command = $location . " -thumbnail " . $target_width . "x" . $target_height . " \"" . $src . "[0]\"" . " \"" . $destination . "\"";
				exec ($command);
			}
			return $destination;
		}
		// IMAGE
		else {
			// Scale image to fit target dimensions
			$src_info = getimagesize($src);
			$src_width = $src_info[0];
			$src_height = $src_info[1];

			$target_big = max($target_width, $target_height);
			$target_small = min($target_width, $target_height);

			$src_big = max($src_width, $src_height);
			$src_small = min($src_width, $src_height);

			$scale_to = min($target_big / $src_big, $target_small / $src_small);
			$scale_to = ($scale_to > 1) ? 1 : $scale_to;

			$scaled_width = intval($src_width * $scale_to);
			$scaled_height = intval($src_height * $scale_to);

			$scaled = imagecreatetruecolor($scaled_width, $scaled_height);

			// If there's already a scaled image with greater dimensions, use that
			if (file_exists($destination)) {
				$dest_info = getimagesize($destination);

				if ($dest_info[0] >= $target_width || $dest_info[1] >= $target_height) {
					return $destination;
				}
			}

			// GIF
			if ($src_info[2] == 1) {
				return $src;

				$img = ImageCreateFromGIF($src);
				imageCopyResampled($scaled, $img, 0, 0, 0, 0, $scaled_width, $scaled_height, $src_width, $src_height);
				ImageGIF($scaled, $destination);
				return $destination;
			}
			// JPEG
			else if ($src_info[2] == 2) {
				$img = ImageCreateFromJPEG($src);
				imageCopyResampled($scaled, $img, 0, 0, 0, 0, $scaled_width, $scaled_height, $src_width, $src_height);
				ImageJPEG($scaled, $destination);

				return $destination;
			}
			// PNG
			else if ($src_info[2] == 3) {
				imagealphablending($scaled, false);
				imagesavealpha($scaled, true);

				$img = ImageCreateFromPNG($src);
				imagealphablending($img, true);
				imageCopyResampled($scaled, $img, 0, 0, 0, 0, $scaled_width, $scaled_height, $src_width, $src_height);
				ImagePNG($scaled, $destination);

				return $destination;
			}
		}

		return null;
	}

	/**
	 * Recursively adds a directory to a zip-archive
	 * @param string dir directory-path to add
	 * @param ZipArchive $zipArchive
	 * @param string zipdir
	 */

	private function addFolderToZip($dir, $zipArchive, $zipdir) {
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				// Add the directory
				if (!empty($zipdir)) $zipArchive->addEmptyDir($zipdir);
				// Loop through all the files
				while ($file = readdir($dh)) {
					// If it's a folder, run the function again
					if (!is_file($dir . $file)) {
						// Skip parent and root directories!
						if (($file != ".") && ($file != "..")) {
							$this->addFolderToZip($dir . $file . "/", $zipArchive, $zipdir . $file . "/");
						}
					}
					else {
						// Add the files
						$zipArchive->addFile($dir . $file, $zipdir . $file);
					}
				}
			}
		}
	}

	public function sync($target, $clientfiles, $lastsync) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		$history = $this->db->history_for_user($this->uid, $lastsync);
		$serverfiles = $this->db->cache_get_all($this->uid, $file['id']);

		$s = new Sync($this);
		$s->start($file['id'], $clientfiles, $serverfiles, $history, $lastsync);
	}

	public function search($needle) {
		if ($this->uid == self::$PUBLIC_USER_ID) {
			throw new Exception('Access denied', '403');
		}

		$files = $this->db->cache_search($this->uid, $needle);

		return array(
			'files'		=> $files,
			'needle'	=> $needle
		);
	}

	public function encrypt($target, $secret) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
		if (Crypto::encrypt_file($path, $secret)) {
			return null;
		}

		throw new Exception('Error encrypting file', '403');
	}

	public function decrypt($target, $secret) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
		if (Crypto::decrypt_file($path, $secret)) {
			return null;
		}

		throw new Exception('Error decrypting file', '403');
	}

	public function children($target, $mode, $recursive = false, $need_md5 = false) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		// Scan folder if autoscan is enabled
		if ($this->db->user_get_by_id($file['ownerid'])['autoscan']) {
			$this->scan($file['id']);
		}

		// SYNC DEMO
		//$clientfiles = array(array('path' => '/p', 'md5' => "0", "edit" => "1477056203"), array('path' => '/p/ting', 'md5' => "0", "edit" => "1477056203"), array('path' => '/test', 'md5' => "0", "edit" => "477056203"));
		//$files_to_sync = $this->sync($target, $clientfiles, "0");

		$files = array();
		$parents = $this->db->cache_parents($file['id'], $this->uid);

		if ($mode == 'trash') {
			$files = $this->db->cache_get_trash($this->uid);
		}
		else if ($mode == "shareout" && strlen($file['path']) == 0) {
			$files = $this->db->share_get_from($this->uid, self::$PERMISSION_READ);
		}
		else if ($mode == "sharein" && strlen($file['path']) == 0) {
			$files = $this->db->share_get_with($this->uid, self::$PERMISSION_READ);
		}
		else {
			$files = $this->db->cache_children($file['id'], $this->uid, $file['ownerid']);
		}

		return array(
			'files'		=> $files,
			'hierarchy'	=> $parents,
			'current'	=> Util::array_remove_keys($file, array('parent', 'path'))
		);
	}

	/**
	 * Creates file/folder, if no filename is specified it iterates over "Unknown file", "Unknown file (1)", etc.
	 * @param string target ID of the directory the element is created in
	 * @param string type "folder" or "file"
	 * @param string orig_filename name of new element (optional)
	 * @return string|null only return status info if something went wrong
	 */

	public function create($target, $type, $orig_filename = "") {
		if (preg_match('/[\/\\\\]/', $orig_filename)) {
			throw new Exception('Filename not allowed', '400');
		}

		$parent = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$parent) {
			throw new Exception('Permission denied', '403');
		}

		$path = $this->config['datadir'] . $parent['owner'] . self::$FILES . $parent['path'];
		$filename = ($orig_filename != "") ? $orig_filename : "Unknown " . $type;

		if ($orig_filename == "" && file_exists($path . "/" . $filename)) {
			$i = 1;

			while (file_exists($path . "/" . $filename . " (" . $i . ")")) {
				$i++;
			}
			$filename .= " (" . $i . ")";
		}
		else if (file_exists($path . "/" . $filename)) {
			throw new Exception('File already exists', '403');
		}

		// Create file/folder
		if (($type == 'file' && touch($path . "/" . $filename)) ||
				($type == 'folder' && mkdir($path . "/" . $filename, 0777, true)))
		{
			$md5 = (is_dir($path . "/" . $filename)) ? "0" : md5_file($path . "/" . $filename);
			return $this->db->cache_add($filename, $parent['id'], self::type($path . "/" . $filename), self::info($path . "/" . $filename), $parent['ownerid'], filemtime($path . "/" . $filename), $md5, $parent['path'] . "/" . $filename);
		}

		throw new Exception('Error creating file', '403');
	}

	/**
	 * Renames a file/folder
	 * @param integer File-ID
	 * @param string newname new filename
	 * @return string|null only return status info if something went wrong
	 */

	public function rename($id, $newname) {
		if (preg_match('/[\/\\\\]/', $newname)) {
			throw new Exception('Filename not allowed', '400');
		}

		$file = $this->get_cached($id, self::$PERMISSION_WRITE);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		$oldpath = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
		$newpath = dirname($oldpath) . "/" . $newname;

		if (is_file($oldpath) && !strrpos($newpath, '.') && strrpos($oldpath, '.')) {
			$newpath = $newpath . substr($oldpath, strrpos($oldpath, '.'));
			$newname = $newname . substr($oldpath, strrpos($oldpath, '.'));
		}

		if (file_exists($newpath)) {
			throw new Exception('File already exists', '403');
		}

		if (rename($oldpath, $newpath)) {
			$parent = (dirname($file['path']) == "/") ? "/" : dirname($file['path']) . "/";
			$this->db->cache_rename($file['id'], $file['path'], $parent . $newname, $newname, $file['ownerid']);
			return null;
		}

		throw new Exception('Error renaming', '500');
	}

	/**
	 * Delete file or move it to trash
	 * @param array sources File-ID(s) to delete
	 * @return string|null only return status if something went wrong
	 */

	public function delete($sources) {
		$errors = 0;

		foreach ($sources as $source) {
			$file = $this->get_cached($source, self::$PERMISSION_WRITE);

			// Access denied or homefolder
			if (!$file || !$file['filename'] || !$file['path']) {
				$errors++;
				continue;
			}

			$trashdir = $this->config['datadir'] . $file['owner'] . self::$TRASH;

			// Create trash if not exists
			if (!file_exists($trashdir)) {
				mkdir($trashdir, 0777);
			}

			// Fully delete
			if ($file['trash']) {
				$this->remove_thumbnail($file);
				$trash_path = $trashdir . $file['id'];

				if (is_dir($trash_path) && $this->recursive_remove($file['ownerid'], $file['id'], $trash_path) ||
					(file_exists($trash_path) && unlink($trash_path) && $this->db->cache_remove($file['id'])))
				{
					continue;
				}
			}

			// Move to trash
			else {
				if (rename($this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'], $trashdir . $file['id'])) {
					$restorepath = (dirname($file['path']) == 1) ? "/" : dirname($file['path']);
					$this->db->cache_trash($file['id'], $file['ownerid'], $file['path'], $restorepath);
					$this->db->share_remove($file['id']);
					continue;
				}
			}
			$errors++;
		}

		if ($errors > 0) {
			throw new Exception('Error deleting ' . $errors . ' file(s)', '500');
		}
		return null;
	}

	/**
	 * Shares a file
	 * @param string target File-ID to be shared
	 * @param string userto user the file is shared with
	 * @param string mail mail address to notify somebody about file sharing
	 * @param integer write 1 for write access, 0 otherwise
	 * @param integer public 1 for public access, 0 otherwise
	 * @param string pass access password
	 */

	public function share($target, $userto, $mail, $write, $public, $pass) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);
		$access = ($write) ? self::$PERMISSION_WRITE : self::$PERMISSION_READ;

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		// If a user was specified, share with him
		if ($userto) {
			$user = $this->db->user_get_by_name($userto);
			if (!$user) {
				throw new Exception('User "' . $userto . '" does not exist', '400');
			}
			else if ($user['id'] == $file['ownerid']) {
				throw new Exception('You can not share a file with yourself...', '400');
			}
			else if ($this->db->share($file['id'], $user['id'], Crypto::generate_password($pass), $access) === null) {
				throw new Exception('Error sharing with "' . $user['username'] . '"', '500');
			}
		}

		// If the share is supposed to be public, do that
		if ($public == 1) {
			if (($share_id = $this->db->share($file['id'], self::$PUBLIC_USER_ID, Crypto::generate_password($pass), $access)) !== null) {
				$link = $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "files/pub/" . $share_id;
				// Regex for verifying email: '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/'
				/*if (isset($_POST['mail']) && $_POST['mail'] != "" && $this->config['mailuser'] != '' && $this->config['mailpass'] != '') {
					$subject = $this->username . " wants to share a file";
					$msg = $link . "\n Password: " + $pass;
					Util::send_mail($subject, $_POST['mail'], $msg);
				}*/
				return $link;
			}
			throw new Exception('Error creating public share', '500');
		}

		return null;
	}

	/**
	 * Remove a share
	 * @param string id
	 */

	public function unshare($id) {
		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		if ($this->db->share_remove($file['id'])) {
			return null;
		}

		throw new Exception('Error unsharing', '500');
	}

	/**
	 * Returns the share-link if the file was shared to public
	 * @param string id
	 * @return string
	 */

	public function get_link($id) {
		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		if ($share = $this->db->share_get_by_file_id($file['id'])) {
			if ($share['userto'] == self::$PUBLIC_USER_ID) {
				return $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "files/pub/" . $share['id'];
			}
		}

		throw new Exception('Error accessing file', '403');
	}

	/**
	 * Copies file(s) to specified directory
	 * @param integer target ID of target-directory
	 * @param array sources File-ID(s) to copy
	 * @return string|null only return status info if something went wrong
	 */

	public function copy($target, $sources) {
		$targetfile = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$targetfile) {
			throw new Exception('Error accessing file', '403');
		}

		$targetpath = $this->config['datadir'] . $targetfile['owner'] . self::$FILES . $targetfile['path'] . "/";

		$errors = 0;
		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$sourcefile) {
				$errors++;
				continue;
			}

			$sourcepath = $this->config['datadir'] . $sourcefile['owner'] . self::$FILES . $sourcefile['path'];

			if (file_exists($targetpath . $sourcefile['filename']) ||
				strpos($targetpath, $sourcepath . "/") === 0 ||
				(is_dir($sourcepath) && !Util::copy_dir($sourcepath, $targetpath . $sourcefile['filename'])) ||
				(!is_dir($sourcepath) && !copy($sourcepath, $targetpath . $sourcefile['filename'])))
			{
				$errors++;
				continue;
			}

			$this->add($sourcepath, $sourcefile['path'], $targetfile['id'], $targetfile['owner']);
		}

		if ($errors > 0) {
			throw new Exception('Error copying ' . $errors . ' file(s)', '500');
		}
		return null;
	}

	/**
	 * Zips file(s)
	 * @param integer target Directory-id to save zip-file in
	 * @param array sources List of files to zip
	 * @param boolean for_download If file is supposed to be downloaded
	 * @return string path to created zip-file
	 */

	public function zip($target, $sources, $for_download = false) {
		$target = (!$target && $for_download) ? '0' : $target;
		$targetfile = $this->get_cached($target, self::$PERMISSION_READ);

		// Download-only doesn't need permissions, because zip will be created in temp
		if (!$for_download && !$targetfile) {
			throw new Exception('Error accessing file', '403');
		}

		if (!extension_loaded("zip")) {
			$this->db->log_write($this->uid, 2, "Zip", "Extension not installed");
			throw new Exception('Zip extension not installed', '500');
		}

		$temp = $this->get_temp_dir($targetfile);

		if ($for_download && !file_exists($temp)) {
			throw new Exception('Could not create temp-folder', '500');
		}

		$destination = "";
		$destination_parent = ($for_download) ? $temp : $this->config['datadir'] . $targetfile['owner'] . self::$FILES . $targetfile['path'] . "/";
		$datestamp = date("o-m-d-His") . '.' . explode('.', microtime(true))[1];

		if (count($sources) > 1) {
			$destination = $destination_parent . $datestamp . ".zip";
		}
		else {
			$firstfile = $this->get_cached(reset($sources), self::$PERMISSION_READ);
			if ($firstfile) {
				// Strip extension if there is one
				$filename = (strrpos($firstfile['filename'], '.')) ? substr($firstfile['filename'], 0, strrpos($firstfile['filename'], '.')) : $firstfile['filename'];
				$destination = $destination_parent . $filename . "-" . $datestamp . ".zip";
			}
		}

		if (file_exists($destination)) {
			throw new Exception('File already exists', '403');
		}

		$zip = new ZipArchive;
		$zip->open($destination, ZipArchive::CREATE);

		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$sourcefile) {
				continue;
			}

			if (is_dir($this->config['datadir'] . $sourcefile['owner'] . self::$FILES . $sourcefile['path'])) {
				$this->addFolderToZip($this->config['datadir'] . $sourcefile['owner'] . self::$FILES . $sourcefile['path'] . "/", $zip, $sourcefile['filename'] . "/");
			}
			else {
				$zip->addFile($this->config['datadir'] . $sourcefile['owner'] . self::$FILES . $sourcefile['path'], $sourcefile['filename']);
			}
		}

		$zip->close();

		if (file_exists($destination)) {
			return $destination;
		}

		throw new Exception('Error creating zip file', '500');
	}

	public function restore($sources) {
		$errors = 0;

		foreach ($sources as $source) {
			$file = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$file) {
				$errors++;
				continue;
			}

			$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
			$home_path = $this->config['datadir'] . $file['owner'] . self::$FILES . "/" . $file['filename'];
			$trash_path = $this->config['datadir'] . $file['owner'] . self::$TRASH . $file['id'];

			$restore_path = $this->db->cache_get_restore_path($file['id']);
			$restore_id = $this->db->cache_id_for_path($file['ownerid'], $restore_path);

			// Restore to original location
			if ($restore_id && file_exists($this->config['datadir'] . $file['owner'] . self::$FILES . $restore_path . "/") &&
					!file_exists($this->config['datadir'] . $file['owner'] . self::$FILES . $restore_path . "/" . $file['filename']) &&
					rename($trash_path, $this->config['datadir'] . $file['owner'] . self::$FILES . $restore_path . "/" . $file['filename']))
			{
				$this->db->cache_restore($file['id'], $restore_id, $file['ownerid'], $restore_path . "/" . $file['filename']);
				continue;
			}
			// Restore to home
			else if (!file_exists($home_path) && rename($trash_path, $home_path)) {
				$this->db->cache_restore($file['id'], $this->db->cache_get_root_id($file['ownerid']), $file['owner'], "/" . $file['filename']);
				continue;
			}

			$errors++;
		}

		if ($errors == 0) {
			return (count($sources) > 1) ? count($sources) . " files restored" : "1 file restored";
		}

		throw new Exception('Error restoring ' . $errors . ' file(s)', '500');
	}

	/**
	 * Moves file(s) to specified target
	 * @param integer target target-folder-id
	 * @param array sources file-id(s) to move
	 * @return string|null only return status info if something went wrong
	 */

	public function move($target, $sources) {
		$targetfile = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$targetfile) {
			throw new Exception('Error accessing file', '403');
		}

		$targetpath = $this->config['datadir'] . $targetfile['owner'] . self::$FILES . $targetfile['path'];

		$errors = 0;
		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_WRITE);

			if (!$sourcefile) {
				$errors++;
				continue;
			}

			$sourcepath = $this->config['datadir'] . $sourcefile['owner'] . self::$FILES . $sourcefile['path'];

			if ($sourcefile['owner'] != $targetfile['owner']) {
				$this->db->share_remove($sourcefile['id']);
			}

			if (file_exists($targetpath . "/" . $sourcefile['filename']) ||
				strpos($targetpath, $sourcepath . "/") === 0 ||
				!rename($sourcepath, $targetpath . "/" . $sourcefile['filename']))
			{
				$this->db->log_write($this->uid, 2, "Move", "Error moving");
				$errors++;
				continue;
			}

			$this->db->cache_move($sourcefile['id'], $targetfile['id'], $sourcefile['path'], $targetfile['path'] . "/" . $sourcefile['filename'], $sourcefile['ownerid']);
		}

		if ($errors == 0) {
			$targetname = ($targetfile['filename'] == "0") ? "Homefolder" : $targetfile['filename'];
			$msg = (count($sources) == 1) ? $sourcefile['filename'] . " moved to " . $targetname : count($sources) .  " files moved";
			return $msg;
		}

		throw new Exception('Error moving ' . $errors . ' file(s)', '500');
	}

	/**
	 * Uploads files in the $_FILES-array to the specified directory
	 * @param integer id of target-directory to upload to
	 */

	public function upload($target) {
		if (isset($_FILES[0])) {
			$max_upload = Util::convert_size(ini_get('upload_max_filesize'));
			$parent = $this->get_cached($target, self::$PERMISSION_WRITE);

			if (!$parent || preg_match('/[\/\\\\]/', $_FILES[0]['name'])) {
				throw new Exception('Access denied', '403');
			}

			$u = new User_Model($this->token);
			if (!$u->check_quota($parent['ownerid'], $_FILES[0]['size']) || $_FILES[0]['size'] > $max_upload) {
				throw new Exception('File too big', '500');
			}

			$userdir = $this->config['datadir'] . $parent['owner'] . self::$FILES;
			$rel_path = $parent['path'];

			$parent_id = $parent['id'];

			$upload_relative_path = rtrim(trim($_POST['paths'], '/'), '/');
			$upload_relative_path_arr = explode('/', $upload_relative_path);

			// Create folder if not exists and user has the permission (for each sub-folder)
			while (sizeof($upload_relative_path_arr) > 0) {
				$next = array_shift($upload_relative_path_arr);
				$rel_path .= "/" . $next;

				if (!file_exists($userdir . $rel_path)) {
					if (mkdir($userdir . $rel_path, 0755) && $this->get_cached($parent_id, self::$PERMISSION_WRITE)) {
						$parent_id = $this->add($userdir . $rel_path, $rel_path, $parent_id, $parent['ownerid']);
					}
					else {
						throw new Exception('Error uploading', '500');
					}
				}
				else {
					$parent_id = $this->db->cache_id_for_path($parent['ownerid'], $rel_path);
					$access_required = (sizeof($upload_relative_path_arr) == 0) ? self::$PERMISSION_WRITE : self::$PERMISSION_READ;
					if (!$this->get_cached($parent_id, $access_required)) {
						throw new Exception('Access denied', '403');
					}
				}
			}

			$rel_path .= ($_POST['paths']) ? "/" . $_FILES[0]['name'] : $_FILES[0]['name'];
			$exists = file_exists($userdir . $rel_path);

			// Actually write the file
			if (move_uploaded_file($_FILES[0]['tmp_name'], $userdir . $rel_path)) {
				if ($exists) {
					$id = $this->db->cache_id_for_path($parent['ownerid'], $rel_path);
					$this->db->cache_update($id, self::type($userdir . $rel_path), self::info($userdir . $rel_path), filemtime($userdir . $rel_path), md5_file($userdir . $rel_path), $parent['owner'], $rel_path);
				}
				else {
					$this->add($userdir . $rel_path, $rel_path, $parent['id'], $parent['ownerid']);
				}
				return null;
			}
			else {
				throw new Exception('Unknown error while uploading', '500');
			}
		}

		throw new Exception('No files to upload', '500');
	}

	/**
	 * Get file-info for public share-id, check for access permissions and return if granted
	 * @param string id public share-id
	 * @param string pass password
	 * @return array share-info
	 */

	public function get_public($id, $pass) {
		$share = $this->db->share_get($id);

		// File not shared at all
		if (!$share || !$share['userto'] == self::$PUBLIC_USER_ID) {
			throw new Exception('File not found', '500');
		}

		$file = $this->db->cache_get($share['file'], $this->uid);

		// File does not exist
		if (!$file) {
			throw new Exception('File not found', '500');
		}

		// Incorrect password
		else if (!Crypto::verify_password($pass, $share['pass']) && !$this->db->share_is_unlocked($share['file'], self::$PERMISSION_READ, $this->token)) {
			throw new Exception('Wrong password', '403');
		}
		else {
			$token = ($this->token) ? $this->token : Crypto::generate_token(0, $id);

			if ($token && $this->db->share_unlock($token, $id)) {
				return array('share' => array('id' => $file['id'], 'filename' => $file['filename'], 'type' => $file['type']), 'token' => $token);
			}
		}

		throw new Exception('An error occurred', '500');
	}

	/**
	 * Returns file to client (in 200kB chunks, so images can build up progressively)
	 * @param array targets file-id(s) to return
	 * @param integer width screen width for shrinking to save bandwidth
	 * @param integer height screen heightfor shrinking to save bandwidth
	 * @return file
	 */

	public function get($targets, $width = null, $height = null, $thumb = 0) {
		$path = null;
		$delete_flag = false;

		// Check each file for access permission
		foreach ($targets as $target) {
			$file = $this->get_cached($target, self::$PERMISSION_READ);

			if (!$file) {
				throw new Exception('Error accessing file', '403');
			}
		}

		$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];

		if (count($targets) > 1 || is_dir($path)) {
			$delete_flag = true;
			$destination = $this->zip(null, $targets, true);
		}
		else if ($width && $height) {
			file_put_contents(LOG, "requesting thumb for " . $file['filename']. "\n", FILE_APPEND);
			$destination = $this->scale_image($file, $width, $height, $thumb);
		}
		else {
			$destination = $path;
		}

		if (file_exists($destination) && is_file($destination)) {
			$download_rate = 200;
			header('Cache-control: private');
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			header("Content-Type: " . finfo_file($finfo, $destination));
			//header('Content-Type: application/octet-stream');
			header('Content-Length: '.filesize($destination));
			header("Content-Disposition: attachment; filename=" . urlencode(basename($destination)));

			finfo_close($finfo);
			flush();
			$f = fopen($destination, "r");
			while (!feof($f)) {
				// send the current file part to the browser
				print fread($f, round($download_rate * 1024));
				// flush the content to the browser
				flush();
			}
			fclose($f);

			if ($delete_flag) {
				unlink($destination);
			}
			return null;
		}

		throw new Exception('Error downloading', '500');
	}

	public function get_id3($target) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		return $file['filename'];
	}

	public function save_odf($target) {
		if (isset($_FILES['data'])) {
			$file = $this->get_cached($target, self::$PERMISSION_WRITE);

			if (!$file) {
				throw new Exception('Error accessing file', '403');
			}

			if (move_uploaded_file($_FILES['data']['tmp_name'], $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'])) {
				return null;
			}
		}

		throw new Exception('Error saving file', '500');
	}

	public function load_text($target) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		if (is_readable($this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'])) {
			return array('filename' => $file['filename'], 'content' => file_get_contents($this->config['datadir'] . $file['owner'] . self::$FILES . $file['path']));
		}

		throw new Exception('Error accessing file', '403');
	}

	public function save_text($target, $data) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$file) {
			throw new Exception('Error accessing file', '403');
		}

		$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'];
		if (file_put_contents($path, $data) !== false) {
			$this->db->cache_update($file['id'], self::type($path), self::info($path), filemtime($path), md5_file($path), $file['owner'], $file['path']);
			return null;
		}

		throw new Exception('Error saving file', '500');
	}

	/**
	 * Get file-info from DB and check if user has permission to access and return if so
	 * @param integer id file-ID
	 * @param integer access required access-rights
	 * @return array file
	 */

	public function get_cached($id, $access) {
		if (!$access) {
			return;
		}

		// Get proper ID
		$id = ($id && $id != "0") ? $id : $this->db->cache_get_root_id($this->uid);
		// Get file from database
		$file = $this->db->cache_get($id, $this->uid);
		// Only return the file if owned or shared
		return ($file && ($file['ownerid'] == $this->uid || $this->db->share_is_unlocked($file['id'], $access, $this->token))) ? $file : null;
	}

	/**
	 * Get file-info from DB and check if user has permission to access and return if so
	 * @param id folder-ID
	 * @param update whether or not to update file-info
	 * @param include_childs wether or not to go recursive
	 */

	public function scan($id, $update = false, $include_childs = false) {
		$scan_lock = ($this->username) ? $this->config['datadir'] . $this->username . self::$LOCK . "scan" : null;
		set_time_limit(0);

		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file || !$file['ownerid'] || $file['ownerid'] != $this->uid || file_exists($scan_lock)) {
			return;
		}

		// Set lock
		if (!file_exists(dirname($scan_lock))) {
			mkdir(dirname($scan_lock));
		}
		file_put_contents($scan_lock, '', LOCK_EX);

		$path = $this->config['datadir'] . $file['owner'] . self::$FILES . $file['path'] . "/";
		$trash_path = $this->config['datadir'] . $file['owner'] . self::$TRASH;

		// Start scan
		$this->scan_trash($file['ownerid'], $file['owner'], $trash_path);

		$start = time();

		if (is_dir($path)) {
			$this->scan_folder($path, $file['path'] . "/", $file['id'], $file['ownerid'], $update, $include_childs);
		}

		$this->db->cache_clean($file['id'], $file['ownerid'], $start, $update, $include_childs);

		// Release lock when finished
		if (file_exists($scan_lock)) {
			unlink($scan_lock);
		}
	}

	private function scan_folder($path, $rel_path, $id, $owner, $update, $include_childs) {
		$files = scandir($path);
		$size = 0;
		$ids = array();

		foreach ($files as $file) {
			if (is_readable($path . $file) && substr($file, 0, 1) != ".") {
				$size++;
				if ($child_id = $this->db->cache_has_child($owner, $id, $file)) {
					if ($update) {
						$md5 = (is_dir($path . $file)) ? "0" : md5_file($path . $file);
						$this->db->cache_update($child_id, self::type($path . $file), self::info($path . $file), filemtime($path . $file), $md5, $owner, $rel_path . $file);
					}
					else {
						array_push($ids, $child_id);
					}

					if ($include_childs && is_dir($path . $file)) {
						$this->scan_folder($path . $file . "/", $rel_path . $file . "/", $child_id, $owner, $update, $include_childs);
					}
				}
				else {
					$this->add($path . $file, $rel_path . $file, $id, $owner, $include_childs);
				}
				clearstatcache();
			}
		}

		if (!$update) {
			$this->db->cache_refresh_array($ids);
		}
		$this->db->cache_update_size($id, $size);
	}

	public function add($path, $rel_path, $parent_id, $owner, $include_childs = false) {
		$md5 = (is_dir($path)) ? "0" : md5_file($path);
		$child_id = $this->db->cache_add(basename($path), $parent_id, self::type($path), self::info($path), $owner, filemtime($path), $md5, $rel_path);

		if ($include_childs && is_dir($path)) {
			$this->add_folder($path . "/", $rel_path . "/", $child_id, $owner);
		}

		return $child_id;
	}

	private function add_folder($path, $rel_path, $id, $owner) {
		$files = scandir($path);

		foreach ($files as $file) {
			if (is_readable($path . $file) && substr($file, 0, 1) != ".") {
				$md5 = (is_dir($path . $file)) ? "0" : md5_file($path . $file);
				$child_id = $this->db->cache_add($file, $id, self::type($path . $file), self::info($path . $file), $owner, filemtime($path . $file), $md5, $rel_path . $file);

				if (is_dir($path . $file)) {
					$this->add_folder($path . $file . "/", $rel_path . $file . "/", $child_id, $owner);
				}
			}
		}
	}

	public function scan_trash($oid, $owner, $path) {
		if (!file_exists($path)) {
			return;
		}

		$files = scandir($path);
		$existing = array();

		foreach ($files as $file) {
			if (is_readable($path . $file) && substr($file, 0, 1) != ".") {
				// Add trash-hash to list of existing files
				array_push($existing, $file);
			}
		}

		$this->db->cache_clean_trash($oid, $existing);
	}
}
