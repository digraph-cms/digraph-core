<?php

namespace DigraphCMS\Embedding;

use DigraphCMS\Media\ImageFile;
use HtmlObjectStrings\GenericTag;

class ImageEmbed extends AbstractEmbed
{
    protected $image, $color, $hash, $height, $width, $aspectRatio;

    public function __construct(ImageFile $image)
    {
        $this->image = $image;
    }

    public function srcHash(): string
    {
        return $this->image->identifier();
    }

    public function color(): ?string
    {
        if (!$this->color) {
            $image = $this->image->clone()->crop(ImageFile::CROP_CENTER, 1, 1);
            $image->write();
            $im = imagecreatefromstring(file_get_contents($image->path()));
            $rgb = imagecolorat($im, 0, 0);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $this->color = "rgb($r,$g,$b)";
        }
        return $this->color;
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
        //generate srcset
        $width = $this->width();
        $srcset = [];
        while ($width >= 200) {
            $image = $this->image->clone()->width($width);
            $srcset[] = $image->url() . ' ' . $width . 'w';
            $width -= 200;
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
        return $img->string();
    }

    public function classes(): array
    {
        return ['media-image'];
    }
}
