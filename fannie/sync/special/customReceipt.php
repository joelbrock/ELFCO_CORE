<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op
  
    This file is part of Fannie.
  
    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

<<<<<<< HEAD:fannie/sync/special/customReceipt.php
// Run DTS to export server data to a CSV file
$dbc->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U $FANNIE_SERVER_USER /P $FANNIE_SERVER_PW /N CSV_products',no_output",$FANNIE_OP_DB);

// on each MySQL lane, load the CSV file
foreach($FANNIE_LANES as $lane){

	if ($lane['type'] != 'MYSQL') continue;

	$dbc->add_connection($lane['host'],$lane['type'],$lane['op'],
			$lane['user'],$lane['pw']);
	if ($dbc->connections[$lane['op']] !== False){

		if (!is_readable('/pos/csvs/customReceipt.csv')) break;
		
		$dbc->query("TRUNCATE TABLE customReceipt",$lane['op']);

		$dbc->query("LOAD DATA LOCAL INFILE '/pos/csvs/customReceipt.csv' INTO TABLE
			products FIELDS TERMINATED BY ',' OPTIONALLY
			ENCLOSED BY '\"' LINES TERMINATED BY '\\r\\n'",$lane['op']);
	}
=======
require('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$page_title = 'Fannie - Sale Signs';
$header = 'Sale Signs';
include($FANNIE_ROOT.'src/header.html');

if (!isset($_REQUEST['signtype'])){
    echo '<ul>';
    $dh = opendir('enabled');
    while(($file=readdir($dh)) !== False){
        if ($file[0] == ".") continue;
        if (substr($file,-4) != ".php") continue;
        printf('<li><a href="index.php?action=start&signtype=%s">%s</a></li>',
            substr($file,0,strlen($file)-4),
            substr($file,0,strlen($file)-4)
        );
    }
    echo '</ul>';
}
else {
    $class = $_REQUEST['signtype'];
    include('enabled/'.$class.'.php');
    $obj = new $class();
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d:fannie/admin/signs/index.php
}

echo "<li>customReceipt table synched</li>";

?>
