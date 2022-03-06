<?php

namespace DigraphCMS\HTML;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\Media\ImageFile;

class ResponsivePicture extends Tag
{
    protected $tag = 'picture';
    protected $void = false;

    protected $image;
    protected $alt;
    protected $expectedWidth = 90;
    protected $maxHeight = 80;
    protected $widthInterval = 200;
    protected $img;

    public function __construct(ImageFile $image, string $alt)
    {
        $this->image = $image;
        $this->setAlt($alt);
        $this->img = new IMG(
            (clone $this->image())
                ->fit(1920, 1080)
                ->optimize()
                ->url(),
            $this->alt()
        );
        $this->addClass('fancyfit');
    }

    protected static function cache(): CacheNamespace
    {
        static $cache;
        return $cache ?? $cache = new CacheNamespace('html/responsivepicture');
    }

    public function attributes(): array
    {
        $w = $this->image()->originalWidth();
        $h = $this->image()->originalHeight();
        return array_merge(
            parent::attributes(),
            [
                'style' => implode(';', [
                    'padding:0',
                    'width:' . ($this->maxHeight * ($w / $h)) . 'vh',
                    'max-width:100%'
                ])
            ]
        );
    }

    public function addChild($child)
    {
        throw new \Exception("Adding children not allowed");
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
    public function alt(): string
    {
        return $this->alt;
    }

    public function image(): ImageFile
    {
        return $this->image;
    }

    protected function sources($webP = false): array
    {
        $sources = [];
        $width = $this->image()->originalWidth();
        $height = $this->image()->originalHeight();
        $ratio = $height / $width;
        do {
            $image = (clone $this->image())
                ->width($width)
                ->optimize();
            if ($webP) {
                $image->webp();
            }
            $height = round($width * $ratio);
            $sources[] = sprintf(
                '<source media="%s" type="%s" srcset="%s" />' . PHP_EOL,
                sprintf(
                    '(min-width: %spx) and (min-height: %spx)',
                    ($width * $this->expectedWidth) / 100,
                    ($height * $this->maxHeight) / 100
                ),
                $image->mime(),
                $image->url()
            );
            $width -= $this->widthInterval;
        } while ($width >= $this->widthInterval);
        return $sources;
    }

    public function children(): array
    {
        return array_merge(
            $this->sources(true),
            $this->sources(),
            [$this->img()]
        );
    }

    public function img(): IMG
    {
        return $this->img;
    }

    public function toString(): string
    {
        return static::cache()->get(
            $this->image()->identifier(),
            function () {
                return parent::toString();
            },
            Config::get('images.ttl')
        );
    }
}
