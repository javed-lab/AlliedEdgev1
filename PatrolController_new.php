<?php
class PatrolController extends Controller {
  protected $f3;

  function __construct($f3) {
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->db_init();
  }
  

  function MyPatrols() {
  }
  
  function PatrolManager() {
    $str = '';
//    return $str;


    $notification_id = (isset($_GET['notification_id']) ? $_GET['notification_id'] : null);

    if($notification_id) {
      $sql = "update patrols set operator_notified = 1, time_dispatched = now() where id = $notification_id;";
      $this->dbi->query($sql);
      echo "Notification Sent";
      exit;
    }

    $time_id = (isset($_GET["time_id"]) ? $_GET["time_id"] : null);

    if($time_id) {
      $start_time = (isset($_GET["start_time"]) ? $_GET["start_time"] : null);
      $finish_time = (isset($_GET["finish_time"]) ? $_GET["finish_time"] : null);
      $dispatch_time = (isset($_GET["dispatch_time"]) ? $_GET["dispatch_time"] : null);
      $sql = "update patrols set
      time_on_site = '$start_time', time_off_site = '$finish_time',
      time_dispatched = concat(date(time_dispatched), ' $dispatch_time')
      where id = $time_id";
      $this->dbi->query($sql);
      exit;
    }

    $more_id = (isset($_GET['more_id']) ? $_GET['more_id'] : null);
    if($more_id) {
/*
       patrols.date_time as `Request Date/Time`,
       DATE_FORMAT(patrols.time_dispatched, '%H:%i') as `Dispatch`,
*/

      $sql = "
         select
         CONCAT(REPLACE(REPLACE(CONCAT(
         if(lookup_fields.item_name = '', '', CONCAT('<h3>', lookup_fields.item_name, if(users5.id IS NULL, '', CONCAT(' @ ', users5.name, ' ', users5.surname)), '</h3>')),
         if(users.id IS NULL, '', CONCAT('Assigned To: &nbsp;', users.name, ' ', users.surname)),
         if(users6.id IS NULL, '', CONCAT('<br />Patrol Car: &nbsp;', users6.employee_id, ' - ', users6.name, ' ', users6.surname)),
         if(users4.id IS NULL, '', CONCAT('<br />Alarm Company: &nbsp;', users4.name, ' ', users4.surname, '(', users4.phone, ')')),
         if(patrols.job_number = '', '', CONCAT('<br />Job No: &nbsp;', patrols.job_number)),
         if(patrols.zone_no = '', '', CONCAT('<br />Zone No: &nbsp;', patrols.zone_no)),
         if(patrols.key_id = '', '', CONCAT('<br />Key No: &nbsp;', patrols.key_id)),
         if(patrols.alarm_code = '', '', CONCAT('<br />Alarm Code: &nbsp;', patrols.alarm_code)),
         if(patrols.docket_number = '', '', CONCAT('<br />Docket Number: &nbsp;', patrols.docket_number)),
         if(patrols.operator_notified = 0, '', CONCAT('<br /><nobr>Dispatch Time: ', DATE_FORMAT(patrols.time_dispatched, '%H:%i'), '</nobr><br />')),
         if(patrols.time_on_site = '00:00:00' and patrols.time_off_site = '00:00:00', '', CONCAT('<br />Time On: ', DATE_FORMAT(patrols.time_on_site, '%H:%i'), ' &nbsp; Time Off: ', DATE_FORMAT(patrols.time_off_site, '%H:%i'))),
         if(patrols.request_description = '', '', CONCAT('<br /><br />Request Description:<br/>', patrols.request_description)),
         if(patrols.operator_description = '', '', CONCAT('<br /><br />Operator Description:<br/>', patrols.operator_description)),
         if(patrols.control_room_notes = '', '', CONCAT('<br /><br />Control Room Notes:<br/>', patrols.control_room_notes)))
        , '\'', '&#8217;'), '\"', '&quot;'))
         as `result`
         from patrols
         left join users on users.id = patrols.assigned_to_id
         left join users4 on users4.id = patrols.alarm_company_id
         left join users5 on users5.id = patrols.site_id
         left join users6 on users6.id = patrols.patrol_car_id
         left join lookup_fields on lookup_fields.id = patrols.service_type_id
         where patrols.id = $more_id
         ";
      $str .= $this->get_sql_result($sql);

      echo $str;
      exit;
    }

    $staff_sql = $this->user_dropdown(107,108);
    $site_sql = $this->user_dropdown(384);
    $alarm_company_sql = $this->user_dropdown(403);
    $car_sql = $this->user_dropdown(530);
    $car_sql = str_replace("CONCAT(users.name, ' ', users.surname)", "CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname)", $car_sql);

      $str = '
      <style>
        #form_content {
          display: none;
        }
        #a_form_content {
          color: #0000AA;
          background-color: transparent !important;
        }
        #a_form_content:hover {
          color: #0000AA;
          background-color: transparent !important;
          border-color: white;

        }
      </style>
      <script>
      
      function show_hide_item(id, open_text, closed_text) {
        if(document.getElementById(id).style.display == "block") {
          document.getElementById(id).style.display = "none";
          document.getElementById("a_" + id).innerHTML = open_text;
          //document.getElementById("filter_message").style.display = "none";
          document.cookie = id + "=closed; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        } else {
          document.getElementById(id).style.display = "block";
          //document.getElementById("filter_message").style.display = "block";
          document.getElementById("a_" + id).innerHTML = closed_text;
          document.cookie = id + "=open; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        }
      }
      function get_cookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(";");
        for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == " ") {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
          }
        }
        return "";
      }
      function send_notication(id) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Patrols/PatrolManager",
              data:{ notification_id: id } ,
              success:function(msg) {
                document.getElementById("send" + id).innerHTML = msg
                var today = new Date();
                var hours = today.getHours() < 10 ? "0" + today.getHours() : today.getHours();
                var minutes = today.getMinutes() < 10 ? "0" + today.getMinutes() : today.getMinutes();
                var the_time = hours + ":" + minutes
                document.getElementById("D" + id).value = the_time
              }
        } );
      }
      function more_info(id) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Patrols/PatrolManager",
              data:{ more_id: id } ,
              success:function(msg) {
                open_modal(msg)
              }
        } );
      }
      
      function save_times(time_id) {
        start_time = document.getElementById("S" + time_id).value
        finish_time = document.getElementById("F" + time_id).value
        dispatch_time = document.getElementById("D" + time_id).value
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Patrols/PatrolManager",
              data:{time_id: time_id, start_time: start_time, finish_time: finish_time, dispatch_time: dispatch_time } ,
              success:function(msg) {
                document.getElementById("message").innerHTML = msg
              }
        } );
      }
      
      
      </script>
      ';

    
         //as `Description`
         
        
    /*$this->list_obj->sql = "select patrols.id as `idin`,
       CONCAT('<div class=\"list_a\" onClick=\"open_modal(',
       QUOTE(REPLACE(REPLACE(CONCAT(
       if(patrols.request_description = '', '', CONCAT('<br /><br />Request Description:<br/>', patrols.request_description)),
       if(patrols.control_room_notes = '', '', CONCAT('<br /><br />Control Room Notes:<br/>', patrols.control_room_notes))), '\'', '&#8217;'), '\"', '&quot;'))
       , ')\">Click for More</div>'
       ) as `More Information`
       "; */
      
    $this->list_obj->sql = "select patrols.id as `idin`,
       'Edit' as `*`, 'Delete' as `!`,
       CONCAT('<a id=\"send', patrols.id, '\" class=\"list_a\" href=\"JavaScript:send_notication(', patrols.id, ')\">', if(patrols.operator_notified, 'Res', 'S'), 'end Notification</a>') as `Notification`,
       CONCAT(patrols.job_number, '<br />', lookup_fields.item_name) as `Job Number<br />Service Type`,
       CONCAT(users.name, ' ', users.surname) as `Assigned To`, 
       CONCAT(users5.name, ' ', users5.surname) as `Site`,
       patrols.date_time as `Request Date/Time`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(D', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"D', patrols.id, '\" name=\"D', patrols.id, '\"  placeholder=\"Dispatch\"   value=\"', DATE_FORMAT(patrols.time_dispatched, '%H:%i'), '\" /></span>') as `Dispatch`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(S', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"S', patrols.id, '\" name=\"S', patrols.id, '\"  placeholder=\"Start\"   value=\"', DATE_FORMAT(patrols.time_on_site, '%H:%i'), '\" /></span>') as `Time on Site`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(F', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"F', patrols.id, '\" name=\"F', patrols.id, '\"  placeholder=\"Finish\"   value=\"', DATE_FORMAT(patrols.time_off_site, '%H:%i'), '\" /></span>') as `Time Off Site`,
       CONCAT('<a class=\"list_a\" onClick=\"more_info(', patrols.id, ')\">Click for More</a>') as `More Information`
       from patrols
       left join users on users.id = patrols.assigned_to_id
       left join users3 on users3.id = patrols.control_room_operator_id
       left join users4 on users4.id = patrols.alarm_company_id
       left join users5 on users5.id = patrols.site_id
       left join users6 on users6.id = patrols.patrol_car_id
     	 left join lookup_fields on lookup_fields.id = patrols.service_type_id
       ";

      
       //$this->list_obj->sql = "select '\\\\\'' as `test`";
       
//return "<textarea>{$this->list_obj->sql}</textarea>";
    $this->editor_obj->table = "patrols";
    $style_small = 'style="width: 100px;"';
    $style = 'style="width: 130px;"';
    $style_large = 'style="width: 170px;"';
    $notes_style = 'class="textbox note_textbox"';

    $this->editor_obj->custom_field = "control_room_operator_id";
    $this->editor_obj->custom_value = $_SESSION['user_id'];



  $this->editor_obj->form_attributes = array(
      array("selServiceType", "cmbAssignedTo", "cmbAlarmCompany", "cmbSite", "cmbPatrolCar", "txtJobNumber", "txaOperatorDescription", "txtZoneNo", "txtKeyId", "txtAlarmCode", "ti2TimeOnSite", "ti2TimeOffSite", "txaRequestDescription", "txaControlRoomNotes"),
      array("Service Type", "Assigned To", "Alarm Company", "Site", "Patrol Car", "Job Number", "Operator Description of Results", "Zone No", "Key No", "Alarm Code", "Time On Site", "Time Off Site", "Description of the Request", "Control Room Notes"),
      array("service_type_id", "assigned_to_id", "alarm_company_id", "site_id", "patrol_car_id", "job_number", "operator_description", "zone_no", "key_id", "alarm_code", "time_on_site", "time_off_site", "request_description", "control_room_notes"),
      array($this->get_lookup('patrol_service_type'), $staff_sql, $alarm_company_sql, $site_sql, $car_sql, "", "", "", "", "", "", "", "", ""),
      array($style, $style_large, $style, $style_large, $style_large, $style, $notes_style, $style_small, $style, $style, $style_small, $style_small, $notes_style, $notes_style),
      array("n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n")
    );
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset"),
      array("cmdAdd", "cmdSave", "cmdReset"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
    );
    $this->editor_obj->form_template = '
    <div class="form-wrapper" style="width: 100%;">
      <div class="form-header" style="">
      Patrols &nbsp; &nbsp; <a id="a_form_content" class="list_a" href="JavaScript:show_hide_item(\'form_content\', \'Open Form &gt;&gt;\', \'&lt;&lt; Close Form\');">Open Form &gt;&gt;</a>
      <span id="message"></span>
      
      </div>
      <div class="form_content" id="form_content">

        <div class="fl"><nobr>tselServiceType</nobr><br />selServiceType</div>
        <div class="fl"><nobr>ttxtJobNumber</nobr><br />txtJobNumber</div>
        <div class="fl"><nobr>tcmbAssignedTo</nobr><br />cmbAssignedTo</div>
        <div class="fl"><nobr>tcmbSite</nobr><br />cmbSite</div>
        <div class="fl"><nobr>tcmbPatrolCar</nobr><br />cmbPatrolCar</div>
        <div class="fl"><nobr>ttxtKeyId</nobr><br />txtKeyId</div>
        <div class="fl"><nobr>tcmbAlarmCompany</nobr><br />cmbAlarmCompany</div>
        <div class="fl"><nobr>ttxtAlarmCode</nobr><br />txtAlarmCode</div>
        <div class="fl"><nobr>ttxtZoneNo</nobr><br />txtZoneNo</div>
        <div class="fl"><nobr>tti2TimeOnSite</nobr><br />ti2TimeOnSite</div>
        <div class="fl"><nobr>tti2TimeOffSite</nobr><br />ti2TimeOffSite</div>

        <div class="cl"></div>
        <nobr>ttxaRequestDescription</nobr><br />txaRequestDescription<br />
        <nobr>ttxaOperatorDescription</nobr><br />txaOperatorDescription<br />
        <nobr>ttxaControlRoomNotes</nobr><br />txaControlRoomNotes<br />
        <div class="cl"></div>
        <div class="fl">'.$this->editor_obj->button_list().'</div>
        <div class="cl"></div>

      </div>
    </div>';
    
            
    $this->editor_obj->editor_template = '
    <div id="xtras">editor_form</div><div class="cl"></div>editor_list';
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    
    if(!$_POST['idin']) {
      $str .= "
      <script>
      //document.getElementById('cmbEnteredBy').value = '{$_SESSION['full_name']}'; 
      //document.getElementById('hdncmbEnteredBy').value = '{$_SESSION['user_id']}';
      //document.getElementById('cmbControlRoomOperator').value = '{$_SESSION['full_name']}'; 
      //document.getElementById('hdncmbControlRoomOperator').value = '{$_SESSION['user_id']}';
      //document.getElementById('calDate').value = '".date('d-M-Y')."';

      </script>
      ";
    }
    $action = $this->action;
    
    if($action == "add_record" || $action == "save_record") {
      if($action == "add_record") {
        $save_id = $this->editor_obj->last_insert_id;
        $sql = "update patrols set date_time = now() where id = $save_id";
        $this->dbi->query($sql);
      } else if($action == "save_record") {
        $save_id = $this->editor_obj->idin;
      }

      if($save_id) {
        
      }
    }
    
    
    $str .= '
    <script>
    var test = get_cookie("form_content");
    ';

    if($_POST['idin'] && $action != "delete_record" && $_COOKIE['form_content'] == 'closed') {
      $str .= '
        show_hide_item(\'form_content\', \'Open Form &gt;&gt;\', \'&lt;&lt; Close Form\');
      ';
    }

    $str .= '
    if(test == "open") {
      show_hide_item(\'form_content\', \'Open Form &gt;&gt;\', \'&lt;&lt; Close Form\');
    }
    ';
    $str .= '</script>';
    
    return $str;
  }
  
  
}

?>