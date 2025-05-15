<?php

class PatrolController extends Controller {

    protected $f3;
    var $img_folder;

    function __construct($f3) {
        $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $this->f3 = $f3;
        $this->list_obj = new data_list;
        $this->editor_obj = new data_editor;
        $this->db_init();
        $this->img_folder = "patrol_images";
    }

    function PatrolManager() {

        $str = '';
//    return $str;

        $email_pdf = (isset($_GET['email_pdf']) ? $_GET['email_pdf'] : null);

        $pdf = (isset($_GET['pdf']) ? $_GET['pdf'] : null);
        if ($pdf) {
            $idin = $pdf;
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->SetFont('Arial', '', 11);
            $pdf->setAutoPageBreak(false);

            $line_height = 5.5;
            $cell_width = 190;
            $startx = 10;
            $starty = 30;

            $pic_right = 95;

            $break_point = 285;

            $startx_indent = 15;
            $cell_width_indent = $cell_width - ($startx_indent - $startx);
            $image_height = 50;
//      if(DATE_FORMAT(patrols.time_dispatched, '%d-%b-%Y') = DATE_FORMAT(patrols.time_on_site, '%d-%b-%Y'), DATE_FORMAT(patrols.time_on_site, '%H:%i'), DATE_FORMAT(patrols.time_on_site, '%d-%b-%Y %H:%i')) as `time_on_site`,
//      if(DATE_FORMAT(patrols.time_dispatched, '%d-%b-%Y') = DATE_FORMAT(patrols.time_off_site, '%d-%b-%Y'), DATE_FORMAT(patrols.time_off_site, '%H:%i'), DATE_FORMAT(patrols.time_off_site, '%d-%b-%Y %H:%i')) as `time_off_site`,

            $sql = "
      select
      lookup_fields.item_name as `type`,
      CONCAT(users5.name, ' ', users5.surname) as `site`,
      CONCAT(users.name, ' ', users.surname) as `assigned_to`,
      CONCAT(users6.employee_id, ' - ', users6.name, ' ', users6.surname) as `patrol_car`,
      CONCAT(users4.name, ' ', users4.surname, '(', users4.phone, ')') as `alarm_company`,
      states.item_name as `state`,
      CONCAT(users5.address, ' ', users5.suburb, ' ', states.item_name, ' ', users5.postcode) as `address`,
      patrols.job_number,
      patrols.zone_no,
      patrols.key_id,
      patrols.alarm_code,
      patrols.id + 123 as `docket_number`,
      DATE_FORMAT(patrols.time_dispatched, '%d-%b-%Y %H:%i') as `time_dispatched`,
      if(patrols.time_on_site != '00:00:00', DATE_FORMAT(patrols.time_on_site, '%H:%i'), '') as `time_on_site`,
      if(patrols.time_off_site != '00:00:00', DATE_FORMAT(patrols.time_off_site, '%H:%i'), '') as `time_off_site`,
      patrols.request_description,
      patrols.operator_description, patrols.control_room_notes, patrols.gps_on, patrols.gps_off
      from patrols
      left join users on users.id = patrols.assigned_to_id
      left join users4 on users4.id = patrols.alarm_company_id
      left join users5 on users5.id = patrols.site_id
      left join states on states.id = users5.state
      left join users6 on users6.id = patrols.patrol_car_id
      left join lookup_fields on lookup_fields.id = patrols.service_type_id
      where patrols.id = $idin
      ";

            $oldy = $pdf->GetY();
            $pdf->SetXY($startx, $starty);
            $pdf->MultiCell($cell_width, 0, "", 0);

            $result = $this->dbi->query($sql);

            if ($myrow = $result->fetch_assoc()) {
                $yl = $starty;
                $type = $myrow['type'];
                $site = $myrow['site'];
                $address = $myrow['address'];
                $assigned_to = $myrow['assigned_to'];
                $patrol_car = $myrow['patrol_car'];
                $alarm_company = $myrow['alarm_company'];
                $job_number = $myrow['job_number'];
                $zone_no = $myrow['zone_no'];
                $key_id = $myrow['key_id'];
                $alarm_code = $myrow['alarm_code'];
                $docket_number = $myrow['docket_number'];
                $time_dispatched = $myrow['time_dispatched'];
                $time_on_site = $myrow['time_on_site'];
                $time_off_site = $myrow['time_off_site'];
                $request_description = $myrow['request_description'];
                $operator_description = $myrow['operator_description'];
                $control_room_notes = $myrow['control_room_notes'];

                $gps_on = $myrow['gps_on'];
                $gps_off = $myrow['gps_off'];

                $state = $myrow['state'];

                $pdf->SetTextColor(30, 30, 30);

                $heading = $type . ' @ ' . $site . "\r\nJob Number:   $job_number      Docket Number: $docket_number\r\nPatrol Driver: $assigned_to";
                $this->pdf_header($pdf, $heading);

                $pdf->SetXY($startx, $yl);

                if ($address) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "Site Address:   $address", 0);
                }

                if ($patrol_car) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "Patrol Car:   $patrol_car", 0);
                }

                if ($alarm_company) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "Alarm Company: $alarm_company", 0);
                }
                $yl = $pdf->GetY();
                $str = "";
                if ($zone_number)
                    $str .= "Zone Number: $zone_number";
                if ($alarm_code)
                    $str .= ($str ? "     " : "") . "Alarm Code: $alarm_code";
                if ($key_id)
                    $str .= ($str ? "     " : "") . "Key ID: $key_id";
                if ($zone_number)
                    $str .= ($str ? "     " : "" ) . ": $zone_number";

                if ($str) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, $str, 0);
                }

                if ($time_on_site) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "Time Dispatched:   $time_dispatched     Time On/Off: $time_on_site - $time_off_site", 0);
                }

                if ($request_description) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "\r\nRequest Description\r\n$request_description", 0);
                }

                if ($operator_description) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "\r\nOperator Description\r\n$operator_description", 0);
                }

                if ($control_room_notes) {
                    $yl = $pdf->GetY();
                    $pdf->SetXY($startx, $yl);
                    $pdf->MultiCell($cell_width, 6, "\r\nResponse Resolution\r\n$control_room_notes", 0);
                }


                /*
                  $items = explode("\r\n", $description);
                  foreach($items as $item) {
                  if(trim($item)) {
                  $yl = $pdf->GetY();
                  if($yl > $break_point) { $this->pdf_header($pdf, $heading); $yl = $starty; }
                  $pdf->SetXY($startx, $yl); 	$pdf->MultiCell($cell_width,6, utf8_decode($item),0);
                  }
                  }
                 */



                $target_dir = $this->f3->get('download_folder') . $this->img_folder;
                $height_add = 0;
                if (count(glob("$target_dir/$idin/*"))) {
                    $dir = new DirectoryIterator("$target_dir/$idin/");
                    $x = 0;
                    $file_list = Array();
                    foreach ($dir as $fileinfo) {
                        if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                            $x++;
                            $yl = $pdf->GetY() + $height_add;

                            if ($yl > $break_point - $image_height) {
                                $this->pdf_footer($pdf);
                                $this->pdf_header($pdf, $heading);
                                $yl = $starty;
                            }

                            $pdf->SetXY($startx, $yl);

                            $x1 = $startx + (!($x % 2) ? $pic_right : 0);

                            $pdf->Image("$target_dir/$idin/" . $fileinfo->getFilename(), $x1, $yl, 0, $image_height);
                            $height_add = + (($x % 2) ? 0 : $image_height);
                        }
                    }
                    $x++;
                    $height_add += (($x % 2) ? 0 : $image_height);
                }
                if ($gps_on) {
                    $yl = $pdf->GetY() + 10 + ($x ? $height_add : 0);
                    if ($yl > $break_point - $image_height) {
                        $this->pdf_footer($pdf);
                        $this->pdf_header($pdf, $heading);
                        $yl = $starty + 10;
                    }
                    $pdf->SetXY($startx - 1, $yl - 6);
                    $pdf->MultiCell($cell_width, 6, "GPS Sign On ($gps_on)", 0);
                    $url = 'https://maps.google.com/maps/api/staticmap?center=' . $gps_on . '&size=480x250&sensor=false&key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo&zoom=16&scale=2';
                    $img = $this->f3->get('upload_folder') . 'map.png';
                    file_put_contents($img, file_get_contents($url));
                    $pdf->Image($img, $startx - 1, $yl, 0, $image_height);

                    if ($gps_off) {
                        $pdf->SetXY(106, $yl - 6);
                        $pdf->MultiCell($cell_width, 6, "GPS Sign Off ($gps_off)", 0);
                        $url = 'https://maps.google.com/maps/api/staticmap?center=' . $gps_off . '&size=480x250&sensor=false&key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo&zoom=16';
                        $img = $this->f3->get('upload_folder') . 'map2.png';
                        file_put_contents($img, file_get_contents($url));
                        $pdf->Image($img, 106, $yl, 0, $image_height);
                    }
                }

                $this->pdf_footer($pdf);
            }

            $pdf->Output();
            exit;
        }

        $notification_id = (isset($_GET['notification_id']) ? $_GET['notification_id'] : null);

        if ($notification_id) {

            $sql = "
         select
         users.phone, users.phone2, CONCAT(lookup_fields.item_name, ' @ ', users2.name, ' ', users2.surname) as `details`
         from patrols
         inner join users on users.id = patrols.assigned_to_id
         left join users2 on users2.id = patrols.site_id
         left join lookup_fields on lookup_fields.id = patrols.service_type_id
         where patrols.id = $notification_id;";

            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {
                    $sms = new sms($this->dbi);
                    $phone = $myrow['phone'];
                    $phone2 = $myrow['phone2'];
                    if ($phone = $sms->process_phones($phone, $phone2)) {
                        $details = $myrow['details'];
                        $message = "$details. To confirm receipt, Visit " . $this->f3->get('full_url') . "MyPatrols";
                        $sms->send_message($phone, $message);
                        $msg = "Notification Sent";
                    } else {
                        $msg = "Invalid Phone Number, message not sent...";
                    }

                    $sql = "update patrols set operator_notified = 1, time_dispatched = now() where id = $notification_id;";
                    //echo $sql;
                    //die("testing");
                    $this->dbi->query($sql);
                }
            }
            echo $msg;
            exit;
        }

        $get_status = (isset($_GET['get_status']) ? $_GET['get_status'] : null);
        //$get_status = 1;

        if ($get_status) {
//              and '$date_time' > DATE_ADD(roster_times.start_date_time, INTERVAL -15 MINUTE) SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(date_time, CONCAT(DATE(date_time), ' ', time_on_site)  ) / 3600, 2))) as `time_diff`

            $sql = "select id, docket_number, date_time, ip_on, ip_off, DATE_FORMAT(patrols.time_on_site, '%H:%i') as `time_on_site`, DATE_FORMAT(patrols.time_off_site, '%H:%i') as `time_off_site`, patrols.time_opened,
      TIME_TO_SEC(TIMEDIFF(now(), time_dispatched))/60 as `time_diff`
      from patrols
      where time_dispatched != '0000-00-00 00:00:00'
      order by date_time DESC;";

            if ($result = $this->dbi->query($sql)) {
                $id = 0;
                while ($myrow = $result->fetch_assoc()) {
                    if ($id)
                        $str .= ",";
                    $id = $myrow['id'];
                    $docket_number = $myrow['docket_number'];
                    $ip_on = $myrow['ip_on'];
                    $ip_off = $myrow['ip_off'];
                    $date_time = $myrow['date_time'];
                    $time_diff = $myrow['time_diff'];
                    $time_on_site = $myrow['time_on_site'];
                    $time_off_site = $myrow['time_off_site'];
                    $time_opened = $myrow['time_opened'];

                    if ($ip_on && $ip_off) {
                        $col = 'green';
                        $txt = "C";
                    } else if ($ip_on && !$ip_off) {
                        $col = 'blue';
                        $txt = "O";
                    } else {
                        $col = ($time_diff < 30 ? ($time_opened == '0000-00-00 00:00:00' ? 'grey' : 'orange') : 'red');
                        $txt = ($time_diff < 30 ? 'P' : 'L');
                    }


                    //$str .= "document.getElementById('$id-1').className = '{$col}_bkg';document.getElementById('$id-1').innerHTML = '$txt';";
                    $str .= "$id-$col-$txt-$time_on_site-$time_off_site";
                }
            }
            //$js_str = $this->js_wrap($str);
            //return $str;
            echo $str;
            exit;
        }
        $time_id = (isset($_GET["time_id"]) ? $_GET["time_id"] : null);

        if ($time_id) {
            $start_time = (isset($_GET["start_time"]) ? $_GET["start_time"] : null);
            $finish_time = (isset($_GET["finish_time"]) ? $_GET["finish_time"] : null);
            $dispatch_time = (isset($_GET["dispatch_time"]) ? $_GET["dispatch_time"] : null);
            $sql = "update patrols set
      time_on_site = '$start_time', time_off_site = '$finish_time',
      time_dispatched = concat(date(time_dispatched), ' $dispatch_time')
      , ip_on=" . ($start_time == '00:00' && $finish_time == '00:00' ? "'', docket_number = '', time_opened = '0000-00-00 00:00:00'" : "'{$_SERVER['REMOTE_ADDR']}', docket_number = (id + 123)") . "
      where id = $time_id";
            //echo $sql;
            $this->dbi->query($sql);
            exit;
        }

        $more_id = (isset($_GET['more_id']) ? $_GET['more_id'] : null);
        if ($more_id) {
            /*
              patrols.date_time as `Request Date/Time`,
              DATE_FORMAT(patrols.time_dispatched, '%H:%i') as `Dispatch`,
             */

            $sql = "
         select
         CONCAT(REPLACE(REPLACE(CONCAT(
         if(lookup_fields.item_name = '', '', CONCAT('<h5>', lookup_fields.item_name, if(users5.id IS NULL, '', CONCAT(' @ ', users5.name, ' ', users5.surname)), '</h5>')),
         if(users.id IS NULL, '', CONCAT('Assigned To: &nbsp;', users.name, ' ', users.surname)),
         if(patrols.job_number = '', '', CONCAT(' &nbsp;Job No: &nbsp;', patrols.job_number)),
         if(users6.id IS NULL, '', CONCAT('<br/>Patrol Car: &nbsp;', users6.employee_id, ' - ', users6.name, ' ', users6.surname)),
         if(users4.id IS NULL, '', CONCAT('<br/>Alarm Company: &nbsp;', users4.name, ' ', users4.surname, '(', users4.phone, ')')),
         if(patrols.zone_no = '', '', CONCAT('<br/>Zone No: &nbsp;', patrols.zone_no)),
         if(patrols.key_id = '', '', CONCAT(' &nbsp; Key No: &nbsp;', patrols.key_id)),
         if(patrols.alarm_code = '', '', CONCAT(' &nbsp; Alarm Code: &nbsp;', patrols.alarm_code)),
         if(patrols.docket_number = '', '', CONCAT(' &nbsp; Docket Number: &nbsp;', patrols.docket_number)),
         
         if(patrols.operator_notified = 0, '', CONCAT('<br/><nobr>Dispatch Time: ', DATE_FORMAT(patrols.time_dispatched, '%H:%i'), '</nobr>')),
         if(patrols.time_opened = '0000-00-00 00:00:00', '', CONCAT(' &nbsp; <nobr>Time Opened: ', DATE_FORMAT(patrols.time_opened, '%H:%i'), '</nobr>')),
         
         if(patrols.time_on_site = '00:00:00' and patrols.time_off_site = '00:00:00', '', CONCAT('<br/>Time On: ', DATE_FORMAT(patrols.time_on_site, '%H:%i'), ' &nbsp; Time Off: ', DATE_FORMAT(patrols.time_off_site, '%H:%i'))),
         
         if(patrols.request_description = '', '', CONCAT('<br/><br/>Request Description:<br/>', patrols.request_description)),
         if(patrols.operator_description = '', '', CONCAT('<br/><br/>Operator Description:<br/>', patrols.operator_description)),
         if(patrols.control_room_notes = '', '', CONCAT('<br/><br/>Control Room Notes/Response Resolution:<br/>', patrols.control_room_notes)))
        , '\'', '&#8217;'), '\"', '&quot;'))
         as `result`,
         CONCAT(users5.address, ' ', users5.suburb, ' ', states.item_name, ' ', users5.postcode) as `address`,
         patrols.gps_on, patrols.gps_off

         from patrols
         left join users on users.id = patrols.assigned_to_id
         left join users4 on users4.id = patrols.alarm_company_id
         left join users5 on users5.id = patrols.site_id
         left join states on states.id = users5.state
         left join users6 on users6.id = patrols.patrol_car_id
         left join lookup_fields on lookup_fields.id = patrols.service_type_id
         where patrols.id = $more_id
         ";

            //$str .= $this->get_sql_result($sql);
            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {

                    $str = $myrow['result'];
                    $address = $myrow['address'];
                    $gps_on = $myrow['gps_on'];
                    $gps_off = $myrow['gps_off'];
                    $url_address = str_replace(" ", "+", urlencode($address));

                    $str .= '<br/><br/><table><tr><td>Location<br/>' . $address . '<br/><div class="map-responsive"><iframe width="480" height="250" src="https://maps.google.com/maps?width=480&amp;height=250&amp;hl=en&amp;q=' . $url_address . '&amp;ie=UTF8&amp;t=&amp;z=16&amp;iwloc=B&amp;output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe></div></td>';

                    if ($gps_on)
                        $str .= '<td>GPS ON<br/>' . $gps_on . '<br/><img src="https://maps.google.com/maps/api/staticmap?center=' . $gps_on . '&size=480x250&sensor=false&key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo&zoom=16" style="width: 480px; height: 250px;" /></td>';
                    if ($gps_off)
                        $str .= '<td>GPS OFF<br/>' . $gps_off . '<br/><img src="https://maps.google.com/maps/api/staticmap?center=' . $gps_off . '&size=480x250&sensor=false&key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo&zoom=16" style="width: 480px; height: 250px;" /></td>';
                    $str .= '</tr></table>';
                }
            }


            //echo $this->ta($sql);
            echo $str;
            exit;
        }


        $staff_sql = $this->user_dropdown(107, 108);
        $site_sql = $this->user_dropdown(384);
        $alarm_company_sql = $this->user_dropdown(403);
        $car_sql = "select id, name item_name from partrolcar_option"; //$this->user_dropdown(530);
        // $car_sql = str_replace("CONCAT(users.name, ' ', users.surname", "CONCAT(users.name, ' ', users.surname, if(users.phone, CONCAT('(', users.phone, ')'), '')", $car_sql);
//return $car_sql;
        $str .= '
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
        .third_textbox {
          width: 100%;
          height: 110px;
        }
        .note_thirds { 
          float: left;
          width: 32%;
          margin-bottom: 2px;
        }
        @media screen and (max-width: 1024px) {
          .note_thirds { width: 100%; }
          .third_textbox { height: 90px;}
        }
      </style>
      <script>
      
      function show_hide_item(id, open_text, closed_text) {
        if(document.getElementById(id).style.display == "block") {
          document.getElementById(id).style.display = "none";
          document.getElementById("a_" + id).innerHTML = open_text;
          //document.getElementById("filter_message").style.display = "none";
          document.getElementById("textadd").style.display = "none";
          document.cookie = id + "=closed; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        } else {
          document.getElementById(id).style.display = "block";
          //document.getElementById("filter_message").style.display = "block";
          document.getElementById("textadd").style.display = "inline-block";
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
              url:"' . $this->f3->get('main_folder') . 'Patrols/PatrolManager",
              data:{ notification_id: id } ,
              success:function(msg) {
                document.getElementById("send" + id).innerHTML = msg
                var today1 = new Date();
                //var today = today1.toLocaleString("en-US", {timeZone: "Australia/Sydney"});
                var today = new Date(today1.toLocaleString("en-US", {timeZone: "Australia/Sydney"}));
                var hours = today.getHours() < 10 ? "0" + today.getHours() : today.getHours();
                var minutes = today.getMinutes() < 10 ? "0" + today.getMinutes() : today.getMinutes();
                var the_time = hours + ":" + minutes
                document.getElementById("D" + id).value = the_time
                refresh_items()
              }
        } );
      }
      function more_info(id) {
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'Patrols/PatrolManager",
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
              url:"' . $this->f3->get('main_folder') . 'Patrols/PatrolManager",
              data:{time_id: time_id, start_time: start_time, finish_time: finish_time, dispatch_time: dispatch_time } ,
              success:function(msg) {
                document.getElementById("message").innerHTML = msg
              }
        } );
      }
      function add_text(str) {
        document.getElementById("txaRequestDescription").value += str.value + " "
        str.value = "";
      }
      </script>
      ';

        $str .= '<div id="test"></div>';
        //as `Description`


        /* $this->list_obj->sql = "select patrols.id as `idin`,
          CONCAT('<div class=\"list_a\" onClick=\"open_modal(',
          QUOTE(REPLACE(REPLACE(CONCAT(
          if(patrols.request_description = '', '', CONCAT('<br/><br/>Request Description:<br/>', patrols.request_description)),
          if(patrols.control_room_notes = '', '', CONCAT('<br/><br/>Control Room Notes:<br/>', patrols.control_room_notes))), '\'', '&#8217;'), '\"', '&quot;'))
          , ')\">Click for More</div>'
          ) as `More Information`
          "; */
//       DATE_FORMAT(patrols.time_opened, '%H:%i') as `time_opened`,
        $input_obj = new input_item;

        $this->list_obj->sql = "select patrols.id as `idin`, ' ' as `S`,
       'Edit' as `*`, 'Delete' as `!`,
       CONCAT('<a id=\"send', patrols.id, '\" class=\"list_a\" href=\"JavaScript:send_notication(', patrols.id, ')\">', if(patrols.operator_notified, 'Res', 'S'), 'end Notification</a>') as `Notification`,
       CONCAT(patrols.job_number, '<br/>', lookup_fields.item_name) as `Job Number<br/>Service Type`,
       CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a><br/>', users.phone) as `Assigned To`, 
       CONCAT(users5.name, ' ', users5.surname) as `Site`,
       patrols.date_time as `Request Date/Time`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(D', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"D', patrols.id, '\" name=\"D', patrols.id, '\"  placeholder=\"Dispatch\"   value=\"', DATE_FORMAT(patrols.time_dispatched, '%H:%i'), '\" /></span>') as `Dispatch`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(S', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"S', patrols.id, '\" name=\"S', patrols.id, '\"  placeholder=\"Start\"   value=\"', DATE_FORMAT(patrols.time_on_site, '%H:%i'), '\" /></span>') as `Time on Site`,
       CONCAT('<span class=\"time_input\"><input onBlur=\"validate_time(F', patrols.id, '); save_times(', patrols.id, ');\" type=\"text\"  id=\"F', patrols.id, '\" name=\"F', patrols.id, '\"  placeholder=\"Finish\"   value=\"', DATE_FORMAT(patrols.time_off_site, '%H:%i'), '\" /></span>') as `Time Off Site`,
       CONCAT('<a class=\"list_a\" onClick=\"more_info(', patrols.id, ')\">Click for More</a>') as `More Info`,
       CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Patrols/PatrolManager?pdf=', patrols.id, '\">PDF</a>') as `PDF`,
       
       CONCAT(if(patrols.gps_on = '', '', CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"https://www.google.com.au/maps/place/', patrols.gps_on, '\">On</a>')),
       if(patrols.gps_off = '', '', CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"https://www.google.com.au/maps/place/', patrols.gps_off, '\">Off</a>'))) as `GPS`
       
       from patrols
       left join users on users.id = patrols.assigned_to_id
       left join users3 on users3.id = patrols.control_room_operator_id
       left join users4 on users4.id = patrols.alarm_company_id
       left join users5 on users5.id = patrols.site_id
       left join users6 on users6.id = patrols.patrol_car_id
     	 left join lookup_fields on lookup_fields.id = patrols.service_type_id
       order by patrols.date_time DESC
       ";

        //$this->list_obj->sql = "select '\\\\\'' as `test`";
//return "<textarea>{$this->list_obj->sql}</textarea>";
        $this->editor_obj->table = "patrols";
        $style_small = 'style="width: 95px;"';
        $style = 'style="width: 125px;"';
        $style_large = 'style="width: 165px;"';
        $notes_style = 'class="textbox third_textbox"';

        $this->editor_obj->custom_field = "control_room_operator_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];

        $this->editor_obj->form_attributes = array(
            array("selServiceType", "cmbAssignedTo", "cmbAlarmCompany", "cmbSite", "selPatrolCar", "txtJobNumber", "txaOperatorDescription", "txtZoneNo", "txtKeyId", "txtAlarmCode", "ti2TimeOnSite", "ti2TimeOffSite", "txaRequestDescription", "txaControlRoomNotes", "tadDateTime"),
            array("Service Type", "Assigned To", "Alarm Company", "Site", "Patrol Car", "Job Number", "Operator Description of Results", "Zone No", "Key No", "Alarm Code", "Time On Site", "Time Off Site", "Description of the Request", "Control Room Notes/Response Resolution", "Date/Time"),
            array("service_type_id", "assigned_to_id", "alarm_company_id", "site_id", "patrol_car_id", "job_number", "operator_description", "zone_no", "key_id", "alarm_code", "time_on_site", "time_off_site", "request_description", "control_room_notes", "date_time"),
            array($this->get_lookup('patrol_service_type'), $staff_sql, $alarm_company_sql, $site_sql, $car_sql, "", "", "", "", "", "", "", "", "", ""),
            array($style, $style_large, $style, $style_large, $style_large, $style, $notes_style, $style_small, $style, $style, $style_small, $style_small, $notes_style, $notes_style, $style),
            array("c", "c", "n", "c", "n", "n", "n", "n", "n", "n", "n", "n", "c", "n", "n")
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
      &nbsp; &nbsp; <span id="textadd">' . $input_obj->cmb("cmbSelect", "", 'placeholder="Add Text to Description..." style="height: 15px !important;" onChange="add_text(this);"', "", $this->dbi, $this->get_blank_lookup('patrol_keywords'), "") . '</span>
      <span id="message"></span>
      
      </div>
      <div class="form_content" id="form_content">

        <div class="fl"><nobr>tselServiceType</nobr><br/>selServiceType</div>
        <div class="fl"><nobr>ttxtJobNumber</nobr><br/>txtJobNumber</div>
        <div class="fl"><nobr>tcmbAssignedTo</nobr><br/>cmbAssignedTo</div>
        <div class="fl"><nobr>tcmbSite &nbsp; [<a href="' . $this->f3->get('main_folder') . 'UserAdder?add_security=1">Add a Site</a>]</nobr><br/>cmbSite</div>
        <div class="fl"><nobr>tselPatrolCar</nobr><br/>selPatrolCar</div>
        <div class="fl"><nobr>ttxtKeyId</nobr><br/>txtKeyId</div>
        <div class="fl"><nobr>tcmbAlarmCompany</nobr><br/>cmbAlarmCompany</div>
        <div class="fl"><nobr>ttxtAlarmCode</nobr><br/>txtAlarmCode</div>
        <div class="fl"><nobr>ttxtZoneNo</nobr><br/>txtZoneNo</div>
        <div class="fl"><nobr>ttadDateTime</nobr><br/>tadDateTime</div>
        <div class="fl"><nobr>tti2TimeOnSite</nobr><br/>ti2TimeOnSite</div>
        <div class="fl"><nobr>tti2TimeOffSite</nobr><br/>ti2TimeOffSite</div>

        <div class="cl"></div>
        
        <div class="note_thirds">ttxaRequestDescription<br/>txaRequestDescription</div>
        <div class="note_thirds">ttxaControlRoomNotes<br/>txaControlRoomNotes</div>
        <div class="note_thirds">ttxaOperatorDescription<br/>txaOperatorDescription</div>
        
        
        <div class="cl"></div>
        <div class="fl">' . $this->editor_obj->button_list() . '</div>
        <div class="cl"></div>

      </div>
    </div>';

        $this->editor_obj->editor_template = '
    <div id="xtras">editor_form</div><div class="cl"></div>editor_list';
        if (isset($_POST['idin'])) {
            $str .= $this->photo_uploader($_POST['idin']);
        }
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);

        if (!$_POST['idin']) {
            $str .= "
      <script>
      //document.getElementById('cmbEnteredBy').value = '{$_SESSION['full_name']}'; 
      //document.getElementById('hdncmbEnteredBy').value = '{$_SESSION['user_id']}';
      //document.getElementById('cmbControlRoomOperator').value = '{$_SESSION['full_name']}'; 
      //document.getElementById('hdncmbControlRoomOperator').value = '{$_SESSION['user_id']}';
      //document.getElementById('calDate').value = '" . date('d-M-Y') . "';

      </script>
      ";
        }
        $action = $this->action;

        if ($action == "add_record" || $action == "save_record") {
            if ($action == "add_record") {
                $save_id = $this->editor_obj->last_insert_id;
                if (!$_POST['tadDateTime']) {
                    $sql = "update patrols set date_time = now() where id = $save_id";
                    $this->dbi->query($sql);
                }
            } else if ($action == "save_record") {
                $save_id = $this->editor_obj->idin;
            }

            if ($save_id) {
                echo $this->redirect($_SERVER['REQUEST_URI']);
            }
        }


        $str .= '
    <style>
    .orange_bkg {
      background-color: #FFDCA9 !important;
    }
    .orange_bkg:hover {
      background-color: #FFE4C1 !important;
    }

    .green_bkg {
      background-color: #B4DCA2 !important;
    }
    .green_bkg:hover {
      background-color: #D3EBC8 !important;
    }
   
    .red_bkg {
      background-color: #E7BDC1 !important;
    }
    .red_bkg:hover {
      background-color: #FFD6DA !important;
    }

    .blue_bkg {
      background-color: #CDEEFD !important;
    }
    .blue_bkg:hover {
      background-color: #DCF3FD !important;
    }
    
    .grey_bkg {
      background-color: #DDDDDD !important;
    }
    .grey_bkg:hover {
      background-color: #EEEEEE !important;
    }
    #textadd {
      margin: 0px !important;
      display: none;
    }

    </style>
    
    
    <script>
    var test = get_cookie("form_content");
    ';

        if ($_POST['idin'] && $action != "delete_record" && $_COOKIE['form_content'] == 'closed') {
            $str .= '
        show_hide_item(\'form_content\', \'Open Form &gt;&gt;\', \'&lt;&lt; Close Form\');
      ';
        }

        $str .= '
    if(test == "open") {
      show_hide_item(\'form_content\', \'Open Form &gt;&gt;\', \'&lt;&lt; Close Form\');
    }
    
    </script>
    ';
        //$str .= $js_str;
//              document.getElementById("totals").innerHTML = msg
        //$str .= "document.getElementById('$id-1').className = '{$col}_bkg';document.getElementById('$id-1').innerHTML = '$txt';";


        $str .= '
    <script>
    function refresh_items() {
      $.ajax({
        type:"get",
            url:"' . $this->f3->get('main_folder') . 'Patrols/PatrolManager",
            data:{get_status: 1} ,
            success:function(msg) {
              if(msg) {
                var res = msg.split(",");
                var len = res.length;
                var res2;
                var dummy, focused
                for(var i = 0; i < len; i++) {
                  res2 = res[i].split("-");
                  document.getElementById(res2[0] + "-1").className = res2[1] + "_bkg";
                  document.getElementById(res2[0] + "-1").innerHTML = res2[2];

                  status = res2[2];
                  if(status == "C" || status == "O") document.getElementById(res2[0] + "-4").innerHTML = "";
                  if(status == "O" || status == "P") { document.getElementById(res2[0] + "-2").innerHTML = ""; document.getElementById(res2[0] + "-3").innerHTML = ""; }
                  
                  dummy = document.getElementById("S" + res2[0]);
                  focused = (document.activeElement === dummy);
                  if(!focused) document.getElementById("S" + res2[0]).value = res2[3];
                  dummy = document.getElementById("F" + res2[0]);
                  focused = (document.activeElement === dummy);
                  if(!focused) document.getElementById("F" + res2[0]).value = res2[4];
                }
              }
            }
      } );
    }
    refresh_items();
    setInterval(refresh_items, 10000);

    </script>
    <div id="totals"></div>
    ';

        return $str;
    }

    function MyPatrols(){
        /* include $this->f3->get('main_folder') . 'App/Controllers/CalendarController.php';
          $calendar = new PN_Calendar($this->f3);
          return $calendar->draw(); */


        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
        $latitude = (isset($_POST['hdnLatitude']) ? $_POST['hdnLatitude'] : 0);
        $longitude = (isset($_POST['hdnLongitude']) ? $_POST['hdnLongitude'] : 0);

        $psql = "select id as `result` from patrols where assigned_to_id = " . $_SESSION['user_id'] . " and (ip_on = '' or ip_off = '') and operator_notified != 0";
         $patrol_id = $this->get_sql_result($psql);
        $patrolIds = array();
        //$result = $this->dbi->query($psql);
       
//        while($myrow = $result->fetch_assoc()) {
//            $patrolIds[] = $myrow;
//        }
        
//        prd($patrol_id);
//        die;
        $patrolIds[] = array('result'=>$patrol_id);
       // prd($patrolIds);

        $hdnFlag = (isset($_POST['hdnFlag']) ? $_POST['hdnFlag'] : 0);
        if(!empty($patrolIds)) {
            foreach ($patrolIds as $patrolidNew) {
                $patrol_id = $patrolidNew['result'];
                $str .= '<div id="x"></div>';

                $on_or_off = ($this->get_sql_result("select ip_on as `result` from patrols where id = $patrol_id") ? 'off' : 'on');
                $str .= '<script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>';
                $str .= '<input type="hidden" name="hdnLatitude" id="hdnLatitude" value="' . $latitude . '" />
                    <input type="hidden" name="hdnLongitude" id="hdnLongitude"  value="' . $longitude . '" />
                    ';

                if (!isset($_POST['hdnLatitude'])) {
                    //return $str . '<script>getLocation();</script>';
                }

                $time_date = date('Y-m-d H:i:s');
                $time = date('H:i:s');
                $current_date = date("Y-m-d");
                $yesterday = date("Y-m-d", strtotime("-1 days"));
                $detect = new Mobile_Detect;
                $txtComment = $this->mesc($_POST['txtComment']);

                //$earliest = date('Y-m-d H:i:s', strtotime("-15 minutes"));
                //$latest = date('Y-m-d H:i:s', strtotime("+30 minutes"));
                //$str .= "$on_or_off // $hdnFlag";

                $font_size = "font-size: 10pt !important;";
                $text_style = "width: 100%; height: 50px; float: left;";

                //if($hdnFlag && $latitude && $longitude) {
                //".($on_or_off == "off" ? ", docket_number = (id + 123)" : "")."
                if ($hdnFlag) {
                    //$patrol_id = ($hdnFlag == 'COMMENT' ? $_POST['hdnSiteId'] : $this->get_by_site_staff($_POST['hdnSiteId'], $_SESSION['user_id']));
                    if ($hdnFlag == 'COMMENT') {
                        $sql = "update patrols set operator_description = '$txtComment' where id = $patrol_id";
                    } else {
                        $sql = "update patrols set time_" . $hdnFlag . "_site = '$time', gps_" . $hdnFlag . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $hdnFlag . " = '" . $_SERVER['REMOTE_ADDR'] . "', operator_description = '$txtComment' where id = $patrol_id";
                        //$str .= "<br/>$sql ";
                    }
                    $result = $this->dbi->query($sql);
                    echo $this->redirect($_SERVER['REQUEST_URI']);
                }


                //         if(patrols.ip_on = '', 'ON', if(patrols.ip_off = '', 'OFF', 'COMPLETED')) as `on_or_off`,
                $sql = "
             select patrols.id,
             patrols.time_on_site, patrols.time_off_site, ip_on,
             patrols.site_id, patrols.time_opened,
             lookup_fields.item_name as `type`,
             CONCAT(users5.name, ' ', users5.surname) as `site`,
             CONCAT(users5.address, ' ', users5.suburb, ' ', states.item_name, ' ', users5.postcode) as `full_address`,
             CONCAT(REPLACE(REPLACE(CONCAT(
             if(patrols.request_description = '', '', CONCAT('<h5>Request/Instructions:</h5>', patrols.request_description, '<br/><br/>')),
             if(patrols.operator_notified = 0, '', CONCAT('<br/><nobr><b>Dispatch Time:</b> ', DATE_FORMAT(patrols.time_dispatched, '%H:%i'), '</nobr>')),
             if(patrols.operator_notified = 0, '', CONCAT('<br/><nobr><b>Dispatch Date:</b> ', DATE_FORMAT(patrols.time_dispatched, '%d-%b-%Y'), '</nobr>')),
             if(users4.id IS NULL, '', CONCAT('<br/><b>Alarm Company:</b> &nbsp;', users4.name, ' ', users4.surname, ' (', users4.phone, ')')),
             if(patrols.job_number = '', '', CONCAT('<br/><b>Job No:</b> &nbsp;', patrols.job_number)),
             if(patrols.zone_no = '', '', CONCAT('<br/><b>Zone No:</b> &nbsp;', patrols.zone_no)),
             if(patrols.key_id = '', '', CONCAT('<br/><b>Key No:</b> &nbsp;', patrols.key_id)),
             if(patrols.alarm_code = '', '', CONCAT('<br/><b>Alarm Code:</b> &nbsp;', patrols.alarm_code)),
             if(users6.id IS NULL, '', CONCAT('<br/><b>Patrol Car:</b> &nbsp;', users6.employee_id)),
             if(users.id IS NULL, '', CONCAT('<br/><b>Control Room:</b> &nbsp;', users.name, ' ', users.surname, ' (', users.phone, ')')),
             if(patrols.operator_description = '', '', CONCAT('<br/><br/><b>Operator Description:</b><br/>', patrols.operator_description)),
             if(patrols.control_room_notes = '', '', CONCAT('<br/><br/><b>Control Room Notes:</b><br/>', patrols.control_room_notes)))
            , '\'', '&#8217;'), '\"', '&quot;'))
             as `details`
             from patrols
             left join users on users.id = patrols.control_room_operator_id
             left join users4 on users4.id = patrols.alarm_company_id
             left join users5 on users5.id = patrols.site_id
             left join users6 on users6.id = patrols.patrol_car_id
             left join lookup_fields on lookup_fields.id = patrols.service_type_id
             left join states on states.id = users5.state
             where patrols.id = $patrol_id
             order by id LIMIT 1
             ";
                //      echo $sql;
                //      die;
                //         where patrols.assigned_to_id = ".$_SESSION['user_id']." and (ip_on = '' or ip_off = '') and operator_notified != 0
                //return "<textarea>$sql</textarea>";

                if ($result = $this->dbi->query($sql)) {
                    //        $on_or_off = ($myrow['ip_on'] ? 'off' : 'on');
                    $latest_check = 0;
                    if ($myrow = $result->fetch_assoc()) {

                        $time_on_site = $myrow['time_on_site'];
                        $time_off_site = $myrow['time_off_site'];
                        // $str .= "<h5>$time_on_site, $time_on_site</h5>";

                        $type = $myrow['type'];
                        //$on_or_off = $myrow['on_or_off'];
                        $address = $myrow['full_address'];
                        $url_address = str_replace(" ", "+", urlencode($address));

                        $map_link = '<div class="map-responsive"><iframe width="480" height="250" src="https://maps.google.com/maps?width=480&amp;height=250&amp;hl=en&amp;q=' . $url_address . '&amp;ie=UTF8&amp;t=&amp;z=10&amp;iwloc=B&amp;output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe></div>';

                        //$patrol_id = $myrow['id'];
                        $details = $myrow["details"];
                        $site = $myrow['site'];
                        $site_id_in = $myrow['site_id'];

                        $time_opened = $myrow['time_opened'];
                        if ($time_opened == '0000-00-00 00:00:00')
                            $this->dbi->query("update patrols set time_opened = now() where id = $patrol_id;");
                    }
                }

                if (!$site_id_in) {
                    $err = 1;
                } else {
                    //$on_or_off = ($on_or_off ? $on_or_off : 'off');
                    //return $on_or_off;

                    if ($msg)
                        $str .= $this->message($msg, 2000);

                    $str .= '<input type="hidden" name="hdnFlag" id="hdnFlag" value="' . $hdnFlag . '" />
                      <input type="hidden" name="hdnSiteId" id="hdnSiteId" />
                      <input type="hidden" name="hdnSaveComments" id="hdnSaveComments" />';

                    $str .= "<h5 class=\"fl\">$type @ $site</h5>";

                    if ($latitude) {
                        $str .= '<span class="fl help_message" style="' . $font_size . '">GPS: ' . round($latitude, 5) . ", " . round($longitude, 5) . '</span>';
                    }

                    $str .= '<div class="cl"></div>';
                    if ($on_or_off == 'off') {
                        $text_style = "height: 150px; width: 100%;";
                        $str .= '<h5>Enter the Results Below</h5>';

                        $str .= '
              <script>
                function add_text(txt) {
                  document.getElementById("txtComment").value += txt + "\r\n"
                  document.getElementById("txtComment").focus()
                }
              </script>
              <style>

              /* Style for the overall container */
                div[align="left"] {
                    max-width: 800px;
                    margin: 20px auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    background-color: #f9f9f9;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                /* Heading styles */
                h5 {
                    font-size: 18px;
                    color: #333;
                    margin-bottom: 10px;
                }

                /* Map responsive styling */
                .map-responsive {
                    overflow: hidden;
                    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
                    position: relative;
                    height: 0;
                }

                .map-responsive iframe {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }

                /* Request instructions styling */
                h5 + p {
                    margin: 10px 0;
                }

                /* General text styles */
                nobr {
                    display: block;
                    margin: 5px 0;
                    font-weight: bold;
                }

                /* Footer styles */
                #footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #777;
                }

                /* Modal dialog styles (optional) */
                #myModal {
                    display: none; 
                    position: fixed; 
                    z-index: 1; 
                    left: 0;
                    top: 0;
                    width: 100%; 
                    height: 100%; 
                    overflow: auto; 
                    background-color: rgb(0,0,0); 
                    background-color: rgba(0,0,0,0.4); 
                }

                .modal-content {
                    background-color: #fefefe;
                    margin: 15% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%; 
                }

                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                }

                .close:hover,
                .close:focus {
                    color: black;
                    text-decoration: none;
                    cursor: pointer;
                }


              </style>


              ';
                        $sql = "select item_name, value from lookup_fields where lookup_id = (select id from lookups where item_name = 'patrolman_keywords') order by sort_order, item_name";
                        if ($result = $this->dbi->query($sql)) {
                            $str .= '<div style="display: inline-block !important; margin-left: 0px;" class="custom_menu_bar">';
                            while ($myrow = $result->fetch_assoc()) {
                                //$idin = $myrow[$this->table_id_name]; 
                                $item_name = $myrow['item_name'];
                                $value = $myrow['value'];
                                $test = json_encode($item_name . $value);
                                $str .= "<a href=\"JavaScript:add_text('" . substr($test, 1, strlen($test) - 2) . "')\">$item_name</a>";
                            }
                            $str .= '</div>';
                        }

                        $str .= '<textarea placeholder="Before Signing Off, please enter the results of the ' . $type . ' Here..." style="' . $text_style . '" name="txtComment" id="txtComment">' . $comment . '</textarea>';
                    }


                    $str .= '<input onClick="' . ($on_or_off == 'off' ? "compulsory_comment = 1;" : "") . ' clock_it(\'' . $on_or_off . '\', ' . $patrol_id . ');" type="button" class="clock_' . (strtolower($on_or_off)) . '" value="Sign ' . $on_or_off . ' @ ' . $site . '" />';
                    $str .= "<h6>Sign $on_or_off above when " . ($on_or_off == 'on' ? "you arrive" : "you're leaving. Make sure that the results are entered above before signing off") . ".</h6>";

                    if ($on_or_off == 'off') {
                        $str .= $this->photo_uploader($patrol_id);
                    }


                    $str .= '
            <span id="myDiv"></span>
            <div align="left">' . $clock_str;
                }
                
             $str .= "
      <hr /><h5>$address</h5>$map_link<p>$details</p>";    
                
            }
            //$err = 1;
            //$pending = 0;
            
        } else {
            $err = 1;
            $pending = 1;
        }
        
        if ($err) {
             $str .= "</br></br>";
            if($pending){
             $str .= "<h5>There are currently no pending patrols.</h5>";
            }  
            $this->list_obj->title = "My Past Patrols";
            $this->list_obj->sql = "select patrols.id as `idin`,
         lookup_fields.item_name as `Service Type`,
         CONCAT(users5.name, ' ', users5.surname) as `Site`,
         patrols.date_time as `Request Date/Time`,
         CONCAT(DATE_FORMAT(patrols.time_on_site, '%H:%i'), ' - ', DATE_FORMAT(patrols.time_off_site, '%H:%i')) as `Time On/Off`
         from patrols
         left join users4 on users4.id = patrols.alarm_company_id
         left join users5 on users5.id = patrols.site_id
         left join users6 on users6.id = patrols.patrol_car_id
         left join lookup_fields on lookup_fields.id = patrols.service_type_id
         where patrols.assigned_to_id = {$_SESSION['user_id']}
         order by patrols.date_time DESC
         ";

            $str .= $this->list_obj->draw_list();

            //$str .= $this->ta($this->list_obj->sql);
        } else {
//            $str .= "
//      <hr /><h5>$address</h5>$map_link<p>$details</p>";
        }


        return $str;
    }

    function photo_uploader($patrol_id) {
        $str = "
    <style>
    @media screen and (max-width: 960px) {
      .map-responsive{
          overflow:hidden;
          padding-bottom:56.25%;
          position:relative;
          height:0;
      }
      .map-responsive iframe{
          left:0;
          top:0;
          height:100%;
          width:100%;
          position:absolute;
      }
    }
    .img_button {
      background-size: cover; 
      float: left;
      width: 32px; height: 32px; padding-bottom: 0px;
      background-image: url('" . $this->f3->get('img_folder') . "image_show.gif');
    }
    .img_label {
      display: inline-block;
      margin-left: 8px;
      margin-top: 5px;
    }
    .img_uploader {
      display: none;
      padding: 0px;
      border: none;
    }
    </style>
    <script>
    function show_hide_img(id,target_dir) {
      if(document.getElementById('img_uploader').style.display == 'block') {
        document.getElementById('img_uploader').style.display = 'none';
        document.getElementById('img_button').style.backgroundImage = 'url(" . $this->f3->get('img_folder') . "image_show.gif)'
        document.getElementById('img_label').innerHTML = ' Click Here to Add Photos...';
      } else {
        document.getElementById('img_uploader').style.display = 'block';
        document.getElementById('img_button').style.backgroundImage = 'url(" . $this->f3->get('img_folder') . "image_hide.gif)'
        //alert('" . $this->f3->get('main_folder') . "Fileman?show_min=1&target_dir='+target_dir+'&target_subdir='+id)
        if(!document.getElementById('photo_upload').src) document.getElementById('photo_upload').src = '" . $this->f3->get('main_folder') . "Fileman?show_min=1&target_dir='+target_dir+'&target_subdir='+id;
        document.getElementById('img_label').innerHTML = ' Click Here to Close Photo Uploader...';
      }
    }
    </script>
    ";
        $target_dir = $this->f3->get('download_folder') . $this->img_folder;
        $str .= '<div class="cl"></div><span style="cursor: pointer !important; background-color: #FFFFEE; display: block;" onClick="show_hide_img(\'' . $patrol_id . "','" . $target_dir . '\');"><div class="img_button" id="img_button"></div><div id="img_label" class="img_label"> Click Here to Add Photos...</div><div class="cl"></div></span><div class="img_uploader" id="img_uploader">
    <iframe id="photo_upload" frameborder="0" width="100%" height="500px;"></iframe>
    </div><div class="cl"></div>';
        return $str;
    }

}

/*

  if($latitude) {
  $sql = "SELECT t1.user_id as `site_id`, users.name as `site`,
  (SELECT meta_value FROM usermeta WHERE meta_key=93 AND user_id = t1.user_id) AS `latitude`,
  (SELECT meta_value FROM usermeta WHERE meta_key=94 AND  user_id = t1.user_id) AS `longitude`
  FROM usermeta AS t1
  left join users on users.id = t1.user_id
  where t1.meta_key = 93 or t1.meta_key = 94
  GROUP BY t1.user_id";

  $result_gps = $this->dbi->query($sql);
  $cnt = 0;
  $found = 0;
  $dist_check = 1000;
  while($myrow = $result_gps->fetch_assoc()) {
  $long = $myrow['longitude'];
  $lat = $myrow['latitude'];
  if(is_numeric($lat) && is_numeric($long) && is_numeric($latitude) && is_numeric($longitude)) $distance = $this->distance($lat, $long, $latitude, $longitude);
  //$str .= "<h5>$lat, $long, $latitude, $longitude -- $distance</h5>";
  if($distance < $dist_check) {
  $found = 1;
  $site_id = $myrow['site_id'];
  $site_ids[] = $myrow['site_id'];
  $site_name = $myrow['site'];
  $sign_text = "Sign $on_or_off @ ";
  $clock_str .= '<input onClick="clock_it(\''.$on_or_off.'\', '.$site_id.');" type="button" class="clock_'.(strtolower($on_or_off)).'" value="'.$sign_text.$site_name.'" />';
  }
  }
  }
 */
?>