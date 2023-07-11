<?php

use DigraphCMS\CodeMirror\YamlArrayInput;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;

echo "<table>";
foreach (Context::page()->metadata() as $k => $v) {
    if (is_array($v)) {
        $v = '<ul>' . implode('', array_map(
            function ($v) {
                return "<li>$v</li>";
            },
            $v
        )
        ) . '</ul>';
    }
    printf('<tr><th>%s</th><td>%s</td></tr>', $k, $v);
}
echo "</table>";

if (!Context::page()->isAdmin()) return;
echo '<div class="navigation-frame navigation-frame--stateless" id="meta-raw-data-editor">';
echo '<h2>Raw data</h2>';
Notifications::printWarning('<strong>Warning:</strong> this tool can permanently delete data, and may put pages into unknown states that produce errors. Use with caution.');
$form = new FormWrapper();
$input = (new YamlArrayInput())
    ->setDefault(Context::page()->get(null));
$field = (new Field('Data', $input))
    ->setRequired(true)
    ->addForm($form);
if ($form->ready()) {
    Context::page()
        ->set(null,[])
        ->set(null,$input->value())
        ->update();
    Notifications::flashConfirmation('Saved page data');
    throw new RefreshException;
}
echo $form;
echo '</div>';