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
 * Renderer for block_mbsnews
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_mbsnews_renderer extends plugin_renderer_base {

    public function render_content() {
        global $USER;

        $o = '';

        $context = context_system::instance();
        $cancreatenews = has_capability('block/mbsnews:sendnews', $context);

        $news = \block_mbsnews\local\newshelper::get_news($USER);

        // Something to display?
        if (!$news and ! $cancreatenews) {
            return $o;
        }

        if ($cancreatenews) {

            $icon = $this->output->pix_icon('t/add', get_string('addnotificationjob', 'block_mbsnews'));
            $url = new moodle_url('/blocks/mbsnews/editjob.php');
            $o .= html_writer::link($url, $icon . ' ' . get_string('addnotificationjob', 'block_mbsnews'));
        }

        if ($news) {

            foreach ($news->messages as $message) {

                // Header.
                $header = '';

                $deleteicon = $this->output->pix_icon('t/delete', get_string('delete'));
                $header = html_writer::tag('div', html_writer::link('#', $deleteicon, array('class' => 'mbsnews-header-delete', 'id' => 'mbsnewsdelete_'.$message->id)));

                $userpic = '';
                if (isset($news->authors[$message->useridfrom])) {
                    $userpic .= $this->output->user_picture($news->authors[$message->useridfrom], array('size' => 30));
                    $userpic .= fullname($news->authors[$message->useridfrom]);
                }

                $header .= html_writer::tag('span', $userpic, array('class' => 'mbsnews-header-userpic'));

                $time = userdate($message->timecreated);
                $header .= html_writer::tag('span', " // {$time} // ", array('class' => 'mbsnews-header-time'));

                $header .= html_writer::tag('span', $message->subject, array('class' => 'mbsnews-header-subject'));

                $header = html_writer::tag('div', $header, array('class' => 'mbsnews-header'));

                // Body.
                $body = html_writer::tag('div', $message->fullmessage, array('class' => 'mbsnews-body'));

                $o .= html_writer::tag('div', $header . $body, array('class' => 'mbsnews-message', 'id' => 'mbsnewsmessage_'.$message->id));
            }

            $ajaxurl = new \moodle_url('/blocks/mbsnews/ajax.php');

            $args = array();
            $args['url'] = $ajaxurl->out();
            $this->page->requires->yui_module('moodle-block_mbsnews-blockmbsnews', 'M.block_mbsnews.blockmbsnews', array($args));
        }
        return html_writer::tag('div', $o, array('class' => 'mbsnews'));
    }

}
