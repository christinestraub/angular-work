'use strict';

(function () {

  var ctrl;
  class HistoryController {

    constructor($http, $resource, $timeout, $scope, $uibModal, toastr, Util) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.scope = $scope;
      this.$uibModal = $uibModal;
      this.toastr = toastr;
      this.Util = Util;

      this.archives = [];
      this.displayed = [];

      // pagination
      this.itemsByPage = 20;
      this.displayedPages = 10;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      this.compareList = [];
      this.firstItem = {};
      this.secondItem = {};
      this.filter = {
        ccc: '',
        month: 0
      };

      ctrl = this;
    }

    $onInit() {
      this.getArchives();
    }

    getArchives() {
      ctrl.$http.get('/api/archives/all').then(response => {
        ctrl.archives = response.data;
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

    downloadFile(archive) {
      let downloadLink = `api/archives/download/${archive.uuid}`;
      if (this.Util.msieversion()) {
        window.open(downloadLink, '_blank');
        return;
      }
      delete ctrl.$http.defaults.headers.common['X-Requested-With'];
      ctrl.$http.get(`api/archives/download/${archive.uuid}`, {responseType: "arraybuffer"}).
      success(function (data, status, headers, config) {
        headers = headers();

        var filename = headers['x-filename'];
        var contentType = headers['content-type'];

        var linkElement = document.createElement('a');
        try {
          var blob = new Blob([data], {type: contentType});
          var url = window.URL.createObjectURL(blob);

          linkElement.setAttribute('href', url);
          linkElement.setAttribute("download", filename);

          var clickEvent = new MouseEvent("click", {
            "view": window,
            "bubbles": true,
            "cancelable": false
          });
          linkElement.dispatchEvent(clickEvent);
        } catch (ex) {
          console.log(ex);
        }
      }).
      error(function (data, status) {
        ctrl.info = "Request failed with status: " + status;
      });
    }

    parse(archive) {
      ctrl.$http.get(`/api/archives/parse/${archive.uuid}`)
        .then(response => {
          ctrl.toastr.info('The parsing successfully completed.', 'Snapshot');
          ctrl.syncData();
        }).catch(err => {
        ctrl.toastr.error(err, 'Snapshot');
      });
    }

    remove(archive) {
      ctrl.archive = archive;
      ctrl.openRemoveDialog();
    }

    openRemoveDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      var removeDlg = this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/history/history.remove.html',
        controller: 'HistoryRemoveCtrl',
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
        ctrl.$http.post('/api/recycled', archive)
          .then(response => {
            var archive = response.data;
            ctrl.toastr.info(`${archive.file_name} moved to recycled bin`, 'Snapshot');
            ctrl.syncData();
          }).catch(err => {
            ctrl.toastr.error(err.response, 'Snapshot');
            ctrl.removeItem(ctrl.currentFileItem);
        });
      });
    }

    snapshot(archive) {
      ctrl.archive = archive;
      ctrl.openSnapshotDialog();
    }

    openSnapshotDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      var modalInstance = this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'components/snapshot/snapshot.html',
        controller: 'SnapshotModalCtrl',
        controllerAs: '$ctrl',
        windowClass: 'app-modal-window',
        size: 'lg',
        appendTo: parentElem,
        resolve: {
          archive: function () {
            return ctrl.archive;
          }
        }
      });

      modalInstance.result.then(function (archive) {
        ctrl.$http.put('/api/archives', archive)
          .then(response => {
            ctrl.syncData();
          }).catch(err => {
          ctrl.toastr.error(err.data, 'Snapshot');
        });
      });
    };

    setFirst(archive) {
      ctrl.firstItem = archive;
      ctrl.compareList = [ ctrl.firstItem, ctrl.secondItem ];
    }

    setSecond(archive) {
      ctrl.secondItem = archive;
      ctrl.compareList = [ ctrl.firstItem, ctrl.secondItem ];
    }

    clearCompare() {
      ctrl.compareList = [];
    }

    compare() {
      ctrl.openCompareDialog();
    }

    openCompareDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/history/history.compare.html',
        controller: 'HistoryCompareCtrl',
        controllerAs: '$ctrl',
        windowClass: 'app-compare-modal',
        size: 'lg',
        appendTo: parentElem,
        resolve: {
          first: function () {return ctrl.firstItem; },
          second: function () {return ctrl.secondItem; }
        }
      });
    };
  }

  angular.module('snapshotApp')
    .component('history', {
      templateUrl: 'app/history/history.html',
      controller: HistoryController,
      controllerAs: 'vm',
      bindings: {$routerParams: '<'}
    });
})();
