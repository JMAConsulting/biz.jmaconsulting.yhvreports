<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return [
  [
    'name' => 'CRM_Yhvreports_Form_Report_LongServiceAwards',
    'entity' => 'ReportTemplate',
    'params' => [
      'version' => 3,
      'label' => 'LongServiceAwards',
      'description' => 'LongServiceAwards (biz.jmaconsulting.yhvreports)',
      'class_name' => 'CRM_Yhvreports_Form_Report_LongServiceAwards',
      'report_url' => 'biz.jmaconsulting.yhvreports/longserviceawards',
      'component' => '',
    ],
  ],
];
