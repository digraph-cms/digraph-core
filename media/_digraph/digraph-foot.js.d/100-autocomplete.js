digraph.autocomplete = {};
digraph.autocomplete.noun = {
    source: digraph.url + '_json/autocomplete-noun',
    source_definitive: digraph.url+'_json/autocomplete-noun-definitive'
};
$(() => {
    var renderItem = function (item) {
        var $div = $('<div class="autocomplete-item">')
            .append('<div class="autocomplete-item-label">' + item.label + '</div>');
        if (item['url']) {
            $div.append('<div class="autocomplete-item-url">' + item.url + '</div>');
        }
        return $div;
    }
    $('.DigraphAutocomplete').each(function (index) {
        var $this = $(this);
        var $input = $this.find('.AutocompleteActual');
        var $wrapper = $('<div class="autocomplete-wrapper">');
        var $userInput = $this.find('.AutocompleteUser');
        var $selection = $('<div class="autocomplete-selection" tabindex="0">').insertAfter($userInput).hide();
        // set up options
        var readyOptions = {};
        var options = digraph.autocomplete[$this.attr('data-autocomplete')];
        for (const key in options) {
            if (options.hasOwnProperty(key)) {
                readyOptions[key] = options[key];
            }
        }
        // custom select callback for transferring selection to actual field
        let select = readyOptions.select;
        readyOptions.select = function (event, ui) {
            $selection.empty().append(renderItem(ui.item)).show();
            $input.val(ui.item.value);
            $userInput.hide();
            $userInput.attr('data-user-val', $userInput.val());
            if (select) {
                select(ui);
            }
        }
        // custom focus callback, mostly to cancel updating user input field
        let focus = readyOptions.focus;
        readyOptions.focus = function (event, ui) {
            if (focus) {
                focus(ui);
            }
            event.preventDefault();
            return false;
        }
        // custom ui events
        $userInput.keyup(function(){
            $userInput.attr('data-user-val', $userInput.val())
        });
        $selection.focus(function(){
            $selection.hide();
            $userInput.show();
            $userInput.focus();
            $userInput.val($userInput.attr('data-user-val'));
            $userInput.autocomplete('search',$userInput.val());
        });
        $userInput.blur(function(){
            $selection.show();
            $userInput.hide();
            $userInput.val($userInput.attr('data-user-val'));
        });
        // initiate autocomplete
        $userInput.autocomplete(readyOptions);
        // check for filled value, try to locate from definitive source
        if ($input.val()) {
            $.getJSON(
                readyOptions.source_definitive,
                {'term': $input.val()},
                function(item) {
                    if (item) {
                        readyOptions.select({},{
                            'item': item
                        });
                    }
                }
            );
        }
        // custom rendering
        $userInput.autocomplete("instance")._renderItem = function (ul, item) {
            return $('<li>')
                .append(renderItem(item))
                .appendTo(ul);
        };
    });
});