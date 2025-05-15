<?php
class TrainingAlliedQuestionsController extends Controller {
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
        if($_REQUEST['lesson_id'] == 0){
            $this->f3->reroute('/AlliedTraining/Courses');
            $lession_id = 0;
        }else{
            $lession_id = $_REQUEST['lesson_id']; 
        }
        
        
       $dataDetailQuery = "SELECT ta_lessons.`id`, ta_lessons.`title` as lesson,ta_courses.`id`, ta_courses.`title`, ta_courses.`description`,ta_courses.`status`,division.`item_name` division_name,ta_frequency.`title` freq_title";
       
       $dataDetailQuery .= " FROM training_allied_lessons ta_lessons "
                    . " LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=ta_lessons.course_id  "
                    . " LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                    . " LEFT JOIN companies division ON division.id=ta_courses.division_id "
                    . " WHERE ta_lessons.`id` = '".$lession_id."'";

        $dataDetail = $this->db->exec($dataDetailQuery); 
        
        
        $select_num_rows = "SELECT count(ta_questions.id) as total_records";
        
        $afterFrom = " FROM  training_allied_lesson_questions ta_questions "
                    ." LEFT JOIN training_allied_lessons ta_lessons ON ta_lessons.id=ta_questions.lesson_id  ";              
          
       $countQuery = $select_num_rows.$afterFrom;
        
       $total_records = $this->db->exec($countQuery);
        $total_data = (!empty($total_records) && isset($total_records[0]['total_records'])) ? $total_records[0]['total_records'] : 0;
            

       # prd($dataDetail);
        $this->f3->set('total_data', $total_data);
        $this->f3->set('dataDetail', $dataDetail);
        $this->f3->set('lession_id', $lession_id);
        $this->f3->set('page_title', 'Questions List');
        $this->f3->set('base_url', $this->f3->get('base_url')."/AlliedTraining/Questions/");
        echo $template->render('header.htm');
        echo $template->render('nav.htm');
        echo $view->render('trainings/questions/index.php');
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
            'ta_questions.`created_at`',
            //'division.`item_name`',
             'ta_questions.`title`',
            'ta_questions.`description`'
            
        );
        
        $sortData = $this->f3->get('POST.order');
        $order_by = $columnData[$sortData[0]['column']];
        $sort_order = $sortData[0]['dir'];



         $where_cond = " 1 ";
         $where_cond .= " and ta_questions.status != 2 ";
         if($_REQUEST['lession_id']){
              $where_cond .= " and ta_questions.lesson_id = ".$_REQUEST['lession_id'];
         }else{
              $where_cond .= " and ta_questions.lesson_id = 0 ";
         }
         
        
         
         
         if($_REQUEST['searchBox']){
             $where_cond .= " and ta_questions.`question` like '%".$_REQUEST['searchBox']."%'";
         }


        $select_records = "SELECT ta_questions.`id`,ta_questions.`lesson_id`,ta_questions.`question`,ta_questions.`option_row`,ta_questions.`option1`,ta_questions.`option2`,ta_questions.`option3`,ta_questions.`option4`,ta_questions.`correct_option`,ta_questions.`status`,division.`item_name` division_name,ta_courses.title course_title,ta_frequency.`title` freq_title";
        $select_num_rows = "SELECT count(ta_questions.id) as total_records";
        
        $afterFrom = " FROM  training_allied_lesson_questions ta_questions "
                    ." LEFT JOIN training_allied_lessons ta_lessons ON ta_lessons.id=ta_questions.lesson_id  "                 
                    ." LEFT JOIN training_allied_courses ta_courses ON ta_courses.id=ta_lessons.course_id  "                 
                    ." LEFT JOIN training_allied_frequency ta_frequency ON ta_frequency.id=ta_courses.id  "
                    ." LEFT JOIN companies division ON division.id=ta_courses.division_id "
                    ." WHERE $where_cond  ORDER BY $order_by $sort_order";
        
        $listPaging = " LIMIT $length OFFSET $start";

        
       $countQuery = $select_num_rows.$afterFrom;
       $listQuery = $select_records.$afterFrom.$listPaging;
       //echo $listQuery; die;
      
    
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
               $question_link = $base_url . "/AlliedTraining/Questions?lesson_id=". $val['id'];
                $edit_link = $base_url . '/AlliedTraining/Questions/Edit?lesson_id='.$val['lesson_id'].'&id=' . $val['id'];
                $delete_link = "javaScript:delete_question(" . $val['id'] . ")";
                //$view_link = "javaScript:view_more_detail(" . $val['id'] . ")";
                $question ='';//'<a title="Edit" class="green-a" href="' . $question_link . '">Question</a>';
                $edit = '<a title="Edit" class="green-a" href="' . $edit_link . '"><span uk-icon="file-edit"></span></a>';
                $delete ='<a title="Delete" class="red-a" href="' . $delete_link . '"><span uk-icon="trash"></span></a>';
                //$view = '<a title="View detail" class="black-a" href="' . $view_link . '"><span uk-icon="more-vertical"></span></a>';
                //$allocation = '<a title="Job allocation" class="black-a" href="' . $allocation_link . '"><span uk-icon="cog"></span></a>';
//                if ($val['status'] != '0')
//                    $edit = $delete = '';
                    $allocation = '';
                    $jsonArray['data'][] = array(
                    'sr_no' => $start + $key + 1,
                    'question' => $val['question'],
                    'option_row' => $val['option_row'],
                    'option1' => mb_strimwidth($val['option1'], 0, 50, '...'),
                    'option2' => mb_strimwidth($val['option2'], 0, 50, '...'),                   
                    'option3' => mb_strimwidth($val['option3'], 0, 50, '...'),                   
                    'option4' => mb_strimwidth($val['option4'], 0, 50, '...'),                   
                    'correct_option' => $val['correct_option'],                   
                    'action' => $question .' '. $edit . ' ' . $delete,
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
        if($_REQUEST['lesson_id'] == 0){
            $this->f3->reroute('/AlliedTraining/Courses');
          $lesson_id = 0;
        }else{
            $lesson_id = $_REQUEST['lesson_id']; 
        }
        
        $this->manageLessons('create',$lesson_id);
      }
/*
  Updtae Lessons
 */

  function edit($lesson_id= NULL, $id = NULL) {
   $this->manageLessons('update', $lesson_id, $id);
  }

  function manageLessons($type,$lesson_id=0, $id = 0) {
    //echo $type; die;
    $template = new Template;
    $view = new View;
    $flder = $this->f3->get('download_folder') . "course_lesson/";
    $showflder = "course_lesson/";
   
    $trainingAlliedLessonsList = $this->db->exec("SELECT id,title FROM  training_allied_lessons WHERE training_allied_lessons.id='$lesson_id' ORDER BY title ASC"); 
          
    $this->f3->set('trainingAlliedLessonsList', $trainingAlliedLessonsList);
    //print_r($trainingAlliedLessonsList); die;
    if ($type != 'create') {
        $select_records = "SELECT *";
        $data_detail = $this->db->exec("$select_records FROM training_allied_lesson_questions WHERE training_allied_lesson_questions.id=$id");
       if (empty($data_detail[0]) || $data_detail[0]['status'] > 2) {
            //redirect to list page
            $this->f3->reroute('/data_detail');
        }
    } else {
        $data_detail = array();
    }

    //Update/Add Job          
   
    if (!empty($_POST)) {
        
        if ($type == 'create' || !empty($data_detail[0])) {

            $error_mesg = '';

            $lesson_id = $this->f3->get('POST.lesson_id');
            $question = $this->f3->get('POST.question');
            $option_row = $this->f3->get('POST.option_row');
            $option1 = $this->f3->get('POST.option1');
            $option2 = $this->f3->get('POST.option2');
            $option3 = $this->f3->get('POST.option3');
            $option4 = $this->f3->get('POST.option4');
            $correct_option = $this->f3->get('POST.correct_option');
            


            /* Validation start */
            if (empty($question))
                $error_mesg .= 'Question is required<br>';
            if (empty($lesson_id))
                $error_mesg .= 'Lesson is required<br>';
             if (empty($option_row))
                $error_mesg .= 'Option Row is required<br>';
            if (empty($option1))
                $error_mesg .= 'Option 1 is required<br>';
            if (empty($option2))
                $error_mesg .= 'Option 2 is required<br>';
            if (empty($option3))
                $error_mesg .= 'Option 3 is required<br>';
            if (empty($option4))
                $error_mesg .= 'Option 4 is required<br>';
            if (empty($correct_option))
                $error_mesg .= 'Currect Option is required<br>';     
            if (!empty($error_mesg)) {
                $data = array(
                    'code' => '404',
                    'mesg' => $error_mesg
                );
                echo json_encode($data);
                die;
            }
            /* Validation end */

             

            $question = ucfirst($question);
            $updated_by = $_SESSION['user_id'];
            
            $created_by = $_SESSION['user_id'];
            $updated_by = $_SESSION['user_id'];
       
          if ($type == 'create') {
                
            $insertQuery = "INSERT INTO `training_allied_lesson_questions` (`lesson_id`, `question`, `option_row`, `option1`, `option2`,`option3`,`option4`,`correct_option`, `created_by`, `updated_by`)"
                        . " VALUES('$lesson_id','$question','$option_row','$option1','$option2','$option3','$option4','$correct_option','$created_by','$updated_by')";
            $question_id1 = $this->db->exec($insertQuery);
               # echo $course_id1; die;
                if (!empty($question_id1)) {
                    $question_id = $this->db->lastInsertId();
                }
            } else {
              
             $upQuery = "UPDATE training_allied_lesson_questions SET lesson_id = '$lesson_id', question = '$question',option_row = '$option_row',option1 = '$option1',option2 = '$option2',option3='$option3',option4='$option4',correct_option='$correct_option',updated_by = '$updated_by'  WHERE id = '$id'";
             #echo $upQuery; die; 
             $this->db->exec($upQuery);
                $question_id = $id;
            }
           
            if ($question_id) {

                if ($type == 'create') {
                    $data = array(
                        'code' => '200',
                        'mesg' => 'Question created successfully',
                        'lesson_id' => $lesson_id,
                        'course_id' => $course_id // Add course_id to the response
                    );
                } else {
                    $data = array(
                        'code' => '200',
                        'mesg' => 'Question updated successfully',
                        'lesson_id' => $id,
                        'course_id' => $course_id // Add course_id to the response // Add course_id to the response
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
      $data_detail = $this->db->exec("$select_records FROM training_allied_lesson_questions WHERE training_allied_lesson_questions.id=$id");
    }
    
    

    $template = new Template;
    $view = new View;
   // print_r($data_detail); die;
    $this->f3->set('data_detail', $data_detail[0]);
    $this->f3->set('base_url', $this->f3->get('base_url'));
    if (isset($data_detail[0]['id'])) {
        $this->f3->set('page_title', 'Edit Question');
    } else {
        $this->f3->set('page_title', 'Add Question');
    }

    $baseUrlOld = $this->f3->get('base_url_pdf');
    
    $this->f3->set('lesson_id', $lesson_id);
    $this->f3->set('document_url', $documentUrl);
    $this->f3->set('job_suppliers', $job_suppliers);
    $this->f3->set('supplier_ids', $supplier_ids);

    echo $template->render('header.htm');
    echo $template->render('nav.htm');
    echo $view->render('trainings/questions/create.php');
    echo $template->render('footer.htm');
}

//delete Question
function deleteQuestion() {
  $str = '';
  if (!empty($_POST)) {
      $id = $this->f3->get('POST.id');
      $str = $this->db->exec("UPDATE training_allied_lesson_questions SET status='2' WHERE id=" . $id);
  }
  echo $str;
  die;
}
  
}

?>