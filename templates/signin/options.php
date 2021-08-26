<ul class='signin-options'>
    <?php

    use DigraphCMS\Context;
    use DigraphCMS\Users\Users;

    foreach (Users::allSigninURLs(Context::fields()['bounce']) as $k => $url) {
        echo "<li class='signin-source type-" . preg_replace('/_.+$/', '', $k) . " $k'>";
        echo $url->html();
        echo "</li>";
    }

    ?>
</ul>