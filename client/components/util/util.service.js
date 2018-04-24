'use strict';

(function() {

/**
 * The Util service is for thin, globally reusable, utility functions
 */
function UtilService($window) {
  var Util = {
    /**
     * Return a callback or noop function
     *
     * @param  {Function|*} cb - a 'potential' function
     * @return {Function}
     */
    safeCb(cb) {
      return (angular.isFunction(cb)) ? cb : angular.noop;
    },

    /**
     * Parse a given url with the use of an anchor element
     *
     * @param  {String} url - the url to parse
     * @return {Object}     - the parsed url, anchor element
     */
    urlParse(url) {
      var a = document.createElement('a');
      a.href = url;

      // Special treatment for IE, see http://stackoverflow.com/a/13405933 for details
      if (a.host === '') {
        a.href = a.href;
      }

      return a;
    },

    /**
     * Test whether or not a given url is same origin
     *
     * @param  {String}           url       - url to test
     * @param  {String|String[]}  [origins] - additional origins to test against
     * @return {Boolean}                    - true if url is same origin
     */
    isSameOrigin(url, origins) {
      url = Util.urlParse(url);
      origins = (origins && [].concat(origins)) || [];
      origins = origins.map(Util.urlParse);
      origins.push($window.location);
      origins = origins.filter(function(o) {
        return url.hostname === o.hostname &&
          url.port === o.port &&
          url.protocol === o.protocol;
      });
      return (origins.length >= 1);
    },

    getHeaderFilename(headers) {
      var header = headers('content-disposition');
      var result = header.split(';')[1].trim().split('=')[1];
      return result.replace(/"/g, '')
    },

    resourceError($q) {
      var arrayBufferToString = function(buff) {
        var charCodeArray = Array.apply(null, new Uint8Array(buff));
        var result = '';
        for (var i = 0, len = charCodeArray.length; i < len; i++) {
          var code = charCodeArray[i];
          result += String.fromCharCode(code);
        }
        return result;
      };

      return function(error) {
        error.data = angular.fromJson(arrayBufferToString(error.data.data));
        return $q.reject(error);
      }
    },

    msieversion() {
      let ua = window.navigator.userAgent;
      let msie = ua.indexOf("MSIE ");

      return !!(msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./));
    }
  };

  return Util;
}

angular.module('snapshotApp.util')
  .factory('Util', UtilService);

})();
