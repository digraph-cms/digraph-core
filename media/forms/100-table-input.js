document.addEventListener('DigraphDOMReady', (e) => {
    const inputs = e.target.getElementsByTagName('input');
    for (const i in inputs) {
        if (Object.hasOwnProperty.call(inputs, i)) {
            const input = inputs[i];
            if (input.classList.contains('table-input')) {
                input.classList.remove('table-input');
                new DigraphTableInput(input);
            }
        }
    }
});

class DigraphTableInput {
    constructor(input) {
        this.input = input;
        // set up wrapper around input
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('table-input');
        this.input.parentNode.insertBefore(this.wrapper, this.input);
        this.wrapper.appendChild(this.input);
        // set up table
        // this.addColumnHTML = "<th class='table-input__table__add-column toolbar'><a class='toolbar__button' tabindex='0' data-command='addColumn'><i class='icon icon--material'>add</i><div class='toolbar__button__tooltip'>Add column</div></a></th>";
        // this.addRowHTML = "<td class='table-input__table__add-column toolbar'><a class='toolbar__button' tabindex='0' data-command='addColumn'><i class='icon icon--material'>add</i><div class='toolbar__button__tooltip'>Add row</div></a></td>";
        this.wrapper.innerHTML = "<table class='table-input__table'></table>" + this.wrapper.innerHTML;
        this.table = this.wrapper.getElementsByClassName('table-input__table')[0];
        this.headRow = this.table.getElementsByClassName('table-input__head-row')[0];
        // sync data
        var data = JSON.parse(this.input.value);
    }
}