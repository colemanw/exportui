<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Export_Form_Map extends CRM_Core_Form {

  /**
   * Loaded mapping ID
   *
   * @var int
   */
  protected $_mappingId;

  /**
   * Build the form object.
   *
   * @return void
   */
  public function preProcess() {
    $this->_mappingId = $this->get('mappingId');

    $contactTypes = array_column(CRM_Utils_Array::makeNonAssociative(CRM_Contact_BAO_ContactType::basicTypePairs(), 'id', 'text'), NULL, 'id');
    foreach (CRM_Contact_BAO_ContactType::subTypeInfo() as $subType) {
      $contactTypes[$subType['parent']]['children'][] = ['id' => $subType['name'], 'text' => $subType['label'], 'description' => CRM_Utils_Array::value('description', $subType)];
    }

    Civi::resources()->addVars('exportUi', [
      'fields' => CRM_Export_Utils::getExportFields($this->get('exportMode')),
      'contact_types' => array_values($contactTypes),
      'location_type_id' => CRM_Utils_Array::makeNonAssociative(CRM_Core_BAO_Address::buildOptions('location_type_id'), 'id', 'text'),
      'option_list' => [
        'phone_type_id' => CRM_Utils_Array::makeNonAssociative(CRM_Core_BAO_Phone::buildOptions('phone_type_id'), 'id', 'text'),
        'website_type_id' => CRM_Utils_Array::makeNonAssociative(CRM_Core_BAO_Website::buildOptions('website_type_id'), 'id', 'text'),
        'im_provider_id' => CRM_Utils_Array::makeNonAssociative(CRM_Core_BAO_IM::buildOptions('provider_id'), 'id', 'text'),
      ],
    ]);

    $loader = new Civi\Angular\AngularLoader();
    $loader->setModules(['exportui']);
    $loader->load();
  }

  public function buildQuickForm() {

    $this->add('hidden', 'export_field_map');

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Previous'),
      ],
      [
        'type' => 'done',
        'icon' => 'fa-times',
        'name' => ts('Return to Search'),
      ],
      [
        'type' => 'next',
        'name' => ts('Download File'),
      ],
    ]);
  }

  public function setDefaultValues() {
    $defaults = [];
    if ($this->_mappingId) {
      $mappingFields = civicrm_api3('mappingField', 'get', ['mapping_id' => $this->_mappingId, 'options' => ['limit' => 0, 'sort' => 'column_number']]);
      $defaults['export_field_map'] = json_encode(array_values($mappingFields['values']));
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportParams = $this->controller->exportValues('Select');

    $greetingOptions = CRM_Export_Form_Select::getGreetingOptions();

    if (!empty($greetingOptions)) {
      foreach ($greetingOptions as $key => $value) {
        if ($option = CRM_Utils_Array::value($key, $exportParams)) {
          if ($greetingOptions[$key][$option] == ts('Other')) {
            $exportParams[$key] = $exportParams["{$key}_other"];
          }
          elseif ($greetingOptions[$key][$option] == ts('List of names')) {
            $exportParams[$key] = '';
          }
          else {
            $exportParams[$key] = $greetingOptions[$key][$option];
          }
        }
      }
    }

    $currentPath = CRM_Utils_System::currentPath();

    $urlParams = NULL;
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams = "&qfKey=$qfKey";
    }

    //get the button name
    $buttonName = $this->controller->getButtonName('done');
    $buttonName1 = $this->controller->getButtonName('next');
    if ($buttonName == '_qf_Map_done') {
      $this->controller->resetPage($this->_name);
      return CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, 'force=1' . $urlParams));
    }

    $mapperKeys = $params['mapper'][1];

    $checkEmpty = 0;
    foreach ($mapperKeys as $value) {
      if ($value[0]) {
        $checkEmpty++;
      }
    }

    if (!$checkEmpty) {
      $this->set('mappingId', NULL);
      CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, '_qf_Map_display=true' . $urlParams));
    }

    if ($buttonName1 == '_qf_Map_next') {
      if (!empty($params['updateMapping'])) {
        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $params['mappingId']);
      }

      if (!empty($params['saveMapping'])) {
        $mappingParams = [
          'name' => $params['saveMappingName'],
          'description' => $params['saveMappingDesc'],
          'mapping_type_id' => $this->get('mappingTypeId'),
        ];

        $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $saveMapping->id);
      }
    }

    //get the csv file
    CRM_Export_BAO_Export::exportComponents($this->get('selectAll'),
      $this->get('componentIds'),
      (array) $this->get('queryParams'),
      $this->get(CRM_Utils_Sort::SORT_ORDER),
      $mapperKeys,
      $this->get('returnProperties'),
      $this->get('exportMode'),
      $this->get('componentClause'),
      $this->get('componentTable'),
      $this->get('mergeSameAddress'),
      $this->get('mergeSameHousehold'),
      $exportParams,
      $this->get('queryOperator')
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Select Fields to Export');
  }

}
