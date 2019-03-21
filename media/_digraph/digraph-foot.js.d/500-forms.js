/**
 * Hide slugs with checkbox
 */
$(function() {
  $('.Form div.class-SlugPattern input.class-Checkbox').on('change', function(e) {
    var $input = $(this).closest('div.class-SlugPattern').find('.FieldWrapper-class-Input');
    if ($(this).is(':checked')) {
      $input.show();
    } else {
      $input.hide();
    }
  });
  $('.Form div.class-SlugPattern input.class-Checkbox').trigger('change');
});

/**
 * Published field
 */
document.addEventListener("DOMContentLoaded", function(event) {
  var publishedFields = document.querySelectorAll('.Form .Container.class-Published');
  for (var i = 0; i < publishedFields.length; i++) {
    var container = publishedFields[i];
    var select = container.querySelectorAll('select')[0];
    var datetimes = [].slice.call(container.querySelectorAll('.Container.class-DateAndTime'));
    select.addEventListener('change', function(e) {
      if (select.value == 'date') {
        datetimes.map(function(e) {
          e.classList.remove('hidden');
        });
      } else {
        datetimes.map(function(e) {
          e.classList.add('hidden');
        });
      }
    });
    var event = document.createEvent("HTMLEvents");
    event.initEvent("change", true, true);
    select.dispatchEvent(event);
  }
});

/**
 * Auto-expanding content textareas
 */
document.addEventListener('DOMContentLoaded', function(e) {
  var tas = document.querySelectorAll('.Form .Container.class-Content .class-ContentTextarea');
  for (var i = 0; i < tas.length; i++) {
    var t = tas[i];
    t.addEventListener('keydown', autosize);
    do_autosize(t);
  }

  function autosize() {
    do_autosize(this);
  }

  function do_autosize(el) {
    setTimeout(function() {
      el.style.cssText = 'height:' + el.scrollHeight + 'px';
    }, 0);
  }
});

/**
 * Ordering field inputs
 */
var _sortablelist_el;

function sortablelist_dragOver(e) {
  if (_sortablelist_el.parentNode !== e.target.parentNode)
    return
  if (sortablelist_isBefore(_sortablelist_el, e.target))
    e.target.parentNode.insertBefore(_sortablelist_el, e.target);
  else
    e.target.parentNode.insertBefore(_sortablelist_el, e.target.nextSibling);
}

function sortablelist_dragEnd(e) {
  _sortablelist_el.classList.remove('dragging');
  _sortablelist_el = null;
}

function sortablelist_dragStart(e) {
  e.dataTransfer.effectAllowed = "move";
  var noun = '';
  var $target = $(e.target);
  if (!$target.is('li.form-ordering-item')) {
    $target = $target.parents('li.form-ordering-item');
  }
  console.log($target);
  if (digraph.noun) noun = ':' + digraph.noun;
  e.dataTransfer.setData("text/plain", "[file" + noun + " id=\"" + $target.attr('data-value') + "\"]");
  _sortablelist_el = e.target;
  _sortablelist_el.classList.add('dragging');
}

function sortablelist_isBefore(el1, el2) {
  if (el2.parentNode === el1.parentNode)
    for (var cur = el1.previousSibling; cur; cur = cur.previousSibling)
      if (cur === el2)
        return true;
  return false;
}

function sortablelist_isBefore(el1, el2) {
  if (el2.parentNode === el1.parentNode)
    for (var cur = el1.previousSibling; cur; cur = cur.previousSibling)
      if (cur === el2)
        return true;
  return false;
}

document.addEventListener('DOMContentLoaded', function(e) {
  var fields = document.querySelectorAll('.FieldWrapper-formward-ordering-field');
  for (var i = 0; i < fields.length; i++) {
    (function() {
      //get all the elements we need
      var wrapper = fields[i];
      wrapper.classList.add('js-active');
      var input = wrapper.querySelectorAll('.formward-ordering-field')[0];
      var controlWrapper = document.createElement('div');
      var controlList = document.createElement('ul');
      var opts = JSON.parse(input.getAttribute('data-opts'));
      controlWrapper.appendChild(controlList);
      wrapper.insertAdjacentElement('beforeend', controlWrapper);
      //set up the adding tool
      var addWrapper = document.createElement('div');
      addWrapper.classList.add('add-item');
      controlWrapper.appendChild(addWrapper);
      var addField = document.createElement('input');
      addField.setAttribute('placeholder', 'add item');
      addWrapper.appendChild(addField);
      addField.addEventListener('keypress', function(e) {
        if (e.which == 13) {
          e.preventDefault();
          input.value += '\n' + addField.value;
          addField.value = '';
          syncUp();
        }
      });
      /*
      Set up the functions/listeners we need
       */
      //retrieve the values from the actual input
      var inputValues = function() {
        values = input
          .value
          .split(/[\r\n]+/)
          .map(function(e) {
            return e.trim()
          })
          .filter(function(e) {
            return e != '';
          });
        return values;
      }
      //sync down from the state of the control into the actual input
      var syncDown = function() {
        var items = controlList.querySelectorAll('.form-ordering-item');
        var value = [];
        for (var i = 0; i < items.length; i++)
          if (!items[i].classList.contains('deleted'))
            value.push(items[i].getAttribute('data-value'));
          else
            value.push('DELETE:' + items[i].getAttribute('data-value'));
        input.value = value.join('\n');
      }
      //sync up from the state of the actual input into the control's UL
      var syncUp = function() {
        controlList.innerHTML = '';
        values = inputValues();
        values.map(function(k) {
          var deleted = '';
          if (k.substring(0, 7) == 'DELETE:') {
            k = k.substring(7);
            deleted = ' deleted';
          }
          var v = '[' + k + ']';
          if (opts[k]) {
            v = opts[k];
          }
          controlList.innerHTML += '<li class="form-ordering-item' + deleted + '" draggable="true" data-value="' + k + '" ondragend="sortablelist_dragEnd()" ondragover="sortablelist_dragOver(event)" ondragstart="sortablelist_dragStart(event)">' + v + '<a class="delete-button">delete</a></li>';
        });
        controlList.addEventListener('dragend', syncDown);
        //set up event listeners on delete buttons
        var bs = controlList.querySelectorAll('.delete-button');
        for (var i = 0; i < bs.length; i++) {
          bs[i].addEventListener('click', function(e) {
            e.target.parentNode.classList.toggle('deleted');
            syncDown();
          });
        }
      }
      /*
      Run syncUp immediately
       */
      syncUp();
    }());
  }
});