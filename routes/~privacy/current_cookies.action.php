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
use DigraphCMS\UI\Notifications;

if (!$_COOKIE) {
    Notifications::printConfirmation('No cookies set.');
    return;
}

$table = new ArrayTable(
    $_COOKIE,
    function (string $key, string $item) {
        if ($value = json_decode($_COOKIE[$key], true)) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
        } else {
            $value = $_COOKIE[$key];
        }
        $value = htmlspecialchars($value);
        $value = preg_replace("/&quot;(secret)&quot;: &quot;(.+)&quot;/", '"$1": "<a class="spoiler">$2</a>"', $value);
        $button = new SingleButton(
            'Delete',
            function () use ($key) {
                Cookies::unsetRaw($key);
                Context::response()->redirect(Context::url());
            },
            ['warning']
        );
        return [
            htmlspecialchars($key),
            Cookies::describe($key),
            "<pre><code class='hljs language-json'>$value</code></pre>",
            Cookies::expiration($key) ? 'After ' . Cookies::expiration($key) : 'When browser is closed',
            $button
        ];
    },
    [
        new ColumnHeader('Name'),
        new ColumnHeader('Description'),
        new ColumnHeader('Value'),
        new ColumnHeader('Automatic expiration'),
        new ColumnHeader('')
    ]
);

echo $table;
Notifications::printNotice(
    "Please note: Some cookies may not be able to be viewed or deleted through this tool due to the way their scopes are defined. For example if they are set to only be sent to specific pages (such as CSRF tokens that are only available on the page where a form is used). You can always clear them using your browser's tools though."
);
echo "</form>";
