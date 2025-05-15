<?php

class ToolboxAlliedController extends Controller {

    protected $f3;
    protected $db;

    function __construct($f3) {
        $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $this->f3 = $f3;
        $this->db_init();
    }
    
    /*
      All Training status list
     */

    function index123() {      
          $template = new Template;
        $view = new View;

        //Get sub job type
//        $job_type_id = 1;
        $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC");

        $this->f3->set('divisionList', $divisionList);
        
        
         $courseList = $this->db->exec("SELECT tac.id,tac.title,comp.item_name"
                 . " FROM training_allied_courses tac "
                 . " LEFT JOIN companies comp on comp.id = tac.division_id "
                 . "  where tac.status != 2"
                 . " ORDER BY comp.id,tac.title ASC");
         //prd($courseList);

        $this->f3->set('courseList', $courseList);

//        //Get jobs_category
//        $select_records = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.`status`,division.`item_name` division_name,ta_frequency.`title` freq_title";
//        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
//        $this->f3->set('jobs_category', $jobs_category);
//
//        //Get job location list
//        $job_location = $this->getLocationOfJobs();
//        $this->f3->set('job_location', $job_location);
//
//        //Get client list            
//        $job_clients = $this->getClientsOfJobs();
//        $this->f3->set('job_clients', $job_clients);
//        $this->f3->set('Job_List', $job_list);
        $this->f3->set('page_title', 'My Training Pending');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedMyTraining");

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/training/user_training_status.php');
        echo $template->render('footer.htm');
    }
    
    
    
     /*
      Fetch job list via ajax
     */

    function getListAjax2() {
        $order_by = array();
        $length = $this->f3->get('POST.length');
        $start = $this->f3->get('POST.start');
       
        if (empty($length)) {
            $length = 10;
            $start = 0;
        }

        $columnData = array(
            'tar.`training_title`',
            'tar.`training_title`',
            'division.`item_name`',
            'tal.`title`',
            'tac.`title`',
            'tatu.`start_date`',
            'tatu.`end_date`',
            '`test_status`',
        );

        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

        $where_cond = " tac.status = 1 and tal.status = 1  ";

        $where_cond .= " and usr.id = '" . $_SESSION['user_id'] . "'";
        
         if($_REQUEST['srch_division_id']){
             $where_cond .= " and division.`id` = '".$_REQUEST['srch_division_id']."'";
         }

          if ($_REQUEST['searchBox']) {
            $where_cond .= " and ta_courses.`title` like '%" . $_REQUEST['searchBox'] . "%'";
        }

        $select_records = " SELECT usr.ID AS user_id,CONCAT(usr.name, ' ', usr.surname) employee_name,tatu.id trainingId,
 tatu.start_date startDate,tatu.end_date endDate,tac.id course_id,division.`item_name` division_name,tar.training_title trainingTitle,tal.id lessonId,tal.title lesson_title,tac.id courseId, tac.title course_title,tatu.is_result,tatu.total_question,tatu.correct_answer,IF(tatu.total_question = 0,0,IF(tatu.total_question = tatu.correct_answer,2,1)) as test_status";
        $select_num_rows = "SELECT count(usr.ID) as total_records";

        $afterFrom = " FROM users usr
                  INNER JOIN training_allied_training_user tatu ON tatu.user_id = usr.ID
                  INNER JOIN training_allied_rules tar ON tar.id = tatu.training_rule_id 
                  LEFT JOIN training_allied_lessons tal ON tal.id = tatu.lesson_id
                  LEFT JOIN training_allied_courses tac ON tac.id = tal.course_id 
                  LEFT JOIN companies division ON division.id=tac.division_id "
                . " WHERE $where_cond";
        
        $sortByFrom =  " ORDER BY $order_by $sort_order";

        $listPaging = " LIMIT $length OFFSET $start";

        $countQuery = $select_num_rows . $afterFrom;
        $listQuery = $select_records . $afterFrom . $sortByFrom .$listPaging;

//       echo $listQuery;
//       die;

        $total_records = $this->db->exec($countQuery);

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $record_list = array();

        if ($total_data > 0) {
            $record_list = $this->db->exec($listQuery);
            //prd($record_list);
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

                $view_link = $base_url . "/AlliedMyTraining/view?id=" . $val['trainingId'];

                $decodeLessonId = $this->encrypt($val['trainingId']);

                // echo  $decodeLessonId;
                $encodeLessonId = $this->decrypt($decodeLessonId);
                // echo $encodeLessonId;
                //  die;

                $test_link = $base_url . '/AlliedMyTraining/TrainingTest?id=' . $decodeLessonId;

                //$edit_link = $base_url . '/AlliedTraining/Courses/Edit?course-id=' . $val['id'];
                //$delete_link = "javaScript:delete_course(" . $val['id'] . ")";
                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
//                $edit =  '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
//                $delete =  '<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                $view = '<a title="View detail" class="black-a" href="' . $view_link . '">'
                       // . '<span uk-icon="more-vertical"></span>'
                        . ' Training Detail </a>';
                $givetest = '<a title="View detail" class="black-a" href="' . $test_link . '"> Training Test </a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                // 'action' => $lesson.' '.$edit . ' ' . $delete,

                $allocation = '';
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'title' => $val['trainingTitle'],
                    'division_name' => $val['division_name'],
                    'lesson_title' => $val['lesson_title'],
                    'course_title' => $val['course_title'],
                    'start_date' => $val['startDate'],
                    'end_date' => $val['endDate'],
                    'is_result' => intval($val['test_status']) == 2 ? "Pass" : ($val['test_status']==1 ? "Failed" : "Pending"),
                    'action' => $view . " &nbsp; &nbsp;" . (intval($val['test_status']) < 2 ? $givetest:""),
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }
    
    
     
    /*
      Pending My Training
     */

    function index(){

        $template = new Template;
        $view = new View;
 
        //Get sub job type
//        $job_type_id = 1;
        $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC");

        $this->f3->set('divisionList', $divisionList);

//        //Get jobs_category
//        $select_records = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.`status`,division.`item_name` division_name,ta_frequency.`title` freq_title";
//        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
//        $this->f3->set('jobs_category', $jobs_category);
//
//        //Get job location list
//        $job_location = $this->getLocationOfJobs();
//        $this->f3->set('job_location', $job_location);
//
//        //Get client list            
//        $job_clients = $this->getClientsOfJobs();
//        $this->f3->set('job_clients', $job_clients);
//        $this->f3->set('Job_List', $job_list);
        $this->f3->set('page_title', 'My Toolbox ');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedMyToolbox");

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('toolbox/toolbox/mytoolbox.php');
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
             'CONCAT(usr.name, " ", usr.surname)',
            'CONCAT(usr.name, " ", usr.surname)',
            'tac.`title`',
            'division.`item_name`',
            'tal.`title`',
            'tac.`start_date`',
            'tac.`end_date`',
            '`test_status`',
        );

        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

        $where_cond = " tac.status = 1 and tal.status = 1  ";
      
        if($_SESSION['toolbox'] == "alluser"){
            
        }else{
            $where_cond .= " and usr.id = '" . $_SESSION['user_id'] . "'";
        }
        
         if($_REQUEST['srch_division_id']){
             $where_cond .= " and division.`id` = '".$_REQUEST['srch_division_id']."'";
         }

          if ($_REQUEST['searchBox']) {
            $where_cond .= " and ta_courses.`title` like '%" . $_REQUEST['searchBox'] . "%'";
        }

        $select_records = " SELECT usr.ID AS user_id,CONCAT(usr.name, ' ', usr.surname) employee_name,tatu.id trainingId,
 tac.start_date startDate,tac.end_date endDate,tac.id course_id,division.`item_name` division_name,tal.id lessonId,tal.title lesson_title,tac.id courseId, tac.title course_title,tatu.is_result,tatu.total_question,tatu.correct_answer,IF(tatu.total_question = 0,0,IF(tatu.total_question = tatu.correct_answer,2,1)) as test_status";
        $select_num_rows = "SELECT count(usr.ID) as total_records";

        $afterFrom = " FROM users usr
                  INNER JOIN toolbox_allied_user tatu ON tatu.user_id = usr.ID                
                  LEFT JOIN toolbox_allied_lessons tal ON tal.id = tatu.lesson_id
                  LEFT JOIN toolbox_allied_courses tac ON tac.id = tal.course_id 
                  LEFT JOIN companies division ON division.id=tac.division_id "
                . " WHERE $where_cond";
        
        $sortByFrom =  " ORDER BY $order_by $sort_order";

        $listPaging = " LIMIT $length OFFSET $start";

        $countQuery = $select_num_rows . $afterFrom;
        $listQuery = $select_records . $afterFrom . $sortByFrom .$listPaging;

//      echo $listQuery;
//      die;

        $total_records = $this->db->exec($countQuery);

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $record_list = array();

        if ($total_data > 0) {
            $record_list = $this->db->exec($listQuery);
            //prd($record_list);
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
      //  prd($record_list);
        if (!empty($record_list)) {
            $StatusArr = array('0' => 'Inactive', '1' => 'Active', '2' => 'Deleted');
            $base_url = $this->f3->get('base_url');
            foreach ($record_list as $key => $val) {
               // prd($val);
                // $allocation_link = $base_url . '/job-allocation?job-id=' . $val['id'];
                 $decodeLessonId = $this->encrypt($val['trainingId']);

                // echo  $decodeLessonId;
                $encodeLessonId = $this->decrypt($decodeLessonId);
                 if($_SESSION['toolbox'] == "alluser"){
                        $view_link = $base_url . "/AlliedUserToolbox/view?id=" . $val['trainingId'];
                        $test_link = $base_url . '/AlliedUserToolbox/ToolboxAck?id=' . $decodeLessonId;
                    }else{
                        $view_link = $base_url . "/AlliedMyToolbox/view?id=" . $val['trainingId'];
                        $test_link = $base_url . '/AlliedMyToolbox/ToolboxAck?id=' . $decodeLessonId;
                    }
               

                $view = '<a title="View detail" class="black-a" href="' . $view_link . '">'
                        . ' Toolbox Detail </a>
                        <style>
                        .form-header {
                            border-width: 0px;
                            color: white;
                            font-weight: bold!important;
                            font-size: 12pt;
                            background-color: #243352;
                            padding: 10px 8px 10px 8px;
                            border-radius: 5px!important;
                        }

                        thead {
                            display: table-header-group;
                            vertical-align: middle;
                            border-color: inherit;
                            background-color: #243352!important;
                            color: white!important;
                            margin-top: 5px!important;
                            border-radius: 5px!important;
                        }

                        .uk-table-hover tbody tr:hover, .uk-table-hover>tr:hover {
                            background: #cdeefd!important;
                        }

                        td {
                            display: table-cell;
                            vertical-align: inherit;
                            color:black!important;
                        }
                        
                        td a {
                            
                        }


                        </style>';
                $givetest = '<a title="View detail" class="black-a" href="' . $test_link . '"> Toolbox Acknowledgement </a>';


                $allocation = '';
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'employee_name' => $val['employee_name'],
                    'title' => $val['course_title'],
                    'division_name' => $val['division_name'],
                    'lesson_title' => $val['lesson_title'],                    
                    'start_date' => $val['startDate'],
                    'end_date' => $val['endDate'],
                    'is_result' => intval($val['test_status']) == 2 ? "Yes" : ($val['test_status']==1 ? "Yes" : "Not"),
                    'action' => $view . " &nbsp; &nbsp;" . $givetest,
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }

    //view Detail
    function view() {
        //$str = '';

        $template = new Template;
        $view = new View;
        if (!empty($_REQUEST)) {

            $id = $_REQUEST['id'];
            $where_cond = " tatu.id = $id and usr.ID = '" . $_SESSION['user_id'] . "'";

            $select_records = " SELECT usr.ID AS userId,CONCAT(usr.name, ' ', usr.surname) employeeName,"
                    . "tatu.id trainingId,tac.start_date startDate,tac.end_date endDate,tatu.is_result,"                  
                    . "tac.id course_id,tac.title courseTitle,"
                    . "tal.id lessonId,tal.title lessonTitle,tal.description lessonDescription, "
                    . "tal.document1,tal.video1";

            $afterFrom = " FROM users usr
                  INNER JOIN toolbox_allied_user tatu ON tatu.user_id = usr.ID                 
                  LEFT JOIN toolbox_allied_lessons tal ON tal.id = tatu.lesson_id
                  LEFT JOIN toolbox_allied_courses tac ON tac.id = tal.course_id "
                    . " WHERE $where_cond";

            $detailQuery = $select_records . $afterFrom;

            $data_detail = $this->db->exec($detailQuery);
            //prd($training_detail);

            $this->f3->set('training_detail', $data_detail[0]);

            $flder = $this->f3->get('download_folder') . "course_lesson/";
            $showflder = "course_lesson/";
            $baseUrlOld = $this->f3->get('base_url_pdf');

            if (!empty($data_detail[0]['document1'])) {
                //echo $showflder."/course_lesson/".$data_detail[0]['document1'];
//echo $flder.$data_detail[0]['document1'];
//die;
                $this->f3->set('document1_file', $flder . $data_detail[0]['document1']);
                //$this->f3->set('f3', $this->f3);
                $this->f3->set('url_document1_file', $baseUrlOld . '/edge/downloads/' . $showflder . $data_detail[0]['document1']);
            }
            if (!empty($data_detail[0]['video1'])) {
                $this->f3->set('video1_file', $flder . $data_detail[0]['video1']);
                $this->f3->set('url_video1_file', $baseUrlOld . '/edge/downloads/' . $showflder . $data_detail[0]['video1']);
            }

            //$this->f3->set('page_title'," Training Detail");
            $this->f3->set('page_title', 'Toolbox Detail');
            $this->f3->set('base_url', $this->f3->get('base_url'));
                    if($_SESSION['toolbox'] == "alluser")
                    {
                        $this->f3->set('back_url', $this->f3->get('base_url')."/AlliedUserToolbox/index");
                    }else{
                         $this->f3->set('back_url', $this->f3->get('base_url')."/AlliedMyToolbox/index");
                    }
           

            echo $template->render('header.htm');
            echo $template->render('nav.htm');
            echo $view->render('toolbox/toolbox/training_detail.php');
            echo $template->render('footer.htm');
            die;
        }
    }

    function trainingTest(){

        $userTrainingId = $this->decrypt(str_replace(" ", "+", $_REQUEST['id']));
//        echo $userTrainingId;
//        die;
        //prd($_REQUEST);
        $template = new Template;
        $view = new View;
         $this->f3->set('correctAnswer',   0);
                 $this->f3->set('total_question', 0);
        if (!empty($_REQUEST['question'])) {
            $questions = $_REQUEST['question'];

            $trainingStatus = 1;
            $questionNewArray = array_values($questions);
            $totalQuestion = 0;
            $correctAnswer = 0;
            $allCorrectAnswer = 1;
//prd($questionNewArray);
            foreach ($questionNewArray as $key => $question) {
                
               // prd($question);
               // prd($question);
                if ($key == 0) {
                    $commaAdd = " ";
                } else {
                    $commaAdd = ",";
                }
                $totalQuestion++;
                // prd($question);
                //die("test");
                if ($question['answer_correct'] == $question['selected_option']) {
                    $answer_point = "1";
                    $correctAnswer++;
                } else {
                    $answer_point = "0";
                    $allCorrectAnswer = 0;
                }
                $insertRec .= $commaAdd . '("' . $userTrainingId . '","' . $question['question_id'] . '","' . $question['selected_option'] . '","' . $question['question_txt'] . '","' . $question['selected_option_text'] . '","' . $question['answer_text_correct'] . '","' . $answer_point . '")';
            }

//             echo $insertRec;
//            prd($questions);

            $deleteQuery = " delete from toolbox_allied_user_training_statics  where toolbox_id = '" . $userTrainingId . "'";
            $this->db->exec($deleteQuery);

            $insertQuery = "INSERT IGNORE INTO toolbox_allied_user_training_statics 
                    (toolbox_id,question_id,selected_option,question, user_answer,correct_answer,answer_point) 
                    VALUES 
                    $insertRec";

//            echo $insertQuery;
//            die;

            $this->db->exec($insertQuery);

            $updateQuery = "UPDATE toolbox_allied_user  "
                    . "set is_result = '" . $allCorrectAnswer . "',total_question='" . $totalQuestion . "',correct_answer='" . $correctAnswer . "' where id = $userTrainingId";
            //echo $updateQuery;
           // die;
            $this->db->exec($updateQuery);
            
            //if($allCorrectAnswer == 1){            
                //$this->f3->reroute('/AlliedMyToolbox/index');
//            }else{
//                 $this->f3->set('correctAnswer',   $correctAnswer);
//                 $this->f3->set('total_question', $totalQuestion);
//            }    
            
        }

        $where_cond = " tau.id = $userTrainingId and tau.user_id = '" . $_SESSION['user_id'] . "'";

        $select_records = " SELECT talq.*,tauts.user_answer,tauts.selected_option,tal.title,tac.title training_title";

        $afterFrom = " FROM toolbox_allied_lesson_questions talq
                       LEFT JOIN toolbox_allied_lessons tal ON tal.id = talq.lesson_id 
                       LEFT JOIN toolbox_allied_courses tac ON tac.id = tal.course_id                    
                       INNER JOIN toolbox_allied_user tau ON tau.course_id = tac.id 
                       LEFT JOIN toolbox_allied_user_training_statics tauts ON (tauts.toolbox_id = tau.id 
                       and tauts.question_id = talq.id) "
                
                       
                . " WHERE $where_cond";

        $detailQuery = $select_records . $afterFrom;
        
        //echo $detailQuery;
        //die;

        $data_detail = $this->db->exec($detailQuery);
        

        $this->f3->set('question_details', $data_detail);
        
        
        
          if($_SESSION['toolbox'] == "alluser")
        {
            $this->f3->set('back_url', $this->f3->get('base_url')."/AlliedUserToolbox/index");
        }else{
             $this->f3->set('back_url', $this->f3->get('base_url')."/AlliedMyToolbox/index");
        }
        

        //$this->f3->set('page_title'," Training Detail");
        $this->f3->set('user_training_id', $userTrainingId);
        $this->f3->set('page_title', 'Toolbox Question');
        $this->f3->set('base_url', $this->f3->get('base_url'));

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('toolbox/toolbox/training_paper.php');
        echo $template->render('footer.htm');
        die;
    }

    /*
      Pending My Training
     */

    function myTrainingCompleted1() {
        $template = new Template;
        if (isset($_REQUEST['srch_divisionid'])) {
            $divisionId = $_REQUEST['srch_divisionid'];
        } else {
            $divisionId = 2103;
        }
        $view = new View;
//        if($_REQUEST['course_id'] == 0){
//            $course_id = 0;
//        }else{
//            $course_id = $_REQUEST['course_id']; 
//        }
        $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC");

        $this->f3->set('divisionList', $divisionList);

        $trainingStatusQuery = " SELECT usr.ID AS user_id,CONCAT(usr.name, ' ', usr.surname) employee_name,sla.lookup_field_id AS division_id,tac.id course_id,group_concat(tatu.id) trainid,
group_concat(tatu.lesson_id) lesson_id,tac.title course_title, count(tatu.id) assign_total,tatu.is_result,
IF(sum(tatu.is_result),sum(tatu.is_result),0) pass_total";

        $trainingStatusQuery .= " FROM users usr
inner join lookup_answers on lookup_answers.foreign_id = usr.id and (lookup_answers.lookup_field_id = 107 ) and lookup_answers.table_assoc = 'users'           
INNER JOIN lookup_answers sla on sla.foreign_id = usr.id and sla.lookup_field_id = '$divisionId'
LEFT JOIN training_allied_courses tac on tac.division_id = '$divisionId'
LEFT JOIN training_allied_lessons tal ON tal.course_id = tac.id
LEFT JOIN training_allied_training_user tatu ON tatu.user_id = usr.ID AND tatu.lesson_id = tal.id
WHERE tac.`status` != 2 AND sla.lookup_field_id IS NOT NULL
GROUP BY  usr.ID,tac.id ORDER BY usr.ID ";

//       echo $trainingStatusQuery;
//       die;
        //Get sub job type
//        $job_type_id = 1;
        $trainingStatusDetail = $this->db->exec($trainingStatusQuery);

        $trainingStatusGraph = $this->trainingStatusGraph($trainingStatusDetail);

        //$this->f3->set('divisionList', $divisionList);  
//
//        //Get jobs_category
//        $jobs_category = $this->db->exec("SELECT id,category_name FROM jobs_category WHERE category_status=1 ORDER BY category_name ASC");
//        $this->f3->set('jobs_category', $jobs_category);
//
//        //Get job location list
//        $job_location = $this->getLocationOfJobs();
//        $this->f3->set('job_location', $job_location);
//
//        //Get client list            
//        $job_clients = $this->getClientsOfJobs();
//        $this->f3->set('job_clients', $job_clients);
//       $this->f3->set('Job_List', $job_list);
        //prd($trainingStatusGraph);

        $this->f3->set('divisionId', $divisionId);
        $this->f3->set('trainingStatusGraph', $trainingStatusGraph);
        //$this->f3->set('course_id', $course_id);
        $this->f3->set('page_title', ' Training Status');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedTraining/trainingStatus/");
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/training/graph.php');
        echo $template->render('footer.htm');
    }

}

?>