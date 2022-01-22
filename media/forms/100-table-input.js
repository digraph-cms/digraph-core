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
        this._buildControls();
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
            this.colCount = 1;
            this.insertRow(this.head);
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
        if (position === undefined || position >= group.childNodes.length) {
            group.appendChild(row);
        } else {
            group.insertBefore(row, group.childNodes[position]);
        }
        this.onCellFocus(this.focused_cell);
    }
    insertColumn(position) {
        // insert either at end or given position (0-indexed, conceptually the spaces between columns)
        if (position === undefined || position >= this.colCount) {
            this.columnCells(this.colCount - 1).forEach(
                cell => cell.parentElement.appendChild(this._makeCell())
            );
        } else {
            this.columnCells(position).forEach(
                cell => cell.parentElement.insertBefore(this._makeCell(), cell)
            );
        }
        this.colCount++;
        this.onCellFocus(this.focused_cell);
    }
    _makeCell(uuid) {
        var cell = document.createElement('td');
        var textarea = document.createElement('textarea');
        cell.appendChild(textarea);
        cell.id = uuid ?? Digraph.uuid();
        textarea.addEventListener('focus', (e) => { this.onCellFocus(cell); });
        textarea.addEventListener('blur', (e) => { this.onCellBlur(cell); });
        return cell;
    }
    onCellFocus(cell) {
        cell = cell ?? this.focused_cell;
        if (!cell) return;
        /* clear existing focus */
        if (this.focused_cell) {
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
        this.setFocusClasses();
        // attach controls to this cell
        this.focused_cell.appendChild(this.controls);
    }
    setFocusClasses() {
        // unset all the focus classes
        [
            'column-focused',
            'row-focused',
            'focused-cell',
            'focused-row',
            'add-column-right-hover',
            'add-column-left-hover',
            'add-row-above-hover',
            'add-row-below-hover'
        ].forEach(c => {
            Array.from(this.table.getElementsByClassName(c))
                .forEach(e => e.classList.remove(c));
        });
        // set focus classes
        if (this.focused_cell) {
            this.focused_cell.classList.add('focused-cell');
            this.focused_row.classList.add('focused-row');
            this.focusedColumnCells().forEach(e => e.classList.add('column-focused'));
            this.focusedRowCells().forEach(e => e.classList.add('row-focused'));
        }
    }
    onCellBlur(cell) {
        // i guess does nothing right now?
    }
    focusedColumnCells() {
        if (!this.focused_cell) return [];
        return this.columnCells(this.focused_column_number);
    }
    focusedRowCells() {
        if (!this.focused_cell) return [];
        return Array.from(
            this.focused_row.getElementsByTagName('td')
        );
    }
    columnCells(column) {
        return Array.from(this.table.getElementsByTagName('tr'))
            .map(tr => tr.getElementsByTagName('td')[column]);
    }
    _buildControls() {
        // basic markup
        this.controls = document.createElement('div');
        this.controls.classList.add('editable-table__controls');
        this.controls.innerHTML = [
            // start top controls
            '<div class="toolbar editable-table__controls__top">',
            '<span class="toolbar__spacer"></span>',
            '<a class="toolbar__button toolbar__button--add-row-above" data-command="addRowAbove"><i class="icon icon--material">add</i><div class="toolbar__button__tooltip">add row</div></a>',
            '</div>',
            // end top controls
            // start bottom controls
            '<div class="toolbar editable-table__controls__bottom">',
            '<span class="toolbar__spacer"></span>',
            '<a class="toolbar__button toolbar__button--warning toolbar__button--delete-column" data-command="deleteColumn"><i class="icon icon--material">delete</i><div class="toolbar__button__tooltip">delete column</div></a>',
            '<a class="toolbar__button toolbar__button--add-row-below" data-command="addRowBelow"><i class="icon icon--material">add</i><div class="toolbar__button__tooltip">add row</div></a>',
            '</div>',
            // end bottom controls
            // start left controls
            '<div class="toolbar toolbar--vertical editable-table__controls__left">',
            '<a class="toolbar__button toolbar__button--add-column-left" data-command="addColumnBefore"><i class="icon icon--material">add</i><div class="toolbar__button__tooltip">add column</div></a>',
            '</div>',
            // end left controls
            // start right controls
            '<div class="toolbar toolbar--vertical editable-table__controls__right">',
            '<a class="toolbar__button toolbar__button--add-column-right" data-command="addColumnAfter"><i class="icon icon--material">add</i><div class="toolbar__button__tooltip">add column</div></a>',
            '<a class="toolbar__button toolbar__button--warning toolbar__button--delete-row" data-command="deleteRow"><i class="icon icon--material">delete</i><div class="toolbar__button__tooltip">delete row</div></a>',
            '</div>'
            // end right controls
        ].join('');
        // delete row listeners, to highlight row
        var delete_row = this.controls.getElementsByClassName('toolbar__button--delete-row')[0];
        delete_row.addEventListener('mouseover', e => {
            this.focusedRowCells().forEach(cell => cell.classList.add('delete-hover'));
        });
        delete_row.addEventListener('mouseout', e => {
            this.focusedRowCells().forEach(cell => cell.classList.remove('delete-hover'));
        });
        // delete column listeners, to highlight column
        var delete_column = this.controls.getElementsByClassName('toolbar__button--delete-column')[0];
        delete_column.addEventListener('mouseover', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.add('delete-hover'));
        });
        delete_column.addEventListener('mouseout', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.remove('delete-hover'));
        });
        // add column left listeners to highlight edge
        var add_column_left = this.controls.getElementsByClassName('toolbar__button--add-column-left')[0];
        add_column_left.addEventListener('mouseover', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.add('add-column-left-hover'));
        });
        add_column_left.addEventListener('mouseout', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.remove('add-column-left-hover'));
        });
        // add column right listeners to highlight edge
        var add_column_right = this.controls.getElementsByClassName('toolbar__button--add-column-right')[0];
        add_column_right.addEventListener('mouseover', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.add('add-column-right-hover'));
        });
        add_column_right.addEventListener('mouseout', e => {
            this.focusedColumnCells().forEach(cell => cell.classList.remove('add-column-right-hover'));
        });
        // add row above listeners to highlight edge
        var add_row_above = this.controls.getElementsByClassName('toolbar__button--add-row-above')[0];
        add_row_above.addEventListener('mouseover', e => {
            this.focusedRowCells().forEach(cell => cell.classList.add('add-row-above-hover'));
        });
        add_row_above.addEventListener('mouseout', e => {
            this.focusedRowCells().forEach(cell => cell.classList.remove('add-row-above-hover'));
        });
        // add row below listeners to highlight edge
        var add_row_below = this.controls.getElementsByClassName('toolbar__button--add-row-below')[0];
        add_row_below.addEventListener('mouseover', e => {
            this.focusedRowCells().forEach(cell => cell.classList.add('add-row-below-hover'));
        });
        add_row_below.addEventListener('mouseout', e => {
            this.focusedRowCells().forEach(cell => cell.classList.remove('add-row-below-hover'));
        });
        // general purpose command listener
        this.controls.addEventListener('click', e => {
            var target = e.target;
            while (!target.classList.contains('toolbar__button')) {
                target = target.parentElement;
                if (target == this.controls) return;
            }
            if (target.dataset.command && this.commands[target.dataset.command]) {
                (this.commands[target.dataset.command])(this);
            }
        });
        // add to wrapper
        this.wrapper.appendChild(this.controls);
    }
}

EditableTable.prototype.commands = {
    addRowAbove: (table) => {
        if (table.focused_cell) table.insertRow(table.focused_group, table.focused_row_number);
    },
    addRowBelow: (table) => {
        if (table.focused_cell) table.insertRow(table.focused_group, table.focused_row_number + 1);
    },
    addColumnBefore: (table) => {
        if (table.focused_cell) table.insertColumn(table.focused_column_number);
    },
    addColumnAfter: (table) => {
        if (table.focused_cell) table.insertColumn(table.focused_column_number + 1);
    }
};