<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Yhvreports_Form_Report_VolunteerSummary',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'VolunteerSummary',
      'description' => 'VolunteerSummary (biz.jmaconsulting.yhvreports)',
      'class_name' => 'CRM_Yhvreports_Form_Report_VolunteerSummary',
      'report_url' => 'biz.jmaconsulting.yhvreports/volunteersummary',
      'component' => '',
    ],
  ],
];
