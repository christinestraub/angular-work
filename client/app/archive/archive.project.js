angular.module('snapshotApp')
  .controller('ProjectModalCtrl', function ($uibModalInstance, $http, archive) {
    var $ctrl = this;

    $ctrl.$uibModalInstance = $uibModalInstance;
    $ctrl.$http = $http;
    $ctrl.checked = false;
    $ctrl.archive = archive;
    $ctrl.region = null;
    $ctrl.country = null;

    $http.get('/api/regions').then(response => {
      $ctrl.regions = response.data;
      for(let i = 0; i < $ctrl.regions.length; i++) {
        if ($ctrl.archive.region == $ctrl.regions[i].name) {
          $ctrl.region = $ctrl.regions[i];
          break;
        }
      }
      if ($ctrl.region == null) {
        $ctrl.region = $ctrl.regions[0];
      }

      $ctrl.countries = $ctrl.region.countries;
      for(let i = 0; i < $ctrl.countries.length; i++) {
        if ($ctrl.archive.country == $ctrl.countries[i].name) {
          $ctrl.country = $ctrl.countries[i];
          break;
        }
      }
      if ($ctrl.country == null) {
        $ctrl.country = $ctrl.countries[0];
      }
    });

    $ctrl.ok = function () {
      $ctrl.close();
    };

    $ctrl.cancel = function () {
      $uibModalInstance.dismiss('cancel');
    };

    $ctrl.selectRegion = function () {
      $ctrl.countries = $ctrl.region.countries;
      $ctrl.country = $ctrl.countries[0];
    };

    $ctrl.selectCountry = function () {
      console.log($ctrl.archive.country);
    };

    $ctrl.close = function () {
      if ($ctrl.archive.project_number.length > 0) {
        $ctrl.archive.region = $ctrl.region.name;
        $ctrl.archive.country = $ctrl.country.name;
        $ctrl.$uibModalInstance.close($ctrl.archive);
      } else {
        $ctrl.statusMessage = "Please enter the project number.";
      }
    }
  });
