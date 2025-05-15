<?php

class RosteringController extends Controller {

    protected $f3;
    var $public_holidays;

    function __construct($f3) {
        $this->download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $this->f3 = $f3;
        $this->list_obj = new data_list;
        $this->editor_obj = new data_editor;
        $this->db_init();
        $this->rid = (isset($_GET['rid']) ? $_GET['rid'] : 0);
        $this->sid = (isset($_GET['sid']) ? $_GET['sid'] : 0);
        $div_id = $_COOKIE["RosteringDivisionId"];
        $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
        if ($this->division_id)
            setcookie("RosteringDivisionId", $this->division_id, 2147483647);
        //$this->days = Array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        //$this->short_days = Array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    }

    function roster_details($rid, $type) {
        $sid = $this->sid;
        $division_id = $this->division_id;

        $sql['edit'] = "    '<a data-uk-tooltip title=\"View Mode\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ViewRoster?rid=', rosters.id, '" . ($sid ? "&sid=$sid" : "") . "\"><i class=\"fa fa-eye\" ></i></a> <a data-uk-tooltip title=\"Edit Roster Times<br/>(and unpaid minutes)\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRosterTimes?rid=', rosters.id, '\"><i class=\"fa fa-clock\" ></i></a> '
                      , if(rosters.shift_template_id != 0, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/GenerateRoster?regid=', rosters.id, '\"><i class=\"fa fa-retweet\" ></i></a>'), '')
                      , '<a data-uk-tooltip title=\"Copy to Another Week\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/CopyRoster?copyid=', rosters.id, '\"><i class=\"fa fa-clone\" ></i></a>'

    ";

        $sql['copy'] = "  '<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\"><i class=\"fa fa-user\"></i></a><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ViewRoster?rid=', rosters.id, '" . ($sid ? "&sid=$sid" : "") . "\"><i class=\"fa fa-eye\" ></i></a>'";
        $sql['times'] = $sql['copy'];
        $sql['view'] = "  '<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\"><i class=\"fa fa-user\"></i></a>'";
        $this->list_obj->show_num_records = 0;
        $this->list_obj->sql = "select distinct(rosters.id) as idin,

    companies.item_name as `Division`, CONCAT(users.name, ' ', users.surname) as `Site`, if(rosters.shift_template_id, ros_shift_templates.name, 'N/A') as `Template`
    " . ($type == 'view' || $type == 'edit' ? ", CONCAT('<a class=\"list_a\" href=\"" . ($type == 'view' ? "View" : "Edit") . "Roster?rid=', (select id from rosters where site_id = users.id and id < '$rid' and division_id = '$division_id' order by id DESC limit 1), '\">&lt;</a>') as `&nbsp;`" : '') . "
    , start_date as `Start Date`
    " . ($type == 'view' || $type == 'edit' ? ", CONCAT('<a class=\"list_a\" href=\"" . ($type == 'view' ? "View" : "Edit") . "Roster?rid=', (select id from rosters where site_id = users.id and id > '$rid' and division_id = '$division_id' order by id limit 1), '\">&gt;</a>') as `&nbsp&nbsp;`" : '') . "

    , CONCAT(" . $sql[$type] . ($sid ? '\'<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/EditRoster?rid=\', rosters.id, \'">Open Full Roster</a>\'' : "") . ") as `Roster Management`
    , CONCAT(
    if(rosters.requires_licences = 1, 'Licences', '')
    , if(rosters.requires_confirmation = 1 and rosters.requires_licences = 1, ', ', '')
    , if(rosters.requires_confirmation = 1, 'Confirmation', '')
    , if(rosters.requires_induction = 1 and (rosters.requires_licences = 1 || rosters.requires_confirmation = 1), ', ', '')
    , if(rosters.requires_induction = 1, 'Induction', '')
    , if(rosters.requires_confirmation != 1 and rosters.requires_licences != 1 and rosters.requires_induction != 1, 'None', '')

    ) as `Requirements`
    , if(rosters.is_published = 1, '<span style=\"color: #009900;\">Published</span>', if(rosters.is_published = 2, '<span style=\"color: #009900;\">Published/Sent</span>', '<span style=\"color: #AA0000;\">Draft</span>')) as `Mode`
    " . ($type == "edit" ? ", if(rosters.is_published >= 1, CONCAT('<a data-uk-tooltip title=\"Click Here to Change to<br/>DRAFT MODE\" class=\"list_a\" href=\"EditRoster?rid=$rid&publish=2\" style=\"width:auto;padding:6px\">Set Draft</a>'), CONCAT('<a  data-uk-tooltip title=\"Click Here to Change to<br/>PUBLISHED MODE\" class=\"list_a tableWhiteBtn\" href=\"EditRoster?rid=$rid&publish=1\">Set Published</a>')) as `Set`" : "") . "
    FROM rosters
    left join companies on companies.id = rosters.division_id
    left join users on users.id = rosters.site_id
    left join ros_shift_templates on ros_shift_templates.id = rosters.shift_template_id
    left join roster_times on roster_times.roster_id = rosters.id
    where rosters.id = $rid ";
        return $this->list_obj->draw_list();
    }

    function RequiredLicences() {
        $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
        $show_min = (isset($_GET["show_min"]) ? $_GET["show_min"] : null);
        $division_id = (isset($_GET["division_id"]) ? $_GET["division_id"] : 108);
        $add_id = (isset($_GET["add_id"]) ? $_GET["add_id"] : null);
        $remove_id = (isset($_GET["remove_id"]) ? $_GET["remove_id"] : null);

        if ($add_id && $lookup_id) {
            $sql = "insert into roster_site_licences (site_id, staff_licence_type_id, division_id) values ($lookup_id, $add_id, $division_id)";
            $this->dbi->query($sql);
        }

        if ($remove_id && $lookup_id) {
            $sql = "delete from roster_site_licences where site_id = $lookup_id and staff_licence_type_id = $remove_id and division_id = $division_id";
            $this->dbi->query($sql);
        }

        $str .= "<h3> Required Licences For " . $this->get_sql_result("select name as `result` from users where id = $lookup_id") . "</h3>";

        $str = $this->division_nav($division_id, 'Rostering/RequiredLicences', 0, $lookup_id, 1);

//      echo $this->redirect($this->f3->get('main_folder') . "Rostering/RosterView" . ($lookup_id ? "?lookup_id=$lookup_id" : ""));

        $str .= '<table border="0" cellpadding="8"><tr><td valign="top">';
        $sql = "select id, item_name from licence_types where id not in (select staff_licence_type_id from roster_site_licences where site_id = $lookup_id);";

        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $item_name = $myrow['item_name'];
                $str .= '<br /><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/RequiredLicences?lookup_id=' . $lookup_id . '&add_id=' . $id . ($division_id ? "&division_id=$division_id" : "") . ($show_min ? "&show_min=1" : "") . '">Add</a> ' . $item_name;
            }
        }
        $str .= '</td><td valign="top">';

        $sql = "select id, item_name from licence_types where id in (select staff_licence_type_id from roster_site_licences where site_id = $lookup_id and division_id = $division_id);";

        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $item_name = $myrow['item_name'];
                $str .= '<br /><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/RequiredLicences?lookup_id=' . $lookup_id . '&remove_id=' . $id . ($division_id ? "&division_id=$division_id" : "") . ($show_min ? "&show_min=1" : "") . '">Remove</a> ' . $item_name;
            }
        }

        $str .= '</td></tr>';

        return $str;
    }

    function RosterUsers($roster_time_id = "") {
        $time_date = date('Y-m-d H:i:s');
        $item = (isset($_GET['item']) ? $_GET['item'] : null);
        $staff = (isset($_GET['staff']) ? $_GET['staff'] : null);
        $del = (isset($_GET['del']) ? $_GET['del'] : null);
        $confirm = (isset($_GET['confirm']) ? $_GET['confirm'] : null);
        $comment = (isset($_GET['comment']) ? $this->mesc($_GET['comment']) : null);
        $rid = $this->rid;
        $sid = $this->sid;

        $leaveTypes = $this->getRosterLeaveTypes();

        $licence_type_ids = (isset($_GET['licence_type_ids']) ? $_GET['licence_type_ids'] : null);

        $view_mode = (isset($_GET['view_mode']) ? $_GET['view_mode'] : null);
        $by_day_mode = (isset($_GET['by_day_mode']) ? $_GET['by_day_mode'] : null);
        $my_ros_mode = (isset($_GET['my_ros_mode']) ? $_GET['my_ros_mode'] : null);
        $roster_times_staff_id = (isset($_GET['roster_times_staff_id']) ? $_GET['roster_times_staff_id'] : null);

        $use_return = ($item || $del || $confirm ? 0 : 1);

        if ($item == 'ALL')
            $item = 0;

        $roster_time_id = ($item ? substr($item, 10) : (isset($_GET['roster_time_id']) ? $_GET['roster_time_id'] : $roster_time_id));

        if ($my_ros_mode) {
            session_start();
            $my_id = $_SESSION['user_id'];
            $itm = new input_item;
            $itm->hide_filter = 1;
        }

        if ($staff) {
            $user_id = $this->emp_id_to_staff_id(strtok($staff, " "));
            //echo "uid: $user_id";

            $sql = "select roster_id, start_time_date, finish_time_date, SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(start_time_date, finish_time_date)) / 3600, 2))) as `add_hours` from roster_times where id = $roster_time_id";
            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {
                    $roster_id = $myrow['roster_id'];
                    $start_time_date = $myrow['start_time_date'];
                    $finish_time_date = $myrow['finish_time_date'];
                    $add_hours = (int) $myrow['add_hours'];
                }
            }
            $msg = "";
            $sql = "select meta_value as `result` from usermeta where meta_key = (select id from lookups where item_name = 'roster_fixed_hours') and user_id = (select site_id from rosters where id = $roster_id)";
            $fixed_hours = (int) $this->get_sql_result($sql);
            if ($fixed_hours) {
                $current_hours = $this->get_sql_result("select SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))) as `result` from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        where roster_times.roster_id = $roster_id");
                //echo $fixed_hours;
                $add_hours += $current_hours;
                if ($add_hours > $fixed_hours) {
                    $msg = "<p>The maximum fixed hours of this site has been reached, the staff member has not been added...</p>";
                }
            }

            $sql = "select roster_times.id as `roster_time_id`, rosters.id as `rid`, users.name as `staff`, users2.name as `site`
      from roster_times_staff
      left join roster_times on roster_times.id = roster_times_staff.roster_time_id
      left join rosters on rosters.id = roster_times.roster_id
      left join users2 on users2.id = rosters.site_id
      left join users on users.id = roster_times_staff.staff_id
      where rosters.id != $roster_id and users.id = $user_id
      and
      ((roster_times.start_time_date BETWEEN '$start_time_date' AND '$finish_time_date')
      or
      (roster_times.finish_time_date BETWEEN '$start_time_date' AND '$finish_time_date'))
      ";
            //echo $sql;
            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {
                    $ridin = $myrow['rid'];
                    //$roster_time_id = $myrow['roster_time_id'];
                    $staff_name = $myrow['staff'];
                    $site = $myrow['site'];
                    $msg .= "<p>$staff_name<br />has already been booked to<br />$site.<br /><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=$ridin\">Open Conflicting Roster</a> &nbsp; </p>";
                }
            }
            if (!$msg) {
                $banned = $this->get_sql_result("select id as `result` from associations where child_user_id = $user_id and parent_user_id = (select site_id from rosters where id = $roster_id) and association_type_id = (select id from association_types where name = 'site_banned_staff')");
                if ($banned) {
                    $staff_name = $this->get_sql_result("select CONCAT(employee_id, ' - ', name, ' ', surname) as `result` from users where id = $user_id");
                    $msg = "$staff_name has been banned from this site. ";
                }
            }


            if (!$msg) {
                $sql = "select roster_site_licences.staff_licence_type_id
        from rosters
        left join users on users.id = rosters.site_id
        inner join roster_site_licences on roster_site_licences.site_id = rosters.site_id and roster_site_licences.division_id = rosters.division_id
        where rosters.id = $rid and rosters.requires_licences = 1";
                if ($result = $this->dbi->query($sql)) {
                    $licence_type_ids = "";
                    while ($myrow = $result->fetch_assoc()) {
                        $licence_type_ids .= ($licence_type_ids ? "," : "");
                        $licence_type_ids .= $myrow['staff_licence_type_id'];
                    }
                }
                if ($licence_type_ids) {
                    $num_licences = $this->get_sql_result("select count(licence_type_id) as `result` from licences where user_id = $user_id and verified_by != 0 and deactivated = 0 and licence_type_id in ($licence_type_ids)");
                    $ids = explode(",", $licence_type_ids);
                    if (count($ids) != $num_licences) {
                        $msg .= "<p>This staff member does not have the required licences for this site.</p>";
                    }
                }
            }
            // Step 1: Check if the user has any incomplete training
            if ($this->hasIncompleteTraining($user_id)) {
                // Add an error message if the user has incomplete training
                $msg .= "<p>This staff member has incomplete required training and cannot be rostered.</p>";
            }

            if (!$msg) {


//                $status = ($this->get_sql_result("select rosters.requires_confirmation as `result` from roster_times_staff
//        inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
//        inner join rosters on rosters.id = roster_times.roster_id
//        where roster_times_staff.roster_time_id = $roster_time_id
//        group by rosters.id") ? 1 : 2);

                /* above commented and below default set 2 */
                $status = 1;

                $sql = "insert ignore into roster_times_staff (staff_id, roster_time_id, status) values ($user_id, $roster_time_id, $status)";
                $this->dbi->query($sql);
                $roster_time_staff_id = $this->dbi->insert_id;
                $this->sendRosterAlterMessage($user_id, "added", $roster_time_staff_id);
            }
        }
        if ($del) {
            // die("hello");
            $user_id = $this->emp_id_to_staff_id(strtok($staff, " "));
            $this->sendRosterAlterMessage($user_id, "deleted", $del);
            //die("test1");
            $sql = "delete from roster_times_staff where id = $del";
            $this->dbi->query($sql);
        }

        /* echo '
          <script>
          function my_status(confirm, roster_times_staff_id, roster_times_id) {
          v = document.getElementById(\'txaComment\' + roster_times_staff_id)
          //alert(confirm)
          if((confirm == 1 || confirm == 3) && v.value == "") {
          alert("Please enter a comment before " + (confirm == 3 ? "cancelling your shift" : "undoing the cancellation of your shift."))
          } else {
          staff_status(confirm, roster_times_staff_id, roster_times_id, document.getElementById(\'txaComment\' + roster_times_staff_id).value);
          }
          }
          function save_comment(roster_times_staff_id, roster_times_id) {
          //staff_comment(roster_times_staff_id, roster_times_id, document.getElementById(\'txaComment\' + roster_times_staff_id).value);
          }
          </script>
          '; */

        $shift_status[1] = "Pending";
        $shift_status[2] = "Confirmed";
        $shift_status[3] = "Cancelled";
        $shift_status[4] = "On Site";
        $shift_status[5] = "Shift Completed";
        if ($confirm || $comment) {
            if ($confirm && $confirm != -1) {

                if ($confirm == 3) {

                    $sql = "SELECT TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date,NOW())),roster_times.start_time_date,roster_times_staff.id
FROM roster_times left JOIN roster_times_staff on roster_times_staff.roster_time_id = roster_times.id
WHERE roster_times_staff.id = $roster_times_staff_id and TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date,NOW())) > 21600";

                    $resultCancel = $this->dbi->query($sql);

//                        if($roster_times_staff_id == 40200){
//                            pr($sql);
//                            pr($resultCancel);
//                            die;
//                         }   



                    if ($resultCancel->num_rows != 0) {
                        $this->sendRosterAlterMessage($user_id, "cancelled", $roster_times_staff_id);
                    } else {
                        echo "not_cancel";
                        die;
                    }
                }

                if ($confirm == 2) {
                    $this->sendRosterAlterMessage($user_id, "confirmed", $roster_times_staff_id);
                } elseif ($confirm == 1) {
                    $this->sendRosterAlterMessage($user_id, "pending", $roster_times_staff_id);
                }


                $sql = "update roster_times_staff set status = $confirm where id = $roster_times_staff_id;";

                $this->dbi->query($sql);

                /* if($my_ros_mode) {
                  if($this->dbi->affected_rows) {
                  $msg .= "<p>Shift Status<br />Changed to<br />".strtoupper($shift_status[$confirm])."</p>";
                  }
                  } */
            }
            if ($comment) {
                $sql = "update roster_times_staff set staff_comment = '$comment' where id = $roster_times_staff_id;";
                $this->dbi->query($sql);

                if ($my_ros_mode && $confirm == -1) {
                    if ($this->dbi->affected_rows) {
                        $msg .= "<p>Comment Saved...</p>";
                    }
                }
            }
        }
        $sql = "select users.id as `user_id`,roster_times_staff.leave_id,roster_times.id as `roster_times_id`, roster_times.start_time_date, roster_times.finish_time_date, roster_times_staff.id as `roster_times_staff_id`,

        CONCAT('<span data-uk-tooltip title=\"', users.employee_id, ' - ', users.name, ' ', users.surname, '<br />', users.phone, '<br />', users.email, '<br / >',  IFNULL(users.company, 'Allied Staff'),
        '<br />Reference #: ', SUBSTRING_INDEX(SUBSTRING_INDEX(roster_times_staff.staff_comment, 'mrn: ', -1), '<br />', 1),
    
        if(roster_times_staff.start_time_date != '0000-00-00 00:00:00', CONCAT('<p>Started: ', DATE_FORMAT(roster_times_staff.start_time_date, '%d-%b @ %H:%i'), '</p>'), ''),
        if(roster_times_staff.finish_time_date != '0000-00-00 00:00:00', CONCAT('<p>Finished: ', DATE_FORMAT(roster_times_staff.finish_time_date, '%d-%b @ %H:%i'), '</p>'), ''),
    
        if(roster_times_staff.staff_comment != '', CONCAT('<p>Staff Comment:<br />', roster_times_staff.staff_comment, '</p>'), ''),
        if(roster_times_staff.controller_comment != '', CONCAT('<p>Controller Comment:<br />', roster_times_staff.controller_comment, '</p>'), ''), '\">', users.name, ' ', users.surname, '</span>') as `staff`,
        
        CONCAT(users.name, ' ', users.surname) as `staff_raw`, roster_times_staff.status,
        if(roster_times_staff.status != 2, 'Y', if(roster_times.start_time_date < '$time_date', 'N', 'Y')) as `staff_started`
        , roster_times_staff.staff_comment, roster_times_staff.controller_comment, roster_times.roster_id
        from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join users on users.id = roster_times_staff.staff_id
        where roster_times_staff.roster_time_id = $roster_time_id " . ($sid ? " and users.id = $sid " : "") . ($my_ros_mode ? " and users.id = $my_id " : "")
                    . " order by users.name ";
        //0000-00-00 00:00:00
//        '$time_date'
//roster_times.start_time_date
//echo $sql;



        if ($msg)
            $msg .= '<p><a class="list_a" href="JavaScript:hide_roster_message(' . $roster_time_id . ')">Close Message</a></p>';

        if ($result = $this->dbi->query($sql)) {
            $class_str[1] = "shift_pending";
            $class_str[2] = "shift_confirmed";
            $class_str[3] = "shift_cancelled";
            $class_str[4] = "shift_active";
            $class_str[5] = "shift_complete";
            $done = 0;
            while ($myrow = $result->fetch_assoc()) {

                if ($_SESSION['user_id'] == 1) {
//                    prd($sql);
//                    prd($myrow);
//                    die;
                }

                $user_id = $myrow['user_id'];
                $roster_id = $myrow['roster_id'];
                $leave_id = $myrow['leave_id'];
                $roster_times_id = $myrow['roster_times_id'];
                $start_time_date = $myrow['start_time_date'];
                $finish_time_date = $myrow['finish_time_date'];
                $status = $myrow['status'];
                $staff_name = $myrow['staff'];

                $staff_started = $myrow['staff_started'];
                $staff_comment = $myrow['staff_comment'];
                $controller_comment = $myrow['controller_comment'];
                $staff_raw = $myrow['staff_raw'];
                $roster_times_staff_id = $myrow['roster_times_staff_id'];

                $cls = ($status == 2 ? ($staff_started == 'Y' ? $class_str[$status] : "shift_missed") : $class_str[$status]);
//        $cls .= ($my_id == $user_id ? " shift_highlight " : "");
                // $staff_str .= '<div class="rosterGroup">';
                $staff_str .= '<div class="' . ($my_ros_mode ? '' : 'name_item ') . $cls . '">';
                /* if($my_ros_mode) {
                  $staff_str .= "<span class=\"rnm\">$staff_name</span>";
                  } */

                if ($my_ros_mode) {
                    if ($my_id == $user_id) {
                        $staff_str .= '<div class="shift_status">' . strtoupper($shift_status[$status]) . '</div>';
                        $staff_str .= '<div class="shift_status">' . $itm->txa("txaComment$roster_times_staff_id", $staff_comment, ' style="width: 100%; height: 90px;" placeholder="Comment" data-uk-tooltip title="Enter Comment Here"', "", "", "", "") . '</div>';
                        if ($controller_comment)
                            $staff_str .= '<div class="shift_message"><b>Controller Comment</b><br />' . $controller_comment . '</div>';
                        $staff_str .= "</div>";
                        if ($status <= 3) {
                            if ($status == 3) {
                                $staff_str .= '<div class="shift_button shift_undo"><a data-uk-tooltip title="UNDO Change To Cancelled<br />(Please state reason in the Comment Section)" href="Javascript:my_status(1, ' . $roster_times_staff_id . ', ' . $roster_times_id . ')">UNDO CANCEL</a></div>';
                            } else if ($status == 2) {
                                $staff_str .= '<div class="shift_button shift_cancel"><a data-uk-tooltip title="Cancel Shift<br />(Please state reason in the Comment Section)" href="Javascript:my_status(3, ' . $roster_times_staff_id . ', ' . $roster_times_id . ')">CANCEL SHIFT</a></div>';
                            } else {
                                $staff_str .= '<div class="shift_button shift_confirm"><a data-uk-tooltip title="Confirm Shift<br />(and Save Comment)" href="Javascript:my_status(2, ' . $roster_times_staff_id . ', ' . $roster_times_id . ')">CONFIRM SHIFT</a></div>';
                                $staff_str .= '<div class="shift_button shift_cancel"><a data-uk-tooltip title="Cancel Shift<br />(Please state reason in the Comment Section)" href="Javascript:my_status(3, ' . $roster_times_staff_id . ', ' . $roster_times_id . ')">CANCEL SHIFT</a></div>';
                            }
                        }
                        $staff_str .= '<div class="shift_button shift_save_comment"><a data-uk-tooltip title="Save Comment" href="Javascript:my_status(-1, ' . $roster_times_staff_id . ', ' . $roster_times_id . ')">SAVE COMMENT</a></div>';
                    }
                } else {
                    if (!$view_mode && $status <= 3) {
                        //$shift_started = (strtotime($start_time_date) > Time() ? 1 : 0);
                        $staff_str .= '<a class="rosa remvoeB" data-uk-tooltip title="Remove<br />' . $staff_raw . '" href="Javascript:remove_item(' . $roster_times_staff_id . ', ' . $roster_times_id . ')"><i class="fa fa-trash"></i></a>';
                        if ($status != 3) {
                            $staff_str .= '<a class="rosa cutB" data-uk-tooltip title="Change To Cancelled" href="Javascript:staff_status(3, ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')"><i class="fa fa-times"></i></a>';
                        } else {
                            $staff_str .= '<a data-uk-tooltip title="Verify (Confirm) Shift" class="rosa replaceB" href="Javascript:staff_status(2, ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')">V</a>';
                        }

                        $staff_str .= '<a data-uk-tooltip title="' . ($status == 1 ? "Verify (Confirm) Shift" : "Set Shift to Pending (Uncornfirmed)") . '" class="rosa ' . ($status == 1 ? "replaceB" : "pastB") . '" href="Javascript:staff_status(' . ($status == 1 ? 2 : 1) . ', ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')">' . ($status == 1 ? "V" : '<i class="fa fa-check-circle" ></i>') . '</a>';
                    }
                }

                /*        if(!$view_mode && ($status >= 4)) {
                  $staff_str .= '<a class="rosa" data-uk-tooltip title="Adjust Times" href="Javascript:staff_status(3, '.$roster_times_staff_id.', '.$roster_times_id.', \'\')">T</a>&nbsp;';
                  } */

                if (!$my_ros_mode) {
                    /*          $staff_str .=
                      ($by_day_mode  ? "<span class=\"rnm\">$staff_name</span></div>"
                      : "<a href=\"EditRoster?rid=$rid&sid=$user_id\" class=\"rnm\">$staff_name</a></div>"); */
                    if ($staff_name) {
                        $staff_str .= "<a href=\"EditRoster?rid=$roster_id&sid=$user_id" . ($view_mode ? "&view_mode=$view_mode" : "") . "\" class=\"rnm\">$staff_name</a>";
                    } else {
                        $staff_str .= "<a href=\"EditRoster?rid=$roster_id&sid=$user_id" . ($view_mode ? "&view_mode=$view_mode" : "") . "\" class=\"rnm\">$staff_raw</a>";
                    }
                }
                $current_time = date('Y-m-d H:i:s');

                $staff_str .= "<input type=\"button\" onclick=\"openLeaves('" . $roster_times_staff_id . "')\" style=\"padding:1px !important; float:right; " . ($leave_id ? "background-color:red" : "") . "\" value=\"&#128198;\" title=\"Apply Leave\" /> ";

                // JavaScript logic to check if the button should be locked or not
                if (strtotime($current_time) >= strtotime($myrow['finish_time_date'])) {
                    // Button unlocked
                    $staff_str .= "<input type=\"button\" onclick=\"sendForcedSignOff('" . $roster_times_staff_id . "', '" . $myrow['start_time_date'] . "', '" . $myrow['finish_time_date'] . "')\" style=\"padding:1px !important; float:right; " . ($leave_id ? "background-color:red" : "") . "\" value=\"&#128336;\" title=\"Force Manual Sign Off\" /> ";
                } else {
                    // Button locked
                    $staff_str .= "<input type=\"button\" disabled=\"disabled\" style=\"padding:1px !important; float:right; background-color:grey\" value=\"&#128336;\" title=\"Force Manual Sign Off (Locked)\" /> ";
                }
                
                $staff_str .= "<div id=\"dialogLeave" . $roster_times_staff_id . "\" style=\"display:none\">"
                                . "Shift start: " . $myrow['start_time_date'] . "<br>"
                                . "Shift End: " . $myrow['finish_time_date'] . "<br>"
                                . "Staff: " . $staff_name . "<br>"
                                . "Roster ID: " . $roster_times_staff_id . "<br>"
                                . "<br><br><select id=\"leaveroster_" . $roster_times_staff_id . "\" >"
                                . "<option value=\"NULL\">Select Leave Type</option>";
    
    // JavaScript function to send forced sign-off data to backend
    $staff_str .= "<script>
    function sendForcedSignOff(roster_times_staff_id, start_time_date, finish_time_date) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
            }
        };
        xhttp.open('POST', '/app/controllers/rosters/forceSignOff.php', true);
        xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhttp.send('roster_times_staff_id=' + roster_times_staff_id + '&start_time=' + start_time_date + '&finish_time=' + finish_time_date);
    }
</script>";

    
    
            


                

                foreach ($leaveTypes as $key => $value) {
                    if ($leave_id == $key) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = "";
                    }
                    $staff_str .= "<option value=\"" . $key . "\" " . $selected . ">" . $value . "</option><br>";
                }

                $staff_str .= "</select>"
                        . "<input type=\"button\" onclick=\"saveLeave('" . $roster_times_staff_id . "')\" value=\" Apply Leave \" />"
                        . "";
                $staff_str .= "</div></div>";
            }

            //$staff_str .= '</div>';
            $staff_str .= "<div class=\"breakerLine\"></div>";
            $staff_str .= '</div>';
            //echo "<h3>$msg</h3>";
            if ($use_return) {
                return $staff_str;
            } else {
                $staff_str .= "||||" . ($msg ? $msg : "N");
                echo $staff_str;
            }
        }
//    echo $emp_id;
    }

    function hasIncompleteTraining($user_id) {
        // Define the WHERE condition for checking incomplete training
        $where_cond = " ta_training_user.user_id = '" . $user_id . "' ";
        $where_cond .= " and tar.start_date <= CURDATE() "; // Training must have started
        $where_cond .= " and tar.end_date >= CURDATE() "; // Training must not have ended
        $where_cond .= " and ta_training_user.is_result != 1 "; // Check if training is incomplete

        // Query to check if there are any incomplete trainings
        $checkQuery = "SELECT COUNT(*) as total_incomplete_training
                    FROM training_allied_rules tar
                    LEFT JOIN training_allied_training_user ta_training_user ON ta_training_user.training_rule_id = tar.id
                    WHERE $where_cond";

        // Execute the query
        $result = $this->dbi->query($checkQuery);

        // Check if there are incomplete trainings
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total_incomplete_training'] > 0; // Return true if incomplete training exists
        } else {
            return false; // Return false if query failed
        }
    }

    function RosterNotes($display_val = 0) {
        $roster_id = (isset($_GET['roster_id']) ? $_GET['roster_id'] : null);

        $this->list_obj->sql = "select roster_times_staff.id as idin,
    DATE_FORMAT(roster_times.start_time_date, '%a, %d-%b-%Y - %H:%i') as `Rostered Start`,
    DATE_FORMAT(roster_times.finish_time_date, '%a, %d-%b-%Y - %H:%i') as `Rostered Finish`,
    roster_times.minutes_unpaid as `Unpaid Minutes`,
    CONCAT(users.name, ' ', users.surname) as `Rostered Staff Member`,
    roster_times_staff.staff_comment as `Staff Comment`, roster_times_staff.controller_comment as `Controller Comment`, 'Edit' as `*`, 'Delete' as `!`
    FROM rosters
    inner join roster_times on roster_times.roster_id = rosters.id
    inner join roster_times_staff on roster_times_staff.roster_time_id = roster_times.id
    inner join users on users.id = roster_times_staff.staff_id
    where rosters.id = $roster_id
    order by roster_times.start_time_date, roster_times.finish_time_date, users.name
    ";

        $this->editor_obj->table = "roster_times_staff";
        $style = "style='width: 100% !important; height: 50px !important;'";
        $this->editor_obj->form_attributes = array(
            array("txaStaffComment"),
            array("Controller Comment"),
            array("controller_comment"),
            array(""),
            array($style),
            array("n")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = (isset($_POST['idin']) ? '
      ttxaStaffComment<br />txaStaffComment<br />
      
      <div style="margin-top: 8px"><br />' . $this->editor_obj->button_list() . '</div>
    ' : '');

        $this->editor_obj->editor_template = '
      editor_form
      editor_list
    ';

        $str .= "<h3>Roster Notes</h3>";
        $str .= "<div class=\"grid-view\">" . $this->editor_obj->draw_data_editor($this->list_obj) . "</div>";

        if ($display_val) {
            echo $str;
        } else {
            return $str;
        }
    }

    function UserOptions() {

        $roster_time_id = (isset($_GET['roster_time_id']) ? $_GET['roster_time_id'] : null);
        $del = (isset($_GET['del']) ? $_GET['del'] : null);
        $confirm = (isset($_GET['confirm']) ? $_GET['confirm'] : null);

        $user_id = (isset($_GET['user_id']) ? $_GET['user_id'] : null);

        $roster_times_staff_id = (isset($_GET['roster_times_staff_id']) ? $_GET['roster_times_staff_id'] : null);
        $use_return = ($item || $del || $confirm ? 0 : 1);

        $roster_time_id = ($item ? substr($item, 10) : (isset($_GET['roster_time_id']) ? $_GET['roster_time_id'] : $roster_time_id));

        if ($confirm) {
            $sql = "update roster_times_staff set status = $confirm where id = $roster_times_staff_id;";
            $this->dbi->query($sql);
        }
        $sql = "select users.id as `user_id`, roster_times.id as `roster_times_id`, roster_times.start_time_date, roster_times.finish_time_date, roster_times_staff.id as `roster_times_staff_id`, CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname) as `staff`, roster_times_staff.status
    from roster_times_staff
    left join roster_times on roster_times.id = roster_times_staff.roster_time_id
    left join users on users.id = roster_times_staff.staff_id
    where roster_times_staff.roster_time_id = $roster_time_id and users.id = $staff_id";

        if ($result = $this->dbi->query($sql)) {
            $class_str[1] = "shift_pending";
            $class_str[2] = "shift_confirmed";
            $class_str[3] = "shift_cancelled";
            while ($myrow = $result->fetch_assoc()) {
                $user_id = $myrow['user_id'];
                $roster_times_id = $myrow['roster_times_id'];
                $start_time_date = $myrow['start_time_date'];
                $finish_time_date = $myrow['finish_time_date'];
                $status = $myrow['status'];
                $staff_name = $myrow['staff'];
                $roster_times_staff_id = $myrow['roster_times_staff_id'];
                $staff_str .= '<div class="name_item ' . $class_str[$status] . '"><a class="list_a" href="Javascript:remove_item(' . $roster_times_staff_id . ', ' . $roster_times_id . ')">X</a> &nbsp;' . $staff_name;
                $staff_str .= '&nbsp; <a data-uk-tooltip title="' . ($status == 1 ? "Confirm Shift" : "Set Shift to Pending (Uncornfirmed)") . '" class="list_a" href="Javascript:staff_status(' . ($status == 1 ? 2 : 1) . ', ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')">' . ($status == 1 ? "C" : "P") . '</a>';
                if ($status != 3) {
                    $staff_str .= '&nbsp;<a class="list_a" data-uk-tooltip title="Change To Cancelled" href="Javascript:staff_status(3, ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')">x</a>';
                } else {
                    $staff_str .= '&nbsp; <a data-uk-tooltip title="Confirm Shift" class="list_a" href="Javascript:staff_status(2, ' . $roster_times_staff_id . ', ' . $roster_times_id . ', \'\')">C</a>';
                }
                $staff_str .= '</div>';
            }
            if ($use_return) {
                return $staff_str;
            } else {
                echo $staff_str;
            }
        }
//    echo $emp_id;
    }

    function RosterView($user_id = "") {
        session_start();

        $str .= '
      <style>
      .time_block {
        display: inline-block;
        float: left;
        padding: 10px;
      }
      .status_header {
        font-weight: normal;
        font-size: 14pt;
        text-align: center;
      }
      .status_subheader {
        font-weight: bold;
        font-size: 12pt;
      }
      .confirm_btn {
        width: 90%;
      }
      </style>
    ';

        $confirm = (isset($_GET['confirm']) ? $_GET['confirm'] : null);
        $roster_times_staff_id = (isset($_GET['roster_times_staff_id']) ? $_GET['roster_times_staff_id'] : null);
        $roster_time_id = (isset($_GET['roster_time_id']) ? $_GET['roster_time_id'] : 0);

        if ($confirm) {
            $sql = "update roster_times_staff set status = $confirm where " . ($roster_times_staff_id == "ALL" ? " staff_id = " . ($user_id ? $user_id : $_SESSION['user_id']) : "id = $roster_times_staff_id;");
            $this->dbi->query($sql);
            echo $this->redirect($this->f3->get('main_folder') . "Rostering/RosterView" . ($user_id ? "?user_id=$user_id" : ""));
        }

        $user_id = ($user_id ? $user_id : $_SESSION['user_id']);

        $class_str[1] = "shift_pending";
        $class_str[2] = "shift_confirmed";
        $class_str[3] = "shift_cancelled";
        $sql = "
    select roster_times.id,
    DAYNAME(roster_times.start_time_date) as `start_day`, DAYNAME(roster_times.finish_time_date) as `finish_day`, roster_times_staff.status, roster_times_staff.id as `roster_times_staff_id`,
    DATE_FORMAT(roster_times.start_time_date, '%d-%b-%Y') as `start_date`,
    DATE_FORMAT(roster_times.finish_time_date, '%d-%b-%Y') as `finish_date`,
    DATE_FORMAT(roster_times.start_time_date, '%H:%i') as `start_time`,
    DATE_FORMAT(roster_times.finish_time_date, '%H:%i') as `finish_time`,
    ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))
    as `hours`
    , CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname) as `staff`
    , CONCAT(users2.name, ' ', users2.surname) as `site`
    from roster_times_staff
    inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
    left join rosters on rosters.id = roster_times.roster_id
    left join users on users.id = roster_times_staff.staff_id
    left join users2 on users2.id = rosters.site_id
    where roster_times_staff.staff_id = $user_id and roster_times.finish_time_date > DATE_ADD(now(), INTERVAL -2 DAY)
    order by roster_times.start_time_date, roster_times.finish_time_date
    ";
        if ($result = $this->dbi->query($sql)) {
            $cnt = 0;
            //$str .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small" ><thead>';
            while ($myrow = $result->fetch_assoc()) {
                $roster_time_id = $myrow['id'];
                $roster_times_staff_id = $myrow['roster_times_staff_id'];
                $start_day = $myrow['start_day'];
                $finish_day = $myrow['finish_day'];
                $start_time = $myrow['start_time'];
                $finish_time = $myrow['finish_time'];
                $start_date = $myrow['start_date'];
                $finish_date = $myrow['finish_date'];
                $staff = $myrow['staff'];
                $site = $myrow['site'];
                $status = $myrow['status'];
                $hours = round($myrow['hours'], 2);

//        $starts[$cnt] = "$start_time";
//        $finishes[$cnt] = "$finish_time";
//        if($old_start_day != $start_day) {
                //      }
                $str .= "<div class=\"time_block " . $class_str[$status] . "\"><div class=\"status_subheader\">$start_day</div>$start_date<hr />";

                $str .= "<div class=\"status_subheader\">$site</div>$start_time - $finish_time<br />$hours Hour Shift";

                $str .= "<hr /><div class=\"status_header\">" . ($status == 2 ? "CONFIRMED" : ($status == 3 ? "CANCELLED" : "PENDING")) . "</div>";

                $str .= '<a class="list_a confirm_btn" href="RosterView?confirm=' . ($status == 2 ? "1" : "2") . '&roster_times_staff_id=' . $roster_times_staff_id . '">' . ($status == 2 ? "Unconfirm" : "Confirm") . '</a>';

                $str .= '<a class="list_a confirm_btn" href="RosterView?confirm=' . ($status == 3 ? "1" : "3") . '&roster_times_staff_id=' . $roster_times_staff_id . '">' . ($status == 3 ? "UNDO Cancel" : "Cancel") . '</a><br />';

                $str .= "</div>";

                $old_start_day = $start_day;
                $cnt++;
            }
            //$str .= "<tr>$dates</tr></thead><tr>$details</tr>";
        }
        $str .= '<div class="cl"></div>' . ($cnt ? '<br /><a class="list_a" href="RosterView?confirm=2&roster_times_staff_id=ALL">Confirm ALL</a><br />' : '');

        /*    $sql = "
          select roster_times.id as `roster_times_id`, roster_times_staff.id as `roster_times_staff_id`
          , CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname) as `staff`
          , CONCAT(users2.client_id, ' - ', users2.name, ' ', users2.surname) as `site`
          , roster_times.start_time_date, roster_times.finish_time_date
          from roster_times_staff
          inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
          left join rosters on rosters.id = roster_times.roster_id
          left join users on users.id = roster_times_staff.staff_id
          left join users2 on users2.id = rosters.site_id
          where roster_times_staff.staff_id = 2
          ";

          if($result = $this->dbi->query($sql)) {
          while($myrow = $result->fetch_assoc()) {
          $staff = $myrow['staff'];
          $site = $myrow['site'];
          $roster_times_staff_id = $myrow['roster_times_staff_id'];
          $roster_times_id = $myrow['roster_times_id'];
          $str .= "$staff_name";
          }
          } */

        return $str;
    }

    function ViewRoster() {

        $rid = $this->rid;
        $notAllowedRoster = $this->checkNotAllowedRoster($rid);
        if ($notAllowedRoster) {
            echo $this->redirect($this->f3->get('main_folder') . "Rostering/rosters");
            die;
        }
        $site_id = $this->get_sql_result(" select rosters.site_id as `result` from rosters where id = $rid ");
        $client_user = $this->f3->get('is_client');
        $site_user = $this->f3->get('is_site');
        $client_or_site = $client_user + $site_user;
        if ($client_user) {
            $allow_access = $this->num_results("select child_user_id from associations where association_type_id = 1 and child_user_id = $site_id and parent_user_id = {$_SESSION['user_id']} ");
        } else if ($site_user) {
            $allow_access = ($site_id == $_SESSION['user_id'] ? 1 : 0);
        } else {
            $allow_access = 1;
        }
        $_GET['view_mode'] = ($client_or_site ? 2 : 1);

        return ($allow_access ? $this->EditRoster($_GET['view_mode']) : "");
    }

    function ByDay() {
        $view_mode = (isset($_GET['view_mode']) ? $_GET['view_mode'] : 0);
        return $this->EditRoster($view_mode, 1);
    }

    function MyRoster() {
        session_start();
        return $this->EditRoster(0, 0, 1);
    }

        // Get the site_id for a given roster_id
        function getSiteId($rid) {
            $sql = "SELECT site_id FROM rosters WHERE id = $rid";
            $result = $this->dbi->query($sql);
            $row = $result->fetch_assoc();
            return $row['site_id'];
        }

         
    // Calculate the total hours from roster_times for a week starting from Monday
    function calculateTotalHoursForWeek($rid) {
        $startDate = date('Y-m-d', strtotime("last Monday"));
        $endDate = date('Y-m-d', strtotime("next Sunday"));
    
        $sql = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(finish_time_date, start_time_date )) / 3600) AS total_hours
                FROM roster_times
                WHERE roster_id = $rid
                AND start_time_date >= '$startDate 00:00:00'
                AND finish_time_date <= '$endDate 23:59:59'";
    
        // Execute the query
        $result = $this->dbi->query($sql);
    
        if (!$result) {
            die('Query error: ' . $this->dbi->error);
        }
    
        $row = $result->fetch_assoc();
    
        if (!$row) {
            die('No results found for roster ID ' . $rid);
        }
    
        return floatval($row['total_hours']);
    }
    
        

    function insertTotalRosterHours($totalHours, $siteId) {
        // Escape and quote the values for the SQL query
        $totalHours = $this->dbi->real_escape_string($totalHours);
        $siteId = $this->dbi->real_escape_string($siteId);
    
        // Calculate the date range for a week
        $startDate = date('Y-m-d', strtotime('-1 week')); // Start date for the week
        $endDate = date('Y-m-d'); // Today's date as end date
    
        // Check for duplicates within the week's range
        $sqlDuplicateCheck = "SELECT * FROM totalrosterhours WHERE site_id = '$siteId' 
                              AND dateCreated BETWEEN '$startDate' AND '$endDate'";
        $resultDuplicateCheck = $this->dbi->query($sqlDuplicateCheck);
    
        if ($resultDuplicateCheck->num_rows > 0) {
            // If duplicates found, delete them
            $sqlDeleteDuplicates = "DELETE FROM totalrosterhours WHERE site_id = '$siteId' 
                                    AND dateCreated BETWEEN '$startDate' AND '$endDate'";
            $deleteResult = $this->dbi->query($sqlDeleteDuplicates);
    
            if (!$deleteResult) {
                die(json_encode(['error' => 'Error deleting duplicates: ' . $this->dbi->error]));
            }
        }
    
        // Insert the new record
        $sqlInsert = "INSERT INTO totalrosterhours (hoursWorked, site_id, dateCreated) 
                      VALUES ('$totalHours', '$siteId', NOW())";
        $insertResult = $this->dbi->query($sqlInsert);
    
       /* if (!$insertResult) {
            die(error_log(['error' => 'Query error: ' . $this->dbi->error]));
        }
    
        echo json_encode(['success' => true]);
        */
    }
    

    function GetTotals() {
        // Retrieve the parameters from the GET request
        $rid = isset($_GET['rid_in']) ? $_GET['rid_in'] : null;
        $sid = isset($_GET['sid_in']) ? $_GET['sid_in'] : null;
        $dateBy = isset($_GET['date_by_in']) ? $_GET['date_by_in'] : null;
        
        // Calculate and get the total hours
        $totalHours = $this->calculateTotalHoursForWeek($rid);
        
        if ($totalHours !== false) {
            // Insert the total hours into the totalrosterhours table
            $siteId = $this->getSiteId($rid);
            $inserted = $this->insertTotalRosterHours($totalHours, $siteId);
            
            // Get the totals
            $totals = $this->get_totals($rid, $sid, $dateBy);
    
            // Check if insertion was successful and return the totals
            if ($inserted) {
                echo $totals;
            } else {
                // Handle insert failure
                echo $totals;
                // You might want to log or handle this differently based on your requirements
            }
        } else {
            // Handle failure in calculating total hours
            echo $totals;
            // You might want to log or handle this differently based on your requirements
        }
    }
    
    function get_totals($rid, $sid, $date_by) {
        $fixed_hours = (int) $this->get_sql_result("select meta_value as `result` from usermeta where meta_key = (select id from lookups where item_name = 'roster_fixed_hours') and user_id = (select site_id from rosters where id = $rid)");
        if ($sid) {
            $str = "<br /><h5>Total Hours: " . $this->get_sql_result("
        select SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))) as `result` from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        where roster_times.roster_id = $rid and roster_times_staff.staff_id = $sid
      ") . ($fixed_hours ? " / $fixed_hours" : "") . "</h5>";
        } else {
            $sql[0] = "
        select CONCAT(users.name, ' ', users.surname) as `item`, SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))) as `hours`,roster_times_staff.status from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join users on users.id = roster_times_staff.staff_id
        where
        " . ($rid ? " roster_times.roster_id = $rid " : " date(roster_times.start_time_date) = '$date_by'") . "
        " . ($sid ? " and users.id = $sid " : "") . "
        group by roster_times_staff.staff_id
        order by users.name
      ";
            $sql[1] = "
        select SUBSTRING(DAYNAME(roster_times.start_time_date), 1, 3) as `item`, SUM(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))) as `hours`, roster_times_staff.status from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join users on users.id = roster_times_staff.staff_id
        where
        " . ($rid ? " roster_times.roster_id = $rid " : " date(roster_times.start_time_date) = '$date_by'") . "
        " . ($sid ? " and roster_times_staff.staff_id = $sid " : "") . "
        group by DAY(roster_times.start_time_date)
        order by DAY(roster_times.start_time_date), users.name
      ";

            /* for aproved and unApproved */
            $sql[2] = "
        select CONCAT(users.name, ' ', users.surname) as `item`, ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2)) as `hours`,roster_times_staff.status from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join users on users.id = roster_times_staff.staff_id
        where
        " . ($rid ? " roster_times.roster_id = $rid " : " date(roster_times.start_time_date) = '$date_by'") . "
        " . ($sid ? " and users.id = $sid " : "") . "       
      ";

            $userTypeId = $this->userDataAccessType($_SESSION['user_id']);
            if ($userTypeId != 3) {

                $title[0] = ($sid ? "Total Hours" : "Hours By Staff Member");
                $title[1] = "Hours By Day";
                $total_hours = 0;

                for ($x = ($sid ? 1 : 0); $x < ($rid ? 2 : 1); $x++) {
                    $str .= '<div class="hour_statics"><h5>' . $title[$x] . '</h5>';
//                echo $sql[$x];
//                die;
                    if ($result = $this->dbi->query($sql[$x])) {
                        while ($myrow = $result->fetch_assoc()) {
                            $item = $myrow['item'];
                            $hours = floatval($myrow['hours']);
                            $rts_status = $myrow['status'];

                            if (!$x) {
                                $total_hours += $hours;
                            }
                            $str .= "<span class=\"group_tile\">$item ($hours)</span>";
                        }
                    }
                    $str .= "</div>";
                }

                $unApprovedHours = 0;
                $approvedHours = 0;
                $completedHours = 0;
                $total_final_hours = 0;

                if ($result = $this->dbi->query($sql[2])) {
                    while ($myrow = $result->fetch_assoc()) {
                        $hours = floatval($myrow['hours']);
                        $rts_status = $myrow['status'];

                        if ($rts_status == 1) {
                            $unApprovedHours += $hours;
                        } else {
                            $approvedHours += $hours;
                        }

                        if ($rts_status == 5) {
                            $completedHours += $hours;
                        }

                        $total_final_hours += $hours;
                    }
                }

                $str .= "<span class=\"group_tile\">completed hours: " . $completedHours . " </span>";

                // if($userTypeId != 3){
                $str .= "<div class=\"hour_statics\"><h5>Approved Hours: " . $approvedHours . ", Unappoved Hours: " . $unApprovedHours . ",Total Hours: $total_final_hours " . ($fixed_hours ? " / $fixed_hours" : "") . "</h5></div>";

                //}
            }
        }
        return $str;
    }

//~!@
    function EditRoster($view_mode = 0, $by_day_mode = 0, $my_ros_mode = 0) {

        $leaveTypes = $this->getRosterLeaveTypes();
        $rid = $this->rid;
        $notAllowedRoster = $this->checkNotAllowedRoster($rid);

        if ($rid && $notAllowedRoster) {
            echo $this->redirect($this->f3->get('main_folder') . "Rostering/rosters");
            die;
        }


        $sid = (isset($_GET['sid']) ? $_GET['sid'] : 0);
        $copy_days = (isset($_POST['copy_days']) ? $_POST['copy_days'] : 0);
        $hdnRemoveTimeId = (isset($_POST['hdnRemoveTimeId']) ? $_POST['hdnRemoveTimeId'] : null);
        $user_id = 0;
        if ($hdnRemoveTimeId) {
            $this->sendRosterAlterMessage($user_id, "deletedAll", $hdnRemoveTimeId);
            $this->dbi->multi_query("delete from roster_times where id = $hdnRemoveTimeId; delete from roster_times_staff where roster_time_id = $hdnRemoveTimeId;");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }

        if (!$my_ros_mode) {
            $key_text = json_encode("<img src=\"" . $this->f3->get('img_folder') . "roster_legend.gif\" />");
            $str .= '<div style="float: right;margin-bottom: 0px;"><a class="list_a orgColor" href=\'JavaScript:open_modal(' . $key_text . ');\'>Key</a></div>';
            $str .= '<div style="float: left;margin-bottom: 10px;"><a href="/Rostering/Rosters"  class="orgColorBlue" > Roster Manager  </a></div><div class="cl"></div>';
        }




        $publish = (isset($_GET['publish']) ? $_GET['publish'] : 0);
        if ($publish) {

            if ($publish == 2) {
                $this->sendRosterAlterMessage($user_id, "unpublish", $rid);
            } else {
                $this->sendRosterAlterMessage($user_id, "publish", $rid);
            }


            $sql = "update rosters set is_published = " . ($publish == 2 ? "0" : "1") . " where id = $rid";
            $this->dbi->query($sql);

            $changedArray = array('publish' => $publish);

            $this->changedInRoster($rid, $changedArray);

            echo $this->redirect("EditRoster?rid=$rid");
        }

        if ($copy_days) {
            $copy_to = $_POST['chkCopyTo'];
            $day_copy = $_POST['selDayCopy'];
            $day_copy--;

            foreach ($copy_to as $day) {

                $diff = $day - $day_copy - 1;
                $sql = "select users.id as `user_id`,
        DATE_ADD(roster_times.start_time_date, INTERVAL $diff DAY) as `r_start`,
        DATE_ADD(roster_times.finish_time_date, INTERVAL $diff DAY) as `r_finish`,
        roster_times_staff.status, roster_times.roster_id from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join users on users.id = roster_times_staff.staff_id
        where roster_times.roster_id = $rid
        and weekday(roster_times.start_time_date) = $day_copy
        order by users.name";
                if ($result = $this->dbi->query($sql)) {
                    $roster_time_id = "";
                    while ($myrow = $result->fetch_assoc()) {
                        $user_id = $myrow['user_id'];
                        $status = $myrow['status'];
                        $r_start = $myrow['r_start'];
                        $r_finish = $myrow['r_finish'];
                        $roster_time_id = $this->get_sql_result("select id as `result` from roster_times where roster_id = $rid and start_time_date = '$r_start' and finish_time_date = '$r_finish'");
                        if (!$roster_time_id) {
                            $sql = "insert into roster_times (roster_id, start_time_date, finish_time_date) values ('$rid', '$r_start', '$r_finish');";
                            $this->dbi->query($sql);
                            $roster_time_id = $this->dbi->insert_id;
                            $detail = "Shift Created";
                            $this->rosterLog(3, NULL, $roster_time_id, NULL, $detail);
                        }
                        if ($roster_time_id) {
                            $sql = "insert ignore into roster_times_staff (staff_id, roster_time_id, status) values('$user_id', '$roster_time_id', '$status')";
                            $this->dbi->query($sql);
                            $roster_time_staff_id = $this->dbi->insert_id;
                            $detail = " Staff has been added";
//                            echo $roster_time_staff_id;
//                            die;
                            $this->rosterLog(4, NULL, NULL, $roster_time_staff_id, $detail);
                        }

//          $str .= "<br />$sql<br /><br />";
                        //  $str .= "<br />test: " . $diff;
                    }
                }
            }
//      $str .= "test: " . $_POST['chkCopyTo'];



            /* $sql = "insert into rosters (division_id, site_id, shift_template_id, start_date, requires_licences, requires_confirmation, requires_induction) (select division_id, site_id, shift_template_id, '$calCopyDate', requires_licences, requires_confirmation, requires_induction from rosters where id = $copyid);";
              $this->dbi->query($sql);
              $copy_to_id = $this->dbi->insert_id;

              $date1 = $this->get_by_id("rosters","start_date",$copyid);
              $interval = $this->get_sql_result("select datediff('$calCopyDate', '$date1') as `result`");

              $sql = "select roster_times.id, DATE_ADD(roster_times.start_time_date, INTERVAL $interval DAY) as `start_time_date`, DATE_ADD(roster_times.finish_time_date, INTERVAL $interval DAY) as `finish_time_date`, rosters.requires_confirmation from roster_times
              left join rosters on rosters.id = roster_times.roster_id
              where roster_id = $copyid";
              if($result = $this->dbi->query($sql)) {
              $sql_add_staff = "";
              while($myrow = $result->fetch_assoc()) {
              $from_roster_time_id = $myrow['id'];
              $start_time_date = $myrow['start_time_date'];
              $finish_time_date = $myrow['finish_time_date'];
              $requires_confirmation = $myrow['requires_confirmation'];
              $sql = "insert into roster_times (roster_id, start_time_date, finish_time_date) values ('$copy_to_id', '$start_time_date', '$finish_time_date');";
              //$str .= "<h3>$sql<h3>";
              $this->dbi->query($sql);
              $roster_time_id = $this->dbi->insert_id;
              $sql = "insert into roster_times_staff (staff_id, roster_time_id ".($requires_confirmation ? "" : ", status").") select staff_id, '$roster_time_id' ".($requires_confirmation ? "" : ", '2'")." from roster_times_staff where roster_time_id = $from_roster_time_id;";
              $this->dbi->query($sql);
              }
              }
              echo $this->redirect($this->f3->get('main_folder') . "Rostering/EditRoster?rid=$copy_to_id");
              } */
        }

        if ($view_mode)
            $_GET['view_mode'] = $view_mode;
        $view_mode = (isset($_GET['view_mode']) ? $_GET['view_mode'] : 0);
        $_GET['by_day_mode'] = $by_day_mode;
        $_GET['my_ros_mode'] = $my_ros_mode;
        $division_id = $this->division_id;
        $hdnFrom = (isset($_POST['hdnFrom']) ? $_POST['hdnFrom'] : null);
        $hdnId = (isset($_POST['hdnId']) ? $_POST['hdnId'] : null);
        $itm = new input_item;
        $itm->hide_filter = 1;

        $my_id = $_SESSION['user_id'];

        $days = (isset($_GET['days']) ? $_GET['days'] : 0);
        $date_by = (isset($_GET['date_by']) ? $_GET['date_by'] : date("Y-m-d", strtotime("$days days")));
        $date_by_show = date("l, d/M/Y", strtotime("$days days"));

        //Current Styles for testing
        $str .= '<input type="hidden" name="hdnRemoveTimeId" id="hdnRemoveTimeId" />';
        $str .= "
    <style>
    #content {  
        margin-top: 20px !important;
    }
      .shift_status {
        text-align: center;
        margin-top: 10px;
        font-size: 13pt;
      }
      .shift_button {
        margin-top: 5px;
        text-align: center;
      }
      .shift_button a {
        padding: 10px;
        color: white !important;
        text-decoration: none;
        display: block;
      }
      .shift_save_comment {
        background-color: #0000AA;
      }
      .shift_confirm {
        background-color: green;
      }
      .shift_cancel {
        background-color: #AA0000;
      }
      .shift_undo {
        background-color: #F85B00;
      }
      
      .shift_message {
        text-align: left;
        margin-top: 7px;
        font-size: 12pt;
      }
      .published {
        background-color: #009900 !important;
        color: #009900;
        border-color: #009900;
        color: white !important;
      }
      .draft {
        color: white !important;
        border-color: #AA0000;
        background-color: #AA0000 !important;
      }
      .list_a {
        margin-top: -4px !important;
      }
      .published, .draft {
        margin-top: 15px !important;
        padding-top: 3px;
        padding-bottom: 3px;
        padding-left: 6px;
        padding-right: 6px;
        border-radius: 8px 8px 8px 8px;
        -moz-border-radius: 8px 8px 8px 8px;
        -webkit-border-radius: 8px 8px 8px 8px;
        white-space: nowrap;
        font-weight: normal !important;
        font-size: 11pt !important;
      }
      
      .small_delete {
        margin-top: -5px; font-size: 9pt; padding-left: 1px;
      }
      
/*      .published:hover {
        background-color: #AA0000 !important;
        border-color: #AA0000 !important;
      }
      .draft:hover {
        background-color: #009900 !important;
        border-color: #009900 !important;
      }
      */
    </style>
    ";

        //$url_xtra = ($days ? "&days=$days" : "").($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").($date_by ? "&date_by=$date_by" : "");

        if ($my_ros_mode) {

            $confirm = (isset($_GET['confirm']) ? $_GET['confirm'] : 0);
            if ($confirm) {
                $sql = "select roster_times_staff.id
        from roster_times_staff
        inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
        where roster_times_staff.staff_id = $my_id and
        (DATE(roster_times.start_time_date) >= DATE(NOW()) or DATE(roster_times.finish_time_date) >= DATE(NOW()))";
                if ($result = $this->dbi->query($sql)) {
                    $item_list = "";
                    while ($myrow = $result->fetch_assoc()) {
                        $item_list .= ($item_list ? "," : "");
                        $item_list .= $myrow['id'];
                    }
                }
                $sql = "update roster_times_staff set status = $confirm where id in ($item_list);";
                //$str .= $sql;
                $this->dbi->query($sql);
                echo $this->redirect($this->f3->get('main_folder') . "MyRoster");
            }

            $str .= "<h3>My Roster</h3>";
            //$str .= '<div class="cl"></div><br /><a class="list_a" href="MyRoster?confirm=2">Confirm ALL</a><br />';
            $tsql = "select count(roster_times_staff.id) as `result`
      from roster_times_staff
      inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
      inner join rosters on rosters.id = roster_times.roster_id and rosters.is_published >= 1
      where roster_times_staff.staff_id = $my_id and
      (DATE(roster_times.start_time_date) >= DATE(NOW()) or DATE(roster_times.finish_time_date) >= DATE(NOW()))
      ";

//            echo $tsql;
//            die;
            $cnt = $this->get_sql_result($tsql);
            if ($cnt > 1) {
                $num_unconfirmed = $this->get_sql_result("select count(roster_times_staff.id) as `result`
        from roster_times_staff
        inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
        where roster_times_staff.staff_id = $my_id and roster_times_staff.status < 2 and
        (DATE(roster_times.start_time_date) >= DATE(NOW()) or DATE(roster_times.finish_time_date) >= DATE(NOW()))
        ");
            }

            $str .= '<div class="cl"></div>';

            if ($cnt == 0)
                $str .= '<h3>You are currently not rostered on.</h3>';

            if ($num_unconfirmed > 1)
                $str .= '<br /><a class="list_a" href="MyRoster?confirm=2">Confirm ALL</a><br />';
        } else if ($by_day_mode) {

            $calByDate = (isset($_GET['calByDate']) ? $_GET['calByDate'] : 0);

            if ($calByDate) {
                $date_by = date('Y-m-d', strtotime(str_replace('/', '-', $calByDate)));
                $date_by_show = date("l, d/M/Y", strtotime($date_by));
            }


            $division_str = $this->division_nav($division_id, 'Rostering/ByDay', 0, 0, 1);

            $str .= $itm->setup_cal();
            //function cal($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
            $str .= '
      </form>
      <form method="_GET" name="frmByDate">
      <input type="hidden" name="division_id" value="' . $division_id . '" />
      <div class="fl">';
            $str .= $itm->cal("calByDate", "", ' style="width: 100px;" placeholder="By Date" data-uk-tooltip title="View the Rosters on a Chosen Date"', "", "", "", "");
            $str .= '<input type="button" onClick="by_date()" class="btn" value="Go">
      </form>';
            $day_count = 7;

            for ($i = $day_count; $i; $i--) {
                $str .= '<a data-uk-tooltip title="Show rosters from ' . $i . ' Day' . ($this->pluralise($i)) . ' ago.<br />' . date("l, d/M/Y", strtotime("-$i days")) . '" class="division_nav_item ' . ($days == -$i ? "division_nav_selected" : "") . '" href="ByDay?days=' . -$i . ($division_id ? "&division_id=$division_id" : "") . '">' . $i . '</a>';
            }

            $str .= " &lt;&lt;  ";
            $str .= '<a data-uk-tooltip title="Show Today\'s Rosters.<br />' . date("l, d/M/Y") . '" class="division_nav_item ' . (!$days ? "division_nav_selected" : "") . '" href="ByDay?a=1' . ($view_mode ? "&view_mode=$view_mode" : "") . ($date_by ? "&date_by=$date_by" : "") . ($division_id ? "&division_id=$division_id" : "") . '">Today</a>';
            $str .= " &gt;&gt; ";
            for ($i = 1; $i <= $day_count; $i++) {
                $str .= '<a data-uk-tooltip title="Show rosters ' . $i . ' Day' . ($this->pluralise($i)) . ' from now.<br />' . date("l, d/M/Y", strtotime("$i days")) . '" class="division_nav_item ' . ($days == $i ? "division_nav_selected" : "") . '" href="ByDay?days=' . $i . ($division_id ? "&division_id=$division_id" : "") . '">' . $i . '</a>';
            }


            $str .= '&nbsp;&nbsp;<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/ByDay?a=1' . ($division_id ? "&division_id=$division_id" : "") . ($days ? "&days=$days" : "") . ($date_by ? "&date_by=$date_by" : "") . ($view_mode ? '">Edit Mode' : '&view_mode=$view_mode">View Mode') . '</a></div>';
            $str .= "<div class=\"fr divisionlink \">$division_str</div><div class=\"cl\"></div>";

            $str .= '<h3>Rosters By Day for ' . $division_show . ' &nbsp; &nbsp;[' . $date_by_show . ']&nbsp;</h3>';

            $str .= "<style>
            #overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9999;
            }
            
            #loader {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: white;
                display: inline-block;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>";

$str .= "<div id='overlay'>
            <div id='loader'></div>
         </div>";

$str .= "<a class='list_a' onclick='captureScreenshot()'>Export as PDF</a>";
$str .= "<script src='https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.js'></script>";
$str .= "<script>
            function captureScreenshot() {
                // Show the overlay and loader
                document.getElementById('overlay').style.display = 'block';

                // Get all elements with the roster_grid class
                var rosterTables = document.getElementsByClassName('roster_grid');
                
                // Check if there's at least one element with the class
                if (rosterTables.length > 0) {
                    // Select the first element (you can adjust this if there are multiple)
                    var rosterTable = rosterTables[0];

                    var hoursBElements = rosterTable.getElementsByClassName('hoursB');
                    for (var i = 0; i < hoursBElements.length; i++) {
                        hoursBElements[i].style.display = 'none';
                    }

                    // Use html2pdf.js to capture the roster_grid table and export as PDF
                    html2pdf(rosterTable, {
                        margin: 10,
                        filename: 'exported-document.pdf',
                        image: { type: 'jpeg', quality: 1 },
                        html2canvas: { scale: 1, scrollX: 0, scrollY: 0, useCORS: true },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                    }).then(function () {
                        // Hide the overlay and loader when the PDF is generated
                        for (var i = 0; i < hoursBElements.length; i++) {
                            hoursBElements[i].style.display = '';
                        }
                        document.getElementById('overlay').style.display = 'none';
                    });
                } else {
                    console.error('No element with class roster_grid found.');
                    // Hide the overlay and loader in case of an error
                    document.getElementById('overlay').style.display = 'none';
                }
            }
        </script>";

        } else {

            $user_id = 0;
            $staff = (isset($_POST['staff_list_main']) ? $_POST['staff_list_main'] : null);
            $cm2DateAdd = (isset($_POST['cm2DateAdd']) ? $_POST['cm2DateAdd'] : null);
            $ti2StartTime = (isset($_POST['ti2StartTime']) ? date('H:i:s', strtotime($_POST['ti2StartTime'])) : null);
            $ti2FinishTime = (isset($_POST['ti2FinishTime']) ? date('H:i:s', strtotime($_POST['ti2FinishTime'])) : null);
            $cm2MinutesUnpaid = (isset($_POST['cm2MinutesUnpaid']) ? ($_POST['cm2MinutesUnpaid'] ? $_POST['cm2MinutesUnpaid'] : 0) : 0);
            $teamGroupName = (isset($_POST['team_group_name']) ? $_POST['team_group_name'] : "");

            if ($staff) {
                $user_id = $this->emp_id_to_staff_id(strtok($staff, " "));
            }
            $hdnConfirmId = (isset($_POST['hdnConfirmId']) ? $_POST['hdnConfirmId'] : null);
            if ($hdnConfirmId) {
                $hdnConfirmStatus = (isset($_POST['hdnConfirmStatus']) ? $_POST['hdnConfirmStatus'] : null);
                $this->dbi->query("update roster_times_staff set status = '" . ($hdnConfirmStatus == 2 || $hdnConfirmStatus == 3 ? $hdnConfirmStatus : 1) . "' where roster_time_id in (select id from roster_times where roster_id = $hdnConfirmId) " . ($user_id ? "and staff_id = $user_id" : "") . "and status = " . ($hdnConfirmStatus == 1 ? 2 : 1) . ";");
                echo $this->redirect($this->f3->get('main_folder') . "Rostering/EditRoster?rid=$rid");
            }
            //$staff &&

            if ($cm2DateAdd && $ti2StartTime && $ti2FinishTime) {

                //$str .= "$user_id, $cm2DateAdd, $ti2StartTime, $ti2FinishTime, $rid, 0, $cm2MinutesUnpaid";
                $str .= $this->add_staff_to_roster($user_id, $cm2DateAdd, $ti2StartTime, $ti2FinishTime, $rid, 0, $cm2MinutesUnpaid, $teamGroupName);
            }

//  function add_staff_to_roster($user_id, $start_date, $start_time, $finish_time, $rid) {
            //$roster_time_id = ($item ? substr($item, 10) : (isset($_GET['roster_time_id']) ? $_GET['roster_time_id'] : $roster_time_id));
            /* if($hdnFrom && $hdnId) {

              $sql = "select roster_times.id,
              DATE(DATE_ADD(roster_times.start_time_date, INTERVAL 1 DAY)) as `start_date`, DATE(DATE_ADD(roster_times.finish_time_date, INTERVAL 1 DAY)) as `finish_date`,
              TIME(roster_times.start_time_date)  as `start_time`, TIME(roster_times.finish_time_date)  as `finish_time`,
              roster_times_staff.staff_id, roster_times_staff.minutes_unpaid from roster_times_staff
              left join roster_times on roster_times.id = roster_times_staff.roster_time_id
              where roster_id = $rid and DAYNAME(roster_times.start_time_date) = '$hdnFrom'";
              if($result = $this->dbi->query($sql)) {
              $sql_add_staff = "";
              while($myrow = $result->fetch_assoc()) {
              $staff_id = $myrow['staff_id'];
              $start_date = $myrow['start_date'];
              $finish_date = $myrow['finish_date'];
              $start_time = $myrow['start_time'];
              $finish_time = $myrow['finish_time'];
              $minutes_unpaid = $myrow['minutes_unpaid'];

              $str .= $this->add_staff_to_roster($staff_id, $start_date, $start_time, $finish_time, $rid, $finish_date, $minutes_unpaid);
              }
              }

              } */


            if (!$view_mode) {

                //Getting the required licences for the site
                $sql = "select roster_site_licences.staff_licence_type_id
        from rosters
        left join users on users.id = rosters.site_id
        inner join roster_site_licences on roster_site_licences.site_id = rosters.site_id and roster_site_licences.division_id = rosters.division_id
        where rosters.id = $rid and rosters.requires_licences = 1";
                if ($result = $this->dbi->query($sql)) {
                    $licence_type_ids = "";
                    while ($myrow = $result->fetch_assoc()) {
                        $licence_type_ids .= ($licence_type_ids ? "," : "");
                        $licence_type_ids .= $myrow['staff_licence_type_id'];
                    }
                }
                if ($licence_type_ids)
                    $_GET['licence_type_ids'] = $licence_type_ids;

                $str .= '
        <input type="hidden" name="hdnFrom" id="hdnFrom" />
        <input type="hidden" name="hdnId" id="hdnId" />
        <input type="hidden" name="hdnConfirmId" id="hdnConfirmId" />
        <input type="hidden" name="hdnConfirmStatus" id="hdnConfirmStatus" />
        ';
            }
        }


        $str .= '
    <script>
      function refresh_item(id) {
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'RosterUsers",
              data:{item: "ALL", roster_time_id: id ' . ($view_mode ? ', view_mode: 1' : '') . ', sid: ' . $sid . ' ' . ($my_ros_mode ? ', my_ros_mode: 1' : '') . '} ,
              success:function(msg) {
                var res = msg.split("||||");
                document.getElementById("strstaff_list" + id).innerHTML = res[0]
              }
        } );
      } ';

        if (!$view_mode) {

            $str .= '
        function my_status(confirm, roster_times_staff_id, roster_times_id) {
          v = document.getElementById(\'txaComment\' + roster_times_staff_id)
          //alert(confirm)
          if((confirm == 1 || confirm == 3) && v.value == "") {
            alert("Please enter a comment before " + (confirm == 3 ? "cancelling your shift" : "undoing the cancellation of your shift."))
          } else {
            staff_status(confirm, roster_times_staff_id, roster_times_id, document.getElementById(\'txaComment\' + roster_times_staff_id).value);
          }
        }
        function save_comment(roster_times_staff_id, roster_times_id) {
          //staff_comment(roster_times_staff_id, roster_times_id, document.getElementById(\'txaComment\' + roster_times_staff_id).value);
        }


        function staff_status(confirm, roster_times_staff_id, roster_times_id, comment) {
        //document.getElementById("strstaff_list" + roster_times_id).innerHTML = ""
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'RosterUsers",
              data:{confirm: confirm, roster_times_staff_id: roster_times_staff_id, roster_time_id: roster_times_id, sid: ' . $sid . ' ' . ($my_ros_mode ? ', my_ros_mode: 1' : '') . ', comment: comment} ,
              success:function(msg) {
              
                if(msg == "not_cancel"){
                    alert("Sorry, You can cancel before 6 hour");
                }else{
                var res = msg.split("||||");
                document.getElementById("strstaff_list" + roster_times_id).innerHTML = res[0]
                if(res[1] != "N" && res[1]) show_roster_message("roster_message" + roster_times_id, res[1]);
                }

                
              }
        } );
      }


      function remove_item(del, id) {
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'RosterUsers",
              data:{del: del, roster_time_id: id, sid: ' . $sid . '} ,
              success:function(msg) {
                var res = msg.split("||||");
                document.getElementById("strstaff_list" + id).innerHTML = res[0]
                if(res[1] != "N" && res[1]) show_roster_message("roster_message" + roster_times_id, res[1]);
              }
        } );
      }
      function add_staff(from_item) {
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'RosterUsers",
              data:{item: from_item.id, staff: from_item.value, sid: ' . $sid . ', ' . ($licence_type_ids ? "licence_type_ids: '$licence_type_ids'" : "") . '} ,
              success:function(msg) {
                var res = msg.split("||||");
                var fi = from_item.id
                document.getElementById("str" + from_item.id).innerHTML = res[0]
                if(res[1] != "N") show_roster_message(fi.replace("staff_list", "roster_message"), res[1]);
              }
        } );
        from_item.value = "";
      }

      ';
        }
        $str .= '</script>';
        if (!$my_ros_mode && !$by_day_mode) {
            if ($view_mode) {

                $str .= '<div class="grid-view">' . $this->roster_details($rid, "view") . '</div>';
                /*
                  $sql_view = "select
                  CONCAT('Roster For ', users.name, ' ', users.surname, ' - Starting on ', DATE_FORMAT(rosters.start_date, '%a, %d-%b-%Y')) as `result`
                  FROM rosters
                  left join users on users.id = rosters.site_id
                  where rosters.id = $rid LIMIT 1";

                  $str .= '<h3 class="fl">' . $this->get_sql_result($sql_view) . '</h3>';
                  if($view_mode == 1) $str .= '<div class="fr"><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/EditRoster?rid='.$rid.'">Edit Roster</a></div><div class="cl"></div>';
                 */
            } else {
                $str .= '<div class="grid-view">' . $this->roster_details($rid, "edit") . '</div>';
                $sql_division = "select rosters.division_id as `result` FROM rosters where id = $rid;";
                $division_id = $this->get_sql_result($sql_division);
            }
        }
        $rosterIngStateIds = $this->rosteringStateIds($_SESSION['user_id']);

        $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
        $loginUserDivisionsArray = explode(',', $loginUserDivisions);
        $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
        $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);

        if (!$loginUserDivisionsStr) {
            $loginUserDivisionsStr = 0;
        }


        $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);
        // prd($userAllowedSiteIds);

        if ($userAllowedSiteIds != "all") {
            $userSiteAllowedconditon = " and location.id in (" . $userAllowedSiteIds . ")";
            $filterAllowedSiteIds = "and rosters.site_id in (" . $userAllowedSiteIds . ")";
        }


        $countRatingSql = " SELECT COUNT(rtsc.id) FROM roster_times_staff rtsc
LEFT JOIN roster_times rtc ON rtc.id = rtsc.roster_time_id
left JOIN rosters rstc on rstc.id = rtc.roster_id WHERE rstc.site_id = roast.site_id AND rtsc.staff_id = users.id ";

//        $sql = "SET @RANK=0";
//        $result = $this->dbi->query($sql);
//      $sql = "SELECT id,concat(item_name,' ',if((@RANK:=@RANK+1) < 6,@RANK,'')) item_name,($countRatingSql)rank
// FROM 
//(";
$sql = "SELECT users.id as `id`, CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname, ' - ',  users.phone, ' ') as `item_name`,($countRatingSql) total
FROM users
left join user_status on user_status.id = users.user_status_id
left join rosters roast on roast.id = $rid
left join users location on location.id = roast.site_id    
inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
inner join lookup_answers2 on lookup_answers2.foreign_id = users.id and lookup_answers2.lookup_field_id = $division_id and lookup_answers2.table_assoc = 'users'
and users.user_status_id = (select id from user_status where item_name = 'ACTIVE') 
where roast.division_id in (" . $loginUserDivisionsStr . ") " . $userSiteAllowedconditon . " and location.state = users.state
AND (
((SELECT GROUP_CONCAT(lic.licence_type_id) FROM licences lic WHERE lic.user_id = users.ID) like CONCAT('%',(SELECT GROUP_CONCAT(rsl.staff_licence_type_id) required_licence FROM roster_site_licences rsl WHERE rsl.site_id = roast.site_id AND division_id = roast.division_id),'%')) 
|| IF((SELECT GROUP_CONCAT(rsl.staff_licence_type_id) required_licence FROM roster_site_licences rsl WHERE rsl.site_id = roast.site_id AND division_id = roast.division_id),false,true)
)  
and CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname, ' - ', users.phone) != ''
ORDER BY ($countRatingSql) desc";
//        if($_SESSION['user_id'] == 11762){
//        
////        }
        // SELECT GROUP_CONCAT(rsl.staff_licence_type_id) required_licence FROM roster_site_licences rsl WHERE rsl.site_id = 11164 AND division_id = 108;
//SELECT GROUP_CONCAT(lic.licence_type_id) FROM licences lic WHERE lic.user_id = 1275
//        echo $sql;
//        die;
//  function cm2($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
        if (!$view_mode && !$sid && !$my_ros_mode) {

            //$list = $itm->cm2("%s", "", "placeholder=\"Add Staff to Shift\" onChange=\"add_staff(this)\" class=\"staff_entry\" onDblClick=\"clear_field(this)\"", "", $this->dbi, $sql, "");
            $list = $itm->cm2("%s", "", "placeholder=\"Add Staff to Shift\" onChange=\"add_staff(this)\" class=\"staff_entry\" onDblClick=\"clear_field(this)\"", "", $this->dbi, $sql, "staff_list_main");
            // $list = $itm->cm2("staff_list_main", "", "placeholder=\"Add Staff to Shift\" onChange=\"add_staff(this)\" class=\"staff_entry\" onDblClick=\"clear_field(this)\"", "", $this->dbi, $sql, "");
            if (!$by_day_mode) {
                // $str .= '<br />';
                $str .= $itm->setup_ti2();
                $start_date = $this->get_by_id("rosters", "start_date", $rid);
                $sql = "select DATE_FORMAT('$start_date', '%a, %d-%b-%Y') as `item_name`";
                for ($x = 1; $x < 7; $x++) {
                    $sql .= " union select DATE_FORMAT(DATE_ADD('$start_date', INTERVAL $x DAY), '%a, %d-%b-%Y')";
                }

                $t = "staff_list_main";
                $str1234 = '<style> 
                    
datalist#lstaff_list_main option:nth-of-type(1),
datalist#lstaff_list_main option:nth-of-type(2),
datalist#lstaff_list_main option:nth-of-type(3),
datalist#lstaff_list_main option:nth-of-type(4),
datalist#lstaff_list_main option:nth-of-type(5){
  background: red;
  color:#fff;
}


datalist#lstaff_list_main  {
  position: absolute;
  background-color: white;
  border: 1px solid #999;
  border-radius: 0 0 5px 5px;
  border-top: none;
  font-family: sans-serif;
  width: 300px;
  padding: 0px;
  overflow-y: auto;
  max-height:200px;
  
}
datalist#lstaff_list_main option { padding:8px 10px; border-bottom:1px solid #f1f1f1; }
datalist#lstaff_list_main option:hover { background:#f1f1f1; color:#373737; }

td.roster_column {
  border: 1px solid black !important;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin: 20px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

</style>';

                $str .= '
<div class="fl filter-input">' . sprintf(str_replace("onChange=\"add_staff(this)\" class=\"staff_entry\"", "onChange=\"toggle_confirm()\" value=\"$staff\"", $list), $t, $t, $t, $t);
                $list = str_replace("to Shift", "to this Shift", $list);
                $str .= $itm->cm2("cm2DateAdd", "", "placeholder=\"Start Date\" onDblClick=\"clear_field(this)\" onChange=\"\" style=\"width: 160px;\"", "", $this->dbi, $sql, "");
                $str .= " " . $itm->ti2("ti2StartTime", "", "placeholder=\"Start Time\" onChange=\"\" onDblClick=\"clear_field(this)\" style=\"width: 85px;\"", "", "", "", "");
                $str .= $itm->ti2("ti2FinishTime", "", "placeholder=\"Finish Time\" onChange=\"\" onDblClick=\"clear_field(this)\" style=\"width: 85px;\"", "", "", "", "");
                $sql_times = "select '30' as `item_name` union select '15' union select '60' union select '45';";
                $str .= $itm->cm2("cm2MinutesUnpaid", "", "data-uk-tooltip title=\"Number of minutes for unpaid breaks \" placeholder=\"Unpaid Minutes\" onChange=\"\" onDblClick=\"clear_field(this)\" style=\"width: 85px;\"", "", $this->dbi, $sql_times, "");
                $str .= '<select id="team_group_name" name="team_group_name">'
                        . '<option value="">Select Team</option>'
                        . '<option value="Team1">Team1</option>'
                        . '<option value="Team2">Team2</option>'
                        . '<option value="Team3">Team3</option>'
                        . '<option value="Team4">Team4</option>'
                        . '<option value="Team5">Team5</option>'
                        . '<option value="Team6">Team6</option>'
                        . '<option value="Team7">Team7</option>'
                        . '<option value="Team8">Team8</option>'
                        . '<option value="Team8">Team9</option>'
                        . '<option value="Team10">Team10</option>'
                        . '</select>';
                $str .= '<input data-uk-tooltip title="Add Staff to Shift" type="button" id="btnAddStaff" class="addicon" onClick="add_staff_and_time()" value="Add" /><input type="button" class="reseticon" onClick="clear_staff_and_time()" data-uk-tooltip title="Clear All Fields.<br />Double click on a field to clear a single field." value="Clear" /> &nbsp; &nbsp; <input id="btnConfirmAll" value="Confirm All" type="button" class="btn" onClick="confirm_all(2, ' . $rid . ')" data-uk-tooltip title="Confirm ALL Staff Rosters." /><input value="Unconfirm All" type="button" class="btn" id="btnUnconfirmAll" onClick="confirm_all(1, ' . $rid . ')" data-uk-tooltip title="Unconfirm ALL Staff Rosters." />';

                $sql = "select 1 as id, 'Mon' as `item_name` union select 2 as id, 'Tue' as `item_name` union select 3 as id, 'Wed' as `item_name` union select 4 as id, 'Thu' as `item_name` union select 5 as id, 'Fri' as `item_name` union select 6 as id, 'Sat' as `item_name` union select 7 as id, 'Sun' as `item_name`";
                $str .= " &nbsp;<nobr>" . $itm->sel("selDayCopy", "", 'data-uk-tooltip title="Select a day to copy all of the shifts and staff FROM." onChange="day_select(0)\"', "", $this->dbi, $sql, "") . " &nbsp;&gt;&gt;";
                $str .= '<span id="d0">' . $itm->chk("mon|chkCopyTo[]", "M", "data-uk-tooltip title=\"Monday\"", "1", "", "") . "</span>";
                $str .= '<span id="d1">' . $itm->chk("tue|chkCopyTo[]", "T", "data-uk-tooltip title=\"Tuesday\"", "2", "", "") . "</span>";
                $str .= '<span id="d2">' . $itm->chk("wed|chkCopyTo[]", "W", "data-uk-tooltip title=\"Wendesday\"", "3", "", "") . "</span>";
                $str .= '<span id="d3">' . $itm->chk("thu|chkCopyTo[]", "T", "data-uk-tooltip title=\"Thursday\"", "4", "", "") . "</span>";
                $str .= '<span id="d4">' . $itm->chk("fri|chkCopyTo[]", "F", "data-uk-tooltip title=\"Friday\"", "5", "", "") . "</span>";
                $str .= '<span id="d5">' . $itm->chk("sat|chkCopyTo[]", "S", "data-uk-tooltip title=\"Saturday\"", "6", "", "") . "</span>";
                $str .= '<span id="d6">' . $itm->chk("sun|chkCopyTo[]", "S", "data-uk-tooltip title=\"Sunday\"", "7", "", "") . "</span>";
                $str .= '<input data-uk-tooltip title="Click Here to Copy all of the shifts from the selected days..." type="button" id="btnCopyDays" class="btn" onClick="copy_from()" value="Go" />';
                $str .= "</nobr>";
                $str .= '<input type="hidden" id="copy_days" name="copy_days" value="0">';
                $str .= "</div>";

                $str1234 = '<script>staff_list_main.onfocus = function () {
  lstaff_list_main.style.display = \'block\';
    
};

function myFunction() {
    setTimeout(function(){ lstaff_list_main.style.display = \'none\'; }, 500);
}

for (let option of lstaff_list_main.options) {
  option.onclick = function () {
    staff_list_main.value = option.value;
    lstaff_list_main.style.display = \'none\';
    staff_list_main.style.borderRadius = "5px";
  }
};

staff_list_main.oninput = function() {
  currentFocus = -1;
  var text = staff_list_main.value.toUpperCase();
  for (let option of lstaff_list_main.options) {
    if(option.value.toUpperCase().indexOf(text) > -1){
      option.style.display = "block";
  }else{
    option.style.display = "none";
    }
  };
}


var currentFocus = -1;
staff_list_main.onkeydown = function(e) {
  if(e.keyCode == 40){
    currentFocus++
   addActive(lstaff_list_main.options);
  }
  else if(e.keyCode == 38){
    currentFocus--
   addActive(lstaff_list_main.options);
  }
  else if(e.keyCode == 13){
    e.preventDefault();
        if (currentFocus > -1) {
          /*and simulate a click on the "active" item:*/
          if (lstaff_list_main.options) lstaff_list_main.options[currentFocus].click();
        }
  }
}

function addActive(x) {
    if (!x) return false;
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    x[currentFocus].classList.add("active");
  }
  function removeActive(x) {
    for (var i = 0; i < x.length; i++) {
      x[i].classList.remove("active");
    }
  }</script>';
                $str .= '
          <script>
          
          function copy_from() {
            if(document.getElementById("mon").checked === true || document.getElementById("tue").checked === true || document.getElementById("wed").checked === true || document.getElementById("thu").checked === true || document.getElementById("fri").checked === true || document.getElementById("sat").checked === true || document.getElementById("sun").checked === true) {
              confirmation = "Are you sure about copying all of the times and staff to the selected day(s)?";
              if(confirm(confirmation)) {
                document.getElementById("copy_days").value = 1
                document.frmEdit.submit()
              }
            } else {
              alert("Please select one or more days to copy the shifts TO.")
            }
          }
          function day_select(idx) {
            if(idx) {
              idx = 0
            } else { 
              idx = document.getElementById(\'selDayCopy\').selectedIndex
            }
            //alert(idx)
            for(x = 0; x < 7; x++) {
              document.getElementById(\'d\' + x).style.display = (x == idx ? \'none\' : \'inline-block\'); 
            }
          }
          day_select(1)
          </script>
        ';
            }
        }

        //$ste.= <a class=\"list_a\" data-uk-tooltip title=\"Remove $start_time - $finish_time ($hours Hours)<br />+ all associated staff.\" href=\"JavaScript:remove_time($roster_time_id)\">X</a>

        $str .= '<div class="cl"></div>';

        //$str .= $main_staff_list;
        //return $str;
        $notesFolder = $this->f3->get('download_folder') . "roster_notes/";
        $notesFolderUrl = $this->f3->get('base_url') . "/edge/downloads/roster_notes/";
        $sql = "select roster_times.id as `roster_time_id`, roster_times.minutes_unpaid,roster_times.start_time_date,roster_times.finish_time_date,team_group_name,
    DAYNAME(roster_times.start_time_date) as `start_day`, DAYNAME(roster_times.finish_time_date) as `finish_day`,
    DATE_FORMAT(roster_times.start_time_date, '%d-%b-%Y') as `start_date`,
    DATE_FORMAT(roster_times.start_time_date, '%Y-%m-%d') as `start_date_unformatted`,
    DATE_FORMAT(roster_times.finish_time_date, '%d-%b-%Y') as `finish_date`,
    DATE_FORMAT(roster_times.start_time_date, '%H:%i') as `start_time`,
    DATE_FORMAT(roster_times.finish_time_date, '%H:%i') as `finish_time`,
    DATE_FORMAT(roster_times.start_time_date, '%l:%i %p') as `start_time_p`,
    DATE_FORMAT(roster_times.finish_time_date, '%l:%i %p') as `finish_time_p`,
    roster_times.roster_time_notes,
    roster_times.roster_time_notes_image,
    roster_times.asset_vehicle_id,
    ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2)) as `hours`
    ";

//      (DATE(start_time_date) = DATE(DATE_ADD(NOW(), INTERVAL $days DAY)) or DATE(finish_time_date) = DATE(DATE_ADD(NOW(), INTERVAL $days DAY)))

        if ($by_day_mode) {

            $sql .= "
      , users.name as `site`, rosters.id as `roster_id`
      from roster_times
      left join rosters on rosters.id = roster_times.roster_id
      left join companies on companies.id = rosters.division_id
      left join users on users.id = rosters.site_id
      where rosters.division_id in (" . $loginUserDivisionsStr . ") " . $filterAllowedSiteIds . " and 
      rosters.division_id = $division_id and
      (DATE(roster_times.start_time_date) = '$date_by' or DATE(roster_times.finish_time_date) = '$date_by')
      order by users.name, roster_times.start_time_date, roster_times.finish_time_date
      ";
        } else if ($my_ros_mode) {
            //roster_times.finish_time_date > DATE_ADD(now(), INTERVAL -2 DAY)
            $sql .= "
      , CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname) as `staff`
      , CONCAT(users2.name, ' ', users2.surname) as `site`
      from roster_times_staff
      inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
      inner join rosters on rosters.id = roster_times.roster_id and rosters.is_published >= 1
      left join users on users.id = roster_times_staff.staff_id
      left join users2 on users2.id = rosters.site_id
      where rosters.division_id in (" . $loginUserDivisionsStr . ") " . $filterAllowedSiteIds . " and roster_times_staff.staff_id = $my_id and
      (DATE(roster_times.start_time_date) >= DATE(NOW()) or DATE(roster_times.finish_time_date) >= DATE(NOW()))

      order by roster_times.start_time_date, roster_times.finish_time_date
      ";
        } else {
            $sql .= "
      from roster_times
      where
      roster_id = $rid " . ($sid ? " and id in (select roster_time_id from roster_times_staff where staff_id = $sid)" : "") . "
      order by start_time_date, finish_time_date";
        }

//        echo $sql;
//        die;
        $days = Array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        //return $sql;
//~!@#
        if ($result = $this->dbi->query($sql)) {
            $max_cols = 7;
            $col_count = 0;
            $current_date = date("Y-m-d");
            if (!$view_mode)
                $str .= '<br />';
            if (!$view_mode && !$sid) {
                $str .= '
        <script>
          function save_times(roster_time_id) {
          
            start_time = document.getElementById("txtStart" + roster_time_id).value
            finish_time = document.getElementById("txtFinish" + roster_time_id).value
            $.ajax({
              type:"get",
                  url:"' . $this->f3->get('main_folder') . 'EditRosterTimes",
                  data:{roster_time_id: roster_time_id, start_time: start_time, finish_time: finish_time } ,
                  success:function(msg) {
                    if(msg == 2){
                        alert("Your vehicle assign to another roster in your new time slot. Please remove Vehicle");
                        window.location = "' . $this->f3->get("main_folder") . 'Rostering/EditRoster?rid=' . $rid . '";
                    }else{
                        document.getElementById("hrs" + roster_time_id).innerHTML = msg;
                        
                     }   
                  }
            } );
          }
        </script>
';
                $str .= $itm->setup_ti2();
            }
            $str .= '<table class="roster_grid" border="0" cellpadding="0"><tr>';
            $time_box = new TimeBox;
            $str .= $time_box->load_css();
            while ($myrow = $result->fetch_assoc()) {
//                pr($myrow);
//                echo "<br>";
                if ($roster_time_id)
                    $js_str .= ",";
                $roster_time_id = $myrow['roster_time_id'];

                $rosterShiftUserList = $this->rosterStaffList($roster_time_id);

                $start_day = $myrow['start_day'];
                $finish_day = $myrow['finish_day'];
                $start_time = $myrow['start_time'];
                $finish_time = $myrow['finish_time'];
                $teamGroup = $myrow['team_group_name'];
                $roster_time_notes = $myrow['roster_time_notes'];
                $roster_time_notes_image = $myrow['roster_time_notes_image'];
                $roster_vehicle_id = $myrow['asset_vehicle_id'];
                $start_time_p = $myrow['start_time_p'];
                $finish_time_p = $myrow['finish_time_p'];
                $start_date = $myrow['start_date'];
                $start_date_unformatted = $myrow['start_date_unformatted'];
                $finish_date = $myrow['finish_date'];
                $hours = round($myrow['hours'], 2);
                $minutes_unpaid = $myrow['minutes_unpaid'];
                //$day_total += $hours;
                //$total_hours += $hours;
                $time_bar = ($this->f3->get('is_mobile') || $this->f3->get('is_tablet') ? "" : "<div class=\"timeBoxBlock\">" . $time_box->show($start_time, $finish_time)) . "</div>";

                if ($by_day_mode) {
                    $site = $myrow['site'];
                    $roster_idin = $myrow['roster_id'];
                    if ($old_site != $site) {
                        if ($col_count >= $max_cols) {
                            $str .= "</td></tr><tr>";
                            $col_count = 0;
                        }
                        $col_count++;

                        $str .= ($old_site ? "</td>" : "") . "<td class=\"roster_column\" valign=\"top\"><div class=\"roster_col_header\"><a class=\"roster_col_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=$roster_idin" . ($view_mode ? "&view_mode=$view_mode" : "") . "\">$site</a></div>";
                    }
                } else if ($my_ros_mode) {
                    $site = $myrow['site'];
                    $roster_idin = $myrow['roster_id'];
                    if ($old_start_day != $start_day) {
                        if ($col_count >= $max_cols) {
                            $str .= "</td></tr><tr>";
                            $col_count = 0;
                        }
                        $col_count++;
                        $str .= ($old_start_day ? "</td>" : "") . "<td class=\"roster_column" . ($start_date_unformatted == $current_date ? " today_header" : "") . "\" valign=\"top\"><div class=\"roster_col_header\">$start_day<br />$start_date<br /><br />$site</div>";
                    }
                } else {
                    if ($old_start_day != $start_day) {
                        if ($col_count >= $max_cols) {
                            $str .= "</td></tr><tr>";
                            $col_count = 0;
                        }
                        $col_count++;
                        //if($old_start_day && !$sid && !$view_mode) {
                        //$days_total[] = $day_total;
                        //$day_xtra = "<a class=\"rosa\" data-uk-tooltip title=\"Copy from $old_start_day<br />to $start_day\" href=\"JavaScript:copy_day('$old_start_day','$roster_time_id')\">&gt;</a>";
                        //}



                        $str .= ($old_start_day ? "</td>" : "") . "<td class=\"roster_column" . ($start_date_unformatted == $current_date ? " today_header" : "") . "\" valign=\"top\"><div class=\"roster_col_header\">$day_xtra" . ($view_mode != 2 ? "<a class=\"roster_col_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ByDay?date_by=" . date('Y-m-d', strtotime($start_date)) . "&division_id=$division_id" . ($view_mode == 1 ? "&view_mode=$view_mode" : "") . "\">" : "") . "$start_day" . ($view_mode ? "<br />" : ", ") . "$start_date </a> </div>";
                        //$day_total = 0;
                    }
                }

                //$list_of_staff
                $t = "staff_list$roster_time_id";
                $str .= "<div class='time_item_start'>";
                if ($my_ros_mode) {
                    $str .= ($old_site != $site && $old_site && $old_start_day == $start_day ? "<div class=\"roster_col_header\">$site</div>" : "");

                    //  $str .= "<div data-uk-tooltip title=\"$start_time_p - $finish_time_p ($hours Hrs)\" class=\"time_item\">";
                    $str .= " $start_time-$finish_time ($hours Hrs)</div>";
                    $str .= '<div class="roster_message" id="roster_message' . $roster_time_id . '"></div>';
                    $str .= '<div class="name_list" id="strstaff_list' . $roster_time_id . '">' . $this->RosterUsers($roster_time_id) . ' notes</div>';
                    
                } else {

                    if (!$view_mode && !$sid)
                        $list_of_staff = '<div style="margin-right: 3px;" class="list_staff"><div style="padding-right: 3px;">' . sprintf($list, $t, $t, $t, $t) . '</div></div><div class="roster_message" id="roster_message' . $roster_time_id . '"></div>';
                    $str .= "<div " . ($minutes_unpaid ? "data-uk-tooltip title=\"$minutes_unpaid Unpaid Minutes\"" : "") . " class=\"time_item\">";
                    if (!$view_mode && !$sid) {

                        $itm->blur_xtra = "save_times($roster_time_id);";
                        $start_time = $itm->ti2("txtStart$roster_time_id", "$start_time", ' placeholder="Start"  ', "", "", "");
                        $finish_time = $itm->ti2("txtFinish$roster_time_id", "$finish_time", ' placeholder="Finish"  ', "", "", "");
                    }


                    $notesDis = "<a data-uk-tooltip=\"\" title=\"\" href=\"JavaScript:openNotes('" . $roster_time_id . "')\" aria-expanded=\"false\"><i class=\"fa fa-comment\" style=\"color: blue;font-size:11px;\"></i></a> "
                            . "<div id=\"dialognotes" . $roster_time_id . "\" style=\"display:none\">"
                            . "<form method=\"post\" id=\"notesform" . $roster_time_id . " id=\"notesform" . $roster_time_id . ">" . "Notes: <textarea id=\"notesroster" . $roster_time_id . "\" rows=\"6\" cols=\"45\">$roster_time_notes</textarea></br>";

                    $notesImage = $notesFolder . $roster_time_notes_image;
                    $notesUrlImage = $notesFolderUrl . $roster_time_notes_image;

                    if ($roster_time_notes_image && file_exists($notesImage)) {
                        $notesDis .= '<br><div class="fl"><nobr></nobr>Old Image
<a target="_blank" href="' . $notesUrlImage . '"><img style="max-width: 100px" src="' . $notesUrlImage . '" /></a></div>';
                    }


                    $notesDis .= '<div style=\"clear:both\"></div><div class="fl section5 user3 user4" style="padding-top: 20px;width:250px;clear:both;overflow: hidden"><nobr>
                          Notes Image </nobr><input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload' . $roster_time_id . '" id="fileToUpload' . $roster_time_id . '" onchange="loadImageFile(' . $roster_time_id . ')"></br>
                            <canvas id="myCanvas' . $roster_time_id . '" style="max-width: 150px; height: 0px;"></canvas>
                            <input type="hidden" name="hdnFileName' . $roster_time_id . '" id="hdnFileName' . $roster_time_id . '" />
                            <input type="hidden" name="hdnImage' . $roster_time_id . '" id="hdnImage' . $roster_time_id . '" />';

                            $notesDis .= "</div><div style=\"clear:both\"></div><br>"
                            . "<input type=\"button\" onclick=\"saveNotes('" . $roster_time_id . "')\" value=\" Save Notes \" /><br><br>"
                            ."<input type=\"button\" onclick=\"sendNotesByEmail('" . $roster_id . " ". $roster_time_id . " " . $roster_time_staff_id . " " . $notesUrlImage ." " . $rostered_start_time_date ."')\" value=\" Message Notes To Staff \" />"
                            . "</form>"
                            . "</div>";


                    $vehicleDropDown = $this->assetVehicleDropDown($roster_time_id, $roster_vehicle_id);

                    $addVehicle = " <a data-uk-tooltip=\"\" title=\"\" href=\"JavaScript:addVehicle('" . $roster_time_id . "')\" aria-expanded=\"false\"><i class=\"fa fa-car\" style=\"color: " . ($roster_vehicle_id ? 'green' : 'red') . ";font-size:11px;\"></i></a> "
                            . "<div id=\"dialogVehicle" . $roster_time_id . "\" style=\"display:none\">"
                            . "Vehicle </br>"
                            . $vehicleDropDown . " "
                            . "<input type=\"button\" onclick=\"assignVehicle('" . $roster_time_id . "',1)\" value=\" Assign Vehicle \" /> "
                            . "<input type=\"button\" onclick=\"assignVehicle('" . $roster_time_id . "',0)\" value=\" Reset Vehicle \" /></br>"
                            . ""
                            . "</div>";


                            


                    if (trim($roster_time_notes) != "") {
                        //$notesDis .= "<span style=\"font-size:20px;color:red\">!</span>";
                    }

                    if ($teamGroup) {
                        $teamDis = "<div class=\"fr\">" . $notesDis . $addVehicle . "  &nbsp;</div> <div class=\"fl\"><span style=\"font-weight:bold; color:red;font-size:12px;\">" . $teamGroup . " </b> &nbsp;</div> <div style=\"clear:both\"></div>";
                    } else {
                        $teamDis = "<div class=\"fl\">" . $notesDis . $addVehicle . " </div>";
                    }

                    $str .= '<span class="time_input"><div style="margin-top: 2px;">' . $teamDis . '<div class="startEndTimeA">' . $start_time . '-' . $finish_time . '</div> <span class="hoursB" id="hrs' . $roster_time_id . '">(' . $hours . ' H)</span></span>'
                    . ($view_mode ? '' : '<a data-uk-tooltip title="Remove this Time from the Roster" class="small_delete closeC" href="JavaScript:remove_time(' . $roster_time_id . ')" ><i class="fa fa-trash" style="font-size:9px;"></i></a>')
                    . '</div>' . $time_bar . '</div>' . $list_of_staff;
            
            
                    $str .= '<script>
                    function forceSignOff(roster_time_id, staff_id) {
                        // Assuming youre using AJAX to send the data to the server
                        // You need to adjust this part based on your server-side code or framework
                        
                        // Fetch start and finish times from roster_times table
                        var start_time_date, finish_time_date;
                        $.ajax({
                            url: \'/app/controllers/rosters/getRosterTimes.php\', 
                            method: \'POST\',
                            data: { roster_time_id: roster_time_id },
                            async: false, // Ensure synchronous AJAX request to fetch times before sending the main AJAX request
                            success: function(response) {
                                var times = JSON.parse(response);
                                start_time_date = times.start_time_date;
                                finish_time_date = times.finish_time_date;
                            },
                            error: function(xhr, status, error) {
                                console.error(\'Error fetching start and finish times:\', error);
                            }
                        });
                        
                        // Main AJAX request to insert data into roster_times_staff table
                        $.ajax({
                            url: \'/app/controllers/rosters/forceSignOff.php\', 
                            method: \'POST\',
                            data: {
                                roster_time_id: roster_time_id,
                                staff_id: staff_id,
                                start_time_date: start_time_date,
                                finish_time_date: finish_time_date
                            },
                            success: function(response) {
                                // Handle success response
                                console.log(\'Data inserted successfully:\', response);
                            },
                            error: function(xhr, status, error) {
                                // Handle error
                                console.error(\'Error inserting data:\', error);
                            }
                        });
                    }
                    </script>';
                           
                        



                    $str .= '<div class="name_list" id="strstaff_list' . $roster_time_id . '">' . $this->RosterUsers($roster_time_id) . '</div>';

                    
                }

                $old_site = $site;
                $old_start_day = $start_day;

                $js_str .= "$roster_time_id";
            }
        }
        $str .= '<div class="cl"></div>';
        if (!$my_ros_mode) {


            $str .= '
 
            <script>
            var canvas, ctx, pfix_in
 
                                 oFReader = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
                                 oFReader.onload = function (oFREvent) {
 
                                     var img = new Image();
                                     img.onload = function () {
                                         canvas = document.getElementById(canvas);
                                         pfix = pfix_in
                                         canvas.style.height = "auto";
                                         ctx = canvas.getContext("2d");
 
                                         //alert("Width: " + img.width + ", Height: " + img.height)
                                         //alert("Nat Width: " + img.naturalWidth + ", Nat Height: " + img.naturalHeight)
 
                                         var MaxWidth = 1000
                                         //alert(img.width)
                                         if (img.width >= MaxWidth) {
                                             canvas.width = MaxWidth
                                             canvas.height = (img.height / img.width) * MaxWidth
                                         } else {
                                             canvas.width = img.width
                                             canvas.height = img.height
                                         }
 
                                         ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, canvas.width, canvas.height);
 
                                         //document.getElementById("uploadPreview").src = canvas.toDataURL();  <h3>Image Preview</h3>
                                         document.getElementById("hdnImage" + pfix).value = canvas.toDataURL("image/jpeg", 0.8);
 
                                     }
                                     img.src = oFREvent.target.result;
 
                                 };
 
                                 function loadFile(pfix = "") {
                                     pfix_in = pfix;
                                     file_uploader = "fileToUpload" + pfix;
                                     
                                     if (document.getElementById(file_uploader).files.length === 0) {
                                         return;
                                     }
                                     var oFile = document.getElementById(file_uploader).files[0];
                                     var fileType = oFile.type;
                                     
                                     if (fileType !== "application/pdf" && !fileType.startsWith("image/")) {
                                         alert("You must select a valid image or PDF file!");
                                         document.getElementById("hdnImage" + pfix).value = "";
                                         return;
                                     }
                                     
                                     document.getElementById("hdnFileName" + pfix).value = document.getElementById(file_uploader).value;
                                 
                                     if (fileType === "application/pdf") {
                                         // Handle the PDF file here
                                         // You might want to read the PDF content or perform other operations.
                                         document.getElementById("hdnPDF" + pfix).value = "PDF file uploaded"; // Placeholder action for PDF
                                     } else {
                                         // Handle image file here
                                         // You can load the image or perform other operations.
                                         document.getElementById("hdnImage" + pfix).value = "Image file uploaded"; // Placeholder action for image
                                     }
                                 }
                                 
 
       function openNotes(id){
          $("#dialognotes"+id).dialog({
                 width : 520,
                 height: 620
           });
        }
        function addVehicle(id){
          $("#dialogVehicle"+id).dialog({
                 width : 520,
                 height: 520
           });
        }
        
 
       
         function openLeaves(id){
             $("#dialogLeave"+id).dialog({
                 width : 420,
                 height: 320
           });
         }

         function openForcedSignOff(id){
            $("#dialogSignOff"+id).dialog({
                width : 420,
                height: 320
          });
        }
       
 
   function openStaffLeave(id){
             $("#dialogShiftLeave"+id).dialog({
                 width : 520,
                 height: 520
           });
         }
       
 
 
           function saveNotes(id){
           //data:{ rostertimeid: id,notesText: notesText,action:"addNotes" },
            var notesText = $("#notesroster"+id).val();  
            let uploadedFile = document.getElementById("fileToUpload"+id).files[0];
           
            var formData = new FormData();
            formData.append("notes_image", uploadedFile);
            formData.append("notesText", notesText);
            formData.append("rostertimeid", id);
            formData.append("action","addNotes");
                         $.ajax({
                                 type: "POST",
                                 url: "' . $this->f3->get('main_folder') . 'Rostering/EditRosterTimes",                                
                                 data: formData,
                                 mimeType: "multipart/form-data",
                                 cache: false,
                                 contentType: false,
                                 processData: false,
                                success:function(msg) {
                               // alert(msg);
                                 alert("Notes has been added");
                                 //$("#dialognotes"+id).dialog("close"); 
                                  window.location = "' . $this->f3->get("main_folder") . 'Rostering/EditRoster?rid=' . $rid . '";
                               }
                             });
           }
 
 
           function sendNotesByEmail(id) {
             // Get the URL parameters
             const urlParams = new URLSearchParams(window.location.search);
         
             // Get all anchor elements with class "rnm"
             const anchorTags = document.querySelectorAll("a.rnm");
         
             // Initialize an array to store sid values
             const sidValues = [];
         
             // Loop through each anchor element with the class "rnm"
             anchorTags.forEach(anchor => {
                 // Get the href attribute of each anchor element
                 const href = anchor.getAttribute("href");
         
                 // Extract the sid value from the href string
                 const sidParam = href.match(/sid=([^&]*)/);
                 if (sidParam && sidParam.length > 1) {
                     sidValues.push(sidParam[1]);
                 }
             });
         
             const rid = urlParams.get("rid");
         
             $.ajax({
                 type: "GET",
                 url: "/app/controllers/rosters/sendNotesByEmail.php",
                 data: {
                     rosterId: rid,
                     userId: sidValues.join(","), // Convert sidValues array to a string
                     rid: rid,
                     rostertimeid: id.replace(/\s/g, "").substr(0, 5)
                 },
                 success: function(response) {
                     // Handle the success message or error received from the backend
                     if (response && response.message) {
                         alert("Message successfully sent: " + response.message);
                     } else if (response && response.error) {
                         alert("Error: " + response.error);
                     }
                     alert("Message Sent To Staff Successfully");
                     window.location = "' . $this->f3->get("main_folder") . 'Rostering/EditRoster?rid=' . $rid . '";
                 },
                 error: function(xhr, status, error) {
                     console.error("Failed to send notes by email:", error);
                     alert("Failed to send notes by email. Please try again later.");
                 }
             });
         }
           
 
          
    function assignVehicle(id,defaultVal) {
          if(defaultVal){
                var vehicleId = $("#roster_vehicle"+id).val();
             }else{
                var vehicleId  = 0;
             }
          
           $.ajax({
              type:"post",
                  url:"' . $this->f3->get('main_folder') . 'Rostering/EditRosterTimes",
                  data:{
 rostertimeid: id,vehicleId: vehicleId,action:"assignVehicle" } ,
                  success:function(msg) {
                    if(msg == 1){
                      alert("Vehicle has been assigned");
                       window.location = "' . $this->f3->get("main_folder") . 'Rostering/EditRoster?rid=' . $rid . '";
                    //$("#dialogVehicle"+id).dialog("close"); 
                    }
                    else if(msg == 2){
                    alert("Vehicle has been removed");
                       window.location = "' . $this->f3->get("main_folder") . 'Rostering/EditRoster?rid=' . $rid . '";
                    }
                    else{
                        $("#roster_vehicle"+id).val("");
                        alert("Vehicle already assigned. Please select another")
                    }
                  }
            } );         

          }
          
        function saveStaffLeave(id) {
            alert("leave has been added");
            $("#dialogShiftLeave"+id).dialog("close"); 
//           var leaveType = $("#leaveroster_"+id).val();
//          
//           $.ajax({
//              type:"post",
//                  url:"' . $this->f3->get('main_folder') . 'Rostering/EditRosterTimes",
//                  data:{ rostertimestaffid: id,leaveType: leaveType,action:"addLeave" } ,
//                  success:function(msg) {
//                    alert("Leave has been added");
//                  $("#dialogLeave"+id).dialog("close"); 
//                  }
//            } );         

          }


             function saveLeave(id) {
           var leaveType = $("#leaveroster_"+id).val();
          
           $.ajax({
              type:"post",
                  url:"' . $this->f3->get('main_folder') . 'Rostering/EditRosterTimes",
                  data:{ rostertimestaffid: id,leaveType: leaveType,action:"addLeave" } ,
                  success:function(msg) {
                    alert("Leave has been added");
                  $("#dialogLeave"+id).dialog("close"); 
                  }
            } );         

          }


          function saveForcedSignOff(id) {
            var signOff = $("#signOff_"+id).val();
           
            $.ajax({
               type:"post",
                   url:"/app/controllers/rosters/forceSignOff.php",
                   data:{ rostertimestaffid: id, start_time_date:start_time_date, finish_time_date: finish_time_date } ,
                   success:function(msg) {
                     alert("Sign off has been completed.");
                   $("#dialogSignOff"+id).dialog("close"); 
                   }
             } );         
 
           }
          </script>';

            $str .= '
                


             
     <script>
      roster_time_ids = [' . $js_str . '];
      num_items = parseInt(roster_time_ids.length) - 1
      function refresh_items() {
        for(index = 0; index < roster_time_ids.length; ++index) {
          refresh_item(roster_time_ids[index]);
        }
        $.ajax({
          type:"get",
              url:"' . $this->f3->get('main_folder') . 'GetTotals",
              data:{rid_in: ' . $rid . ', sid_in: ' . $sid . ', date_by_in: "' . $date_by . '"} ,
              success:function(msg) {
                document.getElementById("totals").innerHTML = msg
              }
        } );
      } 
      //alert(roster_time_ids.length)
      setInterval(refresh_items, 20000);

      </script>
      ';
        }
        $str .= '</td></tr></table>';

        if (!$my_ros_mode) {
            /* $_GET['rid_in'] = $rid;
              $_GET['sid_in'] = $sid;
              $_GET['date_by_in'] = $date_by; */
            $str .= '<div id="totals">' . $this->get_totals($rid, $sid, $date_by) . '</div>';
            //$str .= $date_by;
            if (!$my_ros_mode) {
                $str .= '<div class="divhr"><hr /></div><iframe width="100%" height="1000" src="' . $this->f3->get('main_folder') . "Rostering/RosterNotes?roster_id=$rid&show_min=1\"></iframe>";
            }
        }

        /* if(!$sid && !$my_ros_mode) {
          for($x = 0; $x < count($days_total); $x++) {
          $tcount += $days_total[$x];
          $str .= "<td>Total: " . $days_total[$x] . " Hours</td>";
          }
          $str .= "<td>Total: " . ($total_hours-$tcount) . " Hours</td>";

          $str .= '</tr></table><h3>Total Hours: '.$total_hours.'</h3>';
          } */
//
        //$str .= '<div class="cl"></div>' . $main_staff_list . $date_list . '<div class="cl"></div>';
//    $str .= '<div class="cl"></div>';


        if ($my_ros_mode && $cnt)
            $str .= '<br/><br/><a class="list_a" href="' . $this->f3->get('main_folder') . 'Clock2">Click Here to Sign On/Off</a><br/><br/>';

        return $str;
    }

    function CopyRoster($copyid = 0, $copy_date = 0) {
        $copyid = ($copyid ? $copyid : (isset($_GET['copyid']) ? $_GET['copyid'] : null));
        $calCopyDate = ($copy_date ? $copy_date : (isset($_POST["calCopyDate"]) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST["calCopyDate"]))) : null));

        if ($copyid) {
            if ($calCopyDate) {
                $clash = $this->get_sql_result("select id as `result` from rosters where start_date = '$calCopyDate' and site_id = (select site_id from rosters where id = $copyid) and division_id = (select division_id from rosters where id = $copyid)");

                if ($clash) {
                    $str .= "<h3>A roster with this start date has already been created.</h3>"
                            . '<a class="list_a" href="EditRoster?rid=' . $clash . '">Open Conflicting Roster...</a>';
                } else {
                    $sql = "insert into rosters (division_id, site_id, shift_template_id, start_date, requires_licences, requires_confirmation, requires_induction) (select division_id, site_id, shift_template_id, '$calCopyDate', requires_licences, requires_confirmation, requires_induction from rosters where id = $copyid);";
                    $this->dbi->query($sql);
                    $copy_to_id = $this->dbi->insert_id;

                    $date1 = $this->get_by_id("rosters", "start_date", $copyid);
                    $interval = $this->get_sql_result("select datediff('$calCopyDate', '$date1') as `result`");

                    $sql = "select roster_times.id, DATE_ADD(roster_times.start_time_date, INTERVAL $interval DAY) as `start_time_date`, DATE_ADD(roster_times.finish_time_date, INTERVAL $interval DAY) as `finish_time_date`, rosters.requires_confirmation, roster_times.minutes_unpaid from roster_times
          left join rosters on rosters.id = roster_times.roster_id
          where roster_id = $copyid";
                    if ($result = $this->dbi->query($sql)) {
                        $sql_add_staff = "";
                        while ($myrow = $result->fetch_assoc()) {
                            $from_roster_time_id = $myrow['id'];
                            $start_time_date = $myrow['start_time_date'];
                            $finish_time_date = $myrow['finish_time_date'];
                            $requires_confirmation = $myrow['requires_confirmation'];
                            $minutes_unpaid = $myrow['minutes_unpaid'];
                            $sql = "insert into roster_times (roster_id, start_time_date, finish_time_date, minutes_unpaid) values ('$copy_to_id', '$start_time_date', '$finish_time_date', '$minutes_unpaid');";
                            //$str .= "<h3>$sql<h3>";
                            $this->dbi->query($sql);
                            $roster_time_id = $this->dbi->insert_id;
                            $detail = "Shift Created";
                            $this->rosterLog(3, NULL, $roster_time_id, NULL, $detail);

                            $sql = "insert into roster_times_staff (staff_id, roster_time_id " . ($requires_confirmation ? "" : ", status") . ") select staff_id, '$roster_time_id' " . ($requires_confirmation ? "" : ", '1'") . " from roster_times_staff where roster_time_id = $from_roster_time_id;";
                            $this->dbi->query($sql);

                            $roster_time_staff_id = $this->dbi->insert_id;
                            $detail = " Staff has been added";
//                            echo $roster_time_staff_id;
//                            die;
                            $this->rosterLog(4, NULL, NULL, $roster_time_staff_id, $detail);
                            //if($copy_date) {
                        }
                    }
                    if (!$copy_date) {
                        echo $this->redirect($this->f3->get('main_folder') . "Rostering/EditRoster?rid=$copy_to_id");
                    }
                }
            } else {
                $str .= '
        <script>
          function copy_roster() {
            document.frmEdit.submit();
          }
        </script>';
                $itm = new input_item;
                $itm->hide_filter = 1;
                $str .= '<div style="float: left;margin-bottom: 15px;"><a href="/Rostering/Rosters"  class="orgColorBlue" > Roster Manager  </a></div><div class="cl"></div>';
                $str .= '<h3>This will create a new roster with the details and allocated staff from the selected roster.</h3>';
//        $str .= $this->roster_details($copyid,"edit");
                $str .= '<div class="grid-view">' . $this->roster_details($copyid, "copy") . '</div>';
                $str .= $itm->setup_cal();
                $str .= '<br />Please select a start date:<br /><br />';
                $show_date = $this->get_by_id("rosters", "start_date", $copyid);
                $show_date = date('d/M/Y', strtotime('+1 week', strtotime($show_date)));
                $str .= $itm->cal("calCopyDate", $show_date, "style=\"width: 100px;\"", "", "", "", "");
                $str .= '<input onClick="copy_roster()" type="button" value="Generate New Roster" />';
            }
        }


        /* else {
          if($copy_from) {

          $date1 = $this->get_by_id("rosters","start_date",$copy_from);
          $interval = $this->get_sql_result("select datediff('$calCopyDate', '$date1') as `result`");
          //        $str .= "d1: $date1, d2: $calCopyDate, interval: $interval";

          $copy_to = $rid;

          $sql = "select roster_times_staff.staff_id as `staff_id`,
          DATE_ADD(roster_times.start_time_date, INTERVAL $interval DAY)  as `start`, DATE_ADD(roster_times.finish_time_date, INTERVAL $interval DAY)  as `finish`
          from roster_times_staff
          left join roster_times on roster_times.id = roster_times_staff.roster_time_id
          where roster_times.roster_id = $copy_from";
          if($result = $this->dbi->query($sql)) {
          $sql_add_staff = "";
          while($myrow = $result->fetch_assoc()) {
          $staff_id = $myrow['staff_id'];
          $start = $myrow['start'];
          $finish = $myrow['finish'];
          $sql_add_staff = "insert into roster_times_staff (staff_id, roster_time_id) (select '$staff_id', id from roster_times where roster_id = $copy_to and start_time_date = '$start' and finish_time_date = '$finish'); ";
          $this->dbi->query($sql_add_staff);
          }
          //$str .= $sql_add_staff;
          }
          //echo $this->redirect($this->f3->get('main_folder') . "Rostering/EditRoster?rid=$copy_to");
          }

          } */
        return $str;
    }

    function GenerateRoster() {
        $regid = (isset($_GET['regid']) ? $_GET['regid'] : null);

        if ($regid) {
            $str .= '<h3>This will remove all staff from the roster. Are you sure?</h3><br /><br /><a href="' . $this->f3->get('main_folder') . 'Rostering/GenerateRoster?rid=' . $regid . '" class="list_a">Click Here to Continue...</a><br /><br /><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/Rosters">&lt;&lt; Back to Rosters...</a>';
        } else {
            $rid = (isset($_GET['rid']) ? $_GET['rid'] : null);
            //$this->dbi->query("truncate roster_times");

            $sql = "delete from roster_times where roster_id = $rid";
            $this->dbi->query($sql);

            $start_date = $this->get_by_id("rosters", "start_date", $rid);
            $sql = "select start_time, finish_time, start_day, finish_day
      from ros_shift_template_times where shift_template_id in (select shift_template_id from rosters where id = $rid) order by start_day, finish_day, start_time, finish_time";
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $start_time = $myrow['start_time'];
                    $finish_time = $myrow['finish_time'];
                    $start_day = $myrow['start_day'];
                    $finish_day = $myrow['finish_day'];
                    if (!$first_start_day)
                        $first_start_day = $start_day;
                    if (!$first_finish_day)
                        $first_finish_day = $finish_day;
                    $start_days_to_add = $start_day - $first_start_day;
                    $finish_days_to_add = $finish_day - $first_finish_day;

                    $sql = "insert into roster_times (roster_id, start_time_date, finish_time_date) select '$rid', CONCAT(DATE_ADD('$start_date', INTERVAL $start_days_to_add DAY), ' ', '$start_time'), CONCAT(DATE_ADD('$start_date', INTERVAL $finish_days_to_add DAY), ' ', '$finish_time')";
                    //$str .= "<h3>$sql</h3>";
                    $this->dbi->query($sql);
                    $roster_time_id = $this->dbi->insert_id;
                    $detail = "Shift Created";
                    $this->rosterLog(3, NULL, $roster_time_id, NULL, $detail);
                    echo $this->redirect($this->f3->get('main_folder') . "Rostering/EditRoster?rid=$rid");
                    //$str .= $this->message('Roster Times Generated');
                }
            }
        }
        return $str;
    }

    function RostersOld() {

        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);
        $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);
        $itm = new input_item;

        $division_id = $this->division_id;
        $weeks = (isset($_GET['weeks']) ? $_GET['weeks'] : 0);
        //$date_by = (isset($_GET['date_by']) ? $_GET['date_by'] : date("D, d/M/Y", strtotime("monday this week", strtotime(($calByDate ? str_replace('/', '-', $calByDate) : "$weeks weeks")))));

        $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

        $selTask = (isset($_POST["selTask"]) ? $_POST["selTask"] : null);

        /*
          1 'Notify Published Rosters'
          2 'Publish ALL Draft Rosters'
          3 'Notify ALL Draft AND Published'
          4 'UNPUBLISH ALL Published Rosters'
          5 'UNPUBLISH ALL (including sent)'
          6 'Copy This Week to Next Week'
          7 'Copy Last Week to This Week'
          8 'Copy From 2 Weeks ago to This Week'
         */

        if ($selTask) {
            $is_published[1] = "2";
            $is_published[2] = "1";
            $is_published[3] = "2";
            $is_published[4] = "0";
            $is_published[5] = "0";
            $is_published_cond[1] = "=1";
            $is_published_cond[2] = "=0";
            $is_published_cond[3] = "<=1";
            $is_published_cond[4] = "=1";
            $is_published_cond[5] = "<=2";

            if ($selTask == 1 || $selTask == 3) {
                $sms = new sms($this->dbi);
                $message = "Your Roster for Next Week is Ready. To Confirm or Cancel your Shift, Visit " . $this->f3->get('full_url') . "MyRoster";
                $sql = "select distinct(users.id) as `user_id`, CONCAT(users.name, ' ', users.surname) as `name`, users.phone, users.phone2
        from roster_times_staff
        left join users on users.id = roster_times_staff.staff_id
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join rosters on rosters.id = roster_times.roster_id
        left join companies on companies.id = rosters.division_id
        where YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -1 WEEK, 7)
        and rosters.send_notifications = 1 and rosters.is_published " . ($selTask == 1 ? "" : "<") . "= 1 and rosters.division_id = $division_id;";

                if ($result = $this->dbi->query($sql)) {
                    if ($result->num_rows) {
                        $str .= "<h3>The following Notification was Sent</h3>";
                        $str .= "<p>$message</p>";
                        $str .= "<h3>Sent To</h3>";
                        $str .= '<table class="grid">';
                        $str .= "<tr><th>#</th><th>Phone</th><th>Staff Member</th></tr>";
                    } else {
                        //$str .= "<h3>$sql</h3>";
                        $str .= "<h3>No messages to send...</h3>";
                        $str .= "<h4>Either the notification has already been sent or there are no rosters for next week, or next week's rosters are not published...</h4>";
                    }
                    while ($myrow = $result->fetch_assoc()) {
                        $phone = $myrow['phone'];
                        $phone2 = $myrow['phone2'];
                        $user_id = $myrow['user_id'];
                        $name = $myrow['name'];
                        if ($phone = $sms->process_phones($phone, $phone2)) {
                            $count++;
                            $sms->queue_message($phone, $message);
                            $str .= "<tr><td>$count</td><td>$phone</td><td>$name</td></tr>";
                        } else {
                            $str .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>" . ($myrow['phone'] || $myrow['phone2'] ? "Invalid" : "Missing") . " Phone Number</td><td>$name <a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a></td></tr>";
                        }
                    }
                    if ($result->num_rows)
                        $str .= '</table>';
                }
            }
            if ($selTask == 6 || $selTask == 7 || $selTask == 8) {
                // CopyRoster($copyid=0,$copy_date=0) {
                $copy_date = date('Y-m-d', strtotime(($selTask == 6 ? 'next monday' : 'monday this week')));
                $sql = "select id from rosters where division_id = $division_id and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL " . ($selTask - 6) . " WEEK, 7);";

                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $copyid = $myrow['id'];
                        $this->CopyRoster($copyid, $copy_date);
                    }
                }
            } else {
                $sql = "update rosters set is_published = {$is_published[$selTask]} where division_id = $division_id and is_published {$is_published_cond[$selTask]} and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)";
                //$str .= "<h3>$sql</h3>";
                $this->dbi->query($sql);
            }
        }
        /* $sql = "
          SELECT users.id as `id`, CONCAT(users.client_id, ' - ', users.name, ' ', users.surname) as `item_name`
          FROM users
          left join user_status on user_status.id = users.user_status_id
          inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users'
          and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
          ";
          $sites = $itm->cmb("cmbSiteSelect", "", "placeholder=\"Select a Site\" onDblClick=\"clear_field(this)\" class=\"full_width\"", "", $this->dbi, $sql, ""); */

        $str .= '<div class="fl">';

        $str .= $this->division_nav($division_id, 'Rostering/Rosters', 0, 0, 1, ($weeks ? "&weeks=$weeks" : ""));

        //$str .= $this->get_times();

        $str .= '</div>';
        $str .= '<div class="fr">';

        /*    for($i = -2; $i <= $week_count; $i++) {
          $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("-$i Weeks")));
          $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : "$i Weeks Ago")) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == -$i ? "division_nav_selected" : "").'" href="Rosters?weeks='.-$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : $i))  . '</a>';
          } */
        for ($i = -12; $i <= 2; $i++) {
            $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("$i Weeks")));
            $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting ' . $date_from . '" class="division_nav_item ' . ($weeks == $i ? "division_nav_selected" : "") . '" href="Rosters?weeks=' . $i . ($division_id ? "&division_id=$division_id" : "") . ($view_mode ? "&view_mode=$view_mode" : "") . '">'
                    . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i)))) . '</a>';
        }



        $str .= '</div><div class="cl"></div>';

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('rosters.xlsx');
        } else {
            $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
            $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
            if (!$nav_month) {
                $def_month = 1;
//        $nav_month = date("m");
//        $nav_year = date("Y");
                $nav_month = 0;
                $nav_year = 0;
            }
            $compare_date = "$nav_year-" . ($nav_month < 10 ? "0$nav_month" : $nav_month) . "-01";
            //$str .= $compare_date;
            if ($nav_month > 0) {
                $nav1 = "and (MONTH(rosters.start_date) = $nav_month or DATE_ADD(CONCAT(YEAR(rosters.start_date), '-', MONTH(rosters.start_date), '-01'), interval -1 month) = '$compare_date')";
            } else {
                $nav_month = "ALL Months";
            }
            if ($nav_year > 0) {
                $nav2 = "and YEAR(rosters.start_date) = $nav_year";
            } else {
                $nav_year = "ALL Years";
            }
            $itm = new input_item;
            $itm->hide_filter = 1;

            $sql_tasks = "select 0 as id, '---- Select a Task ----' as `item_name` 
              union select 1 as id, 'Notify Published Rosters' as `item_name` 
              union select 2 as id, 'Publish ALL Draft Rosters' as `item_name` 
              union select 3 as id, 'Notify ALL Draft AND Published' as `item_name` 
              union select 4 as id, 'UNPUBLISH ALL Published Rosters' as `item_name`
              union select 5 as id, 'UNPUBLISH ALL (including sent)' as `item_name`
              union select 6 as id, 'Copy This Week to Next Week' as `item_name`
              union select 7 as id, 'Copy Last Week to This Week' as `item_name`
              union select 8 as id, 'Copy From 2 Weeks ago to This Week' as `item_name`
              
              ";

            $nav = new navbar;
            $filter_box = '
        <script language="JavaScript">
          function perform_task() {
            /*var confirmation;
            confirmation = "Are you sure about deleting this item?";
            if (confirm(confirmation)) {
            }*/
            if(document.getElementById("selTask").selectedIndex) {
              document.frmEdit.submit()
            }
          }
          function report_filter() {
            document.getElementById("hdnReportFilter").value=1
            document.frmFilter.submit()
          }
        </script>
        <div class="fl" style="padding: 10px;">
        ' . $itm->sel("selTask", "", 'data-uk-tooltip title="Choose a task."', "", $this->dbi, $sql_tasks, "") . '<input onClick="perform_task()" type="button" value="Go" />' . '
        </div>
        </form>
        <!--
        <form method="GET" name="frmFilter">
        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
        <input type="hidden" name="division_id" value="' . $division_id . '">
        <input type="hidden" name="sid" value="' . $sid . '">
        <div class="fr" style="padding: 10px;">
        ' . $nav->month_year(2016) . '    <input onClick="report_filter()" type="button" value="Go" />
        </form>
        -->
        </div>
      ';
            if ($def_month) {
                $filter_box .= '
          <script language="JavaScript">
            //change_selDate()
          </script>
        ';
            }

//      $str = $filter_box . '</div><div class="cl"></div>';
            $filter_string = ($hdnFilter ? "filter_string" : "");
            //$this->list_obj->title = "Rosters";
            $this->list_obj->show_num_records = 0;
            //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 100;
            $this->list_obj->nav_count = 25;
            $this->editor_obj->remove_from_filter = "and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)";
//            if(rosters.shift_template_id, ros_shift_templates.name, 'N/A') as `Template`,
            $this->list_obj->sql = "
            select distinct(rosters.id) as idin,

            " . ($sid ? "CONCAT(users.name, ' ', users.surname)" : "CONCAT('<a href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\">', users.name, ' ', users.surname, '</a>')") . "
             as `Site`,

            start_date as `Start Date`

            " . ($show_times ? "" : ", 'Edit' as `*`, 'Delete' as `!`") . "
            , if(roster_times.id is null and rosters.shift_template_id != 0, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/GenerateRoster?rid=', rosters.id, '\">Generate Roster</a>')
              , CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\">Manage Roster</a><a data-uk-tooltip title=\"View Mode\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ViewRoster?rid=', rosters.id, '\">View</a>',
                       '<a data-uk-tooltip title=\"Edit the Times and Unpaid Minutes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRosterTimes?rid=', rosters.id, '\">Edit Times</a>',
                       if(rosters.shift_template_id != 0, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/GenerateRoster?regid=', rosters.id, '\">Regenerate Roster</a>'), ''),
                       '<a data-uk-tooltip title=\"Copy to Another Week\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/CopyRoster?copyid=', rosters.id, '\">Copy</a>'
                       )) as `Roster Management`
            , CONCAT(
            if(rosters.requires_licences = 1, 'Licences', '')
            , if(rosters.requires_confirmation = 1 and rosters.requires_licences = 1, ', ', '')
            , if(rosters.requires_confirmation = 1, 'Confirmation', '')
            , if(rosters.requires_induction = 1 and (rosters.requires_licences = 1 || rosters.requires_confirmation = 1), ', ', '')
            , if(rosters.requires_induction = 1, 'Induction', '')
            , if(rosters.requires_confirmation != 1 and rosters.requires_licences != 1 and rosters.requires_induction != 1, 'No Requirements', '')
            , if(rosters.send_notifications = 1, ', Send Notifications', '')
            , if(rosters.auto_update = 1, ', Automatic Sign On/Off', '')

            ) as `Options`
            , if(rosters.is_published = 1, '<span style=\"color: #009900;\">Published</span>', if(rosters.is_published = 2, '<span style=\"color: #009900;\">Published/Sent</span>', '<span style=\"color: #AA0000;\">Draft</span>')) as `Mode`
            FROM rosters
            left join companies on companies.id = rosters.division_id
            left join users on users.id = rosters.site_id
            left join ros_shift_templates on ros_shift_templates.id = rosters.shift_template_id
            left join roster_times on roster_times.roster_id = rosters.id
            where " . ($show_times ? "rosters.id = $show_times" : "1 $filter_string") . " and rosters.division_id = $division_id
            " . ($sid ? " and users.id = $sid " : "") . "
            {$this->editor_obj->remove_from_filter}
            $nav1 $nav2
            $sort_xtra
            order by rosters.start_date DESC
        ";

//            echo $this->list_obj->sql;
//            die;
            //return "<textarea>" . $this->list_obj->sql . '</textarea>';
            //$this->editor_obj->add_now = "updated_date";
            //$this->editor_obj->update_now = "updated_date";
            if ($show_times) {
                $str .= $this->list_obj->draw_list();
                $str .= '<iframe width="100%" height="1000" src="' . $this->f3->get('main_folder') . "Rostering/RosterTimes?lookup_id=$show_times&show_min=1\"></iframe>";
            } else {
                $str .= '<div class="form-wrapper"><div class="form-header" style="padding: 0px 0px 0px 10px !important;"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td>Rosters</td><td align="right">' . $filter_box . '</td></tr></table></div>';
                //$this->editor_obj->custom_field = "staff_id";
                //$this->editor_obj->custom_value = $_SESSION['user_id'];
                $this->editor_obj->table = "rosters";
                $style = 'class="full_width"';
                $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"

                $this->editor_obj->xtra_id_name = "division_id";
                $this->editor_obj->xtra_id = $division_id;

                $site_sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users'";

                $template_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT ros_shift_templates.id as `idin`, ros_shift_templates.name as `item_name` FROM ros_shift_templates";

                $this->editor_obj->form_attributes = array(
                    array("cmbSite", "calStartDate", "chkRequiresLicences", "chkRequiresConfirmation", "chkRequiresInduction", "chkIsPublished", "chkSendNotifications", "chkAutoUpdate"),
                    array("Site", "Start Date", "Requires Licences", "Requires Confirmation", "Requires Induction", "Is Published", "Send Notifcations", "Automatic Sign On/Off"),
                    array("site_id", "start_date", "requires_licences", "requires_confirmation", "requires_induction", "is_published", "send_notifications", "auto_update"),
                    array($site_sql),
                    array($style, $style, "", "", "", "", "checked", ""),
                    array("c", "n", "n")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset", "Filter"),
                    array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
                );
                $this->editor_obj->form_template = '
                  <div class="fl large_textbox"><nobr>tcmbSite</nobr><br />cmbSite</div>
                  <div class="fl small_textbox"><nobr>tcalStartDate</nobr><br />calStartDate</div>
                  <div class="fl">
                  <nobr>chkRequiresLicences tchkRequiresLicences</nobr>
                  <br /><nobr>chkRequiresConfirmation tchkRequiresConfirmation</nobr>
                  <br /><nobr>chkRequiresInduction tchkRequiresInduction</nobr>
                  </div>
                  <div class="fl">
                  <nobr>chkIsPublished tchkIsPublished</nobr>
                  <br /><nobr>chkSendNotifications tchkSendNotifications</nobr>
                  <br /><nobr>chkAutoUpdate tchkAutoUpdate</nobr>
                  </div>
                  <div class="cl"></div>
                  <br />
                  ' . $this->editor_obj->button_list();

                $this->editor_obj->editor_template = '
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>';

                //$this->editor_obj->editor_template .= '';  //Any text between form and list
                $this->editor_obj->editor_template .= 'editor_list';

                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
                if ($sid)
                    $str .= '<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/Rosters' . ($division_id ? "?division_id=$division_id" : "") . '">Show All Sites</a>';
            }
            if ($action == "delete_record") {
                $idin = $_POST['idin'];
                $sql = "delete from roster_times_staff where roster_time_id in (select id from roster_times where roster_id = $idin);";
                $sql .= "delete from roster_times where roster_id = $idin;";
                $this->dbi->multi_query($sql);
            }

            /* if($action == "add_record") {
              } else if($action == "save_record") {
              } */
            //return $action;
            return $str;
        }
    }

    function Rosters() {

        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);
        $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);
        $itm = new input_item;
        if ($action == "delete_record") {
            $idin = $_POST['idin'];
            $detail = "Roster Deleted";
            $this->rosterLog(2, $idin, NULL, NULL, $detail);
        }

        $division_id = $this->division_id;
        $weeks = (isset($_GET['weeks']) ? $_GET['weeks'] : 0);
        //$date_by = (isset($_GET['date_by']) ? $_GET['date_by'] : date("D, d/M/Y", strtotime("monday this week", strtotime(($calByDate ? str_replace('/', '-', $calByDate) : "$weeks weeks")))));

        $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

        $selTask = (isset($_POST["selTask"]) ? $_POST["selTask"] : null);

        /*
          1 'Notify Published Rosters'
          2 'Publish ALL Draft Rosters'
          3 'Notify ALL Draft AND Published'
          4 'UNPUBLISH ALL Published Rosters'
          5 'UNPUBLISH ALL (including sent)'
          6 'Copy This Week to Next Week'
          7 'Copy Last Week to This Week'
          8 'Copy From 2 Weeks ago to This Week'
         */
        if ($selTask) {
            $is_published[1] = "2";
            $is_published[2] = "1";
            $is_published[3] = "2";
            $is_published[4] = "0";
            $is_published[5] = "0";
            $is_published_cond[1] = "=1";
            $is_published_cond[2] = "=0";
            $is_published_cond[3] = "<=1";
            $is_published_cond[4] = "=1";
            $is_published_cond[5] = "<=2";

            if ($selTask == 1 || $selTask == 3) {
                $sms = new sms($this->dbi);
                $message = "Your Roster for Next Week is Ready. To Confirm or Cancel your Shift, Visit " . $this->f3->get('full_url') . "MyRoster";
                $sql = "select distinct(users.id) as `user_id`, CONCAT(users.name, ' ', users.surname) as `name`, users.phone, users.phone2
        from roster_times_staff
        left join users on users.id = roster_times_staff.staff_id
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join rosters on rosters.id = roster_times.roster_id
        left join companies on companies.id = rosters.division_id
        where YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -1 WEEK, 7)
        and rosters.send_notifications = 1 and rosters.is_published " . ($selTask == 1 ? "" : "<") . "= 1 and rosters.division_id = $division_id;";

                if ($result = $this->dbi->query($sql)) {
                    // $str .= date('Y-m-d h:i:s');
                    if ($result->num_rows) {
                        $str .= "<h3>The following Notification was Sent</h3>";
                        $str .= "<p>$message</p>";
                        $str .= "<h3>Sent To</h3>";
                        $str .= '<table class="grid">';
                        $str .= "<tr><th>#</th><th>Phone</th><th>Staff Member</th></tr>";
                    } else {
                        //$str .= "<h3>$sql</h3>";
                        $str .= "<h3>No messages to send...</h3>";
                        $str .= "<h4>Either the notification has already been sent or there are no rosters for next week, or next week's rosters are not published...</h4>";
                    }
                    while ($myrow = $result->fetch_assoc()) {
                        $phone = $myrow['phone'];
                        $phone2 = $myrow['phone2'];
                        $user_id = $myrow['user_id'];
                        $name = $myrow['name'];
                        if ($phone = $sms->process_phones($phone, $phone2)) {
                            $count++;
                            $sms->queue_message($phone, $message);
                            $str .= "<tr><td>$count</td><td>$phone</td><td>$name</td></tr>";
                        } else {
                            $str .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>" . ($myrow['phone'] || $myrow['phone2'] ? "Invalid" : "Missing") . " Phone Number</td><td>$name <a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a></td></tr>";
                        }
                    }
                    if ($result->num_rows)
                        $str .= '</table>';
                }
            }

            //    $str .= "</br>".date('Y-m-d h:i:s');
            if ($selTask == 6 || $selTask == 7 || $selTask == 8) {
                // CopyRoster($copyid=0,$copy_date=0) {
                $copy_date = date('Y-m-d', strtotime(($selTask == 6 ? 'next monday' : 'monday this week')));
                $sql = "select id from rosters where division_id = $division_id and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL " . ($selTask - 6) . " WEEK, 7);";

                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $copyid = $myrow['id'];
                        $this->CopyRoster($copyid, $copy_date);
                    }
                }
            } else {
                $sql = "update rosters set is_published = {$is_published[$selTask]} where division_id = $division_id and is_published {$is_published_cond[$selTask]} and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)";
                //$str .= "<h3>$sql</h3>";
                $this->dbi->query($sql);
            }
        }
        /* $sql = "
          SELECT users.id as `id`, CONCAT(users.client_id, ' - ', users.name, ' ', users.surname) as `item_name`
          FROM users
          left join user_status on user_status.id = users.user_status_id
          inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users'
          and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
          ";
          $sites = $itm->cmb("cmbSiteSelect", "", "placeholder=\"Select a Site\" onDblClick=\"clear_field(this)\" class=\"full_width\"", "", $this->dbi, $sql, ""); */
        //     $str .= "3".date('Y-m-d h:i:s');
        $str .= '<div class="fl">';

        $str .= $this->division_nav($division_id, 'Rostering/Rosters', 0, 0, 1, ($weeks ? "&weeks=$weeks" : ""));

        //$str .= $this->get_times();

        $str .= '</div>';
        $str .= '<div class="fr">';

        /*    for($i = -2; $i <= $week_count; $i++) {
          $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("-$i Weeks")));
          $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : "$i Weeks Ago")) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == -$i ? "division_nav_selected" : "").'" href="Rosters?weeks='.-$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : $i))  . '</a>';
          } */
//    for($i = -12; $i <= 2; $i++) {
//      $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("$i Weeks")));
//      $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == $i ? "division_nav_selected" : "").'" href="Rosters?weeks='.$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">'
//      . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i)))) . '</a>';
//    }
//        $str .= '<select id="select_week" class="mb-3 weekRoasterChange" onChange="weekRoasterChange()"  name="select_week">';
//        $str .= '<option value=""> <a>Select Week </a> </option>';
//        for ($i = -12; $i <= 2; $i++) {
//            $weeksSelected = $_REQUEST['weeks'];
//            if ($i == $weeksSelected) {
//                $selected = "selected";
//            } else {
//                $selected = "";
//            }
//            $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("$i Weeks")));
//            $str .= '<option value=' . $i . ' ' . $selected . '> <a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting ' . $date_from . '" class="division_nav_item ' . ($weeks == $i ? "division_nav_selected" : "") . '" href="Rosters?weeks=' . $i . ($division_id ? "&division_id=$division_id" : "") . ($view_mode ? "&view_mode=$view_mode" : "") . '">'
//                    . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '</a></option>';
//
//            /*      $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == $i ? "division_nav_selected" : "").'" href="Rosters?weeks='.$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">'
//              . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i)))) . '</a>'; */
//        }
//        $str .= "</select>";

        $str .= '</div><div class="cl"></div><br>';
//$str .= "4".date('Y-m-d h:i:s');
        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('rosters.xlsx');
        } else {
            $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
            $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
            if (!$nav_month) {
                $def_month = 1;
//        $nav_month = date("m");
//        $nav_year = date("Y");
                $nav_month = 0;
                $nav_year = 0;
            }
            $compare_date = "$nav_year-" . ($nav_month < 10 ? "0$nav_month" : $nav_month) . "-01";
            //$str .= $compare_date;
            if ($nav_month > 0) {
                $nav1 = "and (MONTH(rosters.start_date) = $nav_month or DATE_ADD(CONCAT(YEAR(rosters.start_date), '-', MONTH(rosters.start_date), '-01'), interval -1 month) = '$compare_date')";
            } else {
                $nav_month = "ALL Months";
            }
            if ($nav_year > 0) {
                $nav2 = "and YEAR(rosters.start_date) = $nav_year";
            } else {
                $nav_year = "ALL Years";
            }
            $itm = new input_item;
            $itm->hide_filter = 1;

            $sql_tasks = "select 0 as id, '---- Select a Task ----' as `item_name` 
              union select 1 as id, 'Notify Published Rosters' as `item_name` 
              union select 2 as id, 'Publish ALL Draft Rosters' as `item_name` 
              union select 3 as id, 'Notify ALL Draft AND Published' as `item_name` 
              union select 4 as id, 'UNPUBLISH ALL Published Rosters' as `item_name`
              union select 5 as id, 'UNPUBLISH ALL (including sent)' as `item_name`
              union select 6 as id, 'Copy This Week to Next Week' as `item_name`
              union select 7 as id, 'Copy Last Week to This Week' as `item_name`
              union select 8 as id, 'Copy From 2 Weeks ago to This Week' as `item_name`
              
              ";

            $nav = new navbar;

            $filter_box .= '<div class="fl" style="padding-top:12px">';
            $filter_box .= '<select id="select_week" class="mb-3 weekRoasterChange" onChange="weekRoasterChange()"  name="select_week">';
            $filter_box .= '<option value=""> <a>Select Week </a> </option>';
            for ($i = -12; $i <= 2; $i++) {
                $weeksSelected = $_REQUEST['weeks'];
                if ($i == $weeksSelected) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }
                $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("$i Weeks")));
                $filter_box .= '<option value=' . $i . ' ' . $selected . '> <a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting ' . $date_from . '" class="division_nav_item ' . ($weeks == $i ? "division_nav_selected" : "") . '" href="Rosters?weeks=' . $i . ($division_id ? "&division_id=$division_id" : "") . ($view_mode ? "&view_mode=$view_mode" : "") . '">'
                        . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '</a></option>';

                /*      $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == $i ? "division_nav_selected" : "").'" href="Rosters?weeks='.$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">'
                  . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i)))) . '</a>'; */
            }
            $filter_box .= "</select>";

            $filter_box .= '</div><div class="cl"></div><script>  
            function exportCSVRosterReport(){
                var locationId =  $("#userLocation").val();
                var employeeId =  $("#userEmp").val();
                var CltId =  $("#userClt").val();
                var reportStartDate =  $("#calReportStartDate").val().trim();
                var calReportEndDate =  $("#calReportEndDate").val().trim();
                var calReportStartTime =  $("#ti2StartTime").val();
                var calReportEndTime   =  $("#ti2FinishTime").val();
        
                if(reportStartDate == ""){
                    alert("Please select report Start Date");
                }
                else if(calReportEndDate == ""){
                    alert("Please select report End Date");
                }else{   
                    // Create a hidden form
                    var form = $("<form action=\"/export-csv-roster-data\" method=\"GET\"></form>");
                    form.append("<input type=\"hidden\" name=\"CltId\" value=\"" + CltId + "\">");
                    form.append("<input type=\"hidden\" name=\"employee_id\" value=\"" + employeeId + "\">");
                    form.append("<input type=\"hidden\" name=\"location_id\" value=\"" + locationId + "\">");
                    form.append("<input type=\"hidden\" name=\"report-start-date\" value=\"" + reportStartDate + "\">");
                    form.append("<input type=\"hidden\" name=\"report-end-date\" value=\"" + calReportEndDate + "\">");
                    form.append("<input type=\"hidden\" name=\"report-start-time\" value=\"" + calReportStartTime + "\">");
                    form.append("<input type=\"hidden\" name=\"report-end-time\" value=\"" + calReportEndTime + "\">");
                    
                    // Append form to body and submit
                    $("body").append(form);
                    form.submit();
                }
            }
        </script>';
        
        
        
        
       



            

            



//            $filter_box = '
//        <script language="JavaScript">
//          function perform_task() {
//            /*var confirmation;
//            confirmation = "Are you sure about deleting this item?";
//            if (confirm(confirmation)) {
//            }*/
//            if(document.getElementById("selTask").selectedIndex) {
//              document.frmEdit.submit()
//            }
//          }
//          function report_filter() {
//            document.getElementById("hdnReportFilter").value=1
//            document.frmFilter.submit()
//          }
//        </script>
//        <div class="fl fright" style="padding: 10px;">
//        ' . $itm->sel("selTask", "adfasd", 'data-uk-tooltip class="radius-right-zero" title="Choose a task."', "", $this->dbi, $sql_tasks, "") . '<input onClick="perform_task()" class="goBtnSearch" type="button" value="Go" />' . '
//        </div>
//        </form>
//        <!--
//        <form method="GET" name="frmFilter">
//        <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
//        <input type="hidden" name="division_id" value="' . $division_id . '">
//        <input type="hidden" name="sid" value="' . $sid . '">
//        <div class="fr" style="padding: 10px;">
//        ' . $nav->month_year(2016) . '    <input onClick="report_filter()" type="button" value="Go" />
//        </form>
//        -->
//        </div>
//      ';
//$str .= " 5".date('Y-m-d h:i:s');
            if ($def_month) {
                $filter_box .= '
          <script language="JavaScript">
            //change_selDate()
            $( document ).ready(function() {
    console.log( "ready!" );
});
          </script>
        ';
            }
            //        $str .= " 6".date('Y-m-d h:i:s');
            $filter_box .= '<script language="JavaScript">
                
            $(document).ready(function() {
               
                var filter_message = $("#filter_message").html();
                $(".filter_message_new").html(filter_message); 
                $("#filter_message").html("");
            });
          </script>';

//      $str = $filter_box . '</div><div class="cl"></div>';
            $filter_string = ($hdnFilter ? "filter_string" : "");
            //$this->list_obj->title = "Rosters";
            $this->list_obj->show_num_records = 0;
            //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 100;
            $this->list_obj->nav_count = 25;
            $this->editor_obj->remove_from_filter = "and YEARWEEK(rosters.start_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)";
//            if(rosters.shift_template_id, ros_shift_templates.name, 'N/A') as `Template`,
            if (!$division_id) {
                $division_id = 0;
            }

            $rosterIngStateIds = $this->rosteringStateIds($_SESSION['user_id']);

            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);

            if (!$loginUserDivisionsStr) {
                $loginUserDivisionsStr = 0;
            }

            $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);
            // prd($userAllowedSiteIds);

            if ($userAllowedSiteIds != "all") {
                $userSiteAllowedconditon = " and users.id in (" . $userAllowedSiteIds . ")";
                $filterAllowedSiteIds = "and rosters.site_id in (" . $userAllowedSiteIds . ")";
            }


            //and location.state in (" . $rosterIngStateIds . ") 


            $this->list_obj->sql = "
            select distinct(rosters.id) as idin,

            " . ($sid ? "CONCAT(users.name, ' ', users.surname)" : "CONCAT('<a href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\">', users.name, ' ', users.surname, '</a>')") . "
             as `Site`,

            start_date as `Start Date`

           
            , if(roster_times.id is null and rosters.shift_template_id != 0, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/GenerateRoster?rid=', rosters.id, '\">Generate Roster</a>')
              , CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=', rosters.id, '\"><i class=\"fa fa-user\" ></i>
 <!Manage Roster -->
 </a><a data-uk-tooltip title=\"View Mode\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ViewRoster?rid=', rosters.id, '\"><i class=\"fa fa-eye\" ></i></a>',
                       '<a data-uk-tooltip title=\"Edit the Times and Unpaid Minutes\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRosterTimes?rid=', rosters.id, '\"><i class=\"fa fa-edit\" ></i></a>',
                       if(rosters.shift_template_id != 0, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/GenerateRoster?regid=', rosters.id, '\"><i class=\"fa fa-retweet\" ></i></a>'), ''),
                       '<a data-uk-tooltip title=\"Copy to Another Week\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/CopyRoster?copyid=', rosters.id, '\"><i class=\"fa fa-clone\" ></i></a>'
                       )) as `Roster Management`
            , CONCAT(
            if(rosters.requires_licences = 1, 'Licences', '')
            , if(rosters.requires_confirmation = 1 and rosters.requires_licences = 1, ', ', '')
            , if(rosters.requires_confirmation = 1, 'Confirmation', '')
            , if(rosters.requires_induction = 1 and (rosters.requires_licences = 1 || rosters.requires_confirmation = 1), ', ', '')
            , if(rosters.requires_induction = 1, 'Induction', '')
            , if(rosters.requires_confirmation != 1 and rosters.requires_licences != 1 and rosters.requires_induction != 1, 'No Requirements', '')
            , if(rosters.send_notifications = 1, ', Send Notifications', '')
            , if(rosters.auto_update = 1, ', Automatic Sign On/Off', '')

            ) as `Options`
            , if(rosters.is_published = 1, '<span style=\"color: #009900;\">Published</span>', if(rosters.is_published = 2, '<span style=\"color: #009900;\">Published/Sent</span>', '<span style=\"color: #AA0000;\">Draft</span>')) as `Mode`
             " . ($show_times ? "" : ",'Edit' as `Edit`, 'Delete' as `Delete`") . "
            FROM rosters
            left join companies on companies.id = rosters.division_id
            left join users on users.id = rosters.site_id
            left join ros_shift_templates on ros_shift_templates.id = rosters.shift_template_id
            left join roster_times on roster_times.roster_id = rosters.id
            left join users as location on location.id = rosters.site_id
            where users.id IS NOT NULL and " . ($show_times ? "rosters.id = $show_times" : "1 $filter_string") . " "
                    . " $filterAllowedSiteIds and   rosters.division_id in (" . $loginUserDivisionsStr . ") and rosters.division_id = $division_id
            " . ($sid ? " and users.id = $sid " : "") . "
            {$this->editor_obj->remove_from_filter}
            $nav1 $nav2
            $sort_xtra
            order by CONCAT(users.name, ' ', users.surname) asc, rosters.start_date DESC
        ";

            //           $str .= " 7 ".date('Y-m-d h:i:s');
//             echo  $this->list_obj->sql;
//             DIE;
//            echo $this->list_obj->sql;
//            die;
//            echo $this->list_obj->sql;
//            die;
            //return "<textarea>" . $this->list_obj->sql . '</textarea>';
            //$this->editor_obj->add_now = "updated_date";
            //$this->editor_obj->update_now = "updated_date";
            if ($show_times) {
                $str .= $this->list_obj->draw_list();
                $str .= '<iframe width="100%" height="1000" src="' . $this->f3->get('main_folder') . "Rostering/RosterTimes?lookup_id=$show_times&show_min=1\"></iframe>";
            } else {
                $str .= '<div class="form-wrapper headingTitle"><div class="form-header" style="padding: 0px 0px 0px 10px !important;"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td width="10%">Rosters </td><td>' . $filter_box . '</td><td align="right"></td></tr></table></div>';
                //$this->editor_obj->custom_field = "staff_id";
                //$this->editor_obj->custom_value = $_SESSION['user_id'];
                $this->editor_obj->table = "rosters";
                $style = 'class="full_width"';
                $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"

                $this->editor_obj->xtra_id_name = "division_id";
                $this->editor_obj->xtra_id = $division_id;

                //$userSiteAllowedconditon = "";
                //  select companies.id, companies.item_name from users_user_division_groups inner join companies on companies.id = users_user_division_groups .user_group_id where users_user_division_groups .user_id = $user_id
                //users.state in (".$rosterIngStateIds.")   and 

                if ($_REQUEST['userClt']) {
                    $reportUserClt = $_REQUEST['userClt'];
                } else {
                    $reportUserClt = 0;
                }

// and users_user_division_groups.user_group_id in (" . $loginUserDivisionsStr . ") $userSiteAllowedconditon

                $site_sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
        inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
        where users_user_division_groups.user_group_id in (" . $loginUserDivisionsStr . ") $userSiteAllowedconditon group by users.id";

                $site_sql_new = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` 
                    FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' 
        inner join users_user_division_groups on users_user_division_groups.user_group_id = '" . $division_id . "' and users_user_division_groups.user_id = lookup_answers.foreign_id
        LEFT JOIN associations ass1 ON ass1.child_user_id = users.ID AND ass1.association_type_id = 1
        where ass1.parent_user_id = '" . $reportUserClt . "' group by users.id";

                $locationRecords = $this->getRecordIds($site_sql_new);
//$str .= " 8 ".date('Y-m-d h:i:s');
                $selLocation = "<div class='fl' style='padding-right:10px'> "
                        . "<select class='large_textbox' name='userLocation' id ='userLocation'><option value=\"\"> Select location </option>";

                foreach ($locationRecords as $location) {
//                    if (in_array($location['id'], $assignedSiteArray)) {
//                        $selected = "selected";
//                    } else {
                    $selected = "";
                    //}

                    $locationNameSelect = $location['item_name'];

                    $selLocation .= '<option value="' . $location['id'] . '" ' . $selected . '>' . $locationNameSelect . '</option>';
                }
                $selLocation .= '</select></div>';

                $employee_sql = "SELECT users.id as `id`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
      inner join roster_times_staff rts on rts.staff_id = users.ID where rts.staff_id IS NOT NULL group by users.id ORDER BY users.name asc";

                $employeeRecords = $this->getRecordIds($employee_sql);
//$str .= " 9 ".date('Y-m-d h:i:s');
                $selEmp = "<div class='fl' style='padding-right:10px'> "
                        . "<select class='large_textbox' name='userEmp' id ='userEmp'><option value=\"\"> Select Employee </option>";

                foreach ($employeeRecords as $employee) {
//                    if (in_array($location['id'], $assignedSiteArray)) {
//                        $selected = "selected";
//                    } else {
                    $selected = "";
                    //}

                    $empNameSelect = $employee['item_name'];

                    $selEmp .= '<option value="' . $employee['id'] . '" ' . $selected . '>' . $empNameSelect . '</option>';
                }
                $selEmp .= '</select></div>';
//$str .= " 10 ".date('Y-m-d h:i:s');
                //emp.ID IS NOT NULL AND location.ID IS NOT NULL AND 
//                $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
//FROM roster_times_staff rts
//LEFT JOIN users AS emp ON emp.ID = rts.staff_id
//LEFT Join roster_times rt ON rt.id = rts.roster_time_id 
//LEFT JOIN rosters rst ON rst.id = rt.roster_id
//left join users as location on location.id = rst.site_id
//LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
//LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
//WHERE clt.id IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104)and rst.division_id in (" . $loginUserDivisionsStr . ")
//    group by clt.id
//ORDER BY CONCAT('', clt.name, ' ', clt.surname, '') asc";


                $clt_sql = "SELECT clt.id as `id`,CONCAT('', clt.name, ' ', clt.surname, '') as 'item_name'
FROM rosters rst
left join users as location on location.id = rst.site_id
LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
WHERE clt.id IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104)and rst.division_id in (" . $loginUserDivisionsStr . ")
    group by clt.id
ORDER BY CONCAT('', clt.name, ' ',TRIM(clt.surname), '') asc";

                $cltRecords = $this->getRecordIds($clt_sql);
                // prd($cltRecords);
                //  prd($_REQUEST);
//$str .= " 11 ".date('Y-m-d h:i:s'); //$clt_sql;
                $selClt = "<div class='fl' style='padding-right:10px'> "
                        . "<select class='large_textbox' name='hdncmbSite' id ='hdncmbSite'><option value=\"\"> Select Client </option>";

                foreach ($cltRecords as $client) {
                    if ($client['id'] == $_REQUEST['userClt']) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }

                    $clientNameSelect = $client['item_name'];

                    $selClt .= '<option value="' . $client['id'] . '" ' . $selected . '>' . $clientNameSelect . '</option>';
                }
                $selClt .= '</select></div>';

//                echo $site_sql;
//                die;

                $template_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT ros_shift_templates.id as `idin`, ros_shift_templates.name as `item_name` FROM ros_shift_templates";

                $this->editor_obj->form_attributes = array(
                    array("cmbReportSite", "cmbSite", "calStartDate", "chkRequiresLicences", "chkRequiresConfirmation", "chkRequiresInduction", "chkIsPublished", "chkSendNotifications", "chkAutoUpdate"),
                    array("Location", "Site", "Start Date", "Requires Licences", "Requires Confirmation", "Requires Induction", "Is Published", "Send Notifcations", "Automatic Sign On/Off"),
                    array("site_id", "site_id", "start_date", "requires_licences", "requires_confirmation", "requires_induction", "is_published", "send_notifications", "auto_update"),
                    array($site_sql, $site_sql),
                    array($style, $style, $style, "", "", "", "", "checked", ""),
                    array("", "c", "n", "n")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset", "Filter"),
                    array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter"),
                    array(),
                    array('addicon', 'saveicon', 'reseticon', 'filtericon')
                );
                $this->editor_obj->form_template = '
                     <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
 $(document).ready(function() {
    $(".userLocationClass").select2();
});
</script>
            <div class="mainbox w70">
                <div class="section1">
                ' . $selClt1 . ' <!-- Inserted client select dropdown here -->
                ' . $selLocation1 . ' <!-- Inserted site/location select dropdown -->
                      <div class="fl large_textbox mr-20"><nobr>tcmbSite</nobr><br />cmbSite</div>                   
                      <div class="fl small_textbox calicon"><nobr>tcalStartDate</nobr><br />calStartDate</div>
               </div>
               <div class="section2">
                      <div class="fl lcheckBox">
                      <nobr>chkRequiresLicences tchkRequiresLicences</nobr>
                      <nobr>chkRequiresConfirmation tchkRequiresConfirmation</nobr>
                      <nobr>chkRequiresInduction tchkRequiresInduction</nobr>

                      <nobr>chkIsPublished tchkIsPublished</nobr>
                     <nobr>chkSendNotifications tchkSendNotifications</nobr>
                      <nobr>chkAutoUpdate tchkAutoUpdate</nobr>
                      </div>
                      <div class="cl"></div>
               </div>
               </br>
              
           </div>
                  
                   <div class="fl button-sec">
                
                  ' . $this->editor_obj->button_list() . ' </br><span class="filter_message_new"><br/> </span></div>';
//$str .= " 12 ".date('Y-m-d h:i:s');
                $filter_box2 .= '<div class="cl"></div><div class="fr" style="padding-top:12px">';
                $filter_box2 .= '<select id="select_week" class="mb-3 weekRoasterChange" onChange="weekRoasterChange()"  name="select_week">';
                $filter_box2 .= '<option value=""> <a>Select Week </a> </option>';
                for ($i = -12; $i <= 2; $i++) {
                    $weeksSelected = $_REQUEST['weeks'];
                    if ($i == $weeksSelected) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }
                    $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("$i Weeks")));
                    $filter_box2 .= '<option value=' . $i . ' ' . $selected . '> <a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting ' . $date_from . '" class="division_nav_item ' . ($weeks == $i ? "division_nav_selected" : "") . '" href="Rosters?weeks=' . $i . ($division_id ? "&division_id=$division_id" : "") . ($view_mode ? "&view_mode=$view_mode" : "") . '">'
                            . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '</a></option>';

                    /*      $str .= '<a data-uk-tooltip title="Show rosters from<br />' . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i) . " Weeks " . ($i < 0 ? 'Ago' : 'From Now')))) . '.<br />Starting '.$date_from.'" class="division_nav_item '.($weeks == $i ? "division_nav_selected" : "").'" href="Rosters?weeks='.$i.($division_id ? "&division_id=$division_id" : "").($view_mode ? "&view_mode=$view_mode" : "").'">'
                      . ($i == 0 ? 'This Week' : ($i == -1 ? 'Last Week' : ($i == 1 ? 'Next Week' : abs($i)))) . '</a>'; */
                }
                $filter_box2 .= "</select>";

                $itm = new input_item;
                $startTimeJs = $itm->setup_ti2();
//$str .= " 13 ".date('Y-m-d h:i:s');
                //$this->editor_obj->form_template .= $filter_box2;

                $this->editor_obj->editor_template = $startTimeJs . '
                    <div class="form-content searchFilter">
                    editor_form
                    </div></div>
                    <div class="">
                     <div class="small_textbox calicon"><nobr>
                      <b>Report</b>
                         
                       </div>
                        <form metho="post">
                    <div class="fl small_textbox calicon"><nobr>
                   
                      <nobr>Start Date</nobr>
                          <input readonly="" onclick="javascript:NewCssCal(&quot;calReportStartDate&quot;, &quot;ddMMMyyyy&quot;)" value = "' . $_REQUEST["calReportStartDate"] . '"  type="text" id="calReportStartDate" name="calReportStartDate" class="full_width" value="">
                             
                       </div>
                         
                       <div class="fl small_textbox calicon"><nobr>    <nobr>End Date</nobr>
                         <input readonly="" onclick="javascript:NewCssCal(&quot;calReportEndDate&quot;, &quot;ddMMMyyyy&quot;)" value = "' . $_REQUEST["calReportEndDate"] . '"  type="text" id="calReportEndDate" name="calReportEndDate" class="full_width" value="">
                      </div>
                      <div class="fl small_textbox calicon"><nobr>
                   
                      <nobr>Start Time</nobr>
                          
                              <input onblur="validate_time(ti2StartTime); " type="text" id="ti2StartTime" name="ti2StartTime" placeholder="Start Time" onchange="" ondblclick="clear_field(this)" style="width: 85px;" value="">
                       </div>
                       
                      <div class="fl small_textbox calicon"><nobr>                   
                        <nobr>End Time</nobr>
                          <input onblur="validate_time(ti2FinishTime); " type="text" id="ti2FinishTime" name="ti2FinishTime" placeholder="Finish Time" onchange="" ondblclick="clear_field(this)" style="width: 85px;" value="">                          
                       </div>
                      <div class="fl mid_textbox mr-10"><nobr>Client</nobr><br />' . $selClt . '</div>  
                      <div class="fl mid_textbox mr-10"><nobr>Location</nobr><br />' . $selLocation . '</div> 
                      <div class="fl mid_textbox mr-10"><nobr>Employee</nobr><br />' . $selEmp . '</div>
                    
                      <div class="fl">
                        <nobr>&nbsp;</nobr></br>
                        <input id="reportExport" onclick="exportRosterReport()" type="button" value="PDF Report"  class="addicon">
                        <input id="reportCSVExport" onclick="exportCSVRosterReport()" type="button" value="CSV Report"  class="addicon">

                     </div>
                   </form>  
               </div>
                    </div>
                    <style>
  	.user4 {margin-right: 8px; margin-bottom:10px}
  	table tr th, table tr td {font-size: 14px !important;}
  	.loaderScreen {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 999999;
    background: rgba(0, 0, 0, 0.7);
}

.loaderScreen span {
    display: flex;
    width: 100%;
    height: 100%;
    justify-content: center;
    align-items: center;
}

.loaderScreen span:after {
    content: "";
    border: 4px solid #fff;
    border-top-color: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1.5s linear infinite;
    -webkit-animation: spin 1.5s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
    }
}

@-webkit-keyframes spin {
    0% {
        transform: rotate(0deg);
        -webkit-transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
    }
}
  </style>
                     <div class="loaderScreen" style="display:none;"><span></span></div>
                   ';

                //$this->editor_obj->editor_template .= '';  //Any text between form and list

                $this->editor_obj->editor_template .= '<div class="grid-view">' . 'editor_list' . '</div>';
                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
                //$str .= " 14 ".date('Y-m-d h:i:s');
                if ($sid)
                    $str .= '<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/Rosters' . ($division_id ? "?division_id=$division_id" : "") . '">Show All Sites</a>';
            }

            //$str .= " 15 ".date('Y-m-d h:i:s');

            if ($action == "delete_record") {
                $idin = $_POST['idin'];
                $sql = "delete from roster_times_staff where roster_time_id in (select id from roster_times where roster_id = $idin);";
                $sql .= "delete from roster_times where roster_id = $idin;";
                $this->dbi->multi_query($sql);
                //$this->rosterLog();
            }

            if ($action == "add_record") {
                $newRosterId = $this->editor_obj->last_insert_id;
                $detail = "Roster Added";
                $this->rosterLog(1, $newRosterId, NULL, NULL, $detail);
            }

            //return $action;
            return $str;
        }
    }

    function get_tender_sql($id) {


        $sql = "select company_name as `Business Name`, site_name as `Site Name/Other Nicknames`,
        client_rep_name as `Client Rep`, client_rep_phone as `Client Rep Phone`, client_rep_email as `Client Rep Email`, " .
                ($this->download_xl ? "DATE_FORMAT(tender_due_date, '%a %d-%m-%Y') as `Tender Due Date`" : "tender_due_date as `Tender Due Date`") . "
        " . (!$this->download_xl ? ",
        CONCAT('<div style=\"color: ',
              if(DATEDIFF(bd_tenders.tender_due_date, now()) <= 0,
                CONCAT('red\">Due ', if(DATEDIFF(bd_tenders.tender_due_date, now()) = 0,
                  CONCAT('Today.'),
                  CONCAT(ABS(DATEDIFF(bd_tenders.tender_due_date, now())), ' Days Ago'))),
                if(DATEDIFF(bd_tenders.tender_due_date, now()) <= 28,
                  CONCAT('orange\">', DATEDIFF(bd_tenders.tender_due_date, now()), ' Days Remaining'),
                  CONCAT('green\">', ROUND(DATEDIFF(bd_tenders.tender_due_date, now()) / 7), ' Weeks Remaining'))), '</div>') as `Time`" : "") . "
        , comments as `Comments` from bd_tenders where division_id = $id and bd_tenders.tender_due_date >= now() order by bd_tenders.tender_due_date";
        return $sql;
    }

    function ShiftTemplates() {
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('ros_shift_templates.xlsx');
        } else {
            $filter_string = "filter_string";
            //$this->list_obj->title = "Shift Templates";
            $this->list_obj->show_num_records = 0;
            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 100;
            $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "
            select ros_shift_templates.id as idin, name as `Template Name`

            " . ($show_times ? ", CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ShiftTemplates\">&lt;&lt; Back to Templates</a>') as `&lt;&lt;`" : "
            , 'Edit' as `*`, 'Delete' as `!`, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/ShiftTemplates?show_times=', ros_shift_templates.id, '\">Manage Times</a>') as `Manage Times`") . "
            FROM ros_shift_templates
            where " . ($show_times ? "ros_shift_templates.id = $show_times" : "1 $sort_xtra") . "
        ";
            //$this->editor_obj->add_now = "updated_date";
            //$this->editor_obj->update_now = "updated_date";
            if ($show_times) {
                //$str .= $this->list_obj->draw_list();
                //$str .= '<iframe width="100%" height="1000" src="'.$this->f3->get('main_folder')."Rostering/ShiftTemplateTimes?lookup_id=$show_times&show_min=1\"></iframe>";
                $str .= $this->ShiftTemplateTimes($show_times);
            } else {
                $str .= '<div class="form-wrapper"><div class="form-header">Shift Templates</div>';

                //$this->editor_obj->custom_field = "staff_id";
                //$this->editor_obj->custom_value = $_SESSION['user_id'];
                $this->editor_obj->table = "ros_shift_templates";
                $style = 'class="full_width"';
                $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"

                $site_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users') as a order by item_name";
                $this->editor_obj->form_attributes = array(
                    array("txtName"),
                    array("Template Name"),
                    array("name"),
                    array(""),
                    array($style),
                    array("c")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset"),
                    array("cmdAdd", "cmdSave", "cmdReset"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
                );
                $this->editor_obj->form_template = '
                  <div class="fl full_width"><nobr>ttxtName</nobr><br />txtName</div>
                  <div class="cl"></div>
                  <br />
                  ' . $this->editor_obj->button_list();
                $this->editor_obj->editor_template = '

                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
        ';
                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
            }
            /* if($action == "add_record") {
              } else if($action == "save_record") {
              } */
            return $str;
        }
    }

    function ShiftTemplateTimes($lookup_id) {
        //$lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
        $this->editor_obj->xtra_id_name = "shift_template_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $summary_mode = ($lookup_id ? null : 1);

        $copy_to = (isset($_POST["copy_to"]) ? $_POST["copy_to"] : null);

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('ros_shift_template_times.xlsx');
        } else {
            $days = Array('', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            $colours = Array('#5C821A', '#00293C', '#CB0000', '#518FD0', '#CB6318', '#752A17', '#1E656D', '#DB9501', '#B38867', '#31A9B8');

            if ($copy_to) {
                $sql = "delete from ros_shift_template_times where shift_template_id = $lookup_id and start_day != 1";
                $this->dbi->query($sql);
                $sql_top = "insert into ros_shift_template_times (shift_template_id, start_day, finish_day, start_time, finish_time) ";
                $num_days = ($copy_to == 'w' ? 5 : 7);
                for ($x = 1; $x < $num_days; $x++) {
                    $sql = "$sql_top (select shift_template_id, start_day + $x, finish_day + $x, start_time, finish_time from ros_shift_template_times where shift_template_id = $lookup_id and start_day = 1)";
                    $this->dbi->query($sql);
                }
                $sql = "update ros_shift_template_times set finish_day = 1 where finish_day = 8";
                $this->dbi->query($sql);
            }

            $str = '
      <input type="hidden" name="copy_to" id = "copy_to">
      <script>
        function copy_monday(to_where) {
          confirmation = "Are you sure about copying all of the values from Monday to all "+(to_where == \'w\' ? "week" : "")+"days?";
          if(confirm(confirmation)) {
            document.getElementById("copy_to").value = to_where
            document.frmEdit.submit()
          }
        }
      </script>

      <style>
      .day_header {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        display: inline-block;
        width: 220px;
        border: 1px solid #CCCCCC;
        font-size: 11pt;
        padding: 6px 0px 6px 0px;
        margin: 0;
        text-align: center;
      }
      .current_day {
        background-color: #FFFFCC;
      }
      .time_header {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        width: 60px;
        display: inline-block;
        text-align: left;
        border: 1px solid #CCCCCC;
        font-size: 11pt;
        padding: 4px;
      }
      .time_item {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
        display: inline-block;
        width: 220px;
        display: inline-block;
        text-align: center !important;
        border: 1px solid transparent;
        font-size: 11pt;
        padding: 4px;
      }
      ';

            for ($x = 0; $x < count($colours); $x++) {
                $str .= '.colour' . ($x + 1) . '{color:white;background-color:' . $colours[$x] . '} ';
            }


            $str .= '</style>';

            // return $str;


            $this->list_obj->show_num_records = 0;
            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 100;
            $this->list_obj->nav_count = 20;

            $this->list_obj->sql = "
            select ros_shift_template_times.id as idin,

            " . ($summary_mode ? "
            ros_shift_templates.title as `Shift Template`,
            " : "") . "
            CONCAT('<div style=\"display: inline-block; width: 37px;\">', ros_week_days.short_day, '</div> ', DATE_FORMAT(ros_shift_template_times.start_time, '%H:%i')) as `Start`,
            CONCAT('<div style=\"display: inline-block; width: 37px;\">', ros_week_days2.short_day, '</div> ', DATE_FORMAT(ros_shift_template_times.finish_time, '%H:%i')) as `Finish`,

            ABS(ROUND(TIME_TO_SEC(TIMEDIFF(CONCAT('2000-01-0', 2+ros_shift_template_times.start_day, ' ', ros_shift_template_times.start_time), CONCAT('2000-01-0', 2+ros_shift_template_times.finish_day + if(finish_day < start_day, 7, 0), ' ', ros_shift_template_times.finish_time))) / 3600, 2))
            as `Hours`

              " . ($this->download_xl || $summary_mode ? "" : ", 'Edit' as `*`, 'Delete' as `!`") . "

            FROM ros_shift_template_times
            inner join ros_shift_templates on ros_shift_templates.id = ros_shift_template_times.shift_template_id

            left join ros_week_days on ros_week_days.id = ros_shift_template_times.start_day
            left join ros_week_days2 on ros_week_days2.id = ros_shift_template_times.finish_day
            " . ($summary_mode ? "" : "where ros_shift_template_times.shift_template_id = $lookup_id") . "
            $sort_xtra
            order by ros_week_days.id, ros_shift_template_times.start_time
        ";

            //return "<textarea>" . $this->list_obj->sql . '</textarea>';
            $this->list_obj->title = ($summary_mode ? "Campaign Overview" : "");
            if ($summary_mode) {
                $str .= $this->list_obj->draw_list();
            } else {
                //$this->list_obj->title = "Roster Shifts";
                //$this->editor_obj->add_now = "updated_date";
                //$this->editor_obj->update_now = "updated_date";
                $sql = "select name from ros_shift_templates where id = $lookup_id";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $site_name = $myrow['name'];
                    }
                }

                $str .= '<div class="form-wrapper">
        <div class="form-header">
        <div class="fl">Shift Times for ' . $site_name . '</div>
        <div class="fr"><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/ShiftTemplates">&lt;&lt; Back to Templates</a></div>
        <div class="cl"></div>
        </div>
        ';
                //$this->editor_obj->custom_field = "user_id";
                //$this->editor_obj->custom_value = $_SESSION['user_id'];
                $this->editor_obj->table = "ros_shift_template_times";
                $style = 'class="full_width"';
                $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"

                $day_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT id as `idin`, long_day as `item_name` from ros_week_days order by id";
                $this->editor_obj->form_attributes = array(
                    array("selStartDay", "ti2StartTime", "selFinishDay", "ti2FinishTime"),
                    array("Start Day", "Start Time", "Finish Day", "Finish Time"),
                    array("start_day", "start_time", "finish_day", "finish_time"),
                    array($day_sql, "", $day_sql, ""),
                    array("", $style, "", $style),
                    array("c", "c", "c", "c")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset"),
                    array("cmdAdd", "cmdSave", "cmdReset"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
                );
                $this->editor_obj->form_template = '
                  <div class="fl "><nobr>tselStartDay</nobr><br />selStartDay</div>
                  <div class="fl small_textbox"><nobr>tti2StartTime</nobr><br />ti2StartTime</div>
                  <div class="fl "><nobr>tselFinishDay</nobr><br />selFinishDay</div>
                  <div class="fl small_textbox"><nobr>tti2FinishTime</nobr><br />ti2FinishTime</div>
                  <div class="fl" style="margin-left: 5px; margin-top: 3px"><br />' . $this->editor_obj->button_list() . '</div>
                  <div class="cl"></div>
                  ';

                $this->editor_obj->editor_template = '

                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
        ';

                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
                $str .= '<br /><input onClick="copy_monday(\'w\')" type="button" value="Copy Monday to Weekdays &gt;&gt;" /><input onClick="copy_monday(\'a\')" type="button" value="Copy Monday to All Other Days &gt;&gt;" />';
            }




            return $str;
        }
    }

    function EditRosterTimes() {

        $rostertimeid = (isset($_REQUEST["rostertimeid"]) ? $_REQUEST["rostertimeid"] : null);
        $notesText = (isset($_REQUEST["notesText"]) ? $_REQUEST["notesText"] : null);
        $assetVehicleId = (isset($_REQUEST["vehicleId"]) ? $_REQUEST["vehicleId"] : null);
        $leaveType = (isset($_REQUEST["leaveType"]) ? $_REQUEST["leaveType"] : null);
        $action = (isset($_REQUEST["action"]) ? $_REQUEST["action"] : null);
        $rostertimestaffid = (isset($_REQUEST["rostertimestaffid"]) ? $_REQUEST["rostertimestaffid"] : null);

        if ($action == "addNotes") {

            // prd($_FILES);
            if($rostertimeid == '100243') {
                var_dump($_REQUEST); die;
            }

            if (isset($_FILES['notes_image'])) {
                $folder = $this->f3->get('download_folder') . "roster_notes";
                $errors = array();
                $file_name = $_FILES['notes_image']['name'];
                $file_size = $_FILES['notes_image']['size'];
                $file_tmp = $_FILES['notes_image']['tmp_name'];
                $file_type = $_FILES['notes_image']['type'];
                $file_ext = strtolower(end(explode('.', $_FILES['notes_image']['name'])));

                $extensions = array("jpeg", "jpg", "png");

                if (in_array($file_ext, $extensions) === false) {
                    //echo "extension not allowed, please choose a JPEG or PNG file.";
                    //die;
                }
                if ($file_size > 2097152) {
                    echo 'File size should be not more then 2 MB';
                    die;
                }


                if (!file_exists($folder)) {
                    //  prd($folder);
                    mkdir($folder, 0755, true);
                    //chmod($folder, 0755,true);
                }
                $file_name = time() . "." . $file_ext;

                $notesFolder = "$folder/$file_name";

                if (move_uploaded_file($file_tmp, $notesFolder)) {
                    $sql = "update roster_times set roster_time_notes = '$notesText',roster_time_notes_image= '$file_name'  where id = $rostertimeid";
                    $this->dbi->query($sql);
                    echo $sql;
                    exit;
                }
            } else {
                $sql = "update roster_times set roster_time_notes = '$notesText' where id = $rostertimeid";
                $this->dbi->query($sql);
                echo $sql;
                exit;
            }
        }
        if ($action == "assignVehicle") {

            $availableVehicleList = $this->getAssetVehicleList($rostertimeid);
            $availableVehicleListForCheck = array();
            foreach ($availableVehicleList as $value) {
                $availableVehicleListForCheck[] = $value['id'];
            }

            //prd($availableVehicleListForCheck);
            if ($assetVehicleId == 0 || in_array($assetVehicleId, $availableVehicleListForCheck)) {
                $sql = "update roster_times set asset_vehicle_id = '$assetVehicleId' where id = $rostertimeid";
                $this->dbi->query($sql);
                if ($assetVehicleId == 0) {
                    echo 2;
                } else {
                    echo 1;
                }
            } else {
                echo 3;
            }
            exit;
        }

        if ($action == "addLeave") {
            $sql = "update roster_times_staff set leave_id = '" . $leaveType . "' where id = '" . $rostertimestaffid . "'";
            $this->dbi->query($sql);
            echo $sql;
            exit;
        }




        $roster_time_id = (isset($_GET["roster_time_id"]) ? $_GET["roster_time_id"] : null);
//        $roster_time_id = (isset($_GET["roster_time_id"]) ? $_GET["roster_time_id"] : null);
//
//        $roster_time_id = (isset($_GET["roster_time_id"]) ? $_GET["roster_time_id"] : null);
        $start_time = (isset($_GET["start_time"]) ? $_GET["start_time"] : null);
        $finish_time = (isset($_GET["finish_time"]) ? $_GET["finish_time"] : null);

        if ($roster_time_id) {

            $rosterTimeDetail = $this->rosterTimeDetail($roster_time_id);
            $vehicleId = $rosterTimeDetail['asset_vehicle_id'];
            $vehicleExistInOtherRoster = 0;
            if ($vehicleId) {
                $rosterDate = date('Y-m-d', strtotime($rosterTimeDetail['start_time_date']));
                $rosterNewStartDate = $rosterDate . " " . $start_time;
                $rosterNewFinishDate = $rosterDate . " " . $finish_time;
                $vehicleExistInOtherRoster = $this->checkVehicleExistInOtherSlot($roster_time_id, $vehicleId, $rosterDate, $rosterNewStartDate, $rosterNewFinishDate);
            }


            if ($vehicleExistInOtherRoster) {
                echo 2;
            } else {
                $sql = "update roster_times set start_time_date = concat(date(start_time_date), ' $start_time'), finish_time_date = CONCAT(DATE(DATE_ADD(start_time_date, INTERVAL " . (strtotime($start_time) < strtotime($finish_time) ? 0 : 1) . " DAY)), ' $finish_time') where id = $roster_time_id";
                $this->dbi->query($sql);
                $this->sendRosterAlterMessage(0, "changeTime", $roster_time_id);
                echo floatval($this->get_sql_result("select ABS(ROUND(TIME_TO_SEC(TIMEDIFF(start_time_date, finish_time_date)) / 3600, 2)) as `result` from roster_times where id = $roster_time_id"));
            }
            exit;
        }

        $lookup_id = (isset($_GET["rid"]) ? $_GET["rid"] : null);

        $notAllowedRoster = $this->checkNotAllowedRoster($lookup_id);
        if ($lookup_id && $notAllowedRoster) {
            echo $this->redirect($this->f3->get('main_folder') . "Rostering/rosters");
            die;
        }


        $this->editor_obj->xtra_id_name = "roster_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);

        // return $str;
        $this->list_obj->show_num_records = 0;
        $this->list_obj->form_nav = 1;
        $this->list_obj->num_per_page = 100;
        $this->list_obj->nav_count = 20;
        $str = '<div style="float: left;margin-bottom: 15px;"><a href="/Rostering/Rosters"  class="orgColorBlue" > Roster Manager  </a></div><div class="cl"></div>';
        $str .= "<div class=\"grid-view\">" . $this->roster_details($lookup_id, "times") . "</div><br />";

        //Converting decimal to HH:MM format
        //CONCAT(CEIL(mydecimal),':', LPAD(Floor(mydecimal*60 % 60),2,'0'))
        /* CONCAT(CEIL(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))),':', LPAD(Floor(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))*60 % 60),2,'0')) */

        //- (roster_times.minutes_unpaid * 60)

        $this->list_obj->sql = "
          select roster_times.id as idin,
          DATE_FORMAT(roster_times.start_time_date, '%a, %d-%b-%Y - %H:%i') as `Start`,
          DATE_FORMAT(roster_times.finish_time_date, '%a, %d-%b-%Y - %H:%i') as `Finish`,
          
          ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))
          as `Hours`,
          
          roster_times.minutes_unpaid as `Unpaid Minutes`,
          if(roster_times.minutes_unpaid != 0,
          
          ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600 - (roster_times.minutes_unpaid / 60), 2)),
          ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2))
          
          )
          as `Paid Hours`

          " . ($this->download_xl || $summary_mode ? "" : ", 'Edit' as `*`, 'Delete' as `!`") . "

          FROM roster_times
          inner join rosters on rosters.id = roster_times.roster_id

          where roster_times.roster_id = $lookup_id
          order by roster_times.start_time_date, roster_times.finish_time_date
      ";

        $sql = "select CONCAT(users.name, ' ', users.surname, ' Starting on ', DATE_FORMAT(rosters.start_date, '%a, %d-%b-%Y')) as `site` from
      rosters
      inner join users on users.id = rosters.site_id
      where rosters.id = $lookup_id";
        if ($result = $this->dbi->query($sql)) {
            if ($myrow = $result->fetch_assoc()) {
                $site_name = $myrow['site'];
            }
        }

        $str .= '<div class="form-wrapper">
      <div class="form-header">
      <div class="fl">Roster Times Times for ' . $site_name . '</div>
      <div class="fr"><a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/EditRoster?rid=' . $lookup_id . '">Manage Roster &gt;&gt;</a></div>
      <div class="cl"></div>
      </div>
      ';
        //$this->editor_obj->custom_field = "user_id";
        //$this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "roster_times";
        $style = 'class="full_width"';
        $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"

        $day_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT id as `idin`, long_day as `item_name` from ros_week_days order by id";
        $this->editor_obj->form_attributes = array(
            array("tadStartTimeDate", "tadFinishTimeDate", "txtUnpaidMinutes"),
            array("Start Date/Time", "Finish Date/Time", "Unpaid Minutes"),
            array("start_time_date", "finish_time_date", "minutes_unpaid"),
            array($day_sql, $day_sql, ""),
            array($style, $style, $style),
            array("c", "c", "n")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit"),
            array(),
            array('addicon', 'saveicon', 'reseticon')
        );
        $this->editor_obj->form_template = '
                <div class="fl small_textbox"><nobr>ttadStartTimeDate</nobr><br />tadStartTimeDate</div>
                <div class="fl small_textbox"><nobr>ttadFinishTimeDate</nobr><br />tadFinishTimeDate</div>
                <div class="fl small_textbox"><nobr>ttxtUnpaidMinutes</nobr><br />txtUnpaidMinutes</div>
                <div class="fl" style="margin-left: 5px; margin-top: 3px"><br />' . $this->editor_obj->button_list() . '</div>
                <div class="cl"></div>
                ';

        $this->editor_obj->editor_template = '

                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  <div class="grid-view">
                  editor_list
                  </div>
      ';

        $str .= "<div class=\"grid-view\">" . $this->editor_obj->draw_data_editor($this->list_obj) . "</div>";

        if ($action == "delete_record") {
            if ($idin = ($_REQUEST['idin'] ? $_REQUEST['idin'] : 0)) {
                $this->dbi->query("delete FROM `roster_times_staff` where roster_time_id = $idin");
            }
        }

        return $str;
    }

    function get_by_site_staff($site_id, $staff_id, $rosterTimeStaffid = 0) {
        $time_date = date('Y-m-d H:i:s');
        $current_date = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime("-1 days"));
        $tomorrow = date("Y-m-d", strtotime("+1 days"));

        if ($rosterTimeStaffid) {
            $condcheck = " roster_times_staff.id = '$rosterTimeStaffid' and ";
        }

        //and (DATE(roster_times.start_time_date) = '$current_date' or DATE(roster_times.finish_time_date) = '$current_date')  
        //   AND TIME_TO_SEC(TIMEDIFF(NOW(),roster_times_staff.finish_time_date)) < 60
        $sql = "select roster_times.finish_time_date as roster_finish_time_date,roster_times_staff.id, roster_times_staff.start_time_date, roster_times_staff.finish_time_date from
            roster_times_staff
            left join roster_times on roster_times.id = roster_times_staff.roster_time_id
            left join rosters on rosters.id = roster_times.roster_id
                where $condcheck roster_times_staff.status > 1 and " . ($site_id ? "rosters.site_id = $site_id and" : "") . " roster_times_staff.staff_id = $staff_id           
            and (DATE(roster_times.start_time_date) = '$current_date' or DATE(roster_times.start_time_date) = '$yesterday') and (DATE(roster_times.finish_time_date) = '$current_date' or DATE(roster_times.finish_time_date) = '$yesterday' or DATE(roster_times.finish_time_date) = '$tomorrow')"
                . "";

//        echo $sql;
//        die;
        //return $sql;
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $roster_times_staff_id = $myrow['id'];
                $this->start_time_date = $myrow['start_time_date'];
                $this->finish_time_date = $myrow['finish_time_date'];
                $this->roster_finish_time_date = $myrow['roster_finish_time_date'];
            }
        }
        return $roster_times_staff_id;
    }

    function Clock() {

        $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : '');
        $latitude = (isset($_POST['hdnLatitude']) ? $_POST['hdnLatitude'] : 0);
        $longitude = (isset($_POST['hdnLongitude']) ? $_POST['hdnLongitude'] : 0);
        $division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : '');

        $rosterTimeStaffId = (isset($_POST['hdnSiteId']) ? $_POST['hdnSiteId'] : 0);

        $roster_times_staff_id = $this->get_by_site_staff(0, $_SESSION['user_id'], $rosterTimeStaffId);
        $hdnFlag = (isset($_POST['hdnFlag']) ? $_POST['hdnFlag'] : 0);

        $str .= '<div id="x"></div>';
        $NotSignOffMessage = "";
        // prd($roster_times_staff_id);
//        $distance = $this->getDistance('-33.9110271','151.029659', '-33.85764', '150.96566');
//                     echo $distance;
//                     $distanceOld = $this->distance('-33.9110271','151.029659', '-33.85764', '150.96566');
//                     echo "<br>old".$distanceOld;
//                     die;

        if ($roster_times_staff_id) {

            $str .= '<script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>';
            $str .= '<input type="hidden" name="hdnLatitude" id="hdnLatitude" value="' . $latitude . '" />
                <input type="hidden" name="hdnLongitude" id="hdnLongitude"  value="' . $longitude . '" />';

            $distanceMatch = 0;
            $distance = 10000;
            if (!isset($_POST['hdnLatitude'])) {
                return $str . '<script>getLocation();</script>';
            } else {
                
            }

//            echo $distance;
//            die;

            $codes['ON'] = "Signed On";
            $codes['OFF'] = "Signed Off";
            $codes['UNDO'] = "Signed Off";
            $time_date = date('Y-m-d H:i:s');
            $current_date = date("Y-m-d");
            $yesterday = date("Y-m-d", strtotime("-1 days"));

            $detect = new Mobile_Detect;
            $delid = $_POST['delid'];
            $txtComment = $this->mesc($_POST['txtComment']);

            $earliest = date('Y-m-d H:i:s', strtotime("-30 minutes"));
            $latest = date('Y-m-d H:i:s', strtotime("+5 minutes"));

            if ($delid) {
                /* $sql = "delete from roster_times_staff where id = $delid;";
                  $this->dbi->query($sql);
                  $msg = "Item Deleted..."; */
            }


            $font_size = "";
            $text_style = "width: 100%; height: 50px; float: left;";
//            $distance = $this->distance('-33.862625','150.96251', '-33.93741', '151.04841');
//                            
//            echo $distance;
//            die;
            //if($hdnFlag && $latitude && $longitude) {
            if ($hdnFlag) {

                $roster_times_staff_id = $_POST['hdnSiteId']; //($hdnFlag == 'COMMENT' ? $_POST['hdnSiteId'] : $this->get_by_site_staff($_POST['hdnSiteId'], $_SESSION['user_id']));
                $rosterSiteId = (isset($_POST['hdnSiteId']) ? $_POST['hdnSiteId'] : 0);
                $sqlSite = "SELECT users.latitude, users.longitude, users.site_gps_on_off, site_gps_radius, users.id AS rosterSiteId, rost.division_id
                FROM users
                LEFT JOIN rosters rost ON rost.site_id = users.id
                LEFT JOIN roster_times rt ON rt.roster_id = rost.id
                LEFT JOIN roster_times_staff rts ON rts.roster_time_id = rt.id
                WHERE rts.id = $rosterSiteId";
                $resultSite = $this->dbi->query($sqlSite);

                $dist_check = 1000;
                $gpsOnOffStatus = 1;
                $division_id = ''; // Initialize division_id

                if ($resultSite->num_rows != 0) {
                    while ($myrowSite = $resultSite->fetch_assoc()) {
                        $siteLatitude = $myrowSite['latitude'];
                        $siteLongitude = $myrowSite['longitude'];
                        $gpsOnOffStatus = $myrowSite['site_gps_on_off'];
                        $rosterSiteId = $myrowSite['rosterSiteId'];
                        $dist_check = $myrowSite['site_gps_radius'];
                        $division_id = $myrowSite['division_id'];

                    }
                }
//                        echo $rosterSiteId;
//                        die;
                if ($gpsOnOffStatus) {

                    if ($latitude) {
                        $found = 0;

//                        $siteLatitude = 0;
//                        $siteLongitude = 0;
                        //echo $siteLatitude." ".$siteLongitude."<br>";
//$distance = $this->getDistance('-33.9110271','151.029659', '-33.85764', '150.96566');
//                     echo $distance;
//                     die;


                        if (is_numeric($siteLatitude) && is_numeric($siteLongitude) && is_numeric($latitude) && is_numeric($longitude)) {
                            // $distance = $this->getDistance($siteLatitude, $siteLongitude, $latitude, $longitude);                    
                            $distance = $this->distance($siteLatitude, $siteLongitude, $latitude, $longitude);
                        } else {
                            $distance = 1000;
                        }

                        // echo $distance;
                        // die;

                        if ($_SESSION['user_id'] == 1 || $_SESSION['user_id'] == 3 || $_SESSION['user_id'] == 5) {
//                             $str .= " below text is only showing to Amer for testing";
//                            $str .= "<h3>$siteLatitude, $siteLongitude, $latitude, $longitude -- $distance</h3>";
//                           
//                            $customdistance = $this->distance('-33.862625','150.96251', '-33.93741', '151.04841');
//                            $str .= "other custom ".$customdistance;
                        }

                        if ($distance <= $dist_check) {

                            $distanceMatch = 1;
                        }



//                if($_SESSION['user_id'] != 5 && $_SESSION['user_id'] != 1 ){
//                    $distanceMatch = 1;
//                }
//                    $sql = "SELECT t1.user_id as `site_id`, users.name as `site`,
//              (SELECT meta_value FROM usermeta WHERE meta_key=93 AND user_id = t1.user_id) AS `latitude`,
//              (SELECT meta_value FROM usermeta WHERE meta_key=94 AND  user_id = t1.user_id) AS `longitude`
//          FROM usermeta AS t1
//          left join users on users.id = t1.user_id
//          where t1.meta_key = 93 or t1.meta_key = 94
//          GROUP BY t1.user_id";
//
//                    $result_gps = $this->dbi->query($sql);
//                    $cnt = 0;
//                    $found = 0;
//                    $dist_check = 1000;
//                    while ($myrow = $result_gps->fetch_assoc()) {
//                        $long = $myrow['longitude'];
//                        $lat = $myrow['latitude'];
//                        if (is_numeric($lat) && is_numeric($long) && is_numeric($latitude) && is_numeric($longitude))
//                            $distance = $this->distance($lat, $long, $latitude, $longitude);
//                        //$str .= "<h3>$lat, $long, $latitude, $longitude -- $distance</h3>";
//                        if ($distance < $dist_check) {
//                            $found = 1;
//                            $site_id = $myrow['site_id'];
//                            $site_ids[] = $myrow['site_id'];
//                            $site_name = $myrow['site'];
//                            $sign_text = ($ac_type == 'ON' || $ac_type == 'OFF' ? "Sign $ac_type @ " : "UNDO Previous Sign Off from ");
//                            $clock_str .= '<input onClick="clock_it(\'' . $ac_type . '\', ' . $site_id . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="' . $sign_text . $site_name . '" />';
//                        }
//                    }
                    }
                } else {
                    $distanceMatch = 1;
                }




                if ($hdnFlag == 'UNDO') {
                    $sql = "update roster_times_staff set finish_time_date = '0000-00-00 00:00:00', status = 4, gps_off = '', ip_off= '', staff_comment = '$txtComment' where id = $roster_times_staff_id";
                    $result = $this->dbi->query($sql);
                } else if ($hdnFlag == 'COMMENT' && $distanceMatch) {
                    $sql = "update roster_times_staff set staff_comment = '$txtComment' where id = $roster_times_staff_id";
                    $result = $this->dbi->query($sql);
                } else {

                    if ($distanceMatch) {
//          if($this->start_time_date == '0000-00-00 00:00:00') {
                        if ($hdnFlag == 'ON') {
                            $which_date = 'start';
                            $on_or_off = 'on';
                            $status = 4;
                        } else {
                            $which_date = 'finish';
                            $on_or_off = 'off';
                            $status = 5;
                        }

                        // echo $time_date."</br>";
//                     echo var_dump($time_date > $this->roster_finish_time_date);
//                     die;


    if ($division_id != 2104 and $which_date == 'finish' and ($time_date < $this->roster_finish_time_date)) {
        $NotSignOffMessage = '<div class="cl"></div><h3><br/>'
                . 'You cannot sign off before ' . $this->roster_finish_time_date . ' . Please contact to manager.'
                . '<br/><br/>';
    } elseif ($division_id = 2104 and $which_date == 'finish' and ($time_date < $this->roster_finish_time_date)) {
        $NotSignOffMessage = "";
        if ($which_date == 'start') {
            $sql = "update roster_times_staff set " . $which_date . "_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where start_time_date = '0000-00-00 00:00:00' and id = $roster_times_staff_id";
        } else {
            if ($which_date == 'finish') {

                $report_signoff_edit_id = $this->checkPreStartReportExist(141, $roster_times_staff_id);
                if (!$report_signoff_edit_id) {
                    // die("test");
                    echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=141&site_id=' . $roster_times_staff_id);
                    // /Compliance?report_edit_id=97296
                } else {
                    $oldSignReportLink = $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $report_signoff_edit_id;
                    //echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_edit_id='.$report_edit_id);   
                }

                $signoff_report_status = $this->checkPreStartReportCompleted($report_signoff_edit_id);
//echo $signoff_report_status;
//die;
                if($signoff_report_status >= 95) {
//                                        $sendHandover = $_REQUEST['send_handover'];
//                                        if($sendHandover){
                        
                        $this->sendHandOverReport($report_signoff_edit_id);
                    //}
                    $sql = "update roster_times_staff set " . $which_date . "_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where id = $roster_times_staff_id";
//                                        $reprotSignOffLink = '</br></br></br><div style="color:green"><a style="color:green" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Thank you for completing the Shift Ending Client Handover Report. 
//You can sign off to your shift. Stay safe ! </a></div></br>'
//                                                . '<div style="clear:both"></div> ';
                } else {
//                                        $reprotSignOffLink = '</br></br></br><div style="color:red"><a style="color:red" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Prior to signing off, you must complete Shift Ending Client Handover Report </a></div></br>'
//                                                . '<div style="clear:both"></div> ';
                    //die;
                    echo $this->redirect($oldSignReportLink);
                    // echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=132&site_id=' . $roster_times_staff_idin);
                }
            
                
            }
        }
        //$sql = "update roster_times_staff set start_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where start_time_date = '0000-00-00 00:00:00' and id = $roster_times_staff_id";  
        $result = $this->dbi->query($sql);
    } else {
        $NotSignOffMessage = "";
        if ($which_date == 'start') {
            $sql = "update roster_times_staff set " . $which_date . "_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where start_time_date = '0000-00-00 00:00:00' and id = $roster_times_staff_id";
        } else {
            if ($which_date == 'finish') {

                $report_signoff_edit_id = $this->checkPreStartReportExist(141, $roster_times_staff_id);
                if (!$report_signoff_edit_id) {
                    // die("test");
                    echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=141&site_id=' . $roster_times_staff_id);
                    // /Compliance?report_edit_id=97296
                } else {
                    $oldSignReportLink = $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $report_signoff_edit_id;
                    //echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_edit_id='.$report_edit_id);   
                }

                $signoff_report_status = $this->checkPreStartReportCompleted($report_signoff_edit_id);
//echo $signoff_report_status;
//die;
                if($signoff_report_status >= 95) {
//                                        $sendHandover = $_REQUEST['send_handover'];
//                                        if($sendHandover){
                        
                        $this->sendHandOverReport($report_signoff_edit_id);
                    //}
                    $sql = "update roster_times_staff set " . $which_date . "_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where id = $roster_times_staff_id";
//                                        $reprotSignOffLink = '</br></br></br><div style="color:green"><a style="color:green" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Thank you for completing the Shift Ending Client Handover Report. 
//You can sign off to your shift. Stay safe ! </a></div></br>'
//                                                . '<div style="clear:both"></div> ';
                } else {
//                                        $reprotSignOffLink = '</br></br></br><div style="color:red"><a style="color:red" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Prior to signing off, you must complete Shift Ending Client Handover Report </a></div></br>'
//                                                . '<div style="clear:both"></div> ';
                    //die;
                    echo $this->redirect($oldSignReportLink);
                    // echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=132&site_id=' . $roster_times_staff_idin);
                }
            
                
            }
        }
        //$sql = "update roster_times_staff set start_time_date = '$time_date', gps_" . $on_or_off . " = '" . round($latitude, 6) . "," . round($longitude, 6) . "', ip_" . $on_or_off . " = '" . $_SERVER['REMOTE_ADDR'] . "', staff_comment = '$txtComment', status = $status where start_time_date = '0000-00-00 00:00:00' and id = $roster_times_staff_id";  
        $result = $this->dbi->query($sql);
    } 


} else {
    $str .= "<h3>Your location not match with site location. Difference is more then " . round($distance / 1000, 2) . " km </h3>";
}

//$str .= "<h3>$hdnFlag<br /><br />$sql</h3>";
}
}

            //". round($distance / 1000,2) ."

            $sql = "select roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`

              from roster_times_staff
              left join users on users.id = roster_times_staff.staff_id
              left join roster_times on roster_times.id = roster_times_staff.roster_time_id
              left join rosters on rosters.id = roster_times.roster_id
              left join users2 on users2.id = rosters.site_id
              where roster_times_staff.staff_id = " . $_SESSION['user_id'] . "
              and (DATE(roster_times.start_time_date) = '$current_date' or DATE(roster_times.start_time_date) = '$yesterday') and (DATE(roster_times.finish_time_date) = '$current_date' or DATE(roster_times.finish_time_date) = '$yesterday')"
                    . " and roster_times_staff.start_time_date != '0000-00-00 00:00:00' and roster_times_staff.finish_time_date = '0000-00-00 00:00:00'"
                    . " order by roster_times_staff.id desc limit 1  ";

//            echo $sql;
//            die;
            $result = $this->dbi->query($sql);

            if ($result->num_rows != 0) {
                
            } else {

                $sql = "select roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`

                  from roster_times_staff
                  left join users on users.id = roster_times_staff.staff_id
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.staff_id = " . $_SESSION['user_id'] . "
                  and rosters.is_published = 1 and roster_times_staff.status != 1  and '$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL -15 MINUTE)
                  and '$time_date' < DATE_ADD(roster_times.finish_time_date, INTERVAL 180 MINUTE) "
                        . "and roster_times_staff.start_time_date = '0000-00-00 00:00:00' and roster_times_staff.finish_time_date = '0000-00-00 00:00:00'"
                        . "order by roster_times_staff.id asc limit 1  ";

                $result = $this->dbi->query($sql);

                if ($result->num_rows != 0) {
                    
                } else {

                    $sql = "select roster_times_staff.id, roster_times.start_time_date as `roster_start`, roster_times.finish_time_date as `roster_finish`, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, users2.id as `site_id`, users2.name as `site`
                        from roster_times_staff
                        left join users on users.id = roster_times_staff.staff_id
                        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                        left join rosters on rosters.id = roster_times.roster_id
                        left join users2 on users2.id = rosters.site_id
                        where roster_times_staff.staff_id = " . $_SESSION['user_id'] . "
                        and '$time_date' > DATE_ADD(roster_times.start_time_date, INTERVAL -15 MINUTE)
                        and '$time_date' < DATE_ADD(roster_times.finish_time_date, INTERVAL 180 MINUTE) "
                            . "and roster_times_staff.start_time_date != '0000-00-00 00:00:00' and roster_times_staff.finish_time_date != '0000-00-00 00:00:00'"
                            . " order by roster_times_staff.id desc limit 1  ";

                    $result = $this->dbi->query($sql);
                }
            }


            //$str .= "<h3>".$sql."</h3>";
//              and roster_times.finish_time_date < now()
            if ($result) {
                $latest_check = 0;
                while ($myrow = $result->fetch_assoc()) {

                    $start_time_date = $myrow['start_time_date'];
                    $finish_time_date = $myrow['finish_time_date'];
                    // $str .= "<h3>$start_time_date, $start_time_date</h3>";

                    $id_sel = $myrow['id'];
                    $roster_times_staff_idin = $myrow['id'];
                    $comment = $myrow["staff_comment"];
                    $site = $myrow['site'];
                    //$comment = "<div class=\"cl\"></div><br /><textarea placeholder=\"Comment\" name=\"txaComment_$id_sel\" style=\"$text_style;\">$comment</textarea> ";

                    $ac_type = ($start_time_date == '0000-00-00 00:00:00' && $finish_time_date == '0000-00-00 00:00:00' ? 'ON' : ($finish_time_date == '0000-00-00 00:00:00' ? 'OFF' : "UNDO"));

//            $str .= "<h3>test ".$myrow['site_id']." - ".$myrow['site']." - ".$myrow['on_or_off']."</h3>";
                    $site_id_in = $myrow['site_id'];
                }
            }

            if (!$site_id_in) {
                $err = 1;
            } else {
                $ac_type = ($ac_type ? $ac_type : 'OFF');
                //return $ac_type;
                if ($latitude) {

                    $found = 0;

//                    $sql = "SELECT t1.user_id as `site_id`, users.name as `site`,
//              (SELECT meta_value FROM usermeta WHERE meta_key=93 AND user_id = t1.user_id) AS `latitude`,
//              (SELECT meta_value FROM usermeta WHERE meta_key=94 AND  user_id = t1.user_id) AS `longitude`
//          FROM usermeta AS t1
//          left join users on users.id = t1.user_id
//          where t1.meta_key = 93 or t1.meta_key = 94
//          GROUP BY t1.user_id";
//
//                    $result_gps = $this->dbi->query($sql);
//                    $cnt = 0;
//                    $found = 0;
//                    $dist_check = 1000;
//                    while ($myrow = $result_gps->fetch_assoc()) {
//                        $long = $myrow['longitude'];
//                        $lat = $myrow['latitude'];
//                        if (is_numeric($lat) && is_numeric($long) && is_numeric($latitude) && is_numeric($longitude))
//                            $distance = $this->distance($lat, $long, $latitude, $longitude);
//                        //$str .= "<h3>$lat, $long, $latitude, $longitude -- $distance</h3>";
//                        if ($distance < $dist_check) {
//                            $found = 1;
//                            $site_id = $myrow['site_id'];
//                            $site_ids[] = $myrow['site_id'];
//                            $site_name = $myrow['site'];
//                            $sign_text = ($ac_type == 'ON' || $ac_type == 'OFF' ? "Sign $ac_type @ " : "UNDO Previous Sign Off from ");
//                            $clock_str .= '<input onClick="clock_it(\'' . $ac_type . '\', ' . $site_id . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="' . $sign_text . $site_name . '" />';
//                        }
//                    }
                }

                $page_to_load = "Placeholder";
                $check_interval = "180000";

                $str .= '<script type="text/javascript">
                  load_page("' . $page_to_load . '");
                  setInterval(function () {load_page("' . $page_to_load . '")} , ' . $check_interval . ');
                  </script>';

                if ($msg)
                    $str .= $this->message($msg, 2000);


                $str .= '<input type="hidden" name="hdnFlag" id="hdnFlag" value="' . $hdnFlag . '" />
                  <input type="hidden" name="hdnSiteId" id="hdnSiteId" />
                  <input type="hidden" name="delid" id="delid" />
                  <input type="hidden" name="hdnSaveComments" id="hdnSaveComments" />';

                $str .= '<h3 class="fl" style="' . $font_size . '; margin-right: 30px;">Sign On/Off</h3>';
                if ($latitude) {
                    $str .= '<span class="fl help_message" style="' . $font_size . '">GPS: ' . round($latitude, 5) . ", " . round($longitude, 5) . '</span>';
                    //$str .= '<span class="fl help_message" style="' . $font_size . '">GPS: -33.930019, 150.940504</span>';
                }

                if ($ac_type == 'UNDO') {
                    $str .= '
          <input type="hidden" name="txtComment" value="' . $comment . '" />
          <div class="cl"></div><h3><br/>You are now SIGNED OFF</h3><br/><br/><br/><br/>If you\'ve signed off by mistake, please press the button below to sign back on.<br/><br/>';
                } else {
                    if ($ac_type == 'OFF') {
                        $str .= $NotSignOffMessage;
                        $str .= '<div class="cl"></div><h3><br/>You are now SIGNED ON</h3>';
                    }

                    if ($ac_type == 'ON') {

                        $report_edit_id = $this->checkPreStartReportExist(132, $roster_times_staff_idin);

                        if (!$report_edit_id) {
                            // die("test");
                            echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=132&site_id=' . $roster_times_staff_idin);
                            // /Compliance?report_edit_id=97296
                        } else {

                            $oldReportLink = $this->f3->get('main_folder') . 'Compliance?report_edit_id=' . $report_edit_id;

                            //echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_edit_id='.$report_edit_id);   
                        }

                        $report_status = $this->checkPreStartReportCompleted($report_edit_id);

                        if ($report_status >= 70) {
                            $reprotLink = '</br></br></br><div style="color:green"><a style="color:green" class="" href="/Compliance?report_edit_id=' . $report_edit_id . '"> Thank you for completing the Take 5 Report. 
You can sign on to your shift. Stay safe ! </a></div></br>'
                                    . '<div style="clear:both"></div> ';
                        } else {
                            $reprotLink = '</br></br></br><div style="color:red"><a style="color:red" class="" href="/Compliance?report_edit_id=' . $report_edit_id . '"> Prior to signing on, you must complete Take 5 Checklist </a></div></br>'
                                    . '<div style="clear:both"></div> ';
                            //die;
                            echo $this->redirect($oldReportLink);
                            // echo $this->redirect($this->f3->get('main_folder') . 'Compliance?report_id=132&site_id=' . $roster_times_staff_idin);
                        }
                    }
                    $str .= $reprotLink . '<textarea placeholder="Comment (Optional)" style="' . $text_style . '" name="txtComment">' . $comment . '</textarea>';
                }



                //update `roster_times_staff` set start_time_date = '0000-00-00 00:00:00' where id = 1192

                $sql = "SELECT id FROM `roster_times_staff` WHERE staff_id = " . $_SESSION['user_id'] . " and start_time_date != '0000-00-00 00:00:00' and finish_time_date = '0000-00-00 00:00:00'
                and '$time_date' > DATE_ADD(roster_times_staff.start_time_date, INTERVAL -15 MINUTE)
                and '$time_date' < DATE_ADD(roster_times_staff.finish_time_date, INTERVAL 180 MINUTE)
                order by id DESC limit 1";
                //echo $sql;
                //die;
                //$str .= $sql;
                if ($result = $this->dbi->query($sql)) {
                    while ($myrow = $result->fetch_assoc()) {
                        $roster_times_staff_idin = $myrow['id'];
                        $str .= '<input onClick="clock_it(\'COMMENT\', ' . $roster_times_staff_idin . ');" type="button" class="clock_on" value="Save Comment" />';
                    }
                }


                if (!$found) {
                    //$str .= '<div class="cl"></div><div class="fl"><h3 >No Sites Found </h3>' . $site_search . '</div>';

                    $str .= '<div class="cl"></div>' . $site_search;
                    //if($ac_type == 'OFF') {
                    $sql = "select users.id as `idin`, users.name from users where id = $site_id_in";

                    /* } else {
                      $sql = "select users.id as `idin`, users.name
                      from users
                      left join associations on associations.parent_user_id = users.id
                      where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                      //. " and users.id = $site_id_in ";
                      } */

                    //$str .= "$sql";
                    $result = $this->dbi->query($sql);

                    $sign_text = ($ac_type == 'ON' || $ac_type == 'OFF' ? "Sign $ac_type @ " : "UNDO Previous Sign Off from ");

                    $roster_Notes = $this->rosterTimeDetailByTimeStaffid($roster_times_staff_idin);
                    //prd($roster_Notes);
                    $str .= "<br> Notes: " . $roster_Notes['roster_time_notes'];
                    $notesFolderUrl = $this->f3->get('base_url') . "/edge/downloads/roster_notes/" . $roster_Notes['roster_time_notes_image'];
                    // $str .= "<br><br>Image: ".$roster_Notes['roster_time_notes_image'];

                    $str .= '<br><br><div class="fl"><nobr></nobr> Notes Image
<a target="_blank" href="' . $notesFolderUrl . '"><img style="max-width: 100px" src="' . $notesFolderUrl . '" /></a></div><br><br>';

                    //$sign_text .=  $reprotLink;
                    while ($myrow = $result->fetch_assoc()) {


                        if ($ac_type == 'OFF') {
                            $report_signoff_edit_id = $this->checkPreStartReportExist(141, $roster_times_staff_idin);
                            if (!$report_signoff_edit_id) {
                                
                                // die("test");
                                $str .= '</br></br><input onClick="clock_it(\'' . $ac_type . '\', ' . $roster_times_staff_idin . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="Shift Ending Client Handover" />';
                                // /Compliance?report_edit_id=97296
                            } else {

                                $signoff_report_status = $this->checkPreStartReportCompleted($report_signoff_edit_id);

                                if($signoff_report_status >= 95) {
                                    $str .= '</br><div style="color:green"><a style="color:green" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Thank you for completing the Shift Ending Client Handover Report. 
You can sign off to your shift. Stay safe ! </a></div></br>'
                                            . '<div style="clear:both"></div> ';
                                    
//                           $str .= '</br> <b>Send Handover Report</b> <input type="checkbox" name="send_handover" id="send_handover"  checked value="1">'
//                                    . '</br>'
//                                    . '<div style="clear:both"></div> ';
                                    
                                    
                                    $str .= '</br></br><input onClick="clock_it(\'' . $ac_type . '\', ' . $roster_times_staff_idin . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="' . $sign_text . $myrow['name'] . '" />';
                                } else {
                                    
                                    $str .= '</br></br></br><div style="color:red"><a style="color:red" class="" href="/Compliance?report_edit_id=' . $report_signoff_edit_id . '"> Prior to signing off, you must complete Shift Ending Client Handover Report </a></div></br><br>';
//                                                . '<div style="clear:both"></div> ';
                                    $str .= '</br></br><input onClick="clock_it(\'' . $ac_type . '\', ' . $roster_times_staff_idin . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="Shift Ending Client Handover" />';
                                }
                            }
                        } else {
                            $str .= '</br></br><input onClick="clock_it(\'' . $ac_type . '\', ' . $roster_times_staff_idin . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="' . $sign_text . $myrow['name'] . '" />';
                        }

                        // $str .= '<input onClick="clock_it(\'' . $ac_type . '\', ' . $myrow['idin'] . ');" type="button" class="clock_' . (strtolower($ac_type)) . '" value="' . $sign_text . $myrow['name'] . '" />';
                    }
                }


                $str .= '
        <span id="myDiv"></span>
        <div align="left">' . $clock_str;
            }
        } else {
            $err = 1;
        }
        if ($err) {
            //$str .= date('Y-m-d h:i'); 
            $str .= "<h3>You are currently not rostered on.</h3><h3>You may sign in up to 15 minutes before the start of your shift.</h3>";
            $sql = "select users2.name as `site`,
                  DATE_FORMAT(roster_times.start_time_date, '%d-%b-%Y') as `start_date`,
                  DATE_FORMAT(roster_times.finish_time_date, '%d-%b-%Y') as `finish_date`,
                  DATE_FORMAT(roster_times.start_time_date, '%H:%i') as `start_time`,
                  DATE_FORMAT(roster_times.finish_time_date, '%H:%i') as `finish_time`
                  from roster_times_staff
                  left join roster_times on roster_times.id = roster_times_staff.roster_time_id
                  left join rosters on rosters.id = roster_times.roster_id
                  left join users2 on users2.id = rosters.site_id
                  where roster_times_staff.staff_id = " . $_SESSION['user_id'] . "
                  and '$time_date' < roster_times.start_time_date
                  and '$time_date' > now()
                  order by roster_times.start_time_date DESC
                  LIMIT 1
                  ";
            //$str .= "<h3>".$sql."</h3>";
            //              and roster_times.finish_time_date < now()
            //return $str;
            if ($result = $this->dbi->query($sql)) {
                $latest_check = 0;
                if ($myrow = $result->fetch_assoc()) {
                    $start_date = $myrow['start_date'];
                    $start_time = $myrow['start_time'];
                    $site = $myrow['site'];
                    $str .= "<h3>Your next shift is at $site and starts on $start_date at $start_time.</h3>";
                }
            }

            $str .= '<p><a class="list_a" href="' . $this->f3->get('main_folder') . 'Clock2">Start Again...</a></p>';
            $str .= '<p><a class="list_a" href="' . $this->f3->get('main_folder') . 'MyRoster">View Roster</a></p>';
        }
        return $str;
    }

    function get_times() {
        $state_array = array('NSW', 'ACT', 'VIC', 'TAS', 'QLD', 'SA', 'WA', 'NT');
        $capital['NSW'] = "Sydney";
        $capital['ACT'] = "Canberra";
        $capital['VIC'] = "Melbourne";
        $capital['TAS'] = "Hobart";
        $capital['QLD'] = "Brisbane";
        $capital['SA'] = "Adelaide";
        $capital['WA'] = "Perth";
        $capital['NT'] = "Darwin";
        $header .= '<div style="margin: 0px; padding: 0px; padding-bottom: 0px; font-size: 32px; color: #000066; font-weight: bold; float: left;">' . ($on_off ? 'SIGNON/OFF' : 'WELFARE') . ' DASHBOARD</div>';
        $str .= '<div class="state_times">NSW, ACT, VIC, TAS: ';
        foreach ($state_array as $st) {
            if ($st == 'NSW' || $st == 'ACT' || $st == 'VIC' || $st == 'TAS') {
                if ($st == 'NSW') {
//          date_default_timezone_set('NSW');
                    date_default_timezone_set('Australia/Sydney');
                    $str .= date("d/m H:i");
                }
            } else {
                if ($st != 'NT') {
                    date_default_timezone_set("Australia/{$capital[$st]} ");
                    $str .= " | $st: " . date("d/m H:i");
                }
            }
        }
        $str .= '</div>';
        return $str;
    }

    function add_staff_to_roster($user_id, $start_date, $start_time, $finish_time, $rid, $finish_date = 0, $minutes_unpaid = 0, $teamGroupName = "") {
        $start_date = date('Y-m-d', strtotime((strpos($start_date, ',') === true ? substr($start_date, 4) : $start_date)));

        $finish_date = ($finish_date ? $finish_date : (strtotime($start_time) < strtotime($finish_time) ? $start_date : $this->get_sql_result("select DATE_ADD('$start_date', INTERVAL 1 DAY) as `result`")));

        $start_time_date = $start_date . ' ' . $start_time;
        $finish_time_date = $finish_date . ' ' . $finish_time;

        if ($user_id) {
            $banned = $this->get_sql_result("select id as `result` from associations where child_user_id = $user_id and parent_user_id = (select site_id from rosters where id = $rid) and association_type_id = (select id from association_types where name = 'site_banned_staff')");
            if ($banned) {
                $staff_name = $this->get_sql_result("select CONCAT(employee_id, ' - ', name, ' ', surname) as `result` from users where id = $user_id");
                $msg = "$staff_name has been banned from this site. ";
            } else {
                $sql = "select roster_times.id, rosters.id as `rid`, users.name as `staff`, users2.name as `site`, roster_times.id as `roster_time_id`
        from roster_times_staff
        left join roster_times on roster_times.id = roster_times_staff.roster_time_id
        left join rosters on rosters.id = roster_times.roster_id
        left join users2 on users2.id = rosters.site_id
        left join users on users.id = roster_times_staff.staff_id
        where users.id = $user_id
        and
        ((roster_times.start_time_date BETWEEN '$start_time_date' AND '$finish_time_date')
        or
        (roster_times.finish_time_date BETWEEN '$start_time_date' AND '$finish_time_date'))
        and rosters.id != $rid
        ";

                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $ridin = $myrow['rid'];
                        $staff_name = $myrow['staff'];
                        $site = $myrow['site'];
                        $roster_time_id = $myrow['roster_time_id'];
                        $msg = "$staff_name has already been booked to " . ($rid != $ridin ? "$site. <a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Rostering/EditRoster?rid=$ridin\">Open Conflicting Roster</a>" : "this site.");
                    }
                }
            }
        }
        if ($msg) {
            $str .= "<h3 class=\"message\">$msg</h3>";
        } else {


            $sql = "select id from roster_times where roster_id = $rid and start_time_date = '$start_time_date' and finish_time_date = '$finish_time_date' and team_group_name = '$teamGroupName' ";
            $roster_time_id = 0;
            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {
                    $roster_time_id = $myrow['id'];
                }
            }
            if (!$roster_time_id) {
                $sql = "insert into roster_times (roster_id, start_time_date, finish_time_date, minutes_unpaid,team_group_name) values ($rid, '$start_time_date', '$finish_time_date', $minutes_unpaid,'$teamGroupName');";
                $this->dbi->query($sql);
                $roster_time_id = $this->dbi->insert_id;
                $detail = "Shift Created";
                $this->rosterLog(3, NULL, $roster_time_id, NULL, $detail);
                //$this->sendRosterAlterMessage($user_id, "added", $roster_time_id);
            }
            if ($user_id) {
                $sql = "insert ignore into roster_times_staff (staff_id, roster_time_id) values ($user_id, $roster_time_id)";
                $this->dbi->query($sql);
                $roster_time_staff_id = $this->dbi->insert_id;
                $this->sendRosterAlterMessage($user_id, "added", $roster_time_staff_id);
            }
        }

        return $str;
    }

    function clear_data() {
        $this->dbi->multi_query("delete from roster_times where roster_id not in (select id from rosters); delete from roster_times_staff where roster_time_id not in (select id from roster_times);");
    }

    function AdjustTimes() {
        $adjust_time_id = (isset($_GET['adjust_time_id']) ? $_GET['adjust_time_id'] : 0);

        $sql = "
    SELECT
    roster_times.start_time_date, roster_times.finish_time_date,
    DATE_FORMAT(roster_times.start_time_date, '%W, %d-%b-%Y %H:%i') as `start`,
    DATE_FORMAT(roster_times.finish_time_date, '%W, %d-%b-%Y %H:%i') as `finish`,
    ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2)) as `hours`
    FROM `roster_times_staff`
    inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
    where roster_times_staff.id = $adjust_time_id";
        if ($result = $this->dbi->query($sql)) {
            if ($myrow = $result->fetch_assoc()) {
                $start = $myrow['start'];
                $finish = $myrow['finish'];
                $start_raw = $myrow['start_time_date'];
                $finish_raw = $myrow['finish_time_date'];
                $hours = $myrow['hours'];
                $this->dbi->query("update roster_times_staff set status = 5, start_time_date = '$start_raw', finish_time_date = '$finish_raw' where id = $adjust_time_id");
                echo "$start||||$finish||||$hours";
            }
        }
    }

    function bgCellColour($objPHPExcel, $cells, $colour) {
        $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => array(
                'rgb' => $colour
            )
        ));
    }

    function TimeSheets() {

        $division_id = $this->division_id;
        $weeks = (isset($_GET['weeks']) ? $_GET['weeks'] : -0);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $download_myob = (isset($_GET['download_myob']) ? $_GET['download_myob'] : null);

        $date_by = (isset($_GET['date_by']) ? $_GET['date_by'] : date("D, d/M/Y", strtotime("monday this week", strtotime(($calByDate ? str_replace('/', '-', $calByDate) : "$weeks weeks")))));

        $rid = $this->rid;
        $sid = (isset($_GET['sid']) ? $_GET['sid'] : 0);
        $hdnFrom = (isset($_POST['hdnFrom']) ? $_POST['hdnFrom'] : null);
        $hdnId = (isset($_POST['hdnId']) ? $_POST['hdnId'] : null);
        $itm = new input_item;
        $itm->hide_filter = 1;
        $edit_mode = (isset($_GET['edit_mode']) ? $_GET['edit_mode'] : null);
        $my_id = $_SESSION['user_id'];

        $separate_sites = (isset($_GET['separate_sites']) ? $_GET['separate_sites'] : null);

        $update_times = (isset($_GET['update_times']) ? $_GET['update_times'] : null);
        if ($update_times) {
            $sql = "
      update roster_times_staff 
      inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
      inner join rosters on rosters.id = roster_times.roster_id
      set roster_times_staff.start_time_date = roster_times.start_time_date, roster_times_staff.finish_time_date = roster_times.finish_time_date
      where
      YEARWEEK(roster_times.start_time_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)
      and rosters.division_id = $division_id";
            $this->dbi->query($sql);
        }

        //Current Styles for testing
        $str .= "
    <style>
    </style>
    <script></script>
    ";

        //$calByDate = (isset($_REQUEST['calByDate']) ? $_REQUEST['calByDate'] : 0);



        $str .= '<div class="fr">' . $this->division_nav($division_id, 'Rostering/Timesheets', 0, 0, 1, ($weeks ? "&weeks=$weeks" : "") . ($edit_mode ? "&edit_mode=$edit_mode" : "")) . '</div>';

        $str .= $itm->setup_cal();
        //function cal($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
        /* $str .= '
          <script>
          function by_date() {
          if(document.getElementById("calByDate").value) {
          document.frmEdit.submit()
          }
          }
          </script>
          <input type="hidden" name="division_id" value="'.$division_id.'" />
          <div class="fl">';
          $str .= $itm->cal("calByDate", "", ' style="width: 100px;" placeholder="By Date" data-uk-tooltip title="View the Time Sheets by the week Chosen Date"', "", "", "", "");
          $str .= '<input type="button" onClick="by_date()" class="btn" value="Go">
          ';
          if(!$edit_mode) {
          $str .= '
          <form method="GET" name="frmByDate">
          <input type="hidden" name="division_id" value="'.$division_id.'" />
          <div class="fl">';
          $str .= $itm->cal("calByDate", "", ' style="width: 100px;" placeholder="By Date" data-uk-tooltip title="View the Time Sheets by the week Chosen Date"', "", "", "", "");
          $str .= '<input type="button" onClick="by_date()" class="btn" value="Go">
          </form>
          ';
          } */

        $str .= '<div class="fl">';
        $week_count = 15;
        $siteId = $_REQUEST['site_id'];

//    WHERE DATE(ramses.batch_log.start_time) < SUBDATE(SUBDATE(NOW(), INTERVAL WEEKDAY(NOW()) DAY), INTERVAL 2 WEEK);
//return date('Y-m-d', strtotime('last week Monday'));

        $Sitestr = "";
        if ($division_id) {

            $locationData = $this->getLocation($division_id);
            $changeUrl = $this->f3->get('main_folder') . 'Rostering/TimeSheets?1' . ($division_id ? "&division_id=$division_id" : "") . ($weeks ? "&weeks=$weeks" : "") . ($edit_mode ? "&edit_mode=$edit_mode" : "");

            $Sitestr = '<select id="site_id" name="site_id"><option value="' . $this->f3->get('main_folder') . 'Rostering/TimeSheets' . '"> Select Site </option>';

            if (!empty($locationData)) {

                foreach ($locationData as $location) {

                    if ($siteId == $location['id']) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }

                    $Sitestr .= '<option value="' . $changeUrl . "&site_id=" . $location['id'] . '" ' . $selected . '>' . $location['item_name'] . '</option>';
                }

                $Sitestr .= "</select>";
            }
        }


        for ($i = 0; $i <= $week_count; $i++) {
            $date_from = date("D, d/M/Y", strtotime("monday this week", strtotime("-$i Weeks")));
            $str .= '<a data-uk-tooltip title="Show time sheets from<br />' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : "$i Weeks Ago")) . '.<br />Starting ' . $date_from . '" class="division_nav_item ' . ($weeks == -$i ? "division_nav_selected" : "") . '" href="TimeSheets?weeks=' . -$i . ($division_id ? "&division_id=$division_id" : "") . ($siteId ? "&site_id=$siteId" : "") . ($edit_mode ? "&edit_mode=$edit_mode" : "") . '">' . ($i == 0 ? 'This Week' : ($i == 1 ? 'Last Week' : $i)) . '</a>';
        }

        $str .= '&nbsp; &nbsp;<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/TimeSheets?a=1' . ($division_id ? "&division_id=$division_id" : "") . ($weeks ? "&weeks=$weeks" : "") . ($siteId ? "&site_id=$siteId" : "") . ($siteId ? "&site_id=$siteId" : "") . ($edit_mode ? '">Staff View' : '&edit_mode=1">Detailed View') . '</a>';
        $str .= '&nbsp; &nbsp;<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/TimeSheets?download_xl=1' . ($division_id ? "&division_id=$division_id" : "") . ($weeks ? "&weeks=$weeks" : "") . ($siteId ? "&site_id=$siteId" : "") . '">Download Excel</a>';

        $str .= "</div><div class=\"fr divisionlink\">$division_str</div><div class=\"cl\"></div>";

        $str .= "</div><div class=\"fl sitelink\">$Sitestr</div><div class=\"cl\"></div>";

        $str .= '<h3>Week Starting ' . $date_by . '&nbsp; &nbsp;<a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/TimeSheets?update_times=1' . ($division_id ? "&division_id=$division_id" : "") . ($weeks ? "&weeks=$weeks" : "") . ($edit_mode ? "&edit_mode=$edit_mode" : "") . ($siteId ? "&site_id=$siteId" : "") . '">Adjust ALL Times</a></h3>';

        $str .= '<div class="cl"></div>';

        $this->list_obj->show_num_records = 0;
        $this->list_obj->header_row = 1;

        $str .= '
        <script>
        $("#site_id").change(function(){
            newurl = this.value;
            window.location = newurl;


        });
            
        </script>';

//SET @row_number = 0;

        if ($edit_mode) {
            $str .= '
        <script>
          function adjust_times(id) {
            $.ajax({
              type:"get",
                  url:"' . $this->f3->get('main_folder') . 'AdjustTimes",
                  data:{ adjust_time_id: id } ,
                  success:function(msg) {
                    var res = msg.split("||||");
                    document.getElementById(id + "-6").innerHTML = res[0]
                    document.getElementById(id + "-7").innerHTML = res[1]
                    document.getElementById(id + "-8").innerHTML = res[2]
                  }
            } );
          }
        </script>';
            $siteCond = "";
            if ($siteId) {
                $siteCond = " and rosters.site_id = $siteId ";
            }

            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 1000;
            $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "select
      CONCAT(users2.name, ' ', users2.surname) as `Site`,
      roster_times_staff.id as `idin`,
      CONCAT(users.employee_id, ' - ', users.name, ' ', users.surname) as `Staff Name`,
      DATE_FORMAT(roster_times.start_time_date, '%W, %d-%b-%Y %H:%i') as `Rostered Start`,
      DATE_FORMAT(roster_times.finish_time_date, '%W, %d-%b-%Y %H:%i') as `Rostered Finish`,
      ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2)) as `Rostered Hours`,
      rlt.leave_title as `Leave Type`,
      DATE_FORMAT(roster_times_staff.start_time_date, '%W, %d-%b-%Y %H:%i') as `Actual Start`,
      DATE_FORMAT(roster_times_staff.finish_time_date, '%W, %d-%b-%Y %H:%i') as `Actual Finish`,
      IF(roster_times_staff.finish_time_date,ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times_staff.start_time_date, roster_times_staff.finish_time_date)) / 3600, 2)),0) as `Actual Hours`, 'Edit' as `*`, 'Delete' as `!`,
      CONCAT('<a class=\"list_a\" href=\"JavaScript:adjust_times(', roster_times_staff.id, ')\">Adjust Times</a>') as `Adjust Times`
      from roster_times_staff      
      inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
      left join roster_leave_types rlt on rlt.id = roster_times_staff.leave_id
      left join rosters on rosters.id = roster_times.roster_id
      left join users on users.id = roster_times_staff.staff_id
      left join users2 on users2.id = rosters.site_id
      where
      YEARWEEK(roster_times.start_time_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)
      and rosters.division_id = $division_id
      $siteCond   
      order by users2.name, roster_times.start_time_date, roster_times.finish_time_date
      ";

//      echo  $this->list_obj->sql;
//      die;



            $str .= '<div class="form-wrapper">
      <div class="form-header">
      <div class="fl">Change Start Times and Dates</div>
      <div class="fr">
      <!-- <a class="list_a" href="' . $this->f3->get('main_folder') . 'Rostering/TimeSheets?weeks=' . $weeks . '&division_id=' . $division_id . '">Time Sheets &gt;&gt;</a> -->
      </div>
      <div class="cl"></div>
      </div>
      ';
            //$this->editor_obj->custom_field = "user_id";
            //$this->editor_obj->custom_value = $_SESSION['user_id'];
            $this->editor_obj->table = "roster_times_staff";
            $style = 'class="full_width"';

            $this->editor_obj->form_attributes = array(
                array("tadStartTimeDate", "tadFinishTimeDate"),
                array("Start Date/Time", "Finish Date/Time"),
                array("start_time_date", "finish_time_date"),
                array("", ""),
                array($style, $style),
                array("c", "c")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );
            $this->editor_obj->form_template = '
                <div class="fl small_textbox"><nobr>ttadStartTimeDate</nobr><br />tadStartTimeDate</div>
                <div class="fl small_textbox"><nobr>ttadFinishTimeDate</nobr><br />tadFinishTimeDate</div>
                <div class="fl" style="margin-left: 5px; margin-top: 3px"><br />' . $this->editor_obj->button_list() . '</div>
                <div class="cl"></div>
                ';

            $this->editor_obj->editor_template = '

                  <div class="form-content">
                  editor_form
                  </div>
                  </div>
                  editor_list
      ';

            $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        } else {
            if ($download_xl) {
                $date_by = substr(str_replace("/", "-", $date_by), 5);
                $fileName = "timesheets-$date_by.xlsx";
                $column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
                $objPHPExcel = new PHPExcel();
                $objPHPExcel->setActiveSheetIndex(0);
                $columnLength = 4;
                $field_count = 0;
                $objPHPExcel->getActiveSheet()->SetCellValue("A1", "Employee ID");
                $objPHPExcel->getActiveSheet()->SetCellValue("B1", "Employee Name");
                $objPHPExcel->getActiveSheet()->SetCellValue("C1", "Hours Worked");
                $objPHPExcel->getActiveSheet()->SetCellValue("D1", "Pay");
                $objPHPExcel->getActiveSheet()->getStyle($column_array[0] . "1:" . $column_array[$columnLength] . "1")->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle($column_array[0] . "1:" . $column_array[$columnLength] . "1")->getFont()->setSize(14);
                $row_count = 2;
            }
//      $main_sql = "select roster_times_staff.id as idin,
//      users.employee_id,
//      roster_times_staff.start_time_date,
//      roster_times_staff.finish_time_date,
//      roster_times.start_time_date as `rostered_start_time_date`,
//      roster_times.finish_time_date as `rostered_finish_time_date`,
//      roster_times.minutes_unpaid,
//      users2.state,
//      users.id as `staff_id`, CONCAT(users.name, ' ', users.surname) as `Staff`,
//      users2.id as `site_id`, CONCAT(users2.name, ' ', users2.surname) as `Site`
//      FROM rosters
//      inner join roster_times on roster_times.roster_id = rosters.id
//      inner join roster_times_staff on roster_times_staff.roster_time_id = roster_times.id
//      inner join users on users.id = roster_times_staff.staff_id
//      left join users2 on users2.id = rosters.site_id
//      LEFT JOIN states ON users2.state = states.id
//      where YEARWEEK(roster_times.start_time_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)
//      and rosters.division_id = $division_id
//      order by users2.name, users.name
//      
//      ";


            $main_sql = "SELECT roster_times_staff.id AS idin, users.id AS `staff_id`, users.employee_id, CONCAT(users.name, ' ', users.surname) AS `Staff`,IF(provider.id,CONCAT(provider.name, ' ', provider.surname),'Allied') AS `providername`, users2.id AS `site_id`, CONCAT(users2.name, ' ', users2.surname) AS `Site`, users2.state, states.item_name location_state, 
userstates.item_name employee_state, CONCAT('', clt.name, ' ', clt.surname, '') AS 'Client',
roster_times.start_time_date AS `rostered_start_time_date`, roster_times.finish_time_date AS `rostered_finish_time_date`, rlt.leave_title, roster_times_staff.start_time_date, roster_times_staff.finish_time_date, roster_times_staff.staff_comment, roster_times_staff.controller_comment, roster_times.minutes_unpaid
FROM rosters
INNER JOIN roster_times ON roster_times.roster_id = rosters.id
INNER JOIN roster_times_staff ON roster_times_staff.roster_time_id = roster_times.id
INNER JOIN users ON users.id = roster_times_staff.staff_id 
left JOIN users provider ON provider.ID = users.provider_id
left JOIN roster_leave_types rlt ON rlt.id = roster_times_staff.leave_id 
left JOIN users2 ON users2.id = rosters.site_id 
LEFT JOIN associations ass1 ON ass1.child_user_id = users2.id AND ass1.association_type_id = 1
LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
LEFT JOIN states ON users2.state = states.id 
LEFT JOIN states userstates ON users.state = userstates.id
      where YEARWEEK(roster_times.start_time_date, 7) = YEARWEEK(CURDATE() - INTERVAL -$weeks WEEK, 7)
      and rosters.division_id = $division_id
      order by users2.name, users.name      
      ";

            if ($result = $this->dbi->query($main_sql)) {
                $sql = "select lookup_fields.id, lookup_fields.value from lookup_fields
          left join lookups on lookups.id = lookup_fields.lookup_id 
          where lookups.item_name = " . ($division_id == 108 ? "'security_officer_level'" : "'cleaning_services_level'");
                if ($result2 = $this->dbi->query($sql)) {
                    while ($myrow2 = $result2->fetch_assoc()) {
                        $tmp = $myrow2['value'];
                        $paysplit = explode(",", $tmp);
                        $fulltime_rate[$myrow2['id']] = $paysplit[0];
                        $part_time_rate[$myrow2['id']] = $paysplit[1];
                        $casual_rate[$myrow2['id']] = ($division_id == 108 ? $part_time_rate[$myrow2['id']] : $paysplit[2]);
                    }
                }


                $current_date = date("Y-m-d");
                if (!$view_mode)
                    $str .= '<br />';
                $str .= '<table class="uk-table-condensed uk-table-hover uk-table-divider uk-table-middle uk-table-striped uk-table-small" border="0" cellpadding="0">';
                $str .= "<tr><th>ID</th><th>Name</th><th>Rostered</th><th>Weekday</th><th>Weeknight</th><th>Saturday</th><th>Sunday</th><th>Public Holiday</th><th>Paid Hours</th>" . ($separate_sites ? "<th>Site</th>" : "") . "<th>Pay</th></tr>";
                $old_site_id = 0;
                $old_state = 0;
                $staff_hours_rostered = Array();
                $exlKey = 0;
                while ($myrow = $result->fetch_assoc()) {
                    $site_id = $myrow['site_id'];
                    $staff_id = $myrow['staff_id'];
                    $employee_id = $myrow['employee_id'];
                    $site = $myrow['Site'];
                    $staff = $myrow['Staff'];
                    $state = $myrow['state'];
                    $rostered_start_time_date = $myrow['rostered_start_time_date'];
                    $rostered_finish_time_date = $myrow['rostered_finish_time_date'];
                    //$rostered_finish_time_date = $myrow['leave_title'];
                    $minutes_unpaid = $myrow['minutes_unpaid'];

                    $start_time_date = $myrow['start_time_date'];
                    $finish_time_date = $myrow['finish_time_date'];

                    $sql = "
            select weekdays, weekdays_nights, saturdays, sundays, public_holidays from staff_payrates where division_id = $division_id and site_id = $site_id and staff_id = $staff_id
            union select weekdays, weekdays_nights, saturdays, sundays, public_holidays from staff_payrates where division_id = $division_id and staff_id = $staff_id
            ";
                    //return $sql;
                    $weekdays = 0;
                    if ($result2 = $this->dbi->query($sql)) {
                        if ($myrow2 = $result2->fetch_assoc()) {
                            $weekdays = $myrow2['weekdays'];
                            $weekdays_nights = $myrow2['weekdays_nights'];
                            $saturdays = $myrow2['saturdays'];
                            $sundays = $myrow2['sundays'];
                            $public_holidays = $myrow2['public_holidays'];
                        }
                    }
                    if (!$weekdays) {
                        $officer_level_id = $this->get_sql_result("SELECT meta_value as `result` FROM usermeta where user_id = $staff_id and meta_value in (
                select lookup_fields.id
                from lookup_fields
                left join lookups on lookups.id = lookup_fields.lookup_id 
                where lookups.item_name = " . ($division_id == 108 ? "'security_officer_level'" : "'cleaning_services_level'") . ");");
                        $worker_classification = $this->get_sql_result("SELECT lower(lookup_fields.item_name) as `result` FROM usermeta 
                  left join lookup_fields on lookup_fields.id = usermeta.meta_value
                  where user_id = $staff_id and meta_value in (
                    select lookup_fields.id
                    from lookup_fields
                    left join lookups on lookups.id = lookup_fields.lookup_id 
                    where lookups.item_name = 'worker_classification'
                  );");

                        $shrt = substr($worker_classification, 0, 4);
                        $payrate = ($shrt == 'full' ? $fulltime_rate[$officer_level_id] : ($shrt == 'part' ? $part_time_rate[$officer_level_id] : $casual_rate[$officer_level_id]));

                        $weekdays = $payrate;
                        $weekdays_nights = $payrate * 1.2;
                        $saturdays = $payrate * 1.5;
                        $sundays = $payrate * 2;
                        $public_holidays = $payrate * 2.5;

                        //$staff = "$worker_classification $staff ($payrate)";
                    }

                    if ($old_site_id != $site_id || !$state) {
                        if (!$state)
                            $state = 2;
                        $this->get_public_holidays($state);
                    }
                    //$str .= "<h3>{$test2["public"]} $rostered_start_time_date, $rostered_finish_time_date</h3>";

                    $test2 = $this->get_hours_breakdown($rostered_start_time_date, $rostered_finish_time_date);
                    $hours_rostered = $test2["weekday"] + $test2["weeknight"] + $test2["saturday"] + $test2["sunday"] + $test2["public"];

                    $test = $this->get_hours_breakdown($start_time_date, $finish_time_date);
                    //prd($test);
                    $hours_worked = $test["weekday"] + $test["weeknight"] + $test["saturday"] + $test["sunday"] + $test["public"];

                    $mid_point = (strtotime($rostered_start_time_date) + strtotime($rostered_finish_time_date)) / 2;
                    $start_unpaid = date("Y-m-d H:i:s", $mid_point);
                    $finish_unpaid = date("Y-m-d H:i:s", strtotime("+" . $minutes_unpaid . " minutes", $mid_point));

                    $test_unpaid = $this->get_hours_breakdown($start_unpaid, $finish_unpaid);
                    $unpaid_hours = ($test["weekday"] ? $test_unpaid["weekday"] : 0) + ($test["weeknight"] ? $test_unpaid["weeknight"] : 0) + ($test["saturday"] ? $test_unpaid["saturday"] : 0) + ($test["sunday"] ? $test_unpaid["sunday"] : 0) + ($test["public"] ? $test_unpaid["public"] : 0);
                    //$str .= "<h3>$unpaid_hours</h3>";

                    $pay = ($weekdays * $test["weekday"]) + ($weekdays_nights * $test["weeknight"]) + ($saturdays * $test["saturday"]) + ($sundays * $test["sunday"]) + ($public_holidays * $test["public"]);
                    $pay -= ($test["weekday"] ? ($weekdays * $test_unpaid["weekday"]) : 0) + ($test["weeknight"] ? ($weekdays_nights * $test_unpaid["weeknight"]) : 0) + ($test["saturday"] ? ($saturdays * $test_unpaid["saturday"]) : 0) + ($test["sunday"] ? ($sundays * $test_unpaid["sunday"]) : 0) + ($test["public"] ? ($public_holidays * $test_unpaid["public"]) : 0);

                    $site_id_tmp = ($separate_sites ? $site_id : "");

                    $staff_names["$staff_id$site_id_tmp"] = $staff;
                    $staff_empids["$staff_id$site_id_tmp"] = $employee_id;
                    if ($separate_sites)
                        $sites["$staff_id$site_id_tmp"] = $site;
                    $staff_hours_rostered["$staff_id$site_id_tmp"] += $hours_rostered;
                    $staff_hours_worked["$staff_id$site_id_tmp"] += $hours_worked - $unpaid_hours;
                    $staff_pays["$staff_id$site_id_tmp"] += $pay;

                    $weekday_hours["$staff_id$site_id_tmp"] += $test["weekday"] - ($test["weekday"] ? $test_unpaid["weekday"] : 0);
                    $weekday_pay["$staff_id$site_id_tmp"] += ($weekdays * $test["weekday"]) - ($weekdays * ($test["weekday"] ? $test_unpaid["weekday"] : 0));

                    $weeknight_hours["$staff_id$site_id_tmp"] += $test["weeknight"] - ($test["weeknight"] ? $test_unpaid["weeknight"] : 0);
                    $weeknight_pay["$staff_id$site_id_tmp"] += ($weekdays_nights * $test["weeknight"]) - ($weekdays_nights * ($test["weeknight"] ? $test_unpaid["weeknight"] : 0));

                    $saturday_hours["$staff_id$site_id_tmp"] += $test["saturday"] - ($test["saturday"] ? $test_unpaid["saturday"] : 0);
                    $saturday_pay["$staff_id$site_id_tmp"] += ($saturdays * $test["saturday"]) - ($saturdays * ($test["saturday"] ? $test_unpaid["saturday"] : 0));

                    $sunday_hours["$staff_id$site_id_tmp"] += $test["sunday"] - ($test["sunday"] ? $test_unpaid["sunday"] : 0);
                    $sunday_pay["$staff_id$site_id_tmp"] += ($sundays * $test["sunday"]) - ($sundays * ($test["sunday"] ? $test_unpaid["sunday"] : 0));

                    $public_holiday_hours["$staff_id$site_id_tmp"] += $test["public"] - ($test["public"] ? $test_unpaid["public"] : 0);
                    $public_holiday_pay["$staff_id$site_id_tmp"] += ($public_holidays * $test["public"]) - ($public_holidays * ($test["public"] ? $test_unpaid["public"] : 0));

                    $total_hours_rostered += $hours_rostered;
                    $total_hours += $hours_worked;

                    $old_site_id = $site_id;
                    $old_state = $state;

                    $reportExcel[$exlKey]['Employee_ID'] = $myrow['employee_id'];
                    $reportExcel[$exlKey]['Employee_Name'] = $myrow['Staff'];
                    $reportExcel[$exlKey]['providername'] = $myrow['providername'];
                    $reportExcel[$exlKey]['Location'] = $myrow['Site'];
                    $reportExcel[$exlKey]['Client'] = $myrow['Client'];
                    $reportExcel[$exlKey]['Location_State'] = $myrow['location_state'];

                    $rostered_start_date_time = explode(" ", $myrow['rostered_start_time_date']);

                    $rostered_start_date = $rostered_start_date_time[0];
                    $rostered_start_time = $rostered_start_date_time[1];
                    $reportExcel[$exlKey]['Roster_Start_Date'] = $rostered_start_date;
                    $reportExcel[$exlKey]['Roster_Start_Time'] = $rostered_start_time;

                    $rostered_finish_time_date = explode(" ", $myrow['rostered_finish_time_date']);
                    $rostered_finish_date = $rostered_finish_time_date[0];
                    $rostered_finish_time = $rostered_finish_time_date[1];
                    $reportExcel[$exlKey]['Roster_End_Date'] = $rostered_finish_date;
                    $reportExcel[$exlKey]['Roster_End_Time'] = $rostered_finish_time;

                    $reportExcel[$exlKey]['Scheduled_Hours'] = $hours_rostered;
                    $reportExcel[$exlKey]['Leave_Type'] = $myrow['leave_title'];

                    // $reportExcel[$exlKey]['Actual_Start_Date_Time'] = $myrow['start_time_date'];                    
                    $Actual_time_date = explode(" ", $myrow['start_time_date']);
                    $Actual_date = $Actual_time_date[0];
                    $Actual_time = $Actual_time_date[1];
                    $reportExcel[$exlKey]['Actual_Start_Date'] = $Actual_date;
                    $reportExcel[$exlKey]['Actual_Start_Time'] = $Actual_time;

                    //$reportExcel[$exlKey]['Actual_End_Date_Time'] = $myrow['finish_time_date'];                    
                    $finish_time_date = explode(" ", $myrow['finish_time_date']);
                    $finish_date = $finish_time_date[0];
                    $finish_time = $finish_time_date[1];
                    $reportExcel[$exlKey]['Actual_End_Date'] = $finish_date;
                    $reportExcel[$exlKey]['Actual_End_Time'] = $finish_time;

                    $reportExcel[$exlKey]['Actual_Hours'] = $hours_worked;

                    $reportExcel[$exlKey]['Staff_Comment'] = $myrow['staff_comment'];
                    $reportExcel[$exlKey]['Controller_Comment'] = $myrow['controller_comment'];

                    $reportExcel[$exlKey]['Minutes_Unpaid'] = $myrow['minutes_unpaid'];

                    $reportExcel[$exlKey]['Variation'] = $reportExcel[$exlKey]['Actual_Hours'] - $reportExcel[$exlKey]['Scheduled_Hours'];

                    $exlKey++;
                }
                /// prd($reportExcel);
            }

            foreach ($staff_hours_rostered as $key => $val) {
                if ($download_xl) {
                    $objPHPExcel->getActiveSheet()->SetCellValue("A$row_count", $staff_empids[$key]);
                    $objPHPExcel->getActiveSheet()->SetCellValue("B$row_count", $staff_names[$key]);
                    $objPHPExcel->getActiveSheet()->SetCellValue("C$row_count", $staff_hours_worked[$key]);
                    $objPHPExcel->getActiveSheet()->SetCellValue("D$row_count", round($staff_pays[$key], 2));

                    $row_count++;
                } else {
                    $str .= "<tr>
            <td>" . $staff_empids[$key] . "</td>
            <td>" . $staff_names[$key] . "</td>
            <td>$val</td>
            <td>" . ($weekday_hours[$key] ? $weekday_hours[$key] . " Hours Worked<br />\$" . round($weekday_pay[$key], 2) . " Paid<br />Rate: \$" . round($weekday_pay[$key] / $weekday_hours[$key], 2) : "-") . "</td>
            <td>" . ($weeknight_hours[$key] ? $weeknight_hours[$key] . " Hours Worked<br />\$" . round($weeknight_pay[$key], 2) . " Paid<br />Rate: \$" . round($weeknight_pay[$key] / $weeknight_hours[$key], 2) : "-") . "</td>
            <td>" . ($saturday_hours[$key] ? $saturday_hours[$key] . " Hours Worked<br />\$" . round($saturday_pay[$key], 2) . " Paid<br />Rate: \$" . round($saturday_pay[$key] / $saturday_hours[$key], 2) : "-") . "</td>
            <td>" . ($sunday_hours[$key] ? $sunday_hours[$key] . " Hours Worked<br />\$" . round($sunday_pay[$key], 2) . " Paid<br />Rate: \$" . round($sunday_pay[$key] / $sunday_hours[$key], 2) : "-") . "</td>
            <td>" . ($public_holiday_hours[$key] ? $public_holiday_hours[$key] . " Hours Worked<br />\$" . round($public_holiday_pay[$key], 2) . " Paid<br />Rate: \$" . round($public_holiday_pay[$key] / $public_holiday_hours[$key], 2) : "-") . "</td>
            <td>" . $staff_hours_worked[$key] . "</td>
            " . ($separate_sites ? "<td>{$sites[$key]}</td>" : "") . "
            <td>\$" . round($staff_pays[$key], 2) . " </td></tr>";
                    $staff_total_hours += $val;
                    $staff_total_worked += $staff_hours_rostered[$key];
                }
            }
            if ($download_xl) {


//          for ($i = 1; $i != $columnLength; $i++) {
//            $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->setAutoSize(true);
//          }
//          $objPHPExcel->getActiveSheet()->calculateColumnWidths();
//          for ($i = 1; $i != $columnLength; $i++) {
//            $curr_width = $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->getWidth();
//            $objPHPExcel->getActiveSheet()->getColumnDimension($column_array[$i])->setAutoSize(false)->setWidth($curr_width * 0.86);
//          }
//          
//          if($i % 2) $this->bgCellColour($objPHPExcel, "A1:{$column_array[$columnLength-1]}1", "DDDDDA");
//          for($i = 2; $i < $row_count; $i++) {
//            if($i % 2) $this->bgCellColour($objPHPExcel, "A$i:{$column_array[$columnLength-1]}$i", "EEEEEE");
//          }
//          $objPHPExcel->getActiveSheet()->getStyle("A1:" . $column_array[$columnLength-1] . ($row_count-1))->applyFromArray(array(
//            'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'AAAAAA')))
//          ));
//
//          header('Content-type: application/vnd.ms-excel');
//          header('Content-Disposition: attachment; filename="' . $fileName . '"');
//          $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
//          $objWriter->save('php://output');

                $date_by = substr(str_replace("/", "-", $date_by), 5);
                $fileName = "timesheets_$date_by.csv";
                // prd($reportExcel);
                // $filename = 'users_' . date('Ymd') . '-' . time() . '.csv';
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment; filename=$filename");
                header("Content-Type: application/csv;");

                // prd($user_data);
                // file creation 
                $file = fopen('php://output', 'w');
                //prd($reportExcel);
                foreach ($reportExcel as $key => $line) {
                    if ($key == 0) {
                        foreach ($line as $keyh => $keyhv) {
                            $headerArray[] = $keyh; //str_replace('_',' ', $keyh);
                        }
                        // $header = implode(',',$headerArray);
                        // prd($header);
                        fputcsv($file, $headerArray);
                    }
                }

                //echo "test";
                // exit;
//                $header = array("Date", "Height", "Weight", "Blood Sugar Fasting", "Blood Sugar Postprandial", "Blood Pressure Diastolic", "Blood Pressure Systolic");
//
//                fputcsv($file, $header);

                foreach ($reportExcel as $key => $line) {

                    fputcsv($file, $line);
                }
                fclose($file);

                exit;
            }

            //if($site_id) $str .= "<tr><td>&nbsp;</td><td>$total_hours_rostered</td><td>$total_hours</td></tr>";
            $str .= '</table>';
            $str .= '<div class="cl"></div>';
        }

        $str .= '<div class="cl"></div>';
        return $str;
    }

    function ClientCharges() {


        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);
        $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);
        $itm = new input_item;
        $edit_types = (isset($_GET['edit_types']) ? $_GET['edit_types'] : null);

        $division_id = $this->division_id;

        $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

        if ($edit_types) {
            $e = new EditController($this->f3);
            return $e->LookupItems(132, "Charge Types", "", 1);
        }


        $str .= $this->division_nav($division_id, 'Rostering/ClientCharges', 0, 0, 1);

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('client_charges.xlsx');
        } else {
            $filter_string = ($hdnFilter ? "filter_string" : "");
            $this->list_obj->show_num_records = 0;
            //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "
            select distinct(client_charges.id) as idin
            , CONCAT(users.name, ' ', users.surname) as `Client`
            , CONCAT(users2.name, ' ', users2.surname) as `Site`
            , lookup_fields.item_name as `Service`
            , CONCAT('\$', client_charges.weekdays) as `Weekdays`
            , CONCAT('\$', client_charges.weekdays_nights) as `Weekday Nights`
            , CONCAT('\$', client_charges.saturdays) as `Saturdays`
            , CONCAT('\$', client_charges.sundays) as `Sundays`
            , CONCAT('\$', client_charges.public_holidays) as `Public Holidays`
            , 'Edit' as `*`, 'Delete' as `!`
            FROM client_charges
            left join lookup_fields on lookup_fields.id = client_charges.service_id
            left join companies on companies.id = client_charges.division_id
            left join users on users.id = client_charges.client_id
            left join users2 on users2.id = client_charges.site_id
            where client_charges.division_id = $division_id
            $filter_string
            order by users.name, users2.name
        ";

            //$str .= $this->ta($this->list_obj->sql);
            //

            $str .= '<div class="form-wrapper"><div class="form-header"><h3 class="fl">Client Charges</h3><div class="fl"> &nbsp; &nbsp; Please select either a client OR a site, or neither for a default rate.</div><div class="cl"></div></div>';
            $this->editor_obj->table = "client_charges";
            $style = 'class="full_width"';

            $this->editor_obj->xtra_id_name = "division_id";
            $this->editor_obj->xtra_id = $division_id;

            $client_sql = $this->user_dropdown(104);
            $site_sql = $this->user_dropdown(384);
            $this->editor_obj->form_attributes = array(
                array("cmbClient", "cmbSite", "selService", "txtWeekdays", "txtWeekdayNights", "txtSaturdays", "txtSundays", "txtPublicHolidays"),
                array("Client", "Site", "Service", "Weekdays", "Weekday Nights", "Saturdays", "Sundays", "Public Holidays"),
                array("client_id", "site_id", "service_id", "weekdays", "weekdays_nights", "saturdays", "sundays", "public_holidays"),
                array($client_sql, $site_sql, $this->get_lookup('service_type')),
                array($style, $style, $style, $style . 'onChange=calc_rates()', $style, $style, $style, $style),
                array("n", "n", "c", "c", "c", "c", "c", "c")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );

//[<a tabindex="-1" target="_blank" href="'.$this->f3->get('main_folder').'Assets/Manager?edit_categories=1">Edit</a>]

            $this->editor_obj->form_template = '
                <div class="fl med_textbox"><nobr>tcmbClient</nobr><br />cmbClient</div>
                <div class="fl med_textbox"><nobr>tcmbSite</nobr><br />cmbSite</div>
                <div class="fl small_textbox"><nobr>tselService [<a tabindex="-1" target="_blank" href="' . $this->f3->get('main_folder') . 'Rostering/ClientCharges?edit_types=1">Edit</a>]</nobr><br />selService</div>
                <div class="fl small_textbox"><nobr>ttxtWeekdays</nobr><br />txtWeekdays</div>
                <div class="fl small_textbox"><nobr>ttxtWeekdayNights</nobr><br />txtWeekdayNights</div>
                <div class="fl small_textbox"><nobr>ttxtSaturdays</nobr><br />txtSaturdays</div>
                <div class="fl small_textbox"><nobr>ttxtSundays</nobr><br />txtSundays</div>
                <div class="fl small_textbox"><nobr>ttxtPublicHolidays</nobr><br />txtPublicHolidays</div>
                <div class="fl"><br /> &nbsp; &nbsp;<input type="button" onClick="fixed_rates()" value="Fixed"><input type="button" onClick="calc_rates()" value="Calculated"></div>

                <div class="cl"></div>
                <br />
                ' . $this->editor_obj->button_list();

            $this->editor_obj->editor_template = '
        <div class="form-content">
        editor_form
        </div>
        </div>
        editor_list
      ';
            $str .= '
        <script>
          function calc_rates() {
            var res = document.getElementById("txtWeekdays").value
            if(res) {
              if(!isNaN(res)) {
                document.getElementById("txtWeekdayNights").value = Math.round(res * 1.2 * 100) / 100
                document.getElementById("txtSaturdays").value = Math.round(res * 1.5 * 100) / 100
                document.getElementById("txtSundays").value = Math.round(res * 2 * 100) / 100
                document.getElementById("txtPublicHolidays").value = Math.round(res * 2.5 * 100) / 100
              }
            }
          }
          function fixed_rates() {
            var res = document.getElementById("txtWeekdays").value
            if(res) {
              if(!isNaN(res)) {
                document.getElementById("txtWeekdayNights").value = res
                document.getElementById("txtSaturdays").value = res
                document.getElementById("txtSundays").value = res
                document.getElementById("txtPublicHolidays").value = res
              }
            }
          }
        </script>
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);

            return $str;
        }
    }

    function Payrates() {


        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);
        $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);
        $itm = new input_item;

        $division_id = $this->division_id;

        $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

        $str .= $this->division_nav($division_id, 'Rostering/Payrates', 0, 0, 1);

//    $str .= $this->get_times();

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('payrates.xlsx');
        } else {
            $filter_string = ($hdnFilter ? "filter_string" : "");
            //$this->list_obj->title = "staff_payrates";
            $this->list_obj->show_num_records = 0;
            //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "
            select distinct(staff_payrates.id) as idin
            , CONCAT(users.name, ' ', users.surname) as `Site`
            , users2.employee_id as `Emp ID`
            , CONCAT(users2.name, ' ', users2.surname) as `Staff Member`
            , CONCAT('\$', staff_payrates.weekdays) as `Weekdays`
            , CONCAT('\$', staff_payrates.weekdays_nights) as `Weekday Nights`
            , CONCAT('\$', staff_payrates.saturdays) as `Saturdays`
            , CONCAT('\$', staff_payrates.sundays) as `Sundays`
            , CONCAT('\$', staff_payrates.public_holidays) as `Public Holidays`
            , 'Edit' as `*`, 'Delete' as `!`
            FROM staff_payrates
            left join companies on companies.id = staff_payrates.division_id
            left join users on users.id = staff_payrates.site_id
            left join users2 on users2.id = staff_payrates.staff_id
            where staff_payrates.division_id = $division_id
            $filter_string
            order by users.name, users2.name
        ";

            //
            //return $this->ta($this->list_obj->sql);
            //$this->editor_obj->add_now = "updated_date";
            //$this->editor_obj->update_now = "updated_date";
            $str .= '<div class="form-wrapper"><div class="form-header"><h3>Payrates</h3></div>';
            //$this->editor_obj->custom_field = "staff_id";
            //$this->editor_obj->custom_value = $_SESSION['user_id'];
            $this->editor_obj->table = "staff_payrates";
            $style = 'class="full_width"';

            $this->editor_obj->xtra_id_name = "division_id";
            $this->editor_obj->xtra_id = $division_id;

            $site_sql = $this->user_dropdown(384);
            $staff_sql = $this->user_dropdown(107);

            $this->editor_obj->form_attributes = array(
                array("cmbSite", "cmbStaff", "txtWeekdays", "txtWeekdayNights", "txtSaturdays", "txtSundays", "txtPublicHolidays"),
                array("Site (Optional)", "Staff Member", "Weekdays", "Weekday Nights", "Saturdays", "Sundays", "Public Holidays"),
                array("site_id", "staff_id", "weekdays", "weekdays_nights", "saturdays", "sundays", "public_holidays"),
                array($site_sql, $staff_sql),
                array($style, $style, $style . 'onChange=calc_rates()', $style, $style, $style, $style),
                array("n", "c", "c", "c", "c", "c", "c")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );

            $this->editor_obj->form_template = '
                <div class="fl med_textbox"><nobr>tcmbSite</nobr><br />cmbSite</div>
                <div class="fl med_textbox"><nobr>tcmbStaff</nobr><br />cmbStaff</div>

                <div class="fl small_textbox"><nobr>ttxtWeekdays</nobr><br />txtWeekdays</div>
                <div class="fl small_textbox"><nobr>ttxtWeekdayNights</nobr><br />txtWeekdayNights</div>
                <div class="fl small_textbox"><nobr>ttxtSaturdays</nobr><br />txtSaturdays</div>
                <div class="fl small_textbox"><nobr>ttxtSundays</nobr><br />txtSundays</div>
                <div class="fl small_textbox"><nobr>ttxtPublicHolidays</nobr><br />txtPublicHolidays</div>
                <div class="fl"><br /> &nbsp; &nbsp;<input type="button" onClick="fixed_rates()" value="Fixed Rates"><input type="button" onClick="calc_rates()" value="Calculated Rates"></div>

                <div class="cl"></div>
                <br />
                ' . $this->editor_obj->button_list();

            $this->editor_obj->editor_template = '
        <div class="form-content">
        editor_form
        </div>
        </div>
        editor_list
      ';
            $str .= '
        <script>
          function calc_rates() {
            var res = document.getElementById("txtWeekdays").value
            if(res) {
              if(!isNaN(res)) {
                document.getElementById("txtWeekdayNights").value = Math.round(res * 1.2 * 100) / 100
                document.getElementById("txtSaturdays").value = Math.round(res * 1.5 * 100) / 100
                document.getElementById("txtSundays").value = Math.round(res * 2 * 100) / 100
                document.getElementById("txtPublicHolidays").value = Math.round(res * 2.5 * 100) / 100
              }
            }
          }
          function fixed_rates() {
            var res = document.getElementById("txtWeekdays").value
            if(res) {
              if(!isNaN(res)) {
                document.getElementById("txtWeekdayNights").value = res
                document.getElementById("txtSaturdays").value = res
                document.getElementById("txtSundays").value = res
                document.getElementById("txtPublicHolidays").value = res
              }
            }
          }
        </script>
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);

            return $str;
        }
    }

    function Awards() {


        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
        $show_times = (isset($_GET['show_times']) ? $_GET['show_times'] : null);
        $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);
        $itm = new input_item;

        $division_id = $this->division_id;

        $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

        $str .= $this->division_nav($division_id, 'Rostering/Awards', 0, 0, 1);

//    $str .= $this->get_times();

        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;
            $xl_obj->sql = "";
            $xl_obj->sql_xl('awards.xlsx');
        } else {
            $filter_string = ($hdnFilter ? "filter_string" : "");
            $this->list_obj->show_num_records = 0;
            //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "
            select distinct(award_payrates.id) as idin
            , lookup_fields1.item_name as `Worker Level`
            , lookup_fields2.item_name as `Worker Classification`
            , if(lookup_fields3.item_name != '', lookup_fields3.item_name, 'Any') as `Workday`
            , lookup_fields4.item_name as `Work Period`
            , CONCAT('\$', award_payrates.payrate) as `Payrate`
            , 'Edit' as `*`, 'Delete' as `!`

            FROM award_payrates

            left join lookup_fields1 on lookup_fields1.id = award_payrates.worker_level_id
            left join lookup_fields2 on lookup_fields2.id = award_payrates.worker_classification_id
            left join lookup_fields3 on lookup_fields3.id = award_payrates.workday_classification_id
            left join lookup_fields4 on lookup_fields4.id = award_payrates.work_period_id
            left join companies on companies.id = award_payrates.division_id
            where award_payrates.division_id = $division_id
            $filter_string
        ";
            //return $this->ta($this->list_obj->sql);
            //$this->editor_obj->add_now = "updated_date";
            //$this->editor_obj->update_now = "updated_date";
            $str .= '<div class="form-wrapper"><div class="form-header"><h3>Award Payrates</h3></div>';
            //$this->editor_obj->custom_field = "staff_id";
            //$this->editor_obj->custom_value = $_SESSION['user_id'];
            $this->editor_obj->table = "award_payrates";
            $style = 'class="full_width"';

            $this->editor_obj->xtra_id_name = "division_id";
            $this->editor_obj->xtra_id = $division_id;
            $this->editor_obj->form_attributes = array(
                array("selLevel", "selWorkerClassification", "selWorkdayClassification", "selWorkPeriod", "txtPayrate"),
                array("Worker Level", "Worker Classification", "Workday", "Work Period", "Payrate"),
                array("worker_level_id", "worker_classification_id", "workday_classification_id", "work_period_id", "payrate"),
                array($this->get_lookup('security_officer_level'), $this->get_lookup('worker_classification'), $this->get_lookup('workday_classification'), $this->get_lookup('work_period')),
                array($style, $style, $style, $style),
                array("c", "c", "n", "c", "c")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );
            $this->editor_obj->form_template = '
                <div class="fl med_textbox"><nobr>tselLevel</nobr><br />selLevel</div>
                <div class="fl med_textbox"><nobr>tselWorkerClassification</nobr><br />selWorkerClassification</div>
                <div class="fl med_textbox"><nobr>tselWorkdayClassification</nobr><br />selWorkdayClassification</div>
                <div class="fl med_textbox"><nobr>tselWorkPeriod</nobr><br />selWorkPeriod</div>
                <div class="fl med_textbox"><nobr>ttxtPayrate</nobr><br />txtPayrate</div>
                <div class="cl"></div>
                <br />
                ' . $this->editor_obj->button_list();

            $this->editor_obj->editor_template = '
        <div class="form-content">
        editor_form
        </div>
        </div>
        editor_list
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);

            return $str;
        }
    }

    function tester() {
        /* $c = new UserController();
          $sql = "select users.id, users.username, users.name, users.surname, users.email, pw
          from associations
          left join users on users.id = associations.child_user_id
          where associations.parent_user_id = 169
          and users.user_status_id = 30";

          $str .= '<table class="grid">
          <tr><th>Name</th><th>Surname</th><th>Email</th><th>Username</th><th>Password</th></tr>';

          if($result = $this->dbi->query($sql)) {
          while($myrow = $result->fetch_assoc()) {
          $uid = $myrow['id'];
          $username = $myrow['username'];
          $name = $myrow['name'];
          $surname = $myrow['surname'];
          $email = $myrow['email'];
          $pw = $myrow['pw'];

          $pwc = $c->ed_crypt($username, $uid);

          $pwc = ($pw ? ($pwc == $pw ? $username : "Set By Staff Member") : "No Password Set");

          $str .= "<tr><td>$name</td><td>$surname</td><td>$email</td><td>$username</td><td>$pwc</td></tr>";
          }
          }
          $str .= "</table>";


          return $str; */
    }

    function get_public_holidays($state_id) {
        $sql = "select date from public_holidays where state_id = $state_id order by date";
        if ($result = $this->dbi->query($sql)) {
            while ($myrow = $result->fetch_assoc()) {
                $this->public_holidays[] = $myrow['date'];
            }
        }
    }

    function PublicHolidays() {


        if ($_SESSION['page_access'] == 4) {

            $str .= '<h3><div class="fl">Upload Excel</div><div class="fr"><!-- <a class="list_a" href="?download_xl=1">Download Excel</a>--></div><div class="cl"></div></h3>';
            $str .= '
          <ol>
          <li>The first column must contain the date</li>
          <li>The second column must contain the name of the holiday</li>
          <li>The third column must contain the state</li>
          </ol>
        </form>
        <form method="post" action="' . $this->f3->get('main_folder') . 'Rostering/PublicHolidays" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
        <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
        </form>';

            if ($_FILES["thefile"]["error"] > 0) {
                $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
            } else if ($_FILES["thefile"]["name"]) {
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
                $this->dbi->query("truncate public_holidays;");
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                    $sql = "insert into public_holidays (date, title, state_id) values (";
                    $cnt = 0;
                    foreach ($rowData[0] as $k => $v) {
                        if (!$k && !$v)
                            break;
                        $v = mysqli_real_escape_string($this->dbi, trim($v));
                        if ($cnt == 0)
                            $v = substr($v, 0, 4) . "-" . substr($v, 4, 2) . "-" . substr($v, 6, 2);
                        if ($cnt == 2) {
                            $sql .= ", COALESCE((select id from states where item_name = '$v'), 0)";
                        } else {
                            $sql .= ($cnt ? ", " : "") . "'$v'";
                        }
                        $cnt++;
                    }
                    $sql .= ");";
                    //$str .= "<h3>$sql</h3>";
                    $this->dbi->query($sql);
                }
                if ($row > 2) {
                    $str .= "<h3>Holidays Updated...</h3>";
                }
            }
        }
        $this->list_obj->show_num_records = 0;

        $this->list_obj->title = "Future Public Holidays";
        $str .= '<div style="margin-top: 25px;">&nbsp; </div>';

        $this->list_obj->sql = "select public_holidays.title as `Holiday`, public_holidays.date as `Date`, states.item_name as `State` from public_holidays
      left join states on states.id = public_holidays.state_id
      where date >= now()
      order by date ";
        $str .= '<div class="fl" style="margin-right: 100px;">' . $this->list_obj->draw_list() . '</div>';

        $this->list_obj->title = "Past Public Holidays";
        $this->list_obj->sql = "select public_holidays.title as `Holiday`, public_holidays.date as `Date`, states.item_name as `State` from public_holidays
      left join states on states.id = public_holidays.state_id
      where date <= now()
      order by date ";
        $str .= '<div class="fl">' . $this->list_obj->draw_list() . '</div>';

        return $str;
    }

    function get_hours_breakdown($time1, $time2) {


        $hoursForShift = array("weekday" => 0, "weeknight" => 0, "saturday" => 0, "sunday" => 0, "public" => 0);
        $t1 = strtotime($time1);
        $t2 = strtotime($time2);
        $t1_Day = (int) date("N", $t1);
        $t2_Day = (int) date("N", $t2);
        $t1_Hour = (int) date("G", $t1);
        $t2_Hour = (int) date("G", $t2);
        $t1_Date = (int) date("j", $t1);
        $t2_Date = (int) date("j", $t2);
        $t1_Month = (int) date("n", $t1);
        $t2_Month = (int) date("n", $t2);
        $t1_Year = (int) date("Y", $t1);
        $t2_Year = (int) date("Y", $t2);
        $t1_Minutes = round(date("i", $t1) / 60, 2); // to substitute, extra
        $t2_Minutes = round(date("i", $t2) / 60, 2); // to add, lost
        $start = explode(' ', $time1);
        $finish = explode(' ', $time2);

        // Working days
        if ($t1_Day < 6) {
            if ($t1_Hour > $t2_Hour)
                $t2_Hour += 24;
            for ($i = $t1_Hour; $i < $t2_Hour; $i++) {
                if (($i >= 6 && $i < 18) || ($t2_Day < 6 && $i >= 30 && $i < 42)) {
                    $hoursForShift['weekday']++;
                } else if ($t2_Day < 6 || $i < 24) {
                    $hoursForShift['weeknight']++;
                } else if ($t2_Day == 6) {
                    $hoursForShift['saturday']++;
                } else if ($t2_Day == 7) {
                    $hoursForShift['sunday']++;
                }
            }

            $t2_Hour = ($t2_Hour > 23) ? $t2_Hour - 24 : $t2_Hour;

            // Deducting extra minutes from sign in time
            if ((!in_array($start[0], $this->public_holidays)) && (!in_array($finish[0], $this->public_holidays))) {
                if ($t1_Hour >= 6 && $t1_Hour < 18)
                    $hoursForShift['weekday'] -= $t1_Minutes;
                else if ($t1_Hour < 6 || $t1_Hour >= 18)
                    $hoursForShift['weeknight'] -= $t1_Minutes;

                // Adding lost minutes to sign out
                if ($t2_Day < 6 && ($t2_Hour >= 6 && $t2_Hour < 18)) {
                    $hoursForShift['weekday'] += $t2_Minutes;
                } else if ($t2_Day < 6 && ($t2_Hour < 6 || $t2_Hour >= 18))
                    $hoursForShift['weeknight'] += $t2_Minutes;

                // Adding lost minutes to sign out if, sign out on weekend
                if ($t2_Day == 6)
                    $hoursForShift['saturday'] += $t2_Minutes;
                else if ($t2_Day == 7)
                    $hoursForShift['sunday'] += $t2_Minutes;
            }
        } else if ($t1_Day >= 6) {
            //Weekends

            if ($t1_Hour > $t2_Hour)
                $t2_Hour += 24;

            for ($i = $t1_Hour; $i < $t2_Hour; $i++) {
                if ($t2_Day == 1 && $i >= 24 && $i < 30) {
                    $hoursForShift['weeknight']++;
                } else if ($t2_Day == 1 && $i > 29 && $i < 42) {
                    $hoursForShift['weekday']++;
                } else if ($t1_Day == 6 && $i < 24) {
                    $hoursForShift['saturday']++;
                } else if ($t1_Day == 7 || ($t1_Day == 6 && $i >= 24)) {
                    $hoursForShift['sunday']++;
                }
            }


            if ((!in_array($start[0], $this->public_holidays)) && (!in_array($finish[0], $this->public_holidays))) {
                $t2_Hour = ($t2_Hour > 23) ? $t2_Hour - 24 : $t2_Hour;

                if ($t1_Day == 1 && $t1_Hour >= 0 && $t1_Hour < 6)
                    $hoursForShift['weeknight'] -= $t1_Minutes;
                else if ($t1_Day == 1 && $t1_Hour >= 6 && $t1_Hour < 18)
                    $hoursForShift['weekday'] -= $t1_Minutes;
                else if ($t1_Day == 6)
                    $hoursForShift['saturday'] -= $t1_Minutes;
                else if ($t1_Day == 7)
                    $hoursForShift['sunday'] -= $t1_Minutes;

                if ($t2_Day == 1 && $t2_Hour < 6 || $t2_Day == 1 && $t2_Hour >= 18)
                    $hoursForShift['weeknight'] += $t2_Minutes;
                else if ($t2_Day == 1 && $t2_Hour >= 6 && $t2_Hour < 18)
                    $hoursForShift['weekday'] += $t2_Minutes;
                else if ($t2_Day == 6)
                    $hoursForShift['saturday'] += $t2_Minutes;
                else if ($t2_Day == 7)
                    $hoursForShift['sunday'] += $t2_Minutes;
            }
        }
        // If the start and end date is public holiday
        if ((in_array($start[0], $this->public_holidays)) && (in_array($finish[0], $this->public_holidays))) {
            $hoursForShift['public'] = $hoursForShift['weekday'];
            $hoursForShift['weekday'] = 0;
            $hoursForShift['public'] += $hoursForShift['weeknight'];
            $hoursForShift['weeknight'] = 0;
            $hoursForShift['public'] += $hoursForShift['saturday'];
            $hoursForShift['saturday'] = 0;
            $hoursForShift['public'] += $hoursForShift['sunday'];
            $hoursForShift['sunday'] = 0;
            $hoursForShift['public'] -= $t1_Minutes;
            $hoursForShift['public'] += $t2_Minutes;

            //If start date is on public holiday but the end date
        } else if ((in_array($start[0], $this->public_holidays)) && (!in_array($finish[0], $this->public_holidays))) {
            if ($t1_Hour > $t2_Hour)
                $t2_Hour += 24;
            for ($i = $t1_Hour; $i < 24; $i++) {
                if ($t1_Day < 6) {
                    if (($i >= 6 && $i < 18)) {
                        $hoursForShift['weekday']--;
                        $hoursForShift['public']++;
                    } else if ($i >= 18 && $i < 24) {
                        $hoursForShift['weeknight']--;
                        $hoursForShift['public']++;
                    } else if ($i > 0 && $i <= 6) {
                        $hoursForShift['weeknight']--;
                        $hoursForShift['public']++;
                    }
                } else if ($t1_Day == 6) {
                    $hoursForShift['saturday']--;
                    $hoursForShift['public']++;
                } else if ($t1_Day == 7) {
                    $hoursForShift['sunday']--;
                    $hoursForShift['public']++;
                }
            }
            $t2_Hour = ($t2_Hour > 23) ? $t2_Hour - 24 : $t2_Hour;

            $hoursForShift['public'] -= $t1_Minutes;

            if ($t2_Day < 6 && $t2_Hour > 0 && $t2_Hour < 6 || $t2_Day < 6 && $t2_Hour >= 18)
                $hoursForShift['weeknight'] += $t2_Minutes;
            else if ($t2_Day < 6 && $t2_Hour >= 6 && $t2_Hour < 18)
                $hoursForShift['weekday'] += $t2_Minutes;
            else if ($t2_Day == 6)
                $hoursForShift['saturday'] += $t2_Minutes;
            else if ($t2_Day == 7)
                $hoursForShift['sunday'] += $t2_Minutes;

            //If the start date is not on a public holiday but end date is.
        } else if ((!in_array($start[0], $this->public_holidays)) && (in_array($finish[0], $this->public_holidays))) {
            if ($t1_Hour > $t2_Hour)
                $t2_Hour += 24;
            for ($i = 25; $i <= $t2_Hour; $i++) {
                if ($t2_Day < 6) {
                    if ($i > 24 && $i <= 30) {
                        $hoursForShift['weeknight']--;
                        $hoursForShift['public']++;
                    } else if ($i >= 30 && $i < 42) {
                        $hoursForShift['weekday']--;
                        $hoursForShift['public']++;
                    }
                } else if ($t2_Day == 6) {
                    $hoursForShift['saturday']--;
                    $hoursForShift['public']++;
                } else if ($t2_Day == 7) {
                    $hoursForShift['sunday']--;
                    $hoursForShift['public']++;
                }
            }
            $hoursForShift['public'] += $t2_Minutes;
            if ($t1_Day < 6 && $t1_Hour > 0 && $t1_Hour < 6 || $t1_Day < 6 && $t1_Hour >= 18)
                $hoursForShift['weeknight'] -= $t1_Minutes;
            else if ($t1_Day < 6 && $t1_Hour >= 6 && $t1_Hour < 18)
                $hoursForShift['weekday'] -= $t1_Minutes;
            else if ($t1_Day == 6)
                $hoursForShift['saturday'] -= $t1_Minutes;
            else if ($t1_Day == 7)
                $hoursForShift['sunday'] -= $t1_Minutes;
        }

        if ($time2 === '0000-00-00 00:00:00') {
            foreach ($hoursForShift as $keyh => $valueh) {
                $hoursForShift[$keyh] = 0;
            }
        }


        return $hoursForShift;
    }

    function UserAnalysis() {

        return true;
        die;
        $sms = new sms($this->dbi);

        $mail = new email_q($this->dbi);
        $mail->clear_all();

        $sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `name`, users.phone, users.phone2, users.email
               FROM users
               left join user_status on user_status.id = users.user_status_id
               inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
                and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
               order by users.name";
        if ($result = $this->dbi->query($sql)) {
            //$sms_message = "Please NOTE: Change of Address for Allied Security Management and Allied Facilities Management. New Address   Unit 19/55-61 Pine Road, Yennora 2161.";
            $sms_message = "To continue working with Allied, ALL Staff MUST read/sign safety (pg 3) and SOP (pg 28), found in the blue site compliance folders. Due Date: Wed, 20/Nov/2019";
            $hr_email_message = "";
            //$str .= "<h3>The SMS was Sent to:</h3>";
            //$str2 .= "<h3>The Email was Sent to:</h3>";

            $str .= "<h3>Phone Numbers:</h3>";
            $str2 .= "<h3>Email Addresses:</h3>";

            //$str .= "<p>$sms_message</p>";
            //$str .= "<h3>Sent To</h3>";
            $str .= '<table class="grid">';
            $str2 .= '<table class="grid">';
            $str .= "<tr><th>#</th><th>Phone</th><th>Staff Member</th></tr>";
            $str2 .= "<tr><th>#</th><th>Email</th><th>Staff Member</th></tr>";
            $count = 0;
            $count2 = 0;
            $subject = "Change of Address: Allied Security/Facilities Management";
            while ($myrow = $result->fetch_assoc()) {
                $phone = $myrow['phone'];
                $phone2 = $myrow['phone2'];
                $user_id = $myrow['idin'];
                $name = $myrow['name'];
                $email = $myrow['email'];
                if ($this->validate_email($email)) {
                    $count2++;
                    $email_message = "Hello $name,<br/><br/>This letter is to inform you that Allied Security Management and Allied Facilities have a <b>NEW address:</b><br /><br />Unit 19/55-61 Pine Road, Yennora 2161<br/><br/>Regards,<br/>Allied Management.";
                    $mail->clear_all();
                    $mail->AddAddress($email);
                    $mail->Subject = $subject;
                    $mail->Body = $email_message;
                    $mail->queue_message();
                    $mail->send();

                    $str2 .= "<tr><td>$count2</td><td>$email</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Email Address.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a> $name</td></tr>";
                } else {
                    $str2 .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>" . ($email ? "Invalid" : "Missing") . " Email Address" . ($email ? "<br/>$email" : "") . "</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Email Address.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a> $name<br/>" . $myrow['phone'] . " " . $myrow['phone2'] . "</td></tr>";
                }

                if ($phone = $sms->process_phones($phone, $phone2)) {
                    $count++;
                    //$sms->queue_message($phone, $sms_message);
                    $str .= "<tr><td>$count</td><td>" . $myrow['phone'] . " " . $myrow['phone2'] . "</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a> $name</td></tr>";
                } else {
                    $str .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>" . ($myrow['phone'] || $myrow['phone2'] ? "Invalid" : "Missing") . " Phone Number" . ($myrow['phone'] || $myrow['phone2'] ? '<br/>' . $myrow['phone'] . ' ' . $myrow['phone2'] : "") . "</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$user_id\">Card</a> $name</td></tr>";
                }
            }
            $str .= '</table>';
            $str2 .= '</table>';
            /* $mail->clear_all();
              $mail->AddAddress("edgar@alliedmanagement.com.au");
              $mail->AddAddress("compliance@alliedmanagement.com.au");
              $mail->Subject = $subject;
              $mail->Body    = $str . $str2;
              $mail->queue_message(); */
        }
        return "<table cellpadding=\"12\"><tr><td valign=\"top\">$str2</td><td valign=\"top\">$str</td></tr></table>";
    }

    function UploadPublicHolidays() {
        /*
          require_once('app/controllers/parsecsv.class.php');

          $states['ACT'] = 1;
          $states['NSW'] = 2;
          $states['NT'] = 3;
          $states['QLD'] = 4;
          $states['SA'] = 5;
          $states['TAS'] = 6;
          $states['VIC'] = 7;
          $states['WA'] = 8;

          $str .= '
          </form>
          <p><a class="list_a" href="ProcessPowerforce?exceptions_mode=1">Powerforce Exceptions</a></p>
          <form method="post" action="ProcessPowerforce" enctype="multipart/form-data">
          <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
          <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
          </form>
          ';

          if ($_FILES["thefile"]["error"] > 0) {
          $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
          } else {
          move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . $_FILES["thefile"]["name"]);
          $csv = new parseCSV($this->f3->get('upload_folder') . $_FILES["thefile"]["name"]);
          $row_count = 0;
          foreach($csv->data as $val) {
          $row_count++;
          $date = date(strtotime($val['Date'], 'Y-m-d');
          $title = $val['Holiday Name'];
          $state_id = $val['Jurisdiction'];

          }
          return $str;
          $str .= "</pre>";
          }
          return $str;

         */
    }

}

?>