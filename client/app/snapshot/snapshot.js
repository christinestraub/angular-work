'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/snapshot/:archiveId?', {
        template: '<snapshot></snapshot>',
        authenticate: true
      });
  });
