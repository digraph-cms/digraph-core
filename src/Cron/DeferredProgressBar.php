<?php

namespace DigraphCMS\Cron;

use DigraphCMS\HTML\Tag;

class DeferredProgressBar extends Tag
{
    protected $tag = 'div';
    protected $group;
    protected $completeMessage;

    public function __construct(string $group, string $completeMessage = '')
    {
        $this->group = $group;
        $this->id = 'deferred-progress-bar--' . $group;
    }

    public function children(): array {
        return array_merge(
            ['<div class="progress-bar"><span class="progress-bar__indicator"></span></div>'],
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
                'deferred-progress-bar'
            ]
        );
    }
}
