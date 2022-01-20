document.addEventListener('DigraphDOMReady', (e) => {
    const inputs = e.target.getElementsByTagName('input');
    for (const i in inputs) {
        if (Object.hasOwnProperty.call(inputs, i)) {
            const input = inputs[i];
            if (input.classList.contains('ordering-input')) {
                new DigraphOrderingInput(input);
            }
        }
    }
});

class DigraphOrderingInput {
    constructor(input) {
        this.options = {};
        this.dragging = null;
        this.input = input;
        this.input.classList.remove('ordering-input');
        // set up wrapper around input
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('ordering-input');
        this.input.parentNode.insertBefore(this.wrapper, this.input);
        this.wrapper.appendChild(this.input);
        // set up options wrapper
        this.optionList = document.createElement('div');
        this.optionList.classList.add('ordering-input__option-list');
        this.wrapper.appendChild(this.optionList);
        // add initial values
        var initial = JSON.parse(this.input.value);
        var labels = JSON.parse(this.input.dataset.labels);
        for (const i in initial) {
            if (Object.hasOwnProperty.call(initial, i)) {
                const value = initial[i];
                this.addValue(value, labels[value] ?? value);
            }
        }
        // set up event listeners
        this.optionList.addEventListener('dragstart', (e) => { this.dragStart(e); });
        this.optionList.addEventListener('dragover', (e) => { this.dragOver(e); });
        this.optionList.addEventListener('dragend', (e) => { this.dragEnd(e); });
    }
    addValue(value, label) {
        this.removeValue(value);
        var child = document.createElement('div');
        child.classList.add('ordering-input__option');
        child.setAttribute('draggable', 'true');
        child.dataset.value = value;
        child.innerHTML = '<div class="ordering-input__option__label">' + label + '</div>';
        this.optionList.appendChild(child);
        this.options[value] = child;
        // set up delete button if requested
        if (this.input.dataset.allowDeletion == "true") {
            var button = document.createElement('div');
            button.classList.add('ordering-input__option__delete');
            child.appendChild(button);
            button.addEventListener('click', (e) => {
                if (child.classList.contains('ordering-input__option--deleted')) {
                    child.classList.remove('ordering-input__option--deleted');
                } else {
                    child.classList.add('ordering-input__option--deleted');
                }
                this.syncValue();
            });
        }
    }
    removeValue(value) {
        delete (this.options[value]);
    }
    dragStart(e) {
        this.dragging = this.getOptionWrapper(e.target);
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", null);
        this.dragging.classList.add('ordering-input__option--dragging');
        this.optionList.classList.add('ordering-input__option-list--dragging');
    }
    dragOver(e) {
        var target = this.getOptionWrapper(e.target);
        if (this.dragging === null || this.dragging == target) {
            return;
        }
        if (this.isBefore(this.dragging, target)) {
            this.optionList.insertBefore(this.dragging, target);
        } else {
            this.optionList.insertBefore(this.dragging, target.nextSibling);
        }
    }
    dragEnd(e) {
        if (this.dragging !== null) {
            this.dragging.classList.remove('ordering-input__option--dragging');
            this.optionList.classList.remove('ordering-input__option-list--dragging');
            this.dragging = null;
            this.syncValue();
        }
    }
    getOptionWrapper(e) {
        while (!e.classList.contains('ordering-input__option')) {
            e = e.parentNode;
            if (e.classList.contains('ordering-input__option-list')) {
                return null;
            }
        }
        return e;
    }
    isBefore(el1, el2) {
        if (el2.parentNode === el1.parentNode)
            for (var cur = el1.previousSibling; cur; cur = cur.previousSibling)
                if (cur === el2)
                    return true;
        return false;
    }
    syncValue() {
        var value = [];
        var options = this.optionList.getElementsByClassName('ordering-input__option');
        for (const i in options) {
            if (Object.hasOwnProperty.call(options, i)) {
                const element = options[i];
                if (!element.classList.contains('ordering-input__option--deleted')) {
                    value.push(element.dataset.value);
                }
            }
        }
        this.input.value = JSON.stringify(value);
    }
}