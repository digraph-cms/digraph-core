<?php

namespace DigraphCMS\Media;

use ColorThief\ColorThief;
use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\FS;
use Mimey\MimeTypes;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

class ImageFile extends DeferredFile
{
    protected $src, $image, $manipulations, $cache;

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
        $this->content = [$this, 'contentCallback'];
        $this->filename = $filename;
        $this->cache = new CacheNamespace('image-file', $this->ttl());
    }

    public function color(): string
    {
        return $this->cache->get(
            'color/' . $this->identifier(),
            function () {
                $this->write();
                return 'rgb('
                    . implode(
                        ',',
                        ColorThief::getColor($this->path())
                    )
                    . ')';
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
        FS::mkdir(dirname($this->path()));
        $this->image
            ->manipulate($this->manipulations)
            ->save($this->path());
        // try to free up some memory
        $this->image = new Image($this->src);
    }

    public function __clone()
    {
        $this->image = new Image($this->src);
        $this->manipulations = unserialize(serialize($this->manipulations));
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
     * @return $this
     */
    public function jpg()
    {
        $this->extension = 'jpg';
        return $this;
    }

    /**
     * Make into a PNG file
     *
     * @return $this
     */
    public function png()
    {
        $this->extension = 'png';
        return $this;
    }

    /**
     * Make into a GIF file
     *
     * @return $this
     */
    public function gif()
    {
        $this->extension = 'gif';
        return $this;
    }

    /**
     * Make into a webp file
     *
     * @return $this
     */
    public function webp()
    {
        $this->extension = 'webp';
        return $this;
    }

    public function identifier(): string
    {
        return md5(serialize([
            $this->src,
            $this->manipulations,
            $this->extension
        ]));
    }

    public function originalWidth(): int
    {
        $exif = @exif_read_data($this->src());
        if ($exif && @$exif['Orientation']) {
            if (in_array($exif['Orientation'], [6, 8])) {
                return $this->image->getHeight();
            }
        }
        return $this->image->getWidth();
    }

    public function originalHeight(): int
    {
        $exif = @exif_read_data($this->src());
        if ($exif && @$exif['Orientation']) {
            if (in_array($exif['Orientation'], [6, 8])) {
                return $this->image->getWidth();
            }
        }
        return $this->image->getHeight();
    }

    /**
     * @param string|File $watermark
     * @return $this
     */
    public function watermark($watermark)
    {
        if ($watermark instanceof File) {
            $watermark = $watermark->path();
        }
        $this->manipulations->watermark($watermark);
        return $this;
    }

    /**
     * Set opacity of watermark
     *
     * @param integer $watermarkOpacity value from 0 to 100
     * @return $this
     */
    public function watermarkOpacity(int $watermarkOpacity)
    {
        $this->manipulations->watermarkOpacity($watermarkOpacity);
        return $this;
    }

    /**
     * Use class constants like ::POSITION_CENTER, ::POSITION_BOTTOM_RIGHT, etc
     *
     * @param string $watermarkPosition
     * @return $this
     */
    public function watermarkPosition(string $watermarkPosition)
    {
        $this->manipulations->watermarkPosition($watermarkPosition);
        return $this;
    }

    /**
     * Set padding around watermark, by default accepts a pixel value
     *
     * @param integer $xPadding left/right padding
     * @param integer $yPadding top/bottom padding
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return $this
     */
    public function watermarkPadding(int $xPadding, int $yPadding, string $unit = 'px')
    {
        $this->manipulations->watermarkPadding($xPadding, $yPadding, $unit);
        return $this;
    }

    /**
     * Width of watermark
     *
     * @param integer $width
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return $this
     */
    public function watermarkWidth(int $width, string $unit = '%')
    {
        $this->manipulations->watermarkWidth($width, $unit);
        return $this;
    }

    /**
     * Height of watermark
     *
     * @param integer $height
     * @param string $unit class constant UNIT_PIXELS or UNIT_PERCENT
     * @return $this
     */
    public function watermarkHeight(int $height, string $unit = '%')
    {
        $this->manipulations->watermarkHeight($height, $unit);
        return $this;
    }

    /**
     * Use class constants like FIT_CONTAIN, FIT_CROP etc
     *
     * @param string $watermarkFit
     * @return $this
     */
    public function watermarkFit(string $watermarkFit)
    {
        $this->manipulations->watermarkFit($watermarkFit);
        return $this;
    }

    /**
     * Accepts hex colors (without the leading #) and color names
     * 
     * @param string $background
     * @return $this
     */
    public function background(string $background)
    {
        $this->manipulations->background($background);
        return $this;
    }

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

    /**
     * Add border around image, by default overlaying it without changing image size
     *
     * @param integer $width
     * @param string $color
     * @param string $borderType class constant like BORDER_SHRINK, BORDER_EXPAND
     * @return $this
     */
    public function border(int $width, string $color, string $borderType = 'overlay')
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
     * @return $this
     */
    public function orientation(string $orientation)
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
     * @return $this
     */
    public function flip(string $flip)
    {
        $this->manipulations->flip($flip);
        return $this;
    }

    /**
     * Resize to a given width in pixels
     *
     * @param integer $width
     * @return $this
     */
    public function width(int $width)
    {
        $this->manipulations->width($width);
        return $this;
    }

    /**
     * Resize to a given height in pixels
     *
     * @param integer $height
     * @return $this
     */
    public function height(int $height)
    {
        $this->manipulations->height($height);
        return $this;
    }

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

    /**
     * Fit image to given pixel dimensions. By default does not crop.
     *
     * @param integer $width
     * @param integer $height
     * @param string $fitMethod
     * @return $this
     */
    public function fit(int $width, int $height, string $fitMethod = 'contain')
    {
        $this->manipulations->fit($fitMethod, $width, $height);
        return $this;
    }

    /**
     * Scale and crop image to cover the entire dimensions given,
     * only upscaling if $upscale is true
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $upscale
     * @return true
     */
    public function cover(int $width, int $height, $upscale = false)
    {
        $this->manipulations->fit(
            $upscale ? static::FIT_FILL : static::FIT_MAX,
            $width,
            $height
        );
    }

    const CROP_TOP_LEFT = Manipulations::CROP_TOP_LEFT;
    const CROP_TOP_RIGHT = Manipulations::CROP_TOP_RIGHT;
    const CROP_BOTTOM_LEFT = Manipulations::CROP_BOTTOM_LEFT;
    const CROP_BOTTOM_RIGHT = Manipulations::CROP_BOTTOM_RIGHT;
    const CROP_TOP = Manipulations::CROP_TOP;
    const CROP_BOTTOM = Manipulations::CROP_BOTTOM;
    const CROP_LEFT = Manipulations::CROP_LEFT;
    const CROP_RIGHT = Manipulations::CROP_RIGHT;
    const CROP_CENTER = Manipulations::CROP_CENTER;

    /**
     * Crop image to given pixel dimensions. Crops to center by default,
     * but this can be overridden with $cropMethod
     *
     * @param integer $width
     * @param integer $height
     * @param string $cropMethod
     * @return $this
     */
    public function crop(int $width, int $height, string $cropMethod = 'crop-center')
    {
        $this->manipulations->crop($cropMethod, $width, $height);
        return $this;
    }

    /**
     * Crop to the given dimensions, centered on the given focal point,
     * optionally zoomed.
     *
     * @param integer $width
     * @param integer $height
     * @param integer $focalX
     * @param integer $focalY
     * @param integer $zoom
     * @return $this
     */
    public function focalCrop(int $width, int $height, int $focalX, int $focalY, float $zoom = 1)
    {
        $this->manipulations->focalCrop($width, $height, $focalX, $focalY, $zoom);
        return $this;
    }

    /**
     * Crop to a given width and height, with the top left corner at
     * the given x/y coordinates
     *
     * @param integer $width
     * @param integer $height
     * @param integer $x
     * @param integer $y
     * @return $this
     */
    public function manualCrop(int $width, int $height, int $x, int $y)
    {
        $this->manipulations->manualCrop($width, $height, $x, $y);
        return $this;
    }

    /**
     * @param integer $brightness -100 to 100
     * @return $this
     */
    public function brightness(int $brightness)
    {
        $this->manipulations->brightness($brightness);
        return $this;
    }

    /**
     * @param integer $contrast -100 to 100
     * @return $this
     */
    public function contrast(int $contrast)
    {
        $this->manipulations->contrast($contrast);
        return $this;
    }

    /**
     * @param integer $gamma 0.1 to 9.99
     * @return $this
     */
    public function gamma(float $gamma)
    {
        $this->manipulations->gamma($gamma);
        return $this;
    }

    /**
     * Optimize file size
     *
     * @return $this
     */
    public function optimize()
    {
        $this->manipulations->optimize();
        return $this;
    }

    /**
     * @param integer $blur 0 to 100
     * @return $this
     */
    public function blur(int $blur)
    {
        $this->manipulations->blur($blur);
        return $this;
    }

    /**
     * @param integer $pixelate 0 to 100
     * @return $this
     */
    public function pixelate(int $pixelate)
    {
        $this->manipulations->pixelate($pixelate);
        return $this;
    }

    /**
     * Convert to greyscale
     *
     * @return $this
     */
    public function greyscale()
    {
        $this->manipulations->greyscale();
        return $this;
    }

    /**
     * Convert to sepia
     *
     * @return $this
     */
    public function sepia()
    {
        $this->manipulations->sepia();
        return $this;
    }

    /**
     * @param integer $sharpen 0 to 100
     * @return $this
     */
    public function sharpen(int $sharpen)
    {
        $this->manipulations->sharpen($sharpen);
        return $this;
    }
}
