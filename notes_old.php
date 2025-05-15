<?php
class notes extends Controller {
  var $id;
  var $notes_heading;
  var $hours_limit = 24;
  var $show_delete;
  var $table;
  var $table_id_name;
  var $add_title;
  var $num_per_page;  //Set to 0 for no pagination
  var $use_comments;
  var $excel_file;
  var $date_time;
  var $show_cam;

  function __construct() {
    $this->db_init();
    //$this->dbi = $this->dbi;
    $sql = "select states.item_name as `state` from users left join states on states.id = users.state where users.id = " . $_SESSION['user_id'];
    $result = $this->dbi->query($sql);
    if($myrow = $result->fetch_assoc()) {
      date_default_timezone_set(($myrow['state'] == "TAMIL NADU" ? 'Asia/Kolkata' : 'Australia/' . $myrow['state']));
      $this->date_time = date('Y-m-d H:i:s');
    }
  }
  
  function display_add() {
    $table = $this->table;
    $this->dbi = $this->dbi;
    $date_time = $this->date_time;
    $str .=  $this->notes_heading;

    $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);
    if($txtNotes) {
      $sql = "insert into $table (" . $this->table_id_name . ", date, description, user_id) values (" . $this->id . ", '$date_time', '$txtNotes', " . $_SESSION['user_id'] . ");";
      $result = $this->dbi->query($sql);
    }
    $hide_comments = (isset($_GET['hide_comments']) ? $_GET['hide_comments'] : null);
    $str .= ($hide_comments ? "" : '<style>table.forum_item tr:nth-child(3n+1) { background-color: #DDDDDD; }</style>');


    $hdnAddID = (isset($_POST['hdnAddID']) ? $_POST['hdnAddID'] : null);
    $hdnDeleteCommentID = (isset($_POST['hdnDeleteCommentID']) ? $_POST['hdnDeleteCommentID'] : null);
    $hdnDeleteEntryID = (isset($_POST['hdnDeleteEntryID']) ? $_POST['hdnDeleteEntryID'] : null);
    
    if($hdnAddID) {
      $comment = $this->dbi->real_escape_string($_POST['txtComment']);
//      $sql = "insert into site_notes_comments(site_notes_id, user_id, date_added, description) values ($hdnAddID, ".$_SESSION['user_id'].", '$date_time', '$comment')";
      $sql = "insert into $table"."_comments ($table"."_id, user_id, date_added, description) values ($hdnAddID, ".$_SESSION['user_id'].", '$date_time', '$comment')";
      $result = $this->dbi->query($sql);
      //$str .=  $sql;
      $msg = "Comment Added...";
    } else if($hdnDeleteCommentID) {
      $sql = "delete from $table"."_comments where id = $hdnDeleteCommentID";
      $result = $this->dbi->query($sql);
      $msg = "Comment Deleted...";
    } else if($hdnDeleteEntryID) {
      $sql = "delete from $table where id = $hdnDeleteEntryID";
      $result = $this->dbi->query($sql);
      if($this->use_comments) {
        $sql = "delete from $table"."_comments where $table"."_id = $hdnDeleteEntryID";
        $result = $this->dbi->query($sql);
      }
      $msg = "Note Deleted...";
    }
    if($msg) $str .=  $this->message($msg, 2000);
    
    $str .=  '
          <input type="hidden" name="txtComment" id="txtComment">
          <input type="hidden" name="hdnAddID" id="hdnAddID">
          <input type="hidden" name="hdnDeleteCommentID" id="hdnDeleteCommentID">
          <input type="hidden" name="hdnDeleteEntryID" id="hdnDeleteEntryID">
          <input type="hidden" name="idin" id="idin">
          <script language="JavaScript">
            function add_note() {
              if(document.getElementById("txtNotes").value) document.frmEdit.submit();
            }
            function add_comment(id) {
              if(document.getElementById("txtComment"+id).value) {
                document.getElementById("hdnAddID").value = id
                document.getElementById("txtComment").value = document.getElementById("txtComment"+id).value
                document.frmEdit.submit()
              }
            }
            function delete_comment(id) {
              if(confirm("Remove this Comment?")) {
                document.getElementById("hdnDeleteCommentID").value = id
                document.frmEdit.submit()
              }
            }
            function delete_log_entry(id) {
              if(confirm("Remove this Note and All Associated Comments?")) {
                document.getElementById("hdnDeleteEntryID").value = id
                document.frmEdit.submit()
              }
            }
            function edit_item(id) {
              document.getElementById("idin").value = id;
              document.frmEdit.action="?hdnReportFilter=1&selDateMonth='.$_GET['selDateMonth'].'&selDateYear='.$_GET['selDateYear'].'"
              document.frmEdit.submit();
            }
          </script>
    ';
    $str .=  $this->add_title . '<textarea class="textbox note_textbox" placeholder="Enter Notes Here..." name="txtNotes" id="txtNotes" class=""></textarea>
                             <div class="fr"><input type="button" onClick="add_note()" value="Post" /></div>';
  
    return $str;
  }
  
  function download_xl() {
    $xl_obj = new data_list;
    $xl_obj->dbi = $this->dbi;
    $table = $this->table;
    $xl_obj->sql = "select 
                   DATE_FORMAT($table.date, '%d-%b-%Y %H:%i') as `Date`,
                   CONCAT(users.name, ' ', users.surname) as `Added By`, $table.description as `Description`
                   from site_notes
                   left join users on users.id = $table.user_id
                   where 
                   $table." . $this->table_id_name . " = " .  $this->id . "
                   order by date DESC;
    ";
    $xl_obj->sql_xl($this->excel_file);  
  }
  
  function display_notes() {
    $table = $this->table;
    $this->dbi = $this->dbi;
    $date_time = $this->date_time;

    $page = (isset($_GET['page']) ? $_GET['page'] : 1);
    $num_per_page = $this->num_per_page;

    $sql = "select SQL_CALC_FOUND_ROWS $table.id as `" . $this->table_id_name . "`, CONCAT(DATE_FORMAT($table.date, '%a %d-%b-%Y at %H:%i')) as `date`, $table.date as `test_date`,
                   CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                   REPLACE($table.description, '\n', '<br />') as `description`
                   from $table
                   left join users on users.id = $table.user_id
                   where
                   $table." . $this->table_id_name . " = " .  $this->id . "
                   order by $table".".date DESC"
                   . ($num_per_page ? " LIMIT " . (($page - 1) * $num_per_page) . ", " . $num_per_page : "");
                   //$str .=  $sql;
    $result = $this->dbi->query($sql);

    $str .= '
      <style>
      .img_button {
        background-size: cover; 
        float: right;
        width: 50px; height: 50px; padding-bottom: 0px;
        background-image: url(\''.$this->f3->get('img_folder').'image_show.gif\');
      }
      .img_uploader {
        font-size: 16pt; padding: 12px;
        display: none;
        background-color: #FFFFEE;
        border: 1px solid #660000;
      }
      </style>
      <script>
      function show_hide_img(id,target_dir) {
        if(document.getElementById(\'img_uploader\'+id).style.display == \'block\') {
          document.getElementById(\'img_uploader\'+id).style.display = \'none\';
          document.getElementById(\'img_button\'+id).style.backgroundImage = "url(\''.$this->f3->get('img_folder').'image_show.gif\')";
        } else {
          document.getElementById(\'img_uploader\'+id).style.display = \'block\';
          document.getElementById(\'img_button\'+id).style.backgroundImage = "url(\''.$this->f3->get('img_folder').'image_hide.gif\')";
          if(!document.getElementById(\'photo_upload\'+id).src) document.getElementById(\'photo_upload\'+id).src = "'.$this->f3->get('main_folder').'Fileman?show_min=1&target_dir="+target_dir+"&target_subdir="+id;
        }
      }
    </script>
    ';
    if($this->num_per_page) {
      $hide_comments = (isset($_GET['hide_comments']) ? $_GET['hide_comments'] : null);
      $sql2 = "SELECT FOUND_ROWS()";
      $result = $this->dbi->query($sql);
      $num_rows = $result->num_rows;
      $result2 = $this->dbi->query($sql2);
      $of_rows = $result2->fetch_row();
      $of_rows = $of_rows[0];
      $end_row = ($page * $num_per_page > $of_rows ? $of_rows : $page * $num_per_page);
      $start_row = ($page * $num_per_page) - $num_per_page + 1;
      $qry_str = ($_GET['show_min'] ? "&show_min=1" : "");
      /*if($this->use_comments) {
        $str .=  (!$hide_comments ? '<a class="comment_button" href="?view_mode=1&hide_comments=1'.$qry_str.'">&gt;&gt; Hide Comments &lt;&lt;</a>' : '<a class="comment_button" href="?view_mode=1'.$qry_str.'">&lt;&lt; Show Comments &gt;&gt;</a>') . '&nbsp; &nbsp;<a class="comment_button" title="Download Excel" href="?view_mode=1'.$qry_str.'&download_xl=1">Download Excel</a>';
      }*/
      $str .=  "<div class=\"cl\"></div><div>" . ($num_rows ? "Showing " . ($of_rows > $num_per_page ? "$start_row-$end_row of $of_rows" : $num_rows) . " Entr" . ($num_rows > 1 ? "ies" : "y") : "No Records Found") . "...</div>";
      //$str .=  ($result->num_rows ? '<table class="forum_item">' : 'If you can\'t see your latest entries, try viewing last months...');
      $item_count = 0;
      if($of_rows > $num_per_page) {
        if($of_rows > $num_per_page) {
          $num_pages = ceil($of_rows / $num_per_page);
          $pagnav = '<ul class="pagination">';
          $pagnav .= '<li><a href="?page=1'.$qry_str.'" aria-label="First"><span aria-hidden="true">|&laquo;</span></a></li>';
          for($x = 1; $x <= $num_pages; $x++) {
            $pagnav .= '<li><a href="?page='.$x.$qry_str.'" aria-label="First"><span aria-hidden="true">'.$x.'</span></a></li>';
          }
          $pagnav .= '<li><a href="?page='.$num_pages.$qry_str.'" aria-label="Last"><span aria-hidden="true">&raquo;|</span></a></li>';
          $pagnav .= "</ul>";
        }
      }
      $str .=  $pagnav;
    } else {
      $num_rows = $result->num_rows;
      if($num_rows) $str .=  "<p>Showing $num_rows Record".($num_rows == 1 ? "" : "s")."</p>";
    }
    if($num_rows) {
      while($myrow = $result->fetch_assoc()) {
        $idin = $myrow[$this->table_id_name]; 
        $added_by_id = $myrow['added_by_id']; 
        $date = $myrow['date']; 
        $test_date = $myrow['test_date']; 
        $added_by = $myrow['added_by']; 
        $description = $myrow['description']; 
        $shared_with = $myrow['shared_with']; 
        $edit = $myrow['edit']; 
        $str .=  '<div class="log_entry"><div class="log_head fl">'.$added_by.' wrote on '.$date.'</div>';
        if(($added_by_id == $_SESSION['user_id'] && round((strtotime($date_time) - strtotime($test_date))/(3600)) <= $this->hours_limit && $this->show_delete) || ($added_by_id == $_SESSION['user_id'] && !$this->hours_limit)) $str .=  '<div class="fr"><a class="list_a" href="JavaScript:delete_log_entry('.$idin.')">Remove Note </a></div>';
                        $itm = '';

        $str .=  '<div class="cl"></div>'.$description.'</div>';

        if($this->use_comments && !$hide_comments) {
          $str .=  '<div class="fl comment_area">
                <input type="text" onKeyPress="if(event.keyCode == 13) add_comment('.$idin.')" class="textbox comment_textbox" placeholder="Write a comment here..." name="txtComment'.$idin.'"  id="txtComment'.$idin.'"  />
                </div>
                <div class="fl" style="padding-left: 10px;">
                <input class="comment_button" type="button" name="btnSave" id="btnSave" value="Add" onClick="add_comment('.$idin.')" />
                </div>
                <div class="cl"></div>
                ';
        }
        $sql = "
                select
                $table"."_comments.id as idin,
                $table"."_comments.date_added as `added_on`,
                CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                REPLACE($table"."_comments.description, '\n', '<br />') as `comment`
                " . ($this->use_comments && !$hide_comments ? ", CONCAT('<a class=\"list_a\" href=\"JavaScript:delete_comment(', $table"."_comments.id, ')\">Remove Comment</a>') as `delete`" : "") . "
                from $table"."_comments
                inner join users on users.id = $table"."_comments.user_id
                where $table"."_id = $idin
                order by $table"."_comments.date_added";
                //$str .=  "<textarea>" . $sql . "</textarea>";
        //$str .=  $list_obj->draw_list();
        $result2 = $this->dbi->query($sql);
        while($myrow2 = $result2->fetch_assoc()) {
          $id_added = $myrow2['idin'];
          $added_by_id = $myrow2['added_by_id']; 
          $added_on = Date("d-M-Y", strtotime($myrow2['added_on']));
          $added_time = Date("H:i", strtotime($myrow2['added_on']));
          $added_by = $myrow2['added_by'];
          $comment = $myrow2['comment'];
          $delete = $myrow2['delete'];
          $str .=  "<div class=\"log_entry comment\"><div class=\"fl comment_head\">$added_by wrote on $added_on at $added_time</div>";

          if($added_by_id == $_SESSION['user_id'] && round((strtotime($date_time) - strtotime($myrow2['added_on']))/(3600)) <= $hours_limit)  $str .=  '<div class="fr">'.$delete.'</div>';

//            if($added_by_id == $_SESSION['user_id']) $str .=  "<div class=\"fr\">$delete</div>";
          $str .=  "<div class=\"cl\"></div>$comment</div>";
        }
        $target_dir = "/home/yoda/downloads/notes_images";
        $str .= ($this->show_cam ? '<div class=\"cl\"></div><div onClick="show_hide_img('.$idin.',\''.$target_dir.'\');" class="img_button" id="img_button'.$idin.'"></div><div class="cl"></div><div class="img_uploader" id="img_uploader'.$idin.'">
        <iframe id="photo_upload'.$idin.'" frameborder="0" width="100%" height="500px;"></iframe>
        </div><div class="cl"></div>' : '');
        if (count(glob("$target_dir/$idin/*"))) {
          $str .= '<script>show_hide_img('.$idin.',\''.$target_dir.'\');</script>';
        }
      }
    }
    return $str;
  }

  
/*  
  function calendar_nav() {
    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);

    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }
    if($nav_month > 0) {
      $nav1 = "and MONTH($table.date) = $nav_month";
    } else {
      $nav_month = "ALL Months";
    }
    if($nav_year > 0) {
      $nav2 = "and YEAR($table.date) = $nav_year";
    } else {
      $nav_year = "ALL Years";
    }
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
      <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
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
  
  }
*/
  
}

?>