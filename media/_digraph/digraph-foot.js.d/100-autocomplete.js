// endpoint for returning nouns
digraph.autocomplete.noun = {
    source: digraph.url + '_json/autocomplete-noun',
    source_definitive: digraph.url + '_json/autocomplete-noun-definitive',
    autoFocus: true
};

// endpoint for returning dates/times
digraph.autocomplete.datetime = {
    source: digraph.url + '_json/autocomplete-datetime',
    source_definitive: digraph.url + '_json/autocomplete-datetime?_definitive=true',
    autoFocus: true
};

// endpoint for returning dates
digraph.autocomplete.date = {
    source: digraph.url + '_json/autocomplete-datetime?_date=true',
    source_definitive: digraph.url + '_json/autocomplete-datetime?_date=true&_definitive=true',
    autoFocus: true
};

// endpoint for matching existing values in fields
digraph.autocomplete.fieldvalue = {
    source: digraph.url + '_json/autocomplete-fieldvalue?_token=%token%',
    source_definitive: digraph.url + '_json/autocomplete-fieldvalue?_definitive=true&_token=%token%',
    autoFocus: false
};

$(() => {
    var renderItem = function (item) {
        var $div = $('<div class="autocomplete-item">')
            .append('<div class="autocomplete-item-label">' + item.label + '</div>');
        if (item['url']) {
            $div.append('<div class="autocomplete-item-url">' + item.url + '</div>');
        }
        if (item['desc']) {
            $div.append('<div class="autocomplete-item-desc">' + item.desc + '</div>');
        }
        return $div;
    }
    $('.DigraphAutocomplete').each(function (index) {
        var $this = $(this);
        var $input = $this.find('.AutocompleteActual');
        var $userInput = $this.find('.AutocompleteUser');
        var $selectionWrapper = $('<div class="autocomplete-selection-wrapper"></div>').insertAfter($userInput).hide();
        var $selection = $('<div class="autocomplete-selection" tabindex="0">&nbsp;</div>');
        var $clearButton = $('<a class="autocomplete-clear" title="clear field">clear field</a>');
        $selectionWrapper.append($selection);
        $selectionWrapper.append($clearButton);
        // set up options
        var readyOptions = {};
        var options = digraph.autocomplete[$this.attr('data-autocomplete')];
        for (const key in options) {
            if (options.hasOwnProperty(key)) {
                readyOptions[key] = options[key];
            }
        }
        // set up clear button
        $clearButton.click(function(){
            $input.val('');
            $userInput.val('').show();
            $selection.html('&nbsp;');
            $selectionWrapper.hide();
        });
        // get config token
        var token = $this.attr('data-autocomplete-token');
        readyOptions.source = readyOptions.source.replace('%token%',token);
        readyOptions.source_definitive = readyOptions.source_definitive.replace('%token%',token);
        // add extra args
        var srcArgs = {};
        if ($this.attr('data-srcargs')) {
            srcArgs = JSON.parse($this.attr('data-srcargs'));
        }
        if (srcArgs) {
            for (const key in srcArgs) {
                if (srcArgs.hasOwnProperty(key)) {
                    const val = srcArgs[key];
                    var p = readyOptions.source.indexOf('?') == -1 ? '?' : '&';
                    readyOptions.source = readyOptions.source+p+key+'='+val;
                    p = readyOptions.source_definitive.indexOf('?') == -1 ? '?' : '&';
                    readyOptions.source_definitive = readyOptions.source_definitive+p+key+'='+val;
                }
            }
        }
        // custom select callback for transferring selection to actual field
        let select = readyOptions.select;
        readyOptions.select = function (event, ui) {
            $selection.empty().append(renderItem(ui.item));
            $selectionWrapper.show();
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
        $userInput.keyup(function () {
            $userInput.attr('data-user-val', $userInput.val())
        });
        $selection.focus(function () {
            $selectionWrapper.hide();
            $userInput.show();
            $userInput.focus();
            $userInput.val($userInput.attr('data-user-val'));
            $userInput.autocomplete('search', $userInput.val());
        });
        $userInput.blur(function () {
            $selectionWrapper.show();
            $userInput.hide();
            $userInput.val($userInput.attr('data-user-val'));
        });
        // initiate autocomplete
        $userInput.autocomplete(readyOptions);
        // check for filled value, try to locate from definitive source
        if ($input.val()) {
            $.getJSON(
                readyOptions.source_definitive, {
                    'term': $input.val()
                },
                function (item) {
                    if (item) {
                        readyOptions.select({}, {
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