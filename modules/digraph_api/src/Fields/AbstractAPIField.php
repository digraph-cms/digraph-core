<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_api\Fields;

use Formward\AjaxFields\AjaxAutocomplete;
use Digraph\CMS;
use Formward\FieldInterface;

abstract class AbstractAPIField extends AjaxAutocomplete
{
    protected $cms;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        $this->cms($cms);
        parent::__construct($label, $name, $parent);
        $this['query']->attr('placeholder', 'Type to search...');
    }

    public function &cms(CMS &$cms = null)
    {
        if ($cms) {
            $this->cms = $cms;
            $this->ajaxSource(
                $this->cms->helper('urls')->url(
                    '_api',
                    'json',
                    [
                        'cmd' => static::API_CMD,
                        'q' => '$q'
                    ]
                )->string()
            );
        }
        return $this->cms;
    }

    public function ajaxGetResults($query)
    {
        if (!$this->cms) {
            return [];
        }
        return $this->cms->helper('api')->call(static::API_CMD, $query);
    }
}
