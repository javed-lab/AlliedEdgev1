<?php

class ToolboxAlliedAssignController extends Controller {

    protected $f3;
    protected $db;

    function __construct($f3) {
        $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $this->f3 = $f3;
        $this->db_init();
    }

    /*
      All job list
     */

    function index() {
        
        $template = new Template;
        $view = new View;

        if ($_REQUEST['course_id'] == 0) {
            $this->f3->reroute('/AlliedToolbox/Courses');
            $course_id = 0;
        } else {
            $course_id = $_REQUEST['course_id'];
        }

//       $select_records = "SELECT *";
//       $lessonDetail = $this->db->exec("$select_records FROM training_allied_lessons WHERE training_allied_lessons.id=$lesson_id");
//        $course_id = $lessonDetail['course_id'];

        $dataDetailQuery = "SELECT ta_courses.`id`, ta_courses.`title`,division.`item_name` division_name";

        $dataDetailQuery .= " FROM training_allied_courses ta_courses"
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE ta_courses.`id` = '" . $course_id . "'";

//        echo $dataDetailQuery;
//        die;

        $dataDetail = $this->db->exec($dataDetailQuery);
//            
//
        // prd($dataDetail);
        $this->f3->set('dataDetail', $dataDetail);
        $this->f3->set('course_id', $course_id);
        $this->f3->set('lesson_id', $lesson_id);
        $this->f3->set('page_title', 'Training List');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedTraining/AssignTraining/");
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/assign/index.php');
        echo $template->render('footer.htm');
    }

    /*
      Fetch job list via ajax
     */

    function getListAjax() {
        $order_by = array();
        $length = $this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');

        if (empty($length)) {
            $length = 10;
            $start = 0;
        }
        $columnData = array(
            'tar.`created_at`',
            'tar.`training_title`',
            'clt.`name`',
            'site.`name`',
            'ta_course.`title`',
            'tar.`start_date`',
            'tar.`end_date`'
        );

        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

        $course_id = $_REQUEST['course_id'];

        $where_cond = " tar.course_id = '" . $course_id . "' ";

        $where_cond .= " and tar.status != 2 and ta_courses.status != 2 ";

//         $where_cond .= " and ta_questions.status != 2 ";
//         if($_REQUEST['lession_id']){
//              $where_cond .= " and ta_questions.lesson_id = ".$_REQUEST['lession_id'];
//         }else{
//              $where_cond .= " and ta_questions.lesson_id = 0 ";
//         }
//         

        if ($_REQUEST['searchBox']) {
            $where_cond .= " and  tar.training_title like '%" . $_REQUEST['searchBox'] . "%'";
        }

        $select_records = "SELECT tar.id,concat(clt.name,'',clt.surname) clt_name,concat(site.name,'',site.surname) site_name,tar.training_title,ta_courses.`id` course_id, ta_courses.`title` course_title,division.`item_name` division_name,tar.start_date,tar.end_date";
        $select_num_rows = "SELECT count(tar.id) as total_records";

        $afterFrom = " FROM training_allied_rules tar"
                . " left join users clt on clt.id = tar.client_id"
                . " left join users site on site.id = tar.site_id"
                . " LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=tar.course_id  "           
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE $where_cond  ORDER BY $order_by $sort_order";

        $listPaging = " LIMIT $length OFFSET $start";

        $countQuery = $select_num_rows . $afterFrom;
        $listQuery = $select_records . $afterFrom . $listPaging;
        //echo $listQuery; die;


        $total_records = $this->db->exec($countQuery);

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $record_list = array();
//        echo $listQuery;
//        die;
        if ($total_data > 0) {
            $record_list = $this->db->exec($listQuery);

//            $record_list = $this->db->exec("$select_records FROM training_allied_courses ta_courses "
//                    . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
//                    . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
//                    . " WHERE $where_cond  ORDER BY $order_by $sort_order LIMIT $length OFFSET $start");
        }

        // prd($record_list);

        $jsonArray = array(
            'draw' => $this->f3->get('POST.draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_data,
            'data' => array(),
        );
        //creating view for business user in datatable
        if (!empty($record_list)) {
            $StatusArr = array('0' => 'Inactive', '1' => 'Active', '2' => 'Deleted');
            $base_url = $this->f3->get('base_url');
            foreach ($record_list as $key => $val) {
                // $allocation_link = $base_url . '/job-allocation?job-id=' . $val['id'];
                $user_assign_link = $base_url . "/AlliedTraining/AssignTraining/UserAssign?training_id=" . $val['id'];
                $user_assigned_link = $base_url . "/AlliedTraining/AssignTraining/trainingUser?training_id=" . $val['id'];
                $edit_link = $base_url . '/AlliedTraining/EditTraining?training_id='.$val['id'];
                $delete_link = "javaScript:delete_training(" . $val['id'] . ")";
//                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
//                
                //$user_assign = $user_assign_link;

                $user_assign = '<a title="Assign User" class="black-a" href="' . $user_assign_link . '">AssignUser </a>';
                $training_user = '<a title="Assign User" class="black-a" href="' . $user_assigned_link . '"> Users Assigned </a>';

//                $question ='';//'<a title="Edit" class="green-a" href="' . $question_link . '">Question</a>';
                $edit = '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
                $delete = '<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                //$view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                // 'action' => $question .' '. $edit . ' ' . $delete,
                $allocation = '';
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'training_title' => $val['training_title'],
                    'client'    => $val['clt_name'] ? $val['clt_name'] : "All Client",
                    'location'  => $val['site_name'] ? $val['site_name'] : "All Site",                    
                    'course_title' => $val['course_title'],
                    'start_date' => $val['start_date'],
                    'end_date' => $val['end_date'],
                    'action' => $user_assign . '&nbsp; &nbsp;' . $training_user . '&nbsp; &nbsp;' . $edit .'&nbsp; &nbsp;' . $delete,
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }

    /*
     * Create a new Lessons
     * 
     * 
     */

    function userAssign() {
        
        $training_id = $_REQUEST['training_id'];
        if($training_id < 1) {
            die;
        }
         //prd($_REQUEST);
        if($_REQUEST['max_level_id'] > 0) {
           // prd($_REQUEST);
            $trainingRuleId = $training_id;
            $trainingRuleDetail = $this->trainingRuleDetail($trainingRuleId);
          //  prd($trainingRuleDetail);
            if ($trainingRuleDetail) {
                $training_rule_id = $trainingRuleId;
                $division_id = $trainingRuleDetail['division_id'];
                $course_id = $trainingRuleDetail['course_id'];
                $start_date = $trainingRuleDetail['start_date'];
                $end_date = $trainingRuleDetail['end_date'];
                $client_id = 0;
                $min_level = $_REQUEST['min_level_id'];
                $max_level = $_REQUEST['max_level_id'];

                $insertRoleQuery = "INSERT INTO `training_allied_rules_roles` (`training_rule_id`,`client_id`,`min_level`,`max_level`)"
                        . " VALUES('$trainingRuleId','$client_id','$min_level','$max_level')";

                $this->db->exec($insertRoleQuery);

                $training_rule_roe_id = $this->db->lastInsertId();

                $userLists = $this->trainingAssignUser($trainingRuleId, $min_level, $max_level);
                
//prd($userLists);

                $lessonList = $this->lessonList($course_id);

                //prd($lessonList);

                foreach ($lessonList as $lesson) {
                    

                    $insertRec = "";
                    foreach ($userLists as $key => $userList) {
                        // prd($key);
                        if ($key == 0) {
                            $commaAdd = " ";
                        } else {
                            $commaAdd = ",";
                        }
                        $insertRec .= $commaAdd . '("' . $training_rule_id . '","' . $lesson['id'] . '","' . $userList['userId'] . '","' . $start_date . '","' . $end_date . '")';
                    }
                    if ($userLists) {
                        $insertQuery = "INSERT IGNORE INTO training_allied_training_user 
                    (training_rule_id, lesson_id, user_id,start_date,end_date) 
                    VALUES 
                    $insertRec";
                    }
                     $this->db->exec($insertQuery);
                }
            }
          //  if($insertQuery != "")
          
           
             
            //$trainingRuleId = $_REQUEST[];
            //prd($userList);
//            if($client_id == 0){
//                $clientUser = "";
//            }


            $this->f3->reroute('/AlliedTraining/AssignTraining?course_id=' . $course_id);
        }
           // prd($_REQUEST);
        //echo $type; die;
        $template = new Template;
        $view = new View;

//        $dataDetailQuery = "SELECT ta_courses.`id`, ta_courses.`title` course_name,division.`item_name` division_name";
//
//        $dataDetailQuery .= " FROM training_allied_rules tar "
//                . " LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=tar.course_id  "
//                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
//                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
//                . " WHERE tar.`id` = '" . $training_id . "'";
        
        
        //$dataDetail = $this->db->exec($dataDetailQuery);
        
        $trainingRuleDetail = $this->trainingRuleDetailWithName($training_id);
        
       
        
        

        

        $this->f3->set('data_detail', $trainingRuleDetail);

//        SELECT ass.parent_user_id,users.id, users.name
//FROM users
//inner join lookup_answers on lookup_answers.foreign_id = users.id and (lookup_answers.lookup_field_id = 384 ) and lookup_answers.table_assoc = 'users'
//inner join associations ass ON ass.association_type_id = 1 AND ass.child_user_id = users.ID
//ORDER BY id ASC;






        $this->f3->set('base_url', $this->f3->get('base_url'));
//    if (isset($data_detail[0]['id'])) {
//        $this->f3->set('page_title', 'Edit Question');
//    } else {
        
        //}
        //$baseUrlOld = $this->f3->get('base_url_pdf');
        //$clientList = $this->getClientsOfJobs();
        //$userLevel = $this->get_simple_lookup('user_level');

        $trainingRulesRolesData = $this->trainingRuleRolesDetail($training_id);
        
        $this->f3->set('page_title', 'Assign User');
        
        

        $userLevel = $this->getUserLevel();

        $this->f3->set('training_id', $training_id);
        $this->f3->set('trainingRulesRolesData', $trainingRulesRolesData);
        $this->f3->set('userLevel', $userLevel);

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/assign/userassign.php');
        echo $template->render('footer.htm');
    }

    /*
      All job list
     */

    function toolbox_user_index() {

        $template = new Template;
        $view = new View;
        if ($_REQUEST['course_id'] == 0) {
            $course_id = 0;
        } else {
            $course_id = $_REQUEST['course_id'];
        }
        
        $this->assignedToolboxUser($course_id);

//       $select_records = "SELECT *";
//       $lessonDetail = $this->db->exec("$select_records FROM training_allied_lessons WHERE training_allied_lessons.id=$lesson_id");
//        $course_id = $lessonDetail['course_id'];

        $dataDetailQuery = "SELECT ta_courses.start_date,ta_courses.end_date,ta_courses.`id` course_id, ta_courses.`title` course_title,division.`item_name` division_name";

        $dataDetailQuery .= " FROM toolbox_allied_courses ta_courses "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE ta_courses.status = 1 and ta_courses.`id` = '" . $course_id . "'";

        $dataDetail = $this->db->exec($dataDetailQuery);
//            

      $this->f3->set('course_id', $course_id);
        $this->f3->set('dataDetail', $dataDetail);
        $this->f3->set('page_title', 'Toolbox User List');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedToolbox/");
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('toolbox/assign/user_index.php');
        echo $template->render('footer.htm');
    }

    function getTrainingUserAjax() {

        $course_id = $_REQUEST['course_id'];
//echo $course_id;
        if($course_id < 1) {
            die;
        }

        $order_by = array();
        $length = $this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');

        if (empty($length)) {
            $length = 10;
            $start = 0;
        }
        $columnData = array(
            'tac.`created_at`',
            'CONCAT(usr.name," ", usr.surname)',           
            'tac.`start_date`',
            'tac.`end_date`'
        );

        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

        $where_cond = " tac.id = '" . $course_id . "' ";
//         $where_cond .= " and ta_questions.status != 2 ";
//         if($_REQUEST['lession_id']){
//              $where_cond .= " and ta_questions.lesson_id = ".$_REQUEST['lession_id'];
//         }else{
//              $where_cond .= " and ta_questions.lesson_id = 0 ";
//         }
//         

        if ($_REQUEST['searchBox']) {
            $where_cond .= " and  CONCAT(usr.name,' ', usr.surname) like '%" . $_REQUEST['searchBox'] . "%'";
        }





        $select_records = " SELECT usr.ID AS user_id,CONCAT(usr.name, ' ', usr.surname) user_name,
           tac.id course_id,tac.start_date start_date,tac.end_date end_date,tac.title course_title";
        $select_num_rows = "SELECT count(distinct(usr.ID)) as total_records";

        $afterFrom = " FROM users usr                 
                  INNER JOIN toolbox_allied_user tatu ON tatu.user_id = usr.ID
                  LEFT JOIN toolbox_allied_courses tac ON tac.id = tatu.course_id "
                . " WHERE $where_cond  ";

        $trainingStatusQuery = " ";

        $trainingStatusQuery .= "";

        $listPaging = "group by usr.id ORDER BY $order_by $sort_order LIMIT $length OFFSET $start";

        $countQuery = $select_num_rows . $afterFrom;
        $listQuery = $select_records . $afterFrom . $listPaging;
        
//        echo $countQuery;
//        die;

        $total_records = $this->db->exec($countQuery);

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $record_list = array();

        if ($total_data > 0) {
            $record_list = $this->db->exec($listQuery);

//            $record_list = $this->db->exec("$select_records FROM training_allied_courses ta_courses "
//                    . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
//                    . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
//                    . " WHERE $where_cond  ORDER BY $order_by $sort_order LIMIT $length OFFSET $start");
        }

        //prd($record_list);

        $jsonArray = array(
            'draw' => $this->f3->get('POST.draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_data,
            'data' => array(),
        );
        
        //prd($record_list);
        //creating view for business user in datatable
        if (!empty($record_list)) {
            
            $StatusArr = array('0' => 'Inactive', '1' => 'Active', '2' => 'Deleted');
            $base_url = $this->f3->get('base_url');
            foreach ($record_list as $key => $val) {
                // $allocation_link = $base_url . '/job-allocation?job-id=' . $val['id'];
                //$user_assign_link = $base_url . "/AlliedTraining/AssignTraining/UserAssign?training_id=" . $val['id'];
                // $user_assigned_link = $base_url . "/AlliedTraining/AssignTraining/trainingUser?training_id=" . $val['id'];
//                $edit_link = $base_url . '/AlliedTraining/Questions/Edit?lesson_id='.$val['lesson_id'].'&id=' . $val['id'];
//                $delete_link = "javaScript:delete_question(" . $val['id'] . ")";
//                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
//                
                //$user_assign = $user_assign_link;
                //$user_assign = '<a title="Assign User" class="black-a" href="' . $user_assign_link . '"><span uk-icon="more-vertical"></span></a>';
                //$training_user = '<a title="Assign User" class="black-a" href="' . $user_assigned_link . '"> Training User </a>';
//                $question ='';//'<a title="Edit" class="green-a" href="' . $question_link . '">Question</a>';
//                $edit = '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
//                $delete ='<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                //$view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                // 'action' => $question .' '. $edit . ' ' . $delete,
                $allocation = '';
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'toolbox_name' => $val['course_title'],                   
                    'user_name' => $val['user_name'],
                    'start_date' => $val['start_date'],
                    'end_date' => $val['end_date'],
                        //'action' => "",
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }
    
    function fetchClientList() {
        $str =  '<option value="0"> All Site </option>' ;
        if (!empty($_POST)){
            $client_id = $this->f3->get('POST.client_id');
            $division_id = $this->f3->get('POST.division_id');
            if(!empty($client_id) && !empty($division_id)){                
               $str = $this->clientLocationDropdown($division_id,$client_id);
                
            }
        }
        echo $str;
        die;
    }
    
    

    /*
      Create a new training

     *    */

    function create() {
        
        if ($_REQUEST['course_id'] == 0) {
            $course_id = 0;
        } else {
            $course_id = $_REQUEST['course_id'];
        }

        $clientData = $this->getClientsData();

        //prd($clientData);
        $this->f3->set('clientData', $clientData);

        $this->manageTraining('create', $course_id);
    }
    
    
     /*
      Create a new training

     *    */

    function edit() {
        
        $trainingId = $_REQUEST['training_id'];
       
        
        
        if ($_REQUEST['course_id'] == 0) {
            $course_id = 0;
        } else {
            $course_id = $_REQUEST['course_id'];
        }

     

        $this->manageTraining('edit', $course_id,$trainingId);
    }

    /*
      Updtae Lessons
     */

//  function edit($lesson_id= NULL, $id = NULL) {
//   $this->manageLessons('update', $lesson_id, $id);
//  }

    function manageTraining($type, $course_id = 0, $id = 0) {
      
        //echo $type; die;
        $template = new Template;
        $view = new View;
       $siteDropDown = "<option value='0'> All Site </option>";
       if($id != 0){
           $trainingRuleDetail = $this->trainingRuleDetail($id);
          // pr($trainingRuleDetail);
           $course_id = $trainingRuleDetail['course_id'];
           $this->f3->set('trainingDetail', $trainingRuleDetail);           
            if(!empty($trainingRuleDetail['client_id'])){                
               $siteDropDown = $this->clientLocationDropdown($trainingRuleDetail['division_id'],$trainingRuleDetail['client_id'],$trainingRuleDetail['site_id']);              
            } 
           //echo "hello";
           // prd($siteDropDown);
        }
        //prd($siteDropDown);
         $this->f3->set('siteDropDown', $siteDropDown);
        

        $courseDetailQuery = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.`status`,ta_courses.`division_id` division_id,division.`item_name` division_name,ta_frequency.`title` freq_title";

        $courseDetailQuery .= " FROM training_allied_courses ta_courses "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE ta_courses.`id` = '" . $course_id . "'";

        //Get sub job type
//        $job_type_id = 1;
        $courseDetail = $this->db->exec($courseDetailQuery);
        if (!empty($_POST)) {

            if ($type == 'create' || $type == 'edit') {

                $error_mesg = '';

              //  prd($_REQUEST);

                $course_id = $this->f3->get('POST.course_id');
                $client_id = $this->f3->get('POST.client_id');

                $start_date = $this->f3->get('POST.start_date');
                $end_date = $this->f3->get('POST.end_date');
                
                $client_id = $this->f3->get('POST.client_id');
                $site_id = $this->f3->get('POST.site_id');
                
                
                
                
                
                $training_title = $this->f3->get('POST.training_title');
                /* Validation start */

                if (empty($course_id))
                    $error_mesg .= 'Course is required<br>';
                if (empty($start_date))
                    $error_mesg .= ' Start Date is required<br>';
                if (empty($end_date))
                    $error_mesg .= ' End date is required<br>';

                $training_start_date = date('Y-m-d', strtotime($start_date));

                $training_end_date = date('Y-m-d', strtotime($end_date));
//                echo strtotime($training_start_date)."</br>";
//                echo strtotime($training_end_date);
//                die;

                if(strtotime($training_start_date) > strtotime($training_end_date))
                    $error_mesg .= ' Start date should be less than end date <br>';
                if (!empty($error_mesg)) {
                    $data = array(
                        'code' => '404',
                        'mesg' => $error_mesg
                    );
                    echo json_encode($data);
                    die;
                }
                /* Validation end */



                $updated_by = $_SESSION['user_id'];
                $created_by = $_SESSION['user_id'];
                $updated_by = $_SESSION['user_id'];

                if ($type == 'create') {

                    $insertQuery = "INSERT INTO `training_allied_rules` (`training_title`,`client_id`,`site_id`,`course_id`,`start_date`,`end_date`,`created_by`,`updated_by`)"
                            . " VALUES('$training_title','$client_id','$site_id','$course_id','$training_start_date','$training_end_date','$created_by','$updated_by')";

                    $this->db->exec($insertQuery);
                    $training_rule_id = $this->db->lastInsertId();

                } else {

            // $upQuery = "UPDATE training_allied_lesson_questions SET lesson_id = '$lesson_id', question = '$question',option_row = '$option_row',option1 = '$option1',option2 = '$option2',option3='$option3',option4='$option4',correct_option='$correct_option',updated_by = '$updated_by'  WHERE id = '$id'";
             
             $upQuery = "UPDATE `training_allied_rules` SET "
                     . "`training_title` = '$training_title' ,`client_id`  = '$client_id',`site_id` = '$site_id',"
                     . "`course_id` = '$course_id' ,`start_date`  = '$training_start_date',`end_date` = '$training_end_date',"
                     . "`created_by` = '$created_by' ,`updated_by`  = '$updated_by' WHERE id = '$id'";

             //echo $upQuery; die; 
             $this->db->exec($upQuery);
             $training_rule_id = $id;
                }

                if ($training_rule_id) {

                    if ($type == 'create') {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Training created successfully'
                        );
                    } else {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Training updated successfully'
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

        $template = new Template;
        $view = new View;
        // print_r($courseDetail); die;
        $this->f3->set('dataDetail', $courseDetail[0]);
        $this->f3->set('base_url', $this->f3->get('base_url'));
//    if (isset($data_detail[0]['id'])) {
//        $this->f3->set('page_title', 'Edit Question');
//    } else {
        
        
         if ($type == 'create') {
        
            $this->f3->set('page_title', 'Add Training Duration');
         }else{
             $this->f3->set('page_title', 'Edit Training Duration'); 
         }
        //}
        //$baseUrlOld = $this->f3->get('base_url_pdf');

        $this->f3->set('course_id', $course_id);
        $clientData = $this->getClientsData();

       // prd($clientData);
        $this->f3->set('clientData', $clientData);
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/assign/create.php');
        echo $template->render('footer.htm');
    }

//delete Question
    function deleteTraining() {
        $str = '';
        if (!empty($_POST)) {
            $id = $this->f3->get('POST.id');
            $str = $this->db->exec("UPDATE training_allied_rules SET status='2' WHERE id=" . $id);
        }
        echo $str;
        die;
    }

}

?>