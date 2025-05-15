<?php
class KPIResultsController extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
  }

  function SaveCheck() {
    session_start();
    if($_SESSION['user_id']) {
      $table = (isset($_GET['table']) ? $_GET['table'] : null);
      $col = (isset($_GET['col']) ? $_GET['col'] : null);
      $item_id = (isset($_GET['item_id']) ? $_GET['item_id'] : null);
      $status = (isset($_GET['status']) ? $_GET['status'] : null);
      $sql = "update $table set $col = ".($status == "on" ? 1 : 0)." where id = $item_id;";
      $result = $this->dbi->query($sql);
    }
  }
  function KpiSummary() {
    $kpi_xl = (isset($_GET['kpi_xl']) ? $_GET['kpi_xl'] : null);
    $kpi_map = (isset($_GET['kpi_map']) ? $_GET['kpi_map'] : null);
    $my_kpi = (isset($_GET['my_kpi']) ? $_GET['my_kpi'] : null);
    $kpi_missed = (isset($_GET['kpi_missed']) ? $_GET['kpi_missed'] : null);
    $str .= '
    <div id="message"></div>
    <script>
      function toggle_itm(table, col, item_id, status) {
        var ctl = document.getElementById(item_id);
        //alert(ctl.checked)
        if (window.XMLHttpRequest) {  xmlhttp = new XMLHttpRequest(); } else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
        xmlhttp.open("GET","'.$this->f3->get('main_folder').'SaveCheck?table="+table+"&col="+col+"&item_id="+item_id+"&status="+(ctl.checked == true ? \'on\' : \'off\'),true);
        xmlhttp.send();
          xmlhttp.onreadystatechange=function() { if (this.readyState==4 && this.status==200) { document.getElementById("message").innerHTML=this.responseText; document.getElementById("message").style.display = \'block\'; } }  
      }
    </script>
    <style>
      .sel_itm input[type=checkbox] {
        width: 30px !important;
        height: 30px !important;
        ms-transform: scale(2.5); /* IE */
        -moz-transform: scale(2.5); /* FF */
        -o-transform: scale(2.5); /* Opera */
      }
      </style>
    ';
    if($kpi_missed) {
      $str .= '<div class="fl">';
    	$this->list_obj->title = "Staff without a KPI";
      $this->list_obj->show_num_records = 1;
      $this->list_obj->sql = "select distinct(users.employee_id) as `EMP ID`, concat('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Staff Member`, users.phone as `Phone`, states.item_name as `State`
                        from users
                        left join states on states.id = users.state
                        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in(534, 1992, 385) and lookup_answers.table_assoc = 'users'
                        where users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                        and users.id not in (
                          select compliance_auditors_subjects.user_id from compliance
                          inner join compliance_auditors on compliance_auditors.compliance_id = compliance.id
                          inner join compliance_auditors_subjects on compliance_auditors_subjects.compliance_auditor_id = compliance_auditors.id
                          where compliance.title LIKE '%kpi%'
                        )
                        and users.username NOT in ('sam.johnson', 'fernando.tapia')
                        ";
      $str .= $this->list_obj->draw_list();
      $str .= '</div><div class="fl" style="padding-left: 30px;">';
      $kpi_for_month = date("m") - 1;
      $kpi_for_year = date("Y");
      if(!$kpi_for_month) {
        $kpi_for_month = 12;
        $kpi_for_year--;
      }
      $kpi_date = Date("F Y", strtotime("$kpi_for_year-$kpi_for_month-01"));
    	$this->list_obj->title = "Missed KPI's - $kpi_date";
      $this->list_obj->show_num_records = 1;
      $this->list_obj->sql = "select compliance.title as `Title`, concat('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a><br />', users.phone) as `Assessor`,
                        concat('<a href=\"mailto:', users2.email, '\">', users2.name, ' ', users2.surname, '</a><br />', users2.phone) as `Subject`
                        from compliance_auditors_subjects
                        left join compliance_auditors on compliance_auditors.id = compliance_auditors_subjects.compliance_auditor_id
                        left join users on users.id = compliance_auditors.user_id
                        left join compliance on compliance.id = compliance_auditors.compliance_id
                        left join users2 on users2.id = compliance_auditors_subjects.user_id
                        where compliance.title LIKE '%kpi%'
                        and not (compliance.id in (select compliance_id from compliance_checks where month(check_date_time) = month(now()) and year(check_date_time) = year(now()))
                        and users2.id in (select subject_id from compliance_checks where month(check_date_time) = month(now()) and year(check_date_time) = year(now())))
                        and users.user_status_id = 30 and users2.user_status_id = 30
                        order by concat(users2.name, ' ', users2.surname), users.user_level_id";
    	$str .= $this->list_obj->draw_list();
      $str .= '</div>';
      $str .= '<div class="cl"></div>';
    } else if($kpi_map) {
      $itm = new input_item;
      $itm->hide_filter = 1;
        if(!$kpi_xl) {
        $cmb_sql = "select compliance.title as `item_name` from compliance where title LIKE '%kpi%'";
        $str .= '<h3 class="fl" style="padding-right: 20px;">KPI Map</h3><div class="fl">' . $itm->cmb("txtKPISearch", "", ' style="height: 24px !important;" placeholder="Search..." class="search_box"', "", "", $cmb_sql);
        $str .= '<input type="submit" value="Search" class="search_button" /> &nbsp; &nbsp; &nbsp; <a href="?kpi_map=1&kpi_xl=1">Download in Excel Format</a></div><div class="cl"></div>';
        $srch = (isset($_POST['txtKPISearch']) ? $_POST['txtKPISearch'] : null);
        if($srch) {
          $srch = " and (compliance.title LIKE '%$srch%' or concat(users.name, ' ', users.surname) LIKE '%$srch%' or concat(users2.name, ' ', users2.surname) LIKE '%$srch%')";
        }
      }
      $this->list_obj->sql = "select compliance.title as `Title`, concat(users.name, ' ', users.surname) as `Auditor`, concat(users2.name, ' ', users2.surname) as `Subject`
                        " . ($kpi_xl ? "" : ",
                        CONCAT('<a class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Edit/ComplianceQuestions?lookup_id=', compliance.id, '\">Questions</a><br />
                        <a class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Page/ComplianceAccess?compliance_id=', compliance.id, '\">Auditors</a><a class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Page/ComplianceAccess?compliance_id=', compliance.id, '&subjects_for_id=', users.id ,'\">Subjects</a><br />
                        <a class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Page/UploadCompliance?compliance_id=', compliance.id, '\">Upload Questions</a>') as `Manage`") . "
                        from compliance_auditors_subjects
                        left join compliance_auditors on compliance_auditors.id = compliance_auditors_subjects.compliance_auditor_id
                        left join users on users.id = compliance_auditors.user_id
                        left join compliance on compliance.id = compliance_auditors.compliance_id
                        left join users2 on users2.id = compliance_auditors_subjects.user_id
                        where compliance.title LIKE '%kpi%' $srch
                        order by concat(users.name, ' ', users.surname), compliance.title;";
      $str .= ($kpi_xl ? $this->list_obj->sql_xl('kpi_map.xlsx') : 
      '<div class="fl">' . $this->list_obj->draw_list() . '</div><div class="fl"><iframe id="edit" name="edit" width="900" height="5000" frameborder="0"></iframe></div><div class="cl"></div>');
    } else if($my_kpi) {
      $itm = new input_item;
      $itm->hide_filter = 1;
        $this->list_obj->sql = "select distinct(compliance.title) as `KPI`,
                        CONCAT('<a class=\"list_a\" target=\"_blank\" href=\"".$this->f3->get('main_folder')."Page/ComplianceAccess?my_kpi=1&compliance_id=', compliance.id, '&subjects_for_id=', users.id ,'\">My Staff</a><br />') as `Manage`
                        from compliance_auditors_subjects
                        left join compliance_auditors on compliance_auditors.id = compliance_auditors_subjects.compliance_auditor_id
                        left join users on users.id = compliance_auditors.user_id
                        left join compliance on compliance.id = compliance_auditors.compliance_id
                        left join users2 on users2.id = compliance_auditors_subjects.user_id
                        where compliance.title LIKE '%kpi%' and users.id = ".$_SESSION['user_id']."
                        group by compliance.title
                        order by concat(users.name, ' ', users.surname), compliance.title;";
      $str .= '<div class="fl">' . $this->list_obj->draw_list() . '</div><div class="fl"><iframe id="edit" name="edit" width="900" height="5000" frameborder="0"></iframe></div><div class="cl"></div>';
    } else {
      $group_kpi = (isset($_GET['group_kpi']) ? $_GET['group_kpi'] : null);
      $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
      $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
      $show_subject = (isset($_GET['show_subject']) ? $_GET['show_subject'] : null);
      $subject_name = (isset($_GET['subject_name']) ? $_GET['subject_name'] : null);
      $show_assessor = (isset($_GET['show_assessor']) ? $_GET['show_assessor'] : null);
      $assessor_name = (isset($_GET['assessor_name']) ? $_GET['assessor_name'] : null);
      $edit_date = (isset($_GET['edit_date']) ? $_GET['edit_date'] : null);
      $date_change = (isset($_GET['date_change']) ? $_GET['date_change'] : null);
      $txtDate = (isset($_GET['txtDate']) ? $_GET['txtDate'] : null);
      if($txtDate) {
        $mysql_date = Date("Y-m-d", strtotime($date_change));
        $sql = "update compliance_checks set check_date_time = '$mysql_date 0:0:0' where id = $edit_date";
        $this->dbi->query($sql);
        $str .= "Date Changed...";
      }
      if($edit_date) {
        $txt_itm = new input_item;
        $str .= $txt_itm->setup_cal();
        $str .= '
        <script language="JavaScript">
          function change_date() {
            document.getElementById("date_change").value=document.getElementById("txtDate").value
            document.getElementById("hdnChangeDate").value=1
            document.frmChangeDate.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmChangeDate">
        <input type="hidden" name="date_change" id="date_change">
        <input type="hidden" name="hdnChangeDate" id="hdnChangeDate">
        <input type="hidden" name="edit_date" id="edit_date" value="' . $edit_date . '">
        <input type="hidden" name="group_kpi" id="edit_date" value="' . $group_kpi . '">
        <div class="form-wrapper">
        <div class="form-header">Change Date -- Add one month after the date that the check was for. Eg. Making it April puts it in March.</div>
        <div  style="padding: 10px;">
        ' . $txt_itm->cal("txtDate", "", ' value="'.$date_change.'" ', "", "", "") .
        '<input onClick="change_date()" type="button" value="Go" /> 
        </div>
        </div>
        </form>';
      }
      if(!$nav_month) {
        $def_month = 1;
        $nav_month = date("m");
        $nav_year = date("Y");
      }
      if(!$edit_date) {
        $nav = new navbar;
        $str .= '
            <script language="JavaScript">
              function report_filter() {
                document.getElementById("hdnReportFilter").value=1
                document.frmFilter.submit()
              }
            </script>
            </form>
            <form method="GET" name="frmFilter">
            <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
            <input type="hidden" name="group_kpi" id="edit_date" value="' . $group_kpi . '">
            <div class="form-wrapper">
            <div class="form-header">Filter</div>
            <div  style="padding: 10px;">
            '.$nav->month_year(2015).'
            <input onClick="report_filter()" type="button" value="Go" /> 
            </div>
            </div>
            </form>
        ';
        if($def_month) {
          $str .= '
            <script language="JavaScript">
              change_selDate()
            </script>
          ';
        }
      }
      if($nav_month > 0) {
        $nav1 = "and MONTH(compliance_checks.check_date_time) = $nav_month";
      } else {
        $nav_month = "ALL Months";
      }
      if($nav_year > 0) {
        $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
      } else {
        $nav_year = "ALL Years";
      }
      $kpi_for_month = $nav_month - 1;
      $kpi_for_year = $nav_year;
      if(!$kpi_for_month) {
        $kpi_for_month = 12;
        $kpi_for_year--;
      }
      if($show_subject) {
        $nav2 .= " and users.id = $show_subject ";
        $title_xtra = " on $subject_name ";
      }
      if($show_assessor) {
        $nav2 .= " and users2.id = $show_assessor ";
        $title_xtra = " by $assessor_name ";
      }
      if($edit_date) {
        $nav1 = "";
        $nav2 .= " and compliance_checks.id = $edit_date ";
      }
      if($group_kpi) $group_xtra = "inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $group_kpi and lookup_answers.table_assoc = 'users'";
      $sql = "
      select compliance_checks.id, compliance.title, CONCAT(users.name, ' ', users.surname) as `subject`, CONCAT(users2.name, ' ', users2.surname) as `assessor`, states.item_name as `state_name`, compliance_checks.trigger_sent,
              users.id as `subject_id`, users2.id as `assessor_id`, users.employee_id, compliance_checks.total_out_of, compliance_checks.check_date_time, user_level.item_name as `user_level`, compliance_checks.percent_complete
              from compliance_checks
              left join users on users.id = compliance_checks.subject_id
              left join users2 on users2.id = compliance_checks.assessor_id
              left join compliance on compliance.id = compliance_checks.compliance_id
              left join states on states.id = users.state
              left join user_level on user_level.id = users.user_level_id
              $group_xtra
              where compliance.title like '%KPI%' and compliance.title NOT LIKE 'Vicinity KPI' and compliance.title NOT LIKE 'Westfield KPI' and compliance.title NOT LIKE 'Investa KPI'
              and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
              $nav1 $nav2 
              order by compliance_checks.check_date_time DESC
      ";
      if($result = $this->dbi->query($sql)) {
        if(!$edit_date) {
          $num_results = $result->num_rows;
          if($nav_month != "ALL Months") $for_months = "(for $kpi_for_month / $kpi_for_year)";
          $str .= "<h3 class=\"fl\">KPI Summary Performed During $nav_month / $nav_year $for_months $title_xtra</h3><div style=\"padding: 6px; border: 1px solid #990000; background-color: #FFFFDD;\" class=\"fr\"><b>Note: </b>Please disregard if the score is now <i>and</i> the percent done is also low...</div><div class=\"cl\"></div><h6>$num_results KPI's Added</h6>";
        } else {
          $str .= "<h3>Editing KPI Date For</h3>";
        }
        $str .= "<table class=\"grid\"><tr><th>ID</th><th>Date</th><th>Date For</th><th>% Done</th><th>Title</th><th>Assessor</th><th>Subject Name</th><th>User Level</th><th>Subject ID</th><th>State</th><th>Score</th><th>Paid</th></tr>";
        while($myrow = $result->fetch_assoc()) {
          $ccid = $myrow['id'];
          $subject = $myrow['subject'];
          $subject_id = $myrow['subject_id'];
          $assessor = $myrow['assessor'];
          $title = $myrow['title'];
          $assessor_id = $myrow['assessor_id'];
          $employee_id = $myrow['employee_id'];
          $state_name = $myrow['state_name'];
          $user_level = $myrow['user_level'];
          $check_date = Date("d-M-Y", strtotime($myrow['check_date_time']));
          $date_for = date('M-Y', strtotime('-1 months', strtotime($myrow['check_date_time']))); 
          $out_of = $myrow['total_out_of'];
          $percent_complete = $myrow['percent_complete'];
          $sql = "select (sum(value)/$out_of)*100 as `score` from compliance_check_answers where compliance_check_id = $ccid";
          $result2 = $this->dbi->query($sql);
          if($myrow2 = $result2->fetch_assoc()) {
            $score = round($myrow2['score']) . "%";
          }
          $trigger_sent = "<span class=\"sel_itm\"><input onClick=\"toggle_itm('compliance_checks', 'trigger_sent', '$ccid', '" . ($myrow['trigger_sent'] ? "off" : "on") . "')\" type=\"checkbox\" " . ($myrow['trigger_sent'] ? "checked" : "") . " id=\"$ccid\" name=\"$ccid\" value=\"1\" /></span>";
          if($myrow2['score']) {
            $str .= "<tr><td>$ccid</td><td><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."KPI/KPISummary?date_change=$check_date&edit_date=$ccid&group_kpi=$group_kpi\" title=\"Change the Date from $check_date\">Change</a> $check_date</td><td>$date_for</td><td>$percent_complete</td><td>$title</td>
            <td><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?hdnReportFilter=1&selDateMonth=-1&selDateYear=-1&show_assessor=$assessor_id&assessor_name=".urldecode($assessor)."&group_kpi=$group_kpi\" title=\"View KPI Assessments By $assessor\">By</a> $assessor</td>
            <td><b><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?hdnReportFilter=1&selDateMonth=-1&selDateYear=-1&show_subject=$subject_id&subject_name=".urldecode($subject)."&group_kpi=$group_kpi\" title=\"View KPI Assessments For $subject\">For</a> $subject</b></td>
            <td>$user_level</td><td>$employee_id</td><td>$state_name</td><td>$score</td><td>$trigger_sent</td></tr>";
          }
        }
      }
      $str .= "</table>";
      if($edit_date) {
      //?group_kpi='.$group_kpi.'
        $str .= '<br /><br /><a class="list_a" href="'.$this->f3->get('main_folder').'KPI/KPISummary">Back to KPI Summary...</a>';
      }
    }
    return $str;
  }
  function GroupKpi() {
    $group_name = (isset($_GET['group_name']) ? $_GET['group_name'] : null);
    $str .= '
    <style>
    .legend_wrap {
      display: inline-block;
      border-left: 1px solid #000099;
      border-top: 1px solid #000099;
      border-bottom: 1px solid #000099;
      padding: 0px;
      margin-bottom: 8px;
      font-weight: bold;
    }
    .legend_item {
      border-right: 1px solid #000099;
      display: inline-block;
      padding-top: 8px;
      padding-bottom: 8px;
      padding-right: 15px;
      padding-left: 15px;
    }
    </style>
    <div class="legend_wrap">
    <span class="legend_item">LEGEND</span>
    <span class="legend_item" style="color: red;">NOT COMPETENT (1-25)</span>
    <span class="legend_item" style="color: green;">COMPETENT (26-39)</span>
    <span class="legend_item" style="color: blue;">EXCEEDS EXPECATIONS (40-52)</span>
    </div>
    ';
    $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
    $state_filter = (isset($_GET['state_filter']) ? $_GET['state_filter'] : null);
    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
    $show_month = $nav_month;
    $show_year = $nav_year;
    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }
    if($report_view_id) {
        $icons = array(
        ".pdf" => "pdf-icon.png",
        "docx" => "word-icon.png",
        ".doc" => "word-icon.png",
        "xlsx" => "excel-icon.png",
        ".xls" => "excel-icon.pn",
        ".zip" => "zip-icon.png",
        ".png" => "png-icon.png",
        ".jpg" => "jpg-icon.png",
        ".gif" => "gif-icon.png"
      );
    }
    $nav = new navbar;
    $str .= '
        <script language="JavaScript">
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter">
        <input type="hidden" name="group_name" id="group_name" value="'.$group_name.'">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <div class="form-wrapper">
        <div class="form-header">Filter</div>
        <div  style="padding: 10px;">
        '.$nav->month_year(2015,0).'
        <input onClick="report_filter()" type="button" value="Go" /> 
        </div>
        </div>
        </form>
    ';
    if($def_month) {
      $str .= '
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
    }
    if(!$report_view_id) {
      if($nav_month > 0) {
        $nav1 = "and MONTH(date_sub(compliance_checks.check_date_time, interval 1 month)) = $nav_month";
      } else {
        $nav_month = "ALL Months";
      }
      if($nav_year > 0) {
        $nav2 = "and YEAR(date_sub(compliance_checks.check_date_time, interval 1 month)) = $nav_year";
      } else {
        $nav_year = "ALL Years";
      }
    }
    $kpi_for_month = $nav_month - 1;
    $kpi_for_year = $nav_year;
    if(!$kpi_for_month) {
      $kpi_for_month = 12;
      $kpi_for_year--;
    }
    if($show_month != -1 && $show_year != -1) {
      $sites_in_state = array();
      $sql = "select states.item_name, count(states.item_name) as num_in_state from users
              inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in 
                (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = (select id from compliance where title like '$group_name KPI'))
              inner join states on states.id = users.state
              group by states.item_name";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $sites_in_state[$myrow['item_name']] = $myrow['num_in_state'];
      }
    }
    if($report_view_id) {
      $nav2 .= " and compliance_checks.id = $report_view_id ";
    }
    if($state_filter) {
      $state_filter_show = strtolower($state_filter);
      $state_filter = " and states.item_name = '$state_filter' ";
    } else {
      $state_filter_show = "of_australia";
    }
    $sql = "
    select compliance_checks.id, compliance.title, compliance.id as `compliance_id`, users.name as `subject`, CONCAT(users2.name, ' ', users2.surname) as `assessor`, states.item_name as `state_name`,
            users.id as `subject_id`, users2.id as `assessor_id`, users.employee_id, compliance_checks.total_out_of, date_sub(compliance_checks.check_date_time, interval 1 month) as `check_date_time`, user_level.item_name as `user_level`
            from compliance_checks
            left join users on users.id = compliance_checks.subject_id
            left join users2 on users2.id = compliance_checks.assessor_id
            left join compliance on compliance.id = compliance_checks.compliance_id
            left join states on states.id = users.state
            left join user_level on user_level.id = users.user_level_id
            where compliance.title LIKE '$group_name KPI' and compliance_checks.total_out_of > 0 and compliance_checks.percent_complete > 0
            and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
            $state_filter
            $nav1 $nav2 
            order by compliance_checks.check_date_time DESC
    ";
    $result = $this->dbi->query($sql);
    if(!$report_view_id) {
      $num_results = $result->num_rows;
      if($nav_month != "ALL Months") {
        $for_months = "(for $kpi_for_month / $kpi_for_year)";
            $nav_month = Date("M", strtotime("2000-$nav_month-11"));
      }
      $str .= "<br /><h3>$group_name KPI Summary For $nav_month / $nav_year $title_xtra</h3><h6>$num_results KPI's Added</h6>";
      $r_title = "Reports";
      $avg_str = "<br /><h3>State Average KPI's</h3><table class=\"grid\"><tr><th>State</th><th>Average KPI</th>";
      if($show_month != -1 && $show_year != -1) $avg_str .= "<th>Completed</th>";
      $avg_str .= "</tr>";
    } else {
      $r_title = "Report Details";
    }
    $state_avgs = array();
    $state_counts = array();
    $sites = array();
    $site_avgs = array();
    $site_states = array();
    $site_counts = array();
    if(($show_month != -1 && $show_year != -1 || $state_filter)) $report_str = "<br /><h3>$r_title</h3><table class=\"grid\"><tr><th>*</th>" . ($report_view_id ? "<th>Month/Year</th>" : "") . "<th>Title</th><th>Assessor</th><th>Site Name</th><th>State</th><th>Score</th></tr>";
    if($show_month == -1 && $show_year == -1 && !$state_filter) $report_str = "<br /><h3>Site Averages</h3><table class=\"grid\"><tr><th>Site Name</th><th>State</th><th>Average KPI</th></tr>";
    while($myrow = $result->fetch_assoc()) {
      $ccid = $myrow['id'];
      $compliance_id = $myrow['compliance_id'];
      $out_of = $myrow['total_out_of'];
      $state_name = $myrow['state_name'];
      $subject = $myrow['subject'];
      if(($show_month != -1 && $show_year != -1 || $state_filter)) {
        $title = $myrow['title'];
        $assessor = $myrow['assessor'];
        $assessor_id = $myrow['assessor_id'];
        $employee_id = $myrow['employee_id'];
        $user_level = $myrow['user_level'];
        $check_date = Date("M/Y", strtotime($myrow['check_date_time']));
      }
      $sql = "select sum(value) as `score` from compliance_check_answers where compliance_check_id = $ccid";
      $result2 = $this->dbi->query($sql);
      if($myrow2 = $result2->fetch_assoc()) {
        $score = $myrow2['score'] . "/52";
        $state_avgs[$state_name] += $myrow2['score'];
        $national_avg += $myrow2['score'];
        $national_count++;
        $state_counts[$state_name]++;
        if($show_month == -1 && $show_year == -1 && !$state_filter) {
          $site_avgs[$subject] += $myrow2['score'];
          $site_states[$subject] = $state_name;
          $site_counts[$subject]++;
        }
      }
      if($myrow2['score'] > 39) {
        $colour = "blue";
      } else if($myrow2['score'] > 25) {
        $colour = "green";
      } else {
        $colour = "red";
      }
      if($myrow2['score'] && ($show_month != -1 && $show_year != -1 || $state_filter)) {
        $report_str .= "<tr>";
        if($report_view_id) {
          $report_str .= "<td><a href=\"".$this->f3->get('main_folder')."KPI/GroupKPI?group_name=$group_name\">Back to Summary</a></td><td>$check_date</td><td>$title</td>";
        } else {
          $report_str .= "<td><a href=\"".$this->f3->get('main_folder')."KPI/GroupKPI?report_view_id=$ccid&group_name=$group_name\">View Details</a></td><td>$title</td>";
        }
        $subject = str_replace(" QLD", "", $subject);
        $subject = str_replace(" NSW", "", $subject);
        $subject = str_replace(" WA", "", $subject);
        $subject = str_replace(" SA", "", $subject);
        $subject = str_replace(" WA", "", $subject);
        $subject = str_replace(" TAS", "", $subject);
        $subject = str_replace(" VIC", "", $subject);
        $subject = str_replace("WA ", "", $subject);
        $report_str .= "<td>$assessor</td>
        <td><b>$subject</b></td>
        <td>$state_name</td><td style=\"color: $colour; font-weight: bold;\">$score</td></tr>";
      }
    }
    if(!$report_view_id) {
      $coords = array();
      $coords['WA'] = "3,340,188,33";
      $coords['NT'] = "188,1,306,202";
      $coords['SA'] = "191,204,327,377";
      $coords['QLD'] = "307,0,482,252";
      $coords['NSW'] = "330,253,482,329";
      $coords['ACT'] = "401,330,427,356";
      $coords['VIC'] = "330,332,422,385";
      $coords['TAS'] = "344,389,409,441";
      $area_map = "";
      foreach($state_avgs as $state => $state_avg) {
        $state_avg = round($state_avg / $state_counts[$state]);
        if($state_avg > 39) {
          $colour = "blue";
          $c = "b";
              } else if($state_avg > 25) {
          $colour = "green";
          $c = "g";
              } else {
          $colour = "red";
          $c = "r";
              }
        $img_qry .= "&$state=$c";
        $link = "?group_name=$group_name&hdnReportFilter=" . $_GET['hdnReportFilter'] . "&selDateMonth=$show_month&selDateYear=$show_year&state_filter=$state";
        $area_map .= "<area alt=\"$state (Average KPI = " . ($state_avg + 7) . "/52)\" title=\"$state (Average KPI = " . ($state_avg + 7) . "/52)\" href=\"".$this->f3->get('main_folder')."$link\" shape=\"rect\" coords=\"".$coords[$state]."\" />\n";
        $avg_str .= "<tr><td><a href=\"".$this->f3->get('main_folder')."$link\">$state</a></td><td style=\"color: $colour; font-weight: bold;\">" . ($state_avg + 7) . "/52</td>";
            if($show_month != -1 && $show_year != -1) {
          $avg_str .= "<td>" . $state_counts[$state] . "/" . $sites_in_state[$state] . "</td>";
        }
        $avg_str .= "</tr>";
      }
      foreach($site_avgs as $site => $site_avg) {
        $site_avg = round($site_avg / $state_counts[$state]);
        if($site_avg > 39) {
          $colour = "blue";
        } else if($site_avg > 25) {
          $colour = "green";
        } else {
          $colour = "red";
        }
        $report_str .= "<tr><td>$site</td><td>".$site_states[$site]."</td><td style=\"color: $colour; font-weight: bold;\">$site_avg%</td></tr>";
      }
      $avg_str .= "</table>";
      if($national_avg && !$state_filter) {
        $national_avg = round($national_avg / $national_count);
        if($national_avg > 39) {
          $colour = "blue";
          $c = "b";
          $national_avg = "Exceeds Expectations";
        } else if($national_avg > 25) {
          $colour = "green";
          $c = "g";
          $national_avg = "Competent";
        } else {
          $colour = "red";
          $c = "r";
          $national_avg = "Not Yet Competent";
        }
        $avg_str .= "<br /><b>National Average</b>: <span style=\"color: $colour; font-weight: bold;\">$national_avg</span>";
      }
      $str .= '<div class="fl">' . $avg_str . '</div>
      <div class="fl" style="padding-left: 20px;"><img src="'.$this->f3->get('main_folder').'DrawOz?img='.$this->f3->get('img_folder').'map_'.$state_filter_show.'.gif'.$img_qry.'" usemap="#Map" /></div><div class="cl"></div>
      <map name="Map" id="Map">
      '.$area_map.'    
      </map>
      ';
    }
    
    $report_str .= "</table>";
    if($report_view_id) {
      $compliance_obj = new compliance;
      $compliance_obj->dbi = $this->dbi;
        $compliance_obj->compliance_check_id = $report_view_id;
      $compliance_obj->display_results();
    } else {
      $this->list_obj->title = "Missed KPI's";
      $this->list_obj->sql = "select name as `Site`, states.item_name as `State`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?report_id=$compliance_id&site_id=', users.id, '\" target=\"_blank\">Perform KPI</a>') as `**` from users
                        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in 
                        (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = (select id from compliance where title = '$group_name KPI'))
                        inner join states on states.id = users.state
                        where users.id NOT in (
                          select  users.id
                          from compliance_checks
                          left join users on users.id = compliance_checks.subject_id
                          left join compliance on compliance.id = compliance_checks.compliance_id
                          left join states on states.id = users.state
                          left join user_level on user_level.id = users.user_level_id
                          where compliance.title LIKE '$group_name KPI' and compliance_checks.total_out_of > 0 and compliance_checks.percent_complete > 0
                          and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
                          $nav1 $nav2 
                        )
                        $state_filter
                        ;";
      $str .= '<div class="fl">' . $report_str . '</div><div class="fl" style="padding-left: 30px; padding-top: 18px;">' . $this->list_obj->draw_list() . '</div><div class="cl"></div>';
    }
    return $str;
  }
  function WestfieldKpi() {
    $str .= '
    <style>
    .legend_wrap {
      display: inline-block;
      border-left: 1px solid #000099;
      border-top: 1px solid #000099;
      border-bottom: 1px solid #000099;
      padding: 0px;
      margin-bottom: 8px;
      font-weight: bold;
    }
    .legend_item {
      border-right: 1px solid #000099;
      display: inline-block;
      padding-top: 8px;
      padding-bottom: 8px;
      padding-right: 10px;
      padding-left: 10px;
    }
    </style>
    <div class="legend_wrap">
    <span class="legend_item">LEGEND</span>
    <span class="legend_item" style="color: red;">NOT MEETING EXPECTATIONS (-50-15)</span>
    <span class="legend_item" style="color: #FBBC05;">NEEDS IMPROVEMENT (16-25)</span>
    <span class="legend_item" style="color: #FBBC05;">MEETS MOST EXPECTATIONS (26-35)</span>
    <span class="legend_item" style="color: green;">MEETS ALL EXPECTATIONS (36-45)</span>
    <span class="legend_item" style="color: blue;">EXCEEDS ALL EXPECTATIONS (46-55)</span>
    </div>
    ';
    $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
    $state_filter = (isset($_GET['state_filter']) ? $_GET['state_filter'] : null);
    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
    $show_month = $nav_month;
    $show_year = $nav_year;
    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }
    if($report_view_id) {
        $icons = array(
        ".pdf" => "pdf-icon.png",
        "docx" => "word-icon.png",
        ".doc" => "word-icon.png",
        "xlsx" => "excel-icon.png",
        ".xls" => "excel-icon.pn",
        ".zip" => "zip-icon.png",
        ".png" => "png-icon.png",
        ".jpg" => "jpg-icon.png",
        ".gif" => "gif-icon.png"
      );
    }
    $nav = new navbar;
    $str .= '
        <script language="JavaScript">
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <div class="form-wrapper">
        <div class="form-header">Filter</div>
        <div  style="padding: 10px;">
        '.$nav->month_year(2015,0).'
        <input onClick="report_filter()" type="button" value="Go" /> 
        </div>
        </div>
        </form>
    ';
    if($def_month) {
      $str .= '
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
    }
    if(!$report_view_id) {
      if($nav_month > 0) {
        $nav1 = "and MONTH(date_sub(compliance_checks.check_date_time, interval 1 month)) = $nav_month";
      } else {
        $nav_month = "ALL Months";
      }
      if($nav_year > 0) {
        $nav2 = "and YEAR(date_sub(compliance_checks.check_date_time, interval 1 month)) = $nav_year";
      } else {
        $nav_year = "ALL Years";
      }
    }
    $kpi_for_month = $nav_month - 1;
    $kpi_for_year = $nav_year;
    if(!$kpi_for_month) {
      $kpi_for_month = 12;
      $kpi_for_year--;
    }
    if($show_month != -1 && $show_year != -1) {
      $sites_in_state = array();
      $sql = "select states.item_name, count(states.item_name) as num_in_state from users
              inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id in 
                (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = (select id from compliance where title = 'Westfield KPI'))
              inner join states on states.id = users.state
              where users.user_status_id = 30
              group by states.item_name";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $sites_in_state[$myrow['item_name']] = $myrow['num_in_state'];
      }
      $sites_in_state['NSW'] = 7;
    }
    if($report_view_id) {
      $nav2 .= " and compliance_checks.id = $report_view_id ";
    }
    if($state_filter) {
      $state_filter_show = strtolower($state_filter);
      $state_filter = " and states.item_name = '$state_filter' ";
    } else {
      $state_filter_show = "of_australia";
    }
    $sql = "
    select compliance_checks.id, compliance.title, users.name as `subject`, CONCAT(users2.name, ' ', users2.surname) as `assessor`, states.item_name as `state_name`, compliance.id as `compliance_id`,
            users.id as `subject_id`, users2.id as `assessor_id`, users.employee_id, compliance_checks.total_out_of, date_sub(compliance_checks.check_date_time, interval 1 month) as `check_date_time`, user_level.item_name as `user_level`
            from compliance_checks
            left join users on users.id = compliance_checks.subject_id
            left join users2 on users2.id = compliance_checks.assessor_id
            left join compliance on compliance.id = compliance_checks.compliance_id
            left join states on states.id = users.state
            left join user_level on user_level.id = users.user_level_id
            where compliance.title LIKE 'Westfield KPI' and compliance_checks.total_out_of > 0 and compliance_checks.percent_complete > 0
            and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
            $state_filter
            $nav1 $nav2 
            order by compliance_checks.check_date_time DESC
    ";
              $result = $this->dbi->query($sql);
    if(!$report_view_id) {
      $num_results = $result->num_rows;
      if($nav_month != "ALL Months") {
        $for_months = "(for $kpi_for_month / $kpi_for_year)";
            $nav_month = Date("M", strtotime("2000-$nav_month-11"));
      }
      $str .= "<br /><h3>Westfield KPI Summary For $nav_month / $nav_year $title_xtra</h3><h6>$num_results KPI's Added</h6>";
      $r_title = "Reports";
      $avg_str = "<br /><h3>State Average KPI's</h3><table class=\"grid\"><tr><th>State</th><th>Average KPI</th>";
      if($show_month != -1 && $show_year != -1) $avg_str .= "<th>Completed</th>";
      $avg_str .= "</tr>";
    } else {
      $r_title = "Report Details";
    }
    $state_avgs = array();
    $state_counts = array();
    $sites = array();
    $site_avgs = array();
    $site_states = array();
    $site_counts = array();
    if(($show_month != -1 && $show_year != -1 || $state_filter)) $report_str = "<br /><h3>$r_title</h3><table class=\"grid\"><tr><th>*</th>" . ($report_view_id ? "<th>Month/Year</th>" : "") . "<th>Title</th><th>Assessor</th><th>Site Name</th><th>State</th><th>Score</th></tr>";
    if($show_month == -1 && $show_year == -1 && !$state_filter) $report_str = "<br /><h3>Site Averages</h3><table class=\"grid\"><tr><th>Site Name</th><th>State</th><th>Average KPI</th></tr>";
    while($myrow = $result->fetch_assoc()) {
      $ccid = $myrow['id'];
      $compliance_id = $myrow['compliance_id'];
      $out_of = $myrow['total_out_of'];
      $state_name = $myrow['state_name'];
      $subject = $myrow['subject'];
      if(($show_month != -1 && $show_year != -1 || $state_filter)) {
        $title = $myrow['title'];
        $assessor = $myrow['assessor'];
        $assessor_id = $myrow['assessor_id'];
        $employee_id = $myrow['employee_id'];
        $user_level = $myrow['user_level'];
        $check_date = Date("M/Y", strtotime($myrow['check_date_time']));
      }
      $sql = "select sum(value) as `score` from compliance_check_answers where compliance_check_id = $ccid";
      $result2 = $this->dbi->query($sql);
      if($myrow2 = $result2->fetch_assoc()) {
            $score = $myrow2['score'] . "/$out_of";
        $state_avgs[$state_name] += $myrow2['score'];
        $national_avg += $myrow2['score'];
        $national_count++;
        $state_counts[$state_name]++;
        if($show_month == -1 && $show_year == -1 && !$state_filter) {
          $site_avgs[$subject] += $myrow2['score'];
          $site_states[$subject] = $state_name;
          $site_counts[$subject]++;
        }
      }
      if($myrow2['score'] > 45) {
        $colour = "blue";
      } else if($myrow2['score'] > 35) {
        $colour = "green";
      } else if($myrow2['score'] > 25) {
        $colour = "#FBBC05";
      } else if($myrow2['score'] > 15) {
        $colour = "#FBBC05";
      } else {
        $colour = "red";
      }
      if($myrow2['score'] && ($show_month != -1 && $show_year != -1 || $state_filter)) {
        $report_str .= "<tr>";
        if($report_view_id) {
          $report_str .= "<td><a href=\"".$this->f3->get('main_folder')."KPI/WesfieldKpi\">Back to Summary</a></td><td>$check_date</td><td>$title</td>";
        } else {
          $report_str .= "<td><a href=\"".$this->f3->get('main_folder')."KPI/WestfieldKpi?report_view_id=$ccid\">View Details</a></td><td>$title</td>";
        }
        $report_str .= "<td>$assessor</td>
        <td><b>$subject</b></td>
        <td>$state_name</td><td style=\"color: $colour; font-weight: bold;\">$score</td></tr>";
      }
    }
    if(!$report_view_id) {
      $coords = array();
      $coords['WA'] = "3,340,188,33";
      $coords['NT'] = "188,1,306,202";
      $coords['SA'] = "191,204,327,377";
      $coords['QLD'] = "307,0,482,252";
      $coords['NSW'] = "330,253,482,329";
      $coords['ACT'] = "401,330,427,356";
      $coords['VIC'] = "330,332,422,385";
      $coords['TAS'] = "344,389,409,441";
      $area_map = "";
      foreach($state_avgs as $state => $state_avg) {
        $state_avg = round($state_avg / $state_counts[$state]);
        if($state_avg > 45) {
          $colour = "blue";
          $c = "b";
              } else if($state_avg > 35) {
          $colour = "green";
          $c = "g";
              } else if($state_avg > 25) {
          $colour = "#FBBC05";
          $c = "y";
              } else if($state_avg > 15) {
          $colour = "#FBBC05";
          $c = "y";
              } else {
          $colour = "red";
          $c = "r";
              }
        $img_qry .= "&$state=$c";
        $link = "?hdnReportFilter=" . $_GET['hdnReportFilter'] . "&selDateMonth=$show_month&selDateYear=$show_year&state_filter=$state";
        $area_map .= "<area alt=\"$state (Average KPI = $state_avg/55)\" title=\"$state (Average KPI = $state_avg/45)\" href=\"".$this->f3->get('main_folder')."$link\" shape=\"rect\" coords=\"".$coords[$state]."\" />\n";
            $avg_str .= "<tr><td><a href=\"".$this->f3->get('main_folder')."$link\">$state</a></td><td style=\"color: $colour; font-weight: bold;\">$state_avg/55</td>";
            if($show_month != -1 && $show_year != -1) {
          $avg_str .= "<td>" . $state_counts[$state] . "/" . $sites_in_state[$state] . "</td>";
        }
        $avg_str .= "</tr>";
      }
      foreach($site_avgs as $site => $site_avg) {
        $site_avg = round($site_avg / $state_counts[$state]);
        if($site_avg > 45) {
          $colour = "blue";
        } else if($site_avg > 35) {
          $colour = "green";
        } else if($site_avg > 25) {
          $colour = "#FBBC05";
        } else if($site_avg > 15) {
          $colour = "#FBBC05";
        } else {
          $colour = "red";
        }
        $report_str .= "<tr><td>$site</td><td>".$site_states[$site]."</td><td style=\"color: $colour; font-weight: bold;\">$site_avg%</td></tr>";
      }
      $avg_str .= "</table>";
      if($national_avg && !$state_filter) {
        $national_avg = round($national_avg / $national_count);
        if($national_avg > 45) {
          $colour = "blue";
          $c = "b";
          $national_avg = "Exceeds All Expectations";
        } else if($national_avg > 35) {
          $colour = "green";
          $c = "g";
          $national_avg = "Meets All Expectations";
        } else if($national_avg > 25) {
          $colour = "#FBBC05";
          $c = "y";
          $national_avg = "Meets Most Expectations";
        } else if($national_avg > 15) {
          $colour = "#FBBC05";
          $c = "y";
          $national_avg = "Needs Improvement";
        } else {
          $colour = "red";
          $c = "r";
          $national_avg = "Not Meeting Expectations";
        }
        $avg_str .= "<br /><b>National Average</b>: <span style=\"color: $colour; font-weight: bold;\">$national_avg</span>";
      }
      $str .= '<div class="fl">' . $avg_str . '</div>
      <div class="fl" style="padding-left: 20px;"><img src="'.$this->f3->get('main_folder').'DrawOz?img='.$this->f3->get('img_folder').'map_'.$state_filter_show.'.gif'.$img_qry.'" usemap="#Map" /></div><div class="cl"></div>
      <map name="Map" id="Map">
      '.$area_map.'    
      </map>
      ';
    }
    $report_str .= "</table>";
    $this->list_obj->title = "Missed KPI's";
    $this->list_obj->sql = "select name as `Site`, states.item_name as `State`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Compliance?report_id=$compliance_id&site_id=', users.id, '\" target=\"_blank\">Perform KPI</a>') as `**` from users
                      inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc != 'compliance' and lookup_answers.lookup_field_id in 
                      (select lookup_field_id from lookup_answers where table_assoc = 'compliance' and foreign_id = (select id from compliance where title = 'Westfield KPI'))
                      inner join states on states.id = users.state
                      where users.user_status_id = 30 and
                      users.id NOT in (
                        select  users.id
                        from compliance_checks
                        left join users on users.id = compliance_checks.subject_id
                        left join compliance on compliance.id = compliance_checks.compliance_id
                        left join states on states.id = users.state
                        left join user_level on user_level.id = users.user_level_id
                        where compliance.title LIKE 'Westfield KPI' and compliance_checks.total_out_of > 0 and compliance_checks.percent_complete > 0
                        and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
                        $nav1 $nav2 
                      )
                      $state_filter
                      ;";
    $str .= '<div class="fl">' . $report_str . '</div><div class="fl" style="padding-left: 30px; padding-top: 18px;">' . $this->list_obj->draw_list() . '</div><div class="cl"></div>';
    if($report_view_id) {
      $compliance_obj = new compliance;
      $compliance_obj->dbi = $this->dbi;
      $compliance_obj->compliance_check_id = $report_view_id;
      $compliance_obj->display_results();
    }
      //$str = "<textarea>{$this->list_obj->sql}</textarea>";                  
    return $str;
  }
          
}

?>
