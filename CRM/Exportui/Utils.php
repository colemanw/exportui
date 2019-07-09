<?php

class CRM_Exportui_Utils {

  /**
   * @return array
   */
  public static function getExportFields() {
    $fields = CRM_Core_BAO_Mapping::getBasicFields('Export');
    $hier = [];

    $categories = [
      'contact' => ['text' => ts('Contact Fields'), 'is_contact' => TRUE],
      'address' => ['text' => ts('Address Fields'), 'is_contact' => TRUE],
      'communication' => ['text' => ts('Communication Fields'), 'is_contact' => TRUE],
    ];
    $optionMap = [
      'civicrm_website' => 'website_type_id',
      'civicrm_phone' => 'phone_type_id',
      'civicrm_im' => 'im_provider_id',
    ];
    // Whitelist of field properties we actually care about; others will be discarded
    $fieldProps = ['id', 'text', 'has_location', 'option_list', 'relationship_type_id', 'related_contact_type'];
    $relTypes = civicrm_api3('RelationshipType', 'get', ['options' => ['limit' => 0]])['values'];

    // Add component fields
    $exportMode = CRM_Export_Form_Select::CONTACT_EXPORT; // temp placeholder for real data
    $compFields = [];
    $compLabels = CRM_Core_BAO_Mapping::addComponentFields($compFields, 'Export', $exportMode);
    foreach ($compLabels as $comp => $label) {
      $categories[$comp] = ['text' => $label];
      foreach ($compFields[$comp] as $key => $field) {
        $field['text'] = $field['title'];
        $field['id'] = $key;
        $categories[$comp]['children'][] = array_intersect_key($field, array_flip($fieldProps));
      }
    }

    // Unset groups, tags, notes for component export
    if ($exportMode != CRM_Export_Form_Select::CONTACT_EXPORT) {
      foreach (array_keys($fields) as $type) {
        CRM_Utils_Array::remove($fields[$type], 'groups', 'tags', 'notes');
      }
    }

    foreach (array_keys($fields) as $contactType) {
      unset($fields[$contactType]['related']);
      $hier[$contactType] = $categories;
      foreach ($fields[$contactType] as $key => $field) {
        $group = 'contact';
        $field['text'] = $field['title'];
        $field['id'] = $key;
        $field['has_location'] = !empty($field['hasLocationType']);
        if (isset($field['table_name']) && isset($optionMap[$field['table_name']])) {
          $field['option_list'] = $optionMap[$field['table_name']];
          $group = 'communication';
        }
        elseif (!empty($field['has_location'])) {
          $group = 'address';
        }
        if ($key == 'email') {
          $group = 'communication';
        }
        if (!empty($field['custom_group_id'])) {
          $group = $field['custom_group_id'];
          $hier[$contactType][$group]['text'] = $field['groupTitle'];
          $hier[$contactType][$group]['is_contact'] = TRUE;
        }
        if (!empty($field['related'])) {
          $group = 'related';
          $hier[$contactType][$group]['text'] = ts('Related Contact Info');
          list($type, , $dir) = explode('_', $key);
          $field['related_contact_type'] = CRM_Utils_Array::value("contact_sub_type_$dir", $relTypes[$type], CRM_Utils_Array::value("contact_type_$dir", $relTypes[$type], '*'));
          // Skip relationship types targeting disabled contacts
          if ($field['related_contact_type'] != '*' && !isset($fields[$field['related_contact_type']])) {
            continue;
          }
        }
        // Discard unwanted field props to save space
        $hier[$contactType][$group]['children'][] = array_intersect_key($field, array_flip($fieldProps));
      }
    }
    return array_map('array_values', $hier);
  }

}
