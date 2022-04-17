<?php

namespace DigraphCMS\Cron;

use DigraphCMS\HTML\Tag;
use DigraphCMS\URL\URL;

class DeferredProgressBar extends Tag
{
    protected $tag = 'div';
    protected $group;
    protected $displayAfter;
    protected $bounceAfter;
    protected $note = 'process will continue to run if this page is closed, but may take longer to complete';

    public function __construct(string $group)
    {
        $this->group = $group;
        $this->id = 'deferred-progress-bar--' . $group;
    }

    public function setDisplayAfter(?string $after)
    {
        $this->displayAfter = $after;
        return $this;
    }

    public function setBounceAfter(?URL $after)
    {
        $this->bounceAfter = $after;
        return $this;
    }

    public function setNote(?string $note)
    {
        $this->note = $note;
        return $this;
    }

    public function children(): array
    {
        return array_filter(array_merge(
            [
                '<noscript><div class="notification notification--error">This progress bar will not function correctly without javascript</div></noscript>',
                '<div class="progress-bar"><span class="progress-bar__indicator"></span><span class="progress-bar__text"></span></div>',
                $this->note ? '<div class="deferred-progress-bar__note">' . $this->note . '</div>' : false
            ],
            parent::children()
        ));
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'data-group' => $this->group,
                'data-display-after' => $this->displayAfter ? base64_encode($this->displayAfter) : null,
                'data-bounce-after' => $this->bounceAfter ? base64_encode($this->bounceAfter) : null
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
