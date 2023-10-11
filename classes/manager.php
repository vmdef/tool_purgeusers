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
    /** @var int TABLECHECK Table to check records for activity. */
    private const TABLECHECK = 1;

    /** @var int TABLEBACKUP Table with records to backup. */
    private const TABLEBACKUP = 2;

    /** @var int NOPURGE The user has been processed and is not being purged. */
    private const NOPURGE = 0;

    /** @var int SUSPENSIONNOTIFIED The user has been notified about the suspension. */
    private const SUSPENSIONNOTIFIED = 1;

    /** @var int SUSPENDED The user has been suspended. */
    private const SUSPENDED = 2;

    /** @var int DELETED The user has been deleted. */
    private const DELETED = 3;

    /** @var int RESTORED The user has been restored. */
    private const RESTORED = 4;

    /** @var int MAX_USERS_PER_QUERY Limit the number of users to be deleted per execution to avoid timeouts or memory issues. */
    private const MAX_USERS_PER_QUERY = 1000;

    /**
     * Moodle components to check for activity. The format is:
     * Component is the name of the plugin type or subsystem to check (lib/components.json).
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
        'tool' => [
            'dataprivacy' => [
                [
                    'table' => 'tool_dataprivacy_request',
                    'alias' => 'tdd',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'tool_dataprivacy_request',
                    'alias' => 'tdd2',
                    'field' => 'requestedby',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
        'local' => [
            'dev' => [
                [
                    'table' => 'dev_activity',
                    'alias' => 'd',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'dev_git_commits',
                    'alias' => 'dgc',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'dev_git_user_aliases',
                    'alias' => 'dgua',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
            'plugins' => [
                [
                    'table' => 'local_plugins_contributor',
                    'alias' => 'lpc',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'local_plugins_log',
                    'alias' => 'lpl',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'local_plugins_review',
                    'alias' => 'lpr',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'local_plugins_set_plugin',
                    'alias' => 'lpsp',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'local_plugins_vers',
                    'alias' => 'lpv',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
        ],
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
                [
                    'table' => 'forum_discussions',
                    'alias' => 'fd',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
                [
                    'table' => 'forum_discussions',
                    'alias' => 'fd2',
                    'field' => 'usermodified',
                    'action' => self::TABLECHECK,
                ],
            ],
            'quiz' => [
                [
                    'table' => 'quiz_attempts',
                    'alias' => 'qa',
                    'field' => 'userid',
                    'action' => self::TABLECHECK,
                ],
            ],
            'survey' => [
                [
                    'table' => 'survey_answers',
                    'alias' => 'san',
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

    /** @var bool $backup Keep a backup of the deleted records. */
    private bool $backup;

    /** @var bool $logging Logs the user status. */
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
     * Check if a users have activity on the site.
     *
     * Check if the users have records in the tables of the components.
     * To avoid a huge SQL query, queries by component are performed.
     * @param int[] $userlist List of user ids to check for activity.
     * @return array
     */
    private function check_activity(array $userlist): array {
        global $DB;

        // SQL joins.
        $sqljoins = '';

        // SQL where clause.
        $sqlwhere = '';

        $inparams = $userlist;

        foreach (self::COMPONENTS as $type => $components) {
            foreach ($components as $name => $tables) {
                if ($name !== 'subsystem') {
                    if (!\core_plugin_manager::instance()->get_plugin_info($type . '_' . $name)) {
                        // If the plugin is not installed, we can skip it.
                        continue;
                    }
                }
                $sql = "SELECT u.id
                          FROM {user} u";
                foreach ($tables as $table) {
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

        return $inparams;
    }

    /**
     * Get the users to purge.
     *
     * The users to purge are the users that have been deleted and don't have activity on the site.
     * The users that have been deleted and have activity on the site will be logged and won't be purged.
     *
     * @return int[] The ids of the users to purge.
     */
    public function get_users_to_purge(): array {
        global $DB;

        // Look for deleted users.
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {tool_purgeusers_log} l ON l.userid = u.id
                       AND l.status = ?
                 WHERE u.deleted = 1
                       AND l.id IS NULL";
        $rs = $DB->get_recordset_sql($sql, [self::NOPURGE], 0, self::MAX_USERS_PER_QUERY);

        if (!$rs->valid()) {
            // If there are no deleted users, we can stop here.
            return [];
        }
        $initialusers = array_keys(iterator_to_array($rs, true));
        $rs->close();

        // Check if the users have activity on the site.
        $usersnocontent = $this->check_activity($initialusers);

        $userswithcontent = array_diff($initialusers, $usersnocontent);
        foreach ($userswithcontent as $userid) {
            // Log the users with content to not delete, so they won't be checked again.
            $this->log($userid, self::NOPURGE);
        }

        // At this point $inparams contains the list of users that don't have activity.
        return $usersnocontent;
    }

    /**
     * Purge a list of users.
     *
     * Remove the user records from the database.
     *
     * @param int[] $userids The user ids.
     * @return void
     */
    public function purge_users(array $userids) {
        foreach ($userids as $userid) {
            $this->purge_user($userid);
        }
    }

    /**
     * Restore a list of users.
     *
     * Restore the user records from the backup table to the user table.
     * If the logging is enabled, the user status will be logged.
     *
     * @param int[] $userids The user ids.
     * @return void
     */
    public function restore_users(array $userids) {

        foreach ($userids as $userid) {
            $this->restore_user($userid);
        }
    }

    /** Restore a user.
     *
     * Restore the user record from the backup table to the user table.
     * If the logging is enabled, the user status will be logged.
     *
     * @param int $userid The user id.
     * @return void
     */
    private function restore_user(int $userid) {
        global $DB;

            // Restore the user record from the backup table.
        if ($backup = $DB->get_record('tool_purgeusers_backup', ['tablename' => 'user', 'tableid' => $userid])) {
            $record = json_decode($backup->record);
            $DB->import_record('user', $record);

            if ($this->logging) {
                // Log the user restoration.
                $this->log($userid, self::RESTORED);
            }
        }
    }

    /**
     * Purge a user.
     *
     * Remove the user record from the database.
     * If the backup is enabled, a backup of the user data will be created.
     * If the logging is enabled, the user status will be logged.
     *
     * @param int $userid The user id.
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
     * @param int $id The record id.
     * @param int $userid The user id the record belongs to.
     * @return void
     */
    private function backup_record(string $table, int $id, int $userid) {
        global $DB;

        $record = $DB->get_record($table, ['id' => $id], '*', MUST_EXIST);
        $econdedrecord = json_encode($record);
        // Before deleting the user, we need to create or update a backup of the record.
        if ($backup = $DB->get_record('tool_purgeusers_backup', ['tablename' => $table, 'tableid' => $id, 'userid' => $userid])) {
            $backup->timestamp = time();
            $backup->record = $econdedrecord;
            $DB->update_record('tool_purgeusers_backup', $backup);
        } else {
            $backuprecord = new \stdClass();
            $backuprecord->tablename = $table;
            $backuprecord->userid = $userid;
            $backuprecord->tableid = $id;
            $backuprecord->timestamp = time();
            $backuprecord->record = $econdedrecord;
            $DB->insert_record('tool_purgeusers_backup', $backuprecord);
        }
    }

    /**
     * Log the user status.
     *
     * If the log already exists, the status and time will be updated. Otherwise, a new log will be created.
     * The status can be one of the following:
     * - SUPENSIONNOTIFIED: The user has been notified about the suspension.
     * - SUSPENDED: The user has been suspended.
     * - DELETED: The user has been deleted.
     * - RESTORED: The user has been restored.
     *
     * @param int $userid The user id.
     * @param int $status The user status.
     * @return void
     */
    private function log(int $userid, int $status) {
        global $DB;

        if ($log = $DB->get_record('tool_purgeusers_log', ['userid' => $userid, 'status' => $status])) {
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
