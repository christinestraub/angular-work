'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/history', {
        template: '<history></history>',
        authenticate: true
      });
  });
