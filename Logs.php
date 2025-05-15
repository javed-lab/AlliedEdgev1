<?php

class Logs extends Controller {
  function Show() {
    $this->list_obj = new data_list;
    $nav = new navbar;
    $str .= $nav->draw_navbar(2016);
    $filter_string = $nav->get_filter("attempt_date_time");
    $filter_string2 = $nav->get_filter("date");
    $str .= '<br /><br /><a href="#page_access">Page Access Stats</a> | <a href="#downloads">Download Stats</a>';
    $this->list_obj->title = "Login Log";
    $this->list_obj->sql = "
          select 'Unique Users' as `Statistic`, count(distinct user_id) as `Num Items`
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Successful Logins' as ` `, count(id)
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Failed Attepts', count(id)
          FROM log_login
          where is_successful = 0 $filter_string
          UNION
          select 'Total Logins', count(id)
          FROM log_login
          where 1 $filter_string
      ";
    $str .= $this->list_obj->draw_list();
    $this->list_obj->sql = "
          select log_login.attempt_date_time as `Date`, if(log_login.is_successful, 'Yes', 'No') as `Is Successful`, CONCAT(users.name, ' ', users.surname) as `User`, log_login.username as `Username`, log_login.ip_address as `IP Address`
          FROM log_login
          left join users on users.id = log_login.user_id
          where 1 $filter_string
          order by log_login.attempt_date_time desc
      ";
    $this->list_obj->title = "";
    $str .= $this->list_obj->draw_list();
    $this->list_obj->title = "Page Access Log";
    $this->list_obj->sql = "
          select page as `Page`, count(id) as `Count`
          FROM log_page_access
          where is_successful = 1 $filter_string
          group by page
          order by count(id) DESC
      ";
    $str .= '<div id="page_access"></div><br /><br /><a href="#top">Top</a> | <a href="#downloads">Download Stats</a><br /><br />' . $this->list_obj->draw_list();
    $this->list_obj->sql = "
          select downloads.date as `Date`, CONCAT(users.name, ' ', users.surname) as `User`, downloads.file_name as `File`
          FROM downloads
          left join users on users.id = downloads.downloaded_by
          where 1 $filter_string2
          order by downloads.date desc
      ";
    $sql = "select file_name FROM downloads where 1 $filter_string2";
    if($result = $this->dbi->query($sql)) {
      while($myrow = $result->fetch_assoc()) {
        $file = "/home/Edge/downloads/" . $myrow['file_name'];
        if($myrow['file_name'] != "/") {
          $size = filesize($file);
          $total_size += $size;
        }
      }
      $total_size /= 1024;
      $total_size = number_format(round($total_size)) . " Kb";
    }
    $this->list_obj->title = "Downloads Log<br /><br />Total Size: $total_size";
    $str .= '<div id="downloads"></div><br /><br /><a href="#top">Top</a> | <a href="#page_access">Page Access Stats</a><br /><br />' . $this->list_obj->draw_list() . '<a href="#top">Top</a> | <a href="#page_access">Page Access Stats</a> | <a href="#downloads">Download Stats</a><h3>Total Size: ' . "$total_size</h3>";
    return $str;
    exit; // Terminate script execution
  }
}
        
?>
