angular.module('snapshotApp')
  .controller('OldDeleteCtrl', function ($uibModalInstance, archive) {
    var $ctrl = this;

    $ctrl.$uibModalInstance = $uibModalInstance;
    $ctrl.archive = archive;

    $ctrl.ok = function () {
      $ctrl.$uibModalInstance.close($ctrl.archive);
    };

    $ctrl.cancel = function () {
      $uibModalInstance.dismiss('cancel');
    };
  });

