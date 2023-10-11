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
 * CLI script to restore purged users from the backup.
 *
 * Before running this script, you should make sure that you have a backup of your database.
 * This script will restore records from the backup table to the user table.
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

// Get the cli options.
[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'run' => false,
        'ids' => null,
    ],
    [
        'h' => 'help',
        'r' => 'run',
    ]
);

$help = "Purge deleted users from the database.

Options:
    -h --help                   Print this help.
    -r --run                    Restore the records from the backup table to the user table. If this option is not set, the script will run in dry mode.

Examples:

    # php restorepurgedusers.php  --ids=123,456,789 --run
        Restores the user records with the ids 123, 456 and 789 from the backup table to the user table.
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
$users = [];
if (!empty($options['ids'])) {
    $users = explode(',', $options['ids']);
}

if (empty($users)) {
    cli_writeln('There are no users to restore.');
    die();
}

if ($options['run']) {
    $manager->restore_users($users);
} else {
    cli_writeln('The following users will be restored:');
    foreach ($users as $user) {
        cli_writeln($user);
    }
}
