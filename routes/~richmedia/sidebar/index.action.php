<?php

use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;

Context::response()->template('chromeless.php');

echo '<div id="' . Context::arg('frame') . '">';

// fancy autocomplete search form
$form = (new FormWrapper())->addClass('autoform');
$form->button()->setText('Add');
$adder = (new AutocompleteInput('rich-media-type-search', new URL('/~api/v1/autocomplete/rich-media-type.php')))
    ->addClass('autocomplete-input--autopopulate')
    ->addClass('autocomplete-input--autosubmit')
    ->setAttribute('placeholder', 'create rich media');
$form->addChild($adder);
echo $form;

// script to launch editor if a new item is created
if ($form->ready()) {
    printf(
        '<script>Digraph.popup("%s","%s");</script>',
        new URL(sprintf(
            '/~richmedia/editor/?add=%s&frame=%s&parent=%s',
            $adder->value(),
            Context::arg('frame'),
            Context::arg('uuid')
        )),
        Digraph::uuid()
    );
}

// display list
$media = RichMedia::select(Context::arg('uuid'))
    ->order('updated DESC');
if (!$media->count()) echo '<p><em>No existing rich media</em></p>';
else {
    $table = new PaginatedTable(
        $media,
        function (AbstractRichMedia $media): array {
            $main = '<strong style="draggable:true;">' . $media->icon() . ' ' . $media->name() . '</strong>';
            return [
                $main,
                $media->hasTuner()
                    ? (new ToolbarLink('Tune embed', 'tune', null, null))
                    ->addClass('toolbar__button--compact-tip')
                    ->setAttribute('onclick', sprintf(
                        'Digraph.popup("%s")',
                        new URL('/~richmedia/tune/?frame=' . Context::arg('frame') . '&uuid=' . $media->uuid())
                    ))
                    : '',
                (new ToolbarLink('Edit', 'edit', null, null))
                    ->addClass('toolbar__button--compact-tip')
                    ->setAttribute('onclick', sprintf(
                        'Digraph.popup("%s")',
                        new URL('/~richmedia/editor/?frame=' . Context::arg('frame') . '&uuid=' . $media->uuid())
                    ))
            ];
        },
    );
    $table->paginator()->perPage(10);
    echo $table;
}

echo '</div>';
