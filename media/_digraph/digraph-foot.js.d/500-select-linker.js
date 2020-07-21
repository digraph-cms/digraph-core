$(() => {
    $('body').on(
        'change',
        'select.linker',
        (e)=>{
            window.location.href = $(e.target).val();
        }
    );
});
