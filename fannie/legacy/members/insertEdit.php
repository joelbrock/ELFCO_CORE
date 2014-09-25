<?php
include('../../config.php');
<<<<<<< HEAD
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
=======
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
$sql = $dbc;

include_once($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editmembers') && !validateUserQuiet('editmembers_csc') && !validateUserQuiet('viewmembers')){
	$url = $FANNIE_URL.'auth/ui/loginform.php?redirect='.$_SERVER['PHP_SELF'];
	header('Location: '.$url);
	exit;
}
//include('../db.php');
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d

include('memAddress.php');

$username = validateUserQuiet('editmembers');

if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}

//$lName = $_POST['lastName'];

/* audit logging */
$uid = getUID($username);
$auditQ = "insert custUpdate select ".$sql->now().",$uid,1,
	CardNo,personNum,LastName,FirstName,
	CashBack,Balance,Discount,ChargeLimit,ChargeOK,
	WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
	NumberOfChecks,memCoupons,blueLine,Shown,id from custdata where cardno=$memID";
//$auditR = $sql->query($auditQ);

<<<<<<< HEAD
?>
<html>
<head>
</head>
<body 
	bgcolor="#66CC99" 
	leftmargin="0" topmargin="0" 
	marginwidth="0" marginheight="0" 
	onload="MM_preloadImages(
		'../images/memOver.gif',
		'../images/memUp.gif',
		'../images/repUp.gif',
		'../images/itemsDown.gif',
		'../images/itemsOver.gif',
		'../images/itemsUp.gif',
		'../images/refUp.gif',
		'../images/refDown.gif',
		'../images/refOver.gif',
		'../images/repDown.gif',
		'../images/repOver.gif'
	)"
>

<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="../images/newLogo_small1.gif" /></h1></td>
    <!-- <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  --> </tr>
  <tr>
    <td colspan="11" bgcolor="#006633"><!--<a href="memGen.php">-->
	<img src="../images/general.gif" width="72" height="16" border="0" />
	<a href="testDetails.php?memID=<?php echo $memID; ?>">
		<img src="../images/equity.gif" width="72" height="16" border="0" />
	</a>
	<a href="memARTrans.php?memID=<?php echo $memID; ?>">
		<img src="../images/AR.gif" width="72" height="16" border="0" />
	</a>
	<a href="memControl.php?memID=<?php echo $memID ?>">
		<img src="../images/control.gif" width="72" height="16" border="0" />
	</a>
	<a href="memTrans.php?memID=<?php echo $memID; ?>">
		<img src="../images/detail.gif" width="72" height="16" border="0" />
	</a>
   </td>
  </tr>
  <tr>
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="memDocs.php?memID=<?php echo $memID; ?>" target="_top" onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

//echo $memID;
//echo $lName;

$memNum = $_POST['memNum'];
$fName = $sql->escape($_POST['fName']);
$lName = $sql->escape($_POST['lName']);
$blueline = $memNum . " " . $_POST['lName'];
$bladd = "";
if ($_POST['status'] == "ACTIVE"){
	$bladd = " Coup(".$_POST['memcoupons'].")";
}
$blueline .= $bladd;
$blueline = $dbc->escape($blueline);
$address1 = $_POST['address1'];
$address2 = $_POST['address2'];
$city = $_POST['city'];
$state = $_POST['state'];
$zip = $_POST['zip'];
$startDate = $_POST['startDate'];
$arLimit = $_POST['chargeLimit'];
$phone = $_POST['phone'];
$phone2 = $_POST['phone2'];
$email = $_POST['email'];
$discList=$_POST['discList'];
//$charge1 = $_POST['charge1'];
//$checks1 = $_POST['checks1'];
//$charge2 = $_POST['charge2'];
//$checks2 = $_POST['checks2'];
//$charge3 = $_POST['charge3'];
//$checks3 = $_POST['checks3'];
$enddate = $_POST['endDate'];
$curDiscLimit = $_POST['curDiscLimit'];
$mailflag = $_POST['mailflag'];

add_second_server();
$sql->query_all(sprintf("DELETE FROM memberCards WHERE card_no=%d",$memNum));
if (isset($_REQUEST['cardUPC']) && is_numeric($_REQUEST['cardUPC'])){
	$sql->query_all(sprintf("INSERT INTO memberCards VALUES (%d,'%s')",
		$memNum,str_pad($_REQUEST['cardUPC'],13,'0',STR_PAD_LEFT)));
}

$sql->query_all("UPDATE meminfo SET ads_OK=$mailflag WHERE card_no=$memNum");
$sql->query_all("UPDATE memContact SET pref=$mailflag WHERE card_no=$memNum");

//echo $charge1."<br />".$charge2."<br />".$charge3."<br />".$checks1."<br />".$checks2."<br />".$checks3."<br />";
$charge1=$charge2=$charge3=0;
$checks1=$checks2=$checks3=0;
/*
if ($charge1 == 'on')
     $charge1 = 1;
else
     $charge1 = 0;
if ($charge2 == 'on')
     $charge2 = 1;
else
     $charge2 = 0;
if ($charge3 == 'on')
     $charge3 = 1;
else
     $charge3 = 0;
if ($checks1 == 'on')
     $checks1 = 1;
else
     $checks1 = 0;
if ($checks2 == 'on')
     $checks2 = 1;
else
     $checks2 = 0;
if ($checks3 == 'on')
     $checks3 = 1;
else
     $checks3 = 0;
*/

//echo $fname1.$fname2.$fname3."<br>";
//echo $memNum."<br>";
//echo $lName."<br>";
//echo $address1."<br>";
//echo $address2."<br>";
//echo $city."<br>";
//echo $state."<br>";
//echo $zip."<br>";
//echo $startDate."<br>";
//echo $enddate."<br>";
//echo $arLimit."<br>";
//echo "discList:".$discList."<br>";
//echo $lname1."<br>";
//echo $curDiscLimit."<br>";

if ($discList == '')
     $discList = $curDiscLimit;

$staff=0;
$disc = 0;
$mem = "REG";
if ($discList == 1 || $discList == 3)
	$mem = "PC";
if ($discList == 3 || $discList == 9){
	$disc = 12;
	$staff=1;
}

if (isset($discount) && isset($doDiscount))
	$disc = $discount;

// update top name
//echo "<br>";
$custdataQ = "Update custdata set firstname = $fName, lastname = $lName, blueline=$blueline where cardNo = $memNum and personnum = 1";
$memNamesQ = "Update memNames set fname = $fName, lname = $lName where memNum = $memNum and personnum = 1";
//echo $memNamesQ."<br>";
$custdataR = $sql->query_all($custdataQ);
//$memNamesR = $sql->query($memNamesQ);

// update other stuff
if(isset($discList)){
  $discMstrQ = "UPDATE mbrmastr SET DiscountPerc = $disc, memType=$discList, DiscountType = $discList WHERE memNum = $memNum";
  $discCORE = "UPDATE custdata SET memdiscountlimit = $arLimit,memType = $discList,Discount = $disc,staff=$staff WHERE cardNo = $memNum";
  $typeQ = "UPDATE custdata set type = '$mem' where cardNo=$memNum and type <> 'INACT' and type <> 'TERM'";
  //$discRes1 = $sql->query($discMstrQ);
  $discRes2 = $sql->query_all($discCORE);
  $typeR = $sql->query_all($typeQ);
}

$memDiscQ = "select memDiscountLimit,balance,type,memType,staff,SSI,discount,chargeOk,memCoupons from custdata where cardno=$memNum and personnum = 1";
$memDiscR = $sql->query($memDiscQ);
$memDiscRow = $sql->fetch_row($memDiscR);
// ideally, memdiscountlimit 0 would stop charges
// unfortunately, it stops ALL charges right now
$cd_charge1 = $memDiscRow[0];// * $charge1;
$cd_charge2 = $memDiscRow[0];// * $charge2;
$cd_charge3 = $memDiscRow[0];// * $charge3;
$balance = $memDiscRow[1];
$type = $memDiscRow[2];
$memType = $memDiscRow[3];
$staff = $memDiscRow[4];
$SSI = $memDiscRow[5];
$discount = $memDiscRow[6];
$can_charge = $memDiscRow[7];
$mcoup = $memDiscRow[8];

$delCQ = "delete from custdata where cardno=$memNum and personnum > 1";
$delMQ = "delete from memnames where memnum=$memNum and personnum > 1";
$delCR = $sql->query_all($delCQ);
//$delMR = $sql->query($delMQ);
=======
$MI_FIELDS = array();

$memNum = $_POST['memNum'];
$MI_FIELDS['street'] = $_POST['address1'] . (!empty($_POST['address2']) ? "\n".$_POST['address2'] : '');
$MI_FIELDS['city'] = $_POST['city'];
$MI_FIELDS['state'] = $_POST['state'];
$MI_FIELDS['zip'] = $_POST['zip'];
$MI_FIELDS['phone'] = $_POST['phone'];
$MI_FIELDS['email_2'] = $_POST['phone2'];
$MI_FIELDS['email_1'] = $_POST['email'];
$MI_FIELDS['ads_OK'] = $_POST['mailflag'];

$cust = new CustdataModel($dbc);
$cust->CardNo($memNum);
$cust->personNum(1);
$cust->load(); // get all current values
$cust->MemDiscountLimit($_POST['chargeLimit']);
$cust->ChargeLimit($_POST['chargeLimit']);
$cust->ChargeOk( $_POST['chargeLimit'] == 0 ? 0 : 1 );
$cust->memType($_POST['discList']);
$cust->Type('REG');
$cust->Staff(0);
$cust->Discount(0);

MemberCardsModel::update($memNum,$_REQUEST['cardUPC']);

$mcP = $sql->prepare("UPDATE memContact SET pref=? WHERE card_no=?");
$sql->execute($mcP, array($MI_FIELDS['ads_OK'], $memNum));

if ($cust->memType() == 1 || $cust->memType() == 3){
	$cust->Type('PC');
}
if ($cust->memType() == 3 || $cust->memType() == 9){
	$cust->Discount(12);
	$cust->Staff(1);
}

$cust->FirstName($_POST['fName']);
$cust->LastName($_POST['lName']);
$cust->BlueLine( $cust->CardNo().' '.$cust->LastName() );
$cust->save(); // save personNum=1
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d

$lnames = $_REQUEST['hhLname'];
$fnames = $_REQUEST['hhFname'];
$count = 2;
for($i=0;$i<count($lnames);$i++){
	if (empty($lnames[$i]) && empty($fnames[$i])) continue;

<<<<<<< HEAD
	$fname1 = $sql->escape($fnames[$i]);
	$lname1 = $sql->escape($lnames[$i]);
	$blue1 = $sql->escape($memNum.' '.$lnames[$i]);

	$houseCustUpQ1 = "Insert into custdata (lastname,firstname,blueline,cardno,personnum,chargeok,
			writechecks,shown,memDiscountLimit,type,memType,staff,SSI,balance,discount,
			CashBack,StoreCoupons,Purchases,NumberOfChecks,memCoupons) values ($lname1,
			$fname1,$blue1,$memNum,$count,1,$checks1,1,$cd_charge1,'$type',$memType,$staff,
			$SSI,$balance,$discount,0,0,0,999,$mcoup)";

	$houseMemUpQ1 = "insert into memnames (lname,fname,memnum,personnum,charge,checks,active) values ($lname1,$fname1,$memNum,$count,$charge1,$checks1,1)";

	$sql->query_all($houseCustUpQ1);
	//$sql->query($houseMemUpQ1);
=======
	$cust->personNum($count);
	$cust->FirstName($fnames[$i]);
	$cust->LastName($lnames[$i]);
	$cust->BlueLine( $cust->CardNo().' '.$cust->LastName() );
	$cust->save(); // save next personNum
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d

	$count++;
}
// remove names that were blank on the form
for($i=$count;$i<5;$i++){
	$cust->personNum($i);
	$cust->delete();
}

<<<<<<< HEAD
$mbrQ =    "UPDATE mbrmastr SET zipCode = '$zip',phone ='$phone',address1='$address1',address2='$address2',arLimit=$arLimit,city='$city',state='$state',startdate='$startDate',enddate = '$enddate',notes='$phone2',emailaddress='$email'  WHERE memNum = $memNum";
//$result=$sql->query($mbrQ);
$meminfoQ = sprintf("UPDATE meminfo SET street='%s',city='%s',state='%s',zip='%s',phone='%s',email_1='%s',email_2='%s'
		WHERE card_no=%d",(!empty($address2)?"$address1\n$address2":$address1),
			$city,$state,$zip,
			$phone,$email,$phone2,$memNum);
$sql->query_all($meminfoQ);

$datesQ = "UPDATE memDates SET start_date='$startDate',end_date='$enddate' WHERE card_no=$memNum";
$sql->query_all($datesQ);
=======
MeminfoModel::update($memNum, $MI_FIELDS);
MemDatesModel::update($memNum, $_POST['startDate'], $_POST['endDate']);
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d

// FIRE ALL UPDATE
include('custUpdates.php');
updateCustomerAllLanes($memNum);

/* general note handling */
$notetext = $_POST['notetext'];
$notetext = preg_replace("/\n/","<br />",$notetext);
$notetext = preg_replace("/\'/","''",$notetext);
$checkQ = $sql->prepare("select * from memberNotes where note=? and cardno=?");
$checkR = $sql->execute($checkQ, array($notetext, $memNum));
if ($sql->num_rows($checkR) == 0){
	$noteQ = $sql->prepare("insert into memberNotes (cardno, note, stamp, username) VALUES (?, ?, ".$sql->now().", ?)");
	$noteR = $sql->execute($noteQ, array($memNum, $notetext, $username));
}

header('Location: memGen.php?memNum='.$memNum);

