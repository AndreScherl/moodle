<?php

/**
 * mbsmycourses block rendrer
 *
 * @package theme_mebis
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsmycourses/renderer.php');
require_once($CFG->libdir. '/coursecatlib.php');

class theme_mebis_block_mbsmycourses_renderer extends block_mbsmycourses_renderer
{
    /**
     * Construct contents of mbsmycourses block
     *
     * @param array $courses list of courses in sorted order
     * @param array $overviews list of course overviews
     * @return string html to be displayed in mbsmycourses block
     */
    public function mbsmycourses($courses, $overviews)
    {
        $html = '';
        $config = get_config('block_mbsmycourses');
        $ismovingcourse = false;
        $courseordernumber = 0;
        $maxcourses = count($courses);

        $userediting = false;
        // Intialise string/icon etc if user is editing and courses > 1
        if ($this->page->user_is_editing() && (count($courses) > 1)) {
            $userediting = true;
            $this->page->requires->js_init_call('M.block_mbsmycourses.add_handles');

            // Check if course is moving
            $ismovingcourse = optional_param('movecourse', FALSE, PARAM_BOOL);
            $movingcourseid = optional_param('courseid', 0, PARAM_INT);
        }

        // Render first movehere icon.
        if ($ismovingcourse) {
            // Remove movecourse param from url.
            $this->page->ensure_param_not_in_url('movecourse');

            // Show moving course notice, so user knows what is being moved.
            $html .= $this->output->box_start('notice');
            $a = new stdClass();
            $a->fullname = $courses[$movingcourseid]->fullname;
            $a->cancellink = html_writer::link($this->page->url, get_string('cancel'));
            $html .= get_string('movingcourse', 'block_mbsmycourses', $a);
            $html .= $this->output->box_end();

            $moveurl = new moodle_url('/blocks/mbsmycourses/move.php',
                        array('sesskey' => sesskey(), 'moveto' => 0, 'courseid' => $movingcourseid));
            // Create move icon, so it can be used.
            $movetofirsticon = html_writer::empty_tag('img',
                    array('src' => $this->output->pix_url('movehere'),
                        'alt' => get_string('movetofirst', 'block_mbsmycourses', $courses[$movingcourseid]->fullname),
                        'title' => get_string('movehere')));
            $moveurl = html_writer::link($moveurl, $movetofirsticon);
            $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
        }

        $html .= html_writer::start_tag('ul', array('class' => 'block-grid-xs-1 block-grid-xc-2 block-grid-md-3 course_list courses'));
        foreach ($courses as $key => $course) {
            // If moving course, then don't show course which needs to be moved.
            if ($ismovingcourse && ($course->id == $movingcourseid)) {
                continue;
            }

            $html .= html_writer::start_tag('li', array('class' => 'coursebox', 'id' => "course-{$course->id}"));

            $html .= html_writer::start_div('course_title');
            // If user is editing, then add move icons.
            if ($userediting && !$ismovingcourse) {
                $moveicon = html_writer::empty_tag('img',
                        array('src' => $this->pix_url('t/move')->out(false),
                            'alt' => get_string('movecourse', 'block_mbsmycourses', $course->fullname),
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array('sesskey' => sesskey(), 'movecourse' => 1, 'courseid' => $course->id));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));

            }

            $html .= html_writer::end_div();

            // .coursebox-meta
            $html .= html_writer::start_div('coursebox-meta');

            $html .= html_writer::start_div('row');

            $html .= html_writer::start_div('col-xs-6 course-is-new');
            $html .= html_writer::tag('span', get_string('new', 'theme_mebis'));
            $html .= html_writer::end_div();

            //TODO: If is not new, pull-right-class is needed (or change to col-12)
            $html .= html_writer::start_div('col-xs-6 box-type text-right');
            $html .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));

            $html .= html_writer::end_div();

            $html .= html_writer::end_div();
            $html .= html_writer::end_div();

            // .coursebox-inner
            $html .= html_writer::start_div('coursebox-inner');

            // No need to pass title through s() here as it will be dont automatically by html_writer.
            $attributes = array('title' => $course->fullname);
            if ($course->id > 0) {
                if (empty($course->visible)) {
                    $attributes['class'] = 'dimmed';
                }
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);

                $html .= html_writer::start_tag('a', array('class' => 'coursebox-link', 'href' => $courseurl));
                $html .= html_writer::tag('span', $coursefullname, array('class' => 'coursename internal'));

                $cat = coursecat::get($course->category, IGNORE_MISSING);
                if ($cat) {
                    $html .= html_writer::tag('p', $cat->get_formatted_name(), array('class' => 'coursetype'));
                }
                $html .= html_writer::end_tag('a');
            } else {
                $html .= $this->output->heading(html_writer::link(
                    new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id='.$course->remoteid)),
                    format_string($course->shortname, true), $attributes) . ' (' . format_string($course->hostname) . ')', 2, 'title');
            }

            if (!empty($config->showchildren) && ($course->id > 0)) {
                // List children here.
                if ($children = mbsmycourses::get_child_shortnames($course->id)) {
                    $html .= html_writer::tag('span', $children, array('class' => 'coursechildren'));
                }
            }

            // If user is moving courses, then down't show overview.
            if (isset($overviews[$course->id]) && !$ismovingcourse) {
                $html .= $this->activity_display($course->id, $overviews[$course->id]);
            }

            $courseordernumber++;
            if ($ismovingcourse) {
                $moveurl = new moodle_url('/blocks/mbsmycourses/move.php',
                            array('sesskey' => sesskey(), 'moveto' => $courseordernumber, 'courseid' => $movingcourseid));
                $a = new stdClass();
                $a->movingcoursename = $courses[$movingcourseid]->fullname;
                $a->currentcoursename = $course->fullname;
                $movehereicon = html_writer::empty_tag('img',
                        array('src' => $this->output->pix_url('movehere'),
                            'alt' => get_string('moveafterhere', 'block_mbsmycourses', $a),
                            'title' => get_string('movehere')));
                $moveurl = html_writer::link($moveurl, $movehereicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
            }

            $html .= html_writer::end_div();

            $html .= html_writer::end_tag('li');

        }

        $html .= html_writer::end_tag('ul');

        // Wrap course list in a div and return.
        $course_list = html_writer::tag('div', $html, array('class' => 'col-md-12'));

        $html .= html_writer::end_tag('div');

        return html_writer::tag('div', $course_list, array('class' => 'row course_list'));
    }

    /**
     * Constructs header in editing mode
     *
     * @param int $max maximum number of courses
     * @return string html of header bar.
     */
    public function editing_bar_head($max = 0)
    {
        $output = $this->output->box_start('row notice');
        $output .= html_writer::start_tag('div');
        $options = array('0' => get_string('alwaysshowall', 'theme_mebis'));
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $url = new moodle_url('/my/index.php');
        $select = new single_select($url, 'mynumber', $options, mbsmycourses::get_max_user_courses(), array());
        $select->set_label(get_string('numtodisplay', 'theme_mebis'), array("class" => "coursenumber-label"));
        $output .= $this->output->render($select);
        $output .= html_writer::end_div();
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Construct form to filter courses
     *
     * @return string return the HTML as a string, rather than printing it.
     */
    public function filter_form()
    {
        global $CFG;
        require_once($CFG->libdir."/formslib.php");

        $output = html_writer::start_tag('div', array('class' => 'my-courses'));

        $schools = [];
        foreach (mbsmycourses::schools_of_user() as $key => $value) {
            $schools[$value->id] = $value->name;
        }

        $output .= html_writer::start_tag('div', array('class' => 'row my-courses-filter'));
        $output .= html_writer::start_tag('div', array('class' => 'col-md-12'));
        $output .= html_writer::start_tag('div', array('class' => 'course-sorting'));

        // form [begin]
        $output .= html_writer::start_tag('form',  array("id" => "filter_form", "action" => new moodle_url("blocks/mbsmycourses/block_mbsmycourses.php"), "method" => "get", 'class' => 'row form-horizontal'));

        $output .= html_writer::start_tag('div', array('class'=>'col-md-7'));
        $output .= html_writer::select($schools, "filter_school", $selected = "0", false, array('class' => 'form-control'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class'=>'col-md-3'));
        $output .= html_writer::select(array(get_string('sort-manual', 'theme_mebis'), get_string('sort-name', 'theme_mebis'), get_string('sort-created', 'theme_mebis'), get_string('sort-modified', 'theme_mebis')), "sort_type", $selected = false, $nothing = get_string('sort-default', 'theme_mebis'), array('class' => 'form-control'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class'=>'col-md-2 text-right text-mobile-left'));
        $output .= html_writer::start_tag('label', array("for"=>"switch_list"));
        $output .= html_writer::tag('input', '<i class="icon-me-listenansicht"></i>', array("type" => "radio", "name" => "switch_view", "id" => "switch_list", "value" => "list"));
        $output .= html_writer::end_tag('label');
        $output .= html_writer::start_tag('label', array("for"=>"switch_grid"));
        $output .= html_writer::tag('input', '<i class="icon-me-kachelansicht"></i>', array("type" => "radio", "name" => "switch_view", "id" => "switch_grid", "value" => "grid", "class" => "grid-switch", "checked" => "checked"));
        $output .= html_writer::end_tag('label');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('form');
        // form [end]

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Show hidden courses count
     *
     * @param int $total count of hidden courses
     * @return string html
     */
    public function hidden_courses($total)
    {
        if ($total <= 0) {
            return;
        }
        $output = $this->output->box_start('notice margin-bottom-small');
        $plural = $total > 1 ? 'plural' : '';
        $config = get_config('block_mbsmycourses');
        // Show view all course link to user if forcedefaultmaxcourses is not empty.
        if (!empty($config->forcedefaultmaxcourses)) {
            $output .= get_string('hiddencoursecount'.$plural, 'theme_mebis', $total);
        } else {
            $a = new stdClass();
            $a->coursecount = $total;
            $a->showalllink = html_writer::link(new moodle_url('/my/index.php', array('mynumber' => block_mbsmycourses::SHOW_ALL_COURSES)),
                    get_string('showallcourses'));
            $output .= get_string('hiddencoursecountwithshowall'.$plural, 'theme_mebis', $a);
        }

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Construct button to load more results
     *
     * @return string return the HTML as a string, rather than printing it.
     */
    public function load_more_button()
    {
        $output = html_writer::start_tag('div', array('class' => 'row'));
        $output .= html_writer::start_tag('div', array('class' => 'col-md-12 add-more-results margin-bottom-medium'));
        $output .= html_writer::tag("button", get_string("load_more_results", "theme_mebis"), array("class" => "btn load-more-results"));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * //@TODO:
     * This is not actually used and only a blueprint. 12345 is used as a standin for an actual id.  and  may be
     * replaced with icon classes. Javascript has to be added to collape the schoolbox and change the icon (or class)
     */
    public function listView()
    {
        foreach($schools as $school) {
            //start schoolbox
            $list = html_writer::start_div('col-sm-12 categorybox text-left', array('data-categoryid' => '12345', 'data-type' => '1'));
            $list .= html_writer::start_div('row');
            $list .= html_writer::start_div('col-sm-12');
            $list .= html_writer::start_div('category-container category-name');
            $list .= $schoolName;
            $list .= html_writer::div('','closebutton', array('id' => 'close-12345'));
            $list .= html_writer::end_div();
            $list .= html_writer::end_div();
            $list .= html_writer::start_div('col-sm-12');
            $list .= html_writer::start_div('category-container', array('id' => 'cat-12345'));
            $list .= html_writer::start_div('row');

            foreach($courses as $course) {
                //start coursebox
                $list .= html_writer::start_div('col-sm-12');
                $list .= html_writer::start_div('category-coursebox');
                $list .= html_writer::div('','iconbox');
                $list .= html_writer::div(get_string('new', 'theme_mebis'),'newbox');
                $list .= $courseName;
                $list .= html_writer::end_div();
                $list .= html_writer::end_div();
                //end coursebox
            }

            $list .= html_writer::end_div();
            $list .= html_writer::end_div();
            $list .= html_writer::end_div();
            $list .= html_writer::end_div();
            $list .= html_writer::end_div();
            //end schoolbox
        }
        return $list;
    }
}
