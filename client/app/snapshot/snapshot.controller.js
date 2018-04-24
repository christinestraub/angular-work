'use strict';

(function () {

  var ctrl;
  class SnapshotController {

    constructor($http, $resource, $timeout, $scope, $routeParams) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.scope = $scope;

      this.snapshots = [];
      this.displayed = [];

      // pagination
      this.itemsByPage = 20;
      this.displayedPages = 10;

      this.loading = false;
      this.firsttime = true;
      this.loading = false;

      ctrl = this;

      this.archiveId = $routeParams.archiveId;

      if (typeof this.archiveId !== 'undefined') {
        ctrl.$http.get('/api/archives/' + this.archiveId)
          .then(response => {
            var archive = response.data;
            var params = "?archive=" + archive.file_name;
            ctrl.isLoading = true;
            ctrl.$http.get('/api/snapshots/page/0/' + 20 + params)
              .then(response => {
                ctrl.displayed = response.data;
                ctrl.isLoading = false;
              })
              .catch(err => {
                console.log(err);
                ctrl.isLoading = false;
              });
          });
      }
    }

    $onInit() {
    }

    callServer(tableState) {
      var pagination = tableState.pagination;
      var start = pagination.start || 0;
      var limit = pagination.number || 20;

      var filtered = tableState.search.predicateObject;
      var sort = tableState.sort.predicate;
      var reverse = tableState.sort.reverse;

      var params = "?";
      if (filtered) {
        if (filtered.archive)
          params = params + "archive=" + filtered.archive + "&";
        if (filtered.path)
          params = params + "path=" + filtered.path + "&";
        if (filtered.file)
          params = params + "file=" + filtered.file + "&";
        if (filtered.chassis) {
          params = params + "tag=@GV.ProjectInfo.Chassis&";
          params = params + "val=" + filtered.chassis + "&";
        } else {
          if (filtered.tag)
            params = params + "tag=" + filtered.tag + "&";
          if (filtered.val)
            params = params + "val=" + filtered.val + "&";
        }
        if (filtered.ccc)
          params = params + "ccc=" + filtered.ccc + "&";
      }

      ctrl.isLoading = true;
      ctrl.$http.get('/api/snapshots/page/' + start + '/' + limit + params)
        .then(response => {
          ctrl.displayed = response.data;
          tableState.pagination.numberOfPages = 20;
          ctrl.isLoading = false;
        })
        .catch(err => {
          console.log(err);
          ctrl.isLoading = false;
        });
    }
  }

  angular.module('snapshotApp')
    .component('snapshot', {
      templateUrl: 'app/snapshot/snapshot.html',
      controller: SnapshotController,
      controllerAs: 'vm',
      bindings: {$routerParams: '<'}
    });
})();
