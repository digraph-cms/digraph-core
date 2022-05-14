<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DigraphCMS\UI\Format;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class CellHelper
{
    public static function date($date)
    {
        $date = Format::parseDate($date);
        $cell = WriterEntityFactory::createCell($date->format('j/n/Y'));
        return $cell;
    }

    public static function user($user)
    {
        if (!($user instanceof User)) $user = Users::user($user);
        $cell = WriterEntityFactory::createCell($user->name());
        return $cell;
    }
}
