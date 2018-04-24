'use strict';

(function () {

  var ctrl;
  class ArchiveController {
    constructor($http, $resource, $timeout, $scope, $uibModal, $location, $document, FileUploader, toastr, Util) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.$scope = $scope;
      this.$uibModal = $uibModal;
      this.$location = $location;
      this.$document = $document;
      this.toastr = toastr;
      this.animationsEnabled = true;
      this.Util = Util;

      this.project = null;
      this.archive = null;
      this.archives = [];
      this.displayed = [];
      this.projectNumber = '';

      // pagination
      this.itemsByPage = 50;
      this.displayedPages = 12;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      var uploader = new FileUploader({
        url: 'api/archives/upload'
      });

      // FILTERS
      uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
          return this.queue.length < 1;
        }
      });

      // CALLBACKS
      uploader.onWhenAddingFileFailed = function (item /*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
      };
      uploader.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
      };
      uploader.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
      };
      uploader.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
      };
      uploader.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
      };
      uploader.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
      };
      uploader.onSuccessItem = function (fileItem, response, status, headers) {
        ctrl.currentFileItem = fileItem;
        ctrl.archive = response;
        ctrl.openProjectDialog();
      };
      uploader.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
        ctrl.toastr.warning(response, 'Snapshot');
        ctrl.removeItem(fileItem);
      };

      uploader.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
      };
      uploader.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
      };
      uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
      };

      this.uploader = uploader;
      this.uploadPanel = false;

      ctrl = this;
    }

    $onInit() {
      this.getArchives();
    }

    getArchives() {
      ctrl.$http.get('/api/archives').then(response => {
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

    removeItem(item) {
      item.remove();
      angular.element("#file_uploader").val(null);
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
        templateUrl: 'app/archive/archive.remove.html',
        controller: 'ArchiveRemoveCtrl',
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
            ctrl.toastr.error(err.data, 'Snapshot');
            ctrl.removeItem(ctrl.currentFileItem);
        });
      });
    };

    downloadFile(archive) {
      let downloadLink = `api/archives/download/${archive.uuid}`;
      if (this.Util.msieversion()) {
        window.open(downloadLink, '_blank');
        return;
      }
      delete ctrl.$http.defaults.headers.common['X-Requested-With'];
      ctrl.$http.get(downloadLink, {responseType: "arraybuffer"}).
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
      ctrl.$http.get('/api/archives/parse/' + archive.uuid)
        .then(response => {
          ctrl.toastr.info('The parsing successfully completed.', 'Snapshot');
          ctrl.syncData();
        }).catch(err => {
          ctrl.toastr.error(err.data, 'Snapshot');
      });
    }

    toggleUploadPanel() {
      ctrl.uploadPanel = !ctrl.uploadPanel;
    }

    hideUploadPanel() {
      ctrl.uploadPanel = false;
    }

    clearUploadPanel() {
      ctrl.removeItem(ctrl.currentFileItem);
    }

    openProjectDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      var modalInstance = this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/archive/archive.project.html',
        controller: 'ProjectModalCtrl',
        controllerAs: '$ctrl',
        size: 'md',
        appendTo: parentElem,
        resolve: {
          archive: function () {
            return ctrl.archive;
          }
        }
      });

      modalInstance.result.then(function (archive) {

        ctrl.archive.project_number = archive.project_number;
        ctrl.archive.region = archive.region;
        ctrl.archive.country = archive.country;

        ctrl.$http.post('/api/archives', ctrl.archive)
          .then(response => {
            var archive = response.data;
            ctrl.uploadPanel = false;
            ctrl.removeItem(ctrl.currentFileItem);
            ctrl.syncData();
          }).catch(err => {
          ctrl.toastr.error(err.response, 'Snapshot');
          ctrl.removeItem(ctrl.currentFileItem);
        });
      });
    };

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

    compare(archive) {
      ctrl.archive = archive;
      ctrl.openCompareDialog();
    }

    openCompareDialog(size, parentSelector) {
      var parentElem = parentSelector ?
        angular.element(ctrl.$document[0].querySelector('.modal-demo ' + parentSelector)) : undefined;
      this.$uibModal.open({
        animation: this.animationsEnabled,
        ariaLabelledBy: 'modal-title',
        ariaDescribedBy: 'modal-body',
        templateUrl: 'app/archive/archive.compare.html',
        controller: 'CompareModalCtrl',
        controllerAs: '$ctrl',
        windowClass: 'app-compare-modal',
        size: 'lg',
        appendTo: parentElem,
        resolve: {
          archive: function () {
            return ctrl.archive;
          }
        }
      });
    };
  }

  angular.module('snapshotApp')
    .component('archive', {
      templateUrl: 'app/archive/archive.html',
      controller: ArchiveController,
      controllerAs: 'vm'
    });
})();
