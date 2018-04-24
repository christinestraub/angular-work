'use strict';

angular.module('snapshotApp.auth', [
  'snapshotApp.constants',
  'snapshotApp.util',
  'ngCookies',
  'ngRoute'
])
  .config(function($httpProvider) {
    $httpProvider.interceptors.push('authInterceptor');
  });
