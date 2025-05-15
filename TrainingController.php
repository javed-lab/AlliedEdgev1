<?php
class TrainingController extends Controller {
  protected $f3;

  function __construct($f3) {
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->db_init();
    $div_ids = $_COOKIE["RosteringDivisionId"];
    $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_ids ? $div_ids : 0));
    if($this->division_id) setcookie("RosteringDivisionId", $this->division_id, 2147483647);
  }

  function jump_to() {
    $division_id = $this->division_id;
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
    $itm = new input_item;
    $itm->hide_filter = 1;
    $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $str .= "
    <script>
    function select_page() {
      t = document.getElementById('hdncmbPageSelect').value;
      window.location = '$url?lookup_id=' + t;
    }
    </script>
    ";
    //$site_sql = $this->user_dropdown(384,104,0,0,0,1);
    $sql = "select training.id as `idin`, CONCAT(title, ' - ', if(training.division_id = 0, 'ALL', companies.item_name)) as `item_name` from training
    left join companies on companies.id = training.division_id
    where training.id != $lookup_id" . ($division_id == 'ALL' ? "" : " and division_id = $division_id or division_id = 0 order by division_id, companies.item_name, training.title");
        
          
    
    //return $sql;
    $str .= $itm->cmb("cmbPageSelect", "", "placeholder=\"Jump to...\" class=\"uk-search-input search-field\" style=\"width: 150px;\" onChange=\"select_page()\"  ", "", $this->dbi, $sql, "");
    return $str;
  }
  
  function Rules() {
    $main_folder = $this->f3->get('main_folder');    
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);

    $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
    $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);

    $this->list_obj->show_num_records = 0;
    //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
    $this->list_obj->sql = "
          select distinct(training_rules.id) as idin,
          if(training_rules.client_id = '' and training_rules.site_id = '', 'ALL SITES', CONCAT(users.name, ' ', users.surname, ' (', users.client_id, ')')) as `Client`,
          if(training_rules.client_id = '' and training_rules.site_id = '', 'ALL CLIENTS', CONCAT(users2.name, ' ', users2.surname, ' (', users2.client_id, ')')) as `Site`,
          training_rules.start_week as `Start Week`, training_rules.end_week as `End Week`,
          user_level.item_name as `Min User Lvl`,
          max_user_level.item_name as `Max User Lvl`,
          'Edit' as `*`, 'Delete' as `!`
          FROM training_rules
          left join users on users.id = training_rules.client_id
          left join users2 on users2.id = training_rules.site_id
          left join user_level on user_level.id = training_rules.min_user_level_id
          left join max_user_level on max_user_level.id = training_rules.max_user_level_id
          where training_id = $lookup_id
          order by training_rules.start_week
      ";

    //return $this->ta($this->list_obj->sql);
    //$this->editor_obj->add_now = "updated_date";
    //$this->editor_obj->update_now = "updated_date";

    
    $str .= '<div class="form-wrapper"><div class="form-header"><h3>Training Rules for '.$this->get_sql_result("select title as `result` from training where id = $lookup_id").' &nbsp; '.$this->jump_to().'
     &nbsp; <a class="list_a" href="'.$main_folder.'Training/Units?lookup_id='.$lookup_id.'">Units</a>
    </h3></div>';
    //$this->editor_obj->custom_field = "staff_id";
    //$this->editor_obj->custom_value = $_SESSION['user_id'];
    $this->editor_obj->table = "training_rules";
    $style = 'class="full_width"';

    $this->editor_obj->xtra_id_name = "training_id";
    $this->editor_obj->xtra_id = $lookup_id;

    $client_sql = $this->user_dropdown(104);
    $site_sql = $this->user_dropdown(384);


    $this->editor_obj->form_attributes = array(
             array("txtStartWeek", "txtEndWeek", "selMinUserLevel", "selMaxUserLevel", "cmbClient", "cmbSite"),
             array("Start Week", "End Week", "Min User Level", "Max User Level", "Client", "Site"),
             array("start_week", "end_week", "min_user_level_id", "max_user_level_id", "client_id", "site_id"),
             array("", "", $this->get_simple_lookup('user_level'), $this->get_simple_lookup('user_level'), $client_sql, $site_sql),
             array($style, $style, $style, $style, $style, $style),
             array("n", "n", "c", "c", "n", "n")
    );
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset"),
      array("cmdAdd", "cmdSave", "cmdReset"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
    );


    $this->editor_obj->form_template = '
              <div class="fl med_textbox"><nobr>tcmbClient</nobr><br />cmbClient</div>
              <div class="fl med_textbox"><nobr>tcmbSite</nobr><br />cmbSite</div>
              <div class="fl small_textbox"><nobr>ttxtStartWeek</nobr><br />txtStartWeek</div>
              <div class="fl small_textbox"><nobr>ttxtEndWeek</nobr><br />txtEndWeek</div>
              <div class="fl small_textbox"><nobr>tselMinUserLevel</nobr><br />selMinUserLevel</div>
              <div class="fl small_textbox"><nobr>tselMaxUserLevel</nobr><br />selMaxUserLevel</div>
              <div class="fl"><br /> &nbsp; &nbsp;'.$this->editor_obj->button_list().'</div>
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

    return $str;

    
  }

  



  function Matrix($set_matrix_id=0) {
    
    
    $main_folder = $this->f3->get('main_folder');    
    $str = "";
    $itm = new input_item;
    $itm->hide_filter = 1;
    
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);


    $set_matrix_id = ($set_matrix_id ? $set_matrix_id : (isset($_GET["set_matrix_id"]) ? $_GET["set_matrix_id"] : null));

    $matrix_complete_id = (isset($_GET["matrix_complete_id"]) ? $_GET["matrix_complete_id"] : null);
    if($matrix_complete_id) {
      $set_completed = $this->get_sql_result("select if(date_completed = '0000-00-00', 1, 0) as `result` from training_matrix where id = $matrix_complete_id");
      $sql = "update training_matrix set marked_completed_by_id = '" . $_SESSION['user_id'] . "', date_completed = ".($set_completed ? "CURRENT_DATE()" : "0000-00-00")." where id = $matrix_complete_id";
      $this->dbi->query($sql);
      echo ($set_completed ? Date("d-M-Y") . " <a href=\"JavaScript:set_completed('$matrix_complete_id')\">x</a>"
                           : "<a class=\"list_a\" href=\"JavaScript:set_completed('$matrix_complete_id')\">Set Completed</a>");
      exit;
    }

    $division_id = $this->division_id;
    if(!$lookup_id) {
      if($division_id == 'ALL') $division_id = 0;
      $str .= $this->division_nav($division_id, 'Training/Matrix', 0, 0);
    }
    $set_matrix = (isset($_POST["hdnSetMatrix"]) ? $_POST["hdnSetMatrix"] : ($set_matrix_id ? 1 : null));

    //$str .= "<h3>Training Matrix</h3>";

    if($set_matrix) {
      $calSetCompletedDate = (isset($_POST["calSetCompletedDate"]) ? $_POST["calSetCompletedDate"] : null);
      for($x = 1; $x <= 5; $x++) {
        $test .= (isset($_POST["hdncmbStaffSelect$x"]) ? $_POST["hdncmbStaffSelect$x"] : null);
      }
      if(!$test && $set_matrix == 2) {
        $sql = "update training_matrix set marked_completed_by_id = '" . $_SESSION['user_id'] . "', date_completed = " . ($calSetCompletedDate ? "'" . date('Y-m-d', strtotime($calSetCompletedDate)) . "'" : "CURRENT_DATE()") . " where ".($division_id && !$lookup_id ? "staff_id in (select user_id from users_user_groups where user_group_id = $division_id)" : "1")." and date_completed = '0000-00-00'";
        $this->dbi->query($sql);
        $msg = "Dates Updated...";
      } else {
        for($x = 1; $x <= 5; $x++) {
          $staff_id = (isset($_POST["hdncmbStaffSelect$x"]) ? $_POST["hdncmbStaffSelect$x"] : null);
          if($set_matrix_id) { $staff_id = $set_matrix_id; $x = 5; }
          if($staff_id) {
            $test = $this->get_divisions($staff_id, 0, 1);
            if($set_matrix == 1) {
              $user_level = $this->get_sql_result("select user_level_id as `result` from users where id = $staff_id");
              $sql = "insert ignore into training_matrix (staff_id, training_id, start_date, date_due)
              select '$staff_id', training_id,
              DATE_ADD(CURRENT_DATE(), interval start_week week),
              DATE_ADD(CURRENT_DATE(), interval (start_week + end_week) week)
              from training_rules where training_id in (select id from training where division_id in ($test, 0)) and min_user_level_id <= $user_level and max_user_level_id >= $user_level";
              $msg = "Staff Added to Matrix";
              //$str .= $this->ta($sql);
            } else {
              $sql = "update training_matrix set marked_completed_by_id = '" . $_SESSION['user_id'] . "', date_completed = " . ($calSetCompletedDate ? "'" . date('Y-m-d', strtotime($calSetCompletedDate)) . "'" : "CURRENT_DATE()") . " where staff_id = $staff_id and date_completed = '0000-00-00'";
              $msg = "Dates Updated...";
            }
            //$str .= $this->ta($sql);
            $this->dbi->query($sql);
            $str .= $this->message($msg, 3000);
          } 
        }
      }
      if($set_matrix_id && !$lookup_id) return 0;
    }
    //return "test: " . $this->ta($sql);
    
    $this->list_obj->sql = "
          select distinct(training_matrix.id) as idin,
          ".($lookup_id ? "" : "CONCAT(users.name, ' ', users.surname, ' (', users.employee_id, ')') as `Staff Member`,")." 
          training.title as `Training`,
          if(training.division_id = 0, 'ALL', companies.item_name) as `Division`,
          training_matrix.start_date as `Start Date`,
          training_matrix.date_due as `Date Due`,
          if(training_matrix.date_completed = '0000-00-00', CONCAT('<a class=\"list_a\" href=\"JavaScript:set_completed(', training_matrix.id, ')\">Set Completed</a>'), CONCAT(DATE_FORMAT(training_matrix.date_completed, '%d-%b-%Y'), ' <a href=\"JavaScript:set_completed(', training_matrix.id, ')\">x</a>')) as `Date Completed`
          ".($lookup_id ? "" : ", 'Edit' as `*`, 'Delete' as `!`")." 
          FROM training_matrix
          left join users on users.id = training_matrix.staff_id
          left join training on training.id = training_matrix.training_id
          left join companies on companies.id = training.division_id
          where 1
          ".($division_id && !$lookup_id ? " and (training.division_id = $division_id or users.id in (select user_id from users_user_groups where user_group_id = $division_id)) " : "")."
          ".($lookup_id ? " and training_matrix.staff_id = $lookup_id " : "")." 
          order by users.name
      ";


  //return $this->ta($this->list_obj->sql);
    $str .= '
    <script>
      function set_staff_matrix(val) {
        if(val == 2) {
          test = 1
        } else {
          finished = 0;  test = 0;
          for(x = 1; x <= 5; x++) {
            if(document.getElementById("hdncmbStaffSelect" + x).value) test = 1
          }
        }
        if(test) {
          document.getElementById("hdnSetMatrix").value = val
          document.frmEdit.submit()
        } else {
          alert("Please select one or more staff members before proceeding...")
        }
      }
      function set_completed(id) {
        $.ajax({
          type:"get",
              url:"'.$main_folder.'Training/Matrix",
              data:{ matrix_complete_id: id } ,
              success:function(msg) {
                document.getElementById(id + "-'.($lookup_id ? "5" : "6").'").innerHTML = msg
              }
        } );
      }
    </script>
    <input type="hidden" id="hdnSetMatrix" name="hdnSetMatrix" />
    ';

    if($lookup_id) {
      $str .= $this->list_obj->draw_list();
      if(!$this->list_obj->num_results) {
        //$this->Matrix($lookup_id);
        $str .= "<h3>No items in the Matrix</h3>";
        $str .= '<a class="list_a" href="'.$main_folder.'Training/Matrix?show_min=1&lookup_id='.$lookup_id.'&set_matrix_id='.$lookup_id.'">Update Matrix</a>';
        //$str .= $this->list_obj->draw_list();
      }
    } else {      
      $this->list_obj->form_nav = 1;
      $this->list_obj->num_per_page = 100;
      $this->list_obj->nav_count = 20;
      $this->list_obj->show_num_records = 1;
      $str .= '<div class="form-wrapper"><div class="form-header">';
      $staff_sql = $this->user_dropdown(107);
      for($x = 1; $x <= 5; $x++) {
        $str .= '<div class="fl">Staff #'.$x.'<br/>';
        $str .= $itm->cmb("cmbStaffSelect$x", "", "placeholder=\"Select Staff #$x...\" class=\"uk-search-input search-field\" style=\"width: 150px;\" onChange=\"\"  ", "", $this->dbi, $staff_sql, "");
        
        $js_str .= "document.getElementById('cmbStaffSelect$x').value = '';document.getElementById('hdncmbStaffSelect$x').value = 0;";
        
        $str .= '</div>';
      }
      $str .= '<div class="fl"><br/><input type="button" value= "Add to Matrix" onClick="set_staff_matrix(1)" />';
      $str .= $itm->cal("calSetCompletedDate", Date('d-M-Y'), "style=\"width: 100px; margin-left: 20px;\"", "", "", "", "");
      $str .= '<input type="button" value= "Set Completed" onClick="set_staff_matrix(2)" />';
      $str .= '</div><h3 class="fr">Training Matrix</h3><div class="cl"></div>';
      $str .= '</div>';
      
      if($set_matrix) $str .= $this->js_wrap($js_str);
      
      //$this->editor_obj->custom_field = "staff_id";
      //$this->editor_obj->custom_value = $_SESSION['user_id'];
      $this->editor_obj->table = "training_matrix";
      $style = 'class="full_width"';

      //$this->editor_obj->xtra_id_name = "training_id";
      //$this->editor_obj->xtra_id = $lookup_id;

      $training_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all select id, title as `item_name` from training) a order by item_name";

      $this->editor_obj->form_attributes = array(
               array("selTraining", "cmbStaffMember", "calStartDate", "calDateDue", "calDateCompleted"),
               array("Training", "Staff Member", "Start Date", "Date Due", "Date Completed"),
               array("training_id", "staff_id", "start_date", "date_due", "date_completed"),
               array($training_sql, $this->user_dropdown(107), "", "", ""),
               array($style, $style, $style, $style, $style),
               array("c", "c", "n", "c", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset"),
        array("cmdAdd", "cmdSave", "cmdReset"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
      );


      $this->editor_obj->form_template = '
                <div class="fl med_textbox"><nobr>tselTraining</nobr><br />selTraining</div>
                <div class="fl med_textbox"><nobr>tcmbStaffMember</nobr><br />cmbStaffMember</div>
                <div class="fl small_textbox"><nobr>tcalStartDate</nobr><br />calStartDate</div>
                <div class="fl small_textbox"><nobr>tcalDateDue</nobr><br />calDateDue</div>
                <div class="fl small_textbox"><nobr>tcalDateCompleted</nobr><br />calDateCompleted</div>
                <div class="fl"><br /> &nbsp; &nbsp;'.$this->editor_obj->button_list().'</div>
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
    } 
    
    

    
    return $str;
  }

  function MyTraining() {
    $main_folder = $this->f3->get('main_folder');    
    $user_id = $_SESSION['user_id'];
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
    $training_id_in = (isset($_GET["training_id"]) ? $_GET["training_id"] : null);
    $this->list_obj->show_num_records = 0;

    $str .= '
    <div class="form-wrapper" style="width: 100%;">
      <div class="form-header">
      My Training
      </div>
      <div class="form_content" id="form_content">
      ';

    $sql = "
        select distinct(id) as idin, title
        FROM training
        where id in (select training_id from training_matrix where staff_id = $user_id)
        order by title 
    ";
    if($result = $this->dbi->query($sql)) {
      $xtra = (!$training_id_in && !$lookup_id ? " selected" : "");
      $str .= "<a class=\"list_a$xtra\" href=\"{$main_folder}Training/MyTraining\">ALL</a>
      <style>
        .selected {
          background-color: #242E48;
          border-color: #242E48;
          color: white !important;
        }
      </style>
      ";
      while($myrow = $result->fetch_assoc()) {
        $training_id = $myrow['idin'];
        $division = $myrow['division'];
        $title = $myrow['title'];
        $xtra = ($training_id == $training_id_in ? " selected" : "");
        $str .= "<a class=\"list_a$xtra\" href=\"{$main_folder}Training/MyTraining?training_id=$training_id\">$title</a>";
      }
    }
    $str .= '</div></div>';

    
    if($lookup_id) {
      $sql = "select title, description as `training_notes` FROM training_units where id = $lookup_id";
      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $title = $myrow['title'];
          $training_notes = $myrow['training_notes'];
          $str .= "<h3>$title</h3>$training_notes";
        }
      }
    } else {
      $sql = "
          select distinct(training.id) as idin
          , if(training.division_id = 0, 'ALL', companies.item_name) as `division`, training.title
          FROM training
          left join companies on companies.id = training.division_id
          where training.id 
          ".($training_id_in ? " = $training_id_in " : " in (select training_id from training_matrix where staff_id = $user_id) order by division_id, companies.item_name, training.title ")."
      ";

      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          $training_id = $myrow['idin'];
          $division = $myrow['division'];
          $title = $myrow['title'];
          $sql = "
              select distinct(training_units.id) as idin,
              training_units.title as `title`,
              if(training_units.description != '', CONCAT('<a class=\"list_a\" href=\"{$main_folder}Training/MyTraining?lookup_id=', training_units.id ,'\"><span class=\"uk-margin-small-right\" data-uk-icon=\"icon: link\"></span>View Notes</a>'), '') as `notes`
              FROM training_units
              left join lookup_fields1 on lookup_fields1.id = training_units.training_type_id
              left join lookup_fields2 on lookup_fields2.id = training_units.training_type_id2
              where training_id = $training_id
              order by training_units.title
          ";
          if($result2 = $this->dbi->query($sql)) {
            $str .= "<h3>$title</h3>";
            while($myrow2 = $result2->fetch_assoc()) {
              $title = $myrow2['title'];
              $notes = $myrow2['notes'];
              $str .= "$title $notes<br/>";
            }
          }


        }
        //$str = "<p>$link_list</p>$tmp_str";
      }
    }
    return $str;
  }

//************************************************************************************************************************************************************************

  function Units() {
    $main_folder = $this->f3->get('main_folder');    

    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);

    $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
    $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);

    $this->list_obj->show_num_records = 0;
    //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
    $this->list_obj->sql = "
          select distinct(training_units.id) as idin
          , training_units.title as `Title`, if(training_units.description != '', 'Yes', 'No') as `Training Notes`, CONCAT(lookup_fields1.item_name, if(lookup_fields2.item_name = '', '', CONCAT(' / ', lookup_fields2.item_name))) as `Training Type(s)`, 
          if(duration = 0, '', duration) as `Duration`,
          'Edit' as `*`, 'Delete' as `!`
          FROM training_units
          left join lookup_fields1 on lookup_fields1.id = training_units.training_type_id
          left join lookup_fields2 on lookup_fields2.id = training_units.training_type_id2
          where training_id = $lookup_id
          order by training_units.title
      ";



    //return $this->ta($this->list_obj->sql);
    //$this->editor_obj->add_now = "updated_date";
    //$this->editor_obj->update_now = "updated_date";
    $str .= '<div class="form-wrapper"><div class="form-header"><h3>Training Units for '.$this->get_sql_result("select title as `result` from training where id = $lookup_id").' &nbsp; '.$this->jump_to().'
     &nbsp; <a class="list_a" href="'.$main_folder.'Training/Rules?lookup_id='.$lookup_id.'">Rules</a>
    </h3></div>
    ';
    //$this->editor_obj->custom_field = "staff_id";
    //$this->editor_obj->custom_value = $_SESSION['user_id'];
    $this->editor_obj->table = "training_units";
    $style = 'class="full_width"';

    $this->editor_obj->xtra_id_name = "training_id";
    $this->editor_obj->xtra_id = $lookup_id;

    $this->editor_obj->form_attributes = array(
             array("txtTitle", "cmsNotes", "selTrainingType1", "selTrainingType2", "txtDuration"),
             array("Title", "Training Notes", "Training Type 1", "Training Type 2", "Duration (Hours)"),
             array("title", "description", "training_type_id", "training_type_id2", "duration"),
             array("", "", $this->get_lookup('training_type'), $this->get_lookup('training_type'), ""),
             array($style, 'height: "280",	width: "98%"', $style, $style, $style),
             array("c", "n", "c", "n", "n")
    );

    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset"),
      array("cmdAdd", "cmdSave", "cmdReset"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
    );


    $this->editor_obj->form_template = '
              <div class="fl med_textbox"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div class="fl small_textbox"><nobr>ttxtDuration</nobr><br />txtDuration</div>
              <div class="fl small_textbox"><nobr>tselTrainingType1</nobr><br />selTrainingType1</div>
              <div class="fl small_textbox"><nobr>tselTrainingType2</nobr><br />selTrainingType2</div>
              <div class="fl"><br /> &nbsp; &nbsp;'.$this->editor_obj->button_list().'</div>
              <div class="cl"></div>
              <nobr>tcmsNotes</nobr><br />cmsNotes
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

    return $str;


    
  }
  function Courses() {
      
    $main_folder = $this->f3->get('main_folder');    

    $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
    $hdnFilter = (isset($_POST['hdnFilter']) ? $_POST['hdnFilter'] : null);

    $division_id = $this->division_id;
    if($division_id == 'ALL') $division_id = 0;
    $str .= $this->division_nav($division_id, 'Training/Courses', 0, 0);



    $this->list_obj->show_num_records = 0;
    //$this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
    $this->list_obj->sql = "
          select distinct(training.id) as idin
          , if(training.division_id = 0, 'ALL', companies.item_name) as `Division`
          , training.title as `Title`, lookup_fields.item_name as `Frequency`, training.description as `Description`
          , CONCAT(
          '<a class=\"list_a\" href=\"{$main_folder}Training/Units?lookup_id=', training.id, '\">Units</a>
          <a class=\"list_a\" href=\"{$main_folder}Training/Rules?lookup_id=', training.id, '\">Rules</a>'
          ) as `Actions`,
          'Edit' as `*`, 'Delete' as `!`
          FROM training
          left join companies on companies.id = training.division_id
          left join lookup_fields on lookup_fields.id = training.frequency_id
          ".($division_id ? "where training.division_id = $division_id or division_id = 0 " : "")."
          order by division_id, companies.item_name, training.title 
      ";

    //return $this->ta($this->list_obj->sql);
    //$this->editor_obj->add_now = "updated_date";
    //$this->editor_obj->update_now = "updated_date";
    $str .= '<div class="form-wrapper"><div class="form-header"><h3>Training Courses &nbsp; <a class="list_a" href="'.$main_folder.'Training/Matrix">Matrix</a></h3></div>';
    
    
    //$this->editor_obj->custom_field = "staff_id";
    //$this->editor_obj->custom_value = $_SESSION['user_id'];
    $this->editor_obj->table = "training";
    $style = 'class="full_width"';

    if($division_id) {
      $this->editor_obj->xtra_id_name = "division_id";
      $this->editor_obj->xtra_id = $division_id;
    }

    $this->editor_obj->form_attributes = array(
             array("txtTitle", "txtDescription", "selFrequency"),
             array("Title", "Description", "Frequency"),
             array("title", "description", "frequency_id"),
             array("", "", $this->get_lookup('training_frequency')),
             array($style, $style, $style),
             array("c", "n", "c")
    );
    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset"),
      array("cmdAdd", "cmdSave", "cmdReset"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
    );


    $this->editor_obj->form_template = '
              <div class="fl med_textbox"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div class="fl small_textbox"><nobr>tselFrequency</nobr><br />selFrequency</div>
              <div class="fl large_textbox"><nobr>ttxtDescription</nobr><br />txtDescription</div>
              <div class="fl"><br /> &nbsp; &nbsp;'.$this->editor_obj->button_list().'</div>
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

    return $str;
  }
  
  
}

?>