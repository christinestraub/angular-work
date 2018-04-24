'use strict';

describe('Component: archiveComponent', function () {

  // load the controller's module
  beforeEach(module('snapshotApp'));

  var scope;
  var archiveComponent;
  var $httpBackend;

  // Initialize the controller and a mock scope
  beforeEach(inject(function (_$httpBackend_,
                              $http,
                              $componentController,
                              $rootScope) {
    $httpBackend = _$httpBackend_;
    $httpBackend.expectGET('/api/archive')
      .respond(['HTML5 Boilerplate', 'AngularJS', 'Karma', 'Express']);

    scope = $rootScope.$new();
    archiveComponent = $componentController('main', {
      $http: $http,
      $scope: scope
    });
  }));

  it('should attach a list of things to the controller', function () {
    archiveComponent.$onInit();
    $httpBackend.flush();
    expect(archiveComponent.awesomeThings.length).toBe(4);
  });
});
