digraph.autocomplete = {};
digraph.autocomplete.noun = {
    source: digraph.url + '_json/autocomplete-noun'
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
        var $userInputIndex = $this.find('.AutocompleteUserIndex');
        var $selection = $('<div class="autocomplete-selection">').insertAfter($userInput).hide();
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
            if (select) {
                select(event, ui);
            }
        }
        // custom ui events
        $selection.click(function(){
            $selection.empty().hide();
            $input.val('');
            $userInput.val('');
            $userInput.show();
            $userInput.focus();
        });
        // initiate autocomplete
        $userInput.autocomplete(readyOptions);
        // custom rendering
        $userInput.autocomplete("instance")._renderItem = function (ul, item) {
            return $('<li>')
                .append(renderItem(item))
                .appendTo(ul);
        };
    });
});