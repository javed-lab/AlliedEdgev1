<?php

class RosterByDay extends Controller {

    protected $f3;
    protected $db;

    function __construct() {
        /* for Image 
          1= befoe work imaggelocation image; 6 = Final Location image
         * */
        $this->db_init();
    }

    /*
      All job list
     */

    function index() {
        $template = new Template;
        $view = new View;

        //Get sub job type
        $job_type_id = 1;
        $jobs_sub_type = $this->db->exec("SELECT id,title,color_code FROM jobs_sub_type WHERE job_sub_type_status=1 AND jobs_main_type_id=$job_type_id ORDER BY id ASC");
        $this->f3->set('jobs_sub_type', $jobs_sub_type);

        //Get jobs_category
        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
        $this->f3->set('jobs_category', $jobs_category);

        //Get job location list
        $job_location = $this->getLocationOfJobs();
        $this->f3->set('job_location', $job_location);

        //Get client list            
        $job_clients = $this->getClientsOfJobs();
        $this->f3->set('job_clients', $job_clients);

        $this->f3->set('Job_List', $job_list);
        $this->f3->set('page_title', 'Roster By Day');
        $this->f3->set('base_url', $this->f3->get('base_url'));
        echo $template->render('header.htm');
        //echo $template->render('nav.htm');
        echo $view->render('rosterbyday/index.php');
        echo $template->render('footer.htm');
    }
     function indexManage() {
        if($_POST['ros_type'] == 'site_contact'){
            if($_POST['action'] == 'view'){
               
                $rosSiteid = $_POST['site_id'];            
                $rosSiteDetail = $this->getUserDetail($rosSiteid);
                $view = new View;
//                pr('5');
//                pr($rosStaffDetail);
//                prd("hello");
                $this->f3->set('base_url', $this->f3->get('base_url'));
                $this->f3->set('rosSiteDetail',$rosSiteDetail);
                
                echo $view->render('rosterbyday/site_contact.php');
                die;            
            }
            
        } 
        if($_POST['ros_type'] == 'internal_comment'){
            if($_POST['action'] == 'save'){
                $rosStaffId = $_POST['staff_id']; 
                $internal_comment = $_POST['internal_comment'];
                $updateQuery = "update roster_times_staff set ros_comment_internal = '".$internal_comment."' where id = '".$rosStaffId."'";
                
                //echo $updateQuery;
                 $result = $this->dbi->query($updateQuery);
                 echo 1;
                 die;
            }else{
                $rosStaffId = $_POST['staff_id'];            
                $rosStaffDetail = $this->rosStaffDetail($rosStaffId);
                $view = new View;
//                pr('5');
//                pr($rosStaffDetail);
//                prd("hello");
                $this->f3->set('base_url', $this->f3->get('base_url'));
                $this->f3->set('rosStaffDetail',$rosStaffDetail);
                
                echo $view->render('rosterbyday/internal_comment.php');
                die;            
            }
            
        }
        
        if($_POST['ros_type'] == 'send_message'){
            if($_POST['action'] == 'save'){
                $rosStaffId = $_POST['staff_id']; 
                $send_message = $_POST['send_message'];
                $updateQuery = "update roster_times_staff set roster_send_message = '".$send_message."' where id = '".$rosStaffId."'";
                
                 $this->rosterSendMessage($rosStaffId,$send_message);
                 echo 1;
                 die;
            }else{
                $rosStaffId = $_POST['staff_id'];            
                $rosStaffDetail = $this->rosStaffDetail($rosStaffId);
                $view = new View;
//                pr('5');
//                pr($rosStaffDetail);
//                prd("hello");
                $this->f3->set('base_url', $this->f3->get('base_url'));
                $this->f3->set('rosStaffDetail',$rosStaffDetail);
                
                echo $view->render('rosterbyday/send_message.php');
                die;            
            }
            
        }
        
        if($_POST['ros_type'] == 'green_called'){
            if($_POST['action'] == 'save'){
                $rosStaffId = $_POST['staff_id']; 
                $green_called = $_POST['green_called'];
                $green_called_who = $_POST['green_called_who'];
                $updateQuery = "update roster_times_staff set green_called = '".$green_called."',green_called_who = '".$green_called_who."' where id = '".$rosStaffId."'";
                
                //echo $updateQuery;
                 $result = $this->dbi->query($updateQuery);
                 echo 1;
                 die;
            }else{
                $rosStaffId = $_POST['staff_id'];            
                $rosStaffDetail = $this->rosStaffDetail($rosStaffId);
                $view = new View;
//                pr('5');
//                pr($rosStaffDetail);
//                prd("hello");
                $this->f3->set('base_url', $this->f3->get('base_url'));
                $this->f3->set('rosStaffDetail',$rosStaffDetail);
                
                echo $view->render('rosterbyday/green_called.php');
                die;            
            }
            
        }
        
        $template = new Template;
        $view = new View;

        //Get sub job type
        $job_type_id = 1;
        $jobs_sub_type = $this->db->exec("SELECT id,title,color_code FROM jobs_sub_type WHERE job_sub_type_status=1 AND jobs_main_type_id=$job_type_id ORDER BY id ASC");
        $this->f3->set('jobs_sub_type', $jobs_sub_type);

        //Get jobs_category
        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
        $this->f3->set('jobs_category', $jobs_category);

        //Get job location list
        $job_location = $this->getLocationOfJobs();
        $this->f3->set('job_location', $job_location);

        //Get client list            
        $job_clients = $this->getClientsOfJobs();
        $this->f3->set('job_clients', $job_clients);        
        $this->f3->set('Job_List', $job_list);
        $this->f3->set('page_title', 'Roster By Day');
        $this->f3->set('base_url', $this->f3->get('base_url'));
        echo $template->render('header.htm');
        //echo $template->render('nav.htm');
        echo $view->render('rosterbyday/index_manage.php');
        echo $template->render('footer.htm');
    }
    
    function fetchTimeZoneAjax(){
         $state_array = array('NSW', 'ACT', 'VIC', 'TAS', 'QLD', 'SA', 'WA', 'NT');
    $capital['NSW'] = "Sydney";    $capital['ACT'] = "Canberra";    $capital['VIC'] = "Melbourne";    $capital['TAS'] = "Hobart";
    $capital['QLD'] = "Brisbane";    $capital['SA'] = "Adelaide";    $capital['WA'] = "Perth";    $capital['NT'] = "Darwin";
    $header .= '<div style="margin: 0px; padding: 0px; padding-bottom: 0px; font-size: 32px; color: #000066; font-weight: bold; float: left;">'. ($on_off ? 'SIGNON/OFF' : 'WELFARE') . ' DASHBOARD</div>';
    $ts = "NSW, ACT, VIC, TAS: ";
    foreach ($state_array as $st) {
      if($st == 'NSW' || $st == 'ACT' || $st == 'VIC' || $st == 'TAS') {
        if($st == 'NSW') {
          date_default_timezone_set('Australia/Sydney');
          $ts .= date("d/m H:i");
        }
      } else {
        if($st != 'NT') {
          date_default_timezone_set("Australia/{$capital[$st]}");
          $ts .= " | $st: " . date("d/m H:i");
        }
      }
    }    
        echo $ts;
        exit;
    }

    /*
      Fetch job list via ajax
     */

    function fetchRosterAjax() {
        $order_by = array();
        $length = 1000; //$this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');

        if (empty($length)) {
            $length = 1000;
            $start = 0;
        }
        
        $columnData = array(           
            'TIMESTAMPDIFF(minute,NOW(), rt.start_time_date)'            
        );
        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = ' asc'; //$sortData[0]['dir'];
        $searchData = $this->f3->get('POST.searchBox');

        $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);

        
        $where_cond = " TIMESTAMPDIFF(HOUR,NOW(), rt.start_time_date) <= 24 
AND  TIMESTAMPDIFF(minute,NOW(), rt.start_time_date) >= 0 and comp.id  in ($loginUserDivisions) ";
        
        $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);
       
        if($userAllowedSiteIds === 0 || $userAllowedSiteIds != "all") {
            
               // $userSiteAllowedconditon = " and users.id in (" . $userAllowedSiteIds . ")";
                $where_cond .= " and rost.site_id in (" . $userAllowedSiteIds . ")";
            }else{
                $where_cond .= "";
            }
            
        $select_records = "SELECT rost.site_id siteId,SUBSTRING(comp.item_name,1,3) division,TIMESTAMPDIFF(minute,NOW(), rt.start_time_date) starting_in ,site_state.item_name ros_state,rossite.name location_site,clt.name client_name,
rt.start_time_date,
rossite.address ros_site_address,
date_format(rt.start_time_date,'%H:%i') shift_starting_time,
date_format(rt.finish_time_date,'%H:%i') shift_finish_time,emp.name employee,emp.phone2 contact_number,rts.`status`,rts.start_time_date,rts.green_called,rts.green_called_who,rts.ros_comment_internal,rts.roster_send_message";
       
       
       
       $select_num_rows = "SELECT count(rts.id) as total_records";
        
        
        $rosertByDay_Query = "
FROM roster_times_staff rts
LEFT JOIN roster_times rt ON rt.id = rts.roster_time_id
LEFT JOIN rosters rost ON rost.id = rt.roster_id
LEFT JOIN companies comp ON comp.id = rost.division_id
LEFT JOIN users rossite ON rossite.ID = rost.site_id
LEFT JOIN associations ass1 ON ass1.child_user_id = rossite.ID AND ass1.association_type_id = 1
LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
LEFT JOIN states site_state ON site_state.id = rossite.state
LEFT JOIN users emp ON emp.ID = rts.staff_id";
//WHERE 1 
//and TIMESTAMPDIFF(HOUR,NOW(), rt.start_time_date) <= 24 
//AND  TIMESTAMPDIFF(HOUR,NOW(), rt.start_time_date) >= 0
//ORDER BY rt.start_time_date ASC
//LIMIT 100; ";
        
//        echo "$select_num_rows $rosertByDay_Query where $where_cond";
//        die;
        
        $total_records = $this->db->exec("$select_num_rows $rosertByDay_Query where $where_cond");

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $roster_list = array();

        if ($total_data > 0) {
            $roster_list = $this->db->exec("$select_records $rosertByDay_Query where $where_cond  ORDER BY $order_by $sort_order LIMIT $length OFFSET $start");
        }

//echo "$select_records $rosertByDay_Query where $where_cond  ORDER BY $order_by $sort_order LIMIT $length OFFSET $start";
//die;


        $jsonArray = array(
            'draw' => $this->f3->get('POST.draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_data,
            'lengthChange' => false,
            'data' => array(),
        );
        //creating view for business user in datatable
        if (!empty($roster_list)) {
            $jobStatusArr = array('0' => 'Open', '5' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
            $base_url = $this->f3->get('base_url');
            foreach ($roster_list as $key => $val) {
                
                $sitecontact_link = "javaScript:site_contact(" . $val['siteId'] . ")";
                $sitecontact_view = '<a title="Site Contact Comment" class="black-a" href="' . $sitecontact_link . '"><b>Site Contact view<b></a>';
                

$stateCode = $val['ros_state']; // Make sure $stateCode is the value of ros_state
$shiftStatus = $this->rosterShiftStatus($val['status']);
$startingIn = $this->rosterStartingIn($val['starting_in'], $stateCode); // Pass state code here
$divisionWithColor = $this->divisionWithColor($val['division']);
                

                
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'ros_division' => $divisionWithColor,
                    'ros_starting_in' => $startingIn,
                    'ros_state' => $val['ros_state'],
                    'ros_client' => $val['client_name'],                   
                    'ros_location_site' => $val['location_site'],
                    'ros_site_address'=> $val['ros_site_address'],
                    'ros_client_contact_detail'=> $sitecontact_view,
                    'ros_shift_starting_time' => $val['shift_starting_time'],
                    'ros_shift_finish_time' => $val['shift_finish_time'],
                    'ros_employee' => $val['employee'],
                    'ros_contact_number' => $val['contact_number'],
                    'ros_shift_status' => '<b>'.$shiftStatus.'</b>',
                    'ros_signed_on' => $val['start_time_date'] == "0000-00-00 00:00:00" ? "<span style='color:red'><b>No</b></span>": "<span style='color:green'><b>Yes</b></span>",
                    'ros_green_called' => $val['green_called']=='Yes'? "<span style='color:gren'>".$val['green_called']." - ".$val['green_called_who']."</span>":"<span style='color:red'>".$val['green_called']."</span>",  
                    'ros_comment_internal' => $val['ros_comment_internal'],
                    'ros_send_message' => $val['roster_send_message'],
                );
            }
            
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }


    function manageFetchRosterAjax() {        
        $order_by = array();
        $length = 1000; //$this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');

        if (empty($length)) {
            $length = 1000;
            $start = 0;
        }
        
        $columnData = array(           
            'TIMESTAMPDIFF(minute,NOW(), rt.start_time_date)'            
        );
        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = ' asc'; //$sortData[0]['dir'];
        $searchData = $this->f3->get('POST.searchBox');
//
        $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
        //prd($loginUserDivisions);
        
        $where_cond = " TIMESTAMPDIFF(HOUR,NOW(), rt.start_time_date) <= 24 
AND  TIMESTAMPDIFF(minute,NOW(), rt.start_time_date) >= 0 and comp.id  in ($loginUserDivisions) ";
        
        $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);
       
        if($userAllowedSiteIds === 0 || $userAllowedSiteIds != "all") {
            
               // $userSiteAllowedconditon = " and users.id in (" . $userAllowedSiteIds . ")";
                $where_cond .= " and rost.site_id in (" . $userAllowedSiteIds . ")";
            }else{
                $where_cond .= "";
            }
         
        $select_records = "SELECT rost.site_id siteId,rts.id,SUBSTRING(comp.item_name,1,3) division,TIMESTAMPDIFF(minute,NOW(), rt.start_time_date) starting_in ,site_state.item_name ros_state,rossite.name location_site,clt.name client_name,
rt.start_time_date,
rossite.address ros_site_address,
date_format(rt.start_time_date,'%H:%i') shift_starting_time,
date_format(rt.finish_time_date,'%H:%i') shift_finish_time,emp.name employee,emp.phone2 contact_number,rts.`status`,rts.start_time_date,rts.green_called,rts.green_called_who,rts.ros_comment_internal,rts.roster_send_message";
        $select_num_rows = "SELECT count(rts.id) as total_records";
        
        
        $rosertByDay_Query = "
FROM roster_times_staff rts
LEFT JOIN roster_times rt ON rt.id = rts.roster_time_id
LEFT JOIN rosters rost ON rost.id = rt.roster_id
LEFT JOIN companies comp ON comp.id = rost.division_id
LEFT JOIN users rossite ON rossite.ID = rost.site_id
LEFT JOIN associations ass1 ON ass1.child_user_id = rossite.ID AND ass1.association_type_id = 1
LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
LEFT JOIN states site_state ON site_state.id = rossite.state
LEFT JOIN users emp ON emp.ID = rts.staff_id";

      
        $total_records = $this->db->exec("$select_num_rows $rosertByDay_Query where $where_cond");

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;




        $roster_list = array();
        


        if ($total_data > 0) {
            $roster_list = $this->db->exec("$select_records $rosertByDay_Query where $where_cond  ORDER BY $order_by $sort_order LIMIT $length OFFSET $start");
        }




        $jsonArray = array(
            'draw' => $this->f3->get('POST.draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_data,
            'lengthChange' => false,
            'data' => array(),
        );
        //creating view for business user in datatable
        if (!empty($roster_list)) {
            $jobStatusArr = array('0' => 'Open', '5' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
            $base_url = $this->f3->get('base_url');
            foreach ($roster_list as $key => $val) {
                
                $sitecontact_link = "javaScript:site_contact(" . $val['siteId'] . ")";
//                $edit = '<a title="Edit job" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
//                $delete = '<a title="Delete job" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                $sitecontact_view = '<a title="Site Contact Comment" class="black-a" href="' . $sitecontact_link . '">Site Contact view</a>';
                
                $comment_link = "javaScript:internal_comment(" . $val['id'] . ")";
//                $edit = '<a title="Edit job" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
//                $delete = '<a title="Delete job" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                $comment_view = '<a title="Internal Comment" class="black-a" href="' . $comment_link . '">Internal Comment</a>';
//                $allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
                
                
                $sendmessage_link = "javaScript:send_message(" . $val['id'] . ")";
                $sendmessage_view = '<a title="Send Message" class="black-a" href="' . $sendmessage_link . '">Send Message</a>';
                
                $greencalled_link = "javaScript:greenCalled(" . $val['id'] . ")";
               $greenCalledText = $val['green_called']=='Yes'?"<span style='color:green'>".$val['green_called']." - ".$val['green_called_who']."</span>":($val['green_called']=='No'?"<span style='color:red'>".$val['green_called']."<span>":"Add");
               // $greenCalledText = $val['green_called']=='Yes'?"mm":"ok";
                $greencalled_view = '<a title="GreenCalled" class="black-a" href="' . $greencalled_link . '">'.$greenCalledText.'</a>';
                
                
                $stateCode = $val['ros_state']; // Make sure $stateCode is the value of ros_state
                $shiftStatus = $this->rosterShiftStatus($val['status']);
                $startingIn = $this->rosterStartingIn($val['starting_in'], $stateCode); // Pass state code here
                $divisionWithColor = $this->divisionWithColor($val['division']);

                
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'ros_division' => $divisionWithColor,
                    'ros_starting_in' => $startingIn,
                    'ros_state' => $val['ros_state'],
                    'ros_client' => $val['client_name'],                   
                    'ros_location_site' => $val['location_site'],
                    'ros_site_address'=> $val['ros_site_address'],
                    'ros_client_contact_detail'=> $sitecontact_view,
                    'ros_shift_starting_time' => $val['shift_starting_time'],
                    'ros_shift_finish_time' => $val['shift_finish_time'],
                    'ros_employee' => $val['employee'],
                    'ros_contact_number' => $val['contact_number'],
                    'ros_shift_status' => $shiftStatus,
                    'ros_signed_on' => $val['start_time_date'] == "0000-00-00 00:00:00" ? "<span style='color:red'>No</span>": "<span style='color:green'>Yes</span>",
                    'ros_green_called' => $greencalled_view,  
                    'ros_comment_internal' => $comment_view, //$val['ros_comment_internal'],
                    'ros_send_message' => $sendmessage_view,
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }

    /*
      Create a new job
     */

    function create() {

        $this->manageJob('create');

//        if(!empty($_POST)){         
//     
//      $error_mesg = '';
//      $job_title       = $this->f3->get('POST.job_title'); 
//      $job_type_id     = $this->f3->get('POST.job_type_id');
//      $job_sub_type_id = $this->f3->get('POST.job_sub_type_id');
//      $job_category_id = $this->f3->get('POST.job_category_id');
//      $job_client_id   = $this->f3->get('POST.job_client_id');
//      $job_location_id = $this->f3->get('POST.job_location_id');
//      $job_supplier_ids= $this->f3->get('POST.job_supplier_ids');
//
//      $job_detail      = $this->f3->get('POST.job_detail');
//     // $job_review_point= $this->f3->get('POST.job_review_point');
//      $recommend_action= $this->f3->get('POST.recommend_action');
//      //$job_quote_detail= $this->f3->get('POST.job_quote_detail');
//      $client_order_no = $this->f3->get('POST.client_order_no');
//      $job_start_date  = $this->f3->get('POST.job_start_date');
//      $start_time      = $this->f3->get('POST.start_time');
//      $job_end_date    = $this->f3->get('POST.job_end_date');
//      $end_time        = $this->f3->get('POST.end_time');
////      $job_hours       = $this->f3->get('POST.job_hours');
////      $job_cost        = $this->f3->get('POST.job_cost');
//
//      /* Validation start */
//      if(empty($job_title))
//        $error_mesg .= 'Job title is required<br>';
//      if(empty($job_type_id))
//        $error_mesg .= 'Job type is required<br>';
//      if(empty($job_sub_type_id))
//        $error_mesg .= 'Job type is required<br>';
//      if(empty($job_category_id))
//        $error_mesg .= 'Job category is required<br>';
//       if(empty($job_client_id))
//        $error_mesg .= 'Job client is required<br>';
//       if(empty($job_location_id))
//        $error_mesg .= 'Job location is required<br>';
//       if(empty($job_supplier_ids))
//        $error_mesg .= 'Suppliers are required<br>';
//       if(empty($job_detail))
//        $error_mesg .= 'Job detail is required<br>';
//       if(!empty($job_start_date) && !empty($job_end_date) && $job_end_date<$job_start_date)
//        $error_mesg .= 'Invalid Quote end date<br>';
//       if(empty($job_start_date))
//        $error_mesg .= 'Quote start date is required<br>';
//      if(empty($job_end_date))
//        $error_mesg .= 'Quote end date is required<br>';
//       if(!empty($job_end_date) && empty($job_start_date))
//        $error_mesg .= 'Quote start date is required<br>';
//       if(!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00'){
//         $job_start_date_time = $job_start_date.' '.$start_time.':00';
//         $job_end_date_time = $job_end_date.' '.$end_time.':00';
//         if($job_end_date_time<$job_start_date_time)
//           $error_mesg .= 'Quote start dateTime should be less than Quote end dateTime<br>';
//       }       
//       if(!empty($error_mesg)){
//        $data = array(
//          'code' =>  '404',
//          'mesg'   => $error_mesg
//        );
//        echo json_encode($data);
//        die;
//       }      
//      /* Validation end */       
//       if(!empty($job_start_date))
//        $job_start_date = $job_start_date.' '.$start_time.':00';     
//      else
//        $job_start_date = '';
//
//      if(!empty($job_end_date))
//        $job_end_date = $job_end_date.' '.$end_time.':00';
//      else
//        $job_end_date = ''; 
//
//      if(!empty($job_start_date))
//        $job_start_date = date('Y-m-d H:i:s',strtotime($job_start_date));
//
//      if(!empty($job_end_date))
//        $job_end_date = date('Y-m-d H:i:s',strtotime($job_end_date));
//
//      $job_title  = ucfirst($job_title);
//      $created_by = $_SESSION['user_id']; 
//      $time      = time();
//      $rand      = rand(999,9999);      
//      $job_ref_no = $time+$rand;      
//      
//      $job_title         = addslashes($job_title);
//      $job_detail        = addslashes($job_detail);
//      $client_order_no   = addslashes($client_order_no);
//      //$job_review_point  = addslashes($job_review_point);
//      $recommend_action  = addslashes($recommend_action);
//      //$job_quote_detail  = addslashes($job_quote_detail);     
//      $job_pdf_filename  = $job_ref_no.".pdf";
//
//      $job_id1 = $this->db->exec("INSERT INTO jobs (job_type_id,job_sub_type_id,job_category_id,job_client_id,job_location_id,job_title,job_detail,job_ref_no,client_order_no,quote_start_date,quote_end_date,recommend_action,created_by) "
//              . " VALUES('$job_type_id','$job_sub_type_id','$job_category_id','$job_client_id','$job_location_id','$job_title','$job_detail','$job_ref_no','$client_order_no','$job_start_date','$job_end_date','$recommend_action','$created_by')"); 
//      
//      
//      
//        if(!empty($job_id1)){
//            $job_id = $this->db->lastInsertId(); 
//            
//           foreach($job_supplier_ids as $supplier_id){
//                
//               $this->db->exec("INSERT INTO jobs_supplier (job_id,supplier_id) VALUES($job_id,$supplier_id)");
//               
//           }
//           
//          
//           //Create PDF file of Job order
//                // Require composer autoload
//               require_once 'vendor/autoload.php';
//               
////               echo $job_id;
////      die;
//               
//               
//                // Create an instance of the class:
//               
//                $mpdf = new \Mpdf\Mpdf();                 
//                 $view = new View; 
//                //fetch job detail  
//                 
//                $select_records = "SELECT jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(location.name, ' ', location.surname) as location_name";
//                $job_w_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id LEFT JOIN users as location ON jobs.job_location_id=location.id WHERE jobs.id=$job_id"); 
//
//                 $date = date('d-M-Y H:i');        
//                 $job_detail_arr = array(
//                 'date'          => $date,
//                 'job_title'     => $job_title, 
//                 'job_detail'     => $job_detail, 
//                 'job_start_date' => $job_start_date, 
//                 'job_end_date'   => $job_end_date, 
//                 'job_type'       => $job_w_detail[0]['job_sub_type_title'],
//                 'job_category'   => $job_w_detail[0]['job_category_name'],
//                 'job_location'   => $job_w_detail[0]['location_name'],                 
//                 );                 
//                ob_start();  // start output buffering
//                 $this->f3->set('base_url', $this->f3->get('base_folder'));
//                $this->f3->set('job_detail_arr', $job_detail_arr); 
//                echo $view->render('jobs/job_order_pdf.php');                  
//                $content = ob_get_clean(); // get content of the buffer and clean the buffer
//                $mpdf->SetDisplayMode('fullpage');
//                $mpdf->WriteHTML($content);
//                $base_url = $this->f3->get('base_folder').'pdf_files/';
//                 if(!file_exists($base_url)){
//                     mkdir($base_url, 0777,true);
//                }
//                
//                
//                $file_path_name = $base_url.$job_pdf_filename;
//                $mpdf->Output($file_path_name,'F',true);
//               //Send Job order to suppliers via email                
//                $supp_records = $this->db->exec("SELECT users.ID,email,name FROM jobs_supplier LEFT JOIN users ON jobs_supplier.supplier_id=users.ID WHERE job_id=$job_id"); 
//                if(!empty($supp_records)){
//                  foreach($supp_records as $sup){
//                    if(empty($sup['email']))
//                     continue;                    
//                    $supplier_email_address = $sup['email'];
//                    $email_msg = 'Hello '.$sup['name'].',<br> Please find an attachment of the job detail.<br> If you are interested, Please reply us on the same email.<br><br>Thanks<br>'.$this->f3->get('company_name');
//                    $mail = new email_q($this->dbi);                   
//                    $mail->AddAddress($supplier_email_address);                
//                    $mail->Subject = $this->f3->get('company_name') . " - New job detail";
//                    $mail->Body = $email_msg;                
//                    //$mail->queue_message();
//                    $mail->AddAttachment( $file_path_name );
//                    $mail->send(); 
//                    $mail->job_mail_logs($job_id,$sup['ID'],$job_pdf_filename,$supplier_email_address);
//                  }
//                }
//                 $data = array(
//                  'code' =>  '200',
//                  'mesg'   => 'Job created successfully'
//                );
//                echo json_encode($data);
//                die;
//        }else{
//           $data = array(
//            'code' =>  '404',
//            'mesg'   => 'Somthing went to wrong. please try again.'
//          );
//          echo json_encode($data);
//          die;  
//        }
//     }     
//     $template = new Template;                     
//     $view = new View;         
//     //Get sub job type
//      $job_type_id  = 1;
//      $jobs_sub_type = $this->db->exec("SELECT id,title FROM jobs_sub_type WHERE job_sub_type_status=1 AND jobs_main_type_id=$job_type_id ORDER BY title ASC"); 
//      $this->f3->set('jobs_sub_type', $jobs_sub_type);  
//     //Get jobs_category
//     $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");                 
//     $this->f3->set('jobs_category', $jobs_category);  
//
//     //Get job location list
//     $job_location = $this->getLocationOfJobs();     
//     $this->f3->set('job_location', $job_location); 
//
//     //Get client list       
//     //Get job location list
//     $job_clients = $this->getClientsOfJobs();       
//     $this->f3->set('job_clients', $job_clients); 
//
//     $this->f3->set('page_title', 'Create a new job');     
//     $this->f3->set('base_url',$this->f3->get('base_url'));
//     echo $template->render('header.htm');                            
//     echo $template->render('nav.htm');                            
//     echo $view->render('jobs/edit.php');  
//     echo $template->render('footer.htm');
    }

    function manageJob($type, $job_id = 0) {
        $flder = $this->f3->get('download_folder') . "job_files/";
        $showflder = "job_files/";
        if ($type != 'create') {
            $select_records = "SELECT *";
            $job_detail = $this->db->exec("$select_records FROM jobs WHERE jobs.id=$job_id");
            if (empty($job_detail[0]) || $job_detail[0]['job_status'] > 2) {
                //redirect to list page
                $this->f3->reroute('/jobs');
            }
        } else {
            $job_detail = array();
        }

        //Update/Add Job          

        if (!empty($_POST)) {
            if ($type == 'create' || !empty($job_detail[0])) {

                $error_mesg = '';

                $resend_email = $this->f3->get('POST.resend_email');
                $job_title = $this->f3->get('POST.job_title');
                $job_type_id = $this->f3->get('POST.job_type_id');
                $job_sub_type_id = $this->f3->get('POST.job_sub_type_id');
                $job_category_id = $this->f3->get('POST.job_category_id');
                $job_client_id = $this->f3->get('POST.job_client_id');
                $job_location_id = $this->f3->get('POST.job_location_id');
                $job_supplier_ids = $this->f3->get('POST.job_supplier_ids');
                $job_manager_email = $this->f3->get('POST.job_manager_email');
                $job_detail1 = $this->f3->get('POST.job_detail');
                // $job_review_point= $this->f3->get('POST.job_review_point');
                $recommend_action = $this->f3->get('POST.recommend_action');
                // $job_quote_detail= $this->f3->get('POST.job_quote_detail');
                $client_order_no = $this->f3->get('POST.client_order_no');
                $job_start_date = $this->f3->get('POST.job_start_date');
                $start_time = $this->f3->get('POST.start_time');
                $job_end_date = $this->f3->get('POST.job_end_date');
                $end_time = $this->f3->get('POST.end_time');

                $quote_end_date = $this->f3->get('POST.quote_end_date');
                $quote_end_time = $this->f3->get('POST.quote_end_time');

                if ($quote_end_time == "") {
                    $quote_end_time = "00:00";
                }
                if ($start_time == "") {
                    $start_time = "00:00";
                }
                if ($end_time == "") {
                    $end_time = "00:00";
                }
//      pr($_POST);
//      echo $quote_end_time;
//      die;
                //$job_hours       = $this->f3->get('POST.job_hours');
                //$job_cost        = $this->f3->get('POST.job_cost');

                /* Validation start */
                if (empty($job_title))
                    $error_mesg .= 'Job title is required<br>';
                if (empty($job_type_id))
                    $error_mesg .= 'Job type is required<br>';
                if (empty($job_sub_type_id))
                    $error_mesg .= 'Job type is required<br>';
                if (empty($job_category_id))
                    $error_mesg .= 'Job category is required<br>';
                if (empty($job_client_id))
                    $error_mesg .= 'Job client is required<br>';
                if (empty($job_location_id))
                    $error_mesg .= 'Job location is required<br>';
                if (empty($job_supplier_ids))
                    $error_mesg .= 'Suppliers are required<br>';
                if (empty($job_detail1))
                    $error_mesg .= 'Job detail is required<br>';


                if (isset($_FILES['job_document'])) {
                    $errors = array();
                    $file_name = $_FILES['job_document']['name'];
                    $file_size = $_FILES['job_document']['size'];
                    $file_tmp = $_FILES['job_document']['tmp_name'];
                    $file_type = $_FILES['job_document']['type'];
                    $file_ext = strtolower(end(explode('.', $_FILES['image']['name'])));

//                        $extensions = array("jpeg", "jpg", "png");
//
//                        if (in_array($file_ext, $extensions) === false) {
//                            $errors[] = "extension not allowed, please choose a JPEG or PNG file.";
//                        }

                    if ($file_size > 2097152) {
                        $error_mesg .= 'File size should be not more then 2 MB';
                    }
                }




//       if(!empty($job_start_date) && !empty($job_end_date) && $job_end_date<$job_start_date)
//        $error_mesg .= 'Invalid job end date<br>';
//       if($start_time != '00:00' && empty($job_start_date))
//        $error_mesg .= 'Job start date is required<br>';
//      if($end_time != '00:00' && empty($job_end_date))
//        $error_mesg .= 'Job end date is required<br>';
//       if(!empty($job_end_date) && empty($job_start_date))
//        $error_mesg .= 'Job start date is required<br>';
//       if(!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00'){
//         $job_start_date_time = $job_start_date.' '.$start_time.':00';
//         $job_end_date_time = $job_end_date.' '.$end_time.':00';
//         if($job_end_date_time<$job_start_date_time)
//           $error_mesg .= 'Job start dateTime should be less than job end dateTime<br>';
//       }     

                if (empty($quote_end_date))
                    $error_mesg .= 'Quote end date is required<br>';


                if (!empty($job_start_date) && !empty($job_end_date) && $job_end_date < $job_start_date)
                    $error_mesg .= 'Invalid Job end date<br>';
                if (empty($job_start_date))
                    $error_mesg .= 'Job start date is required<br>';
                if (empty($job_end_date))
                    $error_mesg .= 'Job end date is required<br>';
                if (!empty($job_end_date) && empty($job_start_date))
                    $error_mesg .= 'Job start date is required<br>';
                if (!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00') {
                    $job_start_date_time = $job_start_date . ' ' . $start_time . ':00';
                    $job_end_date_time = $job_end_date . ' ' . $end_time . ':00';
                    if ($job_end_date_time < $job_start_date_time)
                        $error_mesg .= 'Job start dateTime should be less than Job end dateTime<br>';
                }
                if (!empty($error_mesg)) {
                    $data = array(
                        'code' => '404',
                        'mesg' => $error_mesg
                    );
                    echo json_encode($data);
                    die;
                }
                /* Validation end */

                if (!empty($quote_end_date))
                    $quote_end_date = $quote_end_date . ' ' . $quote_end_time . ':00';
                else
                    $quote_end_date = '';


                if (!empty($job_start_date))
                    $job_start_date = $job_start_date . ' ' . $start_time . ':00';
                else
                    $job_start_date = '';

                if (!empty($job_end_date))
                    $job_end_date = $job_end_date . ' ' . $end_time . ':00';
                else
                    $job_end_date = '';

                if (!empty($job_start_date))
                    $job_start_date = date('Y-m-d H:i:s', strtotime($job_start_date));

                if (!empty($job_end_date))
                    $job_end_date = date('Y-m-d H:i:s', strtotime($job_end_date));


                if (!empty($quote_end_date))
                    $quote_end_date = date('Y-m-d H:i:s', strtotime($quote_end_date));




                $job_title = ucfirst($job_title);
                $updated_by = $_SESSION['user_id'];
                $time = time();
                $rand = rand(999, 9999);
                $job_ref_no = $time + $rand;

                $job_title = addslashes($job_title);
                $job_detail1 = addslashes($job_detail1);
                $client_order_no = addslashes($client_order_no);
                //$job_review_point  = addslashes($job_review_point);
                $recommend_action = addslashes($recommend_action);
                // $job_quote_detail  = addslashes($job_quote_detail);     
                $job_pdf_filename = $job_ref_no . ".pdf";

//      echo 'job_start_date = '.$job_start_date.',job_end_date = '.$job_end_date.',quote_end_date = '.$quote_end_date;
//      die;
                if ($type == 'create') {
                    $insertQuery = "INSERT INTO jobs (job_type_id,job_sub_type_id,job_category_id,job_client_id,job_location_id,job_title,job_manager_email,job_detail,job_ref_no,client_order_no,job_start_date,job_end_date,quote_end_date,recommend_action,created_by) "
                            . " VALUES('$job_type_id','$job_sub_type_id','$job_category_id','$job_client_id','$job_location_id','$job_title','$job_manager_email','$job_detail1','$job_ref_no','$client_order_no','$job_start_date','$job_end_date','$quote_end_date','$recommend_action','$created_by')";

                    $job_id1 = $this->db->exec($insertQuery);

                    if (!empty($job_id1)) {
                        $job_id = $this->db->lastInsertId();
                    }
                } else {
                    $upQuery = "UPDATE jobs SET job_type_id = '$job_type_id', job_sub_type_id = '$job_sub_type_id',job_category_id = '$job_category_id',job_client_id = '$job_client_id', job_location_id = '$job_location_id',job_title = '$job_title',job_manager_email='$job_manager_email',job_detail = '$job_detail1', client_order_no = '$client_order_no', job_start_date = '$job_start_date',job_end_date = '$job_end_date',quote_end_date = '$quote_end_date',job_quote_detail = '$job_quote_detail',updated_by = '$updated_by'  WHERE id = '$job_id'";
                    $this->db->exec($upQuery);
                }
// echo $upQuery;
                // die;
                //die('update');
                if ($job_id) {

                    $imageLoop = 12;

                    for ($i = 1; $i < $imageLoop; $i++) {
                        if (isset($_POST['hdnImage' . $i])) {
                            //die("test");
                            $img = $_POST['hdnImage' . $i];
                            if ($img) {
                                //$flder = $this->f3->get('download_folder') . "job_files/";
                                $folder = "$flder$job_id";
                                if (!file_exists($folder)) {
                                    mkdir($folder, 0755, true);
                                    //chmod($folder, 0755,true);
                                }
                                //save image
                                $img = str_replace(' ', '+', $img);
                                $img = substr($img, strpos($img, ",") + 1);
                                $data = base64_decode($img);
                                $img_name = basename($_POST['hdnFileName' . $i]);

                                $img_name = 'jobimage' . $i . '.jpg';
                                $file = "$folder/$img_name";
                                $success = file_put_contents($file, $data);
                                if ($success) {
//                                    $sql = "update jobs set job_image = '$img_name' where ID = $job_id";
//                                    $this->dbi->query($sql);
                                }
                            }
                        }
                    }




                    if (isset($_FILES['job_document'])) {
                        $errors = array();
                        $file_name = $_FILES['job_document']['name'];
                        $file_size = $_FILES['job_document']['size'];
                        $file_tmp = $_FILES['job_document']['tmp_name'];
                        $file_type = $_FILES['job_document']['type'];
                        $file_ext = strtolower(end(explode('.', $_FILES['job_document']['name'])));
//                       echo $file_ext.$_FILES['image']['name'];
//                       die;
//                        $extensions = array("jpeg", "jpg", "png");
//
//                        if (in_array($file_ext, $extensions) === false) {
//                            $errors[] = "extension not allowed, please choose a JPEG or PNG file.";
//                        }


                        if (empty($errors) == true) {
                            $folder = "$flder$job_id";
                            if (!file_exists($folder)) {
                                mkdir($folder, 0755, true);
                                //chmod($folder, 0755,true);
                            }
                            $file_name = time() . "." . $file_ext;
                            $jobDocumentFolder = "$folder/$file_name";

                            if (move_uploaded_file($file_tmp, $jobDocumentFolder)) {
                                $sql = "update jobs set job_document = '$file_name' where ID = $job_id";
                                $this->dbi->query($sql);
                            }
                        }
                    }



                    $this->db->exec("DELETE FROM jobs_supplier WHERE job_id = $job_id");
                    foreach ($job_supplier_ids as $supplier_id) {
                        $this->db->exec("INSERT INTO jobs_supplier (job_id,supplier_id) VALUES($job_id,$supplier_id)");
                    }
                    if ($type == 'create' || $resend_email) {
                        //Create PDF file of Job order
                        // Require composer autoload
                        require_once 'vendor/autoload.php';
                        // Create an instance of the class:
                       $mpdf = new \Mpdf\Mpdf();
                       //$mpdf = new \Mpdf\Mpdf(['orientation' => 'L','format' => [1236, 1190]]);
                        $view = new View;
                        //fetch job detail                
                        $select_records = "SELECT jobs_sub_type.id as job_sub_type_id, jobs_sub_type.color_code as color_code,jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(location.name, ' ', location.surname) as location_name";
                        $job_w_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id LEFT JOIN users as location ON jobs.job_location_id=location.id WHERE jobs.id=$job_id");

                        $date = date('d-M-Y H:i');
                        $job_detail_arr = array(
                            'date' => $date,
                            'job_title' => $job_title,
                            'job_detail' => $job_detail1,
                            'job_start_date' => $job_start_date,
                            'job_end_date' => $job_end_date,
                            'quote_end_date' => $quote_end_date,
                            'job_sub_type_id' => $job_w_detail[0]['job_sub_type_id'],
                            'color_code' => $job_w_detail[0]['color_code'],
                            'job_type' => $job_w_detail[0]['job_sub_type_title'],
                            'job_category' => $job_w_detail[0]['job_category_name'],
                            'job_location' => $job_w_detail[0]['location_name'],
                        );
                        ob_start();  // start output buffering
                        $baseUrlOld = $this->f3->get('base_url_pdf');
                        $this->f3->set('base_url', $this->f3->get('base_folder'));
                        $this->f3->set('job_detail_arr', $job_detail_arr);
                        if ($job_id) {
                            $imageLoop = 12;
                            for ($i = 1; $i <= $imageLoop; $i++) {
                                ${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";
                                if (file_exists(${'img_file' . $i})) {
                                    ${'show_img_file' . $i} =  "$showflder$job_id/jobimage$i.jpg";
                                    ${'url_img_file' . $i} = $baseUrlOld.'/edge/downloads/'.${'show_img_file' . $i};
                                            //$this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt(${'show_img_file' . $i}));
//                                    pr($baseUrlOld);
//                                    prd($url_img_file1);
//                                    die;
                                    
                                    $this->f3->set('img_file' . $i, ${'img_file' . $i});
                                    //$this->f3->set('f3', $this->f3);
                                    $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
                                }
                            }
                        }

                        echo $view->render('jobs/job_order_pdf.php');
                      //  die;
                        $content = ob_get_clean(); // get content of the buffer and clean the buffer
                        $mpdf->SetDisplayMode('fullpage');
                        $mpdf->WriteHTML($content);
                        $base_url = $this->f3->get('base_folder') . 'pdf_files/';
                        // mkdir
                        if (!file_exists($base_url)) {
                            mkdir($base_url, 0777, true);
                        }
                        $file_path_name = $base_url . $job_pdf_filename;
                        $mpdf->Output($file_path_name, 'F', true);
                        //Send Job order to suppliers via email                
                        $supp_records = $this->db->exec("SELECT users.ID,email,name FROM jobs_supplier LEFT JOIN users ON jobs_supplier.supplier_id=users.ID WHERE job_id=$job_id");
                        // prd($supp_records);
                        if (!empty($supp_records)) {
                            foreach ($supp_records as $sup) {
                                if (empty($sup['email']))
                                    continue;
                                $supplier_email_address = $sup['email'];
                                $email_msg = 'Hello ' . $sup['name'] . ',<br> Please find an attachment of the job detail.<br> If you are interested, Please reply us on the same email.<br><br>Thanks<br>' . $this->f3->get('company_name');
                                $mail = new email_q($this->dbi);
                                $mail->AddAddress($supplier_email_address);

                                $mail->Subject = $this->f3->get('company_name') . " - Updated job detail";
                                $mail->Body = $email_msg;
                                //$mail->queue_message();
                                $mail->AddAttachment($file_path_name);
                                $mail->send();
                                $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
                            }


                            if (trim($job_manager_email) != "") {
                                $supplier_email_address = $job_manager_email;
                                $email_msg = 'Hello ,<br> Please find an attachment of the job detail.<br> Please check and discurss with suppliers.<br><br>Thanks<br>' . $this->f3->get('company_name');
                                $mail = new email_q($this->dbi);
                                $mail->AddAddress($supplier_email_address);

                                $mail->Subject = $this->f3->get('company_name') . " - Updated job detail";
                                $mail->Body = $email_msg;
                                //$mail->queue_message();
                                $mail->AddAttachment($file_path_name);
                                $mail->send();
                                // $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
                            }
                        }
                    }

                    if ($type == 'create') {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Job created successfully'
                        );
                    } else {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Job updated successfully'
                        );
                    }

                    echo json_encode($data);
                    die;
                } else {
                    $data = array(
                        'code' => '404',
                        'mesg' => 'Somthing went to wrong. please try again.'
                    );
                    echo json_encode($data);
                    die;
                }
            }
        }

        if ($job_id) {
            $job_detail = $this->db->exec("$select_records FROM jobs WHERE jobs.id=$job_id");
        }


        //Get sub job type
        $job_type_id = 1;
        $jobs_sub_type = $this->db->exec("SELECT id,title,color_code FROM jobs_sub_type WHERE job_sub_type_status=1 AND jobs_main_type_id=$job_type_id ORDER BY id ASC");
        $this->f3->set('jobs_sub_type', $jobs_sub_type);
        //Get jobs_category
        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY id ASC");
        $this->f3->set('jobs_category', $jobs_category);

        //Get client list       
        //Get job location list
        $job_clients = $this->getClientsOfJobs();
        $this->f3->set('job_clients', $job_clients);

        //Get job location list
        if ($job_detail[0]) {
            $job_location_id = $job_detail[0]['job_location_id'];
            $job_client_id = $job_detail[0]['job_client_id'];

//         echo $job_client_id;
//         die;

            $job_location = $this->getLocationOfJobs($job_client_id, $job_location_id);
            $this->f3->set('job_location', $job_location);
        } else {
            $job_location = array();
        }

        //Get supplier list
        if ($job_detail[0]) {
            $job_location_id = $job_detail[0]['job_location_id'];
            $job_category_id = $job_detail[0]['job_category_id'];

            $job_suppliers = $this->getSupplierOfJobs($job_category_id, $job_location_id);

            $job_id = $job_detail[0]['id'];
        } else {
            $job_detail[0] = array();
        }
        $supplier_list = $this->db->exec("SELECT supplier_id FROM jobs_supplier WHERE job_id=$job_id");
        $supplier_ids = array();
        foreach ($supplier_list as $sup) {
            $supplier_ids[] = $sup['supplier_id'];
        }

        $template = new Template;
        $view = new View;
        $this->f3->set('job_detail', $job_detail[0]);
        $this->f3->set('base_url', $this->f3->get('base_url'));
        if (isset($job_detail[0]['id'])) {
            $this->f3->set('page_title', 'Edit Job');
        } else {
            $this->f3->set('page_title', 'Add Job');
        }


        if ($job_id) {
            $imageLoop = 6;
            for ($i = 1; $i <= $imageLoop; $i++) {
                ${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";

                if (file_exists(${'img_file' . $i})) {
                    ${'show_img_file' . $i} = "$showflder$job_id/jobimage$i.jpg";
                    ${'url_img_file' . $i} = $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt(${'show_img_file' . $i}));
                    $this->f3->set('img_file' . $i, ${'img_file' . $i});
                    //$this->f3->set('f3', $this->f3);
                    $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
                }
            }
        }

        $documentFileName = $job_detail[0]['job_document'];

        $documentFileFullPath = "$flder$job_id/$documentFileName";
        //echo $documentFileName;

        if (file_exists($documentFileFullPath)) {
            $documentUrl = $this->f3->get('main_folder') . 'job_document_download?job_id=' . $job_id . '&file_name=' . $documentFileName;
        } else {
            $documentUrl = "";
        }



        $this->f3->set('document_url', $documentUrl);
        $this->f3->set('job_suppliers', $job_suppliers);
        $this->f3->set('supplier_ids', $supplier_ids);

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('jobs/edit.php');
        echo $template->render('footer.htm');
    }

    //getJobSubTypeList
    function fetchJobSupplierList() {
        $str = '';
        if (!empty($_POST)) {
            $job_location_id = $this->f3->get('POST.job_location_id');
            $job_category_id = $this->f3->get('POST.job_category_id');
            if (!empty($job_category_id)) {
                $job_suppliers = $this->getSupplierOfJobs($job_category_id, $job_location_id);
                if (!empty($job_suppliers)) {
                    foreach ($job_suppliers as $supplier) {
                        $str .= '<option value="' . $supplier['id'] . '">' . $supplier['item_name'] . '</option>';
                    }
                }
            }
        }
        echo $str;
        die;
    }

    function fetchJobClientList() {
        $str = '';
        if (!empty($_POST)) {
            $job_client_id = $this->f3->get('POST.job_client_id');
            if (!empty($job_client_id)) {
                $job_Location = $this->getLocationOfJobs($job_client_id);
                if (!empty($job_Location)) {
                    foreach ($job_Location as $location) {
                        $str .= '<option value="' . $location['id'] . '">' . $location['item_name'] . '</option>';
                    }
                }
            }
        }
        echo $str;
        die;
    }

    //delete job
    function deleteJob() {
        $str = '';
        if (!empty($_POST)) {
            $job_id = $this->f3->get('POST.job_id');
            $str = $this->db->exec("UPDATE jobs SET job_delete_status='0' WHERE id=" . $job_id);
        }
        echo $str;
        die;
    }

    //view Job Detail
    function viewJobDetail() {
        $str = '';
        if (!empty($_REQUEST)) {
            $job_id = $this->f3->get('POST.job_id');
            $select_records = "SELECT jobs.*, jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(client.name, ' ', client.surname) as client_name, CONCAT(location.name, ' ', location.surname) as location_name, CONCAT(supplier.name, ' ', supplier.surname) as supplier_name, CONCAT(user_tbl.name, ' ', user_tbl.surname) as created_by,  CONCAT(u_tbl.name, ' ', u_tbl.surname) as updated_by";
            $job_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id LEFT JOIN users as user_tbl ON jobs.created_by=user_tbl.ID LEFT JOIN users as u_tbl ON jobs.updated_by=u_tbl.ID WHERE jobs.id=$job_id");
            $this->f3->set('Job_Detail', $job_detail[0]);

            $supplier_list = $this->db->exec("SELECT CONCAT(users.name, ' ', users.surname) as supplier_name FROM jobs_supplier LEFT JOIN users ON jobs_supplier.supplier_id=users.ID WHERE jobs_supplier.job_id=$job_id");
            $this->f3->set('supplier_list', $supplier_list);
            //Email log detail
            $email_logs = $this->db->exec("SELECT jobs_mail_logs.*,CONCAT(users.name, ' ', users.surname) as supplier_name FROM jobs_mail_logs LEFT JOIN users ON jobs_mail_logs.supplier_id=users.ID WHERE jobs_mail_logs.jobs_id=$job_id ORDER BY id DESC");
            $this->f3->set('email_logs', $email_logs);
            $flder = $this->f3->get('download_folder') . "job_files/";
            $showflder = "job_files/";
            if ($job_id) {
                $imageLoop = 6;
                for ($i = 1; $i <= $imageLoop; $i++) {

                    ${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";

                    if (file_exists(${'img_file' . $i})) {

                        ${'show_img_file' . $i} = "$showflder$job_id/jobimage$i.jpg";
                        ${'url_img_file' . $i} = $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt(${'show_img_file' . $i}));
                        $this->f3->set('img_file' . $i, ${'img_file' . $i});
                        //$this->f3->set('f3', $this->f3);
                        $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
                    }
                }
            }



            $this->f3->set('base_url', $this->f3->get('base_url'));
            $view = new View;
            echo $view->render('jobs/job_detail.php');
            die;
        }
        echo $str;
        die;
    }

    function edit($job_id = NULL) {



        $this->manageJob('update', $job_id);

//        $select_records = "SELECT *";
//        $job_detail = $this->db->exec("$select_records FROM jobs WHERE jobs.id=$job_id");
//        if (empty($job_detail[0])) {
//            //redirect to list page
//            $this->f3->reroute('/jobs');
//        }
//        //Update Job   
//        if (!empty($_POST) && !empty($job_detail[0])) {
//            $error_mesg = '';
//            $resend_email = $this->f3->get('POST.resend_email');
//            $job_title = $this->f3->get('POST.job_title');
//            $job_type_id = $this->f3->get('POST.job_type_id');
//            $job_sub_type_id = $this->f3->get('POST.job_sub_type_id');
//            $job_category_id = $this->f3->get('POST.job_category_id');
//            $job_client_id = $this->f3->get('POST.job_client_id');
//            $job_location_id = $this->f3->get('POST.job_location_id');
//            $job_supplier_ids = $this->f3->get('POST.job_supplier_ids');
//
//            $job_detail = $this->f3->get('POST.job_detail');
//            // $job_review_point= $this->f3->get('POST.job_review_point');
//            $recommend_action = $this->f3->get('POST.recommend_action');
//            // $job_quote_detail= $this->f3->get('POST.job_quote_detail');
//            $client_order_no = $this->f3->get('POST.client_order_no');
//            $job_start_date = $this->f3->get('POST.job_start_date');
//            $start_time = $this->f3->get('POST.start_time');
//            $job_end_date = $this->f3->get('POST.job_end_date');
//            $end_time = $this->f3->get('POST.end_time');
//
//            $quote_end_date = $this->f3->get('POST.quote_end_date');
//            $quote_end_time = $this->f3->get('POST.quote_end_time');
//
//            if ($quote_end_time == "") {
//                $quote_end_time = "00:00";
//            }
//            if ($start_time == "") {
//                $start_time = "00:00";
//            }
//            if ($end_time == "") {
//                $end_time = "00:00";
//            }
////      pr($_POST);
////      echo $quote_end_time;
////      die;
//            //$job_hours       = $this->f3->get('POST.job_hours');
//            //$job_cost        = $this->f3->get('POST.job_cost');
//
//            /* Validation start */
//            if (empty($job_title))
//                $error_mesg .= 'Job title is required<br>';
//            if (empty($job_type_id))
//                $error_mesg .= 'Job type is required<br>';
//            if (empty($job_sub_type_id))
//                $error_mesg .= 'Job type is required<br>';
//            if (empty($job_category_id))
//                $error_mesg .= 'Job category is required<br>';
//            if (empty($job_client_id))
//                $error_mesg .= 'Job client is required<br>';
//            if (empty($job_location_id))
//                $error_mesg .= 'Job location is required<br>';
//            if (empty($job_supplier_ids))
//                $error_mesg .= 'Suppliers are required<br>';
//            if (empty($job_detail))
//                $error_mesg .= 'Job detail is required<br>';
//
////       if(!empty($job_start_date) && !empty($job_end_date) && $job_end_date<$job_start_date)
////        $error_mesg .= 'Invalid job end date<br>';
////       if($start_time != '00:00' && empty($job_start_date))
////        $error_mesg .= 'Job start date is required<br>';
////      if($end_time != '00:00' && empty($job_end_date))
////        $error_mesg .= 'Job end date is required<br>';
////       if(!empty($job_end_date) && empty($job_start_date))
////        $error_mesg .= 'Job start date is required<br>';
////       if(!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00'){
////         $job_start_date_time = $job_start_date.' '.$start_time.':00';
////         $job_end_date_time = $job_end_date.' '.$end_time.':00';
////         if($job_end_date_time<$job_start_date_time)
////           $error_mesg .= 'Job start dateTime should be less than job end dateTime<br>';
////       }     
//
//            if (empty($quote_end_date))
//                $error_mesg .= 'Quote end date is required<br>';
//
//
//            if (!empty($job_start_date) && !empty($job_end_date) && $job_end_date < $job_start_date)
//                $error_mesg .= 'Invalid Job end date<br>';
//            if (empty($job_start_date))
//                $error_mesg .= 'Job start date is required<br>';
//            if (empty($job_end_date))
//                $error_mesg .= 'Job end date is required<br>';
//            if (!empty($job_end_date) && empty($job_start_date))
//                $error_mesg .= 'Job start date is required<br>';
//            if (!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00') {
//                $job_start_date_time = $job_start_date . ' ' . $start_time . ':00';
//                $job_end_date_time = $job_end_date . ' ' . $end_time . ':00';
//                if ($job_end_date_time < $job_start_date_time)
//                    $error_mesg .= 'Job start dateTime should be less than Job end dateTime<br>';
//            }
//            if (!empty($error_mesg)) {
//                $data = array(
//                    'code' => '404',
//                    'mesg' => $error_mesg
//                );
//                echo json_encode($data);
//                die;
//            }
//            /* Validation end */
//
//            if (!empty($quote_end_date))
//                $quote_end_date = $quote_end_date . ' ' . $quote_end_time . ':00';
//            else
//                $quote_end_date = '';
//
//
//            if (!empty($job_start_date))
//                $job_start_date = $job_start_date . ' ' . $start_time . ':00';
//            else
//                $job_start_date = '';
//
//            if (!empty($job_end_date))
//                $job_end_date = $job_end_date . ' ' . $end_time . ':00';
//            else
//                $job_end_date = '';
//
//            if (!empty($job_start_date))
//                $job_start_date = date('Y-m-d H:i:s', strtotime($job_start_date));
//
//            if (!empty($job_end_date))
//                $job_end_date = date('Y-m-d H:i:s', strtotime($job_end_date));
//
//
//            if (!empty($quote_end_date))
//                $quote_end_date = date('Y-m-d H:i:s', strtotime($quote_end_date));
//
//
//
//
//            $job_title = ucfirst($job_title);
//            $updated_by = $_SESSION['user_id'];
//            $time = time();
//            $rand = rand(999, 9999);
//            $job_ref_no = $time + $rand;
//
//            $job_title = addslashes($job_title);
//            $job_detail = addslashes($job_detail);
//            $client_order_no = addslashes($client_order_no);
//            //$job_review_point  = addslashes($job_review_point);
//            $recommend_action = addslashes($recommend_action);
//            // $job_quote_detail  = addslashes($job_quote_detail);     
//            $job_pdf_filename = $job_ref_no . ".pdf";
//
////      echo 'job_start_date = '.$job_start_date.',job_end_date = '.$job_end_date.',quote_end_date = '.$quote_end_date;
////      die;
//
//            $upQuery = "UPDATE jobs SET job_type_id = '$job_type_id', job_sub_type_id = '$job_sub_type_id',job_category_id = '$job_category_id',job_client_id = '$job_client_id', job_location_id = '$job_location_id',job_title = '$job_title',job_detail = '$job_detail', client_order_no = '$client_order_no', job_start_date = '$job_start_date',job_end_date = '$job_end_date',quote_end_date = '$quote_end_date',job_quote_detail = '$job_quote_detail',updated_by = '$updated_by'  WHERE id = '$job_id'";
//            // echo $upQuery;
//            // die;
//            $this->db->exec($upQuery);
//            //die('update');
//            if ($job_id) {
//                $this->db->exec("DELETE FROM jobs_supplier WHERE job_id = $job_id");
//                foreach ($job_supplier_ids as $supplier_id) {
//                    $this->db->exec("INSERT INTO jobs_supplier (job_id,supplier_id) VALUES($job_id,$supplier_id)");
//                }
//                if ($resend_email) {
//                    //Create PDF file of Job order
//                    // Require composer autoload
//                    require_once 'vendor/autoload.php';
//                    // Create an instance of the class:
//                    $mpdf = new \Mpdf\Mpdf();
//                    $view = new View;
//                    //fetch job detail                
//                    $select_records = "SELECT jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(location.name, ' ', location.surname) as location_name";
//                    $job_w_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id LEFT JOIN users as location ON jobs.job_location_id=location.id WHERE jobs.id=$job_id");
//
//                    $date = date('d-M-Y H:i');
//                    $job_detail_arr = array(
//                        'date' => $date,
//                        'job_title' => $job_title,
//                        'job_detail' => $job_detail,
//                        'job_start_date' => $job_start_date,
//                        'job_end_date' => $job_end_date,
//                        'quote_end_date' => $quote_end_date,
//                        'job_type' => $job_w_detail[0]['job_sub_type_title'],
//                        'job_category' => $job_w_detail[0]['job_category_name'],
//                        'job_location' => $job_w_detail[0]['location_name'],
//                    );
//                    ob_start();  // start output buffering
//                    $this->f3->set('base_url', $this->f3->get('base_folder'));
//                    $this->f3->set('job_detail_arr', $job_detail_arr);
//                    echo $view->render('jobs/job_order_pdf.php');
//                    $content = ob_get_clean(); // get content of the buffer and clean the buffer
//                    $mpdf->SetDisplayMode('fullpage');
//                    $mpdf->WriteHTML($content);
//                    $base_url = $this->f3->get('base_folder') . 'pdf_files/';
//                    // mkdir
//                    if (!file_exists($base_url)) {
//                        mkdir($base_url, 0777, true);
//                    }
//                    $file_path_name = $base_url . $job_pdf_filename;
//                    $mpdf->Output($file_path_name, 'F', true);
//                    //Send Job order to suppliers via email                
//                    $supp_records = $this->db->exec("SELECT users.ID,email,name FROM jobs_supplier LEFT JOIN users ON jobs_supplier.supplier_id=users.ID WHERE job_id=$job_id");
//                    // prd($supp_records);
//                    if (!empty($supp_records)) {
//                        foreach ($supp_records as $sup) {
//                            if (empty($sup['email']))
//                                continue;
//                            $supplier_email_address = $sup['email'];
//                            $email_msg = 'Hello ' . $sup['name'] . ',<br> Please find an attachment of the job detail.<br> If you are interested, Please reply us on the same email.<br><br>Thanks<br>' . $this->f3->get('company_name');
//                            $mail = new email_q($this->dbi);
//                            $mail->AddAddress($supplier_email_address);
//                            $mail->Subject = $this->f3->get('company_name') . " - Updated job detail";
//                            $mail->Body = $email_msg;
//                            //$mail->queue_message();
//                            $mail->AddAttachment($file_path_name);
//                            $mail->send();
//                            $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
//                        }
//                    }
//                }
//                $data = array(
//                    'code' => '200',
//                    'mesg' => 'Job updated successfully'
//                );
//                echo json_encode($data);
//                die;
//            } else {
//                $data = array(
//                    'code' => '404',
//                    'mesg' => 'Somthing went to wrong. please try again.'
//                );
//                echo json_encode($data);
//                die;
//            }
//        }
//        //Get sub job type
//        $job_type_id = 1;
//        $jobs_sub_type = $this->db->exec("SELECT id,title FROM jobs_sub_type WHERE job_sub_type_status=1 AND jobs_main_type_id=$job_type_id ORDER BY title ASC");
//        $this->f3->set('jobs_sub_type', $jobs_sub_type);
//        //Get jobs_category
//        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
//        $this->f3->set('jobs_category', $jobs_category);
//
//        //Get job location list
//        $job_location = $this->getLocationOfJobs();
//        $this->f3->set('job_location', $job_location);
//
//        //Get client list       
//        //Get job location list
//        $job_clients = $this->getClientsOfJobs();
//        $this->f3->set('job_clients', $job_clients);
//
//        //Get supplier list
//        $job_location_id = $job_detail[0]['job_location_id'];
//        $job_category_id = $job_detail[0]['job_category_id'];
//        $job_suppliers = $this->getSupplierOfJobs($job_category_id, $job_location_id);
//        $job_id = $job_detail[0]['id'];
//        $supplier_list = $this->db->exec("SELECT supplier_id FROM jobs_supplier WHERE job_id=$job_id");
//        $supplier_ids = array();
//        foreach ($supplier_list as $sup) {
//            $supplier_ids[] = $sup['supplier_id'];
//        }
//        $template = new Template;
//        $view = new View;
//        $this->f3->set('job_detail', $job_detail[0]);
//        $this->f3->set('base_url', $this->f3->get('base_url'));
//        $this->f3->set('page_title', 'Edit Job');
//        $this->f3->set('job_suppliers', $job_suppliers);
//        $this->f3->set('supplier_ids', $supplier_ids);
//        echo $template->render('header.htm');
//        echo $template->render('nav.htm');
//        echo $view->render('jobs/edit.php');
//        echo $template->render('footer.htm');
    }

    //allocation job
    function allocation($job_id = NULL) {
		
        $flder = $this->f3->get('download_folder') . "job_files/";
        $showflder = "job_files/";

        $select_records = "SELECT *";
        $job_detail = $this->db->exec("$select_records FROM jobs WHERE jobs.id=$job_id");
        $jobOldStatus = $job_detail[0]['job_status'];

        if (empty($job_detail[0]) || $job_detail[0]['job_status'] > 3) {
            //redirect to list page
            $this->f3->reroute('/jobs');
        }



        // prd($_POST);
        //Update Job   
        if (!empty($_POST) && !empty($job_detail[0])) {

            $error_mesg = '';
            
            $resend_email = $this->f3->get('POST.resend_email');
            $job_sub_type_title = $this->f3->get('POST.job_sub_type_title');

            $job_supplier_id = $this->f3->get('POST.job_supplier_id');
            $job_manager_email = $this->f3->get('POST.job_manager_email');
            $job_review_point = $this->f3->get('POST.job_review_point');
            $recommend_action = $this->f3->get('POST.recommend_action');
            $job_quote_detail = $this->f3->get('POST.job_quote_detail');
            $client_order_no = $this->f3->get('POST.client_order_no');
            $job_start_date = $this->f3->get('POST.job_start_date');
            $job_status = $this->f3->get('POST.job_status');
            $start_time = $this->f3->get('POST.start_time');
            $job_end_date = $this->f3->get('POST.job_end_date');
            $end_time = $this->f3->get('POST.end_time');
            $job_hours = 0; //$this->f3->get('POST.job_hours');
            $job_cost = $this->f3->get('POST.job_cost');

            // prd($_REQUEST);
//            echo $job_status;
//            die;
//             if ($quote_end_time == "") {
//                $quote_end_time = "00:00";
//            }
            if ($start_time == "") {
                $start_time = "00:00";
            }
            if ($end_time == "") {
                $end_time = "00:00";
            }


            /* Validation start */
            if (empty($job_supplier_id))
                $error_mesg .= 'Supplier is required<br>';
            if (!empty($job_start_date) && !empty($job_end_date) && $job_end_date < $job_start_date)
                $error_mesg .= 'Invalid job end date<br>';
            if (empty($job_start_date))
                $error_mesg .= 'Job start date is required<br>';
            if (empty($job_end_date))
                $error_mesg .= 'Job end date is required<br>';
            if (!empty($job_end_date) && empty($job_start_date))
                $error_mesg .= 'Job start date is required<br>';
            if (!empty($job_start_date) && !empty($job_end_date) && $start_time != '' && $start_time != '00:00' && $end_time != '' && $end_time != '00:00') {
                $job_start_date_time = $job_start_date . ' ' . $start_time . ':00';
                $job_end_date_time = $job_end_date . ' ' . $end_time . ':00';
                if ($job_end_date_time < $job_start_date_time)
                    $error_mesg .= 'Job start dateTime should be less than job end dateTime<br>';
            }


            // prd($job_sub_type_title);
            if (trim($job_sub_type_title) != "Emergency" || $job_status == 4) {
                //  die('test');
//                if ($job_hours <= 0) {
//                    $error_mesg .= 'Job hour should be more then 0 <br>';
//                }
                if ($job_cost <= 0) {
                    $error_mesg .= 'Job cost should be more then 0 <br>';
                }
            }


            if (!empty($error_mesg)) {
                $data = array(
                    'code' => '404',
                    'mesg' => $error_mesg
                );
                echo json_encode($data);
                die;
            }
            /* Validation end */
            if (!empty($job_start_date))
                $job_start_date = $job_start_date . ' ' . $start_time . ':00';
            else
                $job_start_date = '';

            if (!empty($job_end_date))
                $job_end_date = $job_end_date . ' ' . $end_time . ':00';
            else
                $job_end_date = '';

            if (!empty($job_start_date))
                $job_start_date = date('Y-m-d H:i:s', strtotime($job_start_date));

            if (!empty($job_end_date))
                $job_end_date = date('Y-m-d H:i:s', strtotime($job_end_date));

            $updated_by = $_SESSION['user_id'];

            $client_order_no = addslashes($client_order_no);
            $job_review_point = addslashes($job_review_point);
            $recommend_action = addslashes($recommend_action);
            $job_quote_detail = addslashes($job_quote_detail);

            $this->db->exec("UPDATE jobs SET job_supplier_id = '$job_supplier_id',client_order_no = '$client_order_no', job_review_point = '$job_review_point',job_manager_email='$job_manager_email',job_start_date = '$job_start_date',job_end_date = '$job_end_date',recommend_action = '$recommend_action',job_quote_detail = '$job_quote_detail',job_hours = '$job_hours',job_cost = '$job_cost',updated_by = '$updated_by', job_status='$job_status'  WHERE id = '$job_id'");
//            echo "UPDATE jobs SET job_supplier_id = '$job_supplier_id',client_order_no = '$client_order_no', job_review_point = '$job_review_point',job_start_date = '$job_start_date',job_end_date = '$job_end_date',recommend_action = '$recommend_action',job_quote_detail = '$job_quote_detail',job_hours = '$job_hours',job_cost = '$job_cost',updated_by = '$updated_by', job_status='$job_status'  WHERE id = '$job_id'";
//            die;
			/* print_r($job_detail); 
			print_r($job_detail[0]['job_title']); die('fgf'); */
			/* print_r($_FILES); 
			print_r($_POST); die('sdfd'); */
            $imageLoop = 12;
            for ($i = 1; $i <= $imageLoop; $i++) {
                if (isset($_POST['hdnImage' . $i])) {
                    //die("test");
                    $img = $_POST['hdnImage' . $i];
					if ($img) {
                        //$flder = $this->f3->get('download_folder') . "job_files/";
                        $folder = "$flder$job_id";
                        if (!file_exists($folder)) {
                            mkdir($folder, 0755, true);
                            //chmod($folder, 0755,true);
                        }
                        //save image
                        $img = str_replace(' ', '+', $img);
                        $img = substr($img, strpos($img, ",") + 1);
                        $data = base64_decode($img);
                        $img_name = basename($_POST['hdnFileName' . $i]);

                        $img_name = 'jobimage' . $i . '.jpg';
                        $file = "$folder/$img_name";
                        $success = file_put_contents($file, $data);
                        if ($success) {
//                                    $sql = "update jobs set job_image = '$img_name' where ID = $job_id";
//                                    $this->dbi->query($sql);
                        }
                    }
                }
				if($i >= 10 && $i <= 12){
					if (isset($_FILES['hdnImage' . $i]) && !empty($_FILES['hdnImage' . $i])) {
						$folder = "$flder$job_id";
						if (!file_exists($folder)) {
							mkdir($folder, 0755, true);
							//chmod($folder, 0755,true);
						}
						if($i==10 && !empty($_FILES['hdnImage' . $i]['name'])){
						$ext =  end((explode(".", $_FILES['hdnImage' . $i]['name'])));
						$img_name = "swms_document." .$ext;
						if(!empty($job_detail[0]['swms_document'])){
						$documentpath = $this->f3->get('download_folder') . "job_files/".$job_id."/".$job_detail[0]['swms_document'];
							if (file_exists($documentpath)) {
								@unlink($documentpath);
							}
						}
						$this->db->exec("UPDATE jobs SET swms_document = '$img_name' WHERE id = '$job_id'");
						}
						else if($i==11 && !empty($_FILES['hdnImage' . $i]['name'])){
						$ext =  end((explode(".", $_FILES['hdnImage' . $i]['name'])));
						$img_name = "msds_document." .$ext;
						if(!empty($job_detail[0]['msds_document'])){
						$documentpath = $this->f3->get('download_folder') . "job_files/".$job_id."/".$job_detail[0]['msds_document'];
							if (file_exists($documentpath)) {
								@unlink($documentpath);
							}
						}
						$this->db->exec("UPDATE jobs SET msds_document = '$img_name' WHERE id = '$job_id'");
						}
						else if($i==12 && !empty($_FILES['hdnImage' . $i]['name'])){
						$ext =  end((explode(".", $_FILES['hdnImage' . $i]['name'])));
						$img_name = "other_compliance_and_safety_document." .$ext;
						if(!empty($job_detail[0]['other_compliance_and_safety_document'])){
						$documentpath = $this->f3->get('download_folder') . "job_files/".$job_id."/".$job_detail[0]['other_compliance_and_safety_document'];
							if (file_exists($documentpath)) {
								@unlink($documentpath);
							}
						}
						$this->db->exec("UPDATE jobs SET other_compliance_and_safety_document = '$img_name' WHERE id = '$job_id'");
						}
						else{
							$img =$_FILES['hdnImage' . $i]['name'];
						}
						
						$uploadfile = $folder.'/' . basename($img_name);
						
						if (move_uploaded_file($_FILES['hdnImage' . $i]['tmp_name'], $uploadfile)) {
							//echo "File is valid, and was successfully uploaded.\n";
						}
						
					}
				
				}
            }

            if ($job_id) {
                if(($jobOldStatus != 3 && $job_status == 3) || $resend_email) {
                    $this->sendWorkOrder($job_id);
                }
                $data = array(
                    'code' => '200',
                    'mesg' => 'Job status updated successfully'
                );
                echo json_encode($data);
                die;
            } else {
                $data = array(
                    'code' => '404',
                    'mesg' => 'Something went to wrong. please try again.'
                );
                echo json_encode($data);
                die;
            }
        }
        //Get job detail
        $select_records = "SELECT jobs.*, jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(client.name, ' ', client.surname) as client_name, CONCAT(location.name, ' ', location.surname) as location_name, CONCAT(supplier.name, ' ', supplier.surname) as supplier_name";
        $job_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE jobs.id=$job_id");
        //prd($job_detail);
        $this->f3->set('job_detail', $job_detail[0]);

        $supplier_list = $this->db->exec("SELECT jobs_supplier.supplier_id,CONCAT(users.name, ' ', users.surname) as supplier_name FROM jobs_supplier LEFT JOIN users ON jobs_supplier.supplier_id=users.ID WHERE jobs_supplier.job_id=$job_id");
        $this->f3->set('supplier_list', $supplier_list);

        $template = new Template;
        $view = new View;

        if ($job_id) {
            $imageLoop = 12;
            for ($i = 1; $i <= $imageLoop; $i++) {
                ${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";

                if (file_exists(${'img_file' . $i})) {
                    ${'show_img_file' . $i} = "$showflder$job_id/jobimage$i.jpg";
                    ${'url_img_file' . $i} = $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt(${'show_img_file' . $i}));
                    $this->f3->set('img_file' . $i, ${'img_file' . $i});
                    //$this->f3->set('f3', $this->f3);
                    $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
                }
            }
        }

        $this->f3->set('base_url', $this->f3->get('base_url'));
        $this->f3->set('page_title', 'Job allocation');
        $this->f3->set('file_types', '.xlsx,.xls,.doc, .docx,.ppt, .pptx,.txt,.pdf');
		
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('jobs/allocation.php');
        echo $template->render('footer.htm');
    }

    //export jobs data
    function exportJobsData() {
        //die("test");
        $searchData = $this->f3->get('REQUEST.searchBox');

        $job_sub_type_id = $this->f3->get('REQUEST.job_sub_type_id');
        $job_category_id = $this->f3->get('REQUEST.job_category_id');
        $job_client_id = $this->f3->get('REQUEST.job_client_id');
        $job_location_id = $this->f3->get('REQUEST.job_location_id');
        $job_status = $this->f3->get('REQUEST.job_status');
        $quote_end_date = $this->f3->get('REQUEST.quote_end_date');
        $from_date = $this->f3->get('REQUEST.from_date');
        $to_date = $this->f3->get('REQUEST.to_date');

        $where_cond = "jobs.job_delete_status='1' AND jobs.job_type_id='1'";
        $and = ' AND ';
        if ($searchData) {
            $searchData = trim($searchData);
            $searchData = addslashes($searchData);
            $where_cond .= $and . '(jobs.job_title like "%' . $searchData . '%" OR jobs.job_detail like "%' . $searchData . '%" OR jobs.job_ref_no like "%' . $searchData . '%")';
        }
        if (!empty($quote_end_date)) {
            $quote_end_date = date('Y-m-d', strtotime($quote_end_date));
            //$quote_end_date = $quote_end_date . ' 00:00:00';
            $where_cond .= $and . "(jobs.quote_end_date like '%" . $quote_end_date . "%')";
        }

        if (!empty($from_date)) {
            $from_date = date('Y-m-d', strtotime($from_date));
            //$from_date = $from_date . ' 00:00:00';
            $where_cond .= $and . "(jobs.job_start_date like '%" . $from_date . "%')";
        }
        if (!empty($to_date)) {
            $to_date = date('Y-m-d', strtotime($to_date));
            //$to_date = $to_date . ' 23:59:59';
            $where_cond .= $and . "(jobs.job_end_date like '%" . $to_date . "%')";
        }

//        echo $where_cond;
//        die;
//        if (!empty($from_date)) {
//            $from_date = date('Y-m-d', strtotime($from_date));
//            $from_date = $from_date . ' 00:00:00';
//            $where_cond .= $and . "(jobs.quote_end_date >='" . $from_date . "')";
//        }
//        if (!empty($to_date)) {
//            $to_date = date('Y-m-d', strtotime($to_date));
//            $to_date = $to_date . ' 23:59:59';
//            $where_cond .= $and . "(jobs.quote_end_date <='" . $to_date . "')";
//        }
        if (!empty($job_sub_type_id))
            $where_cond .= $and . "(jobs.job_sub_type_id ='" . $job_sub_type_id . "')";
        if (!empty($job_category_id))
            $where_cond .= $and . "(jobs.job_category_id ='" . $job_category_id . "')";
        if (!empty($job_client_id))
            $where_cond .= $and . "(jobs.job_client_id ='" . $job_client_id . "')";
        if (!empty($job_location_id))
            $where_cond .= $and . "(jobs.job_location_id ='" . $job_location_id . "')";
        if ($job_status != '')
            $where_cond .= $and . "(jobs.job_status ='" . $job_status . "')";

        $select_records = "SELECT jobs_sub_type.id as job_sub_type_id, jobs_sub_type.color_code as color_code, jobs.id, jobs.job_title, jobs.job_ref_no, jobs.job_status, jobs.job_start_date, jobs.created_at,jobs.quote_end_date, jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(client.name, ' ', client.surname) as client_name, CONCAT(location.name, ' ', location.surname) as location_name, CONCAT(supplier.name, ' ', supplier.surname) as supplier_name";
        $job_list = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond  ORDER BY jobs.id DESC");
        //creating view for business user in datatable
        //  if(!empty($job_list)){
        $jobStatusArr = array('0' => 'Open', '1' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
        //generate PDF file 
        // Require composer autoload
        require_once 'vendor/autoload.php';
        // Create an instance of the class:     
        $mpdf = new \Mpdf\Mpdf();
        $view = new View;
        ob_start();  // start output buffering    
        $this->f3->set('job_list', $job_list);
        $this->f3->set('jobStatusArr', $jobStatusArr);
        echo $view->render('jobs/export_job_data_pdf.php');
        $content = ob_get_clean(); // get content of the buffer and clean the buffer
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($content);
        $file_name = time() . '.pdf';
        $mpdf->Output($file_name, 'D');
        //}else{
        // $this->f3->reroute('/jobs');  
        // }
    }

    function sendWorkOrder($job_id) {
        $select_records = "SELECT *";
        $job_details = $this->db->exec("$select_records FROM jobs WHERE jobs.id=$job_id");
        $job_detail = $job_details[0];

        //Create PDF file of Job order
        // Require composer autoload
        require_once 'vendor/autoload.php';
        // Create an instance of the class:
        $mpdf = new \Mpdf\Mpdf();
        $view = new View;
        //fetch job detail                
        $select_records = "SELECT jobs_sub_type.id as job_sub_type_id,jobs_sub_type.title as job_sub_type_title,jobs_sub_type.color_code as color_code, jobs_category.category_name as job_category_name, CONCAT(location.name, ' ', location.surname) as location_name";
        $job_w_detail = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id LEFT JOIN users as location ON jobs.job_location_id=location.id WHERE jobs.id=$job_id");
		//prd($job_detail);
        $date = date('d-M-Y H:i');
        
       
        $job_detail_arr = array(
            'date' => $date,           
            'id' => $job_detail['id'],
            'job_title' => $job_detail['job_title'],
            'job_detail' => $job_detail['job_detail'],
            'recommend_action' => $job_detail['recommend_action'],
            'job_ref_no' => $job_detail['job_ref_no'],           
            'job_quote_detail' => $job_detail['job_quote_detail'],
            'job_manager_email' => $job_detail['job_manager_email'],
            'job_start_date' => $job_detail['job_start_date'],
            'job_end_date' => $job_detail['job_end_date'],
            'quote_end_date' => $job_detail['quote_end_date'],
            'job_ref_no' => $job_detail['job_ref_no'],
            'job_hours' => $job_detail['job_hours'],
            'job_cost' => $job_detail['job_cost'],
            'color_code' => $job_w_detail[0]['color_code'],
            'job_sub_type_id' => $job_w_detail[0]['job_sub_type_id'],
            'job_supplier_id' => $job_detail['job_supplier_id'],
            'job_type' => $job_w_detail[0]['job_sub_type_title'],
            'job_category' => $job_w_detail[0]['job_category_name'],
            'job_location' => $job_w_detail[0]['location_name'],
            'swms_document' => $job_detail['swms_document'],
            'msds_document' => $job_detail['msds_document'],
            'other_compliance_and_safety_document' => $job_detail['other_compliance_and_safety_document'],
        );
        
       

		$flder = $this->f3->get('download_folder') . "job_files/";
		$showflder = "job_files/";
		$baseUrlOld = $this->f3->get('base_url_pdf');
        if ($job_id) {
            /* $imageLoop = 12;
            for ($i = 1; $i <= $imageLoop; $i++) {
                ${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";	
                if (file_exists(${'img_file' . $i})) {
                   ${'show_img_file' . $i} = "$showflder$job_id/jobimage$i.jpg";
				   ${'url_img_file' . $i} = $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt(${'show_img_file' . $i}));
					$this->f3->set('img_file' . $i, ${'img_file' . $i});
                    $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
                }
            } */
			$imageLoop = 12;
			for ($i = 1; $i <= $imageLoop; $i++) {
				${'img_file' . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";
				
				if (file_exists(${'img_file' . $i})) {
					${'show_img_file' . $i} =  "$showflder$job_id/jobimage$i.jpg";
					${'url_img_file' . $i} = $baseUrlOld.'/edge/downloads/'.${'show_img_file' . $i};
					$this->f3->set('img_file' . $i, ${'img_file' . $i});
					$this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
				}
			}
        }


		ob_start();  // start output buffering
        $this->f3->set('base_url', $this->f3->get('base_folder'));
        $this->f3->set('base_url_job', $this->f3->get('base_url_pdf'));
		
        $this->f3->set('job_detail_arr', $job_detail_arr);
        echo $view->render('jobs/job_work_order_pdf.php');
		//die;

        $content = ob_get_clean(); // get content of the buffer and clean the buffer
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($content);
        $base_url = $this->f3->get('base_folder') . 'pdf_files/';
        // mkdir
        if (!file_exists($base_url)) {
            mkdir($base_url, 0777, true);
        }

        $job_pdf_filename = "work_order" . $job_detail_arr['job_ref_no'] . ".pdf";

        $file_path_name = $base_url . $job_pdf_filename;
        $mpdf->Output($file_path_name, 'F', true);
        //Send Job order to suppliers via email             


        $supp_records = $this->db->exec("SELECT users.ID,email,name FROM jobs LEFT JOIN users ON jobs.job_supplier_id=users.ID WHERE jobs.id=$job_id");
        // prd($supp_records);
        if (!empty($supp_records)) {
            foreach ($supp_records as $sup) {
                if (empty($sup['email']))
                    continue;
                $supplier_email_address = $sup['email'];

                $email_msg = 'Hello ' . $sup['name'] . ',<br> Please find an attachment of the job  Work Order detail.<br><br>Thanks<br>' . $this->f3->get('company_name');

                $mail = new email_q($this->dbi);
                $mail->AddAddress($supplier_email_address);
                $mail->Subject = $this->f3->get('company_name') . " -  job  Work detail";
                $mail->Body = $email_msg;
                //$mail->queue_message();
                $mail->AddAttachment($file_path_name);
                $mail->send();
                $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
            }


            if (trim($job_detail['job_manager_email']) != "") {
                $supplier_email_address = trim($job_detail['job_manager_email']);
                $email_msg = 'Hello ,<br> Please find an attachment of the job  Work Order detail.<br><br>Thanks<br>' . $this->f3->get('company_name');
                $mail = new email_q($this->dbi);
                $mail->AddAddress($supplier_email_address);
                $mail->Subject = $this->f3->get('company_name') . " -  job  Work detail";
                $mail->Body = $email_msg;
                //$mail->queue_message();
                $mail->AddAttachment($file_path_name);
                $mail->send();
                // $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
            }
        }
    }

    function job_document_download() {


        $fileName = $_REQUEST['file_name'];
        $jobId = $_REQUEST['job_id'];
        $flder = $this->f3->get('download_folder') . "job_files/";
        $showflder = "job_files/";

        $document_file = "$flder$jobId/$fileName";
        $documentUrl = $this->f3->get('base_url') . "/" . "$showflder$jobId/$fileName";

        if ($fileName && $jobId) {

            if (file_exists($document_file)) {

                //Define header information
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: 0");
                header('Content-Disposition: attachment; filename="' . basename($document_file) . '"');
                header('Content-Length: ' . filesize($document_file));
                header('Pragma: public');

                //Clear system output buffer
                flush();

                //Read the size of the file
                readfile($document_file);
                //Terminate from the script
                die("test1");
            } else {
                echo "File does not exist.";
            }
        } else {
            echo "Filename is not defined.";
        }
    }
	//export jobs data
    function exportCompletedJobsData() {
        /* print_r($_REQUEST['complete_email']);
		die("test"); */
        $searchData = $this->f3->get('REQUEST.searchBox');

        $job_sub_type_id = $this->f3->get('REQUEST.job_sub_type_id');
        $job_category_id = $this->f3->get('REQUEST.job_category_id');
        $job_client_id = $this->f3->get('REQUEST.job_client_id');
        $job_location_id = $this->f3->get('REQUEST.job_location_id');
        $job_status = $this->f3->get('REQUEST.job_status');
        $quote_end_date = $this->f3->get('REQUEST.quote_end_date');
        $from_date = $this->f3->get('REQUEST.from_date');
        $to_date = $this->f3->get('REQUEST.to_date');

        $where_cond = "jobs.job_delete_status='1' AND jobs.job_type_id='1'";
        $and = ' AND ';
        if ($searchData) {
            $searchData = trim($searchData);
            $searchData = addslashes($searchData);
            $where_cond .= $and . '(jobs.job_title like "%' . $searchData . '%" OR jobs.job_detail like "%' . $searchData . '%" OR jobs.job_ref_no like "%' . $searchData . '%")';
        }
        if (!empty($quote_end_date)) {
            $quote_end_date = date('Y-m-d', strtotime($quote_end_date));
            //$quote_end_date = $quote_end_date . ' 00:00:00';
            $where_cond .= $and . "(jobs.quote_end_date like '%" . $quote_end_date . "%')";
        }

        if (!empty($from_date)) {
            $from_date = date('Y-m-d', strtotime($from_date));
            //$from_date = $from_date . ' 00:00:00';
            $where_cond .= $and . "(jobs.job_start_date like '%" . $from_date . "%')";
        }
        if (!empty($to_date)) {
            $to_date = date('Y-m-d', strtotime($to_date));
            //$to_date = $to_date . ' 23:59:59';
            $where_cond .= $and . "(jobs.job_end_date like '%" . $to_date . "%')";
        }

//        echo $where_cond;
//        die;
//        if (!empty($from_date)) {
//            $from_date = date('Y-m-d', strtotime($from_date));
//            $from_date = $from_date . ' 00:00:00';
//            $where_cond .= $and . "(jobs.quote_end_date >='" . $from_date . "')";
//        }
//        if (!empty($to_date)) {
//            $to_date = date('Y-m-d', strtotime($to_date));
//            $to_date = $to_date . ' 23:59:59';
//            $where_cond .= $and . "(jobs.quote_end_date <='" . $to_date . "')";
//        }
        if (!empty($job_sub_type_id))
            $where_cond .= $and . "(jobs.job_sub_type_id ='" . $job_sub_type_id . "')";
        if (!empty($job_category_id))
            $where_cond .= $and . "(jobs.job_category_id ='" . $job_category_id . "')";
        if (!empty($job_client_id))
            $where_cond .= $and . "(jobs.job_client_id ='" . $job_client_id . "')";
        if (!empty($job_location_id))
            $where_cond .= $and . "(jobs.job_location_id ='" . $job_location_id . "')";
        if ($job_status != '')
            $where_cond .= $and . "(jobs.job_status ='" . $job_status . "')";

        $select_records = "SELECT jobs.*,jobs_sub_type.id as job_sub_type_id, jobs_sub_type.color_code as color_code, jobs.id, jobs.job_title, jobs.job_ref_no, jobs.job_status, jobs.job_start_date, jobs.created_at,jobs.quote_end_date, jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(client.name, ' ', client.surname) as client_name, CONCAT(location.name, ' ', location.surname) as location_name, CONCAT(supplier.name, ' ', supplier.surname) as supplier_name";
        $job_list = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond  ORDER BY jobs.id DESC LIMIT 20");
        //print_r($job_list); die;
		//creating view for business user in datatable
        //  if(!empty($job_list)){
        //$jobStatusArr = array('0' => 'Open', '1' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
		$complete_email=$_REQUEST['complete_email'];
		$this->sendCompletedWorkOrder($job_list,$job_client_id,$complete_email);
		echo 'Report Send Successfully'; die;
		
        //generate PDF file 
        // Require composer autoload
        /* require_once 'vendor/autoload.php';
        // Create an instance of the class:     
        $mpdf = new \Mpdf\Mpdf();
        $view = new View;
        ob_start();  // start output buffering    
        $this->f3->set('job_list', $job_list);
        $this->f3->set('jobStatusArr', $jobStatusArr);
        echo $view->render('jobs/export_job_data_pdf.php');
		
        $content = ob_get_clean(); // get content of the buffer and clean the buffer
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($content);
        $file_name = time() . '.pdf';
        $mpdf->Output($file_name, 'D'); */
        
    }
	
	function sendCompletedWorkOrder($job_list,$job_client_id,$complete_email) {
		//print_r($job_list); die('sdfsdf');
		require_once 'vendor/autoload.php';
        // Create an instance of the class:
        $mpdf = new \Mpdf\Mpdf();
        $view = new View;
        //fetch job detail 
		$job_detail_arr = array();
		$flder = $this->f3->get('download_folder') . "job_files/";
		$showflder = "job_files/";
		$baseUrlOld = $this->f3->get('base_url_pdf');		
		if(!empty($job_list)){
			foreach($job_list as $key => $job_detail){
				#print_r($job_detail);die;
				$date = date('d-M-Y H:i');
				
			   $job_detail_arr[] = array(
					'date' => $date,           
					'id' => $job_detail['id'],
					'job_title' => $job_detail['job_title'],
					'job_detail' => $job_detail['job_detail'],
					'recommend_action' => $job_detail['recommend_action'],
					'job_ref_no' => $job_detail['job_ref_no'],           
					'job_quote_detail' => $job_detail['job_quote_detail'],
					'job_manager_email' => $job_detail['job_manager_email'],
					'job_start_date' => $job_detail['job_start_date'],
					'job_end_date' => $job_detail['job_end_date'],
					'quote_end_date' => $job_detail['quote_end_date'],
					'job_ref_no' => $job_detail['job_ref_no'],
					'job_hours' => $job_detail['job_hours'],
					'job_cost' => $job_detail['job_cost'],
					'color_code' => $job_detail['color_code'],
					'job_sub_type_id' => $job_detail['job_sub_type_id'],
					'job_supplier_id' => $job_detail['job_supplier_id'],
					'job_type' => $job_detail['job_sub_type_title'],
					'job_category' => $job_detail['job_category_name'],
					'job_location' => $job_detail['location_name'],
					'job_review_point' => $job_detail['job_review_point'],
					'swms_document' => $job_detail['swms_document'],
					'msds_document' => $job_detail['msds_document'],
					'other_compliance_and_safety_document' => $job_detail['other_compliance_and_safety_document'],
				);
				$job_id=$job_detail['id'];
				if ($job_id) {
				   $imageLoop = 12;
					for ($i = 1; $i <= $imageLoop; $i++) {
						${'img_file'.$key . $i} = $flder . $job_id . "/jobimage" . $i . ".jpg";
						
						if (file_exists(${'img_file'.$key  . $i})) {
							${'show_img_file'.$key . $i} =  "$showflder$job_id/jobimage$i.jpg";
							${'url_img_file'.$key . $i} = $baseUrlOld.'/edge/downloads/'.${'show_img_file'.$key . $i};
							$this->f3->set('img_file'.$key . $i, ${'img_file'.$key . $i});
							$this->f3->set('url_img_file'.$key . $i, ${'url_img_file'.$key . $i});
							/* echo 'img_file'.$key . $i;
							echo 'url_img_file'.$key.$i; die; */
						}
					}
				}
				/* echo '<pre>';
				print_r($job_detail);
				echo '</pre>'; */
			}
		}
		/* echo '<pre>';
		print_r($job_detail_arr);
		echo '</pre>';
		die('sdfds'); */
        
       

		
        


		ob_start();  // start output buffering
        $this->f3->set('base_url', $this->f3->get('base_folder'));
        $this->f3->set('base_url_job', $this->f3->get('base_url_pdf'));
		
        $this->f3->set('job_detail_arr1', $job_detail_arr);
        echo $view->render('jobs/job_complete_work_order_pdf.php');
		//die;

        $content = ob_get_clean(); // get content of the buffer and clean the buffer
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($content);
        $base_url = $this->f3->get('base_folder') . 'pdf_files/';
        // mkdir
        if (!file_exists($base_url)) {
            mkdir($base_url, 0777, true);
        }

        $job_pdf_filename = "work-complete_order" . time() . ".pdf";

        $file_path_name = $base_url . $job_pdf_filename;
        $mpdf->Output($file_path_name, 'F', true);
        //Send Job order to suppliers via email             


        $supp_records = $this->db->exec("SELECT users.ID,email,name FROM users  WHERE users.id=$job_client_id");
		
        // prd($supp_records);
        if (!empty($supp_records)) {
            foreach ($supp_records as $sup) {
                if (empty($sup['email']))
                    continue;
                //$supplier_email_address = $sup['email'];
                //$supplier_email_address = 'hemendra.singh@dotsquares.com';
                $supplier_email_address = $complete_email;

                $email_msg = 'Hello ' . $sup['name'] . ',<br> Please find an attachment of the Job Completed Report.<br><br>Thanks<br>' . $this->f3->get('company_name');

                $mail = new email_q($this->dbi);
                $mail->AddAddress($supplier_email_address);
                $mail->Subject = $this->f3->get('company_name') . " -  Job Completed Report";
                $mail->Body = $email_msg;
                //$mail->queue_message();
                $mail->AddAttachment($file_path_name);
                //$mail->queue_message();
                $mail->send();
                $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
            }


            /* if (trim($job_detail['job_manager_email']) != "") {
                $supplier_email_address = trim($job_detail['job_manager_email']);
                $email_msg = 'Hello ,<br> Please find an attachment of the job  Work Order detail.<br><br>Thanks<br>' . $this->f3->get('company_name');
                $mail = new email_q($this->dbi);
                $mail->AddAddress($supplier_email_address);
                $mail->Subject = $this->f3->get('company_name') . " -  job  Work detail";
                $mail->Body = $email_msg;
                //$mail->queue_message();
                $mail->AddAttachment($file_path_name);
                $mail->send();
                // $mail->job_mail_logs($job_id, $sup['ID'], $job_pdf_filename, $supplier_email_address);
            } */
        }
    }
    
    //export jobs data
    function exportRosterData() {
        //die("test");
//        $searchData = $this->f3->get('REQUEST.searchBox');
//
//        $job_sub_type_id = $this->f3->get('REQUEST.job_sub_type_id');
//        $job_category_id = $this->f3->get('REQUEST.job_category_id');
//        $job_client_id = $this->f3->get('REQUEST.job_client_id');
//        $job_location_id = $this->f3->get('REQUEST.job_location_id');
//        $job_status = $this->f3->get('REQUEST.job_status');
//        $quote_end_date = $this->f3->get('REQUEST.quote_end_date');
//        $from_date = $this->f3->get('REQUEST.from_date');
//        $to_date = $this->f3->get('REQUEST.to_date');
//
//        $where_cond = "jobs.job_delete_status='1' AND jobs.job_type_id='1'";
//        $and = ' AND ';
//        if ($searchData) {
//            $searchData = trim($searchData);
//            $searchData = addslashes($searchData);
//            $where_cond .= $and . '(jobs.job_title like "%' . $searchData . '%" OR jobs.job_detail like "%' . $searchData . '%" OR jobs.job_ref_no like "%' . $searchData . '%")';
//        }
//        if (!empty($quote_end_date)) {
//            $quote_end_date = date('Y-m-d', strtotime($quote_end_date));
//            //$quote_end_date = $quote_end_date . ' 00:00:00';
//            $where_cond .= $and . "(jobs.quote_end_date like '%" . $quote_end_date . "%')";
//        }
//
//        if (!empty($from_date)) {
//            $from_date = date('Y-m-d', strtotime($from_date));
//            //$from_date = $from_date . ' 00:00:00';
//            $where_cond .= $and . "(jobs.job_start_date like '%" . $from_date . "%')";
//        }
//        if (!empty($to_date)) {
//            $to_date = date('Y-m-d', strtotime($to_date));
//            //$to_date = $to_date . ' 23:59:59';
//            $where_cond .= $and . "(jobs.job_end_date like '%" . $to_date . "%')";
//        }
//
////        echo $where_cond;
////        die;
////        if (!empty($from_date)) {
////            $from_date = date('Y-m-d', strtotime($from_date));
////            $from_date = $from_date . ' 00:00:00';
////            $where_cond .= $and . "(jobs.quote_end_date >='" . $from_date . "')";
////        }
////        if (!empty($to_date)) {
////            $to_date = date('Y-m-d', strtotime($to_date));
////            $to_date = $to_date . ' 23:59:59';
////            $where_cond .= $and . "(jobs.quote_end_date <='" . $to_date . "')";
////        }
//        if (!empty($job_sub_type_id))
//            $where_cond .= $and . "(jobs.job_sub_type_id ='" . $job_sub_type_id . "')";
//        if (!empty($job_category_id))
//            $where_cond .= $and . "(jobs.job_category_id ='" . $job_category_id . "')";
//        if (!empty($job_client_id))
//            $where_cond .= $and . "(jobs.job_client_id ='" . $job_client_id . "')";
//        if (!empty($job_location_id))
//            $where_cond .= $and . "(jobs.job_location_id ='" . $job_location_id . "')";
//        if ($job_status != '')
//            $where_cond .= $and . "(jobs.job_status ='" . $job_status . "')";
//
//        $select_records = "SELECT jobs_sub_type.id as job_sub_type_id, jobs_sub_type.color_code as color_code, jobs.id, jobs.job_title, jobs.job_ref_no, jobs.job_status, jobs.job_start_date, jobs.created_at,jobs.quote_end_date, jobs_sub_type.title as job_sub_type_title, jobs_category.category_name as job_category_name, CONCAT(client.name, ' ', client.surname) as client_name, CONCAT(location.name, ' ', location.surname) as location_name, CONCAT(supplier.name, ' ', supplier.surname) as supplier_name";
//        $job_list = $this->db->exec("$select_records FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond  ORDER BY jobs.id DESC");
            
//            if(!$division_id) {
//                $division_id = 0;
//            }
        
         session_start();
        
            $division_id = 0;
            $rosterIngStateIds = $this->rosteringStateIds($_SESSION['user_id']);

            $loginUserDivisions = $this->get_divisions($_SESSION['user_id'], 0, 1);
            //prd($loginUserDivisions);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);

            if (!$loginUserDivisionsStr) {
                $loginUserDivisionsStr = 0;
            }
            
       //  prd($_REQUEST);

            $report_start_date = strtotime($_REQUEST['report-start-date']);
            $report_start_time = strtotime($_REQUEST['report-start-time']);
            $report_search_start_date = date('Y-m-d',$report_start_date);
             $report_search_start_time = $report_start_time;
             
             if(!$report_search_start_time){
                 $report_search_start_time = "00:00:00";
             }
             
             
             
             $report_search_start_cmp_time = $report_search_start_date." ".$report_search_start_time;
            
            $report_end_date = strtotime($_REQUEST['report-end-date']);
            $report_end_time = strtotime($_REQUEST['report-end-time']);
            $report_search_end_date = date('Y-m-d',$report_end_date);
             $report_search_end_time = $report_end_time;
             if(!$report_search_end_time){
                 $report_search_end_time = "00:00:00";
             }
             
             $report_search_end_cmp_time = $report_search_end_date." ".$report_search_end_time;
            
             
            
            if($_REQUEST['location_id']){
                 $locationId = $_REQUEST['location_id'];
                 $locationQuery =  "and location.id = '".$locationId."'";
            }else{
               $locationQuery = ""; 
            }
            
            if($_REQUEST['employee_id']){
                 $employeeId = $_REQUEST['employee_id'];
                 $employeeQuery =  "and emp.id = '".$employeeId."'";
            }else{
               $employeeQuery = ""; 
            }
            
            if($_REQUEST['clt_id']){
                 $cltId = $_REQUEST['clt_id'];
                    $clientQuery =  "and clt.id = '".$cltId."'";
            }else{
                    $clientQuery = ""; 
            }
            
          
         
            
            
            
            $userAllowedSiteIds = $this->getUserSiteIdsByUserId($_SESSION['user_id']);
          
            // prd($_REQUEST);

            if ($userAllowedSiteIds != "all") {
                $userSiteAllowedconditon = " and location.id in (" . $userAllowedSiteIds . ")";
                $filterAllowedSiteIds = "and rst.site_id in (" . $userAllowedSiteIds . ")";
            }else{
                $filterAllowedSiteIds = "";
            }
            
//            echo $filterAllowedSiteIds;
//            die;


            //and location.state in (" . $rosterIngStateIds . ") 
// and rst.division_id = 108 

            $reportsql = "
            SELECT DATE(rt.start_time_date) AS DATE, CONCAT('', location.name, ' ', location.surname, '') as 'Location',
CONCAT('', emp.name, ' ', emp.surname, '') as 'Employee',
CONCAT('', clt.name, ' ', clt.surname, '') as 'Client',
(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rt.start_time_date, rt.finish_time_date)) / 3600, 2))) assigned_hours,
rts.start_time_date,rts.finish_time_date,IF(rts.start_time_date != '0000-00-00 00:00:00' AND rts.finish_time_date != '0000-00-00 00:00:00',(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(rts.start_time_date, rts.finish_time_date)) / 3600, 2))),0) completed_hours
FROM roster_times_staff rts
LEFT JOIN users AS emp ON emp.ID = rts.staff_id
LEFT Join roster_times rt ON rt.id = rts.roster_time_id 
LEFT JOIN rosters rst ON rst.id = rt.roster_id
left join users as location on location.id = rst.site_id
LEFT JOIN associations ass1 ON ass1.child_user_id = location.ID AND ass1.association_type_id = 1
LEFT JOIN users AS clt ON clt.ID = ass1.parent_user_id
WHERE 1 ".$locationQuery." ".$employeeQuery." ".$clientQuery." and emp.ID IS NOT NULL AND location.ID IS NOT NULL AND rst.division_id in (108,2100,2102,2103,2104)
and rt.start_time_date >= '".$report_search_start_cmp_time."' AND rt.start_time_date <=  '".$report_search_end_cmp_time."' $filterAllowedSiteIds and rst.division_id in (" . $loginUserDivisionsStr . ")
ORDER BY rt.start_time_date desc
        ";
            
            //echo $reportsql;
            //die;
            
           
            
            
            $roster_list = $this->db->exec($reportsql);
            
           // prd($roster_list);
        
        //creating view for business user in datatable
        //  if(!empty($job_list)){
        //$jobStatusArr = array('0' => 'Open', '1' => 'Cancelled', '2' => 'Pending', '3' => 'Inprogress', '4' => 'Completed');
        //generate PDF file 
        // Require composer autoload
        require_once 'vendor/autoload.php';
        // Create an instance of the class:     
        $mpdf = new \Mpdf\Mpdf();
        $view = new View;
        ob_start();  // start output buffering    
        $this->f3->set('roster_list', $roster_list);
        
        //$this->f3->set('jobStatusArr', $jobStatusArr);
        echo $view->render('jobs/export_roster_data_pdf.php');
        $content = ob_get_clean(); // get content of the buffer and clean the buffer
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($content);
        $file_name = time() . '.pdf';
        $mpdf->Output($file_name, 'D');
        //}else{
        // $this->f3->reroute('/jobs');  
        // }
    }
    function rosterStartingIn($minute, $stateCode) {
        $timeDifferences = [
            'QLD' => 60, // QLD: add 1 hour
            'NT' => 90,  // NT: add 1 hour 30 minutes
            'WA' => 180, // WA: add 3 hours
            'SA' => 30,  // SA: add 30 minutes
            'NSW' => 0,  // NSW: no adjustment
            'VIC' => 0,  // VIC: no adjustment
            'ACT' => 0,  // ACT: no adjustment
            'TAS' => 0   // TAS: no adjustment
        ];
    
        $currentTime = new DateTime('now', new DateTimeZone('Australia/Sydney')); // Default to NSW time
    
        // Get the roster starting time
        $rosterTime = clone $currentTime;
        $rosterTime->modify("+$minute minutes");
    
        // If the current time is past the roster starting time, return '0 Hour 0 Minute'
        if ($currentTime > $rosterTime) {
            return '0 Hour 0 Minute';
        }
    
        if ($stateCode === 'QLD') {
            if ($minute > 119) {
                $timeDifference = $timeDifferences[$stateCode];
                $currentTime->modify("+$timeDifference minutes");
    
                $hour = ceil($minute / 60);
                $minute = $minute % 60;
    
                $futureTime = clone $currentTime;
                $futureTime->modify("+$hour hours $minute minutes");
    
                $interval = $futureTime->diff($currentTime);
                return $interval->format('%h Hour %i Minute');
            } elseif ($minute < 59) {
                $futureTime = clone $currentTime;
                $futureTime->modify("+$minute minutes");
                $interval = $futureTime->diff($currentTime);
    
                // Calculate the remaining minutes and add an hour if not previously added
                $remainingMinutes = $interval->i;
                if ($remainingMinutes < 59) {
                    $futureTime->modify("+1 hour");
                    $interval = $futureTime->diff($currentTime);
                }
                return $interval->format('%h Hour %i Minute');
            }
        }
    
        if ($stateCode === 'WA') {
            if ($minute > 119) {
                // For more than 2 hours starting time, adjust time accordingly
                $timeDifference = $timeDifferences[$stateCode];
                $hour = floor($minute / 60);
                $minute = $minute % 60;
                $currentTime->modify("+$timeDifference minutes");
                $futureTime = clone $currentTime;
                $futureTime->modify("+$hour hours $minute minutes");
                $interval = $futureTime->diff($currentTime);
                return $interval->format('%h Hour %i Minute');
            } elseif ($minute < 59) {
                // For less than 1 hour starting time, add 3 hours
                $futureTime = clone $currentTime;
                $futureTime->modify("+3 hours");
                $interval = $futureTime->diff($currentTime);
                return $interval->format('%h Hour %i Minute');
            }
        }
    
        // The rest of the states follow the standard logic
        if (isset($timeDifferences[$stateCode])) {
            $timeDifference = $timeDifferences[$stateCode];
            $currentTime->modify("+$timeDifference minutes");
        }
    
        $futureTime = clone $currentTime;
        $futureTime->modify("+$minute minutes");
        $interval = $futureTime->diff($currentTime);
    
        return $interval->format('%h Hour %i Minute');
    }
    
    



}

?>