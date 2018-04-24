'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/statistics', {
        template: '<statistics></statistics>',
        authenticate: true
      });
  });
