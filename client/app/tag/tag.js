'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/tag', {
        template: '<tag></tag>',
        authenticate: true
      });
  });
