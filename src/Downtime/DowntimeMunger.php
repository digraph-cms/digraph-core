<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Downtime;

use Digraph\Mungers\AbstractMunger;

class DowntimeMunger extends AbstractMunger
{
    const ALLOWED_URLS = [
        '_user',
        '_user/',
        '_user/signin',
        'digraph.css',
        'favicon.ico'
    ];

    public function doConstruct($name)
    {}

    public function doMunge($package)
    {
        foreach ($package->cms()->helper('downtime')->prenotifications() as $downtime) {
            $message = '<strong>Upcoming scheduled downtime:</strong> This site is scheduled to be offline for maintenance';
            $message .= '<br>Begins at: ' . $package->cms()->helper('strings')->datetimeHTML($downtime['downtime.start']);
            if ($downtime['downtime.end']) {
                $message .= '<br>Scheduled end: ' . $package->cms()->helper('strings')->datetimeHTML($downtime['downtime.end']);
            }
            $package->cms()->helper('notifications')->notice($message);
        }
        if ($downtime = $package->cms()->helper('downtime')->current()) {
            if ($package->cms()->helper('permissions')->check('_downtime/display')) {
                $package->cms()->helper('notifications')->notice('The site is currently down for maintenance for other users.');
                return;
            }
            $package->template('standalone.twig');
            if (!in_array($package['request.url'], static::ALLOWED_URLS)) {
                $package->error(503);
                $package['downtime.title'] = $downtime->title();
                $package['downtime.message'] = $downtime->body();
                $package['downtime.end'] = $downtime['downtime.end'];
            } else {
                $message = 'The site is currently down for maintenance. Unless you are the site administrator, you will likely be unable to access any other content.';
                if ($downtime['downtime.end']) {
                    $message .= '<br>Scheduled end: ' . $package->cms()->helper('strings')->datetimeHTML($downtime['downtime.end']);
                }
                $package->cms()->helper('notifications')->notice($message);
            }
        }
    }
}
