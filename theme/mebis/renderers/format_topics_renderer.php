<?php


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/topics/renderer.php');

/**
 * Basic renderer for onetopic format.
 *
 * @copyright 2012 David Herney Bernal - cirano
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_mebis_format_topics_renderer extends format_topics_renderer
{
    /**
     * Generate a summary of a section for display on the 'coruse index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        //Add side jump-navigation
        echo html_writer::start_tag('ul',array('class' => 'jumpnavigation'));
        for($i = 1;$i <= $course->numsections;$i++){
            if($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available  ){
                echo html_writer::tag('li', '<span>'.$this->section_title($sections[$i], $course).'</span>'
                    , array('class' => 'jumpnavigation-point', 'data-scroll' => '#section-'.$i));
            }
        }
        echo html_writer::tag('li', '<span>'.  get_string('course-apps','theme_mebis').'</span>', array('class' => 'jumpnavigation-point', 'data-scroll' => '#block-region-side-post'));
        echo html_writer::tag('li', '<span>^</span>', array('class' => 'jumpnavigation-point up-arrow', 'data-scroll' => 'top'));
        echo html_writer::end_tag('ul');
        //End side jump-navigation

        parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
    }














    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $controls[] = html_writer::link($url,
                                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
                                        'class' => 'icon ', 'alt' => get_string('markedthistopic'))),
                                    array('title' => get_string('markedthistopic'), 'class' => 'editing_highlight'));
            } else {
                $url->param('marker', $section->section);
                $controls[] = html_writer::link($url,
                                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
                                    'class' => 'icon', 'alt' => get_string('markthistopic'))),
                                array('title' => get_string('markthistopic'), 'class' => 'editing_highlight'));
            }
        }

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

}