'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/old', {
        template: '<old></old>',
        authenticate: true
      });
  });
