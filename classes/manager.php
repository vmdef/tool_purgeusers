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
class manager
{

    // Table to check records for activity.
    const TABLECHECK = '1';

    // Table to backup records.
    const TABLEBACKUP = '2';

    /**
     * Moodle components to check for activity. The format is:
     * Component is the name of the plugin type or subsytem to check {@see lib/components.json}.
     * Type is the name of the plugin or "subsystem" for core subsystems.
     * Table is the name of the table to check for activity.
     * Alias is the alias to use for the table.
     * Field is the name of the field to check for the user id.
     * Action is the action to perform on the table. TABLECHECK or TABLEBACKUP.
     * 'component' => [
     *     'type' => [
     *         [
     *             'table' => 'table_name',
     *             'alias' => 'table_alias',
     *             'field' => 'table_field',
     *             'action' => TABLECHECK | TABLEBACKUP,
     *         ],
     *     ],
     * ],
     */
    const COMPONENTS = [
        'mod' => [
            'assign' => [
                [
                    'table' => 'assign_submission',
                    'alias' => 'asu',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'assign_grades',
                    'alias' => 'ag',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
            'chat' => [
                [
                    'table' => 'chat_messages',
                    'alias' => 'cm',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
            'choice' => [
                [
                    'table' => 'choice_answers',
                    'alias' => 'ca',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
            'forum' => [
                [
                    'table' => 'forum_posts',
                    'alias' => 'fp',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
        'badges' => [
            'subsystem' => [
                [
                    'table' => 'badge_issued',
                    'alias' => 'bi',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'badge_manual_award',
                    'alias' => 'bma',
                    'field' => 'recipientid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'badge_criteria_met',
                    'alias' => 'bcm',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
        'comment' => [
            'subsystem' => [
                [
                    'table' => 'comments',
                    'alias' => 'c',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
        'contentbank' => [
            'subsystem' => [
                [
                    'table' => 'contentbank_content',
                    'alias' => 'cbc',
                    'field' => 'usercreated',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'contentbank_content',
                    'alias' => 'cbc2',
                    'field' => 'usermodified',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
    ];

    const SUPENSIONNOTIFIED = 0;
    const SUSPENDED = 1;
    const DELETED = 2;

    // We need to limit the number of users to be deleted per execution to avoid timeouts or memory issues.
    const MAX_USERS_PER_QUERY = 10;

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
        // TODO: Check the COMPONENTS array to make sure the tables exists and there are no duplicated aliases.
    }

    /**
     * Get the users to purge.
     *
     * @return array The ids of the users to purge.
     */
    public function get_users_to_purge(): array {
        global $DB;

        // SQL joins.
        $sqljoins = '';

        // SQL where clause.
        $sqlwhere = '';

        // Look for deleted users.
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE deleted = 1";
        $rs = $DB->get_recordset_sql($sql, null, 0, self::MAX_USERS_PER_QUERY);

        if (empty($rs)) {
            // If there are no deleted users, we can stop here.
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys(iterator_to_array($rs, true)));

        foreach (self::COMPONENTS as $component) {
            foreach ($component as $type) {
                $sql = "SELECT u.id
                          FROM {user} u";
                foreach ($type as $table) {
                    $sqljoins .= "
                            LEFT JOIN {{$table['table']}} {$table['alias']}
                                ON {$table['alias']}.{$table['field']} = u.id";
                    $sqlwhere .= " AND {$table['alias']}.{$table['field']} IS NULL";
                }
                // Update the number of users to purge.
                [$insql, $inparams] = $DB->get_in_or_equal($inparams);
                $sql .= $sqljoins . "
                        WHERE u.id $insql" . $sqlwhere;
                // Update $inparams with the users than don't have records in the tables of the current component.
                $inparams = $DB->get_fieldset_sql($sql, $inparams);
                if (empty($inparams)) {
                    // If there are no users to purge, we can stop checking the rest of the components.
                    break;
                }
                $sqljoins = '';
                $sqlwhere = '';
            }
        }
        // At this point $inparams contains the list of users that don't have activity.
        return $inparams;
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
