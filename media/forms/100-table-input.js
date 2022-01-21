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
        this.table = new EditableTable();
        this.wrapper.appendChild(this.table.wrapper);
        // sync data
        var data = JSON.parse(this.input.value);

    }
}

class EditableTable {
    constructor(data) {
        // set up basic markup
        this.table = document.createElement('table');
        this.table.innerHTML = '<thead></thead><tbody></tbody>';
        this.head = this.table.getElementsByTagName('thead')[0];
        this.body = this.table.getElementsByTagName('tbody')[0];
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('editable-table');
        this.wrapper.appendChild(this.table);
        // controls
        this.controls = document.createElement('div');
        this.controls.classList.add('editable-table__controls');
        this.controls.innerHTML = [
            '<div class="toolbar editable-table__controls__top">top toolbar</div>',
            '<div class="toolbar editable-table__controls__bottom">bottom toolbar</div>',
            '<div class="toolbar toolbar--vertical editable-table__controls__left">left toolbar</div>',
            '<div class="toolbar toolbar--vertical editable-table__controls__right">right toolbar</div>'
        ].join('');
        this.wrapper.appendChild(this.controls);
        // this.controls.
        // blank focus information
        this.focused_group = null;
        this.focused_row = null;
        this.focused_cell = null;
        this.focused_row_number = null;
        this.focused_cell_number = null;
        if (data) {
            // TODO: insert data
        } else {
            // insert one row and column per head and body
            this.colCount = 5;
            this.insertRow(this.head);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
            this.insertRow(this.body);
        }
    }
    insertRow(group, position) {
        // set up row object
        var row = document.createElement('tr');
        row.id = Digraph.uuid();
        // add cells as needed
        for (let i = 0; i < this.colCount; i++) {
            row.appendChild(this._makeCell());
        }
        // insert either at end or given position (0-indexed, conceptually the spaces between rows)
        if (position === undefined) {
            group.appendChild(row);
        }
    }
    _makeCell(uuid) {
        var cell = document.createElement('td');
        var textarea = document.createElement('textarea');
        cell.appendChild(textarea);
        cell.id = uuid ?? Digraph.uuid();
        textarea.addEventListener('focus', (e) => { this.onCellFocus(cell) });
        textarea.addEventListener('blur', (e) => { this.onCellBlur(cell) });
        return cell;
    }
    onCellFocus(cell) {
        /* short circuit if this cell is already focused */
        if (this.focused_cell == cell) return;
        /* clear existing focus */
        if (this.focused_cell) {
            // unset all the focus classes
            this.focused_cell.classList.remove('focused-cell');
            this.focused_row.classList.remove('focused-row');
            Array.from(this.table.getElementsByClassName('column-focused'))
                .forEach(e => e.classList.remove('column-focused'));
            Array.from(this.table.getElementsByClassName('row-focused'))
                .forEach(e => e.classList.remove('row-focused'));
            // save focused element hierarchy
            this.focused_cell = null;
            this.focused_row = null;
            this.focused_group = null;
            // save focused row/column numbers
            this.focused_row_number = null;
            this.focused_column_number = null;
            // detach controls
            this.wrapper.appendChild(this.controls);
        }
        /* record new focus */
        // save focused element hierarchy
        this.focused_cell = cell;
        this.focused_row = cell.parentElement;
        this.focused_group = this.focused_row.parentElement;
        // save focused row/column numbers
        this.focused_row_number = Array.from(this.focused_group.childNodes).indexOf(this.focused_row);
        this.focused_column_number = Array.from(this.focused_row.childNodes).indexOf(this.focused_cell);
        // set focus classes
        this.focused_cell.classList.add('focused-cell');
        this.focused_row.classList.add('focused-row');
        this.focusedColumnCells().forEach(e => e.classList.add('column-focused'));
        this.focusedRowCells().forEach(e => e.classList.add('row-focused'));
        // attach controls to this cell
        this.focused_cell.appendChild(this.controls);
    }
    onCellBlur(cell) {
        // i guess does nothing right now?
    }
    focusedColumnCells() {
        if (!this.focused_cell) return [];
        return Array.from(this.table.getElementsByTagName('tr'))
            .map(tr => tr.getElementsByTagName('td')[this.focused_column_number]);
    }
    focusedRowCells() {
        if (!this.focused_cell) return [];
        return Array.from(
            this.focused_row.getElementsByTagName('td')
        );
    }
}