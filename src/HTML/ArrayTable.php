<?php

namespace DigraphCMS\HTML;

class ArrayTable extends TABLE
{
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = array_filter($data, function ($e) {
            return !!$e;
        });
    }

    public function children(): array
    {
        return array_merge(
            $this->buildRows(),
            parent::children()
        );
    }

    protected function buildRows(): array
    {
        $rows = [];
        foreach ($this->data as $k => $v) {
            $rows[] = sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                $k,
                $v
            );
        }
        return $rows;
    }
}
