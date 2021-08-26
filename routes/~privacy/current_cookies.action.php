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
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\Notifications;

if (!$_COOKIE) {
    Notifications::printConfirmation('No cookies set.');
    return;
}

if (($post = Context::request()->post()) && @$post['delete']) {
    foreach ($post['delete'] as $key) {
        Cookies::unsetRaw($key);
    }
    Context::response()->redirect(Context::url());
    return;
}

$table = new ArrayTable(
    $_COOKIE,
    function (string $key, string $item) {
        return [
            "<input type='checkbox' name='delete[]' value='$key' id='" . md5($key) . "'>" .
                "<label for='" . md5($key) . "'>$key</label>",
            Cookies::describe($key),
            Cookies::expiration($key) ? 'After ' . Cookies::expiration($key) : 'When browser is closed'
        ];
    },
    [
        new ColumnHeader('Name'),
        new ColumnHeader('Description'),
        new ColumnHeader('Automatic expiration'),
    ]
);

echo "<form method='post'>";
echo $table;
echo "<input type='submit' value='Delete selected cookies'>";
Notifications::printNotice("Please note: Some cookies may not be able to be deleted through this tool due to the way their path scopes are defined. You can always clear them using your browser's tools though.");
echo "</form>";
