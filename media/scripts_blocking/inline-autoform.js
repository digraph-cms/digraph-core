/**
 * inline-autoforms display as one row, and the submit button doesn't appear until something is changed
 */
document.addEventListener('DigraphDOMReady', (e) => {
    var forms = Array.from(e.target.getElementsByClassName('inline-autoform'));
    if (e.target.classList.contains('inline-autoform')) forms.push(e.target);
    forms.forEach(form => {
        if (form.classList.contains('inline-autoform--js')) return; // this one is already set up
        form.classList.add('inline-autoform--js');
        var submit = form.getElementsByClassName('submit-button')[0];
        submit.style.display = 'none';
        form.addEventListener('change', e => submit.style.display = null);
        form.addEventListener('keyup', e => submit.style.display = null);
    });
});

/**
 * select--autosubmits are select fields that automatically submit their form when a value is picked in them
 */
document.addEventListener('DigraphDOMReady', (e) => {
    Array.from(e.target.getElementsByClassName('select--autosubmit')).forEach(select => {
        if (select.classList.contains('select--autosubmit--js')) return; // this one is already set up
        select.addEventListener('change', e => {
            // submit form
            Digraph.submitForm(select.form);
        });
    });
});
