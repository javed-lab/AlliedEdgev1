<?php

class Diary extends Controller {
  function Show() {
    $show_post = (isset($_GET['show_post']) ? $_GET['show_post'] : null);

    if($show_post) {
      $txtDiary = (isset($_POST['txtDiary']) ? mysqli_real_escape_string($this->dbi, $_POST['txtDiary']) : null);
      if($txtDiary) {
        $sql = "insert into diary (date, description, user_id) values (now(), '$txtDiary', " . $_SESSION['user_id'] . ");";
        $result = $this->dbi->query($sql);
        $share_id = $this->dbi->insert_id;
        $save_msg = '<div class="fl help_message">Message Saved... <a target="_top" class="list_a" href="'.$this->f3->get('main_folder').'Diary?share_id='.$share_id.'">Share</a></div>';
        $sql = "select users.id as `idin` from users left join associations on associations.parent_user_id = users.id
            where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
        $result = $this->dbi->query($sql);
        if($myrow = $result->fetch_assoc()) {
          $manager_id = $myrow['idin'];
          $sql = "insert into diary_share (diary_id, user_id) values ($share_id, $manager_id);";
          $this->dbi->query($sql);
        }
      }

      $str = '
        <h3>Diary</h3>
        <style>
        .on-your-mind {
          width: 99%;
          height: 50px;
        }
        .help_message {
          white-space:nowrap;
          padding: 2px;
          background-color: #FFFFEE;
          border-left: 8px solid #990000;
          border-right: 2px solid #990000;
          border-top: 2px solid #990000;
          border-bottom: 2px solid #990000;
          font-size: 12pt;
          margin-right: 6px;
        }
        </style>
        <script>
          function submit_message() {
            if(document.getElementById("txtDiary").value) document.frmEdit.submit();
          }
        </script>
        <textarea class="on-your-mind" placeholder="What\'s on your mind?" name="txtDiary" id="txtDiary" class=""></textarea>
        ' . $save_msg . '
        <div class="fl"> <a class="list_a" target="_top" href="'.$this->f3->get('main_folder').'Diary">My Diary</a></div>
        <div class="fr"><input type="button" onClick="submit_message()" value="Post" /></div>
        <div class="cl"></div><br />';
    } else {
      $this->list_obj = new data_list;
      $this->editor_obj = new data_editor;

      $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
      $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
      $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
      $compliance_id = (isset($_GET['compliance_id']) ? $_GET['compliance_id'] : null);
      if(!$nav_month) {
        $def_month = 1;
        $nav_month = date("m");
        $nav_year = date("Y");
      }
      if($nav_month > 0) {
        $nav1 = "and MONTH(diary.date) = $nav_month";
      } else {
        $nav_month = "ALL Months";
      }
      if($nav_year > 0) {
        $nav2 = "and YEAR(diary.date) = $nav_year";
      } else {
        $nav_year = "ALL Years";
      }
      if($nav_month != "ALL Months") $for_months = "(for $kpi_for_month / $kpi_for_year)";
      if($download_xl) {
        session_start();
          //include('Classes/data_list.class.php');
          $xl_obj = new data_list;
          $xl_obj->dbi = $this->dbi;
          $xl_obj->multi_line_col = "Description";
          $xl_obj->sql = "select 
                         DATE_FORMAT(diary.date, '%d-%b-%Y') as `Date`,
                         DATE_FORMAT(diary.date_due, '%d-%b-%Y') as `Date Due`,
                         DATE_FORMAT(diary.date_completed, '%d-%b-%Y') as `Completed`,
                         CONCAT(users.name, ' ', users.surname) as `Added By`, diary.description as `Description`
                         from diary
                         left join users on users.id = diary.user_id
                         where 
                         " . ($compliance_id ? "compliance_id = $compliance_id"
                         : "compliance_id = 0 and (user_id = " . $_SESSION['user_id'] . " or diary.id in (select diary_id from diary_share where user_id = " . $_SESSION['user_id'] . "))") . "
                         $nav1 $nav2 
                         order by date DESC;
          ";
          $xl_obj->sql_xl('diary.xlsx');
      } else {
        $hide_comments = (isset($_GET['hide_comments']) ? $_GET['hide_comments'] : null);
        if($compliance_id) {
          $sql = "select title from compliance where id = $compliance_id";
                      $result = $this->dbi->query($sql);
          if($myrow = $result->fetch_assoc()) {
            $str .= "<h3>Diary Entries for " . $myrow['title'] . "</h3>";
          }
        }
        $str .= '<style>
        table.forum_item td {
          border-width: 1px;
          padding: 4px;
          border-style: solid;
          border-color: #DDDDDD;
        }
        table.forum_item tr:hover, table.forum_item {
          background-color: #D5FEFF;
        }
        th {text-align: left !important; padding: 4px;}' . ($hide_comments ? '' : 'table.forum_item tr:nth-child(3n+1) { background-color: #DDDDDD; }') . '
        table.forum_item tr {background-color: #EFEFEF;}
        .comment {
          border-top: 2px solid #CCCCCC;
          padding: 6px;
        }
        .comment:hover {
          border-top: 2px solid #AF1923;
          background-color: #FFF5C3;
        }
        .comment_head { color: #407929; }
        </style>';

        $view_mode = (isset($_GET['view_mode']) ? $_GET['view_mode'] : null);
        $share_id = (isset($_GET['share_id']) ? $_GET['share_id'] : null);
        $nav = new navbar;
        $filter_box = '
          <script language="JavaScript">
            function report_filter() {
              document.getElementById("hdnReportFilter").value=1
              document.frmFilter.submit()
            }
          </script>
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="compliance_id" id="compliance_id" value="'.$compliance_id.'">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
          <input type="hidden" name="view_mode" id="view_mode" value="'.$view_mode.'">
          <div class="form-wrapper" style="width: 725px;">
          <div class="form-header">Filter (Showing Entries During ' . "$nav_month / $nav_year" . ')</div>
          <div  style="padding: 10px;">
          '.$nav->month_year(2016).'
          <input onClick="report_filter()" type="button" value="Go" /> 
          </div>
          </form>
        ';
        if($def_month) {
          $filter_box .= ' 
            <script language="JavaScript">
              change_selDate()
            </script>
          ';
        }
        $qry_str = "&selDateMonth=$nav_month&selDateYear=$nav_year" . ($compliance_id ? "&compliance_id=$compliance_id" : "");
        if($view_mode) {
          $hdnAddID = (isset($_POST['hdnAddID']) ? $_POST['hdnAddID'] : null);
          $hdnDeleteID = (isset($_POST['hdnDeleteID']) ? $_POST['hdnDeleteID'] : null);
          if($hdnAddID) {
            $comment = $this->dbi->real_access_modetring($_POST['txtComment']);
            $sql = "insert into diary_comments(diary_id, user_id, date_added, description) values ($hdnAddID, ".$_SESSION['user_id'].", now(), '$comment')";
            $result = $this->dbi->query($sql);
            $msg = "Comment Added...";
          } else if($hdnDeleteID) {
            $sql = "delete from diary_comments where id = $hdnDeleteID";
            $result = $this->dbi->query($sql);
            $msg = "Comment Deleted...";
          }
          if($msg) $str .= $this->message($msg, 3000);
          $str .= '
                <input type="hidden" name="txtComment" id="txtComment">
                <input type="hidden" name="hdnAddID" id="hdnAddID">
                <input type="hidden" name="hdnDeleteID" id="hdnDeleteID">
                <input type="hidden" name="idin" id="idin">
                <script language="JavaScript">
                  function add_comment(id) {
                    if(document.getElementById("txtComment"+id).value) {
                      document.getElementById("hdnAddID").value = id
                      document.getElementById("txtComment").value = document.getElementById("txtComment"+id).value
                      document.frmEdit.submit()
                    }
                  }
                  function delete_comment(id) {
                    if(confirm("Delete this Comment?")) {
                      document.getElementById("hdnDeleteID").value = id
                      document.frmEdit.submit()
                    }
                  }
                  function edit_item(id) {
                    document.getElementById("idin").value = id;
                    document.frmEdit.action="'.$this->f3->get('main_folder').'Diary?hdnReportFilter=1&selDateMonth='.$_GET['selDateMonth'].'&selDateYear='.$_GET['selDateYear'].'"
                    document.frmEdit.submit();
                  }
                </script>
          ';
          $str .= '<div class="cl"></div>';
          $str .= $filter_box . "</div>";
          $sql = "select diary.id as `diary_id`, CONCAT(DATE_FORMAT(diary.date, '%d-%b-%Y'),
                         if(diary.date_due, CONCAT('<br />', 'DUE: ', DATE_FORMAT(diary.date_due, '%d-%b-%Y')), ''),
                         if(diary.date_completed, CONCAT('<br />', 'CMP: ', DATE_FORMAT(diary.date_completed, '%d-%b-%Y')), '')) as `date`,
                         if(users.id != " . $_SESSION['user_id'] . ", CONCAT(users.name, ' ', users.surname), 'Me') as `added_by`,
                         REPLACE(diary.description, '\n', '<br />') as `description`,
                         if(users.id = " . $_SESSION['user_id'] . ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."JavaScript:edit_item(', diary.id, ')\">Edit</a>'), ' ') as `edit`
                         from diary
                         left join users on users.id = diary.user_id
                         where
                         " . ($compliance_id ? "compliance_id = $compliance_id"
                         : "compliance_id = 0 and (user_id = " . $_SESSION['user_id'] . " or diary.id in (select diary_id from diary_share where user_id = " . $_SESSION['user_id'] . "))") . "
                         $nav1 $nav2 
                         order by date DESC;";
                             $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'Diary'.($compliance_id ? "?compliance_id=$compliance_id" : "").'"><< Diary Edit Mode >></a> ' . (!$hide_comments ? '<a class="list_a" href="'.$this->f3->get('main_folder').'Diary?view_mode=1&hide_comments=1&'.$qry_str.'">>> Hide Comments <<</a>' : '<a class="list_a" href="'.$this->f3->get('main_folder').'Diary?view_mode=1'.$qry_str.'"><< Show Comments >></a>') . '   <a class="list_a" title="Download Excel" href="'.$this->f3->get('main_folder').'Diary?view_mode=1'.$qry_str.'&download_xl=1">Download Excel</a>';
          $result = $this->dbi->query($sql);
          $str .= "<p>" . ($result->num_rows ? "Showing " . $result->num_rows. " Entries" : "No Records Found") . "...</p>";
          $str .= ($result->num_rows ? '<table class="forum_item">' : 'If you can\'t see your latest entries, try viewing last months...');
          $item_count = 0;
          while($myrow = $result->fetch_assoc()) {
            $item_count++;
            $diary_id = $myrow['diary_id']; 
            $date = $myrow['date']; 
            $added_by = $myrow['added_by']; 
            $description = $myrow['description']; 
            $shared_with = $myrow['shared_with']; 
            $edit = $myrow['edit']; 
            if(($hide_comments && $item_count == 1) || !$hide_comments) $str .= "<tr><th>Date</th><th><nobr>Added By</nobr></th><th>Description</th>" . ($compliance_id ? "" : "<th>Shared With</th><th>Share</th>") . "<th>Edit</th></tr>";
            $str .= "<tr><td><nobr>$date</nobr></td><td>$added_by</td><td>$description</td>";
            if(!$compliance_id) {
              $str .= "<td>";
              $sql = "select CONCAT(users.name, ' ', users.surname) as `shared_with`
                      from diary_share
                      inner join users on users.id = diary_share.user_id
                      where diary_id = $diary_id";
                              $result2 = $this->dbi->query($sql);
              while($myrow2 = $result2->fetch_assoc()) {
                $shared_with = $myrow2['shared_with'];
                $str .= "[$shared_with] ";
              }
              $str .= '</td><td><a class="list_a" href="Diary?share_id='.$diary_id.'">Share</a></td>';
            }
            $str .= '<td>'.$edit.'</td></tr>';
            if(!$hide_comments) {
              $str .= '<tr><td colspan="6" style="border-bottom: 5px solid #CCCCCC;">
                    <div class="fl" style="width: 80%;" >
                    <b>Comments</b><br />
                    <textarea placeholder="Write a comment here..." name="txtComment'.$diary_id.'"  id="txtComment'.$diary_id.'" style="width: 100%; height: 70px;"></textarea>
                    </div>
                    <div class="fl" style="padding-left: 10px;">
                    <br />
                    <input type="button" name="btnSave" id="btnSave" value="Add" onClick="add_comment('.$diary_id.')" />
                    </div>
                    <div class="cl"></div>
                    ';
                  $sql = "
                      select
                      diary_comments.id as idin,
                      diary_comments.date_added as `added_on`,
                      if(users.id = ".$_SESSION['user_id'].",'I', CONCAT(users.name, ' ', users.surname)) as `added_by`,
                      REPLACE(diary_comments.description, '\n', '<br />') as `comment`,
                      CONCAT('<a class=\"list_a\" href=\"JavaScript:delete_comment(', diary_comments.id, ')\">Delete Comment</a>') as `delete`
                      from diary_comments
                      inner join users on users.id = diary_comments.user_id
                      where diary_id = $diary_id
                      ";
                                      $result2 = $this->dbi->query($sql);
              while($myrow2 = $result2->fetch_assoc()) {
                $id_added = $myrow2['idin'];
                $added_on = Date("d-M-Y", strtotime($myrow2['added_on']));
                $added_time = Date("H:i", strtotime($myrow2['added_on']));
                $added_by = $myrow2['added_by'];
                $comment = $myrow2['comment'];
                $delete = $myrow2['delete'];
                $str .= "<div class=\"comment\"><div class=\"fl comment_head\">On $added_on at $added_time, $added_by wrote:</div><div class=\"fr\">$delete</div>
                      <div class=\"cl\"></div>
                      $comment</div>";
              }
              $str .= '</td></tr>';
            }
          }
        } else if($share_id) {
          $share_with_id = (isset($_GET['share_with_id']) ? $_GET['share_with_id'] : null);
          $share_remove_id = (isset($_GET['share_remove_id']) ? $_GET['share_remove_id'] : null);
          if($share_with_id) {
            $sql = "insert into diary_share (diary_id, user_id) values ($share_id, $share_with_id);";
            $this->dbi->query($sql);
            if($this->dbi->affected_rows == 1) $msg = "Share Added";
          } else if($share_remove_id) {
            $sql = "delete from diary_share where diary_id = $share_id and user_id = $share_remove_id;";
                  $this->dbi->query($sql);
            $msg = "Share Removed <a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Diary?share_id=$share_id&share_with_id=$share_remove_id\">Undo</a>";
          }
          $txtFind = (isset($_POST['txtFind']) ? $_POST['txtFind'] : null);
          $sql = "select 
                  CONCAT(DATE_FORMAT(date, '%d-%b-%Y')) as `date`,
                  CONCAT(DATE_FORMAT(date_due, '%d-%b-%Y')) as `date_due`,
                  CONCAT(DATE_FORMAT(date_completed, '%d-%b-%Y')) as `date_completed`,
                  REPLACE(description, '\n', '<br />') as `description`
                  from diary
                  where id = $share_id
          ";
          $result = $this->dbi->query($sql);
          if($myrow = $result->fetch_assoc()) {
            $date = $myrow['date'];
            $date_due = $myrow['date_due'];
            $date_completed = $myrow['date_completed'];
            $description = $myrow['description'];
            $str .= '<div class="fl">
            <div class="form-wrapper" style="width: 725px;">
            <div class="form-header">Share Diary Entry</div>
            <div class="form-content">
            <a class="list_a" title="Back to Diary..." href="'.$this->f3->get('main_folder').'Diary"><<</a>  <b>Date:</b> ' . $date . ($date_due ? " :: <b>Due:</b> $date_due" : "") . ($date_due ? " :: <b>Completed:</b> $date_completed " : "") . "<hr /><p>$description</p><hr />";
          }
          $str .= '<h3>Search for a person by entering all or part of their name:</h3>
                <p><input maxlength="50" name="txtFind" id="search" type="text" class="search_box" value="" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg
                . '</p><div class="cl"></div>';
          if($txtFind) {
            $search = $txtFind;
            $search = "
              where (users.name LIKE '%$search%'
              or users.surname LIKE '%$search%'
              or users.email LIKE '%$search%'
              or users.employee_id LIKE '%$search%'
              or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
              and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') and users.id != ".$_SESSION['user_id']." and users.id not in (select user_id from diary_share where diary_id = $share_id)";
            $this->list_obj->show_num_records = 1;
            $this->list_obj->sql = "
                    select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                    phone as `Phone`, users.email as `Email`,
                    CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Diary?share_id=$share_id&share_with_id=', users.id, '\">Select Person</a>') as `**` from users
                    left join states on states.id = users.state
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                    inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'HR' or lookup_fields.value = 'PERSON'
                    $search";
                          $str .= $this->list_obj->draw_list();
          }
          $str .= "</div></div>";
          $this->list_obj->title = "Previous Shares";
          $this->list_obj->sql = "
                  select users.id as idin, CONCAT(users.name, ' ', users.surname) as `Name`,
                  CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Diary?share_id=$share_id&share_with_id=', users.id, '\">Select Person</a>') as `**` from users
                  where users.id in (select user_id from diary_share where diary_id in (select id from diary where user_id = ".$_SESSION['user_id']."))
                  and users.id not in (select user_id from diary_share where diary_id = $share_id)";
                      $str .= $this->list_obj->draw_list();
          $str .= "</div>";
          $str .= '<div class="fl" style="padding-left: 25px;">' . $msg;
          $this->list_obj->title = "Shared With";
          $this->list_obj->sql = "select concat(users.name, ' ',  users.surname) as `Shared With`,
                            CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Diary?share_id=$share_id&share_remove_id=', users.id, '\">Remove Share</a>') as `**`
                            from diary_share inner join users on users.id = diary_share.user_id where diary_share.diary_id = $share_id";
                $str .= $this->list_obj->draw_list();
          $str .= "<h3>Please Note:</h3>Diary Entries are automatically shared with your manager. Wrong Person? <a class=\"list_a\" href=\"".$this->f3->get('main_folder')."my_account.php?select_manager=1\">Click Here to select your manager.</a>";
          $str .= '</div>';
          $str .= "<div class=\"cl\"></div>";
        } else {
          $this->list_obj->sql = "select id as `idin`, 
                            CONCAT(DATE_FORMAT(date, '%d-%b-%Y'),
                            if(date_due, CONCAT('<br />', 'DUE: ', DATE_FORMAT(date_due, '%d-%b-%Y')), ''),
                            if(date_completed, CONCAT('<br />', 'CMP: ', DATE_FORMAT(date_completed, '%d-%b-%Y')), '')) as `Entry Date      `,
                            REPLACE(description, '\n', '<br />') as `Description`, 'Edit' as `*`, 'Delete' as `!`
                            " . ($compliance_id ? "" : ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Diary?share_id=', id, '\">Share</a>') as `Share`") . "
                            from diary
                            where 
                            " . ($compliance_id ? " compliance_id = $compliance_id "
                            : "compliance_id = 0 and (user_id = " . $_SESSION['user_id'] . ")") . "
                            $nav1 $nav2 
                            order by date DESC;";
          $this->editor_obj->table = "diary";
          $this->editor_obj->xtra_id_name = "user_id";
          $this->editor_obj->xtra_id = $_SESSION['user_id'];
          if($compliance_id) {
            $this->editor_obj->xtra_id_name2 = "compliance_id";
            $this->editor_obj->xtra_id2 = $compliance_id;
          }
          $style = 'style="width: 200px;"';
          $style_small = 'style="width: 120px;"';
          $this->editor_obj->form_attributes = array(
                     array("calDate", "calDateDue", "calDateCompleted", "txaDescription"),
                     array("Date", "Date Due", "Date Completed", "Description"),
                     array("date", "date_due", "date_completed", "description"),
                     array("", "", "", ""),
                     array($style . ' value="'.date('d-M-Y').'"', $style, $style, 'style="width: 700px; height: 150px;"'),
                     array("c", "n", "n", "c")
            );
          $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
          );
          $this->editor_obj->form_template = '
            <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
            <div class="fl"><nobr>tcalDateDue</nobr><br />calDateDue</div>
            <div class="fl"><nobr>tcalDateCompleted</nobr><br />calDateCompleted</div>
            <div class="cl"></div>
            <nobr>ttxaDescription</nobr><br />txaDescription
            <div class="cl"></div>
            ' . $this->editor_obj->button_list();
          $this->editor_obj->editor_template = '
            <div class="cl"></div>
            <div class="fl">
            <div class="form-wrapper" style="width: 725px;">
            <div class="form-header">
            Diary
            <div class="fr" style="margin-right: 5px; margin-top: -2px;"><a class="list_a" title="Download Excel" href="'.$this->f3->get('main_folder').'Diary?view_mode=1'.$qry_str.'&download_xl=1">Download Excel</a> <a class="list_a" title="View Mode" href="'.$this->f3->get('main_folder').'Diary?view_mode=1'.$qry_str.'">View Mode</a></div>
            </div>
            <div class="cl"></div>
            <div class="form-content">
            editor_form
            </div>
            </div>
            </div>  <div class="fl" style="padding-left: 15px; max-width: 50%">
            ' . $filter_box . '
            </div>
            editor_list
            </div>
            <div class="cl"></div>';


          $str .= $this->editor_obj->draw_data_editor($this->list_obj);
          if($_POST["hdnAction"] == "add_record" && !$compliance_id) {
            $share_id = $this->editor_obj->last_insert_id;
            $sql = "select users.id as `idin` from users left join associations on associations.parent_user_id = users.id
                    where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
            $result = $this->dbi->query($sql);
            if($myrow = $result->fetch_assoc()) {
              $manager_id = $myrow['idin'];
              $sql = "insert into diary_share (diary_id, user_id) values ($share_id, $manager_id);";
              $this->dbi->query($sql);
            }
          }
        }
      }
    }
    return $str;


  }
}
    
?>
