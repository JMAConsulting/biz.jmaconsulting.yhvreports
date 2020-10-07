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

  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
    $onHover = ts('View Contact Summary for this Contact');
    foreach ($rows as $rowNum => $row) {
      if (!empty($row['civicrm_value_volunteering_12_custom_57']) && !empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_56'])) {
        $row['civicrm_value_volunteering_12_custom_56'] = 'Total';
      }
      elseif (!empty($row['civicrm_value_volunteering_12_custom_57']) && empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_56'])) {
        $row['civicrm_value_volunteering_12_custom_59'] = 'Total';
      }
      elseif (!empty($row['civicrm_activity_id_count']) && empty($row['civicrm_value_volunteering_12_custom_57']) && empty($row['civicrm_value_volunteering_12_custom_59']) && empty($row['civicrm_value_volunteering_12_custom_56'])) {
        $row['civicrm_value_volunteering_12_custom_57'] = 'Total';
      }
      // make count columns point to activity detail report
      if (!empty($row['civicrm_activity_id_count'])) {
        $url = [];
        $urlParams = ['activity_type_id', 'gid', 'status_id', 'contact_id'];
        foreach ($urlParams as $field) {
          if (!empty($row['civicrm_activity_' . $field])) {
            $url[] = "{$field}_op=in&{$field}_value={$row['civicrm_activity_'.$field]}";
          }
          elseif (!empty($this->_params[$field . '_value'])) {
            $val = implode(",", $this->_params[$field . '_value']);
            $url[] = "{$field}_op=in&{$field}_value={$val}";
          }
        }
        $date_suffixes = ['relative', 'from', 'to'];
        foreach ($date_suffixes as $suffix) {
          if (!empty($this->_params['activity_date_time_' . $suffix])) {
            list($from, $to)
              = $this->getFromTo(
                CRM_Utils_Array::value("activity_date_time_relative", $this->_params),
                CRM_Utils_Array::value("activity_date_time_from", $this->_params),
                CRM_Utils_Array::value("activity_date_time_to", $this->_params)
                );
            $url[] = "activity_date_time_from={$from}&activity_date_time_to={$to}";
            break;
          }
        }
        // reset date filter on activity reports.
        $url[] = "resetDateFilter=1";
        $url = implode('&', $url);
        $url = CRM_Report_Utils_Report::getNextUrl('activity', "reset=1&force=1&{$url}",
                 $this->_absoluteUrl,
                 $this->_id,
                 $this->_drilldownReport);
        $rows[$rowNum]['civicrm_activity_id_count_link'] = $url;
        $rows[$rowNum]['civicrm_activity_id_count_hover'] = ts('List all activity(s) for this row.');
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_id']) {

          // unset the name, email and phone fields if the contact is the same as the previous contact
          if (isset($previousContact) && $previousContact == $value) {
            $rows[$rowNum]['civicrm_contact_sort_name'] = "";

            if (array_key_exists('civicrm_email_email', $row)) {
              $rows[$rowNum]['civicrm_email_email'] = "";
            }
            if (array_key_exists('civicrm_phone_phone', $row)) {
              $rows[$rowNum]['civicrm_phone_phone'] = "";
            }
          }
          else {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );

            $rows[$rowNum]['civicrm_contact_sort_name'] = "<a href='$url'>" . $row['civicrm_contact_sort_name'] .
              '</a>';
          }

          // store the contact ID of this contact
          $previousContact = $value;
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {

          $value = explode(',', $value);
          foreach ($value as $key => $id) {
            $value[$key] = $activityType[$id];
          }

          $rows[$rowNum]['civicrm_activity_activity_type_id'] = implode(' , ', $value);
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_status_id', $row)) {
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $activityStatus[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_priority_id', $row)) {
        if ($value = $row['civicrm_activity_priority_id']) {
          $rows[$rowNum]['civicrm_activity_priority_id'] = $priority[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_duration', $row)) {
        if ($value = $row['civicrm_activity_duration']) {
          $rows[$rowNum]['civicrm_activity_duration'] = ROUND(($rows[$rowNum]['civicrm_activity_duration_total'] / 60), 2);
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
