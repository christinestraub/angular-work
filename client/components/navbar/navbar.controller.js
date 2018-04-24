'use strict';

class NavbarController {
  constructor($location, Auth) {
    //start-non-standard
    this.menu = [
      {
        'title': 'Home',
        'link': '/'
      },
      {
        'title': 'Diagnostics files',
        'link': '/archive'
      },
      {
        'title': 'Search',
        'link': '/snapshot'
      },
      {
        'title': 'History',
        'link': '/history'
      },
      {
        'title': 'Recycled',
        'link': '/recycled'
      }
    ];

    this.adminMenu = [
      {
        'title': 'Old',
        'link': '/old'
      },
      {
        'title': 'Tags',
        'link': '/tag'
      },
      {
        'title': 'Statistics',
        'link': '/statistics'
      },
      {
        'title': 'Admin',
        'link': '/admin'
      }
    ];

    this.isCollapsed = true;

    //end-non-standard
    this.$location = $location;
    this.isLoggedIn = Auth.isLoggedIn;
    this.isAdmin = Auth.isAdmin;
    this.getCurrentUser = Auth.getCurrentUser;
  }

  isActive(route) {
    return route === this.$location.path();
  }
}

angular.module('snapshotApp')
  .controller('NavbarController', NavbarController);
