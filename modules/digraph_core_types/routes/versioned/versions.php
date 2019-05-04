<?php
$versions = $package->noun()->availableVersions();

echo "<h2>Revision history</h2>";
echo "<form action='".$this->url($package['noun.dso.id'], 'version-diff', [])."' method='get'>";
echo "<table id='digraph-revision-history'>";
echo "<tr><th colspan=2>&nbsp;</th><th>Title</th><th>Date</th></tr>";
$i = 0;
foreach ($versions as $k => $v) {
    $i++;
    echo "<tr class='revision-row' data-rownum='$i'>";
    echo "<td><input type='radio' class='compare-radio compare-radio-b' value='".$v['dso.id']."' name='b' data-rownum='$i'></td>";
    echo "<td><input type='radio' class='compare-radio compare-radio-a' value='".$v['dso.id']."' name='a' data-rownum='$i'></td>";
    echo "<td>".$v->url()->html()."</td>";
    echo "<td>";
    echo $cms->helper('strings')->datetimeHTML($v->effectiveDate());
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<div class='fixed-controls'>";
echo "<input type='submit' class='cta-button green' value='Compare'></div>";
echo "</form>";

?>
<style>
tr.hide-a .compare-radio-a {
    display:none;
}
tr.hide-b .compare-radio-b {
    display:none;
}
</style>
<script>
$(()=>{
    var $table = $('#digraph-revision-history');
    var $rows = $table.find('tr.revision-row');
    var updateTable = function() {
        $a = $table.find('input.compare-radio-a:checked');
        $b = $table.find('input.compare-radio-b:checked');
        a = $a.attr('data-rownum');
        b = $b.attr('data-rownum');
        //hide bs after as position
        if (a) {
            $rows.each((i)=>{
                if ($rows.eq(i).attr('data-rownum') >= a) {
                    $rows.eq(i).addClass('hide-b');
                }else {
                    $rows.eq(i).removeClass('hide-b');
                }
            });
        }
        //hide as before bs position
        if (b) {
            $rows.each((i)=>{
                if ($rows.eq(i).attr('data-rownum') <= b) {
                    $rows.eq(i).addClass('hide-a');
                }else {
                    $rows.eq(i).removeClass('hide-a');
                }
            });
        }
        //highlight selection
        if (a && b) {
            $rows.each((i)=>{
                if ($rows.eq(i).attr('data-rownum') >= b && $rows.eq(i).attr('data-rownum') <= a) {
                    $rows.eq(i).addClass('highlighted');
                }else {
                    $rows.eq(i).removeClass('highlighted');
                }
            });
        }
    }
    updateTable();
    $table.find('input.compare-radio').change(updateTable);
});
</script>
