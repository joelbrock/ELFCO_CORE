<?php
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Special Order :: Mangement";
$header = "Manage Special Orders";
if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
	$header = "Special Orders for Member #".((int)$_REQUEST['card_no']);
}
include($FANNIE_ROOT.'src/header.html');

$status = array(
	0 => "New",
	1 => "Assigned",
	2 => "Pending",
	3 => "Paid",
	4 => "Ordered",
	5 => "Arrived"
);

$assignments = array();
$q = "SELECT superID,super_name FROM MasterSuperDepts
	GROUP BY superID,super_name ORDER BY superID";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r))
	$assignments[$w[0]] = $w[1];
$assignments[0] = "No One";
$assignments[-1] = "Multiple";

$f1 = (isset($_REQUEST['f1']) && $_REQUEST['f1'] !== '')?(int)$_REQUEST['f1']:'';
$f2 = (isset($_REQUEST['f2']) && $_REQUEST['f2'] !== '')?(int)$_REQUEST['f2']:'';

$filterstring = "";
if ($f1 !== '' && $f2 !== ''){
	$filterstring = sprintf("WHERE status_flag=%d AND sub_status=%d",
		$f1,$f2);
}
else if ($f1 !== ''){
	$filterstring = sprintf("WHERE status_flag=%d",$f1);
}
else if ($f2 !== ''){
	$filterstring = sprintf("WHERE sub_status=%d",$f2);
}

echo "<b>Filters</b>: ";
echo '<select id="f_1" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($status as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f1?'selected':''),$k,$v);
}
echo '</select>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;';
echo '<select id="f_2" onchange="refilter();">';
echo '<option value="">All</option>';
foreach($assignments as $k=>$v){
	printf("<option %s value=\"%d\">%s</option>",
		($k===$f2?'selected':''),$k,$v);
}
echo '</select>';
echo '<hr />';

if (isset($_REQUEST['card_no']) && is_numeric($_REQUEST['card_no'])){
	if (empty($filterstring))
		$filterstring .= sprintf("WHERE p.card_no=%d",$_REQUEST['card_no']);
	else
		$filterstring .= sprintf(" AND p.card_no=%d",$_REQUEST['card_no']);
	printf('<input type="hidden" id="cardno" value="%d" />',$_REQUEST['card_no']);
}

$q = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
	count(*)-1 as items,status_flag,sub_status,
	CASE WHEN MAX(p.card_no)=0 THEN MAX(t.last_name) ELSE MAX(c.LastName) END as name	
	FROM PendingSpecialOrder as p
	LEFT JOIN SpecialOrderStatus as s ON p.order_id=s.order_id
	LEFT JOIN SpecialOrderNotes as n ON n.order_id=p.order_id
	LEFT JOIN custdata AS c ON c.CardNo=p.card_no
	LEFT JOIN SpecialOrderContact as t on t.card_no=p.order_id
	$filterstring
	GROUP BY p.order_id,status_flag,sub_status
	HAVING count(*) > 1 OR
	SUM(CASE WHEN notes LIKE '' THEN 0 ELSE 1 END) > 0
	ORDER BY min(datetime)";
$r = $dbc->query($q);
$ret = '<table cellspacing="0" cellpadding="4" border="1">
	<tr><th>Order Date</th><th>Order ID</th><th>Name</th><th>Value</th>
	<th>Items</th><th>Status</th><th>Assigned To</th></tr>';
while($w = $dbc->fetch_row($r)){
	$ret .= sprintf('<tr><td><a href="view.php?orderID=%d">%s</a></td>
		<td>%d</td><td>%s</td><td>%.2f</td>
		<td>%d</td>',$w['order_id'],
		$w['orderDate'],$w['order_id'],
		$w['name'],
		$w['value'],$w['items']);
	$ret .= '<td><select id="s_status" onchange="updateStatus('.$w['order_id'].');">';
	foreach($status as $k=>$v){
		$ret .= sprintf('<option %s value="%d">%s</option>',
			($w['status_flag']==$k?'selected':''),
			$k,$v);
	}
	$ret .= "</select></td>";
	$ret .= '<td><select id="s_sub" onchange="updateSub('.$w['order_id'].');">';
	foreach($assignments as $k=>$v){
		$ret .= sprintf('<option %s value="%d">%s</option>',
			($w['sub_status']==$k?'selected':''),
			$k,$v);
	}
	$ret .= "</select></td></tr>";
}
$ret .= "</table>";

echo $ret;
?>
<script type="text/javascript">
function refilter(){
	var f1 = $('#f_1').val();
	var f2 = $('#f_2').val();

	var loc = 'clearinghouse.php?f1='+f1+'&f2='+f2;
	if ($('#cardno').length!=0)
		loc += '&card_no='+$('#cardno').val();
	
	location = loc;
}
function updateStatus(oid){
	var val = $('#s_status').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateStatus&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){}
	});
}
function updateSub(oid){
	var val = $('#s_sub').val();
	$.ajax({
	url: 'ajax-calls.php',
	dataType: 'post',
	data: 'action=UpdateSub&orderID='+oid+'&val='+val,
	cache: false,
	success: function(resp){}
	});
}
</script>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>