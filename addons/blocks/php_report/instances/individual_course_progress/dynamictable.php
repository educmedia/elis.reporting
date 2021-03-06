<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    elis
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once('../../../../config.php');
global $CFG;

// Require all related classes to access session variables
require_once($CFG->dirroot.'/blocks/php_report/instances/individual_course_progress/individual_course_progress_report.class.php');
require_once($CFG->dirroot.'/blocks/php_report/php_report_base.php');
require_once($CFG->dirroot.'/blocks/php_report/php_report_block.class.php');
require_once($CFG->dirroot.'/blocks/php_report/lib/filtering.php');
require_once($CFG->dirroot.'/curriculum/lib/filtering/lib.php');
require_once($CFG->dirroot.'/curriculum/lib/filtering/custom_field_multiselect.php');
require_once($CFG->dirroot.'/curriculum/lib/filtering/custom_field_multiselect_data.php');
require_once($CFG->dirroot.'/curriculum/lib/filtering/custom_field_multiselect_values.php');

$reportname = 'individual_course_progress';
$lang_file = 'rlreport_' . $reportname;

$block_id = required_param('block_id', PARAM_TEXT);
$action = required_param('action', PARAM_TEXT);
$fieldid = optional_param('fieldid', null, PARAM_TEXT);
$fieldname = optional_param('fieldname', null, PARAM_TEXT);
$fieldnamelist = optional_param('fieldnamelist', null, PARAM_TEXT);
$fieldidlist = optional_param('fieldidlist', null, PARAM_TEXT);
$scheduled = optional_param('scheduled', 0, PARAM_INT);

//Unserialize fieldidlist to check against field list
if (isset($fieldidlist) && ($fieldidlist !== null)) {
    $fieldidlist = unserialize(base64_decode($fieldidlist));
}
if (isset($fieldnamelist) && ($fieldnamelist !== null)) {
    $fieldnamelist = unserialize(base64_decode($fieldnamelist));
}
if (isset($fieldname) && ($fieldname !== null)) {
    $fieldname = unserialize(base64_decode($fieldname));
}

// Update the field id and name lists based on the action
fix_object($SESSION->php_reports[$block_id]);

// Create a new data class for custom fields
$multi_filter = new generalized_filter_custom_field_multiselect_data($block_id,$fieldidlist,$fieldnamelist);
// Apply the action to the list of field ids and field names
$multi_filter->update_field_list($action, $fieldid, $fieldname, $fieldidlist, $fieldnamelist,$scheduled);

// Set custom field id and name arrays to the updated id and name lists
$fieldidlist = $multi_filter->_fieldidlist;
$fieldnamelist = $multi_filter->_fieldnamelist;

// We need to serialize fieldidlist and fieldnamelist and fieldname for all form elements
$serialized_fieldidlist = '';
$serialized_fieldnamelist = '';
$serialized_fieldname = '';

if (isset($fieldidlist) && !empty($fieldidlist)) {
    // Reindex first
    $fieldidlist = array_merge($fieldidlist);
    // Get custom field names if we have a list of custom ids and no names
    if ((!isset($fieldnamelist) || empty($fieldnamelist)) &&
        !($scheduled && $action == 'init')) {
        $multiselect_filter_obj = $SESSION->php_reports[$block_id]->inner_report->multiselect_filter;
        $multiselect_filter_obj->options['reportname'] = $reportname;
        $multi_filter_values = new generalized_filter_custom_field_multiselect_values($multiselect_filter_obj->uniqueid,
                                                                                      $multiselect_filter_obj->tablealias,
                                                                                      $multiselect_filter_obj->fieldname,
                                                                                      $multiselect_filter_obj->displayname,
                                                                                      $multiselect_filter_obj->advanced,
                                                                                      $multiselect_filter_obj->type,
                                                                                      $multiselect_filter_obj->options,
                                                                                      base64_encode(serialize($fieldidlist)),
                                                                                      $fieldnamelist,
                                                                                      $action);
        $multi_filter_values->get_names();
        $fieldidlist = $multi_filter_values->_fieldidlist;
        $fieldnamelist = $multi_filter_values->_fieldnamelist;
    }
    $serialized_fieldidlist = base64_encode(serialize($fieldidlist));
}


if (isset($fieldnamelist)) {
    $fieldnamelist = array_merge($fieldnamelist);
    $serialized_fieldnamelist = base64_encode(serialize($fieldnamelist));
}

if (isset($fieldname)) {
    $serialized_fieldname = base64_encode(serialize($fieldname));
}

$table = new stdClass();
$table->head = array(get_string('course_field_title',$lang_file));

$table->class = 'individual_course_progress';

$subtable = new stdClass();

$subtable->head = array(get_string('course_field',$lang_file),
                     get_string('display_order',$lang_file),
                     get_string('remove_fields',$lang_file)
    );
$subtable->class = '';
$subtable->width = '75%';

// For each selected custom field, display the fieldname,
// icons to reorder and icon to remove the field
if (is_array($fieldidlist)) {
    $count = 0;
    $numfields = count($fieldidlist)-1;
    foreach ($fieldidlist as $field_id) {
        $fieldname = $fieldnamelist[$count];

        // Set up reordering links
        $moveup = get_string('move_up',$lang_file);
        $movedown = get_string('move_down',$lang_file);
        if ($count < $numfields) {
            $image_filename = 'arrow-down.png';
        } else {
            $image_filename = 'arrow-blank.png';
            $move_down = '';
        }
        $moveupdown = '<input type="image" src="'.$CFG->wwwroot . '/blocks/php_report/pix/'. $image_filename . '" '.
                ' onclick="customfields_updateTable(\''.$block_id.'\',\'down\',\''.
                $CFG->wwwroot.'/blocks/php_report/instances/individual_course_progress/\',\''.
                $field_id.'\',\''.$serialized_fieldname.'\',\''.$serialized_fieldidlist.'\',\''.
                $serialized_fieldnamelist.'\');return false;" '.
                'alt="'.$movedown.'" title="'.$movedown.'" />';
        if ($count > 0) {
            $image_filename = 'arrow-up.png';
        } else {
            $image_filename = 'arrow-blank.png';
            $move_up = '';
        }
        $moveupdown .= '<input type="image" src="'.$CFG->wwwroot . '/blocks/php_report/pix/'. $image_filename . '" '.
                ' onclick="customfields_updateTable(\''.$block_id.'\',\'up\',\''.
                $CFG->wwwroot.'/blocks/php_report/instances/individual_course_progress/\',\''.
                $field_id.'\',\''.$serialized_fieldname.'\',\''.$serialized_fieldidlist.'\',\''.
                $serialized_fieldnamelist.'\');return false;" '.
                'alt="'.$moveup.'" title="'.$moveup.'" />';

        // Set up remove link
        $image_filename = 'remove.png';
        $removethis = get_string('remove_this',$lang_file);
        $remove = '<input type="image" src="'.$CFG->wwwroot . '/blocks/php_report/pix/'. $image_filename . '" '.
                ' onclick="customfields_updateTable(\''.$block_id.'\',\'remove\',\''.
                $CFG->wwwroot.'/blocks/php_report/instances/individual_course_progress/\',\''.
                $field_id.'\',\''.$serialized_fieldname.'\',\''.$serialized_fieldidlist.'\',\''.
                $serialized_fieldnamelist.'\');return false;" '.
                'alt="'.$removethis.'" title="'.$removethis.'" />';

        $subtable->data[] = array($fieldname,$moveupdown,$remove);
        $subtable->rowclass[] = 'course_field_row select_all_row';
        $count++;
    }
}

echo print_table($table, true);
echo print_table($subtable,true);

// Now append our serialized fields to this output for the javascript to update the form
echo ':'.$serialized_fieldidlist.':'.$serialized_fieldnamelist.':';

/* Do the serialize/unserialize trick to enable all the functionality of the class
 * @param   object  &$object    object to fix
 * @return  object  $object     fixed object
 */
function fix_object(&$object) {
    if (!is_object ($object) && gettype ($object) == 'object') {
        $object = unserialize (serialize ($object));
    }
    return $object;
}
