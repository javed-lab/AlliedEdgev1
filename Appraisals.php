<?php
class Appraisals extends Controller {

  function display_scores($appraisal_view_id, $name = '', $self_appraisal = 0) {
    $sql = "select question, answer, score from appraisal_check_scores where appraisal_check_id = $appraisal_view_id";
    $total_score = 0;
    $total_outof = 0;
    $result = $this->dbi->query($sql);
    if($result->num_rows) {
      $str .= "<h3>Appraisal Scores" . ($self_appraisal || !$name ? "" : " for $name") . "</h3>";
      $str .= '<table class="grid"><tr><th>Question</th><th>Answer</th><th>Score</th></tr>';
      while($myrow = $result->fetch_assoc()) {
        $question = $myrow['question'];
        $answer = $myrow['answer'];
        $score = $myrow['score'];
        $total_score += $score;
        $total_outof += 4;
        $str .= "<tr><td>$question</td><td class=\"selection"."$score\">$answer</td><td class=\"selection"."$score\">$score/4</td></tr>";
      }
      $str .= '<tr><th colspan="2"><div align="right">Total Score:</div></th><th>'.$total_score.'/'.$total_outof.'</th></tr>';
      $str .= "</table>";
    } else {
      $str .= "<br /><h3>No Scores Have Been Added...</h3>";
    }
  }

  function get_sub_staff($id) {
    $sql = "select users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `staff_name`,
                      CONCAT('<a title=\"Appriasals for ', users.name, ' ', users.surname, '\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=', users.id, '\">App</a>') as `appraisals`,
                      CONCAT('<a title=\"Personal Development for ', users.name, ' ', users.surname, '\"class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=', users.id, '&pdp=1\">PD</a>') as `personal_development`
                      from users
                      left join associations on associations.child_user_id = users.id 
                      where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.parent_user_id = $id
                      and users.user_status_id = 30";
                        return $sql;
  }
  
  function Show() {    
    $this->list_obj = new data_list;
    $report_mode = (isset($_GET['report_mode']) ? $_GET['report_mode'] : null);
    $appraisal_check_id = (isset($_REQUEST['appraisal_check_id']) ? $_REQUEST['appraisal_check_id'] : null);
    $staff_id = (isset($_GET['staff_id']) ? $_GET['staff_id'] : null);
    $summary_mode = (isset($_GET['summary_mode']) ? $_GET['summary_mode'] : null);
    if($summary_mode) {
      $start_year = 2016;
      $current_year = date('Y');
      $year_select = (isset($_GET['year_select']) ? $_GET['year_select'] : $current_year);
      $str .= "<hr />";
      for($x = $start_year; $x <= $current_year; $x++) {
        $str .= "<b>" . ($x == $year_select ? $x : '<a href="'.$this->f3->get('main_folder').'Appraisals?summary_mode=1&year_select='.$x.'">'.$x.'</a>') . "  </b>";
      }
    }
    if($report_mode == 1) {
      $this->list_obj->title = 'Appraisal Report';
      $this->list_obj->show_num_records = ($appraisal_check_id ? 0 : 1);
      $this->list_obj->sql = "select appraisal_checks.id as idin, DATE_FORMAT(appraisal_checks.date_added, '%d-%M-%Y') as `Date Added`, lookup_fields1.item_name as `Status`, 
              CONCAT(users2.name, ' ', users2.surname) as `Staff Member`,
              CONCAT(users.name, ' ', users.surname) as `Manager`,
              count(distinct appraisal_check_answers.id) as `Questions Answered`,
              CONCAT('<a href=\"".$this->f3->get('main_folder')."?report_mode=1" . ($appraisal_check_id ? "\"><< Back to Report" : "&appraisal_check_id=', appraisal_checks.id, '&staff_id=', users2.id, '\">More Info...</a>") . "') as `**`
              from appraisal_checks
              left join lookup_fields1 on lookup_fields1.id = appraisal_checks.status_id
              left join users on users.id = appraisal_checks.assessor_id
              left join users2 on users2.id = appraisal_checks.subject_id
              left join appraisal_check_answers on appraisal_check_answers.appraisal_check_id = appraisal_checks.id
              where lookup_fields1.item_name != 'Cancelled' and appraisal_check_answers.answer != ''
              " . ($appraisal_check_id ? " and appraisal_checks.id = $appraisal_check_id " : "") . "
              group by appraisal_checks.id
              order by CONCAT(users2.name, ' ', users2.surname)
              ";
      $str .= $this->list_obj->draw_list();
      if($appraisal_check_id) {
        $this->display_scores($appraisal_check_id);
        $this->list_obj->title = "<br />Personal Development";
        $this->list_obj->sql = "
        select appraisal_development.id as idin, REPLACE(appraisal_development.development_area, '\r\n', '<br />') as `Development Area`, REPLACE(appraisal_development.planned_training, '\r\n', '<br />') as `Planned Training`,
        appraisal_development.due_date as `Due Date`, appraisal_development.date_completed as `Date Completed` from appraisal_development where appraisal_development.user_id = $staff_id
        ";
        $lst = $this->list_obj->draw_list();
        $str .= ($lst ? $lst : "<br /><h3>No Personal Development Added...</h3>");
      }
    } else {
      $appraisal_id = (isset($_GET['appraisal_id']) ? $_GET['appraisal_id'] : null);
      $appraisal_view_id = (isset($_GET['appraisal_view_id']) ? $_GET['appraisal_view_id'] : null);
      $save_appraisal = (isset($_POST['save_appraisal']) ? $_POST['save_appraisal'] : null);
      $new_appraisal_id = (isset($_GET['new_appraisal_id']) ? $_GET['new_appraisal_id'] : null);
      $hdnNewAppraisal = (isset($_POST['hdnNewAppraisal']) ? $_POST['hdnNewAppraisal'] : null);
      $pdp = (isset($_GET['pdp']) ? $_GET['pdp'] : null);
      $self_appraisal = (isset($_GET['self_appraisal']) ? $_GET['self_appraisal'] : null);
      $start_appraisal = ($save_appraisal ? null : (isset($_GET['start_appraisal']) ? $_GET['start_appraisal'] : null));
      $txtFindManager = (isset($_POST['txtFindManager']) ? $_POST['txtFindManager'] : null);
      $manager_select = (isset($_POST['manager_select']) ? $_POST['manager_select'] : null);
      $skip_add_manager = (isset($_GET['skip_add_manager']) ? $_GET['skip_add_manager'] : null);
      $manager_notify = (isset($_POST['manager_notify']) ? $_POST['manager_notify'] : null);
      $staff_notify = (isset($_POST['staff_notify']) ? $_POST['staff_notify'] : null);
      $col[1] = "#F5A9A9";
      $col[2] = "#EBD2C9";
      $col[3] = "#36D9FF";
      $col[4] = "#E1F5A9";
  }
  $str .= '
    <style>
      .question_title {
        font-size: 11pt !important;
        background-color: #FFFFEE !important; font-weight: bold !important;
        padding-top: 20px !important;
      }
      .score_text {
        width: 60px;
      }
      .answer_area {
        margin-bottom: 15px;
        margin-top: 0px;
        margin-right: 10px;
        padding: 10px;
        background-color: #DDDDDD;
        border-bottom: 1px solid #BBBBBB;
        border-right: 1px solid #BBBBBB;
        border-top: 1px solid white;
        border-left: 1px solid white;
      }
      .answer_area:hover {
        background-color: #D5D5D5;
        border-bottom: 1px solid #999999;
        border-right: 1px solid #999999;
      }
      .question_area {
        position: relative;
        margin-bottom: 5px;
        margin-top: 5px;
        margin-right: 10px;
        padding: 10px;
        background-color: #DDDDDD;
        border-bottom: 2px solid #BBBBBB;
        border-right: 2px solid #BBBBBB;
        border-top: 2px solid white;
        border-left: 2px solid white;
        float: left;
        width: 880px;
        height: 250px;
      }
      .question_content {
        position: absolute;
        bottom: 10px;
        left: 10px;
      }
     .question_text_box {
          background-color: white;
          color: #222222;
          border: 1px solid #CCCCCC;
          font-size: 11pt !important;
          width: 870px;;
          height: 200px;
          }
      .choice1, .choice2, .choice3, .choice4 {
        cursor: pointer;
        height: 50px;
      }
      .choice1:hover, .choice2:hover, .choice3:hover, .choice4:hover {
        /* color: white; */
      }
      .choice1:hover {
        background-color: ' .$col[1] . ';
      }
      .choice2:hover {
        background-color: ' .$col[2] . ';
      }
      .choice3:hover {
        background-color: ' .$col[3] . ';
      }
      .choice4:hover {
        background-color: ' .$col[4] . ';
      }
      .selection1 {
        background-color: ' .$col[1] . ';
      }
      .selection2 {
        background-color: ' .$col[2] . ';
      }
      .selection3 {
        background-color: ' .$col[3] . ';
      }
      .selection4 {
        background-color: ' .$col[4] . ';
      }
      .l_1:hover {
        background-color: #F4E3C9;
      }
      .l_2:hover {
        background-color: #CFD9F2;
      }
      .l_3:hover {
        background-color: #F5E1EA;
      }
      .l_4:hover {
        background-color: #FFFCF8;
      }
      .l_5:hover {
        background-color: #F5F0ED;
      }
    </style>';
      $mail = new email_q($this->dbi);
      $mail->Subject = "Performance Appraisals";
      $footer = "Regards,<br />Southern Cross Edge,<br />https://Edge.scgs.com.au/";
      $tmp_id = ($staff_id ? $staff_id : $_SESSION['user_id']);
      $sql = "select id, email, CONCAT(name, ' ', surname) as `name_send`, sex from users where id = $tmp_id";
      $result = $this->dbi->query($sql);        $myrow = $result->fetch_assoc();
      $staff_id_tmp = $myrow['id'];        $email_send2 = $myrow['email'];        $name_send2 = $myrow['name_send'];        $sex = $myrow['sex'];
      $sql = "select id, sort_order, value, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'appraisal_status') and sort_order > 10 order by sort_order";
      $result = $this->dbi->query($sql);
      $stat_count = 0;
      while($myrow = $result->fetch_assoc()) {
        $str_tmp = "CONCAT('<input type=\"button\" class=\"cs_button\" style=\"font-size: $standard_font !important;\" value=\"".$myrow['value']."\" onClick=\"change_status(', appraisal_checks.id, ', ".$myrow['id'].", \'".$myrow['item_name']."\')\" />')";
        if($myrow['sort_order'] > 20) $stat_str = $str_tmp;
        $stat_str2 .= "$stat_xtra $str_tmp";
        $stat_xtra = ", ";
        $stat_count++;
      }
      if($self_appraisal) {
        $show_change_status = "";
        $edit_xtra = "Appraisals?self_appraisal=1";
      } else {
        $stat_str = "
                      if(lookup_fields1.item_name = 'Pending',
                      CONCAT($stat_str2),
                      CONCAT($stat_str))
        ";
        $stat_str_off = "' '";
        $show_change_status = "if(appraisal_checks.assessor_id = " . $_SESSION['user_id'] . ", $stat_str, $stat_str_off) as `Change Status`,";
        $edit_xtra = "?staff_id=$staff_id_tmp";
      }
      $str .= '
        <input type="hidden" name="manager_select" id="manager_select" />
        <input type="hidden" name="manager_notify" id="manager_notify" />
        <input type="hidden" name="staff_notify" id="staff_notify" />
        <script>
          function select_manager(manager_id) {
            document.getElementById(\'manager_select\').value = manager_id
            document.frmEdit.submit()
          }
          function notify_manager(manager_id) {
            confirmation = "Are all of the details correct?";
            if (confirm(confirmation)) {
              document.getElementById(\'manager_notify\').value = manager_id
              document.frmEdit.submit()
            }
          }
          function notify_staff(staff_id) {
            confirmation = "Are all of the details correct?";
            if (confirm(confirmation)) {
              document.getElementById(\'staff_notify\').value = staff_id
              document.frmEdit.submit()
            }
          }
        </script>
      ';
      if(!$self_appraisal) {
        $str .= "<script>
                function change_status(change_itm, status_in, status_txt) {
                  var confirmation;
                  confirmation = 'Are you sure about changing the status this record to '+status_txt+'?';
                  if (confirm(confirmation)) {
                    document.getElementById('hdnChangeItem').value = change_itm
                    document.getElementById('hdnChangeStatus').value = status_in
                  //alert(document.getElementById('hdnChangeItem').value)
                    document.frmEdit.submit()
                  }
                }
                ".'
              </script>
              <input type="hidden" name="hdnNewAppraisal" id="hdnNewAppraisal" />
              <input type="hidden" name="staff_id" value="'.$staff_id.'">
              <input type="hidden" name="summary_mode" value="'.$summary_mode.'">
              <input type="hidden" name="hdnChangeItem" id="hdnChangeItem" />
              <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />
              ';
          }
      $hdnChangeItem = (isset($_REQUEST['hdnChangeItem']) ? $_REQUEST['hdnChangeItem'] : null);
      if($hdnChangeItem) {
        $sql = "update appraisal_checks set status_id = " . $_REQUEST['hdnChangeStatus'] . " where id = " . $hdnChangeItem;
            $result = $this->dbi->query($sql);
      }
      if($self_appraisal) {
        $staff_id = $_SESSION['user_id'];
        $str .= "<h3>My Appraisals</h3><hr />";
        $name = 1;
        if($start_appraisal) {
          if($manager_select) {
            $sql = "select lookup_fields.id, lookup_fields.item_name, lookup_fields.value from lookup_fields
                  left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
                  where foreign_id = '" . $_SESSION['user_id'] . "' and table_assoc = 'users' and lookup_fields.value = 'MANAGEMENT'
                  ";
            $result = $this->dbi->query($sql);
            $is_manager = $result->num_rows;
                    if($is_manager) {
              while($myrow = $result->fetch_assoc()) {
                if($myrow['item_name'] == 'S1') $s1 = 1;
              }
            }
            $aid = ($s1 ? 3 : ($is_manager ? 2 : 1));
            if(!$skip_add_manager) {
              $sql = "delete from associations where association_type_id in (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
              $result = $this->dbi->query($sql);
                        $sql = "insert into associations (association_type_id, child_user_id, parent_user_id)  (select id, '" . $_SESSION['user_id'] . "', '$manager_select' from association_types where name = 'manager_staff')";
              $result = $this->dbi->query($sql);
              }
            $sql = "insert into appraisal_checks (appraisal_id, subject_id, assessor_id, date_added, status_id) values ($aid, $staff_id, $manager_select, now(), 1994)";
            $result = $this->dbi->query($sql);
            $appraisal_check_id = $this->dbi->insert_id;
            $sql = "insert into appraisal_check_answers (appraisal_check_id, question_id, question, is_manager) select '$appraisal_check_id', id, question, is_manager from appraisal_questions
                    where appraisal_id = (select appraisal_id from appraisal_checks where id = $appraisal_check_id) order by is_manager, sort_order";
            $result = $this->dbi->query($sql);
            $sql = "select email, CONCAT(name, ' ', surname) as `name_send` from users where id = $manager_select;";
            $result = $this->dbi->query($sql);        $myrow = $result->fetch_assoc();
            $email_send = $myrow['email'];        $name_send = $myrow['name_send'];
                    $mail_msg2 = "Hello $name_send2,<br /><br />Thank you for starting the appraisal process.<br /><br />Please organise a meeting with $name_send to complete the appraisal.<br /><br />For further updates, <a href=\"".$this->f3->get('main_folder')."Appraisals?appraisal_check_id=$appraisal_check_id&self_appraisal=1\">Click Here to Edit the Appraisal...</a><br /><br />$footer";
            //include("print_css.php");
            $mail->clear_all();
            $mail->AddReplyTo($email_send);
            $mail->AddAddress($email_send2);
            $mail->Body = $css . "\n\n" . $mail_msg2;
            $mail->queue_message();
          } else {
            $this->list_obj->sql = "select users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `Manager`, states.item_name as `State`, phone as `Phone`, users.email as `Email`,
                              CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"select_manager(\'', users.id, '\')\" value=\"Select Manager\">') as `**`
                              from users
                              left join states on states.id = users.state
                              left join associations on associations.parent_user_id = users.id 
                              where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                                if($tstr = $this->list_obj->draw_list()) {
              $str .= "<h3>Step 1: Please Select Your Manager (The one responsible for your appraisal)</h3>";
              $str .= $tstr;
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
                and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                and users.id != ".$_SESSION['user_id']."
              ";
              $this->list_obj->sql = "
                      select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                      phone as `Phone`, users.email as `Email`,
                      CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"select_manager(\'', users.id, '\')\" value=\"Select Manager\">') as `**` from users
                      left join states on states.id = users.state
                      inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                      inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'MANAGEMENT'
                      $search";
                                $str .= $this->list_obj->draw_list();
            }
          }
        } else {
          $str .= '<div class="mobile_font" style="padding-top: 40px; padding-bottom: 40px;"><a href="'.$this->f3->get('main_folder').'Appraisals?self_appraisal=1&start_appraisal=1">Start a New Appraisal</a></div>';
          $str .= "<hr />";
        }
      } else {
        if($staff_id) {
          $title = ($pdp ? "<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=$staff_id\">Appraisals</a> Personal Development"
                 : "<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=$staff_id&pdp=1\">Personal Development</a>" . 
                 ($appraisal_check_id ? " <a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=$staff_id\"><< Back to Appraisals</a>" : "") . ($appraisal_check_id ? " Interview Questions" : " Appraisals"));
          $sql =  "select employee_id, CONCAT(name, ' ', surname) as `name` from users
                   where id = $staff_id
                  ";
          $result = $this->dbi->query($sql);
          $str .= "<hr />";
          if($myrow = $result->fetch_assoc()) {
            $employee_id = $myrow['employee_id'];
            $name = $myrow['name'];
            $str .= "<h3>$title for [$employee_id] $name</h3><hr />";
          }
        }
      }
      if($staff_id) {
        if($appraisal_view_id || $appraisal_check_id) {
            $tid = ($appraisal_view_id ? $appraisal_view_id : $appraisal_check_id);
            $tag_xtra = ($_SESSION['user_id'] == $staff_id ? "&self_appraisal=1" : "&staff_id=$staff_id");
            $show_link = ($appraisal_view_id ? "CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?appraisal_check_id=', appraisal_checks.id, '$tag_xtra\">Edit Appraisal</a>') as `Edit Appraisal`" : "CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?appraisal_view_id=', appraisal_checks.id, '$tag_xtra\">View Appraisal</a>', '<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?appraisal_view_id=', appraisal_checks.id, '$tag_xtra&hide_menu=1\">Print View</a>') as `View Appraisal`");
            if($self_appraisal && !$appraisal_view_id) {
              $show_link .= ", CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"notify_manager(\'', users.id, '\')\" value=\"Save and Notify Manager of Completed Appraisal\">') as `Save and Notify Manager`";
            } else {
              if(!$self_appraisal) $show_link .= ", CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"notify_staff(\'', users.id, '\')\" value=\"Save and Notify Staff Member of Completed Appraisal\">') as `Save and Notify Staff Member`";
            }
            $this->list_obj->sql = "select appraisal_checks.id as idin, DATE_FORMAT(appraisal_checks.date_added, '%d-%M-%Y') as `Date Added`, lookup_fields1.item_name as `Status`, CONCAT(users.name, ' ', users.surname) as `Assessor`,
                    CONCAT(users2.name, ' ', users2.surname) as `Subject`,
                    appraisals.title as `Appraisal Type`,
                    $show_link
                    from appraisal_checks
                    left join lookup_fields1 on lookup_fields1.id = appraisal_checks.status_id
                    left join appraisals on appraisals.id = appraisal_checks.appraisal_id
                    left join compliance on compliance.title = appraisals.title
                    left join users on users.id = appraisal_checks.assessor_id
                    left join users2 on users2.id = appraisal_checks.subject_id
                    where appraisal_checks.id = $tid";
            $str .= $this->list_obj->draw_list() . "<hr />";
        }
      }
      if($pdp) {
        $this->list_obj = new data_list;
        $this->editor_obj = new data_editor;
        if(!$staff_id) $staff_id = $_SESSION['user_id'];
        $this->list_obj->title = "<br />";
        $this->list_obj->sql = "
        select appraisal_development.id as idin, REPLACE(appraisal_development.development_area, '\r\n', '<br />') as `Development Area`, REPLACE(appraisal_development.planned_training, '\r\n', '<br />') as `Planned Training`,
        appraisal_development.due_date as `Due Date`, appraisal_development.date_completed as `Date Completed`, 'Edit' as `*`, 'Delete' as `!` from appraisal_development where appraisal_development.user_id = $staff_id
        ";
        $list_top = "select 0 as id, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "appraisal_development";
        $style = 'style="width: 220px;"';
        $description_box = 'style="width: 440px; height: 112px;"';
        $this->editor_obj->form_attributes = array(
                 array("txaDevelopmentArea", "txaPlannedTraining", "calDueDate", "calDateCompleted"),
                 array("Development Area", "Planned Training", "Due Date", "Date Completed"),
                 array("development_area", "planned_training", "due_date", "date_completed"),
                 array("", "", "", ""),
                 array($description_box, $description_box, $style, $style),
                 array("c", "c", "n", "n"),
                 array("", "", "", ""),
                 array("", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
          array("Add New", "Save", "Reset"),
          array("cmdAdd", "cmdSave", "cmdReset"),
          array("if(add()) this.form.submit()", "if(save()) this.form.submit()"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
                  <div class="fl"><nobr>ttxaDevelopmentArea</nobr><br />txaDevelopmentArea</div>
                  <div class="fl"><nobr>ttxaPlannedTraining</nobr><br />txaPlannedTraining</div>
                  <div class="fl"><nobr>tcalDueDate</nobr><br />calDueDate<br /><br />
                  <nobr>tcalDateCompleted</nobr><br />calDateCompleted<br /></div>
                  <div class="cl"></div>
                  '.$this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                    <div class="form-wrapper">
                    <div class="form-header">Personal Development</div>
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
                    </div>
        ';
        $this->editor_obj->xtra_id_name = "user_id";
        $this->editor_obj->xtra_id = $staff_id;
            return $this->editor_obj->draw_data_editor($this->list_obj);
      } else if($summary_mode) {
        $str .= "<hr /><h3><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals\">My Staff</a> Appraisal Summary for $year_select</h3><hr />";
        $this->list_obj->sql = "select appraisal_checks.id as idin, DATE_FORMAT(appraisal_checks.date_added, '%d-%M-%Y') as `Date Added`, lookup_fields1.item_name as `Status`, 
                CONCAT(users.name, ' ', users.surname) as `Subject`,
                appraisals.title as `Appraisal Type`,
                $show_change_status
                if(lookup_fields1.item_name = 'Pending', CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=', users.id, '&appraisal_check_id=', appraisal_checks.id, '\">Edit Appraisal</a>'),
                '<div class=\"list_a\"><strike><b>Edit Appraisal</b></strike></div>')
                as `Edit Appraisal`,
                CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=', users.id, '&appraisal_view_id=', appraisal_checks.id, '\">View Appraisal</a>', '<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?appraisal_view_id=', appraisal_checks.id, '$tag_xtra&hide_menu=1\">Print View</a>') as `View Appraisal`,
                CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=', users.id, '&pdp=1\">Personal Development</a>') as `Personal Development`
                from appraisal_checks
                left join lookup_fields1 on lookup_fields1.id = appraisal_checks.status_id
                left join appraisals on appraisals.id = appraisal_checks.appraisal_id
                left join users on users.id = appraisal_checks.subject_id
                where lookup_fields1.item_name != 'Cancelled' and  appraisal_checks.assessor_id = " . $_SESSION['user_id'] . "
                and year(appraisal_checks.date_added) = $year_select
                ;";
        $str .= $this->list_obj->draw_list();
      } else if($appraisal_view_id) {
        $sql = "select appraisal_check_answers.is_manager, appraisal_check_answers.question, appraisal_check_answers.answer, appraisal_checks.compliance_check_id from appraisal_check_answers
                left join appraisal_checks on appraisal_checks.id = appraisal_check_answers.appraisal_check_id
                where appraisal_check_id = $appraisal_view_id order by appraisal_check_answers.is_manager, appraisal_check_answers.id";
                    $result = $this->dbi->query($sql);
        $show_self_assessment = 1;
        $show_manager = 1;
        while($myrow = $result->fetch_assoc()) {
          $is_manager = $myrow['is_manager'];
          $question = $myrow['question'];
          $report_view_id = $myrow['compliance_check_id'];
          $answer = nl2br(strip_tags($myrow['answer']));
          if($show_self_assessment) {
            $show_self_assessment = 0;
            $str .= "<h3>Self Assessment Answers</h3><hr />";
          }
          if($is_manager && $show_manager) {
            $show_manager = 0;
            $str .= "<hr /><h3>Manager Answers</h3><hr />";
          }
          $str .= "<div class=\"answer_area\"><p><b>Q:</b> $question</p>";
          if($answer) $str .= "<b>A:</b><br />$answer";
          $str .= "</div>"; 
        }
        if($appraisal_view_id) {
          $this->display_scores($appraisal_view_id, $name, $self_appraisal);
        }
        $this->list_obj->title = "<br />Personal Development";
        $this->list_obj->sql = "
        select appraisal_development.id as idin, REPLACE(appraisal_development.development_area, '\r\n', '<br />') as `Development Area`, REPLACE(appraisal_development.planned_training, '\r\n', '<br />') as `Planned Training`,
        appraisal_development.due_date as `Due Date`, appraisal_development.date_completed as `Date Completed` from appraisal_development where appraisal_development.user_id = $staff_id
        ";
        $str .= $this->list_obj->draw_list();
      } else {
          if($staff_id) {
          if($name) {
            $str .= '
                  <script>
                    function save_the_appraisal() {
                      document.frmEdit.submit()
                    }
                    function save_complete_appraisal() {
                      document.getElementById("complete_appraisal").value = 1
                      document.frmEdit.submit()
                    }
                  </script>
                  <input type="hidden" name="staff_id" value="'.$staff_id.'">
                  <input type="hidden" name="self_appraisal" value="'.$self_appraisal.'">
                  <input type="hidden" name="appraisal_check_id" value="'.$appraisal_check_id.'">
                  <input type="hidden" name="complete_appraisal" id="complete_appraisal">
            ';
            if($manager_notify || $staff_notify) {
              $sql = "select sex, email, CONCAT(name, ' ', surname) as `name_send` from users where id = " . ($manager_notify ? $manager_notify : $staff_notify) . ";";
              $result = $this->dbi->query($sql);        $myrow = $result->fetch_assoc();
              $email_send = $myrow['email'];        $name_send = $myrow['name_send'];
              $sex = ($manager_notify ? $sex : $myrow['sex']);
              $sex = (strtoupper($sex) == 'F' ? "her" : "his");
              $mail_msg = ($manager_notify
              ? 
                "Hello $name_send,<br /><br />$name_send2 has completed $sex appraisal.<br /><br />Please complete the appraisal for $name_send2.<br /><br /><a href=\"".$this->f3->get('main_folder')."Appraisals?staff_id=$staff_id&appraisal_check_id=$appraisal_check_id\">Click Here to Complete the Appraisal...</a><br /><br />$footer"
              :
                "Hello $name_send2,<br /><br />$name_send has completed $sex part of your appraisal.<br /><br />Please arrange a meeting with $name_send to complete the appraisal.<br /><br /><a href=\"".$this->f3->get('main_folder')."Appraisals?self_appraisal=1&appraisal_view_id=$appraisal_check_id\">Click Here to View your Appraisal...</a><br /><br />$footer"
              );
              //include("print_css.php");
              $mail->clear_all();
              $mail->AddAddress($email_send);
              $mail->Body = $css . "\n\n" . $mail_msg;
              $mail->queue_message();
              $msg = "Your " . ($manager_notify ? "manager" : "staff member") . " has been notified...";
              $str .= "<h3>$msg</h3><br />";
              $msg .= "<br /><br />";
            } 
            if($appraisal_check_id) {
              $str .= '<input type="hidden" name="save_appraisal" value="1">';
              if($save_appraisal) {
                $sql = "select id from appraisal_check_answers where appraisal_check_id = $appraisal_check_id order by id";
                $result = $this->dbi->query($sql);
                while($myrow = $result->fetch_assoc()) {
                  $aid = $myrow['id'];
                  $answer = (isset($_POST['txt'.$aid]) ? $_POST['txt'.$aid] : null);
                                if($answer) {
                    $sql = "update appraisal_check_answers set answer = '" . mysqli_real_escape_string($this->dbi, $answer) . "' where id = $aid";
                    $this->dbi->query($sql);
                  }
                }
                $msg .= "Appraisal Saved...";
                $sql = "delete from appraisal_check_scores where appraisal_check_id = $appraisal_check_id order by id";
                $result = $this->dbi->query($sql);
              }
              $sql = "select id, question, answer from appraisal_check_answers where appraisal_check_id = $appraisal_check_id and is_manager = " . ($_SESSION['user_id'] == $staff_id ? "0" : "1") . " order by id";
              $result = $this->dbi->query($sql);
              while($myrow = $result->fetch_assoc()) {
                $app_qid = $myrow['id'];
                $question = $myrow['question'];
                $answer = $myrow['answer'];
                $str .= '
                  <div class="question_area"><div class="question_content">
                  '.$question.'<br />
                  <textarea name="txt'.$app_qid.'" class="question_text_box">'.$answer.'</textarea>
                  </div></div>
                ';
              }
              $str .= '<div class="cl"></div>';
              if(!$self_appraisal) {
                $sql = "select appraisals.compliance_id from appraisal_checks left join appraisals on appraisals.id = appraisal_checks.appraisal_id where appraisal_checks.id = $appraisal_check_id";
                $result = $this->dbi->query($sql);
                if($myrow = $result->fetch_assoc()) {
                  $compliance_id = $myrow['compliance_id'];
                }
              }
              $str .= '
              <script>
                function change_choice(txt, val, itm, col) {
                  document.getElementById("hdnQuestion" + txt).value = val
                  var row = document.getElementById("row"+txt)
                  var cells = row.getElementsByTagName("td");
                  for(x = 0; x < cells.length; x++) {
                    cells[x].style.backgroundColor = "#F9F9F9"
                  }
                  document.getElementById(itm).style.backgroundColor = col
                }
                function perform_search() {
                }
              </script>';
              $str .= "<h3>Appraisal Scores for [$employee_id] $name</h3><div style=\"color: red;\">Please select scores by clicking on the items below:</div><table class=\"grid\">";
              $sql = "select id, question_title from compliance_questions where compliance_id = $compliance_id and question_type = 'opt'";
              if($result = $this->dbi->query($sql)) {
                while($myrow = $result->fetch_assoc()) {
                  $question_title = $myrow['question_title'];
                  $compliance_question_id = $myrow['id'];
                  $str .= "<tr><th class=\"question_title\" colspan=\"4\">$question_title</th></tr>";
                  if($save_appraisal) {
                    $save_item = (isset($_POST["hdnQuestion$compliance_question_id"]) ? $_POST["hdnQuestion$compliance_question_id"] : "");
                    if($save_item) {
                      $sql = "insert into appraisal_check_scores (appraisal_check_id, question_id, question, answer, score)
                      select '$appraisal_check_id', '$compliance_question_id', '$question_title', choice, '$save_item' from compliance_question_choices where compliance_question_id = $compliance_question_id and choice_value = '$save_item';";
                      $this->dbi->query($sql);
                                  }
                  }
                  $sql = "select score from appraisal_check_scores where question_id = $compliance_question_id and appraisal_check_id = $appraisal_check_id";
                                $result2 = $this->dbi->query($sql);
                  if($myrow2 = $result2->fetch_assoc()) {
                    $score = $myrow2['score'];
                  } else {
                    $score = "";
                  }
                  $str .= "<tr id=\"row$compliance_question_id\">";
                  $sql = "select id, choice, choice_value from compliance_question_choices where compliance_question_id = $compliance_question_id order by sort_order";
                  $result2 = $this->dbi->query($sql);
                  $cnt = 0;
                  while($myrow2 = $result2->fetch_assoc()) {
                    $cnt++;
                    $choice_id = $myrow2['id'];
                    $choice = $myrow2['choice'];
                    $choice_value = $myrow2['choice_value'];
                    if($cnt == $score) {
                      $class_xtra = "selection$score";
                    } else {
                      $class_xtra = "";
                    }
                    $str .= "<td class=\"choice$cnt $class_xtra\" id=\"$choice_id\" onClick=\"change_choice('$compliance_question_id', $cnt, $choice_id, '$col[$cnt]')\">$choice</td>";
                  }
                  $str .= "<input class=\"\" type=\"hidden\" name=\"hdnQuestion$compliance_question_id\" id=\"hdnQuestion$compliance_question_id\" class=\"score_text\" value=\"$score\" />";
                  $str .= "</tr>";
                }
              }
              $str .= "</table>";
              $str .= '<input type="button" onClick="save_the_appraisal()" value="Save for Later" />';
              if($self_appraisal) {
                $sql = "select users.id from users
                        left join associations on associations.parent_user_id = users.id 
                        where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                                    $result = $this->dbi->query($sql);
                if($myrow = $result->fetch_assoc()) $manager_id_select = $myrow['id'];
              }
              $str .= "    " . ($self_appraisal
              ? 
                '<input type="button" onClick="notify_manager('.$manager_id_select.')" value="Save and Notify Manager" />'
              :
                '<input type="button" onClick="notify_staff('.$_SESSION['user_id'].')" value="Save and Notify Staff Member" />'
              );
            } else {
              if($_SESSION['user_id'] == $staff_id) {
                $this->list_obj->title = "";
                $xtra = "";
                $edit_xtra = "Appraisals?self_appraisal=1";
              }
              if(!$start_appraisal) {
                $this->list_obj->sql = "select appraisal_checks.id as idin, DATE_FORMAT(appraisal_checks.date_added, '%d-%M-%Y') as `Date Added`, lookup_fields1.item_name as `Status`, 
                        CONCAT(users.name, ' ', users.surname) as `Assessor`,
                        CONCAT(users2.name, ' ', users2.surname) as `Subject`,
                        appraisals.title as `Appraisal Type`,
                        $show_change_status
                        if((appraisal_checks.assessor_id = " . $_SESSION['user_id'] . " or '1' = '$self_appraisal') and lookup_fields1.item_name = 'Pending', CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."$edit_xtra&appraisal_check_id=', appraisal_checks.id, '\">Edit Appraisal</a>'),
                        '<div class=\"list_a\"><strike><b>Edit Appraisal</b></strike></div>')
                        as `Edit Appraisal`,
                        CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."$edit_xtra&appraisal_view_id=', appraisal_checks.id, '\">View Appraisal</a>') as `View Appraisal`
                        from appraisal_checks
                        left join lookup_fields1 on lookup_fields1.id = appraisal_checks.status_id
                        left join appraisals on appraisals.id = appraisal_checks.appraisal_id
                        left join users on users.id = appraisal_checks.assessor_id
                        left join users2 on users2.id = appraisal_checks.subject_id
                        left join compliance on compliance.title = appraisals.title
                        where $xtra appraisal_checks.subject_id = $staff_id and lookup_fields1.item_name != 'Cancelled'";
                              $str .= ($_SESSION['user_id'] == $staff_id ? "" : "<br /><br />") . $this->list_obj->draw_list();
              }
            }
            $str .= "</form>";
            if($msg) $str .= "<script>caller('$msg');</script>";
          }
        } else {
          //function appraisal_done($this->dbi, $id) {
//            return $is_done;
  //        }
          $result = $this->dbi->query($this->get_sub_staff($_SESSION['user_id']));
          if($result->num_rows) {
            $str .= "<hr /><h3><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."?summary_mode=1\">Appraisal Summary</a> My Staff</h3><hr />";
            $str .= "<ol>";
            while($myrow = $result->fetch_assoc()) {
              $idin = $myrow['idin'];
              $staff_name = $myrow['staff_name'];
              $appraisals = $myrow['appraisals'];
              $personal_development = $myrow['personal_development'];
              $str .= "<li class=\"l_1\">$appraisals $personal_development $staff_name";
              $result2 = $this->dbi->query($this->get_sub_staff($idin));
              if($result2->num_rows) {
                $str .= "<ol type=\"A\">";
                while($myrow2 = $result2->fetch_assoc()) {
                  $idin = $myrow2['idin'];
                  $staff_name = $myrow2['staff_name'];
                  $appraisals = $myrow2['appraisals'];
                  $personal_development = $myrow2['personal_development'];
                  $str .= "<li class=\"l_2\">$appraisals $personal_development $staff_name";
                  $result3 = $this->dbi->query($this->get_sub_staff($idin));
                  if($result3->num_rows) {
                    $str .= "<ol type=\"I\">";
                    while($myrow3 = $result3->fetch_assoc()) {
                      $idin = $myrow3['idin'];
                      $staff_name = $myrow3['staff_name'];
                      $appraisals = $myrow3['appraisals'];
                      $personal_development = $myrow3['personal_development'];
                      $str .= "<li class=\"l_3\">$appraisals $personal_development $staff_name";
                      $result4 = $this->dbi->query($this->get_sub_staff($idin));
                      if($result4->num_rows) {
                        $str .= "<ol type=\"a\">";
                        while($myrow4 = $result4->fetch_assoc()) {
                          $idin = $myrow4['idin'];
                          $staff_name = $myrow4['staff_name'];
                          $appraisals = $myrow4['appraisals'];
                          $personal_development = $myrow4['personal_development'];
                          $str .= "<li class=\"l_4\">$appraisals $personal_development $staff_name";
                          $result5 = $this->dbi->query($this->get_sub_staff($idin));
                          if($result5->num_rows) {
                            $str .= "<ol type=\"i\">";
                            while($myrow5 = $result5->fetch_assoc()) {
                              $idin = $myrow5['idin'];
                              $staff_name = $myrow5['staff_name'];
                              $appraisals = $myrow5['appraisals'];
                              $personal_development = $myrow5['personal_development'];
                              $str .= "<li class=\"l_5\">$appraisals $personal_development $staff_name</li>";
                            }
                            $str .= "</ol>";
                          }
                        }
                        $str .= "</ol>";
                      }
                    }
                    $str .= "</ol>";
                  }
                }
                $str .= "</ol>";
              }
              $str .= "</li>";
            }
            $str .= "<ol>";
          } else {
            $str .= "<h3>There is currently no staff reporting to you.</h3>";
          }
        }
      }
      return $str;
    }
  }
  
?>
