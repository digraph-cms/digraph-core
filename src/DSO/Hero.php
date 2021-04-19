<?php
/* Digraph Core | https://github.com/jobyone/digraph-core | MIT License */

namespace Digraph\DSO;

use Digraph\FileStore\FileStoreFile;
use Digraph\Forms\Fields\FileStoreFieldMulti;
use Digraph\Forms\Fields\ImageFieldSingle;
use Formward\Fields\Url;

class Hero extends Noun
{
    public function body(): string
    {
        $body = '<div class="' . $this->classes() . '" style="' . $this->contentCSS() . '">';
        $body .= '<div class="digraph-actionbar digraph-actionbar-noun" data-actionbar-noun="' . $this['dso.id'] . '" data-actionbar-verb="display"></div>';
        $body .= '<div class="digraph-hero-content-wrapper">';
        if ($mainImage = $this->mainImage()) {
            $body .= '<div class="digraph-hero-main-image" style="background-image:url('.$mainImage.')">';
        }
        $body .= '<div class="digraph-hero-content">';
        $body .= $this->contentHTML();
        $body .= '</div>';
        if ($mainImage) {
            $body .= '</div>';
        }
        $body .= '</div>';
        $body .= '</div>';
        return $body;
    }

    public function mainImage(): ?string
    {
        $files = $this->cms()->helper('filestore')->list($this,'main');
        return $files ? reset($files)->imageUrl('hero-main') : null;
    }

    public function backgroundImage(): ?string
    {
        $files = $this->cms()->helper('filestore')->list($this,'background');
        return $files ? reset($files)->imageUrl('hero-background') : null;
    }

    public function tag_embed()
    {
        return $this->body();
    }

    protected function classes(): string
    {
        $classes = ['digraph-hero'];
        $classes[] = 'bg-position-' . $this['bg.position'];
        return implode(' ', $classes);
    }

    protected function contentCSS(): string
    {
        $style = [
            'background-color' => $this['bg.color'] ?? '#ccc'
        ];
        if ($url = $this->backgroundImage()) {
            $style['background-image'] = "url($url)";
        }
       array_walk(
            $style,
            function(&$v,$k) {
                $v = "$k:$v";
            }
        );
        return implode(';',$style);
    }

    protected function contentHTML(): string
    {
        return parent::body();
    }

    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['background_color'] = [
            'label' => 'Background color',
            'class' => 'text',
            'required' => false,
            'weight' => 300,
            'field' => 'bg.color'
        ];
        $map['background_image'] = [
            'label' => 'Background image',
            'class' => ImageFieldSingle::class,
            'extraConstructArgs' => ['background'],
            'weight' => 300
        ];
        $map['background_position'] = [
            'label' => 'Background image size/position',
            'class' => 'select',
            'field' => 'bg.position',
            'weight' => 300,
            'default' => 'cover',
            'required' => true,
            'options' => [
                'cover' => 'Cover (cropping if necessary)',
                'tile' => 'Tile (centered)'
            ]
        ];
        $map['main_image'] = [
            'label' => 'Main image',
            'class' => ImageFieldSingle::class,
            'extraConstructArgs' => ['main'],
            'weight' => 350
        ];
        $map['main_link'] = [
            'label' => 'Main link',
            'class' => Url::class,
            'field' => 'link',
            'weight' => 350
        ];
        $map['files'] = [
            'label' => 'Files',
            'class' => FileStoreFieldMulti::class,
            'extraConstructArgs' => ['files'],
            'weight' => 600
        ];
        return $map;
    }

    function parentEdgeType($parent)
    {
        return 'hero';
    }
}
