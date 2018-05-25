<?php

/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2018, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		https://simpledrive.org
 */

/*
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
 * 		If none exists or it is not for deletion, add it to client-list for download
 * 		Otherwise delete from server
 *
 * Client must set $last_sync after all files have been synched, not on sync-start!
 * Otherwise the following problem might occurr:
 * 		Initial sync at 0
 * 		A creates a file at 50
 * 		Next sync at 100
 * 		A sets $last_sync = 100
 * 		B does not have the file, so B is supposed to get it
 * 		Sync gets aborted before download
 * 		Next sync at 200:
 * 		B does not have the file and las edit was before last sync, so the algorithm
 * 		assumes that B deleted it after sync and A hasn't updated it since,
 * 		so the file gets deleted
 *
 * Expected behaviour:
 * 		When the 200-sync starts, $last_sync is still 0, so the file's edit
 * 		at 50 is more recent than last sync so it's scheduled for download again
 */
class Sync {
	/**
	 * Constructor
	 *
	 * @param File_Model $file To access the delete-function
	 */
	public function __construct($file) {
		$this->file = $file;
	}

	/**
	 * Start sync-process
	 *
	 * @param array $clientfiles
	 * @param array $serverfiles
	 * @param array $history
	 * @param int $last_sync
	 * @return array
	 */
	public function start($clientfiles, $serverfiles, $history, $last_sync) {
		// Eliminate matches
		$files = $this->eliminate_matches($clientfiles, $serverfiles);
		$clientfiles = $files[0];
		$serverfiles = $files[1];

		// Eliminate deleted
		$clientfiles = $this->process_clientfiles($clientfiles, $history);

		// Mark the remaining serverfiles for download
		$clientfiles = $this->process_serverfiles($clientfiles, $serverfiles, $history);

		return $clientfiles;
	}

	/**
	 * Remove exact matches (same md5) from both arrays
	 *
	 * @param array $clientfiles
	 * @param array $serverfiles
	 * @return array Containing both client- and server-files separately
	 */
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

	/**
	 * Mark client-entries
	 * Delete if there is a newer version on the server
	 * Upload if there is an older or no version on the server
	 * and the server hasn't deleted the file after last client-edit
	 *
	 * @param array $clientfiles
	 * @param array $history
	 * @return array
	 */
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

	/**
	 * Process the remaining serverfiles that don't have an exact match on the client
	 * Add serverfile to client-array for download
	 * if no history entry exists or it is not for deletion
	 *
	 * @param array $clientfiles
	 * @param array $serverfiles
	 * @param array $history
	 * @return array
	 */
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

	/**
	 * Mark client-folder and all its children for deletion
	 *
	 * @param array $clientfiles
	 * @param string $path
	 * @return array
	 */
	private function mark_folder_for_delete($clientfiles, $path) {
		for ($i = 0; $i < sizeof($clientfiles); $i++) {
			if (strpos($clientfiles[$i]['path'], $path) === 0) {
				$clientfiles[$i]['action'] = 'delete';
			}
		}

		return $clientfiles;
	}
}
