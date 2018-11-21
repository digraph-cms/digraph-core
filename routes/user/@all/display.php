<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;
$u = $package->cms()->helper('users');
$s = $package->cms()->helper('strings');
$urls = $package->cms()->helper('urls');

$user = $u->user();

if (!$user) {
    echo $s->string('user.notsignedin', [
        'signin' => $urls->parse('user/signin')->html()
    ]);
    return;
}

echo '<p>'.$s->string('user.signedin', [
    'name' => $user->name(),
    'signout' => $urls->parse('user/signout')->html()
]).'</p>';

echo '<p>'.$s->string('user.groups', [
    'name' => $user->name(),
    'groups' => implode(', ', $u->groups())
]).'</p>';
