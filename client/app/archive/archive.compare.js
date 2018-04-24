angular.module('snapshotApp')
  .controller('CompareModalCtrl', function ($uibModalInstance, $http, archive) {
    var $ctrl = this;

    $ctrl.$uibModalInstance = $uibModalInstance;
    $ctrl.$http = $http;
    $ctrl.archive = archive;
    $ctrl.cur_archive = {};
    $ctrl.old_archive = {};
    $ctrl.snapshot = {};

    String.prototype.hashCode = function () {
      var hash = 0, i, chr, len;
      if (this.length === 0) return hash;
      for (i = 0, len = this.length; i < len; i++) {
        chr = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + chr;
        hash |= 0; // convert to 32bit integer
      }
      return hash;
    };

    String.prototype.hashCodeKey = function () {
      return 'k' + Math.abs(this.hashCode());
    };

    var getHashTags = function (snapshot) {
      var hashTags = [];
      var fields = snapshot.fields;

      if (fields === undefined || fields === null) {
        return [];
      }

      for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        for (var j = 0; j < field.tags.length; j++) {
          var tag = field.tags[j];
          var uri = field.path + field.file + tag.tag;
          hashTags[uri.hashCodeKey()] = tag.value;
        }
      }

      return hashTags;
    };

    var mergeSnapshot = function(cur_archive, old_archive) {
      if (cur_archive.snapshot === undefined || cur_archive.snapshot === null)
        return [];
      if (old_archive.snapshot === undefined || old_archive.snapshot === null)
        return [];

      var snapshot = cur_archive.snapshot;
      var hashTags = getHashTags(old_archive.snapshot);
      var fields = snapshot.fields;

      for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        for (var j = 0; j < field.tags.length; j++) {
          var tag = field.tags[j];
          var uri = field.path + field.file + tag.tag;
          var hash = uri.hashCodeKey();
          field.tags[j].old_value = hashTags.hasOwnProperty(hash) ? hashTags[hash]: '';
        }
      }

      return snapshot;
    };

    $http.get('/api/archives/' + archive.uuid).then(response => {
      $ctrl.cur_archive = response.data;
      $http.post('/api/archives/previous', archive).then(response => {
        $ctrl.old_archive = response.data;
        $ctrl.snapshot = mergeSnapshot($ctrl.cur_archive, $ctrl.old_archive);
      });
    });

    $ctrl.cancel = function () {
      $uibModalInstance.dismiss('cancel');
    };
  });
