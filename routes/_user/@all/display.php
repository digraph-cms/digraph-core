<?php
$package->cache_noStore();
$u = $package->cms()->helper('users');
$s = $package->cms()->helper('strings');
$urls = $package->cms()->helper('urls');

$user = $u->user();

if (!$user) {
    echo $s->string('user.notsignedin', [
        'signin' => $urls->parse('_user/signin')->html()
    ]);
    return;
}

echo '<p>'.$s->string('user.signedin', [
    'name' => $user->name(),
    'signout' => $urls->parse('_user/signout')->html()
]).'</p>';

echo '<p>'.$s->string('user.groups', [
    'name' => $user->name(),
    'groups' => implode(', ', $u->groups())
]).'</p>';
