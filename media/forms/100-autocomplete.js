document.addEventListener('DigraphDOMReady', (e) => {
    const inputs = e.target.getElementsByTagName('input');
    for (const i in inputs) {
        if (Object.hasOwnProperty.call(inputs, i)) {
            const input = inputs[i];
            if (input.dataset.autocompleteSource && !input.classList.contains('autocomplete-input')) {
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
        this.input.classList.add('autocomplete-input');
        this.input.style.display = null;
        this.input.autocompleteObject = this;
        this.input.removeAttribute('required');
        // set up wrapper around input
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('autocomplete-input-wrapper');
        this.input.parentNode.insertBefore(this.wrapper, this.input);
        // set up selected value container
        this.selected = document.createElement('div');
        this.selected.style.display = 'none';
        this.selected.classList.add('autocomplete-selected-card');
        this.wrapper.appendChild(this.input);
        this.wrapper.appendChild(this.selected);
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
        this.value.setAttribute('form', this.input.getAttribute('form'));
        this.value.setAttribute('type', 'hidden');
        this.wrapper.appendChild(this.value);
        // set up debounced events
        this.updateResults = Digraph.debounce(() => { this._doUpdateResults(); });
        this.blurEvent = Digraph.debounce(
            (e) => { this._doBlurEvent(e); },
            500
        );
        // enter initial awaiting input state
        this.setState('awaiting-input');
        this.resultFocused = false;
        // pull existing value if it exists
        if (this.input.dataset.value) {
            const value = JSON.parse(this.input.dataset.value);
            this.selectedCard.tabIndex = 0;
            this.selectedCard.innerHTML = '<div class="result-html"><div class="after-icon">' + value.html + "</div></div>"
            if (value.class) {
                this.selectedCard.firstChild.classList.add(value.class);
            }
            this.value.value = value.value;
            this.input.value = '';
            this.selected.style.display = null;
            this.input.style.display = 'none';
        }
        // set up event listeners
        this.wrapper.addEventListener('keydown', (e) => { this.globalKeyDownHandler(e); });
        this.input.addEventListener('focus', (e) => { this.focusEvent(e); });
        this.input.addEventListener('blur', (e) => { this.blurEvent(e); });
        this.input.addEventListener('blur', (e) => { this.wrapper.classList.remove('focused'); });
        this.input.addEventListener('change', (e) => { this.changeHandler(e); });
        this.input.addEventListener('keydown', (e) => { this.keyDownHandler(e); });
        this.results.addEventListener('click', (e) => { this.resultSelectEvent(e); });
        this.results.addEventListener('select', (e) => { this.resultSelectEvent(e); });
        this.results.addEventListener('keypress', (e) => { this.resultKeyPressEvent(e); });
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
            this.selectElement(el);
        }
    }
    selectElement(el) {
        var event = new Event('autocomplete-select', { bubbles: true });
        event.autocompleteValue = el.dataset.value;
        if (el.dataset.extra) {
            event.autocompleteExtra = JSON.parse(el.dataset.extra);
        }
        el.dispatchEvent(event);
        this.value.value = el.dataset.value;
        this.selectedCard.innerHTML = el.outerHTML;
        this.selected.style.display = null;
        this.input.style.display = 'none';
        this.results.style.display = 'none';
        this.wrapper.classList.remove('ui-focused');
        this.wrapper.classList.remove('focused');
    }
    /**
     * @param {Event} e 
     */
    globalKeyDownHandler(e) {
        // do nothing if state isn't normal or any modifiers are pressed
        if (this.state != 'normal' || e.shiftKey || e.altKey || e.ctrlKey) {
            return;
        }
        // down arrow
        else if (e.key == 'ArrowDown' || e.key == 'Down') {
            var next;
            if (next = this.focusedResult_next()) {
                next.focus();
            } else if (this.input == document.activeElement && this.results.childNodes.length) {
                this.results.childNodes[0].focus();
            } else {
                this.input.focus();
            }
            e.preventDefault();
        }
        // up arrow
        else if (e.key == 'ArrowUp' || e.key == 'Up') {
            var previous;
            if (previous = this.focusedResult_previous()) {
                previous.focus();
            } else {
                this.input.focus();
            }
            e.preventDefault();
        }
        // enter key
        else if (e.key == 'Enter') {
            var result;
            // input is currently focused
            if (this.input == document.activeElement) {
                // there are results, select the first one
                if (this.results.childNodes.length) {
                    this.selectElement(this.results.childNodes[0]);
                }
            }
            // input is not focused, we have a result
            else if (result = this.focusedResult()) {
                this.selectElement(result);
            }
            e.preventDefault();
        }
    }
    focusedResult() {
        for (let i = 0; i < this.results.childNodes.length; i++) {
            const result = this.results.childNodes[i];
            if (result == document.activeElement) {
                return result;
            }
        }
        return null;
    }
    focusedResult_next() {
        for (let i = 0; i < this.results.childNodes.length - 1; i++) {
            const result = this.results.childNodes[i];
            if (result == document.activeElement) {
                return this.results.childNodes[i + 1];
            }
        }
        return null;
    }
    focusedResult_previous() {
        for (let i = 1; i < this.results.childNodes.length; i++) {
            const result = this.results.childNodes[i];
            if (result == document.activeElement) {
                return this.results.childNodes[i - 1];
            }
        }
        return null;
    }
    /**
     * @param {Event} e 
     */
    resultKeyPressEvent(e) {
        // keys that select the current item
        if ([13, 32].includes(e.keyCode)) {
            e.preventDefault();
            return this.resultSelectEvent(e);
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
        this.wrapper.classList.add('ui-focused');
        this.wrapper.classList.add('focused');
        if (this.input.classList.contains('autocomplete-input--autopopulate')) this.updateResults();
    }
    /**
     * @param {Event} e 
     */
    _doBlurEvent(e) {
        if (!this.resultFocused && !this.input.matches(':focus')) {
            this.results.style.display = 'none';
            this.wrapper.classList.remove('ui-focused');
            this.wrapper.classList.remove('focused');
            if (this.selected.style.display != 'none') {
                this.input.style.display = 'none';
            }
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
    keyDownHandler(e) {
        this.updateResults()
    }
    _doUpdateResults() {
        if (this.input.value == this.input.previousValue) {
            return;
        }
        this.input.previousValue = this.input.value;
        if (this.xhr) {
            this.xhr.abort();
            this.setState('normal');
        }
        // if input is empty enter awaiting input state
        if (this.input.value.trim() == '' && !this.input.classList.contains('autocomplete-input--autopopulate')) {
            this.setState('awaiting-input');
            this.results.innerHTML = '';
            return;
        }
        // set up XHR and event listeners
        this.setState('loading');
        this.xhr = new XMLHttpRequest();
        this.xhr.addEventListener('load', (e) => {
            this.results.innerHTML = '';
            const data = JSON.parse(e.target.response);
            if (data.length == 0) {
                this.setState('no-results');
            } else {
                data.forEach((result) => {
                    const li = document.createElement('div');
                    li.tabIndex = 0;
                    if (result.extra) li.dataset.extra = JSON.stringify(result.extra);
                    li.innerHTML = '<div class="result-html"><div class="after-icon">' + result.html + "</div></div>";
                    li.dataset.value = result.value;
                    if (result.class) {
                        li.classList.add(result.class);
                    }
                    this.results.append(li);
                });
                this.setState('normal');
            }
        });
        // send query
        const query = new URLSearchParams();
        query.append('csrf', Digraph.getCookie('csrf', 'autocomplete'));
        query.append('query', this.input.value);
        this.xhr.open('GET', this.input.dataset.autocompleteSource + '?' + query.toString());
        this.xhr.send();
    }
    setState(state) {
        this.state = state;
        this.wrapper.dataset.autocompleteState = state;
        this.results.dataset.autocompleteState = state;
        if (state == 'loading') {
            this.results.classList.add('loading');
        } else {
            this.results.classList.remove('loading');
        }
    }
}