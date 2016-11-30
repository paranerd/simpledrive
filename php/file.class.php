<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

require_once dirname(__DIR__) . '/lib/oggclass/Ogg.class.php';
require_once 'sync.class.php';

class File {
	static $ROOT_ID				= "0";
	static $PERMISSION_NONE		= 0;
	static $PERMISSION_READ		= 1;
	static $PERMISSION_WRITE	= 2;
	static $TRASH				= "/.trash/";
	static $TEMP				= "/.tmp/";

	/**
	 * Constructor, links db-connection, sets current user and config array
	 */

	public function __construct($token) {
		$this->token				= $token;
		$this->config				= json_decode(file_get_contents(dirname(__DIR__) . '/config/config.json'), true);
		$this->db					= Database::getInstance();
		$this->user					= $this->db->user_get_by_token($token);
		$this->uid					= ($this->user) ? $this->user['id'] : null;
		$this->username				= ($this->user) ? $this->user['username'] : "";
		$this->core					= new Core();
		$this->scan_lock			= dirname(__FILE__) . '/../config/locks/scan_';
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
					if ($key == 'odt' && !file_exists(dirname(__DIR__) . "/plugins/webodf")) {
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
	 * Removes all thumbnails for images (recursively in a directory)
	 * @param string $id
	 * @param string $path to check if is directory
	 */

	private function remove_thumbnail($file) {
		$thumbnails = (is_dir($this->config['datadir'] . $file['owner'] . $file['path'])) ? $this->db->thumbnail_get_all($file['id']) : array(array('id' => $file['id'], 'path' => $this->db->thumbnail_get_path($file['id'])));
		$temp = $this->get_temp_dir($file);

		if (!file_exists($temp)) {
			return;
		}

		foreach ($thumbnails as $thumbnail) {
			if (strlen($thumbnail['path']) > 0 && unlink($temp . $thumbnail['path'])) {
				$this->db->thumbnail_remove($thumbnail['id']);
			}
		}
	}

	/**
	 * Recursively deletes directory
	 * @param string $path
	 * @param string $owner owner of the directory to delete
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

	/**
	 * Creates a thumbnail from a pdf or shrinks an image so that its biggest size is smaller/equal to to biggest size of the supplied container-dimensions while keeping the ratio
	 * @param int $width of the container
	 * @param int $height of the container
	 * @param string $src path of the original file
	 * @param string|null $target destination-path (if null, function generates the path - important for image-type)
	 * @return string destination-path
	 */

	private function shrink_image($file, $width, $height) {
		$src = $this->config['datadir'] . $file['owner'] . $file['path'];
		$temp = $this->config['datadir'] . $file['owner'] . self::$TEMP;

		if (!file_exists($temp)) {
			return null;
		}

		$thumb_name = $this->db->thumbnail_get_path($file['id']);

		// PDF
		if (mime_content_type($src) == "application/pdf") {
			if (!file_exists("/usr/bin/convert")) {
				return null;
			}

			$thumb_name = ($thumb_name) ? $thumb_name : md5($src) . ".jpg";
			if (!file_exists($temp . $thumb_name)) {
				$location   = "/usr/bin/convert";
				$command = $location . " -thumbnail " . $width . "x" . $height . " \"" . $src . "[0]\"" . " \"" . $temp . $thumb_name . "\"";
				exec ($command);
				$this->db->thumbnail_create($file['id'], md5($src) . ".jpg");
			}
			return $temp . $thumb_name;
		}
		// IMAGE
		else {
			$info = getimagesize($src);
			$img_width = $info[0];
			$img_height = $info[1];

			$bigger_size = max($width, $height);
			$smaller_size = min($width, $height);

			$shrink_to = 1;

			if ($img_height > $img_width) {
				$shrink_to = ($img_height > $bigger_size || $img_width > $smaller_size) ? min($bigger_size / $img_height, $smaller_size / $img_width) : 1;
			}
			else {
				$shrink_to = ($img_width > $bigger_size || $img_height > $smaller_size) ? min($smaller_size / $img_height, $bigger_size / $img_width) : 1;
			}

			$target_width = intval($img_width * $shrink_to);
			$target_height = intval($img_height * $shrink_to);

			$thumb = imagecreatetruecolor($target_width, $target_height);

			if ($info[2] == 1) {
				$thumb_name = ($thumb_name) ? $thumb_name : md5($src) . ".gif";

				if (file_exists($temp . $thumb_name)) {
					$info2 = getimagesize($temp . $thumb_name);

					if ($info2[0] >= $width || $info2[1] >= $height) {
						return $temp . $thumb_name;
					}
				}

				$this->db->thumbnail_create($file['id'], $thumb_name);
				return $src;

				$img = ImageCreateFromGIF($src);
				imageCopyResampled($thumb, $img, 0, 0, 0, 0, $target_width, $target_height, $img_width, $img_height);
				ImageGIF($thumb, $temp . $thumb_name);
				return $temp . $thumb_name;
			}
			else if ($info[2] == 2) {
				$thumb_name = ($thumb_name) ? $thumb_name : md5($src) . ".jpg";

				if (file_exists($temp . $thumb_name)) {
					$info2 = getimagesize($temp . $thumb_name);

					if ($info2[0] >= $width || $info2[1] >= $height) {
						return $temp . $thumb_name;
					}
				}

				$img = ImageCreateFromJPEG($src);
				imageCopyResampled($thumb, $img, 0, 0, 0, 0, $target_width, $target_height, $img_width, $img_height);
				ImageJPEG($thumb, $temp . $thumb_name);

				$this->db->thumbnail_create($file['id'], $thumb_name);
				return $temp . $thumb_name;
			}
			else if ($info[2] == 3) {
				$thumb_name = ($thumb_name) ? $thumb_name : md5($src) . ".png";

				if (file_exists($temp . $thumb_name)) {
					$info2 = getimagesize($temp . $thumb_name);

					if ($info2[0] >= $width || $info2[1] >= $height) {
						return $temp . $thumb_name;
					}
				}

				imagealphablending($thumb, false);
				imagesavealpha($thumb, true);

				$img = ImageCreateFromPNG($src);
				imagealphablending($img, true);
				imageCopyResampled($thumb, $img, 0, 0, 0, 0, $target_width, $target_height, $img_width, $img_height);
				ImagePNG($thumb, $temp . $thumb_name);

				$this->db->thumbnail_create($file['id'], $thumb_name);
				return $temp . $thumb_name;
			}
		}

		return null;
	}

	/**
	 * Recursively adds a directory to a zip-archive
	 * @param array $dir directory to add
	 * @param ZipArchive $zipArchive
	 * @param string $zipdir
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
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		$history = $this->db->history_for_user($this->uid, $lastsync);
		$serverfiles = $this->db->cache_get_all($this->uid, $target);

		$s = new Sync($this);
		$s->start($target, $clientfiles, $serverfiles, $history, $lastsync);
	}

	public function for_benchmark($path) {
		$files = scandir($path);
		$size = 0;
		$ids = array();

		foreach ($files as $file) {
			if (is_readable($path . $file) && substr($file, 0, 1) != ".") {
				$type = $this->type($path . $file);
				$info = $this->info($path . $file);
			}
		}
	}

	public function children($target, $mode, $recursive = false, $need_md5 = false) {
		$start = microtime(true);
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		// Scan folder if autoscan is enabled
		if ($this->db->user_autoscan($file['ownerid'])) {
			$this->scan($target);
		}
		//$this->for_benchmark($this->config['datadir'] . $file['owner'] . $file['path'] . "/");

		// SYNC DEMO
		//$clientfiles = array(array('path' => '/p', 'md5' => "0", "edit" => "1477056203"), array('path' => '/p/ting', 'md5' => "0", "edit" => "1477056203"), array('path' => '/test', 'md5' => "0", "edit" => "477056203"));
		//$files_to_sync = $this->sync($target, $clientfiles, "0");

		$files = array();
		$parents = $this->db->cache_parents($target, $this->uid);

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
			$files = $this->db->cache_children($target, $file['ownerid'], self::$PERMISSION_READ);
		}

		//file_put_contents(LOG, "db took " . (microtime(true) - $start) . " seconds\n", FILE_APPEND);
		return array('files' => $files, 'hierarchy' => $parents);
	}

	/**
	 * Creates file/folder, if no filename is specified it iterates over "Unknown file", "Unknown file (1)", etc.
	 * @param array $target directory the element is created in
	 * @param string $type "folder" or "file"
	 * @param string $orig_filename name of new element (not necessary)
	 * @return string|null only return status info if something went wrong
	 */

	public function create($target, $type, $orig_filename = "") {
		if (preg_match('/[\/\\\\]/', $orig_filename)) {
		//if (preg_match('/[\'\/~`\!@#\$%\^&\*=\{\}\[\];:"\<\>,\?\\\\]/', $orig_filename)) {
			header('HTTP/1.1 400 Filename not allowed');
			return "Filename not allowed";
		}

		$file = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		$path = $this->config['datadir'] . $file['owner'] . $file['path'];
		$filename = ($orig_filename != "") ? $orig_filename : "Unknown " . $type;

		if ($orig_filename == "" && file_exists($path . "/" . $filename)) {
			$i = 1;

			while (file_exists($path . "/" . $filename . " (" . $i . ")")) {
				$i++;
			}
			$filename .= " (" . $i . ")";
		}
		else if (file_exists($path . "/" . $filename)) {
			header('HTTP/1.1 403 This file already exists');
			return "This file already exists";
		}

		// Create file/folder
		if (($type == 'file' && touch($path . "/" . $filename)) ||
			mkdir($path . "/" . $filename, 0777, true))
		{
			$md5 = (is_dir($path . "/" . $filename)) ? "0" : md5_file($path . "/" . $filename);
			$this->db->cache_add($filename, $target, $this->type($path . "/" . $filename), $this->info($path . "/" . $filename), $file['ownerid'], filemtime($path . "/" . $filename), $md5, $file['path'] . "/" . $filename);
			return null;
		}

		header('HTTP/1.1 403 Error creating file');
		return "Error creating file";
	}

	/**
	 * Renames a file/folder
	 * @param array $file
	 * @param string $newname new filename
	 * @return string|null only return status info if something went wrong
	 */

	public function rename($id, $newname) {
		if (preg_match('/[\/\\\\]/', $orig_filename)) {
		//if (preg_match('/[\'\/~`\!@#\$%\^&\*=\{\}\[\];:"\<\>,\?\\\\]/', $orig_filename)) {
			header('HTTP/1.1 400 Filename not allowed');
			return "Filename not allowed";
		}

		$file = $this->get_cached($id, self::$PERMISSION_WRITE);

		if (!$file) {
			header('HTTP/1.1 500 Error accessing file');
			return "Error accessing file";
		}

		$oldpath = $this->config['datadir'] . $file['owner'] . $file['path'];
		$newpath = dirname($oldpath) . "/" . $newname;

		if (is_file($oldpath) && !strrpos($newpath, '.') && strrpos($oldpath, '.')) {
			$newpath = $newpath . substr($oldpath, strrpos($oldpath, '.'));
			$newname = $newname . substr($oldpath, strrpos($oldpath, '.'));
		}

		if (file_exists($newpath)) {
			header('HTTP/1.1 403 File exists');
			return "File exists";
		}

		if (rename($oldpath, $newpath)) {
			$parent = (dirname($file['path']) == "/") ? "/" : dirname($file['path']) . "/";
			$this->db->cache_rename($id, $file['path'], $parent . $newname, $newname, $file['ownerid']);
			return null;
		}

		header('HTTP/1.1 500 Error renaming');
		return "Error renaming";
	}

	/**
	 * Delete file or move it to trash
	 * @param array $sources files to delete
	 * @param string $final move files to trash if "false"
	 * @return string|null only return status if something went wrong
	 */

	public function delete($sources) {
		$start = microtime(true);
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
				$trash_path = $trashdir . $file['filename'] . $file['trash'];

				if (is_dir($trash_path) && $this->recursive_remove($file['ownerid'], $source, $trash_path) ||
					(file_exists($trash_path) && unlink($trash_path) && $this->db->cache_remove($source)))
				{
					$this->remove_thumbnail($file);
					continue;
				}
			}

			// Move to trash
			else {
				$trash_hash = $this->db->cache_get_unique_trashhash();

				if (rename($this->config['datadir'] . $file['owner'] . $file['path'], $trashdir . $file['filename'] . $trash_hash)) {
					$restorepath = (dirname($file['path']) == 1) ? "/" : dirname($file['path']);
					$this->db->cache_trash($source, $restorepath, $trash_hash, $file['ownerid'], $file['path']);
					$this->db->share_remove($source);
					continue;
				}
			}
			$errors++;
		}

		if ($errors > 0) {
			header('HTTP/1.1 500 Error deleting ' . $errors . ' file(s)');
			return "Error deleting " . $errors . " file(s)";
		}
		return null;
	}

	/**
	 * Engages to create a share entry
	 * @param array $file file to be shared
	 * @param string $userto user the file is shared with
	 * @param string $mail mail address to notify somebody about file sharing
	 * @param integer $write 1 for write access, 0 otherwise
	 * @param integer $public 1 for public access, 0 otherwise
	 * @param string $key access password
	 */

	public function share($target, $userto, $mail, $write, $public, $key) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);
		$user = $this->db->user_get_by_name($userto);
		$userto_uid = ($user) ? $user['id'] : null;

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		if ($userto && !$user) {
			header('HTTP/1.1 400 User does not exist');
			return "User does not exist";
		}

		if ($userto == $file['owner']) {
			header('HTTP/1.1 400 That is yourself...');
			return "That is yourself...";
		}

		$access = ($write) ? self::$PERMISSION_WRITE : self::$PERMISSION_READ;
		$crypt_pass = ($key) ? hash('sha256', $key . $this->config['salt']) : '';

		if ($hash = $this->db->share($target, $userto_uid, $crypt_pass, $public, $access)) {
			if ($public == 1) {
				$link = $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "files/public/" . $hash;
				// Regex for verifying email: '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/'
				/*if (isset($_POST['mail']) && $_POST['mail'] != "" && $this->config['mailuser'] != '' && $this->config['mailpass'] != '') {
					$subject = $this->username . " wants to share a file";
					$msg = $link . "\n Password: " + $key;
					Util::send_mail($subject, $_POST['mail'], $msg);
				}*/
				return $link;
			}
			return null;
		}

		header('HTTP/1.1 500 An error occurred');
		return "An error occurred";
	}

	/**
	 * Engages the removal of the share entry for file from database
	 * @param array $file
	 */

	public function unshare($id) {
		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file) {
			header('HTTP/1.1 403 Access denied');
			return "Access denied";
		}

		if ($this->db->share_remove($id)) {
			return null;
		}

		header('HTTP/1.1 500 Error unsharing');
		return "Error unsharing";
	}

	/**
	 * Returns the share-link if the file was shared to public
	 * @param array $file
	 * @return string
	 */

	public function get_link($id) {
		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file) {
			header('HTTP/1.1 403 Access denied');
			return "Access denied";
		}

		if ($share = $this->db->share_get_by_id($id)) {
			if ($share['public']) {
				return $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "files/public/" . $share['hash'];
				//return $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "files/public/" . $share['hash'] . "?r=1";
				//return $this->config['protocol'] . $this->config['domain'] . $this->config['installdir'] . "public?r=" . $share['hash'];
			}
		}

		header('HTTP/1.1 403 Access denied');
		return "Access denied";
	}

	/**
	 * Copies file(s) to specified directory
	 * @param array $sources file(s) to copy
	 * @param array $target target directory
	 * @return string|null only return status info if something went wrong
	 */

	public function copy($target, $sources) {
		$targetfile = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$targetfile) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		$targetpath = $this->config['datadir'] . $targetfile['owner'] . $targetfile['path'] . "/";

		$errors = 0;
		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$sourcefile) {
				$errors++;
				continue;
			}

			$sourcepath = $this->config['datadir'] . $sourcefile['owner'] . $sourcefile['path'];

			if (file_exists($targetpath . $sourcefile['filename']) ||
				(is_dir($sourcepath) && !Util::copy_dir($sourcepath, $targetpath . $sourcefile['filename'])) ||
				(!is_dir($sourcepath) && !copy($sourcepath, $targetpath . $sourcefile['filename'])))
			{
				$errors++;
				continue;
			}

			$this->add($sourcepath, $sourcefile['path'], $target, $target['owner']);
		}

		if ($errors > 0) {
			header('HTTP/1.1 500 Error copying ' . $errors . ' file(s)');
			return "Error copying " . $errors . " file(s)";
		}
		return null;
	}

	/**
	 * Zips file(s)
	 * @param array $sources list of files to zip
	 * @param array $target directory to save zip-file in
	 * @return string path to created zip-file
	 */

	public function zip($target, $sources, $for_download = false) {
		$targetfile = ($for_download) ? null : $this->get_cached($target, self::$PERMISSION_READ);

		// Download-only doesn't need permissions, because zip will be created in temp
		if (!$for_download && !$targetfile) {
			header('HTTP/1.1 403 Error accessing file');
			return ($for_download) ? null : "Error accessing file";
		}

		if (!extension_loaded("zip")) {
			$this->db->log_write($this->uid, 2, "Zip", "Extension not installed");
			header('HTTP/1.1 500 Zip extension not installed');
			return ($for_download) ? null : "Zip extension not installed";
		}

		$temp = $this->get_temp_dir($targetfile);

		if ($for_download && !file_exists($temp)) {
			header('HTTP/1.1 500 Could not create temp-folder');
			return ($for_download) ? null : "Could not create temp-folder";
		}

		$destination;
		$destination_parent = ($for_download) ? $temp : $this->config['datadir'] . $targetfile['owner'] . $targetfile['path'] . "/";
		$datestamp = date("o-m-d-His") . '.' . explode('.', microtime(true))[1];

		if (count($sources) > 1) {
			$destination_parent . $datestamp . ".zip";
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
			header('HTTP/1.1 403 File already exists');
			return ($for_download) ? null : "File already exists";
		}

		$zip = new ZipArchive;
		$zip->open($destination, ZipArchive::CREATE);

		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$sourcefile) {
				continue;
			}

			if (is_dir($this->config['datadir'] . $sourcefile['owner'] . $sourcefile['path'])) {
				$this->addFolderToZip($this->config['datadir'] . $sourcefile['owner'] . $sourcefile['path'] . "/", $zip, $sourcefile['filename'] . "/");
			}
			else {
				$zip->addFile($this->config['datadir'] . $sourcefile['owner'] . $sourcefile['path'], $sourcefile['filename']);
			}
		}

		$zip->close();

		if (file_exists($destination)) {
			return $destination;
		}

		header('HTTP/1.1 500 Error creating zip file');
		return ($for_download) ? null : "Error creating zip file";
	}

	public function restore($sources) {
		$errors = 0;

		foreach ($sources as $source) {
			$file = $this->get_cached($source, self::$PERMISSION_READ);

			if (!$file) {
				$errors++;
				continue;
			}

			$path = $this->config['datadir'] . $file['owner'] . $file['path'];
			$home_path = $this->config['datadir'] . $file['owner'] . "/" . $file['filename'];
			$trash_path = $this->config['datadir'] . $file['owner'] . self::$TRASH . $file['filename'] . $file['trash'];

			$restore_path = $this->db->cache_get_restore_path($file['id']);
			$restore_id = $this->db->cache_id_for_path($file['ownerid'], $restore_path);

			// Restore to original location
			if ($restore_id && file_exists($this->config['datadir'] . $file['owner'] . $restore_path . "/") && !file_exists($this->config['datadir'] . $file['owner'] . $restore_path . "/" . $file['filename']) && rename($trash_path, $this->config['datadir'] . $file['owner'] . $restore_path . "/" . $file['filename'])) {
				$this->db->cache_restore($source, $restore_id, $file['ownerid'], $restore_path . "/" . $file['filename']);
				continue;
			}
			// Restore to home
			else if (!file_exists($home_path) && rename($trash_path, $home_path)) {
				$this->db->cache_restore($source, self::$ROOT_ID, $file['owner'], "/" . $file['filename']);
				continue;
			}

			$errors++;
		}

		if ($errors == 0) {
			return (count($sources) > 1) ? count($sources) . " files restored" : "1 file restored";
		}

		header('HTTP/1.1 500 Error restoring ' . $errors . ' file(s)');
		return "Error restoring " . $errors . " file(s)";
	}

	/**
	 * Moves file(s) to specified target
	 * @param array $sources files to move
	 * @param array $target target folder
	 * @param string $trash if "true", special name-rules apply
	 * @return string|null only return status info if something went wrong
	 */

	public function move($target, $sources) {
		$targetfile = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$targetfile) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		$targetpath = $this->config['datadir'] . $targetfile['owner'] . $targetfile['path'];

		$errors = 0;
		foreach ($sources as $source) {
			$sourcefile = $this->get_cached($source, self::$PERMISSION_WRITE);

			if (!$sourcefile) {
				$errors++;
				continue;
			}

			$sourcepath = $this->config['datadir'] . $sourcefile['owner'] . $sourcefile['path'];

			if ($sourcefile['owner'] != $targetfile['owner']) {
				$this->db->share_remove($sourcefile['id']);
			}

			if (file_exists($targetpath . "/" . $sourcefile['filename']) || !rename($sourcepath, $targetpath . "/" . $sourcefile['filename'])) {
				$this->db->log_write($this->uid, 2, "Move", "Error moving");
				$errors++;
				continue;
			}

			$this->db->cache_move($source, $target, $sourcefile['path'], $targetfile['path'] . "/" . $sourcefile['filename'], $sourcefile['ownerid']);
		}

		if ($errors == 0) {
			$targetname = ($targetfile['filename'] == "0") ? "Homefolder" : $targetfile['filename'];
			$msg = (count($sources) == 1) ? $sourcefile['filename'] . " moved to " . $targetname : count($sources) .  " files moved";
			return $msg;
		}

		header('HTTP/1.1 500 Error moving ' . $errors . ' file(s)');
		return "Error moving " . $errors . " file(s)";
	}

	/**
	 * Uploads files in the $_FILES-array to the specified directory
	 * @param array $dir directory to upload to
	 */

	public function upload($target) {
		if (isset($_FILES[0])) {
			$max_upload = Util::convert_size(ini_get('upload_max_filesize'));
			$file = $this->get_cached($target, self::$PERMISSION_WRITE);

			if (!$file || preg_match('/[\/\\\\]/', $_FILES[0]['name'])) {
				header('HTTP/1.1 403 Access denied');
				return "Access denied";
			}

			$path = $this->config['datadir'] . $file['owner'] . $file['path'];

			$u = new User($this->token);
			if (!$u->check_quota($file['owner'], $_FILES[0]['size']) || $_FILES[0]['size'] > $max_upload) {
				header('HTTP/1.1 500 File too big');
				return "File too big";
			}

			$rel_path = $file['path'];
			$forward_path = explode("/", $_POST['paths']);
			if (sizeof($forward_path) > 0) {
				array_shift($forward_path);
			}

			$parent_id = $target;

			// Create folder if not exists and user has the permission (for each sub-folder)
			while (sizeof($forward_path) > 0) {
				$next = array_shift($forward_path);
				$path .= "/" . $next;
				$rel_path .= "/" . $next;

				if (!file_exists($path)) {
					if (mkdir($path, 0755) && $this->get_cached($parent_id, self::$PERMISSION_WRITE)) {
						$parent_id = $this->add($path, $rel_path, $parent_id, $file['ownerid']);
					}
					else {
						header('HTTP/1.1 500 An error occurred');
						return "An error occurred";
					}
				}
				else {
					$parent_id = $this->db->cache_id_for_path($file['ownerid'], $rel_path);
					$access_required = (sizeof($forward_path) == 0) ? self::$PERMISSION_WRITE : self::$PERMISSION_READ;
					if (!$this->get_cached($parent_id, $access_required)) {
						header('HTTP/1.1 403 Access denied');
						return "Access denied";
					}
				}
			}

			if (move_uploaded_file($_FILES[0]['tmp_name'], $path . "/" . $_FILES[0]['name'])) {
				$parent_id = $this->add($path . "/" . $_FILES[0]['name'], $rel_path . $_FILES[0]['name'], $parent_id, $file['ownerid']);
				return null;
			}
			else {
				header('HTTP/1.1 500 Unknown error while uploading');
				return "Unknown error while uploading";
			}
		}

		header('HTTP/1.1 500 No files to upload');
		return "No files to upload";
	}

	public function get_public($hash, $key) {
		$key = ($key != "") ? hash('sha256', $key . $this->config['salt']) : "";
		$share = $this->db->share_get_by_hash($hash);

		// File not shared at all
		if (!$share) {
			header('HTTP/1.1 500 File not found');
			return "File not found";
		}

		$file = $this->get_cached($share['id'], self::$PERMISSION_READ, $hash);

		// File not shared with accessing user
		if (!$file) {
			header('HTTP/1.1 500 File not found');
			return "File not found";
		}


		// Incorrect password
		else if ($share['pass'] != $key && !$this->db->share_is_unlocked($hash, $this->token)) {
			header('HTTP/1.1 403 Wrong password');
			return "Wrong password";
		}
		else {
			$token = $this->core->generate_token(0, $hash);

			if ($token) {
				$return = array('share' => array('id' => $file['id'], 'filename' => $file['filename'], 'type' => $file['type']), 'token' => $token);
				return $return;
			}
		}

		header('HTTP/1.1 500 An error occurred');
		return "An error occurred";
	}

	/**
	 * Returns file to client (in 200kB chunks, so images can build up progressively)
	 * @param array $source file to return
	 * @param array $width screen width for shrinking to save bandwidth
	 * @param string $height screen heightfor shrinking to save bandwidth
	 * @returns file
	 */

	public function get($targets, $width = null, $height = null) {
		$path = null;
		$delete_flag = false;

		foreach ($targets as $target) {
			$file = $this->get_cached($target, self::$PERMISSION_READ);

			if (!$file) {
				header('HTTP/1.1 403 Error accessing file');
				return "Error accessing file";
			}
		}

		$path = $this->config['datadir'] . $file['owner'] . $file['path'];

		if (count($targets) > 1 || is_dir($path)) {
			$delete_flag = true;
			$destination = $this->zip(null, $targets, true);
		}
		else if ($width && $height) {
			$destination = $this->shrink_image($file, $width, $height);
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

		header('HTTP/1.1 500 Error downloading');
		header('Location: ../../files');
		return null;
	}

	public function get_id3($target) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			header('HTTP/1.1 403 Access denied');
			return "Access denied";
		}

		return $file['filename'];
	}

	public function save_odf($target, $data) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		if (file_put_contents($this->config['datadir'] . $file['owner'] . $file['path'], base64_decode($data))) {
			return null;
		}

		header('HTTP/1.1 500 Error saving file');
		return "Error saving file";
	}

	public function load_text($target) {
		$file = $this->get_cached($target, self::$PERMISSION_READ);

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		if (is_readable($this->config['datadir'] . $file['owner'] . $file['path'])) {
			return array('filename' => $file['filename'], 'content' => file_get_contents($this->config['datadir'] . $file['owner'] . $file['path']));
		}

		header('HTTP/1.1 403 Error accessing file');
		return "Error accessing file";
	}

	public function save_text($target, $msg) {
		$file = $this->get_cached($target, self::$PERMISSION_WRITE);

		if (!$file) {
			header('HTTP/1.1 403 Error accessing file');
			return "Error accessing file";
		}

		$path = $this->config['datadir'] . $file['owner'] . $file['path'];
		if (file_put_contents($path, $msg)) {
			$this->db->cache_update($target, $this->type($path), $this->info($path), filemtime($path), md5_file($path), $file['owner'], $file['path']);
			return null;
		}

		header('HTTP/1.1 500 Error saving file');
		return "Error saving file";
	}

	public function get_cached($id, $access, $hash = "") {
		if (!$access) {
			return;
		}

		if ($id == self::$ROOT_ID && $this->username != "") {
			return array('ownerid' => $this->uid, 'owner' => $this->username, 'path' => "", 'type' => 'folder');
		}

		if (!$this->uid && $hash == "") {
			$hash = $this->db->get_hash_from_token($this->token);
		}

		return $this->db->cache_get($id, $this->uid, $access, $hash);
	}

	public function scan($id, $update = false, $include_childs = false) {
		set_time_limit(0);

		$file = $this->get_cached($id, self::$PERMISSION_READ);

		if (!$file || !$file['owner'] || file_exists($this->scan_lock . $file['owner'])) {
			return;
		}

		// Set lock
		if (!file_exists(dirname(__FILE__) . '/../config/locks')) {
			mkdir(dirname(__FILE__) . '/../config/locks');
		}
		file_put_contents($this->scan_lock . $file['owner'], '', LOCK_EX);

		$path = $this->config['datadir'] . $file['owner'] . $file['path'] . "/";
		$trash_path = $this->config['datadir'] . $file['owner'] . self::$TRASH;

		// Start scan
		$this->scan_trash($file['ownerid'], $file['owner'], $trash_path);
		//$this->scan($id, $path, $file['path'] . "/", $file['ownerid']);

		$start = time();

		if (is_dir($path)) {
			$this->scan_folder($path, $file['path'] . "/", $id, $file['ownerid'], $update, $include_childs);
		}

		$this->db->cache_clean($id, $file['ownerid'], $start, $update, $include_childs);

		// Release lock when finished
		if (file_exists($this->scan_lock . $file['owner'])) {
			unlink($this->scan_lock . $file['owner']);
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

	public function add($path, $rel_path, $id, $owner, $include_childs = false) {
		$md5 = (is_dir($path)) ? "0" : md5_file($path);
		$child_id = $this->db->cache_add(basename($path), $id, self::type($path), self::info($path), $owner, filemtime($path), $md5, $rel_path);

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
				array_push($existing, substr($file, -32));
			}
		}

		$this->db->cache_clean_trash($oid, $existing);
	}
}
