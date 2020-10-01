<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Yhvreports_Form_Report_VolunteerActivity',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'VolunteerActivity',
      'description' => 'VolunteerActivity (biz.jmaconsulting.yhvreports)',
      'class_name' => 'CRM_Yhvreports_Form_Report_VolunteerActivity',
      'report_url' => 'biz.jmaconsulting.yhvreports/volunteeractivity',
      'component' => '',
    ],
  ],
];
