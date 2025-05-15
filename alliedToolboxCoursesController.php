<?php
class ToolboxAlliedCoursesController extends Controller {
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
        

        //Get sub job type
//        $job_type_id = 1;
          $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC"); 
            
          $this->f3->set('divisionList', $divisionList);  
//
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
        $this->f3->set('page_title', 'Course List');
        $this->f3->set('base_url', $this->f3->get('base_url')."/AlliedTraining/Courses/");
        //$this->f3->set('base_url_add', $this->f3->get('base_url')."/AlliedTraining/Courses/add");
        
          $select_records = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.is_required,ta_courses.`status`, ta_courses.`created_at`,division.`item_name` division_name,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_courses.id) as total_records";
        
        $afterFrom = " FROM training_allied_courses ta_courses "
                    . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                    . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                    . " WHERE $where_cond  ORDER BY $order_by $sort_order";
        
        
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/courses/index.php');
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
            'ta_courses.`created_at`',
            'division.`item_name`',
             'ta_courses.`title`',
            'ta_courses.`description`',
            'ta_courses.`is_required`',
            'ta_courses.`created_at`',
            //'ta_frequency.`title`',
            
        );
        
        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];



         $where_cond = " 1 ";
         $where_cond .= " and ta_courses.`status` !=2 ";
         if($_REQUEST['srch_division_id']){
             $where_cond .= " and division.`id` = '".$_REQUEST['srch_division_id']."'";
         }

          if ($_REQUEST['searchBox']) {
            $where_cond .= " and ta_courses.`title` like '%" . $_REQUEST['searchBox'] . "%'";
        }

        $select_records = "SELECT ta_courses.`id`, ta_courses.`title`, ta_courses.`description`, ta_courses.`is_required`,ta_courses.`status`,ta_courses.`created_at`,division.`item_name` division_name,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_courses.id) as total_records";
        
        $afterFrom = " FROM training_allied_courses ta_courses "
                    . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.allied_frequency_id  "
                    . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                    . " WHERE $where_cond  ORDER BY $order_by $sort_order";
        
        $listPaging = " LIMIT $length OFFSET $start";

        
       $countQuery = $select_num_rows.$afterFrom;
       $listQuery = $select_records.$afterFrom.$listPaging;
       
//       echo $listQuery;
//       die;
    
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
               $training_link = $base_url . "/AlliedTraining/AssignTraining?course_id=" . $val['id'];
               $lesson_link = $base_url . "/AlliedTraining/Lessons?course_id=". $val['id'];
               $edit_link = $base_url . '/AlliedTraining/Courses/Edit?course-id=' . $val['id'];
                $delete_link = "javaScript:delete_course(" . $val['id'] . ")";
                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
                $lesson ='<a title="Edit" class="green-a" href="' . $lesson_link . '"> Lessons </a> <br>';
                $trainingLink = '<a title="Edit" class="green-a" href="' . $training_link . '"> User-requisites  </a>';
                $edit =  '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
                $delete =  '<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                //$view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                    $allocation = '';
                    $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'title' => $val['title'],
                     'description' => substr(strip_tags($val['description']),0,100),
                    //'freq_title' => $val['freq_title'],
                    'is_required' => $val['is_required']?"Yes":"No",   
                    'division_name' => $val['division_name'],  
                    'created_at' => $val['created_at'], 
                    'lessonButton' => $lesson,   
                    'trainingButton' => $trainingLink,
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
      Create a new Courses
     */

     function create() {

          $this->manageCourse('create');
    }
    /*
      Updtae Courses
     */

     function edit($course_id = NULL) {
      $this->manageCourse('update', $course_id);
    }

    function manageCourse($type, $id = 0) {
     
      $template = new Template;
      $view = new View;
     
      $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC"); 
            
      $this->f3->set('divisionList', $divisionList);

      $trainingAlliedFrequencyList = $this->db->exec("SELECT id,title FROM training_allied_frequency ORDER BY title ASC"); 
            
      $this->f3->set('trainingAlliedFrequencyList', $trainingAlliedFrequencyList);
      
      if ($type != 'create') {
          $select_records = "SELECT *";
          $course_detail = $this->db->exec("$select_records FROM training_allied_courses WHERE training_allied_courses.id=$id");
         if (empty($course_detail[0]) || $course_detail[0]['status'] > 2) {
              //redirect to list page
              $this->f3->reroute('/course_detail');
          }
      } else {
          $course_detail = array();
      }

      //Update/Add Job          

      if (!empty($_POST)) {
        //print_r($_POST); die;
       
          if ($type == 'create' || !empty($course_detail[0])) {

              $error_mesg = '';

              $division_id = $this->f3->get('POST.division_id');
              $allied_frequency_id = $this->f3->get('POST.allied_frequency_id');
              $title = $this->f3->get('POST.title');
              $description = $this->f3->get('POST.description');
              $is_required = (!empty($this->f3->get('POST.is_required')))?1:0;
             


              /* Validation start */
              if (empty($title))
                  $error_mesg .= 'Course title is required<br>';
              if (empty($division_id))
                  $error_mesg .= 'Division is required<br>';
              if (empty($allied_frequency_id))
                  $error_mesg .= 'Allied frequency is required<br>';
              if (empty($description))
                  $error_mesg .= 'Course description is required<br>';
             

              if (!empty($error_mesg)) {
                  $data = array(
                      'code' => '404',
                      'mesg' => $error_mesg
                  );
                  echo json_encode($data);
                  die;
              }
              /* Validation end */

               

              $title = ucfirst($title);
              $updated_by = $_SESSION['user_id'];
              

              

//      echo 'job_start_date = '.$job_start_date.',job_end_date = '.$job_end_date.',quote_end_date = '.$quote_end_date;
//      die;
             
if ($type == 'create') {
                
  $insertQuery = "INSERT INTO `training_allied_courses` (`division_id`, `allied_frequency_id`, `is_required`, `title`, `description`)"
                          . " VALUES('$division_id','$allied_frequency_id',$is_required,'$title','$description')";
  
  
 

                  $course_id1 = $this->db->exec($insertQuery);
                 # echo $course_id1; die;
                  if (!empty($course_id1)) {
                      $course_id = $this->db->lastInsertId();
                  }
              } else {
                
               $upQuery = "UPDATE training_allied_courses SET division_id = '$division_id', allied_frequency_id = '$allied_frequency_id',`is_required`= '$is_required',title = '$title',description = '$description'  WHERE id = '$id'";
                  $this->db->exec($upQuery);
                  $course_id = $id;
              }
// echo $upQuery;
              // die;
              //die('update');
              if ($course_id) {

                  if ($type == 'create') {
                      $data = array(
                          'code' => '200',
                          'mesg' => 'Course created successfully'
                      );
                  } else {
                      $data = array(
                          'code' => '200',
                          'mesg' => 'Course updated successfully'
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

      if ($course_id) {
          $course_detail = $this->db->exec("$select_records FROM training_allied_courses WHERE training_allied_courses.id=$course_id");
      }
      

      $template = new Template;
      $view = new View;
      $this->f3->set('course_detail', $course_detail[0]);
      $this->f3->set('base_url', $this->f3->get('base_url'));
      if (isset($course_detail[0]['id'])) {
          $this->f3->set('page_title', 'Edit Course');
      } else {
          $this->f3->set('page_title', 'Add Course');
      }


     
//      $this->f3->set('document_url', $documentUrl);
//      $this->f3->set('job_suppliers', $job_suppliers);
//      $this->f3->set('supplier_ids', $supplier_ids);

      echo $template->render('header.htm');
      echo $template->render('nav.htm');
      echo $view->render('trainings/courses/create.php');
      echo $template->render('footer.htm');
  }

  //delete jCourseob
  function deleteCourse() {
    $str = '';
    if (!empty($_POST)) {
        $id = $this->f3->get('POST.id');
        $str = $this->db->exec("UPDATE training_allied_courses SET status='2' WHERE id=" . $id);
    }
    echo $str;
    die;
}
  
  
}

?>