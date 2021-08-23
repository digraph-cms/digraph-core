<?php

namespace DigraphCMS\UI;

class UserMenu extends ActionMenu
{
    public function __construct()
    {
        // does nothing
        $this->user = true;
    }

    public function __toString()
    {
        ob_start();
        echo "<nav class='action-menu user-menu'><h1>User menu</h1>";
        // output buffer contents separately
        ob_start();
        $this->printUserActions();
        // return empty if no contents
        if (!ob_get_length()) {
            ob_end_clean();
            ob_end_clean();
            return '';
        } else {
            ob_end_flush();
        }
        // close up and return
        echo "</nav>";
        return ob_get_clean();
    }
}
