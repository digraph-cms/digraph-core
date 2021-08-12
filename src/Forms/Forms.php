<?php

namespace DigraphCMS\Forms;

use DigraphCMS\Content\Page;

class Forms
{
    /**
     * Given a page and action, create a form related to the specified page.
     *
     * @param Page $page
     * @param string $action
     * @return Form
     */
    public static function pageForm(Page $page, string $action = null): Form
    {
        // prepare, sort, and filter fields
        $fields = $page->fields($action);
        $fields = array_filter(
            $fields,
            function ($e) use ($action) {
                if (!$e) {
                    return false;
                } elseif (@$e['only_actions']) {
                    return in_array($action, $e['only_actions']);
                } elseif (@$e['excluded_actions']) {
                    return !in_array($action, $e['excluded_actions']);
                } else {
                    return true;
                }
            }
        );
        uasort(
            $fields,
            function ($a, $b) {
                return @$a['weight'] - @$b['weight'];
            }
        );
        // create form and insert all fields
        $form = new Form('', $action . $page->uuid());
        foreach ($fields as $name => $f) {
            $form[$name] = $f['field'];
        }
        // set up event listeners
        $form->addCallback(function () use ($form, $page) {
            static::handlePageForm($form, $page, $action);
        });
        return $form;
    }

    protected static function handlePageForm(Form $form, Page $page, string $action)
    {
        throw new \Exception("Form error");
    }
}
