<?php

namespace DigraphCMS\HTTP;

class RequestHeaders extends AbstractHeaders
{
    const AUTO_INGEST = [
        'accept-language',
        'x-for-navigation-frame'
    ];
}
