<?php

use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

?>

<h1>Privacy policy</h1>

<h2>Content management system</h2>
<p>
    This site's content management system may store and collect data about you via the following cookies.
    All cookies are strictly opt-in, and you may view and delete the cookies currently set for your browser session at any time by visiting the <a href="<?php echo new URL('current_cookies.html'); ?>">current cookies page</a>.
</p>

<table>
    <tr>
        <th>Type</th>
        <th>Description</th>
        <th>Automatic expiration</th>
    </tr>
    <?php
    foreach (Cookies::listTypes() as $type) {
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