<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

class TabInterface
{
    protected $tabs = [];
    protected $defaultTab;
    protected $id;
    protected $arg;
    protected $vertical;

    public function __construct(string $id = null)
    {
        static $counter = 0;
        $this->id = $id ?? $counter++;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function addTab(string $id, string $label, callable $fn)
    {
        $this->tabs[$id] = [$label, $fn];
    }

    public function link(string $id): string
    {
        $activeTab = $id == $this->activeTab() ? 'true' : 'false';
        $currentClass = $id == $this->activeTab() ? ' class="current-tab"' : '';
        return sprintf(
            '<a href="%s" data-target="%s" data-active-tab="%s"%s>%s</a>',
            $this->url($id),
            'tab-interface-' . $this->id,
            $activeTab,
            $currentClass,
            $this->tabs[$id][0]
        );
    }

    public function arg(string $set = null): string
    {
        if ($set !== null) $this->arg = $set;
        return $this->arg ?? '_tab_' . $this->id();
    }

    public function activeTab(): string
    {
        if ($arg = Context::arg($this->arg())) {
            if (isset($this->tabs[$arg])) {
                return $arg;
            }
        }
        return $this->defaultTab();
    }

    public function defaultTab(string $set = null): string
    {
        if ($set) {
            $this->defaultTab = $set;
        }
        return $this->defaultTab ?? @array_shift(array_keys($this->tabs));
    }

    public function url(string $id): URL
    {
        $url = Context::url();
        $url->arg($this->arg(), $id);
        return $url;
    }

    public function vertical(): bool
    {
        if ($this->vertical !== null) return $this->vertical;
        if (count($this->tabs) > 7) return true;
        $words = 0;
        foreach ($this->tabs as $tab) $words += str_word_count($tab[0]);
        return $words > 20;
    }

    /**
     * Set whether tabs should be vertical on the side instead of across the top
     *
     * @param boolean|null $vertical null indicates automatic
     * @return void
     */
    public function setVertical(?bool $vertical)
    {
        $this->vertical = $vertical;
        return $this;
    }

    public function __toString()
    {
        ob_start();
        if (!$this->tabs) {
            Notifications::printError('No tabs defined');
            return ob_get_clean();
        };
        echo '<div class="tab-interface' . ($this->vertical() ? ' tab-interface--vertical' : '') . ' navigation-frame" data-target="_top" id="tab-interface-' . $this->id . '">' . PHP_EOL;
        if (count($this->tabs) > 1) {
            echo '<nav class="tab-interface-tabs">' . PHP_EOL;
            foreach ($this->tabs as $id => $tab) {
                echo $this->link($id) . PHP_EOL;
            }
            echo '</nav>' . PHP_EOL;
        }
        echo '<div class="tab-interface-content">' . PHP_EOL;
        call_user_func($this->tabs[$this->activeTab()][1]);
        echo '</div>' . PHP_EOL;
        echo '</div>' . PHP_EOL;
        return ob_get_clean();
    }
}
