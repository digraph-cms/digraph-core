$(function() {
  var $ac = $('.FormwardAjaxAutocomplete');
  //run query from query field, display in results div
  var runQuery = function(query, $container) {
    query = query.trim();
    var url = atob($container.data('ajaxsource'));
    var $query = $container.find('input.ajax-field-query');
    var $results = $container.find('div.ajax-field-results');
    var $value = $container.find('select.ajax-field-value');
    if (query !== $container.data('lastquery')) {
      $container.data('lastquery', query);
      url = url.replace('%24q', encodeURIComponent(query));
      $container.data('lasturl', url);
      $results.html('<div class="ajax-loading"></div>');
      $.ajax({
        url: url,
        dataType: 'json',
        success: function(data, status, xhr) {
          if (true || xhr.responseURL == $container.data('lasturl')) {
            var current = $container.data('lastselected');
            if (data.length == 0) {
              $results.html('<div class="ajax-empty"></div>');
              $value.html('');
              $value.val(current);
              return;
            }
            var html = '';
            var select = '';
            for (var k in data) {
              var v = data[k];
              html += '<div data-value="' + k + '" class="ajax-item';
              select += '<option value="' + k + '"';
              if (k == current) {
                html += ' selected';
                select += ' selected';
              }
              html += '">';
              select += '>';
              if (v.html) {
                html += v.html;
                select += v.text;
              } else {
                html += v;
                select += v;
              }
              html += '</div>';
              select += '</option>';
            }
            $results.html(html);
            $value.html(select);
            $value.val(current);
            if (!$value.val()) {
              selectNext($value);
            }
          }
        }
      });
    }
  }
  //select next option
  var selectNext = function($select) {
    var $next = $select.find("[value=\"" + $select.val() + "\"]").next();
    if (!$next.size()) {
      $next = $select.find("option:first-child");
    }
    $select.find('option').removeAttr('selected');
    $next.attr('selected', true);
    $select.val($next.attr('value'));
    $select.trigger('change');
  }
  //select prev option
  var selectPrev = function($select) {
    var $prev = $select.find("[value=\"" + $select.val() + "\"]").prev();
    if ($prev.size()) {
      $next = $select.find("option:first-child");
    }
    $select.find('option').removeAttr('selected');
    $prev.attr('selected', true);
    $select.val($prev.attr('value'));
    $select.trigger('change');
  }
  //set up autocompletes
  $ac.each(function() {
    //set up all the fields and such that we need
    var $container = $(this);
    var url = atob($container.data('ajaxsource'));
    $container.addClass('js-active');
    $container.addClass('blurred');
    var $query = $container.find('input.ajax-field-query');
    var $results = $container.find('div.ajax-field-results');
    var $value = $container.find('select.ajax-field-value');
    //record value that was selected from HTML
    $container.data('lastselected', $value.val());
    //add event listeners on query
    var runQueryT = _.throttle(function() {
      runQuery($query.val(), $container)
    }, 1000, true);
    $query.on('keyup', runQueryT);
    //run query immediately, or add waiting item
    if ($query.val()) {
      runQueryT();
    } else {
      $results.html('<div class="ajax-waiting"></div>');
    }
    //set up event listeners for handling keyboard shortcuts (arrow keys)
    $query.on('keydown', function(e) {
      //down
      if (e.keyCode == 40) {
        selectNext($value);
        e.preventDefault();
        return false;
      }
      //up
      if (e.keyCode == 38) {
        selectPrev($value);
        e.preventDefault();
        return false;
      }
      //return
      if (e.keyCode == 13) {
        e.preventDefault();
        return false;
      }
    });
    //set up event listener for when value select changes
    $value.on('change', function(e) {
      var $this = $(this);
      $results.find('.ajax-item').removeClass('selected');
      $results.find('.ajax-item[data-value="' + $this.val() + '"]').addClass('selected');
      $container.data('lastselected', $this.val());
    });
    //set up event listener for clicks on ajax-items
    $container.on('click', function(e) {
      var $target = $(e.target);
      if ($target.is('.ajax-item')) {
        if ($value.val() == $target.attr('data-value')) {
          $query.focus();
          return;
        }
        $value.val($target.attr('data-value'));
        $value.trigger('change');
      }
    });
    //events for focus/blur
    $query.on('focus', function(e) {
      $container.addClass('focused');
      $container.removeClass('blurred');
    });
    $query.on('blur', function(e) {
      setTimeout(function() {
        $container.addClass('blurred');
        $container.removeClass('focused');
      }, 250);
    });
  });
});
