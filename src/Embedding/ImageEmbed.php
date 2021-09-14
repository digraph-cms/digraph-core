<?php

namespace DigraphCMS\Embedding;

use ColorThief\ColorThief;
use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Media\ImageFile;
use HtmlObjectStrings\GenericTag;

class ImageEmbed extends AbstractEmbed
{
    protected $image, $hash, $height, $width, $aspectRatio, $cache;

    public function __construct(ImageFile $image)
    {
        $this->image = $image;
        $this->cache = new CacheNamespace('image-embed');
    }

    public function srcHash(): string
    {
        return $this->image->identifier();
    }

    public function color(): ?string
    {
        return $this->cache->get(
            md5('colorthief.' . $this->image->src()),
            function () {
                $color = ColorThief::getColor($this->image->src());
                return "rgb(" . implode(',', $color) . ")";
            },
            $this->image->ttl()
        );
    }

    public function height(): ?int
    {
        $this->height = $this->height ?? $this->image->getHeight();
        return $this->height;
    }

    public function width(): ?int
    {
        $this->width = $this->width ?? $this->image->getWidth();
        return $this->width;
    }

    public function aspectRatio(): float
    {
        $this->aspectRatio = $this->aspectRatio ?? $this->height() / $this->width();
        return $this->aspectRatio;
    }

    protected function html(): string
    {
        return $this->cache->get(
            md5(serialize([
                $this->image->src(),
                $this->additionalClasses
            ])),
            function () {
                //generate srcset
                $width = $this->width();
                $srcset = [];
                while ($width >= 200) {
                    $image = $this->image->clone()->width($width);
                    $srcset[] = $image->url() . ' ' . $width . 'w';
                    $width -= 100;
                }
                //build img tag
                $img = new GenericTag();
                $img->tag = 'img';
                $img->selfClosing = true;
                $img->attr('src', $this->image->url());
                $img->attr('style', 'width:100%;height:auto;');
                $img->attr('srcset', implode(',', $srcset));
                $img->attr('loading', 'lazy');
                if ($this->alt) {
                    $img->attr('alt', $this->alt);
                }
                $img = $img->string();
                $full = $this->image->url();
                return "<a target='_lightbox' href='$full'>$img</a>";
            },
            $this->image->ttl()
        );
    }

    public function classes(): array
    {
        return ['media-image'];
    }
}
