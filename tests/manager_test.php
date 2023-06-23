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
