<?php

use DigraphCMS\Digraph;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\UI\Toolbars\ToolbarLink;

echo new ToolbarLink('', 'web', null, null);
echo $input = (new INPUT(Digraph::uuid()))
    ->addClass('navigation-frame__autofocus');
echo $submit = (new A())
    ->setID(Digraph::uuid())
    ->addClass('button')
    ->addClass('button--inverted')
    ->addChild('Insert');

?>
<script>
    (() => {
        var input = document.getElementById('<?php echo $input->id(); ?>');
        var submit = document.getElementById('<?php echo $submit->id(); ?>');
        var insert_fn = () => {
            input.dispatchEvent(Digraph.RichContent.insertEvent(
                '[{content}]({0})'.format(input.value),
                '[{0}]({1})'.format(
                    input.value.replace(/^(https?:)?\/\//, ''),
                    input.value
                )
            ));
            input.dispatchEvent(new Event('navigation-frame-reset', {
                bubbles: true
            }));
        };
        input.addEventListener('keydown', (e) => {
            if (e.key == 'Enter') {
                insert_fn();
            }
        });
        submit.addEventListener('click', (e) => {
            insert_fn();
        });
    })();
</script>