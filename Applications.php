<?php
class Applications extends Controller {
  function Show() {
    $curr_date_time = date('Y-m-d H:i:s');
    $is_accounts = (array_search(422, $_SESSION['lids']) !== false ? 1 : 0);
    if($is_accounts) {
      $sql = "select state from users where id = " . $_SESSION['user_id'];
      if($result = $this->dbi->query($sql)) {
        if($myrow = $result->fetch_assoc()) {
          $acc_state = $myrow['state'];
        }
      }
    }
    $this->list_obj = new data_list;
    $leave_dashboard = (isset($_GET['leave_dashboard']) ? $_GET['leave_dashboard'] : null);
//    require_once("top".($leave_dashboard ? "_min" : "").".php");
    if($leave_dashboard) {
        $this->list_obj->sql = "
          select CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `<b>Staff Member on Leave</b>`,
          leave_request_dates.start_date as `<b>Starts</b>`, leave_request_dates.finish_date as `<b>Finishes</b>`
          from application_requests
          left join users on users.id = application_requests.requester_id
          left join application_status on application_status.id = application_requests.status_id
          left join compliance on compliance.id = application_requests.compliance_id
          inner join leave_request_dates on leave_request_dates.compliance_check_id = application_requests.compliance_check_id
          where compliance.title LIKE 'Leave Request%' and application_status.item_name like '%approved%'
          and DATE_FORMAT(STR_TO_DATE(leave_request_dates.start_date, '%d-%b-%Y'), '%Y-%m-%d') <= DATE_FORMAT(now(), '%Y-%m-%d') and DATE_FORMAT(STR_TO_DATE(leave_request_dates.finish_date, '%d-%b-%Y'), '%Y-%m-%d') >= DATE_FORMAT(now(), '%Y-%m-%d')
          and users.user_status_id = 30
       ";
       $str = "<h3>Staff on Leave</h3>" . ($this->list_obj->draw_list() ? $this->list_obj->draw_list() : "<i>No Staff Members Currently On Leave...</i>");
    } else {
      $str .= '<style>
        .appraisal_btn {
          font-size: 11pt !important;
          font-weight: bold !important;
          text-align: center;
          padding-top: 5px !important;
          padding-bottom: 5px !important;
          padding-left: 15px !important;
          padding-right: 15px !important;
          border-radius: 8px 8px 8px 8px;
          -moz-border-radius: 8px 8px 8px 8px;
          -webkit-border-radius: 8px 8px 8px 8px;
          border: 1px solid #DDDDDD !important;
          color: #333333;
          background-color: #B2C0DD;
          white-space: nowrap;
        }
        .approve {    background-color: #009900 !important;    color: white !important;  }
        .deny {    background-color: #990000 !important;    color: white !important;  }
        .cancel {    background-color: #330000 !important;    color: white !important;  }
        .approve:hover {    background-color: #00AA00 !important;  }
        .deny:hover {    background-color: #BB0000 !important;  }
        .cancel:hover {    background-color: #660000 !important;  }
       .select_a {
          font-size: 20px;
          display: block;
          padding: 20px;
          margin-bottom: 10px;
          width: 50%;
          background-color: #DDDDDD;
          border-top: 1px solid #CCCCCC;
          border-left: 1px solid #CCCCCC;
          border-bottom: 1px solid #CCCCCC;
          border-right: 1px solid #CCCCCC;
        }
        .select_a:hover {
          text-decoration: none !important;
          background-color: #DDEEEE;
          /* border: 1px solid #DDDDDD; */
        }
      </style>';
      $staff_id = (isset($_GET['staff_id']) ? $_GET['staff_id'] : null);
      $request_id = (isset($_GET['request_id']) ? $_GET['request_id'] : null);
      $process_mode = (isset($_GET['process_mode']) ? $_GET['process_mode'] : null);
      $report_mode = (isset($_GET['report_mode']) ? $_GET['report_mode'] : null);
      $my_applications = (isset($_GET['my_applications']) ? $_GET['my_applications'] : null);
      $selShowStat = (isset($_GET['selShowStat']) ? $_GET['selShowStat'] : ($my_applications || $report_mode ? 1 : ($is_accounts ? 20 : 10)));
      if($process_mode || $report_mode) {
        if(!$more_info) {
          $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : -1);
          $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : -1);
          $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
          if(!isset($_GET['selDateMonth']) && !isset($_GET['selDateYear']) && $report_mode) {
            $def_month = 1;
            $nav_month = date("m");
            $nav_year = date("Y");
          }
        }
        $hdnChangeItem = (isset($_REQUEST['hdnChangeItem']) ? $_REQUEST['hdnChangeItem'] : null);
        $hdnReportFilter = (isset($_REQUEST['hdnReportFilter']) ? $_REQUEST['hdnReportFilter'] : null);
        $txtReason = (isset($_REQUEST['txtReason']) ? $_REQUEST['txtReason'] : null);
        if($hdnChangeItem) {
          $hdnChangeStatus = (isset($_REQUEST['hdnChangeStatus']) ? $_REQUEST['hdnChangeStatus'] : null);
          $sql = "update application_requests set status_id = $hdnChangeStatus, " . ($is_accounts ? "accounts" : "manager") . 
                 "_reason='$txtReason', " . ($is_accounts ? "accounts_" : "") . 
                 "approval_date = '$curr_date_time' " . ($is_accounts ? ", accounts_id = " . $_SESSION['user_id'] : "") . " where id = " . $hdnChangeItem;
          $result = $this->dbi->query($sql);
          $str .= $this->message('Approval Status Changed...', 3000);
        }
        $more_info = (isset($_GET['more_info']) ? $_GET['more_info'] : null);
        $sql = "select id, item_name, colour from application_status order by id";
        $result = $this->dbi->query($sql);
        $stat_count = 0;
        $stat_selections .= '<option value="1">---------- ALL ----------</option>';
        while($myrow = $result->fetch_assoc()) {
          $stat_str = "CONCAT('<input type=\"button\" class=\"cs_button\" style=\"font-size: 10pt !important; color: ".$myrow['colour']." !important;\" value=\"".$myrow['item_name']."\" onClick=\"change_status(', application_requests.id, ', ".$myrow['id'].", \'".$myrow['item_name']."\')\" />')";
          $stat_str2 .= "$stat_xtra $stat_str";
          $stat_xtra = ", ";
          $stat_selections .= '<option ' . ($selShowStat == $myrow['id'] ? "selected" : "") . ' value="' . $myrow['id'] . '">'.$myrow['item_name'].'</option>';
          $stat_count++;
        }
        $stat_str = $stat_str2;
        $nav = new navbar;
        $str .= '
            <script language="JavaScript">
              function change_status(change_itm, status_in, status_txt) {
                var confirmation;
                confirmation = "Are you sure about changing the status this record to "+status_txt+"?";
                if (confirm(confirmation)) {
                  document.getElementById("hdnChangeItem").value = change_itm
                  document.getElementById("hdnChangeStatus").value = status_in
    //alert(document.getElementById("hdnChangeItem").value)
                  document.frmEdit.submit()
                }
              }
              function report_filter() {
                document.getElementById("hdnReportFilter").value=1
                document.frmFilter.submit()
              }
            </script>
            <input type="hidden" name="hdnChangeItem" id="hdnChangeItem" />
            <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />
            ';
        if(!$more_info) {
          $str .= '
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter" />
          <input type="hidden" name="process_mode" id="process_mode" value="'.$process_mode.'" />
          <input type="hidden" name="report_mode" id="report_mode" value="'.$report_mode.'" />
          <input type="hidden" name="my_applications" id="my_applications" value="'.$my_applications.'" />
          <div class="form-wrapper">
          <div class="form-header">Filter</div>
          <div  style="padding: 10px;">
          '.$nav->month_year(2015).'
          <select name="selShowStat">'.$stat_selections.'</select>
          <input onClick="report_filter()" type="button" value="Go" /> 
          </div>
          </div>
          </form>
          ';
        }
        if($def_month) {
          $str .= '
            <script language="JavaScript">
              change_selDate()
            </script>
          ';
        }
        if($nav_month > 0) {
          $nav1 = " and MONTH(application_requests.date_applied) = $nav_month ";
        } else {
          $nav_month = "ALL Months";
        }
        if($nav_year > 0) {
          $nav1 .= " and YEAR(application_requests.date_applied) = $nav_year ";
        } else {
          $nav_year = "ALL Years";
        }
        $this->list_obj->title = ($report_mode ? "View" : "Process" . " Request Applications");
        $xtra = ($more_info ? "" : ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Applications?" . ($report_mode ? "report" : "process") . "_mode=1&more_info=', application_requests.id, '" . ($my_applications ? "&my_applications=1" : "") . "\">More Information</a>') as ` `");
        $nav1 .= ($is_accounts || $report_mode ? "" : " and users2.id = " . $_SESSION['user_id']);
        $nav1 .= ($acc_state == 9 ? " and users2.state = 9 " : " and users2.state != 9 ");
        $this->list_obj->sql = "
              select users.id as idin, CONCAT('<span style=\"font-weight: bold; color: ', application_status.colour, '\">', application_status.item_name, '<span>') as `Status`, manager_reason as `Manager Reason` " . ($is_accounts || $report_mode ? ", accounts_reason as `Accounts Reason`" : "") . ", CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Applicant`, users.phone as `Phone`, CONCAT('<a href=\"mailto:', users2.email, '\">', users2.name, ' ', users2.surname, '</a>') as `Manager`, compliance.title as `Request Type`,
              application_requests.date_applied as `Date Applied` $xtra, CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."Resources?current_dir=compliance¤t_subdir=', application_requests.compliance_check_id, '\">Attachments</a>') as `Attachments`, if(application_status.item_name = 'PENDING', CONCAT('<a class=\"list_a\" href=\"Compliance?report_edit_id=', application_requests.compliance_check_id , '&request_id=', application_requests.id, '\">Edit</a>'), '')  as `Edit`
              from application_requests
              left join users on users.id = application_requests.requester_id
              left join application_status on application_status.id = application_requests.status_id
              left join compliance on compliance.id = application_requests.compliance_id
              left join users2 on users2.id = application_requests.manager_id
        ";
        if($my_applications) {
          $this->list_obj->sql .= ($more_info ? " where application_requests.id = $more_info" : " where 1 and users.id = " . $_SESSION['user_id'] . ($selShowStat > 1 ? " and application_requests.status_id = $selShowStat" : " and application_requests.status_id != (select id from application_status where item_name = 'CANCELLED') ") . ($selFilterApplication > 0 ? " and compliance.id = $selFilterApplication" : "") . " order by application_requests.date_applied DESC");
        } else {
          $this->list_obj->sql .= ($more_info ? " where application_requests.id = $more_info" : " where 1 $nav1 " . ($selShowStat > 1 ? " and application_requests.status_id = $selShowStat" : "") . ($selFilterApplication > 0 ? " and compliance.id = $selFilterApplication" : "") . ($is_accounts ? " and compliance.show_all_entities = 1 " : "") ." order by application_status.id, application_requests.date_applied");
        }
        $str .= $this->list_obj->draw_list(). "<br />";
        $str .= '<div class="cl"></div>';
        if($more_info) {
          $sql = "select application_requests.manager_reason, application_requests.accounts_reason, compliance.show_all_entities, users.name as `requester_name`, users.state, users.surname as `requester_surname`, users.email as `requester_email`,
                  users2.name as `manager_name`, users2.surname as `manager_surname`, users2.email as `manager_email`, compliance.title, application_requests.compliance_check_id
                  from application_requests 
                  left join users on users.id = application_requests.requester_id
                  left join users2 on users2.id = application_requests." . ($is_accounts ? "accounts_" : "manager_") . "id
                  left join compliance on compliance.id = application_requests.compliance_id
                  where application_requests.id = $more_info;";
          $result = $this->dbi->query($sql);
          if($myrow = $result->fetch_assoc()) {
            $reason = ($is_accounts ? $myrow['accounts_reason'] : $myrow['manager_reason']);
            $accounts_reason = $myrow['accounts_reason'];
            $show_all_entities = $myrow['show_all_entities'];
            $requester_name = $myrow['requester_name'];
            $state = $myrow['state'];
            $requester_surname = $myrow['requester_surname'];
            $manager_name = $myrow['manager_name'];
            $manager_email = $myrow['manager_email'];
            $requester_email = $myrow['requester_email'];
            $manager_surname = $myrow['manager_surname'];
            $app_title = $myrow['title'];
            $email = $myrow['email'];
            $compliance_obj = new compliance;
            $compliance_obj->print_results = 0;
            $compliance_obj->hide_score = 1;
            $compliance_obj->dbi = $this->dbi;
            $compliance_obj->title = "Request Details";
            $compliance_obj->compliance_check_id = $myrow['compliance_check_id'];
            $compliance_obj->display_results();
          }
          if($hdnChangeItem) {
            if($hdnChangeStatus == 50 && $my_applications) {
              $msg = "Hello $manager_name,<br /><br />$requester_name has <span style=\"color: red; font-weight: bold;\">CANCELLED</span> their request...<br /><br />No further action is required on your part...<br /><br />Regards.";
              $manager_name = "Edge";
              $requester_email = $manager_email;
              $manager_email = "no-reply@scgroup.global";
            } else {
              if($hdnChangeStatus != 50) {
                $msg = "Hello $requester_name,<br /><br /><b>Request Type:</b> $app_title<br /><br />";
                $msg .= ($hdnChangeStatus == 40 ? "Unfortunately y" : "Y") .  "our request has been " .
                        ($hdnChangeStatus == 20 || $hdnChangeStatus == 30 ? "<span style=\"color: green; font-weight: bold;\">APPROVED</span>" : "<span style=\"color: red; font-weight: bold;\">DENIED</span>") .
                        ($is_accounts ? " by " . ($state == 9 ? "HR" : "Accounts") . "." : " by your manager.");
                $msg .= ($reason ? "<br /><br /><b>Reason Given: </b>$reason" : "");
                $msg .= ($show_all_entities && !$is_accounts && $hdnChangeStatus == 20 ? "<br /><br />Whilst your manager has approved this request, complete approval is still pending from accounts." : "");
                $msg .= $compliance_obj->str;
                $msg .= "<br /><br />Regards,<br />$manager_name $manager_surname.";
              }
            }
            if($msg || $hdnChangeStatus == 20) {
              $mail = new email_q($this->dbi);
            }
            if($msg) {
              $mail->clear_all();
              $mail->AddReplyTo($manager_email);
              $mail->AddAddress($requester_email);
              $mail->Subject = $app_title;
              $mail->Body = $css . "\n\n" . $msg;
              $mail->queue_message();
            }
            if($hdnChangeStatus == 20) {
              $msg = "Hello " . ($state == 9 ? "HR" : "Accounts") . ",<br /><br /><b>Request Type:</b> $app_title<br /><br />I, $manager_name $manager_surname approved a request made by $requester_name $requester_surname.<br /><br />
                      Please log in to <a href=\"".$this->f3->get('full_url')."Applications?process_mode=1\">Edge</a> to process the request.".$compliance_obj->str."<br /><br />Regards,<br />$manager_name $manager_surname.";
              $mail->clear_all();
              $mail->AddReplyTo($manager_email);
              $acc_email = ($state == 9 ? "banumathy.ganesan@scgroup.global" : "megen.li@scgroup.global");
              $mail->AddAddress($acc_email);
              $mail->Subject = $app_title;
              $mail->Body    = $css . "\n\n" . $msg;
              $mail->queue_message();
            }
          }
          if(!$my_applications && !$report_mode) {
            $str .= '<h3>Approve/Deny Request</h3>
                  <textarea name="txtReason" id="txtReason" placeholder="Enter Reason for Approval/Denial of Request..." style="width: 98%; height: 120px;">'.$reason.'</textarea><br />
                  <input class="appraisal_btn approve" onClick="change_status('.$more_info.', ' . ($is_accounts ? "30" : "20") . ', \'Approved\');" type="button" value="Approve Request">
                  <input class="appraisal_btn deny" onClick="change_status('.$more_info.', 40, \'Denied\');" type="button" value="Deny Request">
                 ';
          }
          $str .= '<input class="appraisal_btn cancel" onClick="change_status('.$more_info.', 50, \'Cancelled\');" type="button" value="Cancel Request"><br /><br />';
          $str .= '<a class="list_a" href="Applications?' . ($report_mode ? "report" : "process") . '_mode=1' . ($my_applications ? "&my_applications=1" : "") . '"><< Back to Application Requests...</a>';
          if($my_applications) $str .= "<br /><br />";
        }
        if($more_info) $str .= $compliance_obj->str;
      } else if($request_id) {
        $uid = $_SESSION['user_id'];
        $manager_select = (isset($_POST['manager_select']) ? $_POST['manager_select'] : null);
        if($manager_select) {
          $sql = "insert into application_requests (compliance_id, requester_id, manager_id, status_id, date_applied) values ($request_id, $uid, $manager_select, 10, '$curr_date_time')";
          $result = $this->dbi->query($sql);
          $r_id = $this->dbi->insert_id;
          $sql = "select id from compliance_auditors where compliance_id = $request_id and user_id = $uid;";
          $result = $this->dbi->query($sql);
          if(!$result->num_rows) {
            $sql = "insert into compliance_auditors (compliance_id, user_id) values ('$request_id', '$uid');";
            $result = $this->dbi->query($sql);
            $caid = $this->dbi->insert_id;
            $sql = "insert into compliance_auditors_subjects (compliance_auditor_id, user_id) values ('$caid', '$uid')";
            $result = $this->dbi->query($sql);
          }
          $sql = "insert into compliance_checks (compliance_id, assessor_id, subject_id, status_id, check_date_time, last_modified) values ($request_id, $manager_select, $uid, 522, '$curr_date_time', '$curr_date_time')";
          $result = $this->dbi->query($sql);
          $compliance_check_id = $this->dbi->insert_id;
          $sql = "update application_requests set compliance_check_id = $compliance_check_id where id = $r_id";
          $result = $this->dbi->query($sql);
          $str .= $this->redirect($this->f3->get('main_folder')."Compliance?report_edit_id=$compliance_check_id&request_id=$r_id");
        } else {
          $txtFindManager = (isset($_POST['txtFindManager']) ? $_POST['txtFindManager'] : null);
          $str .= '
            <input type="hidden" name="manager_select" id="manager_select" />
            <script>
              function select_manager(manager_id) {
                document.getElementById(\'manager_select\').value = manager_id
                document.frmEdit.submit()
              }
            </script>
          ';
          $this->list_obj->sql = "select users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `Manager`, states.item_name as `State`, phone as `Phone`, users.email as `Email`,
                            CONCAT('<input type=\"button\" class=\"appraisal_btn\" onClick=\"select_manager(\'', users.id, '\')\" value=\"Select Manager\">') as `**`
                            from users
                            left join states on states.id = users.state
                            left join associations on associations.parent_user_id = users.id 
                            where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
          if($tstr = $this->list_obj->draw_list()) {
            $str .= "$tstr<h3>Step 1: Please Select Your Manager (The one responsible for your request)</h3>";
//            return $str;
            $or_show = "Or search for";
          } else {
            $or_show = "Step 1: Please find and select";
          }
          $str .= '<br /><h3>'.$or_show.' your manager by entering all or part of his/her name:</h3>
                </form>
                <form method="POST">
                <p><input maxlength="50" name="txtFindManager" id="search" type="text" class="search_box" value="" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg
                . '</p><div class="cl"></div>';
          if($txtFindManager) {
            $search = $txtFindManager;
            $search = "
              where (users.name LIKE '%$search%'
              or users.surname LIKE '%$search%'
              or users.email LIKE '%$search%'
              or users.employee_id LIKE '%$search%'
              or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
              and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') and users.id != ".$_SESSION['user_id']."
            ";
            $this->list_obj->sql = "
                    select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                    phone as `Phone`, users.email as `Email`,
                    CONCAT('<input type=\"button\" class=\"appraisal_btn\" onClick=\"select_manager(\'', users.id, '\')\" value=\"Select Manager\">') as `**` from users
                    left join states on states.id = users.state
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                    inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'MANAGEMENT'
                    $search";
              $str .= $this->list_obj->draw_list();
          }
        }
      } else {
        $sql = "select id, title from compliance where category_id in (select id from lookup_fields where item_name = 'Request Forms');";
        $result = $this->dbi->query($sql);
        while($myrow = $result->fetch_assoc()) {
          $compliance_id = $myrow['id'];
          $compliance_title = $myrow['title'];
          $titles .= "<h4><a class=\"select_a\" href=\"".$this->f3->get('main_folder')."Applications?request_id=$compliance_id\">$compliance_title</a></h4>";
        }
        $str .= "<h3>Select a Request Form Below</h3><br />$titles";
      }
    }
    return $str;
  }
}
?>