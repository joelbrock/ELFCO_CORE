<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class DonationKey extends Parser 
{
	function check($str)
    {
		if ($str == "RU" || substr($str,-2)=="RU") {
			return true;
        } else {
            return false;
        }
	}

	function parse($str)
    {
		global $CORE_LOCAL;
        $dept = $CORE_LOCAL->get('roundUpDept');
        if ($dept === '') {
            $dept = 701;
        }

		$ret = $this->default_json();
		if ($str == "RU") {
			Database::getsubtotals();
			$ttl = $CORE_LOCAL->get("amtdue");	
			$next = ceil($ttl);
<<<<<<< HEAD
			$amt = ($ttl == $next) ? 1.00 : $next - $ttl;
			$ret = PrehLib::deptkey($amt*100, 850, $ret);
=======
			$amt = sprintf('%.2f',(($ttl == $next) ? 1.00 : ($next - $ttl)));
<<<<<<< HEAD
			$ret = PrehLib::deptkey($amt*100, 7010, $ret);
>>>>>>> 6ef701b7099b88df44d419903824240e3f91a588
		}
		else {
			$amt = substr($str,0,strlen($str)-2);
			$ret = PrehLib::deptkey($amt, 850, $ret);
=======
			$ret = PrehLib::deptkey($amt*100, $dept.'0', $ret);
		} else {
			$amt = substr($str,0,strlen($str)-2);
			$ret = PrehLib::deptkey($amt, $dept.'0', $ret);
>>>>>>> df8b0cc72594d5f680991ca82124b29d3130232d
		}

		return $ret;
	}

	function doc()
    {
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>DONATE</td>
				<td>
				Round transaction up to next dollar
				with open ring to donation department.
				</td>
			</tr>
			</table>";
	}
}

