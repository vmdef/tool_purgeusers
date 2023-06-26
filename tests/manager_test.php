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

    public function test_get_users_to_purge() {
        global $DB;

        $this->resetAfterTest();

        $manager = new manager();

        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $user1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $user2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $user3 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // user1 has a post.
        $record = new \stdClass();
        $record->course = $course->id;
        $record->userid = $user1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // user1 and user2 are deleted.
        delete_user($user1);
        delete_user($user2);

        $users = $manager->get_users_to_purge();

        // Just user2 should be returned: is deleted and doesn't have activity.
        $this->assertEquals(1, count($users));
        $this->assertEquals($user2->id, $users[0]);
    }

    /**
     * Test for the get_purge_users method.
     *
     * @covers ::get_purge_users
     * @dataProvider purge_users_provider
     * @return void
     */
    public function test_purge_users(int $numusers, int $numusersdeleted, int $expectedusers) {
        global $DB;

        $this->resetAfterTest();

        $initialusers = $DB->count_records('user');
        $expectedusers += $initialusers;

        // Create users.
        $userids = [];
        for ($i = 0; $i < $numusers; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $userids[] = $user->id;
        }

        $userdeletedids = [];
        // Mark users as deleted.
        if ($numusersdeleted) {
            for ($i = 0; $i < $numusersdeleted; $i++) {
                $user = new \stdClass();
                $user->deleted = 1;
                $user->id = $userids[$i];
                $DB->update_record('user', $user);
                $userdeletedids[] = $user->id;
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
