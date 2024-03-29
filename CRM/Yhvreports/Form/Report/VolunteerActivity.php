<?php

require_once __DIR__ . '/yhvreports.variables.php';

use CRM_Yhvreports_ExtensionUtil as E;

class CRM_Yhvreports_Form_Report_VolunteerActivity extends CRM_Report_Form_ActivitySummary {

  protected $_customGroupExtends = ['Activity'];

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_contact']['fields']['external_identifier'] = [
      'title' => ts('External Identifier'),
    ];
    $this->_columns['civicrm_contact']['filters']['external_identifier'] = [
      'title' => ts('External Identifier'),
    ];
    $this->_columns['civicrm_activity']['fields']['volunteers_no'] = [
      'title' => ts('# of Volunteers'),
      'dbAlias' => 'COUNT(target_activity.contact_id)',
    ];
    $this->_columns['civicrm_activity']['fields']['volunteers_unique_no'] = [
      'title' => ts('# of Unique Volunteers'),
      'dbAlias' => 'COUNT(DISTINCT target_activity.contact_id)',
    ];
    $this->_columns['civicrm_activity']['fields']['duration']['title'] = ts('Volunteer Hours');
    $volunteeringTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => VOLUNTEERING_CG, 'return' => 'table_name']);
    foreach($this->_columns[$volunteeringTableName]['fields'] as $column => $field) {
      if ($column == WORK_HOURS_CF) {
        $this->_columns[$volunteeringTableName]['fields'][$column]['dbAlias'] = sprintf("SUM(%s)", $field['name']);
      }
      $this->_columns[$volunteeringTableName]['group_bys'][$field['name']] = [
        'title' => $field['title'],
        'dbAlias' => "value_volunteering_12_civireport.{$field['name']}",
      ];
    }
  }

  public function where($durationMode = FALSE) {
    parent::where($durationMode);
    $this->_where .= " AND value_volunteering_12_civireport.location_59 IS NOT NULL AND value_volunteering_12_civireport.location_59 != '' AND value_volunteering_12_civireport.division_58 IS NOT NULL AND value_volunteering_12_civireport.division_58 != '' AND value_volunteering_12_civireport.funder_57 IS NOT NULL AND value_volunteering_12_civireport.funder_57 != ''";
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = [];
    $this->groupByStat($statistics);

    $this->filterStat($statistics);

    return $statistics;
  }

  /**
   * Group the fields.
   *
   * @param bool $includeSelectCol
   */
  public function groupBy($includeSelectCol = TRUE) {
    $this->_groupBy = [];
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys'])) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              if (!empty($field['chart'])) {
                $this->assign('chartSupported', TRUE);
              }
              if (!empty($table['group_bys'][$fieldName]['frequency']) &&
                !empty($this->_params['group_bys_freq'][$fieldName])
              ) {

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  ['year']
                )) {
                  $append = '';
                }
                $this->_groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupBy[] = $field['dbAlias'];
              }
            }
          }
        }
      }
      $groupBy = $this->_groupBy;
      foreach ($groupBy as $value) {
        $this->_where .= " AND {$value} IS NOT NULL ";
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy);
      if (strstr($this->_groupBy, 'value_volunteering_12_civireport') && !strstr($this->_from, 'value_volunteering_12_civireport')) {
        $this->_from .= "
        LEFT JOIN civicrm_value_volunteering_12 value_volunteering_12_civireport ON value_volunteering_12_civireport.entity_id = activity_civireport.id ";
      }
    }
    else {
      $groupBy = "{$this->_aliases['civicrm_activity']}.id";
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_activity']}.id ";
    }
    if ($includeSelectCol) {
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
    $this->_groupBy .= " WITH ROLLUP ";
  }

  public function modifyColumnHeaders() {
    if (array_key_exists('civicrm_activity_id_count', $this->_columnHeaders)) {
      $volunteerNoHeader = $this->_columnHeaders['civicrm_activity_volunteers_no'];
      $uniqueVolunteerNoHeader = $this->_columnHeaders['civicrm_activity_volunteers_unique_no'];
      unset(
        $this->_columnHeaders['civicrm_activity_volunteers_no'],
        $this->_columnHeaders['civicrm_activity_volunteers_unique_no'],
        $this->_columnHeaders['civicrm_activity_id_count'],
        $this->_columnHeaders['civicrm_activity_activity_type_id'],
        $this->_columnHeaders['civicrm_activity_status_id']
      );
      $this->_columnHeaders['civicrm_activity_volunteers_no'] = $volunteerNoHeader;
      $this->_columnHeaders['civicrm_activity_volunteers_unique_no'] = $uniqueVolunteerNoHeader;
    }
  }

  public function alterCustomDataDisplay(&$rows) {
    parent::alterCustomDataDisplay($rows);
    foreach ($rows as $rowNum => &$row) {
      if (!empty($row['civicrm_value_volunteering_12_custom_57']) && !empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_56'])) {
        $rows[$rowNum]['civicrm_value_volunteering_12_custom_56'] =  '<b>Program Subtotal</b>';
      }
      if (!empty($row['civicrm_value_volunteering_12_custom_57']) && !empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_58'])) {
        $rows[$rowNum]['civicrm_value_volunteering_12_custom_58'] = '<b>Divison Subtotal</b>';
      }
      if (!empty($row['civicrm_value_volunteering_12_custom_57']) && empty($row['civicrm_value_volunteering_12_custom_59']) && (empty($row['civicrm_value_volunteering_12_custom_56']) || empty($row['civicrm_value_volunteering_12_custom_58']))) {
        $rows[$rowNum]['civicrm_value_volunteering_12_custom_59'] =  '<b>Location Subtotal</b>';
      }
      elseif (!empty($row['civicrm_contact_id']) && empty($row['civicrm_value_volunteering_12_custom_57']) && empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_56'])) {
        $rows[$rowNum]['civicrm_value_volunteering_12_custom_57'] = 'Grand Total';
        if (!empty($rows[$rowNum]['civicrm_value_volunteering_12_custom_58'])) {
          $rows[$rowNum]['civicrm_value_volunteering_12_custom_58'] = NULL;
        }
      }
    }
    $this->assign('rows1', $rows);
  }

  /**
   * Generate from clause for when calculating activity durations.
   */
  public function activityDurationFrom() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $this->_from = "
      FROM civicrm_activity {$this->_aliases['civicrm_activity']}
              LEFT JOIN civicrm_activity_contact target_activity
                     ON {$this->_aliases['civicrm_activity']}.id = target_activity.activity_id AND
                        target_activity.record_type_id = {$targetID}
              LEFT JOIN civicrm_contact contact_civireport
                     ON target_activity.contact_id = contact_civireport.id
              {$this->_aclFrom}";

    // Email table is needed if sorting by Email.
    $this->joinEmailFromContact();
    $this->customDataFrom();
  }

}
