<?php
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

$package['fields.page_name'] = 'Log: '.$log->name();
$s = $cms->helper('strings');
?>

<ul>
    <li>Recorded at: <a href="<?php echo $log['url']; ?>"><?php echo $log['url']; ?></a></li>
    <li>Times recorded: <?php echo $log['count']; ?></li>
    <li>First recorded: <?php echo $s->dateTimeHTML($log['dso.created.date']); ?></li>
    <li>Last recorded: <?php echo $s->dateTimeHTML($log['dso.modified.date']); ?></li>
</ul>

<h2>Users impacted</h2>
<ul>
<?php
foreach ($log['users'] as $a) {
    foreach ($a as $b) {
        echo @"<li>{$b['id']} at {$b['ip']} {$b['fw']}</li>";
    }
}
 ?>
</ul>

<?php if ($log['package.error']) {
     ?>
<h2>Error trace</h2>
<pre style="white-space:pre-wrap;">
<?php echo yaml($log['package.error']); ?>
</pre>
<?php
 } ?>

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
<?php echo yaml($log['package']); ?>
</pre>
