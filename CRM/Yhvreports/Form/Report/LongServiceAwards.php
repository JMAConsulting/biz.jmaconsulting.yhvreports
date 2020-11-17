<?php
use CRM_Yhvreports_ExtensionUtil as E;

class CRM_Yhvreports_Form_Report_LongServiceAwards extends CRM_Report_Form_ActivitySummary {
  protected $_customGroupExtends = ['Activity', 'Individual'];

  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_address'] = [
      'dao' => 'CRM_Core_DAO_Address',
      'fields' => [
        'street_address' => NULL,
        'city' => NULL,
        'postal_code' => NULL,
      ],
    ];
    $this->_columns['civicrm_activity']['fields']['years_of_service'] = [
      'title' => 'Years of Service',
      'dbAlias' => 'temp_last_year.years_of_service',
    ];
    $this->_columns['civicrm_activity']['fields']['hours_last_year'] = [
      'title' => 'Hours Last Year',
      'dbAlias' => 'temp_last_year.hours_last_year',
    ];
    $this->_columns['civicrm_activity']['fields']['past_awards'] = [
      'title' => 'Past Awards',
      'dbAlias' => 'temp.past_awards',
    ];
    $this->_columns['civicrm_activity']['fields']['activity_date_time'] = [
      'title' => E::ts('Registration Date'),
    ];
    $this->_columns['civicrm_activity']['filters']['hours_last_year'] = [
      'title' => E::ts('Volunteer Hours'),
      'dbAlias' => 'temp_last_year.hours_last_year',
      'operatorType' => CRM_Report_Form::OP_INT,
      'type' => CRM_Utils_Type::T_INT,
    ];
    $this->_columns['civicrm_activity']['fields']['duration']['title'] = E::ts('Volunteer Hours');
    $this->_columns['civicrm_activity']['group_bys']['activity_type_id']['default'] = $this->_columns['civicrm_activity']['group_bys']['status_id']['default'] = FALSE;

  }


  /**
   * Generate from clause.
   */
  public function from() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $pastYear = date("Y",strtotime("-1 year"));

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}

             LEFT JOIN civicrm_activity_contact target_activity
                    ON {$this->_aliases['civicrm_activity']}.id = target_activity.activity_id AND
                       target_activity.record_type_id = {$targetID}
             LEFT JOIN civicrm_activity_contact assignment_activity
                    ON {$this->_aliases['civicrm_activity']}.id = assignment_activity.activity_id AND
                       assignment_activity.record_type_id = {$assigneeID}
             LEFT JOIN civicrm_activity_contact source_activity
                    ON {$this->_aliases['civicrm_activity']}.id = source_activity.activity_id AND
                       source_activity.record_type_id = {$sourceID}
             LEFT JOIN civicrm_contact contact_civireport
                    ON target_activity.contact_id = contact_civireport.id
             LEFT JOIN civicrm_contact civicrm_contact_assignee
                    ON assignment_activity.contact_id = civicrm_contact_assignee.id
             LEFT JOIN civicrm_contact civicrm_contact_source
                    ON source_activity.contact_id = civicrm_contact_source.id

             LEFT JOIN (
               SELECT target_activity.contact_id,
                  GROUP_CONCAT(DISTINCT CONCAT(cust.award_name_54, ' - ', DATE_FORMAT(cust.award_for_year_55, '%Y %b'))) as past_awards
                FROM civicrm_activity as volunteer
                LEFT JOIN civicrm_activity_contact target_activity
                       ON volunteer.id = target_activity.activity_id AND
                       target_activity.record_type_id = {$targetID} AND volunteer.status_id IN ('2') AND volunteer.activity_type_id = 56
                LEFT JOIN civicrm_value_volunteer_awa_11 cust ON cust.entity_id = target_activity.activity_id
                 GROUP BY target_activity.contact_id
             ) temp ON temp.contact_id = contact_civireport.id

             LEFT JOIN (
               SELECT target_activity.contact_id, SUM(duration) as hours_last_year, COUNT(DISTINCT YEAR(volunteer.activity_date_time)) as years_of_service
                FROM civicrm_activity as volunteer
                LEFT JOIN civicrm_activity_contact target_activity
                       ON volunteer.id = target_activity.activity_id AND
                       target_activity.record_type_id = {$targetID} AND volunteer.status_id IN ('2') AND volunteer.activity_type_id = 55 AND YEAR(volunteer.activity_date_time) = {$pastYear}
                 GROUP BY target_activity.contact_id
             ) temp_last_year ON temp_last_year.contact_id = contact_civireport.id
             {$this->_aclFrom}
             LEFT JOIN civicrm_option_value
                    ON ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
             LEFT JOIN civicrm_option_group
                    ON civicrm_option_group.id = civicrm_option_value.option_group_id
             LEFT JOIN civicrm_case_activity
                    ON civicrm_case_activity.activity_id = {$this->_aliases['civicrm_activity']}.id
             LEFT JOIN civicrm_case
                    ON civicrm_case_activity.case_id = civicrm_case.id
             LEFT JOIN civicrm_case_contact
                    ON civicrm_case_contact.case_id = civicrm_case.id ";

    $this->joinPhoneFromContact();

    $this->joinEmailFromContact();

    $this->joinAddressFromContact();
  }


  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $totalType = $totalActivity = $totalDuration = 0;

    $query = "SELECT {$this->_tempTableName}.civicrm_activity_activity_type_id,
        {$this->_tempTableName}.civicrm_activity_id_count,
        {$this->_tempDurationSumTableName}.civicrm_activity_duration_total
    FROM {$this->_tempTableName} INNER JOIN {$this->_tempDurationSumTableName}
      ON ({$this->_tempTableName}.id = {$this->_tempDurationSumTableName}.id)";

    $actDAO = CRM_Core_DAO::executeQuery($query);

    $activityTypesCount = [];
    while ($actDAO->fetch()) {
      if (!in_array($actDAO->civicrm_activity_activity_type_id, $activityTypesCount)) {
        $activityTypesCount[] = $actDAO->civicrm_activity_activity_type_id;
      }

      $totalActivity += $actDAO->civicrm_activity_id_count;
      $totalDuration += $actDAO->civicrm_activity_duration_total;
    }

    $totalType = count($activityTypesCount);

    $statistics['counts']['type'] = [
      'title' => ts('Total Types'),
      'value' => $totalType,
    ];
    $statistics['counts']['activities'] = [
      'title' => ts('Total Number of Activities'),
      'value' => $totalActivity,
    ];
    $statistics['counts']['duration'] = [
      'title' => ts('Total Volunteer Hours'),
      'type' => CRM_Utils_Type::T_STRING,
      'value' => round($totalDuration, 2),
    ];
    return $statistics;
  }

  /**
   * Generate where clause.
   *
   * @param bool $durationMode
   */
  public function where($durationMode = FALSE) {
    $optionGroupClause = '';
    if (!$durationMode) {
      $optionGroupClause = 'civicrm_option_group.name = "activity_type" AND ';
    }
    $this->_where = " WHERE {$optionGroupClause}
                            {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                            {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                            {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
          }
          else {
            if ($fieldName == 'duration' || ($fieldName === 'hours_last_year' && $durationMode)) {
              continue;
            }
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere && !$durationMode) {
      $this->_where .= " AND ({$this->_aclWhere} OR civicrm_contact_source.is_deleted=0 OR civicrm_contact_assignee.is_deleted=0)";
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
      $this->_groupBy = empty($this->_groupBy) ? ["{$this->_aliases['civicrm_activity']}.id"] : $this->_groupBy;
      $groupBy = $this->_groupBy;
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy);
    }
    else {
      $groupBy = "{$this->_aliases['civicrm_activity']}.id";
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_activity']}.id ";
    }
    if ($includeSelectCol) {
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
  }


  /**
   * Build the report query.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->buildGroupTempTable();
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->buildPermissionClause();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    // Order by & Section columns not selected for display need to be included in SELECT.
    $unselectedColumns = array_merge($this->unselectedOrderByColumns(), $this->unselectedSectionColumns());
    foreach ($unselectedColumns as $alias => $field) {
      $clause = $this->getSelectClauseWithGroupConcatIfNotGroupedBy($field['table_name'], $field['name'], $field);
      if (!$clause) {
        $clause = "{$field['dbAlias']} as {$alias}";
      }
      $this->_select .= ", $clause ";
    }

    if ($applyLimit && empty($this->_params['charts'])) {
      $this->limit();
    }
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    // build temporary table column names base on column headers of result
    $dbColumns = [];
    foreach ($this->_columnHeaders as $fieldName => $dontCare) {
      $dbColumns[] = $fieldName . ' VARCHAR(128)';
    }

    // Order by & Section columns not selected for display need to be included in temp table.
    foreach ($unselectedColumns as $alias => $section) {
      $dbColumns[] = $alias . ' VARCHAR(128)';
    }

    // create temp table to store main result
    $this->_tempTableName = $this->createTemporaryTable('tempTable', "
      id int unsigned NOT NULL AUTO_INCREMENT, " . implode(', ', $dbColumns) . ' , PRIMARY KEY (id)',
    TRUE);

    // build main report query
    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    $this->addToDeveloperTab($sql);

    // store the result in temporary table
    $insertCols = '';
    $insertQuery = "INSERT INTO {$this->_tempTableName} ( " . implode(',', array_merge(array_keys($this->_columnHeaders), array_keys($unselectedColumns))) . " )
{$sql}";
    CRM_Core_DAO::disableFullGroupByMode();
    CRM_Core_DAO::executeQuery($insertQuery);
    CRM_Core_DAO::reenableFullGroupByMode();

    // now build the query for duration sum
    $this->activityDurationFrom();
    $this->where(TRUE);
    $this->groupBy(FALSE);

    // build the query to calulate duration sum
    $sql = "SELECT SUM(activity_civireport.duration) as civicrm_activity_duration_total {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    $this->addToDeveloperTab($sql);

    // create temp table to store duration
    $this->_tempDurationSumTableName = $this->createTemporaryTable('tempDurationSumTable', "
      id int unsigned NOT NULL AUTO_INCREMENT, civicrm_activity_duration_total INT(128), PRIMARY KEY (id)",
    TRUE);

    // store the result in temporary table
    $insertQuery = "INSERT INTO {$this->_tempDurationSumTableName} (civicrm_activity_duration_total)
    {$sql}";
    CRM_Core_DAO::disableFullGroupByMode();
    CRM_Core_DAO::executeQuery($insertQuery);
    CRM_Core_DAO::reenableFullGroupByMode();

    $fieldName = 'duration';
    $duration = CRM_Utils_Array::value("{$fieldName}_value", $this->_params, 0);
    $durationMin = CRM_Utils_Array::value("{$fieldName}_min", $this->_params, 0);
    $durationMax = CRM_Utils_Array::value("{$fieldName}_max", $this->_params, 0);
    $op = $this->_params["{$fieldName}_op"] ?? NULL;

    $clause = '(1)';
    if ($op && ($duration > 0 || $durationMin > 0 || $durationMax > 0)) {
      $c = $this->whereClause($this->_columns['civicrm_activity']['filters']['duration'], $op,
        $duration,
        $durationMin,
        $durationMax
      );
      $clause = $c ?: $clause;
    }

    $sql = "SELECT {$this->_tempTableName}.*,  {$this->_tempDurationSumTableName}.civicrm_activity_duration_total
    FROM {$this->_tempTableName} INNER JOIN {$this->_tempDurationSumTableName}
      ON ({$this->_tempTableName}.id = {$this->_tempDurationSumTableName}.id)
      WHERE {$clause}
      ";
     $this->addToDeveloperTab($sql);

    // finally add duration total to column headers
    $this->_columnHeaders['civicrm_activity_duration_total'] = ['no_display' => 1];

    // reset the sql building to default, which is used / called during other actions like "add to group"
    $this->from();
    $this->where();

    return $sql;
  }

  public function modifyColumnHeaders() {
    //CRM-16719 modify name of column
    if (!empty($this->_columnHeaders['civicrm_activity_status_id'])) {
      $this->_columnHeaders['civicrm_activity_status_id']['title'] = ts('Status');
    }
    $columnHeaders = [];
    foreach ([
      'civicrm_contact_sort_name' => 'Name',
      'civicrm_value_volunteer_inf_9_custom_24' => NULL,
      'civicrm_contact_exposed_id' => 'Vol ID#',
      'civicrm_activity_years_of_service' => NULL,
      'civicrm_activity_activity_date_time' => NULL,
      'civicrm_phone_phone' => NULL,
      'civicrm_address_street_address' => NULL,
      'civicrm_address_city' => NULL,
      'civicrm_address_postal_code' => NULL,
      'civicrm_activity_activity_type_id' => NULL,
      'civicrm_activity_hours_last_year' => NULL,
      'civicrm_activity_past_awards' => NULL,
      'civicrm_activity_duration' => 'Volunteer Hours',
    ] as $columnName => $title) {
      if (!empty($this->_columnHeaders[$columnName])) {
        if (!empty($title)) {
          $this->_columnHeaders[$columnName]['title'] = $title;
        }
        $columnHeaders[$columnName] = $this->_columnHeaders[$columnName];
        unset($this->_columnHeaders[$columnName]);
      }
    }
    $this->_columnHeaders = array_merge($columnHeaders, $this->_columnHeaders);
  }


  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
    $onHover = ts('View Contact Summary for this Contact');
    foreach ($rows as $rowNum => $row) {
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
          $rows[$rowNum]['civicrm_activity_duration'] = ROUND($rows[$rowNum]['civicrm_activity_duration_total'], 2);
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}
