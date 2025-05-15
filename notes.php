<?php

class notes extends Controller {

    var $id;
    var $notes_heading;
    var $hours_limit = 24;
    var $show_delete;
    var $table;
    var $table_id_name;
    var $add_title;
    var $title_bare;
    var $num_per_page;  //Set to 0 for no pagination
    var $use_comments;
    var $hide_enter_comments;
    var $hide_add_comment;
    var $moderate;
    var $excel_file;
    var $date_time;
    var $show_cam;
    var $last_id;  //The id of the last item added
    var $img_folder;
    //var $default_days = "all";
    var $default_days = "all";
    var $quick_add = "";
    var $division_id;

    function __construct() {
        $this->db_init();
        //$this->dbi = $this->dbi;
        $sql = "select states.item_name as `state` from users left join states on states.id = users.state where users.id = " . $_SESSION['user_id'];
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            date_default_timezone_set('Australia/' . $myrow['state']);
            $this->date_time = date('Y-m-d H:i:s');
        }

        $user_divs = $this->get_divisions($_SESSION['user_id'], 0, 1);

        if (strrpos($user_divs, ',') !== false) {
            $div_id = $_COOKIE["NotesDivisionId"];
            $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
            if ($this->division_id)
                setcookie("NotesDivisionId", $this->division_id, 2147483647);
        } else {
            $this->division_id = $user_divs;
        }
    }

    function display_add() {
        $table = $this->table;
        $this->dbi = $this->dbi;
        $date_time = $this->date_time;
        $title = $this->title_bare;
        $division_id = $this->division_id;

        $calByDate = (isset($_REQUEST['calByDate']) ? $_REQUEST['calByDate'] : null);
        $days = (isset($_GET['days']) ? $_GET['days'] : ($this->default_days == 'all' ? $this->default_days : 'today'));
        $qry_str = ($_GET['show_min'] ? "&show_min=1" : "");
        $qry_str .= ($_GET['lookup_id'] ? '&lookup_id=' . $_GET['lookup_id'] : '');
        //$qry_str .= ($show_min ? "&show_min=1" : "");
        //$qry_str .= ($days ? "&days=" . $days : "");
        //$qry_str .= ($calByDate ? '&calByDate=' . $calByDate : '');
        if ($calByDate)
            $days = null;



        $itm = new input_item;
        $str .= $itm->setup_cal();

        $is_mobile = $this->f3->get('is_mobile');
        if($is_mobile) {
            $str .= str_replace("<span></span>", "", $this->notes_heading);
        } else {
            $nav_str .= ($days != 'today' && $days != 'all' ?
                    '<a target="_blank" uk-tooltip="title: PDF Download<br />' . $title . '<br />' . date("l, d/M/Y") . '" class="division_nav_item ' . ($days == 'today' ? "division_nav_selected" : "") . '" href="?pdf=1&days=' . $days . $qry_str . ($calByDate ? '&calByDate=' . $calByDate : '') . '">PDF</a>' : '');
            $nav_str .= '<a uk-tooltip="title: Show Today\'s Entries<br />' . $title . '<br />' . date("l, d/M/Y") . '" class="division_nav_item ' . ($days == 'today' ? "division_nav_selected" : "") . '" href="?days=today' . $qry_str . '">Today</a><a uk-tooltip="title: Show ALL Entries<br />' . $title . '<br />' . date("l, d/M/Y") . '" class="division_nav_item ' . ($days == 'all' ? "division_nav_selected" : "") . '" href="?days=all' . $qry_str . '">All</a>';
            $day_count = 7;
            for ($i = 1; $i <= $day_count; $i++) {
                $nav_str .= '<a uk-tooltip="title: Show ' . $title . ' from ' . $i . ' Day' . ($this->pluralise($i)) . ' ago.<br />' . date("l, d/M/Y", strtotime("-$i days")) . '" class="division_nav_item ' . ($days == -$i ? "division_nav_selected" : "") . '" href="?days=' . -$i . ($division_id ? "&division_id=$division_id" : "") . $qry_str . '">' . $i . '</a>';
            }
            $nav_str .= $itm->cal("calByDate", "", ' style="width: 100px;" placeholder="By Date" uk-tooltip="title: View the ' . $title . ' on a Chosen Date"', "", "", "", "");
            $nav_str .= '<input type="button" onClick="by_date()" class="btn" value="Go"> &nbsp; &nbsp;';
            $str .= str_replace("<span></span>", $nav_str, $this->notes_heading);
        }

        if ($this->quick_add) {
            $str .= '
      <script>
        function add_text(txt) {
          //tmp = document.getElementById("txtNotes").value
          if(txt == "Current Time") {
            var today = new Date();
            var hours = today.getHours() < 10 ? "0" + today.getHours() : today.getHours();
            var minutes = today.getMinutes() < 10 ? "0" + today.getMinutes() : today.getMinutes();
            txt = hours + ":" + minutes
          }
          document.getElementById("txtNotes").value += " " + txt
          document.getElementById("txtNotes").focus()
        }
        function clear_notes() {
        //alert("hello");
          document.getElementById("txtNotes").value = "";
           document.frmEdit.submit();
          //document.getElementById("txtNotes").focus();
        }
      </script>
      <style>
        
      </style>
      
      
      ';
            $sql = "select item_name from lookup_fields where lookup_id = (select id from lookups where item_name = '{$this->quick_add}') order by sort_order, item_name";
            if ($result = $this->dbi->query($sql)) {
                $str .= '<div style="display: inline-block !important; margin-left: 0px;" class="custom_menu_bar">Click Text to Add &gt;&gt;';
                while ($myrow = $result->fetch_assoc()) {
                    //$idin = $myrow[$this->table_id_name]; 
                    $item_name = $myrow['item_name'];
                    $str .= "<a href=\"JavaScript:add_text('$item_name')\">$item_name</a>";
                }
                $str .= '</div>';
            }
        }else{
            
            $str .= '
      <script>
        
        function clear_notes() {
        
          document.getElementById("txtNotes").value = ""
          //document.frmEdit.submit();
        }
        
        function clear_srch(){
            //alert("hello");
             document.getElementById("txtNotes").value = "";
             document.getElementById("srchNotes").value = "";
             document.getElementById("hdnSearchNotes").value = 0;
             
           document.frmEdit.submit();
        }
      </script>
      <style>
        
      </style>
      
      
      ';
        }

        $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);
        
        $txtSrchNotes = (isset($_POST['srchNotes']) ? $this->dbi->real_escape_string($_POST['srchNotes']) : null);
         
        $hdnSearchNotes = (isset($_POST['hdnSearchNotes']) ? $_POST['hdnSearchNotes'] : null);
        if($txtNotes && !$hdnSearchNotes){
            $sql = "insert into $table (" . $this->table_id_name . ", date, description, user_id " . ($this->notesToUser ? ", to_user_id" : "") . ($division_id != "ALL" && $division_id ? ", division_id" : "") . ") values (" . $this->id . ", '$date_time', '$txtNotes', " . $_SESSION['user_id'] . ($this->notesToUser ? ", $this->notesToUser" : "") . ($division_id != "ALL" && $division_id ? ", $division_id" : "") . ");";
           // echo $sql;
            //die;
            //return $this->ta($sql);
            $result = $this->dbi->query($sql);
        }
        $hide_comments = (isset($_GET['hide_comments']) ? $_GET['hide_comments'] : null);
        $str .= ($hide_comments ? "" : '<style>table.forum_item tr:nth-child(3n+1) { background-color: #DDDDDD; }</style>');

        $hdnAddID = (isset($_POST['hdnAddID']) ? $_POST['hdnAddID'] : null);
        $hdnDeleteCommentID = (isset($_POST['hdnDeleteCommentID']) ? $_POST['hdnDeleteCommentID'] : null);
        $hdnDeleteEntryID = (isset($_POST['hdnDeleteEntryID']) ? $_POST['hdnDeleteEntryID'] : null);

        if ($hdnAddID) {
            $comment = $this->dbi->real_escape_string($_POST['txtComment']);
//      $sql = "insert into site_notes_comments(site_notes_id, user_id, date_added, description) values ($hdnAddID, ".$_SESSION['user_id'].", '$date_time', '$comment')";
            $sql = "insert into $table" . "_comments ($table" . "_id, user_id, " . ($this->srchUserId ? "to_user_id, " : "") . " date_added, description) values ($hdnAddID, " . $_SESSION['user_id'] . ($this->srchUserId ? ", $this->srchUserId" : "") . ",'$date_time', '$comment')";
     // echo "mmm ".$sql;
      
            $result = $this->dbi->query($sql);
            $this->last_id = $this->dbi->insert_id;
            //$str .=  $sql;
            $msg = "Comment Added...";
        } else if ($hdnDeleteCommentID) {
            $sql = "delete from $table" . "_comments where id = $hdnDeleteCommentID";
            $result = $this->dbi->query($sql);
            $msg = "Comment Deleted...";
        } else if ($hdnDeleteEntryID) {
            $sql = "delete from $table where id = $hdnDeleteEntryID";
            $result = $this->dbi->query($sql);
            if ($this->use_comments) {
                $sql = "delete from $table" . "_comments where $table" . "_id = $hdnDeleteEntryID";
                $result = $this->dbi->query($sql);
            }
            $msg = "Note Deleted...";
        }
        if ($msg)
            $str .= $this->message($msg, 2000);
        if (!$this->moderate) {
            
//            if($_REQUEST['hdnSearchNotes'] == 1){
//                $txtSrchNotes = $txtSrchNotes;
//            }else{
//                $txtSrchNotes = "";
//            }
            
            $txtSrchNotes = $txtSrchNotes;
            
            
          if(!$this->srchUserId){
              
              $notesUserList = $this->divisionUserList($this->site_id, $notes->division_id,0);

//        prd($userList);
//        die;
                  $pageName = ($pfix == 'site_notes' ?'GetSiteNotes':'OccurrenceLog');

                $selectNotesUser = '<select name="notesToUser"  id="notesToUser" style="width:250px;margin-bottom:20px;"><option value="0">All User</option>';
                if ($notesUserList) {
                    foreach ($notesUserList as $ukey => $uvalue) { 
                        $selectNotesUser .= '<option '.$selected.' value="' . $uvalue['user_id'] . '" >' . $uvalue['name'] . " " . $uvalue['surname'] . ' </option>';
                    }
                }
                $selectNotesUser .= '</select>';
              
              
              $selectNotesUser = "";
              
            $str .= $this->add_title . $selectNotesUser.'<textarea class="textbox note_textbox" placeholder="Enter Notes Here, or enter items to search for, separated by commas for more than one item..." name="txtNotes" id="txtNotes" class=""></textarea>
                               <div class="fl"><input type="button" onClick="add_note()" value="Post" />';
            $str .= '&nbsp; &nbsp;<input type="button" onClick="clear_notes()" value="Clear" /></div>';
          }  
          
          $str .= '<div style="clear:both"><div class="fl"><br/><br/>'
                  . '<input class="" style="width: 490px;height:36px;" placeholder="                                                      search keywords" name="srchNotes" id="srchNotes" value="'.$txtSrchNotes.'" class=""/></div>';
           // $str .= '&nbsp; &nbsp;<input type="button" onClick="search_notes()" value="Search" /></div>';
            // $str .= '&nbsp; &nbsp;<input type="button" onClick="clear_srch()" value="Clear" />';
            

            
        }

        $str .= '
          <input type="hidden" name="txtComment" id="txtComment">
          <input type="hidden" name="hdnAddID" id="hdnAddID">
          <input type="hidden" name="hdnDivisionID" id="hdnDivisionID" value="' . ($division_id == 'ALL' ? 0 : $division_id) . '">

          <input type="hidden" name="hdnDeleteCommentID" id="hdnDeleteCommentID">
          <input type="hidden" name="hdnDeleteEntryID" id="hdnDeleteEntryID">
          <input type="hidden" name="hdnSearchNotes" id="hdnSearchNotes">
          <input type="hidden" name="idin" id="idin">
          <script language="JavaScript">
            function add_note() {
             document.getElementById("hdnSearchNotes").value = 0;
            var txtNotes = document.getElementById("txtNotes").value;
              if(txtNotes.trim())
              {
                document.frmEdit.submit();
              }else{
                alert("Please enter some notes item(s)...")
               }
            }
            

            $("#srchNotes").keyup(function(e){
            var code = e.keyCode || e.which;
                
            if(code == 13){
                document.frmEdit.submit();
            }
              //  alert(code);
              //$("input").css("background-color", "pink");
            });


            function search_notes() {
            if(document.getElementById("txtNotes")){
                document.getElementById("txtNotes").value = "";
            }
             // if(document.getElementById("srchNotes").value) {
                document.getElementById("hdnSearchNotes").value = 1
                document.frmEdit.submit();
//              /*} else {
//              document.getElementById("hdnSearchNotes").value = 0;
//                alert("Please enter search item(s)...")
//              }*/
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
              document.frmEdit.action="?hdnReportFilter=1&selDateMonth=' . $_GET['selDateMonth'] . '&selDateYear=' . $_GET['selDateYear'] . '"
              document.frmEdit.submit();
            }
            function by_date() {
              if(document.getElementById("calByDate").value) {
                document.frmEdit.submit()
              }
            }
          </script>
    ';

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
                   $table." . $this->table_id_name . " = " . $this->id . "
                   order by date DESC;
    ";
        $xl_obj->sql_xl($this->excel_file);
    }

    function display_notes() {

//      echo $this->site_id;
//      die;
        $table = $this->table;
        $this->dbi = $this->dbi;
        $date_time = $this->date_time;
        $show_min = ($_GET['show_min'] ? '?show_min=1' : '');
        $pdf = (isset($_GET['pdf']) ? $_GET['pdf'] : null);
        $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
        $division_id = $this->division_id;

        $days = (isset($_GET['days']) ? $_GET['days'] : ($this->default_days == 'all' ? $this->default_days : ($this->hide_enter_comments ? 'all' : 'today')));
        $calByDate = (isset($_REQUEST['calByDate']) ? $_REQUEST['calByDate'] : null);

        if ($calByDate) {
            $date_by = date('Y-m-d', strtotime(str_replace('/', '-', $calByDate)));
            $date_by_show = date("l, d/M/Y", strtotime($date_by));
        } else if ($days != 'all') {
            if ($days == 'today') {
                $date_by = date("Y-m-d");
                $date_by_show = date("l, d/M/Y");
            } else {
                $date_by = (isset($_POST['date_by']) ? $_POST['date_by'] : date("Y-m-d", strtotime("$days days")));
                $date_by_show = date("l, d/M/Y", strtotime("$days days"));
            }
        }

        if ($date_by)
            $srch = ($this->table_id_name ? " and " : " where ") . "DATE($table.date) = '$date_by'";

        $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $curr_divs_site = $this->get_divisions($this->site_id, 0, 1);

        if($pdf) {
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->setAutoPageBreak(false);

            $sql = "select CONCAT(users2.name, ' ', users2.surname) as `result`
                   from $table
                   left join users2 on users2.id = $table.site_id
                   " . ($this->table_id_name ? "where $table." . $this->table_id_name . " = " . $this->id : "") . "
                   $srch";

            $heading = ($this->table == 'site_notes' ? "Whiteboard" : "Occurrence Log") . "\r\n" . $this->get_sql_result($sql) . "\r\n" . $date_by_show;

            $this->pdf_header($pdf, $heading);

            $line_height = 5.5;
            $cell_width = 190;
            $startx = 10;
            $starty = 30;

            $pic_right = 95;

            $break_point = 285;

            $startx_indent = 15;
            $cell_width_indent = $cell_width - ($startx_indent - $startx);
            $image_height = 50;

            $sql = "select $table.id as `" . $this->table_id_name . "`, CONCAT(DATE_FORMAT($table.date, '%a %d-%b-%Y at %H:%i')) as `date`, $table.date as `test_date`,
                  $table.division_id,
                   CONCAT(users2.name, ' ', users2.surname) as `site`, users2.id as `location_id`, $table.user_id,
                   CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                   $table.description as `description`
                   from $table
                   left join users on users.id = $table.user_id
                   left join users2 on users2.id = $table.site_id
                   " . ($this->table_id_name ? "where $table." . $this->table_id_name . " = " . $this->id : "") . "
                   $srch
                   order by $table" . ".date";
            
           
//. " or $table." . $this->table_id_name . " = 0"
            //$pdf->SetXY(130, 30); 	$pdf->MultiCell(50,6, $sql,0);

            $oldy = $pdf->GetY();
            $pdf->SetXY($startx, $starty);
            $pdf->MultiCell($cell_width, 0, "", 0);

            $result = $this->dbi->query($sql);

            while ($myrow = $result->fetch_assoc()) {
                $site = $myrow['site'];

                $idin = $myrow[$this->table_id_name];
                $added_by_id = $myrow['added_by_id'];
                $site_id = $myrow['location_id'];
                $date = $myrow['date'];
                $test_date = $myrow['test_date'];
                $added_by = $myrow['added_by'];
                $site = $myrow['site'];
                $description = $myrow['description'];
                $edit = $myrow['edit'];
                $added_user_id = $myrow['user_id'];

                $div_id = $myrow['division_id'];
                if ($div_id == 0) {
                    if ($div_str = $this->get_divisions($added_user_id))
                        $div_str = " ($div_str) ";
                } else {
                    $div_str = " (" . $this->get_sql_result("select SUBSTRING(item_name, 1, 3) as `result` from companies where id = $div_id") . ")";
                }

                $added_by = $added_by . $div_str . ' wrote on ' . $date;

                $yl = $pdf->GetY() + $height_add;
                if ($yl > $break_point) {
                    $this->pdf_header($pdf, $heading);
                    $yl = $starty;
                }
                $pdf->SetTextColor(0, 90, 0);
                $pdf->SetXY($startx, $yl);
                $pdf->MultiCell($cell_width, 6, $added_by, 0);
                $pdf->SetTextColor(30, 30, 30);

                $items = explode("\r\n", $description);
                foreach ($items as $item) {
                    if (trim($item)) {
                        $yl = $pdf->GetY();
                        if ($yl > $break_point) {
                            $this->pdf_header($pdf, $heading);
                            $yl = $starty;
                        }
                        $pdf->SetXY($startx, $yl);
                        $pdf->MultiCell($cell_width, 6, utf8_decode($item), 0);
                    }
                }

                //$str .=  '<div class="cl"></div>'.$description.'</div>';

                $sql = "
                select
                $table" . "_comments.id as idin,
                $table" . "_comments.date_added as `added_on`,
                CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                $table" . "_comments.description as `comment`
                from $table" . "_comments
                inner join users on users.id = $table" . "_comments.user_id
                where $table" . "_id = $idin
                order by $table" . "_comments.date_added";

                $yl = $pdf->GetY();

                //$pdf->SetXY($startx, $yl); 	$pdf->MultiCell($cell_width,6, "test: " . $this->table_id_name, 0);


                if ($result2 = $this->dbi->query($sql)) {
                    while ($myrow2 = $result2->fetch_assoc()) {
                        $id_added = $myrow2['idin'];
                        $added_by_id = $myrow2['added_by_id'];
                        $added_on = Date("d-M-Y", strtotime($myrow2['added_on']));
                        $added_time = Date("H:i", strtotime($myrow2['added_on']));
                        $added_by = $myrow2['added_by'];
                        $comment = $myrow2['comment'];

                        $added_by = "$added_by replied on $added_on at $added_time";
                        $yl = $pdf->GetY();
                        if ($yl > $break_point) {
                            $this->pdf_header($pdf, $heading);
                            $yl = $starty;
                        }
                        $pdf->SetTextColor(0, 90, 0);
                        $pdf->SetXY($startx_indent, $yl);
                        $pdf->MultiCell($cell_width_indent, 6, $added_by, 0);
                        $pdf->SetTextColor(30, 30, 30);
                        $yl = $pdf->GetY();
                        if ($yl > $break_point) {
                            $this->pdf_header($pdf, $heading);
                            $yl = $starty;
                        }
                        $pdf->SetXY($startx_indent, $yl);
                        $pdf->MultiCell($cell_width_indent, 6, utf8_decode($comment), 0);

                        //$str .=  "<div class=\"log_entry comment\"><div class=\"fl comment_head\">$added_by wrote on $added_on at $added_time</div>";
                        //$str .=  "<div class=\"cl\"></div>$comment</div>";
                    }
                }
                $target_dir = $this->f3->get('download_folder') . $this->img_folder;
                /* $str .= ($this->show_cam ? '<div class=\"cl\"></div><div onClick="show_hide_img('.$idin.',\''.$target_dir.'\');" class="img_button" id="img_button'.$idin.'"></div><div class="img_label">Upload Images &gt;&gt;</div><div class="cl"></div><div class="img_uploader" id="img_uploader'.$idin.'">
                  <iframe id="photo_upload'.$idin.'" frameborder="0" width="100%" height="500px;"></iframe>
                  </div><div class="cl"></div>' : '');
                 */
                //$str .= "<p>$target_dir/$idin/*</p>";
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
                                $this->pdf_header($pdf, $heading);
                                $yl = $starty;
                            }

                            $pdf->SetXY($startx, $yl);
                            //$pdf->MultiCell(70,6, $x, 0);
                            $pdf->Image("$target_dir/$idin/" . $fileinfo->getFilename(), $startx + (!($x % 2) ? $pic_right : 0), $yl, 0, $image_height);

                            //$yl += $image_height;
                            $height_add = + (($x % 2) ? 0 : $image_height);

                            //if($x == 1) $str .= "<p style=\"font-weight: bold;\">Image Attachments</p>";
                            //$str .= '<img style="width:49%;" src="' . $this->f3->get('full_url') . 'Image?i=' . urlencode($this->encrypt($fileinfo->getFilename())) . '&alt_flder=' . urlencode("$target_dir/$idin/") . '" />';
                        }
                    }
                    $x++;
                    $height_add += (($x % 2) ? 0 : $image_height);
                }
            }


            /* $pdf->SetFont('Arial','',11);
              $pdf->SetXY(72,12.5); 	$pdf->Cell(110,6,'By: ' . $assessor . ' on ' . $date);
              $pdf->SetXY(72,18); 	$pdf->Cell(110,6,'Subject: ' . $subject);
              $pdf->SetXY($startx+($cell_width*2),$starty-6); 	$pdf->MultiCell($cell_width,6, "Additional Text",1);
              $pdf->SetXY($startx,$starty-6); 	$pdf->MultiCell($cell_width,6, "Question",1);
              $pdf->SetXY($startx+$cell_width,$starty-6); 	$pdf->MultiCell($cell_width,6, "Answer",1);
              $y1 = $pdf->GetY();
              $pdf->SetFillColor(255,255,255); */

            $pdf->Output();
            exit;
        }

        $page = (isset($_GET['page']) ? $_GET['page'] : 1);
        
//        echo $this->num_per_page;
//        die;
        //$num_per_page = (isset($_GET['show_min']) || $lookup_id || $this->f3->get('is_client') || $this->f3->get('is_client_staff') || $this->f3->get('is_site') || $this->f3->get('is_admin') ? $this->num_per_page : 0);
        $num_per_page = $this->num_per_page;
        $this->num_per_page = $num_per_page;
        //$num_per_page = $this->num_per_page;

        $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);
        $txtSrchNotes = (isset($_POST['srchNotes']) ? $this->dbi->real_escape_string($_POST['srchNotes']) : null);
        $hdnSearchNotes = (isset($_POST['hdnSearchNotes']) ? $_POST['hdnSearchNotes'] : null);
        if ($txtSrchNotes) {
            $srch = ($this->table_id_name ? " and " : " where ") . "(";
            if(stripos($txtSrchNotes, ",")) {
                $srches = explode(",", $txtSrchNotes);
                $done = 0;
                
                foreach ($srches as $srch_item) {
                    $srch .= " $table.description like '%$srch_item%' or ";
                }                
                
                $srch = substr($srch, 0, (strlen($srch) - 4)) . ")";
            } else {
                
                  if($table == "compliance_check_notes"){
                        $srchNotes .= "  ";
                        $srchNotes .= " $table.description like '%$txtSrchNotes%' ";
//                        $srchNotes .= " or ((SELECT count(snc.id) FROM $table"."_comments snc WHERE snc.description LIKE '%$txtSrchNotes%'  AND snc.".$table."_id = $table".".id) > 0)";
//                        $srchNotes .= ") ";
//                        $srchComment .= "  ";
//                        $srchComment .= "  $table"."_comments.description like '%$txtSrchNotes%' ";
                        //$srch .= " or ((SELECT count(snc.id) FROM $table"."_comments snc WHERE snc.description LIKE '%$txtNotes%'  AND snc.site_notes_id = site_notes.id) > 0)";
                        $srchNotes .= ") ";
                  }else{                
                        $srchNotes .= "  ";
                        $srchNotes .= " $table.description like '%$txtSrchNotes%' ";
                        $srchNotes .= " or ((SELECT count(snc.id) FROM $table"."_comments snc WHERE snc.description LIKE '%$txtSrchNotes%'  AND snc.".$table."_id = $table".".id) > 0)";
                        $srchNotes .= ") ";
                        $srchComment .= "  ";
                        $srchComment .= "  $table"."_comments.description like '%$txtSrchNotes%' ";
                        //$srch .= " or ((SELECT count(snc.id) FROM $table"."_comments snc WHERE snc.description LIKE '%$txtNotes%'  AND snc.site_notes_id = site_notes.id) > 0)";
                        $srchComment .= ") ";
                  }
               
            }
        }

       //$_SESSION['user_id']
        
        if ($this->srchUserId) {
            //$srch_notes .= $srch .$srchNotes. " and (($table.to_user_id IS NULL or  ";
            
            $srch_notes .= $srch .$srchNotes. " and (( ";
            $srch_notes .= "$table.to_user_id = '$this->srchUserId')";
            
            $srch_notes .= " or $table.user_id = '$this->srchUserId'";
            
            $srch_notes .= ")";

            $srch_comment .= $srch .$srchComment . " and ($table" . "_comments.to_user_id IS NULL or  ";
            $srch_comment .= "$table" . "_comments.to_user_id = '$this->srchUserId')";
        } else {
            $srch_notes .= $srch .$srchNotes;

            $srch_comment .= $srch .$srchComment;
        }
        
       /* for occurance log code (*/
//         if($this->pfix == 'occurrence_log'){ 
//             $srch_notes = " and 1";
//              $srch_comment = " and 1";
//         }

        //" . ($division_id != "ALL" && $division_id ? " and ($table.division_id = $division_id || $table.division_id = 0)" : "") . "

        if($table == "compliance_check_notes"){
             $sql = "select SQL_CALC_FOUND_ROWS $table.id as `" . $this->table_id_name . "`, "
                     . "CONCAT(DATE_FORMAT($table.date, '%a %d-%b-%Y at %H:%i')) as `date`, "
                     . "$table.date as `test_date`,
                    $table.division_id,                  
                   CONCAT(users.name, ' ', users.surname) as `added_by`,users.user_subtype, users.id as `added_by_id`, $table.user_id,
                   REPLACE($table.description, '\n', '<br />') as `description`
                   from $table
                   left join users on users.id = $table.user_id                   
                   " . ($this->table_id_name ? "where ( $table." . $this->table_id_name . " = " . $this->id . " or $table." . $this->table_id_name . " = 0)" : "") . "                  
                   $srch_notes
                   order by $table" . ".date DESC"
                . (isset($_GET['show_min']) ? ($num_per_page ? " LIMIT " . (($page - 1) * $num_per_page) . ", " . $num_per_page : "") : " LIMIT 40 ");
            
        }else{
                $sql = "select SQL_CALC_FOUND_ROWS $table.id as `" . $this->table_id_name . "`, CONCAT(DATE_FORMAT($table.date, '%a %d-%b-%Y at %H:%i')) as `date`, $table.date as `test_date`,
                  $table.division_id,
                   CONCAT(users2.name, ' ', users2.surname) as `site`, users2.id as `location_id`,
                   CONCAT(users.name, ' ', users.surname) as `added_by`,users.user_subtype, users.id as `added_by_id`, $table.user_id,
                    $table.to_user_id,CONCAT(userto.name, ' ', userto.surname) AS `added_for`,
                   REPLACE($table.description, '\n', '<br />') as `description`
                   from $table
                   left join users on users.id = $table.user_id
                   left join users2 on users2.id = $table.site_id
                   left join users userto on userto.id = $table.to_user_id 
                   " . ($this->table_id_name ? "where ( $table." . $this->table_id_name . " = " . $this->id . " or $table." . $this->table_id_name . " = 0)" : "") . "
                   " . ($division_id != "ALL" && $division_id ? " and ($table.division_id = $division_id || $table.division_id = 0) and ($table.division_id in ($curr_divs_user) and $table.division_id in ($curr_divs_site))" : " and ($table.division_id = 0 || ($table.division_id in ($curr_divs_user) and $table.division_id in ($curr_divs_site)))") . "
                   $srch_notes
                   order by $table" . ".date DESC"
                . (isset($_GET['show_min']) ? ($num_per_page ? " LIMIT " . (($page - 1) * $num_per_page) . ", " . $num_per_page : "") : " LIMIT 40 ");
        }
        
       

        //. (isset($_GET['show_min']) ? ($num_per_page ? " LIMIT " . (($page - 1) * $num_per_page) . ", " . $num_per_page : "") : "LIMIT 50");
        //$str .=  "<textarea>" . $sql . "</textarea>";
//                   " . (isset($_GET['show_min']) ? "" : "and $table.date between date_sub(now(),INTERVAL 1 WEEK) and now()") . "
        //               . ($num_per_page ? " LIMIT " . (($page - 1) * $num_per_page) . ", " . $num_per_page : "");

//        

//     echo $sql;
//     die;
        if($_SESSION['user_id'] == '11203'){
//            echo $sql;
//            die;
          }
        
        
        $result = $this->dbi->query($sql);

        $hide_comments = (isset($_GET['hide_comments']) ? $_GET['hide_comments'] : null);
        if (!$this->hide_enter_comments) {
            $str .= '
        <style>
        .img_button {
          background-size: cover; 
          float: right;
          width: 32px; height: 32px; padding-bottom: 0px;
          background-image: url(\'' . $this->f3->get('img_folder') . 'image_show.gif\');
        }
        .img_label {
          float: right;
          display: inline-block;
          margin-top: 4px;
          margin-right: 10px;
        }
        /*.img_uploader {
          font-size: 16pt; padding: 12px;
          display: none;
          background-color: #FFFFEE;
          border: 1px solid #660000;
        }*/
        .img_uploader {
          display: none;
          padding: 0px;
          border: none;
        }
        </style>
        <script>
        function show_hide_img(id,target_dir) {
          if(document.getElementById(\'img_uploader\'+id).style.display == \'block\') {
            document.getElementById(\'img_uploader\'+id).style.display = \'none\';
            document.getElementById(\'img_button\'+id).style.backgroundImage = "url(\'' . $this->f3->get('img_folder') . 'image_show.gif\')";
          } else {
            document.getElementById(\'img_uploader\'+id).style.display = \'block\';
            document.getElementById(\'img_button\'+id).style.backgroundImage = "url(\'' . $this->f3->get('img_folder') . 'image_hide.gif\')";
            if(!document.getElementById(\'photo_upload\'+id).src) document.getElementById(\'photo_upload\'+id).src = "' . $this->f3->get('main_folder') . 'Fileman?show_min=1&target_dir="+target_dir+"&target_subdir="+id;
          }
        }
      </script>
      ';
        }
        if ($this->num_per_page) {
            $max_nav = 24; //The maximum number of nav items to show
            $sql2 = "SELECT FOUND_ROWS()";
            $result = $this->dbi->query($sql);
            $num_rows = $result->num_rows;
            $result2 = $this->dbi->query($sql2);
            $of_rows = $result2->fetch_row();
            $of_rows = $of_rows[0];
            $end_row = ($page * $num_per_page > $of_rows ? $of_rows : $page * $num_per_page);
            $start_row = ($page * $num_per_page) - $num_per_page + 1;
            $qry_str = ($_GET['show_min'] ? "&show_min=1" : "&show_min=");
            $qry_str .= ($days ? "&days=" . $days : "");
            $qry_str .= ($_GET['lookup_id'] ? '&lookup_id=' . $_GET['lookup_id'] : '');
            $qry_str .= ($calByDate ? '&calByDate=' . $calByDate : '');

            /* if($this->use_comments) {
              $str .=  (!$hide_comments ? '<a class="comment_button" href="?view_mode=1&hide_comments=1'.$qry_str.'">&gt;&gt; Hide Comments &lt;&lt;</a>' : '<a class="comment_button" href="?view_mode=1'.$qry_str.'">&lt;&lt; Show Comments &gt;&gt;</a>') . '&nbsp; &nbsp;<a class="comment_button" title="Download Excel" href="?view_mode=1'.$qry_str.'&download_xl=1">Download Excel</a>';
              } */
            $str .= "<div class=\"cl\"></div><br><br><div>" . ($num_rows ? "Showing " . ($of_rows > $num_per_page ? "$start_row-$end_row of $of_rows" : $num_rows) . " Entr" . ($num_rows > 1 ? "ies" : "y") : "No Records Found") . "...</div>";
            //$str .=  ($result->num_rows ? '<table class="forum_item">' : 'If you can\'t see your latest entries, try viewing last months...');
            $item_count = 0;
            if ($of_rows > $num_per_page) {
                $num_pages = ceil($of_rows / $num_per_page);

                $test = $page - ($max_nav / 2);
                $first_page = ($test > 1 ? $test : 1);
                $test2 = $first_page + $max_nav;
                $last_page = ($test2 > $num_pages ? $num_pages : $test2);

                if ($last_page - $first_page < $max_nav) {
                    $test = $page - $max_nav + ($num_pages - $page);
                    $first_page = ($test > 1 ? $test : 1);
                }

                $pagnav = '<ul class="pagination">';
                $pagnav .= '<li><a uk-tooltip="title: Page 1 of ' . $num_pages . '" href="?page=1' . $qry_str . '" aria-label="First"><span aria-hidden="true">|&laquo;</span></a></li>';
                for ($x = $first_page; $x <= $last_page; $x++) {
                    $pagnav .= '<li><a uk-tooltip="title: Page ' . $x . ' of ' . $num_pages . '" href="?page=' . $x . $qry_str . '" ' . ($x == $page ? 'style="background-color: #EEEEEE !important;" ' : '') . 'aria-label="First"><span aria-hidden="true">' . $x . '</span></a></li>';
                }
                $pagnav .= '<li><a uk-tooltip="title: Last Page<br />Page ' . $num_pages . '" href="?page=' . $num_pages . $qry_str . '" aria-label="Last"><span aria-hidden="true">&raquo;|</span></a></li>';
                $pagnav .= "</ul>";
            }
            $str .= $pagnav;
        } else {
            $num_rows = $result->num_rows;
            if ($num_rows)
                $str .= "<div class=\"cl\"></div><p>Showing $num_rows Record" . ($num_rows == 1 ? "" : "s") . "</p>";
        }
        if ($num_rows) {
            $site_id = 0;
            $old_site_id = 0;
            while ($myrow = $result->fetch_assoc()) {
                //$idin = $myrow[$this->table_id_name]; 
                $idin = $myrow[$this->table_id_name];
                $added_by_id = $myrow['added_by_id'];
                $site_id = $myrow['location_id'];
                $date = $myrow['date'];
                $test_date = $myrow['test_date'];

                if ($myrow['user_subtype'] > 1) {
                    $userSubType = ($myrow['user_subtype'] == 2 ? "Client Representive" : "Employee");
                } else {
                    $userSubType = "";
                }

                $added_by = $myrow['added_by'] . " - " . $userSubType;

                if ($myrow['added_for']) {
                    $added_for_main_notes = ' msg for ' . $myrow['added_for'];
                } else {
                    $added_for_main_notes = "";
                }

                $site = $myrow['site'];
                $description = $myrow['description'];
                $edit = $myrow['edit'];
                $added_user_id = $myrow['user_id'];

                if ($this->moderate && $old_site_id != $site_id && $site) {
                    $str .= "<h3>" . ($this->table == 'site_notes' ? "Whiteboard" : "Occurrence Log") . " For $site</h3>";
                }

                $old_site_id = $site_id;

                $div_id = $myrow['division_id'];
                if ($div_id == 0) {
                    $divisionClass = 'All';
                    if ($div_str = $this->get_divisions($added_user_id))
                        $div_str = " ($div_str) ";
                } else {
                    $divisionClass = $this->get_sql_result("select SUBSTRING(item_name, 1, 3) as `result` from companies where id = $div_id");
                    $div_str = " (" . $divisionClass . ")";
                }

                $str .= '<div class="mainpost"><div class="log_entry "><div class="log_head fl ' . $divisionClass . '">' . $added_by . $div_str . ' wrote on ' . $date . $added_for_main_notes . '</div>';
                //if(($added_by_id == $_SESSION['user_id'] && round((strtotime($date_time) - strtotime($test_date))/(3600)) <= $this->hours_limit && $this->show_delete) || ($added_by_id == $_SESSION['user_id'] && !$this->hours_limit)) 
                if (($added_by_id == $_SESSION['user_id'] || $this->f3->get('is_admin')) && $this->show_delete)
                    $str .= '<div class="fr"><a class="list_a" href="JavaScript:delete_log_entry(' . $idin . ')">Remove Note </a></div>';

                $str .= '<div class="cl"></div>' . $description . '</div>';

                if ($this->use_comments && !$hide_comments) {
                    if (!$this->hide_enter_comments && !$this->hide_add_comment) {
                        $str .= '<div class="fl comment_area">
                  <input type="text" onKeyPress="if(event.keyCode == 13) add_comment(' . $idin . ')" class="textbox comment_textbox" placeholder="Write a comment here..." name="txtComment' . $idin . '"  id="txtComment' . $idin . '"  />
                  </div>
                  <div class="fl" style="padding-left: 10px;">
                  <input class="comment_button" type="button" name="btnSave" id="btnSave" value="Add" onClick="add_comment(' . $idin . ')" />
                  </div>
                  <div class="cl"></div>
                  ';
                    }





                    $sql = "
                  select
                  $table" . "_comments.id as idin,
                  $table" . "_comments.date_added as `added_on`,
                  $table.division_id,
                  CONCAT(users.name, ' ', users.surname) as `added_by`, users.id as `added_by_id`,
                  REPLACE($table" . "_comments.description, '\n', '<br />') as `comment`,
                  $table"."_comments.to_user_id,CONCAT(userto.name, ' ', userto.surname) AS `subcomment_added_for`   
                  " . (!$this->hide_enter_comments ? ", CONCAT('<a class=\"list_a\" href=\"JavaScript:delete_comment(', $table" . "_comments.id, ')\">Remove Comment</a>') as `delete`" : "") . "
                  from $table" . "_comments
                  inner join users on users.id = $table" . "_comments.user_id
                  left join users userto on userto.id =  $table" . "_comments.to_user_id     
                  left join $table on $table.id = $table" . "_comments.$table" . "_id
                  where $table" . "_id = $idin 
                  $srch_comment    
                  order by $table" . "_comments.date_added";
                  //prd($sql);
                    //$str .=  "<textarea>" . $sql . "</textarea>";
                    //$str .=  $list_obj->draw_list();
                    //return $this->ta($sql);
                    $result2 = $this->dbi->query($sql);
                    while ($myrow2 = $result2->fetch_assoc()) {
                        $id_added = $myrow2['idin'];
                        $added_by_id = $myrow2['added_by_id'];

                        if($myrow2['subcomment_added_for']) {
                            $added_for_subcomment_notes = ' msg for ' . $myrow2['subcomment_added_for'];
                        } else {
                            $added_for_subcomment_notes = "";
                        }
                        
                        $added_on = Date("d-M-Y", strtotime($myrow2['added_on']));
                        $added_time = Date("H:i", strtotime($myrow2['added_on']));
                        $added_by = $myrow2['added_by'];
                        $comment = $myrow2['comment'];
                        $delete = $myrow2['delete'];

                        if ($this->moderate && $old_site_id != $site_id && $site) {
                            $str .= "<h3>" . ($this->table == 'site_notes' ? "Whiteboard" : "Occurrence Log") . " For $site</h3>";
                        }

                        $old_site_id = $site_id;

                        $div_id = $myrow2['division_id'];
                        if ($div_id == 0) {
                            if ($div_str = $this->get_divisions($added_by_id))
                                $div_str = " ($div_str) ";
                        } else {
                            $div_str = " (" . $this->get_sql_result("select SUBSTRING(item_name, 1, 3) as `result` from companies where id = $div_id") . ")";
                        }
                      
                        $str .= "<div class=\"log_entry comment\"><div class=\"fl comment_head\">$added_by$div_str replied on $added_on at $added_time </div>";
                        
                        //$added_for_subcomment_notes
                        
                        
                        //if($added_by_id == $_SESSION['user_id'] && round((strtotime($date_time) - strtotime($myrow2['added_on']))/(3600)) <= $hours_limit)  $str .=  '<div class="fr">'.$delete.'</div>';
                        if ($added_by_id == $_SESSION['user_id'] || $this->f3->get('is_admin'))
                            $str .= '<div class="fr">' . $delete . '</div>';

                        //            if($added_by_id == $_SESSION['user_id']) $str .=  "<div class=\"fr\">$delete</div>";
                        $str .= "<div class=\"cl\"></div>$comment</div>";
                    }
                }
                $target_dir = $this->f3->get('download_folder') . $this->img_folder;
                $i_dir = "notes_images";
                if (!$this->hide_enter_comments) {
                    $str .= ($this->show_cam ? '<div class=\"cl\"></div><div onClick="show_hide_img(' . $idin . ',\'' . $target_dir . '\');" class="img_button" id="img_button' . $idin . '"></div><div class="img_label">Upload Images &gt;&gt;</div><div class="cl"></div><div class="img_uploader" id="img_uploader' . $idin . '">
          <iframe id="photo_upload' . $idin . '" frameborder="0" width="100%" height="500px;"></iframe>
          </div><div class="cl"></div>' : '');
                }
                //$str .= "<p>$target_dir/$idin/*</p>";
                if (count(glob("$target_dir/$idin/*"))) {
                    if ($this->hide_enter_comments) {
                        $dir = new DirectoryIterator("$target_dir/$idin/");
                        $x = 0;
                        $file_list = Array();
                        foreach ($dir as $fileinfo) {
                            if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                                $x++;
                                if ($x == 1)
                                    $str .= "<p style=\"font-weight: bold;\">Image Attachments</p>";
                                $str .= '<img style="width:49%;" src="' . $this->f3->get('full_url') . 'Image?i=' . urlencode($this->encrypt($fileinfo->getFilename())) . '&alt_flder=' . urlencode("$target_dir/$idin/") . '" />';
                            }
                        }
                    } else {
                        $str .= '<script>show_hide_img(' . $idin . ',\'' . $target_dir . '\');</script>';
                    }
                }

                $str .= "</div>";
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