/**
 * Actions are loaded from a JSON file, by scanning the page for objects with
 * the CSS class digraph-actionbar
 *
 * Actionbars must contain an attribute data-id that indicates the ID/slug of
 * the object to retrieve actions for. Each actionbar on the page will generate
 * its own fetch of the JSON file, but they are cached by default so it
 * shouldn't be a performance problem
 */
document.addEventListener("DOMContentLoaded", function(event) {
  var actionbars = document.getElementsByClassName('digraph-actionbar');
  for (var i = 0; i < actionbars.length; i++) {
    let actionbar = actionbars[i];
    fetch('{{config.url.base}}user/actionbar.json?id=' + actionbar.getAttribute('data-id') + '&sid=' + digraph.user.sid)
      //turn into json
      .then(function(response) {
        return response.json();
      })
      //receive json and put it on the page
      .then(function(data) {
        let active = false;
        //set up links if necessary
        if (data.links.length > 0) {
          actionbar.innerHTML += data.links.join(' ');
          active = true;
        }
        //set up adder select box if necessary
        if (data.addable.length > 0) {
          //set up the field
          let adderID = 'digraph-actionbar-adder-' + i;
          let html = '<select class="actionbar-adder" id="' + adderID + '">';
          html += '<option value="">{{cms.helper("strings").string("actionbar.adder_cue")}}</option>';
          for (var i = 0; i < data.addable.length; i++) {
            let type = data.addable[i];
            let label = '{{cms.helper("strings").string("actionbar.adder_item")}}';
            label = label.replace('!type', type);
            html += '<option value="' + type + '">' + label + '</option>';
          }
          html += '</select>';
          actionbar.innerHTML += html;
          active = true;
          //set up the event listener
          let adder = document.getElementById(adderID);
          adder.addEventListener('change', function(e) {
            if (adder.value != '') {
              let url = data.addable_url + '?type=' + adder.value;
              window.location.href = url;
            }
          });
        }
        //make this actionbar active if necessary
        if (active) {
          actionbar.classList.remove('inactive');
          actionbar.classList.add('active');
        }
      });
  }
});