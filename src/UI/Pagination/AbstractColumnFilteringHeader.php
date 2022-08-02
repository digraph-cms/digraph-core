<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\Icon;

abstract class AbstractColumnFilteringHeader extends ColumnHeader implements FilterToolInterface
{
    protected $id, $section;

    abstract public function statusIcon(): string;
    abstract public function toolbox(): string;

    public function __construct(string $label)
    {
        static $id = 0;
        $this->id = $id++;
        parent::__construct($label);
    }

    protected function headerContent(): string
    {
        return sprintf(
            <<<EOD
            <div id="f_%s__closed"></div>
            <div class="filtering-header">
                <div class="filtering-header__label">%s</div>
                <div class="column-filter" id="f_%s__open">
                    <span class="column-filter__toggle">
                        <a href="%s" class="column-filter__icon column-filter__toggle__open">%s</a>
                        <a href="%s" class="column-filter__icon column-filter__toggle__close">%s</a>
                    </span>
                    <span class="column-filter__icon column-filter__status">%s</span>
                    <a href="%s" class="column-filter__icon column-filter__clear">%s</a>
                    <div class="column-filter__icon column-filter__toolbox">
                        %s
                    </div>
                </div>
            </div>
            EOD,
            $this->id,
            $this->label,
            $this->id,

            '#f_' . $this->id . '__open',
            $this->toggleOpenIcon(),

            '#f_' . $this->id . '__closed',
            $this->toggleCloseIcon(),

            $this->statusIcon(),

            $this->section->url($this->getFilterID(), null),
            $this->clearIcon(),

            $this->toolbox()
        );
    }

    protected function config()
    {
        return $this->section->getToolConfig($this->getFilterID());
    }

    public function setSection(PaginatedSection $section)
    {
        $this->section = $section;
    }

    public function isActive(): bool
    {
        return $this->config() !== null;
    }

    protected function classes(): array
    {

        if (!$this->isActive()) return [];
        else return ['filters-applied'];
    }

    public function getFilterID(): string
    {
        return 'f' . crc32($this->id);
    }

    protected function toggleOpenIcon(): string
    {
        return new Icon('expand-more', 'Show options');
    }

    protected function toggleCloseIcon(): string
    {
        return new Icon('expand-less', 'Hide options');
    }

    protected function clearIcon(): string
    {
        if (!$this->isActive()) return '';
        else return new Icon('cancel', 'Reset column');
    }
}
