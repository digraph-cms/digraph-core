<?php

use DigraphCMS\Config;
use DigraphCMS\Context;

Context::response()->filename('response.json');

$query = explode(' ', trim(strtolower(Context::arg('query'))));
$query = array_filter(
    $query,
    function ($e) {
        return !!$e;
    }
);

// construct the cards we'll be searching
$cards = [];
foreach (Config::get('rich_media_types') as $name => $class) {
    $cards[] = [
        'html' => '<div class="title">' . (new $class)->icon() . ' ' . $class::className() . '</div><div class="meta">' . $class::description() . '</div>',
        'value' => $name,
        'class' => 'rich-media-type'
    ];
}

// if there's a query filter and sort
if ($query) {
    // filter by whether card text includes
    $cards = array_filter(
        $cards,
        function ($card) use ($query) {
            $text = strtolower(strip_tags($card['html']) . ' ' . $card['value']);
            foreach ($query as $word) {
                if (strpos($text, $word) !== false) {
                    return true;
                }
            }
            return false;
        }
    );

    // sort by ranking
    usort($cards, function ($a, $b) use ($query) {
        $htmlA = strtolower(strip_tags($a['html']));
        $htmlB = strtolower(strip_tags($b['html']));
        $scoreA = $scoreB = 0;
        foreach ($query as $word) {
            $scoreA += substr_count($htmlA, $word) * strlen($word);
            $scoreB += substr_count($htmlB, $word) * strlen($word);
        }
        return $scoreB - $scoreA;
    });
}
// otherwise sort alphabetically
else {
    usort($cards, function ($a, $b) {
        return strcasecmp($a['html'], $b['html']);
    });
}

// echo results
echo json_encode(array_values($cards));
