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

function scan_layouts(){
<<<<<<< HEAD
	global $FANNIE_ROOT;
	$layouts = array();
	$dh = opendir($FANNIE_ROOT.'admin/labels/pdf_layouts/');
	while( ($file=readdir($dh)) !== False){
		if ($file[0] == ".") continue;
		if (substr(strtolower($file),-4) == ".php")
			$layouts[] = str_replace("_"," ",substr($file,0,strlen($file)-4));
	}
	sort($layouts);

	return $layouts;
=======
    $layouts = array();
    $dh = opendir(dirname(__FILE__).'/pdf_layouts/');
    while( ($file=readdir($dh)) !== False){
        if ($file[0] == ".") continue;
        if (substr(strtolower($file),-4) == ".php")
            $layouts[] = str_replace("_"," ",substr($file,0,strlen($file)-4));
    }
    sort($layouts);

    return $layouts;
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d
}
