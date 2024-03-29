<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

?>

<h1>Your privacy on this site</h1>

<h2>Data collected by the content management system</h2>

<p>
    All potentially personally-identifying cookies are opt-in, and you may view and delete your cookies on the <a href="<?php echo new URL('current_cookies.html'); ?>">current cookies page</a>.
    You can also adjust your cookie settings on the <a href="<?php echo new URL('cookie_authorizations.html'); ?>">cookie authorizations page</a>.
</p>

<table>
    <tr>
        <th>Type</th>
        <th>Description</th>
        <th>Automatic expiration</th>
    </tr>
    <?php
    foreach (Cookies::allTypes() as $type) {
        echo "<tr>";
        echo "<td>" . Cookies::name($type) . "</td>";
        echo "<td>" . Cookies::describe($type) . "</td>";
        if ($expiration = Cookies::expiration($type)) {
            echo '<td>after ' . $expiration . '</td>';
        } else {
            echo '<td>when you close your browser</td>';
        }
        echo "</tr>";
    }
    ?>
</table>

<h2>Third-party analytics</h2>

<p>
    Site traffic data and anonymous tracking information may be shared with third-party analytics software.
</p>

<?php
Router::include('info_cms/*.php');
Router::include('info_general/*.php');
