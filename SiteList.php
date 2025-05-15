<?php

class SiteList extends Controller {
  function Show() {
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $upload_xl = (isset($_GET['upload_xl']) ? $_GET['upload_xl'] : null);
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    if($download_xl) {
      session_start();
      if($_SESSION['user_id']) {
        //include('Classes/data_list.class.php');
        $xl_obj = new data_list;
        $xl_obj->sql = "select site_name as `Site Name`, phone as `Phone`, email as `Email`, control_room_no as `CR Phone`, address as `Address`, suburb as `Suburb`, states.item_name as `State`, postcode as `Postcode`, abn as `ABN`,
                          acc_name as `Acc Mgr Name`, acc_phone as `Acc Mgr Phone`, acc_email as `Acc Mgr Email`,
                          ops_name as `Ops Mgr Name`, ops_phone as `Ops Mgr Phone`, ops_email as `Ops Mgr Email`,
                          cm_name as `Center Mgr Name`, cm_phone as `Center Mgr Phone`, cm_email as `Center Mgr Email`,
                          sup_name as `Site Super Name`, sup_phone as `Site Super Phone`, sup_email as `Site Super Email`
                          from site_contacts
                          left join states on states.id = site_contacts.state_id
                          order by site_contacts.site_name
                          ";
        $xl_obj->dbi = $this->dbi;
        $xl_obj->sql_xl('site_list.xlsx');
      }
    } else if($upload_xl) {
      if($_SESSION['page_access'] == 4) {
        $str .= '<h3><div class="fl">Upload Excel</div><div class="fr"><a class="list_a" href="?download_xl=1">Download Excel</a> &nbsp; &nbsp; &nbsp; &nbsp; <a class="list_a" href="?edit_mode=0">View Mode</a><a class="list_a" href="?edit_mode=1">Edit Mode</a></div><div class="cl"></div></h3>';
        $str .= '
          <ol>
          <li>Please <a href="?download_xl=1">Download the Excel</a> File first and save it before making changes.</li>
          <li><b>DO NOT</b> remove any of the columns from the Excel file.</li>
          </ol>
        </form>
        <form method="post" action="' . $this->f3->get('main_folder') . 'SiteList?upload_xl=1" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
        <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
        </form>';
        
        if ($_FILES["thefile"]["error"] > 0) {
          $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
        } else if($_FILES["thefile"]["name"]) {
          require_once('App/Controllers/PHPExcel.php');
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
          $this->dbi->query("truncate site_contacts;");
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            $sql = "insert into site_contacts (site_name, phone, email, control_room_no, address, suburb, state_id, postcode, abn, acc_name, acc_phone, acc_email, ops_name, ops_phone, ops_email, cm_name, cm_phone, cm_email, sup_name, sup_phone, sup_email) values (";
            $cnt = 0;
            foreach($rowData[0] as $k=>$v) {
              if(!$k && !$v) break;
              if($cnt < 21) {
                $v = mysqli_real_escape_string($this->dbi, trim($v));
                if($cnt == 6) {
                  $sql .= ", COALESCE((select id from states where item_name = '$v'), 0)";
                } else {
                  $sql .= ($cnt ? ", " : "") . "'$v'";
                }
              }
              $cnt++;
            }
            $sql .= ");";
            $this->dbi->query($sql);
                  }
          if($row > 2) {
            $str .= "<h3>Sites Updated...</h3>";
          }
        }
      }
    } else {
      $edit_mode = (isset($_GET['edit_mode']) ? $_GET['edit_mode'] : null);
      if($_POST['hdnUpdateEdge']) {
        foreach ($_POST as $key => $value) {
          if(strpos($key, 'chk') !== false) {
            $tmp = explode('_', $key);
            $rid = $tmp[1];
            $sql = "insert into users (name, surname, email, address, suburb, state, postcode, user_status_id) (select site_name, '(Site)', if(cm_email != '', cm_email, NULL), address, suburb, state_id, postcode, '30' from site_contacts where id = $rid)";
            $str .= "<h3>$sql</h3>";
            if($this->dbi->query($sql)) {
              $iid = $this->dbi->insert_id;
              $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($iid, 384, 'users');";
              $this->dbi->query($sql);
            }
          }
        }
      }
      if($_SESSION['user_id'] == 2) {
        $str .= '
          <input type="button" onClick="add_all()" value="Add Items to Edge" />
          <script>
            function add_all() {
              var confirmation;
              confirmation = \'Are you sure about adding these items to the main Edge Database?\';
              if (confirm(confirmation)) {
                document.getElementById("hdnUpdateEdge").value = 1;
                document.frmSiteSearch.submit();
              }
            }
          </script>
        ';
      }
      $search = (isset($_POST['txtFindSite']) ? $_POST['txtFindSite'] : null);
      $search_str = $search;
          if($search) $search = "where site_name LIKE '%$search%' or control_room_no LIKE '%$search%' or address LIKE '%$search%' or suburb LIKE '%$search%' or postcode LIKE '%$search%' or states.item_name LIKE '%$search%' or acc_name LIKE '%$search%' or acc_phone LIKE '%$search%' or acc_email LIKE '%$search%' or ops_name LIKE '%$search%' or ops_phone LIKE '%$search%' or ops_email LIKE '%$search%' or cm_name LIKE '%$search%' or cm_phone LIKE '%$search%' or cm_email LIKE '%$search%' or sup_name LIKE '%$search%' or sup_phone LIKE '%$search%' or sup_email LIKE '%$search%'";
      $main_sql = "select site_contacts.id as idin, " . ($_SESSION['user_id'] == 2 ? " CONCAT('<input type=\"checkbox\" name=\"chk_', site_contacts.id, '\"/>') as `^`, " : "") . " site_name as `Site Name`, phone as `Phone`
      , if(email != '', CONCAT('<a title=\"Company - ', email, '\" href=\"mailto:', email, '\">Email</a>'), '') as `Email`
      , control_room_no as `CR Phone`, CONCAT(address, ' ', suburb, ' ', states.item_name, ' ', postcode) as `Address`, suburb as `Suburb`, states.item_name as `State`, abn as `ABN`,
                        acc_name as `Name`, acc_phone as `Phone `, if(acc_email != '', CONCAT('<a  title=\"Account Manager - ', acc_email, '\" href=\"mailto:', acc_email, '\">Email</a>'), '') as `Email `,
                        ops_name as `Name `, ops_phone as `Phone  `, if(ops_email != '', CONCAT('<a  title=\"Ops Manager - ', ops_email, '\" href=\"mailto:', ops_email, '\">Email</a>'), '') as `Email  `,
                        cm_name as `Name  `, cm_phone as `Phone   `, if(cm_email != '', CONCAT('<a  title=\"Center Manager - ', cm_email, '\" href=\"mailto:', cm_email, '\">Email</a>'), '') as `Email   `,
                        sup_name as `Name   `, sup_phone as `Phone    `, if(sup_email != '', CONCAT('<a  title=\"Site Supervisor - ', sup_email, '\" href=\"mailto:', sup_email, '\">Email</a>'), '') as `Email    `
                        " .($edit_mode ? ", 'Edit' as `*`, 'Delete' as `!`" : "") ."
                        from site_contacts
                        left join states on states.id = site_contacts.state_id
                        $search
                        order by site_contacts.site_name
                        ";
      if(!$edit_mode) {
        $this->list_obj->sql = $main_sql;
      }
          $this->list_obj->top_row = '<tr><th colspan="' . ($_SESSION['user_id'] == 2 ? '9' : '8') . '" class="centre_bold" style="background-color: #FFC0CB;">Site Details</th>
                            <th colspan="3" class="centre_bold" style="background-color: #EAD2A7;">Account Manager (SCGS)</th>
                            <th colspan="3" class="centre_bold" style="background-color: #9FF0E5;">Operations Manager</th>
                            <th colspan="3" class="centre_bold" style="background-color: #F0EC7B;">Center Manager</th>
                            <th colspan="3" class="centre_bold" style="background-color: #F0C427;">Site Supervisor</th>
                            ' . ($edit_mode ? '<th colspan="2" style="background-color: #EEEEEE !important;">&nbsp;</th></tr>' : "");
      if($edit_mode) {
        $this->list_obj->sql = $main_sql;
        $list_top = "select 0 as id, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "site_contacts";
          $style = 'style="width: 180px;"';
          $style_sel = 'style="width: 186px;"';
          $style_small = 'style="width: 100px;"';
          $this->editor_obj->form_attributes = array(
                   array("txtSiteName", "txtPhone", "txtEmail", "txtSiteControlRoomNo", "txtAddress", "txtSuburb", "selState", "txtPostcode", "txtABN", "txtAccName", "txtAccPhone", "txtAccEmail", "txtOpsName", "txtOpsPhone", "txtOpsEmail", "txtCmName", "txtCmPhone", "txtCmEmail", "txtSupName", "txtSupPhone", "txtSupEmail"),
                   array("Name", "Phone", "Email", "Control Room No", "Address", "Suburb", "State", "Postcode", "ABN", "Account Manager Name", "Account Manager Phone", "Account Manager Email", "Ops Manager Name", "Ops Manager Phone", "Ops Manager Email", "Center Manager Name", "Center Manager Phone", "Center Manager Email", "Site Supervisor Name", "Site Supervisor Phone", "Site Supervisor Email"),
                   array("site_name", "phone", "email", "control_room_no", "address", "suburb", "state_id", "postcode", "abn", "acc_name", "acc_phone", "acc_email", "ops_name", "ops_phone", "ops_email", "cm_name", "cm_phone", "cm_email", "sup_name", "sup_phone", "sup_email"),
                   array("", "", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states order by item_name", "", "", "", "", "", "", "", "", "", "", "", "", "", ""),
                   array($style, $style, $style, $style, $style, $style, $style_sel, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style),
                   array("c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n")
          );
            $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
          );
        $this->editor_obj->form_template = '
                  <div class="fl"><nobr>ttxtSiteName</nobr><br />txtSiteName</div>
                  <div class="fl"><nobr>ttxtPhone</nobr><br />txtPhone</div>
                  <div class="fl"><nobr>ttxtEmail</nobr><br />txtEmail</div>
                  <div class="fl"><nobr>ttxtSiteControlRoomNo</nobr><br />txtSiteControlRoomNo</div>
                  <div class="fl"><nobr>ttxtAddress</nobr><br />txtAddress</div>
                  <div class="fl"><nobr>ttxtSuburb</nobr><br />txtSuburb</div>
                  <div class="fl"><nobr>tselState</nobr><br />selState</div>
                  <div class="fl"><nobr>ttxtPostcode</nobr><br />txtPostcode</div>
                  <div class="fl"><nobr>ttxtABN</nobr><br />txtABN</div>
                  <div class="fl"><nobr>ttxtAccName</nobr><br />txtAccName</div>
                  <div class="fl"><nobr>ttxtAccPhone</nobr><br />txtAccPhone</div>
                  <div class="fl"><nobr>ttxtAccEmail</nobr><br />txtAccEmail</div>
                  <div class="fl"><nobr>ttxtOpsName</nobr><br />txtOpsName</div>
                  <div class="fl"><nobr>ttxtOpsPhone</nobr><br />txtOpsPhone</div>
                  <div class="fl"><nobr>ttxtOpsEmail</nobr><br />txtOpsEmail</div>
                  <div class="fl"><nobr>ttxtCmName</nobr><br />txtCmName</div>
                  <div class="fl"><nobr>ttxtCmPhone</nobr><br />txtCmPhone</div>
                  <div class="fl"><nobr>ttxtCmEmail</nobr><br />txtCmEmail</div>
                  <div class="fl"><nobr>ttxtSupName</nobr><br />txtSupName</div>
                  <div class="fl"><nobr>ttxtSupPhone</nobr><br />txtSupPhone</div>
                  <div class="fl"><nobr>ttxtSupEmail</nobr><br />txtSupEmail</div>
                  <div class="cl"></div>
                  '.$this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                    <div class="form-wrapper">
                    <div class="form-header"><div class="fl">Site Contact List</div><div class="fr"><a class="list_a" href="?edit_mode=0">View Mode</a></div><div class="cl"></div></div>
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    <div class="cl"></div>
                    editor_list
        ';
      }

        $heading = '<div class="fl">Site Contact List</div><div class="fr input-group custom-search-form"><input placeholder="Find Sites by Name/Contact Details/Staff..." maxlength="100" name="txtFindSite" id="search" type="text" class="form-control"  style="padding: 2px !important; height: 28px !important;" value="'.$search_str.'" /><button onClick="this.form.submit()" name="cmdFollowSearch" value="Search" class="btn btn-default" style="margin-left: -4px;" /><i class="fa fa-search"></i></button>&nbsp; &nbsp; &nbsp; &nbsp; ' . ($_SESSION['page_access'] == 4 ? '<a class="list_a" href="?download_xl=1">Download Excel</a> <a class="list_a" href="?upload_xl=1">Upload Excel</a>&nbsp; &nbsp; &nbsp; &nbsp; ' : '') . '<a class="list_a" href="?edit_mode=' . ($edit_mode ? '0">View' : '1">Edit') . ' Mode</a></div><div class="cl"></div>';

//        $heading = '<div class="fl">Site Contact List</div><div class="fr"></form><form method="POST" name="frmSiteSearch"><input maxlength="50" placeholder="Find Sites by Name/Contact Details/Staff..." name="txtFindSite" id="search" type="text" class="search_box" value="'.$search_str.'" /><input type="submit" name="cmdFindSite" value="Search" class="search_button" />&nbsp; &nbsp; &nbsp; &nbsp; ' . ($_SESSION['page_access'] == 4 ? '<a class="list_a" href="?download_xl=1">Download Excel</a> <a class="list_a" href="?upload_xl=1">Upload Excel</a>&nbsp; &nbsp; &nbsp; &nbsp; ' : '') . '<a class="list_a" href="?edit_mode=' . ($edit_mode ? '0">View' : '1">Edit') . ' Mode</a></div><div class="cl"></div>';
      if($edit_mode) {
        $this->list_obj->title = $heading;
      } else {
        $this->list_obj->show_num_records = 1;
        $str .= "<h3>$heading</h3>";
      }
      $str .= ($edit_mode ? $this->editor_obj->draw_data_editor($this->list_obj) : $this->list_obj->draw_list());
      $str .= '
      <input type="hidden" id="hdnUpdateEdge" name="hdnUpdateEdge" />
      <input type="hidden" name="lookup_id" value="' . $lookup_id . '">
      </form>
      ';
      
    }
    return $str;
  }
}