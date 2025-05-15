<?php

class MeetingMinutes extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
    $this->next_meeting_sql = "
      (select
      DATE_FORMAT(
        if(meeting_templates.days_between_meetings = 31
      ,
         if(
         ADDDATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01') , MOD((7+(DAYOFWEEK(meeting_templates.date))-DAYOFWEEK(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'))),7) + (7 * (if(ceil(day(meeting_templates.date) / 7) = 5, 4, ceil(day(meeting_templates.date) / 7)) - 1))) < CURDATE()
         ,
         ADDDATE(DATE_ADD(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'), INTERVAL 1 MONTH) , MOD((7+(DAYOFWEEK(meeting_templates.date))-DAYOFWEEK(DATE_ADD(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'), INTERVAL 1 MONTH))),7) + (7 * (if(ceil(day(meeting_templates.date) / 7) = 5, 4, ceil(day(meeting_templates.date) / 7)) - 1)))
         ,
          ADDDATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01') , MOD((7+(DAYOFWEEK(meeting_templates.date))-DAYOFWEEK(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'))),7) + (7 * (if(ceil(day(meeting_templates.date) / 7) = 5, 4, ceil(day(meeting_templates.date) / 7)) - 1)))
         )
      ,
        DATE_ADD(CURDATE(), INTERVAL (if(DAYOFWEEK(meeting_templates.date) >= DAYOFWEEK(CURDATE()), DAYOFWEEK(meeting_templates.date) - DAYOFWEEK(CURDATE()), 7 - (DAYOFWEEK(CURDATE()) - DAYOFWEEK(meeting_templates.date))))
        + if(ceil(abs(datediff(meeting_templates.date, CURDATE())) / 7) % 2 = 0 or meeting_templates.days_between_meetings = 7, 0, 7) DAY)
      ), '%d-%b-%Y'))
      AS `Next Meeting Date`";


    $this->meeting_agenda_id_action = (isset($_REQUEST['meeting_agenda_id_action']) ? $_REQUEST['meeting_agenda_id_action'] : null);
    $this->meeting_agenda_id_comments = (isset($_REQUEST['meeting_agenda_id_comments']) ? $_REQUEST['meeting_agenda_id_comments'] : null);
    $this->meeting_id = (isset($_REQUEST['meeting_id']) ? $_REQUEST['meeting_id'] : null);
    $this->view_mode = (isset($_REQUEST['view_mode']) ? $_REQUEST['view_mode'] : null);
    $this->user_id = (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null);

    $this->mode_nav = '<div class="fr"><a class="list_a" href="' . $this->f3->get('full_url') . 'MeetingMinutes/Meetings?meeting_id='.$this->meeting_id.($this->view_mode ? "" : "&view_mode=1").($this->meeting_agenda_id_action || $this->meeting_agenda_id_action ? ($this->meeting_agenda_id_action ? "meeting_agenda_id_action" : "meeting_agenda_id_comments")."={$this->id_use}" : "").'">'.($this->view_mode ? "Edit Mode" : "View Mode").'</a></div><div class="cl"></div>';

    $complete = (isset($_REQUEST['complete']) ? $_REQUEST['complete'] : null);
    $incomplete = (isset($_REQUEST['incomplete']) ? $_REQUEST['incomplete'] : null);
    $this->str = "";
    if($complete || $incomplete) {
      $id = ($complete ? $complete : $incomplete);
      $date_set = ($complete ? "now()" : "'0000-00-00'");
      $sql = "update meeting_agenda_action_items set date_completed = $date_set where id = $id";
      $this->dbi->query($sql);
      $this->str = $this->message("Item Set to ".($complete ? "Completed" : "Incomplete")."...", 2000);
      echo $this->redirect($this->f3->get('main_folder') . "MeetingMinutes/Meetings?meeting_id={$this->meeting_id}" . (isset($_GET['show_min']) ? "&show_min=" . $_GET['show_min'] : "" ) . "&meeting_agenda_id_action={$this->meeting_agenda_id_action}");
    }

  }

  function Tasks() {
      $str = $this->str;
      $manager = (isset($_GET["manager"]) ? $_GET["manager"] : null);
      
      $this->list_obj->title = ($manager ? "Tasks I've Assigned" : "My Tasks");
      
      
      $this->list_obj->sql = "select
      
      if(users4.id IS NOT NULL, CONCAT(users4.name, ' ', users4.surname), '&nbsp;') as `Assigned By`,
      CONCAT(
        if(users.id IS NOT NULL, CONCAT('<nobr>', users.name, ' ', users.surname, '</nobr><br />'), ''),
        if(users2.id IS NOT NULL, CONCAT('<nobr>', users2.name, ' ', users2.surname, '</nobr><br />'), ''),
        if(users3.id IS NOT NULL, CONCAT('<nobr>', users3.name, ' ', users3.surname, '</nobr>'), '')
      ) as `Assigned To`,
      CONCAT(
        if(meetings.id IS NOT NULL, meetings.title, ''),
        if(meeting_templates.id IS NOT NULL, meeting_templates.title, '')
      ) as `Meeting`
      
      , meeting_agenda_items.title as `Discussion Item`, meeting_agenda_action_items.item_name as `Action Item`, 
      DATE_FORMAT(meeting_agenda_action_items.date_due, '%a %d/%b/%Y') as `Date Due`,
      
      if(meeting_agenda_action_items.date_completed = '0000-00-00',
        concat('<div style=\"color: ',
              if(DATEDIFF(meeting_agenda_action_items.date_due, now()) <= 0,
                CONCAT('red\">Due ', if(DATEDIFF(meeting_agenda_action_items.date_due, now()) = 0,
                  CONCAT('Today.'),
                  CONCAT(ABS(DATEDIFF(meeting_agenda_action_items.date_due, now())), ' Days Ago'))),
                if(DATEDIFF(meeting_agenda_action_items.date_due, now()) <= 28,
                  CONCAT('orange\">', DATEDIFF(meeting_agenda_action_items.date_due, now()), ' Days Remaining'),
                  'green\">OK')), '</div>'),
        concat('<div style=\"color: ',
                CONCAT('#000099\">Completed ', if(DATEDIFF(meeting_agenda_action_items.date_completed, now()) = 0,
                  CONCAT('Today'),
                  CONCAT(
                    IF(ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())) = 1,
                      'Yesterday',
                      CONCAT(ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())), ' Days Ago')
                    )
                  )))
                )
      ) as `Time`,
      
      meeting_agenda_action_items.date_completed as `Date Completed`,
      if(meeting_agenda_action_items.date_completed = '0000-00-00',
        CONCAT('<a class=\"list_a\" href=\"?complete=', meeting_agenda_action_items.id, '\">Set to Completed</a>'),
        CONCAT('<a class=\"list_a\" href=\"?incomplete=', meeting_agenda_action_items.id, '\">Set to Not Completed</a>')
      ) as `Change To`
      from meeting_agenda_action_items
      inner join meeting_agenda_items on meeting_agenda_items.id = meeting_agenda_action_items.meeting_agenda_id
      left join meetings on meetings.id = meeting_agenda_items.meeting_id
      left join meeting_templates on meeting_templates.id = meeting_agenda_items.meeting_template_id
      left join users on users.id = meeting_agenda_action_items.assigned_to1
      left join users2 on users2.id = meeting_agenda_action_items.assigned_to2
      left join users3 on users3.id = meeting_agenda_action_items.assigned_to3
      left join users4 on users4.id = meeting_agenda_items.user_id
      where " . ($manager ? "users4.id=" . $_SESSION['user_id'] : "(meeting_agenda_action_items.assigned_to1 = " . $_SESSION['user_id'] . " or meeting_agenda_action_items.assigned_to2 = " . $_SESSION['user_id'] . " or meeting_agenda_action_items.assigned_to3 = " . $_SESSION['user_id'] . ")") . "
      order by meeting_agenda_action_items.date_completed, meeting_agenda_action_items.date_due
      ;";

/*      if(meetings.id IS NOT NULL,
      CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id, '&view_mode=1\">View</a> <a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id, '\">Edit</a>'), '') as `Meeting Minutes`*/
//      order by meetings.title
// and meeting_agenda_action_items.date_completed = '0000-00-00'
      
  //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
      $str .= $this->list_obj->draw_list();
    return $str;

      /*$sql = "select meetings.id, meeting_template_id, meetings.title, meetings.date
      from meetings
      left join users on users.id = meetings.site_id
      where meetings.id in (select meeting_id from meetings_attendees where user_id = {$_SESSION['user_id']});";

      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $meeting_date = $myrow['id'];
          $meeting_title = $myrow['title'];
          $str .= "<h3>$meeting_title</h3>";

          if($myrow['meeting_template_id']) {
            $meeting_idin = $myrow['meeting_template_id'];
            $id_use = "meeting_template_id";
          } else {
            $meeting_idin = $myrow['id'];
            $id_use = "meeting_id";
          }
          $this->list_obj->sql = "select meeting_agenda_items.title as `Discussion Item`
          from meeting_agenda_action_items
          inner join meeting_agenda_items on meeting_agenda_items.id = meeting_agenda_action_items.meeting_agenda_id
          
          where meeting_agenda_items.$id_use = $meeting_idin";
          //$str .= $this->list_obj->sql;
          $str .= $this->list_obj->draw_list();
          
          
        }
      }*/


  }
  
  function Meetings() {
    $is_admin = $this->f3->get('is_admin');
    $str = $this->str;
    $meeting_agenda_id_action = $this->meeting_agenda_id_action;
    $meeting_agenda_id_comments = $this->meeting_agenda_id_comments;
    $meeting_id = $this->meeting_id;
    $view_mode = $this->view_mode;
    
    $id_use = ($meeting_agenda_id_action ? $meeting_agenda_id_action : $meeting_agenda_id_comments);
    
    
    if($meeting_agenda_id_comments || $meeting_agenda_id_action) {
      $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
      $str .= '
        <style>
          body:hover {
              background-color: #EEFFFF !important;
          }
        </style>
        <script>
          function filter() {
            if(save()) this.form.submit()
          }
        </script>
      ';
      
      $tbl = ($meeting_agenda_id_action ? "meeting_agenda_action_items" : "meeting_comments");

      $str .= '<h5>' . ($meeting_agenda_id_action ? "Action Items" : "Comments") . '</h5>';

      $this->editor_obj->table = "$tbl";
      $this->editor_obj->xtra_id_name = "meeting_agenda_id";
      $this->editor_obj->xtra_id = $id_use;

      $this->editor_obj->alert_style = "position: absolute !important; width: 80%; top: 0px; left: 0; right: 0; margin-left: auto; margin-right: auto; border: solid 1px green;background-color:#FFFFDD;color:red;text-align: center; padding: 10px; font-size: 12px;";
      
      $reset_location = "window.location=location.protocol + '//' + location.host + location.pathname + '?show_min=1&meeting_id=$meeting_id&".($meeting_agenda_id_action ? "meeting_agenda_id_action" : "meeting_agenda_id_comments")."=$id_use';";
      
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "$reset_location"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );
      $style = 'style="width:' . ($meeting_agenda_id_action ? "400" : "800") . 'px;"';
      $style_small = 'style="width: 120px;"';
      if($meeting_agenda_id_action) {
        $this->list_obj->sql = "select if(users.id IS NULL and users2.id IS NULL and users3.id IS NULL, 'Not Assigned', 
        CONCAT(
        if(users.id IS NULL, '', CONCAT('[', users.name, ' ', users.surname, '] ')),
        if(users2.id IS NULL, '', CONCAT('[', users2.name, ' ', users2.surname, '] ')),
        if(users3.id IS NULL, '', CONCAT('[', users3.name, ' ', users3.surname, ']'))
        )) as `Assigned To`,
        $tbl.item_name as `Action Item`, $tbl.id as `idin`, DATE_FORMAT($tbl.date_due, '%a %d/%b/%Y') as `Date Due`" . ($view_mode ? "" : ", $tbl.date_completed as `Date Completed`, 'Edit' as `*`, 'Delete' as `!` ") . ",
        if(meeting_agenda_action_items.date_completed = '0000-00-00',
          CONCAT('<a class=\"list_a\" href=\"?show_min=1&meeting_agenda_id_action=$meeting_agenda_id_action&meeting_id=$meeting_id&complete=', meeting_agenda_action_items.id, '\">Set to Completed</a>'),
          CONCAT('<a class=\"list_a\" href=\"?show_min=1&meeting_agenda_id_action=$meeting_agenda_id_action&meeting_id=$meeting_id&incomplete=', meeting_agenda_action_items.id, '\">Set to Not Completed</a>')
        ) as `Change To`

        from $tbl
        left join users on users.id = $tbl.assigned_to1
        left join users2 on users2.id = $tbl.assigned_to2
        left join users3 on users3.id = $tbl.assigned_to3
        where $tbl.meeting_agenda_id = $id_use
        order by sort_order;";
        
        //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
        
        if(!$view_mode) {
          $tmp_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all select users.id, CONCAT(users.name, ' ', users.surname) as `item_name`
                    from meetings_attendees
                    inner join users on users.id = meetings_attendees.user_id
                    inner join meetings on meetings.id = meetings_attendees.meeting_id
                    where meetings.id = $meeting_id
                    ) a order by item_name;";
          $this->editor_obj->form_template = '
              <div class="fl"><nobr>tselAssignedTo1</nobr><br />selAssignedTo1</div>
              <div class="fl"><nobr>tselAssignedTo2</nobr><br />selAssignedTo2</div>
              <div class="fl"><nobr>tselAssignedTo3</nobr><br />selAssignedTo3</div>
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName</div>
              <div class="fl"><nobr>tcalDateDue</nobr><br />calDateDue</div>
              <div class="fl"><nobr>tcalDateCompleted</nobr><br />calDateCompleted</div><br />'.$this->editor_obj->button_list().'
              <div class="cl"></div>';
          $this->editor_obj->form_attributes = array(
            array("selAssignedTo1", "selAssignedTo2", "selAssignedTo3", "txtItemName", "calDateDue", "calDateCompleted"),
            array("Assigned To", "Assigned To", "Assigned To", "Action Item", "Date Due", "Date Completed"),
            array("assigned_to1", "assigned_to2", "assigned_to3", "item_name", "date_due", "date_completed"),
            array($tmp_sql, $tmp_sql, $tmp_sql, "", "", ""),
            array("", "", "", $style, $style_small, $style_small),
            array("n", "n", "n", "c", "c", "n")
          );
        }

      } else {
        $this->editor_obj->form_template = '
            <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName</div><br />'.$this->editor_obj->button_list().'
            <div class="cl"></div>';
        $this->editor_obj->custom_field = "user_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->list_obj->sql = "select $tbl.id as `idin`, CONCAT(users.name, ' ', users.surname) as `Comment By`,
        $tbl.item_name as `Comment`, if(users.id = {$_SESSION['user_id']}, 'Edit', '') as `*`, if(users.id = {$_SESSION['user_id']}, 'Delete', '') as `!`
        from $tbl
        left join users on users.id = $tbl.user_id
        where $tbl.meeting_agenda_id = $id_use
        order by sort_order;";
        $this->editor_obj->form_attributes = array(
          array("txtItemName"),
          array("Comment"),
          array("item_name"),
          array(""),
          array($style),
          array("c")
        );
      }

      $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
      if($action == "add_record") {
        $save_id = $this->editor_obj->last_insert_id;
        $max_sort = 0;
        $sql = "select max(sort_order) as `max_sort` from $tbl where meeting_agenda_id = $id_use";
        if($result = $this->dbi->query($sql)) {
          $myrow = $result->fetch_assoc();
          if($result->num_rows) {
            $max_sort = $myrow['max_sort'];
          }
        }
        $max_sort += 1;
        $this->dbi->query("update $tbl set sort_order = $max_sort where id = $save_id");
        echo $this->redirect($_SERVER['REQUEST_URI']);
      } else if($action == "delete_record") {
        $this->dbi->query("UPDATE $tbl e,
                           (SELECT @n := 0) m
                           SET e.sort_order = @n := @n + 1 where meeting_agenda_id = $id_use");
      }    
//parent.window.frames[window.name].name 
      $str .= ($view_mode ? $this->list_obj->draw_list() : $this->editor_obj->draw_data_editor($this->list_obj));
      //$str .= '<input type="button" value="test" onClick="JavaScript:resize_frame(parent.document.getElementById(parent.window.frames[window.name].name));" />';
      //$str .= '<input type="button" value="test2" onClick="JavaScript:alert(parent.document.getElementById(parent.window.frames[window.name].name).id);" />';
              $str .= "<script>
          //document.getElementById('calDateDue').onclick = function() {
             //alert(parent.window.frames[window.name].name)
             //NewCssCal('calDateDue', 'ddMMMyyyy')
             //resize_frame(parent.window.frames[window.name].name)
          //};
          </script>";

    } else if($meeting_id) {
      $target_dir = $this->f3->get('upload_folder') . "meetings/$meeting_id/";
      
      $date_cond = "and ((date_closed = '0000-00-00' and date_opened <= (select date from meetings where id = $meeting_id)) or (date_closed >= (select date from meetings where id = $meeting_id) and date_opened <= (select date from meetings where id = $meeting_id)))";
      
      if (!file_exists($target_dir)) {
        mkdir($target_dir);
        chmod($target_dir, 0755);
      }
      $msg = (isset($_REQUEST['msg']) ? $_REQUEST['msg'] : null);
      if($msg) $str .= $this->message($msg, 1500);
      $str .= '
      <style>
        .agenda_wrap {
          border-top: 2px solid #CCCCCC;
          border-bottom: 10px solid #DDDDDD;
          padding: 10px;
        }
        .agenda_wrap:hover {
            background-color: #FFFFDD;
        }
        .selected {
          background-color: #F4F4F4;
          border-top: 2px solid red;
        }
      </style>
      
      ';
      $edit_id = (isset($_REQUEST['edit_id']) ? $_REQUEST['edit_id'] : null);
      $this->list_obj->title = '<div class="fl">Meeting Minutes - Meeting Details</div>' . $this->mode_nav;
      $this->list_obj->sql = "select meetings.id as `idin`, meetings.title as `Title`, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `Site`,
      dayname(meetings.date) as `Day`, date as `Date`,
      TIME_FORMAT(meetings.start_time, '%l:%i %p') as `Start Time`, TIME_FORMAT(meetings.finish_time, '%l:%i %p') as `Finish Time`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager" . ($view_mode ? "?view_mode=1" : "") . "\">&lt;&lt; Back to Meetings</a>') as `&lt;&lt;` " . ($view_mode ? "" : ", CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?meeting_id=$meeting_id\">Attendees &gt;&gt;</a>') as `&gt;&gt;` ") . " from meetings
      left join users on users.id = meetings.site_id
      left join states on states.id = users.state
      where meetings.id = $meeting_id;";


      
      
      $str .= $this->list_obj->draw_list() . "<br /><br />";
      
      $sql = "select CONCAT(users.name, ' ', users.surname) as `name`, CONCAT('<span style=\"color: ', if(meetings_attendees.present = 1, 'blue;\">Present', 'red;\">Not Present'), '</span>') as `present`
                from meetings_attendees
                inner join users on users.id = meetings_attendees.user_id
                inner join meetings on meetings.id = meetings_attendees.meeting_id
                where meetings.id = $meeting_id
                order by CONCAT(users.name, ' ', users.surname);";
      $str .= "<h3>Attendees</h3><p>";
      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $name = $myrow['name'];
          $present = $myrow['present'];
          $str .= ($done ? "&nbsp; &nbsp; | &nbsp; &nbsp;" : "") . "$name ($present)";
          $done = 1;
        }
      }
      $str .= "</p>";
      
      $sql = "select id, meeting_template_id from meetings where id = $meeting_id;";
      if($result = $this->dbi->query($sql)) {
        if($myrow = $result->fetch_assoc()) {
          if($myrow['meeting_template_id']) {
            $meeting_idin = $myrow['meeting_template_id'];
            $id_use = "meeting_template_id";
          } else {
            $meeting_idin = $myrow['id'];
            $id_use = "meeting_id";
          }
        }
      }
      $action = (isset($_REQUEST['hdnAction']) ? $_REQUEST['hdnAction'] : null);
      if($action == "add_record") {
        if(!$edit_id) {
          $sql = "select max(sort_order)+1 as `sort_order` from meeting_agenda_items where $id_use = $meeting_idin";
          if($result = $this->dbi->query($sql)) {
            if($myrow = $result->fetch_assoc()) $sort_order = $myrow['sort_order'];
          }
          //$sort_order = (isset($sort_order) ? $sort_order : 1);
        }
        $selPresenter = $_POST['selPresenter'];
        $txtTitle = $this->dbi->real_escape_string($_POST['txtTitle']);
        $txaDescription = $this->dbi->real_escape_string($_POST['txaDescription']);
        $sql = ($edit_id ? "update meeting_agenda_items set user_id = $selPresenter, title = '$txtTitle', description = '$txaDescription' where id = $edit_id"
               : "insert into meeting_agenda_items($id_use, user_id, title, description, sort_order, date_opened) select '$meeting_idin', '$selPresenter', '$txtTitle', '$txaDescription', '$sort_order', date from meetings where id = $meeting_id"
               );
               //echo $sql;
               //exit;
         $result = $this->dbi->query($sql);
        $msg = "Discussion Topic " . ($edit_id ? "Saved" : "Added") . "...";
      } else if($action == "delete_record") {
        $idin = (isset($_POST['idin']) ? $_POST['idin'] : null);

        $sql = "select sort_order from meeting_agenda_items where id = $idin";
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) $sort_order = $myrow['sort_order'];
        }

        $sql = "select id from meeting_agenda_items where $id_use = $meeting_idin and sort_order > $sort_order order by sort_order";
        if($result = $this->dbi->query($sql)) {
          while($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $sqls .= "update meeting_agenda_items set sort_order = $sort_order where id = $id; ";
            $sort_order++;
          }
        }
        $sqls .= "delete from meeting_agenda_items where id = $idin;";
        
        $result = $this->dbi->multi_query($sqls);
        $msg = "Discussion Topic Deleted...";
      } else if($action == "close" || $action == "open") {
        $status_change_id = (isset($_GET['status_change_id']) ? $_GET['status_change_id'] : null);
        $sql = "update meeting_agenda_items set date_closed = " . ($action == "close" ? "(select date from meetings where id = $meeting_id)" : "'0000-00-00'") . " where id = $status_change_id;";
        $result = $this->dbi->query($sql);
        $msg = "Discussion Topic " . ($action == "close" ? "Closed" : "Re-Opened");
      }
      
      if($action == 'u' || $action == 'd' || $action == 't' || $action == 'b') {
        $move_id = (isset($_GET['move_id']) ? $_GET['move_id'] : null);
        $sql = "select sort_order from meeting_agenda_items where id = $move_id";
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) $sort_order = $myrow['sort_order'];
        }
        if($action == 'u' || $action == 'd') {
          $sql = "select id, sort_order from meeting_agenda_items where $id_use = $meeting_idin and sort_order ".($action == "u" ? "<" : ">")." $sort_order $date_cond order by sort_order ".($action == "u" ? "DESC" :"ASC") . " LIMIT 1;";
          if($result = $this->dbi->query($sql)) {
            if($myrow = $result->fetch_assoc()) {
              $sort_order_to = $myrow['sort_order'];
              $id_to = $myrow['id'];
            }
          }
          $sql = "update meeting_agenda_items set sort_order = $sort_order where id = $id_to; ";
          $sql .= "update meeting_agenda_items set sort_order = $sort_order_to where id = $move_id;";
        } else {
          $sql = "select ".($action == "b" ? "max" : "min")."(sort_order) as `maxsort` from meeting_agenda_items where $id_use = $meeting_idin;";
          if($result = $this->dbi->query($sql)) {
            if($myrow = $result->fetch_assoc()) {
              $sort_order_to = $myrow['maxsort'];
            }
          }
          $sql = "";
          $sql_test = "select id from meeting_agenda_items where $id_use = $meeting_idin and sort_order ".($action == "b" ? ">" : "<")." $sort_order
          and sort_order ".($action == "b" ? ">" : "<")." (select ".($action == "b" ? "min" : "max")."(sort_order) from meeting_agenda_items where $id_use = $meeting_idin $date_cond);";
          if($result = $this->dbi->query($sql_test)) {
            while($myrow = $result->fetch_assoc()) {
              $id = $myrow['id'];
              $sql .= "update meeting_agenda_items set sort_order = sort_order ".($action == "b" ? "-" : "+")." 1 where id = $id; ";
            }
          }
          
          $sql .= "update meeting_agenda_items set sort_order = $sort_order_to where id = $move_id;";
        }

        $result = $this->dbi->multi_query($sql);
  
        $msg = "Discussion Topic Moved...";

      }

      if($action == "add_record" || $action == 'delete_record' || $action == 'u' || $action == 'd' || $action == 't' || $action == 'b' || $action == 'close' || $action == 'open') $this->f3->reroute($this->f3->get('full_url') . "MeetingMinutes/Meetings?meeting_id=$meeting_id&msg=" . urlencode($msg));
      
      if(!$view_mode) {
        $form_obj = new input_form;
        // and present = 1
        $tmp_sql = "select users.id, CONCAT(users.name, ' ', users.surname) as `item_name` from users
                   where users.id in (select user_id from meetings_attendees where meeting_id = $meeting_id)
                   order by CONCAT(users.name, ' ', users.surname);";
        $form_obj->button_attributes = array(
          array("Add Discussion Topic"),
          array("cmdAdd", "cmdSave", "cmdReset"),
          array("if(add()) this.form.submit()"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        
        if($edit_id) {
          $form_obj->button_attributes[0][0] = "Save Discussion Topic";
          $form_obj->button_attributes[0][1] = "Cancel Edit";
          $form_obj->button_attributes[0][2] = "Delete Item";
          $form_obj->button_attributes[2][0] = "if(save()) this.form.submit()";
          $form_obj->button_attributes[2][1] = "window.location=location.protocol + '//' + location.host + location.pathname + '?meeting_id=$meeting_id';";
          $form_obj->button_attributes[2][2] = "delete_record($edit_id)";
        }
        
        $style = 'style="width: 800px;"';
        $form_obj->form_attributes = array(
          array("selPresenter", "txtTitle", "txaDescription"),
          array("Presenter", "Title", "Description"),
          array("", "", ""),
          array($tmp_sql, "", ""),
          array("", $style, 'style="width: 99%; height: 200px;"'),
          array("n", "c", "c")
        );
        $form_obj->form_template = '
        <div class="form-wrapper">
          <div class="form-header" style="">Discussion Topics</div>
          <div class="form-content" style="position: relative;">
            <div class="fl"><nobr>tselPresenter</nobr><br />selPresenter</div>
            <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
            <div class="cl"></div>
            <nobr>ttxaDescription</nobr><br />txaDescription<br />
            '.$form_obj->button_list().'
            
          </div>
        </div>';
        $str .= $form_obj->display_form() . "<br />";
      }
      
      $sql = "select meeting_agenda_items.id, user_id, title, description, CONCAT(users.name, ' ', users.surname) as `presenter`, date_format(meeting_agenda_items.date_opened, '%d-%b-%Y') as `date_opened`, date_format(meeting_agenda_items.date_closed, '%d-%b-%Y') as `date_closed`
      from meeting_agenda_items 
      left join users on users.id = meeting_agenda_items.user_id
      where $id_use = $meeting_idin $date_cond
      order by sort_order";
//        $str .= $sql;
$str .= "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>";   
$str .= "   
<h3>Tasks Assigned</h3>
<div class='row'>
";
      if($result = $this->dbi->query($sql)) {
        $itm_count = 0;
        while($myrow = $result->fetch_assoc()) {
          $itm_count++;
          $meeting_item_id = $myrow['id'];
          $title = $myrow['title'];
          $user_id = $myrow['user_id'];
          $description = $myrow['description'];
          $presenter = $myrow['presenter'];
          $date_opened = $myrow['date_opened'];
          $date_closed = $myrow['date_closed'];
          if($meeting_item_id == $edit_id) {
            $js_str = "document.getElementById('selPresenter').value = $user_id; document.getElementById('txtTitle').value = '$title'; document.getElementById('txaDescription').value = '" . substr(json_encode($description), 1, -1) . "'; ";
            $title .= ' <span style="color:red;">(Editing)</span>';
            $sxtra = 'selected';
          } else {
            $sxtra = "";
          }
         

          $str .= "
          <div class=\"agenda_wrap  $sxtra\"><h5 class=\"fl\">$itm_count. $title</h5>
          <div class=\"fr\"><strong>Date Opened: </strong>$date_opened " . ($date_closed != "" ? "&nbsp; &nbsp; &nbsp;<strong>Date Closed: </strong>$date_closed" : "") . " &nbsp; &nbsp; &nbsp;<strong>Presenter: </strong>$presenter " . 
          ($view_mode ? "" : "&nbsp; &nbsp; &nbsp;<a uk-tooltip=\"title: Change Status of this Discussion Topic.\" class=\"list_a\" href=\"Meetings?meeting_id=$meeting_id&status_change_id=$meeting_item_id&hdnAction=" . ($date_closed == "" ? "close\">Close" : "open\">Re-Open") . "</a>");
          $str .= ($meeting_item_id == $edit_id
          ? "<a class=\"list_a\" uk-tooltip=\"title: Cancel Editing this Discussion Topic.\" href=\"Meetings?meeting_id=$meeting_id\">Cancel Edit</a>"
          : "<a class=\"list_a\" uk-tooltip=\"title: Edit this Discussion Topic<br/>($title)\" href=\"Meetings?meeting_id=$meeting_id&edit_id=$meeting_item_id\">Edit Topic</a>");
      
        
      
          if(!$view_mode) {
            $str .= "&nbsp;";
            if($itm_count > 1) {
              if($itm_count > 2) $str .= "<a uk-tooltip=\"title: Move to Top\" href=\"Meetings?meeting_id=$meeting_id&move_id=$meeting_item_id&hdnAction=t\"><img style=\"vertical-align: middle; height: 20px;\" src=\"".$this->f3->get('img_folder')."sort_top.gif\" /></a>";
              $str .= "<a uk-tooltip=\"title: Move to Position ".($itm_count-1).".\" href=\"Meetings?meeting_id=$meeting_id&move_id=$meeting_item_id&hdnAction=u\"><img style=\"vertical-align: middle; height: 20px;\" src=\"".$this->f3->get('img_folder')."sort_up.gif\" /></a>";
            }
            if($itm_count < $result->num_rows) {
              $str .= "<a uk-tooltip=\"title: Move to Position ".($itm_count+1).".\" href=\"Meetings?meeting_id=$meeting_id&move_id=$meeting_item_id&hdnAction=d\"><img style=\"vertical-align: middle; height: 20px;\" src=\"".$this->f3->get('img_folder')."sort_down.gif\" /></a> ";
              if($itm_count < $result->num_rows - 1) $str .= "<a uk-tooltip=\"title: Move to Bottom\" href=\"Meetings?meeting_id=$meeting_id&move_id=$meeting_item_id&hdnAction=b\"><img style=\"vertical-align: middle; height: 20px;\" src=\"".$this->f3->get('img_folder')."sort_bottom.gif\" /></a>";
            }
          }

          $str .= "</div><div class=\"cl\"></div>" . nl2br($description) . "<br /><br />";
          
          
          //onLoad=\"resize_frame('frame_action$meeting_item_id');\"
         $str .= "<iframe name=\"frame_action$meeting_item_id\" id=\"frame_action$meeting_item_id\" src=\"" . $this->f3->get('main_folder') . "MeetingMinutes/Meetings?show_min=1&meeting_id=$meeting_id&meeting_agenda_id_action=$meeting_item_id".($view_mode ? "&view_mode=1" : "")."\" style=\"border: 0; width: 100%; height: 250px; display:inline-flex!important;\"  ></iframe>
          <iframe name=\"frame_comment$meeting_item_id\" id=\"frame_comment$meeting_item_id\" src=\"" . $this->f3->get('main_folder') . "MeetingMinutes/Meetings?show_min=1&meeting_id=$meeting_id&meeting_agenda_id_comments=$meeting_item_id\" style=\"border: 0; width: 100%;\"  onLoad=\"resize_frame('frame_comment$meeting_item_id');\"></iframe>";
          
          $str.= "</div>";
        
        }
      }
      $str .= ($js_str ? "<script>\r\n$js_str\r\n</script>" : "");
    }
    return $str;
  }
  
  function MeetingManager() {

    $meeting_id = $this->meeting_id;
    $view_mode = $this->view_mode;
    if($meeting_id) {
      $this->list_obj->title = "Attendees - Meeting Details";
      $this->list_obj->sql = "select meetings.id as `idin`, meetings.title as `Title`, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `Site`,
      dayname(meetings.date) as `Day`, date as `Date`,
      TIME_FORMAT(meetings.start_time, '%l:%i %p') as `Start Time`, TIME_FORMAT(meetings.finish_time, '%l:%i %p') as `Finish Time`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager" . ($view_mode ? "?view_mode=1" : "") . "\">&lt;&lt; Back to Meetings</a>') as `&lt;&lt;`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=$meeting_id" . ($view_mode ? "&view_mode=1" : "") . "\">Meeting Minutes &gt;&gt;</a>') as `&gt;&gt;` from meetings
      left join users on users.id = meetings.site_id
      left join states on states.id = users.state
      where meetings.id = $meeting_id;";

    //return "<textarea>{$this->list_obj->sql}</textarea>";

      $str .= $this->list_obj->draw_list() . "<br /><br />";


      $user_id = $this->user_id;
      $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
      $txtFind = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
      if($action == "edit_notes") {
        $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);
        $sql = "select CONCAT(name, ' ', surname) as `notes_for` from users where id = $user_id;";
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) {
            $notes_for = $myrow['notes_for'];
          }
        }
        $msg['edit_notes'] = "Notes for $notes_for";
        if($txtNotes) {
          $qry['edit_notes'] = "update meetings_attendees set notes = '$txtNotes' where meeting_id = $meeting_id and user_id = $user_id;";
          $msg['edit_notes'] .= " - Saved";
        } else {
          $qry['edit_notes'] = "";
        }
        $msg['edit_notes'] .= '
        <script>
          function save_note() {
            if(document.getElementById(\'txtNotes\').value) document.frmEdit.submit();
          }
        </script>
        
        <br /><textarea style="width: 98%; height: 100px;" name="txtNotes" id="txtNotes" placeholder="Enter Notes/Apologies Here">'.$txtNotes.'</textarea>
        <br /><input type="button" value="Save" onClick="save_note()" /><br />';
      }
      if($action) {
        $qry['add'] = "insert into meetings_attendees (meeting_id, user_id) values ($meeting_id, $user_id);";
        $qry['remove'] = "delete from meetings_attendees where meeting_id = $meeting_id and user_id = $user_id;";
        $qry['add_present'] = "update meetings_attendees set present = 1 where meeting_id = $meeting_id and user_id = $user_id;";
        $qry['remove_present'] = "update meetings_attendees set present = 0 where meeting_id = $meeting_id and user_id = $user_id;";
        $qry['create_meeting'] = "
          insert into meetings (meeting_template_id, title, site_id, date, start_time, finish_time) 
          select meetings.id, meetings.title as `Title`, users.id, $tmp_sql,
          meetings.start_time, meetings.finish_time from meetings
          left join users on users.id = meetings.site_id
          where meetings.id = $meeting_id
        ";
        $msg['add'] = "Staff Member Added";
        $msg['remove'] = "Staff Member Removed";
        $msg['add_present'] = "Staff Status Changed to Present";
        $msg['remove_present'] = "Staff Status Changed to Not Present";
        
        if($qry[$action] != "") $this->dbi->query($qry[$action]);

        if($action) echo $this->redirect($this->f3->get('main_folder').'MeetingMinutes/MeetingManager?meeting_id='.$meeting_id);

        $str .= "<div style=\"text-align: left; margin: 0;\" class=\"message\">{$msg[$action]}";
        
        $str .= '<br /><br />';
        if($action == 'remove') $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingManager?action=add&user_id='.$user_id.'&meeting_id='.$meeting_id.'">Undo</a>';
        $str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingManager?meeting_id='.$meeting_id.'">Continue...</a></div>';
        
        
      }
      if($action == "edit_notes") {
        $sql = "select notes from meetings_attendees where meeting_id = $meeting_id and user_id = $user_id;";
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) {
            $txtNotes = $myrow['notes'];
            $str .= '<script>document.getElementById("txtNotes").value="'.$txtNotes.'"</script>';
          }
        }
      }
        //" . ($view_mode ? "&view_mode=1" : "") . "
      $this->list_obj->title = "Current Attendees";
      $this->list_obj->sql = "select " . ($view_mode ? "" : "CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?action=edit_notes&user_id=', users.id, '&meeting_id=$meeting_id\">Notes</a>') as `Edit Notes`,") . "
      CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`, users.email as `Email`, users.phone as `Phone`, notes as `Notes/Apologies`" . ($view_mode ? "" : ",
                CONCAT('<a class=\"list_a warning\"  href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?action=remove&txtFind=$txtFind&user_id=', users.id, '&meeting_id=$meeting_id\">Remove</a>') as `Remove`") . ",
                CONCAT('<span style=\"color: ', if(meetings_attendees.present = 1, 'blue;\">Present', 'red;\">Not Present'), '</span>') as `Attended`
                " . ($view_mode ? "" : ", if(meetings_attendees.present = 1,
                CONCAT('<a class=\"list_a warning\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?action=remove_present&user_id=', users.id, '&meeting_id=$meeting_id\">Change to NOT Present</a>'),
                CONCAT('<a class=\"list_a ok\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?action=add_present&user_id=', users.id, '&meeting_id=$meeting_id\">Change to Present</a>')
                ) as `Change`") . "
                
                from meetings_attendees
                inner join users on users.id = meetings_attendees.user_id
                inner join meetings on meetings.id = meetings_attendees.meeting_id
                left join states on states.id = users.state
                where meetings.id = $meeting_id
                order by CONCAT(users.name, ' ', users.surname)";
      $str .= $this->list_obj->draw_list() . "<br /><br />";

      if(!$action && !$view_mode) {
        $str .= '<div class="fl">
        <div class="form-wrapper" style="width: 725px;">
        <div class="form-header">Select Attendees</div>
        <div class="form-content">';
        $str .= ($user_id
              ? '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingManager?meeting_id='.$meeting_id.'">Start Again</a>'
              : '<p><input placeholder="Enter Staff Member\'s Name" maxlength="70" name="txtFind" id="search" type="text" class="search_box" value="'.$_REQUEST['txtFind'].'" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg . '</p>'
              );
              $str .= '<div class="cl"></div></div>';
        if($txtFind) {
          $search = $txtFind;
          $search = "
            where (users.name LIKE '%$search%'
            or users.surname LIKE '%$search%'
            or users.email LIKE '%$search%'
            or users.employee_id LIKE '%$search%'
            or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
            ";
          $this->list_obj->show_num_records = 1;
          $this->list_obj->sql = "
                  select distinct(users.id) as idin, users.employee_id as `Employee ID`, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                  phone as `Phone`, users.email as `Email`,
                  CONCAT('<a class=\"list_a ok\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?action=add&txtFind=$txtFind&user_id=', users.id, '&meeting_id=$meeting_id\">Add</a>') as `Add`
                  from users
                  left join states on states.id = users.state
                  left join user_status on user_status.id = users.user_status_id
                  inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                  inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'HR' or lookup_fields.value = 'PERSON'
                  $search
                  and user_status.item_name = 'ACTIVE'
                  order by CONCAT(users.name, ' ', users.surname)";
          $str .= "</div></div><div class=\"cl\"></div>" . $this->list_obj->draw_list();
        }
        $str .= "</div></div>";
        $str .= "<div class=\"cl\"></div>";
      }
      return $str;
    } else {
      $form_action = $_POST['hdnAction'];
      $this->list_obj->title = "Meetings";
      $tmp_sql = "select users.id, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `item_name` from users
                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users' and lookup_answers.lookup_field_id = 384
                 left join states on states.id = users.state
                 where users.user_status_id = (select id from user_status where item_name = 'ACTIVE') group by users.id
                 order by CONCAT(users.name, ' ', users.surname);";
      
      $this->list_obj->sql = "
      select meetings.id as `idin`, meetings.title as `Title`, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `Site`, 
      dayname(meetings.date) as `Day`, date as `Date`,
      TIME_FORMAT(meetings.start_time, '%l:%i %p') as `Start Time`, TIME_FORMAT(meetings.finish_time, '%l:%i %p') as `Finish Time`" . ($view_mode ? "" : ", 'Edit' as `*`, 'Delete' as `!`") . ",
      CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingManager?meeting_id=', meetings.id, '\">Attendees</a>') as `Attendees`, 
      CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id, '&view_mode=1\">View Meeting Minutes &gt;&gt;</a>') as `View`,
      CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id, '\">Edit Meeting Minutes &gt;&gt;</a>') as `Edit`
      from meetings
      left join users on users.id = meetings.site_id
      left join states on states.id = users.state
      where meetings.id in (select meeting_id from meetings_attendees where user_id = {$_SESSION['user_id']})
      order by date DESC";
      //return "<textarea>{$this->list_obj->sql}</textarea>";
//$_SESSION['u_level'] >= 1000 || $is_admin

      $this->editor_obj->table = "meetings";
      $style = 'style="width: 125px;"';
      $style_large = 'style="width: 250px;"';
      
      $this->editor_obj->form_attributes = array(
        array("selSiteId", "calDate", "ti2StartTime", "ti2FinishTime", "txtTitle"),
        array("Site", "Date", "Start Time", "Finish Time", "Title"),
        array("site_id", "date", "start_time", "finish_time", "title"),
        array($tmp_sql, "", "", "", ""),
        array('', $style . ' value="'.date('d-M-Y').'"', $style . ' value="'.date('H:i').'"', $style . ' value="'.date('H:i').'"', $style_large),
        array("n", "c", "c", "c", "c")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset", "Filter"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template= '
        <div class="form-wrapper" style="">
        <div class="form-header" style="">Meetings (Please use <u>24H time format</u>)</div>
        <div class="form-content">
          <div class="fl"><nobr>tselSiteId</nobr><br />selSiteId</div>
          <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
          <div class="fl"><nobr>tti2StartTime</nobr><br />ti2StartTime</div>
          <div class="fl"><nobr>tti2FinishTime</nobr><br />ti2FinishTime</div>
          <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
          <div class="cl"></div>
          '.$this->editor_obj->button_list().'
        </div>
        </div>';
      $this->editor_obj->editor_template= 'editor_form<div class="cl"></div>editor_list';
      $str .= ($view_mode ? $this->list_obj->draw_list() : $this->editor_obj->draw_data_editor($this->list_obj));
      if($form_action == 'add_record') {
        $save_id = $this->editor_obj->last_insert_id;
        $sql = "insert into meetings_attendees (meeting_id, user_id, present) values ($save_id, ".$_SESSION['user_id'].", '1');";
        //$str .= $sql;
        $this->dbi->query($sql);
      }
      return $str;
    
    }
  }

  function MeetingTemplates() {
    $meeting_id = $this->meeting_id;

    $next_meeting_sql_tmp = substr($this->next_meeting_sql,0,strrpos($this->next_meeting_sql,"AS `Next"));
    $next_meeting_sql_tmp = str_replace("%d-%b-%Y", "%Y-%m-%d", $next_meeting_sql_tmp);
    if($meeting_id) {


      $user_id = $this->user_id;
      $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
      $txtFind = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
      if($action == "create_meeting") {
        $sql = "select meetings.id from meetings inner join meeting_templates on meeting_templates.id = meetings.meeting_template_id where meetings.meeting_template_id = $meeting_id and meetings.date = $next_meeting_sql_tmp";
        
        if($result = $this->dbi->query($sql)) {
          if($result->num_rows) $action = "meeting_not_copied";
        }
      }
      if($action) {
        $qry['add'] = "insert into meeting_templates_attendees (meeting_id, user_id) values ($meeting_id, $user_id);";
        $qry['remove'] = "delete from meeting_templates_attendees where meeting_id = $meeting_id and user_id = $user_id;";
        $qry['create_meeting'] = "
          insert into meetings (meeting_template_id, title, site_id, date, start_time, finish_time) 
          select meeting_templates.id, meeting_templates.title as `Title`, users.id, $next_meeting_sql_tmp,
          meeting_templates.start_time, meeting_templates.finish_time from meeting_templates
          left join users on users.id = meeting_templates.site_id
          where meeting_templates.id = $meeting_id
        ";
        $msg['add'] = "Staff Member Added";
        $msg['remove'] = "Staff Member Removed";
        $msg['create_meeting'] = "Meeting Created";
        $msg['meeting_not_copied'] = "Meeting Already Created, Not Added...";
        
        //echo $qry['create_meeting'];
        
        if($action != "meeting_not_copied") $this->dbi->query($qry[$action]);
        $msg_str .= "<div style=\"text-align: left; margin: 0;\" class=\"message\">{$msg[$action]}";
        if($action == "create_meeting") {
          $last_id = $this->dbi->insert_id;
          $sql = "insert into meetings_attendees (meeting_id, user_id) select '$last_id', user_id from meeting_templates_attendees where meeting_id = $meeting_id";
          $this->dbi->query($sql);
        }
        
        if($action == 'add' || $action == 'remove') echo $this->redirect($this->f3->get('main_folder').'MeetingMinutes/MeetingTemplates?meeting_id='.$meeting_id);

        
        $msg_str .= '<br /><br />';
        if($action == 'remove') $msg_str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingTemplates?action=add&user_id='.$user_id.'&meeting_id='.$meeting_id.'">Undo</a>';
        $msg_str .= '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingTemplates?meeting_id='.$meeting_id.'">Continue...</a>';
        if($last_id) $msg_str .= "<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=$last_id\">Meeting Minutes &gt;&gt;</a>";
        $msg_str .= '</div>';
        
        
      }

      $this->list_obj->title = "Attendees - Meeting Template Details";
      $this->list_obj->sql = "select meeting_templates.id as `idin`, meeting_templates.title as `Title`, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `Site`,
      meeting_templates.date as `Start Date`, 
      CASE meeting_templates.days_between_meetings WHEN 7 THEN 'Weekly' WHEN 14 THEN 'Fortnightly' WHEN 31 THEN 'Monthly' END as `Frequency`, 
      dayname(meeting_templates.date) as `Day`, {$this->next_meeting_sql},
      TIME_FORMAT(meeting_templates.start_time, '%l:%i %p') as `Start Time`, TIME_FORMAT(meeting_templates.finish_time, '%l:%i %p') as `Finish Time`, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates\">&lt;&lt; Back to Meeting Templates</a>') as `&lt;&lt;`, if(meetings.date is null, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates?meeting_id=$meeting_id&action=create_meeting\">Create Next Meeting &gt;&gt;</a>'), CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id, '\">Meeting Minutes &gt;&gt;</a>')) as `&gt;&gt;` from meeting_templates
      left join users on users.id = meeting_templates.site_id
      left join states on states.id = users.state
      left join meetings on meetings.meeting_template_id = meeting_templates.id and meetings.date = $next_meeting_sql_tmp
      where meeting_templates.id = $meeting_id;";

    //return "<textarea>{$this->list_obj->sql}</textarea>";

      $str .= $this->list_obj->draw_list() . "<br /><br />$msg_str";


        
      $this->list_obj->title = "Current Attendees";
      $this->list_obj->sql = "select CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`, users.email as `Email`, users.phone as `Phone`,
                CONCAT('<a class=\"list_a warning\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates?action=remove&txtFind=$txtFind&user_id=', users.id, '&meeting_id=$meeting_id\">Remove</a>') as `Remove`
                from meeting_templates_attendees
                inner join users on users.id = meeting_templates_attendees.user_id
                inner join meeting_templates on meeting_templates.id = meeting_templates_attendees.meeting_id
                left join states on states.id = users.state
                where meeting_templates.id = $meeting_id
                order by CONCAT(users.name, ' ', users.surname)";
      $str .= $this->list_obj->draw_list() . "<br /><br />";

      if(!$action) {
        $str .= '<div class="fl">
        <div class="form-wrapper" style="width: 725px;">
        <div class="form-header">Select Attendees</div>
        <div class="form-content">';
        $str .= ($user_id
              ? '<a class="list_a" href="'.$this->f3->get('main_folder').'MeetingMinutes/MeetingTemplates?meeting_id='.$meeting_id.'">Start Again</a>'
              : '<p><input placeholder="Enter Staff Member\'s Name" maxlength="70" name="txtFind" id="search" type="text" class="search_box" value="'.$_REQUEST['txtFind'].'" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg . '</p>'
              );
              $str .= '<div class="cl"></div></div>';
        if($txtFind) {
          $search = $txtFind;
          $search = "
            where (users.name LIKE '%$search%'
            or users.surname LIKE '%$search%'
            or users.email LIKE '%$search%'
            or users.employee_id LIKE '%$search%'
            or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
            ";
          $this->list_obj->show_num_records = 1;
          $this->list_obj->sql = "
                  select distinct(users.id) as idin, users.employee_id as `Employee ID`, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                  phone as `Phone`, users.email as `Email`,
                  CONCAT('<a class=\"list_a ok\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates?action=add&txtFind=$txtFind&user_id=', users.id, '&meeting_id=$meeting_id\">Add</a>') as `Add`
                  from users
                  left join states on states.id = users.state
                  left join user_status on user_status.id = users.user_status_id
                  inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                  inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'HR' or lookup_fields.value = 'PERSON'
                  $search
                  and user_status.item_name = 'ACTIVE'";
          $str .= "</div></div><div class=\"cl\"></div>" . $this->list_obj->draw_list();
        }
        $str .= "</div></div>";
        $str .= "<div class=\"cl\"></div>";
      }
    } else {
      $this->list_obj->title = "Meeting Templates";
//      $tmp_sql = "select users.id, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `item_name` from users
//                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 401 and lookup_answers.table_assoc = 'users'
//                 left join states on states.id = users.state
//                 where users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
//                 order by CONCAT(users.name, ' ', users.surname);";
      
      $tmp_sql = "select users.id, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `item_name` from users
                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users' and lookup_answers.lookup_field_id = 384
                 left join states on states.id = users.state
                 where users.user_status_id = (select id from user_status where item_name = 'ACTIVE') group by users.id
                 order by CONCAT(users.name, ' ', users.surname);";
      
      
      $this->list_obj->sql = "
      select meeting_templates.id as `idin`, meeting_templates.title as `Title`, CONCAT(users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `Site`, meeting_templates.date as `Start Date`, 
      CASE meeting_templates.days_between_meetings WHEN 7 THEN 'Weekly' WHEN 14 THEN 'Fortnightly' WHEN 31 THEN 'Monthly' END as `Frequency`, 
      dayname(meeting_templates.date) as `Day`, {$this->next_meeting_sql},
      TIME_FORMAT(meeting_templates.start_time, '%l:%i %p') as `Start Time`, TIME_FORMAT(meeting_templates.finish_time, '%l:%i %p') as `Finish Time`, 'Edit' as `*`, 'Delete' as `!`,
      CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates?meeting_id=', meeting_templates.id, '\">Attendees</a>') as `Attendees`, if(meetings.date is null, CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/MeetingTemplates?meeting_id=', meeting_templates.id,'&action=create_meeting\">Create Next Meeting &gt;&gt;</a>'), CONCAT('<a class=\"list_a\" href=\"".$this->f3->get('main_folder')."MeetingMinutes/Meetings?meeting_id=', meetings.id,'\">Meeting Minutes &gt;&gt;</a>')) as `&gt;&gt;`
      from meeting_templates
      left join users on users.id = meeting_templates.site_id
      left join states on states.id = users.state
      left join meetings on meetings.meeting_template_id = meeting_templates.id and meetings.date = $next_meeting_sql_tmp

      ;";
      
      $this->editor_obj->table = "meeting_templates";
      $style = 'style="width: 125px;"';
      $style_large = 'style="width: 250px;"';
      
      $days = "select 0 as id, '--- Select ---' as item_name union all select '7', 'Weekly' union all select '14', 'Fortnightly' union all select '31', 'Monthly'";

      $this->editor_obj->form_attributes = array(
        array("selSiteId", "calDate", "selFrequency", "ti2StartTime", "ti2FinishTime", "txtTitle"),
        array("Site", "Start Date", "Frequency", "Start Time", "Finish Time", "Title"),
        array("site_id", "date", "days_between_meetings", "start_time", "finish_time", "title"),
        array($tmp_sql, "", $days, "", "", ""),
        array('', $style, $style, $style, $style, $style_large),
        array("n", "n", "n", "n", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset", "Filter"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template = '
        <div class="form-wrapper" style="">
        <div class="form-header" style="">Meeting Templates (Please use <u>24H time format</u>)</div>
        <div class="form-content">
          <div class="fl"><nobr>tselSiteId</nobr><br />selSiteId</div>
          <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
          <div class="fl"><nobr>tselFrequency</nobr><br />selFrequency</div>
          <div class="fl"><nobr>tti2StartTime</nobr><br />ti2StartTime</div>
          <div class="fl"><nobr>tti2FinishTime</nobr><br />ti2FinishTime</div>
          <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
          <div class="cl"></div>
          '.$this->editor_obj->button_list().'
        </div>
        </div>';
      $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    
    }
    
    
    
    return $str;
  }


  
}
    


    
?>
