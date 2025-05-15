<?php

class TaskController extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
    $div_id = $_COOKIE["TaskDivisionId"];
    $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
    if($this->division_id) setcookie("TaskDivisionId", $this->division_id, 2147483647);
    $this->nums = array("", "First", "Second", "Third", "Fourth");
    
    //$this->first_monday = date("Y-m-d", strtotime("first monday 2020-01"));
    $this->first_monday = date("Y-m-d", strtotime("first monday " . date("Y") . "-" . date("m")));
    
    $this->current_week_of_month = $this->get_sql_result("select FLOOR((date(now()) - date('{$this->first_monday}')) / 7) + 1 AS `result`");
    //$this->current_week_of_month = 3;
    
    $this->styles .= '
      <style>
        .task_pending {
          border: 1px solid #DDDDDD;
          background-color: #FFDCA9;
        }
        .task_pending:hover {          background-color: #FFE4C1;        }
        .task_complete {
          border: 1px solid #DDDDDD;
          background-color: #B4DCA2;
        }

        .task_complete a a {          color: black !important;        }
        .task_complete:hover:hover {          background-color: #D3EBC8;        }
        .task_complete .list_a:hover .list_a:hover, .task_late .list_a:hover, .task_in_progress .list_a:hover {
          color: white !important;
        }
        .task_late {
          border: 1px solid #DDDDDD;
          background-color: #E7BDC1;
        }
        .task_late:hover {          background-color: #FFD6DA;        }
        
        table.grid th {          text-align: center !important;        }
        .cell_data {
          max-height: 30px !important;
          height: 30px;
          overflow: hidden;
        }
        .week_cell {
          text-align: center;
          display: table-cell;
          width: 26px;
        }
        .month_cell {
          text-align: center;
          display: table-cell;
          width: 116px;
          font-weight: bold;
        }
        .left_cell { border: 1px solid #CCCCCC; }
        .right_cell {
          border-right: 1px solid #CCCCCC;
          border-top: 1px solid #CCCCCC;
          border-bottom: 1px solid #CCCCCC;
        }
        .c1 { background-color: #F9F9F9; }
        .c2 { background-color: #EEEEEE; }
        .cc { background-color: #F0FFF5; }
        .pt { cursor: pointer; }
      </style>
      ';
    
  }
  
// ************************************************************************************************************************************************************************
  
  function CurrentPeriodicals() {
    
    $division_id = $this->division_id;
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');    
    
    //$division_id = 2100;
    
    $week_select = (isset($_GET['week_select']) ? $_GET['week_select'] : null);
    $task_schedule_id = (isset($_GET['task_schedule_id']) ? $_GET['task_schedule_id'] : null);
    
    if($task_schedule_id) {
      $stat = $this->get_sql_result("select status_id as `result` from task_schedules where id = $task_schedule_id");
      $sql = "update task_schedules set status_id = " . ($stat == 10 ? 40 : 10) . " where id = $task_schedule_id";
      echo "task_" . ($stat == 10 ? "complete" : "pending") . " pt";
      $this->dbi->query($sql);
      exit;
    }
    
    
    $is_admin = ($this->f3->get('is_admin') ? 1 : 0);
    $week_filter = (isset($_GET['week_filter']) ? $_GET['week_filter'] : null);
    $month_filter = (isset($_GET['month_filter']) ? $_GET['month_filter'] : null);
    
    $current_week_of_month = $this->current_week_of_month;

    $this->current_week_of_month = $this->get_sql_result("select FLOOR((DAYOFMONTH(now()) - '{$this->first_monday}' / 7)) + 1 AS `result`");

    $first_monday = $this->first_monday;
    
    $sid = (isset($_GET['sid']) ? $_GET['sid'] : 169);  //Bonnyrigg Default

    $current_year = date("Y");
    //$current_year = 2020;
    $current_month = date("m");
    //$current_month = 5;
    //test sql update `task_schedules` set date_completed = date, status_id = 40 where date < '2020-04-11'

    $this_monday = date("Y-m-d", strtotime($this->nums[$current_week_of_month] . " monday " . "$current_year-$current_month"));
    
    $monday_select = ($week_select ? date("Y-m-d", strtotime("$this_monday -$week_select week")) : $this_monday);

    $week_starting = date("d-M-Y", strtotime("$monday_select"));
    
    $first_monday_year = date("d", strtotime("first monday $current_year-01")) - 1;
    //return $first_monday_year;
    
    $sql = "select task_schedules.id as `idin`, task_schedules.status_id,
    companies.item_name as `division`,
    CONCAT(users.name, ' ', users.surname) as `site`,
    lookup_fields.item_name as `frequency`, 
    tasks.description from task_schedules
    left join tasks on tasks.id = task_schedules.task_id
    left join lookup_fields on lookup_fields.id = tasks.frequency_id
    left join companies on companies.id = tasks.division_id
    left join users on users.id = tasks.site_id
    where tasks.site_id = $sid
    and task_schedules.date = '$monday_select'
    " . ($division_id != 'ALL' ? " and division_id = $division_id " : "")
    . "order by lookup_fields.sort_order;";
    
    
    //return $this->ta($sql);
    if($is_admin) $str .= $this->jump_to();
    
    $str .= $this->styles . '
    <script>
      function update_item(task_schedule_id) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Tasking/CurrentPeriodicals",
              data:{task_schedule_id: task_schedule_id} ,
              success:function(msg) {
                document.getElementById("itm" + task_schedule_id).className = msg
              }
        } );
      }
    </script>
    ';
    $nav_str = $this->division_nav($division_id, 'Tasking/CurrentPeriodicals', 1, 0, 1, "&sid=$sid");
    
    if($result = $this->dbi->query($sql)) {
      $sqls = "";
      while($myrow = $result->fetch_assoc()) {
        if(!$division) {
          $division = $myrow['division'];
          $site = $myrow['site'];
          
          $str .= "<div class=\"fl\">$nav_str</div>";
          $str .= "<h5 class=\"fl\" style=\"padding-left: 20px;\">$site - Periodicals - Week Starting $week_starting</h5>";
          $str .= '<div class="fl" style="padding-left: 20px;">';
          for($x = 0; $x <= 4; $x++) {
            $str .= '<a uk-tooltip="'.($x ? ($x == 1 ? "Last Week" : "$x Weeks Ago") : "This Week").'" class="division_nav_item'.($week_select == $x ? " division_nav_selected" : "").'" href="'.$this->f3->get('main_folder').'Tasking/CurrentPeriodicals?week_select='.$x.($show_min ? "&show_min=1" : "")."&sid=$sid".'">'.($x ? $x : "This Week").'</a>';
          }
          
          $str .= ' &nbsp;<a uk-tooltip="View Current Month" class="division_nav_item" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals?month_filter='.$current_month.($show_min ? "&show_min=1" : "")."&sid=$sid".'">This Month</a>';
          $str .= ' &nbsp;<a uk-tooltip="View Whole Year" class="division_nav_item" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals'."?sid=$sid".($show_min ? "&show_min=1" : "").'">ALL</a>';
          
          $str .= '</div>';
//          $str .= '<div class="fr"><b>TEST DATE</b> '.date('l, d-M-Y', strtotime('2020-05-20'));
          $str .= '<div class="fr">'.date('l, d-M-Y');
          $str .= '</div><div class="cl"></div>Click a Pending Task (in Orange) to Mark as Completed';
          $str .= '<table class="table">';
        }
        $task_schedule_id = $myrow['idin'];
        $status_id = $myrow['status_id'];
        $frequency = $myrow['frequency'];
        $description = $myrow['description'];
        $task_status = ($status_id == 10 ? "pending" : "complete");
        $cls = " task_$task_status";
        if($is_admin && strtotime($test_date) <= strtotime($this_monday)) {
          $xtra = " onClick=\"update_item($task_schedule_id);\" ";
          $cls .= " pt";
        }  else {
          $xtra = "";
        }
        $str .= "<tr><td id=\"itm$task_schedule_id\" $xtra class=\"$cls\">$frequency - $description</td></tr>";
      }
      $str .= "</table>";
    }
    if(!$division) {
      $str .= $nav_str;
    }
    return $str;
  }


  function jump_to() {
    $itm = new input_item;
    $itm->hide_filter = 1;
    $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $url2 = "";
    $url2 .= ($month_filter ? "&month_filter=$month_filter" : "");
    $url2 .= ($week_filter ? "&week_filter=$week_filter" : "");
    $str .= "
    <script>
    function select_page() {
      t = document.getElementById('hdncmbPageSelect').value;
      window.location = '$url?sid=' + t + '$url2';
    }
    </script>
    ";
    $site_sql = $this->user_dropdown(384,104,0,0,0,1);
    $site_sql = str_replace("order by", "and users.id in (select distinct(site_id) from tasks where year(date) = year(now())) order by", $site_sql);
    //return $site_sql;
    $str .= $itm->cmb("cmbPageSelect", "", "placeholder=\"Jump to Site\" class=\"uk-search-input search-field\" style=\"width: 150px;\" onChange=\"select_page()\"  ", "", $this->dbi, $site_sql, "");
    return $str;
  }


// ************************************************************************************************************************************************************************


  function ViewPeriodicals() {
    $division_id = $this->division_id;
    //$division_id = 2100;
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');    
    $is_admin = ($this->f3->get('is_admin') ? 1 : 0);
    $week_filter = (isset($_GET['week_filter']) ? $_GET['week_filter'] : null);
    $month_filter = (isset($_GET['month_filter']) ? $_GET['month_filter'] : null);
    $generate_schedule = (isset($_GET['generate_schedule']) ? $_GET['generate_schedule'] : null);
    $current_week_of_month = $this->current_week_of_month;
    $first_monday = $this->first_monday;
    $sid = (isset($_GET['sid']) ? $_GET['sid'] : 169);  //Bonnyrigg Default

    $current_year = date("Y");
    //$current_year = 2020;
    $current_month = date("m");
    //$current_month = 5;
    //test sql update `task_schedules` set date_completed = date, status_id = 40 where date < '2020-04-11'
    
    if($is_admin) $str .= $this->jump_to();

    $this_monday = date("Y-m-d", strtotime($this->nums[$current_week_of_month] . " monday " . "$current_year-$current_month"));
    
    $first_monday_year = date("d", strtotime("first monday $current_year-01")) - 1;
    //return $first_monday_year;

    $task_id = (isset($_GET['task_id']) ? $_GET['task_id'] : null);
    $wk = (isset($_GET['wk']) ? $_GET['wk'] : null);
    $dt = (isset($_GET['dt']) ? $_GET['dt'] : null);
    if($task_id && $wk) {
      $stat = $this->get_sql_result("select status_id as `result` from task_schedules where task_id = $task_id and date = '$dt'");
      $sql = "update task_schedules set status_id = " . ($stat == 10 ? 40 : 10) . " where task_id = $task_id and date = '$dt'";
      echo "task_" . ($stat == 10 ? "complete" : "pending");
      $this->dbi->query($sql);
      exit;
    }


    $sql = "select tasks.id as `idin`,
    companies.item_name as `division`,
    CONCAT(users.name, ' ', users.surname) as `site`,
    lookup_fields.item_name as `frequency`, 
    tasks.date as `start_date`, 
    DATE_FORMAT(tasks.date, '%m') as `month_num`, 
    DATE_FORMAT(tasks.date, '%W') as `day_of_week`, 
    FLOOR((DAYOFMONTH(tasks.date) - (if(DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))) > 2, 10, 3) - DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))))) / 7) + 1 AS `week_of_month`,
    tasks.description from tasks
    left join lookup_fields on lookup_fields.id = tasks.frequency_id
    left join companies on companies.id = tasks.division_id
    left join users on users.id = tasks.site_id
    where tasks.site_id = $sid
    " . ($division_id != 'ALL' ? " and division_id = $division_id " : "") .
    ($week_filter ? " and ((FLOOR((DAYOFMONTH(tasks.date) - (if(DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))) > 2, 10, 3) - DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))))) / 7) + 1) = $week_filter OR lookup_fields.item_name = 'Weekly' )" : "") .
    " order by lookup_fields.sort_order, FLOOR((DAYOFMONTH(tasks.date) - (if(DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))) > 2, 10, 3) - DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))))) / 7) + 1
    ;";
    //return $this->ta($sql);
    
    if(!$generate_schedule) {
      $str .= $this->styles . '
      <script>
        function update_item(task_id, mnth, wk, dt) {
          $.ajax({
            type:"get",
                url:"'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals",
                data:{task_id: task_id, wk: wk, dt: dt} ,
                success:function(msg) {
                  nme = task_id + (mnth ? "-" + mnth : "") + "-" + wk;
                  document.getElementById(nme).className = msg + (mnth ? "" : " week_cell right_cell ") + " pt"
                }
          } );
        }
      </script>
      ';
      
      
    }

    // Add this code to your PHP function to get unique site_id values
$siteIdOptions = array();

$sqlSiteIds = "SELECT DISTINCT site_id FROM tasks";
$resultSiteIds = $this->dbi->query($sqlSiteIds);

if ($resultSiteIds) {
    while ($row = $resultSiteIds->fetch_assoc()) {
        $siteId = $row['site_id'];
        $siteIdOptions[$siteId] = $siteId;
    }
}

    
    if($result = $this->dbi->query($sql)) {
      $sqls = "";
      while($myrow = $result->fetch_assoc()) {
        if(!$division) {
          $division = $myrow['division'];
          $site = $myrow['site'];
          if(!$generate_schedule) {
            $str .= "<div class=\"fl\">" . $this->division_nav($division_id, 'Tasking/ViewPeriodicals', 1, 0, 1, "&sid=$sid") . "</div>";
            $str .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />';
            $str .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>';
            $str .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />';
            $str .= '<h5 class="fl"> &nbsp;' . "$site - Periodicals</h5>";
            $str .= '<div class="fl" style="padding-left: 20px;">';
            $str .= '<div>
            <label for="siteFilter">Filter by Site:</label>
            <select id="siteFilter" class="js-select2" name="siteFilter">
                <option value="">All Sites</option>';
                
        $servername = "localhost";
        $username = "tnsmwdztaz";
        $password = "vzZ3mFxE2E";
        $dbname = "tnsmwdztaz";
        
        // Create connection
        $connection = mysqli_connect($servername, $username, $password, $dbname);
        
        // Check connection
        if (!$connection) {
            die("Connection failed: " . mysqli_connect_error());
        }
        
        // Fetching site IDs and names from the 'users' table
        $query = "SELECT id, name FROM users 
            WHERE user_maintype = 2 
            AND (facilities_manager_name = '' 
            OR facilities_manager_name2 = '' 
            OR facilities_manager_name3 = '')";
        
        $result = mysqli_query($connection, $query);
        
        if (!$result) {
            die("Query failed: " . mysqli_error($connection));
        }
        
        while ($row = mysqli_fetch_assoc($result)) {
            $str .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
        
        $str .= '</select>
            <script>
            $(document).ready(function() {
                $(".js-select2").select2({
                    // Additional configurations if needed
                });
        
                $("#siteFilter").on("change", function() {
                    var siteFilter = $(this).val();
                    var url = "/app/controllers/getSpecificPeriodical.php";
                    // Redirect to the filtered view with the selected site_id
                    if (siteFilter) {
                        // Navigate to the URL with the selected site_id
                        window.location.href = url + "?site_id=" + siteFilter;
                    } else {
                        // If no filter selected, navigate to the base URL
                        window.location.href = url;
                    }
                });
            });
            </script>
        </div>';
        

            $str .= '<a uk-tooltip="View Whole Year" class="division_nav_item'.(!$week_filter ? " division_nav_selected" : "").'" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals'."?sid=$sid".($show_min ? "&show_min=1" : "").'">ALL</a>';
            for($x = 1; $x <= 4; $x++) {
              $str .= '<a uk-tooltip="View Week '.$x.($current_week_of_month == $x ? "<br />(This Week)" : "").'" class="division_nav_item'.($week_filter == $x ? " division_nav_selected" : "").'" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals?week_filter='.$x.($show_min ? "&show_min=1" : "")."&sid=$sid".'">'.$x.($current_week_of_month == $x ? "*" : "").'</a>';
            }
            //$str .= " &nbsp;" . $this->week_nav;

            $str .= ' &nbsp;<a uk-tooltip="View This Week" class="division_nav_item" href="'.$this->f3->get('main_folder').'Tasking/CurrentPeriodicals'."?sid=$sid".($show_min ? "&show_min=1" : "").'">This Week</a>';
            if($month_filter != $current_month) $str .= ' &nbsp;<a uk-tooltip="View Current Month" class="division_nav_item" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals?month_filter='.$current_month.($show_min ? "&show_min=1" : "")."&sid=$sid".'">This Month</a>';

            $str .= '</div>';
            //, strtotime('2020-05-20')
            $str .= '<div class="fr">'.date('l, d-M-Y');
            $str .= ($this->get_sql_result("select id as `result` from task_schedules where year(task_schedules.date) = $current_year and task_id in (select id from tasks where site_id = $sid)") ? "" : ' &nbsp;&nbsp;<a class="list_a" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals?generate_schedule=1'.($show_min ? "&show_min=1" : "")."&sid=$sid".'">Generate Schedule</a>');
            $str .= '</div><div class="cl"></div><table class="table">';
            $str .= '<tr><th><div class="fl">Tasks (Below)</div><div class="fr">Month &gt;</div><div class="cl"></div></th>';
            if($month_filter) {
              $startx = $month_filter; $finishx = $month_filter;
            } else {
              $startx = 1; $finishx = 12;
            }
              
            for($x = $startx; $x <= $finishx; $x++) {
              $bg = ($x % 2 ? "white" : "#EEEEEE");
              $str .= '<th colspan="4" style="background-color: ' . $bg . ';">' . ($month_filter ? '' : '<a href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals?month_filter='.$x.($show_min ? "&show_min=1" : "")."&sid=$sid".'">') . date("M", strtotime("$current_year-$x")) . '</a></th>';
             }
            $str .= "</tr>";
          }
        }
        $task_id = $myrow['idin'];
        $frequency = $myrow['frequency'];
        $start_date = $myrow['start_date'];
        $week_of_month = $myrow['week_of_month'];
        $day_of_week = strtolower($myrow['day_of_week']);
        $description = $myrow['description'];
        $which_week = $this->nums[$week_of_month];
        $month_num = $myrow['month_num'];
        $month = Date("M", strtotime($start_date));
        $year = Date("Y", strtotime($start_date));
        $start_month = $month_num;
        
        $interval = ($frequency == "Monthly" ? 1 : ($frequency == "Quarterly" ? 3 : ($frequency == 'Three Times a Year' ? 4 : ($frequency == 'Yearly' ? 12 : 6))));
        if($generate_schedule) {
          if($frequency == 'Weekly') {
            $this->dbi->query("delete from task_schedules where task_id = $task_id");
            for($x = 0; $x < 52; $x++) {
              $t = $first_monday_year + ($x * 7);
              $t_date = date("Y-m-d", strtotime("January 1st $current_year + $t days"));
              $sqls .= "insert into task_schedules (task_id, status_id, date) values ($task_id, 10, '$t_date');";
            }
          } else {
            for($x = 1; $x <= 12; $x++) {
              $is_selected = 0;
              for($y = $start_month; $y <= 12; $y += $interval) {
                if($y == $x) $is_selected = 1;
              }
              for($y = 1; $y <= 4; $y++) {
                if($y == $week_of_month && $is_selected) {
                  $t_date = date("Y-m-d", strtotime($this->nums[$y] . " monday " . "$current_year-$x"));
                  $this->dbi->query("delete from task_schedules where task_id = $task_id");
                  $sqls .= "insert into task_schedules (task_id, status_id, date) values ($task_id, 10, '$t_date');";
                }
              }
            }
          }
        } else {
          if($frequency == 'Weekly') {
            $str .= "<tr><td>$frequency: $description</td><td colspan=\"48\">";
            if($month_filter) {
              $str .= 'ALL WEEKS';
            } else {
              for($x = 0; $x < 52; $x++) {
                $t = $first_monday_year + ($x * 7);
                $test_date = date('Y-m-d', strtotime("January 1st $current_year + $t days"));
                $t = date('d', strtotime("January 1st $current_year + $t days"));
                $sql_test = "select if(status_id = 40, 'complete', if(status_id = 10 and date(date) >= '$this_monday', 'pending', 'late')) as `result`, status_id, date(date) from task_schedules where task_id = $task_id and date(date) = '$test_date'\r\n";
                //if($x == 23) return $t;
                $task_status = $this->get_sql_result($sql_test);
                $cls = " task_$task_status";
                if($is_admin && strtotime($test_date) <= strtotime($this_monday)) {
                  $xtra = " onClick=\"update_item($task_id, 0, $x, '$test_date');\" ";
                  $cls .= " pt";
                }  else {
                  $xtra = "";
                }

                $str .= '<div id="' . "$task_id-$x" . '"'. $xtra . ' class="week_cell '. (!$x ? "left_cell" : "right_cell") . $cls . '">'.$t.'</div>';
              }
            }
            $str .= "</td></tr>";
          } else {
            $str .= "<tr><td>$frequency Wk $week_of_month: $description</td>";

            for($x = $startx; $x <= $finishx; $x++) {
              $is_selected = 0;
              for($y = $start_month; $y <= 12; $y += $interval) {
                if($y == $x) $is_selected = 1;
              }
              $cls2 = ($x % 2 ? "c1" : "c2");
              if($x == $current_month) {
                $cls2 = "cc";
              }
              
              for($y = 1; $y <= 4; $y++) {
                $test_date = date("Y-m-d", strtotime($this->nums[$y] . " monday " . "$current_year-$x"));
                if($y == $week_of_month && $is_selected) {
                  //$sql_test .= ($sql_test ? "UNION ALL\r\n" : "");
                  $sql_test = "select if(status_id = 40, 'complete', if(status_id = 10 and date(date) >= '$this_monday', 'pending', 'late')) as `result`, status_id, date(date) from task_schedules where task_id = $task_id and date(date) = '$test_date'\r\n";
                  $task_status = $this->get_sql_result($sql_test);
                  $cls = "task_$task_status";
                } else {
                  $cls = $cls2;
                }
                if($is_admin && $y == $week_of_month && $is_selected && strtotime($test_date) <= strtotime($this_monday)) {
                  $xtra = " onClick=\"update_item($task_id, $x, $y, '$test_date');\" ";
                  $cls .= " pt";
                }  else {
                  $xtra = "";
                }
                
                $str .= "<td $xtra id=\"$task_id-$x-$y\" class=\"$cls\">" . date("d", strtotime($test_date)) . "</td>\n";
              }
            }
            $str .= "</tr>";
          }
        }
      }
//return $this->ta($sql_test);
      if($sqls) {
        
        //$str .= $this->ta($sqls);
        $this->dbi->multi_query($sqls);
        $str .= '<h3>Schedule Generated...</h3><a class="list_a" href="'.$this->f3->get('main_folder').'Tasking/ViewPeriodicals'.($show_min ? "?show_min=1" : "")."&sid=$sid".'">Back to Periodicals</a>';
      }
    }
    
    if(!$division) {
      $str .= '<h3 class="fl">Periodicals</h3>';
      $str .= '<div class="fr" style="padding-right: 20px;">' . $this->division_nav($division_id, 'Tasking/ViewPeriodicals', 1, 0, 1, "&sid=$sid") . '</div><div class="cl"></div>';
    }
    $str .= ($generate_schedule ? "" : "</table>");
    return $str;
  }

  function EditPeriodicals() {
    $division_id = $this->division_id;
    $is_admin = ($this->f3->get('is_admin') ? 1 : 0);

    $str = $this->division_nav($division_id, 'Tasking/EditPeriodicals', 1, 0, 1, "&sid=$sid");

    $this->list_obj->sql = "select tasks.id as `idin`,
    ".($division_id == 'ALL' ? " companies.item_name as `Division`, " : "")."
    ".($is_admin ? " CONCAT(users.name, ' ', users.surname) as `Site`, " : "")."
    lookup_fields.item_name as `Frequency`, 
    tasks.date as `Start Date`, 
    FLOOR((DAYOFMONTH(tasks.date) - (if(DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))) > 2, 10, 3) - DAYOFWEEK(CONCAT(DATE_FORMAT(tasks.date, '%Y-%m-01'))))) / 7) + 1 AS `Week`,
    tasks.description as `Description`, 'Edit' as `*`, 'Delete' as `!` from tasks
    left join lookup_fields on lookup_fields.id = tasks.frequency_id
    left join companies on companies.id = tasks.division_id
    left join users on users.id = tasks.site_id
    ".($division_id != 'ALL' ? " where division_id = $division_id " : "")."
    order by lookup_fields.sort_order, CONCAT(users.name, ' ', users.surname), tasks.date
    ;";
    $this->editor_obj->xtra_id_name = "division_id";
    $this->editor_obj->xtra_id = $division_id;


//    if(!$is_admin) {
  //    $sid = $this->get_sid();
     // $this->editor_obj->custom_field = "sid";
    //  $this->editor_obj->custom_value = $sid;
  //  }
    
    $this->editor_obj->table = "tasks";
    $style_large = 'style="width: 650px;"';
    $style = 'style="width: 125px;"';
    $this->editor_obj->form_attributes = array(
      array("calDate", "selFrequency", "txtDescription"),
      array("Start Date", "Frequency", "Description"),
      array("date", "frequency_id", "description"),
      array("", $this->get_lookup('schedule_frequency'), ""),
      array($style, $style, $style_large),
      array("n", "n", "n")
    );
    if($is_admin) {
      $this->editor_obj->form_attributes[0][3] = "cmbSite";
      $this->editor_obj->form_attributes[1][3] = "Site";
      $this->editor_obj->form_attributes[2][3] = "site_id";
      $this->editor_obj->form_attributes[3][3] = $this->user_dropdown(384);
      $this->editor_obj->form_attributes[4][3] = "";
      $this->editor_obj->form_attributes[5][3] = "c";
    }
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset"),
      array("cmdAdd", "cmdSave", "cmdReset"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
    );
    $this->editor_obj->form_template = '
    <div class="form-wrapper" style="">
      <div class="form-header" style="">Periodicals</div>
      <div class="form-content">
        '.($is_admin ? '<div class="fl"><nobr>tcmbSite</nobr><br />cmbSite</div>' : '').'
        <div class="fl"><nobr>tselFrequency</nobr><br />selFrequency</div>
        <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
        <div class="fl"><nobr>ttxtDescription</nobr><br />txtDescription</div>
        <div class="cl"></div>
        '.$this->editor_obj->button_list().'
      </div>
      <style>
      .upload-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .upload-period {
        flex: 1;
        padding: 10px;
        border: 1px solid #ccc;
        margin: 5px;
    }    
      </style>
      <div class="upload-container">
      <div class="upload-period">
          <h5>Upload a CSV file with the periodicals filled.</h5>
          <form id="uploadForm" enctype="multipart/form-data">
              <input type="file" id="csv_file" accept=".csv" />
              <button type="button" id="uploadButton">Upload CSV</button>
          </form>
          <div id="result" style="display: none;"></div>
      </div>
  
      <div class="upload-period">
          <h5>Upload a CSV file for periodical scheduling.</h5>
          <form id="uploadFormScheduling" enctype="multipart/form-data">
              <input type="file" id="csv_file_scheduling" accept=".csv" />
              <button type="button" id="uploadButtonScheduling">Upload CSV</button>
          </form>
          <div id="resultScheduling" style="display: none;"></div>
      </div>
  </div>  
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
      document.getElementById("uploadButton").addEventListener("click", function () {
          var fileInput = document.getElementById("csv_file");
          var file = fileInput.files[0];
  
          if (file) {
              var formData = new FormData();
              formData.append("csv_file", file);
  
              var xhr = new XMLHttpRequest();
              xhr.open("POST", "/periodicalUpload.php", true);
  
              xhr.onreadystatechange = function () {
                  if (xhr.readyState === 4) {
                      if (xhr.status === 200) {
                          // Request was successful, display the success message
                          var successMessage = document.createElement("div");
                          successMessage.innerText = "Data has been successfully updated.";
                          successMessage.className = "success-message";
                          document.body.appendChild(successMessage);
                            } else {
                          // Request failed, display an error message
                          document.getElementById("result").style.display = "block";
                          document.getElementById("result").innerHTML = "Error: " + xhr.status + " - " + xhr.statusText;
                      }
                  }
              };
  
              xhr.send(formData);
          } else {
              alert("Please select a CSV file to upload.");
          }
      });
  });

  document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("uploadButtonScheduling").addEventListener("click", function () {
        var fileInput = document.getElementById("csv_file_scheduling");
        var file = fileInput.files[0];

        if (file) {
            var formData = new FormData();
            formData.append("csv_file_scheduling", file);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "/periodicalScheduleUpload.php", true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Request was successful, display the success message
                        var successMessage = document.createElement("div");
                        successMessage.innerText = "Scheduling data has been successfully updated.";
                        successMessage.className = "success-message";
                        document.body.appendChild(successMessage);
                    } else {
                        // Request failed, display an error message
                        document.getElementById("resultScheduling").style.display = "block";
                        document.getElementById("resultScheduling").innerHTML = "Error: " + xhr.status + " - " + xhr.statusText;
                    }
                }
            };

            xhr.send(formData);
        } else {
            alert("Please select a CSV file to upload.");
        }
    });
});

  
    </script>';


    $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    return $str;
  }   
}  

?>
