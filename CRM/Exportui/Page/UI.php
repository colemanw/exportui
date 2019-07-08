<?php
use CRM_Exportui_ExtensionUtil as E;

class CRM_Exportui_Page_UI extends CRM_Core_Page {

  public function run() {
    $contactTypes = array_column(CRM_Utils_Array::makeNonAssociative(CRM_Contact_BAO_ContactType::basicTypePairs(), 'id', 'text'), NULL, 'id');
    foreach (CRM_Contact_BAO_ContactType::subTypeInfo() as $subType) {
      $contactTypes[$subType['parent']]['children'][] = ['id' => $subType['name'], 'text' => $subType['label']];
    }

    Civi::resources()->addVars('exportUi', [
      'fields' => CRM_Exportui_Utils::getExportFields(),
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
    parent::run();
  }

}
