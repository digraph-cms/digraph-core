/*
Convenience functions for working with popup windows
*/
Digraph.popup = function(url,target) {
  var features = ['popup'];
  features.push('width=800');
  features.push('height=600');
  return window.open(url,target,features.join(','))
}