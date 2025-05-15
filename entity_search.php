<?php

class entity_search extends data_list {
  var $dbi;
  var $table = "users";
  var $title = "Search";
  var $display_results = 1;
  var $search_name = "SearchEntity";
  var $max_length = 50;
  var $fields;
  var $qry_name, $qry_title = "Select";

  function draw_search() {
    $search = (isset($_POST['txt' . $this->search_name]) ? $_POST['txt' . $this->search_name] : null);

    $str = '<br /><h3>'.$this->title.'</h3>
          </form>
          <form method="POST">
          <p><input maxlength="50" name="txt'.$this->search_name.'" id="search" type="text" class="search_box" value="" /><input type="submit" name="cmd'.$this->search_name.'" value="Search" class="search_button" /></p>
          <div class="cl"></div><br />';
    if($search) {
      $search = "
        where (users.name LIKE '%$search%'
        or users.surname LIKE '%$search%'
        or users.email LIKE '%$search%'
        or users.employee_id LIKE '%$search%'
        or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
        and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') and users.id != ".$_SESSION['user_id']."
      ";
      $this->sql = "
              select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
              phone as `Phone`, users.email as `Email`,
              CONCAT('<a href=\"?$qry_name=', users.id , '\">$qry_title</a>') as `**` from users
              left join states on states.id = users.state
              inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
              $search";
              //echo "<textarea>" . $this->sql . "</textarea>";
      $str .= $this->draw_list();
      $this->dbi->close();
    }
    return $str;
  }


}

?>