/**
 * global digraph object is for storing digraph-specific variables that other
 * scripts might need.
 * Also contains some bare-minimum ajax request tools
 */
var digraph = {
  //store user ID and Session ID
  user: {
    id: null,
    sid: null
  }
};

/**
 * Ajax GET utility function
 * Automatically prepends base URL so that other scripts don't need to deal
 * with it. Also automatically adds the user's SID as a GET variable.
 */
digraph.get = function(url, success, error) {
  //set up url
  url = '{{config.url.base}}' + url;
  //add session ID to url, so that Ajax requests are cached per-user
  if (url.includes('?')) {
    url = url + '&';
  } else {
    url = url + '?';
  }
  url = url + 'sid=' + digraph.user.sid;
  //set up request
  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  xhr.open('GET', url);
  xhr.onreadystatechange = function() {
    if (xhr.readyState > 3 && xhr.status == 200) {
      if (xhr.status == 200) {
        success(xhr.responseText);
      } else {
        error(xhr.status);
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
