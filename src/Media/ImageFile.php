<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\FS;
use Mimey\MimeTypes;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class ImageFile extends DeferredFile
{
    protected $src, $image, $manipulations, $cache;

    /**
     * This is the default fitting method. The image will be resized to be 
     * contained within the given dimensions respecting the original aspect ratio.
     * @var string
     */
    const FIT_CONTAIN = Manipulations::FIT_CONTAIN;
    /**
     * The image will be resized to be contained within the given dimensions 
     * respecting the original aspect ratio and without increasing the size 
     * above the original image size.
     * @var string
     */
    const FIT_MAX = Manipulations::FIT_MAX;
    /**
     * Like FIT_CONTAIN the image will be resized to be contained within the 
     * given dimensions respecting the original aspect ratio. The remaining 
     * canvas will be filled with a background color.
     * @var string
     */
    const FIT_FILL = Manipulations::FIT_FILL;
    /**
     * The image will be stretched out to the exact dimensions given.
     * @var string
     */
    const FIT_STRETCH = Manipulations::FIT_STRETCH;
    /**
     * The image will be resized to completely cover the given dimensions 
     * respecting the orginal aspect ratio. Some parts of the image may be 
     * cropped out.
     * @var string
     */
    const FIT_CROP = Manipulations::FIT_CROP;
    const CROP_TOP_LEFT = Manipulations::CROP_TOP_LEFT;
    const CROP_TOP_RIGHT = Manipulations::CROP_TOP_RIGHT;
    const CROP_BOTTOM_LEFT = Manipulations::CROP_BOTTOM_LEFT;
    const CROP_BOTTOM_RIGHT = Manipulations::CROP_BOTTOM_RIGHT;
    const CROP_TOP = Manipulations::CROP_TOP;
    const CROP_BOTTOM = Manipulations::CROP_BOTTOM;
    const CROP_LEFT = Manipulations::CROP_LEFT;
    const CROP_RIGHT = Manipulations::CROP_RIGHT;
    const CROP_CENTER = Manipulations::CROP_CENTER;
    const POSITION_TOP_LEFT = Manipulations::POSITION_TOP_LEFT;
    const POSITION_TOP_RIGHT = Manipulations::POSITION_TOP_RIGHT;
    const POSITION_BOTTOM_LEFT = Manipulations::POSITION_BOTTOM_LEFT;
    const POSITION_BOTTOM_RIGHT = Manipulations::POSITION_BOTTOM_RIGHT;
    const POSITION_TOP = Manipulations::POSITION_TOP;
    const POSITION_BOTTOM = Manipulations::POSITION_BOTTOM;
    const POSITION_LEFT = Manipulations::POSITION_LEFT;
    const POSITION_RIGHT = Manipulations::POSITION_RIGHT;
    const POSITION_CENTER = Manipulations::POSITION_CENTER;
    const UNIT_PERCENT = Manipulations::UNIT_PERCENT;
    const UNIT_PIXELS = Manipulations::UNIT_PIXELS;
    /**
     * By default the border will be added as an overlay to the image.
     * @var string
     */
    const BORDER_OVERLAY = Manipulations::BORDER_OVERLAY;
    /**
     * Shrinks the image to fit the border around. The canvas size stays the same.
     * @var string
     */
    const BORDER_SHRINK = Manipulations::BORDER_SHRINK;
    /**
     * Adds the border to the outside of the image and thus expands the canvas.
     * @var string
     */
    const BORDER_EXPAND = Manipulations::BORDER_EXPAND;

    public static function handles(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp']
        );
    }

    public function __construct(string $src, string $filename)
    {
        $this->src = $src;
        $this->filename = $filename;
        $this->image = new Image($this->src);
        $this->image->useImageDriver(extension_loaded('imagick') ? 'imagick' : 'gd');
        $this->manipulations = new Manipulations();
        $this->extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $this->content = function () {
            FS::mkdir(dirname($this->path()));
            $this->image
                ->manipulate($this->manipulations)
                ->save($this->path());
        };
        $this->filename = $filename;
        $this->cache = new CacheNamespace('image-file');
    }

    public function src(): string
    {
        return $this->src;
    }

    public function ttl(): int
    {
        return Config::get('images.ttl') ?? 3600;
    }

    public function image(): ?ImageFile
    {
        return new ImageFile($this->src, $this->filename);
    }

    public function clone(): ?ImageFile
    {
        return clone $this;
    }

    public function mime(): string
    {
        return (new MimeTypes())->getMimeType($this->extension());
    }

    public function __clone()
    {
        $this->image = new Image($this->src);
        $this->manipulations = unserialize(serialize($this->manipulations));
        $this->written = false;
        $this->content = function () {
            FS::mkdir(dirname($this->path()));
            $this->image
                ->manipulate($this->manipulations)
                ->save($this->path());
        };
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

    public function jpg(): ImageFile
    {
        $this->extension = 'jpg';
        return $this;
    }

    public function png(): ImageFile
    {
        $this->extension = 'png';
        return $this;
    }

    public function gif(): ImageFile
    {
        $this->extension = 'gif';
        return $this;
    }

    public function webp(): ImageFile
    {
        $this->extension = 'webp';
        return $this;
    }

    public function identifier(): string
    {
        return md5(serialize([
            $this->src,
            $this->manipulations
        ]));
    }

    public function getWidth(): int
    {
        return $this->cache->get(
            'width/' . $this->identifier(),
            function () {
                return $this->image
                    ->manipulate($this->manipulations)
                    ->getWidth();
            },
            $this->ttl()
        );
    }

    public function getHeight(): int
    {
        return $this->cache->get(
            'height/' . $this->identifier(),
            function () {
                return $this->image
                    ->manipulate($this->manipulations)
                    ->getHeight();
            },
            $this->ttl()
        );
    }

    /**
     * @param string|File $watermark
     * @return ImageFile
     */
    public function watermark($watermark): ImageFile
    {
        if ($watermark instanceof File) {
            $watermark = $watermark->path();
        }
        $this->manipulations->watermark($watermark);
        return $this;
    }

    public function watermarkOpacity(int $watermarkOpacity): ImageFile
    {
        $this->manipulations->watermarkOpacity($watermarkOpacity);
        return $this;
    }

    /**
     * Use class constants like ::POSITION_CENTER, ::POSITION_BOTTOM_RIGHT, etc
     *
     * @param string $watermarkPosition
     * @return ImageFile
     */
    public function watermarkPosition(string $watermarkPosition): ImageFile
    {
        $this->manipulations->watermarkPosition($watermarkPosition);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $xPadding
     * @param integer $yPadding
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return ImageFile
     */
    public function watermarkPadding(int $xPadding, int $yPadding, string $unit): ImageFile
    {
        $this->manipulations->watermarkPadding($xPadding, $yPadding, $unit);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $width
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return ImageFile
     */
    public function watermarkWidth(int $width, string $unit): ImageFile
    {
        $this->manipulations->watermarkWidth($width, $unit);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $height
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return ImageFile
     */
    public function watermarkHeight(int $height, string $unit): ImageFile
    {
        $this->manipulations->watermarkHeight($height, $unit);
        return $this;
    }

    /**
     * Use class constants like FIT_CONTAIN, FIT_CROP etc
     *
     * @param string $watermarkFit
     * @return ImageFile
     */
    public function watermarkFit(string $watermarkFit): ImageFile
    {
        $this->manipulations->watermarkFit($watermarkFit);
        return $this;
    }

    /**
     * Accepts hex colors (without the leading #) and color names
     * 
     * @param string $background
     * @return ImageFile
     */
    public function background(string $background): ImageFile
    {
        $this->manipulations->background($background);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $width
     * @param string $color
     * @param string $borderType class constant like BORDER_SHRINK, BORDER_EXPAND
     * @return ImageFile
     */
    public function border(int $width, string $color, string $borderType): ImageFile
    {
        $this->manipulations->border($width, $color, $borderType);
        return $this;
    }

    const ORIENTATION_90 = Manipulations::ORIENTATION_90;
    const ORIENTATION_180 = Manipulations::ORIENTATION_180;
    const ORIENTATION_270 = Manipulations::ORIENTATION_270;

    /**
     * Accepts class constants ORIENTATION_90, ORIENTATION_180, ORIENTATION_270
     *
     * @param string $orientation
     * @return ImageFile
     */
    public function orientation(string $orientation): ImageFile
    {
        $this->manipulations->orientation($orientation);
        return $this;
    }

    const FLIP_HORIZONTALLY = Manipulations::FLIP_HORIZONTALLY;
    const FLIP_VERTICALLY = Manipulations::FLIP_VERTICALLY;
    const FLIP_BOTH = Manipulations::FLIP_BOTH;

    /**
     * Accepts class constants FLIP_HORIZONTALLY, FLIP_VERTICALLY, FLIP_BOTH
     *
     * @param string $flip
     * @return ImageFile
     */
    public function flip(string $flip): ImageFile
    {
        $this->manipulations->flip($flip);
        return $this;
    }

    public function width(int $width): ImageFile
    {
        $this->manipulations->width($width);
        return $this;
    }

    public function height(int $height): ImageFile
    {
        $this->manipulations->height($height);
        return $this;
    }

    public function fit(string $cropMethod, int $width, int $height): ImageFile
    {
        $this->manipulations->fit($cropMethod, $width, $height);
        return $this;
    }

    public function crop(string $cropMethod, int $width, int $height): ImageFile
    {
        $this->manipulations->crop($cropMethod, $width, $height);
        return $this;
    }

    public function focalCrop(int $width, int $height, int $focalX, int $focalY, float $zoom = 1): ImageFile
    {
        $this->manipulations->focalCrop($width, $height, $focalX, $focalY, $zoom);
        return $this;
    }

    public function manualCrop(int $width, int $height, int $x, int $y): ImageFile
    {
        $this->manipulations->manualCrop($width, $height, $x, $y);
        return $this;
    }

    /**
     * @param integer $brightness -100 to 100
     * @return ImageFile
     */
    public function brightness(int $brightness): ImageFile
    {
        $this->manipulations->brightness($brightness);
        return $this;
    }

    /**
     * @param integer $contrast -100 to 100
     * @return ImageFile
     */
    public function contrast(int $contrast): ImageFile
    {
        $this->manipulations->contrast($contrast);
        return $this;
    }

    /**
     * @param integer $gamma 0.1 to 9.99
     * @return ImageFile
     */
    public function gamma(float $gamma): ImageFile
    {
        $this->manipulations->gamma($gamma);
        return $this;
    }

    public function optimize(): ImageFile
    {
        $this->manipulations->optimize();
        return $this;
    }

    /**
     * @param integer $blur 0 to 100
     * @return ImageFile
     */
    public function blur(int $blur): ImageFile
    {
        $this->manipulations->blur($blur);
        return $this;
    }

    /**
     * @param integer $pixelate 0 to 100
     * @return ImageFile
     */
    public function pixelate(int $pixelate): ImageFile
    {
        $this->manipulations->pixelate($pixelate);
        return $this;
    }

    public function greyscale(): ImageFile
    {
        $this->manipulations->greyscale();
        return $this;
    }

    public function sepia(): ImageFile
    {
        $this->manipulations->sepia();
        return $this;
    }

    /**
     * @param integer $sharpen 0 to 100
     * @return ImageFile
     */
    public function sharpen(int $sharpen): ImageFile
    {
        $this->manipulations->sharpen($sharpen);
        return $this;
    }
}
