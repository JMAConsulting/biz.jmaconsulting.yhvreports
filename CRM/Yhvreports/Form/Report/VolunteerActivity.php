<?php

require_once __DIR__ . 'yhvreports.variables.php';

use CRM_Yhvreports_ExtensionUtil as E;

class CRM_Yhvreports_Form_Report_VolunteerActivity extends CRM_Report_Form_ActivitySummary {
  
  protected $_customGroupExtends = ['Activity'];

  public function __construct() {
    parent::__construct();
    $volunteeringTableName = civicrm_api3('CustomGroup', 'getvalue', ['id' => VOLUNTEERING_CF, 'return' => 'table_name']);
    foreach($this->_columns[$volunteeringTableName]['fields'] as $column as $field) {
      $this->_columns[$volunteeringTableName]['group_bys'][$column] = [
        'title' => $field['title'];
      ]; 
    }
  }
  
  /**
   * Override group by function.
   */
  public function groupBy() {
    parent::groupBy();
  }

}
