<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');

$instanceid = required_param('instanceid', PARAM_INT);
$context = get_context_instance(CONTEXT_BLOCK, $instanceid);
$searchterm = required_param('searchterm', PARAM_TEXT);

if (isloggedin() && has_capability('block/quickcourselist:use', $context) && confirm_sesskey()) {

    $output = array();
    if (!empty($searchterm)) {
        
        $where = 'name like ?';

        if (!has_capability('moodle/category:viewhiddencategories', $context)) {
                    $where .= ' AND visible = 1';
        }

        $params = array("%$searchterm%");
        $order = 'name';
        $fields = 'id,name';

        $results = $DB->get_recordset_select('course_categories', $where, $params, $order, $fields);

        if ($results) {
            foreach ($results as $category) {
                $output[] = $category;
            }
            $results->close();
        }
    }
    header('Content-Type: application/json');
    echo json_encode($output);

} else {
    header('HTTP/1.1 401 Not Authorized');
}
