<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/theme/bootstrap/renderers/core_renderer.php");

class theme_mebis_core_renderer extends theme_bootstrap_core_renderer
{
    protected $header_renderer;
    protected $footer_renderer;

    public function __construct(\moodle_page $page, $target)
    {
        parent::__construct($page, $target);
        $this->header_renderer = new theme_mebis_header_renderer($page, $target);
        $this->footer_renderer = new theme_mebis_footer_renderer($page, $target);
    }

    public function main_navbar()
    {
        return $this->header_renderer->main_navbar();
    }


    public function main_sidebar()
    {
        return $this->header_renderer->main_sidebar();
    }

    public function main_header($isCourse = false)
    {
        return $this->header_renderer->main_header($isCourse);
    }

    public function main_footer()
    {
        return $this->footer_renderer->main_footer();
    }

    public function main_eventfooter()
    {
        return $this->footer_renderer->main_eventfooter();
    }

    public function main_searchbar()
    {
        return $this->footer_renderer->main_searchbar();
    }

    public function main_breadcrumbs()
    {
        return $this->header_renderer->main_breadcrumbs();
    }

    public function main_menubar($isCourse)
    {
        return $this->header_renderer->main_menubar($isCourse);
    }

    public function main_schools()
    {
        $content = '';
        $cats = coursecat::make_categories_list();

        $i = 0;
        foreach ($cats as $catId => $catName)
        {

            $content .= html_writer::start_div('col-xs-12 col-sm-6 col-md-4 schoolbox', array('data-courseid' => $catId, 'data-type' => '1'));

            $content .= html_writer::start_div('schoolbox-meta');
            $content .= html_writer::start_div('row');
            $content .= html_writer::start_div('col-md-12 box-type text-right');
            $content .= html_writer::tag('i', '', array('class' => 'icon-me-schule'));
            $content .= html_writer::end_div();
            $content .= html_writer::end_div();
            $content .= html_writer::end_div();

            $content .= html_writer::start_div('schoolbox-inner' . (($i == 0) ? ' first' : ''));
            $url = new moodle_url('/course/index.php', array('categoryid' => $catId));
            $content .= html_writer::start_tag('a', array('class' => 'schoolbox-link', 'href' => $url));
            $content .= html_writer::start_div('panel-heading info');

            $content .= html_writer::tag('span', $catName, array('class' => 'schoolname'));
            $content .= html_writer::tag('span', html_writer::tag('i', '', array('class' => 'icon-me-pfeil-weiter')), array('class' => 'vbox'));

            $content .= html_writer::end_div();
            $content .= html_writer::end_tag('a');
            $content .= html_writer::end_div();

            $content .= html_writer::end_div();

            $i++;
        }

        return $content;
    }

    public function block(block_contents $bc, $region)
    {
        // top region blocks (see theme_mebis_help_renderer) are returned just the way they are
        if($region === 'top' || $region === 'bottom') {
            return $bc->content;
        }

        $bc = clone($bc); // Avoid messing up the object passed in.
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }
        if (!empty($bc->blockinstanceid)) {
            $bc->attributes['data-instanceid'] = $bc->blockinstanceid;
        }
        $skiptitle = strip_tags($bc->title);
        if ($bc->blockinstanceid && !empty($skiptitle)) {
            $bc->attributes['aria-labelledby'] = 'instance-' . $bc->blockinstanceid . '-header';
        } else if (!empty($bc->arialabel)) {
            $bc->attributes['aria-label'] = $bc->arialabel;
        }
        if ($bc->dockable) {
            $bc->attributes['data-dockable'] = 1;
        }
        if ($bc->collapsible == block_contents::HIDDEN) {
            $bc->add_class('hidden');
        }
        if (!empty($bc->controls)) {
            $bc->add_class('block_with_controls');
        }
        if($bc->title == 'Meine Kurse' || $bc->title == 'Meine Schulen'){
            $bc->add_class('row');
        }
        if (empty($skiptitle)) {
            $output = '';
            $skipdest = '';
        } else {
            $output = html_writer::tag('a', get_string('skipa', 'access', $skiptitle),
                array('href' => '#sb-' . $bc->skipid, 'class' => 'skip-block')
            );
            $skipdest = html_writer::tag('span', '', array('id' => 'sb-' . $bc->skipid, 'class' => 'skip-block-to'));
        }
        if($bc->title == 'Meine Kurse' || $bc->title == 'Meine Schulen' || $bc->title == 'Einstellungen' || $bc->title == 'Lesezeichen' || $bc->title == 'Navigation'){
            $output .= html_writer::start_tag('div', array('class' => 'col-md-12'));
        }else{
            $output .= html_writer::start_tag('div', array('class' => 'col-md-4'));
        }
        $output .= html_writer::start_tag('div', $bc->attributes);

        $output .= $this->block_header($bc);
        $output .= $this->block_content($bc);

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= $this->block_annotation($bc);

        //$output .= $skipdest;

        $this->init_block_hider_js($bc);
        return $output;
    }

    public function blocks($region, $classes = array(), $tag = 'aside')
    {
        $displayregion = $this->page->apply_theme_region_manipulations($region);
        $classes = (array) $classes;
        $classes[] = 'block-region';
        $attributes = array(
            'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
            'class' => join(' ', $classes),
            'data-blockregion' => $displayregion,
            'data-droptarget' => '1'
        );
        $content = '';
        if ($this->page->blocks->region_has_content($displayregion, $this)) {
            $content .= $this->blocks_for_region($displayregion);
        } else {
            $content .= '';
        }

        return $content;
    }

}
