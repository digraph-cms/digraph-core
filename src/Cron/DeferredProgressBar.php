<?php

namespace DigraphCMS\Cron;

use DigraphCMS\HTML\Tag;

class DeferredProgressBar extends Tag
{
    protected $tag = 'div';
    protected $group;

    public function __construct(string $group)
    {
        $this->group = $group;
        $this->id = 'deferred-progress-bar--' . $group;
    }

    public function children(): array
    {
        return array_merge(
            [
                '<noscript><div class="notification notification--error">This progress bar will not function correctly without javascript</div></noscript>',
                '<div class="progress-bar"><span class="progress-bar__indicator"></span><span class="progress-bar__text"></span></div>'
            ],
            parent::children()
        );
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'data-group' => $this->group
            ]
        );
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'deferred-progress-bar',
                'deferred-progress-bar--nojs'
            ]
        );
    }
}
