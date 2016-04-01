<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class GeneralDayReport extends FannieReportPage 
{
    public $description = '[General Day Report] lists tenders, sales, discounts, and taxes for a given day
        (should net to zero). Also listed are transaction count &amp; size information by member type and
        equity sales for the day.'; 
    public $report_set = 'Sales Reports';
    public $themed = true;

    protected $title = "Fannie : General Day Report";
    protected $header = "General Day Report";
    protected $report_cache = 'none';
    protected $grandTTL = 1;
    protected $multi_report_mode = true;
    protected $sortable = false;
    protected $no_sort_but_style = true;

    protected $report_headers = array('Desc','Qty','Amount');
    protected $required_fields = array('date1');

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB, $FANNIE_EQUITY_DEPARTMENTS,
            $FANNIE_COOP_ID, $FANNIE_REGISTER_NO;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $d1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $d2 = FormLib::get_form_value('date2',$d1);
        $dates = array($d1.' 00:00:00',$d2.' 23:59:59');
        $data = array();

        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
            $shrinkageUsers = " AND d.card_no not between 99900 and 99998";
        else
            $shrinkageUsers = "";

        $reconciliation = array(
            'Tenders' => 0.0,
            'Sales' => 0.0,
            'Discounts' => 0.0,
            'Tax' => 0.0,
        );

        $dlog = DTransactionsModel::selectDlog($d1);
        $deptSalesQ = $dbc->prepare_statement("SELECT t.dept_name,SUM(d.quantity) AS qty,
                SUM(CASE WHEN d.department IN(50,51) THEN (d.regPrice * d.quantity) ELSE d.total END) AS total
                FROM $dlog AS d LEFT JOIN
                {$FANNIE_OP_DB}.departments AS t ON d.department = t.dept_no
                WHERE d.tdate BETWEEN ? AND ? AND d.department <> 0 AND d.department NOT IN (87,89)
		AND d.trans_subtype NOT IN('MC','CP','IC')
                GROUP BY d.department ORDER BY t.dept_no");
        $deptSalesR = $dbc->exec_statement($deptSalesQ,$dates);
        $report = array();
        while($deptSalesW = $dbc->fetch_row($deptSalesR)) {
                $record = array($deptSalesW['dept_name'],
                        sprintf('%.2f',$deptSalesW['qty']),
                        sprintf('%.2f',$deptSalesW['total']));
                $report[] = $record;
        }
        $data[] = $report;

        $tenderQ = $dbc->prepare_statement("SELECT 
            TenderName,count(d.total),sum(d.total) as total
            FROM $dlog as d,
                {$FANNIE_OP_DB}.tenders as t 
            WHERE d.tdate BETWEEN ? AND ?
                AND d.trans_subtype = t.TenderCode
				AND d.trans_subtype NOT IN('IC','MC','CP')
                AND d.total <> 0{$shrinkageUsers}
            GROUP BY t.TenderName ORDER BY TenderName");
        $tenderR = $dbc->exec_statement($tenderQ,$dates);
        $report = array();
        while($tenderW = $dbc->fetch_row($tenderR)){
            $record = array($tenderW['TenderName'],$tenderW[1],
                    sprintf('%.2f',$tenderW['total']));
            $report[] = $record;
            $reconciliation['Tenders'] += $tenderW['total'];
        }
        $data[] = $report;

        $report = array();
        $discountAQ = $dbc->prepare_statement("SELECT 
            TenderName,count(d.total),sum(d.total) as total
            FROM $dlog as d,
                {$FANNIE_OP_DB}.tenders as t 
            WHERE d.tdate BETWEEN ? AND ?
                AND d.trans_subtype = t.TenderCode
                AND d.trans_subtype IN('IC','MC','CP')
		AND d.total <> 0{$shrinkageUsers}
            GROUP BY t.TenderName ORDER BY TenderName");
        $discountAR = $dbc->exec_statement($discountAQ,$dates);
        $report = array();
        while($discountAW = $dbc->fetch_row($discountAR)){
            $record = array($discountAW['TenderName'],$discountAW[1],
                    sprintf('%.2f',$discountAW['total']));
            $report[] = $record;
            $reconciliation['Tenders'] += $discountAW['total'];
        }
        $discountA2Q = $dbc->prepare_statement("SELECT t.dept_name,SUM(d.quantity) AS qty,
                SUM(CASE WHEN (d.department IN (50,51)) THEN -d.discount ELSE d.total END) AS total
                FROM $dlog AS d LEFT JOIN
                {$FANNIE_OP_DB}.departments AS t ON d.department = t.dept_no
                WHERE d.tdate BETWEEN ? AND ? AND d.department <> 0 AND d.department IN (50,51,87,89)
		AND d.discounttype = 0
                GROUP BY d.department ORDER BY t.dept_no");
        $discountA2R = $dbc->exec_statement($discountA2Q,$dates);
        while($discountA2W = $dbc->fetch_row($discountA2R)) {
                $record = array($discountA2W['dept_name'],
                        sprintf('%.2f',$discountA2W['qty']),
                        sprintf('%.2f',$discountA2W['total']));
                $report[] = $record;
        }		
	// added 2016-03-02 to support new 5% register discount for memtypes 1 and 2  ~jb
	$discountA3Q = $dbc->prepare_statement("SELECT 'Owner 5% Discount' as label, count(*), SUM(total) as Discount
		FROM $dlog WHERE tdate BETWEEN ? AND ? AND upc = 'DISCOUNT' AND memtype IN(1,2)
		AND total <> 0");
	$discountA3R = $dbc->exec_statement($discountA3Q,$dates);
	while($discountA3W = $dbc->fetch_row($discountA3R)) {
		$record = array($discountA3W[0],
			sprintf('%.2f',$discountA3W[1]),
			sprintf('%.2f',$discountA3W[2]));
		$report[] = $record;
		$reconciliation['Discounts'] += $discountA3W['Discount'];
	}
        $data[] = $report;
		
		$discQ = $dbc->prepare_statement("SELECT m.memDesc, SUM(d.total) AS Discount,count(*)
                FROM $dlog d 
                    INNER JOIN memtype m ON d.memType = m.memtype
                WHERE d.tdate BETWEEN ? AND ?
                   AND d.upc = 'DISCOUNT'{$shrinkageUsers}
                AND total <> 0 AND d.memType NOT IN (1,2) 
                GROUP BY m.memDesc ORDER BY m.memDesc");
        $discR = $dbc->exec_statement($discQ,$dates);
        $report = array();
        while($discW = $dbc->fetch_row($discR)){
            $record = array($discW['memDesc'],$discW[2],$discW[1]);
            $report[] = $record;
            $reconciliation['Discounts'] += $discW['Discount'];
        }
        $data[] = $report;
		
        $report = array();
        $trans = DTransactionsModel::selectDTrans($d1);
        $lineItemQ = $dbc->prepare("
            SELECT description,
                SUM(regPrice) AS ttl
            FROM $trans AS d
            WHERE datetime BETWEEN ? AND ?
                AND d.upc='TAXLINEITEM'
                AND " . DTrans::isNotTesting('d') . "
            GROUP BY d.description
        ");
        $lineItemR = $dbc->execute($lineItemQ, $dates);
        while ($lineItemW = $dbc->fetch_row($lineItemR)) {
            $record = array($lineItemW['description'] . ' (est. owed)', sprintf('%.2f', $lineItemW['ttl']));
            $report[] = $record;
        }

        $taxSumQ = $dbc->prepare_statement("SELECT  sum(total) as tax_collected
            FROM $dlog as d 
            WHERE d.tdate BETWEEN ? AND ?
                AND (d.upc = 'tax'){$shrinkageUsers}
            GROUP BY d.upc");
        $taxR = $dbc->exec_statement($taxSumQ,$dates);
        while($taxW = $dbc->fetch_row($taxR)){
            $record = array('Total Tax Collected',round($taxW['tax_collected'],2));
            $report[] = $record;
            $reconciliation['Tax'] = $taxW['tax_collected'];
        }
        $data[] = $report;

        $report = array();		
        $salesQ = $dbc->prepare_statement("SELECT m.super_name,sum(d.quantity) as qty,
                sum(d.total) as total
                FROM $dlog AS d LEFT JOIN
                {$FANNIE_OP_DB}.MasterSuperDepts AS m ON d.department=m.dept_ID
                WHERE d.tdate BETWEEN ? AND ?
                    AND d.department <> 0 AND d.trans_type <> 'T'{$shrinkageUsers}
                GROUP BY m.super_name ORDER BY m.super_name");
        $salesR = $dbc->exec_statement($salesQ,$dates);
        $report = array();
        while($salesW = $dbc->fetch_row($salesR)){
            $record = array($salesW['super_name'],
                    sprintf('%.2f',$salesW['qty']),
                    sprintf('%.2f',$salesW['total']));
            $report[] = $record;
            $reconciliation['Sales'] += $salesW['total'];
        }
        $data[] = $report;



        $report = array();
        foreach ($reconciliation as $type => $amt) {
            $report[] = array(
                $type,
                sprintf('%.2f', $amt),
            );
        }
        $data[] = $report;

        $transQ = $dbc->prepare_statement("select q.trans_num,sum(q.quantity) as items,transaction_type, sum(q.total) from
            (
            select trans_num,card_no,quantity,total,
            m.memDesc as transaction_type
            from $dlog as d
            left join memtype as m on d.memType = m.memtype
            WHERE d.tdate BETWEEN ? AND ?
                AND trans_type in ('I','D')
                AND upc <> 'RRR'{$shrinkageUsers}
            ) as q 
            group by q.trans_num,q.transaction_type");
        $transR = $dbc->exec_statement($transQ,$dates);
        $transinfo = array();
        while($row = $dbc->fetch_array($transR)){
            if (!isset($transinfo[$row[2]]))
                $transinfo[$row[2]] = array(0,0.0,0.0,0.0,0.0);
            $transinfo[$row[2]][0] += 1;
            $transinfo[$row[2]][1] += $row[1];
            $transinfo[$row[2]][3] += $row[3];
        }
        $tSum = 0;
        $tItems = 0;
        $tDollars = 0;
        foreach(array_keys($transinfo) as $k){
            $transinfo[$k][2] = round($transinfo[$k][1]/$transinfo[$k][0],2);
            $transinfo[$k][4] = round($transinfo[$k][3]/$transinfo[$k][0],2);
            $tSum += $transinfo[$k][0];
            $tItems += $transinfo[$k][1];
            $tDollars += $transinfo[$k][3];
        }
        $report = array();
        foreach($transinfo as $title => $info){
            array_unshift($info,$title);
            $report[] = $info;
        }
        $data[] = $report;
        $ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
        if ($ret != 0){
            /* equity departments exist */
            $depts = array_pop($depts);
            $dlist = "(";
            foreach($depts as $d){
                $dates[] = $d; // add query param
                $dlist .= '?,';
            }
            $dlist = substr($dlist,0,strlen($dlist)-1).")";

            $equityQ = $dbc->prepare_statement("SELECT d.card_no,t.dept_name, sum(total) as total 
                FROM $dlog as d
                LEFT JOIN {$FANNIE_OP_DB}.departments as t ON d.department = t.dept_no
                WHERE d.tdate BETWEEN ? AND ?
                    AND d.department IN $dlist{$shrinkageUsers}
		    AND d.register_no <> $FANNIE_REGISTER_NO 
                GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name");
            $equityR = $dbc->exec_statement($equityQ,$dates);
            $report = array();
            while($equityW = $dbc->fetch_row($equityR)){
                $record = array($equityW['card_no'],$equityW['dept_name'],
                        sprintf('%.2f',$equityW['total']));
                $report[] = $record;
            }
            $data[] = $report;
        }
        
        return $data;
    }

    function calculate_footers($data)
    {
        switch($this->multi_counter){
        case 1:
            $this->report_headers[0] = 'Departments';
            break;
        case 2:
            $this->report_headers[0] = 'Tenders';
            break;
	case 3:
	     $this->report_headers[0] = 'Discounts A';
	     break;
	case 4:
	    $this->report_headers[0] = 'Discounts B';
	    break;
        case 5:
            $this->report_headers = array('Tax', 'Amount');
            $sumTax = 0.0;
            for ($i=0; $i<count($data)-1; $i++) {
                $sumTax += $data[$i][1];
            }
            return array('Total Sales Tax', sprintf('%.2f', $sumTax));
            break;
        case 6:
            $this->report_headers[0] = array('Sales','Qty','Amount');
            break;
        case 7:
            $this->report_headers = array('Reconcile Totals', 'Amount');
            $ttl = 0.0;
            foreach ($data as $row) {
                $ttl += $row[1];
            }
            return array('Net', sprintf('%.2f', $ttl));
        case 8:
            $this->report_headers = array('Type','Trans','Items','Avg. Items','Amount','Avg. Amount');
            $trans = 0.0;
            $items = 0.0;
            $amount = 0.0;
            for ($i=0; $i<count($data); $i++) {
                $trans += $data[$i][1];
                $items += $data[$i][2];
                $amount += $data[$i][4];
            }
            return array('Totals', $trans, sprintf('%.2f', $items), sprintf('%.2f', $items/$trans), sprintf('%.2f', $amount), sprintf('%.2f', $amount/$trans));
            break;
        case 9:
            $this->report_headers = array('Mem#','Equity Type', 'Amount');
            $sumSales = 0.0;
            foreach ($data as $row) {
                $sumSales += $row[2];
            }
            return array(null,null,$sumSales);
            break;
        }
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row){
            $sumQty += $row[1];
            $sumSales += $row[2];
        }
        return array(null,$sumQty,$sumSales);
    }

    function form_content()
    {
        $start = date('Y-m-d',strtotime('yesterday'));
        ?>
        <form action=GeneralDayReport.php method=get>
        <div class="form-group">
        <label>Date</label>
        <input type=text id=date1 name=date1 
            class="form-control date-field" required />
	<label>End Date</label>
	<input type=text id=date2 name=date2
	    class="form-control date-field" />
        </div>
        <label>Excel <input type=checkbox name=excel /></label>
        <p>
        <button type=submit name=submit value="Submit"
            class="btn btn-default">Submit</button>
        </p>
        </form>
        <?php
    }
}

FannieDispatch::conditionalExec(false);

?>