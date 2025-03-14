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
 * The form class for editing questions.
 * @package mod_questionnaire
 * @copyright  2016 Mike Churchward (mike.churchward@poetgroup.org)
 * @author Mike Churchward & Joseph Rézeau
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

namespace mod_questionnaire;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Class edit_question_form
 * @package mod_questionnaire
 * @property \MoodleQuickForm _form
 * @property array _customdata
 */
class edit_question_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        // TODO - Find a way to not use globals. Maybe the base class allows more parameters to be passed?
        global $questionnaire, $question, $SESSION;

        // TODO - Is there a better way to do this without session global?
        // The 'sticky' required response value for further new questions.
        if (isset($SESSION->questionnaire->required) && !isset($question->qid)) {
            $question->required = $SESSION->questionnaire->required;
        }
        if (!isset($question->type_id)) {
            throw new \moodle_exception('undefinedquestiontype', 'mod_questionnaire');
        }

        // Each question can provide its own form elements to the provided form, or use the default ones.
        if (!$question->edit_form($this, $questionnaire)) {
            throw new \moodle_exception('Question type had an unknown error in the edit_form method.', 'mod_questionnaire');
        }
    }

    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // If this is a rate question.
        if ($data['type_id'] == QUESRATE) {
            if ($data['length'] < 2) {
                $errors["length"] = get_string('notenoughscaleitems', 'questionnaire');
            }
            // If this is a rate question with no duplicates option.
            if ($data['precise'] == 2 ) {
                $allchoices = $data['allchoices'];
                $allchoices = explode("\n", $allchoices);
                $nbvalues = 0;
                foreach ($allchoices as $choice) {
                    if ($choice && !preg_match("/^[0-9]{1,3}=/", $choice)) {
                            $nbvalues++;
                    }
                }
                if ($nbvalues < 2) {
                    $errors["allchoices"] = get_string('noduplicateschoiceserror', 'questionnaire');
                }
            }
            $ndcount = substr_count(trim($data['allnameddegrees']),PHP_EOL);
            if ($data['precise'] == 4) {
                if ($ndcount>0 && $ndcount+1<$data['length']) {
                    $errors["length"] = get_string('scaleitemslessthannameddegrees', 'questionnaire');
                } else if ($ndcount == 0) {
                    $errors["allnameddegrees"] = get_string('mustusenameddegrees', 'questionnaire');
                }
                if (strpos($data['allnameddegrees'], '=')==false) {
                    $errors["allnameddegrees"] = get_string('nameddegreesnoequals', 'questionnaire');
                }
            }
        }

        return $errors;
    }

    /**
     * Magic method for getting the protected $_form MoodleQuickForm and $_customdata array properties.
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name == '_form') {
            return $this->_form;
        } else if ($name == '_customdata') {
            return $this->_customdata;
        } else {
            throw new \coding_exception($name.' is not a publicly accessible property of '.get_class($this));
        }
    }
}
