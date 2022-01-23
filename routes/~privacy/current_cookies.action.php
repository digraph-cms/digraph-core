<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

Context::response()->private(true);

?>
<h1>Current cookies</h1>
<p>The following cookies are currently associated with this site in your web browser, and can be read by the site.</p>
<p>Visit the <a href="<?php echo new URL('/~privacy/cookie_authorizations.html'); ?>">cookie authorizations page</a> and <a href="<?php echo new URL('/~privacy/'); ?>">privacy policy</a> to learn more about the cookies this site uses.</p>

<?php

use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\ColumnHeader;

$table = new ArrayTable(
    $_COOKIE,
    function (string $key, string $item) {
        if ($value = json_decode($_COOKIE[$key], true)) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
        } else {
            $value = $_COOKIE[$key];
        }
        $value = htmlspecialchars($value);
        $button = new SingleButton(
            'Delete',
            function () use ($key) {
                Cookies::unsetRaw($key);
                Context::response()->redirect(Context::url());
            },
            ['button--warning']
        );
        return [
            htmlspecialchars($key),
            Cookies::describe($key),
            // "<pre><code class='hljs language-json'>$value</code></pre>",
            Cookies::expiration($key) ? 'After ' . Cookies::expiration($key) : 'When browser is closed',
            $button
        ];
    },
    [
        new ColumnHeader('Name'),
        new ColumnHeader('Description'),
        // new ColumnHeader('Value'),
        new ColumnHeader('Automatic expiration'),
        new ColumnHeader('')
    ]
);

echo $table;
echo "</form>";
