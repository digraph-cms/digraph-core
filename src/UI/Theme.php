<?php

namespace DigraphCMS\UI;

/**
 * Manages which CSS/JS/Media are included in the final output after output
 * is templated into a final HTML page. Also responsible for bundling media
 * files where appropriate/configured and for generating the final HTML used
 * to embed things in the HEAD tag and and the end of the BODY tag.
 */
class Theme
{
    /**
     * Generates the markup to embed all linked media in the HEAD tag
     *
     * @return string
     */
    public static function head(): string
    {
        return '';
    }

    /**
     * Generates the markup to embed all linked media at the end of the BODY tag
     *
     * @return string
     */
    public static function body(): string
    {
        return '';
    }
}
