<?php
/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

define("MAX_USERS_TO_LIST_PER_ROLE", 10);

/**
 * Diese Klasse kapselt alle Funktionen, die innerhalb der neuen Darstellung der
 * Kursbereichsseite local/course/category.php verwendet werden.
 *
 * Damit das Skript /local/course/category.php das originale Moodleskript in
 * course/category.php ersetzt, muss in der config.php ist dazu in $CFG->customscripts
 * der Pfad zum Verzeichnis /local eingetragen werden.
 *
 * Siehe auch http://docs.moodle.org/dev/Local_plugins
 *
 */

class directorylisting {

    //Caching
    var $_category;
    var $_context;
    var $_rootcategory;

    //für State
    var $_coursetomove = false;    //== Kursobjekt, falls gewählt und Zielbereich gültig
    var $_coursetolink = false;    //== Kursobjekt, falls gewählt und Zielbereich gültig
    var $_categorytomove = false; //== Categoryobjekt, falls gewählt und Zielbereich gültig
    var $_linktomove = false;      //== Linkobjekt, falls gewählt und Zielbereich gültig

    /** gibt eine Instanz dieser Klasse zurück und merkt sich den aktuellen Kursbereich und
     * dessen Kontext.
     *
     * @staticvar directorylisting $dirlisting
     * @param object $category, der aktuelle Kursbereich
     * @param context $context, der aktuelle Kontext
     * @return directorylisting
     */
    public static function getInstance($category, $context) {
        static $dirlisting;

        if (!isset($dirlisting)) {
            $dirlisting = new directorylisting($category, $context);
        }

        return $dirlisting;
    }

    /** erzeugt ein Objekt der Klasse directorylisting für den aktuellen Kursbereich
     *
     * @param object $category
     * @param context $context
     */
    public function directorylisting($category, $context) {
        $this->_category = $category;
        $this->_context = $context;
    }

    /** Eventhandler für eigene Aktionen
     *
     * @return void
     */
    public function doActions() {
        global $OUTPUT, $DB, $PAGE;

        $category = $this->_category;

        //Berechtigungsprüfungen für die Aktionen
        if (!has_capability('moodle/category:manage', $PAGE->context)) {
            return;
        }

        //++++ Aktionen abarbeiten ++++++++++++++++++++++++++++++++++++++++++++++
        $myaction = optional_param('action', '', PARAM_ALPHA);

        switch ($myaction) {

            //+++ Category-Action
            case 'movecat' :
            //Zielmarke geklickt? -> Kategorie verschieben...
                $movecatid = optional_param('movecatid', 0, PARAM_INT);
                if ($movecatid != 0) {
                    //vor dieser Kategorie einfügen
                    $beforecatid = optional_param('beforecatid', '-1', PARAM_INT);
                    directorylisting::_doMoveCategory($movecatid, $category, $beforecatid);
                }
                break;

            case 'hidecat' :
                
                    $catid = optional_param('catid', '', PARAM_INT);
                    directorylisting::_doShowHideCategory($catid, 0);
                
                break;

            case 'showcat' :
                
                    $catid = optional_param('catid', '', PARAM_INT);
                    directorylisting::_doShowHideCategory($catid, 1);
                break;

            //Courseaction
            case 'movecourse' :
            //Zielmarke geklickt Kurs verschieben...
                $movecourseid = optional_param('movecourseid', 0, PARAM_INT);
                if ($movecourseid != 0) {
                    //vor dieser Kategorie einfügen
                    $aftercourseid = optional_param('aftercourseid', '-1', PARAM_INT);
                    $afterlinkid = optional_param('afterlinkid', '-1', PARAM_INT);
                    directorylisting::_doMoveCourse($movecourseid, $category->id, $aftercourseid, $afterlinkid);
                }
                break;

            //Courselink-Action
            case 'deletecourselink' :
                
                $courselinkid = optional_param('courselinkid', '', PARAM_INT);
                directorylisting::_doDeleteCourseLink($courselinkid);
                break;

            case 'createcourselink' :

                $courseid = optional_param('courseid', 0, PARAM_INT);
                $aftercourseid = optional_param('aftercourseid', 0, PARAM_INT);
                $aftercourselinkid = optional_param('aftercourselinkid', 0, PARAM_INT);
                directorylisting::_doCreateCourseLink($courseid, $category->id, $aftercourseid, $aftercourselinkid);
                break;
            //Courseaction

            case 'movecourselink' :

            //Zielmarke geklickt Kurs verschieben...
                $movelinkid = optional_param('movelinkid', 0, PARAM_INT);
                if ($movelinkid != 0) {
                    //vor dieser Kategorie einfügen
                    $aftercourseid = optional_param('aftercourseid', '-1', PARAM_INT);
                    $aftercourselinkid = optional_param('aftercourselinkid', '-1', PARAM_INT);
                    directorylisting::_doMoveLink($movelinkid, $category->id, $aftercourseid, $aftercourselinkid);
                }
                break;
        }
    }

    /** ermittelt nach durchgeführten Aktionen, ob gültige Kurslinks, Kurse oder
     * Kursbereiche zum Bearbeiten gewählt sind*/
    public function displayState() {
        global $DB, $OUTPUT;

        //SESSION: StatusChecks für die Statusanzeige...

        //+++Kategoriebearbeitung
        $this->_categorytomove = $this->_getCategoryMoveStatus();

        //+++ Kurslistensortierung    ++++++++++++++++++++++++++++++++++++++++++++
        $this->_coursetomove = $this->_getCourseMoveStatus();

        //+++ Kursverlinkungen
        $this->_coursetolink = $this->_getCourseLinkStatus();

        //+++ Kursverlinkungen
        $this->_linktomove = $this->_getLinkMoveStatus();
        //Kursliste
    }

    /** ergänzt die übergebene Kursinformationen um die Liste $course->managers,
     * die die User, denen eine der "coursecontact"-Rollen zugewiesen ist, enthält.
     * Im zweiten Teil ist diese Funktion aus der funtion get_courses_wmanager.. in
     * course/lib.php übernommen.
     *
     * @param int $categoryid, die Kategorie-ID, in denen der Kurs liegt (wird benötigt
     *                         falls eine der coursecontact-Rollen im Kategoriekontext vergeben ist.
     * @param object[] $courses, die Liste der Kurse, deren Informationen ergänzt werden sollen
     * @return void
     */
    protected static function _add_courses_wmanagers($categoryid, &$courses) {
        global $CFG, $DB;

        $catpath  = NULL;
        $allcats = false; //nur die aktuelle Kategorie nehmen..
        $categoryclause = "c.category = :catid";
        $params['catid'] = $categoryid;

        //1. Teil aus get_courses_wmanager..
        $catpaths = array();
        foreach ($courses as $k => $course) {
            context_helper::preload_from_record($course);
            $coursecontext = context_course::instance($course->id);
            $courses[$k] = $course;
            $courses[$k]->managers = array();
            if ($allcats === false) {
                // single cat, so take just the first one...
                if ($catpath === NULL) {
                    $catpath = preg_replace(':/\d+$:', '', $coursecontext->path);
                }
            } else {
                // chop off the contextid of the course itself
                // like dirname() does...
                $catpaths[] = preg_replace(':/\d+$:', '', $coursecontext->path);
            }
        }

        //2. (unveränderter) Teil aus get_courses_wmanager..
        $CFG->coursecontact = trim($CFG->coursecontact);
        if (empty($CFG->coursecontact)) {
            return;
        }

        $managerroles = explode(',', $CFG->coursecontact);
        $catctxids = '';
        if (count($managerroles)) {
            if ($allcats === true) {
                $catpaths  = array_unique($catpaths);
                $ctxids = array();
                foreach ($catpaths as $cpath) {
                    $ctxids = array_merge($ctxids, explode('/',substr($cpath,1)));
                }
                $ctxids = array_unique($ctxids);
                $catctxids = implode( ',' , $ctxids);
                unset($catpaths);
                unset($cpath);
            } else {
                // take the ctx path from the first course
                // as all categories will be the same...
                $catpath = substr($catpath,1);
                $catpath = preg_replace(':/\d+$:','',$catpath);
                $catctxids = str_replace('/',',',$catpath);
            }
            if ($categoryclause !== '') {
                $categoryclause = "AND $categoryclause";
            }
            /*
         * Note: Here we use a LEFT OUTER JOIN that can
         * "optionally" match to avoid passing a ton of context
         * ids in an IN() clause. Perhaps a subselect is faster.
         *
         * In any case, this SQL is not-so-nice over large sets of
         * courses with no $categoryclause.
         *
            */
            $sql = "SELECT ctx.path, ctx.instanceid, ctx.contextlevel,
                       r.id AS roleid, r.name as rolename,
                       u.id AS userid, u.firstname, u.lastname
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ra.contextid = ctx.id
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {role} r ON ra.roleid = r.id
                  LEFT OUTER JOIN {course} c
                       ON (ctx.instanceid=c.id AND ctx.contextlevel=".CONTEXT_COURSE.")
                WHERE ( c.id IS NOT NULL";
            // under certain conditions, $catctxids is NULL
            if($catctxids == NULL) {
                $sql .= ") ";
            }else {
                $sql .= " OR ra.contextid  IN ($catctxids) )";
            }

            $sql .= "AND ra.roleid IN ({$CFG->coursecontact})
                    $categoryclause
                ORDER BY r.sortorder ASC, ctx.contextlevel ASC, ra.sortorder ASC";
            $rs = $DB->get_recordset_sql($sql, $params);

            // This loop is fairly stupid as it stands - might get better
            // results doing an initial pass clustering RAs by path.
            foreach($rs as $ra) {
                $user = new stdClass;
                $user->id        = $ra->userid;
                unset($ra->userid);
                $user->firstname = $ra->firstname;
                unset($ra->firstname);
                $user->lastname  = $ra->lastname;
                unset($ra->lastname);
                $ra->user = $user;
                if ($ra->contextlevel == CONTEXT_SYSTEM) {
                    foreach ($courses as $k => $course) {
                        $courses[$k]->managers[] = $ra;
                    }
                } else if ($ra->contextlevel == CONTEXT_COURSECAT) {
                    if ($allcats === false) {
                        // It always applies
                        foreach ($courses as $k => $course) {
                            $courses[$k]->managers[] = $ra;
                        }
                    } else {
                        foreach ($courses as $k => $course) {
                            $coursecontext = context_course::instance($course->id);
                            // Note that strpos() returns 0 as "matched at pos 0"
                            if (strpos($coursecontext->path, $ra->path.'/') === 0) {
                                // Only add it to subpaths
                                $courses[$k]->managers[] = $ra;
                            }
                        }
                    }
                } else { // course-level
                    if (!array_key_exists($ra->instanceid, $courses)) {
                        //this course is not in a list, probably a frontpage course
                        continue;
                    }
                    $courses[$ra->instanceid]->managers[] = $ra;
                }
            }
            $rs->close();
        }
    }

    /** holt die Kurse und die Kurslinks für die aktuelle Kursbereichsseite mit Seitenaufteilung.
     * Da die Sichtbarkeit der Kurse geprüft werden muss, ist ein limit-SQL-Statement nicht möglich
     *
     * Die Funktion ist angelehnt an get_courses_page aus datalib.php
     *
     * @uses CONTEXT_COURSE
     * @param string|int $categoryid Either a category id or 'all' for everything
     * @param string $sort A field and direction to sort by
     * @param string $fields The additional fields to return
     * @param int $totalcount Reference for the number of courses
     * @param string $limitfrom The course to start from
     * @param string $limitnum The number of courses to limit to
     * @return array Array of courses
     */
    public static function get_courses_page($categoryid, $sort="sortorder ASC", $fields="c.*", &$totalcount, $limitfrom="", $limitnum="") {
        global $USER, $CFG, $DB;

        //den Kontext bereits mit dem SQL-Statment holen und dafür die SQL-Teile holen.
        list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');

        $totalcount = 0;
        if (!$limitfrom) {
            $limitfrom = 0;
        }
        $visiblecourses = array();

        //+++ awag: Hier mit Kurslinks?
        if (!empty($CFG->custom_category_usecourselinks)) {

            directorylisting::_fix_courselink_sortorder($categoryid);

            //Untersortierung hinzufügen...
            if (strpos($sort,'sortorder') !== false) $sort .= ", ".str_replace("sortorder", "suborder", $sort);

            $sql1 = "SELECT {$fields}, c.sortorder, 0 as suborder, 'course' as type, 0 as courselinkid, 0 as aftercourseid $ccselect ".
                    "FROM {course} c $ccjoin WHERE c.category = :c_categoryid ";

            $sql2 = "SELECT {$fields}, cl.sortorder as sortorder, cl.suborder, 'courselink' as type, cl.id as courselinkid, cl.aftercourseid $ccselect ".
                    "FROM {category_course_link} cl ".
                    "JOIN {course} c ON c.id = cl.courseid $ccjoin WHERE cl.category = :cl_categoryid ";

            //ermitteln Kurse und Kursverlinkungen...
            $sql = $sql1." UNION ".$sql2." ORDER BY $sort ";

            $params = array('c_categoryid' => $categoryid, 'cl_categoryid' => $categoryid);

        } else {

            $sql = "SELECT $fields, c.sortorder, 0 as suborder, 'course' as type, 0 as courselinkid, 0 as aftercourseid $ccselect
                 FROM {course} c $ccjoin
                 WHERE c.category = :catid
                 ORDER BY $sort";

            $params['catid'] = $categoryid;
        }

        //wieder original...
        // pull out all course matching the cat
        $rs = $DB->get_recordset_sql($sql, $params);
        // iteration will have to be done inside loop to keep track of the limitfrom and limitnum
        foreach($rs as $course) {

            //Kontext wurde bereits aus den Datenbank geholt, nur noch eine gültige Instanz draus machen.
            context_helper::preload_from_record($course);
            if ($course->visible <= 0) {
                // for hidden courses, require visibility check
                if (has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                    $totalcount++;
                    if ($totalcount > $limitfrom && (!$limitnum or count($visiblecourses) < $limitnum)) {
                        $visiblecourses [$course->id] = $course;
                    }
                }
            } else {
                $totalcount++;
                if ($totalcount > $limitfrom && (!$limitnum or count($visiblecourses) < $limitnum)) {
                    $visiblecourses [$course->id] = $course;
                }
            }
        }
        $rs->close();

        //die Trainer hinzufügen...
        directorylisting::_add_courses_wmanagers($categoryid, $visiblecourses);

        return $visiblecourses;
    }

    /** gibt eine Messagebox aus
     *
     * @param String $messagetype, CSS-Klasse der Box
     * @param String $message, Text der Box
     */
    protected static function _displayMessage($messagetype, $message) {
        global $OUTPUT;

        echo $OUTPUT->box_start($messagetype);
        echo $message;
        echo $OUTPUT->box_end();
    }

    public function isSortEditMode() {
        return ($this->_coursetomove || $this->_coursetolink);
    }

//+++ Kategorien - Liste ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

    /** ermittelt, ob ein Verschieben in den aktuellen Kursbereich möglich ist
     *
     * @global moodle_database $DB
     * @global object $OUTPUT
     * @return mixed false, wenn Verschieben nicht möglich ist
     * sonst das Objekt des zu verschiebenden Kursbereichs
     */
    private function _getCategoryMoveStatus() {
        global $DB, $OUTPUT, $SESSION;

        $category = $this->_category;

        //zu verschiebende Kategorie merken oder vergessen...
        $cattomove = optional_param('cattomove', '-1', PARAM_INT);
        if ($cattomove > 0) $SESSION->cattomove = $cattomove;
        if ($cattomove == 0) $SESSION->cattomove = 0;

        //Hinweis, dass eine Kategorie zum Verschieben vorgemerkt ist.
        if (isset($SESSION->cattomove) and $SESSION->cattomove > 0) {

            //Verschieben abbrechen
            $cancelbutton = $OUTPUT->single_button(new moodle_url('category.php',
                    array('id' => $category->id, 'cattomove' => '0', 'sesskey' => sesskey())), get_string('cancelmove', 'block_custom_category'), 'get');

            $categorytomove = $DB->get_record('course_categories', array('id' => $SESSION->cattomove));

            //Kategorie gültig?
            if (!$categorytomove) {
                $msgtext = get_string('invalidcattomove', 'block_custom_category', "<b>(ID: {$SESSION->cattomove})</b>");
                directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);
                return false;
            }

            $msgtext = get_string('movecatexpl', 'block_custom_category', "<b>{$categorytomove->name} (ID: {$categorytomove->id})</b>");
            directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);

            $return = true;

            //Kategorie darf nicht in sich selbst oder in eine Unterkategorie verschoben werden.
            $parentcategories = explode("/", $category->path);
            if (in_array($categorytomove->id, $parentcategories)) {

                $msgtext = get_string('invalidtargetcat', 'block_custom_category');
                directorylisting::_displayMessage('errorbox', $msgtext);
                $return = false;
            }

            if (!has_capability("moodle/category:manage", $this->_context)) {
                $msgtext = get_string('nopermissiontoeditcat', 'block_custom_category');
                directorylisting::_displayMessage('errorbox', $msgtext);
                $return = false;
            }

            if ($return) return $categorytomove;
        }

        return false;
    }

    /** verschiebt die Kategorie mit der ID $movecatid in der Kategorie $currentcategory vor
     * die Kategorie $beforecatid;
     * @param int $movecatid, ID der zu verschiebenden Kategorie
     * @param $currentcategory
     * @param int $beforecatid, ID der künftig nachfolgenden Kategorie oder 0, wenn an
     *            das Ende der Liste verschoben wird.
     * @return bool, wenn verschoben werden konnte
     */
    protected static function _doMoveCategory($movecatid, $currentcategory, $beforecatid) {
        global $DB, $SESSION;

        if (!confirm_sesskey()) return false;

        //Prüfen, ob Parameter sinnvoll
        if (($movecatid == 0) || ($beforecatid == -1)) return false;

        //Kategorie darf nicht in sich selbst oder eine seiner Unterkategorien verschoben werden
        $parentcategories = explode("/", trim($currentcategory->path, "/"));

        if (in_array($movecatid, $parentcategories)) {
            directorylisting::_displayMessage('errorbox', get_string('movenotinsubcategory', 'block_custom_category'));
            return false;
        }

        //Kategorie verschieben...
        if ($beforecatid == 0) { //ans Ende stellen oder keine sonstigen Kategorien vorhanden

            $sql = "SELECT max(sortorder) FROM {course_categories} where parent = :parentid";
            $sortorder = $DB->get_field_sql($sql, array('parentid' => $currentcategory->id));
            $sortorder++;

        } else {// vor die Kategorie $beforecatid stellen

            $sql = "SELECT sortorder FROM {course_categories} where id = :id";
            $sortorder = $DB->get_field_sql($sql, array('id' => $beforecatid));

            if (!$sortorder) return false;
            $sortorder--;
        }

        //gültige sortorder und gültige ID der zu movenden Kategorie
        $sql = "UPDATE {course_categories} set sortorder = :sortorder, parent = :parent where id = :id";
        $DB->execute($sql, array('sortorder' => $sortorder, 'parent' => $currentcategory->id, 'id' => $movecatid));

        fix_course_sortorder();

        $category = $DB->get_record('course_categories', array('id' => $movecatid));
        directorylisting::_displayMessage('informationbox', get_string('category_moved', 'block_custom_category', "{$category->name} ({$category->id})"));

        $SESSION->cattomove = 0;
        return true;
    }


    /** die Kategorie verstecken oder sichtbar machen
     *
     * @param int $catid, die ID der zu bearbeitenden Kategorie
     * @param bool $showcat, (1 = Kategorie wird sichtbar geschalten,
     *                        0 = Kategorie wird versteckt)
     * @return bool, true bei Erfolg
     */
    protected static function _doShowHideCategory($catid, $showcat) {
        global $DB;

        if (!confirm_sesskey()) return false;

        //checken, ob eine Gültige Kategorie-ID vorliegt:
        $category = $DB->get_record('course_categories', array('id' => $catid));

        if (!$category) return false;

        if ($showcat == 1) {

            course_category_show($category);

        } else {

            course_category_hide($category);
        }
        return true;
    }

    /** erstellt eine Zielmarke zu der die Kategorie $category hin verschoben werden kann
     *
     * @param int $categoryid, die aktuelle ID der Kursbereichsseite
     * @param int $beforecatid, die ID der nachfolgenden Kategorie
     * @internal param int $cattomove , die ID der zu verschiebenden Kategorie
     */
    protected function _printMoveCategoryMarker($categoryid, $beforecatid = 0) {

        if (!$this->_categorytomove) return;

        $movecatid = $this->_categorytomove->id;

        $url = new moodle_url('/course/category.php',
                array('id' => $categoryid, 'action' => 'movecat', 'movecatid' => $movecatid,
                        'beforecatid' => $beforecatid, 'sesskey' => sesskey()));
        echo "<span class=\"category-target\"><a href=\"{$url}\" title=\"".get_string('targettitle', 'block_custom_category')."\">".get_string('targetlink', 'block_custom_category')."</a></span>";
    }

    /** erstellt eine Liste der Unterkategorien im Ordner-Look
     
     * @param Object $category die aktuelle Kategorie
     * @param bool $editingon, true, falls Bearbeiten eingeschalten
     * @param string $sort, Art der Sortierung.
     */
    protected function _printSubCategories($category, $editingon, $sort = "sortorder ASC") {
        global $CFG, $DB, $OUTPUT, $PAGE;

        echo "<div id='subcategories'>";

        $subcategories = $DB->get_records('course_categories', array('parent' => $category->id), 'sortorder ASC');

        if ($category->parent != 0) {

            $urlfolder_up = new moodle_url('/course/category.php', array('id' => $category->parent));
            $classlast = ($subcategories == false)? "-last" : ""; //falls nur "categors up => Möglichkeit ein anderes Bild zu verwenden.

            echo "<div class=\"category-up{$classlast}\"><a href=\"{$urlfolder_up}\">[...]</a></div>";
        }

        if ($subcategories) {

            $lastcategory = array_pop($subcategories);

            $lastcatid = 0;

            foreach ($subcategories as $subcategory) {

                //den Movelink nicht im Bereich davor und danach ausgeben...
                $this->_printMoveCategoryMarker($category->id, $subcategory->id);
                self::_printKategorie($subcategory, $editingon, "subcategory", true);

                $lastcatid = $subcategory->id; //letzte Kategorie merken
            }

            $this->_printMoveCategoryMarker($category->id, $lastcategory->id);
            self::_printKategorie($lastcategory, $editingon, "subcategory-last", true);

        }

        $this->_printMoveCategoryMarker($category->id);

        echo "</div>\n";
    }

    /** gibt eine Kategorie mit Bearbeitungslinks aus
     * @param object $category, auszugebende Kategorie
     * @param bool $editingon, true, falls Bearbeiten eingeschalten
     * @param String $class, CSS-Klasse des Containers um die Kategorie
     * @param bool $isSubcategory, true, falls es nicht die aktuelle Kategorie ist.
     * @return void
     */
    protected static function _printKategorie($category, $editingon, $class, $isSubcategory = false) {
        global $CFG, $OUTPUT, $PAGE;

        //falls nicht sichtbar und nicht das Recht unsichtbare zu sehen exit
        if (!$category->visible and !has_capability('moodle/category:viewhiddencategories', $PAGE->context)) return;

        $dimmed = ($category->visible) ? "" : "dimmed";

        $html = "<div class='{$class} {$dimmed}'>";


        if ($editingon) {
            //falls der User in der übergeordneten Kategorie die erforderlichen Rechte nicht hat, löschen und verbergen nicht zulassen.

            //Parentkontext ermitteln.
            if ($category->parent == 0) $parentcontext = context_system::instance();
            else $parentcontext = context_coursecat::instance($category->parent);

            //verbergen und sichtbar schalten
            if (has_capability('moodle/category:viewhiddencategories', $parentcontext)) {

                $returntocat = ($isSubcategory)? $category->parent : $category->id;

                if (!empty($category->visible)) {

                    $html.= $OUTPUT->action_icon(new moodle_url('/course/category.php',
                            array('id' => $returntocat, 'catid' => $category->id, 'sesskey' => sesskey(), 'action' => 'hidecat')),
                            new pix_icon('t/hide', get_string('hide')));

                } else {

                    $html.= $OUTPUT->action_icon(new moodle_url('/course/category.php',
                            array('id' => $returntocat, 'catid' => $category->id, 'sesskey' => sesskey(), 'action' => 'showcat')),
                            new pix_icon('t/show', get_string('show')));
                }
            }

            //Löschen
            if (has_capability('moodle/category:manage', $parentcontext)) {

                $html.= $OUTPUT->action_icon(new moodle_url('/local/course/deletecategory.php',
                        array('delete' => $category->id, 'sesskey' => sesskey())),
                        new pix_icon('t/delete', get_string('delete')));
            }

            //Bearbeiten
            if (has_capability('moodle/category:manage', $PAGE->context)) {

                $html.= $OUTPUT->action_icon(new moodle_url('/course/editcategory.php',
                        array('id' => $category->id, 'returnto' => $category->id)),
                        new pix_icon('t/edit', get_string('editcategorythis')));
            }

            //Unterkategorie hinzufügen
            if (has_capability('moodle/category:manage', $PAGE->context)) {

                $html.= $OUTPUT->action_icon(new moodle_url('/course/editcategory.php',
                        array('parent' => $category->id, 'returnto' => $category->id)),
                        new pix_icon('category/icon-subcategory-add', get_string('addsubcategory'), 'theme'));
            }

            //Symbol zum Verschieben...
            if (has_capability('moodle/category:manage', $PAGE->context) and ($isSubcategory)) {

                $html.= $OUTPUT->action_icon(new moodle_url('/course/category.php',
                        array('id' => $category->parent, 'cattomove' => $category->id, 'moveactive' => '1')),
                        new pix_icon('t/move', get_string('move')));
            }
        }

        $caturl = new moodle_url('/course/category.php', array('id' => $category->id));

        $html .= "&nbsp;<a href=\"{$caturl}\" title=\"".get_string('intocategory', 'block_custom_category')."\">{$category->name}</a>";
        $html .="</div>";
        echo $html;
    }

    /** ermitteln die oberste (Übergeordnete) Kategorie (=Hauptkategorie)
     * des aktuelle Kursbereichs
     *
     * @global moodle_database $DB
     * @return object, die Hauptkategorie
     */
    public function get_rootCategory() {
        global $DB;

        //ermittle Rootkategorie:
        if ($this->_category->parent == 0) {

            $this->_rootcategory = $this->_category;

        } else {

            $parentids = explode("/", trim($this->_category->path, "/"));
            $this->_rootcategory = $DB->get_record('course_categories', array('id' => $parentids[0]));
        }
        return $this->_rootcategory;
    }

    /** stellt die Kategorieliste der aktuellen Kategorie dar
     *
     * @param bool $editingon, true, falls Bearbeiten eingeschalten
     */
    public function print_CategoriesList($editingon) {
        global $OUTPUT;

        $hauptbereich = $this->get_rootCategory();
        echo "<h1>".get_string('catheading', 'block_custom_category', $hauptbereich->name)."</h1>";

        echo $OUTPUT->box_start('','categories');
        //aktuelle Kategorie ausgeben
        self::_printKategorie($this->_category, $editingon, "category-open");
        $this->_printSubCategories($this->_category, $editingon);

        echo $OUTPUT->box_end();
    }

    /** gibt den Beschreibungstext der Kategorie aus
     *
     */
    public function print_CategoriesDescription() {
        global $OUTPUT;

        $category = $this->_category;
        $context = $this->_context;

        /// Print current category description
        if ($category->description) {

            echo $OUTPUT->box_start();
            $options = new stdClass;
            $options->noclean = true;
            $options->para = false;
            $options->overflowdiv = true;
            if (!isset($category->descriptionformat)) {
                $category->descriptionformat = FORMAT_MOODLE;
            }
            $text = file_rewrite_pluginfile_urls($category->description, 'pluginfile.php', $context->id, 'coursecat', 'description', null);
            echo format_text($text, $category->descriptionformat, $options);
            echo $OUTPUT->box_end();
        }
    }

//--- Kategorien ---------------------------------------------------------------
//+++ Kursliste ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//+++ Kurse

    /** ermittelt, ob ein Verschieben des Kurses möglich ist
     *
     * @global moodle_database $DB
     * @global object $OUTPUT
     * @return mixed false, wenn Verschieben nicht möglich ist
     * sonst das Objekt des zu verschiebenden Kurses
     */
    private function _getCourseMoveStatus() {
        global $DB, $OUTPUT, $SESSION;

        $category = $this->_category;

        //zu verschiebenden Kurs merken oder vergessen...
        $coursetomoveid = optional_param('coursetomove', '-1', PARAM_INT);
        if ($coursetomoveid > 0) $SESSION->coursetomove = $coursetomoveid;
        if ($coursetomoveid == 0) $SESSION->coursetomove = 0;

        //Hinweis, dass eine Kategorie zum Verschieben vorgemerkt ist.
        if (isset($SESSION->coursetomove) and ($SESSION->coursetomove > 0)) {

            $cancelbutton =  $OUTPUT->single_button(new moodle_url('category.php',
                    array('id' => $category->id, 'coursetomove' => '0', 'sesskey' => sesskey())), get_string('cancelmove', 'block_custom_category'), 'get');

            $coursetomove = $DB->get_record('course', array('id' => $SESSION->coursetomove));

            //Kurs gültig?
            if (!$coursetomove) {
                $msgtext = get_string('invalidcoursetomove', 'block_custom_category', "<b>(ID: {$SESSION->coursetomove})</b>");
                directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);
                return false;
            }

            $return = true;

            //Information über den zu sortierenden Kurs
            $msgtext = get_string('movecourseexpl', 'block_custom_category', "<b>{$coursetomove->fullname} (ID: {$coursetomove->id})</b>");
            directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);

            //Prüfen, ob in der aktuellen Kategorie bereits ein Kurslink für diesen Kurs vorhanden ist.
            if (directorylisting::_linkExists($coursetomove->id, $category->id)) {
                directorylisting::_displayMessage('errorbox', get_string('linkexistsexpl', 'block_custom_category', "<b>{$coursetomove->fullname} (ID: {$coursetomove->id})</b>"));
                $return = false;
            }

            if (!has_capability("moodle/category:manage", $this->_context)) {
                $msgtext = get_string('nopermissiontoeditcat', 'block_custom_category');
                directorylisting::_displayMessage('errorbox', $msgtext);
                $return = false;
            }

            if ($return) return $coursetomove;
        }
        return false;
    }

    /** verschiebt den Kurs mit der ID $movecourseid in die Kategorie ($categoryid)
     * vor den Kurs $beforecourseid
     *
     * @param int $movecourseid, die ID des zu verschiebenden Kurses
     * @param int $categoryid, die ID des Zielkursbereiches
     * @param $aftercourseid
     * @param $afterlinkid
     * @return bool true, falls das Verschieben erfolgreich war
     */
    protected static function _doMoveCourse($movecourseid, $categoryid, $aftercourseid, $afterlinkid) {
        global $DB, $SESSION;

        if (!confirm_sesskey()) return false;

        //Prüfen, ob Parameter sinnvoll
        if ($movecourseid == 0) return false;

        //Informationen ermitteln und Kurse und Linkid verifizieren
        $aftercourse = $DB->get_record('course', array('id' => $aftercourseid));
        $afterlink = $DB->get_record('category_course_link', array('id' => $afterlinkid));
        $coursetomove = $DB->get_record('course', array('id' => $movecourseid));

        //Informationen über die alte Lage ($aftercourseoldid und $afterlinkoldsuborder) des zu verschiebenden Kurses holen
        $sql = "SELECT * FROM {course} WHERE sortorder < :sortorder AND category = :category ORDER BY sortorder desc limit 1";
        $aftercourseold = $DB->get_record_sql($sql, array('sortorder' => $coursetomove->sortorder, 'category' => $coursetomove->category));
        $aftercourseoldid = ($aftercourseold)? $aftercourseold->id : 0;

        $sql = "SELECT max(suborder) FROM {category_course_link} ".
                "WHERE aftercourseid = :aftercourseid AND category = :category";
        $afterlinkoldsuborder = $DB->get_field_sql($sql, array('aftercourseid' => $aftercourseoldid, 'category' => $coursetomove->category));
        $afterlinkoldsuborder = ($afterlinkoldsuborder)? $afterlinkoldsuborder : 0;

        //Einfügen des Kurses
        //neue Sortorder des Kurses ermitteln:
        if ($aftercourse) {

            $newsortorder = $aftercourse->sortorder + 1;

        } else {

            $sql = "SELECT min(sortorder) FROM {course} where category = :categoryid";
            $newsortorder = $DB->get_field_sql($sql, array('categoryid' => $categoryid));

            if (!$newsortorder) {//Kategorie enthält keine Kurse, sortierung der Kategorie nehmen...
                $sql = "SELECT max(sortorder) FROM {course_categories} where id = :categoryid";
                $newsortorder = $DB->get_field_sql($sql, array('categoryid' => $categoryid));
            }
        }
        //nachfolgende Kurse nach hinten stellen
        $sql = "UPDATE {course} SET sortorder = sortorder + 1 WHERE sortorder >= :sortorder";
        $DB->execute($sql, array('sortorder' => $newsortorder));

        //Kurs einfügen und aus der ursprünglichen Kategorie entfernen (dort wird Reihenfolge durch fix_course_sortorder korrigiert.)
        $sql = "UPDATE {course} set sortorder = :sortorder, category = :category WHERE id = :id";
        $DB->execute($sql, array('sortorder' => $newsortorder, 'category' => $categoryid, 'id' => $movecourseid));

        //++++++++++++++  korrigieren der Kurslinks für das Entfernen des Kurses +++++++++++++++++++++++++++++++++++++++

        //nachfolgende Kurslinks in der alten Kategorie erhalten als aftercourseid die ID des Vorgängerkurses
        //(Oder 0, falls kein Vorgängerkurs existiert) und werden in der suborder korrigiert.

        $sql = "SELECT id FROM {category_course_link} ".
                "WHERE aftercourseid = :aftercourseid and category = :category";

        $linkstoupdate = $DB->get_records_sql($sql, array('aftercourseid' => $movecourseid, 'category' => $coursetomove->category));

        if ($linkstoupdate) {

            $whereset = "(".implode(",",array_keys($linkstoupdate)).")";

            $sql = "UPDATE {category_course_link} SET aftercourseid = :aftercourseid, ".
                    "suborder = suborder + :oldsuborder WHERE id IN $whereset ";
            $DB->execute($sql, array('aftercourseid' => $aftercourseoldid, 'oldsuborder' => $afterlinkoldsuborder));

        }

        //Korrigieren der aftercourseid-Werte der Links hinter der neuen Kursposition:
        //Alle Kurslinks in der neuen Kategorie mit der gleichen aftercourseid ab der suborder der Links mit
        //der ID afterlink id bekommen als neuen Wert für die aftercourseid die ID des eingefügten Kurses.

        //suborderwert ermitteln
        $suborderstart = ($afterlink)? $afterlink->suborder : 0;

        //einfügen
        $sql = "SELECT id FROM {category_course_link} ".
                "WHERE aftercourseid = :aftercourseid AND category = :category AND suborder > :suborderstart";

        $linkstoupdate = $DB->get_records_sql($sql, array('aftercourseid' => $aftercourseid, 'category' => $categoryid, 'suborderstart' => $suborderstart));

        if ($linkstoupdate) {

            $whereset = "(".implode(",",array_keys($linkstoupdate)).")";

            $sql = "UPDATE {category_course_link} SET aftercourseid = :aftercourseid WHERE id IN {$whereset}";
            $DB->execute($sql, array('aftercourseid' => $movecourseid));
        }

        fix_course_sortorder();
        directorylisting::_fix_courselink_sortorder($categoryid);

        //Bestätigung
        $course = $DB->get_record('course', array('id' => $movecourseid));
        directorylisting::_displayMessage('informationbox', get_string('course_moved', 'block_custom_category', "{$course->fullname} ({$course->id})"));

        $SESSION->coursetomove = 0;
        return true;
    }

    /** erstellt eine Zielmarke zu der der Kurs $coursetomove hin verschoben werden kann
     *
     * @param $position
     */
    public function _printMoveCourseMarker($position) {

        if (!$this->_coursetomove) return;

        $categoryid = $this->_category->id;
        $coursetomove = $this->_coursetomove->id;

        if (directorylisting::_linkExists($coursetomove, $categoryid)) return;

        $url = new moodle_url('/course/category.php',
                array('id' => $categoryid, 'action' => 'movecourse', 'movecourseid' => $coursetomove,
                        'aftercourseid' => $position['aftercourseid'], 'afterlinkid' => $position['afterlinkid'], 'sesskey' => sesskey()));

        echo '<tr><td colspan="3">';
        $str = "aftercourse:".$position['aftercourseid']." afterlink ".$position['afterlinkid'];
        echo "<span class=\"course-target\"><a href=\"{$url}\" title=\"".get_string('targettitle', 'block_custom_category')."\">";
        //echo get_string('targetlink', 'block_custom_category');
        echo $str;
        echo "</a></span>";
        echo '</td></tr>';
    }

    /**
     * angelehnt an die Funktion print_course ind course/lib.php
     *
     * @param object $course the course object.
     * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
     */
    public static function _print_course($course, $highlightterms = '') {
        global $CFG, $USER, $DB, $OUTPUT;

        $context = context_course::instance($course->id);

        // Rewrite file URLs so that they are correct
        $course->summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);

        echo html_writer::start_tag('div', array('class'=>'coursebox clearfix'));
        echo html_writer::start_tag('div', array('class'=>'info'));
        echo html_writer::start_tag('h3', array('class'=>'name'));

        $linkhref = new moodle_url('/course/view.php', array('id'=>$course->id));

        $coursename = get_course_display_name_for_list($course);
        $linktext = highlight($highlightterms, format_string($coursename));
        $linkparams = array('title'=>get_string('entercourse'));
        if (empty($course->visible)) {
            $linkparams['class'] = 'dimmed';
        }
        echo html_writer::link($linkhref, $linktext, $linkparams);
        echo html_writer::end_tag('h3');

        /// first find all roles that are supposed to be displayed
        if (!empty($CFG->coursecontact)) {
            $managerroles = explode(',', $CFG->coursecontact);
            $namesarray = array();
            $rusers = array();

            if (!isset($course->managers)) {
                $rusers = get_role_users($managerroles, $context, true,
                        'ra.id AS raid, u.id, u.username, u.firstname, u.lastname,
                 r.name AS rolename, r.sortorder, r.id AS roleid',
                        'r.sortorder ASC, u.lastname ASC');
            } else {
                //  use the managers array if we have it for perf reasosn
                //  populate the datastructure like output of get_role_users();
                foreach ($course->managers as $manager) {
                    $u = new stdClass();
                    $u = $manager->user;
                    $u->roleid = $manager->roleid;
                    $u->rolename = $manager->rolename;

                    $rusers[] = $u;
                }
            }

            /// Rename some of the role names if needed
            if (isset($context)) {
                $aliasnames = $DB->get_records('role_names', array('contextid'=>$context->id), '', 'roleid,contextid,name');
            }

            $namesarray = array();
            $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
            foreach ($rusers as $ra) {
                if (isset($namesarray[$ra->id])) {
                    //  only display a user once with the higest sortorder role
                    continue;
                }

                if (isset($aliasnames[$ra->roleid])) {
                    $ra->rolename = $aliasnames[$ra->roleid]->name;
                }

                $fullname = fullname($ra, $canviewfullnames);
                $namesarray[$ra->id] = format_string($ra->rolename).': '.
                        html_writer::link(new moodle_url('/user/view.php', array('id'=>$ra->id, 'course'=>SITEID)), $fullname);
            }

            if (!empty($namesarray)) {
                echo html_writer::start_tag('ul', array('class'=>'teachers'));
                foreach ($namesarray as $name) {
                    echo html_writer::tag('li', $name);
                }
                echo html_writer::end_tag('ul');
            }
        }
        echo html_writer::end_tag('div'); // End of info div

        if ($icons = enrol_get_course_info_icons($course)) {
            echo html_writer::start_tag('div', array('class'=>'enrolmenticons'));
            echo html_writer::start_tag('ul');
            foreach ($icons as $icon) {
                echo html_writer::start_tag('li');
                echo $OUTPUT->render($icon)." ".get_string($icon->component.'_'.$icon->pix, 'block_custom_category');
                echo html_writer::end_tag('li');
            }
            echo html_writer::end_tag('ul');
            echo html_writer::end_tag('div'); // End of enrolmenticons div
        }

        echo html_writer::start_tag('div', array('class'=>'summary'));
        $options = new stdClass();
        $options->noclean = true;
        $options->para = false;
        $options->overflowdiv = true;
        if (!isset($course->summaryformat)) {
            $course->summaryformat = FORMAT_MOODLE;
        }
        echo highlight($highlightterms, format_text($course->summary, $course->summaryformat, $options,  $course->id));

        echo html_writer::end_tag('div'); // End of summary div
        echo html_writer::end_tag('div'); // End of coursebox div
    }


    /** gibt die ausführliche Kursinformation zu einem Kurs zurück
     *
     * @param Object $course, der Kurs
     * @param Array $highlightterms, hervorzuhebende Textteile
     * @return String, die Kursinformation
     */
    public static function render_CourseInformation($course, $highlightterms = array()) {
        global $CFG;

        //Ecken abrunden und schön darstellen...
        ob_start();

        echo "<div class=\"infodock-oben-links\"></div>";
        echo "<div class=\"infodock-oben-rechts\"></div>";
        echo "<div style=\"clear:both\"></div>";
        echo "<div class=\"infodock-courseinfo\">";
        directorylisting::_print_course($course);
        echo "</div>";
        echo "<div class=\"infodock-unten-links\"></div>";
        echo "<div class=\"infodock-unten-rechts\"></div>";
        echo "<div style=\"clear:both\"></div>";

        $html =  ob_get_contents();
        ob_end_clean();
        return $html;

    }

    //--- Kurse
    //+++ Kurslinks

    private function _getLinkMoveStatus() {
        global $DB, $OUTPUT, $SESSION;

        $category = $this->_category;

        //zu movenden Link Kurs merken oder vergessen...
        $linktomove = optional_param('linktomove', '-1', PARAM_INT);
        if ($linktomove > 0) $SESSION->linktomove = $linktomove;
        if ($linktomove == 0) $SESSION->linktomove = 0;

        //falls in der Session ein zu verlinkenden Kurs vorgemerkt ist, Gültigkeit prüfen
        if (isset($SESSION->linktomove) and ($SESSION->linktomove > 0)) {

            $cancelbutton = $OUTPUT->single_button(new moodle_url('category.php',
                    array('id' => $category->id, 'linktomove' => '0', 'sesskey' => sesskey())), get_string('cancellinkmove', 'block_custom_category'), 'get');

            $linktomove = $DB->get_record('category_course_link', array('id' => $SESSION->linktomove));

            //Kurs gültig?
            if (!$linktomove) {
                $msgtext = get_string('invalidlinktomove', 'block_custom_category', "<b>(ID: {$SESSION->linktomove})</b>");
                directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);
                return false;
            }

            $return = true;

            //Information über den zu verlinkenden Kurs
            $msgtext = get_string('movelinkexpl', 'block_custom_category', "<b>(ID: {$linktomove->id})</b>");
            directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);

            //Verlinken nicht zulassen, falls ein Kurslink bereits in diesem Kursbereich vorhanden ist
            if (($linktomove->category != $category->id) and
                    directorylisting::_linkExists($linktomove->courseid, $category->id)) {
                directorylisting::_displayMessage('errorbox', get_string('linkexistsexpl', 'block_custom_category', "<b>(ID: {$linktomove->courseid})</b>"));
                $return = false;
            }

            $coursetolink = $DB->get_record('course', array('id' => $linktomove->courseid));

            //Verlinken nicht zulassen, falls der Kurs bereits in diesem Kursbereich vorhanden ist.
            if ($coursetolink and ($coursetolink->category == $category->id)) {
                directorylisting::_displayMessage('errorbox', get_string('courseexistsexpl', 'block_custom_category', "(ID: {$coursetolink->id})</b>"));
                $return = false;
            }

            if (!has_capability("moodle/category:manage", $this->_context)) {
                $msgtext = get_string('nopermissiontoeditcat', 'block_custom_category');
                directorylisting::_displayMessage('errorbox', $msgtext);
                $return = false;
            }

            if ($return) return $linktomove;
        }
        return false;
    }

    /** ermittelt, ob eine Verlinkung im aktuellen Kursbereich möglich ist
     *
     * @global moodle_database $DB
     * @global object $OUTPUT
     * @return mixed false, wenn keine Verlinkung möglich ist sonst das Objekt des zu verlinkenden Kurses
     */
    private function _getCourseLinkStatus() {
        global $DB, $OUTPUT, $SESSION;

        $category = $this->_category;

        //zu verlinkenden Kurs merken oder vergessen...
        $coursetolink = optional_param('coursetolink', '-1', PARAM_INT);
        if ($coursetolink > 0) $SESSION->coursetolink = $coursetolink;
        if ($coursetolink == 0) $SESSION->coursetolink = 0;

        //falls in der Session ein zu verlinkenden Kurs vorgemerkt ist, Gültigkeit prüfen
        if (isset($SESSION->coursetolink) and ($SESSION->coursetolink > 0)) {

            $cancelbutton = $OUTPUT->single_button(new moodle_url('category.php',
                    array('id' => $category->id, 'coursetolink' => '0', 'sesskey' => sesskey())), get_string('cancellink', 'block_custom_category'), 'get');

            $coursetolink = $DB->get_record('course', array('id' => $SESSION->coursetolink));

            //Kurs gültig?
            if (!$coursetolink) {
                $msgtext = get_string('invalidcoursetolink', 'block_custom_category', "<b>(ID: {$SESSION->coursetolink})</b>");
                directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);
                return false;
            }

            $return = true;

            //Information über den zu verlinkenden Kurs
            $msgtext = get_string('linkcourseexpl', 'block_custom_category', "<b>{$coursetolink->fullname} (ID: {$coursetolink->id})</b>");
            directorylisting::_displayMessage('informationbox', $msgtext.$cancelbutton);

            //Verlinken nicht zulassen, falls ein Kurslink bereits in diesem Kursbereich vorhanden ist
            if (directorylisting::_linkExists($coursetolink->id, $category->id)) {
                directorylisting::_displayMessage('errorbox', get_string('linkexistsexpl', 'block_custom_category', "<b>{$coursetolink->fullname} (ID: {$coursetolink->id})</b>"));
                $return = false;
            }

            //Verlinken nicht zulassen, falls der Kurs bereits in diesem Kursbereich vorhanden ist.
            if ($coursetolink->category == $category->id) {
                directorylisting::_displayMessage('errorbox', get_string('courseexistsexpl', 'block_custom_category', "<b>{$coursetolink->fullname} (ID: {$coursetolink->id})</b>"));
                $return = false;
            }

            if (!has_capability("moodle/category:manage", $this->_context)) {
                $msgtext = get_string('nopermissiontoeditcat', 'block_custom_category');
                directorylisting::_displayMessage('errorbox', $msgtext);
                $return = false;
            }

            if ($return) return $coursetolink;
        }
        return false;
    }


    /** füllt das Feld sortorder mit den Werten des Kurses der ID aftercourseid
     * und optimiert die Sortierung suborder für Gruppen gleicher aftercourseid
     *
     * @global <type> $DB
     * @param <type> $categoryid
     */
    protected static function _fix_courselink_sortorder($categoryid) {
        global $DB;

        //hole ein Array mit courseid => sortorder aus mdl_course
        $sql = "SELECT id, sortorder FROM {course} WHERE category = :category";
        $sortorders = $DB->get_records_sql($sql, array('category' => $categoryid));

        //hole alle Kurslinks in dieser Kategorie
        $sql = "SELECT * FROM {category_course_link} WHERE category = :category ORDER BY suborder";
        $courselinks = $DB->get_records_sql($sql, array('category' => $categoryid));

        $suborder = array();

        //schreibe alle sortorder-Werte neu
        foreach ($courselinks as $courselink) {

            if (!isset($suborder[$courselink->aftercourseid])) $suborder[$courselink->aftercourseid] = 1;
            else $suborder[$courselink->aftercourseid] = $suborder[$courselink->aftercourseid] + 1;

            $courselink->suborder = $suborder[$courselink->aftercourseid];

            if (!isset($sortorders[$courselink->aftercourseid])) {
                $courselink->sortorder = 0;
            } else {
                $courselink->sortorder = $sortorders[$courselink->aftercourseid]->sortorder;
            }
            $DB->update_record('category_course_link', $courselink);
        }
    }

    /** prüft, ob im Kursbereich bereits ein Link auf einen Kurs mit der ID
     * $courseid existiert.
     *
     * @global moodle_database $DB
     * @staticvar array $links_incategory, cached das Suchergebnis
     * @param int $courseid, die ID des zu überprüfenden Kurses
     * @param int $categoryid, die ID des Kursbereiches
     * @return bool true, falls ein solcher Link existiert
     */
    protected static function _linkExists($courseid, $categoryid) {
        global $DB;
        static $links_incategory = array();

        if (isset($links_incategory[$categoryid])) return in_array($courseid, $links_incategory[$categoryid]);

        $sql = "SELECT courseid FROM {category_course_link} WHERE category = :category";
        $links = $DB->get_records_sql($sql, array('category' => $categoryid));

        if (!$links) return false;

        $links_incategory[$categoryid] = array_keys($links);
        return in_array($courseid, $links_incategory[$categoryid]);
    }

    /** verschiebt den Kurs mit der ID $movecourseid in die Kategorie ($categoryid)
     * vor den Kurs $beforecourseid
     *
     * @param $movelinkid
     * @param int $categoryid, die ID des Zielkursbereiches
     * @param $aftercourseid
     * @param $afterlinkid
     * @return bool true, falls das Verschieben erfolgreich war
     */
    protected static function _doMoveLink($movelinkid, $categoryid, $aftercourseid, $afterlinkid) {
        global $DB, $SESSION;

        //Prüfen, ob Parameter sinnvoll
        if ($movelinkid == 0) return false;

        if (!confirm_sesskey()) return false;

        //Informationen ermitteln und Kurse und Linkid verifizieren
        $aftercourse = $DB->get_record('course', array('id' => $aftercourseid));
        $afterlink = $DB->get_record('category_course_link', array('id' => $afterlinkid));
        $linktomove = $DB->get_record('category_course_link', array('id' => $movelinkid));

        //Informationen über die alte Lage ($aftercourseoldid und $afterlinkoldsuborder) des zu verschiebenden Links holen
        $sql = "SELECT * FROM {course} WHERE sortorder < :sortorder AND category = :category ORDER BY sortorder desc limit 1";
        $aftercourseold = $DB->get_record_sql($sql, array('sortorder' => $linktomove->sortorder, 'category' => $linktomove->category));
        $aftercourseoldid = ($aftercourseold)? $aftercourseold->id : 0;

        $sql = "SELECT max(suborder) FROM {category_course_link} ".
                "WHERE aftercourseid = :aftercourseid AND category = :category";
        $afterlinkoldsuborder = $DB->get_field_sql($sql, array('aftercourseid' => $aftercourseoldid, 'category' => $linktomove->category));
        $afterlinkoldsuborder = ($afterlinkoldsuborder)? $afterlinkoldsuborder : 0;

        //Einfügen des Links
        //neue Sortorder des Kurses ermitteln:
        if ($aftercourse) {

            $newsortorder = $aftercourse->sortorder;

        } else {

            $sql = "SELECT min(sortorder) FROM {course} where category = :categoryid";
            $newsortorder = $DB->get_field_sql($sql, array('categoryid' => $categoryid));

            if (!$newsortorder) {//Kategorie enthält keine Kurse, sortierung der Kategorie nehmen...
                $sql = "SELECT max(sortorder) FROM {course_categories} where id = :categoryid";
                $newsortorder = $DB->get_field_sql($sql, array('categoryid' => $categoryid));
            }
        }

        //Liste aller Links zur gleichen $aftercourseid holen
        $sql = "SELECT * FROM {category_course_link} WHERE aftercourseid = :aftercourseid ".
                "AND category = :category ORDER BY suborder";
        $links_after_same_course = $DB->get_records_sql($sql, array('aftercourseid' => $aftercourseid, 'category' => $categoryid));


        //gibt es Links vor der aktuellen Einfügeposition (=> ja, wenn $afterlink != false)
        $afterlink = $DB->get_record('category_course_link', array('id' => $afterlinkid, 'aftercourseid' => $aftercourseid));
        $newsuborder = 1;


        if ($afterlink) {//es gibt Links vor der Einfügeposition und möglicherweise auch danach

            //die Kursliste in Links davor und Links danach aufsplitten
            $links_pre_newlink = array(); //Links vor der Einfügeposition
            $links_after_newlink = array(); //LInks nach der Einfüge position

            $insertpre = true;

            foreach ($links_after_same_course as $key => $link) {

                if ($insertpre) {
                    $links_pre_newlink [$key] = $link;
                } else {
                    $links_after_newlink[$key] = $link;
                }

                if ($insertpre and ($link->id == $afterlink->id)) {
                    $insertpre = false;
                }
            }

            if (count($links_pre_newlink) > 0) {

                $first = end($links_pre_newlink);
                $newsuborder = $first->suborder + 1;
            }

        } else {//der link wird vorangestellt, es gibt nur Links danach

            $links_pre_newlink = array();
            $links_after_newlink = $links_after_same_course;
        }

        // Links hinter der Einfügeposition nach hinten schieben...
        if (count($links_after_newlink) > 0) {

            $whereset = "(".implode(",", array_keys($links_after_newlink)).")";

            $sql = "UPDATE {category_course_link} SET suborder = suborder + 1 WHERE id IN $whereset";
            $DB->execute($sql);
        }


        //Link einfügen und aus der ursprünglichen Kategorie entfernen (dort wird Reihenfolge durch fix_course_sortorder korrigiert.)
        $sql = "UPDATE {category_course_link} set sortorder = :sortorder, category = :category, ".
                "aftercourseid = :aftercourseid, suborder = :suborder WHERE id = :id";
        $DB->execute($sql, array('sortorder' => $newsortorder, 'category' => $categoryid,
                'id' => $movelinkid, 'aftercourseid' => $aftercourseid, 'suborder' => $newsuborder));

        fix_course_sortorder();
        directorylisting::_fix_courselink_sortorder($categoryid);
        if ($categoryid != $linktomove->category) directorylisting::_fix_courselink_sortorder($linktomove->category);

        //Bestätigung
        $link = $DB->get_record('course', array('id' => $movelinkid));
        directorylisting::_displayMessage('informationbox', get_string('link_moved', 'block_custom_category', "({$link->id})"));

        $SESSION->linktomove = 0;
        return true;
    }


    /** erzeugt einen Link auf einen Kurs, falls ein solcher in der angegebenen
     * Kategorie noch nicht existiert
     *
     * @global moodle_database $DB
     * @param int $courseid, die ID des zu verlinkenden Kurses
     * @param int $categoryid, die ID des Kursbereiches
     * @param int $aftercourseid, 0 oder die ID des vorangegangen Kurses
     * @param int $aftercourselinkid, 0 oder die ID des letzten vorangeganenen Kurslinks
     * @return void
     */
    protected static function _doCreateCourseLink($courseid, $categoryid, $aftercourseid, $aftercourselinkid) {
        global $DB, $SESSION;

        if (!confirm_sesskey()) return;

        if (directorylisting::_linkExists($courseid, $categoryid)) return;

        //hole Kurse
        $sql = "SELECT * FROM {course} WHERE id IN (:courseid, :aftercourseid)";
        $courses = $DB->get_records_sql($sql, array('courseid' => $courseid, 'aftercourseid' => $aftercourseid));

        //zu verlinkender Kurs ist ungültig
        if (!isset($courses[$courseid])) return;

        //Liste aller Links zur gleichen $aftercourseid holen
        $sql = "SELECT * FROM {category_course_link} WHERE aftercourseid = :aftercourseid ".
                "AND category = :category ORDER BY suborder";
        $links_after_same_course = $DB->get_records_sql($sql, array('aftercourseid' => $aftercourseid, 'category' => $categoryid));

        //gibt es Links vor der aktuellen Einfügeposition (=> ja, wenn $afterlink != false)
        $afterlink = $DB->get_record('category_course_link', array('id' => $aftercourselinkid, 'aftercourseid' => $aftercourseid));
        $newsuborder = 1;

        if ($afterlink) {//es gibt Links vor der Einfügeposition und möglicherweise auch danach

            //die Kursliste in Links davor und Links danach aufsplitten
            $links_pre_newlink = array(); //Links vor der Einfügeposition
            $links_after_newlink = array(); //LInks nach der Einfüge position

            $insertpre = true;

            foreach ($links_after_same_course as $key => $link) {

                if ($insertpre) {
                    $links_pre_newlink [$key] = $link;
                } else {
                    $links_after_newlink[$key] = $link;
                }

                if ($insertpre and ($link->id == $afterlink->id)) {
                    $insertpre = false;
                }
            }

            if (count($links_pre_newlink) > 0) {

                $first = end($links_pre_newlink);
                $newsuborder = $first->suborder + 1;
            }

        } else {//der link wird vorangestellt, es gibt nur Links danach

            $links_pre_newlink = array();
            $links_after_newlink = $links_after_same_course;
        }

        // Links hinter der Einfügeposition nach hinten schieben...
        if (count($links_after_newlink) > 0) {

            $whereset = "(".implode(",", array_keys($links_after_newlink)).")";

            $sql = "UPDATE {category_course_link} SET suborder = suborder + 1 WHERE id IN $whereset";
            $DB->execute($sql);
        }

        //Action
        $courselink = new stdClass();
        $courselink->courseid = $courseid;
        $courselink->category = $categoryid;
        $courselink->aftercourseid = $aftercourseid;
        $courselink->suborder = $newsuborder;
        $courselink->timecreated = time();
        $courselink->timemodified = time();

        $DB->insert_record('category_course_link', $courselink);

        directorylisting::_fix_courselink_sortorder($categoryid);
        //Message
        directorylisting::_displayMessage('informationbox', get_string('courselink_created', 'block_custom_category'));

        $SESSION->coursetolink = 0;
    }

    /** löscht bei gültiger SESSION-ID den Kurslink mit der ID $courselinkid
     *
     * @global moodle_database $DB
     * @param int $courselinkid, die ID der zu löschenden Kurslinks
     * @return void
     */
    protected static function _doDeleteCourseLink($courselinkid) {
        global $DB;

        if (!confirm_sesskey()) return;

        $sql = "DELETE FROM {category_course_link} WHERE id= :courselinkid";
        $DB->execute($sql, array('courselinkid' => $courselinkid));

        directorylisting::_displayMessage('informationbox', get_string('courselink_deleted', 'block_custom_category'));
    }

    /** erstellt eine Zielmarke zu der der Kurs $coursetomove hin verschoben werden kann
     *
     * @param array $position, enthält die IDs der unmittelbar vorangegangenen
     * Kurslinks und Kurse
     */
    public function _printCreateCourseLinkMarker($position) {

        if (!$this->_coursetolink) return;

        $categoryid = $this->_category->id;
        $courseid = $this->_coursetolink->id;

        if (directorylisting::_linkExists($courseid, $categoryid)) return;

        $url = new moodle_url('/course/category.php',
                array('id' => $categoryid, 'action' => 'createcourselink', 'courseid' => $courseid,
                        'aftercourseid' => $position['aftercourseid'], 'aftercourselinkid' => $position['afterlinkid'], 'sesskey' => sesskey()));
        echo '<tr><td colspan="3">';

        echo "<span class=\"courselink-target\"><a href=\"{$url}\" title=\"".get_string('targettitle', 'block_custom_category')."\">";
        //echo get_string('targetlink', 'block_custom_category');
        $str = "aftercourse:".$position['aftercourseid']." afterlink ".$position['afterlinkid'];
        echo $str;
        echo "</a></span>";
        echo '</td></tr>';
    }

    public function _printMoveCourseLinkMarker($position) {

        if (!$this->_linktomove) return;

        $categoryid = $this->_category->id;
        $linkid = $this->_linktomove->id;

        //if (directorylisting::_linkExists($courseid, $categoryid)) return;

        $url = new moodle_url('/course/category.php',
                array('id' => $categoryid, 'action' => 'movecourselink', 'movelinkid' => $linkid,
                        'aftercourseid' => $position['aftercourseid'], 'aftercourselinkid' => $position['afterlinkid'], 'sesskey' => sesskey()));
        echo '<tr><td colspan="3">';

        echo "<span class=\"movelink-target\"><a href=\"{$url}\" title=\"".get_string('targettitle', 'block_custom_category')."\">";
        //echo get_string('targetlink', 'block_custom_category');
        $str = "aftercourse:".$position['aftercourseid']." afterlink ".$position['afterlinkid'];
        echo $str;
        echo "</a></span>";
        echo '</td></tr>';
    }

    //--- Kurslinks
    //--- Kursliste ----------------------------------------------------------------

    /** Adminhilfe: gibt alle zugewiesenen Rollen im aktuellen Kontext aus */
    public function print_RolesInfo($context) {
        global $CFG, $DB, $OUTPUT, $PAGE;
        // Show UI for choosing a role to assign.

        if (!has_capability('moodle/role:assign', $context)) return;

        // Print a warning if we are assigning system roles.
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            echo $OUTPUT->box(get_string('globalroleswarning', 'role'));
        }

        // These are needed early because of tabs.php
        list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
        $overridableroles = get_overridable_roles($context, ROLENAME_BOTH);

        echo html_writer::tag('div', '', array('id' => 'category-roles-left'));
        echo html_writer::tag('div', '', array('id' => 'category-roles-right'));
        echo "<div id = \"category-roles-middle\">";
        // Print instruction
        echo $OUTPUT->heading(get_string('roles', 'role'), 2);

        $url = new moodle_url('/admin/roles/assign.php', array('contextid' => $context->id));
        $strassignrole = get_string('assignrole', 'role');
        echo $OUTPUT->action_icon($url, new pix_icon('i/roles', get_string('assignrole', 'role')));
        echo "&nbsp;";
        echo html_writer::link($url, $strassignrole, array("alt"=>$strassignrole, "title" => $strassignrole));
        echo "<hr />";

        // Get the names of role holders for roles with between 1 and MAX_USERS_TO_LIST_PER_ROLE users,
        // and so determine whether to show the extra column.
        $roleholdernames = array();
        $strmorethanmax = get_string('morethan', 'role', MAX_USERS_TO_LIST_PER_ROLE);
        $showroleholders = false;
        foreach ($assignableroles as $roleid => $notused) {
            $roleusers = '';
            if (0 < $assigncounts[$roleid] && $assigncounts[$roleid] <= MAX_USERS_TO_LIST_PER_ROLE) {
                $roleusers = get_role_users($roleid, $context, false, 'u.id, u.lastname, u.firstname');
                if (!empty($roleusers)) {
                    $strroleusers = array();
                    foreach ($roleusers as $user) {
                        $strroleusers[] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '" >' . fullname($user) . '</a>';
                    }
                    $roleholdernames[$roleid] = implode('<br />', $strroleusers);
                    $showroleholders = true;
                }
            } else if ($assigncounts[$roleid] > MAX_USERS_TO_LIST_PER_ROLE) {
                $assignurl = new moodle_url($PAGE->url, array('roleid'=>$roleid));
                $roleholdernames[$roleid] = '<a href="'.$assignurl.'">'.$strmorethanmax.'</a>';
            } else {
                $roleholdernames[$roleid] = '';
            }
        }

        // Prin$table->tablealign = 'center';t overview table
        $table = new html_table();
        $table->wrap = array('nowrap', '', 'nowrap');
        $table->align = array('left', 'left', 'center');
        if ($showroleholders) {
            $table->headspan = array(1, 1, 2);
            $table->wrap[] = 'nowrap';
            $table->align[] = 'left';
        }

        foreach ($assignableroles as $roleid => $rolename) {
            if (($assigncounts[$roleid]) == 0) continue;
            $description = format_string($DB->get_field('role', 'description', array('id'=>$roleid)));
            $assignurl = new moodle_url($CFG->wwwroot.'/admin/roles/assign.php', array('contextid'=>$context->id ,'roleid'=>$roleid));
            $row = array('<b>'.$rolename.':</b>');
            if ($showroleholders) {
                $row[] = $roleholdernames[$roleid];
            }
            $table->data[] = $row;
        }

        echo html_writer::table($table);
        echo "</div>";
    }
}
