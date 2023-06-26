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

// TODO: Check if the user has the required permissions to run this script.
$manager = new manager();
$users = $manager->get_users_to_purge();
$manager->purge_users($users);
