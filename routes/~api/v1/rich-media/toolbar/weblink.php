<?php

use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\FORM;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\UI\Toolbars\ToolbarLink;

echo new ToolbarLink('', 'web', null, null);
$wrapper = (new FormWrapper())->setID(Digraph::uuid())
    ->setStyle('width', '100%')
    ->setStyle('display', 'contents');
$wrapper->button()->setText('Insert');
$input = (new INPUT(Digraph::uuid()))
    ->addClass('navigation-frame__autofocus');
$wrapper->addChild($input);
echo $wrapper;

?>
<!-- <script>
    (() => {
        var ac = document.getElementById('<?php echo $wrapper->id(); ?>');
        // select value and reset toolbar
        ac.addEventListener('autocomplete-select', (e) => {
            ac.dispatchEvent(Digraph.RichContent.insertTagEvent('link', {
                _: e.autocompleteValue
            }));
            ac.dispatchEvent(new Event('navigation-frame-reset', {
                bubbles: true
            }));
        });
    })();
</script> -->