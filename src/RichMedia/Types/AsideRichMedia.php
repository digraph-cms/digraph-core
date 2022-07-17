<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\HTML\ASIDE;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichContent\RichContentField;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class AsideRichMedia extends AbstractRichMedia
{

    public function prepareForm(FormWrapper $form, $create = false)
    {
        // name input
        $name = (new Field('Name'))
            ->setDefault($this->name())
            ->setRequired(true)
            ->addForm($form);

        // content input
        $content = (new RichContentField('Content', $this->uuid(), true))
            ->setDefault($this->content())
            ->setID('edit-content')
            ->setRequired(true)
            ->addForm($form);

        // callback for taking in values
        $form->addCallback(function () use ($name, $content) {
            $this->name($name->value());
            $this->content($content->value());
        });
    }

    public function content(RichContent $set = null): RichContent
    {
        if ($set) {
            $this['content'] = $set->array();
        }
        return new RichContent($this['content']);
    }

    public static function className(): string
    {
        return 'Aside';
    }

    public static function description(): string
    {
        return 'A block of customizable content contained in a box';
    }

    public function shortCode(ShortcodeInterface $code): ?string
    {
        $aside = (new ASIDE)
            ->addChild($this->content()->html())
            ->addClass('aside-media')
            ->setID('aside-' . $this->uuid());
        if ($code->getParameter('block', 'false') == 'true') {
            $aside->addClass('aside--block');
        }
        return $aside;
    }
}
