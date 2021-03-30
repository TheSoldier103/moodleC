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

/**
 * Block displaying information about current logged-in user.
 *
 * This block can be used as anti cheating measure, you
 * can easily check the logged-in user matches the person
 * operating the computer.
 *
 * @package    block_learnerprofile
 * @copyright  2010 Remote-Learner.net
 * @author     Ufuoma Apoki <ufuomaapoki@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the current user's profile information.
 *
 * @copyright  2010 Remote-Learner.net
 * @author     Olav Jordan <olav.jordan@remote-learner.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_learnerprofile extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_learnerprofile');
    }

    /**
     * block contents
     *
     * @return object
     */
    
    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        require_once($CFG->dirroot . '/config.php');

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!isloggedin() or isguestuser()) {
            return '';      // Never useful unless you are logged in as real users
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $course = $this->page->course;
        $randomName = 'Main Boss';

        if (!isset($this->config->display_picture) || $this->config->display_picture == 1) {
            $this->content->text .= '<div class="learnerprofileitem picture">';
            $this->content->text .= $OUTPUT->user_picture($USER, array('courseid'=>$course->id, 'size'=>'100', 'class'=>'profilepicture'));  // The new class makes CSS easier
            $this->content->text .= '</div>';
        }

        $this->content->text .= '<div class="learnerprofileitem fullname">'.fullname($USER).'</div>';



        //Get responses for the active/reflective questionnaire for logged-in user
        $arFSLS_user_resp = $DB->get_records('questionnaire_response', array('userid' => $USER->id, 'questionnaireid' => 1));
        $arFSLS_user_resp = array_shift($arFSLS_user_resp);
 

        if (!empty($arFSLS_user_resp)){
            $responses = $DB->get_records('questionnaire_resp_single', array('response_id' => $arFSLS_user_resp->id));
            $responses = array_values($responses);
            $reflective = 0;

            //Get questions for active/reflective dimension
            $active_reflective = array($responses[0]->choice_id, $responses[1]->choice_id, 
                                $responses[2]->choice_id,$responses[3]->choice_id,$responses[4]->choice_id, 
                                $responses[5]->choice_id, $responses[6]->choice_id, $responses[7]->choice_id, 
                                $responses[8]->choice_id, $responses[9]->choice_id, $responses[10]->choice_id);

            for ($i = 0; $i < 11; $i++) {
                if($active_reflective[$i] % 2 == 0){   
                    $reflective++;
                }
            }
            
            if (in_array($reflective, array(6, 7, 8, 9, 10, 11))){
                $user_data = $DB->get_record_sql('SELECT * FROM {user_info_data} WHERE userid = ? AND fieldid = ?', [$USER->id, 1]);
                if ($user_data == null){
                    $DB->insert_record('user_info_data', array('userid' => $USER->id, 'fieldid' => 1, 'data' => 'reflective'));
                }
            }
            elseif (in_array($reflective, array(0, 1, 2, 3, 4, 5))) {
                $user_data = $DB->get_record_sql('SELECT * FROM {user_info_data} WHERE userid = ? AND fieldid = ?', [$USER->id, 1]);
                if ($user_data == null){
                    $DB->insert_record('user_info_data', array('userid' => $USER->id, 'fieldid' => 1, 'data' => 'active'));
                }
            }
        }



        //global $DB;
        //$course = $DB->get_record('course', array('id' => 3));
        //$info = get_fast_modinfo($course);
        //print_object($info);
        

        $userPreferences = $DB->get_records_sql('SELECT {user_info_field}.name, {user_info_data}.data FROM {user_info_data} INNER JOIN {user_info_field} ON {user_info_data}.fieldid = {user_info_field}.id WHERE {user_info_data}.userid = ?', [$USER->id]);
        foreach ($userPreferences as $up){
            $this->content->text .= '<div class="learnerprofileitem up">'.$up->name.': '.$up->data. '</div>';
        }
        

        if(!isset($this->config->display_country) || $this->config->display_country == 1) {
            $countries = get_string_manager()->get_list_of_countries(true);
            if (isset($countries[$USER->country])) {
                $this->content->text .= '<div class="learnerprofileitem country">';
                $this->content->text .= get_string('country') . ': ' . $countries[$USER->country];
                $this->content->text .= '</div>';
            }
        }

        if(!isset($this->config->display_city) || $this->config->display_city == 1) {
            $this->content->text .= '<div class="learnerprofileitem city">';
            $this->content->text .= get_string('city') . ': ' . format_string($USER->city);
            $this->content->text .= '</div>';
        }

        if(!isset($this->config->display_email) || $this->config->display_email == 1) {
            $this->content->text .= '<div class="learnerprofileitem email">';
            $this->content->text .= obfuscate_mailto($USER->email, '');
            $this->content->text .= '</div>';
        }

        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * allow more than one instance of the block on a page
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        //allow more than one instance on a page
        return false;
    }

    /**
     * allow instances to have their own configuration
     *
     * @return boolean
     */
    function instance_allow_config() {
        //allow instances to have their own configuration
        return false;
    }

    /**
     * instance specialisations (must have instance allow config true)
     *
     */
    public function specialization() {
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all'=>true);
    }

    /**
     * post install configurations
     *
     */
    public function after_install() {
    }

    /**
     * post delete configurations
     *
     */
    public function before_delete() {
    }

}
