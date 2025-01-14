<ul class='signin-options'>
    <?php

    use DigraphCMS\Context;
    use DigraphCMS\UI\Templates;
    use DigraphCMS\Users\Users;

    echo "<p>Please select a method to use for logging in.</p>";

    echo '<menu class="signin-sources">';
    foreach (Users::allSigninURLs(Context::fields()['bounce']) as $k => $url) {
        echo "<li class='signin-source signin-source--" . preg_replace('/_.+$/', '', $k) . " signin-source--$k'>";
        if (Templates::exists('signin/option_' . $k . '.php')) {
            echo Templates::render('signin/option_' . $k . '.php', ['url' => $url]);
        } elseif (Templates::exists('signin/option_' . preg_replace('/_.+$/', '', $k) . '.php')) {
            echo Templates::render('signin/option_' . preg_replace('/_.+$/', '', $k) . '.php', ['url' => $url]);
        } else {
            echo $url->html();
        }
        echo "</li>";
    }
    echo '</menu>';

    ?>
</ul>