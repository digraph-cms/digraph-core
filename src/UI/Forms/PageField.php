<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\Content\Pages;
use DigraphCMS\URL\URL;

class PageField extends Autocomplete
{
    function construct()
    {
        $this->addClass('pages');
        $this->ajaxSource(new URL('/~api/v1/autocomplete/page.php'));
        $this->cardCallback(
            function (string $value): ?string {
                if ($page = Pages::get($value)) {
                    return $page->url()->html();
                }
                return null;
            }
        );
    }
}
