<?php

require_once 'yhvreports.civix.php';
// phpcs:disable
use CRM_Yhvreports_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function yhvreports_civicrm_config(&$config) {
  _yhvreports_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function yhvreports_civicrm_xmlMenu(&$files) {
  _yhvreports_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function yhvreports_civicrm_install() {
  _yhvreports_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function yhvreports_civicrm_postInstall() {
  _yhvreports_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function yhvreports_civicrm_uninstall() {
  _yhvreports_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function yhvreports_civicrm_enable() {
  _yhvreports_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function yhvreports_civicrm_disable() {
  _yhvreports_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function yhvreports_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _yhvreports_civix_civicrm_upgrade($op, $queue);
}

function yhvreports_civicrm_alterReportVar($type, &$columns, &$form) {
  if ('CRM_Report_Form_Activity' == get_class($form) && $type == 'rows' && (strstr($_GET['q'], 'instance/53') || strstr($_GET['q'], 'instance/54'))) {
    $columnHeaders = [];
    foreach (['civicrm_contact_contact_target', 'civicrm_value_volunteering_12_custom_59', 'civicrm_value_volunteer_inf_9_custom_101'] as $column) {
      $columnHeaders[$column] = $form->_columnHeaders[$column];
      unset($form->_columnHeaders[$column]);
    }
    $form->_columnHeaders = array_merge($columnHeaders, $form->_columnHeaders);
  }
}


/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function yhvreports_civicrm_managed(&$entities) {
  _yhvreports_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function yhvreports_civicrm_caseTypes(&$caseTypes) {
  _yhvreports_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function yhvreports_civicrm_angularModules(&$angularModules) {
  _yhvreports_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function yhvreports_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _yhvreports_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function yhvreports_civicrm_entityTypes(&$entityTypes) {
  _yhvreports_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function yhvreports_civicrm_themes(&$themes) {
  _yhvreports_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function yhvreports_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function yhvreports_civicrm_navigationMenu(&$menu) {
//  _yhvreports_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _yhvreports_civix_navigationMenu($menu);
//}
