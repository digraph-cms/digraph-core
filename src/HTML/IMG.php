<?php
namespace DigraphCMS\HTML;

use DigraphCMS\URL\URL;

class IMG extends Tag
{
    protected $tag = 'img';
    protected $void = true;

    protected $src, $alt;

    public function __construct($src, string $alt)
    {
        $this->setSrc($src);
        $this->setAlt($alt);
    }

    /**
     * Set alt text for image
     *
     * @param string $alt
     * @return void
     */
    public function setAlt(string $alt)
    {
        $this->alt = strip_tags($alt);
    }

    /**
     * Alt text of image
     *
     * @return string
     */
    public function alt(): string {
        return $this->alt;
    }

    /**
     * Set the source to a string or URL
     *
     * @param string|URL $src
     * @return $this
     */
    public function setSrc($src) {
        $this->src = $src;
        return $this;
    }

    /**
     * Source of this img
     *
     * @return string|URL
     */
    public function src()
    {
        return $this->src;
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'src' => $this->src(),
                'alt' => $this->alt()
            ]
        );
    }
}