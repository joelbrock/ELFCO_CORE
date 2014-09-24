<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

class SigTermCommands extends Parser {

	function check($str){
		global $CORE_LOCAL;
		if ($str == "TERMMANUAL"){
			UdpComm::udpSend("termManual");
			return True;
		}
<<<<<<< HEAD
		elseif ($str == "TERMRESET"){
			UdpComm::udpSend("termReset");
=======
		elseif ($str == "TERMRESET" || $str == "TERMREBOOT"){
			if ($str == "TERMRESET")
				UdpComm::udpSend("termReset");
			else
				UdpComm::udpSend("termReboot");
			$CORE_LOCAL->set("paycard_keyed",False);
			$CORE_LOCAL->set("CachePanEncBlock","");
			$CORE_LOCAL->set("CachePinEncBlock","");
			$CORE_LOCAL->set("CacheCardType","");
			$CORE_LOCAL->set("CacheCardCashBack",0);
>>>>>>> 6ef701b7099b88df44d419903824240e3f91a588
			return True;
		}
		elseif ($str == "CCFROMCACHE"){
			return True;
		}
		else if (substr($str,0,9) == "PANCACHE:"){
			$CORE_LOCAL->set("CachePanEncBlock",substr($str,9));
			return True;
		}
		else if (substr($str,0,9) == "PINCACHE:"){
			$CORE_LOCAL->set("CachePinEncBlock",substr($str,9));
			return True;
		}
		else if ($str == "TERMCLEARALL"){
			$CORE_LOCAL->set("CachePanEncBlock","");
			$CORE_LOCAL->set("CachePinEncBlock","");
			$CORE_LOCAL->set("CacheCardType","");
			$CORE_LOCAL->set("CacheCardCashBack",0);
			return True;
		}
		else if (substr($str,0,5) == "TERM:"){
			$CORE_LOCAL->set("CacheCardType",substr($str,5));
			return True;
		}
		else if (substr($str,0,7) == "TERMCB:"){
			$CORE_LOCAL->set("CacheCardCashBack",substr($str,7));
			return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if ($str == "CCFROMCACHE"){
			$ret['retry'] = $CORE_LOCAL->get("CachePanEncBlock");
		}
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>TERMMANUAL</td>
				<td>
				Send CC terminal to manual entry mode
				</td>
			</tr>
			<tr>
				<td>TERMRESET</td>
				<td>Reset CC terminal to begin transaction</td>
			</tr>
			<tr>
				<td>CCFROMCACHE</td>
				<td>Charge the card cached earlier</td>
			</tr>
			<tr>
				<td>PANCACHE:<encrypted block></td>
				<td>Cache an encrypted block on swipe</td>
			</tr>
			<tr>
				<td>PINCACHE:<encrypted block></td>
				<td>Cache an encrypted block on PIN entry</td>
			</tr>
			</table>";
	}
}

?>
