'use strict';

angular.module('snapshotApp', [
    'snapshotApp.auth',
    'snapshotApp.admin',
    'snapshotApp.constants',
    'ngCookies',
    'ngResource',
    'ngSanitize',
    'ngRoute',
    'ngTable',
    'ngTouch',
    'ngAnimate',
    'ui.bootstrap',
    'ui.bootstrap.modal',
    'validation.match',
    'datatables',
    'smart-table',
    'angular.filter',
    'angularFileUpload',
    'toastr',
    'angular-loading-bar',
    'ngMoment'
  ])
  .config(function ($routeProvider, $locationProvider) {
    $routeProvider
      .otherwise({
        redirectTo: '/',
        authenticate: true
      });

    $locationProvider.html5Mode(true);
  })
  .config(['cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeBar = true;
  }])
  .config(function($momentProvider){
    $momentProvider
      .asyncLoading(false)
      .scriptUrl('//cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment.min.js');
  })
  ;

