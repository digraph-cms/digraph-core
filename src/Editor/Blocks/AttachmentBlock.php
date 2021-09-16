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

    public static function jsClass(): ?string
    {
        $endpoint = new URL('/~api/v1/editor/attachment.php');
        $endpoint->arg('csrf', Cookies::csrfToken('editor'));
        if (Context::page()) {
            $endpoint->arg('from', Context::page()->uuid());
        }
        return '{ class: AttachesTool, config: { toolboxTitle: "Attached File", endpoint: "' . $endpoint . '" } }';
    }

    public function render(): string
    {
        $file = Filestore::get($this->data()['file']['uuid']);
        if (!$file) {
            return "<p><strong>Error getting file</strong></p>";
        }
        $text = "<a class='attachment-link' href='" . $file->url() . "'>";
        $text .= "<div class='file-info'>";
        $text .= "<strong class='file-label'>" . $this->data()['title'] . "</strong>";
        $text .= "Size: " . Format::filesize($file->bytes());
        $text .= "<br>Uploaded: " . Format::date($file->created());
        $text .= "</div>";
        $text .= "</a>";
        $id = $this->id();
        $mime = strtolower(preg_replace('/\/.+$/', '', $file->mime()));
        return "<div class='attachment-block attachment-block-mime-$mime attachment-block-" . $this->data()['file']['extension'] . " referenceable-block' id='$id'>$text" . PHP_EOL .
            $this->anchorLink() . PHP_EOL .
            "</div>";
    }
}
