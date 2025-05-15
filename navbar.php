<?php


class navbar {
  var $field, $table, $query_string;
  var $sql_xtra, $sql_xtra2;
  var $month_select, $year_select;

  function get_filter($table, $op = "=") {
    $filter = "";
    if($this->month_select > 0) {
      $filter .= " and MONTH($table) $op $this->month_select ";
    } else if ($this->month_select ==  -1) {
      $filter .= " and MONTH($table) ";
    }

    if($this->year_select > 0) {
      $filter .= " and YEAR($table) $op $this->year_select ";
    } else if ($this->year_select ==  -1) {
      $filter .= " and YEAR($table) ";
    }
    return $filter;
  }
  
  function draw_navbar($start_year = 0, $all_opt = 1, $num_years = 0, $selName = "selDate") {
    $str = '
      <script language="JavaScript">
        function report_filter() {
          document.getElementById("hdnReportFilter").value=1
          document.frmEdit.submit()
        }
      </script>
      <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
      <div class="form-wrapper" style="margin-bottom: 20px;">
      <div class="form-header">Filter by Month/Year</div>
      <div  style="padding: 10px;">
      '.$this->month_year($start_year, $all_opt, $num_years, $selName).'
      <input onClick="report_filter()" type="button" value="Go" /> 
      </div>
      </div>
      ';
    $hdnReportFilter = $_REQUEST['hdnReportFilter'];
    if($hdnReportFilter) {
      $this->month_select = $_REQUEST["selDateMonth"];
      $this->year_select = ($start_year ? $_REQUEST["selDateYear"] : 0);
    //  echo "<h3>$month_select</h3><h3>$year_select</h3>";
    } else {
      $this->month_select = date('m');
      $this->year_select = ($start_year ? date('Y') : 0);
      $str .= '
      <script language="JavaScript">
        change_'.$selName.'(0)
      </script>
      ';
    }
    return $str;
  }
  
  function month_year($start_year = 0, $all_opt = 1, $num_years = 0, $selName = "selDate") {
    //var $show_last_month = 1;
    $str .= '
      <script language="JavaScript">
        function change_'.$selName.'(go_filter) {
          var d = new Date();
          var m = d.getMonth();
          var y = d.getFullYear();
          document.getElementById("'.$selName.'Month").selectedIndex = m+1
          ' . ($start_year ? 'document.getElementById("'.$selName.'Year").value = y' : '') . '
          if(go_filter) report_filter()
        }
        function change_all_'.$selName.'() {
          document.getElementById("'.$selName.'Month").selectedIndex = 0
          ' . ($start_year ? 'document.getElementById("'.$selName.'Year").selectedIndex = 0' : '') . '
          report_filter()
        }
        function last_month_'.$selName.'() {
          if(document.getElementById("'.$selName.'Month").selectedIndex == 1) {
            document.getElementById("'.$selName.'Month").selectedIndex = 12
            ' . ($start_year ? 'document.getElementById("'.$selName.'Year").selectedIndex--' : '') . '
          } else {
            document.getElementById("'.$selName.'Month").selectedIndex--
          }
          report_filter()
        }
        function plus'.$selName.'() {
          idx = document.getElementById("'.$selName.'Month").selectedIndex
          idx++
          if(idx > 12) idx = 1
          document.getElementById("'.$selName.'Month").selectedIndex = idx
        }
        function minus'.$selName.'() {
          idx = document.getElementById("'.$selName.'Month").selectedIndex
          idx--
          if(idx < 1) idx = 12
          document.getElementById("'.$selName.'Month").selectedIndex = idx
        }
      </script>
    ';
    $month_select = $_REQUEST[$selName."Month"];
    //echo "MS: " . $month_select;
    $year_select = $_REQUEST[$selName."Year"];
    $months = array("", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
    $str .= 'Month: ';
    $str .= '<input onClick="minus'.$selName.'()" type="button" value="-" />';
    $str .= '<select name="'.$selName.'Month" id="'.$selName.'Month">';
    $str .= "<option value=\"-1\">ALL</option>";
    for($x = 1; $x < count($months); $x++) {
      $xtra = ($month_select == $x ? "selected" : "");
      $str .= "<option $xtra value=\"$x\">".$months[$x]."</option>";
    }
    $str .= '
      </select>
    ';
    $str .= '<input onClick="plus'.$selName.'()" type="button" value="+" />';
    if($start_year) {
      if(!$num_years) {
        $num_years = date("Y") - $start_year;
      }
      $str .= 'Year: <select name="'.$selName.'Year" id="'.$selName.'Year">';
      $str .= "<option value=\"-1\">ALL</option>";
      for($x = $start_year; $x <= ($start_year+$num_years); $x++) {
        if($year_select == $x) {
          $xtra = "selected";
        } else {
          $xtra = "";
        }
        $str .= "<option $xtra value=\"$x\">$x</option>";
      }
      $str .= '
        </select>
      ';
    }
    $str .= '<input onClick="change_'.$selName.'(1)" type="button" value="Now" />';
    if($all_opt) $str .= '<input onClick="change_all_'.$selName.'()" type="button" value="ALL" />';
    $str .= '<input onClick="last_month_'.$selName.'()" type="button" value="Back 1 Month" />';
    return $str;
  }
  
  function day_month($href_xtra = "") {
    $months = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
    $year = $_REQUEST['nav_year'];
    $month = $_REQUEST['nav_month'];
    $status = $_REQUEST['status'];
    if($status) $href_xtra .= "&status=$status";
    if($year != "ALL") {
      if(!$year) $year = date("Y", strtotime("-1 months"));
      if(!$month) $month = date("m", strtotime("-1 months"));
    }
    $title .= " for $month/$year";
    if($year && $month) {
      $this->sql_xtra = " and MONTH($this->field) = $month and YEAR($this->field) = $year ";
      $this->sql_xtra2 = "  and $this->field < '$year-$month-01' ";
    }
    $finished = 0;

/*    $str .= '
             <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><a href="'.$this->query_string.'&year=ALL'.$href_xtra.'">ALL</a></div>
    ';
		*/
    for($y = 2014; $y <= date('Y'); $y++) {
      for($m = 1; $m <= 12 && !$finished; $m++) {
        if($y == date("Y", strtotime("-1 months")) && $m == date("m", strtotime("-1 months"))) $finished = 1;
        if($y == $year && $m == $month) {
          $str .= '
          <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><b>['.$months[$m-1].'/'.$y.']</b></div>
          ';
        } else {
		  $str .= '
          <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><a href="'.strtok($_SERVER["REQUEST_URI"],'?').'?nav_month='.$m.'&nav_year='.$y.$href_xtra.'">'.$months[$m-1].'/'.$y.'</a></div>
          ';
        }
      }
    }
    $str .= '<div class="cl"></div>';
    return $str;
  }

  function status_bar() {
    $year = $_REQUEST['year']; 
    $month = $_REQUEST['month'];//	echo "<h3>status: $status</h3>";
    $status = $_REQUEST['status'];
    if($month) $href_xtra = "&month=$month";
    if($year) $href_xtra .= "&year=$year";
    include('db_connect.php');
    $status = $_REQUEST['status'];
    if(!$status) $status = 'PAID';
    if($status && $status != "ALL") {
      $this->sql_xtra = " and $this->field = '$status' ";
    }
    $finished = 0;
    $str .= '
             <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><a href="'.$this->query_string.'&status=ALL'.$href_xtra.'">ALL</a></div>
    ';
    $sql = "select item_name from sales_statuses;";
	
	$result = $dbi->query($sql);
    while($myrow = $result->fetch_assoc()) {
      if($myrow['item_name'] == $status) {
        $str .= '
        <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><b>['.$status.']</b></div>
        ';
      } else {
        $str .= '
        <div class="fl" style="padding: 4px; border: 1px solid #CCCCCC;"><a href="'.$this->query_string.'&status='.$myrow['item_name'].$href_xtra.'">'.$myrow['item_name'].'</a></div>
        ';
      }
    }
    $str .= '<div class="cl"></div>';
    return $str;
  }
  
  
}


?>