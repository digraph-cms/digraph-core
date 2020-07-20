/**
 * global digraph object is for storing digraph-specific variables that other
 * scripts might need.
 * Also contains some bare-minimum ajax request tools
 */
var digraph = {
  url: '{{config.url.base}}',
  autocomplete: {}
};

/**
 * Ajax GET utility function
 */
digraph.get = function(url, success, error) {
  //set up url
  url = '{{config.url.base}}' + url;
  //set up request
  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  xhr.open('GET', url);
  xhr.onreadystatechange = function() {
    if (xhr.readyState > 3 && xhr.status == 200) {
      if (xhr.status == 200) {
        if (success) {
          success(xhr.responseText);
        }
      } else {
        if (error) {
          error(xhr.status);
        }
      }
    }
  };
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send();
  return xhr;
};

/**
 * Utility function to handle parsing JSON for a get() call
 */
digraph.getJSON = function(url, success, error) {
  return digraph.get(
    url,
    function(text) {
      return success(JSON.parse(text));
    },
    function(text) {
      return error(JSON.parse(text));
    }
  );
}