'use strict';

(function () {

  var ctrl;
  class MainController {

    constructor($http) {
      this.$http = $http;
      this.phpinfo = '';
      ctrl = this;
    }

    $onInit() {
      //this.getPhpInfo();
    }

    getPhpInfo() {
      ctrl.$http.get('/api/info/php').then(response => {
        ctrl.phpinfo = response.data;
      });
    }
  }

  angular.module('snapshotApp')
    .component('main', {
      templateUrl: 'app/main/main.html',
      controller: MainController
    });

})();
