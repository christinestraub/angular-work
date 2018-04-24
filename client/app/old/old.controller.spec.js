'use strict';

describe('Component: history', function () {

  // load the controller's module
  beforeEach(module('snapshotApp'));

  var scope;
  var mainComponent;
  var $httpBackend;

  // Initialize the controller and a mock scope
  beforeEach(inject(function (_$httpBackend_,
                              $http,
                              $componentController,
                              $rootScope) {
    $httpBackend = _$httpBackend_;
    $httpBackend.expectGET('/api/history')
      .respond(['HTML5 Boilerplate', 'AngularJS', 'Karma', 'Express']);

    scope = $rootScope.$new();
    mainComponent = $componentController('main', {
      $http: $http,
      $scope: scope
    });
  }));

  it('should attach a list of things to the controller', function () {
    mainComponent.$onInit();
    $httpBackend.flush();
    expect(mainComponent.awesomeThings.length).toBe(4);
  });
});
