'use strict';

angular.module('snapshotApp')
  .config(function ($routeProvider) {
    $routeProvider
      .when('/', {
        template: '<main></main>',
        controllerAs: 'vm'
      });
  });
