(function(angular, $, _) {

  angular.module('exportui').config(function($routeProvider) {
      $routeProvider.when('/export', {
        controller: 'Exportuiexport',
        templateUrl: '~/exportui/export.html'

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        //resolve: {
        //  myContact: function(crmApi) {
        //    return crmApi('Contact', 'getsingle', {
        //      id: 'user_contact_id',
        //      return: ['first_name', 'last_name']
        //    });
        //  }
        //}
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('exportui').controller('Exportuiexport', function($scope, $timeout) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('exportui');

    $scope.option_list = CRM.vars.exportUi.option_list;
    $scope.contact_types = CRM.vars.exportUi.contact_types;
    $scope.location_type_id = CRM.vars.exportUi.location_type_id;
    $scope.fields = {};
    var fields = _.cloneDeep(CRM.vars.exportUi.fields);
    var relatedFields = _.cloneDeep(CRM.vars.exportUi.fields);
    var starFields = [];
    _.each(relatedFields, function(groups) {
      _.each(groups, function(group, index) {
        _.each(group.children, function(field) {
          $scope.fields[field.id] = field;
        });
        if (group.relationships) {
          groups.splice(index, 1);
          return;
        }
        var existing = _.where(starFields, {text: group.text});
        if (existing.length) {
          existing[0].children = _.uniq(group.children.concat(existing[0].children), 'id');
        } else {
          starFields.push(group);
        }
      });
    });
    relatedFields['*'] = starFields;

    $scope.getFields = function() {
      return {results: fields[$scope.contact_type]};
    };

    $scope.getRelatedFields = function(contact_type) {
      return function() {
        return {results: relatedFields[contact_type]};
      };
    };

    $scope.contact_type = 'Individual';
    $scope.columns = [];
    $scope.newCol = '';

    // Add new col
    $scope.$watch('newCol', function(val) {
      var field = val;
      $timeout(function() {
        if (field) {
          $scope.columns.push({
            select: field,
            name: '',
            location_type_id: null,
            phone_type_id: null,
            website_type_id: null,
            im_provider_id: null,
            relationship_type_id: null,
            relationship_direction: null
          });
          $scope.newCol = '';
        }
      });
    });

    // Remove col
    $scope.$watch('columns', function(values) {
      // Remove empty values
      _.each(values, function(col, index) {
        if (!col.select) {
          $scope.columns.splice(index, 1);
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
  //
  //angular.module('exportui').directive('crmExportField', function() {
  //  return {
  //    restrict: 'AE',
  //    scope: {
  //      crmExportField: '=field'
  //    },
  //    link: function() {
  //
  //    }
  //  };
  //});

})(angular, CRM.$, CRM._);
