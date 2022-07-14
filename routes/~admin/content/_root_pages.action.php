<h1>Root pages</h1>
<p>
    The following pages have no parent page, and (aside from the home page) can probably not be found in the table of contents of any other pages.
</p>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\Pagination\PageTable;

echo new PageTable(
    Pages::select()
        ->leftJoin('page_link on end_page = page.uuid')
        ->where('page_link.id is null')
);
