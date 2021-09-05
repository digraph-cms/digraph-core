<?php

namespace DigraphCMS\Editor;

use DigraphCMS\Config;
use DigraphCMS\Editor\Blocks\AbstractBlock;
use DigraphCMS\HTTP\HttpError;

class Blocks
{
    public static function types(): array
    {
        return array_filter(Config::get('editor.blocks'));
    }

    /**
     * Undocumented function
     *
     * @param array|string $data
     */
    public function __construct($data = [])
    {
        if ($data === null) {
            $data = [];
        }
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            throw new HttpError(500, "Failed to decode block data");
        }
        $this->data = $data;
        $this->data['blocks'] = array_map(
            function (array $block): AbstractBlock {
                $class = Config::get("editor.blocks.".$block['type']);
                return new $class($block);
            },
            @$this->data['blocks'] ?? []
        );
    }

    public function render(): string
    {
        $out = '';
        foreach ($this->data['blocks'] as $block) {
            $out .= $block->render() . PHP_EOL;
        }
        return $out;
    }
}
