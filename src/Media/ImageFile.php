<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\FS;
use Joby\Smol\Image\Image;
use Joby\Smol\Image\SmolImage;
use Mimey\MimeTypes;

class ImageFile extends DeferredFile
{

    protected CacheNamespace $cache;

    protected Image $image;

    public static function handles(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp'],
        );
    }

    public function __construct(string $src, string $filename, callable|null $permissions = null)
    {
        $this->src = $src;
        $this->filename = $filename;
        $this->image = SmolImage::load($this->src());
        $this->extension = 'webp';
        $this->content = [$this, 'contentCallback'];
        $this->filename = $filename;
        $this->cache = new CacheNamespace('image-file', $this->ttl());
        $this->permissions = $permissions;
    }

    public function previewBackgroundUrl(): string
    {
        return $this->cache->get(
            'previewbg/' . $this->identifier(),
            function () {
                $clone = clone $this;
                $clone->width(100)
                    ->blur(80)
                    ->jpg();
                return $clone->url();
            }
        );
    }

    public function src(): string
    {
        return $this->src;
    }

    /**
     * Use images.ttl instead of the default files.ttl config option
     *
     * @return integer
     */
    public function ttl(): int
    {
        static $ttl;
        return $ttl ?? $ttl = (Config::get('images.ttl') ?? 3600);
    }

    /**
     * Return a new ImageFile object of the same source as this one,
     * but with all transformations reset.
     *
     * @return ImageFile
     */
    public function image(): ?ImageFile
    {
        return new ImageFile($this->src, $this->filename);
    }

    public function mime(): string
    {
        return (new MimeTypes())->getMimeType($this->extension());
    }

    protected function contentCallback()
    {
        ini_set('memory_limit', '1G');
        $path = $this->path();
        FS::mkdir(dirname($path));
        $this->image
            ->save($path);
    }

    public function __clone()
    {
        $this->written = false;
        $this->content = [$this, 'contentCallback'];
        $this->url = null;
    }

    public function filename(): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', '.' . $this->extension, parent::filename());
    }

    public function extension(string $extension = null): string
    {
        if ($extension) {
            $this->extension = $extension;
        }
        return $this->extension;
    }

    /**
     * Make into a jpg file
     *
     * @return static
     */
    public function jpg()
    {
        $this->extension = 'jpg';
        $this->image = $this->image->jpeg();
        return $this;
    }

    /**
     * Make into a PNG file
     *
     * @return static
     */
    public function png()
    {
        $this->extension = 'png';
        $this->image = $this->image->png();
        return $this;
    }

    /**
     * Make into a webp file
     *
     * @return static
     */
    public function webp()
    {
        $this->extension = 'webp';
        $this->image = $this->image->webp();
        return $this;
    }

    public function identifier(): string
    {
        return md5(serialize([
            $this->image->source,
            $this->image->sizer,
            $this->image->format,
            $this->image->quality,
        ]));
    }

    public function originalWidth(): int
    {
        return $this->image->sourceSize()->width;
    }

    public function originalHeight(): int
    {
        return $this->image->sourceSize()->height;
    }

    /**
     * Resize to a given width in pixels
     *
     * @param integer $width
     * @return static
     */
    public function width(int $width)
    {
        $this->image = $this->image->fit($width, null);
        return $this;
    }

    /**
     * Resize to a given height in pixels
     *
     * @param integer $height
     * @return static
     */
    public function height(int $height)
    {
        $this->image = $this->image->fit(null, $height);
        return $this;
    }

    /**
     * Fit image to given pixel dimensions, without cropping, upscaling if necessary.
     *
     * @param integer $width
     * @param integer $height
     * @return static
     */
    public function fit(int $width, int $height)
    {
        $this->image = $this->image->fit($width, $height);
        return $this;
    }

    /**
     * Scale and crop image to cover the entire dimensions given, upscaling if necessary.
     *
     * @param integer $width
     * @param integer $height
     * @return static
     */
    public function cover(int $width, int $height): static
    {
        $this->image = $this->image->cover($width, $height);
        return $this;
    }

    /**
     * Blur the image by an amount between 0-100, which may be interpreted differently by drivers.
     * 
     * @param int<0,100>|null $blur
     */
    public function blur(int|null $blur = 80): static
    {
        $this->image = $this->image->blur($blur);
        return $this;
    }

}
