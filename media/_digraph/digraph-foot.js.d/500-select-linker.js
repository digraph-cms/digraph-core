$(() => {
    var $linkers = $('select.linker');
    $linkers.on('change',(e)=>{
        window.location.href = $(e.target).val();
    });
});