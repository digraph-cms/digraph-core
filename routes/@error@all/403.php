<p>You have been denied access to this page.</p>
<?php
$package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Access denied';

if ($user = $this->helper('users')->user()) {
    echo "<p>You are currently signed in as <code>".$user->name()."</code>. If this isn't you, please sign out and try again</p>";
} else {
    echo "<p>You are not signed in. If you have an account, you should sign in and try again.</p>";
}
