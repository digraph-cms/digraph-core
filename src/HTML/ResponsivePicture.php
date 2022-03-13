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
    protected $img;
    const BREAKPOINTS = [
        400, 800, 1200, 1600, 2000
    ];
    const DEFAULT_WIDTH = 800;

    public function __construct(ImageFile $image, string $alt)
    {
        $this->image = $image;
        $this->setAlt($alt);
        $this->img = new IMG(
            (clone $this->image())
                ->width(Config::get('images.default_width') ?? static::DEFAULT_WIDTH)
                ->optimize()
                ->url(),
            $this->alt()
        );
        $this->addClass('fancyfit');
    }

    public function setExpectedWidth(int $percent)
    {
        $this->expectedWidth = $percent;
    }

    public function setMaxHeight(int $percent)
    {
        $this->maxHeight = $percent;
    }

    protected static function cache(): CacheNamespace
    {
        static $cache;
        return $cache ?? $cache = new CacheNamespace('html/responsivepicture', Config::get('files.ttl'));
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
                    'max-width:100%',
                    'background-color:' . $this->image()->color()
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
        $originalWidth = $this->image()->originalWidth();
        $originalHeight = $this->image()->originalHeight();
        $ratio = $originalHeight / $originalWidth;
        $lastWidth = 0;
        $lastHeight = 0;
        $image = (clone $this->image())
            ->optimize();
        if ($webP) {
            $image->webp();
        }
        foreach (Config::get('images.breakpoints') ?? static::BREAKPOINTS as $width) {
            if ($width > $originalWidth) {
                break;
            }
            $height = round($width * $ratio);
            $sources[] = sprintf(
                '<source media="%s" type="%s" srcset="%s" />' . PHP_EOL,
                sprintf(
                    '(min-width: %spx) and (min-height: %spx)',
                    ($lastWidth * $this->expectedWidth) / 100,
                    ($lastHeight * $this->maxHeight) / 100
                ),
                $image->mime(),
                $this->srcSet($image, $width)
            );
            $lastWidth = $width;
            $lastHeight = $height;
        }
        return array_reverse($sources);
    }

    protected function srcSet(ImageFile $image, float $width)
    {
        $set = [$image->url()];
        $originalWidth = $image->originalWidth();
        foreach ([1.5, 2, 3] as $x) {
            $newWidth = $x * $width;
            if ($originalWidth >= $newWidth) {
                $resized = (clone $image)
                    ->width($newWidth);
                $set[] = $resized->url() . ' ' . $x . 'x';
            }
        }
        return implode(', ', $set);
    }

    public function children(): array
    {
        return array_merge(
            function_exists('imagewebp') ? $this->sources(true) : [],
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
            md5(serialize([
                $this->image()->identifier(),
                $this->expectedWidth,
                $this->maxHeight,
                $this->alt
            ])),
            function () {
                return parent::toString();
            }
        );
    }
}
