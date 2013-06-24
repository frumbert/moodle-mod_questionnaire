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
 * prints the tabbed bar
 *
 * @author Mike Churchward
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionnaire
 */
global $DB, $SESSION;

$tabs = array();
$row  = array();
$inactive = array();
$activated = array();

$courseid = optional_param('courseid', false, PARAM_INT);
$current_tab = $SESSION->questionnaire->current_tab;

// If this questionnaire has a survey, get the survey and owner.
// In a questionnaire instance created "using" a PUBLIC questionnaire, prevent anyone from editing settings, editing questions,
// viewing all responses...except in the course where that PUBLIC questionnaire was originally created.

$courseid = $questionnaire->course->id;
if ($survey = $DB->get_record('questionnaire_survey', array('id' => $questionnaire->sid))) {
    $owner = (trim($survey->owner) == trim($courseid));
} else {
    $survey = false;
    $owner = true;
}
if ($questionnaire->capabilities->manage  && $owner) {
    $row[] = new tabobject('settings', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/qsettings.php?'.
            'id='.$questionnaire->cm->id), get_string('advancedsettings'));
}

if ($questionnaire->capabilities->editquestions && $owner) {
    $row[] = new tabobject('questions', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/questions.php?'.
            'id='.$questionnaire->cm->id), get_string('questions', 'questionnaire'));
}

if ($questionnaire->capabilities->preview && $owner) {
    if (!empty($questionnaire->questions)) {
        $row[] = new tabobject('preview', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/preview.php?'.
                        'id='.$questionnaire->cm->id), get_string('preview_label', 'questionnaire'));
    }
}

$usernumresp = $questionnaire->count_submissions($USER->id);

if ($questionnaire->capabilities->readownresponses && ($usernumresp > 0)) {
    $argstr = 'instance='.$questionnaire->id.'&user='.$USER->id;
    $row[] = new tabobject('myreport', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/myreport.php?'.
                           $argstr), get_string('yourresponses', 'questionnaire'));

    if (in_array($current_tab, array('mysummary', 'mybyresponse', 'myvall', 'mydownloadcsv'))) {
        $inactive[] = 'myreport';
        $activated[] = 'myreport';
        $row2 = array();
        $argstr2 = $argstr.'&action=summary';
        $row2[] = new tabobject('mysummary', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/myreport.php?'.$argstr2),
                                get_string('summary', 'questionnaire'));
        $argstr2 = $argstr.'&byresponse=1&action=vresp';
        $row2[] = new tabobject('mybyresponse', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/myreport.php?'.$argstr2),
                                get_string('responses', 'questionnaire'));
        $argstr2 = $argstr.'&byresponse=0&action=vall';
        $row2[] = new tabobject('myvall', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/myreport.php?'.$argstr2),
                                get_string('myresponses', 'questionnaire'));
    }
}

$numresp = $questionnaire->count_submissions();
// Number of responses in currently selected group (or all participants etc.).
if (isset($SESSION->questionnaire->numselectedresps)) {
    $numselectedresps = $SESSION->questionnaire->numselectedresps;
} else {
    $numselectedresps = $numresp;
}
if (isset($SESSION->questionnaire->currentsessiongroupid)) {
    $currentgroupid = $SESSION->questionnaire->currentsessiongroupid;
} else {
    $currentgroupid  = -1;
}

if ($questionnaire->capabilities->readallresponseanytime && $numresp > 0 && $owner && $numselectedresps > 0) {
    $argstr = 'instance='.$questionnaire->id.'&sid='.$questionnaire->sid;
    $row[] = new tabobject('allreport', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.
                           $argstr.'&action=vall'), get_string('viewresponses', 'questionnaire', $numresp));
    if (in_array($current_tab, array('vall', 'vresp', 'valldefault', 'vallasort', 'vallarsort', 'deleteall', 'downloadcsv',
                                     'vrespsummary', 'individualresp', 'printresp', 'deleteresp'))) {
        $inactive[] = 'allreport';
        $activated[] = 'allreport';
        if ($current_tab == 'vrespsummary' || $current_tab == 'valldefault') {
            $inactive[] = 'vresp';
        }
        $row2 = array();
        $argstr2 = $argstr.'&action=vall';
        $row2[] = new tabobject('vall', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('summary', 'questionnaire'));
        if ($questionnaire->capabilities->viewsingleresponse && $questionnaire->respondenttype != 'anonymous') {
            $argstr2 = $argstr.'&byresponse=1&action=vresp';
            $row2[] = new tabobject('vrespsummary', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('viewbyresponse', 'questionnaire'));
            if ($current_tab == 'individualresp' || $current_tab == 'deleteresp') {
                $argstr2 = $argstr.'&byresponse=1&action=vresp';
                $row2[] = new tabobject('vresp', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                        get_string('viewindividualresponse', 'questionnaire'));
            }
        }
    }
    if (in_array($current_tab, array('valldefault',  'vallasort', 'vallarsort', 'deleteall', 'downloadcsv'))) {
        $activated[] = 'vall';
        $row3 = array();

        $argstr2 = $argstr.'&action=vall';
        $row3[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('order_default', 'questionnaire'));
        if ($current_tab != 'downloadcsv' && $current_tab != 'deleteall') {
            $argstr2 = $argstr.'&action=vallasort&currentgroupid='.$currentgroupid;
            $row3[] = new tabobject('vallasort', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                    get_string('order_ascending', 'questionnaire'));
            $argstr2 = $argstr.'&action=vallarsort&currentgroupid='.$currentgroupid;
			$row3[] = new tabobject('vallarsort', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                    get_string('order_descending', 'questionnaire'));
        }
        if ($questionnaire->capabilities->deleteresponses) {
            $argstr2 = $argstr.'&action=delallresp';
            $row3[] = new tabobject('deleteall', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                    get_string('deleteallresponses', 'questionnaire'));
        }

        if ($questionnaire->capabilities->downloadresponses) {
            $argstr2 = $argstr.'&action=dwnpg&currentgroupid='.$currentgroupid;
            $link  = $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2);
            $row3[] = new tabobject('downloadcsv', $link, get_string('downloadtext'));
        }
    }

    if (in_array($current_tab, array('individualresp', 'printresp', 'deleteresp'))) {
        $inactive[] = 'vresp';
        $activated[] = 'vresp';
        $inactive[] = 'printresp';
        $row3 = array();

        // New way to output popup print window for 2.0.
        $linkname = get_string('print', 'questionnaire');
        $url = '/mod/questionnaire/print.php?qid='.$questionnaire->id.'&amp;rid='.$rid.
               '&amp;courseid='.$course->id.'&amp;sec=1';
        $title = get_string('printtooltip', 'questionnaire');
        $options= array('menubar' => true, 'location' => false, 'scrollbars' => true,
                        'resizable' => true, 'height' => 600, 'width' => 800);
        $name = 'popup';
        $link = new moodle_url($url);
        $action = new popup_action('click', $link, $name, $options);
        $actionlink = $OUTPUT->action_link($link, $linkname, $action, array('title'=>$title));
        $row3[] = new tabobject('printresp', '', $actionlink);

        if ($questionnaire->capabilities->deleteresponses) {
            $argstr2 = $argstr.'&action=dresp&rid='.$rid;
            $row3[] = new tabobject('deleteresp', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                            get_string('deleteresp', 'questionnaire'));
        }
    }
} else if ($questionnaire->capabilities->readallresponses && ($numresp > 0) &&
           ($questionnaire->resp_view == QUESTIONNAIRE_STUDENTVIEWRESPONSES_ALWAYS ||
            ($questionnaire->resp_view == QUESTIONNAIRE_STUDENTVIEWRESPONSES_WHENCLOSED
                && $questionnaire->is_closed()) ||
            ($questionnaire->resp_view == QUESTIONNAIRE_STUDENTVIEWRESPONSES_WHENANSWERED
                && $usernumresp > 0 )) &&
           $questionnaire->is_survey_owner()) {
    $argstr = 'instance='.$questionnaire->id.'&sid='.$questionnaire->sid;
    $row[] = new tabobject('allreport', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.
                           $argstr.'&action=vall'), get_string('viewresponses', 'questionnaire', $numresp));
    if (in_array($current_tab, array('valldefault',  'vallasort', 'vallarsort', 'deleteall', 'downloadcsv'))) {
        $inactive[] = 'vall';
        $activated[] = 'vall';
        $row2 = array();
        $argstr2 = $argstr.'&action=vall';
        $row2[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('summary', 'questionnaire'));
		$inactive[] = $current_tab;
		$activated[] = $current_tab;
        $row3 = array();
        $argstr2 = $argstr.'&action=vall';
        $row3[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('order_default', 'questionnaire'));
        $argstr2 = $argstr.'&action=vallasort&currentgroupid='.$currentgroupid;
        $row3[] = new tabobject('vallasort', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('order_ascending', 'questionnaire'));
        $argstr2 = $argstr.'&action=vallarsort&currentgroupid='.$currentgroupid;
		$row3[] = new tabobject('vallarsort', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                get_string('order_descending', 'questionnaire'));
		if ($questionnaire->capabilities->deleteresponses) {
            $argstr2 = $argstr.'&action=delallresp';
            $row2[] = new tabobject('deleteall', $CFG->wwwroot.htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2),
                                    get_string('deleteallresponses', 'questionnaire'));
        }

        if ($questionnaire->capabilities->downloadresponses) {
            $argstr2 = $argstr.'&action=dwnpg';
            $link  = htmlspecialchars('/mod/questionnaire/report.php?'.$argstr2);
            $row2[] = new tabobject('downloadcsv', $link, get_string('downloadtext'));
        }
        if (count($row2) <= 1) {
            $current_tab = 'allreport';
        }
    }
}

if ($questionnaire->capabilities->viewsingleresponse) {
    $nonrespondenturl = new moodle_url('/mod/questionnaire/show_nonrespondents.php', array('id'=>$questionnaire->cm->id));
    $row[] = new tabobject('nonrespondents',
                    $nonrespondenturl->out(),
                    get_string('show_nonrespondents', 'questionnaire'));
}

if ((count($row) > 1) || (!empty($row2) && (count($row2) > 1))) {
    $tabs[] = $row;

    if (!empty($row2) && (count($row2) > 1)) {
        $tabs[] = $row2;
    }

    if (!empty($row3) && (count($row3) > 1)) {
        $tabs[] = $row3;
    }

    print_tabs($tabs, $current_tab, $inactive, $activated);

}