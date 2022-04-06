<?php

namespace DigraphCMS\HTML;

use DigraphCMS\Config;

class Icon extends Tag
{
    protected $tag = 'i';
    protected $classes = ['icon'];
    protected $name = 'unspecified';
    protected $alt = 'unspecified icon';
    protected $type = 'material';
    protected $string = '&#xefcc;';
    protected $valid = false;

    const ICONS = [
        'undo' => ['string' => 'undo'],
        'redo' => ['string' => 'redo'],
        'link' => ['string' => 'link'],
        'close' => ['string' => 'close'],
        'copy' => ['string' => 'content_copy'],
        'options' => ['string' => 'settings'],
        'media' => ['string' => 'perm_media'],
        'article' => ['string' => 'article'],
        'hide' => ['string' => 'hide_source'],
        'heading' => ['string' => 'title'],
        'bold' => ['string' => 'format_bold'],
        'italic' => ['string' => 'format_italic'],
        'code' => ['string' => 'code'],
        'quote' => ['string' => 'format_quote'],
        'toc' => ['string' => 'toc'],
        'list-bullet' => ['string' => 'format_list_bulleted'],
        'list-numbered' => ['string' => 'format_list_numbered'],
        'user-search' => ['string' => 'person_search'],
        'insert-chart' => ['string' => 'insert_chart'],
        'post-add' => ['string' => 'post_add'],
        'settings-applications' => ['string' => 'settings_applications'],
        'pages' => ['string' => 'pages'],
        'person' => ['string' => 'person'],
        'strikethrough' => ['string' => 'strikethrough_s'],
        'highlight' => ['string' => 'highlight_alt'],
        'clear-format' => ['string' => 'format_clear'],
        'important' => ['string' => 'label_important'],
        'secure' => ['string' => 'lock'],
        'archive' => ['string' => 'archive'],
        'move-to-inbox' => ['string' => 'move_to_inbox'],
        'mark-read' => ['string' => 'mark_email_read'],
        'mark-unread' => ['string' => 'mark_email_unread'],
        'done-all' => ['string' => 'done_all'],
        'inbox' => ['string' => 'inbox'],
        'delete' => ['string' => 'delete'],
        'star' => ['string' => 'star'],
        'url' => ['string' => 'link'],
        'database' => ['string' => '&#xeeff;', 'type' => 'icofont'],
        'template' => ['string' => 'snippet_folder'],
        'next' => ['string' => 'skip_next'],
        'previous' => ['string' => 'skip_previous'],
    ];

    public function __construct(string $name, string $alt = null)
    {
        $this->setIcon($name);
        if ($alt) {
            $this->setAlt($alt);
        }
    }

    public function setAlt(string $alt)
    {
        $this->alt = $alt;
        return $this;
    }

    public function setIcon($name)
    {
        $icon = Config::get("icons.$name");
        $icon = $icon ?? @static::ICONS[$name];
        if ($icon) {
            $this->name = $name;
            $this->alt = @$icon['alt'] ?? $name;
            $this->type = @$icon['type'] ?? $this->type;
            $this->string = $icon['string'] ?? '&#xefcc;';
            $this->valid = true;
        } else {
            $this->name = 'unknown';
            $this->alt = 'unknown icon "' . $name . '"';
            $this->type = 'icofont';
            $this->string = '&#xefcc;';
            $this->valid = false;
        }
        return $this;
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'title' => $this->alt
            ]
        );
    }

    public function children(): array
    {
        return [
            new Text($this->string)
        ];
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'icon--' . $this->type;
        if (!$this->valid) {
            $classes[] = 'icon--invalid';
        }
        return $classes;
    }
}
