<?php

use DigraphCMS\Content\Blocks\AbstractBlock;
use DigraphCMS\Content\Blocks\Blocks;
use DigraphCMS\Context;
use DigraphCMS\UI\DataLists\QueryList;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Theme;

Context::response()->template('iframe.php');
Theme::addBlockingPageJs('/~blocks/editor-integration.js');
Theme::addBlockingPageCss('/~blocks/editor-integration.css');

$query = Blocks::select(Context::arg('page'))
    ->order('updated DESC');

if (!$query->count()) {
    Notifications::printNotice('No blocks found');
} else {
    echo new QueryList(
        $query,
        function (AbstractBlock $block): string {
            $out = $block->thumbnail();
            $out .= '<div class="attachment-action-buttons">';
            $out .= sprintf(
                '<a class="attachment-insert-button" data-attachment="%s">Insert</a>',
                base64_encode(json_encode($block->array()))
            );
            $url = $block->url_edit();
            $url->query([
                'editor' => Context::arg('editor')
            ]);
            $out .= '<a href="'.$url.'">Edit</a>';
            $out .= '</div>';
            return $out;
        },
        'flex-list'
    );
}
