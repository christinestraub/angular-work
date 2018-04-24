angular.module('snapshotApp')
  .controller('TagRemoveCtrl', function ($uibModalInstance, tag) {
    var $ctrl = this;

    $ctrl.$uibModalInstance = $uibModalInstance;
    $ctrl.tag = tag;

    $ctrl.ok = function () {
      $ctrl.$uibModalInstance.close($ctrl.tag);
    };

    $ctrl.cancel = function () {
      $uibModalInstance.dismiss('cancel');
    };
  });

