'use strict';

(function () {

  var ctrl;
  class StatisticsController {

    constructor($http, $resource, $timeout, $scope) {
      this.$http = $http;
      this.$resource = $resource;
      this.$timeout = $timeout;
      this.scope = $scope;

      this.statPerRegions = [];
      this.dispPerRegions = [];
      this.statPerCountries = [];
      this.dispPerCountries = [];
      this.statPerUsers = [];
      this.dispPerUsers = [];
      this.statCccPerRegion = [];
      this.dispCccPerRegion = [];
      this.statCccPerCountry = [];
      this.dispCccPerCountry = [];

      ctrl = this;
    }

    $onInit() {
      this.uploadsPerRegions();
      this.uploadsPerCountries();
      this.uploadsPerUsers();
      this.cccPerRegion();
      this.cccPerCountry();
    }

    uploadsPerRegions() {
      ctrl.$http.get('/api/statistics/uploads/regions').then(response => {
        ctrl.statPerRegions = response.data;
        ctrl.dispPerRegions = response.data;
      });
    }

    uploadsPerCountries() {
      ctrl.$http.get('/api/statistics/uploads/countries').then(response => {
        ctrl.statPerCountries = response.data;
        ctrl.dispPerCountries = response.data;
      });
    }

    uploadsPerUsers() {
      ctrl.$http.get('/api/statistics/uploads/users').then(response => {
        ctrl.statPerUsers = response.data;
        ctrl.dispPerUsers = response.data;
      });
    }

    cccPerRegion() {
      ctrl.$http.get('/api/statistics/ccc/regions').then(response => {
        ctrl.statCccPerRegion = response.data;
        ctrl.dispCccPerRegion = response.data;
      });
    }

    cccPerCountry() {
      ctrl.$http.get('/api/statistics/ccc/countries').then(response => {
        ctrl.statCccPerCountry = response.data;
        ctrl.dispCccPerCountry = response.data;
      });
    }
  }

  angular.module('snapshotApp')
    .component('statistics', {
      templateUrl: 'app/statistics/statistics.html',
      controller: StatisticsController,
      controllerAs: 'vm'
    });
})();
