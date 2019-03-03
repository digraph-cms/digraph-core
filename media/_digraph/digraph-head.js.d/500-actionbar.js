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
    digraph.getJSON(
      '_user/actionbar.json?id=' + actionbar.getAttribute('data-id'),
      function(data) {
        let active = false;
        //set up title
        actionbar.innerHTML = '<div class="digraph-actionbar-title">' + data.title + '</div>';
        //set up links if necessary
        if (data.links.length > 0) {
          actionbar.innerHTML += data.links.join(' ');
          active = true;
        }
        //set up adder select box if necessary
        if (data.addable.length > 0) {
          //set up the field
          let html = '<option value="">{{cms.helper("strings").string("actionbar.adder_cue")}}</option>';
          for (var i = 0; i < data.addable.length; i++) {
            let type = data.addable[i];
            let label = '{{cms.helper("strings").string("actionbar.adder_item")}}';
            label = label.replace('!type', type);
            html += '<option value="' + type + '">' + label + '</option>';
          }
          let adder = document.createElement('select');
          adder.classList.add('actionbar-adder');
          adder.innerHTML = html;
          actionbar.appendChild(adder);
          active = true;
          //set up the event listener
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
      }
    );
  }
});
