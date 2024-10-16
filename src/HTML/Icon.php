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
        'add' => ['string' => 'add'],
        'archive' => ['string' => 'archive'],
        'article' => ['string' => 'article'],
        'bold' => ['string' => 'format_bold'],
        'bookmark' => ['string' => 'bookmark'],
        'calendar' => ['string' => 'calendar_today'],
        'cancel' => ['string' => 'cancel'],
        'clear-format' => ['string' => 'format_clear'],
        'close' => ['string' => 'close'],
        'code' => ['string' => 'code'],
        'copy' => ['string' => 'content_copy'],
        'database' => ['string' => '&#xeeff;', 'type' => 'icofont'],
        'delete' => ['string' => 'delete'],
        'done-all' => ['string' => 'done_all'],
        'download' => ['string' => 'file_download'],
        'edit' => ['string' => 'edit'],
        'expand-more' => ['string' => 'expand_more'],
        'expand-less' => ['string' => 'expand_less'],
        'event' => ['string' => 'event'],
        'facebook' => ['string' => '&#xed37;', 'type' => 'icofont'],
        'feed' => ['string' => 'rss_feed'],
        'filter' => ['string' => 'filter_alt'],
        'heading' => ['string' => 'title'],
        'hide' => ['string' => 'visibility_off'],
        'highlight' => ['string' => 'highlight_alt'],
        'important' => ['string' => 'label_important'],
        'image' => ['string' => 'image'],
        'inbox' => ['string' => 'inbox'],
        'insert-chart' => ['string' => 'insert_chart'],
        'instagram' => ['string' => '&#xed46;', 'type' => 'icofont'],
        'italic' => ['string' => 'format_italic'],
        'link' => ['string' => 'link'],
        'list-bullet' => ['string' => 'format_list_bulleted'],
        'list-numbered' => ['string' => 'format_list_numbered'],
        'mark-read' => ['string' => 'mark_email_read'],
        'mark-unread' => ['string' => 'mark_email_unread'],
        'media' => ['string' => 'perm_media'],
        'move-to-inbox' => ['string' => 'move_to_inbox'],
        'next' => ['string' => 'skip_next'],
        'options' => ['string' => 'settings'],
        'pages' => ['string' => 'pages'],
        'pending' => ['string' => 'pending_actions'],
        'person' => ['string' => 'person'],
        'post-add' => ['string' => 'post_add'],
        'preview' => ['string' => '&#xf1c5;'],
        'previous' => ['string' => 'skip_previous'],
        'publish' => ['string' => 'publish'],
        'quote' => ['string' => 'format_quote'],
        'redo' => ['string' => 'redo'],
        'secure' => ['string' => 'lock'],
        'segment' => ['string' => 'segment'],
        'settings-applications' => ['string' => 'settings_applications'],
        'show' => ['string' => 'visibility'],
        'sort' => ['string' => 'sort'],
        'star' => ['string' => 'star'],
        'strikethrough' => ['string' => 'strikethrough_s'],
        'table' => ['string' => 'table_rows'],
        'template' => ['string' => 'snippet_folder'],
        'toc' => ['string' => 'toc'],
        'tune' => ['string' => 'tune'],
        'twitter' => ['string' => '&#xed7a;', 'type' => 'icofont'],
        'widgets' => ['string' => 'widgets'],
        'undo' => ['string' => 'undo'],
        'url' => ['string' => 'link'],
        'user-search' => ['string' => 'person_search'],
        'youtube' => ['string' => '&#xed8b;', 'type' => 'icofont'],
        'zip' => ['string' => 'archive'],
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