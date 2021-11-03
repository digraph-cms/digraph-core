<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\Editor\Editor;
use DigraphCMS\Embedding\ErrorEmbed;
use DigraphCMS\Embedding\ImageEmbed;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

class ImageBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/image.js');
    }

    protected static function jsClass(): string
    {
        return 'ImageTool';
    }

    protected static function jsConfig(): array
    {
        $fileUrl = new URL('/~api/v1/editor/image_file.php');
        $fileUrl->arg('csrf', Cookies::csrfToken('editor'));
        $urlUrl = new URL('/~api/v1/editor/image_url.php');
        $urlUrl->arg('csrf', Cookies::csrfToken('editor'));
        if (Context::page()) {
            $fileUrl->arg('from', Editor::contextUUID() ?? Context::page()->uuid());
            $urlUrl->arg('from', Editor::contextUUID() ?? Context::page()->uuid());
        }
        return [
            'endpoints' => [
                'byFile' => $fileUrl,
                'byUrl' => $urlUrl
            ]
        ];
    }

    public function doRender(): string
    {
        $caption = $this->caption();
        $classes = $this->classes();
        $file = Filestore::get($this->data()['file']['uuid']);
        if ($image = $file->image()) {
            $figure = new ImageEmbed($image);
            $figure->caption($caption);
        } else {
            $figure = new ErrorEmbed('Error loading image');
        }
        foreach ($classes as $class) {
            $figure->addClass($class);
        }
        return $figure->__toString();
    }

    public function caption(): ?string
    {
        $caption = trim(@$this->data()['caption']);
        if ($caption == '' || $caption == '<br>') {
            return null;
        } else {
            return $caption;
        }
    }

    public function classes(): array
    {
        $classes = [];
        if (@$this->data()['withBorder']) {
            $classes[] = 'withBorder';
        }
        if (@$this->data()['stretched']) {
            $classes[] = 'stretched';
        }
        if (@$this->data()['withBackground']) {
            $classes[] = 'withBackground';
        }
        return $classes;
    }
}
