'use strict';

(function () {

  var ctrl;
  class HistoryController {

    constructor($http, $resource, $timeout, $scope, $uibModal, toastr, $moment) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.scope = $scope;
      this.$uibModal = $uibModal;
      this.toastr = toastr;
      this.$moment = $moment;

      this.archives = [];
      this.displayed = [];

      // pagination
      this.itemsByPage = 20;
      this.displayedPages = 10;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      this.criteria = {
        month: 0
      };

      this.months = [];
      for(var i = 0; i < 13; i++) {
        if (i == 0)
          this.months.push({ number: 0, label: 'Now' });
        else
          this.months.push({ number: i, label: `${i} month ago` });
      }
      this.selectedMonth = this.months[0];

      ctrl = this;
    }

    $onInit() {
      this.getArchives();
    }

    getArchives() {
      var month = this.criteria.month;

      ctrl.$http.get(`/api/old?month=${month}`).then(response => {
        ctrl.archives = response.data;
        var now = new Date();
        ctrl.archives.forEach(function(archive) {
          archive.last_month = ctrl.$moment(archive.uploaded_on).fromNow();
        });
        ctrl.displayed = ctrl.archives.slice(0, ctrl.itemsByPage);
        if (ctrl.tableState !== undefined)
          ctrl.tableState.pagination.numberOfPages = Math.round(ctrl.archives.length / ctrl.itemsByPage);
      });
    }

    syncData() {
      ctrl.getArchives();
    }

    callServer(tableState) {
      ctrl.loading = true;

      var reload = (tableState) => {
        var start = tableState.pagination.start || 0;
        var quantity = tableState.pagination.number || this.itemsByPage;

        ctrl.displayed = ctrl.archives.slice(start, start + quantity);
        tableState.pagination.numberOfPages = Math.round(ctrl.archives.length / ctrl.itemsByPage);

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

    selectMonth() {
      this.criteria.month = this.selectedMonth.number;
      this.getArchives();
    }

    delete(archive) {
      ctrl.archive = archive;
      ctrl.openDeleteDialog();
    }

    openDeleteDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      var removeDlg = this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/old/old.delete.html',
        controller: 'OldDeleteCtrl',
        controllerAs: '$ctrl',
        size: size,
        appendTo: parentElem,
        resolve: {
          archive: function () {
            return ctrl.archive;
          }
        }
      });

      removeDlg.result.then(function (archive) {
        var month = ctrl.criteria.month;
        ctrl.$http.delete(`/api/old/${archive.id}/${month}`)
          .then(response => {
            ctrl.toastr.info(`all archives were deleted`, 'Snapshot');
            ctrl.syncData();
          }).catch(err => {
            ctrl.toastr.error(err.response, 'Snapshot');
        });
      });
    }
  }

  angular.module('snapshotApp')
    .component('old', {
      templateUrl: 'app/old/old.html',
      controller: HistoryController,
      controllerAs: 'vm',
      bindings: {$routerParams: '<'}
    });
})();
