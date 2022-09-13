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
        this.table = new EditableTable(JSON.parse(this.input.value));
        this.wrapper.appendChild(this.table.wrapper);
        // listeners for syncing data into input field
        this.wrapper.addEventListener('editable-table-layout-change', e => this.syncDataToInput());
        this.wrapper.addEventListener('editable-table-content-change', e => this.syncDataToInput());
        // set up submit listener
        if (this.input.form) {
            this.input.form.addEventListener('submit', e => this.syncDataToInput());
        }
    }
    syncDataToInput() {
        this.input.value = JSON.stringify(this.table.data());
    }
}

class EditableTable {
    constructor(data) {
        // set up basic markup
        this.table = document.createElement('table');
        this.id = this.table.id = 'editable-table--' + Digraph.uuid();
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
            // insert rows and columns specified in data
            this.setData(data);
        } else {
            // insert one row and column per head and body
            this.colCount = 1;
            this.insertRow(this.head);
            this.insertRow(this.body);
        }
    }
    setData(data) {
        this.colCount = 1;
        this._setData(this.head, data.head);
        this._setData(this.body, data.body);
        this.onCellFocus();
    }
    _setData(group, data) {
        group.innerHTML = '';
        for (const r in data) {
            if (Object.hasOwnProperty.call(data, r)) {
                const cells = data[r].row;
                const row_id = data[r].id;
                this.insertRow(group, null, row_id);
                // set cell values
                const row = group.childNodes[group.childNodes.length - 1];
                var col = 0;
                for (const c in cells) {
                    if (Object.hasOwnProperty.call(cells, c)) {
                        const cell_data = cells[c].cell;
                        const cell_id = cells[c].id;
                        if (!row.childNodes[col]) this.insertColumn();
                        var cell = row.childNodes[col];
                        cell.id = cell_id;
                        cell.getElementsByTagName('textarea')[0].value = cell_data;
                        col++;
                    }
                }
            }
        }
    }
    insertRow(group, position, uuid) {
        // set up row object
        var row = document.createElement('tr');
        row.id = uuid ?? Digraph.uuid();
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
        this.table.dispatchEvent(new Event('editable-table-layout-change', { bubbles: true }));
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
        this.table.dispatchEvent(new Event('editable-table-layout-change', { bubbles: true }));
    }
    deleteRow(group, position) {
        group.removeChild(
            group.childNodes[position]
        );
        this.onCellFocus();
        this.table.dispatchEvent(new Event('editable-table-layout-change', { bubbles: true }));
    }
    deleteColumn(position) {
        this.columnCells(position).forEach(
            cell => cell.parentElement.removeChild(cell)
        );
        this.colCount--;
        this.onCellFocus();
        this.table.dispatchEvent(new Event('editable-table-layout-change', { bubbles: true }));
    }
    _makeCell(text, uuid) {
        // set up cell markup
        var cell = document.createElement('td');
        var textarea = document.createElement('textarea');
        if (text) textarea.value = text;
        cell.appendChild(textarea);
        cell.id = uuid ?? Digraph.uuid();
        // set up cell focus/blur/change listeners
        textarea.addEventListener('focus', (e) => this.onCellFocus(cell));
        textarea.addEventListener('blur', (e) => this.onCellBlur(cell));
        textarea.addEventListener('change', (e) => this.onCellChange(cell));
        // set up cell insertTagEvent listener
        cell.addEventListener('rich-content-insert', (e) => {
            if (textarea.value) {
                textarea.value = e.insertWithSelection.replaceAll('{content}', textarea.value);
            } else {
                textarea.value = e.insertWithoutSelection;
            }
            e.stopPropagation();
        });
        return cell;
    }
    onCellFocus(cell) {
        cell = cell ?? this.focused_cell;
        /* clear existing focus */
        if (this.focused_cell || !cell || !cell.parentElement || !cell.parentElement.parentElement) {
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
        if (!cell) return;
        /* record new focus */
        // save focused element hierarchy
        if (cell.parentElement && cell.parentElement.parentElement) {
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
            if (!this.controls.dataset.domloaded) {
                this.focused_cell.dispatchEvent(new Event('DigraphDOMReady', { bubbles: true }));
                this.controls.dataset.domloaded = true;
            }
        }
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
    }
    onCellChange(cell) {
        cell.dispatchEvent(new Event('editable-table-content-change', { bubbles: true }));
    }
    data() {
        return {
            'head': this._groupData(this.head),
            'body': this._groupData(this.body)
        }
    }
    _groupData(group) {
        var data = [];
        Array.from(group.childNodes).forEach(
            r => {
                var row = {
                    id: r.id,
                    row: []
                };
                Array.from(r.childNodes).forEach(
                    c => {
                        row.row.push({
                            id: c.id,
                            cell: c.getElementsByTagName('textarea')[0].value
                        });
                    }
                )
                data.push(row);
            }
        );
        return data;
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
            '<div class="toolbar navigation-frame navigation-frame--stateless" data-target="_frame" id="tb_' + this.id + '" data-initial-source="' + Digraph.config.url + '/~api/v1/toolbar/?frame=tb_' + this.id + '&only=insert">???</div>',
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
        // reset toolbar on escape key (only from inside toolbar itself)
        this.controls.addEventListener('keydown', (e) => {
            if (e.key == 'Escape' || e.key == 'Esc') {
                e.target.dispatchEvent(new Event('navigation-frame-reset', {
                    bubbles: true
                }));
            }
        });
        // toolbar keyboard listeners
        this.table.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.shiftKey) {
                var pressed = (e.ctrlKey ? 'Ctrl-' : '') + (e.shiftKey ? 'Shift-' : '') + e.key.toUpperCase();
                var shortcuts = this.controls.getElementsByClassName('toolbar__button__tooltip__shortcut');
                for (let i = 0; i < shortcuts.length; i++) {
                    const s = shortcuts[i];
                    if (s.innerText == pressed) {
                        s.dispatchEvent(new Event('click', { bubbles: true }));
                        e.preventDefault();
                    }
                }
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
    },
    deleteColumn: (table) => {
        if (table.focused_cell) table.deleteColumn(table.focused_column_number);
    },
    deleteRow: (table) => {
        if (table.focused_cell) table.deleteRow(table.focused_group, table.focused_row_number);
    }
};