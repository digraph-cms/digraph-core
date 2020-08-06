<?php
$package->cache_noStore();
$log = $cms->helper('logging')->factory()->read($package['url.args.id']);

if (!$log) {
    $package->error(404);
    return;
}

function yaml($array)
{
    if ($array instanceof Flatrr\FlatArray) {
        $array = $array->get();
    }
    $c = new \Flatrr\Config\Config($array);
    return $c->yaml();
}

$package['fields.page_name'] = 'Log: ' . $log->name();
$s = $cms->helper('strings');
?>

<ul>
    <li>Recorded at: <a href="<?php echo $log['url']; ?>"><?php echo $log['url']; ?></a></li>
    <li>PHP URL: <a href="<?php echo $log['phpurl']; ?>"><?php echo $log['phpurl']; ?></a></li>
    <li>Times recorded: <?php echo $log['count']; ?></li>
    <li>First recorded: <?php echo $s->dateTimeHTML($log['dso.created.date']); ?></li>
    <li>Last recorded: <?php echo $s->dateTimeHTML($log['dso.modified.date']); ?></li>
</ul>

<?php if ($log['package.error']) {
    ?>
<h2>Error info</h2>
<pre style="white-space:pre-wrap;">
<?php echo yaml($log['package.error']); ?>
</pre>
<?php
}?>

<h2>Users</h2>
<?php
foreach ($log['users'] as $a) {
    foreach ($a as $b) {
        echo @"<pre>{$b['ip']} {$b['fw']} {$b['id']}\r\n{$b['ua']}\r\n{$b['url']}</pre>";
    }
}
?>

<h2>Referers</h2>
<pre>
<?php
echo implode("\r\n", array_map(
    function ($r) {
        return "{$r['url']} :: {$r['count']}";
    },
    $log['referers']
));
?>
</pre>

<h2>Package log</h2>
<pre style="white-space:pre-wrap;">
<?php echo implode(PHP_EOL, $log['log.package']); ?>
</pre>

<h2>CMS log</h2>
<pre style="white-space:pre-wrap;">
<?php echo implode(PHP_EOL, $log['log.cms']); ?>
</pre>

<h2>Package dump</h2>
<pre style="white-space:pre-wrap;">
<?php unset($log['package.response.content']);?>
<?php echo yaml($log['package']); ?>
</pre>
