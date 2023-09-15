<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Slugs;
use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\WaybackMachine;

class CoreCronSubscriber
{

    public static function cronJob_maintenance()
    {
        // expire deferred execution jobs
        new DeferredJob(
            function () {
                /** @var int */
                $count = DB::query()->delete('defex')
                    ->where('run is not null')
                    ->where('run < ?', [strtotime(Config::get('maintenance.expire_defex_records'))])
                    ->execute();
                return "Expired $count deferred execution jobs";
            },
            'core_maintenance'
        );
        // expire cron errors
        new DeferredJob(
            function () {
                /** @var int */
                $count = DB::query()
                    ->update('cron', [
                        'error_time' => null,
                        'error_message' => null,
                    ])
                    ->where('error_time is not null')
                    ->where('error_time < ?', [strtotime(Config::get('maintenance.expire_cron_errors'))])
                    ->execute();
                return "Expired $count cron error messages";
            },
            'core_maintenance'
        );
        // expire search index records
        new DeferredJob(
            function () {
                /** @var int */
                $count = DB::query()->delete('search_index')
                    ->where('updated < ?', [strtotime(Config::get('maintenance.expire_search_index'))])
                    ->execute();
                return "Expired $count search index records";
            },
            'core_maintenance'
        );
    }

    public static function cronJob_wayback()
    {
        // check status of wayback machine URLs
        new DeferredJob(
            function (DeferredJob $job) {
                $records = (new DatastoreGroup('wayback', 'status'))->select()
                    ->where(
                        '(`value` = ? OR updated < ? OR (`value` = ? AND updated < ?))',
                        [
                            'pending',
                            time() - Config::get('wayback.check_ttl'),
                            'down', time() - Config::get('wayback.check_notfound_ttl')
                        ]
                    )
                    ->order('updated ASC');
                while ($statusData = $records->fetch()) {
                    $job->spawn(function () use ($statusData) {
                        $status = WaybackMachine::actualUrlStatus('http://' . $statusData->data()['url'])
                            || WaybackMachine::actualUrlStatus('https://' . $statusData->data()['url']);
                        if ($status) {
                            $statusData->setValue('ok');
                            $statusData->update();
                            return $statusData->data()['url'] . ' is up';
                        } else {
                            $statusData->setValue('down');
                            $statusData->update();
                            return $statusData->data()['url'] . ' is down';
                        }
                    });
                }
                return 'Prepared jobs to check ' . $records->count() . ' URLs';
            },
            'wayback_status'
        );
        // refresh wayback machine API call data
        new DeferredJob(
            function (DeferredJob $job) {
                $records = (new DatastoreGroup('wayback', 'api'))->select()
                    ->where(
                        '(`value` = ? OR (`value` = ? AND updated < ?) OR (`value` = ? AND updated < ?) OR (`value` = ? AND updated < ?))',
                        [
                            'pending',
                            'found', time() - Config::get('wayback.api_ttl'),
                            'error', time() - Config::get('wayback.api_error_ttl'),
                            'notfound', time() - Config::get('wayback.api_notfound_ttl')
                        ]
                    )
                    ->order('updated ASC');
                while ($apiData = $records->fetch()) {
                    $job->spawn(function () use ($apiData) {
                        $url = $apiData->data()['url'];
                        $result = WaybackMachine::actualApiCall($url);
                        // there was some sort of error
                        if ($result === false) {
                            $apiData->setValue('error');
                            $apiData->update();
                            return 'API call failed for ' . $url;
                        }
                        // we got an empty result
                        elseif ($result === null) {
                            $apiData->setValue('notfound');
                            $apiData->setData($result);
                            $apiData->data()['url'] = $url;
                            $apiData->update();
                            return 'No API results for ' . $url;
                        }
                        // we got a result
                        else {
                            $apiData->setValue('found');
                            $apiData->setData($result);
                            $apiData->update();
                            return 'API call saved for ' . $url;
                        }
                    });
                }
                return 'Prepared jobs to update ' . $records->count() . ' API calls';
            },
            'wayback_api'
        );
    }

    public static function cronJob_maintenance_heavy()
    {
        // expire old exception logs
        new DeferredJob(
            function () {
                $path = Config::get('paths.storage') . '/exception_log';
                $dayDirs = array_reverse(glob("$path/" . str_repeat('[0123456789]', 8), GLOB_ONLYDIR | GLOB_NOSORT));
                $expires = intval(date('Ymd', time() - (86400 * 30)));
                $count = 0;
                foreach ($dayDirs as $dayDir) {
                    if (intval(basename($dayDir)) < $expires) {
                        foreach (glob("$dayDir/*") as $file) {
                            unlink($file);
                            $count++;
                        }
                        rmdir($dayDir);
                    }
                }
                return "Expired $count old exception logs";
            },
            'core_maintenance_heavy'
        );
        // expire old wayback data
        new DeferredJob(
            function () {
                WaybackMachine::cleanup();
            },
            'core_maintenance_heavy'
        );
        // do periodic maintenance on all pages
        new DeferredJob(
            function (DeferredJob $job) {
                $pages = DB::query()
                    ->from('page')
                    ->leftJoin('page_link on end_page = page.uuid')
                    ->where('page_link.id is null');
                while ($page = $pages->fetch()) {
                    $uuid = $page['uuid'];
                    // recursive job to prepare cron jobs
                    new RecursivePageJob(
                        $uuid,
                        function (DeferredJob $job, AbstractPage $page) {
                            $count = $page->prepareCronJobs();
                            return sprintf("Prepared %s cron jobs for %s (%s)", $count, $page->name(), $page->uuid());
                        },
                        false,
                        $job->group()
                    );
                    // recursive job to refresh all slugs
                    new RecursivePageJob(
                        $uuid,
                        function (DeferredJob $job, AbstractPage $page) {
                            if (!$page->slugPattern()) return $page->uuid() . ": No slug pattern";
                            Slugs::setFromPattern($page, $page->slugPattern(), $page::DEFAULT_UNIQUE_SLUG);
                            return $page->uuid() . " slug set to " . $page->slug();
                        },
                        false,
                        $job->group()
                    );
                }
                return "Spawned page heavy maintenance jobs";
            },
            'core_maintenance_heavy'
        );
    }
}
