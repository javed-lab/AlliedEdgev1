<?php

class Employment extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
  }
  function Show() {
    session_start();
    $show_bare = (isset($this->show_bare) ? $this->show_bare : 0);
    $url = ($show_bare ? "Jobs" : "Employment");
    //header('X-Frame-Options: ALLOW-FROM: https://jobadonline.com/');
    //$employer_id = ($params['p1'] ? $params['p1'] : ($_SESSION['user_id'] ? md5($_SESSION['user_id']) : 0));
    
    $job_id = (isset($_GET["job_id"]) ? $_GET["job_id"] : null);
    $apply_now = (isset($_GET["apply_now"]) ? $_GET["apply_now"] : null);
    $more_info = (isset($_GET["more_info"]) ? $_GET["more_info"] : null);
    $state_id = (isset($_GET['state_id']) ? $_GET['state_id'] : 0);
    
    $msg = (isset($_GET['message']) ? $_GET['message'] : "");
    if($msg) {
      $str .= "<h3 class=\"message\">$msg</h3>";
    }
    
    
    if(!$more_info && !$apply_now) {
      $sql = "select id, item_name from states where id in (select state_id from job_ads where closing_date = '0000-00-00' or closing_date >= now()) order by item_name";
      $result = $this->dbi->query($sql);
      $done = 0;
      while($myrow = $result->fetch_assoc()) {
        $cid = $myrow['id'];
        $cat_name = $myrow['item_name'];
        if(!$done) {
          $str .= '<div class="category_item '.(!$state_id ? 'category_selected' : '').'">
                   <a class="category_a" href="'.$url.'">ALL STATES</a></div>';
          $done = 1;
        }
        $str .= '<div class="category_item '.($cid == $state_id ? 'category_selected' : '').'">
                 <a class="category_a" href="'.$url.'?state_id='.$cid.'">'.$cat_name.'</a></div>';
      }
      if($done) $str .= '<div class="cl" style="margin-bottom: 12px;"></div>';
    }
    
    if($job_id && $apply_now) {
      $action = $_POST["hdnAction"];

      $this->editor_obj = new data_editor;
      $this->editor_obj->hide_filter = 1;
      $this->editor_obj->bot_protect = 1;
      $this->editor_obj->table = "users";
      $this->editor_obj->title = "My Settings";
      $style = 'class="text"';
      $this->editor_obj->form_attributes = array(
               array("txtName", "txtSurname", "txtEmail", "txtPhone"),
               array("Given Name(s)", "Family Name", "Email", "Phone"),
               array("name", "surname", "email", "phone"),
               array("", "", "", ""),
               array($style, $style, $style, $style),
               array("c", "c", "c", "c")
      );
      $sql = "select title from job_ads where id = $job_id";
      if($result = $this->dbi->query($sql)) {
        $myrow = $result->fetch_assoc();
        $job_title = $myrow['title'];
        $title = '<h3 ' . ($_SESSION['user_id'] ? "" : 'class="message"') . ">Applying for {$myrow['title']}</h3>";
      }

      $pos = count($this->editor_obj->form_attributes[0]);
      
      $sql = "select job_ad_questions.id as idin,
              lookup_fields1.item_name as `type`, job_ad_questions.item_name as `question`, if(compulsory, 'c', 'n') as `compulsory`
              from job_ad_questions
              left join lookup_fields1 on lookup_fields1.id = job_ad_questions.type
              where job_ad_questions.id in (select question_id from job_ads_questions where job_ad_id = '$job_id')
              order by job_ad_questions.sort_order";

      if($action) {
//        $msql = "insert into job_applications (user_id, job_ad_id, status_id, date_applied, name, surname, email, phone) values ({$_SESSION['user_id']}, $job_id, 10, now()";
        $msql = "insert into job_applications (job_ad_id, status_id, date_applied, name, surname, email, phone) values ($job_id, 10, now()";
        for($x = 0; $x < $pos; $x++) {
          $itm = $_POST[$this->editor_obj->form_attributes[0][$x]];
          $itm_title = $this->editor_obj->form_attributes[1][$x];
          $field = $this->editor_obj->form_attributes[2][$x];
          $msql .= ", '$itm'";
          //$str .= "<h3>$itm_title: $itm</h3>";
        }
        $msql .= ");";
        //echo $msql;
        //exit;
        
        $this->dbi->query($msql);
        $job_app_id = $this->dbi->insert_id;
        
        //$str .= "<h3>$msql</h3>";
        
        $msql = "insert into job_application_answers (job_application_id, question, answer) values ";
        
        if($result = $this->dbi->query($sql)) {
          while($myrow = $result->fetch_assoc()) {
            $id = $myrow['idin'];
            $type = substr(strtolower($myrow['type']), 0, 3);
            $question = $myrow['question'];
            $itm = "";
            $dd['tex'] = "txt";    $dd['mul'] = "txa";    $dd['opt'] = "opt";    $dd['dro'] = "sel";    $dd['dat'] = "cal";
            if($type == 'tex' || $type == 'mul' || $type == 'opt' || $type == 'dro' || $type == 'dat') {
              $itm = $_POST["{$dd[$type]}$id"];
            } else if($type == 'che') {
              $sql2 = "select id, item_name from job_ad_question_choices where job_ad_question_id = $id order by sort_order";
              if($result2 = $this->dbi->query($sql2)) {
                while($myrow2 = $result2->fetch_assoc()) {
                  $opid = $myrow2['id'];
                  $item_name = $myrow2['item_name'];
                  if($_POST["chk$opid"]) $itm .= $item_name . ", ";
                }
                if($itm) $itm = substr($itm, 0, strlen($itm) - 2);
              }
            }
            if($itm) $msql .= "($job_app_id, '$question', '$itm'), ";
            //$str .= "<h3>$question: $itm</h3>";
          }
          if($msql) $msql = substr($msql, 0, strlen($msql) - 2) . ";";
          //$str .= "<h3>$msql</h3>";
          $this->dbi->query($msql);
          
          $upload_folder = $this->f3->get('upload_folder') . "cv/";
					if (!file_exists($upload_folder)) {
						mkdir($upload_folder);
						chmod($upload_folder, 0755);
					}
          $upload_folder = $this->f3->get('upload_folder') . "cv/$job_app_id/";
					if (!file_exists($upload_folder)) {
						mkdir($upload_folder);
						chmod($upload_folder, 0755);
					}
          
          $target_file = $upload_folder . basename($_FILES["fileToUpload"]["name"][0]);
          $target_file2 = $upload_folder . basename($_FILES["fileToUpload"]["name"][1]);
          //$str .= "<h3>$target_file</h3>";

          if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][0], $target_file)) {
            $str .= "The file ". basename( $_FILES["fileToUpload"]["name"][0]). " has been uploaded.";
          } else {
            if($_FILES['fileToUpload']['error'][0] != 4) $str .= "Sorry, there was an error uploading your file. Error: ";
          }
          $str = "<p>$str</p>";
          if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][1], $target_file2)) {
            $str .= "The file ". basename( $_FILES["fileToUpload"]["name"][1]). " has been uploaded." . $_POST['txtEmail'];
          } else {
            if($_FILES['fileToUpload']['error'][1] != 4) $str .= "Sorry, there was an error uploading your file. Error: ";
          }
          
          //$str = "<p>$str</p>";
          $mail = new email_q($this->dbi);
          $mail->AddAddress($_POST['txtEmail']);
          //$mail->AddAddress("eggaweb@gmail.com");
          $mail->Subject = "Job Application";
          $mail->Body    = "Thank you for your Job Application for $job_title.";
          $mail->queue_message();
          //$mail->send();
          //
          $msg = urlencode("Thank you for your Job Application for $job_title.<br /><br />Your Username is: " . $_SESSION['username'] . "<br /><br />Please take note of your username and password for future applications.");
          $this->f3->reroute("/MyJobApplications?message=$msg");
          
        }
      
      } else {
        $itm = new input_item;
        $itm->hide_filter = 1;
        $str .= $itm->setup_check();
        
        $this->list_obj = new data_list;
        $str .= '
          <style>
          .text, textarea {
            width: 99%;
          }
          textarea {
            height: 100px;
          }
          </style>
        ';
        
        $str .= $title;
       
        if($result = $this->dbi->query($sql)) {
          while($myrow = $result->fetch_assoc()) {
            $id = $myrow['idin'];
            $type = substr(strtolower($myrow['type']), 0, 3);
            $question = $myrow['question'];
            $compulsory = $myrow['compulsory'];

            if($type == 'tex' || $type == 'dat' || $type == 'mul') {
              $t = ($type == 'tex' ? "txt" : ($type == 'mul' ? 'txa' : 'cal'));
              $this->editor_obj->form_attributes[0][$pos] = "$t$id";
              $this->editor_obj->form_attributes[1][$pos] = $question;
              $this->editor_obj->form_attributes[4][$pos] = ($type == 'tex' || $type == 'dat' ? $style : "class=\"text_area\"");
              $this->editor_obj->form_attributes[5][$pos] = $compulsory;
              $itm_str .= "t$t$id<br />$t$id<br />";
            } else if($type == 'lab') {
              $itm_str .= "<h3>$question</h3>";
            } else if($type == 'opt') {
              //$itm_str .= "<h3>$question</h3>";
              //$itm_str .= ;
              $itm_str .= "<div class=\"cl\"></div><hr /><h3>$question</h3>";
              $itm_str .= "<div class=\"cl\"></div>";
              $sql2 = "select id, item_name from job_ad_question_choices where job_ad_question_id = $id order by sort_order";
              if($result2 = $this->dbi->query($sql2)) {
                while($myrow2 = $result2->fetch_assoc()) {
                  $opid = $myrow2['id'];
                  $itm_str .= $itm->opt("opt$opid|opt$id", $myrow2['item_name'], "", "", "", "");
                }
              }
              $itm_str .= "<div class=\"cl\"></div><hr />";
            } else if($type == 'dro') {
              $sql2 = "select 0 as sort_order, '--- Select ---' as item_name union all select id, item_name from job_ad_question_choices where job_ad_question_id = $id order by sort_order";
              $this->editor_obj->form_attributes[0][$pos] = "sel$id";
              $this->editor_obj->form_attributes[1][$pos] = $question;
              $this->editor_obj->form_attributes[3][$pos] = $sql2;
              $this->editor_obj->form_attributes[4][$pos] = $style;
              $this->editor_obj->form_attributes[5][$pos] = $compulsory;
              $itm_str .= "tsel$id<br />sel$id<br />";
            } else if($type == 'che') {
              $sql2 = "select id, item_name from job_ad_question_choices where job_ad_question_id = $id order by sort_order";
              $numchk = 0;
              if($result2 = $this->dbi->query($sql2)) {
                while($myrow2 = $result2->fetch_assoc()) {
                  $numchk++;
                  if($numchk == 1) {
                    $itm_str .= "<div class=\"cl\"></div><hr /><h3>$question</h3>";
                    $itm_str .= "<div class=\"cl\"></div>";
                  }
                  $opid = $myrow2['id'];
                  $itm_str .= $itm->chk("chk$opid|chk$opid", $myrow2['item_name'], "", "", "", "");
                }
              }
              if(!$numchk) $itm_str .= "<div class=\"cl\"></div><hr />" . $itm->chk("chk$id", $question, "", "", "", "");
              $itm_str .= "<div class=\"cl\"></div><hr />";
            }
            if(!($type == "lab" || $type == "opt" || $type == "che")) $pos++;
          }
        }
        
        
        $this->editor_obj->button_attributes = array(
          array("Apply Now"),
          array("cmdApply"),
          array("if(save()) this.form.submit()"),
          array("js_function_save", "js_function_add")
        );
        //return check_exists(tfiles)  onSubmit=""
        if(!$_SESSION['user_id']) {
          $str .= '
          <style>
          body {
            background-color: #333333 !important;
          }
          .choice_box {
            background-color: rgba(0,0,0,0.3);
            color: #FFFFFF !important;
            width: 435px;
            margin: auto;
            margin-top: 50px;
            padding: 20px;
            border: 1px solid white;
            border-radius: 12px 12px 12px 12px;
            -moz-border-radius: 12px 12px 12px 12px;
            -webkit-border-radius: 12px 12px 12px 12px;
          }
          
          

          </style>
          <div class="choice_box">
          <img style="margin-bottom: 15px;" src="' . $this->f3->get('img_folder') . 'logo.svg" height="120" alt="Allied Logo" />

          <h3>Existing Staff/Previous Applicants</h3>
                <a target="_top" href="login?page_from='.urlencode($_SERVER['REQUEST_URI']).'"><img src="'.$this->f3->get('img_folder').'login.png" alt="Login"></a>
                <h3 style="margin-top: 50px;">Or Create a New Account</h3>
                <a target="_top" href="SignUp?page_from='.urlencode($_SERVER['REQUEST_URI']).'" alt="Sign Up"><img src="'.$this->f3->get('img_folder').'signup.png" alt="Sign Up"></a>
          </div>';
        } else {
          $this->editor_obj->form_template = '
                    <p>*Required Fields.</p>
                    ttxtName<br />txtName<br />
                    ttxtSurname<br />txtSurname<br />
                    ttxtEmail<br />txtEmail<br />
                    ttxtPhone<br />txtPhone</br />
                      <div class="fileUpload">
                      <div class="fl"><h4>Cover Letter</h4><input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></div><div class="fl"><h4>Résumé</h4><input type="file" class="upload" name="fileToUpload[]" id="fileToUpload"></div>
                      <div class="cl"></div>
                      <hr />
                      </div>
                    '.$itm_str.'
                    <div class="cl"></div>
                    '.$this->editor_obj->button_list().'
          ';
          $this->editor_obj->editor_template = 'editor_form';
          $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        }
        $sql = "select name, surname, email, phone from users where id = " . $_SESSION['user_id'];
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) {
            $str .= '<script>
              document.getElementById("txtName").value = "'.$myrow['name'].'"
              document.getElementById("txtSurname").value = "'.$myrow['surname'].'"
              document.getElementById("txtEmail").value = "'.$myrow['email'].'"
              document.getElementById("txtPhone").value = "'.$myrow['phone'].'"
            </script>';
          }
        }
      }
      $str .= '<p><a class="list_a" href="'.$url.'">&lt;&lt; Back to Job Advertisements</a> <a class="list_a" href="'.$url.'?more_info='.$job_id.'">View Job Description &gt;&gt;</a></p>';
      
    } else if($more_info) {
      $sql = "select title, description from job_ads where id = $more_info";
      if($result = $this->dbi->query($sql)) {
        $myrow = $result->fetch_assoc();
        $title = $myrow['title'];
        $description = $myrow['description'];
      }
      $str .= "<h3>$title</h3><p>$description</p><a class=\"list_a\" href=\"".$this->f3->get('full_url')."$url?apply_now=1&job_id=$more_info\">Apply Now</a>";
    } else {
      $this->list_obj = new data_list;
      //$this->list_obj->title = "Positions Available";
      $this->list_obj->sql = "
            select
            states.item_name as `State`,
            title as `Job Title`,
            CONCAT('<a class=\"list_a\" href=\"".$url."?more_info=', job_ads.id, '\">More Info</a>') as `&nbsp;`,
            CONCAT('<div style=\"float: right;\"><a class=\"list_a\" href=\"$url?apply_now=1&job_id=', job_ads.id, '\">Apply Now</a></div>') as `&nbsp;&nbsp;&nbsp;`
            FROM job_ads
            left join states on states.id = job_ads.state_id
            where closing_date = '0000-00-00' or closing_date >= now()
            " . ($state_id ? " and state_id = $state_id " : "") . "
            order by sort_order
      ";
      $str .= $this->list_obj->draw_list();
    }
   //   $str .= "<pre>" . print_r($this->editor_obj->form_attributes, true) . "</pre>";
    /*$this->f3->set('content', $str);
    $this->f3->set('title', "Apply For Work");
    $template = new Template;
    echo $template->render('employment_layout.htm');
    */
    return $str;
  }
  
  function JobsOnline() {
    session_start();
    if($_SESSION['user_id']) echo $this->redirect("Employment?apply_now=".$_GET['apply_now']."&job_id=".$_GET['job_id']."");
    $this->show_bare = 1;
    $this->f3->set('content', $this->Show());
    $this->f3->set('title', "Apply For Work");
    $template = new Template;
    echo $template->render('employment_layout.htm');
  }
}