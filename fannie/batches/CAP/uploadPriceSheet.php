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

include(dirname(__FILE__) . '/../../config.php');
if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)) {
    return;
}

if (isset($_POST['MAX_FILE_SIZE'])){
    $tpath = sys_get_temp_dir()."/vendorupload/";
    if (!is_dir($tpath)) mkdir($tpath);
    $dh = opendir($tpath);
    while (($file = readdir($dh)) !== false) {
        if (!is_dir($tpath.$file)) unlink($tpath.$file);
    }
    closedir($dh);

<<<<<<< HEAD:fannie/batches/CAP/uploadPriceSheet.php
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	if ($path_parts['extension'] == "zip"){
		move_uploaded_file($tmpfile,$tpath."CAP.zip");
		$output = system("unzip {$tpath}CAP.zip -d $tpath &> /dev/null");
		unlink($tpath."CAP.zip");
		$dh = opendir($tpath);
		while (($file = readdir($dh)) !== false) {
			if (!is_dir($tpath.$file)) rename($tpath.$file,$tpath."CAP.csv");
		}
		closedir($dh);
	}
	else {
		move_uploaded_file($tmpfile, $tpath."CAP.csv");
	}
	header("Location: loadSales.php");
}
else {

	/* html header, including navbar */
	$page_title = "Fannie - CAP sales";
	$header = "Upload CAP file";
	include($FANNIE_ROOT."src/header.html");
=======
    $tmpfile = $_FILES['upload']['tmp_name'];
    $path_parts = pathinfo($_FILES['upload']['name']);
    if ($path_parts['extension'] == "zip"){
        move_uploaded_file($tmpfile,$tpath."CAP.zip");
        $output = system("unzip {$tpath}CAP.zip -d $tpath &> /dev/null");
        unlink($tpath."CAP.zip");
        $dh = opendir($tpath);
        while (($file = readdir($dh)) !== false) {
            if (!is_dir($tpath.$file)) rename($tpath.$file,$tpath."lcimp.csv");
        }
        closedir($dh);
    }
    else {
        move_uploaded_file($tmpfile, $tpath."lcimp.csv");
    }
    header("Location: load.php");
}
else {

    /* html header, including navbar */
    $page_title = "Fannie - Import info";
    $header = "Import CSV info";
    include($FANNIE_ROOT."src/header.html");
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d:fannie/admin/import/byLC.php
?>
<form enctype="multipart/form-data" action="uploadPriceSheet.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
<p>NOTE: Save co+op deals files as a .csv (<i>M$ Excel won't work</i>, use 
	OpenOffice or GoogleDocs). Then upload the .csv file, NOT the .xls file.</p>

<?php
    /* html footer */
    include($FANNIE_ROOT."src/footer.html");
}
?>
