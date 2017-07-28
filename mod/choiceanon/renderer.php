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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package   mod_choiceanon
 * @copyright 2010 Rossiani Wijaya
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
define ('DISPLAY_HORIZONTAL_LAYOUT', 0);
define ('DISPLAY_VERTICAL_LAYOUT', 1);

class mod_choiceanon_renderer extends plugin_renderer_base {

    /**
     * Returns HTML to display choices of option
     * @param object $options
     * @param int  $coursemoduleid
     * @param bool $vertical
     * @return string
     */
    public function display_options($options, $coursemoduleid, $vertical = false) {
        $layoutclass = 'horizontal';
        if ($vertical) {
            $layoutclass = 'vertical';
        }
        $target = new moodle_url('/mod/choiceanon/view.php');
        $attributes = array('method'=>'POST', 'action'=>$target, 'class'=> $layoutclass);

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('ul', array('class'=>'choiceanons' ));

        $availableoption = count($options['options']);
        $choiceanoncount = 0;
        foreach ($options['options'] as $option) {
            $choiceanoncount++;
            $html .= html_writer::start_tag('li', array('class'=>'option'));
            $option->attributes->name = 'answer';
            $option->attributes->type = 'radio';
            $option->attributes->id = 'choiceanon_'.$choiceanoncount;

            $labeltext = $option->text;
            if (!empty($option->attributes->disabled)) {
                $labeltext .= ' ' . get_string('full', 'choiceanon');
                $availableoption--;
            }

            $html .= html_writer::empty_tag('input', (array)$option->attributes);
            $html .= html_writer::tag('label', $labeltext, array('for'=>$option->attributes->id));
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::tag('li','', array('class'=>'clearfloat'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));

        if (!empty($options['hascapability']) && ($options['hascapability'])) {
            if ($availableoption < 1) {
               $html .= html_writer::tag('label', get_string('choiceanonfull', 'choiceanon'));
            } else {
                $html .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('savemychoiceanon','choiceanon'), 'class'=>'button'));
            }

            if (!empty($options['allowupdate']) && ($options['allowupdate'])) {
                $url = new moodle_url('view.php', array('id'=>$coursemoduleid, 'action'=>'delchoiceanon', 'sesskey'=>sesskey()));
                $html .= html_writer::link($url, get_string('removemychoiceanon','choiceanon'));
            }
        } else {
            $html .= html_writer::tag('label', get_string('havetologin', 'choiceanon'));
        }

        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Returns HTML to display choices result
     * @param object $choiceanons
     * @param bool $forcepublish
     * @return string
     */
    public function display_result($choiceanons, $forcepublish = false) {
        if (empty($forcepublish)) { //allow the publish setting to be overridden
            $forcepublish = $choiceanons->publish;
        }

        $displaylayout = $choiceanons->display;

      /*  if ($forcepublish) {  //CHOICEANON_PUBLISH_NAMES
            return $this->display_publish_name_vertical($choiceanons);
        } else { //CHOICEANON_PUBLISH_ANONYMOUS';
            if ($displaylayout == DISPLAY_HORIZONTAL_LAYOUT) {
                return $this->display_publish_anonymous_horizontal($choiceanons);
            }
            return $this->display_publish_anonymous_vertical($choiceanons);
        }*/

        if ($forcepublish) {
            return $this->display_publish_anonymous_vertical($choiceanons);
        } else {
            if ($displaylayout == DISPLAY_HORIZONTAL_LAYOUT) {
                return $this->display_publish_anonymous_horizontal($choiceanons);
            }
            return $this->display_publish_anonymous_vertical($choiceanons);
        }

    }

    /**
     * Returns HTML to display choices result
     * @param object $choiceanons
     * @param bool $forcepublish
     * @return string
     */
    public function display_publish_name_vertical($choiceanons) {
        global $PAGE;
        $html ='';
        $html .= html_writer::tag('h3',format_string(get_string("responses", "choiceanon")));

        $attributes = array('method'=>'POST');
        $attributes['action'] = new moodle_url($PAGE->url);
        $attributes['id'] = 'attemptsform';

        if ($choiceanons->viewresponsecapability) {
            $html .= html_writer::start_tag('form', $attributes);
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=> $choiceanons->coursemoduleid));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=> sesskey()));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'mode', 'value'=>'overview'));
        }

        $table = new html_table();
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results names ';
        $table->tablealign = 'center';
        $table->summary = get_string('responsesto', 'choiceanon', format_string($choiceanons->name));
        $table->data = array();

        $count = 0;
        ksort($choiceanons->options);

        $columns = array();
        $celldefault = new html_table_cell();
        $celldefault->attributes['class'] = 'data';

        // This extra cell is needed in order to support accessibility for screenreader. MDL-30816
        $accessiblecell = new html_table_cell();
        $accessiblecell->scope = 'row';
        $accessiblecell->text = get_string('choiceanonoptions', 'choiceanon');
        $columns['options'][] = $accessiblecell;

        $usernumberheader = clone($celldefault);
        $usernumberheader->header = true;
        $usernumberheader->attributes['class'] = 'header data';
        $usernumberheader->text = get_string('numberofuser', 'choiceanon');
        $columns['usernumber'][] = $usernumberheader;


        foreach ($choiceanons->options as $optionid => $options) {
            $celloption = clone($celldefault);
            $cellusernumber = clone($celldefault);
            $cellusernumber->style = 'text-align: center;';

            $celltext = '';
            if ($choiceanons->showunanswered && $optionid == 0) {
                $celltext = format_string(get_string('notanswered', 'choiceanon'));
            } else if ($optionid > 0) {
                $celltext = format_string($choiceanons->options[$optionid]->text);
            }
            $numberofuser = 0;
            if (!empty($options->user) && count($options->user) > 0) {
                $numberofuser = count($options->user);
            }

            $celloption->text = $celltext;
            $cellusernumber->text = $numberofuser;

            $columns['options'][] = $celloption;
            $columns['usernumber'][] = $cellusernumber;
        }

        $table->head = $columns['options'];
        $table->data[] = new html_table_row($columns['usernumber']);

        $columns = array();

        // This extra cell is needed in order to support accessibility for screenreader. MDL-30816
        $accessiblecell = new html_table_cell();
        $accessiblecell->text = get_string('userchoosethisoption', 'choiceanon');
        $accessiblecell->header = true;
        $accessiblecell->scope = 'row';
        $accessiblecell->attributes['class'] = 'header data';
        $columns[] = $accessiblecell;

        foreach ($choiceanons->options as $optionid => $options) {
            $cell = new html_table_cell();
            $cell->attributes['class'] = 'data';

            if ($choiceanons->showunanswered || $optionid > 0) {
                if (!empty($options->user)) {
                    $optionusers = '';
                    foreach ($options->user as $user) {
                        $data = '';
                        if (empty($user->imagealt)){
                            $user->imagealt = '';
                        }

                        $userfullname = fullname($user, $choiceanons->fullnamecapability);
                        if ($choiceanons->viewresponsecapability && $choiceanons->deleterepsonsecapability  && $optionid > 0) {
                            $attemptaction = html_writer::label($userfullname, 'attempt-user'.$user->id, false, array('class' => 'accesshide'));
                            $attemptaction .= html_writer::checkbox('attemptid[]', $user->id,'', null, array('id' => 'attempt-user'.$user->id));
                            $data .= html_writer::tag('div', $attemptaction, array('class'=>'attemptaction'));
                        }
                        $userimage = $this->output->user_picture($user, array('courseid'=>$choiceanons->courseid));
                        $data .= html_writer::tag('div', $userimage, array('class'=>'image'));

                        $userlink = new moodle_url('/user/view.php', array('id'=>$user->id,'course'=>$choiceanons->courseid));
                        $name = html_writer::tag('a', $userfullname, array('href'=>$userlink, 'class'=>'username'));
                        $data .= html_writer::tag('div', $name, array('class'=>'fullname'));
                        $data .= html_writer::tag('div','', array('class'=>'clearfloat'));
                        $optionusers .= html_writer::tag('div', $data, array('class'=>'user'));
                    }
                    $cell->text = $optionusers;
                }
            }
            $columns[] = $cell;
            $count++;
        }
        $row = new html_table_row($columns);
        $table->data[] = $row;

        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        $actiondata = '';
        if ($choiceanons->viewresponsecapability && $choiceanons->deleterepsonsecapability) {
            $selecturl = new moodle_url('#');

            $selectallactions = new component_action('click',"checkall");
            $selectall = new action_link($selecturl, get_string('selectall'), $selectallactions);
            $actiondata .= $this->output->render($selectall) . ' / ';

            $deselectallactions = new component_action('click',"checknone");
            $deselectall = new action_link($selecturl, get_string('deselectall'), $deselectallactions);
            $actiondata .= $this->output->render($deselectall);

            $actiondata .= html_writer::tag('label', ' ' . get_string('withselected', 'choiceanon') . ' ', array('for'=>'menuaction'));

            $actionurl = new moodle_url($PAGE->url, array('sesskey'=>sesskey(), 'action'=>'delete_confirmation()'));
            $select = new single_select($actionurl, 'action', array('delete'=>get_string('delete')), null, array(''=>get_string('chooseaction', 'choiceanon')), 'attemptsform');

            $actiondata .= $this->output->render($select);
        }
        $html .= html_writer::tag('div', $actiondata, array('class'=>'responseaction'));

        if ($choiceanons->viewresponsecapability) {
            $html .= html_writer::end_tag('form');
        }

        return $html;
    }


    /**
     * Returns HTML to display choices result
     * @param object $choiceanons
     * @return string
     */
    public function display_publish_anonymous_vertical($choiceanons) {
        global $CHOICEANON_COLUMN_HEIGHT;

        $html = '';
        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->summary = get_string('responsesto', 'choiceanon', format_string($choiceanons->name));
        $table->data = array();

        $count = 0;
        ksort($choiceanons->options);
        $columns = array();
        $rows = array();

        $headercelldefault = new html_table_cell();
        $headercelldefault->scope = 'row';
        $headercelldefault->header = true;
        $headercelldefault->attributes = array('class'=>'header data');

        // column header
        $tableheader = clone($headercelldefault);
        $tableheader->text = html_writer::tag('div', get_string('choiceanonoptions', 'choiceanon'), array('class' => 'accesshide'));
        $rows['header'][] = $tableheader;

        // graph row header
        $graphheader = clone($headercelldefault);
        $graphheader->text = html_writer::tag('div', get_string('responsesresultgraphheader', 'choiceanon'), array('class' => 'accesshide'));
        $rows['graph'][] = $graphheader;

        // user number row header
        $usernumberheader = clone($headercelldefault);
        $usernumberheader->text = get_string('numberofuser', 'choiceanon');
        $rows['usernumber'][] = $usernumberheader;

        // user percentage row header
        $userpercentageheader = clone($headercelldefault);
        $userpercentageheader->text = get_string('numberofuserinpercentage', 'choiceanon');
        $rows['userpercentage'][] = $userpercentageheader;

        $contentcelldefault = new html_table_cell();
        $contentcelldefault->attributes = array('class'=>'data');

        foreach ($choiceanons->options as $optionid => $option) {
            // calculate display length
            $height = $percentageamount = $numberofuser = 0;
            $usernumber = $userpercentage = '';

            if (!empty($option->user)) {
               $numberofuser = count($option->user);
            }

            if($choiceanons->numberofuser > 0) {
               $height = ($CHOICEANON_COLUMN_HEIGHT * ((float)$numberofuser / (float)$choiceanons->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$choiceanons->numberofuser)*100.0;
            }

            $displaygraph = html_writer::tag('img','', array('style'=>'height:'.$height.'px;width:49px;', 'alt'=>'', 'src'=>$this->output->pix_url('column', 'choiceanon')));

            // header
            $headercell = clone($contentcelldefault);
            $headercell->text = $option->text;
            $rows['header'][] = $headercell;

            // Graph
            $graphcell = clone($contentcelldefault);
            $graphcell->attributes = array('class'=>'graph vertical data');
            $graphcell->text = $displaygraph;
            $rows['graph'][] = $graphcell;

            $usernumber .= html_writer::tag('div', ' '.$numberofuser.'', array('class'=>'numberofuser', 'title'=> get_string('numberofuser', 'choiceanon')));
            $userpercentage .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            // number of user
            $usernumbercell = clone($contentcelldefault);
            $usernumbercell->text = $usernumber;
            $rows['usernumber'][] = $usernumbercell;

            // percentage of user
            $numbercell = clone($contentcelldefault);
            $numbercell->text = $userpercentage;
            $rows['userpercentage'][] = $numbercell;
        }

        $table->head = $rows['header'];
        $trgraph = new html_table_row($rows['graph']);
        $trusernumber = new html_table_row($rows['usernumber']);
        $truserpercentage = new html_table_row($rows['userpercentage']);
        $table->data = array($trgraph, $trusernumber, $truserpercentage);

        $header = html_writer::tag('h3',format_string(get_string("responses", "choiceanon")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::tag('a', get_string('skipresultgraph', 'choiceanon'), array('href'=>'#skipresultgraph', 'class'=>'skip-block'));
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        return $html;
    }

    /**
     * Returns HTML to display choices result
     * @param object $choiceanons
     * @return string
     */
    public function display_publish_anonymous_horizontal($choiceanons) {
        global $CHOICEANON_COLUMN_WIDTH;

        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->summary = get_string('responsesto', 'choiceanon', format_string($choiceanons->name));
        $table->data = array();

        $columnheaderdefault = new html_table_cell();
        $columnheaderdefault->scope = 'col';

        $tableheadertext = clone($columnheaderdefault);
        $tableheadertext->text = get_string('choiceanonoptions', 'choiceanon');

        $tableheadernumber = clone($columnheaderdefault);
        $tableheadernumber->text = get_string('numberofuser', 'choiceanon');

        $tableheaderpercentage = clone($columnheaderdefault);
        $tableheaderpercentage->text = get_string('numberofuserinpercentage', 'choiceanon');

        $tableheadergraph = clone($columnheaderdefault);
        $tableheadergraph->text = get_string('responsesresultgraphheader', 'choiceanon');

        $table->head = array($tableheadertext, $tableheadernumber, $tableheaderpercentage, $tableheadergraph);

        $count = 0;
        ksort($choiceanons->options);

        $columndefault = new html_table_cell();
        $columndefault->attributes['class'] = 'data';

        $colheaderdefault = new html_table_cell();
        $colheaderdefault->scope = 'row';
        $colheaderdefault->header = true;
        $colheaderdefault->attributes['class'] = 'header data';

        $rows = array();
        foreach ($choiceanons->options as $optionid => $options) {
            $colheader = clone($colheaderdefault);
            $colheader->text = $options->text;

            $graphcell = clone($columndefault);
            $datacellnumber = clone($columndefault);
            $datacellpercentage = clone($columndefault);

            $numberofuser = $width = $percentageamount = 0;

            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }

            if($choiceanons->numberofuser > 0) {
               $width = ($CHOICEANON_COLUMN_WIDTH * ((float)$numberofuser / (float)$choiceanons->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$choiceanons->numberofuser)*100.0;
            }

            $attributes = array();
            $attributes['style'] = 'height:50px; width:'.$width.'px';
            $attributes['alt'] = '';
            $attributes['src'] = $this->output->pix_url('row', 'choiceanon');
            $displaydiagram = html_writer::tag('img','', $attributes);

            $graphcell->text = $displaydiagram;
            $graphcell->attributes = array('class'=>'graph horizontal');

            if($choiceanons->numberofuser > 0) {
               $percentageamount = ((float)$numberofuser/(float)$choiceanons->numberofuser)*100.0;
            }

            $datacellnumber->text = $numberofuser;
            $datacellpercentage->text = format_float($percentageamount,1). '%';


            $row = new html_table_row();
            $row->cells = array($colheader, $datacellnumber, $datacellpercentage, $graphcell);
            $rows[] = $row;
        }

        $table->data = $rows;

        $html = '';
        $header = html_writer::tag('h3',format_string(get_string("responses", "choiceanon")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::table($table);

        return $html;
    }
}

