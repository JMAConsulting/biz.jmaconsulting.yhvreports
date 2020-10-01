<?php
use CRM_Yhvreports_ExtensionUtil as E;

class CRM_Yhvreports_Form_Report_VolunteerActivity extends CRM_Report_Form_Activity {
  
  public function __construct() {
    parent::__construct();
    $this->_columns['civicrm_activity']['fields']['number_of_volunteers'] = [
      'title' => ts('# of Volunteers'),
      'dbAlias' => "COUNT(DISTINCT civicrm_contact_target.id)",
    ];
    $this->_columns['civicrm_activity']['group_bys'] = [
      'funder' => [
        'title' => 'Funder',
      ],
    ];
  }
  
  /**
   * Override group by function.
   */
  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_activity']}.id");
  }

}
