<?php

class ClockController extends Controller {
  
  function Show() {
    $wfc = (isset($_GET['wfc']) ? $_GET['wfc'] : null);
    $signon_off = (isset($_GET['signon_off']) ? $_GET['signon_off'] : null);
    $tbl = ($wfc ? "wfc" : "time_checks");
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');

    if($wfc == 2 || $signon_off) {
      session_start();
      $sql = ($wfc ? "delete from usermeta where user_id = ".$_SESSION['user_id']." and meta_key = (select id from lookups where item_name = 'is_signon_off')"
                   : "insert into usermeta (user_id, meta_value, meta_key) (select '".$_SESSION['user_id']."', '1', id from lookups where item_name = 'is_signon_off')");
      //  $str .= $sql;           
      $this->dbi->query($sql);
      $str .= $this->redirect("Clock" . ($wfc ? "?wfc=1" . ($show_min ? "&show_min=1" : "") : ($show_min ? "?show_min=1" : "")));
    }


    $sql = "select meta_value from usermeta where user_id = ".$_SESSION['user_id']." and meta_key = (select id from lookups where item_name = 'is_signon_off');";
    $result = $this->dbi->query($sql);
    if($myrow = $result->fetch_assoc()) $is_signon = ($myrow['meta_value'] == 1 ? 1 : 0);

    $str .= '<input type="hidden" name="hdnLatitude" id="hdnLatitude" value="' . $_POST['hdnLatitude'] . '" />
              <input type="hidden" name="hdnLongitude" id="hdnLongitude"  value="' . $_POST['hdnLongitude'] . '" />
              <div id="x"></div>
              <script type="text/javascript">';


    if(!isset($_POST['hdnLatitude']) && !$to_sign_off) $str .= "getLocation();";

    $str .= '</script>';


    $codes['ON'] = "Signed On";
    $codes['OFF'] = "Signed Off";

    $date_time = date('Y-m-d H:i:s');
    $current_date = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 days"));

    $detect = new Mobile_Detect;

    $delid = $_POST['delid'];
    $hdnSaveComments = $_POST['hdnSaveComments'];
    $txtComment = $_POST['txtComment'];
    $txtComment = $this->mesc($txtComment);

    if($delid) {
      $sql = "delete from $tbl where id = $delid;";
      $this->dbi->query($sql);
      $msg = "Item Deleted...";
    }

    if($hdnSaveComments) {
      foreach ($_POST as $param_name => $param_val) {
        $bits = explode("_", $param_name);
        if($bits[0] == "txaComment") {
          $id_change = $bits[1];
          if($param_val) {
            $sql = "update $tbl set " . ($wfc ? "staff_" : "") . "comment = '$param_val' where id = $id_change;";
            $this->dbi->query($sql);
          }
        }
      }
      $msg = "Comment(s) Saved...";
    }


    /*if($detect->isMobile()){
      $font_size = "font-size: 35px;";
      $font_size_message = "font-size: 40px !important;";
      $text_style = "width: 100%; height: 100px;";
      $sos_margin = "margin-top: 600px;";
    } else {
      $font_size = "";
      $text_style = "width: 100%; height: 50px; float: left;";
      $sos_margin = "margin-top: 10px;";
    }*/
      $font_size = "";
      $text_style = "width: 100%; height: 50px; float: left;";
      $sos_margin = "margin-top: 10px;";

    //if($_POST['hdnFlag'] && $_POST['hdnLatitude'] && $_POST['hdnLongitude']) {
    if($_POST['hdnFlag']) {
      $sql = "insert into $tbl (employee_id, date_time, " . (!$wfc ? "$tbl.activity_type, " : "") . " latitude, longitude, ip_address, " . ($wfc ? "staff_" : "") . "comment, site_id) values (" . $_SESSION['user_id'] . ", '$date_time', " . (!$wfc ? "'" . $_POST['hdnFlag'] . "', '": "'") . $_POST['hdnLatitude']."', '".$_POST['hdnLongitude']."', '".$_SERVER['REMOTE_ADDR']."', '$txtComment', '".$_POST['hdnSiteId']."')";
    //  $str .= $sql;
      $result = $this->dbi->query($sql);
    }

    $sql = "select $tbl.id as `tcid`, users.id as `site_id`, users.name as `site`, date_format($tbl.date_time, '%d-%M-%Y %H:%i') as `tt`, " . (!$wfc ? "$tbl.activity_type, " : "") . " $tbl.date_time, $tbl." . ($wfc ? "staff_" : "") . "comment, $tbl.latitude, $tbl.longitude from $tbl
            left join users on users.id = $tbl.site_id
            where $tbl.employee_id = " . $_SESSION['user_id'] . " order by date_time DESC LIMIT " . (!$wfc ? "6" : "10");
      // $str .= "<h3>$sql</h3>";     
    $result = $this->dbi->query($sql);
    $latest_check = 0;
    if($wfc) {
      $ac_type = "WFC";
      $ac_type_tmp = "WFC";
    }
    while($myrow = $result->fetch_assoc()) {
      $id_sel = $myrow['tcid'];
      $comment = $myrow[($wfc ? "staff_" : "") . 'comment'];
      $site = $myrow['site'];
      $comment = "<div class=\"cl\"></div><br /><textarea placeholder=\"Comment\" name=\"txaComment_$id_sel\" style=\"$text_style;\">$comment</textarea> ";

      if(!$wfc) {
        $ac_type = $myrow['activity_type'];
        //  $str .= "<h3>test ".$myrow['site_id']." - ".$myrow['site']." - ".$myrow['activity_type']."</h3>";
        if(!$ac_type_tmp) {
          $ac_type_tmp = $ac_type;
          if($ac_type == 'ON') {
            $site_id_on = $myrow['site_id'];
            $site_name = $site;
          }
        }
      }
      if(!$latest_check) {
        $latest_check = $myrow['date_time'];
        $last_site_id = $myrow['site_id'];
        $last_lat = $myrow['latitude'];
        $last_long = $myrow['longitude'];
      }
      $str_xtra .= '<div class="sign-wrap">
               <div class="fl">' . $codes[$ac_type] . ' @ ' . $myrow['tt'] . ' @ ' . $site . '</div>
               <div class="fr"><input type="button" style="' . $font_size . '" onClick="del_item(\''.$id_sel.'\')" value="X Delete" /></div>
               ' . $comment . '</div>';
    }

    if($_POST['hdnLatitude']) {
      $sql = "select usermeta.meta_value, users.name as `site`, users.id as `site_id` from usermeta
              left join users on users.id = usermeta.user_id
              where (usermeta.meta_key = 93 or usermeta.meta_key = 94)
              order by users.name, meta_key";
      $result_gps = $this->dbi->query($sql);
      $cnt = 0;
      $found = 0;
      while($myrow = $result_gps->fetch_assoc()) {
        if($cnt % 2) {
          $long = $myrow['meta_value'];
          if(is_numeric($lat) && is_numeric($long) && is_numeric($_POST['hdnLatitude']) && is_numeric($_POST['hdnLongitude'])) $distance = $this->distance($lat, $long, $_POST['hdnLatitude'], $_POST['hdnLongitude']);
          $dist_check = ($myrow['site'] == 'ADELAIDE SHORES' ? 5000 : 1000);
            //$str .= "<h3>".$myrow['site']. " -- " . $_POST['hdnLatitude'] . ", " . $_POST['hdnLongitude'] . ", $lat, $long $distance - $dist_check</h3>";
          if($distance < $dist_check) {
            $found = 1;
            $site_id = $myrow['site_id'];
            $site_ids[] = $site_id;
            //if($site_id == $last_site_id) $last_site_id = 0;
            $site_name = $myrow['site'];
            if($wfc) {
              $clock_str .= '<input onClick="clock_it(\'WFC\', '.$site_id.');" type="button" class="wfc" value="Welfare Check @ '.$site_name.'" />';
            } else {
              if($ac_type_tmp == "OFF" || !$ac_type)  {
                $clock_str .= '<input onClick="clock_it(\'ON\', '.$site_id.');" type="button" class="clock_on" value="Sign ON @ '.$site_name.'" />';
              } else {
                if($site_id == $site_id_on) $clock_str .= '<input onClick="clock_it(\'OFF\', '.$site_id.');" type="button" class="clock_off" value="Sign OFF @ '.$site_name.'" />';
              }  
            }  
          }
        } else {
          $lat = $myrow['meta_value'];
        }
        $cnt++;
      }
    }
    //$str .= (array_search($last_site_id, $site_ids) === false ? "yes" : "no");
    if($last_site_id && (!$site_ids || array_search($last_site_id, $site_ids) === false)) {
      $sql = "select name from users where id = $last_site_id";
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $site_name = $myrow['name'];
        $site_id = $last_site_id;
        $lat = $last_lat;
        $long = $last_long;
    //    $clock_str .= '<input onClick="clock_it(\'WFC\', '.$site_id.');" type="button" class="wfc" value="Welfare Check @ '.$site_name.'" />';
        if($wfc) {
          $clock_str .= '<input onClick="clock_it(\'WFC\', '.$site_id.');" type="button" class="wfc" value="Welfare Check @ '.$site_name.'" />';
        } else {
          if($ac_type_tmp == "OFF" || !$ac_type)  {
            $clock_str .= '<input onClick="clock_it(\'ON\', '.$site_id.');" type="button" class="clock_on" value="Sign ON @ '.$site_name.'" />';
          } else {
            if($site_id == $site_id_on) $clock_str .= '<input onClick="clock_it(\'OFF\', '.$site_id.');" type="button" class="clock_off" value="Sign OFF @ '.$site_name.'" />';
          }  
        }  
        $found = 1;
      }
    }


    //$clock_str .= ($wfc_str ? "<br /><br /><hr /><h3>Welfare Checks</h3><hr />" . $wfc_str : "") . $str_xtra;
    $clock_str .= $str_xtra;



    if($id_sel) $clock_str .= '<input onClick="save_comments();" type="button" class="clock_button" value="Save Comments" />';

    $page_to_load = "Placeholder";
    $check_interval = "180000";

    $str .= '<script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
              <script type="text/javascript">
              load_page("' . $page_to_load . '");
              setInterval(function () {load_page("' . $page_to_load . '")}, ' . $check_interval . ');
              </script>';

    if($msg) $str .= $this->message($msg, 2000);


    $str .= '<input type="hidden" name="hdnFlag" id="hdnFlag" value="' . $_POST['hdnFlag'] . '" />
              <input type="hidden" name="hdnSiteId" id="hdnSiteId" />
              <input type="hidden" name="delid" id="delid" />
              <input type="hidden" name="hdnSaveComments" id="hdnSaveComments" />';

//    $str .= '<div class="fr"><a class="list_a" href="Clock'
  //       . ($show_min ? "?show_min=1" . ($is_signon ? "&wfc=2" : "&signon_off=1") : ($is_signon ? "?wfc=1" : "?signon_off=1")) . '">Go To '.($is_signon ? "Welfare Check Mode" : "Signon/Off Mode").'</a></div>';

    $str .= '<h3 class="fl" style="' . $font_size . '; margin-right: 30px;">' . ($wfc ? "Welfare Checks" : "Sign On/Off") . '</h3>';
    if($_POST['hdnLatitude']) { 
      $str .= '<span class="fl help_message" style="' . $font_size . '">GPS: ' . round($_POST['hdnLatitude'],5) . ", " . round($_POST['hdnLongitude'], 5) . '</span>';
    }

    //$str .= '<div class="cl"></div>';

    $search = (isset($_POST['txtSiteSearch']) ? $_POST['txtSiteSearch'] : null);

    if($ac_type_tmp != 'ON') {
      $site_search = '<div class="cl"></div><br /><h3><div class="fl" style="margin-top: 6px; margin-right: 12px;">'
      . ($found ? 'Wrong Site Below?' : "")
      . ' Find a Site: </div><div class="fl"><input maxlength="50" name="txtSiteSearch" id="search" type="text" class="search_box" placeholder="Enter site name here..." />
          <input type="submit" name="cmdSiteSearch" value="Search" class="search_button" /></div></h3>
            <div class="cl"></div><br />';
    }

    if($search) {
      $num_sites = 0;
      $search = "
        where (users.name LIKE '%$search%'
        or users.surname LIKE '%$search%'
        or users.email LIKE '%$search%'
        or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
        and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')";
      $result = $this->dbi->query($sql);
      $sql = "
                select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `name` from users
                left join states on states.id = users.state
                inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users' and lookup_answers.lookup_field_id = 384
                $search";
      $result = $this->dbi->query($sql);
      while($myrow = $result->fetch_assoc()) {
        $num_sites++;
        $site_search .= '<p>' 
                        . ($wfc ? '<input onClick="clock_it(\'WFC\', '.$myrow['idin'].');" type="button" class="wfc" value="Welfare Check @ '.$myrow['name'].'" />'
                           : '<input onClick="clock_it(\'ON\', '.$myrow['idin'].');" type="button" class="clock_on" value="Sign ON @ '.$myrow['name'].'" />')
                        . '</p>';
      }
      if(!$num_sites) $site_search .= "<h3 style=\"color: red;\">No Sites Found, Please Try Again...</h3>";
    }


    $str .= '<h3 class="time_message">Last ' . ($ac_type_tmp == "OFF" || !$ac_type ? "Signed OFF" : ($ac_type == 'WFC' ? "Check" : "Signed ON")) . ': <span id="lblTimeDiff"></span></h3><br />';

    if($found) $str .= $site_search;

    if($search) {
      $str .= $site_search;
    } else {
      $str .= ($found ? '<textarea placeholder="Comment to Add (Optional)" style="' . $text_style . '" name="txtComment"></textarea>'
                   : '<div class="cl"></div><div class="fl"><h3 >No Sites Found </h3>' . $site_search . '</div>');
    }

    $str .= '
    <span id="myDiv"></span>
    <div align="left">' . $clock_str . '
    <script>';
    if($latest_check) {
      $str .= 'var then = moment("' . $latest_check . '");
      setInterval(function () { document.getElementById("lblTimeDiff").innerHTML=get_time_diff(moment(), then) }, 60001);
      document.getElementById("lblTimeDiff").innerHTML = get_time_diff(moment(), then);';
    } else {
      $str .= 'document.getElementById("lblTimeDiff").innerHTML = "None completed..."';
    }
    $str .= '</script>
             </div><div class="cl"></div>';
    return $str;
  }
  
  function WelfareDashboard() {
    $on_off = (isset($_GET['on_off']) ? $_GET['on_off'] : null);
    $tmp_meta_title = ($on_off ? "Signon/Off" : "Welfare") . " Dashboard";
    $controller_confirm_id = (isset($_POST['controller_confirm_id']) ? $_POST['controller_confirm_id'] : null);
    if($controller_confirm_id) {
      if($on_off) {
        $msg = "Sign$on_off Confirmed...";
      } else {
        $sql = "update wfc set controller_confirmed = 1 where id = $controller_confirm_id";
        $msg = "Welfare Check Confirmed...";
      }
      $str .= $this->message($msg, 3000);
      $result = $this->dbi->query($sql);
    }
    $str .= '
    <input type="hidden" name="controller_confirm_id" id="controller_confirm_id" />
    <div class="help" id="help">
    <img src="' . $this->f3->get('img_folder') . 'welfare_dashboard.png" />
    <p><a class="list_a" href="DownloadFile?fl=uhvrxufhv2Jxlghv&f=Zhoiduh%23Gdvkerdug%23Xvhu%23Jxlgh1sgi">Download User guide Here...</a></p>
    </div>
    <div id="warning_message"></div>
    <style>
      .help {
        display: none;
        border: 1px solid #990000;
        padding: 12px;
      }
    </style>
    <script type="text/javascript">
      function confirm_check(wfc_id) { 
        document.getElementById("controller_confirm_id").value = wfc_id
        document.frmEdit.submit()
      }
      load_page("WelfareDashboardShow' . ($on_off ? "?on_off=1&" : "?") . 'show_min=1");
      setInterval(function () {load_page("WelfareDashboardShow' . ($on_off ? "?on_off=1&" : "?") . 'show_min=1")}, 5000);
      
      function show_hide(id) {
        if(document.getElementById(id).style.display == "block") {
          document.getElementById(id).style.display = "none";
        } else {
          document.getElementById(id).style.display = "block";
        }
      }
    </script>
    ';
    
    $this->f3->set('show_min', 1);
    $this->f3->set('content', $str);
    $this->f3->set('css', 'main.css');
    $template = new Template;
    echo $template->render('layout.htm');
    
  }
      
  function time_diff_minutes($time1, $time2) {
    $datetime1 = new DateTime($time1);
    $datetime2 = new DateTime($time2);
    $interval = $datetime1->diff($datetime2);
    return (($interval->format('%d') * 1440) + ($interval->format('%H') * 60) + $interval->format('%i')) * ($datetime1 > $datetime2 ? 1 : -1);
  }
  function WelfareDashboardShow() {
    
    $on_off = (isset($_GET['on_off']) ? $_GET['on_off'] : null);
    $str .= '
    <style>
    .cell_wrap {
      float: left;
      display: inline-block;
      padding: 0px;  
      border-top: 1px solid #DDDDDD;
      border-left: 0px;
      border-bottom: 1px solid #DDDDDD;
      border-right: 1px solid #DDDDDD;
      margin: 0px;
      font-size: 13pt;  margin-bottom: 0px;
    }
    .warning_message {
      display: block;
      border: 3px solid red;
      padding: 5px;
      margin-top: 6px;
      margin-bottom: 6px;
      background-color: #FFFFFF;
    }
    .NSW { border-left: 10px solid #5CA1ED !important; }
    .VIC { border-left: 10px solid #213965 !important; }
    .QLD { border-left: 10px solid #79001F !important; }
    .TAS { border-left: 10px solid #004C3D !important; }
    .SA { border-left: 10px solid #DE0000 !important; }
    .NT { border-left: 10px solid #C0C0C0 !important; }
    .WA { border-left: 10px solid #FFCC00 !important; }
    .CNSW { color: #5CA1ED !important; }
    .CVIC { color: #213965 !important; }
    .CQLD { color: #79001F !important; }
    .CTAS { color: #004C3D !important; }
    .CSA { color: #DE0000 !important; }
    .CNT { color: #C0C0C0 !important; }
    .CWA { color: #FFCC00 !important; }
    .cell_wrap:hover {
      border-bottom: 1px solid black;
    }
    .cell_head { padding: 1px 5px 1px 5px;  background-color: white; }
    .cell_foot { padding: 1px 5px 1px 5px;  background-color: #F9F9F9;  border-top: 1px solid #DDDDDD; text-align: center; }
    .missed_time {  display: inline-block; margin-right: 15px; margin-bottom: 4px; background-color: transparent; font-size: 18pt}
    .first_head, .first_foot {
      text-align: left !important;
      /*background-color: #FFFFEE;*/
      color: #000066;
      width: 500px;
    }
    .plain_btn {
      text-decoration: none !important;
      border: none !important;
      background-color: transparent !important;
      padding: 0px !important;
      font-size: 13pt !important;
      cursor: pointer; cursor: hand;
    }
    .plain_btn {
      text-decoration: none !important;
    }
    #footer {position: relative !important;}
    /*.cell_start { width: 450px; }*/
    /*.cell_time { width: 65px; }*/
    </style>
    <script>
    </script>';

    $confirm_time = (isset($_GET['confirm_time']) ? $_GET['confirm_time'] : null);
    $confirm_id = (isset($_GET['confirm_id']) ? $_GET['confirm_id'] : null);

    if($confirm_time && $confirm_id) {
      $is_confirmed = (isset($_POST['is_confirmed']) ? $_POST['is_confirmed'] : null);
      $txtGuardComment = (isset($_POST['txtGuardComment']) ? $this->dbi->real_escape_string($_POST['txtGuardComment']) : "");
      $txtControlRoomComment = (isset($_POST['txtControlRoomComment']) ? $this->dbi->real_escape_string($_POST['txtControlRoomComment']) : "");
      if($is_confirmed == 1) {  
        $sql = "select distinct(meta_value) from usermeta where (meta_key = 93 or meta_key = 94) and user_id = $confirm_id order by meta_key;";
        $result = $this->dbi->query($sql);
        $myrow = $result->fetch_assoc();
        $lat = $myrow['meta_value'];
        $myrow = $result->fetch_assoc();
        $long = $myrow['meta_value'];
        if($on_off) {
          $sql = "insert into time_checks(staff_comment, controller_comment, employee_id, date_time, latitude, longitude, ip_address, site_id, activity_type) values ('$txtGuardComment', '$txtControlRoomComment', $confirm_id, '$confirm_time', '$lat', '$long', '".$_SERVER['REMOTE_ADDR']."', '$confirm_id', '$on_off');";
        } else {
          $sql = "insert into wfc(staff_comment, controller_comment, employee_id, date_time, latitude, longitude, ip_address, site_id) values ('$txtGuardComment', '$txtControlRoomComment', $confirm_id, '$confirm_time', '$lat', '$long', '".$_SERVER['REMOTE_ADDR']."', '$confirm_id');";
        }
        $result = $this->dbi->query($sql);
      } else if(!$is_confirmed) {
        $sql = "select name from users where id = $confirm_id;";
        $result = $this->dbi->query($sql);
        $myrow = $result->fetch_assoc();
        $site = $myrow['name'];
        $str .= '
          <style>
            body {
              background-color: white !important;
              font: 12pt arial;
            }

            input[type=button], input[type=submit], .button_a {
              -webkit-appearance: none; 
              color: #333333;
              background-color: #B2C0DD;
              border: 1px solid;
              border-color: #DDDDDD;
              padding: 8px; font-size: 13pt;
              font-weight: normal !important;
            }

            input[type=button]:hover, input[type=submit]:hover {
              background-color: #C0D0EA;
              border-color: #FFFFCC;
            }

            input[type=button]:active, input[type=submit]:active {
              border-color: #AAAAAA #EEEEEE #EEEEEE #AAAAAA;
            }
          </style>
          <script>
            function make_changes(val) {
              document.getElementById("is_confirmed").value = val
              document.frmEdit.submit();
            }
          </script>
          <form method="POST" name="frmEdit">
          <input type="hidden" name="is_confirmed" id="is_confirmed">
          <h3>Site: '.$site.'</h3><h3>Check Date/Time: '.date("d/M/Y @ H:i", strtotime($confirm_time)).'</h3>
          <h3>Control Room Comment (Optional):</h3>
          <textarea name="txtControlRoomComment" style="width: 98%; height: 150px;"></textarea>
          <h3>Guard Comment (Optional):</h3>
          <textarea name="txtGuardComment" style="width: 98%; height: 150px;"></textarea>
          <input type="button" value="Mark as Checked and/or Save Notes" onClick="make_changes(1)" /> <input type="button" value="Cancel" onClick="make_changes(2)" />
        ';
        echo $str;
        exit;
      }
      if($is_confirmed) $this->f3->reroute('WelfareDashboard');

    }

/*    if($confirm_time && $confirm_id) {
      $sql = "select distinct(meta_value) from usermeta where (meta_key = 93 or meta_key = 94) and user_id = $confirm_id order by meta_key;";
      $result = $this->dbi->query($sql);
      $myrow = $result->fetch_assoc();
      $lat = $myrow['meta_value'];
      $myrow = $result->fetch_assoc();
      $long = $myrow['meta_value'];
      if($on_off) {
        $sql = "insert into time_checks(employee_id, date_time, latitude, longitude, ip_address, site_id, activity_type) values ($confirm_id, '$confirm_time', '$lat', '$long', '".$_SERVER['REMOTE_ADDR']."', '$confirm_id', '$on_off');";
      } else {
        $sql = "insert into wfc(employee_id, date_time, latitude, longitude, ip_address, site_id) values ($confirm_id, '$confirm_time', '$lat', '$long', '".$_SERVER['REMOTE_ADDR']."', '$confirm_id');";
      }
      $result = $this->dbi->query($sql);
    }*/


    //$str .= $this->redirect("welfare_dashboard.php" .  ($on_off ? "?on_off=1" : ""));
    $state_array = array('NSW', 'ACT', 'VIC', 'TAS', 'QLD', 'SA', 'WA', 'NT');
    $capital['NSW'] = "Sydney";    $capital['ACT'] = "Canberra";    $capital['VIC'] = "Melbourne";    $capital['TAS'] = "Hobart";
    $capital['QLD'] = "Brisbane";    $capital['SA'] = "Adelaide";    $capital['WA'] = "Perth";    $capital['NT'] = "Darwin";
    $header .= '<div style="margin: 0px; padding: 0px; padding-bottom: 0px; font-size: 32px; color: #000066; font-weight: bold; float: left;">'. ($on_off ? 'SIGNON/OFF' : 'WELFARE') . ' DASHBOARD</div>';
    $ts = "NSW, ACT, VIC, TAS: ";
    foreach ($state_array as $st) {
      if($st == 'NSW' || $st == 'ACT' || $st == 'VIC' || $st == 'TAS') {
        if($st == 'NSW') {
//          date_default_timezone_set('NSW');
          date_default_timezone_set('Australia/Sydney');
          $ts .= date("d/m H:i");
        }
      } else {
        if($st != 'NT') {
          date_default_timezone_set("Australia/{$capital[$st]}");
          $ts .= " | $st: " . date("d/m H:i");
        }
      }
    }
    $ts .= ' &nbsp;<input class="list_a" type="button" id="cmdHints" name="cmdHints" onClick="show_hide(\'help\')" value="Hints" />';
    $header .= '<div style="margin: 0px; padding: 0px; font-size: 18px; color: #000066; font-weight: bold; float: right;">'.$ts.'</div><div class="cl"></div>';
    foreach ($state_array as $st) {
      date_default_timezone_set("Australia/{$capital[$st]}");
      $curr_time = date("H:i");  $curr_date = date("Y-m-d");
      $curr_day = date("l");  $previous_day = date("l", strtotime("-1 days"));
      $yesterday = date("Y-m-d", strtotime("-1 days"));  $tomorrow = date("Y-m-d", strtotime("+1 days"));
      if($on_off) {
        $sql = "select time_checks.id, time_checks.employee_id, time_checks.date_time, time_checks.latitude, time_checks.longitude, time_checks.comment, user_states.state, time_checks.site_id, time_checks.activity_type from time_checks
                left join user_states on user_states.id = time_checks.employee_id
                where user_states.state = '$st' and (date(date_time) = '$curr_date' or date(date_time) = '$yesterday') order by date_time DESC";
      } else {
        $sql = "select wfc.id, wfc.employee_id, wfc.date_time, wfc.latitude, wfc.longitude, wfc.staff_comment, wfc.controller_comment, user_states.state, wfc.controller_confirmed, wfc.site_id from wfc
                left join user_states on user_states.id = wfc.employee_id
                where user_states.state = '$st' and (date(date_time) = '$curr_date' or date(date_time) = '$yesterday') order by date_time DESC";
      }
      $result = $this->dbi->query($sql);
      unset($check_ids);
      unset($emp_ids);
      unset($site_ids);
      unset($date_times);
      unset($staff_comments);
      unset($controller_comments);
      unset($controller_confirmed);
      while($myrow = $result->fetch_assoc()) {
        $check_ids[] = $myrow['id'];
        $emp_ids[] = $myrow['employee_id'];
        $site_ids[] = $myrow['site_id'];
        $date_times[] = $myrow['date_time'];
        $staff_comments[] = $myrow['staff_comment'];
        $controller_comments[] = $myrow['controller_comment'];
        $controller_confirmed[] = $myrow['controller_confirmed'];
        if($on_off) $activity_types[] = $myrow['activity_type'];
      }
      $sql =  "select site_id, opening_time, closing_time, check_period, users.name as `site`, users.phone, user_states.state, lookup_fields.item_name as `day` from opening_closing
                left join lookup_fields on lookup_fields.id = opening_closing.day_of_week_id
                inner join users on users.id = opening_closing.site_id
                left join user_states on user_states.id = opening_closing.site_id
                where (user_states.state = '$st' and (lookup_fields.item_name = '$curr_day' or lookup_fields.item_name = '$previous_day')) and opening_closing.check_period != 0 and opening_closing.check_period " . ($on_off ? ">" : "<=") . " 180 order by user_states.state, users.name";
      //$str .= "<h3>$sql</h3>";
      $result = $this->dbi->query($sql);
      if($result->num_rows) {
        $wfc_times = Array();
        while($myrow = $result->fetch_assoc()) {
          $site_id = $myrow['site_id'];
          $site = $myrow['site'];
          $opening_time = $myrow['opening_time'];
          $start_time = $opening_time;
          $closing_time = $myrow['closing_time'];
          $finish_time = $closing_time;
          $check_period = $myrow['check_period'];
          $state = $myrow['state'];
          $phone = $myrow['phone'];
          $day = $myrow['day'];
          if(strtotime("$curr_date $opening_time") > strtotime("$curr_date $closing_time")) {
            $td_split = $this->time_diff_minutes("$curr_date $start_time", "$curr_date $finish_time") / 2;
              if(strtotime("$curr_date $curr_time") < strtotime("-$td_split minutes", strtotime("$curr_date $opening_time"))) {
              $opening_time = strtotime("$yesterday $opening_time");
              $closing_time = strtotime("$curr_date $closing_time");
              $day_compare = $curr_day;
            } else {
              $opening_time = strtotime("$curr_date $opening_time");
              $closing_time = strtotime("$tomorrow $closing_time");
              $day_compare = $curr_day;
            }
          } else {
            $opening_time = strtotime("$curr_date $opening_time");
            $closing_time = strtotime("$curr_date $closing_time");
            $day_compare = $curr_day;
          }
          if($day == $day_compare) {
            unset($wfc_times);
            $wfc_times[] = $opening_time;
            $finished = 0;
            $cnt = 0;
            while(!$finished) {
              $wfc_times[] = strtotime("+$check_period minutes", $wfc_times[$cnt]);
              $cnt++;
              if($wfc_times[$cnt] > $closing_time) $wfc_times[$cnt] = $closing_time;
              $finished = ($wfc_times[$cnt] >= $closing_time ? 1: 0);
            }
            
            
            $cnt = 0;
            unset($wfc_checks);
            unset($wfc_comments);
            $emp_name = "*";
            if(!empty($emp_ids)) {
              foreach ($emp_ids as $emp_id) {
                if($site_id == $site_ids[$cnt]) {
                  $wfc_checks[] = strtotime($date_times[$cnt]);
                  if($staff_comments[$cnt]) $cmnts .= "Guard Comment: " . $staff_comments[$cnt];
                  if($staff_comments[$cnt] && $controller_comments[$cnt]) $cmnts .= '&#13;&#13;';
                  if($controller_comments[$cnt]) $cmnts .= "Control Room Comment: " . $controller_comments[$cnt];
                  $wfc_comments[] = $cmnts;
                  $cmnts = "";
                  $emp_id_tmp = $emp_id;
                }
                $cnt++;
              }
            }
            $last_wfc = ($wfc_checks[0] ? $wfc_checks[0] : "");
            if($last_wfc && $emp_id_tmp) {
                    $sql = "select concat(name, ' ', surname) as `emp_name`, users.phone, users.email from users where id = $emp_id_tmp";
              $result2 = $this->dbi->query($sql);
              while($myrow2 = $result2->fetch_assoc()) {
                    $emp_name = $myrow2['emp_name'] . " (" . $myrow2['phone'] . ")";
              }
            }
            
            $sql = "select companies.item_name from users_user_groups 
            inner join companies on companies.id = users_user_groups.user_group_id
            where user_id = '$site_id' ";
            //and user_group_id in (select id from companies);
            if($result2 = $this->dbi->query($sql)) {
              $num_rows = $result2->num_rows;
              while($myrow2 = $result2->fetch_assoc()) {
                $site .= " (" . substr($myrow2['item_name'], 0, 3) . ")";
              }
            }
            
            
            $str .= '
                  <div class="cell_wrap cell_start">
                  <div class="cell_head first_head '.$state.'"><a name="'.$site_id.'"></a>'.$this->shorten($site,42).' ('.$state.')'.($phone ? "<span style=\"font-size: 12pt; color: #990000;\"> [$phone]</span>" : "").'</div>
                  <div class="cell_foot first_foot '.$state.'">'.$emp_name.'</div>
                  </div>';
            $tcount = 0;
            //print_r($wfc_checks);
            //exit;
            foreach ($wfc_times as $wfc_time) {
              $hit = 0;
              $bg_col = "";
              $clock_time = "*";
              $time_diff_curr = $this->time_diff_minutes($curr_time, date("Y-m-d H:i", $wfc_time));
              $cnt = 0;
              if(is_array($wfc_checks)) {
                foreach ($wfc_checks as $wfc_check) {
                  $time_diff = $this->time_diff_minutes(date("Y-m-d H:i", $wfc_check), date("Y-m-d H:i", $wfc_time));
                  if(abs($time_diff) < $check_period/2) {
                    $hit = 1;
                    $bg_col = 'style="background-color: #9EE5C2;"';
                    $clock_time = '<span ' . ($wfc_comments[$cnt] ? 'title="'.$wfc_comments[$cnt].'"' : "") . '><input class="plain_btn" ' . (!$controller_confirmed[$cnt] ? ' onClick="confirm_check('.$check_ids[$cnt].')" ' : '') . ' type="button" value="' . date("H:i", $wfc_check) . ($controller_confirmed[$cnt] ? '*' : '') . '" /></span>';
                    break;
                                }
                  if(abs($time_diff) > 15 && $wfc_time < strtotime($curr_time)) {
                    $bg_col = 'style="background-color: #FFA092;"';
                    $clock_time = '<span style="font-size: 14px !important; font-weight: bold;"><a href="WelfareDashboardShow?confirm_time='.urlencode(date("Y-m-d H:i:s", $wfc_time)).'&confirm_id='.$site_id.($on_off ? "&on_off=" . ($tcount ? "OFF" : "ON") : "").'">Missed</a></span>';
                  }
                  $cnt++;
                }
              }
              if(!$last_wfc && $wfc_time < strtotime($curr_time)) {
                $bg_col = 'style="background-color: #FFA092;"';
                $clock_time = '<span style="font-size: 14px !important; font-weight: bold;"><a href="WelfareDashboardShow?confirm_time='.urlencode(date("Y-m-d H:i:s", $wfc_time)).'&confirm_id='.$site_id.($on_off ? "&on_off=" . ($tcount ? "OFF" : "ON") : "").'">Missed</a></span>';
              }
              if(abs($time_diff_curr) < 15 && !$hit) {
                $bg_col = 'style="background-color: #FFD38E;"';
                $clock_time = '<span style="font-size: 13.5px !important; font-weight: bold;"><a href="WelfareDashboardShow?confirm_time='.urlencode(date("Y-m-d H:i:s", $wfc_time)).'&confirm_id='.$site_id
                .($on_off ? "&on_off=" . ($tcount ? "OFF" : "ON") : "").'">Pending</a></span>';
              }
              $str .= '<div class="cell_wrap cell_time">
              <div class="cell_head">'.date("H:i", $wfc_time).'</div><div class="cell_foot" '.$bg_col.'>'.$clock_time.'</div>
              </div>';
              if($time_diff_curr >= 15 && $time_diff_curr <= 45 && !$hit) {
                $warnings .= " <div class=\"cell_head missed_time C$state\">$site ($state) @ ".date("H:i", $wfc_time)." <a style=\"font-size: 18pt;\" href=\"".$this->f3->get('main_folder')."#$site_id\">V</a></div>";
              }
              $tcount++;
            }
          }
          $str .= ($on_off ? '<div class="cl"></div>' : '<div class="cl"></div>');
        }
      }
    }
    echo $header . ($warnings ? '<div class="warning_message">MISSED CHECKS &gt;&gt; ' . $warnings . '</div>' : "") . $str;
    //echo $str;
  }
  
  function ClockEditor() {
    $edit = $_REQUEST['edit'];
    $show_pos = $_GET['show_pos'];
    if($show_pos) {
      $sql = "select latitude, longitude from time_checks where id = " . $_GET['show_pos'] . ";";
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $latitude = $myrow['latitude'];
        $longitude = $myrow['longitude'];
        $str .= '<h3>Map</h3><p><img src="http://maps.googleapis.com/maps/api/staticmap?center='.$latitude.','.$longitude.'&zoom=16&size=640x640&sensor=false&maptype=hybrid&markers=color:blue%7Clabel:S%7C'.$latitude.','.$longitude.'" /></p>';
      }
    }
    if($edit) {
      $hdnSaveClock = $_POST['hdnSaveClock'];
      $hdnAddClock = $_POST['hdnAddClock'];
      $hdnDeleteClock = $_POST['hdnDeleteClock'];
      $uid = $_GET['uid'];
      if($hdnSaveClock || $hdnAddClock) {
        $txtDate = $_POST['txtDate'];
        $txtTime = $_POST['txtTime'];
        $txtType = $_POST['txtType'];
        $txtComment = $_POST['txtComment'];
        $date_time = Date("Y-m-d", strtotime($txtDate)) . " $txtTime";
        if($hdnAddClock) {
          $sql = "insert into time_checks (date_time, activity_type, comment, employee_id, latitude, longitude, ip_address) select '$date_time', '$txtType', '$txtComment', '$uid', latitude, longitude, ip_address from time_checks where id = $edit";
          $this->dbi->query($sql);
          $last_id = $this->dbi->insert_id;
        } else if($hdnSaveClock) {
          $sql = "update time_checks set date_time = '$date_time', activity_type = '$txtType', comment = '$txtComment' where id = $edit";
          $this->dbi->query($sql);
        }
      } else if($hdnDeleteClock) {
          $sql = "delete from time_checks where id = $edit";
          $this->dbi->query($sql);
          $edit = 0;
      }
    }
    if(!$edit && !$show_pos) {
      $nav = new navbar;
      $hdnReportFilter = $_REQUEST['hdnReportFilter'];
      if($hdnReportFilter) {
        $month_select = $_REQUEST["selDateMonth"];
        $year_select = $_REQUEST["selDateYear"];
        $staff_select = $_REQUEST["selStaff"];
        if($month_select > 0) {
          $filter_string .= " and MONTH(time_checks.date_time) = $month_select ";
        }
        if($year_select > 0) {
          $filter_string .= " and YEAR(time_checks.date_time) = $year_select ";
        }
        $filter_string .= " and users.id = $staff_select ";
      }
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
        '.$nav->month_year(2018).' Staff Member: <select name="selStaff">';
        $sql =  "select users.id, users.employee_id, CONCAT(users.name, ' ', users.surname) as `name` from users
                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
                 where users.user_status_id = (select id from user_status where item_name = 'ACTIVE') order by CONCAT(users.name, ' ', users.surname);";
        $result = $this->dbi->query($sql);
        while($myrow = $result->fetch_assoc()) {
          $uid = $myrow['id'];
          $employee_id = $myrow['employee_id'];
          $name = $myrow['name'];
          if($uid == $staff_select) {
            $show_selected = "selected";
          } else {
            $show_selected = "";
          }
          $str .= "<option $show_selected value=\"$uid\">[$employee_id] $name</option>";
        }
      $str .= '</select>
        <input onClick="report_filter()" type="button" value="Go" /> 
        </div>
        </div>
        </form>
      ';
    }
    $current_qry = $_SERVER['QUERY_STRING'];
    if($edit) {
      $str .= '<input type="hidden" name="edit" value="'.$edit.'">
            <input type="hidden" name="uid" value="'.$uid.'">
            <input type="hidden" name="hdnAddClock" id="hdnAddClock">
            <input type="hidden" name="hdnSaveClock" id="hdnSaveClock">
            <input type="hidden" name="hdnDeleteClock" id="hdnDeleteClock">
      <script language="JavaScript">
        function add_clock() {
          document.getElementById("hdnAddClock").value = 1
          document.frmEdit.submit()
        }
        function save_clock() {
          document.getElementById("hdnSaveClock").value = 1
          document.frmEdit.submit()
        }
        function delete_clock() {
          var confirmation;
          confirmation = "Are you sure about deleting this record?";
          if (confirm(confirmation)) {
            document.getElementById("hdnDeleteClock").value = 1
            document.frmEdit.submit()
          }
        }
      </script>';
      $txt_itm = new input_item;
      $str .= $txt_itm->setup_ti2();
      $str .= $txt_itm->setup_cal();
      $sql = "select id, date_time, activity_type, comment
            FROM time_checks
            where time_checks.id = $edit
      ";
      $result = $this->dbi->query($sql);
      if($myrow = $result->fetch_assoc()) {
        $tcid = $myrow['id'];
        $date_time = $myrow['date_time'];
        $date = Date("d-M-Y", strtotime($date_time));
        $time = Date("H:i", strtotime($date_time));
        $activity_type = $myrow['activity_type'];
        $comment = $myrow['comment'];
      }
      $str .= '<div class="form-wrapper">
            <div class="form-header">Edit</div>
            <div  style="padding: 10px;">
            <div class="fl">Date<br />' . $txt_itm->cal("txtDate", "", ' value="'.$date.'" ', "", "", "") . '</div>
            <div class="fl">Time<br />' . $txt_itm->ti2("txtTime", "", ' value="'.$time.'" ', "", "", "") . '</div>
            <div class="fl">Type<br />' . $txt_itm->txt("txtType", "", ' value="'.$activity_type.'" ', "", "", "") . '</div>
            <div class="fl">Comment<br />' . $txt_itm->txt("txtComment", "", ' value="'.$comment.'" ', "", "", "") . '</div>
            <div class="fl"><br />
            <input type="button" value="Save" onClick="save_clock()" />
            <input type="button" value="Add as New" onClick="add_clock()" />
            <input type="button" value="Delete" onClick="delete_clock()" /><br /><br />
            </div>
            </div>
            <div class="cl"></div>
            </div>
           ';
      $filter_string = " and time_checks.id = $edit ";
      if($last_id) $filter_string .= " or time_checks.id = $last_id ";
    }
    if($filter_string) {
    	$view_details = new data_list;
    	
    	$view_details->dbi = $this->dbi;
    	$view_details->title = "Clock Editor";
    	if(!$edit) $view_details->show_num_records = 1;
    	$view_details->sql = "
            select time_checks.id as idin, time_checks.date_time as `Date/Time`, CONCAT(users.`name`, ' ', users.surname) as `Employee`, time_checks.activity_type as `Activity Type`, time_checks.latitude as `Latitude`, time_checks.longitude as `Longitude`,
            time_checks.comment as `Comment`,
            CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."ClockEditor?edit=', time_checks.id, '&uid=', users.id, '\">Edit</a>') as `Edit`,
            CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"http://www.citymaps.ie/create-google-map/map.php?width=100%&height=600&hl=en&coord=', time_checks.latitude, ',', time_checks.longitude, '&q=+()&ie=UTF8&t=&z=14&iwloc=A&output=embed\">Show Position</a>') as `Show Position`
            FROM time_checks
            left join users on users.id = time_checks.employee_id
            where 1 $filter_string
            order by date_time desc
    	";
    	$str .= $view_details->draw_list();
    }
    return $str;
  }
}
?>