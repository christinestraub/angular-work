'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/archive', {
        template: '<archive></archive>',
        authenticate: true
      });
  });
