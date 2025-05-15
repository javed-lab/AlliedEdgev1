<?php

class ToolboxAlliedLessonsController extends Controller {

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
        if(!$_REQUEST['course_id']) {
            $this->f3->reroute('/AlliedToolbox/Courses');
            $course_id = 0;
            
        } else {
            $course_id = $_REQUEST['course_id'];
        }


        $courseDetailQuery = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.`status`,division.`item_name` division_name,ta_frequency.`title` freq_title";

        $courseDetailQuery .= " FROM toolbox_allied_courses ta_courses "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE ta_courses.`id` = '" . $course_id . "'";

        //Get sub job type
//        $job_type_id = 1;
        $courseDetail = $this->db->exec($courseDetailQuery);

        //$this->f3->set('divisionList', $divisionList);  
//
//        //Get jobs_category
//        echo "SELECT count(id) FROM toolbox_allied_lessons tal WHERE tal.course_id = $course_id ";
//            die;
        $lessonCount = $this->db->exec("SELECT count(id) totLesson FROM toolbox_allied_lessons tal WHERE tal.course_id = $course_id ");
        
        $totalLesson = $lessonCount[0]['totLesson'];
        
        $this->f3->set('totalLesson', $totalLesson);
//
//        //Get job location list
//        $job_location = $this->getLocationOfJobs();
//        $this->f3->set('job_location', $job_location);
//
//        //Get client list            
//        $job_clients = $this->getClientsOfJobs();
//        $this->f3->set('job_clients', $job_clients);
//       $this->f3->set('Job_List', $job_list);
        //prd($courseDetail);
        $this->f3->set('courseDetail', $courseDetail);
        $this->f3->set('course_id', $course_id);
        $this->f3->set('page_title', 'Toolbox Lesson List');
        $this->f3->set('base_url', $this->f3->get('base_url') . "/AlliedToolbox/Lessons/");
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('toolbox/lessons/index.php');
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
            'ta_lessons.`created_at`',
            //'division.`item_name`',
            'ta_lessons.`title`',
            'ta_lessons.`description`',
             'ta_lessons.`description`',
             'ta_lessons.`description`',
             'ta_lessons.`created_at`'
        );

        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];

//        echo $order_by;
//        die;
//        $searchData = $this->f3->get('POST.searchBox');
//
//        $job_sub_type_id = $this->f3->get('POST.job_sub_type_id');
//        $job_category_id = $this->f3->get('POST.job_category_id');
//        $job_client_id = $this->f3->get('POST.job_client_id');
//        $job_location_id = $this->f3->get('POST.job_location_id');
//        $job_status = $this->f3->get('POST.job_status');
//        $quote_end_date = $this->f3->get('POST.quote_end_date');
//        $from_date = $this->f3->get('POST.from_date');
//        $to_date = $this->f3->get('POST.to_date');

        $where_cond = " 1 ";
        $where_cond .= " and ta_lessons.status != 2 ";
        if ($_REQUEST['course_id']) {
            $where_cond .= " and ta_lessons.course_id = " . $_REQUEST['course_id'];
        } else {
            $where_cond .= " and ta_lessons.course_id = 0 ";
        }




        if ($_REQUEST['searchBox']) {
            $where_cond .= " and ta_lessons.`title` like '%" . $_REQUEST['searchBox'] . "%'";
        }


        $select_records = "SELECT ta_lessons.`id`, ta_lessons.`created_at`,ta_lessons.`course_id`,ta_lessons.`title`,ta_lessons.`document1`,ta_lessons.`video1`,ta_lessons.`description`,ta_lessons.`status`,division.`item_name` division_name,ta_courses.title course_title,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_lessons.id) as total_records";

        $afterFrom = " FROM toolbox_allied_lessons ta_lessons "
                . " LEFT JOIN toolbox_allied_courses ta_courses ON ta_courses.id=ta_lessons.course_id  "
                . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                . " WHERE $where_cond  ORDER BY $order_by $sort_order";

        $listPaging = " LIMIT $length OFFSET $start";

        $countQuery = $select_num_rows . $afterFrom;
        $listQuery = $select_records . $afterFrom . $listPaging;

        $total_records = $this->db->exec($countQuery);

        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;

//        echo "$select_num_rows FROM jobs LEFT JOIN jobs_sub_type ON jobs.job_sub_type_id=jobs_sub_type.id  LEFT JOIN jobs_category ON jobs.job_category_id=jobs_category.id  LEFT JOIN users as client ON jobs.job_client_id=client.id  LEFT JOIN users as location ON jobs.job_location_id=location.id LEFT JOIN users as supplier ON jobs.job_supplier_id=supplier.id WHERE $where_cond";
//        die;


        $record_list = array();
       // prd($listQuery);

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
                $question_link = $base_url . "/AlliedToolbox/Questions?lesson_id=" . $val['id'];
               
                $edit_link = $base_url . '/AlliedToolbox/Lessons/Edit?course_id=' . $val['course_id'] . '&id=' . $val['id'];
                $delete_link = "javaScript:delete_lesson(" . $val['id'] . ")";
                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
                $question = '<a title="Edit" class="green-a" href="' . $question_link . '">Add / View Questions</a>';
               
                $edit = '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
                $delete = '<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                //$view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                $allocation = '';
                $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'title' => $val['title'],
                    'description' => substr(strip_tags($val['description']),0,100),
                    'document1' => $val['document1'],
                    'video1' => $val['video1'],
                    'created_at' => $val['created_at'],                  
                    'question_link' => $question,
                    'action' => $edit . ' ' . $delete,
                );
            }
        }
        echo json_encode($jsonArray);
        exit;
        echo $this->f3->get('POST.draw');
        exit;
    }

    /*
      Create a new Lessons
     */

    function create() {
        if ($_REQUEST['course_id'] == 0) {
              $this->f3->reroute('/AlliedToolbox/Courses');
            $course_id = 0;
        } else {
            $course_id = $_REQUEST['course_id'];
        }

        $this->manageLessons('create', $course_id);
    }

    /*
      Updtae Lessons
     */

    function edit($course_id = NULL, $id = NULL) {
        if(!$course_id){
            if(!$_REQUEST['course_id']){
                $this->f3->reroute('/AlliedTraining/Courses');
            }else{
                $course_id = $_REQUEST['course_id'];
            }
        }
        $this->manageLessons('update', $course_id, $id);
    }

    function manageLessons($type, $course_id = 0, $id = 0) {
        
         
        //echo $type; die;
        $template = new Template;
        $view = new View;
        $flder = $this->f3->get('download_folder') . "course_lesson";
        $showflder = "course_lesson/";

        $trainingAlliedCourseList = $this->db->exec("SELECT id,title FROM  toolbox_allied_courses WHERE toolbox_allied_courses.id='$course_id' ORDER BY title ASC");

        $this->f3->set('trainingAlliedCourseList', $trainingAlliedCourseList);
        # print_r($trainingAlliedCourseList); die;
        if ($type != 'create') {
            $select_records = "SELECT *";
            $data_detail = $this->db->exec("$select_records FROM toolbox_allied_lessons WHERE toolbox_allied_lessons.id=$id");
            if (empty($data_detail[0]) || $data_detail[0]['status'] > 2) {
                //redirect to list page
                $this->f3->reroute('/data_detail');
            }
        } else {
            $data_detail = array();
        }
        

        //Update/Add Job          
//echo $type; die;

        if (!empty($_POST)) {
//            pr($_POST);
//            prd($_FILES);
            
            if ($type == 'create' || !empty($data_detail[0])) {

                $error_mesg = '';

                $course_id = $this->f3->get('POST.course_id');
                $allied_frequency_id = $this->f3->get('POST.allied_frequency_id');
                $title = $this->f3->get('POST.title');
                $description = $this->f3->get('POST.description');
                $duration = $this->f3->get('POST.duration');
                $is_required = (!empty($this->f3->get('POST.is_required'))) ? 1 : 0;

                /* Validation start */
                if (empty($title))
                    $error_mesg .= 'Lesson title is required<br>';
                if (empty($course_id))
                    $error_mesg .= 'Course is required<br>';
                if (empty($description))
                    $error_mesg .= 'Lesson description is required<br>';


                if (!empty($error_mesg)) {
                    $data = array(
                        'code' => '404',
                        'mesg' => $error_mesg
                    );
                    echo json_encode($data);
                    die;
                }
               
                /* Validation end */
                //$this->index();
                
                if(!empty($data_detail[0]))
                {
                   $oldDocument1    = $data_detail[0]['document1']; 
                   $oldVideo1       = $data_detail[0]['video1']; 
                }
                
               

                $title = ucfirst($title);
                $updated_by = $_SESSION['user_id'];

                $document1 = '';
                $video1 = '';
                 
                if (isset($_FILES['document1']) && !empty($_FILES['document1']) && !empty($_FILES['document1']['name'])) {
                    $file_name = time().$_FILES['document1']['name'];
                    $file_size = $_FILES['document1']['size'];
                    $file_tmp = $_FILES['document1']['tmp_name'];
                    $file_type = $_FILES['document1']['type'];
                    
                    $file_ext_array = explode('.',$file_name);
                    $ext = array_pop($file_ext_array);
                     $file_name = time().".".$ext;                   
                    $document1 = $file_name;
                    move_uploaded_file($file_tmp, $flder . "/" . $file_name);
                    if($oldDocument1){
                        @unlink($flder . "/" .$oldDocument1);
                    }
                    
                } else {
                    if (!empty($data_detail[0]['document1'])) {
                        $document1 = $data_detail[0]['document1'];
                    }
                }
                if (isset($_FILES['video1']) && !empty($_FILES['video1']) && !empty($_FILES['video1']['name'])) {
                  //prd($_FILES['video1']);
                    $file_name = $_FILES['video1']['name'];
                    $file_size = $_FILES['video1']['size'];
                    $file_tmp = $_FILES['video1']['tmp_name'];
                    $file_type = $_FILES['video1']['type'];
                    
                     $file_ext_array = explode('.',$file_name);
                    $ext = array_pop($file_ext_array);
                    $file_name = time().".".$ext;     
                    
                    
                    $video1 = $file_name;
                    //echo $flder."/".$file_name;
                   
                    move_uploaded_file($file_tmp, $flder . "/" . $file_name);
                        if($oldVideo1){
                           @unlink($flder . "/" .$oldVideo1);
                        }
                    // prd($file_tmp);
                    //die;
                    //die;
                } else {
                    if (!empty($data_detail[0]['video1'])) {
                        $video1 = $data_detail[0]['video1'];
                    }
                }
                
                $created_by = $_SESSION['user_id'];
                $updated_by = $_SESSION['user_id'];

                if ($type == 'create') {

                    $insertQuery = "INSERT INTO `toolbox_allied_lessons` (`course_id`, `title`, `description`, `document1`, `video1`,`duration`, `created_by`, `updated_by`)"
                            . " VALUES('$course_id','$title','$description','$document1','$video1','$duration','$created_by','$updated_by')";

                    $lession_id1 = $this->db->exec($insertQuery);
                    # echo $course_id1; die;
                    if(!empty($lession_id1)) {
                        $lesson_id = $this->db->lastInsertId();
                    }
                } else {

                    $upQuery = "UPDATE toolbox_allied_lessons SET course_id = '$course_id', title = '$title',description = '$description',document1 = '$document1',video1 = '$video1',duration='$duration',updated_by = '$updated_by'  WHERE id = '$id'";
                    #echo $upQuery; die; 
                    $this->db->exec($upQuery);
                    $lesson_id = $id;
                }

                if ($lesson_id) {

                    if ($type == 'create') {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Lesson created successfully',
                            'lesson_id' => $lesson_id // Add lesson_id to the response
                        );
                    } else {
                        $data = array(
                            'code' => '200',
                            'mesg' => 'Lesson updated successfully',
                            'lesson_id' => $id // Add lesson_id to the response
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
        
        $this->f3->set('img_file' . $i, ${'img_file' . $i});
        
        //$this->f3->set('f3', $this->f3);
        $this->f3->set('url_img_file' . $i, ${'url_img_file' . $i});
        
        if ($id) {
            $data_detail = $this->db->exec("$select_records FROM toolbox_allied_lessons WHERE toolbox_allied_lessons.id=$id");
        }



        $template = new Template;
        $view = new View;
        # print_r($data_detail); die;
        $this->f3->set('data_detail', $data_detail[0]);
        $this->f3->set('base_url', $this->f3->get('base_url'));
        if (isset($data_detail[0]['id'])) {
            $this->f3->set('page_title', 'Edit Toolbox Lesson');
        } else {
            $this->f3->set('page_title', 'Add Toolbox Lesson');
        }

        $baseUrlOld = $this->f3->get('base_url_pdf');
        if (!empty($data_detail[0]['document1'])) {
            //echo $showflder."/course_lesson/".$data_detail[0]['document1'];

            $this->f3->set('document1_file', $flder . "/". $data_detail[0]['document1']);
            //$this->f3->set('f3', $this->f3);
            $this->f3->set('url_document1_file', $baseUrlOld . '/edge/downloads/' . $showflder . $data_detail[0]['document1']);
        }
        if (!empty($data_detail[0]['video1'])) {
           // die("test");
            $this->f3->set('video1_file', $flder . "/". $data_detail[0]['video1']);
            $this->f3->set('url_video1_file', $baseUrlOld . '/edge/downloads/' . $showflder . $data_detail[0]['video1']);
        }
        //  echo $flder.$data_detail[0]['document1'];
        //   die('dfd');
        $this->f3->set('course_id', $course_id);
        $this->f3->set('document_url', $documentUrl);
        $this->f3->set('job_suppliers', $job_suppliers);
        $this->f3->set('supplier_ids', $supplier_ids);

        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('toolbox/lessons/create.php');
        echo $template->render('footer.htm');
    }

//delete Lesson
    function deleteLesson() {
        $str = '';
        if (!empty($_POST)) {
            $id = $this->f3->get('POST.id');
            $str = $this->db->exec("UPDATE toolbox_allied_lessons SET status='2' WHERE id=" . $id);
        }
        echo $str;
        die;
    }

}

?>