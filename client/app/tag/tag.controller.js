/**
 * issues
 * On the "Tags" screen, if I click edit button (left window) "snapshot.csv" I can see the list of parameters on
 * the right window, but it seem that this list is not complete (not all parameters) to edit,
 * and there is no scroll barr.
 * If I'll do the same - clicking edit button (left window) "errlog.txt" there is no list of parameters at all to
 * edit on the right window.
 */
'use strict';

(function () {

  var ctrl;
  class TagController {
    constructor($http, $resource, $scope, $timeout, $document, $uibModal, toastr) {
      this.$http = $http;
      this.$resource = $resource;
      this.$scope = $scope;
      this.$timeout = $timeout;
      this.$uibModal = $uibModal;
      this.$document = $document;
      this.toastr = toastr;

      this.tag = null;
      this.tags = [];
      this.field_data = [];
      this.new_field = '';
      this.field = null;
      this.fieldAction = 'Add';
      this.file_name = '';
      this.displayed = [];

      // pagination
      this.itemsByPage = 50;
      this.displayedPages = 12;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      this.editMode = '';

      this.gridOptions = {
        enableSorting: true,
        enableRowSelection: true,
        enableSelectAll: true,
        multiSelect: true,
        columnDefs: [
          {field: 'field'},
        ],
        onRegisterApi: function (gridApi) {
          $scope.grid1Api = gridApi;
        }
      };
      ctrl = this;
    }

    $onInit() {
      this.getTags();
    }

    getTags() {
      this.$http.get('/api/tags').then(response => {
        this.tags = response.data;
        this.displayed = ctrl.tags.slice(0, this.itemsByPage);
        if (this.tableState !== undefined)
          this.tableState.pagination.numberOfPages = Math.round(ctrl.tags.length / ctrl.itemsByPage);
      });
    }

    syncData() {
      this.getTags();
    }

    callServer(tableState) {
      ctrl.loading = true;

      var reload = (tableState) => {
        ctrl.displayed = ctrl.tags.slice();
        ctrl.loading = false;
        ctrl.tableState = tableState;
      };

      if (ctrl.firsttime == true) {
        ctrl.$timeout(() => {
          ctrl.firsttime = false;
          reload(tableState);
        }, 1000);
      } else {
        reload(tableState);
      }
    }

    addTag() {
      if (this.file_name !== '') {
        var tag = {
          file_name: this.file_name,
          fields: '[]'
        };

        this.$http.post('/api/tags', tag)
          .then(response => {
            ctrl.file_name = ''
            ctrl.syncData();
          });
      }
    }

    editTag(tag) {
      this.tag = tag;
      this.field_data = [];
      if (this.tag.field_list !== null) {
        this.tag.field_list.forEach(field => {
          this.field_data.push({field: field});
        });
      }
    }

    updateTag() {
      this.new_field = '';
      if (this.tag === null) return;
      var tag = {
        id: this.tag.id,
        file_name: this.tag.file_name,
        fields: ''
      };
      var fields = [];
      for (var i = 0, len = this.field_data.length; i < len; i++) {
        fields.push(this.field_data[i].field);
      }
      tag.fields = angular.toJson(fields);
      this.$http.put('/api/tags/' + tag.id, tag)
        .then(response => {
          this.tag.fields = response.data.fields;
          this.tag.field_list = response.data.field_list;
        })
    }

    resetTag() {
      if (this.tag === null) return;
      this.editTag(this.tag);
    }

    restoreDb() {
      this.$http.get('/api/tags/restore')
        .then(response => {
          ctrl.file_name = ''
          ctrl.syncData();
        });
    }

    removeTag(tag, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;

      var removeModal = this.$uibModal.open({
        templateUrl: 'app/tag/tag.remove.html',
        controller: 'TagRemoveCtrl',
        controllerAs: '$ctrl',
        animation: true,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        size: 'sm',
        appendTo: parentElem,
        resolve: {
          tag: function () {
            return tag;
          }
        }
      });

      removeModal.result.then(function (tag) {
        ctrl.$http.delete(`/api/tags/${tag.id}`)
          .then(response => {
            ctrl.syncData();
          }).catch(err => {
            ctrl.toastr.error(err.response, 'Tags');
        });
      });
    }

    removeCheckedFields() {
      var selectedFields = this.field_data.filter(field => {
        return field.isSelected
      });
      console.log('selectedFields', selectedFields);
      selectedFields.forEach(field => {
        var index = this.field_data.indexOf(field);
        if (index > -1) {
          this.field_data.splice(index, 1);
        }
      });
      console.log('field_data', this.field_data);
    }

    addField() {
      if (this.field == null) {
        for (var i = 0, len = this.field_data.length; i < len; i++) {
          if (this.field_data[i].field == this.new_field) {
            this.toastr.warning('Already existing field.');
            return;
          }
        }
        this.field_data.push({'field': this.new_field});
        this.new_field = '';
      } else {
        var index = this.field_data.indexOf(this.field);
        if (index > -1) {
          this.field.field = this.new_field;
        }
        this.resetField();
      }
    }

    removeField(field) {
      var index = this.field_data.indexOf(field);
      if (index > -1) {
        this.field_data.splice(index, 1);
      }
    }

    editField(field) {
      this.field = field;
      this.new_field = field.field;
      this.fieldAction = 'Update';
    }

    resetField() {
      this.field = null;
      this.new_field = '';
      this.fieldAction = 'Add';
    }
  }

  angular.module('snapshotApp')
    .component('tag', {
      templateUrl: 'app/tag/tag.html',
      controller: TagController,
      controllerAs: 'vm'
    });
})();
