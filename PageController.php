<?php

class PageController extends Controller {

    protected $f3;

    function __construct($f3) {
        $this->f3 = $f3;
        $this->list_obj = new data_list;
        $this->notification = new Notification;
        $this->editor_obj = new data_editor;
        $this->filter_string = "filter_string";
        $this->db_init();
        $div_ids = $_COOKIE["RosteringDivisionId"];
        $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_ids ? $div_ids : 0));
        if ($this->division_id)
            setcookie("RosteringDivisionId", $this->division_id, 2147483647);
    }

    function NoticeBoard($ticker_xtra = "") {
        $main_folder = $this->f3->get('main_folder');
        $this->f3->set('js_xtra', 'scroller.js');
        $this->f3->set('css_xtra', 'notice_board.css');
//    $heading = '<h3>Notice Board</h3>';
        $sql = "select notice_board.id as idin, notice_board.sort_order, notice_board.date_modified, notice_board.opening_date, notice_board.closing_date,
        notice_board.title, notice_board.description
        FROM notice_board
        where (notice_board.id in (select foreign_id from lookup_answers where table_assoc = 'notice_board' and lookup_field_id in (" . implode(",", $_SESSION['lids']) . "))
        and closing_date >= now() and sort_order < 500) or (sort_order >= 500)
        order by sort_order";

//        echo $sql;
//        die;
        //$str .= $sql;
        $result = $this->dbi->query($sql);
        $ticker_count = 0;
        $num_items = 0;
        $js_str .= "var pausecontent = new Array();\n";
        if ($ticker_xtra) {
            if (is_array($ticker_xtra)) {
                foreach ($ticker_xtra as $itm) {
                    $js_str .= "pausecontent[$ticker_count]=" . json_encode($itm) . ";\n";
                    $ticker_count++;
                }
            } else {
                $js_str .= "pausecontent[$ticker_count]=" . json_encode($ticker_xtra) . ";\n";
                $ticker_count++;
            }
        }
        while ($myrow = $result->fetch_assoc()) {
            $idin = $myrow['idin'];
            $title = $myrow['title'];
            $sort_order = $myrow['sort_order'];
            //$str .= "<h3>$sort_order</h3>";
            $description = $myrow['description'];
            if ($sort_order < 500) {
                $str .= "<div class=\"log_entry\">" . ($title ? "<h3 class=\"log_head\">$title</h3>" : "") . "$description</div>";
                $num_items++;
            } else {
                $title = str_replace("\n", "", $title);
                $js_str .= "pausecontent[$ticker_count]=" . json_encode($title) . ";\n";
                $ticker_count++;
            }
        }
        if ($ticker_count)
            $str = $heading . "\n<script type=\"text/javascript\">\n$js_str\n\nnew pausescroller(pausecontent, 'pscroller', 'someclass', 4000);\n</script>\n" . $str;
        //$str = $heading . $str . ($ticker_count ? "<div class=\"message_box\"><h3>Headlines</h3></div>" : "");


        return $str;
    }

    function PageAccess() {
        $main_folder = $this->f3->get('main_folder');

        /* $mail = new email_q($this->dbi);
          $mail->clear_all();
          $mail->AddAddress("eggaweb@gmail.com");
          $mail->AddAddress("edgar@alliedmanagement.com.au");
          $mail->Subject = "Test Email";
          $mail_msg = "Test Message";
          $mail->Body    = $mail_msg;
          $mail->queue_message();
          $str .= nl2br($mail_msg . "<br /><br />"); */


        //if($_SESSION['u_level'] >= 1000) $uid = $_GET['uid'];
        $pid = $_REQUEST['pid'];
        $hdnGo = $_POST['hdnGo'];

        if (!$pid)
            $pid = 0;

        $sql = "select id, title from main_pages2 where id != $pid order by title";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $str .= '<a href="?pid=' . $myrow['id'] . '">' . $myrow['title'] . "</a> | ";
        }

        if ($pid) {

            $sql = "select title from main_pages2 where id = $pid";

            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $title = $myrow['title'];
            }

            $str .= "<hr />";
            $str .= '<input type="hidden" value="1" name="hdnGo" />';
            $str .= "<h3>Access Levels for $title</h3>";
            $str .= "<h5>[1] Read Only | [2] Read/Write | [3] Read/Delete | [4] Full Access | [5] Edit Only (No Adding)</h5>";
            $str .= "<hr />";

            $sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') order by sort_order, item_name";

            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                if (!$rcount && $_POST["txt$id"]) {
                    $sql = "delete from page_access where page_id = $pid;";
                    //$str .= "<h3>$sql</h3>";
                    $this->dbi->query($sql);
                    $rcount = 1;
                }
                if ($_POST["txt$id"]) {
                    $sql = "insert into page_access (page_id, group_id, access_level) values ($pid, $id, " . $_POST["txt$id"] . ");";
                    //$str .= "<h3>$sql</h3>";
                    $this->dbi->query($sql);
                }
                $group = $myrow['item_name'];
                $sql = "select access_level from page_access where group_id = $id and page_id = $pid;";
                $result2 = $this->dbi->query($sql);
                $access_level = "";
                if ($myrow2 = $result2->fetch_assoc()) {
                    $access_level = $myrow2['access_level'];
                    //$str .= "<h3>$access_level</h3>";
                }

                $str .= "<input type=\"button\" onclick=\"document.frmEdit.txt$id.value='1';\" value=\"1\" />
          <input type=\"button\" onclick=\"document.frmEdit.txt$id.value='2';\"  value=\"2\" /> 
          <input type=\"button\" onclick=\"document.frmEdit.txt$id.value='3';\"  value=\"3\" /> 
          <input type=\"button\" onclick=\"document.frmEdit.txt$id.value='4';\" value=\"4\" />
          <input type=\"button\" onclick=\"document.frmEdit.txt$id.value='5';\" value=\"5\" />
          ";
                $str .= '<input name="txt' . $id . '" type="text" style="width: 45px;" value="' . $access_level . '" /> ' . $group . '<br />';
            }

            $str .= '<p><input type="submit" /></p>';
        }
        return $str;
    }

    function get_sql($hr_user, $download_xl, $gid) {
        $main_folder = $this->f3->get('main_folder');
        if (($_SESSION['u_level'] >= 1000 || $hr_user) && !$download_xl) {
            $xtra3 = ", CONCAT('<a class=\"list_a\" href=\"JavaScript:edit_record(', users.id, ')\">Edit</a>') as `Edit`";
        }
        $sql = "
        select " . (!$download_xl ? "users.id as `idin`, " : "") . ($hr_user ? " employee_id as `Emp ID`, client_id as `Client ID`, student_id as `Stu ID`, supplier_id as `Supp ID`, " : "") . " 
        CONCAT(" . (!$download_xl ? "'<a href=\"mailto:', users.email, '\">', " : "") . " users.name, ' ', users.surname " . (!$download_xl ? ", '</a>'" : "") . ") as `Name`,
        states.item_name as `State`,
                  phone as `Phone`, " . ($hr_user ? "if(users.pw != '', 'Yes', 'No') as `Has PW`," : "") . " users.email as `Email`
                  " . ($hr_user && !$download_xl ? ", CONCAT('<a class=\"list_a\" uk-tooltip=\"title: View Further Details\" href=\"{$main_folder}UserDetails?uid=', users.id, '&sid=$gid\">View Card</a>  ',
                          '<a class=\"list_a\" uk-tooltip=\"title: Edit Further Details\" href=\"{$main_folder}AccountDetails?uid=', users.id, '&sid=$gid\">Edit Card</a>  ',
                          '<a  class=\"list_a\" href=\"{$main_folder}Compliance?subject_id=', users.id, '\">Audits</a>'
                  )
                  as `**`" : "") . " $xtra3 from users
                  left join states on states.id = users.state
                  inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $gid and lookup_answers.table_assoc = 'users'
                  and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                  order by states.avetmiss_id;
    ";
        return $sql;
    }

    function UsersByGroups() {
        $main_folder = $this->f3->get('main_folder');
        $hr_user = $this->f3->get('hr_user');
        $gid = (isset($_GET['gid']) ? $_GET['gid'] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = $this->get_sql($hr_user, $download_xl, $gid);
            $xl_obj->sql_xl("$download_xl.xlsx");
        } else {
            $str .= '
        <script language="JavaScript">
        function edit_record(idin) {
          document.getElementById("idin").value = idin;
          document.frmEdit.action = "' . $main_folder . 'Edit/Users";
          document.frmEdit.submit();
        }
        </script>
        <input type="hidden" id="idin" name="idin"  value="" />
      ';

            $str .= ($_SESSION['u_level'] >= 1000 || $hr_user ? '<table class="grid2"><tr><th>Group</th><th>Results</th></tr><tr>' : "");
            $filter_string = "filter_string";
            $this->list_obj->title = "";
            $sql = "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') order by item_name";
            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $group = $myrow['item_name'];
                if ($id == $gid)
                    $group_name = $group;
                $group_str .= '[<a href=?gid=' . $id . '&download_xl=' . urlencode($group) . '>XL</a>] <a href=?gid=' . $id . '>' . $group . '</a> <br /> ';
            }
            if ($_SESSION['u_level'] >= 1000 || $hr_user) {
                $str .= '<td valign="top">' . $group_str . '</td><td valign="top">';
            }
            if ($gid) {
                $str .= "<h3>Members of: $group_name</h3>";
                $str .= '<a href=?gid=' . $gid . '&download_xl=' . urlencode($group_name) . '>Download in Excel Format</a>';
                $view_details = new data_list;

                $view_details->dbi = $this->dbi;
                $view_details->title = "";
                $view_details->show_num_records = 1;
                $view_details->num_per_page = 100;
                $view_details->nav_count = 30;
                $view_details->sql = $this->get_sql($hr_user, 0, $gid);
                //echo "<textarea>{$view_details->sql}</textarea>";
                $str .= $view_details->draw_list();
            }
            $str .= "</td></tr></table>";
            return $str;
        }
    }

    function ModerateSiteNotes() {
        $main_folder = $this->f3->get('main_folder');
        $notes = new notes;
        $notes->show_cam = 1;
        $notes->num_per_page = 50;
        $notes->show_delete = 1;
        $notes->use_comments = 1;
        $notes->moderate = 1;
        $notes->hide_add_comment = 1;
        //$notes->id = $site_id;

        $notes->table = "site_notes";
        $notes->notes_heading = "<h3>Moderate Whiteboards</h3>";
        $str .= $notes->display_add();
        $str .= $notes->display_notes();

        return $str;
    }

    function ModerateOccurrenceLog() {
        $main_folder = $this->f3->get('main_folder');
        $notes = new notes;
        $notes->show_cam = 1;
        $notes->num_per_page = 50;
        $notes->show_delete = 1;
        $notes->use_comments = 1;
        $notes->moderate = 1;
        $notes->hide_add_comment = 1;

        $notes->table = "occurrence_log";
        $notes->notes_heading = "<h3>Moderate Occurrence Log</h3>";
        $str .= $notes->display_add();
        $str .= $notes->display_notes();

        return $str;
    }

    function GetSiteNotes() {
        $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
        if ($this->f3->get('is_client')) {
            $sql = "select id as `result` from associations  where association_type_id = (select id from association_types where name = 'clients_sites') and parent_user_id = " . $_SESSION['user_id'] . " and child_user_id = $lookup_id";
            if (!$this->get_sql_result($sql)) {
                $lookup_id = $_SESSION['user_id'];
            }
        }

        return $this->SiteNotes($lookup_id);
    }

    function OccurrenceLog() {

        if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'addStrickyNotes'){
            $siteId = $_REQUEST['siteId'];
            $content = $_REQUEST['notesText'];
            $this->addStrickyNotes($siteId, $content, $_SESSION['user_id']);
            $stickyNotesStr = $this->getStickyNotesContent($siteId);
            echo $stickyNotesStr;
            die();
        }
        
        if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'deleteStrickyNotes'){
            $siteId = $_REQUEST['siteId'];
            $stickyNotesId = $_REQUEST['stickyNotesId'];
            $this->deleteStrickyNotes($stickyNotesId);
            $stickyNotesStr = $this->getStickyNotesContent($siteId);
            echo $stickyNotesStr;
            die();
        }

        $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
        return $this->SiteNotes($lookup_id, "occurrence_log");
    }

    function SiteNotes($site_idin = 0, $pfix = "site_notes") {

        $main_folder = $this->f3->get('main_folder');

        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
        $hdnDivisionID = (isset($_POST['hdnDivisionID']) ? $_POST['hdnDivisionID'] : null);

        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
//    include('top' . ($download_xl ? '_bare' : '') . '.php');

        $site_id = ($site_idin ? $site_idin : $this->get_site_id());

        if ($_SESSION['user_id'] == 5) {
            //$site_id = 3;
        }

        if ($site_id) {
            $notes = new notes;
            if (!$_REQUEST['division_id'] || $_REQUEST['division_id'] == 'ALL') {
                $notes->division_id = 'ALL';
            }
//            echo $notes->division_id;
//            die;
            $notes->show_cam = 1;
            $notes->id = $site_id;

            if (isset($_REQUEST['userid'])) {
                $srchUserId = $_REQUEST['userid'];
            } else {
                $srchUserId = 0;
            }
            $notes->srchUserId = $srchUserId;

            if (isset($_REQUEST['notesToUser'])) {
                $notesToUser = $_REQUEST['notesToUser'];
            } else {
                $notesToUser = 0;
            }
//           prd($_REQUEST);
//            echo $notesUserid;
//            die;
            //$notes->notesToUser = $notesToUser;
            $notes->notesToUser = $srchUserId;

            $sql = "select concat(name, ' ', surname) as `site_name`, state from users where users.id = " . $site_id;
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $site_name = $myrow['site_name'];
                $site_state = $myrow['state'];
            }
            $notes->table = $pfix;
            $notes->table_id_name = "site_id";
        }

        if ($pfix == "occurrence_log")
            $notes->quick_add = "occurrence_keyword";


        if ($download_xl) {
            $notes->excel_file = 'site_notes.xlsx';
            $notes->download_xl();
        } else {
            if ($site_id) {
                $notes->num_per_page = 20;
                $notes->show_delete = 1;
                $notes->use_comments = 1;
                $notes->site_id = $site_id;

                $days = (isset($_GET['days']) ? $_GET['days'] : ($notes->default_days == 'all' ? $notes->default_days : ($notes->hide_enter_comments ? 'all' : 'today')));

                $calByDate = (isset($_POST['calByDate']) ? $_POST['calByDate'] : null);
                if ($calByDate) {
                    $date_by_show = date("l, d/M/Y", strtotime($calByDate));
                } else if ($days == 'all') {
                    $date_by_show = "All Days";
                } else if ($days == 'today') {
                    $date_by_show = "Today, " . date("l, d/M/Y");
                } else {
                    $date_by_show = date("l, d/M/Y", strtotime("$days days"));
                }
                $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
                $notes->img_folder = ($pfix == 'site_notes' ? "notes_images" : "occurrence_log_images");
                $notes->title_bare = ($pfix == 'site_notes' ? "Whiteboard" : "Activity Log") . " for $site_name";

                $notes->site_id = $site_id;

                //$userList = $this->divisionUserList($site_id, $notes->division_id,0);

                $userList = $this->divisionCommentUserList($notes->table, $site_id, $notes->division_id, 0);

//        prd($userList);
//        die;
                $pageName = ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog');

                $selectNotesHeader = '<select name="srchCommentUser" onChange="changeDivisionUser(\'' . $pageName . '\',\'' . $notes->division_id . '\')" id="srchCommentUser" style="width:250px;margin-bottom:20px;"><option value="0">All User</option>';
                if ($userList) {
                    foreach ($userList as $ukey => $uvalue) {
                        if ($notes->srchUserId && ($notes->srchUserId == $uvalue['user_id'])) {
                            $selected = "selected";
                        } else {
                            $selected = "";
                        }

                        $selectNotesHeader .= '<option ' . $selected . ' value="' . $uvalue['user_id'] . '" >' . $uvalue['name'] . " " . $uvalue['surname'] . ' </option>';
                    }
                }
                $selectNotesHeader .= '</select>';

                $show_min_text = ($lookup_id ? "&lookup_id=$lookup_id" : "");

                $show_min_text .= (isset($_GET['show_min']) ? '&show_min=1' : "");

                $notes->notes_heading = "<script>
                    function changeDivisionUser(pageName,divisionId)
                    {
                      
                        var userid = $('#srchCommentUser').val();
                       // alert(userid);
                       // alert(divisionId);
                        var url = '" . $main_folder . "Page/'+pageName+'?division_id='+divisionId+'&userid='+userid+'" . $show_min_text . "';
//                        //var url = " . $this->f3->get('main_folder') . "+pageName'/'.'?division_id='+divisionId+'&userid='+userid;
                          //  alert(url);
                        document.location = url;
                    }
                </script>";
                $notes->pfix = $pfix;

                $siteUserDivisions = $this->division_site_user($site_id, $notes->division_id, 'Page/' . ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog'), 1, $lookup_id);

                if (count($siteUserDivisions) == 2) {
                    $notes->division_id = array_pop($siteUserDivisions);
                }

                if ($pfix != 'site_notes') {
                    //die;
                    $stickyNotesContent = $this->getStickyNotes($site_id);

                    $totalStricky = count($stickyNotesContent);

                    $stickyNotesStr = $this->getStickyNotesContent($site_id);

                    //prd($stickyNotesContent);
//                  //  echo $_SESSION['user_id'];
                    //die;

                    $userRoleName = $this->userRoleNameByUid($_SESSION['user_id']);
                    if ($userRoleName != "EMPLOYEE" && $userRoleName != "PROVIDER" && $userRoleName != "VISITOR") {
                        $notesDis = " <input id=\"dialogStickyBtn\" type=\"button\" onclick=\"openStickyNotes()\" style=\"padding:3px;color:yellow;font-size:49!important\" value=\"StickyNotes(" . $totalStricky . ")\" /> ";
                    }


                    $notesDis = " <input id=\"dialogStickyBtn\" type=\"button\" onclick=\"openStickyNotes()\" style=\"padding:3px;color:yellow;font-size:49!important\" value=\"StickyNotes(" . $totalStricky . ")\" /> "
                            . "<div id=\"dialogStickyNotes\" style=\"display:none\">";

                    if ($userRoleName != "EMPLOYEE" && $userRoleName != "PROVIDER" && $userRoleName != "VISITOR") {
                        $notesDis .= "<textarea id=\"stickyNotes\" rows=\"1\" cols=\"130\"></textarea>"
                                . "<input type=\"button\" onclick=\"saveStickyNotes('" . $site_id . "')\" value=\" Save Sticky Notes \" /><div style='clear:both'></div>";
                    }


                    $notesDis .= "<div id='strickyNotesDis'>$stickyNotesStr</div>"
                            . "</div>";
                }

                // echo $totalStricky;
                // die;

                if ($totalStricky > 0 && !$_SESSION['sticky_timestamp']) {
//                     echo $_SESSION['sticky_timestamp'];
//                    
                    $_SESSION['sticky_timestamp'] = 1;
//                    die;
                    // $_SESSION['sticky_timestamp_'.$site_id] = time();
                    $str .= '   
  <script>
    $( function() {
    $( "#dialogStickyNotes" ).dialog({
  width : 1020,height : 850,title:"StickyNotes"
});         
  } );       
       </script>';
                }


                $str .= '   
  <script>

      function openStickyNotes(){
         $("#dialogStickyNotes").dialog({
  width : 1020,height : 850,title:"StickyNotes"
});      }   

        function saveStickyNotes(siteId) {
           var notesText = $("#stickyNotes").val();
           if(notesText.trim() == ""){
                alert("Please enter notes");
            }else{          
                $.ajax({
                   type:"post",
                       url:"' . $this->f3->get('main_folder') . 'Page/OccurrenceLog",
                       data:{siteId:siteId,notesText: notesText,action:"addStrickyNotes" } ,
                       success:function(msg) {
                       $("#strickyNotesDis").html(msg);
                       $("#stickyNotes").val("");
                         //alert("Notes has been added");                 
                       }
                 });  
            }

          }
          
         function deleteStickyNotes(stickyNotesId,siteId) {
           var notesText = $("#stickyNotes").val();
           if(confirm("Are you sure. You want to delete")){                        
                $.ajax({
                   type:"post",
                       url:"' . $this->f3->get('main_folder') . 'Page/OccurrenceLog",
                       data:{siteId:siteId,stickyNotesId:stickyNotesId,action:"deleteStrickyNotes" } ,
                       success:function(msg) {
                       $("#strickyNotesDis").html(msg);
                       //$("#stickyNotes").val("");
                         //alert("Notes has been added");                 
                       }
                 });  
            }

          }
          </script>';

                $notes->notes_heading .= '<div class="cl"></div><h3 class="fl">' . ($pfix == 'site_notes' ? '<span style="font-size:46px;color:blue">Whiteboard</span>' : $notesDis . '</br><span style="font-size:46px;color:purple">Activity Log</span>') . ' for ' . $site_name . ($date_by_show ? " ($date_by_show)" : "") . '</h3>
        <div class="fl" style="margin-left: 12px; margin-right: 12px;padding-bottom:10px;">' . $this->division_nav_site_user($site_id, $notes->division_id, 'Page/' . ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog'), 1, $lookup_id) . '</div>
        <div class="fl" style="margin-left: 12px; margin-right: 12px;padding-bottom:10px;">  ' . $selectNotesHeader . '  </div>
    
        <div class="fr">
         
        <a class="list_a" target="_blank" href="' . $main_folder . 'Page/' . ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog') . '?pdf=1&days=-1&lookup_id=' . ($lookup_id ? $lookup_id : $_SESSION['site_id']) . '">PDF (Yesterday)</a>
        <a class="list_a" target="_blank" href="' . $main_folder . 'Page/' . ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog') . '?pdf=1&days=0&lookup_id=' . ($lookup_id ? $lookup_id : $_SESSION['site_id']) . '">PDF (Today)</a>
        ' . ($this->f3->get('is_staff') ? '<nobr><a class="list_a" href="' . $main_folder . 'MyAccount?select_site=Page/GetSiteNotes' . $show_min . '">Change Site &gt;&gt;</a>' : '')
                        . ($show_min ? '<a target="_top" class="list_a" href="' . $main_folder . 'Page/' . ($pfix == 'site_notes' ? 'GetSiteNotes' : 'OccurrenceLog') . '">Expand</a>' : '') .
                        '<a class="list_a" href="' . $main_folder . 'Page/' . ($pfix != 'site_notes' ? 'GetSiteNotes?show_min=' . $show_min . '">Whiteboard' : 'OccurrenceLog?show_min=' . $show_min . '">Activity Log') . '</a>
        </nobr></div><div class="cl"></div>';
                //$notes->notes_heading .= ($pfix == "site_notes" ? '<div class="inline_message"><b>NEW - Occurrence Log </b><a class="list_a" href="'.$main_folder.'Page/OccurrenceLog'.$show_min.'">Click Here for Occurrence Log</a></div>' : "");
                $str .= $notes->display_add();
                $str .= $notes->display_notes();
                $txtNotes = (isset($_POST['txtNotes']) ? $_POST['txtNotes'] : null);
                $hdnAddID = (isset($_POST['hdnAddID']) ? $_POST['hdnAddID'] : null);

                if ($txtNotes && $pfix == 'site_notes') {

                    $this->notification->sendWhiteBoardNotification($site_id, $hdnDivisionID, $txtNotes);

                    //$str .= nl2br("$email $email2 $email3" . $mail_msg . "<br /><br />");
                }



                $txtMsgNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);

                $hdnSearchNotes = (isset($_POST['hdnSearchNotes']) ? $_POST['hdnSearchNotes'] : null);
                if ($txtMsgNotes && !$hdnSearchNotes) {
                    $msgForUser = $txtMsgNotes;
                }
                $hdnAddID = (isset($_POST['hdnAddID']) ? $_POST['hdnAddID'] : null);
                if ($hdnAddID) {
                    $msgForUser = $this->dbi->real_escape_string($_POST['txtComment']);
                }
                if ($msgForUser != "" && ($notes->srchUserId && $notes->srchUserId != "ALL")) {
                    $this->notification->sendWhiteBoardUserNotification($notes->srchUserId, $site_id, $hdnDivisionID, $msgForUser);
                }
            } else {
                $str .= '<h3>You are not yet assigned to a site<br /><br /></h3><a class="list_a" href="' . $main_folder . 'MyAccount?select_site=Page/SiteNotes' . ($_GET['show_min'] ? "&show_min=$show_min" : "") . '">Select a Site &gt;&gt;</a>';
            }
        }

        return $str;
    }

    function Associations() {
        $main_folder = $this->f3->get('main_folder');

        /* $str = '


          <iframe style="border: none; width: 100%; height: 1000px;" src="https://alliedmng.sharepoint.com/sites/AlliedIntegratedManagement/Shared%20Documents/Forms/AllItems.aspx?RootFolder=%2fsites%2fAlliedIntegratedManagement%2fShared%20Documents%2f2%2e%20Allied%20Facilities%20Management%2fHuman%20Resources%2f1%2e%20Employee%20Details%20%26%20Compliance%2f1%2e%20Current%20Employees&FolderCTID=0x012000C7670CBA4B398044B67A7228DCEF9532"></iframe>

          '; */


        $desc_id = (isset($_GET["desc_id"]) ? $_GET["desc_id"] : null);
        $added_by_id = $_SESSION['user_id'];

        $str .= '
    <script>
    function show_all_children() {
      document.getElementById(\'txtFilterChildren\').value = "%";
      document.frmEdit.submit();
    }
    function show_all_parents() {
      document.getElementById(\'txtFilterParents\').value = "%";
      document.frmEdit.submit();
    }
    </script>';
        $lookup_id = $_GET['lookup_id'];
        $view_assoc = $_GET['view_assoc'];
        if ($view_assoc) {
            $this->list_obj->sql = "
      select association_types.id as idin, '<div align=\"right\">Association Name: </div>' as `Parent`, association_types.name as `Child` from association_types
      where association_types.id = $lookup_id
      union
      select association_types.id as idin, concat(users.name, ' ', users.surname) as `Parent`,
      concat(users2.name, ' ', users2.surname) as `Child`
      from associations
      left join users on users.id = associations.parent_user_id
      left join users2 on users2.id = associations.child_user_id
      left join association_types on association_types.id = associations.association_type_id
      where association_types.id = $lookup_id";
            //echo "<textarea>" . $this->list_obj->sql . '</textarea>';
            $str .= $this->list_obj->draw_list();
        } else {
            $parent_id = $_GET['parent_id'];
            $child_id = $_GET['child_id'];
            $del_id = $_GET['del_id'];
            $txtFilterChildren = $_POST['txtFilterChildren'];
            if (!$txtFilterChildren)
                $txtFilterChildren = $_REQUEST['txtFilterChildren'];
            $txtFilterParents = $_POST['txtFilterParents'];
            if (!$txtFilterParents)
                $txtFilterParents = $_REQUEST['txtFilterParents'];
            if ($txtFilterParents) {
                $parent_id = "";
            }
            if ($child_id && $parent_id && $del_id) {
                $sql = "delete from associations where id = $del_id;";
                $result = $this->dbi->query($sql);
            }
            if ($child_id && $parent_id && !$del_id) {
                $sql = "insert into associations (association_type_id, parent_user_id, child_user_id, added_by_id) values ($lookup_id, $parent_id, $child_id, $added_by_id);";
                $result = $this->dbi->query($sql);
            }

            $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);

            $sql = "select id, item_name from lookup_fields where id in (select parent_group_id from association_types where id = $lookup_id) union
             select id, item_name from lookup_fields where id in (select child_group_id from association_types where id = $lookup_id)";

//      echo $sql;
//      die;
            if ($result = $this->dbi->query($sql)) {
                $myrow = $result->fetch_assoc();
                $id = $myrow['id'];
                $group = $myrow['item_name'];
                $myrow = $result->fetch_assoc();
                $child_idin = $myrow['id'];
                $child_group = $myrow['item_name'];
            }
            if (!$show_min) {
                $pl = strtoupper(substr($group, 0, 1));
                $pl = ($pl == 'A' || $pl == 'E' || $pl == 'I' || $pl == 'O' || $pl == 'U' ? "n" : "");
                $str .= "<h3>$group &gt;&gt; $child_group Association.<br /><br />Find a$pl $group.<br /></h3>";
                if ($parent_id) {
                    $p_xtra = "and users.id = $parent_id";
                } else {
                    $parent_search = ($txtFilterParents ? " and (users.name LIKE '%$txtFilterParents%' or CONCAT(users.name, ' ', users.surname) LIKE '%$txtFilterParents%' or users.email LIKE '%$txtFilterParents%' or users.employee_id LIKE '%$txtFilterParents%' or users.client_id LIKE '%$txtFilterParents%' or users.contractor_id LIKE '%$txtFilterParents%' or users.supplier_id LIKE '%$txtFilterParents%' or users.student_id LIKE '%$txtFilterParents%')" : " and 1 = 2");
                }
                $str .= '<p><input type="text" placeholder="Search for a ' . ($group ? $group : $parent_group) . '" name="txtFilterParents"  id="txtFilterParents" style="height: 27px;"><input type="submit" value="Search"><input type="button" value="Show All" onClick="show_all_parents()"><br /><br />';
                if ($txtFilterParents == '%')
                    $txtFilterParents = "ALL";
                if ($txtFilterParents)
                    $str .= " -- Filtered By: $txtFilterParents<br /><br />";
                $sql = "select users.id as idin, users.employee_id, users.client_id, users.contractor_id, users.supplier_id, users.student_id, CONCAT(users.name, ' ', users.surname) as `name`, states.item_name as `state` from users
                left join states on states.id = users.state
                inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $id and lookup_answers.table_assoc = 'users'
                $parent_search $p_xtra;";
                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $user_id = $myrow['idin'];
                        $show_id = "";
                        if ($myrow['employee_id'])
                            $show_id .= $myrow['employee_id'] . " -- ";
                        if ($myrow['client_id'])
                            $show_id .= $myrow['client_id'] . " -- ";
                        if ($myrow['contractor_id'])
                            $show_id .= $myrow['contractor_id'] . " -- ";
                        if ($myrow['supplier_id'])
                            $show_id .= $myrow['supplier_id'] . " -- ";
                        if ($myrow['student_id'])
                            $show_id .= $myrow['student_id'] . " -- ";
                        $name = $myrow['name'] . " - " . $myrow['state'];
                        $state = $myrow['state'];
                        if ($parent_id == $user_id) {
                            $parent_user = $name;
                            $str .= "<nobr><b>[$show_id $name]</b>    </nobr> ";
                        } else {
                            $str .= "<nobr>[<a href=\"{$main_folder}Page/Associations?lookup_id=$lookup_id&parent_id=$user_id&view_assoc=$view_assoc" . ($_GET['show_min'] ? "&show_min=1" : "") . "\">$show_id $name</a>]    </nobr> ";
                        }
                    }
                }
            } else {
                $title = $this->get_sql_result("select name as `result` from association_types where id = $lookup_id");
                $title = str_replace("_", " ", $title);
                $title = ucwords($title);
                $str .= "<h3>$title</h3>";
            }
            if ($parent_id) {
                if (!$view_assoc) {
                    $str .= "<table><tr>";
                    $str .= '<td width="50%" valign="top">';
                    $site_search = ($txtFilterChildren ? " and (users.name LIKE '%$txtFilterChildren%' or CONCAT(users.name, ' ', users.surname) LIKE '%$txtFilterChildren%' or users.email LIKE '%$txtFilterChildren%' or users.employee_id LIKE '%$txtFilterChildren%' or users.client_id LIKE '%$txtFilterChildren%' or users.contractor_id LIKE '%$txtFilterChildren%' or users.supplier_id LIKE '%$txtFilterChildren%' or users.student_id LIKE '%$txtFilterChildren%')" : "and 1 = 2");
                    $str .= "<hr /><h5>Select a $child_group to add to $group $parent_user</h5>";
                    $str .= '<p><nobr>Find ' . $child_group . ': <input type="text" name="txtFilterChildren"  id="txtFilterChildren" style="height: 27px;"><input type="submit" value="Go"><input type="button" value="All" onClick="show_all_children()"></nobr>';
                    if ($txtFilterChildren == '%')
                        $txtFilterChildren = "ALL";
                    if ($txtFilterChildren)
                        $str .= " -- Filtered By: $txtFilterChildren";
                    $str .= '</p>';
                    $sql = "select users.id as idin, users.employee_id, users.client_id, users.contractor_id, users.supplier_id, users.student_id, CONCAT(users.name, ' ', users.surname) as `name`, states.item_name as `state` from users
                  left join states on states.id = users.state
                  inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $child_idin and lookup_answers.table_assoc = 'users'
                    where users.id not in (select users.id from users 
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $child_idin and lookup_answers.table_assoc = 'users'
                    inner join associations on associations.child_user_id = users.id and associations.parent_user_id = $parent_id  and associations.association_type_id = $lookup_id)
                    and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                  $site_search
                  ";
                    $result = $this->dbi->query($sql);
                    if ($result) {
                        while ($myrow = $result->fetch_assoc()) {
                            $user_id = $myrow['idin'];
                            $show_id = "";
                            if ($myrow['employee_id'])
                                $show_id .= $myrow['employee_id'] . " -- ";
                            if ($myrow['client_id'])
                                $show_id .= $myrow['client_id'] . " -- ";
                            if ($myrow['contractor_id'])
                                $show_id .= $myrow['contractor_id'] . " -- ";
                            if ($myrow['supplier_id'])
                                $show_id .= $myrow['supplier_id'] . " -- ";
                            if ($myrow['student_id'])
                                $show_id .= $myrow['student_id'] . " -- ";
                            $name = $myrow['name'];
                            $state = $myrow['state'];
                            $str .= "[<a href=\"{$main_folder}Page/Associations?lookup_id=$lookup_id&parent_id=$parent_id&child_id=$user_id&txtFilterChildren=$txtFilterChildren&txtFilterParents=$txtFilterParents" . ($_GET['show_min'] ? "&show_min=$show_min" : "") . "\">$show_id $name - $state</a>]<br /> ";
                        }
                    }
                    $str .= "</td>";
                    $str .= '<td width="50%" valign="top">';
//          $str .= "<hr /><h3>Select a $child_group to Remove Association from  $group $parent_user</h3>";
                    $str .= "<hr /><h5>Associated $child_group" . "s for this $group $parent_user</h5>";
                } else {
                    $str .= "<hr /><h5>$child_group" . "s belonging to $group $parent_user</h5>";
                }
                $sql = "select users.id as idin, associations.id as `assoc_id`, users.employee_id, users.client_id, users.contractor_id, users.supplier_id, users.student_id, CONCAT(users.name, ' ', users.surname) as `name`, states.item_name as `state`, CONCAT(users2.name, ' ', users2.surname) as `added_by` from users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = $child_idin and lookup_answers.table_assoc = 'users'
        inner join associations on associations.child_user_id = users.id and associations.parent_user_id = $parent_id and associations.association_type_id = $lookup_id
        left join states on states.id = users.state
        left join users2 on users2.id = associations.added_by_id
        where users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
        group by users.id";

                $result = $this->dbi->query($sql);
                $x = 0;
                if ($result) {
                    while ($myrow = $result->fetch_assoc()) {
                        $x++;
                        $user_id = $myrow['idin'];
                        $assoc_id = $myrow['assoc_id'];
                        $employee_id = $myrow['employee_id'];
                        $state = $myrow['state'];
                        $show_id = "";
                        if ($myrow['employee_id'])
                            $show_id .= $myrow['employee_id'] . " - ";
                        if ($myrow['client_id'])
                            $show_id .= $myrow['client_id'] . " - ";
                        if ($myrow['contractor_id'])
                            $show_id .= $myrow['contractor_id'] . " - ";
                        if ($myrow['supplier_id'])
                            $show_id .= $myrow['supplier_id'] . " - ";
                        if ($myrow['student_id'])
                            $show_id .= $myrow['student_id'] . " - ";
                        $added_by = ($myrow['added_by'] ? 'uk-tooltip="title: Added By: ' . $myrow['added_by'] . '"' : "");
                        $name = $myrow['name'];
                        if ($view_assoc) {
                            $str .= "<nobr>$x. [$show_id $name] <br /></nobr> ";
                        } else {
                            $str .= "<span $added_by>$x. <a class=\"list_a\" href=\"{$main_folder}Page/Associations?lookup_id=$lookup_id" . ($_GET['show_min'] ? "&show_min=$show_min" : "") . "&parent_id=$parent_id&child_id=$user_id&del_id=$assoc_id&txtFilterChildren=$txtFilterChildren&txtFilterParents=$txtFilterParents&view_assoc=$view_assoc\">X</a> $show_id $name</span><br />";
                        }
                    }
                }
                if (!$view_assoc)
                    $str .= "</td></tr></table>";
            }
        }
        return $str;
    }

    function ComplianceCheckNotes() {

        $main_folder = $this->f3->get('main_folder');
        $compliance_check_id = (isset($_GET['compliance_check_id']) ? $_GET['compliance_check_id'] : null);
        $notes = new notes;
        $notes->id = $compliance_check_id;
        $notes->dbi = $this->dbi;
        $notes->show_delete = 1;
        $notes->table = "compliance_check_notes";
        $notes->table_id_name = "compliance_check_id";
        $sql = "select compliance.id as `idin`, compliance.title from compliance_checks
                      left join compliance on compliance.id = compliance_checks.compliance_id
                      where compliance_checks.id = $compliance_check_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $compliance_id = $myrow['idin'];
            $compliance_title = $myrow['title'];
        }
        $sql = "select states.item_name as `state` from users left join states on states.id = users.state where users.id = " . $_SESSION['user_id'];
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            date_default_timezone_set(($myrow['state'] == "TAMIL NADU" ? 'Asia/Kolkata' : 'Australia/' . $myrow['state']));
            $date_time = date('Y-m-d H:i:s');
            $current_date = date("Y-m-d");
            $yesterday = date("Y-m-d", strtotime("-1 days"));
        }
        $notes->add_title = '<div class="cl"></div><h3>Notes for ' . $compliance_title . '</h3>';
        $view_details = new data_list;

        $view_details->dbi = $this->dbi;
        $view_details->sql = "
          select 
          compliance_checks.id as idin, compliance_checks.id as `ID`, CONCAT(compliance_checks.percent_complete, '%') as `% Done`, $title_str lookup_fields1.item_name as `Status`, $title_xtra
          CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`,
          CONCAT(users.employee_id, ' ', users.client_id, ' ', users.name, ' ', users.surname) as `Assessor`,
          CONCAT(if(users2.employee_id != '', users2.employee_id, ''), ' ', if(users2.client_id != '', users2.client_id, ''), ' ', users2.name, ' ', users2.surname) as `Subject`, $stat_str
          CONCAT('<a uk-tooltip=\"title: View Online\" class=\"list_a\" href=\"{$main_folder}Compliance?report_view_id=', compliance_checks.id, '\">View</a>', '<a uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"{$main_folder}CompliancePDF/', compliance_checks.id, '\">PDF</a>', '<a uk-tooltip=\"title: Upload/View Attachemts\" class=\"list_a\" href=\"{$main_folder}Resources?current_dir=compliance&current_subdir=', compliance_checks.id, '\">Attachments...</a>', '<a uk-tooltip=\"title: Add Notes\" class=\"list_a\" href=\"{$main_folder}ComplianceFollowup?compliance_check_id=', compliance_checks.id, '\">Resolve Issues...</a>') as `***`,
          if((lookup_fields1.item_name = 'Pending' and users.id = " . $_SESSION['user_id'] . ") or (lookup_fields1.item_name = 'Pending' and compliance.allow_sharing = 1),
          CONCAT('<a uk-tooltip=\"title: Edit Checklist\" class=\"list_a\" href=\"{$main_folder}Compliance?report_edit_id=', compliance_checks.id, '\">Edit</a>'),
          '<span class=\"list_a\"><strike>Edit</strike></span>')
          as `**`
          FROM compliance_checks
          left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
          left join compliance on compliance.id = compliance_checks.compliance_id
          left join users on users.id = compliance_checks.assessor_id
          left join users2 on users2.id = compliance_checks.subject_id
          left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
          where compliance_checks.id = $compliance_check_id
          group by compliance_checks.id
          order by check_date_time desc
      ";
        $notes->add_title .= $view_details->draw_list() . '<br />';
        $str .= $notes->display_add();
        $str .= $notes->display_notes();
        return $str;
    }

    function LoginAs() {
        $main_folder = $this->f3->get('main_folder');
        if ($_SESSION['user_id'] == 2) {
            $search_str = (isset($_GET['shift_search']) ? $_GET['shift_search'] : null);
            $applicable_to_id = (isset($_GET['applicable_to_id']) ? $_GET['applicable_to_id'] : null);
            $search = $search_str;
            if ($applicable_to_id) {
                $user = new UserController;
                $user->authenticate($applicable_to_id);
            }

            $str .= '
        <input type="hidden" name="apply_course" value="" />
        </form>
        <form method="get" name="frmFollowSearch">
        <input type="hidden" name="cdid" value="' . $cdid . '" />
        <div class="fl"><h3>Find Entities to Setup</h3>
        <input maxlength="50" name="shift_search" id="search" type="text" class="search_box" value="' . $search_str . '" /><input type="submit" onClick="perform_search(\'' . $search_page . '\')" name="cmdFollowSearch" value="Search" class="search_button" /> ' . $search_msg . '
        </div>
        <div class="cl"></div>
        </form>
      ';
            if ($search) {
                $search = "
          where (users.name LIKE '%$search%'
          or users.surname LIKE '%$search%'
          or users.email LIKE '%$search%'
          or users.employee_id LIKE '%$search%'
          or users.supplier_id LIKE '%$search%'
          or users.client_id LIKE '%$search%'
          or users.student_id LIKE '%$search%'
          or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
          and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
        ";
            }
            if ($search) {
                $sql = "
          SELECT users.id as `idin`, employee_id, student_id, client_id, supplier_id, CONCAT(users.name, ' ', users.surname) as `user_name`,
                 CONCAT('<a href=\"mailto: ', users.email, '\">', users.email, '</a>') as `email`, if(users.pw != '', 'Yes', 'No') as `has_pw`,
                 states.item_name as `state`
                 FROM users
                 left join states on states.id = users.state
                 left join user_status on user_status.id = users.user_status_id
                 $search
          ";
                $result = $this->dbi->query($sql);
                $show_first = 1;
                while ($myrow = $result->fetch_assoc()) {
                    if ($show_first) {
                        $str .= '<h3>Search Results</h3><table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small" >
                  <tr><th align="left"><nobr>Full Name<nobr></th><th align="left">
                  <nobr>Email<nobr></th><th align="left"><nobr>State<nobr></th><th align="left"><nobr>Has PW<nobr></th><th align="left">**</th></tr>';
                    }
                    $idin = $myrow['idin'];
                    $user_name = $myrow['user_name'];
                    $email = $myrow['email'];
                    $state = $myrow['state'];
                    $has_pw = $myrow['has_pw'];
                    $setup_str = "<td valign=\"top\"><a class=\"list_a\" href=\"{$main_folder}Page/LoginAs?applicable_to_id=$idin\">Login As</a></a></td>";
                    $str .= '<tr><td valign="top">' . $user_name . '</td><td valign="top">' . $email . '</td><td valign="top">' . $state . '</td><td valign="top">' . $has_pw . '</td>' . $setup_str . '</tr>';
                    $show_first = 0;
                }
                $str .= "</table>";
            }
        }
        return $str;
    }

    function PromoteDemote() {
        $main_folder = $this->f3->get('main_folder');
        $user_id = (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null);
        $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
        $txtFind = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
        if ($user_id && $action) {
            $qry['promote'] = "update users set user_level_id = 600 where id = $user_id; insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($user_id, 111, 'users'); insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($user_id, 534, 'users');";
            $qry['promote_s2'] = "update users set user_level_id = 600 where id = $user_id; insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($user_id, 2039, 'users'); insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($user_id, 534, 'users');";
            $qry['demote'] = "update users set user_level_id = 300 where id = $user_id; delete from lookup_answers where foreign_id = $user_id and table_assoc = 'users' and lookup_field_id = 111; delete from lookup_answers where foreign_id = $user_id and table_assoc = 'users' and lookup_field_id = 534;";
            $qry['activate'] = "update users set user_status_id = 30 where id = $user_id;";
            $qry['deactivate'] = "update users set user_status_id = 40 where id = $user_id;";
            $qry['remove_kpi'] = "delete FROM `compliance_auditors` where id in (select compliance_auditor_id from compliance_auditors_subjects where user_id = $user_id);delete from compliance_auditors where user_id = $user_id;delete from compliance_auditors_subjects where user_id = $user_id;";
            $sqls = explode(";", $qry[$action]);
            for ($x = 0; $x < count($sqls); $x++) {
                if ($sqls[$x])
                    $this->dbi->query($sqls[$x]);
            }
            $msg['promote'] = "User Promoted to S1";
            $msg['promote_s2'] = "User Promoted to S2";
            $msg['demote'] = "User Demoted from S1 to Staff Member";
            $msg['activate'] = "User Activated";
            $msg['deactivate'] = "User Deactivated";
            $msg['remove_kpi'] = "User Removed from KPI's";
            $str .= $this->message($msg[$action], 2000);
        }
        $str .= '<div class="fl">
    <div class="form-wrapper" style="width: 725px;">
    <div class="form-header">Promote/Demote/Activate/Deactivate Staff -- S1 &lt;&gt; Staff Member</div>
    <div class="form-content">';
        $str .= ($user_id ? '<a class="list_a" href="PromoteDemote">Start Again</a>' : '<p><input placeholder="Enter Staff Member\'s Name" maxlength="70" name="txtFind" id="search" type="text" class="search_box" value="' . $_REQUEST['txtFind'] . '" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg . '</p>'
                );
        $str .= '<div class="cl"></div></div>';
        if ($txtFind) {
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
              select distinct(users.id) as idin, users.employee_id as `Employee ID`, CONCAT(users.name, ' ', users.surname) as `Name`, users.username as `Username`, states.item_name as `State`,
              phone as `Phone`, users.email as `Email`, user_level.item_name as `User Level`, user_status.item_name as `Status`,
              if(user_level.ID < 600, CONCAT('<a style=\"color: green !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=promote&txtFind=$txtFind&user_id=', users.id, '\">Promote to S1</a><a style=\"color: green !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=promote_s2&txtFind=$txtFind&user_id=', users.id, '\">Promote to S2</a>'), CONCAT('<a style=\"color: red !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=demote&txtFind=$txtFind&user_id=', users.id, '\">Demote</a>')) as `**`,
              if(user_status.item_name = 'ACTIVE', CONCAT('<a style=\"color: red !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=deactivate&txtFind=$txtFind&user_id=', users.id, '\">DEACTIVATE</a>'), CONCAT('<a style=\"color: green !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=activate&txtFind=$txtFind&user_id=', users.id, '\">ACTIVATE</a>')) as `***`,
              CONCAT('<a style=\"color: red !important;\" class=\"list_a\" href=\"{$main_folder}Page/PromoteDemote?action=remove_kpi&txtFind=$txtFind&user_id=', users.id, '\">Remove from KPI</a>') as `****`
              from users
              left join states on states.id = users.state
              left join user_level on user_level.id = users.user_level_id
              left join user_status on user_status.id = users.user_status_id
              inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
              inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'HR' or lookup_fields.value = 'PERSON'
              $search
              and users.user_level_id < " . $_SESSION['u_level'];

            $str .= "</div></div><div class=\"cl\"></div>" . $this->list_obj->draw_list();
        }
        $str .= "</div></div>";
        $str .= "<div class=\"cl\"></div>";
        return $str;
    }

    function status_changer($table, $selected_item = 0) {
        $sql = "select id, item_name, colour from $table order by id";
        $result = $this->dbi->query($sql);

        $str .= '
    <style>
    .status_item {
      display: inline-block;
      text-align: center;
      width: 85px; padding: 3px 10px 3px 10px; color: white;
    }
    </style>
    ';
        while ($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $item_name = $myrow['item_name'];
            $colour = $myrow['colour'];
            $str .= ($id == $selected_item ? "" : "<span class=\"status_item\" style=\"background-color: $colour;\">$item_name</span>");
        }

        return $str;
    }

    function Logs() {
        $main_folder = $this->f3->get('main_folder');

        /*
          $sql =  "select item_name, colour from task_status order by id";
          $result = $this->dbi->query($sql);
          while($myrow = $result->fetch_assoc()) {
          $item_name = $myrow['item_name'];
          $item_names[] = $item_name;
          $colour = $myrow['colour'];
          $colours[] = $colour;
          }

          $str .= $this->status_changer("task_status");

          $str .= '<table class="grid">
          <tr><th>Status</th><th>Task</th><th>Due Date</th><th>Completion Date</th><th>Change Status</th></tr>';

          $str .= "
          <tr><td style=\"color: white; background-color: {$colours[0]}\">{$item_names[0]}</td><td>Here is a description of a task. Here is some further info for the task. Here is even more info...</td><td>25/Mar/2018</td><td>&nbsp;</td><td>".$this->status_changer("task_status", 10)."</td></tr>
          <tr><td style=\"color: white; background-color: {$colours[1]}\">{$item_names[1]}</td><td>Here is a description of a task. Here is some further info for the task. Here is even more info...</td><td>25/Mar/2018</td><td>&nbsp;</td><td>".$this->status_changer("task_status", 20)."</td></tr>
          <tr><td style=\"color: white; background-color: {$colours[2]}\">{$item_names[2]}</td><td>Here is a description of a task. Here is some further info for the task. Here is even more info...</td><td>25/Mar/2018</td><td>&nbsp;</td><td>".$this->status_changer("task_status", 30)."</td></tr>
          <tr><td style=\"color: white; background-color: {$colours[3]}\">{$item_names[3]}</td><td>Here is a description of a task. Here is some further info for the task. Here is even more info...</td><td>25/Mar/2018</td><td>&nbsp;</td><td>".$this->status_changer("task_status", 40)."</td></tr>
          <tr><td style=\"color: white; background-color: {$colours[4]}\">{$item_names[4]}</td><td>Here is a description of a task. Here is some further info for the task. Here is even more info...</td><td>25/Mar/2018</td><td>&nbsp;</td><td>".$this->status_changer("task_status", 50)."</td></tr>

          ";
          /*
          $str = '<span style="margin-right: 1px; padding: 3px 25px 3px 25px; color: white; background-color: #E64A00;">PENDING</span>
          <span style="margin-right: 1px; padding: 3px 25px 3px 25px; color: white; background-color: #0000A0;">IN PROGRESS</span>
          <span style="margin-right: 1px; padding: 3px 25px 3px 25px; color: white; background-color: #444444;">STUCK</span>
          <span style="margin-right: 1px; padding: 3px 25px 3px 25px; color: white; background-color: #008C00;">COMPLETED</span>
          <span style="margin-right: 1px; padding: 3px 25px 3px 25px; color: white; background-color: #8C0000;">CANCELLED</span>
          '; */


//return $str;

        $sql = "
          select 'Unique Users' as `Statistic`, count(distinct user_id) as `Num Items`
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Successful Logins' as `&nbsp;`, count(id)
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Failed Attempts', count(id)
          FROM log_login
          where is_successful = 0 $filter_string
          UNION
          select 'Total Logins', count(id)
          FROM log_login
          where 1 $filter_string
      ";

        $nav = new navbar;
        $str .= $nav->draw_navbar(2016);
        $filter_string = $nav->get_filter("attempt_date_time");
        $filter_string2 = $nav->get_filter("date");
        $this->list_obj->title = "Login Log";
        $this->list_obj->sql = "
          select 'Unique Users' as `Statistic`, count(distinct user_id) as `Num Items`
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Successful Logins' as `&nbsp;`, count(id)
          FROM log_login
          where is_successful = 1 $filter_string
          UNION
          select 'Failed Attempts', count(id)
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
        $str .= "<br /><br />" . $this->list_obj->draw_list();
        $this->list_obj->title = "Downloads Log";
        $this->list_obj->sql = "
          select downloads.date as `Date`, CONCAT(users.name, ' ', users.surname) as `User`, downloads.file_name as `File`
          FROM downloads
          left join users on users.id = downloads.downloaded_by
          where 1 $filter_string2
          order by downloads.date desc
      ";
        $str .= "<br /><br />" . $this->list_obj->draw_list();
        return $str;
    }

    function Timesheets() {
        $main_folder = $this->f3->get('main_folder');
        $str .= '
    <script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
    <style>
    .date_item {
      display: inline-block;
      font-size: 14px;
      padding: 12px 14px;
      font-weight: normal !important;
      border-style: solid;
      border-color: #FFFFFF #CCCCCC #DDDDDD #FFFFFF;
      border-width: 1px 1px 1px 1px;
      margin-right: 3px;
      border-radius: 0px 10px 0px 0px;
      -moz-border-radius: 0px 10px 0px 0px;
      -webkit-border-radius: 0px 10px 0px 0px;
    }
    .date_selected {
      background-color: #DDDDDD;
      border-color: #BBBBBB #FFFFFF #DDDDDD #DDDDDD;
      margin-left: 1px;
    }
    .date_area {
      border-style: solid;
      border-color: #FFFFFF;
      border-width: 0px 1px 1px 1px;
      background-color: #DDDDDD;
      padding: 15px;
    }
    .date_item:hover {
      background-color: #CCCCCC;
      text-decoration: none !important;
    }
    </style>
    <script>
    function get_time_diff(now, then) { 
      var ms = moment(now,"DD/MM/YYYY HH:mm:ss").diff(moment(then,"DD/MM/YYYY HH:mm:ss"));
      var d = moment.duration(ms);
      var num_days = Math.floor(d.asDays());
      num_hours = Math.floor(d.asHours());
      if(num_days) num_hours = num_hours % 24;
      var num_minutes = moment.utc(ms).format("m");
      var s
      if(Math.floor(num_days) || Math.floor(num_hours) || Math.floor(num_minutes)) {
        if(Math.floor(num_days) < 0 || Math.floor(num_hours) < 0 || Math.floor(num_minutes) < 0) {
          s = 0;
        } else {
          s = (num_days ? num_days + " Day" + (num_days != 1 ? "s" : "") + ", " : "")  + (num_hours ? num_hours + " Hour" + (num_hours != 1 ? "s" : "") : "") + (num_minutes != 0 ? (num_hours ? ", " : "") + num_minutes + " Minute" + (num_minutes != 1 ? "s" : "") : "");
        }
      } else {
        s = 0;
      }
      return s;
    }
    </script>';

        $date_in = ($_REQUEST['date_in'] ? $_REQUEST['date_in'] : date('Y-m-d', strtotime((date('Y-m-d') == date('Y-m-d', strtotime("this monday")) ? "this" : "last") . " monday")));
        $friendly_date = date('d-M-Y', strtotime($date_in));
        $end_date = date('Y-m-d', strtotime("+6 days", strtotime($date_in)));
        $month_in = date('m', strtotime($date_in));
        $year_in = date('Y', strtotime($date_in));
        $num_dates = 32;
        for ($x = 0; $x < $num_dates; $x++) {
            $inc = $x * 7;
            $mysql_dates[$x] = date('Y-m-d', strtotime(((date('Y-m-d') == date('Y-m-d', strtotime("this monday")) ? "this" : "last") . " monday -$inc days")));
            $friendly_dates[$x] = date('d-M-Y', strtotime(((date('Y-m-d') == date('Y-m-d', strtotime("this monday")) ? "this" : "last") . " monday -$inc days")));
            $date_bar .= '<a class="date_item ' . ($date_in == $mysql_dates[$x] ? 'date_selected' : '') . '" href="?date_in=' . $mysql_dates[$x] . '">' . $friendly_dates[$x] . '</a> ';
        }
        $str .= "<h2>Timesheets Week Starting: $friendly_date<br /></h2>";
        $str .= "<div class=\"reverse_wrap\">$date_bar</div><div class=\"dir_area\">";
        $sql = "select distinct(users.id), users.employee_id, users.name, users.surname from users where id in (select employee_id from time_checks where date_time >= '$date_in' and date_time <= '$end_date')";
        $cl = new clock;
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $uid = $myrow['id'];
            $employee_id = $myrow['employee_id'];
            $name = $myrow['name'];
            $surname = $myrow['surname'];
            $sql = "select date_time, weekday(date_time) as `day_of_week`, activity_type, date_format(date_time, '%W %d/%M') as `week_day`, latitude, longitude, concat(users.name, ' ', users.surname) as `site`
              from time_checks
              left join users on users.id = time_checks.site_id
              where time_checks.employee_id = $uid and weekofyear(date_time) = weekofyear('$date_in') and year(date_time) = year('$date_in') order by date_time";
            $result2 = $this->dbi->query($sql);
            $ccount = 0;
            $astr = "";
            $old_activity_type = "";
            while ($myrow2 = $result2->fetch_assoc()) {
                if (!$ccount) {
                    $str .= "<h5><a class=\"list_a\" href=\"{$main_folder}clock_editor.php?hdnReportFilter=1&selDateMonth=-1&selDateYear=-1&selStaff=$uid\">Edit Times</a> $employee_id $name $surname</h5>";
                    $str .= "<table class=\"grid\"><tr><th>Site</th><th>Start Date/Time</th><th>Finish Date/Time</th><th>Hours Worked</th></tr>";
                }
                $date_time = $myrow2['date_time'];
                $activity_type = $myrow2['activity_type'];
                $day_of_week = $myrow2['day_of_week'];
                $week_day = $myrow2['week_day'];
                $site = $myrow2['site'];
                $time = date('H:i', strtotime($date_time));
                if ($activity_type == 'ON') {
                    $old_date_time = $date_time;
                } else if ($activity_type == 'OFF') {
                    $str .= "<tr><td>$site</td><td>" . date('D d-M-Y H:i', strtotime($old_date_time)) . "</td><td>" . date('D d-M-Y H:i', strtotime($date_time)) . "</td><td>";
                    $str .= "\n<script> var time1 = moment('$date_time'); var time2 = moment('$old_date_time'); document.write(get_time_diff(time1, time2));</script>";
                    $str .= '</td></tr>';
                }
                $old_activity_type = $activity_type;
                $ccount++;
            }
            if ($activity_type == 'ON') {
                $str .= "<tr><td>$site</td><td>" . date('D d-M-Y H:i', strtotime($old_date_time)) . "</td><td><b>INCOMPLETE</b></td><td>";
                $str .= "\n<script> var time1 = moment('" . date('Y-m-d H:i') . "'); var time2 = moment('$old_date_time'); document.write(get_time_diff(time1, time2));</script>";
                $str .= '</td></tr>';
            }
            $str .= "</table>";
            if ($tstr)
                $str .= "<br /><h5>WEEKLY TOTAL: " . $cl->calc_hours(substr($tstr, 0, (strlen($tstr) - 1))) . "</h5><hr />";
            $tstr = "";
        }
        $str .= '</div>';
        return $str;
    }

    function ComplianceAccess() {
        $main_folder = $this->f3->get('main_folder');
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
        $my_kpi = (isset($_GET['my_kpi']) ? $_GET['my_kpi'] : null);
        $compliance_id = (isset($_GET['compliance_id']) ? $_GET['compliance_id'] : null);
        $user_id = (isset($_GET['user_id']) ? $_GET['user_id'] : null);
        $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
        $subjects_for_id = (isset($_GET['subjects_for_id']) ? $_GET['subjects_for_id'] : null);
        $cpy_subjects_to = (isset($_GET['cpy_subjects_to']) ? $_GET['cpy_subjects_to'] : null);
        $add_user_id = (isset($_GET['add_user_id']) ? $_GET['add_user_id'] : null);
        $remove_user_id = (isset($_GET['remove_user_id']) ? $_GET['remove_user_id'] : null);
        if (!$user_id)
            $user_id = 0;
        if ($add_user_id) {
            $sql = "insert into compliance_auditors" . ($subjects_for_id ? "_subjects (compliance_auditor_id, user_id) values ((select id from compliance_auditors where user_id = $subjects_for_id and compliance_id = $compliance_id), $add_user_id)" : " (compliance_id, user_id) values ($compliance_id, $add_user_id)");
        } else if ($remove_user_id) {
            $sql = "delete from compliance_auditors" . ($subjects_for_id ? "_subjects where compliance_auditor_id = (select id from compliance_auditors where user_id = $subjects_for_id and compliance_id = $compliance_id)" : " where compliance_id = $compliance_id") . " and user_id = $remove_user_id";
        }
        $result = ($sql ? $this->dbi->query($sql) : "");
        $txtFilterEntities = $_POST['txtFilterEntities'];
        if (!$txtFilterEntities)
            $txtFilterEntities = $_REQUEST['txtFilterEntities'];
        $sql = "select title from compliance where id = $compliance_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $compliance_title = $myrow['title'];
        }
        if ($subjects_for_id) {
            $sql = "select name, surname from users where id = $subjects_for_id";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $auditor_name = $myrow['name'] . ' ' . $myrow['surname'];
            }
            $str .= "<h3>Select " . ($my_kpi ? " your staff for" : " entities that $auditor_name can audit with") . " $compliance_title</h3>";
            $filter_xtra = " and id not in (select user_id from compliance_auditors_subjects where compliance_auditor_id = (select id from compliance_auditors where user_id = $subjects_for_id and compliance_id = $compliance_id)) ";
        } else {
            $filter_xtra = " and id not in (select user_id from compliance_auditors where compliance_id = $compliance_id) ";
            $str .= "<h3>Select auditors for $compliance_title</h3>";
        }
        $str .= '<p>Search: <input type="text" name="txtFilterEntities" value="' . $txtFilterEntities . '" style="height: 27px;"><input type="submit" value="Go">';
        $str .= '<div class="fl" style="width: 48%"><h3>Search Results</h3>';
        if ($txtFilterEntities) {
            $str .= " -- Searched By: $txtFilterEntities<br /><br />";
            $sql = "select id, name, surname, employee_id, client_id from users where (client_id like '%$txtFilterEntities%' or employee_id like '%$txtFilterEntities%' or name like '%$txtFilterEntities%'
              or surname like '%$txtFilterEntities%' or email like '%$txtFilterEntities%' or postcode like '%$txtFilterEntities%' or phone like '%$txtFilterEntities%') $filter_xtra and user_status_id = 30";
            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $user_id = $myrow['id'];
                $name = $myrow['name'];
                $client_id = $myrow['client_id'];
                $employee_id = $myrow['employee_id'];
                $surname = $myrow['surname'];
                $str .= "<a href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&compliance_id=$compliance_id&txtFilterEntities=$txtFilterEntities&my_kpi=$my_kpi&add_user_id=$user_id&subjects_for_id=$subjects_for_id\">$employee_id $client_id $name $surname</a><br />";
            }
        }
        $str .= '</div>';
        if ($subjects_for_id) {
            $sql = "select users.id as `user_id`, users.name, users.surname, users.employee_id, users.client_id from compliance_auditors_subjects
              left join users on users.id = compliance_auditors_subjects.user_id
              where compliance_auditors_subjects.compliance_auditor_id = (select id from compliance_auditors where user_id = $subjects_for_id and compliance_id = $compliance_id) and users.user_status_id = 30";
            $who_for = "Subjects";
        } else {
            $sql = "select users.id as `user_id`, users.name, users.surname, users.employee_id, users.client_id from compliance_auditors
              left join users on users.id = compliance_auditors.user_id
              where compliance_auditors.compliance_id = $compliance_id  and users.user_status_id = 30";
            $who_for = "Auditors";
        }
        $str .= '<div class="fl" style="width: 48%"><h3>' . $who_for . '</h3>';
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $user_id = $myrow['user_id'];
            $name = $myrow['name'];
            $client_id = $myrow['client_id'];
            $employee_id = $myrow['employee_id'];
            $surname = $myrow['surname'];
            $str .= "<a class=\"list_a\" href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&user_id=$user_id&compliance_id=$compliance_id&txtFilterEntities=$txtFilterEntities&my_kpi=$my_kpi&remove_user_id=$user_id&subjects_for_id=$subjects_for_id\">Remove</a> ";
            if (!$subjects_for_id) {
                $str .= "<a class=\"list_a\" href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&compliance_id=$compliance_id&subjects_for_id=$user_id\">Subjects</a> ";
            }
            $str .= "$employee_id $client_id $name $surname<br />";
            if ($cpy_subjects_to && $cpy_subjects_to != "ALL" && $subjects_for_id) {
                $sql = "insert into compliance_auditors_subjects (compliance_auditor_id, user_id) values ((select id from compliance_auditors where compliance_id = $compliance_id and user_id = $cpy_subjects_to), $user_id);";
                $this->dbi->query($sql);
            }
            if ($cpy_subjects_to == "ALL") {
                $sql = "select compliance_auditors.id as `compliance_auditor_id` from compliance_auditors
                left join users on users.id = compliance_auditors.user_id
                where compliance_auditors.compliance_id = $compliance_id and compliance_auditors.user_id != $subjects_for_id and users.user_status_id = 30";
                $result2 = $this->dbi->query($sql);
                while ($myrow2 = $result2->fetch_assoc()) {
                    $compliance_auditor_id = $myrow2['compliance_auditor_id'];
                    $sql = "insert ignore into compliance_auditors_subjects (compliance_auditor_id, user_id) values ('$compliance_auditor_id', '$user_id')";
                    $this->dbi->query($sql);
                }
            }
        }
        if (!$my_kpi) {
            if ($subjects_for_id) {
                $str .= "<h3>Copy Subjects to:</h3>";
                $sql = "select users.id as `user_id`, users.name, users.surname, users.employee_id, users.client_id, compliance_auditors.id as `compliance_auditor_id` from compliance_auditors
                left join users on users.id = compliance_auditors.user_id
                where compliance_auditors.compliance_id = $compliance_id and compliance_auditors.user_id != $subjects_for_id and users.user_status_id = 30";
                $result = $this->dbi->query($sql);
                while ($myrow = $result->fetch_assoc()) {
                    $user_id = $myrow['user_id'];
                    $name = $myrow['name'];
                    $client_id = $myrow['client_id'];
                    $employee_id = $myrow['employee_id'];
                    $surname = $myrow['surname'];
                    $str .= "<a class=\"list_a\" href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&user_id=$user_id&compliance_id=$compliance_id&txtFilterEntities=$txtFilterEntities&my_kpi=$my_kpi&cpy_subjects_to=$user_id&subjects_for_id=$subjects_for_id\">Copy</a> ";
                    $str .= "<a class=\"list_a\" href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&compliance_id=$compliance_id&subjects_for_id=$user_id\">Subjects</a> ";
                    $str .= "$employee_id $client_id $name $surname<br />";
                }
            }
            if ($cpy_subjects_to && $subjects_for_id) {
                $str .= $this->message("Subjects Copied...", 3000);
            }
            $str .= "<p><a class=\"list_a\" href=\"{$main_folder}Page/ComplianceAccess?show_min=$show_min&user_id=$user_id&compliance_id=$compliance_id&txtFilterEntities=$txtFilterEntities&my_kpi=$my_kpi&cpy_subjects_to=ALL&subjects_for_id=$subjects_for_id\">Copy Subjects to ALL</a></p>";
        }
        $str .= '</div>';
        $str .= '<div class="cl"></div>';
        return $str;
    }

    function UploadCompliance() {
        $main_folder = $this->f3->get('main_folder');
        require_once('app/controllers/PHPExcel.php');
        ini_set('memory_limit', '-1');
        $compliance_id = $_REQUEST['compliance_id'];
        $sql = "select title from compliance where id = $compliance_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $compliance_title = $myrow['title'];
        }
        $input_obj = new input_item;
        $str .= '<h3 class="fl">Upload Questions for ' . $compliance_title . '</h3>
          <div class="fr"><a class="download_link" href="DownloadFile?fl=' . $this->encrypt("excel_templates") . '&f=' . $this->encrypt("compliance.xlsx") . '">Download Template</a></div>
          <div class="cl"></div>
          ' . $page_content . '
          </form>
          <form method="post" action="UploadCompliance?show_min=' . $show_min . '" enctype="multipart/form-data">
          ' . $input_obj->chk("chkOverwrite", 'Overwrite ALL', 'checked', '1', '', '') . '
          <br /><br />
          <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
          <input type="hidden" name="compliance_id" value="' . $compliance_id . '" />
          <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
          </form>
    ';

        if ($_FILES["thefile"]["error"] > 0) {
            $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
        } else if ($_FILES["thefile"]["name"]) {
            move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]));
            $inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
            $excelReader = PHPExcel_IOFactory::createReaderForFile($inputFileName);
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            if ($_POST['chkOverwrite']) {
                $this->dbi->query("delete from compliance_questions where compliance_id = $compliance_id;");
                $max_sort = 0;
            } else {
                $result = $this->dbi->query("select max(sort_order) as max_sort from compliance_questions where compliance_id = $compliance_id;");
                if ($myrow = $result->fetch_assoc()) {
                    $max_sort = $myrow['max_sort'];
                }
            }
            $has_parent = ((strtoupper($objPHPExcel->getActiveSheet()->getCell("E1")->getValue()) == "PARENT") ? 1 : 0);
            $num_init = $has_parent + 4;
            $x = $max_sort;
            $choice_sql = "insert into compliance_question_choices (compliance_question_id, choice, choice_value, colour_scheme_id, additional_text_required, sort_order) (select ";
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                $sql = "insert into compliance_questions (compliance_id, question_title, answer, question_type, choices_per_row" . ($has_parent ? ", parent" : "") . ", sort_order) values ($compliance_id, ";
                $opt_count = 0;
                $opt_sort = 0;
                foreach ($rowData[0] as $k => $v) {
                    if ($k < $num_init) {
                        if ($k <= 1)
                            $v = mysqli_real_escape_string($this->dbi, $v);
                        $sql .= "'$v', ";
                        if (!$k && !$v)
                            break;
                    } else {
                        $opt_count++;
                        if (!($k % 4))
                            $v = mysqli_real_escape_string($this->dbi, $v);
                        if ($k == $num_init) {
                            $x += 10;
                            $sql .= "$x)";
                            $this->dbi->query($sql);
                            if ($v) {
                                $last_id = $this->dbi->insert_id;
                                $sql = $choice_sql . "'$last_id'";
                            } else {
                                $last_id = 0;
                            }
                        }
                        if ($last_id) {
                            if ($opt_count == 3) {
                                $colour = $v;
                                $sql .= ", id";
                            } else {
                                $sql .= ", '$v'";
                            }
                            if ($opt_count == 4) {
                                $opt_sort += 10;
                                $sql .= ", '$opt_sort' FROM `lookup_fields` where item_name = '$colour' and lookup_id = 85);";
                                $this->dbi->query($sql);
                                $sql = $choice_sql . "'$last_id'";
                                $opt_count = 0;
                            }
                        }
                    }
                }
                //echo "<h3>$sql</h3>";
            }
            if ($row > 2) {
                $str .= "<h3>Compliance Questions Added...</h3>";
            }
        }
        return $str;
    }

    function ProcessExcel() {
        $main_folder = $this->f3->get('main_folder');
        require_once('app/controllers/PHPExcel.php');

        $str .= '
    <h3>Convert Ageing Summary</h3>
    </form>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
    <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
    </form>
    ';
        if ($_FILES["thefile"]["error"] > 0) {
            $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
        } else if ($_FILES["thefile"]["name"]) {
            include('Classes/PHPExcel.php');
            $base_dir = "/home/Edge/downloads";
            $dl = "/excel_templates";
            move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]));
            $inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
            $excelReader = PHPExcel_IOFactory::createReaderForFile($inputFileName);
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
            $objPHPExcel2 = new PHPExcel();
            $date_format = 'd/m/Y';
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $objPHPExcel2->getActiveSheet()->SetCellValue("A3", "Company Id");
            $objPHPExcel2->getActiveSheet()->SetCellValue("B3", "Company Name");
            $objPHPExcel2->getActiveSheet()->SetCellValue("C3", "Cust Group");
            $objPHPExcel2->getActiveSheet()->SetCellValue("D3", "Invoice #");
            $objPHPExcel2->getActiveSheet()->SetCellValue("E3", "Transaction Date");
            $objPHPExcel2->getActiveSheet()->SetCellValue("F2", $objPHPExcel->getActiveSheet()->getCell("F12")->getValue());
            $objPHPExcel2->getActiveSheet()->SetCellValue("F3", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell("F13")->getValue())));
            $t = ord("G");
            $cols_from = array('H', 'J', 'M', 'O', 'Q', 'T');
            foreach ($cols_from as $col_from) {
                $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "1", $objPHPExcel->getActiveSheet()->getCell($col_from . "11")->getValue());
                if ($col_from == 'H') {
                    $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "2", $objPHPExcel->getActiveSheet()->getCell($col_from . "12")->getValue());
                    $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "3", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($col_from . "13")->getValue())));
                } else {
                    $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "2", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($col_from . "12")->getValue())));
                    $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "3", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($col_from . "13")->getValue())));
                }
                $t++;
            }
            $objPHPExcel2->getActiveSheet()->getStyle("A1:L3")->getFont()->setBold(true)->getColor()->setRGB('000066');
            $ranges = array('0-30', '31-90', '91-180', '181+');
            $c = ord("I");
            foreach ($ranges as $range)
                $objPHPExcel2->getActiveSheet()->SetCellValue(chr($c++) . "1", "$range Days");
            $cols_from = array('F', 'H', 'J', 'M', 'O', 'Q', 'T');
            $item_no = 3;
            for ($row = 10; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                $cell1 = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $row);
                $col1 = $cell1->getValue();
                if ($col1 != "" && $col1 != "Total" && $col1 != "Transaction date") {
                    if (PHPExcel_Shared_Date::isDateTime($cell1)) {
                        $item_no++;
                        $col1 = date($date_format, PHPExcel_Shared_Date::ExcelToPHP($col1));
                        $invoice_no = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(4, $row);
                        $invoice_no = substr($invoice_no, strpos($invoice_no, "/") + 1);
                        $objPHPExcel2->getActiveSheet()->SetCellValue("A" . $item_no, $company_id);
                        $objPHPExcel2->getActiveSheet()->SetCellValue("B" . $item_no, $company_name);
                        $objPHPExcel2->getActiveSheet()->SetCellValue("C" . $item_no, $customer_type);
                        $objPHPExcel2->getActiveSheet()->SetCellValue("D" . $item_no, $invoice_no);
                        $objPHPExcel2->getActiveSheet()->SetCellValue("E" . $item_no, $col1);
                        $t = ord("F");
                        foreach ($cols_from as $col_from) {
                            $objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "$item_no", $objPHPExcel->getActiveSheet()->getCell($col_from . "$row")->getValue());
                            $t++;
                        }
                    } else {
                        $company_id = $col1;
                        $company_name = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(4, $row);
                        $customer_type = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(5, $row);
                    }
                }
            }
            for ($i = 'A'; $i != 'L'; $i++) {
                $objPHPExcel2->getActiveSheet()->getColumnDimension($i)->setAutoSize(true);
            }
            $objPHPExcel2->getActiveSheet()->calculateColumnWidths();
            for ($i = 'A'; $i != 'L'; $i++) {
                $curr_width = $objPHPExcel2->getActiveSheet()->getColumnDimension($i)->getWidth();
                $objPHPExcel2->getActiveSheet()->getColumnDimension($i)->setAutoSize(false)->setWidth($curr_width * 0.86);
            }
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel2);
            $objWriter->save("$base_dir" . "$dl/" . 'customer_ageing.xlsx');
            $str .= '<h3>File Created...<br /><br /><a href="download_file.php?fl=' . urlencode(encrypt($dl)) . '&f=' . urlencode(encrypt("customer_ageing.xlsx")) . '">Download File...</a></h3>';
        }
        $str .= '
      <input type="hidden" id="hdnUpdateEdge" name="hdnUpdateEdge" />
      <input type="hidden" name="lookup_id" value="' . $lookup_id . '">
    ';
        return $str;
    }

    function WelfareReport() {
        $main_folder = $this->f3->get('main_folder');
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);

        $lats = Array();
        $longs = Array();
        $site_ids = Array();
        $sql = "select distinct(users.id) as `site_id` from opening_closing inner join users on users.id = opening_closing.site_id";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $site_id = $myrow['site_id'];
            $site_ids[] = $site_id;
            $sql = "select distinct(meta_value) from usermeta where (meta_key = 93 or meta_key = 94) and user_id = $site_id order by meta_key;";
            $result2 = $this->dbi->query($sql);
            $myrow2 = $result2->fetch_assoc();
            $lats[] = $myrow2['meta_value'];
            $myrow2 = $result2->fetch_assoc();
            $longs[] = $myrow2['meta_value'];
        }
        $sql = "select id, latitude, longitude from wfc where not site_id and id > (select id from wfc where site_id > 1 order by id DESC limit 1)";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $latitude = $myrow['latitude'];
            $longitude = $myrow['longitude'];
            $cnt = 0;
            foreach ($site_ids as $site_id) {
                $dist = round(distance($latitude, $longitude, $lats[$cnt], $longs[$cnt]));
                if ($dist < 750) {
                    $sql = "update wfc set site_id = $site_id where id = $id;";
                    $this->dbi->query($sql);
                }
                $cnt++;
            }
        }
        $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
        $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
        $calStartDate = (isset($_GET['calStartDate']) ? $_GET['calStartDate'] : null);
        $calFinishDate = (isset($_GET['calFinishDate']) ? $_GET['calFinishDate'] : null);
        if (!$nav_month) {
            $def_month = 1;
            $nav_month = date("m");
            $nav_year = date("Y");
        }
        if ($nav_month > 0) {
            $nav1 = "and MONTH(wfc.date_time) = $nav_month";
        } else {
            $nav_month = "ALL Months";
        }
        if ($nav_year > 0) {
            $nav2 = "and YEAR(wfc.date_time) = $nav_year";
        } else {
            $nav_year = "ALL Years";
        }
        if ($calStartDate) {
            $nav1 = " and wfc.date_time between '" . date('Y-m-d', strtotime($calStartDate)) . " 00:00:00'";
            $nav2 = " and '" . date('Y-m-d', strtotime($calFinishDate)) . " 23:59:59' ";
            if ($calStartDate == $calFinishDate) {
                $nav_month = "On $calStartDate";
                $nav_year = "";
            } else {
                $nav_month = "Between $calStartDate";
                $nav_year = " and $calFinishDate";
            }
        } else {
            $nav_month = " During $nav_month / ";
        }
        $num_results = $result->num_rows;
        if ($nav_month != "ALL Months")
            $for_months = "(for $kpi_for_month / $kpi_for_year)";
        if (!$download_xl) {
            $str .= "<h3 class=\"fl\">Welfare Checks Performed $nav_month $nav_year</h3><div class=\"cl\"></div><b>Note: </b> To filter the Excel results, perform the filter first and then click [Download Excel].";
            $nav = new navbar;
            $str .= '
          <script language="JavaScript">
            function report_filter() {
              document.getElementById("calStartDate").value=""
              document.getElementById("calFinishDate").value=""
              document.getElementById("hdnReportFilter").value=1
              document.frmFilter.submit()
            }
            function date_range() {
              if(document.getElementById("calStartDate").value == "" || document.getElementById("calFinishDate").value == "") {
                msg1 = ""; msg2 = "";
                if(document.getElementById("calStartDate").value == "") msg1 = "Please enter a start date";
                if(document.getElementById("calFinishDate").value == "") msg2 = "Please enter a finish date";
                if(msg1 && msg2) msg1 += "\\n\\n";
                alert(msg1 + msg2);
              } else {
                document.getElementById("selDateMonth").selectedIndex=0
                document.getElementById("selDateYear").selectedIndex=0
                document.getElementById("hdnReportFilter").value=1
                document.frmFilter.submit()
              }
            }
          </script>
          </form>
          <form method="GET" name="frmFilter">
          <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
          <input type="hidden" name="group_kpi" id="edit_date" value="' . $group_kpi . '">
          <div class="form-wrapper">
          <div class="form-header">Filter</div>
          <div  style="padding: 10px;">
          ' . $nav->month_year(2016) . '
          <input onClick="report_filter()" type="button" value="Go" />';
            $itm = new input_item;
            $itm->hide_filter = 1;
            $str .= $itm->setup_cal();
            $str .= " OR Between: ";
            $str .= $itm->cal("calStartDate", Date('d-M-Y'), "style=\"width: 100px;\"", "", "", "", "");
            $str .= " And ";
            $str .= $itm->cal("calFinishDate", Date('d-M-Y'), "style=\"width: 100px;\"", "", "", "", "");

            $str .= ' <input onClick="date_range()" type="button" value="By Date Range" /> &nbsp; &nbsp; <a class="list_a" href="WelfareReport?download_xl=1&' . $_SERVER['QUERY_STRING'] . '">Download Excel</a>
          </div>
          </div>
          </form>
      ';
            if ($def_month) {
                $str .= '
          <script language="JavaScript">
            change_selDate()
          </script>
        ';
            }
        }
        $view_details = new data_list;
        $view_details->dbi = $this->dbi;
        $sql_top = ($download_xl ? "concat(users.name, ' ', users.surname) as `Staff Member`, wfc.date_time as `Check Date/Time`, concat(users2.name, ' ', users2.surname) as `Site`, wfc.staff_comment as `Guard Comment`, wfc.controller_comment as `Control Room Comment`" : "wfc.id as `idin`, wfc.id as `ID`, concat(users.name, ' ', users.surname) as `Staff Member`, wfc.date_time as `Check Date/Time`, wfc.ip_address as `IP`,
                            concat(users2.name, ' ', users2.surname) as `Site`, CONCAT(wfc.latitude, ' ', wfc.longitude) as `GPS Coords`, wfc.staff_comment as `Guard Comment`, wfc.controller_comment as `Control Room Comment`,
                            CONCAT('<a class=\"list_a\" target=\"_blank\" href=\"http://www.citymaps.ie/create-google-map/map.php?width=100%&height=600&hl=en&coord=', wfc.latitude, ',', wfc.longitude, '&q=+()&ie=UTF8&t=&z=14&iwloc=A&output=embed\">Map</a>') as `Map`"
                );
        $view_details->sql = "select $sql_top
                          from wfc
                          left join users on users.id = wfc.employee_id
                          left join users2 on users2.id = wfc.site_id
                          where 1
                          $nav1 $nav2 
                          order by users2.name ASC, wfc.date_time DESC;";
        if ($download_xl) {
            $view_details->format_xl = 1;
            $view_details->sql_xl('welfare_report.xlsx');
        } else {

            $view_details->title = "";
            $view_details->show_num_records = 1;
            $str .= $view_details->draw_list();
            //$str .= "<textarea>{$view_details->sql}</textarea>";
            return $str;
        }
    }

    function process_date($indate) {
        $date_elements = explode("/", $indate);
        $year = $date_elements[2];
        $current_year = Date("y");
        if ($year > $current_year) {
            $year = "19$year";
        } else {
            $year = "20$year";
        }
        $out_date = $year . "-" . $date_elements[1] . "-" . $date_elements[0];
        return $out_date;
    }

    function ProcessPowerforce() {
        $main_folder = $this->f3->get('main_folder');

        /* Fixing the given names problem */

        $sql = "select id, employee_id, name, surname from users where employee_id like 'K%' and name like '% %' and surname not like 'afridi'";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $num++;
            $id = $myrow['id'];
            $emp_id = $myrow['employee_id'];
            $first_name = $myrow['name'];
            $surname = $myrow['surname'];
            $uname = str_replace("'", "", $first_name);
            $uname = str_replace(",", "", $uname);
            $uname = current(explode(" ", $uname)); // getting the first name from the given name(s)
            $usurname = str_replace("'", "", $surname);
            $usurname = str_replace(" ", "", $usurname);
            $usurname = str_replace(",", "", $usurname);

            $username = strtolower("$uname.$usurname");

            $username_tmp = $username;
            $x = 1;
            $finished = 0;
            while (!$finished) {
                $sql = "select id from users where username = '$username_tmp' and employee_id != '$emp_id'";
                $result3 = $this->dbi->query($sql);
                if ($result3->num_rows) {
                    $x++;
                    $username_tmp = $username . $x;
                } else {
                    $finished = 1;
                }
            }
            $username = $username_tmp;
            $pw = $this->ed_crypt($username, $id);
            $sql = "update users set username = '$username', pw = '$pw' where id = $id;";

            $str .= "<h3>$num. $emp_id - $first_name $surname :: $username</h3>";

            $this->dbi->query($sql);
        }
        return $str;
        exit;

        $exceptions_mode = (isset($_GET['exceptions_mode']) ? $_GET['exceptions_mode'] : null);
        if ($exceptions_mode) {
            $this->list_obj->title = "Powerforce Exceptions";
            $this->list_obj->sql = "
        select powerforce_exceptions.id as `idin`, users.employee_id as `Employee Id`, CONCAT(users.name, ' ', users.surname) as `Staff Member`, states.item_name as `State`,
        'Delete' as `!` from powerforce_exceptions 
        left join users on users.id = powerforce_exceptions.user_id
        left join states on states.id = users.state
        where 1 $filter_string
        ORDER BY users.surname
      ";
            $this->editor_obj->table = "powerforce_exceptions";
            $tmp_sql = "select users.id, CONCAT(users.employee_id, ' -- ', users.name, ' ', users.surname, if(states.item_name != '', concat(' (', states.item_name, ')'), '')) as `item_name` from users
                 inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
                 left join states on states.id = users.state
                 where users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                 and users.id not in (select user_id from powerforce_exceptions)
                 order by users.surname;";
            $style = 'style="width: 440px;';
            $style_small = 'style="width: 120px;"';
            $this->editor_obj->form_attributes = array(array("selStaffMember"), array("Staff Member"), array("user_id"), array($tmp_sql), array(''), array("c"));
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );
            $this->editor_obj->form_template = '
                <div class="fl"><nobr>tselStaffMember</nobr><br />selStaffMember</div>
                <div style="clear: left;"></div>
                ' . $this->editor_obj->button_list() . '
                ';
            $this->editor_obj->editor_template = '
                  <div class="fl" style="max-width: 49%; margin-right: 20px; margin-bottom: 20px;">
                  <div class="form-wrapper">
                  <div class="form-header">Exceptions</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  editor_list
                  </div>
      ';
            $this->editor_obj->editor_template .= '
                <div class="fl" style="margin-left: 20px; width: 49%; padding: 0px; margin: 0px;">
                <iframe style="border: none; width: 100%; height: 750px;" name="child_frame"></iframe>
                </div>
                <div class="cl"></div>
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);
            return $str;
        } else {
            require_once('app/controllers/parsecsv.class.php');
            $str .= '
      <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">
      ' . $page_content . '
      </div>
      </form>
      <p><a class="list_a" href="ProcessPowerforce?exceptions_mode=1">Powerforce Exceptions</a></p>
      <form method="post" action="ProcessPowerforce" enctype="multipart/form-data">
      <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
      <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
      </form>
      ';
            $ustat[10] = 'PENDING';
            $ustat[20] = 'APPROVED';
            $ustat[30] = 'ACTIVE';
            $ustat[40] = 'INACTIVE';
            $ustat[50] = 'CANCELLED';
            $rstates = array();
            $rstates[1] = 'ACT';
            $rstates[2] = 'NSW';
            $rstates[3] = 'NT';
            $rstates[4] = 'QLD';
            $rstates[5] = 'SA';
            $rstates[6] = 'TAS';
            $rstates[7] = 'VIC';
            $rstates[8] = 'WA';
            if ($_FILES["thefile"]["error"] > 0) {
                $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
            } else if ($_FILES["thefile"]["name"] == 'powerforce.csv') {
                move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . $_FILES["thefile"]["name"]);
                $csv = new parseCSV($this->f3->get('upload_folder') . $_FILES["thefile"]["name"]);
                $row_count = 0;
                foreach ($csv->data as $val) {
                    $row_count++;
                    $emp_id = $val['Employeeid'];
                    $email = mysqli_real_escape_string($this->dbi, $val['Email Address']);
                    if ($emp_id != "SC00898" && $emp_id != "SC00811" && trim($emp_id)) {
                        $first_name = $val['First name'];
                        $name_elements = explode("(", $first_name);
                        $first_name = trim($name_elements[0]);
                        $sub = trim(str_replace(")", "", $name_elements[1]));
                        $surname = $val['Surname'];
                        $preferred_name = $val['PREFFERED_NAME'];
                        $name_elements = explode("(", $surname);
                        $surname = trim($name_elements[0]);
                        if (!$sub) {
                            $sub = trim(str_replace(")", "", $name_elements[1]));
                        }
                        $first_name = addslashes($first_name);
                        $surname = addslashes($surname);
                        if (trim(strpos(trim($first_name), "1") === FALSE)) {
                            $title = $val['TITLE'];
                            $sex = $val['Sex'];
                            $dob = $val['Birth Date'];
                            $street = addslashes($val['Employee a street']);
                            $suburb = addslashes($val['Suburb.....']);
                            $state = $val['Employee a city'];
                            $postcode = $val['Postcode'];
                            $employment_date = $val['Date Employed'];
                            $employee_type = $val['Emp Type'];
                            if ($employee_type == 'FULLTIME100' || $employee_type == 'FULLTIME76')
                                $employee_type = 'FULLTIME';
                            $cost_centre = $val['Emp Coy'];
                            $phone = $val['Home Phone'];
                            $tfn = $val['Tax  File Number'];
                            $termination_date = $val['Terminated'];
                            $termination_reason = $val['Reason For Termination'];
                            $cross_id = $val['SKILLS_IMAGE'];
                            $user_status_id = $val['EMPLOYEE_ACTIVE'];
                            if (strtoupper($user_status_id) == "NO") {
                                $user_status_id = 30;
                            } else {
                                if ($termination_date) {
                                    $termination_date = $this->process_date($termination_date);
                                    $user_status_id = 50;
                                } else {
                                    $user_status_id = 40;
                                }
                            }
                            $dob = ($dob ? $this->process_date($dob) : '');
                            $employment_date = ($employment_date ? $this->process_date($employment_date) : '');
                            if ($sub) {
                                $sql = "select id from users where supplier_id = '$sub';";
                                $result = $this->dbi->query($sql);
                                if ($myrow = $result->fetch_assoc()) {
                                    $cont_id = $myrow['id'];
                                } else {
                                    $cont_id = "";
                                }
                            }
                            $dob = Date("Y-m-d", strtotime($dob));
                            if ($email && $emp_id) {
                                $sql = "select id from users where email = '$email' and user_status_id = 30 and employee_id is NULL";
                                $result = $this->dbi->query($sql);
                                if ($myrow = $result->fetch_assoc()) {
                                    $id_change = $myrow['id'];
                                    $sql = "select name, email from users where employee_id = '$emp_id';";
                                    $result2 = $this->dbi->query($sql);
                                    if (!$result2->fetch_assoc()) {
                                        $sql = "update users set employee_id = '$emp_id' where id = $id_change";
                                        $this->dbi->query($sql);
                                        $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($id_change, 107, 'users'), ($id_change, 108, 'users')";
                                        if ($sub)
                                            $sql .= ", ($id_change, 481, 'users')";
                                        $this->dbi->query($sql);
                                        $pw = $this->ed_crypt($username, $id_change);
                                        $sql = "update users set pw = '$pw' where id = $id_change;";
                                        $this->dbi->query($sql);
                                        $sql = "insert into user_update_log (user_id, date_time, action) values ($id_change, now(), 'JOB_APP_TRANFERRED')";
                                        $this->dbi->query($sql);
                                        $num_job_app_transferred++;
                                        $transferred_users .= "[$emp_id - $first_name $surname] ";
                                    }
                                }
                            }
                            $sql = "select id, email, user_status_id from users where employee_id = '$emp_id'";
                            $result = $this->dbi->query($sql);
                            if ($myrow = $result->fetch_assoc()) {
                                $id = $myrow['id'];
                                $original_user_status_id = $myrow['user_status_id'];
                                if ($original_user_status_id != $user_status_id) {
                                    $email_in = $myrow['email'];
                                    if (!$email) {
                                        if ($email_in) {
                                            $email = $email_in;
                                        } else {
                                            $email = $emp_id;
                                        }
                                    }
                                    $sql = "update users set user_status_id = $user_status_id ";
                                    $pos = strpos($email, $emp_id);
                                    $sql .= ($termination_date ? ", termination_date = '$termination_date', termination_reason = '$termination_reason'" : "") .
                                            ($pos !== false ? ", email = '$emp_id'" : "");
                                    $sql .= " where id = $id and id not in (select user_id from powerforce_exceptions)";
                                    $this->dbi->query($sql);
                                    if (mysqli_affected_rows($this->dbi)) {
                                        $num_updated++;
                                        $changed_users .= "<h3>$emp_id - $first_name $surname status changed to " . ($user_status_id == 30 ? "ACTIVE" : ($user_status_id == 50 ? "TERMINATED" : "INACTIVE")) . "...</h3>";
                                        $sql = "insert into user_update_log (user_id, date_time, action) values ($id, now(), 'STATUS:" . ($user_status_id == 30 ? "ACTIVE" : ($user_status_id == 50 ? "TERMINATED" : "INACTIVE")) . "')";
                                        $this->dbi->query($sql);
                                    }
                                }
                                if ($employment_date) {
                                    $sql_t .= "update users set commencement_date = '$employment_date' where id = $id; ";
                                }
                            } else {
                                if ($user_status_id) {
                                    $x++;
                                    if (!$email)
                                        $email = $emp_id;
                                    if ($termination_date) {
                                        $xtra1 = ", termination_date, termination_reason";
                                        $xtra2 = ", '$termination_date', '$termination_reason'";
                                    } else {
                                        $xtra1 = "";
                                        $xtra2 = "";
                                    }
                                    $uname = str_replace("'", "", $first_name);
                                    $uname = current(explode(' ', $uname)); // getting the first name from the given name(s)
                                    $usurname = str_replace("'", "", $surname);
                                    $usurname = str_replace(" ", "", $usurname);
                                    $username = strtolower("$uname.$usurname");
                                    $username_tmp = $username;
                                    $x = 1;
                                    $finished = 0;
                                    while (!$finished) {
                                        $sql = "select id from users where username = '$username_tmp'";
                                        $result3 = $this->dbi->query($sql);
                                        if ($result3->num_rows) {
                                            $x++;
                                            $username_tmp = $username . $x;
                                        } else {
                                            $finished = 1;
                                        }
                                    }
                                    $username = $username_tmp;
                                    $sql = "insert into users (employee_id, user_status_id, name, surname, preferred_name, username, dob, email, address, suburb, postcode, state, phone, user_level_id, commencement_date, sex, employee_type $xtra1)
                            values ('$emp_id', '$user_status_id', '$first_name', '$surname', '$preferred_name', '$username', '$dob', '$email', '$street', '$suburb', '$postcode', '" . $states[$state] . "', '$phone', 300, '$employment_date', '$sex', '$employee_type' $xtra2)";
                                    if ($result = $this->dbi->query($sql)) {
                                        $num_added++;
                                        $added_users .= "[$emp_id - $first_name $surname] ";
                                        $iid = $this->dbi->insert_id;
                                        $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($iid, 107, 'users'), ($iid, 108, 'users')";
                                        if ($sub)
                                            $sql .= ", ($iid, 481, 'users')";
                                        $pw = $this->ed_crypt($username, $iid);
                                        $sql = "update users set pw = '$pw' where id = $iid;";
                                        $this->dbi->query($sql);
                                        $sql = "insert into user_update_log (user_id, date_time, action) values ($iid, now(), 'ADDED')";
                                        $this->dbi->query($sql);
                                    } else {
                                        $iid = $id;
                                    }
                                }
                            }
                            if ($cont_id && $iid) {
                                $sql = "insert into associations (association_type_id, parent_user_id, child_user_id) values (7, $cont_id, $iid);";
                            }
                        }
                    }
                }
                $str .= '<br /><br /><div class="message">' . ($num_added ? $num_added : "No") . ' New Users Added...</div><br /><h3>' . $added_users . '</h3><br />';
                $str .= '<br /><br /><div class="message">' . ($num_updated ? $num_updated : "No") . ' Users Updated...</div><br />' . $changed_users . '<br />';
                $str .= '<br /><br /><div class="message">' . ($num_job_app_transferred ? $num_job_app_transferred : "No") . ' Job Applications Transferred...</div><br />' . $transferred_users . '<br />';
                return $str;
                $str .= "</pre>";
            }
        }
        return $str;
    }

    function SetupStaff() {
        $main_folder = $this->f3->get('main_folder');
        $search = $_GET['shift_search'];
        $applicable_to_id = (isset($_GET['applicable_to_id']) ? $_GET['applicable_to_id'] : null);
        $remove_subject_kpi = (isset($_GET['remove_subject_kpi']) ? $_GET['remove_subject_kpi'] : null);
        $remove_assessor_kpi = (isset($_GET['remove_assessor_kpi']) ? $_GET['remove_assessor_kpi'] : null);
        $search_str = $search;
        $tmp_id = ($remove_subject_kpi ? $remove_subject_kpi : ($remove_assessor_kpi ? $remove_assessor_kpi : ($applicable_to_id ? $applicable_to_id : 0)));
        if ($tmp_id) {
            $sql = "select CONCAT(name, ' ', surname) as `user_name`, email, username from users where id = $tmp_id;";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $name = $myrow['user_name'];
                $username = $myrow['username'];
                $email = $myrow['email'];
            }
        }
        if ($remove_subject_kpi) {
            $sql = "select compliance_auditors_subjects.id from compliance_auditors_subjects
              left join compliance_auditors on compliance_auditors.id = compliance_auditors_subjects.compliance_auditor_id
              left join compliance on compliance.id = compliance_auditors.compliance_id
              where compliance.title LIKE '%kpi%' and compliance_auditors_subjects.user_id = $tmp_id";
            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc())
                $this->dbi->query("delete from compliance_auditors_subjects where id = " . $myrow['id']);
            $str .= "<h3 class=\"message\">$name removed from subject KPI's...</h3>";
        } else if ($remove_assessor_kpi) {
            $sql = "select compliance_auditors.id from compliance_auditors
              left join compliance on compliance.id = compliance_auditors.compliance_id
              where compliance.title LIKE '%kpi%' and compliance_auditors.user_id = $tmp_id";
            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc())
                $this->dbi->query("delete from compliance_auditors where id = " . $myrow['id']);
            $str .= "<h3 class=\"message\">$name removed from assessor KPI's...</h3>";
        } else if ($applicable_to_id) {
            $pw = $this->ed_crypt($username, $applicable_to_id);
            $sql = "update users set pw = '$pw', student_id = '$student_id' where id = $applicable_to_id;";
            $this->dbi->query($sql);
            $msg = 'Hello ' . $name . ',
      
Welcome to Edge. Edge allows you to sign on to a site, perform compliance checks, download resources and keep up-to date on the latest news.
Your Login Details are as follows:
Username: ' . $username . '
Your password is currently the same as your username. You’ll be asked to update your details (including password) on first login.
Please follow this link below to login.

https://Edge.scgs.com.au/

Regards,
Edgar Walkowsky.
      ';
            $str .= '<br /><br /><a class=\"list_a\" href="mailto:' . $email . '?subject=Welcome%20To%20Edge&body=' . rawurlencode($msg) . '">Email</a><br /><br />';
            $str .= "<h3>Added...</h3>";
            $str .= "<br /><br /><br />";
        }
        $str .= '
      <input type="hidden" name="apply_course" value="" />
      </form>
      <form method="get" name="frmFollowSearch">
      <input type="hidden" name="cdid" value="' . $cdid . '" />
      <div class="fl"><h3>Find Entities to Setup</h3>
      <input maxlength="50" name="shift_search" id="search" type="text" class="search_box" value="' . $_GET['shift_search'] . '" /><input type="submit" onClick="perform_search(' . $search_page . ')" name="cmdFollowSearch" value="Search" class="search_button" /> ' . $search_msg . '
      </div>
      <div class="cl"></div>
      </form>
    ';
        if ($search) {
            $search = "
        where (users.name LIKE '%$search%'
        or users.surname LIKE '%$search%'
        or users.email LIKE '%$search%'
        or users.employee_id LIKE '%$search%'
        or users.supplier_id LIKE '%$search%'
        or users.client_id LIKE '%$search%'
        or users.student_id LIKE '%$search%'
        or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
      ";
        }
        if ($search) {
            $sql = "
        SELECT users.id as `idin`, employee_id, student_id, client_id, supplier_id, CONCAT(users.name, ' ', users.surname) as `user_name`, user_status.item_name as `status`,
               CONCAT('<a class=\"list_a\" href=\"mailto: ', users.email, '\">', users.email, '</a>') as `email`, if(users.pw != '', 'Yes', 'No') as `has_pw`,
               states.item_name as `state`
               FROM users
               left join states on states.id = users.state
               left join user_status on user_status.id = users.user_status_id
               $search
        ";
            $result = $this->dbi->query($sql);
            $show_first = 1;
            while ($myrow = $result->fetch_assoc()) {
                if ($show_first) {
                    $str .= '<h3>Search Results</h3><table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small" >
                <tr><th align="left"><nobr>Emp ID<nobr></th><th align="left"><nobr>Status<nobr></th><th align="left"><nobr>Full Name<nobr></th><th align="left">
                <nobr>Email<nobr></th><th align="left"><nobr>State<nobr></th><th align="left"><nobr>Has PW<nobr></th><th colspan="8" align="left"><nobr>Entity Setup<nobr></th></tr>';
                }
                $idin = $myrow['idin'];
                $employee_id = $myrow['employee_id'];
                $client_id = $myrow['client_id'];
                $student_id = $myrow['student_id'];
                $supplier_id = $myrow['supplier_id'];
                $user_name = $myrow['user_name'];
                $email = $myrow['email'];
                $state = $myrow['state'];
                $has_pw = $myrow['has_pw'];
                $status = $myrow['status'];
                $setup_str = "<td valign=\"top\"><a class=\"list_a\" href=\"{$main_folder}Page/SetupStaff?applicable_to_id=$idin&shift_search=$search_str\">Setup</a></a></td>";
                $setup_str .= "<td valign=\"top\"><a class=\"list_a\" href=\"{$main_folder}Page/SetupStaff?remove_subject_kpi=$idin\">Remove Subject KPI's</a></a></td>";
                $setup_str .= "<td valign=\"top\"><a class=\"list_a\" href=\"{$main_folder}Page/SetupStaff?remove_assessor_kpi=$idin\">Remove Assessor KPI's</a></a></td>";
                $str .= '<tr><td valign="top">' . $employee_id . '</td><td valign="top">' . $status . '</td><td valign="top">' . $user_name . '</td><td valign="top">' . $email . '</td><td valign="top">' . $state . '</td><td valign="top">' . $has_pw . '</td>' . $setup_str . '</tr>';
                $show_first = 0;
            }
            $str .= "</table>";
        }
        return $str;
    }

    function Tickets() {
        $main_folder = $this->f3->get('main_folder');
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
        $edit = (isset($_GET['edit']) ? $_GET['edit'] : null);
        $process = (isset($_GET['process']) ? $_GET['process'] : null);
        $tid = (isset($_GET['tid']) ? $_GET['tid'] : '');
        $rid = (isset($_GET['rid']) ? $_GET['rid'] : '');
        if ($edit) {
            if (isset($_POST['hdnFilter']))
                $filter_string = "filter_string";
            $this->list_obj->title = "Tickets";
            $this->list_obj->sql = "
        select id as idin, id as `ID`, title as `Title`, date as `Date`, time as `Time`, if(calculate_availability = 1, 'Yes', 'No') as `Calculate Availability`, 'Edit' as `*`, 'Delete' as `!` from tickets
        where 1 $filter_string
        order by date ASC
        ";
            $this->editor_obj->table = "tickets";
            $style = 'style="width: 280px;"';
            $this->editor_obj->form_attributes = array(
                array("txtTitle", "calDate", "ti2Time", "chkCalculateAvailability", "txtNumAvailable"),
                array("Title", "Date", "Time", "Calculate Availability", "Num Available"),
                array("title", "date", "time", "calculate_availability", "num_available"),
                array("", "", "", "", ""),
                array($style, $style, $style, '', $style),
                array("c", "n", "c", "n"),
                array("", "", "", ""),
                array("", "", "", "")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset", "Filter"),
                array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
            );
            $this->editor_obj->form_template = '
                <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
                <div class="fl"><nobr>ttxtNumAvailable</nobr><br />txtNumAvailable</div>
                <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
                <div class="fl"><nobr>tti2Time</nobr><br />ti2Time</div>
                <div class="fl"><nobr>chkCalculateAvailability tchkCalculateAvailability</nobr><br /></div>
                <div class="cl"></div>
                ' . $this->editor_obj->button_list();
            $this->editor_obj->editor_template = '
                  <div class="form-wrapper" style="width: 100%;">
                  <div class="form-header">Tickets</div>
                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="cl"></div>
                  editor_list
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);
            return $str;
        } else if ($process) {
            $grant = (isset($_GET['grant']) ? $_GET['grant'] : '');
            $collect = (isset($_GET['collect']) ? $_GET['collect'] : '');
            $tiid = (isset($_GET['tiid']) ? $_GET['tiid'] : '');
            if ($tiid) {
                if ($grant) {
                    $sql = "update ticket_interest set is_granted = " . ($grant == 'yes' ? "1" : "0, is_collected = 0") . " where id = $tiid";
                    $result = $this->dbi->query($sql);
                } else if ($collect) {
                    $sql = "update ticket_interest set is_collected = " . ($collect == 'yes' ? "1, is_granted = 1" : "0") . " where id = $tiid";
                    $result = $this->dbi->query($sql);
                }
            }
            $this->list_obj->title = "Ticket Requests";
            $this->list_obj->sql = "
        select tickets.title as `Title`, DATE_FORMAT(tickets.date, '%a %d-%b-%Y') as `Date`, TIME_FORMAT(tickets.time, '%h:%m %p') as `Time`,
        concat('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Staff Member`,
        CONCAT('<span style=\"color: ', if(ticket_interest.is_granted = 1, '#009900; font-weight: bold;\">Granted', '#990000; \">Not Granted'), '</span>') as `Is Granted`,
        CONCAT('<a class=\"list_a\" href=\"{$main_folder}Page/Tickets?process=1&tiid=', ticket_interest.id, '&grant=', if(ticket_interest.is_granted = 1, 'no\">Remove Grant', 'yes\">Grant'), '</a>') as `Grant`,
        CONCAT('<span style=\"color: ', if(ticket_interest.is_collected = 1, '#009900; font-weight: bold;\">Collected', '#990000; \">Not Collected'), '</span>') as `Is Collected`,
        CONCAT('<a class=\"list_a\" href=\"{$main_folder}Page/Tickets?process=1&tiid=', ticket_interest.id, '&collect=', if(ticket_interest.is_collected = 1, 'no\">Remove Collected', 'yes\">Mark Collected'), '</a>') as `Collect`
        from tickets
        inner join ticket_interest on ticket_interest.ticket_id = tickets.id
        inner join users on users.id = ticket_interest.user_id
        where tickets.date != '0000-00-00' and tickets.date >= now()
        order by tickets.title, users.surname
      ";
            $str .= $this->list_obj->draw_list();
            //$str = "<textarea>{$this->list_obj->sql}</textarea>";
        } else {
            $reg = (isset($_GET['reg']) ? $_GET['reg'] : '');
            if ($tid && $reg) {
                if ($reg == 'yes') {
                    $sql = "insert into ticket_interest (user_id, ticket_id) values ('" . $_SESSION['user_id'] . "', '$tid');";
                } else {
                    $sql = "delete from ticket_interest where user_id = '" . $_SESSION['user_id'] . "' and ticket_id = '$tid';";
                }
                $result = $this->dbi->query($sql);
            }
            $this->list_obj->title = "Tickets - Register your Interest for FREE Tickets (Subject to Availability)";
            $this->list_obj->sql = "
        select distinct(tickets.id) as `idin`, tickets.title as `Title`, DATE_FORMAT(tickets.date, '%a %d-%b-%Y') as `Date`, TIME_FORMAT(tickets.time, '%h:%m %p') as `Time`,
        CONCAT('<span style=\"color: ', if('" . $_SESSION['user_id'] . "' in (select user_id from ticket_interest where ticket_id = tickets.id), '#009900; font-weight: bold;\">Registred', '#990000; \">Not Registered'), '</span>') as `Is Registered`,
        CONCAT('<a class=\"list_a\" href=\"{$main_folder}Page/Tickets?tid=', tickets.id, '&reg=', if('" . $_SESSION['user_id'] . "' in (select user_id from ticket_interest where ticket_id = tickets.id), 'no\">Unregister', 'yes" . ($_GET['show_min'] ? "&show_min=$show_min" : "") . "\">Register'), '</a>') as `Register`
        from tickets
        left join ticket_interest on ticket_interest.ticket_id = tickets.id
        left join users on users.id = ticket_interest.user_id
        where tickets.date != '0000-00-00' and tickets.date >= now()
        order by tickets.date ASC
      ";
            $str .= ($this->list_obj->draw_list() ? $this->list_obj->draw_list() : "<h3>Tickets</h3><i>No Tickets Currently Available...</i>");
        }
        return $str;
    }

    function StaffLicences() {
        $main_folder = $this->f3->get('main_folder');
        $verify_mode = (isset($_GET['verify_mode']) ? $_GET['verify_mode'] : null);
        $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
        $verify_id = (isset($_GET['verify_id']) ? $_GET['verify_id'] : null);
        $unverify_id = (isset($_GET['unverify_id']) ? $_GET['unverify_id'] : null);
        $state_id = (isset($_GET['state_id']) ? $_GET['state_id'] : null);
        $suspend = (isset($_GET['suspend']) ? $_GET['suspend'] : null);
        $suspendid = (isset($_GET['suspendid']) ? $_GET['suspendid'] : null);
        $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $edit_id = (isset($_POST['idin']) ? $_POST['idin'] : null);
        $chkLicences = (isset($_POST['chkLicences']) ? $_POST['chkLicences'] : null);
        if ((!$hr_user || !$lookup_id) && !$verify_mode && !$suspend && !$verify_id && !$unverify_id && $_SESSION['u_level'] < 700)
            $lookup_id = $_SESSION['user_id'];
        if (!$lookup_id && !$verify_mode && !$suspend && !$verify_id && !$unverify_id)
            $lookup_id = $_SESSION['user_id'];
        if ($lookup_id) {
            $sql = "select users.employee_id, users.name, users.surname, states.item_name, users.state from users
              left join states on states.id = users.state
              where users.id = $lookup_id";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $employee_id = $myrow['employee_id'];
                $name = $myrow['name'];
                $surname = $myrow['surname'];
                $state = $myrow['item_name'];
                if (!$state_id)
                    $state_id = $myrow['state'];
            }
            if ($hr_user) {
                if (strtoupper(substr($name, strlen($name) - 1, 1)) == 'S') {
                    $pl = "'";
                } else {
                    $pl = "'S";
                }
                $str .= "<p><a target=\"_blank\" href=\"{$main_folder}Resources?current_dir=user_files&current_subdir=$lookup_id\">Go to $name$pl Personnel File on Edge</a></p>";
            } else {
                $str .= "<p>Please email your scanned or photographed licence to <a href=\"mailto:hr.department@scgs.com.au\">hr.department@scgs.com.au</a>.</p>";
            }
        }
        if ($verify_id || $unverify_id) {
            if ($verify_id) {
                $vby = $_SESSION['user_id'];
                $pre = "V";
                $undo = "<br /><br /><a class=\"list_a\" href=\"{$main_folder}?lookup_id=$lookup_id&unverify_id=$verify_id&verify_mode=$verify_mode\">Undo...</a>";
            } else {
                $vby = "0";
                $verify_id = $unverify_id;
                $pre = "Unv";
                $undo = "<br /><br /><a class=\"list_a\" href=\"{$main_folder}?lookup_id=$lookup_id&verify_id=$verify_id&verify_mode=$verify_mode\">Reverify...</a>";
            }
            $sql = "update staff_licences set verified_by = " . $vby . " where id = $verify_id";
            $this->dbi->query($sql);
            $str .= "<h3>Licence $pre" . "erified.</h3>$undo<br /><br />";
            if ($verify_mode) {
                $str .= "<a class=\"list_a\" href=\"{$main_folder}?verify_mode=1\">Back to Licence Verification...</a>";
            } else {
                $str .= "<a class=\"list_a\" href=\"{$main_folder}?lookup_id=$lookup_id\">Back to Licences for $name $surname...</a>";
            }
        } else if ($suspend) {
            if ($suspend == 1) {
                $sby = "1";
                $pre = "Suspended";
            } else {
                $sby = "0";
                $pre = "Suspension Removed";
            }
            $sql = "update staff_licences set is_suspended = " . $sby . " where id = $suspendid";
            $this->dbi->query($sql);
            $str .= "<h3>Licence $pre</h3><br /><br />";
            if ($verify_mode) {
                $str .= "<a class=\"list_a\" href=\"{$main_folder}?verify_mode=1\">Back to Licence Verification...</a>";
            } else {
                $str .= "<a class=\"list_a\" href=\"{$main_folder}?lookup_id=$lookup_id\">Back to Licences for $name $surname...</a>";
            }
        } else {
            $str .= '
      <style>
      .state_tab {
        padding: 12px !important;
        border: none !important;
        font-size: 13pt !important;
        display: inline-block;
        color: #333333;
        background-color: #B2C0DD;
        border: 0px !important;
        text-decoration: none !important;
        margin-bottom: -1px;
      }
      .state_selected {
        color: white !important;
        background-color: #14457E;
      }
      .state_tab:visited {
        color: #333333;
      }
      .state_tab:hover {
        background-color: #CCCCCC;
        color: #333333 !important;
      }
      .state_selected:hover {
        color: white !important;
        background-color: #14457E !important;
      }
      </style>';
            $show_edit = "";
            if ($hr_user) {
                $show_edit = ",
                     if(staff_licences.is_suspended, CONCAT('<a class=\"list_a\" href=\"{$main_folder}?suspend=2&suspendid=', staff_licences.id, '&verify_mode=$verify_mode&lookup_id=$lookup_id\">Remove Suspension</a>'),
                     CONCAT('<a class=\"list_a\" href=\"{$main_folder}?suspend=1&suspendid=', staff_licences.id, '&verify_mode=$verify_mode&lookup_id=$lookup_id\">Suspend</a>')) as `Suspension` $additional_fields,
                     CONCAT('<a class=\"list_a\" href=\"{$main_folder}staff_licences.php?lookup_id=$lookup_id&verify_id=', staff_licences.id, '&verify_mode=$verify_mode\">Verify</a>') as `Verify`";
            }
            if ($verify_mode) {
                $additional_fields = ", CONCAT('<a class=\"list_a\" target=\"_blank\" href=\"{$main_folder}Resources?current_dir=user_files&current_subdir=', users.id, '\">Personnel File</a> <a href=\"{$main_folder}?lookup_id=', users.id, '\">', users.name, ' ', users.surname, '</a>') as `Licence Holder`";
                $where_clause = "where staff_licences.verified_by = 0 and user_status.item_name = 'ACTIVE'";
                $this->list_obj->title = "Licence Verification";
            } else {
                $this->list_obj->title = "Licences for $employee_id $name $surname";
                $where_clause = "where staff_licences.user_id = $lookup_id and user_status.item_name = 'ACTIVE'";
                $additional_fields = "";
                $show_edit .= ",'Edit' as `*`";
                $str .= "<div>";
                $sql = "select * from states";
                $result = $this->dbi->query($sql);
                if (!$state_id || $state_id == "ALL")
                    $state_css_xtra = "state_selected";
                $str .= "<a class=\"state_tab $state_css_xtra\" href=\"{$main_folder}Page/StaffLicences?lookup_id=$lookup_id&state_id=ALL\">ALL</a>";
                while ($myrow = $result->fetch_assoc()) {
                    $state_idin = $myrow['id'];
                    if ($state_idin == $state_id) {
                        $state_css_xtra = "state_selected";
                    } else {
                        $state_css_xtra = "";
                    }
                    $str .= "<a class=\"state_tab $state_css_xtra\" href=\"{$main_folder}Page/StaffLicences?lookup_id=$lookup_id&state_id=$state_idin\">" . $myrow['item_name'] . "</a>";
                }
                $str .= "</div>";
            }
            if ($hr_user && !$verify_mode) {
                $show_edit .= ", 'Delete' as `!`";
            }
            $this->list_obj->sql = "
            select staff_licences.id as idin, staff_licence_lookups.item_name as `Licence Type`, staff_licences.licence_number as `Licence Number`, staff_licences.expiry_date as `Expiry Date`, CONCAT(users2.name, ' ', users2.surname) as `Verified By`,
            if(staff_licences.is_suspended, 'Yes', 'No') as `Is Suspended`
            $additional_fields $show_edit
            FROM staff_licences
            left join users2 on users2.id = staff_licences.verified_by
            left join staff_licence_lookups on staff_licence_lookups.id = staff_licences.staff_licence_type_id
            left join users on users.id = staff_licences.user_id
            left join user_status on user_status.id = users.user_status_id 
            $where_clause
            $filter_string
        ";
            if (!$verify_mode) {
                if ($hr_user) {
                    $this->editor_obj->custom_field = "verified_by";
                    $this->editor_obj->custom_value = $_SESSION['user_id'];
                }
                $this->editor_obj->xtra_id_name = "user_id";
                $this->editor_obj->xtra_id = $lookup_id;
                $this->editor_obj->table = "staff_licences";
                $style = 'style="width: 200px;"';
                $style_small = 'style="width: 120px;"';
                $this->editor_obj->form_attributes = array(
                    array("txtLicenceNumber", "calExpiryDate"),
                    array("Licence Number", "Expiry Date"),
                    array("licence_number", "expiry_date"),
                    array("", ""),
                    array($style, $style_small),
                    array("c", "n"),
                    array("", ""),
                    array("", "")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset"),
                    array("cmdAdd", "cmdSave", "cmdReset"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
                );
                $state_lookup_filter = " where is_current = 1 ";
                if ($state_id && $state_id != "ALL") {
                    $state_lookup_filter .= " and states.id = $state_id ";
                    $state_xtra = ", staff_licence_lookups.item_name";
                } else {
                    $state_xtra = ", CONCAT(states.item_name, ' - ', staff_licence_lookups.item_name) as `item_name`";
                }
                $this->editor_obj->form_template = '
                  <div class="fl"><nobr>ttxtLicenceNumber</nobr><br />txtLicenceNumber</div>
                  <div class="fl"><nobr>tcalExpiryDate</nobr><br />calExpiryDate</div>
                  <div style="clear: left;"></div>';
                $this->editor_obj->form_template .= "checklist1_goes_hEre";
                $this->editor_obj->form_template .= '
                  <div style="clear: left;"></div>' . $this->editor_obj->button_list();
                $this->editor_obj->editor_template = '
                    <div class="fl">
                    <div class="form-wrapper" style="max-width: 650px;">
                    <div class="form-header">Licences for ' . "$employee_id $name $surname" . '</div>
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
                    </div>
                    <div class="cl"></div>
        ';
                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
            } else {
                $str .= $this->list_obj->draw_list();
            }
            if ($action == "add_record") {
                $save_id = $this->editor_obj->last_insert_id;
            } else if ($action == "save_record") {
                $save_id = $this->editor_obj->idin;
                if ($state_id && $state_id != "ALL")
                    $del_xtra = " and staff_licence_lookup_id in (select id from staff_licence_lookups where state_id = $state_id) ";
                $sql = "delete from staff_licences_types where staff_licence_id = $save_id $del_xtra";
                $this->dbi->query($sql);
                if ($lookup_id == $_SESSION['user_id']) {
                    $sql = "update staff_licences set verified_by = 0 where id = $save_id";
                    $this->dbi->query($sql);
                }
            } else if ($action == "delete_record") {
                $sql = "delete from staff_licences_types where staff_licence_id = " . $this->editor_obj->idin;
                $this->dbi->query($sql);
            }
            if ($save_id) {
                $edit_id = $save_id;
                foreach ($chkLicences as $licence_lookup_id) {
                    $sql = "insert into staff_licences_types(staff_licence_id, staff_licence_lookup_id) values ($save_id, $licence_lookup_id)";
                    $this->dbi->query($sql);
                }
                if ($licence_lookup_id) {
                    $sql = "select staff_licence_lookups2.id as `parent_id`
                from staff_licence_lookups
                left join staff_licence_lookups2 on staff_licence_lookups2.id = staff_licence_lookups.parent_id
                where staff_licence_lookups.id = $licence_lookup_id";
                    $result = $this->dbi->query($sql);
                    if ($myrow = $result->fetch_assoc()) {
                        $par_id = $myrow['parent_id'];
                        $sql = "update staff_licences set staff_licence_type_id = $par_id where id = $save_id";
                        $this->dbi->query($sql);
                    }
                }
            }
            $checklist = "";
            $chk_obj = new input_item;
            if ($edit_id) {
                $sql = "select staff_licence_lookup_id from staff_licences_types where staff_licence_id = $edit_id order by staff_licence_lookup_id";
                $result = $this->dbi->query($sql);
                $x = 0;
                while ($myrow = $result->fetch_assoc()) {
                    $staff_licence_lookup_ids[$x] = $myrow['staff_licence_lookup_id'];
                    $x++;
                }
            }
            $sql = "select staff_licence_lookups.id, staff_licence_lookups.parent_id $state_xtra from staff_licence_lookups left join states on states.id = staff_licence_lookups.state_id $state_lookup_filter or staff_licence_lookups.parent_id = 0";
            $result = $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $item_id = $myrow['id'];
                $item_name = $myrow['item_name'];
                $parent_id = $myrow['parent_id'];
                if ($edit_id && $staff_licence_lookup_ids) {
                    if (array_search($item_id, $staff_licence_lookup_ids) !== false) {
                        $chk = "checked";
                    } else {
                        $chk = "";
                    }
                }
                if ($parent_id) {
                    $checklist .= $chk_obj->chk("$item_id|chkLicences[]", $item_name, $chk, $item_id, '', '');
                } else {
                    $checklist .= "<div class=\"cl\"></div><h3>$item_name</h3>";
                }
            }
            $str = str_replace("checklist1_goes_hEre", $checklist, $str);
            $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
        }
        return $str;
    }

    function ComplianceDateChange() {
        $main_folder = $this->f3->get('main_folder');
        $txtID = (isset($_POST['txtID']) ? $_POST['txtID'] : null);
        $calDate = (isset($_POST['calDate']) ? $_POST['calDate'] : null);
        $hdnChangeItem = (isset($_POST['hdnChangeItem']) ? $_POST['hdnChangeItem'] : null);
        $selChangeStatus = (isset($_POST['selChangeStatus']) ? $_POST['selChangeStatus'] : null);
        $status_mode = (isset($_GET['status_mode']) ? $_GET['status_mode'] : null);
        if ($txtID && ($calDate || $selChangeStatus) && $hdnChangeItem) {
            $mysql_date = ($calDate ? Date("Y-m-d", strtotime($calDate)) : "");
            $sql = "update compliance_checks set " . ($mysql_date ? "check_date_time = '$mysql_date 0:0:0'" : "") . ($selChangeStatus ? ($mysql_date ? ", " : " ") . "compliance_checks.status_id = '$selChangeStatus'" : "") . " where id = $txtID";
            $this->dbi->query($sql);
            $str .= $this->message(($mysql_date ? "Date" : "") . ($selChangeStatus ? ($mysql_date ? " and " : " ") . "Status" : "") . " Changed...", 2000);
        }
        $txt_itm = new input_item;
        $str .= $txt_itm->setup_cal();
        $str .= '
    <script language="JavaScript">
      function change_date() {
        document.getElementById("hdnChangeItem").value=1
        document.frmEdit.submit()
      }
      function cancel() {
        document.getElementById("txtID").value=""
        document.getElementById("selChangeStatus").selectedIndex = 0
        document.frmEdit.submit()
      }
    </script>
    <input type="hidden" name="hdnChangeItem" id="hdnChangeItem">
    <div class="form-wrapper">
    <div class="form-header">Change ' . (!$status_mode ? 'Date/' : '') . 'Status</div>
    <div  style="padding: 10px;">
    ';
        $str .= $txt_itm->txt("txtID", "", ' placeholder="ID" style="width: 80px;" ', "", "", "");
        if (!$status_mode)
            $str .= $txt_itm->cal("calDate", "", ' placeholder="Date" value="' . $calDate . '" style="width: 180px;" ', "", "", "");
        $str .= $txt_itm->sel("selChangeStatus", "", ' placeholder="Date" value="' . $calDate . '" style="width: 180px;" ', "", "", $this->get_lookup('compliance_status'));
        if ($txtID && ($calDate || $selChangeStatus) && !$hdnChangeItem) {
            $this->list_obj->sql = "select compliance.title as `Check`, compliance_checks.check_date_time as `Date`, lookup_fields.item_name as `Status`, compliance_checks.percent_complete as `% Done`, CONCAT(users.name, ' ', users.surname) as `Assessor`, CONCAT(users2.name, ' ', users2.surname) as `Subject`
      from compliance_checks
      left join users on users.id = compliance_checks.assessor_id
      left join users2 on users2.id = compliance_checks.subject_id
      left join lookup_fields on lookup_fields.id = compliance_checks.status_id
      left join compliance on compliance.id = compliance_checks.compliance_id
      where compliance_checks.id = $txtID";
            $str .= $this->list_obj->draw_list() . "<div style='margin-top: 15px; width: 335px; border: 1px solid #CCCCCC;  background-color: #FFFFDD;  color: #660000;  font-size: 16pt;  font-weight: bold;  padding: 15px;'>
                                    <nobr>Confirm Change to the following:<br /><br />
                                     " . ($calDate ? "- Date" : "") . ($selChangeStatus ? ($calDate ? "<br />" : "") . "- Status" : "") . "
                                    </nobr><br /><br />
      ";
            $str .= '<input onClick="change_date()" type="button" value="CONFIRM" /> ';
            $str .= '<input onClick="cancel()" type="button" value="CANCEL" /></div> ';
        } else {
            $str .= '<input type="submit" value="GO" /> ';
        }
        return $str;
    }

    function CreateForm() {
        $main_folder = $this->f3->get('main_folder');
        $table_select = (isset($_GET['table_select']) ? $_GET['table_select'] : null);
        if ($table_select) {
            $sql = "SELECT * FROM `$table_select`";
            $str = '  function ' . strtoupper(substr($table_select, 0, 1)) . substr($table_select, 1) . '() {<br /><br />    $this->list_obj->sql = "';
            if ($result = mysqli_query($this->dbi, $sql)) {
                $sql_str = "select ";
                $form_str = htmlspecialchars('<div class="form-wrapper" style="">' . "\r\n" . '      <div class="form-header" style="">' . strtoupper(substr($table_select, 0, 1)) . substr($table_select, 1) . '</div>' . "\r\n      " . '<div class="form-content">');
                while ($fieldinfo = mysqli_fetch_field($result)) {
                    $field = $fieldinfo->name;
                    $parts = explode('_', $field);
                    if (stripos($field, "date") !== false) {
                        $txt = "cal";
                    } else if (stripos($field, "_by_id") !== false || stripos($field, "user_id") !== false || stripos($field, "staff_id") !== false || stripos($field, "_to_id") !== false) {
                        $txt = "cmb";
                    } else if (stripos($field, "_id") !== false) {
                        $txt = "sel";
                    } else {
                        $txt = "txt";
                    }
                    $title = "";
                    foreach ($parts as $part) {
                        $part = strtoupper(substr($part, 0, 1)) . substr($part, 1);
                        $title .= "$part ";
                        $txt .= $part;
                    }
                    $title = (strtolower($field) == "id" ? "idin" : trim($title));
                    $title = str_ireplace(" id", "", $title);
                    $sql_str .= "$table_select.$field as `$title`, ";
                    if (strtoupper($field) != "ID") {
                        $txt_str .= "\"$txt\", ";
                        $title_str .= "\"$title\", ";
                        $table_str .= "\"$field\", ";
                        $blank_str .= "\"\", ";
                        $comp_str .= "\"n\", ";
                        $form_str .= '<br />        ' . htmlspecialchars('<div class="fl"><nobr>t' . $txt . '</nobr><br />' . $txt . '</div>');
                    }
                }
                $form_str .= "\r\n        " . htmlspecialchars("<div class=\"cl\"></div>") . "\r\n        '." . '$this->editor_obj->button_list()' . ".'" . htmlspecialchars("\r\n      </div>\r\n    </div>");
                $sql_str = "$sql_str'Edit' as `*`, 'Delete' as `!` from $table_select;";
                $txt_str = substr($txt_str, 0, strlen($txt_str) - 2);
                $title_str = substr($title_str, 0, strlen($title_str) - 2);
                $table_str = substr($table_str, 0, strlen($table_str) - 2);
                $blank_str = substr($blank_str, 0, strlen($blank_str) - 2);
                $comp_str = substr($comp_str, 0, strlen($comp_str) - 2);
                mysqli_free_result($result);
            }
            $str .= $sql_str . '";
    $this->editor_obj->table = "' . $table_select . '";
    $style = \'style="width: 125px;"\';
    $this->editor_obj->form_attributes = array(
      array(' . $txt_str . '),
      array(' . $title_str . '),
      array(' . $table_str . '),
      array(' . $blank_str . '),
      array(' . $comp_str . ')
    );
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset", "Filter"),
      array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
    );
    $this->editor_obj->form_template = \'
    ' . $form_str . '\';
    $this->editor_obj->editor_template = \'editor_form' . htmlspecialchars('<div class="cl"></div>') . 'editor_list\';
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);
    return $str;
  }
    ';
        }
        $str = "<pre>$str</pre>";
        $this->list_obj->sql = "
    SELECT CONCAT('<a href=\"{$main_folder}Page/CreateForm?table_select=', TABLE_NAME, '\">', TABLE_NAME, '</a>') as `TABLE` FROM INFORMATION_SCHEMA.TABLES WHERE (TABLE_SCHEMA LIKE 'eroster' or TABLE_SCHEMA LIKE 'Edge') and TABLE_TYPE != 'VIEW';
    ";
        $str .= $this->list_obj->draw_list();
        return $str;
    }

    function AccessSummary() {
        $main_folder = $this->f3->get('main_folder');
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        if (!$download_xl) {
            $srch = (isset($_POST['txtPerformSearch']) ? $_POST['txtPerformSearch'] : null);
            $str .= '<input name="txtPerformSearch" style="height: 24px !important;" id="txtPerformSearch" type="text" placeholder="Search..." class="search_box" value="' . $srch . '" />
            <input type="submit" value="Search" class="search_button" /><!-- &nbsp; &nbsp; &nbsp; <a href="?kpi_map=1&xl=1">Download in Excel Format</a>--><div class="cl"></div>' .
                    ($srch ? "<p><b>Search Criteria: </b>$srch</p>" : "");
            if ($srch) {
                $srch = "
          and (users.name LIKE '%$srch%'
          or users.surname LIKE '%$srch%'
          or users.preferred_name LIKE '%$srch%'
          or users.email LIKE '%$srch%'
          or users.phone LIKE '%$srch%'
          or users.employee_id LIKE '%$srch%'
          or users.username LIKE '%$srch%'
          or CONCAT(users.name, ' ', users.surname) LIKE '%$srch%'
          or CONCAT(users.preferred_name, ' ', users.surname) LIKE '%$srch%')";
            }
        }
        $sql = "select employee_id, CONCAT(name, ' ', surname) as `users_name` from users";
        $sql = "
        select users.id as `idin`, employee_id as `employee_id`,
        CONCAT(" . (!$download_xl ? "'<a href=\"mailto:', users.email, '\">', " : "") . " users.name, ' ', users.surname " . (!$download_xl ? ", '</a>'" : "") . ") as `users_name`,
        states.item_name as `state`, phone, if(users.pw != '', 'Yes', 'No') as `has_pw`, users.email, user_level.item_name as `user_level` from users
                  left join states on states.id = users.state
                  inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
                  left join user_level on user_level.id = users.user_level_id
                  where users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
                  $srch
                  order by users.surname;
    ";
        $result = $this->dbi->query($sql);
        $str .= "<p>" . ($result->num_rows ? "Showing " . $result->num_rows . " Staff Members" : "No Records Found") . "...</p>";
        $str .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small"><tr><th>ID</th><th>Name</th><th>State</th><th>Phone</th><th>Has PW</th><th>User Level</th><th>Groups</th></tr>';
        while ($myrow = $result->fetch_assoc()) {
            $id = $myrow['idin'];
            $employee_id = $myrow['employee_id'];
            $users_name = $myrow['users_name'];
            $state = $myrow['state'];
            $phone = $myrow['phone'];
            $has_pw = $myrow['has_pw'];
            $user_level = $myrow['user_level'];
            $str .= "<tr><td>$employee_id</td><td>$users_name</td><td>$state</td><td>$phone</td><td>$has_pw</td><td>$user_level</td><td>";
            $sql = "select lookup_fields.item_name from lookup_answers
              inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id
              where lookup_answers.table_assoc = 'users' and lookup_answers.foreign_id = $id";
            $result2 = $this->dbi->query($sql);
            while ($myrow2 = $result2->fetch_assoc()) {
                $user_group = $myrow2['item_name'];
                $str .= "[$user_group] ";
            }
            $str .= "</td></tr>";
        }
        $str .= "</table>";
        return $str;
    }

    function GpsSites() {
        $main_folder = $this->f3->get('main_folder');
        $sql = "select usermeta.meta_value, users.name as `site`, users.id as `site_id` from usermeta
            left join users on users.id = usermeta.user_id
            where (usermeta.meta_key = 93 or usermeta.meta_key = 94)
            order by users.name, meta_key";
        $result = $this->dbi->query($sql);
        $cnt = 0;
        while ($myrow = $result->fetch_assoc()) {
            if ($cnt % 2) {
                $long = $myrow['meta_value'];
                $site_name = $myrow['site'];
                $str .= '<p><a target="_blank" class="list_a" href="http://www.citymaps.ie/create-google-map/map.php?width=100%&height=600&hl=en&coord=' . $lat . ',' . $long . '&q=+()&ie=UTF8&t=&z=14&iwloc=A&output=embed">Map</a> - ' . $site_name . '</p>';
            } else {
                $lat = $myrow['meta_value'];
            }
            $cnt++;
        }
        return $str;
    }

    function MyKpi() {
        $main_folder = $this->f3->get('main_folder');
        $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
        $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
        $report_view_id = (isset($_GET['report_view_id']) ? $_GET['report_view_id'] : null);
        if (!$nav_month) {
            $def_month = 1;
            $nav_month = date("m");
            $nav_year = date("Y");
        }
        $nav = new navbar;
        $str .= '
        <script language="JavaScript">
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <div class="form-wrapper">
        <div class="form-header">Filter</div>
        <div  style="padding: 10px;">
        ' . $nav->month_year(2015) . '
        <input onClick="report_filter()" type="button" value="Go" /> 
        </div>
        </div>
        </form>
    ';
        if ($def_month) {
            $str .= '
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
        }
        if ($nav_month > 0) {
            $nav1 = "and MONTH(compliance_checks.check_date_time) = $nav_month";
        } else {
            $nav_month = "ALL Months";
        }
        if ($nav_year > 0) {
            $nav2 = "and YEAR(compliance_checks.check_date_time) = $nav_year";
        } else {
            $nav_year = "ALL Years";
        }
        $kpi_for_month = $nav_month - 1;
        $kpi_for_year = $nav_year;
        if (!$kpi_for_month) {
            $kpi_for_month = 12;
            $kpi_for_year--;
        }
        if ($report_view_id) {
            $sql = "select id from compliance_checks where id = $report_view_id and subject_id = " . $_SESSION['user_id'] . ";";
            $result = $this->dbi->query($sql);
            if ($result->num_rows) {
                $compliance_obj = new compliance;
                $compliance_obj->dbi = $this->dbi;
                $compliance_obj->compliance_check_id = $report_view_id;
                $compliance_obj->display_results();
            }
        } else {
            $sql = "
      select compliance_checks.id, compliance.title, CONCAT(users2.name, ' ', users2.surname) as `assessor`, states.item_name as `state_name`,
              users.id as `subject_id`, users2.id as `assessor_id`, users.employee_id, compliance_checks.total_out_of, compliance_checks.check_date_time
              from compliance_checks
              left join users on users.id = compliance_checks.subject_id
              left join users2 on users2.id = compliance_checks.assessor_id
              left join compliance on compliance.id = compliance_checks.compliance_id
              left join states on states.id = users.state
              where users.id = " . $_SESSION['user_id'] . "
              and (compliance_checks.status_id = 522 or compliance_checks.status_id = 524)
              $nav1 $nav2 
              and compliance.title LIKE '%kpi%'
              order by compliance_checks.check_date_time DESC
      ";
            $result = $this->dbi->query($sql);
            $num_results = $result->num_rows;
            if ($nav_month != "ALL Months")
                $for_months = "(for $kpi_for_month / $kpi_for_year)";
            $str .= "<h3>KPI Summary Performed During $nav_month / $nav_year $for_months $title_xtra</h3><h6>" . ($num_results ? $num_results : "No") . " KPI" . ($num_results == 1 ? "" : "s") . " Added</h6>";
            if ($num_results)
                $str .= "<table class=\"grid\"><tr><th>Date</th><th>Title</th><th>Assessor</th><th>State</th><th>Score</th><th>Results</th></tr>";
            while ($myrow = $result->fetch_assoc()) {
                $ccid = $myrow['id'];
                $subject = $myrow['subject'];
                $subject_id = $myrow['subject_id'];
                $assessor = $myrow['assessor'];
                $title = $myrow['title'];
                $assessor_id = $myrow['assessor_id'];
                $employee_id = $myrow['employee_id'];
                $state_name = $myrow['state_name'];
                $check_date = Date("d-M-Y", strtotime($myrow['check_date_time']));
                $out_of = $myrow['total_out_of'];
                $sql = "select (sum(value)/$out_of)*100 as `score` from compliance_check_answers where compliance_check_id = $ccid";
                $result2 = $this->dbi->query($sql);
                if ($myrow2 = $result2->fetch_assoc()) {
                    $score = round($myrow2['score']) . "%";
                }
                if ($myrow2['score']) {
                    $str .= "<tr><td>$check_date</td><td>$title</td>
          <td>$assessor</td>
          <td>$state_name</td><td>$score</td><td><a class=\"list_a\" href=\"{$main_folder}?report_view_id=$ccid\">View</a></td></tr>";
                }
            }
            $str .= "</table>";
        }
        return $str;
    }

    function Qry() {
        $main_folder = $this->f3->get('main_folder');
        $action = (isset($_POST['hdnPerformAction']) ? $_POST['hdnPerformAction'] : null);
        $qry = (isset($_POST['txtQry']) ? $_POST['txtQry'] : null);
        $table_select = (isset($_GET['table_select']) ? $_GET['table_select'] : null);
        if ($table_select && !$qry) {
            $qry = "select * from $table_select";
        }
        $str .= '
      <script>
        function perform_action(action) {
          if(document.getElementById("txtQry").value) {
            document.getElementById("hdnPerformAction").value = action
            document.frmEdit.submit()
          }
        }
        function add_text(text) {
            var txtarea = document.getElementById("txtQry");
            var scrollPos = txtarea.scrollTop;
            var caretPos = txtarea.selectionStart;
            var front = (txtarea.value).substring(0, caretPos);
            var back = (txtarea.value).substring(txtarea.selectionEnd, txtarea.value.length);
            txtarea.value = front + text + back;
            caretPos = caretPos + text.length;
            txtarea.selectionStart = caretPos;
            txtarea.selectionEnd = caretPos;
            txtarea.focus();
            txtarea.scrollTop = scrollPos;
        }
        function clear_text() {
          document.getElementById("txtQry").value = ""
        }
      </script>
      <input type="hidden" name="hdnPerformAction" id="hdnPerformAction" />
      <h3>Query</h3>
      <textarea name="txtQry" id="txtQry" style="width: 98%; height: 200px;">' . $qry . '</textarea>
      <input type="button" onClick=perform_action("QRY") value="Execute Query"><input  onClick=perform_action("XL") type="button" value="Download Excel">
    ';
        if ($table_select) {
            $str .= '<p><a class="list_a" href="' . $this->f3->get('main_folder') . 'Page/Qry">&lt;&lt; Start Again</a></p>';
        }
        if ($action) {
            
        } else {
            $qry = ($table_select ?
                    "
          SELECT concat('<a class=\"list_a\" href=\"JavaScript:add_text(\'', COLUMN_NAME, '\')\">', COLUMN_NAME, '</a>') as `Field` FROM INFORMATION_SCHEMA.COLUMNS
          WHERE COLUMNS.TABLE_NAME = '$table_select' GROUP BY COLUMN_NAME
          order by ORDINAL_POSITION
        " :
                    "
          SELECT CONCAT('<a href=\"{$main_folder}Page/Qry?table_select=', TABLE_NAME, '\">', TABLE_NAME, '</a>') as `Table`, TABLE_TYPE as `Type`, CONCAT('<a href=\"{$main_folder}Page/Qry?table_select=', TABLE_NAME, '\">', TABLE_NAME, '</a>') as `Table`
          FROM INFORMATION_SCHEMA.TABLES WHERE (TABLE_SCHEMA LIKE 'eroster' or TABLE_SCHEMA LIKE 'Edge')
          GROUP BY TABLE_NAME
          order by TABLE_TYPE, TABLE_NAME;
        "
                    );
        }
        if (preg_match('/SELECT/', strtoupper($qry)) != 0) {
            $disAllow = array(
                'INSERT', 'UPDATE', 'DELETE', 'RENAME', 'DROP', 'CREATE', 'TRUNCATE', 'ALTER', 'COMMIT', 'ROLLBACK', 'MERGE', 'CALL', 'EXPLAIN', 'LOCK', 'GRANT', 'REVOKE', 'SAVEPOINT', 'TRANSACTION', 'SET', 'EMAIL_Q', 'APPRAISAL',
            );
            $disAllow = implode('|', $disAllow);
            if (preg_match('/(' . $disAllow . ')/', strtoupper($qry)) == 0) {
                $this->list_obj->sql = $qry;
                $func = ($action == "XL" ? "sql_xl" : "draw_list");
                $str .= $this->list_obj->$func();
            }
        }
        return $str;
    }

    function WfcReport() {
        $main_folder = $this->f3->get('main_folder');
        $show_pos = (isset($_GET['show_pos']) ? $_GET['show_pos'] : null);
        $by_staff = (isset($_GET['by_staff']) ? $_GET['by_staff'] : null);
        $by_site = (isset($_GET['by_site']) ? $_GET['by_site'] : null);
        if ($show_pos) {
            $str .= "<h3>Map</h3>";
        } else {
            $nav = new navbar;
            $hdnReportFilter = $_REQUEST['hdnReportFilter'];
            if ($hdnReportFilter) {
                $month_select = $_REQUEST["selDateMonth"];
                $year_select = $_REQUEST["selDateYear"];
                if ($month_select > 0) {
                    $filter_string .= " and MONTH(wfc.date_time) = $month_select ";
                }
                if ($year_select > 0) {
                    $filter_string .= " and YEAR(wfc.date_time) = $year_select ";
                }
            }
            $str .= '
        <script language="JavaScript">
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        </form>
        <form method="GET" name="frmFilter">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <div class="form-wrapper">
        <div class="form-header">Filter</div>
        <div  style="padding: 10px;">
        ' . $nav->month_year(2014) . ' 
        <input onClick="report_filter()" type="button" value="Go" /> 
        </div>
        </div>
        </form>
      ';
        }
        $current_qry = $_SERVER['QUERY_STRING'];
        if ($hdnReportFilter || $show_pos) {
            if ($show_pos)
                $filter_string = " and wfc.id = $show_pos";
            if ($by_site)
                $filter_string .= " and wfc.site_id = $by_site";
            if ($by_staff)
                $filter_string .= " and wfc.employee_id = $by_staff";
            $view_details = new data_list;

            $view_details->dbi = $this->dbi;
            $view_details->title = ($show_pos ? "" : "Clock Editor");
            $view_details->show_num_records = ($show_pos ? 0 : 1);
//            CONCAT('<a href=\"{$main_folder}Page/WfcReport?show_pos=', wfc.id, '\">Show Position</a>') as `Show Position`") . "
            $view_details->sql = "
            select wfc.id as idin, wfc.date_time as `Date/Time`, CONCAT('<a href=\"{$main_folder}Page/WfcReport?hdnReportFilter=1&by_site=', users2.id, '\">', users2.`name`, ' ', users2.surname, '</a>') as `Site`, CONCAT('<a href=\"{$main_folder}Page/WfcReport?hdnReportFilter=1&by_staff=', users.id, '\">', users.`name`, ' ', users.surname, '</a>') as `Employee`, wfc.latitude as `Latitude`, wfc.longitude as `Longitude`,
            wfc.staff_comment as `Comment` " . ($show_pos ? "" : ",
            CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"https://www.google.com.au/maps/place/', wfc.latitude, ',', wfc.longitude, '\">Show Position</a>') as `Show Position`") . "
            FROM wfc
            left join users on users.id = wfc.employee_id
            left join users2 on users2.id = wfc.site_id
            where 1 $filter_string
            order by date_time desc
    	";
            //return "<textarea>{$view_details->sql}</textarea>";
            $str .= $view_details->draw_list();
        }
        if ($show_pos) {
            $sql = "select latitude, longitude from wfc where id = " . $_GET['show_pos'] . ";";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $latitude = $myrow['latitude'];
                $longitude = $myrow['longitude'];
//         $str .= '<p><img src="http://maps.googleapis.com/maps/api/staticmap?key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo&center='.$latitude.','.$longitude.'&zoom=16&size=640x640&sensor=false&maptype=hybrid&markers=color:blue%7Clabel:S%7C'.$latitude.','.$longitude.'" /></p>';
                $this->redirect('https://www.google.com.au/maps/place/' . $latitude . ',' . $longitude);
            }
        }
        $str .= '
    <script language="JavaScript">
//      change_selDate()
    </script>';
        return $str;
    }

    function ATM() {
        $main_folder = $this->f3->get('main_folder');

        $address = "3/75 Elizabeth Street Sydney NSW 2000";
        $address = str_replace(" ", "+", urlencode($address));
        //$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";
        $details_url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $address . "&sensor=false&key=AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo";
//AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $details_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch), true);

        // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
        if ($response['status'] != 'OK') {
//    return null;
        }

        //prd("mm".$response['results'][0]['geometry']['location']['lat']);

        $geometry = $response['results'][0]['geometry'];
        $latitude = $geometry['location']['lat'];
        $longitude = $geometry['location']['lng'];
        echo "test: " . $latitude . ", " . $longitude;

        exit;
        $action = (isset($_GET['action']) ? $_GET['action'] : null);
        if ($action == 'report') {
            $site_name = "ATM - FLR";
            $check_name = "FLR/FLM Quick Site Survey - ATM";

            //Compliance?report_id=201&site_id=12591

            $txtFind = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
            $str .= '<div class="fl">
      <div class="form-wrapper" style="width: 725px;">
      <div class="form-header">Find ATM</div>
      <div class="form-content">';
            $str .= ($user_id ? '<a class="list_a" href="ATM">Start Again</a>' : '<p><input placeholder="Enter ATM ID, Client Code or Location" maxlength="70" name="txtFind" id="search" type="text" class="search_box" value="' . $_REQUEST['txtFind'] . '" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg . '</p>'
                    );
            $str .= '<div class="cl"></div></div>';
            if ($txtFind) {
                $sql = "select id from users where name = '$site_name';";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $site_id = $myrow['id'];
                    }
                }
                $sql = "select id from compliance where title = '$check_name';";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $compliance_id = $myrow['id'];
                    }
                }

                $search = $txtFind;
                $search = "
          where (atm_id LIKE '%$search%'
          or client_code LIKE '$search'
          or location LIKE '$search'
          or address LIKE '%$search%')
          ";
                $this->list_obj->show_num_records = 1;
                $this->list_obj->sql = "
                select client_code as `Client Code`, atm_id as `ATM ID`, location as `Location`, address as `Address`, suburb as `Suburb`, state as `State`, postcode as `Postcode`, business_hours as `Business Hours`, CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Start Site Survey\" href=\"{$main_folder}Compliance?report_id=$compliance_id&site_id=$site_id&prefill=', atm_id, '\">Start Site Survey</a>') as `Start Site Survey`, CONCAT('<a class=\"list_a\" uk-tooltip=\"title: Current Surveys for this ATM\" href=\"{$main_folder}Compliance?hdnReportFilter=1&report_id=$compliance_id&selDateMonth=-1&selDateYear=-1&chkPending=522&chkCompleted=524&txtComplianceFilter=', atm_id, '\">Current Surveys</a>') as `Current Surveys`
                from atms
                $search";

                $str .= "</div></div><div class=\"cl\"></div>" . $this->list_obj->draw_list();
            }
            $str .= "</div></div>";
            $str .= "<div class=\"cl\"></div>";
        } else if ($action == 'process') {
            $str .= '<h3 class="fl">Upload ATMs</h3>
            <div class="fr"></div>
            <div class="cl"></div>
            </form>
            <form method="post" action="ATM?action=process" enctype="multipart/form-data">
            <br /><br />
            <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
            <input type="hidden" name="compliance_id" value="' . $compliance_id . '" />
            <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
            </form>
      ';
            if ($_FILES["thefile"]["error"] > 0) {
                $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
            } else if ($_FILES["thefile"]["name"]) {
                $states = [];
                $states['ACT'] = 1;
                $states['NSW'] = 2;
                $states['NT'] = 3;
                $states['QLD'] = 4;
                $states['SA'] = 5;
                $states['TAS'] = 6;
                $states['VIC'] = 7;
                $states['WA'] = 8;
                $sql = "truncate atms;";
                $result = $this->dbi->query($sql);
                require_once('app/controllers/PHPExcel.php');

                move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]));
                $inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
                $excelReader = PHPExcel_IOFactory::createReaderForFile($inputFileName);
                try {
                    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objPHPExcel = $objReader->load($inputFileName);
                } catch (Exception $e) {
                    die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                            . '": ' . $e->getMessage());
                }
                $sheet = $objPHPExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $sql = "";
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sql = "insert into atms (client_code, atm_id, location, address, suburb, state, postcode, business_hours) values (";
                    $col = 1;
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                    foreach ($rowData[0] as $k => $v) {
                        $v = mysqli_real_escape_string($this->dbi, $v);
                        $sql .= "'$v'";
                        $sql .= ($col == 8 ? ");" : ", ");
                        $col++;
                    }
                    $this->dbi->query($sql);

                    $str .= "<h3>$sql</h3>";
                }
                if ($row > 2) {
                    $str .= "<h3>ATMs Added...</h3>";
                }
            }
        }

        return $str;
    }

    function UploadEntities() {
        $main_folder = $this->f3->get('main_folder');

        /* Fix for first name middle name problem. */


        $str .= '
    <h3>Upload Entities</h3>
    </form>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
    <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
    </form>
    ';

        /*
          Emp ID
          Client/Site ID
          Supplier ID
          Given Name
          Surname
          Preferred Name
          Email 1
          Email 2
          Phone 1
          Phone 2
          Address
          Suburb
          State
          Postcode
          DOB
         */


        if ($_FILES["thefile"]["error"] > 0) {
            $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
        } else if ($_FILES["thefile"]["name"]) {
            $states = [];
            $states['ACT'] = 1;
            $states['NSW'] = 2;
            $states['NT'] = 3;
            $states['QLD'] = 4;
            $states['SA'] = 5;
            $states['TAS'] = 6;
            $states['VIC'] = 7;
            $states['WA'] = 8;
            require_once('app/controllers/PHPExcel.php');
            $base_dir = $this->f3->get('download_folder');
            $dl = "/excel_templates";
            move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]));
            $inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
            $excelReader = PHPExcel_IOFactory::createReaderForFile($inputFileName);
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch (Exception $e) {
                die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                        . '": ' . $e->getMessage());
            }
            $date_format = 'd/m/Y';
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            //$highestColumn = "O";
            $field_list = array("employee_id", "client_id", "supplier_id", "name", "surname", "preferred_name", "email", "email2", "phone", "phone2", "address", "suburb", "state", "postcode", "dob");

            $groups = Array();
            $group_ids = Array();
            $rowData = $sheet->rangeToArray('P1:' . $highestColumn . "1", NULL, TRUE, FALSE);
            $num_lookups = 0;
            foreach ($rowData[0] as $k => $v) {

                $v = trim($v);
                $groups[] = $v;
                $sql = "select id from lookup_fields where lookup_id = 21 and item_name = '$v'";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $num_lookups++;
                        $group_ids[] = $myrow['id'];
                    }
                }
            }
            /* foreach ($group_ids as $test) {
              $str .= "<h3>{$test}</h3>";
              }
              return $str; */
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                $update_str = "";
                $empty_str = 1;
                $group_sql = "";
                foreach ($rowData[0] as $k => $v) {
                    $v = mysqli_real_escape_string($this->dbi, trim($v));
                    if ($k < 3) {
                        if ($k == 0 && $v)
                            $employee_id = $v;
                        if ($k == 1 && $v)
                            $client_id = $v;
                        if ($k == 2 && $v)
                            $supplier_id = $v;
                        if ($k == 2) {

                            $sql = "select id from users where " . ($employee_id ? "employee_id = '$employee_id'" : ($client_id ? "client_id = '$client_id'" : ($supplier_id ? "supplier_id = '$supplier_id'" : "")));

                            if ($result = $this->dbi->query($sql)) {
                                if ($myrow = $result->fetch_assoc()) {
                                    $user_id = $myrow['id'];
                                    $group_sql .= "delete from `lookup_answers` where foreign_id = $user_id and table_assoc = 'users';";
                                }
                            }


                            /* $user_id = $this->get_sql_result("select id as `result` from users where " . ($employee_id ? "employee_id = '$employee_id'" : ($client_id ? "client_id = '$client_id'" : ($supplier_id ? "supplier_id = '$supplier_id'" : ""))));

                              if($user_id) $group_sql .= "delete from `lookup_answers` where foreign_id = $user_id and table_assoc = 'users';<br /><br />"; */
                        }
                    } else if ($k < 15) {
                        if ($k == 3)
                            $name = $v;
                        if ($k == 12)
                            $v = $states[$v];
                        //if($k != 10) {
                        $update_str .= ($field_list[$k] == 'email' && !$v ? "" : $field_list[$k] . " = '$v'");
                        if ($k < 14) {
                            $update_str .= ($field_list[$k] == 'email' && !$v ? "" : ",");
                        }
                        if ($v)
                            $empty_str = 0;
                    } else {
                        if ($v) {
                            $group_sql .= "insert ignore into `lookup_answers` (foreign_id, lookup_field_id, table_assoc) values ($user_id, " . $group_ids[$k - 15] . ", 'users');";
                        } else {
                            
                        }
                    }
                    if ($k == 14 + $num_lookups) {
                        $employee_id = 0;
                        $client_id = 0;
                        $supplier_id = 0;
                    }
                }
                if ($user_id && $name) {
                    $sqls .= "update users set $update_str where id = $user_id; $group_sql";
                }
            }

            $str .= $this->message('Items Updated...', 2000);

            //return $sqls;
            $this->dbi->multi_query($sqls);
            //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "2", $objPHPExcel->getActiveSheet()->getCell($col_from . "12")->getValue());
            //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "1", $objPHPExcel->getActiveSheet()->getCell($col_from . "11")->getValue());
            //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "3", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($col_from . "13")->getValue())));
        }
        $str .= '
      <input type="hidden" id="hdnUpdateEdge" name="hdnUpdateEdge" />
      <input type="hidden" name="lookup_id" value="' . $lookup_id . '">
    ';
        return $str;
    }

    function FixTable() {
        $main_folder = $this->f3->get('main_folder');
        $table_name = (isset($_GET['table_name']) ? $_GET['table_name'] : null);
        if ($table_name) {
            $sql = "select id from $table_name order by id;";
            $result = $this->dbi->query($sql);
            $x = 0;
            while ($myrow = $result->fetch_assoc()) {
                $x++;
                $id = $myrow['id'];
                if ($id != $x) {
                    $sql .= "update $table_name set id = $x where id = $id;";
                }
            }
            $str = "<textarea>$sql</textarea>";
            //$dbi->multi_query($sql);
            //$x++;
            //$sql = "ALTER TABLE $table_name AUTO_INCREMENT = $x";
            //$result = $dbi->query($sql);
        }
        $tables = [];
        $tables[0] = "lookup_answers";
        $tables[1] = "usermeta";
        $tables[2] = "compliance_question_choices";
        $tables[3] = "associations";
        foreach ($tables as $table) {
            $str .= '<p><a href="FixTable?table_name=' . $table . '">' . $table . '</a></p>';
        }
        return $str;
    }

    function AllWhiteboards() {
        $main_folder = $this->f3->get('main_folder');

        $division_id = $this->division_id;
        if ($division_id == 'ALL')
            $division_id = 0;

        $itm = new input_item;
        $itm->hide_filter = 1;

        $chlUserGroups = (isset($_POST["chlUserGroups"]) ? $_POST["chlUserGroups"] : null);
        $txtNotes = (isset($_POST['txtNotes']) ? $this->dbi->real_escape_string($_POST['txtNotes']) : null);
        $txtNotesSend = (isset($_POST['txtNotes']) ? $_POST['txtNotes'] : null);

        if ($chlUserGroups) {
            $group_ids = "";
            $group_names = "";
            foreach ($chlUserGroups as $group_id) {
                $group_ids .= ($group_ids ? "," : "") . $group_id;
                $group_name = $this->get_sql_result("select item_name as `result` from lookup_fields where id = $group_id");
                $group_names .= ($group_names ? ", " : "") . $group_name;
            }
        }

        if ($txtNotes) {
            $redirectDivision = $_REQUEST["division_id"];
              $str .= "<h3><a href='/Page/AllWhiteboards?division_id=$redirectDivision'>Back</a></h3>";
            $uid = $_SESSION['user_id'];

            $this->dbi->query("insert into site_notes (user_id, division_id, date, description) values ($uid, '$division_id', now(), '$txtNotes')");

            $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3` from users
      where users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
      " . ($division_id ? " and users.id in (select user_id from users_user_division_groups where user_group_id = $division_id) " : "")
                    . ($group_ids ? " and users.id in (select user_id from users_user_groups where user_group_id in ($group_ids)) " : "");
            //return $this->ta($sql);

            $div_str = $this->get_divisions($_SESSION['user_id'], 1);
            if ($div_str)
                $div_str = " ($div_str) ";
            $result = $this->dbi->query($sql);
            $mail = new email_q($this->dbi);
            $x = 0;
            $y = 0;
            while ($myrow = $result->fetch_assoc()) {
                $name = $myrow['name'];
                $user_id = $myrow['user_id'];
                $email = trim($myrow['email']);
                $email2 = trim($myrow['email2']);
                $email3 = trim($myrow['email3']);
                //$mail->AddBCC("eggaweb@gmail.com");

                $mail->Subject = "Allied Edge Comment on Whiteboard";
                $mail_msg = "Hello $name,<br /><br />The following message was added by " . $_SESSION['name'] . $div_str . " to the Whiteboard:<br /><br />$txtNotesSend<br /><br />Regards,<br /><a href=\"" . $this->f3->get('full_url') . "\">" . $this->f3->get('software_name') . "</a>";
//echo $mail_msg;
//die;
                $mail->Body = $mail_msg;

                $ok = 0;
                if ($email) {
                    if ($this->validate_email($email)) {
                        $x++;
                        $mail->AddAddress($email);
                        $working .= "$name - $email<br/>";
                        $ok = 1;
                    } else {
                        $y++;
                        $not_working .= "<a href=\"{$main_folder}UserCard?uid=$user_id\">$name</a> - $email<br/>";
                    }
                }
                if ($email2) {
                    if ($this->validate_email($email2)) {
                        $x++;
                        $mail->AddAddress($email2);
                        $working .= "$name - $email2<br/>";
                        $ok = 1;
                    } else {
                        $y++;
                        $not_working .= "<a href=\"{$main_folder}UserCard?uid=$user_id\"$name</a> - $email2<br/>";
                    }
                }
                if ($email3) {
                    if ($this->validate_email($email3)) {
                        $x++;
                        $mail->AddAddress($email3);
                        $working .= "$name - $email3<br/>";
                        $ok = 1;
                    } else {
                        $y++;
                        $not_working .= "<a href=\"{$main_folder}UserCard?uid=$user_id\"$name</a> - $email3<br/>";
                    }
                }

                if ($email == 'edgar@alliedmanagement.com.au') {
                    //$mail->send();
                    $str .= "<p>$mail_msg</p>";
                }
                if ($ok) {
                    $mail->queue_message();
                    $mail->clear_all();
                }

                //return $mail_msg;
            }
            $str .= "<table class=\"grid\"><tr><th>Working ($x)</td><th>Not Working ($y)</th></tr>";
            $str .= "<tr><td>$working</td><td>$not_working</td></tr></table>";
        }
        if($txtNotes){
            $message = ($group_ids ? "Message Sent/Posted to $x users of $group_names" : "Message Posted");
             
            $str .= $this->message($message, 2000);
            $str .= "<h6>$message</h6>";
            
         
        } else {




            //chl($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "")
           // $sql_user_groups = "select id, item_name from user_groups where item_name like '%Staff%' or item_name like '%Partner%' or item_name like '%Supervisor%' or item_name = 'IT' or item_name = 'HR' or item_name = 'Accounts' or item_name = 'BD' or item_name LIKE '%Client%' or item_name LIKE '%Site%'";
            $sql_user_groups = "select id, item_name from user_groups";

            $str .= '<h3 class="fl" style="padding-right: 30px;">Company Wide Whiteboard Entries</h3>';
            $str .= '<div class="fl">' . $this->division_nav($division_id, 'Page/AllWhiteboards', 0, 0) . '</div>';
            $str .= '<div class="cl"></div>';
            $str .= '<textarea class="textbox note_textbox" placeholder="Enter Notes Here..." name="txtNotes" id="txtNotes" class=""></textarea>';

            $str .= '<div class="fl"><h5>To Send an Email, Select One or More User Groups</h5>';
            $str .= $itm->chl($field_id = "chlUserGroups", "", "", "", $this->dbi, $sql_user_groups, "") . '</div>';

            $str .= '<div class="fr"><input type="button" onClick="add_note()" value="Post" /></div><div class="cl"></div>';

            $this->list_obj->title = "<br/>Previous Messages";
            $this->list_obj->sql = "select users.name as `Entered By`, if(site_notes.division_id = '', 'ALL', companies.item_name) as `Division`, date as `Date Added`, description as `Message` from site_notes
    left join users on users.id = site_notes.user_id
    left join companies on companies.id = site_notes.division_id
    where site_id = 0
    order by date DESC";
            $str .= $this->list_obj->draw_list();
            $str .= '
    <script>
      function add_note() {
        if(document.getElementById("txtNotes").value) {
          document.frmEdit.submit()
        } else {
          alert("Please enter a note to send first.")
        }
      }
     //document.getElementById("chlUserGroups107").checked = true
    </script>
    <input type="hidden" id="hdnSetMatrix" name="hdnSetMatrix" />
    
    ';
}
            return $str;
        
    }

}

?>