<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}

class PIApply extends FannieRESTfulPage 
{
    public function preprocess()
    {
        $this->__routes[] = 'get<id><email>';

        return parent::preprocess();
    }

    public function get_id_email_handler()
    {
        global $FANNIE_OP_DB;
        $mem = new MeminfoModel(FannieDB::get($FANNIE_OP_DB));
        $mem->card_no($this->id);
        $mem->email_1($this->email);
        $mem->save();

        header('Location: PIMemberPage.php?id=' . $this->id);

        return false;
    }

}

FannieDispatch::conditionalExec();
