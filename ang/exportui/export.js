(function(angular, $, _) {

  angular.module('exportui', CRM.angular.modules)

  .controller('ExportUiCtrl', function($scope, $timeout) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('exportui');

    $scope.option_list = CRM.vars.exportUi.option_list;
    $scope.contact_types = CRM.vars.exportUi.contact_types;
    $scope.location_type_id = CRM.vars.exportUi.location_type_id;
    $scope.fields = {};
    $scope.data = {
      contact_type: 'Individual',
      columns: []
    };
    $scope.new = {col: ''};
    var contactTypes = [];
    var contactSubTypes = {};
    var fields = _.cloneDeep(CRM.vars.exportUi.fields);
    var relatedFields = _.cloneDeep(CRM.vars.exportUi.fields);
    var starFields = [];
    _.each(relatedFields, function(groups, cat) {
      _.each(groups, function(group) {
        _.each(group.children, function(field) {
          $scope.fields[field.id] = field;
        });
        if (!group.is_contact) {
          return;
        }
        var existing = _.where(starFields, {text: group.text});
        if (existing.length) {
          existing[0].children = _.uniq(group.children.concat(existing[0].children), 'id');
        } else {
          starFields.push(group);
        }
      });
      relatedFields[cat] = _.filter(relatedFields[cat], 'is_contact');
    });
    relatedFields['*'] = starFields;

    _.each($scope.contact_types, function(type) {
      contactTypes.push(type.text);
      if (type.children) {
        _.each(type.children, function(subType) {
          contactSubTypes[subType.id] = type.id;
        });
      }
    });

    function getSelectedColumns() {
      var map = [];
      _.each($scope.data.columns, function(col, no) {
        // Make a copy of col without the extra angular props
        var item = JSON.parse(angular.toJson(col));
        delete item.select;
        item.contact_type = $scope.data.contact_type;
        item.column_number = no;
        map.push(item);
      });
      return map;
    }

    function loadFieldMap(map) {
      $scope.data.columns = [];
      _.each(map, function(col) {
        // Set main contact type selector, preferring sub-types
        if (contactSubTypes[col.contact_type]) {
          $scope.data.contact_type = col.contact_type;
        } else if (!contactSubTypes[$scope.data.contact_type] && _.contains(contactTypes, col.contact_type)) {
          $scope.data.contact_type = col.contact_type;
        }
        if (col.relationship_type_id && col.relationship_direction) {
          col.select = '' + col.relationship_type_id + '_' + col.relationship_direction;
        } else {
          col.select = col.name;
        }
        $scope.data.columns.push(col);
      });
    }

    $scope.getFields = function() {
      return {results: fields[$scope.data.contact_type]};
    };

    $scope.getRelatedFields = function(contact_type) {
      return function() {
        return {results: relatedFields[contact_type]};
      };
    };

    // Load saved mapping
    if ($('input[name=export_field_map]').val()) {
      loadFieldMap(JSON.parse($('input[name=export_field_map]').val()));
    }

    // Add new col
    $scope.$watch('new.col', function(val) {
      var field = val;
      $timeout(function() {
        if (field) {
          $scope.data.columns.push({
            select: field,
            name: '',
            location_type_id: null,
            phone_type_id: null,
            website_type_id: null,
            im_provider_id: null,
            relationship_type_id: null,
            relationship_direction: null
          });
          $scope.new.col = '';
        }
      });
    });

    // Remove col
    $scope.$watch('data.columns', function(values) {
      _.each(values, function(col, index) {
        // Remove empty values
        if (!col.select) {
          $scope.data.columns.splice(index, 1);
        } else {
          // Format item
          var selection = $scope.fields[col.select];
          if (selection.relationship_type_id) {
            col.relationship_type_id = selection.relationship_type_id;
            col.relationship_direction = col.select.slice(col.select.indexOf('_')+1);
          } else {
            col.name = col.select;
            col.relationship_direction = col.relationship_type_id = null;
          }
          var field = col.name ? $scope.fields[col.name] : {};
          col.location_type_id = field.has_location ? col.location_type_id || '' : null;
          _.each($scope.option_list, function(options, list) {
            col[list] = (col.location_type_id || !field.has_location) && field.option_list === list ? col[list] || options[0].id : null;
          });
        }
      });
      // Store data in a quickform hidden field
      var selectedColumns = getSelectedColumns();
      $('input[name=export_field_map]').val(JSON.stringify(selectedColumns));

      // Hide submit button when no fields selected
      $('.crm-button_qf_Map_next').toggle(!!selectedColumns.length);
    }, true);
  });

})(angular, CRM.$, CRM._);
