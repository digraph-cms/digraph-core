<?php
use Formward\Form;
use Formward\Fields\Container;
use Formward\Fields\Input;
use Formward\Fields\Number;
use Formward\Fields\Select;

$package->noCache();

$form = new Form('');

# enabled configuration
$form['enabled'] = new Select('enabled');
$form['enabled']->options([
    'TRUE' => 'true',
    'FALSE' => 'false'
]);
$form['enabled']->nullText = 'default: '.($cms->config['pdf.enabled']?'true':'false');

# include_in_books
$form['include_in_books'] = new Select('include_in_books');
$form['include_in_books']->options([
    'TRUE' => 'true',
    'FALSE' => 'false'
]);
$form['include_in_books']->nullText = 'default: '.($cms->config['pdf.include_in_books']?'true':'false');

# cache ttl configuration
$form['ttl'] = new Number('cache ttl (in seconds)');
$form['ttl']->attr('placeholder', 'default: '.$cms->config['pdf.ttl']);

# css path
$form['css'] = new Input('css');
$form['css']->attr('placeholder', 'default: '.$cms->config['pdf.css']);

# article break type configuration
$form['article_break_type'] = new Select('article_break_type');
$form['article_break_type']->options([
    'next-odd' => 'next-odd',
    'next-even' => 'next-even',
    'next' => 'next'
]);
$form['article_break_type']->nullText = 'default: '.$cms->config['pdf.article_break_type'];

# column count/gap configuration
$form['columns'] = new Container('columns');
$form['columns']['count'] = new Number('count');
$form['columns']['count']->attr('placeholder', 'default: '.$cms->config['pdf.columns.count']);

$form['columns']['gap'] = new Number('gap (in millimeters)');
$form['columns']['gap']->attr('placeholder', 'default: '.$cms->config['pdf.columns.gap']);

# mpdf options
$form['mpdf'] = new Container('mpdf');
$form['mpdf']['mirrorMargins'] = new Select('mirrorMargins');
$form['mpdf']['mirrorMargins']->options([
    'TRUE' => 'true',
    'FALSE' => 'false',
]);
$form['mpdf']['mirrorMargins']->nullText = 'default: '.($cms->config['pdf.mpdf.mirrorMargins']?'true':'false');


//set form defaults from noun
$form->default($package->noun()['pdf']);

//output form
echo $form;

//handle form
if ($form->handle()) {
    $noun = $package->noun();
    $value = array_map_recursive(
        function ($e) {
            if ($e === '') {
                return null;
            }
            if ($e === 'TRUE') {
                return true;
            }
            if ($e === 'FALSE') {
                return false;
            }
            return $e;
        },
        $form->value()
    );
    $noun->merge($value, 'pdf', true);
    if ($noun->update()) {
        $cms->helper('strings')->string(
            'notifications.edit.confirmation',
            ['name'=>$noun->link()]
        );
    }
}

function array_map_recursive($callback, $array)
{
    $func = function ($item) use (&$func, &$callback) {
        return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
    };
    return array_map($func, $array);
}
