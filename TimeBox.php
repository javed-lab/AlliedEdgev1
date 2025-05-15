<?php
class TimeBox {
  function load_css() {
    $str = '
    <style>
    table.time_labels {
      border-width: 1px;
      border-spacing: 0px;
      border-style: outset;
      border-color: #DDDDDD;
      border-collapse: collapse;
      width: 100%;
    }
    .time_label {
      border-top-color:  transparent !important;
      border-left-color:  transparent !important;
      border-right-color:  transparent !important;
      font-size: 6pt !important;
      color: #AAAAAA;
      width: 12%;
    }
    .time_label_text {
      margin-bottom: -3px;      
    }

    .time_on { background-color: #FFFF55; display: inline-block; }
    .t1_4 { width: 25%; }
    .t1_2 { width: 50%; }
    .t3_4 { width: 75%; }
    .tfull { width: 100%; }
    .lead { float: right; }

    table.time_box {
      border-width: 1px;
      border-spacing: 0px;
      border-style: outset;
      border-color: #DDDDDD;
      border-collapse: collapse;
      width: 100%;
    }
    table.time_box td {
      border-width: 1px;
      padding: 0px;
      border-style: solid;
      border-color: #DDDDDD;
      font-size: 5pt;
      width: 4%;
      text-align: left !important;
    }

    table.time_box tr:hover {
      background-color: #F0FFF5;
      
    }

    </style>
    ';
    return $str;
  }
  
  function show($start_time, $finish_time) {
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
    $s1 = intval(Date("H", strtotime($start_time)));
    $s2 = intval(Date("i", strtotime($start_time)));
    $f1 = intval(Date("H", strtotime($finish_time)));
    $f2 = intval(Date("i", strtotime($finish_time)));

    //$str .= "$s1, $s2, $f1, $f2";

    if($s2 > 50) {
      $s2 = 0;
      $s1++;
      if($s1 == 24) $s1 = 0;
    } else if($s2 < 10) {
      $s2 = 0;
    }
    $lead = "time_on ";
    if($s2 == 0) {
      $lead .= "tfull";
    } else if($s2 >= 10 && $s2 <= 20) {
      $lead .= "t3_4 lead";
    } else if($s2 >= 40 && $s2 <= 50) {
      $lead .= "t1_4 lead";
    } else if($s2 > 20 && $s2 < 40) {
      $lead .= "t1_2 lead";
    }

    if($f2 > 50) {
      $f2 = 0;
      $f1++;
      if($f1 == 24) $f1 = 0;
    } else if($f2 < 10) {
      $f2 = 0;
    }
    $end = "time_on ";
    if($f2 == 0) {
      $end .= "tfull";
    } else if($f2 >= 10 && $f2 <= 20) {
      $end .= "t1_4";
    } else if($f2 >= 40 && $f2 <= 50) {
      $end .= "t3_4";
    } else if($f2 > 20 && $f2 < 40) {
      $end .= "t1_2";
    }


    $str .= '<table class="time_box">';
    
    $str .= '<tr>';
    for($x = 0; $x < 24; $x += 2) {
      $str .= '<td class="time_label" colspan="2"><div class="time_label_text">'."$x</div></td>";
    }
    $str .= '</tr>';
    
    $str .= '<tr>';

    $cls = "";
    for($x = 0; $x < 24; $x++) {
      if($x == $s1) {
        $cls = $lead;
      } else if(($x + ($f2 >= 10 ? 0 : 1)) == $f1) {
        $cls = $end;
      } else if (($f1 > $s1 && $x > $s1 && $x < $f1) || ($f1 < $s1 && (($x > $f1 && $x > $s1) || ($x < $s1 && $x < $f1)))) {
        $cls = "time_on tfull";
      } else {
        $cls = "";
      }
      $str .= "<td><div class=\"$cls\">&nbsp;</div></td>";
    }

    $str .= '
    </tr>
    </table>
    ';
        
    return $str;
  }
  
  
}

?>