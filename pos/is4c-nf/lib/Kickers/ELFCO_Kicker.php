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

/**
  @class ELFCO_Kicker

*/
class ELFCO_Kicker extends Kicker 
{

    public function doKick($trans_num)
    {
        global $CORE_LOCAL;
        if($CORE_LOCAL->get('training') == 1) {
            return false;
        }
        $db = Database::tDataConnect();

        $query = "SELECT trans_id   
                  FROM localtranstoday 
                  WHERE 
                    (trans_subtype = 'CA' and total <> 0) OR
					(trans_subtype = 'DCCB' AND total <> 0) OR
		            (trans_subtype = 'CKCB' AND total <> 0)
                    AND " . $this->refToWhere($trans_num);

        $result = $db->query($query);
        $num_rows = $db->num_rows($result);

        return ($num_rows > 0) ? true : false;
    }

    protected function refToWhere($ref)
    {
        list($e, $r, $t) = explode('-', $ref, 3);
        return sprintf(' emp_no=%d AND register_no=%d AND trans_no=%d ',
                        $e, $r, $t);
    }    

    public function kickOnSignIn() 
	{
        global $CORE_LOCAL;
        if($CORE_LOCAL->get('training') == 1) {
            return false;
        }

        return true;
    }
    public function kickOnSignOut()
    {
        global $CORE_LOCAL;
        if($CORE_LOCAL->get('training') == 1) {
            return false;
        }

        return true;
    }
}


?>
