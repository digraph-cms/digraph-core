<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

Context::response()->private(true);

?>
<h1>Current cookies</h1>
<p>The following cookies are currently associated with this site in your web browser, and can be read by the site.</p>
<p>Visit the <a href="<?php echo new URL('/~privacy/cookie_authorizations.html'); ?>">cookie authorizations page</a> to learn more or delete cookies.</p>

<?php

use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\ColumnHeader;

$table = new ArrayTable(
    $_COOKIE,
    function (string $key, string $item) {
        return [
            $key,
            Cookies::describe($key),
            Cookies::expiration($key) ? 'After ' . Cookies::expiration($key) : 'When browser is closed'
        ];
    },
    [
        new ColumnHeader('Name'),
        new ColumnHeader('Description'),
        new ColumnHeader('Automatic expiration')
    ]
);

echo $table;
