<?
/**
* CalendarSynchronizer.class.php
* 
*
* @author		Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @version	$Id: CalendarSynchronizer.class.php,v 1.2 2008/12/23 09:50:34 thienel Exp $
* @access		public
* @modulegroup	calendar_modules
* @module		calendar_import
* @package	Calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// CalendarSynchronizer.class.php
// 
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once $RELATIVE_PATH_CALENDAR . '/lib/ErrorHandler.class.php';
require_once $RELATIVE_PATH_CALENDAR . "/lib/driver/$CALENDAR_DRIVER/CalendarDriver.class.php";

class CalendarSynchronizer {
	
	var $_import;
	var $_export;
	var $count = 0;
	var $max_events;
	var $last_sync = 0;
	// ids of imported events deleted in Stud.IP
	var $deleted;
	
	function CalendarSynchronizer (&$import, &$export) {
		global $CALENDAR_MAX_EVENTS;
		
		// initialize error handling
		init_error_handler('_calendar_error');
		global $_calendar_error;
		
		$this->_import =& $import;
		$this->_export =& $export;
		$this->deleted = array();
		$this->setMaxEvents();
	}
	
	function setMaxEvents ($max_events = 0) {
		if ($max_events == 0)
			$this->max_events = $CALENDAR_MAX_EVENTS;
		else
			$this->max_events = $max_events;
	}
	
	function synchronize ($range_id, $compareLastSync = FALSE, $compare_fields = NULL) {
		global $_calendar_error;
		
		if (!$this->_import->importIntoObjects()) {
			return FALSE;
		}
		
		$this->last_sync = CalendarSynchronizer::GetLastSync(trim($this->_import->getClientIdentifier()));
		if (FALSE === $this->last_sync) {
			CalendarSynchronizer::NewSyncClient(trim($this->_import->getClientIdentifier()));
			$this->last_sync = 0;
		}
		$this->_export->setClientIdentifier(trim($this->_import->getClientIdentifier()));
		
		// dont't synchronize with empty import files, except for the first time
		// (would delete all events in Stud.IP)
		if ($this->last_sync > 0 && $this->_import->getCount() == 0) {
			$_calendar_error->throwError(ERROR_WARNING,
					_("Der Stud.IP-Terminkalender kann nicht mit einem Import synchronisiert werden, der keine Termindaten enth&auml;lt!"));
			return FALSE;
		}
		
		// export to extern CUA
		$ext = array();
		// events to replace in Stud.IP
		$int = array();
		// events (only ids) to delete from db
		$del = array();
		
		$events = $this->_import->getObjects();
		$this->count = sizeof($events);
	
		// get events from database
		$db =& CalendarDriver::getInstance($range_id);
		$db->openDatabase('EVENTS', 'ALL_EVENTS', 0, CALENDAR_END, NULL, Calendar::getBindSeminare());
		$in_changed = TRUE;
		$sentinel = '#';
		$create_export = FALSE;
		array_unshift($events, $sentinel);
		
		// synchronize!
		while ($in = $db->nextObject()) {
			while ($ex = array_pop($events)) {
			
				// end of queue, return to start
				if ($ex == $sentinel) {
					if ($in_changed) {
						if ($in->properties['LAST-MODIFIED'] >= $this->last_sync
								|| $in->getImportDate() > $this->last_sync) {
							$ext[] = $in;
							$create_export = TRUE;
						} elseif (strtolower(get_class($in)) != 'seminarevent') {
							$del[] = $in->getId();
						} else {
							// initial export of seminar events
							$ext[] = $in;
							$create_export = TRUE;
						}
					}
					$in_changed = TRUE;
					array_unshift($events, $sentinel);
					continue 2;
				}
				
				// no LAST-MODIFIED...
				if (!$ex->properties['LAST-MODIFIED']) {
					$_calendar_error->throwError(ERROR_CRITICAL,
							_("Die Datei kann nicht mit dem Stud.IP-Terminkalender synchronisiert werden."));
					return FALSE;
				}
								
				
				if ($ex->properties['UID'] == $in->properties['UID']) {
					if ($ex->properties['LAST-MODIFIED'] < $in->properties['LAST-MODIFIED']) {
						$ext[] = $in;
						$create_export = TRUE;
					} elseif ($compareLastSync) {
						if ($ex->properties['LAST-MODIFIED'] > $this->last_sync) {
							// dont't try to change seminar events in Stud.IP
							if (strtolower(get_class($in)) == 'seminarevent') {
								$ext[] = $in;
								$create_export = TRUE;
								continue;
							}
							$ex->id = $in->id;
							$ex->setImportDate($in->getImportDate());
							$int[] = $ex;
							$ext[] = $ex;
						} else {
							$ext[] = $ex;
						}
						$in_changed = FALSE;
					} elseif ($ex->properties['LAST-MODIFIED'] > $in->properties['LAST-MODIFIED']) {
						// dont't try to change seminar events in Stud.IP
						if (strtolower(get_class($in)) == 'seminarevent') {
							$ext[] = $in;
							$create_export = TRUE;
							continue;
						}
						$ex->id = $in->id;
						$ex->setImportDate($in->getImportDate());
						$int[] = $ex;
						$ext[] = $ex;
					} else {
						$ext[] = $ex;
					}
					$in_changed = FALSE;
				}
				// unsafe, if we have no UID...
				elseif ($ex->properties['CREATED'] == $in->properties['CREATED']) {
					if (trim($ex->properties['SUMMARY']) == trim($in->properties['SUMMARY'])) {
						if ($ex->properties['LAST-MODIFIED'] < $in->properties['LAST-MODIFIED']) {
							$ext[] = $in;
							$create_export = TRUE;
						} elseif ($ex->properties['LAST-MODIFIED'] > $in->properties['LAST-MODIFIED']) {
							$ex->id = $in->id;
							$ex->setImportDate($in->getImportDate());
							$int[] = $ex;
							$ext[] = $ex;
						} else {
							$ext[] = $ex;
						}
						$in_changed = FALSE;
					}
				}
				else {
					array_unshift($events, $ex);
				}
			}
		}
		
		$this->count += $db->getCount();
		
		// delete sentinel
		array_shift($events);
		// every event left over in $events is not in Stud.IP, so import the rest
		// dont't import seminar events
		foreach ($events as $event) {
			if (FALSE === strpos($event->properties['UID'], 'Stud.IP-SEM-')) {
				if ($event->properties['LAST-MODIFIED'] >= $this->last_sync) {
					$event->setImportDate();
					$int[] = $event;
					$ext[] = $event;
			//		$create_export = TRUE;
				} else {
					$this->deleted[] = $event->properties['UID'];
					$create_export = TRUE;
				}
			} else {
				$ext[] = $event;
				$create_export = TRUE;
			}
		}
		
		if (sizeof($int) > $this->max_events) {
			$_calendar_error->throwError(ERROR_CRITICAL,
					_("Die zu synchronisierende Datei enth&auml;lt zu viele Termine."));
			return FALSE;
		}
		
		// OK, work is done, import and export the events
		if (sizeof($del)) {
			$db->deleteFromDatabase('SINGLE', $del);
			$_calendar_error->throwError(ERROR_MESSAGE,
					sprintf(_("Es wurde(n) %s Termin(e) in Ihrem Stud.IP-Terminkalender gel&ouml;scht."),
					sizeof($del)));
		}
		$db->writeObjectsIntoDatabase($int, 'REPLACE');
	//	if (!$create_export) {
	//		$ext = array();
	//	}
		$this->_export->exportFromObjects($ext);
		if (!$compareLastSync) {
			CalendarSynchronizer::SetLastSync($this->_import->getClientIdentifier());
		}
		return TRUE;
	}
	
	function getCount () {
	
		return $this->count;
	}
	
	function getDeleted () {
		return $this->deleted;
	}
	
	function GetSyncClientIdentifiers ($user_id = NULL) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$client_names = array();
		$db =& new DB_Seminar();
		$query = "SELECT client_identifier FROM calendar_sync WHERE range_id = '$user_id'";
		$db->query($query);
		while ($db->next_record()) {
			$client_names[] = stripslashes($db->f('client_identifier'));
		}
		return $client_names;
	}
	
	function GetLastSync ($client_identifier, $user_id = NULL) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$db =& new DB_Seminar();
		$query = "SELECT last_sync FROM calendar_sync WHERE range_id = '$user_id'"
				. " AND client_identifier = '"
				. addslashes($client_identifier) . "'";
		$db->query($query);
		if ($db->next_record()) {
			return $db->f('last_sync');
		}
		return FALSE;
	}
	
	function GetAllLastSync ($user_id = NULL) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$db =& new DB_Seminar();
		$query = "SELECT last_sync FROM calendar_sync WHERE range_id = '$user_id' "
				. "ORDER BY last_sync DESC";
		$db->query($query);
		if ($db->next_record()) {
			return $db->f('last_sync');
		}
		
		return FALSE;
	}
	
	function SetLastSync ($client_identifier, $user_id = NULL) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$db =& new DB_Seminar();
		$query = "UPDATE calendar_sync SET last_sync = " . time()
				. " WHERE range_id = '$user_id' AND client_identifier = '"
				. addslashes($client_identifier) . "'";
		$db->query($query);
		if ($db->affected_rows()) {
			return TRUE;
		}
		return FALSE;
	}
	
	function NewSyncClient ($client_identifier, $user_id = NULL) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$db =& new DB_Seminar();
		$query = "INSERT INTO calendar_sync VALUES ('$user_id', '"
				. addslashes($client_identifier) . "', 0)";
		$db->query($query);
		if ($db->affected_rows()) {
			return TRUE;
		}
		return FALSE;
	}
	
	
	function DeleteSyncClients ($client_identifiers, $user_id = NULL, $all = FALSE) {
		if (is_null($user_id)) {
			$user_id = $GLOBALS['user']->id;
		}
		
		$db =& new DB_Seminar();
		$query = "DELETE from calendar_sync WHERE range_id = '$user_id'";
		if (!$all) {
			$clients = array();
			$query .= " AND client_identifier IN ('";
			foreach ($client_identifiers as $client_identifier) {
				$clients[] = addslashes($client_identifier);
			}
			$query .= implode("','", $client_identifiers) . "')";
		}
		$db->query($query);
		return $db->affected_rows();
	}
	
	function DeleteAllSyncClients ($user_id) {
		CalendarSynchronizer::DeleteSyncClients (array(), $user_id, TRUE);
	}
	
}

?>
