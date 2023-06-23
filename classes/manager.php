<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_purgeusers;

/**
 * Tool purgeusers manager class.
 *
 * @package     tool_purgeusers
 * @category    admin
 * @copyright   2023 Victor Deniz <victor@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    const SUPENSIONNOTIFIED = 0;
    const SUSPENDED = 1;
    const DELETED = 2;

    /** @var boolean $backup Keep a backup of the deleted records. */
    private bool $backup;

    /** @var boolean $logging Logs the user status. */
    private bool $logging;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        // TODO: Add settings to enable/disable the backup and the logging.
        $this->backup = true;
        $this->logging = true;
    }

        /**
     * Purge a list of users.
     *
     * Remove the user records from the database.
     *
     * @param array $userids The user ids.
     * @return void
     */
    public function purge_users(array $userids) {
        foreach ($userids as $userid) {
            $this->purge_user($userid);
        }
    }

    /**
     * Purge a user.
     *
     * Remove the user record from the database.
     *
     * @param integer $userid The user id.
     * @return void
     */
    private function purge_user(int $userid) {
        global $DB;

        if ($this->backup) {
            // Before deleting the user, backup the user data.
            $this->backup_record('user', $userid, $userid);
        }

        if ($this->logging) {
            // Log the user deletion.
            $this->log($userid, self::DELETED);
        }

        $DB->delete_records('user', ['id' => $userid]);
    }

    /**
     * Save a backup of a record.
     *
     * The record is serialized before saving it to the database.
     *
     * @param string $table The table name.
     * @param integer $id The record id.
     * @param integer $userid The user id the record belongs to.
     * @return void
     */
    private function backup_record(string $table, int $id, int $userid) {
        global $DB;

        $record = $DB->get_record($table, ['id' => $id], '*', MUST_EXIST);
        // Before deleting the user, we need to create a backup of the record.
        $backuprecord = new \stdClass();
        $backuprecord->table = $table;
        $backuprecord->userid = $userid;
        $backuprecord->tableid = $id;
        $backuprecord->timestamp = time();
        $backuprecord->record = json_encode($record);
        $DB->insert_record('tool_purgeusers_backup', $backuprecord);
    }

    /**
     * Log the user status.
     *
     * If the log already exists, the status and time will be updated. Otherwise, a new log will be created.
     * The status can be one of the following:
     * - SUPENSIONNOTIFIED: The user has been notified about the suspension.
     * - SUSPENDED: The user has been suspended.
     * - DELETED: The user has been deleted.
     *
     * @param integer $userid The user id.
     * @param integer $status The user status.
     * @return void
     */
    private function log(int $userid, int $status) {
        global $DB;

        if ($log = $DB->get_record('tool_purgeusers_log', ['userid' => $userid])) {
            $log->status = $status;
            $log->timestamp = time();
            $DB->update_record('tool_purgeusers_log', $log);
        } else {
            $log = new \stdClass();
            $log->userid = $userid;
            $log->status = $status;
            $log->timestamp = time();
            $DB->insert_record('tool_purgeusers_log', $log);
        }
    }
}
