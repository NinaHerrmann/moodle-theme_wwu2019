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
 * Renderer for WWU 2019 Theme
 *
 * @package    theme_wwu2019
 * @copyright  2019 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_wwu2019\output;

use context_course;
use moodle_page;
use moodle_url;
use navigation_node;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderer for WWU 2019 Theme
 *
 * @package    theme_wwu2019
 * @copyright  2019 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer {

    /**
     * core_renderer constructor.
     * Overrides parent to require admin tree init in $PAGE->settingsnav
     *
     * @param moodle_page $page the page we are doing output for.
     * @param string $target one of rendering target constants.
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        navigation_node::require_admin_tree();
    }

    /**
     * Renders logo heading.
     * @return string HTML string.
     */
    public function logo_header() {
        global $CFG;

        $templatecontext = [
                'wwwroot' => $CFG->wwwroot,
                'logo' => $CFG->wwwroot . '/theme/wwu2019/pix/learnweb_logo.svg'
        ];
        return $this->render_from_template('theme_wwu2019/logo_header', $templatecontext);
    }

    /**
     * Renders main menu.
     * @return string HTML string.
     */
    public function main_menu() {
        global $CFG, $USER;

        $mainmenu = [];

        // Add MyCourses menu.
        if (count($courses = $this->get_courses())) {
            $mainmenu[] = [
                    'name' => get_string('mycourses', 'theme_wwu2019'),
                    'hasmenu' => true,
                    'menu' => $this->add_breakers($courses),
                    'icon' => (new \pix_icon('i/graduation-cap', ''))->export_for_pix()
            ];
        }

        // Add This Course menu.
        if (count($thiscourse = $this->get_activity_menu())) {
            $mainmenu[] = [
                    'name' => get_string('thiscourse', 'theme_wwu2019'),
                    'hasmenu' => true,
                    'menu' => $this->add_breakers($thiscourse),
                    'icon' => (new \pix_icon('i/book', ''))->export_for_pix()
            ];
        }

        // Add Administration menu.
        if (count($settings = $this->settingsnav_for_template($this->page->settingsnav->children))) {
            $mainmenu[] = [
                    'name' => get_string('pluginname', 'block_settings'),
                    'hasmenu' => true,
                    'menu' => $this->add_breakers($settings),
                    'icon' => (new \pix_icon('i/cogs', ''))->export_for_pix()
            ];
        }

        // Add dashboard.
        $mainmenu[] = [
                'name' => get_string('dashboard', 'theme_wwu2019'),
                'hasmenu' => false,
                'menu' => null,
                'href' => $CFG->wwwroot . '/my/',
        ];

        $userpic = new \user_picture($USER);

        $usermenu = [
            'name' => sprintf('%.1s. %s', $USER->firstname, $USER->lastname),
            'pic' => $userpic->get_url($this->page)->out(true)
        ];

        $templatecontext = [
                'left-menu' => $mainmenu,
                'wwwroot' => $CFG->wwwroot,
                'right-menu-icons' => [
                        [
                                'icon' => (new \pix_icon('i/cogs', ''))->export_for_pix(),
                        ],
                        [
                                'icon' => (new \pix_icon('i/book', ''))->export_for_pix(),
                        ],
                        [
                                'icon' => (new \pix_icon('i/cogs', ''))->export_for_pix(),
                        ]
                ],
                'user-menu' => $usermenu
        ];

        $this->page->requires->js_call_amd('theme_wwu2019/menu', 'init');
        return $this->render_from_template('theme_wwu2019/menu', $templatecontext);
    }

    /**
     * Puts the given $nodecollection into a format properly usable in templates.
     *
     * @param \navigation_node_collection $nodecollection
     * @return array the array usable in templates.
     */
    private function settingsnav_for_template(\navigation_node_collection $nodecollection) {
        $items = [];
        $navbranchicon = (new \pix_icon('i/navigationbranch', ''))->export_for_pix();
        $navitemicon = (new \pix_icon('i/navigationitem', ''))->export_for_pix();
        foreach ($nodecollection as $node) {
            if ($node->display) {

                $templateformat = array(
                        'name' => $node->get_content()
                );

                if ($node->icon && !$node->hideicon) {
                    if ($node->icon->pix == 'i/navigationitem' && $node->has_children()) {
                        $templateformat['icon'] = $navbranchicon;
                    } else {
                        $templateformat['icon'] = $node->icon->export_for_pix();
                    }
                } else {
                    $templateformat['icon'] = $navitemicon;
                }

                if ($node->has_children()) {
                    $templateformat['hasmenu'] = true;
                    $templateformat['menu'] = $this->settingsnav_for_template($node->children);
                } else {
                    $templateformat['hasmenu'] = false;
                    $templateformat['menu'] = null;
                }
                if ($node->has_action() && !$node->has_children()) {
                    $templateformat['href'] = $node->action->out(false);
                }
                $items[] = $templateformat;
            }
        }
        return $items;
    }

    /**
     * Adds breakers for submenu items, which causes the items to be divided equally in two or three column menus.
     * @param array $menuitems
     * @return array The menuitems with breakers.
     */
    private function add_breakers(array $menuitems) {
        if (count($menuitems) == 0) {
            return array();
        }
        $columntwo = intval(ceil(count($menuitems) / 2.0) - 1);
        $columnthree1 = intval(ceil(count($menuitems) / 3.0) - 1);
        $columnthree2 = intval(min(ceil(count($menuitems) / 3.0) * 2 - 1, count($menuitems) - 1));
        $menuitems[$columntwo]['breaker'] = ['c2'];
        if (array_key_exists('breaker', $menuitems[$columnthree1])) {
            $menuitems[$columnthree1]['breaker'][] = 'c3';
        } else {
            $menuitems[$columnthree1]['breaker'] = ['c3'];
        }
        if (array_key_exists('breaker', $menuitems[$columnthree2])) {
            $menuitems[$columnthree2]['breaker'][] = 'c3';
        } else {
            $menuitems[$columnthree2]['breaker'] = ['c3'];
        }

        return $menuitems;
    }

    /**
     * Gets and sorts all of the user's courses into terms.
     * @return array The sorted courses, ready for use in templates.
     */
    private function get_courses() {

        $courses = enrol_get_my_courses(array(), 'c.startdate DESC');
        $terms = [];

        $calendaricon = (new \pix_icon('i/calendar', ''))->export_for_pix();
        $courseicon = (new \pix_icon('i/graduation-cap', ''))->export_for_pix();

        $termindependentlimit = new \DateTime("2000-00-00");

        foreach ($courses as $course) {
            $coursestart = new \DateTime();
            $coursestart->setTimestamp($course->startdate);

            $year = (int) $coursestart->format('Y');
            $term = 0;
            $istermindependent = false;

            $term0start = new \DateTime("$year-04-01");
            $term1start = new \DateTime("$year-10-01");

            if ($coursestart < $termindependentlimit) {
                $istermindependent = true;
            } else if ($coursestart < $term0start) {
                $year--;
                $term = 1;
            } else if ($coursestart < $term1start) {
                $term = 0;
            } else {
                $term = 1;
            }

            $termid = $istermindependent ? 0 : $year . '_' . $term;
            if (!array_key_exists($termid, $terms)) {
                if ($istermindependent) {
                    $name = get_string('termindependent', 'theme_wwu2019');
                } else {
                    if ($term == 0) {
                        $name = 'SoSe ' . $year;
                    } else {
                        $name = 'WiSe ' . $year . '/' . ($year + 1);
                    }
                }
                $terms[$termid] = [
                    'name' => $name,
                    'icon' => $calendaricon,
                    'hasmenu' => true,
                    'menu' => []
                ];
            }

            $terms[$termid]['menu'][] = [
                'name' => $course->shortname,
                'href' => (new moodle_url('/course/view.php', array('id' => $course->id)))->out(false),
                'icon' => $courseicon,
                'hasmenu' => false,
                'menu' => null
            ];
        }
        return array_values($terms);
    }

    /**
     * Returns all activity types of a course.
     * @return array The activity types, ready for use in templates.
     */
    private function get_activity_menu() {
        $activities = [];
        if (!isguestuser()) {
            if (in_array($this->page->pagelayout, array('course', 'incourse', 'report', 'admin', 'standard')) &&
                    (!empty($this->page->course->id) && $this->page->course->id > 1)) {

                $activities[] = [
                        'name' => get_string('participants'),
                        'icon' => (new \pix_icon('i/users', ''))->export_for_pix(),
                        'hasmenu' => false,
                        'menu' => null,
                        'href' => (new moodle_url('/user/index.php', array('id' => $this->page->course->id)))->out(false)
                ];

                $context = context_course::instance($this->page->course->id);
                if (((has_capability('gradereport/overview:view', $context) ||
                                        has_capability('gradereport/user:view', $context)) &&
                                $this->page->course->showgrades) || has_capability('gradereport/grader:view', $context)) {
                    $activities[] = [
                            'name' => get_string('grades'),
                            'icon' => (new \pix_icon('i/grades', ''))->export_for_pix(),
                            'hasmenu' => false,
                            'menu' => null,
                            'href' => (new \moodle_url('/grade/report/index.php', array('id' => $this->page->course->id)))
                                    ->out(false)
                    ];
                }
                $activities[] = [
                        'name' => get_string('badgesview', 'badges'),
                        'icon' => (new \pix_icon('i/trophy', ''))->export_for_pix(),
                        'hasmenu' => false,
                        'menu' => null,
                        'href' => (new moodle_url('/badges/view.php', array('id' => $this->page->course->id, 'type' => 2)))
                                ->out(false)
                ];

                $data = $this->get_course_activities();
                foreach ($data as $modname => $modfullname) {
                    if ($modname === 'resources') {
                        $icon = $this->pix_icon('icon', '', 'mod_page', array('class' => 'icon'));
                        $activities[] = [
                                'name' => $modfullname,
                                'icon' => (new \pix_icon('icon', '', 'mod_page'))->export_for_pix(),
                                'hasmenu' => false,
                                'menu' => null,
                                'href' => (new moodle_url('/course/resources.php', array('id' => $this->page->course->id)))
                                        ->out(false)
                        ];
                    } else {
                        $activities[] = [
                                'name' => $modfullname,
                                'icon' => (new \pix_icon('icon', '', $modname))->export_for_pix(),
                                'hasmenu' => false,
                                'menu' => null,
                                'href' => (new moodle_url("/mod/$modname/index.php", array('id' => $this->page->course->id)))
                                        ->out(false)
                        ];
                    }
                }
            }
        }
        return $activities;
    }

    /**
     * Collections information about the course's activities.
     * @return array in format $modname => $modfullname
     */
    private function get_course_activities() {
        // A copy of block_activity_modules.
        $course = $this->page->course;
        $modinfo = get_fast_modinfo($course);
        $course = \course_get_format($course)->get_course();
        $modfullnames = array();
        $archetypes = array();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if (((!empty($course->numsections)) and ($section > $course->numsections)) or (empty($modinfo->sections[$section]))) {
                // This is a stealth section or is empty.
                continue;
            }
            foreach ($modinfo->sections[$thissection->section] as $modnumber) {
                $cm = $modinfo->cms[$modnumber];
                // Exclude activities which are not visible or have no link (=label).
                if (!$cm->uservisible or !$cm->has_view()) {
                    continue;
                }
                if (array_key_exists($cm->modname, $modfullnames)) {
                    continue;
                }
                if (!array_key_exists($cm->modname, $archetypes)) {
                    $archetypes[$cm->modname] = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                }
                if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
                    if (!array_key_exists('resources', $modfullnames)) {
                        $modfullnames['resources'] = get_string('resources');
                    }
                } else {
                    $modfullnames[$cm->modname] = $cm->modplural;
                }
            }
        }
        \core_collator::asort($modfullnames);

        return $modfullnames;
    }

}
