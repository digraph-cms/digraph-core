<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Context;
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

    /**
     * Generate a shortcode rendering of this media
     *
     * @param ShortcodeInterface $code
     * @param self $media
     * @return string|null
     */
    public static function shortCode(ShortcodeInterface $code, $media): ?string
    {
        $aside = (new ASIDE)
            ->addChild($media->content()->html())
            ->addClass('aside-media')
            ->setID('aside-' . $media->uuid());
        if ($code->getParameter('block','false') == 'true') {
            $aside->addClass('aside--block');
        }
        return $aside;
    }
}
