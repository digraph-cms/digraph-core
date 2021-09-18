document.addEventListener('DigraphDOMReady', (e) => {
    const inputs = e.target.getElementsByTagName('input');
    for (const i in inputs) {
        if (Object.hasOwnProperty.call(inputs, i)) {
            const input = inputs[i];
            if (input.dataset.autocompleteSource && !input.classList.contains('autocomplete-field')) {
                new DigraphAutocomplete(input);
            }
        }
    }
});

class DigraphAutocomplete {
    /**
     * Constructor is passed only an input element, which will be wrapped and 
     * have an autocomplete interface constructed around it.
     * @param {input} input 
     */
    constructor(input) {
        // prepare input
        this.input = input;
        this.input.classList.add('autocomplete-field');
        this.input.style.display = null;
        this.input.autocompleteObject = this;
        this.input.removeAttribute('required');
        // set up wrapper around input
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('autocomplete-wrapper');
        this.input.parentNode.insertBefore(this.wrapper, this.input);
        // set up selected value container
        this.selected = document.createElement('div');
        this.selected.style.display = 'none';
        this.selected.classList.add('autocomplete-selected-card');
        this.wrapper.appendChild(this.selected);
        this.wrapper.appendChild(this.input);
        // set up selected card
        this.selectedCard = document.createElement('div');
        this.selectedCard.classList.add('selected-card');
        this.selected.appendChild(this.selectedCard);
        // set up selection clearing tool
        this.clearSelection = document.createElement('a');
        this.clearSelection.classList.add('clear-selection');
        this.selected.appendChild(this.clearSelection);
        // set up results block
        this.results = document.createElement('div');
        this.results.style.display = 'none';
        this.results.classList.add('autocomplete-results');
        this.wrapper.appendChild(this.results);
        // insert actual value field with same name/id as original input
        this.value = document.createElement('input');
        this.value.name = this.input.name;
        this.input.name = this.input.name + '__input';
        this.value.id = this.input.id;
        this.input.id = this.input.id + '__input';
        this.value.setAttribute('type', 'hidden');
        this.wrapper.appendChild(this.value);
        // set up debounced events
        this.updateResults = Digraph.debounce(() => { this._doUpdateResults(); });
        this.blurEvent = Digraph.debounce(
            (e) => { this._doBlurEvent(e); },
            1000
        );
        // set up event listeners
        this.input.addEventListener('focus', (e) => { this.focusEvent(e); });
        this.input.addEventListener('blur', (e) => { this.blurEvent(e); });
        this.input.addEventListener('change', (e) => { this.changeHandler(e); });
        this.input.addEventListener('keydown', (e) => { this.keyHandler(e); });
        this.results.addEventListener('click', (e) => { this.resultSelectEvent(e); });
        this.results.addEventListener('select', (e) => { this.resultSelectEvent(e); });
        this.results.addEventListener('mouseover', (e) => { this.resultFocusEvent(e); });
        this.results.addEventListener('focusin', (e) => { this.resultFocusEvent(e); });
        this.results.addEventListener('focusout', (e) => { this.resultBlurEvent(e); });
        this.selectedCard.addEventListener('focusin', (e) => {
            this.input.style.display = null;
            this.input.focus();
        });
        this.clearSelection.addEventListener('click', (e) => {
            this.value.value = '';
            this.selected.style.display = 'none';
            this.input.style.display = null;
            this.input.focus();
        });
        // enter initial no result state
        this.enterNoResultsState();
        this.resultFocused = false;
        // pull existing value if it exists
        if (this.input.dataset.value) {
            const value = JSON.parse(atob(this.input.dataset.value));
            this.selectedCard.innerHTML = '<div class="result-html"><div class="after-icon">' + value.html.html + "</div></div>"
            if (value.html.class) {
                this.selectedCard.firstChild.classList.add(value.html.class);
            }
            this.value.value = value.value;
            this.input.value = '';
            this.selected.style.display = null;
            this.input.style.display = 'none';
        }
    }
    /**
     * @param {Event} e 
     */
    resultSelectEvent(e) {
        var el = e.target;
        while (el && el.parentNode != this.results) {
            el = el.parentNode;
        }
        if (el) {
            this.value.value = el.dataset.value;
            this.selectedCard.innerHTML = el.outerHTML;
            this.selected.style.display = null;
            this.input.style.display = 'none';
            this.results.style.display = 'none';
        }
    }
    /**
     * @param {Event} e 
     */
    resultFocusEvent(e) {
        var el = e.target;
        while (el && el.parentNode != this.results) {
            el = el.parentNode;
        }
        if (el) {
            el.focus();
            this.resultFocused = true;
            this.focusEvent();
        }
    }
    resultBlurEvent(e) {
        this.resultFocused = false;
        this.blurEvent();
    }
    /**
     * @param {Event} e 
     */
    focusEvent(e) {
        this.results.style.display = null;
    }
    /**
     * @param {Event} e 
     */
    _doBlurEvent(e) {
        if (!this.resultFocused && !this.input.matches(':focus')) {
            this.results.style.display = 'none';
        }
    }
    /**
     * @param {Event} e 
     */
    changeHandler(e) {
        this.updateResults();
        this.input.previousValue = this.input.value;
    }
    /**
     * @param {Event} e 
     */
    keyHandler(e) {
        this.updateResults()
    }
    _doUpdateResults() {
        if (this.input.value == this.input.previousValue) {
            return;
        }
        this.input.previousValue = this.input.value;
        if (this.xhr) {
            this.xhr.abort();
            this.enterNormalState();
        }
        // set up XHR and even listeners
        this.enterLoadingState();
        this.xhr = new XMLHttpRequest();
        this.xhr.addEventListener('load', (e) => {
            this.results.innerHTML = '';
            const data = JSON.parse(e.target.response);
            if (data.length == 0) {
                this.enterNoResultsState();
            } else {
                data.forEach((result) => {
                    const li = document.createElement('div');
                    li.tabIndex = 0;
                    li.innerHTML = '<div class="result-html"><div class="after-icon">' + result.html + "</div></div>";
                    li.dataset.value = result.value;
                    if (result.class) {
                        li.classList.add(result.class);
                    }
                    this.results.append(li);
                });
                this.enterNormalState();
            }
        });
        // send query
        const query = new URLSearchParams();
        query.append('csrf', Digraph.getCookie('csrf', 'autocomplete'));
        query.append('query', this.input.value);
        this.xhr.open('GET', this.input.dataset.autocompleteSource + '?' + query.toString());
        this.xhr.send();
    }
    enterLoadingState() {
        this.state = "loading";
        this.enterNormalState();
        this.wrapper.classList.add('loading');
        this.results.classList.add('loading');
    }
    enterErrorState() {
        this.state = "error";
        this.enterLoadingState();
        this.wrapper.classList.add('error');
        this.results.classList.add('error');
    }
    enterNormalState() {
        this.state = "normal";
        this.wrapper.classList.remove('loading');
        this.wrapper.classList.remove('error');
        this.wrapper.classList.remove('noresults');
        this.results.classList.remove('loading');
        this.results.classList.remove('error');
        this.results.classList.remove('noresults');
    }
    enterNoResultsState() {
        this.state = "noresults";
        this.enterLoadingState();
        this.wrapper.classList.add('noresults');
        this.results.classList.add('noresults');
    }
}