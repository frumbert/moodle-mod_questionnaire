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
 * Questionnaire module external API
 *
 * @package    mod_questionnaire
 * @category   external
 * @copyright  2018 Igor Sazonov <sovletig@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/questionnaire/lib.php');
require_once($CFG->dirroot . '/mod/questionnaire/locallib.php');
require_once($CFG->dirroot . '/mod/questionnaire/questionnaire.class.php');

/**
 * Questionnaire module external functions
 *
 * @package    mod_questionnaire
 * @category   external
 * @copyright  2018 Igor Sazonov <sovletig@yandex.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_questionnaire_external extends \external_api {

    /**
     * Describes the parameters for submit_questionnaire_response.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function submit_questionnaire_response_parameters() {
        return new \external_function_parameters(
            [
                'questionnaireid' => new \external_value(PARAM_INT, 'Questionnaire instance id'),
                'surveyid' => new \external_value(PARAM_INT, 'Survey id'),
                'userid' => new \external_value(PARAM_INT, 'User id'),
                'cmid' => new \external_value(PARAM_INT, 'Course module id'),
                'sec' => new \external_value(PARAM_INT, 'Section number'),
                'completed' => new \external_value(PARAM_INT, 'Completed survey or not'),
                'rid' => new \external_value(PARAM_INT, 'Existing response id'),
                'submit' => new \external_value(PARAM_INT, 'Submit survey or not'),
                'action' => new \external_value(PARAM_ALPHA, 'Page action'),
                'responses' => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'name' => new \external_value(PARAM_RAW, 'data key'),
                            'value' => new \external_value(PARAM_RAW, 'data value')
                        ]
                    ),
                    'The data to be saved', VALUE_DEFAULT, []
                )
            ]
        );
    }

    /**
     * Submit questionnaire responses
     *
     * @param int $questionnaireid the questionnaire instance id
     * @param int $surveyid Survey id
     * @param int $userid User id
     * @param int $cmid Course module id
     * @param int $sec Section number
     * @param int $completed Completed survey 1/0
     * @param int $rid Already in progress response id.
     * @param int $submit Submit survey?
     * @param string $action
     * @param array $responses the response ids
     * @return array answers information and warnings
     */
    public static function submit_questionnaire_response($questionnaireid, $surveyid, $userid, $cmid, $sec, $completed, $rid,
                                                         $submit, $action, $responses) {
        self::validate_parameters(self::submit_questionnaire_response_parameters(),
            [
                'questionnaireid' => $questionnaireid,
                'surveyid' => $surveyid,
                'userid' => $userid,
                'cmid' => $cmid,
                'sec' => $sec,
                'completed' => $completed,
                'rid' => $rid,
                'submit' => $submit,
                'action' => $action,
                'responses' => $responses
            ]
        );

        list($cm, $course, $questionnaire) = questionnaire_get_standard_page_items($cmid);
        $questionnaire = new \questionnaire($course, $cm, 0, $questionnaire);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/questionnaire:submit', $context);

        $result = $questionnaire->save_mobile_data($userid, $sec, $completed, $rid, $submit, $action, $responses);
        $result['submitted'] = true;
        if (isset($result['warnings']) && !empty($result['warnings'])) {
            unset($result['responses']);
            $result['submitted'] = false;
        }
        $result['warnings'] = [];
        return $result;
    }

    /**
     * Describes the submit_questionnaire_response return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function submit_questionnaire_response_returns() {
        return new \external_single_structure(
            [
                'submitted' => new \external_value(PARAM_BOOL, 'submitted', true, false, false),
                'warnings' => new \external_warnings()
            ]
        );
    }


    /* --------- STORE FEEDBACK TEXT VALUE ----------- */

    public static function submit_questionnaire_feedback_returns() {
        return new \external_single_structure(
            [
                'output' => new \external_value(PARAM_RAW, 'json object', true, false, true),
            ]
        );
    }
    public static function submit_questionnaire_feedback_parameters() {
        return new \external_function_parameters(
            [
                'cmid' => new \external_value(PARAM_INT, 'Course module id'),
                'responseid' => new \external_value(PARAM_INT, 'Questionnaire response id'),
                'fieldname' => new \external_value(PARAM_ALPHA, 'Field name to find'),
                'value' => new \external_value(PARAM_RAW, 'Value to store')
            ]
        );
    }
    public static function submit_questionnaire_feedback($cmid, $responseid, $fieldname, $value) {
    global $DB;
        $output = new \stdClass();

        if (!empty($value)) {

            $context = \context_module::instance($cmid);
            self::validate_context($context);
            require_capability('mod/questionnaire:submit', $context);

            $qresponse = $DB->get_record('questionnaire_response', ['id' => $responseid]);
            // a questionnaire has a sid
            $questionnaire = $DB->get_record('questionnaire', ['id'=>$qresponse->questionnaireid]);
            // a sid is a questionnaire_survey id, which questions are bound to
            $question = $DB->get_record('questionnaire_question', ['surveyid'=>$questionnaire->sid, 'name'=>$fieldname]);
            if ($question) {
                $record = $DB->get_record('questionnaire_response_text', ['response_id'=>$qresponse->id,'question_id'=>$question->id]);
                if (!$record) {
                    $record = new stdClass();
                    $record->response_id = $qresponse->id;
                    $record->question_id = $question->id;
                    $record->response = $value;
                    $DB->insert_record('questionnaire_response_text', $record);
                } else {
                    $record->response = $value;
                    $DB->update_record('questionnaire_response_text', $record);
                }
                $output->status = 'ok';
            } else {
                $output->status = 'not found';
            }
        }
        return ['output'=>json_encode($output)];
    }




    /* --------- LOOK UP RESPONSE VALUE ----------- */

    // output of routine is aleady an ajax string; so return as param_raw
    public static function lookup_questionnaire_response_data_returns() {
        return new \external_single_structure(
            [
                'output' => new \external_value(PARAM_RAW, 'json object', true, false, true),
            ]
        );
    }
    public static function lookup_questionnaire_response_data_parameters() {
        return new \external_function_parameters(
            [
                'cmid' => new \external_value(PARAM_INT, 'Course module id'),
                'responseid' => new \external_value(PARAM_INT, 'Questionnaire response id'),
                'fieldname' => new \external_value(PARAM_ALPHA, 'Field name to lookup')
            ]
        );
    }

    public static function lookup_questionnaire_response_data($cmid, $responseid, $fieldname) {
    global $DB, $USER;
        $output = new \stdClass();

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/questionnaire:submit', $context);

        $qresponse = $DB->get_record('questionnaire_response',['id'=>$responseid,'userid'=>$USER->id], '*', MUST_EXIST); // if not the request isn't valid
        $questionnaire = $DB->get_record('questionnaire', ['id'=>$qresponse->questionnaireid]);
        if ($question = $DB->get_record('questionnaire_question', ['name'=>$fieldname, 'surveyid'=>$questionnaire->sid],'id,type_id',MUST_EXIST)) { // if not found then request isn't valid
            switch (intval($question->type_id)) {
                case 1: // QUESYESNO
                    if ($rec = $DB->get_record('questionnaire_response_bool',['response_id'=>$qresponse->id,'question_id'=>$question->id])) {
                        $output->value = $rec->choice_id; 
                    }
                break; 

                case 2: // QUESTEXT
                case 3: // QUESESSAY 
                case 10: // QUESNUMERIC 
                    if ($rec = $DB->get_record('questionnaire_response_text',['response_id'=>$qresponse->id,'question_id'=>$question->id])) {
                        $output->value = $rec->response; 
                    }
                break; 

                case 4: // QUESRADIO
                break; 

                case 5: // QUESCHECK
                break; 

                case 8: // QUESRATE 
                    $sql = 'select c.content, r.rankvalue from {questionnaire_response_rank} r inner join {questionnaire_quest_choice} c on r.choice_id = c.id where r.response_id = ? and r.question_id = ?';
                    if ($rows = $DB->get_records_sql($sql,[$qresponse->id,$question->id])) {
                        $output = [];
                        foreach ($rows as $row) {
                            $row->content = strtolower(trim(strip_tags($row->content)));
                            $row->rankvalue = intval($row->rankvalue);
                            $output[] = $row;
                            // $output[$row->choice_id] = $row->rankvalue;
                            // $output[] = [$row->choice_id => $row->rankvalue];
                        }
                    }
                break;

                case 9: // QUESDATE 
                    if ($rec = $DB->get_record('questionnaire_response_date',['response_id'=>$qresponse->id,'question_id'=>$question->id])) {
                        $output->value = $rec->response; 
                    }
                break;
            }
        }

        return ['output'=>json_encode($output)];
    }



    /* this isn't really realted to questionnaire but was handy for the project that introduced the above two methods */
    /* --------- LOOK UP RELATED USER COMPLETION ----------- */
    public static function section_completions_for_course_returns() {
        return new \external_single_structure(
            [
                'output' => new \external_value(PARAM_RAW, 'json object', true, false, true),
            ]
        );
    }
    public static function section_completions_for_course_parameters() {
        return new \external_function_parameters(
            [
                'courseid' => new \external_value(PARAM_ALPHANUMEXT, 'Related course idnumber to look up')
            ]
        );
    }

    public static function section_completions_for_course($courseid) {
    global $DB, $USER;

    $output = new \stdClass();

    $theuser = $USER;
    if ($USER->auth == "anonymous") {
        // anonymous auth puts a key into the idnumber which MIGHT represent another user who MIGHT exist
        // if that user does exist, we want to lookup the record for that user instead.

        // MemberID
        if ($match = $DB->get_record('user_preferences', ['name'=>'memberid','value'=>$USER->idnumber])) {
            $theuser = $DB->get_record('user',['id'=>$match->userid]);

        // Email
        } else if ($possibleuser = $DB->get_record('user',['email'=>$USER->idnumber])) {
            $theuser = $possibleuser;
        }
    }

    // courseid might be int or idnumber
    if (is_numeric($courseid)) {
        $course = get_course($courseid);
    } else {
        $course = $DB->get_record('course', array('idnumber' => $courseid), '*', MUST_EXIST);
    }
    $context = context_course::instance($course->id);
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $canviewhidden = has_capability('moodle/course:viewhiddensections', $context, $theuser);
    // $completioninfo = new completion_info($course);

        $section  = 0;
        $numsections = (int)max(array_keys($sections));
        while ($section <= $numsections) {

            $thissection = $sections[$section];
            $showsection = true;
            if (!$thissection->visible || !$thissection->available) {
                $showsection = $canviewhidden || !($course->hiddensections == 1);
            }

            if ($showsection) {

                $sectObj = new stdClass();
                $sectObj->name = $thissection->name;
                $sectObj->participated = false;

                if (!empty($modinfo->sections[$section])) {
                    foreach ($modinfo->sections[$section] as $modnumber) {
                        $mod = $modinfo->cms[$modnumber];

                        if (!$mod->is_visible_on_course_page()) {
                            continue; // skip
                        }

                        $completiondetails = \core_completion\cm_completion_details::get_instance($mod, $theuser->id);
                        if (!$completiondetails->has_completion()) {
                            continue; // skip modules without completion or
                        }

                        if (($completiondetails->get_overall_completion() === COMPLETION_COMPLETE)) {
                            $sectObj->participated = true;
                            // could break here
                        }
                    }
                }

                $output->results[] = $sectObj;

            }
            $section++;
        }
        return ['output'=>json_encode($output)];
    }
}
