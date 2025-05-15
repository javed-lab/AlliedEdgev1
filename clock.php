<?php


class clock {

//  function __construct($field, $query_string) {

  function calc_hours($str, $just_minutes = 0) {
    $time_sets = split(",", $str);
    $diff = "";
    foreach ($time_sets as $time_set) {
      $diff_old = $diff;
      $time_set = trim($time_set);
      $times = split("-", $time_set);
      $start_time = $times[0];
      $finish_time = $times[1];
      if($finish_time) {
        $diff += round(abs(strtotime($finish_time) - strtotime($start_time)) / 60,2);
      }
    }
    
    if($diff) {
      if($just_minutes) {
        $r_str = $diff;
      } else {
        $p1 = floor($diff / 60);
        $p2 = ($diff % 60);	
        if($p1 != 1) $pl1 = "s";
        if($p2 != 1) $pl2 = "s";
        $diff = "$p1 hour$pl1, $p2 minute$pl2";
        if($p1 < 10) $p1 = "0$p1";
        if($p2 < 10) $p2 = "0$p2";
        $diff1 = "($p1:$p2) ";
        $r_str = $diff1 . $diff;
      }
      return $r_str;
    }
  }

}


?>