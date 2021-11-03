<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

class AttachmentBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/attachment.js');
    }

    protected static function jsClass(): string
    {
        return 'AttachesTool';
    }

    protected static function jsConfig(): array
    {
        $endpoint = new URL('/~api/v1/editor/attachment.php');
        $endpoint->arg('csrf', Cookies::csrfToken('editor'));
        if (Context::page()) {
            $endpoint->arg('from', Context::page()->uuid());
        }
        return [
            'endpoint' => $endpoint->__toString()
        ];
    }

    public function doRender(): string
    {
        $file = Filestore::get($this->data()['file']['uuid']);
        if (!$file) {
            return "<p><strong>Error getting file</strong></p>";
        }
        $text = "<a class='attachment-link' href='" . $file->url() . "'>";
        $text .= "<div class='file-info'>";
        $text .= "<strong class='file-label'>" . $this->data()['title'] . "</strong>";
        if ($this->data()['title'] != $file->filename()) {
            $text .= $file->filename() . '<br>';
        }
        $text .= Format::filesize($file->bytes());
        $text .= ", uploaded " . Format::date($file->created());
        $text .= "</div>";
        $text .= "</a>";
        $mime = strtolower(preg_replace('/\/.+$/', '', $file->mime()));
        return "<div class='attachment-block attachment-block-mime-$mime attachment-block-" . $this->data()['file']['extension'] . "'>$text</div>";
    }
}
