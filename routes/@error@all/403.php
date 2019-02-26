<p>You have been denied access to this page.</p>
<?php
$package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Access denied';

if ($user = $this->helper('users')->user()) {
    $signout = $cms->helper('users')->signoutUrl($package);
    echo "<p>You are currently signed in as <code>".$user->name()."</code>. If this isn't you, please <a href=\"$signout\">sign out</a> to try again.</p>";
} else {
    $signin = $cms->helper('users')->signinUrl($package);
    echo "<p>You are not signed in. If you have an account, you can try <a href=\"$signin\">signing in</a>.</p>";
}
