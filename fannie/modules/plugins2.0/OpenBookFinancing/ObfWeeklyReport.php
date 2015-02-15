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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ObfWeeklyReport extends FannieReportPage
{
    protected $header = 'OBF: Weekly Report';
    protected $title = 'OBF: Weekly Report';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Weekly Report] shows sales and labor data for a given week.';
    public $themed = true;

    protected $required_fields = array('weekID');

    protected $report_headers = array(
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', 'Last Year', 'Plan Goal', '% Store', 'Trend', 'Actual', '% Growth', '% Store', 'Current O/U', 'Long-Term O/U'),
        array('', '', 'Plan Goal', '%', '', 'Actual', '%', 'Est. Bonus', 'Current Year', 'Last Year'),
    );

    public function report_description_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));

        return array('Week ' . date('F d, Y', $start_ts) . ' to ' . date('F d, Y', $end_ts));
    }

    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();

        $colors = array(
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#99C299',
            '#CDB49B',
            '#6685C2',
            '#FF4D4D',
            '#99C299',
            '#C299EB',
            '#FFB280',
            '#FFFF66',
        );
        
        $labor = new ObfLaborModel($dbc);
        $labor->obfWeekID($week->obfWeekID());
        
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));
        $start_ly = strtotime($week->previousYear());
        $end_ly = mktime(0, 0, 0, date('n', $start_ly), date('j', $start_ly)+6, date('Y', $start_ly));

        $month = false;
        $year = false;
        if (date('n', $start_ts) == date('n', $end_ts)) {
            $month = date('n', $start_ts);
            $year = date('Y', $start_ts);
        } else {
            $split = 0;
            for ($i=0; $i<7; $i++) {
                $ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+$i, date('Y', $start_ts));
                if (date('n', $start_ts) == date('n', $ts)) {
                    $split++;
                }
            }
            if ($split >= 4) {
                $month = date('n', $start_ts);
                $year = date('Y', $start_ts);
            } else {
                $month = date('n', $end_ts);
                $year = date('Y', $end_ts);
            }
        }

        $start_ly = mktime(0, 0, 0, $month, 1, $year-1);
        $end_ly = mktime(0, 0, 0, $month, date('t', $start_ly), $year-1);

        $future = $end_ts >= strtotime(date('Y-m-d')) ? true: false;

        $sales = new ObfSalesCacheModel($dbc);
        $sales->obfWeekID($week->obfWeekID());
        $sales->actualSales(0, '>');
        $num_cached = $sales->find();
        if (count($num_cached) == 0) {
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());
            $salesQ = 'SELECT 
                        m.obfCategoryID as id,
                        m.superID,
                        SUM(t.total) AS sales
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                        LEFT JOIN ObfCategories AS c
                            ON m.obfCategoryID=c.obfCategoryID
                       WHERE c.hasSales=1
                        AND t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                       GROUP BY m.obfCategoryID, m.superID';

            $transQ = 'SELECT 
                        YEAR(t.tdate) AS year,
                        MONTH(t.tdate) AS month,
                        DAY(t.tdate) AS day,
                        t.trans_num
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                       WHERE 
                        t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                        AND t.upc <> \'RRR\'
                       GROUP BY 
                        YEAR(t.tdate),
                        MONTH(t.tdate),
                        DAY(t.tdate),
                        t.trans_num';

            $dlog1 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ts), date('Y-m-d', $end_ts));
            $dlog2 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ly), date('Y-m-d', $end_ly));
            $args = array(date('Y-m-d 00:00:00', $start_ts), date('Y-m-d 23:59:59', $end_ts));

            $trans1Q = str_replace('__table__', $dlog1, $transQ);
            $transP = $dbc->prepare($trans1Q);
            $transR = $dbc->execute($transP, $args);
            if (!$future && $transR) {
                $sales->transactions($dbc->num_rows($transR));
            } else {
                $sales->transactions(0);
            }

            $oneQ = str_replace('__table__', $dlog1, $salesQ);
            $oneP = $dbc->prepare($oneQ);
            $oneR = $dbc->execute($oneP, $args);
            while($w = $dbc->fetch_row($oneR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $sales->actualSales($w['sales']);
                if ($future) {
                    $sales->actualSales(0);
                }
                $sales->growthTarget($week->growthTarget());
                $sales->save();
            }
            
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());
            $args = array(date('Y-m-d 00:00:00', $start_ly), date('Y-m-d 23:59:59', $end_ly));
            $num_days = (float)date('t', $start_ly);

            $trans2Q = str_replace('__table__', $dlog2, $transQ);
            $transP = $dbc->prepare($trans2Q);
            $transR = $dbc->execute($transP, $args);
            if ($transR) {
                $month_trans = $dbc->num_rows($transR);
                $avg_trans = ($month_trans / $num_days) * 7;
                $sales->lastYearTransactions($avg_trans);
            } else {
                $sales->lastYearTransactions(0);
            }

            $twoQ = str_replace('__table__', $dlog2, $salesQ);
            $twoP = $dbc->prepare($twoQ);
            $twoR = $dbc->execute($twoP, $args);
            while ($w = $dbc->fetch_row($twoR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $avg_sales = ($w['sales'] / $num_days) * 7;
                $sales->lastYearSales($avg_sales);
                if ($future) {
                    $sales->actualSales(0);
                    $sales->growthTarget($week->growthTarget());
                }
                $sales->save();
            }
        }

        $data = array();
        $total_sales = array(0, 0);
        $total_trans = 0;
        $ly_total_trans = 0;
        $total_hours = 0;
        $total_wages = 0;
        $proj_total = 0;
        $total_proj_wages = 0;
        $total_proj_hours = 0;
        $qtd_plan = 0;
        $qtd_sales = 0;
        $qtd_hours = 0;
        $qtd_wages = 0;
        $qtd_laborsales = 0;
        $qtd_proj_hours = 0;
        $qtd_proj_wages = 0;
        $qtd_sales_ou = 0;
        $qtd_hours_ou = 0;
        $qtd_wages_ou = 0;
        $qtd_trans = array(0, 0);
        $trend_total = 0;
        $total_trend_hours = 0;
        $total_trend_wages = 0;

        $categories = new ObfCategoriesModel($dbc);
        $categories->hasSales(1);
        $salesP = $dbc->prepare('SELECT s.actualSales,
                                    s.lastYearSales,
                                    s.growthTarget,
                                    n.super_name,
                                    s.superID,
                                    s.transactions,
                                    s.lastYearTransactions
                                 FROM ObfSalesCache AS s
                                    LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superDeptNames
                                        AS n ON s.superID=n.superID
                                 WHERE s.obfWeekID=?
                                    AND s.obfCategoryID=?
                                 ORDER BY s.superID,n.super_name');

        $quarterSalesP = $dbc->prepare('SELECT SUM(s.actualSales) AS actual,
                                            SUM(s.lastYearSales) AS lastYear,
                                            SUM(s.lastYearSales * (1+s.growthTarget)) AS plan,
                                            SUM(s.transactions) AS trans,
                                            SUM(s.lastYearTransactions) AS ly_trans
                                        FROM ObfSalesCache AS s
                                            INNER JOIN ObfWeeks AS w ON s.obfWeekID=w.obfWeekID
                                        WHERE w.obfQuarterID = ?
                                            AND s.obfCategoryID = ?
                                            AND s.superID=?
                                            AND w.endDate <= ?'); 
        $quarterLaborP = $dbc->prepare('SELECT SUM(l.hours) AS hours,
                                            SUM(l.wages) AS wages,
                                            AVG(l.laborTarget) as laborTarget,
                                            AVG(l.averageWage) as averageWage,
                                            SUM(l.hoursTarget) as hoursTarget
                                        FROM ObfLabor AS l
                                            INNER JOIN ObfWeeks AS w ON l.obfWeekID=w.obfWeekID
                                        WHERE w.obfLaborQuarterID=?
                                            AND l.obfCategoryID=?
                                            AND w.endDate <= ?');

        $quarterSplhP = $dbc->prepare('SELECT SUM(c.actualSales) AS actualSales
                                        FROM ObfLabor AS l
                                            INNER JOIN ObfWeeks AS w ON l.obfWeekID=w.obfWeekID
                                            INNER JOIN ObfSalesCache AS c ON c.obfWeekID=l.obfWeekID
                                                AND c.obfCategoryID=l.obfCategoryID
                                        WHERE w.obfLaborQuarterID=?
                                            AND l.obfCategoryID=?
                                            AND w.endDate <= ?');
        /**
          Calculate actual sales growth over
          the last three weeks with complete
          year-over-year basis. Used to allocate
          labor hours using growth trend and
          sales per labor hour goal
        */
        $splhWeeks = '(';
        $splhWeekQ = '
            SELECT c.obfWeekID
            FROM ObfSalesCache AS c
                INNER JOIN ObfWeeks AS w ON c.obfWeekID=w.obfWeekID
            GROUP BY c.obfWeekID
            HAVING SUM(c.actualSales) > 0
            ORDER BY MAX(w.endDate) DESC';
        $splhWeekQ = $dbc->add_select_limit($splhWeekQ, 13);
        $splhWeekR = $dbc->query($splhWeekQ);
        while ($splhWeekW = $dbc->fetch_row($splhWeekR)) {
            $splhWeeks .= sprintf('%d,', $splhWeekW['obfWeekID']);
        }
        $splhWeeks = substr($splhWeeks, 0, strlen($splhWeeks)-1) . ')';
        $trendQ = '
            SELECT 
                actualSales,
                lastYearSales
            FROM ObfSalesCache AS c
            WHERE c.obfCategoryID = ?
                AND c.superID = ?
                AND c.actualSales > 0
                AND c.obfWeekID IN ' . $splhWeeks . '
            ORDER BY c.obfWeekID';
        $trendP = $dbc->prepare($trendQ);
        $splhGrowthQ = '
            SELECT 
                (SUM(c.actualSales) - SUM(c.lastYearSales)) / SUM(c.actualSales) as avgGrowth,
                SUM(c.actualSales) AS actualSales,
                SUM(c.lastYearSales) AS lastYearSales
            FROM ObfSalesCache AS c
            WHERE c.obfCategoryID = ?
                AND c.actualSales > 0
                AND c.obfWeekID IN ' . $splhWeeks . '
            GROUP BY c.obfCategoryID';
        $splhGrowthP = $dbc->prepare($splhGrowthQ);
        $splh_info = array('actual'=>0.0, 'lastYear' => 0.0);

        foreach ($categories->find('name') as $category) {
            $data[] = array($category->name(), '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $sum = array(0.0, 0.0);
            $dept_proj = 0.0;
            $dept_trend = 0;
            $salesR = $dbc->execute($salesP, array($week->obfWeekID(), $category->obfCategoryID()));
            $qtd_dept_plan = 0;
            $qtd_dept_sales = 0;
            $qtd_dept_ou = 0;
            while ($w = $dbc->fetch_row($salesR)) {
                $proj = ($w['lastYearSales'] * $w['growthTarget']) + $w['lastYearSales'];

                $trendR = $dbc->execute($trendP, array($category->obfCategoryID(), $w['superID']));
                $dataset1 = array();
                $dataset2 = array();
                $x = 0;
                while ($trendW = $dbc->fetchRow($trendR)) {
                    $dataset1[] = array($x, $trendW['actualSales']);
                    $x++;
                }
                $dataset3 = array();
                $min_index = 0;
                $max_index = 0;
                for ($i=0; $i<count($dataset1); $i++) {
                    if ($dataset1[$i][1] < $dataset1[$min_index][1]) {
                        $min_index = $i;
                    }
                    if ($dataset1[$i][1] > $dataset1[$max_index][1]) {
                        $max_index = $i;
                    }
                }
                for ($i=0; $i<count($dataset1); $i++) {
                    if ($i != $min_index && $i != $max_index) {
                        $dataset3[] = $dataset1[$i];
                    }
                }
                $exp = $this->expFit($dataset3);
                $trend1 = exp($exp['a']) * exp($exp['b'] * $x);

                $dept_trend += $trend1;
                $trend_total += $trend1;

                $quarter = $dbc->execute($quarterSalesP, 
                    array($week->obfQuarterID(), $category->obfCategoryID(), $w['superID'], date('Y-m-d 00:00:00', $end_ts))
                );
                if ($dbc->num_rows($quarter) == 0) {
                    $quarter = array('actual'=>0, 'lastYear'=>0, 'plan'=>0, 'trans'=>0, 'ly_trans'=>0);
                } else {
                    $quarter = $dbc->fetch_row($quarter);
                }
                $qtd_dept_plan += $quarter['plan'];
                $qtd_dept_sales += $quarter['actual'];
                $qtd_trans = array($quarter['trans'], $quarter['ly_trans']);

                $record = array(
                    $w['super_name'],
                    number_format($w['lastYearSales'], 0),
                    number_format($proj, 0),
                    number_format($proj, 0),
                    number_format($trend1, 0),
                    number_format($w['actualSales'], 0),
                    sprintf('%.2f%%', $this->percentGrowth($w['actualSales'], $w['lastYearSales'])),
                    number_format($w['actualSales'], 0),
                    number_format($w['actualSales'] - $proj, 0),
                    number_format($quarter['actual'] - $quarter['plan'], 0),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $colors[0],
                    'meta_foreground' => 'black',
                );
                $sum[0] += $w['actualSales'];
                $sum[1] += $w['lastYearSales'];
                $total_sales[0] += $w['actualSales'];
                $total_sales[1] += $w['lastYearSales'];
                if ($total_trans == 0) {
                    $total_trans = $w['transactions'];
                }
                if ($ly_total_trans == 0) {
                    $ly_total_trans = $w['lastYearTransactions'];
                }
                $proj_total += $proj;
                $dept_proj += $proj;
                $qtd_plan += $quarter['plan'];
                $qtd_sales += $quarter['actual'];
                $qtd_sales_ou += ($quarter['actual'] - $quarter['plan']);
                $qtd_dept_ou += ($quarter['actual'] - $quarter['plan']);
                $data[] = $record;
            }

            $labor->obfCategoryID($category->obfCategoryID());
            $labor->load();
            $record = array(
                'Total',
                number_format($sum[1], 0),
                number_format($dept_proj, 0),
                number_format($dept_proj, 0), // % of store sales re-written later
                number_format($dept_trend, 0),
                number_format($sum[0], 0),
                sprintf('%.2f%%', $this->percentGrowth($sum[0], $sum[1])),
                number_format($sum[0], 0),
                number_format($sum[0] - $dept_proj, 0),
                number_format($qtd_dept_ou, 0),
                'meta' => FannieReportPage::META_COLOR | FannieReportPage::META_BOLD,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $data[] = $record;

            // use SPLH instead of pre-allocated
            $proj_hours = $dept_proj / $category->salesPerLaborHourTarget();
            $average_wage = 0;
            if ($labor->hours() != 0) {
                $average_wage = $labor->wages() / ((float)$labor->hours());
            }
            $proj_wages = $proj_hours * $average_wage;

            $trend_hours = $dept_trend / $category->salesPerLaborHourTarget();
            $trend_wages = $trend_hours * $average_wage;


            $quarter = $dbc->execute($quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0, 'actualSales' => 0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_splh = $dbc->execute($quarterSplhP,
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($qt_splh)) {
                $w = $dbc->fetch_row($qt_splh);
                $quarter['actualSales'] = $w['actualSales'];
            }
            $qt_average_wage = $quarter['wages'] / ((float)$quarter['hours']);
            $qt_proj_hours = $quarter['actualSales'] / $category->salesPerLaborHourTarget();
            $qt_proj_labor = $qt_proj_hours * $qt_average_wage;
            $qtd_hours += $quarter['hours'];
            $qtd_proj_hours += $qt_proj_hours;
            $qtd_laborsales += $quarter['actualSales'];

            $data[] = array(
                'Hours',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($trend_hours, 0),
                number_format($labor->hours(), 0),
                sprintf('%.2f%%', $this->percentGrowth($labor->hours(), $proj_hours)),
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format($quarter['hours'] - $qt_proj_hours, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_hours += $labor->hours();
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Wages',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($trend_wages, 0),
                number_format($labor->wages(), 0),
                sprintf('%.2f%%', $this->percentGrowth($labor->wages(), $proj_wages)),
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format($quarter['wages'] - $qt_proj_labor, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_wages += $labor->wages();
            $qtd_wages += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);
            $total_proj_wages += $proj_wages;
            $total_proj_hours += $proj_hours;
            $total_trend_wages += $trend_wages;
            $total_trend_hours += $trend_hours;

            $data[] = array(
                '% of Sales',
                '',
                sprintf('%.2f%%', $proj_wages / $dept_proj * 100),
                '',
                sprintf('%.2f%%', $trend_wages / $dept_trend * 100),
                sprintf('%.2f%%', $sum[0] == 0 ? 0 : $labor->wages() / $sum[0] * 100),
                sprintf('%.2f%%', $this->percentGrowth(($sum[0] == 0 ? 0 : $labor->wages()/$sum[0]*100), ($proj_wages/$dept_proj*100))),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = ($qtd_dept_sales)/($quarter['hours']);
            $quarter_proj_sph = ($qtd_dept_plan)/($qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                '',
                number_format($dept_proj / $proj_hours, 2),
                '',
                number_format($dept_trend / $trend_hours, 2),
                number_format($labor->hours() == 0 ? 0 : $sum[0] / $labor->hours(), 2),
                sprintf('%.2f%%', $this->percentGrowth(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()), $dept_proj/$proj_hours)),
                '',
                number_format(($labor->hours() == 0 ? 0 : $sum[0]/$labor->hours()) - ($dept_proj / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        for ($i=0; $i<count($data); $i++) {
            if (isset($data[$i][7]) && preg_match('/^[\d,]+$/', $data[$i][7])) {
                $amt = str_replace(',', '', $data[$i][7]);
                $percentage = ($total_sales[0] == 0) ? 0.00 : ((float)$amt) / ((float)$total_sales[0]);
                $data[$i][7] = number_format($percentage*100, 2) . '%';
            }
            if (isset($data[$i][3]) && preg_match('/^[\d,]+$/', $data[$i][3])) {
                $amt = str_replace(',', '', $data[$i][3]);
                $percentage = ((float)$amt) / ((float)$proj_total);
                $data[$i][3] = number_format($percentage*100, 2) . '%';
            }
        }

        $cat = new ObfCategoriesModel($dbc);
        $cat->hasSales(0);
        foreach ($cat->find('name') as $c) {
            $data[] = array($c->name(), '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $labor->obfCategoryID($c->obfCategoryID());
            $labor->load();

            $quarter = $dbc->execute($quarterLaborP, 
                array($week->obfLaborQuarterID(), $labor->obfCategoryID(), date('Y-m-d 00:00:00', $end_ts))
            );
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'hoursTarget'=>0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_average_wage = $quarter['wages'] / ((float)$quarter['hours']);
            $qt_proj_hours = $qtd_laborsales / $c->salesPerLaborHourTarget();
            $qt_proj_labor = $qt_proj_hours * $qt_average_wage;
            $qtd_hours += $quarter['hours'];
            $qtd_proj_hours += $qt_proj_hours;

            $average_wage = 0;
            if ($labor->hours() != 0) {
                $average_wage = $labor->wages() / ((float)$labor->hours());
            }
            // use SPLH instead of pre-allocated
            $proj_hours = $proj_total / $c->salesPerLaborHourTarget();
            $proj_wages = $proj_hours * $average_wage;

            $trend_hours = $trend_total / $c->salesPerLaborHourTarget();
            $trend_wages = $trend_hours * $average_wage;

            $data[] = array(
                'Hours',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($trend_hours, 0),
                number_format($labor->hours(), 0),
                '',
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format($quarter['hours'] - $qt_proj_hours, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Wages',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($trend_wages, 0),
                number_format($labor->wages(), 0),
                '',
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format($quarter['wages'] - $qt_proj_labor, 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_wages += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);

            $data[] = array(
                '% of Sales',
                '',
                sprintf('%.2f%%', $proj_wages / $proj_total * 100),
                '',
                sprintf('%.2f%%', $trend_wages / $trend_total * 100),
                sprintf('%.2f%%', $total_sales[0] == 0 ? 0.00 : $labor->wages() / $total_sales[0] * 100),
                '',
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = ($qtd_sales)/($quarter['hours']);
            $quarter_proj_sph = ($qtd_plan)/($qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                '',
                sprintf('%.2f', $proj_total / $proj_hours),
                '',
                sprintf('%.2f', $trend_total / $trend_hours),
                number_format($labor->hours() == 0 ? 0 : $total_sales[0] / $labor->hours(), 2),
                '',
                '',
                number_format(($labor->hours() == 0 ? 0 : $total_sales[0]/$labor->hours()) - ($proj_total / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            $total_hours += $labor->hours();
            $total_wages += $labor->wages();
            $total_proj_wages += $proj_wages;
            $total_proj_hours += $proj_hours;
            $total_trend_wages += $trend_wages;
            $total_trend_hours += $trend_hours;

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        $data[] = array('Total Store', '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
        );
        $data[] = array(
            'Sales',
            number_format($total_sales[1], 0),
            number_format($proj_total, 0),
            '',
            number_format($trend_total, 0),
            number_format($total_sales[0], 0),
            sprintf('%.2f%%', $this->percentGrowth($total_sales[0], $total_sales[1])),
            '',
            number_format($total_sales[0] - $proj_total, 0),
            number_format($qtd_sales_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Hours',
            '',
            number_format($total_proj_hours, 0),
            '',
            number_format($total_trend_hours, 0),
            number_format($total_hours, 0),
            '',
            '',
            number_format($total_hours - $total_proj_hours, 0),
            number_format($qtd_hours_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Wages',
            '',
            number_format($total_proj_wages, 0),
            '',
            number_format($total_trend_wages, 0),
            number_format($total_wages, 0),
            '',
            '',
            number_format($total_wages - $total_proj_wages, 0),
            number_format($qtd_wages_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Wages as % of Sales',
            '',
            sprintf('%.2f%%', $total_proj_wages / $proj_total * 100),
            '',
            sprintf('%.2f%%', $total_trend_wages / $trend_total * 100),
            sprintf('%.2f%%', $total_sales[0] == 0 ? 0 : $total_wages / $total_sales[0] * 100),
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $p_est = 0.32;
        $data[] = array(
            'Other Personnel Cost (est)',
            '',
            number_format($total_proj_wages * $p_est, 0),
            '',
            number_format($total_trend_wages * $p_est, 0),
            number_format($total_wages * $p_est, 0),
            '',
            '',
            number_format(($total_wages - $total_proj_wages) * $p_est, 0),
            number_format($qtd_wages_ou * $p_est, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $p_est += 1.0;
        $data[] = array(
            'Total Personnel Cost (est)',
            '',
            number_format($total_proj_wages * $p_est, 0),
            '',
            number_format($total_trend_wages * $p_est, 0),
            number_format($total_wages * $p_est, 0),
            '',
            '',
            number_format(($total_wages - $total_proj_wages) * $p_est, 0),
            number_format($qtd_wages_ou * $p_est, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $quarter_actual_sph = ($qtd_sales)/($qtd_hours);
        $quarter_proj_sph = ($qtd_plan)/($qtd_proj_hours);
        $data[] = array(
            'Sales per Hour',
            '',
            sprintf('%.2f', $proj_total / $total_proj_hours),
            '',
            sprintf('%.2f', $trend_total / $total_trend_hours),
            number_format($total_hours == 0 ? 0 : $total_sales[0] / $total_hours, 2),
            '',
            '',
            number_format(($total_hours == 0 ? 0 : $total_sales[0]/$total_hours) - ($proj_total/$total_proj_hours), 2),
            number_format($quarter_actual_sph - $quarter_proj_sph, 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $proj_trans = $ly_total_trans * 1.05;
        $qtd_proj_trans = $qtd_trans[1] * 1.05;
        $data[] = array(
            'Transactions',
            number_format($ly_total_trans),
            number_format($proj_trans),
            '',
            '',
            number_format($total_trans),
            sprintf('%.2f%%', $this->percentGrowth($total_trans, $ly_total_trans)),
            '',
            number_format($total_trans - $proj_trans),
            number_format($qtd_trans[0] - $qtd_proj_trans),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Average Basket',
            number_format($total_sales[1] / $ly_total_trans, 2),
            number_format($proj_total / $proj_trans, 2),
            '',
            '',
            number_format($total_trans == 0 ? 0 : $total_sales[0] / $total_trans, 2),
            sprintf('%.2f%%', $this->percentGrowth($total_trans == 0 ? 0 : $total_sales[0]/$total_trans, $total_sales[1]/$ly_total_trans)),
            '',
            number_format(($total_trans == 0 ? 0 : $total_sales[0]/$total_trans) - ($proj_total/$proj_trans), 2),
            number_format(($qtd_sales/$qtd_trans[0]) - ($qtd_plan/$qtd_proj_trans), 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        if (count($colors) > 1) {
            array_shift($colors);
        }

        $quarterP = $dbc->prepare(
            'SELECT
                SUM(s.actualSales) AS actual,
                SUM(s.lastYearSales * (1+s.growthTarget)) AS plan
             FROM ObfSalesCache AS s
                INNER JOIN ObfWeeks AS w ON s.obfWeekID=w.obfWeekID
             WHERE w.obfQuarterID=?
                AND w.endDate <= ?'
        );
        $quarterR = $dbc->execute($quarterP, array($week->obfQuarterID(), date('Y-m-d 00:00:00', $end_ts)));
        $quarterW = $dbc->fetch_row($quarterR);

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('Quarter to Date', '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
        );
        $data[] = array(
            'Sales',
            '',
            number_format($quarterW['plan']),
            '',
            '',
            number_format($qtd_sales, 0),
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Total Wages',
            '',
            '',
            '',
            '',
            number_format($qtd_wages, 0),
            number_format(($qtd_wages) / ($qtd_sales) * 100, 2) . '%',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $plan_personnel = $quarterW['plan'] * 0.2081;
        $bonus = ($qtd_sales * 0.2081) - ($qtd_wages * $p_est);
        if ($bonus < 0) {
            $bonus = 0;
        } else if ($bonus > 35000) {
            $bonus = 35000.00;
        }

        $data[] = array(
            'Total Personnel (est)',
            '',
            number_format($plan_personnel, 0),
            number_format(($plan_personnel) / ($quarterW['plan']) * 100, 2) . '%',
            '',
            number_format($qtd_wages * $p_est, 0),
            number_format(($qtd_wages * $p_est) / ($qtd_sales) * 100, 2) . '%',
            number_format($bonus, 0),
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $stockP = $dbc->prepare('
            SELECT SUM(stockPurchase) AS ttl
            FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'stockpurchases
            WHERE tdate BETWEEN ? AND ?
                AND dept=992
        ');

        $args1 = array(
            date('Y-07-01 00:00:00', $end_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        if (date('n', $end_ts) < 7) {
            $args1[0] = (date('Y', $end_ts) - 1) . '-07-01 00:00:00';
        }

        $last_year = mktime(0, 0, 0, date('n',$end_ts), date('j',$end_ts), date('Y',$end_ts)-1);
        $args2 = array(
            date('Y-07-01 00:00:00', $last_year),
            date('Y-m-d 23:59:59', $last_year),
        );
        if (date('n', $last_year) < 7) {
            $args2[0] = (date('Y', $last_year) - 1) . '-07-01 00:00:00';
        }

        $args3 = array(
            date('Y-m-d 00:00:00', $start_ts),
            date('Y-m-d 23:59:59', $end_ts),
        );
        $args4 = array(
            date('Y-m-d 00:00:00', $start_ly),
            date('Y-m-d 23:59:59', $end_ly),
        );

        $current = $dbc->execute($stockP, $args1);
        $prior = $dbc->execute($stockP, $args2);
        $this_week = $dbc->execute($stockP, $args3);
        $last_week = $dbc->execute($stockP, $args4);
        if ($dbc->num_rows($current) > 0) {
            $current = $dbc->fetch_row($current);
            $current = $current['ttl'] / 20;
        } else {
            $current = 0;
        }
        if ($dbc->num_rows($prior) > 0) {
            $prior = $dbc->fetch_row($prior);
            $prior = $prior['ttl'] / 20;
        } else {
            $prior = 0;
        }
        if ($dbc->num_rows($this_week) > 0) {
            $this_week = $dbc->fetch_row($this_week);
            $this_week = $this_week['ttl'] / 20;
        } else {
            $this_week = 0;
        }
        if ($dbc->num_rows($last_week) > 0) {
            $last_week = $dbc->fetch_row($last_week);
            $last_week = $last_week['ttl'] / 20;
        } else {
            $last_week = 0;
        }

        $data[] = array(
            'Ownership This Week',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($this_week, 0),
            number_format($last_week, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );
        $data[] = array(
            'Ownership This Year',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($current, 0),
            number_format($prior, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= '<div class="form-group form-inline">
            <label>Week Starting</label>: 
            <select class="form-control" name="weekID">';
        $model = new ObfWeeksModel($dbc);
        foreach ($model->find('startDate', true) as $week) {
            $ret .= sprintf('<option value="%d">%s</option>',
                            $week->obfWeekID(),
                            date('M, d Y', strtotime($week->startDate()))
                            . ' - ' . date('M, d Y', strtotime($week->endDate()))
            );
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" class="btn btn-default">Get Report</button>';
        $ret .= '</div>';
        $ret .= '</form>';
        $ret .= '<p><button class="btn btn-default"
                onclick="location=\'ObfIndexPage.php\';return false;">Home</button>
                </p>';

        return $ret;
    }

    private function percentGrowth($a, $b)
    {
        if ($b == 0) {
            return 0.0;
        } else {
            return 100 * ($a - $b) / ((float)$b);
        }
    }

    private function leastSquare($points)
    {
        $avg_x = 0.0;
        $avg_y = 0.0;
        foreach ($points as $p) {
            $avg_x += $p[0];
            $avg_y += $p[1];
        }
        $avg_x /= (float)count($points);
        $avg_y /= (float)count($points);

        $numerator = 0.0;
        $denominator = 0.0;
        foreach ($points as $p) {
            $numerator += (($p[0] - $avg_x) * ($p[1] - $avg_y));
            $denominator += (($p[0] - $avg_x) * ($p[0] - $avg_x));
        }
        $slope = $numerator / $denominator;
        $y_intercept = $avg_y - ($slope * $avg_x);

        return array(
            'slope' => $slope,
            'y_intercept' => $y_intercept,
        );
    }

    private function expFit($points)
    {
        $a_num = 
            (array_reduce($points, function($c,$i){ return $c + (pow($i[0],2)*$i[1]); })
            * array_reduce($points, function($c,$i){ return $c + ($i[1] * log($i[1])); })) 
            -
            (array_reduce($points, function($c,$i){ return $c + ($i[0]*$i[1]); })
            * array_reduce($points, function($c,$i){ return $c + ($i[0] * $i[1] * log($i[1])); })); 

        $a_denom = 
            (array_reduce($points, function($c,$i) { return $c + $i[1]; })
            * array_reduce($points, function($c,$i) { return $c + (pow($i[0],2)*$i[1]); }))
            -
            pow(
                array_reduce($points, function($c,$i) { return $c + $i[0]*$i[1]; }),
                2);

        $a = $a_num / $a_denom;

        $b_num = 
            (array_reduce($points, function($c,$i){ return $c + $i[1]; })
            * array_reduce($points, function($c,$i){ return $c + ($i[0] * $i[1] * log($i[1])); })) 
            -
            (array_reduce($points, function($c,$i){ return $c + ($i[0]*$i[1]); })
            * array_reduce($points, function($c,$i){ return $c + ($i[1] * log($i[1])); })); 
        $b_denom = $a_denom;

        $b = $b_num / $b_denom;

        return array('a' => $a, 'b' => $b);
    }
}

FannieDispatch::conditionalExec();
