'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/recycled', {
        template: '<recycled></recycled>',
        authenticate: true
      });
  });
