<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2016, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

class Sync {
	/**
	 * Sync-Process
	 *
	 * Client sends a list of all its files
	 * Server gets a list of all its files
	 *
	 * Handle files that exist on both client and server
	 * 		If they've got matching checksums, remove them from both lists (no need to further process them)
	 * 		Otherwise compare edit-dates, keep the most recent and delete or mark-for-delete the other one
	 *
	 * For the remaining client-files check if there is an entry in server-history
	 * 		If there is, it is for deletion and it is more recent than the file's last edit-date, mark the client-file for deletion
	 * 		Otherwise mark it for upload (but do not upload folders from client!)
	 *
	 * For the remaining server-files check the last history-entry
	 * 		If it is not for deletion, add it to client-list for download
	 * 		Otherwise delete from server
	 */

	public function __construct($file) {
		$this->file		= $file;
	}

	public function start($target, $clientfiles, $serverfiles, $history, $last_sync) {
		$start = microtime(true);

		file_put_contents(LOG, "clientfiles: " . print_r($clientfiles, true) . "\n", FILE_APPEND);
		file_put_contents(LOG, "serverfiles: " . print_r($serverfiles, true) . "\n", FILE_APPEND);
		return;

		// Eliminate matches
		$files = $this->eliminate_matches($clientfiles, $serverfiles);
		$clientfiles = $files[0];
		$serverfiles = $files[1];
		file_put_contents(LOG, "matches eliminated (client): " . print_r($clientfiles, true) . "\n", FILE_APPEND);
		file_put_contents(LOG, "matches eliminated (server): " . print_r($serverfiles, true) . "\n", FILE_APPEND);

		// Eliminate deleted
		$clientfiles = $this->process_clientfiles($clientfiles, $history);
		file_put_contents(LOG, "deleted eliminated (client): " . print_r($clientfiles, true) . "\n", FILE_APPEND);
		file_put_contents(LOG, "deleted eliminated (server): " . print_r($serverfiles, true) . "\n", FILE_APPEND);

		// Mark the remaining serverfiles for download
		$clientfiles = $this->process_serverfiles($clientfiles, $serverfiles, $history);
		file_put_contents(LOG, "final: " . print_r($clientfiles, true) . "\n", FILE_APPEND);

		return $clientfiles;
	}

	private function eliminate_matches($clientfiles, $serverfiles) {
		$size = sizeof($clientfiles);
		for ($i = 0; $i < $size; $i++) {
			$match = Util::search_in_array_2D($serverfiles, 'path', $clientfiles[$i]['path']);
			if ($match !== null) {
				if ($clientfiles[$i]['md5'] == $serverfiles[$match]['md5']) {
					unset($clientfiles[$i]);
					//$clientfiles = array_values($clientfiles);
				}
				else {
					if ($clientfiles[$i]['edit'] < $serverfiles[$match]['edit']) {
						$clientfiles[$i]['action'] = 'download';
						$clientfiles[$i]['id'] = $serverfiles[$match]['id'];
					}
					else {
						$this->file->delete(array($serverfiles[$i]['id']));
					}
				}
				unset($serverfiles[$match]);
				$serverfiles = array_values($serverfiles);
			}
		}

		$clientfiles = array_values($clientfiles);
		$serverfiles = array_values($serverfiles);
		return array($clientfiles, $serverfiles);
	}

	private function process_clientfiles($clientfiles, $history) {
		for ($i = 0; $i < sizeof($clientfiles); $i++) {
			$match = Util::search_in_array_2D($history, 'path', $clientfiles[$i]['path']);
			if ($match !== null && $history[$match]['deleted']) {
				if ($clientfiles[$i]['edit'] < $history[$match]['timestamp']) {
					$clientfiles[$i]['action'] = 'delete';
					$clientfiles = $this->mark_folder_for_delete($clientfiles, $clientfiles[$i]['path']);
					continue;
				}
			}
			else if (!array_key_exists('action', $clientfiles[$i])) {
				$clientfiles[$i]['action'] = 'upload';
			}
		}

		return $clientfiles;
	}

	private function process_serverfiles($clientfiles, $serverfiles, $history) {
		for ($i = 0; $i < sizeof($serverfiles); $i++) {
			$match = Util::search_in_array_2D($history, 'path', $serverfiles[$i]['path']);
			if ($match !== null && !$history[$match]['deleted']) {
				if ($serverfiles[$i]['type'] != "folder") {
					$serverfiles[$i]['action'] = 'download';
					array_push($clientfiles, $serverfiles[$i]);
					continue;
				}
			}
			else {
				$this->file->delete(array($serverfiles[$i]['id']));
			}
		}

		return $clientfiles;
	}

	private function mark_folder_for_delete($clientfiles, $path) {
		for ($i = 0; $i < sizeof($clientfiles); $i++) {
			if (strpos($clientfiles[$i]['path'], $path) === 0) {
				$clientfiles[$i]['action'] = 'delete';
			}
		}

		return $clientfiles;
	}
}
