<?php
$package['response.cacheable'] = false;
$package->cms()->helper('users')->signout();

$cms->helper('notifications')->flashConfirmation('You are now signed out');
$package->redirect($this->helper('urls')->parse('user/signin')->string());
