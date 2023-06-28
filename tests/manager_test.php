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
 * @category    test
 * @copyright   2023 Victor Deniz <victor@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \tool_purgeusers\manager
 */
class manager_test extends \advanced_testcase {

    /**
     * Test for the get_users_to_purge method.
     *
     * @covers ::get_users_to_purge
     * @dataProvider get_users_to_purge_provider
     * @param int $numusers Number of users to create.
     * @param int $numusersdeleted Number of users to delete.
     * @param int $numuserscontent Number of users with content.
     * @param int $expecteduserstopurge Expected number of users to purge.
     * @return void
     */
    public function test_get_users_to_purge(int $numusers, int $numusersdeleted, int $numuserscontent , int $expecteduserstopurge) {
        global $DB;

        $this->resetAfterTest();

        $manager = new manager();

        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        // Create users.
        $users = [];
        for ($i = 0; $i < $numusers; $i++) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $users[$i] = $user;
        }

        // Add content to users.
        for ($i = 0; $i < $numuserscontent; $i++) {
            $record = new \stdClass();
            $record->course = $course->id;
            $record->userid = $users[$i]->id;
            $record->forum = $forum->id;
            $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        }

        // Delete users.
        for ($i = 0; $i < $numusersdeleted; $i++) {
            delete_user($users[$i]);
        }

        $userstopurge = $manager->get_users_to_purge();

        $this->assertEquals($expecteduserstopurge, count($userstopurge));
    }

    /**
     * Data provider for test_get_users_to_purge.
     *
     * @return array
     */
    public function get_users_to_purge_provider(): array {
        return [
            'No users to delete' => [
                'numusers' => 10,
                'numusersdeleted' => 0,
                'numuserscontent' => 0,
                'expecteduserstopurge' => 0,
            ],
            'Some users deleted without content' => [
                'numusers' => 10,
                'numusersdeleted' => 5,
                'numuserscontent' => 0,
                'expecteduserstopurge' => 5,
            ],
            'Some users deleted with content' => [
                'numusers' => 10,
                'numusersdeleted' => 5,
                'numuserscontent' => 3,
                'expecteduserstopurge' => 2,
            ],
        ];
    }

    /**
     * Test for the purge_users method.
     *
     * @covers ::purge_users
     * @dataProvider purge_users_provider
     * @param int $numusers Number of users to create.
     * @param int $numusersdeleted Number of users to mark as deleted.
     * @param int $expectedusers Expected number of users after the purge.
     * @return void
     */
    public function test_purge_users(int $numusers, int $numusersdeleted, int $expectedusers) {
        global $DB;

        $this->resetAfterTest();

        $initialusers = $DB->count_records('user');
        $expectedusers += $initialusers;

        // Create users.
        $users = [];
        for ($i = 0; $i < $numusers; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[$i] = $user;
        }

        $userdeletedids = [];
        // Mark users as deleted.
        if ($numusersdeleted) {
            for ($i = 0; $i < $numusersdeleted; $i++) {
                delete_user($users[$i]);
                $userdeletedids[] = $users[$i]->id;
            }
        }

        $manager = new manager();
        $manager->purge_users($userdeletedids);

        $this->assertEquals($expectedusers, $DB->count_records('user'));
        $this->assertEquals($numusersdeleted, $DB->count_records('tool_purgeusers_log'));
        $this->assertEquals($numusersdeleted, $DB->count_records('tool_purgeusers_backup'));
        $this->assertEquals($userdeletedids, $DB->get_fieldset_select('tool_purgeusers_backup', 'userid', null));
    }

    /**
     * Data provider for test_purge_user.
     *
     * @return array
     */
    public function purge_users_provider(): array {
        return [
            'No users deleted' => [
                'numusers' => 10,
                'numusersdeleted' => 0,
                'expectedusers' => 10,
            ],
            'Some users deleted' => [
                'numusers' => 10,
                'numusersdeleted' => 5,
                'expectedusers' => 5,
            ],
        ];
    }
}
