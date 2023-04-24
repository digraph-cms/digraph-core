<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Icon;
use DigraphCMS\URL\URL;

abstract class AbstractColumnFilteringHeader extends ColumnHeader implements FilterToolInterface
{
    protected $id;
    /** @var PaginatedSection|null */
    protected $section;
    protected $column;

    abstract public function toolbox();

    public function statusIcon(): string
    {
        return $this->isActive()
            ? new Icon('filter', 'Filters applied')
            : '';
    }

    /**
     * Column name, converted to a full table.column type string if possible.
     *
     * @return string
     */
    public function column(): string
    {
        if (strpos($this->column, '.')) return $this->column;
        elseif ($this->section->tableName()) return $this->section->tableName() . '.' . $this->column;
        else return $this->column;
    }

    public function __construct(string $label, string $column)
    {
        static $id = 0;
        $this->id = $id++;
        $this->column = $column;
        parent::__construct($label);
    }

    protected function link($config, $text): A
    {
        return (new A($this->url($config)))
            ->setData('target', '_frame')
            ->setStyle('white-space', 'nowrap')
            ->addChild($text);
    }

    protected function form(): FormWrapper
    {
        $form = new FormWrapper('form-' . $this->id);
        $form->addClass('form--small');
        $form->setData('target', '_frame');
        $form->button()->setText('Apply');
        return $form;
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
                    <a href="%s" class="column-filter__icon column-filter__clear" data-target="_frame">%s</a>
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

    protected function url($config): URL
    {
        return $this->section->url(
            $this->getFilterID(),
            $config
        );
    }

    protected function config(string $key = null)
    {
        if (!$this->section) return null;
        if ($key) return @$this->section->getToolConfig($this->getFilterID())[$key];
        else return $this->section->getToolConfig($this->getFilterID());
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
