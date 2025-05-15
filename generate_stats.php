<?php
  
class stats_generator {
  function generate_stats($dbi) {
    $sql = "select stats.id, stats.update_frequency_id, lookup_fields.item_name as `repeat`, stats.repeat_count, stats.date_field, stats.title, stats.query from stats
                               left join lookup_fields on lookup_fields.id = stats.repeat_id
    ";
    //return $sql;
    if($result = $dbi->query($sql)) {
      $dbi->query("truncate stats_results");
      while($myrow = $result->fetch_assoc()) {
        $sql = "";
        $id = $myrow['id'];
        $repeat = $myrow['repeat'];
        $repeat_count = ($myrow['repeat_count'] ? $myrow['repeat_count'] : 1);
        $date_field = $myrow['date_field'];
        $title = $myrow['title'];
        $query = $myrow['query'];
        $query = str_ireplace("select ", "select '$id', ", $query);

        if(strtolower($repeat) == 'monthly') {
          for($x = $repeat_count; $x >= 0; $x--) {
            $sql .= "$query 
            " . ($x
            ? " and month($date_field) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL $x MONTH)) and year($date_field) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL $x MONTH)) UNION " 
            : " and month($date_field) = MONTH(CURRENT_DATE()) and year($date_field) = YEAR(CURRENT_DATE());"
            );
          }
        }
        $sql = "insert into stats_results (stat_id, label, value) $sql";
        //return $sql;
        $dbi->query($sql);
        
      }    
    }
  }
}

?>