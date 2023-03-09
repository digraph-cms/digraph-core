<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\Fields\RadioListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Icon;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class BookmarkRichMedia extends AbstractRichMedia
{
    public function icon()
    {
        return new Icon('bookmark');
    }

    public static function className(): string
    {
        return 'Bookmark';
    }

    public static function description(): string
    {
        return 'Reusable link to either an internal page or an external URL';
    }

    public function prepareForm(FormWrapper $form, $create = false)
    {
        // name input
        $name = (new Field('Name'))
            ->setDefault($this->name())
            ->setRequired(true)
            ->addTip('May be used as link tooltip and/or link text if no text is specified when embedded')
            ->addForm($form);

        // mode input
        $mode = (new RadioListField('What type of bookmark would you like to create?', [
            'url' => 'Link to any URL on the web',
            'page' => 'Bookmark a page on this site'
        ]))
            ->setRequired(true)
            ->setDefault($this['mode'] ?? 'url')
            ->addForm($form);

        // url input
        $url = (new Field('URL'))
            ->setID('url-input')
            ->setDefault($this['url'])
            ->addForm($form);
        $url->input()
            ->setAttribute('placeholder', 'https://')
            ->addValidator(function () use ($url) {
                if ($url->value() && !filter_var($url->value(), FILTER_VALIDATE_URL)) {
                    return "Please enter a valid URL";
                } else return null;
            });

        // page input
        $page = (new PageField('Page'))
            ->setID('page-input')
            ->setDefault($this['page'])
            ->addForm($form);

        // special validators to ensure the right url/page field is required based on mode
        $url->addValidator(function () use ($url, $mode) {
            if (!$url->value() && $mode->value() == 'url') {
                return "This field is required";
            } else return null;
        });
        $page->addValidator(function () use ($page, $mode) {
            if (!$page->value() && $mode->value() == 'page') {
                return "This field is required";
            } else return null;
        });

        // special scripting for front end visibility
        $form->__toString();
        $url_mode_id = $mode->field('url')->input()->id();
        $page_mode_id = $mode->field('page')->input()->id();
        $url_id = $url->id();
        $page_id = $page->id();
        $form->addChild(<<<SCRIPT
            <script>
                (() => {
                    // get elements
                    var url = document.getElementById('$url_mode_id');
                    var page = document.getElementById('$page_mode_id');
                    var url_field = document.getElementById('$url_id');
                    var page_field = document.getElementById('$page_id');
                    // add event listeners
                    url.addEventListener('change', checkStatus);
                    page.addEventListener('change', checkStatus);
                    // do initial check
                    checkStatus();
                    // status checking 
                    function checkStatus() {
                        url_field.style.display = url.checked ? null : 'none';
                        page_field.style.display = page.checked ? null : 'none';
                    }
                })();
            </script>
            SCRIPT);

        // callback for taking in values
        $form->addCallback(function () use ($name, $mode, $url, $page) {
            $this->name($name->value());
            $this['mode'] = $mode->value();
            $this['url'] = $url->value();
            $this['page'] = $page->value();
        });
    }

    public function shortCode(ShortcodeInterface $code): ?string
    {
        $link = (new A)
            ->addClassString($code->getParameter('class', ''))
            ->setAttribute('title', $this->name());
        if ($this['mode'] == 'url') {
            // arbitrary URL
            $url = $this['url'];
            $link->setAttribute('href', $url);
        } elseif ($this['mode'] == 'page') {
            // link to a page on this site
            if ($this['page'] && $page = Pages::get($this['page'])) {
                $link->setAttribute('href', $page->url())
                    ->setAttribute('title', $page->name());
            } else {
                $link->addClass('link--broken')
                    ->setAttribute('title', 'linked page is missing');
            }
        }
        $link
            ->addChild($code->getContent() ? $code->getContent() : $this->name());
        return $link;
    }
}
