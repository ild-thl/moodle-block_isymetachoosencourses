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
 * block_ild_chosencourses module version information
 *
 * @package		block_ild_chosencourses
 * @author		2018 Stefan Bomanns - ILD, Technische Hochschule Lübeck, <stefan.bomanns@th-luebeck.de>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_isychosencourses extends block_base {

    public function init() {
		global $PAGE;
        $this->title = get_string('pluginname', 'block_isychosencourses');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'all' => true
        );
    }
    
    public function hide_header() {
    	return true;
    }

    public function get_content()
    {
        global $USER, $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $context = context_system::instance();

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $this->content->text = '';
        $this->content->text .= '<h2>' . get_string('my_courses', 'block_isychosencourses') . '</h2>';

        $courses = enrol_get_my_courses('*', 'fullname ASC');

        if (!empty($courses)) {

            $this->content->text .= '<div class="metatile-container">';

            // noindexcourse = 0
            foreach ($courses as $course) {

                // get meta per course
                $sql = 'SELECT * FROM {isymeta} WHERE courseid = ? AND noindexcourse != ?';
                $sql_param = array('courseid' => $course->id, 'noindexcourse' => 1);
                $meta_record = $DB->get_record_sql($sql, $sql_param);

                if ($meta_record) {
                    $course_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                    $coursecontext = context_course::instance($course->id);
                    $image_name = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid = :contextid AND itemid = :itemid AND filearea = :filearea AND component = :component AND filesize > 0 ORDER BY timemodified DESC", array('contextid' => $coursecontext->id, 'itemid' => 0, 'filearea' => 'overviewimage', 'component' => 'local_isymeta'));

                    if (!empty($image_name)) {

                        $image = moodle_url::make_pluginfile_url($coursecontext->id, $image_name->component, $image_name->filearea, $image_name->itemid, '/', $image_name->filename);

                    } else {
                        $image = '';
                    }

                    $startdate = false;
                    if (isset($meta_record->starttime) and $meta_record->starttime > time()) {
                        $startdate = date('d.m.Y', $meta_record->starttime);
                    }

                    //currently no use
                    // $meta_course = $DB->get_record('course', array('id' => $meta_record->courseid), '*', MUST_EXIST);

                    /*
                    $num_course_badges = 0;
                    $course_badges = badges_get_badges(BADGE_TYPE_COURSE, $course->id);
                    if ($course_badges) {
                        #mtrace(print_r($course_badges,true));
                        $num_course_badges = count($course_badges);
                        $course_user_badges = badges_get_user_badges($USER->id, $course->id);
                        #mtrace(print_r($course_user_badges,true));
                        if ($course_user_badges) {
                            $num_course_user_badges = count($course_user_badges);
                        } else {
                            $num_course_user_badges = 0;
                        }
                    }
                    */
                    /*
                    // simplecertificate
                    $num_simple_certificates = 0;
                    $num_issued_simple_certificates = 0;
                    $simple_certificates = $DB->get_records_sql('SELECT * FROM {simplecertificate} WHERE course='.$course->id);
                    #mtrace(print_r($simple_certificates,true));
                    if ($simple_certificates) {
                        foreach ($simple_certificates as $simple_certificate) {
                            $num_simple_certificates++;
                            $sql = 'SELECT * FROM {simplecertificate_issues} WHERE certificateid='.$simple_certificate->id.' AND userid='.$USER->id;
                            #mtrace($sql);
                            $issued_simple_certificates = $DB->get_records_sql('SELECT * FROM {simplecertificate_issues} WHERE certificateid='.$simple_certificate->id.' AND userid='.$USER->id);
                            #mtrace(print_r($issued_simple_certificates,true));
                            if ($issued_simple_certificates) {
                                foreach ($issued_simple_certificates as $issued_simple_certificate) {
                                    $num_issued_simple_certificates++;
                                }
                            }
                        }
                    }
                    $num_certificates = $num_simple_certificates;
                    $num_issued_certificates = $num_issued_simple_certificates;
                    */

                    $universities = $DB->get_record('user_info_field', array('shortname' => 'isymeta_de_targetgroups'));
                    $subjectareas = $DB->get_record('user_info_field', array('shortname' => 'isymeta_de_formats'));
                    $uni = explode("\n", $universities->param1);
                    $subject = explode("\n", $subjectareas->param1);


                    $lang_list = [
                        'Deutsch',
                        'Englisch'
                    ];

                    $starttime = date('d.m.y', $meta_record->meta5);


                    $data = array('courseurl' => $course_url,
                        'starttime' => $starttime,
                        'coursetitle' => $meta_record->coursetitle,
                        'university' => $uni[$meta_record->meta2],
                        'subject' => $subject[$meta_record->meta6],
                        'lecturer' => $meta_record->lecturer,
                        'courselanguage' => $lang_list[$meta_record->courselanguage],
                        'processingtime' => $meta_record->meta4,
                        'image' => $image,
                    );

                    $this->content->text .= $OUTPUT->render_from_template('block_isychosencourses/chosencourses', $data);
                }
            }

            // noindexcourse = 1
            foreach ($courses as $course) {

                // get meta per course
                $sql = 'SELECT * FROM {isymeta} WHERE courseid = ? AND noindexcourse = ?';
                $sql_param = array('courseid' => $course->id, 'noindexcourse' => 1);
                $meta_record = $DB->get_record_sql($sql, $sql_param);

                if ($meta_record) {
                    $course_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                    $coursecontext = context_course::instance($course->id);
                    $image_name = $DB->get_record_sql("SELECT * FROM {files} WHERE contextid = :contextid AND itemid = :itemid AND filearea = :filearea AND component = :component AND filesize > 0 ORDER BY timemodified DESC", array('contextid' => $coursecontext->id, 'itemid' => 0, 'filearea' => 'overviewimage', 'component' => 'local_isymeta'));

                    if (!empty($image_name)) {

                        $image = moodle_url::make_pluginfile_url($coursecontext->id, $image_name->component, $image_name->filearea, $image_name->itemid, '/', $image_name->filename);

                    } else {
                        $image = '';
                    }

                    $startdate = false;
                    if (isset($meta_record->starttime) and $meta_record->starttime > time()) {
                        $startdate = date('d.m.Y', $meta_record->starttime);
                    }

                    $universities = $DB->get_record('user_info_field', array('shortname' => 'isymeta_de_targetgroups'));
                    $subjectareas = $DB->get_record('user_info_field', array('shortname' => 'isymeta_de_formats'));
                    $uni = explode("\n", $universities->param1);
                    $subject = explode("\n", $subjectareas->param1);


                    $lang_list = [
                        'Deutsch',
                        'Englisch'
                    ];

                    $starttime = date('d.m.y', $meta_record->starttime);


                    $data = array('courseurl' => $course_url,
                        'starttime' => $starttime,
                        'coursetitle' => $meta_record->coursetitle,
                        'university' => $uni[$meta_record->university],
                        'subject' => $subject[$meta_record->subjectarea],
                        'lecturer' => $meta_record->lecturer,
                        'courselanguage' => $lang_list[$meta_record->courselanguage],
                        'processingtime' => $meta_record->meta4,
                        'image' => $image,
                    );

                    $this->content->text .= $OUTPUT->render_from_template('block_isychosencourses/chosencourses', $data);
                }
            }

            $this->content->text .= '</div>';

        } else {

            $this->content->text .= get_string('no_courses', 'block_isychosencourses');

        }

        return $this->content;
    }
     
}   