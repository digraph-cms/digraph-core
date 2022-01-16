<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;
use DigraphCMS\UI\Theme;

class FORM extends Tag
{
    protected $tag = 'form';

    public function __construct()
    {
        Theme::addInternalPageCss('/forms/*.css');
        Theme::addblockingPageJS('/forms/*.js');
    }
}
