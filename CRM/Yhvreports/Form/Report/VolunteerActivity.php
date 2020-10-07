<?php

require_once __DIR__ . '/yhvreports.variables.php';

use CRM_Yhvreports_ExtensionUtil as E;

class CRM_Yhvreports_Form_Report_VolunteerActivity extends CRM_Report_Form_ActivitySummary {

  protected $_customGroupExtends = ['Activity'];

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_activity']['fields']['volunteers_no'] = [
      'title' => ts('# of Volunteers'),
      'dbAlias' => 'COUNT(target_activity.contact_id)',
    ];
    $this->_columns['civicrm_activity']['fields']['volunteers_unique_no'] = [
      'title' => ts('# of Unique Volunteers'),
      'dbAlias' => 'COUNT(DISTINCT target_activity.contact_id)',
    ];
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
    $this->_rollup = " WITH ROLLUP";
    $this->_groupBy .= " {$this->_rollup}";
  }

  public function alterDisplay(&$rows) {
    parent::alterDisplay($rows);
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

}
