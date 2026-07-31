<?php

use DigraphCMS\Context;
use DigraphCMS\Media\Media;
use DigraphCMS\Users\Users;

echo "<p>Please select a method to use for logging in.</p>";

echo '<menu class="signin-sources">';
foreach (Users::allSigninURLs(Context::fields()['bounce']) as $k => $url) {
    echo "<li class='signin-source signin-source--" . preg_replace('/_.+$/', '', $k) . " signin-source--$k'>";
    $logo_path = sprintf('/signin_logos/%s.png', $k);
    $image = Media::get($logo_path);
    if ($image) {
        printf(
            '<a href="%s"><img src="%s" alt="%s"></a>',
            $url,
            $image->url(),
            $url->name(),
        );
    } else {
        echo $url->html();
    }
    echo "</li>";
}
echo '</menu>';

?>