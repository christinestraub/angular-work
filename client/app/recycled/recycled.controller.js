'use strict';

(function () {

  var ctrl;
  class RecycledController {
    constructor($http, $resource, $timeout, $scope, $uibModal, toastr, Auth, Util) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.$scope = $scope;
      this.$uibModal = $uibModal;
      this.toastr = toastr;
      this.animationsEnabled = true;
      this.Auth = Auth;
      this.isAdmin = Auth.isAdmin;
      this.Util = Util;

      console.log(this.isAdmin());

      this.project = null;
      this.archive = null;
      this.archives = [];
      this.displayed = [];

      // pagination
      this.itemsByPage = 50;
      this.displayedPages = 12;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      ctrl = this;
    }

    $onInit() {
      this.getArchives();
    }

    getArchives() {
      ctrl.$http.get('/api/recycled').then(response => {
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

    delete(archive) {
      if (ctrl.isAdmin()) {
        ctrl.archive = archive;
        ctrl.openDeleteDialog();
      } else {
        ctrl.toastr.info('Only Admin can delete the files permanently', 'Snapshot');
      }
    }

    openDeleteDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      var modalInstance = this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/recycled/recycled.delete.html',
        controller: 'RecycledDeleteCtrl',
        controllerAs: '$ctrl',
        size: size,
        appendTo: parentElem,
        resolve: {
          archive: function () {
            return ctrl.archive;
          }
        }
      });

      modalInstance.result.then(function (archive) {
        ctrl.$http.delete(`/api/recycled/${archive.id}`)
          .then(response => {
            var archive = response.data;
            ctrl.toastr.info(`${archive.file_name} deleted permanently.`, 'Snapshot');
            ctrl.syncData();
          }).catch(err => {
            ctrl.toastr.error(err.data, 'Snapshot');
        });
      });
    };

    restore(archive) {
      ctrl.$http.put('/api/recycled', archive)
        .then(response => {
          var archive = response.data;
          ctrl.toastr.info(`${archive.file_name} restored from recycled bin.`, 'Snapshot');
          ctrl.syncData();
        }).catch(err => {
          ctrl.toastr.error(err, 'Snapshot');
        });
    }
  }

  angular.module('snapshotApp')
    .component('recycled', {
      templateUrl: 'app/recycled/recycled.html',
      controller: RecycledController,
      controllerAs: 'vm'
    });
})();
