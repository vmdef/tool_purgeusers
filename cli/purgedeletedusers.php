<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * CLI script to purge deleted users from the database.
 *
 * Before running this script, you should make sure that you have a backup of your database.
 * This script will move records from users that have been previously deleted and don't have any content on the site to
 * a backup table.
 *
 *
 * @package     tool_purgeusers
 * @subpackage  cli
 * @copyright   2023 Victor Deniz <victor@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use tool_purgeusers\manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Table to check records for activity.
const TABLECHECK = '1';

// Table to backup records.
const TABLEBACKUP = '2';

// Moodle components to check for activity. The format is:
// Component is the name of the plugin type or subsytem to check {@see lib/components.json}.
// Type is the name of the plugin or "subsystem" for core subsystems.
// Table is the name of the table to check for activity.
// Alias is the alias to use for the table.
// Field is the name of the field to check for the user id.
// Action is the action to perform on the table. TABLECHECK or TABLEBACKUP.
// 'component' => [
//     'type' => [
//         [
//             'table' => 'table_name',
//             'alias' => 'table_alias',
//             'field' => 'table_field',
//             'action' => TABLECHECK | TABLEBACKUP,
//         ],
//     ],
// ],
const COMPONENTS = [
    'mod' => [
        'assign' => [
            [
                'table' => 'assign_submission',
                'alias' => 'asu',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
            [
                'table' => 'assign_grades',
                'alias' => 'ag',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
        ],
        'chat' => [
            [
                'table' => 'chat_messages',
                'alias' => 'cm',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
        ],
        'choice' => [
            [
                'table' => 'choice_answers',
                'alias' => 'ca',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
        ],
    ],
    'badges' => [
        'subsystem' => [
            [
                'table' => 'badge_issued',
                'alias' => 'bi',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
            [
                'table' => 'badge_manual_award',
                'alias' => 'bma',
                'field' => 'recipientid',
                'action' => TABLECHECK,
            ],
            [
                'table' => 'badge_criteria_met',
                'alias' => 'bcm',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
        ],
    ],
    'comment' => [
        'subsystem' => [
            [
                'table' => 'comments',
                'alias' => 'c',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
        ],
    ],
    'contentbank' => [
        'subsystem' => [
            [
                'table' => 'contentbank_content',
                'alias' => 'cbc',
                'field' => 'userid',
                'action' => TABLECHECK,
            ],
            [
                'table' => 'contentbank_content',
                'alias' => 'cbc2',
                'field' => 'usermodified',
                'action' => TABLECHECK,
            ],
        ],
    ],
];

// We need to limit the number of users to be deleted per execution to avoid timeouts or memory issues.
const MAX_USERS_PER_QUERY = 10;

/** @var string $sqljoins SQL joins */
$sqljoins = '';

/** @var string $sqlwhere SQL where */
$sqlwhere = '';

// Get the cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false
    ],
    [
        'h' => 'help'
    ]
);

$help =
    "
Help message for tool_purgeusers cli script.

Please include a list of options and associated actions.

Please include an example of usage.
";

if ($unrecognized) {
    $unrecognized = implode("\n\t", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    die();
}

$manager = new manager();

// TODO: Check if the user has the required permissions to run this script.
// TODO: Check the COMPONENTS array to make sure the tables exists and there are no duplicated aliases.
// Look for deleted users.
$sql = "SELECT u.id
          FROM {user} u
         WHERE deleted = 1";
$rs = $DB->get_recordset_sql($sql, null, 0, MAX_USERS_PER_QUERY);

[$insql, $inparams] = $DB->get_in_or_equal(array_keys(iterator_to_array($rs, true)));
foreach (COMPONENTS as $component) {
    foreach ($component as $type) {
        $sql = "SELECT u.id
                  FROM {user} u";
        foreach ($type as $table) {
            $sqljoins .= "
                      LEFT JOIN {{$table['table']}} {$table['alias']}
                        ON {$table['alias']}.{$table['field']} = u.id";
            $sqlwhere .= " AND {$table['alias']}.{$table['field']} IS NULL";
        }
        $sql .= $sqljoins . "
                WHERE u.id $insql" . $sqlwhere;
        // Update $inparams with the users than don't have records in the tables of the current component.
        $inparams = $DB->get_fieldset_sql($sql, $inparams);
    }
    $sqljoins = '';
    $sqlwhere = '';
    //print_object($inparams);
    //print_object($DB->get_fieldset_sql($sql, $inparams));
}

// At this point $inparams contains the list of users that don't have activity.
foreach ($inparams as $id) {
    // Remove the user record.
    $manager->purge_user($id);
}
