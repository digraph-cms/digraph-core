<p>You have been denied access to this page.</p>
<?php
if ($user = $this->helper('users')->user()) {
    $package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Access denied';
    $signout = $cms->helper('users')->signoutUrl($package);
    echo "<p>You are currently signed in as <code>".$user->name()."</code>. If this isn't you, please <a href=\"$signout\">sign out</a> to try again.</p>";
} else {
    $package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Sign-in required';
    $signin = $cms->helper('users')->signinUrl($package);
    echo "<p>Access to this page requires <a href=\"$signin\">signing in</a>.</p>";
}
