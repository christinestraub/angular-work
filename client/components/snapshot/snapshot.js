angular.module('snapshotApp')
  .controller('SnapshotModalCtrl', function ($uibModalInstance, $http, archive) {
    var $ctrl = this;

    $ctrl.$uibModalInstance = $uibModalInstance;
    $ctrl.$http = $http;
    $ctrl.project_number = '';
    $ctrl.region = '';
    $ctrl.country = '';

    $http.get(`/api/archives/${archive.uuid}`).then(response => {
      $ctrl.archive = response.data;

      $ctrl.project_number = $ctrl.archive.project_number;
      $ctrl.region = $ctrl.archive.region;
      $ctrl.country = $ctrl.archive.country;

      $http.get('/api/regions').then(response => {
        $ctrl.regions = response.data;
        $ctrl.countries = $ctrl.region.countries;
      });
    });

    $ctrl.update = function () {
      if ($ctrl.archive.project_number != $ctrl.project_number) {
        $ctrl.archive.project_number = $ctrl.project_number;
      }
      if ($ctrl.region !== $ctrl.archive.region) {
        $ctrl.archive.region = $ctrl.region.name;
      }
      if ($ctrl.country !== $ctrl.archive.country) {
        $ctrl.archive.country = $ctrl.country.name;
      }
      $ctrl.$uibModalInstance.close($ctrl.archive);
    };

    $ctrl.cancel = function () {
      $uibModalInstance.dismiss('cancel');
    };

    $ctrl.selectRegion = function () {
      $ctrl.countries = $ctrl.region.countries;
      if ($ctrl.countries !== undefined)
        $ctrl.country = $ctrl.countries[0];
    };

    $ctrl.selectCountry = function () {
    };
  });
