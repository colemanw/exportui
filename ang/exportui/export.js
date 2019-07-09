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

    $scope.getFields = function() {
      return {results: fields[$scope.data.contact_type]};
    };

    $scope.getRelatedFields = function(contact_type) {
      return function() {
        return {results: relatedFields[contact_type]};
      };
    };

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
      // Remove empty values
      _.each(values, function(col, index) {
        if (!col.select) {
          $scope.data.columns.splice(index, 1);
        } else {
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
    }, true);
  });

})(angular, CRM.$, CRM._);
