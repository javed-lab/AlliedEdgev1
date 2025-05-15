<?php

class EditControllerbbb extends Controller {

    protected $f3;

    function __construct($f3) {
        $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $this->f3 = $f3;
        $this->list_obj = new data_list;
        $this->editor_obj = new data_editor;
        $this->filter_string = "filter_string";
        $this->db_init();
    }

    function tester() {
        $xl_obj = new data_list;
        $xl_obj = new data_list;
        $xl_obj->dbi = $this->dbi;
        $xl_obj->sql = "select item_name from  states";
        $xl_obj->sql_xl('tester.xlsx');
    }

    function NoticeBoard() {
        $this->list_obj->title = "";


        $this->list_obj->sql = "
          select notice_board.id as idin, notice_board.sort_order as `Display Order`, notice_board.date_modified as `Date Modified`, notice_board.closing_date as `Closing Date`, CONCAT(users.name, ' ', users.surname) as `Modified By`,
          notice_board.title as `Title`, notice_board.description as `Description`, 
          'Edit' as `*`, 'Delete' as `!`
          FROM notice_board
          left join users on users.id = notice_board.modified_by_id
          where sort_order < 500
          order by notice_board.sort_order
      ";

        $this->editor_obj = new data_editor;

        $this->editor_obj->add_now = "date_modified";
        $this->editor_obj->update_now = "date_modified";


        $this->editor_obj->custom_field = "modified_by_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "notice_board";

        $style = 'style="width: 240px;"';
        $style_large = 'style="width: 360px;"';
        $this->editor_obj->form_attributes = array(
            array("calClosingDate", "cmsDescription", "chlUserGroups", "txtSortOrder", "txtTitle"),
            array("Closing Date", "Description", "User Groups", "Display Order (Less than 500)", "Title (Optional)"),
            array("closing_date", "description", "id", "sort_order", "title"),
            array("", "", $this->get_chl('user_group'), "", ""),
            array($style, "", "", $style, $style_large),
            array("c", "n", "n", "c", ""),
            array("", "", "user_group", "", "")
        );

        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );

        $target_dir = $this->f3->get('base_img_folder') . "notice_board/";
        // return $target_dir;
        $show_dir = $this->f3->get('img_folder') . "notice_board/";
        $img_insert = 'Insert Image (<a href="' . $this->f3->get('main_folder') . 'MediaManager?target_dir=' . urlencode($target_dir) . '">Media</a>)<br /><select name="optContentPicture" onChange="JavaScript:insert_image(document.frmEdit.optContentPicture[document.frmEdit.optContentPicture.selectedIndex].value);"><option>-- Insert Image --</option>';
        $dir = new DirectoryIterator($target_dir);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                $file = $fileinfo->getFilename();
                $entry = $show_dir . $file;
                $img_insert .= "<option value=\"$entry\">$file</option>\n";
            }
        }
        $img_insert .= '</select>';

        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
              <div class="fl"><nobr>tcalClosingDate</nobr><br />calClosingDate</div>
              <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div style="float: left; height: 65px;">' . $img_insert . '</div>
              <div class="cl"></div>
              <div class="fl">tcmsDescription<br />
              cmsDescription
              <br />
              <div>tchlUserGroups<br />chlUserGroups</div>
              <div class="cl"></div>
              <br /><br />
              ' . $this->editor_obj->button_list();


        $this->editor_obj->editor_template = '
                <div class="fl form-wrapper" >
                <div class="form-header">News Editor</div>
                <div class="form-content" >
                editor_form
                </div>
                </div>
                </div>
                <div class="cl"></div>
                editor_list
                </div>
                <div class="cl"></div>
    ';



        if ($filter) {
            $this->editor_obj->xtra_js = $this->editor_obj->js_wrapper('document.getElementById("hdnFilter").value = "' . $filter . '";');
        }


        return $this->editor_obj->draw_data_editor($this->list_obj);
    }

    function MainPages() {
        $this->list_obj->title = "";

        $this->list_obj->sql = "
    SELECT main_pages2.id as `idin`, main_pages2.id as `ID`, main_pages2.parent_id as `PID`, main_pages_view.title as `Parent`, main_pages2.sort_order as `Sort Order`, main_pages2.title as `Title`, user_level.item_name as `User Level`, max_user_level.item_name as `Max User Level`, main_pages2.url as `URL`, page_types.item_name as `Page Type`,
    'Edit' as `*`, 'Delete' as `!`, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/PageAccess?pid=', main_pages2.id, '\">Access', '</a>') as `Access`, users.name as `Site`
    FROM main_pages2
    left join user_level on user_level.id = main_pages2.user_level_id
    left join max_user_level on max_user_level.id = main_pages2.max_user_level_id
    left join page_types on page_types.id = main_pages2.page_type_id
    left join main_pages_view on main_pages_view.id = main_pages2.parent_id
    left join users on users.id = main_pages2.site_id
    where 1
    $this->filter_string
    order by main_pages2.parent_id, main_pages2.sort_order
    ";

        //echo "<textarea>{$this->list_obj->sql}</textarea>";

        $site_sql = $this->user_dropdown(384);

        $this->editor_obj->table = "main_pages2";
        $style = 'style="width: 190px"';

        $this->editor_obj->form_attributes = array(
            array("cmbSite", "chlUserGroups", "txtSortOrder", "txtTitle", "cmsDescription", "txtURL", "selUserLevel", "selMaxUserLevel", "selParentID", "selPageType", "txtClass", "txtMetaDescription"),
            array("Site (Optional)", "User Groups", "Sort Order", "Title", "Description", "URL", "User Level", "Max User Level", "Parent", "Page Type", "Class", "Meta Description"),
            array("site_id", "sort_order", "sort_order", "title", "description", "url", "user_level_id", "max_user_level_id", "parent_id", "page_type_id", "class", "meta_description"),
            array($site_sql, "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') order by sort_order, item_name", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from user_level order by id", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from max_user_level order by id", "select 0 as id, '--- No Parent ---' as item_name union all select id, title from main_pages2 order by id", "select 0 as id, '--- Default ---' as item_name union all select id, item_name from page_types order by id"),
            array($style, "", $style, $style, 'height: "280",	width: "98%"', $style, $style, $style, $style, $style, $style, $style),
            array("n", "n", "c", "c", "u", "u", "u", "u", "u", "u", "u", "u"),
            array("", "user_group")
        );

        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );

        $this->editor_obj->form_template = '
      <table class="standard_form">
      <tr>
      <td class="form_header">Main Pages</td>
      </tr>
      <tr>
      <td>
      <div class="fl">ttxtSortOrder<br />txtSortOrder</div>
      <div class="fl">ttxtTitle<br />txtTitle</div>
      <div class="fl">ttxtURL<br />txtURL</div>
      <div class="fl">tselParentID<br />selParentID</div>
      <div class="fl">tselUserLevel<br />selUserLevel</div>
      <div class="fl">tselMaxUserLevel<br />selMaxUserLevel</div>
      <div class="fl">ttxtClass<br />txtClass</div>
      <div class="fl">ttxtMetaDescription<br />txtMetaDescription</div>
      <div class="fl">tselPageType<br />selPageType</div>
      <div class="fl">tcmbSite<br />cmbSite</div>
      <div class="cl"></div>
      <span class="field_header">tcmsDescription</span><br />
      cmsDescription
      <br />
      tchlUserGroups<br />
      chlUserGroups
      <div class="cl"></div>
      <hr />

      button_list
      </tr>
      </td>
      </table>
    ';

        //$this->editor_obj->editor_template = '<table><tr><td valign="top">editor_form</td><td valign="top">editor_list</td></tr></table>';
        $this->editor_obj->editor_template = 'editor_form<hr />editor_list';

        if ($this->action) {
            if ($this->action == "add_record") {
                if ($save_id = $this->editor_obj->last_insert_id)
                    $this->dbi->query("insert into page_access (page_id, group_id, access_level) values ($save_id, 114, 4), ($save_id, 115, 4);");
            }
            $_SESSION['side_menu'] = NULL;
            $_SESSION['custom_menu'] = NULL;
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }

        $str = $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<script>document.getElementById("chlUserGroups114").checked = true; document.getElementById("chlUserGroups115").checked = true;</script>';
        return $str;
    }
    
     function MenuLevel() {
         
        $lid = 0;
        $pid = 0;
//        print_r($_REQUEST);
//        die;
        if($_REQUEST['lid'])
        {
            $lid = $_REQUEST['lid']+1; 
        }else{
          $lid = 0+1;
         
        }
         
        if(isset($_REQUEST['pid']))
        {
            $pid = $_REQUEST['pid'];
        }else{
            $lid = 0+1;
           
        }
        
        if($pid > 0){
            
            $parentId = $this->parentMenuDetail($pid);
           
            if($parentId > 0){
               
                if($lid > 2){
                    $backlid = $lid - 2; 
                     $backButton = '<a class="list_a"  href="'. $this->f3->get('main_folder') . 'Edit/MenuLevel?lid='.$backlid.'&pid='.$parentId.'"> Back </a>'; 
                }else{
                    $backButton = '<a class="list_a"  href="'. $this->f3->get('main_folder') . ' Edit/MenuLevel"> Back </a>';  
                }
               
            }
            else{
                  
                 $backButton = '<a class="list_a"  href="'. $this->f3->get('main_folder') . 'Edit/MenuLevel"> Back </a>';  
            }
           
            
        }else{
            
             $backButton = '';
        }
      
       
         
         
        $this->list_obj->title = "";

        $this->list_obj->sql = "
    SELECT main_pages2.id as `idin`, main_pages2.id as `ID`, main_pages2.parent_id as `PID`, main_pages_view.title as `Parent`, main_pages2.sort_order as `Sort Order`, main_pages2.title as `Title`, user_level.item_name as `User Level`, max_user_level.item_name as `Max User Level`, main_pages2.url as `URL`, page_types.item_name as `Page Type`,
    'Edit' as `*`, 'Delete' as `!`, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/PageAccess?pid=', main_pages2.id, '\">Access', '</a>') as `Access`,CONCAT('<a class=\"list_a\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Edit/MenuLevel?lid=',$lid,'&pid=',main_pages2.id,'\"> Sublevel',$lid, '</a>') as `Sub Menu`, users.name as `Site`
    FROM main_pages2
    left join user_level on user_level.id = main_pages2.user_level_id
    left join max_user_level on max_user_level.id = main_pages2.max_user_level_id
    left join page_types on page_types.id = main_pages2.page_type_id
    left join main_pages_view on main_pages_view.id = main_pages2.parent_id
    left join users on users.id = main_pages2.site_id
    where 1 and main_pages2.parent_id = '".$pid."'
    $this->filter_string
    order by main_pages2.parent_id, main_pages2.sort_order
    ";

        //echo "<textarea>{$this->list_obj->sql}</textarea>";

        $site_sql = $this->user_dropdown(384);

        $this->editor_obj->table = "main_pages2";
        $style = 'style="width: 190px"';
        
        //$parentQuery = "select 0 as id, '--- No Parent ---' as item_name union all select id, title from main_pages2 where parent_id = 0 order by id";
        if($pid == 0){
              $parentQuery = "select 0 as id, '--- No Parent ---' as item_name order by id";
        }else{
           $parentQuery = "select id, title from main_pages2 where id = '".$pid."' order by id"; 
        }
      
      //  echo $parentQuery;
       // die;
        

        $this->editor_obj->form_attributes = array(
            array("cmbSite", "chlUserGroups", "txtSortOrder", "txtTitle", "cmsDescription", "txtURL", "selUserLevel", "selMaxUserLevel", "selParentID", "selPageType", "txtClass", "txtMetaDescription"),
            array("Site (Optional)", "User Groups", "Sort Order", "Title", "Description", "URL", "User Level", "Max User Level", "Parent", "Page Type", "Class", "Meta Description"),
            array("site_id", "sort_order", "sort_order", "title", "description", "url", "user_level_id", "max_user_level_id", "parent_id", "page_type_id", "class", "meta_description"),
            array($site_sql, "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') order by sort_order, item_name", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from user_level order by id", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from max_user_level order by id", $parentQuery, "select 0 as id, '--- Default ---' as item_name union all select id, item_name from page_types order by id"),
            array($style, "", $style, $style, 'height: "280",	width: "98%"', $style, $style, $style, $style, $style, $style, $style),
            array("n", "n", "c", "c", "u", "u", "u", "u", "u", "u", "u", "u"),
            array("", "user_group")
        );

        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        
        
        
       
    
        $this->editor_obj->form_template = '
      <table class="standard_form">
      <tr>
      <td class="form_header">Main Menu</td>
      <td class="form_header">'.$backButton.'</td>
      </tr>
      <tr>
      <td colspan=2>
      <div class="fl">ttxtSortOrder<br />txtSortOrder</div>
      <div class="fl">ttxtTitle<br />txtTitle</div>
      <div class="fl">ttxtURL<br />txtURL</div>
      <div class="fl">tselParentID<br />selParentID</div>
      <div class="fl">tselUserLevel<br />selUserLevel</div>
      <div class="fl">tselMaxUserLevel<br />selMaxUserLevel</div>
      <div class="fl">ttxtClass<br />txtClass</div>
      <div class="fl">ttxtMetaDescription<br />txtMetaDescription</div>
      <div class="fl">tselPageType<br />selPageType</div>
      <div class="fl">tcmbSite<br />cmbSite</div>
      <div class="cl"></div>
      <span class="field_header">tcmsDescription</span><br />
      cmsDescription
      <br />
      tchlUserGroups<br />
      chlUserGroups
      <div class="cl"></div>
      <hr />

      button_list
      </tr>
      </td>
      </table>
    ';

        //$this->editor_obj->editor_template = '<table><tr><td valign="top">editor_form</td><td valign="top">editor_list</td></tr></table>';
        $this->editor_obj->editor_template = 'editor_form<hr />editor_list';

        if ($this->action) {
            if ($this->action == "add_record") {
                if ($save_id = $this->editor_obj->last_insert_id)
                    $this->dbi->query("insert into page_access (page_id, group_id, access_level) values ($save_id, 114, 4), ($save_id, 115, 4);");
            }
            $_SESSION['side_menu'] = NULL;
            $_SESSION['custom_menu'] = NULL;
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }

        $str = $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<script>document.getElementById("chlUserGroups114").checked = true; document.getElementById("chlUserGroups115").checked = true;</script>';
        return $str;
    }
    
    
    function MenuLevel2() {
        $pid = 500;
        if($_REQUEST['pid'])
        {
            $pid = $_REQUEST['pid']; 
            
        }
        if($pid == 0){
            $pid = 500;
        }
        
        $this->list_obj->title = "";

        $this->list_obj->sql = "
    SELECT main_pages2.id as `idin`, main_pages2.id as `ID`, main_pages2.parent_id as `PID`, main_pages_view.title as `Parent`, main_pages2.sort_order as `Sort Order`, main_pages2.title as `Title`, user_level.item_name as `User Level`, max_user_level.item_name as `Max User Level`, main_pages2.url as `URL`, page_types.item_name as `Page Type`,
    'Edit' as `*`, 'Delete' as `!`, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/PageAccess?pid=', main_pages2.id, '\">Access', '</a>') as `Access`, users.name as `Site`
    FROM main_pages2
    left join user_level on user_level.id = main_pages2.user_level_id
    left join max_user_level on max_user_level.id = main_pages2.max_user_level_id
    left join page_types on page_types.id = main_pages2.page_type_id
    left join main_pages_view on main_pages_view.id = main_pages2.parent_id
    left join users on users.id = main_pages2.site_id
    where 1 and main_pages2.parent_id = '".$pid."'
    $this->filter_string
    order by main_pages2.parent_id, main_pages2.sort_order
    ";

        //echo "<textarea>{$this->list_obj->sql}</textarea>";

        $site_sql = $this->user_dropdown(384);

        $this->editor_obj->table = "main_pages2";
        $style = 'style="width: 190px"';
        
        $parentQuery = "select id, title from main_pages2 where id = '".$pid."' order by id";

        $this->editor_obj->form_attributes = array(
            array("cmbSite", "chlUserGroups", "txtSortOrder", "txtTitle", "cmsDescription", "txtURL", "selUserLevel", "selMaxUserLevel", "selParentID", "selPageType", "txtClass", "txtMetaDescription"),
            array("Site (Optional)", "User Groups", "Sort Order", "Title", "Description", "URL", "User Level", "Max User Level", "Parent", "Page Type", "Class", "Meta Description"),
            array("site_id", "sort_order", "sort_order", "title", "description", "url", "user_level_id", "max_user_level_id", "parent_id", "page_type_id", "class", "meta_description"),
            array($site_sql, "select id, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_group') order by sort_order, item_name", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from user_level order by id", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from max_user_level order by id", $parentQuery, "select 0 as id, '--- Default ---' as item_name union all select id, item_name from page_types order by id"),
            array($style, "", $style, $style, 'height: "280",	width: "98%"', $style, $style, $style, $style, $style, $style, $style),
            array("n", "n", "c", "c", "u", "u", "u", "u", "u", "u", "u", "u"),
            array("", "user_group")
        );

        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );

        $this->editor_obj->form_template = '
      <table class="standard_form">
      <tr>
      <td class="form_header">Main Menu</td>
      </tr>
      <tr>
      <td>
      <div class="fl">ttxtSortOrder<br />txtSortOrder</div>
      <div class="fl">ttxtTitle<br />txtTitle</div>
      <div class="fl">ttxtURL<br />txtURL</div>
      <div class="fl">tselParentID<br />selParentID</div>
      <div class="fl">tselUserLevel<br />selUserLevel</div>
      <div class="fl">tselMaxUserLevel<br />selMaxUserLevel</div>
      <div class="fl">ttxtClass<br />txtClass</div>
      <div class="fl">ttxtMetaDescription<br />txtMetaDescription</div>
      <div class="fl">tselPageType<br />selPageType</div>
      <div class="fl">tcmbSite<br />cmbSite</div>
      <div class="cl"></div>
      <span class="field_header">tcmsDescription</span><br />
      cmsDescription
      <br />
      tchlUserGroups<br />
      chlUserGroups
      <div class="cl"></div>
      <hr />

      button_list
      </tr>
      </td>
      </table>
    ';

        //$this->editor_obj->editor_template = '<table><tr><td valign="top">editor_form</td><td valign="top">editor_list</td></tr></table>';
        $this->editor_obj->editor_template = 'editor_form<hr />editor_list';

        if ($this->action) {
            if ($this->action == "add_record") {
                if ($save_id = $this->editor_obj->last_insert_id)
                    $this->dbi->query("insert into page_access (page_id, group_id, access_level) values ($save_id, 114, 4), ($save_id, 115, 4);");
            }
            $_SESSION['side_menu'] = NULL;
            $_SESSION['custom_menu'] = NULL;
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }

        $str = $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<script>document.getElementById("chlUserGroups114").checked = true; document.getElementById("chlUserGroups115").checked = true;</script>';
        return $str;
    }

    function DeleteSearchUser() {
        $userIds = $_REQUEST['id'];

        foreach ($userIds as $userId) {
            if ($userId != 5) {
                $sql_xtra = "delete from lookup_answers where foreign_id = " . $userId . " and table_assoc = 'users';";
                $this->dbi->query($sql_xtra);
                $str = "delete from users where id  = $userId";
                $this->dbi->query($str);
            }
        }

        echo 1;
        die;
        //$userId
        //
        //
    }
    
    function assignPageWithRole() {
        $userIds = $_REQUEST['id'];
        
        $userIds = $_REQUEST['roleId'];

        foreach ($userIds as $userId) {
            if ($userId != 5) {
                $sql_xtra = "delete from lookup_answers where foreign_id = " . $userId . " and table_assoc = 'users';";
                $this->dbi->query($sql_xtra);
                $str = "delete from users where id  = $userId";
                $this->dbi->query($str);
            }
        }

        echo 1;
        die;
        //$userId
        //
        //
    }

    function Tickets() {

        //if(isset($_POST['hdnFilter']))
        $this->list_obj->title = "Tickets";
        $this->list_obj->sql = "
      select id as idin, id as `ID`, title as `Title`, date as `Date`, time as `Time`, if(calculate_availability = 1, 'Yes', 'No') as `Calculate Availability`, 'Edit' as `*`, 'Delete' as `!` from tickets
      where 1 filter_string
      order by date ASC
      ";
        $this->editor_obj = new data_editor;
        $this->editor_obj->table = "tickets";

        $style = 'style="width: 280px;"';
        $this->editor_obj->form_attributes = array(
            array("txtTitle", "calDate", "ti2Time", "chkCalculateAvailability", "txtNumAvailable"),
            array("Title", "Date", "Time", "Calculate Availability", "Num Available"),
            array("title", "date", "time", "calculate_availability", "num_available"),
            array("", "", "", "", ""),
            array($style, $style, $style, '', $style),
            array("c", "n", "c", "n"),
            array("", "", "", ""),
            array("", "", "", "")
        );

        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div class="fl"><nobr>ttxtNumAvailable</nobr><br />txtNumAvailable</div>
              <div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
              <div class="fl"><nobr>tti2Time</nobr><br />ti2Time</div>
              <div class="fl"><nobr>chkCalculateAvailability tchkCalculateAvailability</nobr><br /></div>
              <div class="cl"></div>
              ' . $this->editor_obj->button_list();


        $this->editor_obj->editor_template = '
                <div class="form-wrapper" style="width: 100%;">
                <div class="form-header">Tickets</div>
                <div class="form-content">
                editor_form
                </div>
                </div>
                <div class="cl"></div>
                editor_list
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        //$str .= "<h3>SQL: " . $this->editor_obj->sql . "</h3>";
        return $str;
    }

    function Lookups() {
        //return $this->get_chl('user_group');


        $filter_string = "filter_string";
        $this->list_obj->title = "Lookups";
        $this->list_obj->sql = "
    	select lookups.id as idin, lookups.id as `ID`, lookups.item_name as `Item Name`, lookups.id as `ID`, lookups.sort_order as `Sort`, lookup_fields1.item_name as `Type`,
    	lookup_fields2.item_name as `Category`, lookups.description as `Description`, lookups.regex as `Validation`, 'Edit' as `*`, 'Delete' as `!`
    	from lookups
    	left join lookup_fields1 on lookup_fields1.id = lookups.type
    	left join lookup_fields2 on lookup_fields2.id = lookups.lookup_group_id
      where 1 $filter_string
    	order by lookups.sort_order, lookups.item_name
      ";

        $this->list_obj->show_num_records = true;
        $this->editor_obj->table = "lookups";
        $style = 'style="width: 200px;"';
        $style_small = 'style="width: 100px;"';
        $this->editor_obj->form_attributes = array(
            array("txtSortOrder", "chlUserGroups", "txtItemName", "selInputType", "selLookupGroup", "txtDescription", "txtValidation"),
            array("Sort Order", "User Groups", "Name", "Type", "Category", "Description", "Validation"),
            array("sort_order", "item_name", "item_name", "type", "lookup_group_id", "description", "regex"),
            array("", $this->get_chl('user_group'), "", $this->get_lookup('input_type'),
                $this->get_lookup('lookup_groups'), "", ""),
            array($style_small, "", $style, "", "", 'style="width: 667px;"', $style_small),
            array("n", "n", "c", "c", "n", "n", "n"),
            array("", "user_group", "", "", "", "", ""),
            array("", "", "", "", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>tselLookupGroup</nobr><br />selLookupGroup</div>
              <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
              <div class="fl"><nobr>ttxtValidation</nobr><br />txtValidation</div>
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName<br /></div>
              <div class="fl"><nobr>tselInputType</nobr><br />selInputType</div>
              <div class="fl"><nobr>ttxtDescription</nobr><br />txtDescription<br /></div>
              <div class="cl"></div>
    					<hr />
    					tchlUserGroups<br />
    					chlUserGroups
              <div class="cl"></div><br />
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div style="float: left; ">
                <div class="form-wrapper" style="max-width: 700px;">
                <div class="form-header">Lookups</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
                editor_list
    						</div>
    ';
        if ($_POST['idin']) {
            $this->editor_obj->editor_template .= '
    						<div style="float: left; width: 800px; margin-left: 15px;">
    						<iframe style="border: none; width: 100%; height: 1000px;" src="' . $this->f3->get('main_folder') . 'Edit/LookupItems?show_min=1&lookup_id=' . $_POST['idin'] . '"></iframe>
    						</div>
    	';
        }
        $this->editor_obj->editor_template .= '
                <div class="cl"></div>
    ';

        //prd($this->list_obj);

        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        // prd($str);
        return $str;
    }

//Specific Functions to Edit Various Lists

    function OccurrenceKeywords() {
        return $this->LookupItems(123, "Occurrence Keywords", "Keywords (Separated By Commas)");
    }

    function UserGroups() {
        return $this->LookupItems(21, "User Groups");
    }

    function LookupItems($lookup_id = "", $title = "", $value_title = "", $hide_value = 0) {
        $show_max = (isset($_REQUEST['show_max']) ? $_REQUEST['show_max'] : null);

        $title = ($title ? $title : (isset($_GET["title"]) ? $_GET["title"] : "Lookup Items"));
        $value_title = ($value_title ? $value_title : (isset($_GET["value_title"]) ? $_GET["value_title"] : "Value"));
        $lookup_id = ($lookup_id ? $lookup_id : (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null));



//    $filter_string = "filter_string";
        $filter_string = "";
        $this->list_obj->title = $title;



        $this->list_obj->sql = "
    	select lookup_fields.id as idin, lookup_fields.sort_order as `Sort`, lookup_fields.item_name as `Item Name`,
      " . ($hide_value ? "" : "lookup_fields.value as `$value_title`, ") . "
      'Edit' as `*`, 'Delete' as `!` from lookup_fields
      where lookup_id = $lookup_id
      $filter_string
    	order by sort_order, lookup_fields.item_name
      ";
        //return $this->list_obj->sql;
        $list_top = "select 0 as id, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "lookup_fields";
        $style_small = 'style="width: 100px;"';
        $style = 'style="width: 190px;"';
        $style_large = 'style="width: 385px; height: 60px;"';
        $this->editor_obj->form_attributes = (
                array(array("txtSortOrder", "txtItemName"),
                    array("Sort Order", "Name"),
                    array("sort_order", "item_name"),
                    array("", "", ""),
                    array($style_small, $style_large, $style_large),
                    array("c", "c", "n"),
                    array("", "", ""),
                    array("", "", "")
                )
                );

        if (!$hide_value) {
            $this->editor_obj->form_attributes[0][2] = "txaValue";
            $this->editor_obj->form_attributes[1][2] = $value_title;
            $this->editor_obj->form_attributes[2][2] = "value";
        }
        //print_r($this->editor_obj->form_attributes);
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName</div>
              ' . ($hide_value ? '' : '<div class="fl"><nobr>ttxaValue</nobr><br />txaValue<br /></div>') . '
              <div class="cl"></div>
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div class="form-wrapper" style="width: 100%;">
                <div class="form-header">' . $title . '</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
    						<div class="cl"></div>
                editor_list
    ';
        $this->editor_obj->xtra_id_name = "lookup_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '"><input type="hidden" name="show_max" value="' . $show_max . '">';

        return $str;
    }

    function Ofi() {
        $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        $filter_string = "filter_string";
        $this->list_obj->title = "Bug Report";
        $this->list_obj->sql = "
    			select ofi.id as idin, CONCAT(users.name, ' ', users.surname) as `Raised By`, CONCAT(users2.name, ' ', users2.surname) as `Modified By`, 
          REPLACE(ofi.description, '\r\n', '<br />') as `Description`,
    			ofi.discussion_date as `Date Added`, ofi.updated_date as `Last Updated`, 
    			'Edit' as `*`, 'Delete' as `!`
    			FROM ofi
    			left join lookup_fields1 on lookup_fields1.id = ofi.opportunity_type_id
    			left join lookup_fields2 on lookup_fields2.id = ofi.identified_through_id
    			left join lookup_fields3 on lookup_fields3.id = ofi.type_id
          left join users on users.id = ofi.raised_by_id
          left join users2 on users2.id = ofi.modified_by_id
    		  where 1 $filter_string
          order by discussion_date DESC
      ";
        $this->editor_obj->table = "ofi";
        $this->editor_obj->custom_add_only = 1;
        $this->editor_obj->custom_add_only2 = 1;

        $this->editor_obj->custom_field = "raised_by_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->custom_update_field = "modified_by_id";
        $this->editor_obj->custom_update_value = $_SESSION['user_id'];

        $this->editor_obj->custom_field2 = "discussion_date";
        $this->editor_obj->custom_value2 = date('Y-m-d H:i:s');
        $this->editor_obj->custom_update_field2 = "updated_date";
        $this->editor_obj->custom_update_value2 = date('Y-m-d H:i:s');


        $style = 'style="width: 220px;"';
        $description_box = 'style="width: 98%; height: 150px;"';
        $this->editor_obj->form_attributes = array(
            array("txaDescription"),
            array("Please provide a detailed description of the bug/error or request"),
            array("description"),
            array(""),
            array($description_box),
            array("c"),
            array(""),
            array("")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
              <h3>Bug Report</h3>
    					ttxaDescription<br />txaDescription
              ' . $this->editor_obj->button_list();

        $this->editor_obj->editor_template = '
                editor_form
    						<div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                editor_list
    ';
        $str = $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function Users() {

        if(isset($_GET['get_divisions_ids'])) {
            $get_divisions_ids = (isset($_GET['get_divisions_ids']) ? $_GET['get_divisions_ids'] : 0);
            $searchId = $get_divisions_ids;
            $html = $this->alliedDivision($get_divisions_ids);
            echo $html;
            exit();
        }
        
        //$this->mainMenuWithSubMenu();
        //$this->hiddenMainMenuWithSubMenu();

        $anniversaries = (isset($_GET['anniversaries']) ? $_GET['anniversaries'] : null);
        $dobs = (isset($_GET['dobs']) ? $_GET['dobs'] : null);
        $show_nav = (isset($_GET['show_nav']) ? $_GET['show_nav'] : null);
        $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
        //prd($action);
        if ($anniversaries || $dobs) {

            $str .= "<h3>" . ($anniversaries ? 'Anniversaries' : 'Birth Dates') . "</h3>";
            $tbl = ($anniversaries ? "commencement_date" : "dob");
            if ($show_nav) {
                $nav = new navbar;
                $str .= $nav->draw_navbar();
                $filter_string = $nav->get_filter($tbl);
                $str .= '<a class="list_a" href="?' . ($aniversaries ? 'anniversaries' : 'dobs') . '=1">Just Show Today</a>';
            } else {
                $filter_string = " and month($tbl) = month(now()) and day($tbl) = day(now()) ";
                $str .= '<a class="list_a" href="?' . ($aniversaries ? 'anniversaries' : 'dobs') . '=1&show_nav=1">Show Whole Month</a>';
            }
            $str .= ($anniversaries ? '<a class="list_a" href="?dobs=1&show_nav=' . $show_nav . '">Birth Dates</a>' : '<a class="list_a" href="?anniversaries=1&show_nav=' . $show_nav . '">Anniversaries</a>');
            $this->list_obj->show_num_records = 1;
            $this->list_obj->sql = "    SELECT id as `idin`, employee_id as `Employee ID`, name as `First Name`, surname as `Surname`, CONCAT('<a href=\"mailto:', email, '\">', email, '</a>') as `Email`, phone as `Phone`, company as `Company`,
                $tbl as `" . ($anniversaries ? 'Start Date' : 'DOB') . "`
                FROM users
                where employee_id != '' and user_status_id = (select id from user_status where item_name = 'ACTIVE') and user_level_id >= 300 $filter_string
                order by $tbl
      ";
            //echo "<textarea>{$this->list_obj->sql}</textarea>";
            $str .= $this->list_obj->draw_list();
        } else {
            $edit_id = (isset($_POST['idin']) ? $_POST['idin'] : null);





            if (isset($_POST['hdnImage'])) {
                $img = $_POST['hdnImage'];
            }

            //$next_empid = $this->get_sql_result("SELECT CONCAT('K', substring(max(employee_id), 2) + 1) as `result` FROM users where LEFT(employee_id, 1) = 'K' and substring(employee_id, 2) REGEXP '^-?[0-9]+$'");
            //$next_cid = $this->get_sql_result("SELECT CONCAT('C', substring(max(client_id), 2) + 1) as `result` FROM users where LEFT(client_id, 1) = 'C' and substring(client_id, 2) REGEXP '^-?[0-9]+$'");
            $str .= '<div style="visibility: hidden; height: 0px;"><input type="password"></div>';
            //$str .= "<div class=\"fl\">Next Available Employee ID: $next_empid -- <a class=\"list_a\" onClick=\"JavaScript:document.getElementById('txtEmployeeID').value='$next_empid';\">Use</a> &nbsp;";
            //$str .= "Next Available Client ID: $next_cid -- <a class=\"list_a\" onClick=\"JavaScript:document.getElementById('txtClientID').value='$next_cid';\">Use</a></div>";
            $uid = $_REQUEST["idin"];
            //prd($uid);
            if ($uid) {
                $str .= "<div class=\"fr\"><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$uid\">Open Card</a></div>";
            }
            $str .= "<div class=\"cl\"></div>";
            //$str .= "<p>Next Available Student ID: $nextid -- [<a style=\"cursor: hand;\" onClick=\"JavaScript:document.getElementById('txtStudentID').value='$nextid';\">Use</a>]</p>";
            $str .= '
        <script language="JavaScript">
        function edit_record2(idin, empid, clid, suppid, stuid) {
              document.getElementById("idin").value = idin;
              document.getElementById("txtEmployeeID").value = empid;
              document.getElementById("txtClientID").value = clid;
              document.getElementById("txtSupplierID").value = suppid;
              //document.getElementById("txtID").value = id;
              document.getElementById("hdnFilter").value = 1;
              document.frmEdit.action = "Edit/Users";
              document.frmEdit.submit();
        }
        </script>
      ';
            $filter_string = "filter_string";
            $this->list_obj->show_num_records = 1;
            if ($_SESSION['u_level'] >= 1000 || $hr_user) {
                $xtra3 = ", 'Edit' as `*`";
            }

            $form_obj = $this->editor_obj;
            $this->list_obj->sql = "
        SELECT users.id as `idin`, CONCAT(if(employee_id IS NOT NULL && employee_id != '', CONCAT(employee_id, '<br />'), ''), if(client_id  IS NOT NULL && client_id != '', CONCAT(client_id, '<br />'), ''), if(supplier_id IS NOT NULL && supplier_id != '', CONCAT(supplier_id, '<br />'), '')) as `User IDs`, users.name as `First Name`, users.surname as `Surname`, users.email as `Email`, users.email2 as `Email 2`, users.phone as `Phone`,
               users.company as `Company`, user_level.item_name as `User Level`, users.commencement_date as `Start Date`, users.dob as `DOB`,
               CONCAT('<a class=\"list_a\" uk-tooltip=\"title: View User Card\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=', users.id, '\">Card</a>')
                      as `**` $xtra3
                      , 'Delete' as `!`
               FROM users
               left join user_level on user_level.id = users.user_level_id
               left join lookup_fields1 on lookup_fields1.id = users.main_site_id
               where users.user_level_id <= " . $_SESSION['u_level'] . " 
               $filter_string
        ";

//            echo $this->list_obj->sql;
//            die;
//      echo "<textarea>{$this->list_obj->sql}</textarea>";
            $style = 'style="width: 200px;"';
            $styleMulti = 'style="width: 500px;height: 150px !important;"';
            $style_med = 'style="width: 500px;"';
            $style_med = 'style="width: 170px;"';
            $style_small = 'style="width: 90px;"';
            $form_obj->xtra_validation = '
                if(document.getElementById("pw1").value != document.getElementById("pw2").value){    
                    err++;
                    error_msg += err + ". This password and confirm password not match.\\n";
                }
                
//        if(document.getElementById("hdnExistingEmail").value == "yes" && document.getElementById("txtEmail").value != "") {
//          err++;
//          error_msg += err + ". This Email address (" + document.getElementById("txtEmail").value + ") already exists, please try another email address.\\n";
//        }
     ';

            /* second Part */

            $list_top = "select 0 as id, '--- Select ---' as item_name union all";
            $this->editor_obj->table = "users";

            //$lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, " . ($bd ? "REPLACE(item_name, ' - Lead', '')" : "item_name") . " from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') and item_name " . ($bd ? "" : "NOT") . " LIKE('%- Lead')) a order by sort_order, item_name";
            $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, " . ($bd ? "REPLACE(item_name, ' - Lead', '')" : "item_name") . " from lookup_fields where lookup_id = 107 AND item_name IN ('Client','Location','User','Supplier') and item_name " . ($bd ? "" : "NOT") . " LIKE('%- Lead')) a order by sort_order, item_name";
            //return $lookup_sql;
            $division_sql = "select id, item_name from companies order by item_name";
            $pfix = ($bd ? "Rep " : "");

            /* $parent_company_sql = "select * from (select 0 as id, 0 as `sort_order`, '--- Select ---' as item_name union all 
              SELECT users.id, users.name as `sort_order`, concat (users.client_id, ' - ', users.name, ' ', users.surname) as `item_name`
              FROM users
              left join user_status on user_status.id = users.user_status_id
              inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 104 and lookup_answers.table_assoc = 'users'
              and users.user_status_id = 30) a order by sort_order, item_name
              "; */

            $parent_company_sql = $this->user_dropdown(104);
            $userType = $this->getUserType($edit_id);
            $getUserName = $this->getUserName($edit_id);
            $getUserRole = $this->getUserRole($edit_id);
            $currUserIds = $this->getUserId($edit_id);
            //prd($userType);
            if (isset($_POST["mslParentSite"])) {
                $parentSiteSelected = implode(',', $_POST["mslParentSite"]);
//                echo $parentSiteSelected;
//                die;
                $parentSites = $parentSiteSelected;
            } else {
                $parentSites = $this->getUserSiteIds($edit_id);
            }


            $site_sql = $this->user_select_dropdown(0); //$this->user_dropdown(384);

            $workinging_for_provider_sql = "select 'Y' as id, 'Yes' item_name union all select 'N', 'No';";
            $provider_sql = $this->user_select_dropdown(105); //$this->user_dropdown(384);
            //prd($provider_sql);


            $sex_select = "select 0 as id, '--- Select ---' as item_name union all select 'M', 'Male' union all select 'F', 'Female';";
            //$sex_select = "select 'M', 'Male' union all select 'F', 'Female';";
            //prd($sex_select);
            $doctype_select = "select 'Qualification', 'Qualification' union all select 'License', 'License';";

            $user_sub_type = "select 1 as id, '--- Select ---' as item_name union all select '2', 'Client Representative' union all select '3', 'Employee';";


//      $this->editor_obj->form_attributes = array(
//                 array("txtSex", "txtURL", "selUserStatus", "calCommencementDate", "txtEmployeeID", "txtClientID", "txtSupplerID", "txtName", "txtSurname", "txtPreferredName", "txtEmail", "txtEmail2", "txtPhone", "txtTelephone2", "txtAddress", "txtSuburb", "txtPostcode", "selUserLevel", "chlUserGroups", "selStaffPosition", "selState", "txtCompany", "txtUsername", "txtImage", "calDOB"),
//                 array("Sex", "Website", "Status", "Commencement Date", "Employee ID", "Client ID", "Supplier ID", "Given Name(s)", "Family Name", "Preferred Name", "Email Address", "Email Address 2", "Phone", "Phone 2", "Address", "Suburb", "Postcode", "User Level", "User Groups", "Staff Position", "State", "Company", "Username", "Image", "DOB"),
//                 array("sex", "url", "user_status_id", "commencement_date", "employee_id", "client_id", "supplier_id", "name", "surname", "preferred_name", "email", "email2", "phone", "phone2", "address", "suburb", "postcode", "user_level_id", "name", "staff_position_id", "state", "company", "username", "image", "dob"),
//                 array("", "", $this->get_simple_lookup('user_status'), "", "", "", "", "", "", "", "", "", "", "", "", "", "", $this->get_simple_lookup('user_level'), $this->get_chl('user_group'), "select 0 as id, '--- Select ---' as item_name union all select id, title as item_name from staff_positions order by item_name", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states order by item_name", "", "", "", ""),
//                 array($style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, "", $style, $style, $style, $style, $style . ' autocomplete="new-password" ', $style, $style),
//                 array("", "", "c", "", "null", "null", "null", "c", "u", "u", "null", "u", "u", "u", "u", "u", "u", "u", "u", "u", "c", "n", "null", "n", "n"),
//                 array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "user_group", "", "", "", "", "", ""),
//                 array("", '<a href="mailto:display_itm">display_itm</a>', '<a href="mailto:display_itm">display_itm</a>', "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "")
//      
//      
            $formAttributeArr = array();

            /* section1 */
            $formAttributeArr[] = array("selUserSubType", "User Type", "user_subtype", $user_sub_type,'', "", "");
            $formAttributeArr[] = array("txtName", ($bd ? "Company Name" : "Name"), "name", "", $style_med, "c", "");
            $formAttributeArr[] = array("txtMiddleName", " Middle Name", "middle_name", "", $style_med, "", "");
            $formAttributeArr[] = array("txtSurname", "Surname", "surname", "", $style_med, "", "");
            $formAttributeArr[] = array("txtPreferredName", " Short Name", "preferred_name", "", $style_med, "", "");
            $formAttributeArr[] = array("selSex", " Gender", "sex", $sex_select, $style_med, "", "");
            $formAttributeArr[] = array("calDob", "Date Of Birth", "dob", "", $style, "n", "");
            //$formAttributeArr[] = array("cmbParentCompany", "Parent Client Name", "company", $parent_company_sql, $style_med, "n", "");
            $formAttributeArr[] = array("selUserLevel", "User Level", "user_level_id", $this->get_simple_lookup_access_level('user_level'), $style_med, "n", "");
////
////            /* section2 */
            $formAttributeArr[] = array("txtPhone", $pfix . "Phone", "phone", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtPhone2", $pfix . "Mobile", "phone2", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtFax", $pfix . "Fax", "fax", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtEmail", $pfix . "Email Address", "email", "",
                $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail').value)\"", "NULL", '', "");
            $formAttributeArr[] = array("txtEmail2", $pfix . "Email Address2", "email2", "",
                $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail2').value)\"", "n", "", "");
            $formAttributeArr[] = array("txtUrl", $pfix . "Website", "url", "", $style_med, "n", "");
////
//
//            /* section3 */
            $formAttributeArr[] = array("txtAbn", "Abn", "abn", "", $style_med . " onChange=\"JavaScript:check_abn(document.getElementById('txtAbn').value)\"", "n", "", "");
            $formAttributeArr[] = array("chkAbnNoRequired", "No ABN is required, Invoice to be send on location", "abn_no_required", "", "", "n", "", "");
            $formAttributeArr[] = array("txtAddress", "Address", "address", "", $style, "");
            $formAttributeArr[] = array("selState", "State", "state", "select 0 as id,"
                . " '--- Select ---' as item_name union all select id, item_name from states", "", "n", "");
            $formAttributeArr[] = array("txtSuburb", "Suburb", "suburb", "", $style, "n", "");
            $formAttributeArr[] = array("txtPostcode", "Postcode", "postcode", "", $style_small, "n", "");
            $formAttributeArr[] = array('txtLatitude', "Latitude", "latitude", "", $style, "n", "");
            $formAttributeArr[] = array('txtLongitude', "Longitude", "longitude", "", $style, "n", "");

//            /* section4 */
            $formAttributeArr[] = array("txtEmergencyContactFullName", "Full Name", "emergency_contact_full_name", "", $style, "");
            $formAttributeArr[] = array("txtEmergencyContactRelationship", "Relationship", "emergency_contact_relationship", "", $style, "");
            $formAttributeArr[] = array("txtEmergencyContactMobile", "Mobile Number", "emergency_contact_mobile", "", $style, "");

            /* section5 */
            $formAttributeArr[] = array("txtManagerInchargeName", "Name", "manager_incharge_name", "", $style, "");
            $formAttributeArr[] = array("txtManagerInchargeMobile", "Mobile", "manager_incharge_mobile", "", $style, "");
            $formAttributeArr[] = array("txtManagerInchargeEmail", "Email", "manager_incharge_email", "", $style, "");

            $formAttributeArr[] = array("txtManagerIncharge2Name", "Name", "manager_incharge2_name", "", $style, "");
            $formAttributeArr[] = array("txtManagerIncharge2Mobile", "Mobile", "manager_incharge2_mobile", "", $style, "");
            $formAttributeArr[] = array("txtManagerIncharge2Email", "Email", "manager_incharge2_email", "", $style, "");
//
//            /* section 7 */
            $formAttributeArr[] = array("selWorkingForProvider", "Working For Provider", "working_for_provider", $workinging_for_provider_sql, $style_med, "", "");
            $formAttributeArr[] = array("selProviderId", "Provider", "provider_id", $provider_sql, $styleLarge, "", "");
//
//
//            /* section15 */
            $formAttributeArr[] = array("calCommencementDate", "Commencement Date", "commencement_date", "", $style, "n", " ");
//
//
//            /* section16 */
            $formAttributeArr[] = array("chlDivisions", "Select Allied Division:", "id", $division_sql, $style, "u", "user_group");

            $formAttributeArr[] = array("chlUserGroups", "User Groups", "user_level_id", $this->get_chl('user_group'), $style, "u", "user_group");
//
//            /* section11 */
            $formAttributeArr[] = array("txtTaxFileNumber", "Tax File Number", "tax_file_number", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtBankName", "Bank Account Name", "bank_name", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtBsbNumber", "Bsb Number", "bsb_number", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtAccountNumber", "Account Number", "account_number", "", $style_med, "n", "");
//
//            /* section12 */
            $formAttributeArr[] = array("txtSiteContactName1", "Site Contact Name", "site_contact_name1", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactPosition1", "Site Contact Position", "site_contact_position1", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactPhone1", "Site Contact Phone", "site_contact_phone1", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactMobile1", "Site Contact Mobile", "site_contact_mobile1", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactEmail1", "Site Contact Email", "site_contact_email1", "", $style, "n", "");

            $formAttributeArr[] = array("txtSiteContactName2", "Site Contact Name", "site_contact_name2", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactPosition2", "Site Contact Position", "site_contact_position2", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactPhone2", "Site Contact Phone", "site_contact_phone2", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactMobile2", "Site Contact Mobile", "site_contact_mobile2", "", $style_med, "n", "");
            $formAttributeArr[] = array("txtSiteContactEmail2", "Site Contact Email", "site_contact_email2", "", $style, "n", "");

////      
////      
////      
////      
////      
////      
////      
            //$formAttributeArr[] = array("cmbParentSite", "Main Site", "parent_site", $site_sql, $style, "n", "");
//
            $formAttributeArr[] = array("mslParentSite", "Main Location", "main_site_id", $site_sql, "multiple " . $styleMulti, "u", "");
//            $formAttributeArr[] = array("cmbParentCompany", "Parent Client Name", "parent_company", $parent_company_sql, $style_med, "n", "");
            // $formAttributeArr[] = array("txtUsername", "Username", "username", "", $style, "n", $username_rnd);
//            $formAttributeArr[] = array("txtPassword", "Set a Password", "pw", "", $style, "n", $password_rnd);
//      $formAttributeArr[] = array("selWorkerClassification", "Worker Classification", 
//          "worker_classification",$this->get_lookup('worker_classification'), $style, "n", "");
//
//      $formAttributeArr[] = array("cmbVisaType", "Visa Type", "visa_type", $this->get_cmb_lookup('visa_type'), $style, "n", ""); 
            $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
            //print_r($resultV); die;
            $form_obj->form_attributes = $resultV;




            $this->editor_obj->button_attributes = array(
                array("Save", "Reset", "Filter"),
                array("cmdSave", "cmdReset", "cmdFilter"),
                array("if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                array("js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
            );



            $flder = $this->f3->get('download_folder') . "user_files/";
            $img_file = "$flder$edit_id/profile.jpg";
            $show_img = "user_files/$edit_id/profile.jpg";

            $form_obj->form_template = '
          <input type="hidden" name="del_photo" id="del_photo" />
          <input type="hidden" name="hdn_sites" id="hdn_sites" value="' . $parentSites . '" />             
          <input type="hidden" name="hdn_user_types" id="hdn_user_types" value="' . $userType . '" />    
               
                <input type="hidden" name="rotate" id="rotate" />
      <div class="cl"></div>
      <h3 id="title">' . ($bd ? "Leads" : " Stakeholders") . '</h3>
      <hr />
      <!--  section 1 -->';
            if ($edit_id == 3) {
                $form_obj->form_template .= '<div class="fl">tselUserLevel<br />selUserLevel</div>';
            } else {
                $form_obj->form_template .= '<div class="fl user3 user4">tselUserLevel<br />selUserLevel</div>';
            }

            $form_obj->form_template .= '<!--<div class="fl section1 user1 user2 user3 user4"><nobr>tselUserType</nobr><br />selUserType</div> -->
      <div class="fl section1 user3 user4"><nobr>tselUserSubType</nobr><br />selUserSubType</div>
      <div class="fl section1 user1 user2 user3 user4"><nobr id="name_label">ttxtName</nobr><br />txtName<br /></div>
      <div class="fl section1 user3"><nobr >ttxtMiddleName</nobr><br />txtMiddleName<br /></div>
              
      <div class="fl section1 user3"><nobr >ttxtSurname</nobr><br />txtSurname<br /></div>
      <div class="fl section1 user3"><nobr >tselSex</nobr><br />selSex<br /></div>
      <div class="fl section1 user2 user3"><nobr id="short_label">ttxtPreferredName</nobr><br />txtPreferredName<br /></div>  
      <div class="fl section1 user3"><nobr id="">tcalDob</nobr><br />calDob<br /></div>
      <!-- <div class="fl section1 user2" id="parent_company">
        <nobr>tcmbParentCompany</nobr>
        <br />cmbParentCompany<br />
      </div> -->
      <!--  section 2 -->
      <div class="fl section2 user1 user3 user4"><nobr>ttxtPhone</nobr><br />txtPhone</div>
      <div class="fl section2 user3 user4"><nobr>ttxtPhone2</nobr><br />txtPhone2</div>
      <div class="fl section2 user1"><nobr>ttxtFax</nobr><br />txtFax</div>
      <div class="fl section2 user1 user3 user4"><nobr>ttxtEmail</nobr><br />txtEmail</div>
      <div class="fl section2 user1"><nobr>ttxtEmail2</nobr><br />txtEmail2</div>
      <div class="fl section2 user1"><nobr>ttxtUrl</nobr><br />txtUrl</div>
      
 <div class="comdiv user1div">
        <div class="cl"></div><br />        
        <div class="fl section2 user1">chkAbnNoRequired <nobr>tchkAbnNoRequired</nobr></div>
        <div class="cl"></div><br />
     </div>   
       <div class="fl section3 user1 user2 user4"><nobr>ttxtAbn</nobr><span class="abnsign" style="display:none;color:green">
            <i class="fa fa-check" aria-hidden="true"></i>
       </span><br />txtAbn</div>
      <div class="fl section3 user1 user2 user3 user4"><nobr>ttxtAddress</nobr><br />txtAddress</div>
      <div class="fl section3 user1 user2 user3 user4"><nobr>tselState</nobr><br />selState</div>
      <div class="fl  section3 user1 user2 user3 user4"><nobr>ttxtSuburb</nobr><br />txtSuburb</div>
       <div class="fl section3 user1 user2 user3 user4"><nobr>ttxtPostcode</nobr><br />txtPostcode<br /></div>
      <div class="fl section3 user2 user3 user4"><nobr>ttxtLatitude</nobr><br />txtLatitude<br /></div>
      <div class="fl section3 user2 user3 user4"><nobr>ttxtLongitude</nobr><br />txtLongitude<br /></div>
      

 <!--  section 15 -->
      <div class="fl section5 user1 user2 user3 user4">tcalCommencementDate<br />calCommencementDate</div>
<div id="xtras">     
      <div class="fl section5 user1 user2 user3 user4" style="padding: 10px;"><nobr>Upload Logo</nobr>
       <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload" id="fileToUpload" onchange="loadImageFile()"><canvas id="myCanvas" style="max-width: 100px; height: 0px;"></canvas>
      ' . (file_exists($img_file) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img)) . '"><img style="max-width: 100px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img)) . '" /></a><br />' : '') . '
      </div></div><input type="hidden" name="hdnFileName" id="hdnFileName" />
      <input type="hidden" name="hdnImage" id="hdnImage" />

      <!--  section 4 -->
<div class="comdiv user3div">
        <div class="cl"></div><br /><b>Emergency Contact Info </b>  <div class="cl"></div> 
      <div class="fl section3  user3"><nobr>ttxtEmergencyContactFullName</nobr><br />txtEmergencyContactFullName</div>
      <div class="fl section3  user3"><nobr>ttxtEmergencyContactRelationship</nobr><br />txtEmergencyContactRelationship</div>
      <div class="fl section3  user3"><nobr>ttxtEmergencyContactMobile</nobr><br />txtEmergencyContactMobile</div>
      
</div>
 
<!--  section 5 -->
<div class="comdiv user4div">
        <div class="cl"></div><br />
        <b> Manager Incharge </b></br>
        
      <div class="fl section3  user4"><nobr>ttxtManagerInchargeName</nobr><br />txtManagerInchargeName</div>
      <div class="fl section3  user4"><nobr>ttxtManagerInchargeMobile</nobr><br />txtManagerInchargeMobile</div>
      <div class="fl section3  user4"><nobr>ttxtManagerInchargeEmail</nobr><br />txtManagerInchargeEmail</div>
      <div class="cl"></div><br />
      
      <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Name</nobr><br />txtManagerIncharge2Name</div>
      <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Mobile</nobr><br />txtManagerIncharge2Mobile</div>
      <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Email</nobr><br />txtManagerIncharge2Email</div>
      <div class="cl"></div><br /><div class="cl"></div><br />
</div>

      <div class="comdiv user2div user3div user4div"> 
     <div class="cl"></div><br />
        
    </div> 
    <div class="cl"></div><br />
<div class="fl section6 user2 user3 user4">

         tchlDivisions  <br />chlDivisions
         </div>
         <div class="cl"></div><br />
      
<div class="comdiv user3div"> 
<div class="cl"></div><br />
      <div class="fl section9  user3"><nobr>tselWorkingForProvider</nobr><br />selWorkingForProvider</div>
      <div class="fl section9  user3 user4"><nobr>tselProviderId</nobr><br />selProviderId</div>
      <div class="cl"></div><br />
</div>
<!-- section11 -->
  <div class="comdiv user3div user4div"> 
      <div class="fl section1 user3 user4" id="parent_site">
               <nobr>tmslParentSite</nobr><span class="mslParentSign" style="display:none;color:green">
            <i class="fa fa-check" aria-hidden="true"></i>
       </span><br />mslParentSite<br />
      </div>
  <div class="cl"></div><br /> 
  </div>
 <!--  section 11 -->
<div class="comdiv user3div user4div">     
      <div class="fl section11  user3"><nobr>ttxtTaxFileNumber</nobr><br />txtTaxFileNumber</div>
      <div class="fl section11  user3 user4"><nobr>ttxtBankName</nobr><br />txtBankName</div>
      <div class="fl section11  user3 user4"><nobr>ttxtBsbNumber</nobr><br />txtBsbNumber</div>
      <div class="fl section11  user3 user4"><nobr>ttxtAccountNumber</nobr><br />txtAccountNumber</div>
      <div class="cl"></div><br />
</div>   

<div class="comdiv user2div">
<b> Site Contact1 </b>  <div class="cl"></div>  
<div class="fl section12 user2"><nobr>ttxtSiteContactName1</nobr><br />txtSiteContactName1</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactPosition1</nobr><br />txtSiteContactPosition1</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactPhone1</nobr><br />txtSiteContactPhone1</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactMobile1</nobr><br />txtSiteContactMobile1</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactEmail1</nobr><br />txtSiteContactEmail1</div>

<div class="cl"></div><br />  <b> Site Contact2 </b>  <div class="cl"></div> 
<div class="fl section12 user2"><nobr>ttxtSiteContactName2</nobr><br />txtSiteContactName2</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactPosition2</nobr><br />txtSiteContactPosition2</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactPhone2</nobr><br />txtSiteContactPhone2</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactMobile2</nobr><br />txtSiteContactMobile2</div>
<div class="fl section12 user2"><nobr>ttxtSiteContactEmail2</nobr><br />txtSiteContactEmail2</div>
<div class="cl"></div>
</div>
     <div class="fl user3" >tchlUserGroups<br />chlUserGroups</div>    

         
 <div class="fl">Password<br /><input name="pw1" id="pw1" type="password" style="width: 190px;" autocomplete="new-password"></div>
 <div class="fl">Confirm Password<br /><input name="pw2" id="pw2" type="password" style="width: 190px;" autocomplete="new-password"></div>
      <div class="cl"></div></br>
      ' . $form_obj->button_list();


// <div class="fl user3" >tchlUserGroups<br />chlUserGroups</div>

//      $this->editor_obj->form_template = '
//                <input type="hidden" name="del_photo" id="del_photo" />
//                <input type="hidden" name="hdnFileName" id="hdnFileName" />
//                <input type="hidden" name="hdnImage" id="hdnImage" />
//                <input type="hidden" name="rotate" id="rotate" />
//
//                <div class="fl">ttxtEmployeeID<br />txtEmployeeID</div>
//                <div class="fl">ttxtClientID<br />txtClientID</div>
//                <div class="fl">ttxtSupplerID<br />txtSupplerID</div>
//                <div class="fl">ttxtCompany<br />txtCompany</div>
//                <div class="fl">ttxtUsername<br />txtUsername</div>
//                <div class="fl">tselUserStatus<br />selUserStatus</div>
//                <div class="fl">ttxtSex<br />txtSex</div>
//                <div class="fl">tcalDOB<br />calDOB</div>
//                <div class="fl">ttxtURL<br />txtURL</div>
//                <div class="fl">ttxtName<br />txtName</div>
//                <div class="fl">ttxtSurname<br />txtSurname</div>
//                <div class="fl">ttxtPreferredName<br />txtPreferredName</div>
//                <div class="fl">ttxtEmail<br />txtEmail</div>
//                <div class="fl">ttxtEmail2<br />txtEmail2</div>
//                <div class="fl">ttxtPhone<br />txtPhone</div>
//                <div class="fl">ttxtTelephone2<br />txtTelephone2</div>
//                <div class="fl">ttxtAddress<br />txtAddress</div>
//                <div class="fl">ttxtSuburb<br />txtSuburb</div>
//                <div class="fl">ttxtPostcode<br />txtPostcode</div>
//                <div class="fl">tselState<br />selState</div>
//                <div class="fl">tselUserLevel<br />selUserLevel</div>
//                <div class="fl">tselStaffPosition<br />selStaffPosition</div>
//                <div class="fl">tcalCommencementDate<br />calCommencementDate</div>
//                <div class="fl">ttxtImage<br />txtImage</div>
//                <div class="fl">Password<br /><input name="pw1" type="password" style="width: 190px;" autocomplete="new-password"></div>
//                <div class="fl">Confirm Password<br /><input name="pw2" type="password" style="width: 190px;" autocomplete="new-password"></div>
//
//                  <div class="fl" style="padding-left: 10px;"><nobr>Photo</nobr><br />
//                  <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload" id="fileToUpload" onchange="loadImageFile()"><br /><canvas id="myCanvas" style="max-width: 200px; height: 0px;"></canvas>
//                  ' . (file_exists($img_file) ? '<a target="_blank" href="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img)) . '"><img style="max-width: 200px" src="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img)) . '" /></a><br />' : '') . '
//                  </div>
//
//               <div class="cl"></div>
//                <div class="fl">tchlUserGroups<br />chlUserGroups</div>
//                <br />
//                <div class="cl"></div><br />
//      
//
//      '.$this->editor_obj->button_list();
            $usernameShow = "";
            if ($userType == 105 || $userType == 107) {
                $usernameShow = 'Username ' . $getUserName."   Role: ".$getUserRole;
            }
            $this->editor_obj->editor_template = '
                   <div class="form-wrapper">
                   <div class="form-header">User Management - Stakeholder Id - ' . $currUserIds . ' ' . $usernameShow . '</div>
                   <div class="standard-form">editor_form</div>
                   </div>
      ';
            $this->editor_obj->editor_template .= '
                   <td valign="top">editor_list</td>
                   </tr>
                   </table>
      ';



            $str .= $this->editor_obj->draw_data_editor($this->list_obj);


            if ($action == "add_record" || $action == "save_record") {

                if ($action == "add_record") {
                    $save_id = $this->editor_obj->last_insert_id;
                } else if ($action == "save_record") {
                    $save_id = $this->editor_obj->idin;
//                    pr("mm");
//                     prd($_REQUEST);
                }

                //                    $save_id = $_REQUEST["idin"];
//                 
//            
                if ($save_id) {
                    if ($img) {
                        $folder = "$flder$save_id";
                        if (!file_exists($folder)) {
                            mkdir($folder);
                            chmod($folder, 0755);
                        }
                    }
                    if ($img) {
                        $img = str_replace(' ', '+', $img);
                        $img = substr($img, strpos($img, ",") + 1);
                        $data = base64_decode($img);
                        if ($norename) {
                            $img_name = basename($_POST['hdnFileName']);
                            //echo $img_name;
                            //die;
                            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
                        } else {
                            $img_name = "profile.jpg";
                        }
                        $file = "$folder/$img_name";
                        $success = file_put_contents($file, $data);
                    }

                    $rotate = (isset($_POST['rotate']) ? $_POST['rotate'] : null);

                    if ($rotate) {
                        $rotate = (isset($_POST['rotate']) ? $_POST['rotate'] : null);
                        if ($rotate) {
                            //$rotate = ($rotate ? $file : $rotate);
                            $degrees = (strtoupper($rotate) == 'LEFT' ? 270 : ($rotate == 'FLIP' ? 180 : 90));
                            $source = imagecreatefromjpeg($file);
                            $rotate = imagerotate($source, $degrees, 0);
                            imagejpeg($rotate, $file);
                            imagedestroy($source);
                            imagedestroy($rotate);
                        }
                    }


                    $pw1 = $_POST['pw1'];
                    $pw2 = $_POST['pw2'];
                    $msg = "";
                    //prd("mm". $pw1." ".$pw2);
                    if ($pw1 && $pw2) {
                        if ($pw1 != $pw2) {
                            $msg = "Passwords don't match. The password was NOT saved";
                        } else if (strlen($pw1) < 6) {
                            $msg = "The chosen password must be at least 6 characters long.  The password was NOT saved";
                        } else {
                            $pw = $this->ed_crypt($pw1, $save_id);
                            $sql = "update users set pw = '$pw' where id = $save_id;";
                            $this->dbi->query($sql);
                            if ($action == "save_record")
                                $msg = "The password has been saved";
                        }
                    }


                    $hdn_user_types = $_POST["hdn_user_types"];

                   // if ($_POST["chlDivisions"]) {
                         $chlDivisions = $_POST["chlDivisions"];
//                         $sql1 = "delete from lookup_answers where table_assoc = 'users' and foreign_id = $save_id;";  
//                         $result = $this->dbi->query($sql1);
                        
                        
//                        $ids = array();
//                        if ($chlDivisions) {
//                            foreach ($chlDivisions as $division_id) {
//                                $skip = 0;
//                                foreach ($ids as $test) {
//                                    if ($test == $division_id)
//                                        $skip = 1;
//                                }
//                                if (!$skip) {
//                                    array_push($ids, $division_id);
//                                }
//                            }
//                        }
//                        foreach ($ids as $id) {
//                            $ins_str .= "($last_id, $id, 'users'),";
//                            if ($id = 107)
//                                $is_staff = 1;
//                        }
//                        if($ins_str){
//
//            $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
//            $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";
//            //return "<h3>$sql</h3>";
//            $this->dbi->query($sql);
//                    }
                    
//                    $selUserLevel = $_POST["selUserLevel"];
//                    if(!$selUserLevel){
//                        $selUserLevel = 10;
//                    }
//                    
//                    $userRoleId = $this->updateUserRoleByLevel($edit_id,$selUserLevel);
                    //echo $userRoleId;
                    //die;

//                    $ins_str .= "($last_id, $userRoleId, 'users'),";
//                    $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
//                    $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";
                    
                    
                    
                    

                    $mslParentSite = $_POST["mslParentSite"];


                    if ($mslParentSite) {
                        $selUserType = 600;
                        $assoc_type = ($selUserType == 504 ? 12 : 4);
                        $sql = "delete from users_sites where user_id = $save_id;";
                        $sql = "delete from associations where child_user_id = $save_id;";

                        $this->dbi->query($sql);
                        foreach ($mslParentSite as $sitekey => $mslSiteItem) {
                            if ($sitekey == 0) {
                                $sql = "update users set main_site_id = $mslSiteItem where id = $save_id;";
                                $this->dbi->query($sql);
                            }
                            $sql = "insert into users_sites (user_id,site_id) values ($save_id, $mslSiteItem)";
                            $this->dbi->query($sql);
                            $sql = "insert into associations (association_type_id, parent_user_id, child_user_id, added_by_id) values ($assoc_type, $mslSiteItem, $save_id, " . $_SESSION['user_id'] . ");";
                            $this->dbi->query($sql);
                        }
                    }
                }
            }


            if ($this->list_obj->is_empty && $search_string) {
                $str .= '
          <script language = "JavaScript">
          document.getElementById("txtEmail").value = "' . $search_string . '"
          </script>
        ';
            }
            $form_action = $_POST['hdnAction'];
            if ($form_action == 'add_record') {
                $str .= '
          <script language="JavaScript">
            document.getElementById("txtSearch").value = document.getElementById("txtEmail").value;
            perform_search("Edit/Users");
          </script>
        ';
            }
        }
        if ($_REQUEST["idin"]) {
            //$str .= '<iframe width="100%" height="700" src="' . $this->f3->get('main_folder') . 'AccountDetails?show_min=1&uid=' . $_REQUEST["idin"] . '" frameborder="0"></iframe>';
        }
        return $str;
    }

    function AssociationTypes() {
        if (isset($_POST['hdnFilter']))
            $filter_string = "filter_string";
        $this->list_obj->sql = "
    			select association_types.id as idin, association_types.name as `Association Name`, lookup_fields1.item_name as `Parent Group`, lookup_fields2.item_name as `Child Group`, 
    			'Edit' as `*`, 'Delete' as `!`,
    			CONCAT('<nobr><a class=\"list_a\" uk-tooltip=\"title: Edit Associations\" target=\"child_frame\" href=\"" . $this->f3->get('main_folder') . "Page/Associations?show_min=1&lookup_id=', association_types.id, '\">Edit Associations</a>', '<a class=\"list_a\" uk-tooltip=\"title: View Associations\" target=\"child_frame\" href=\"" . $this->f3->get('main_folder') . "Page/Associations?show_min=1&view_assoc=1&lookup_id=', association_types.id, '\">View</a></nobr>') as `Associations`
    			FROM association_types
    			left join lookup_fields1 on lookup_fields1.id = association_types.parent_group_id
    			left join lookup_fields2 on lookup_fields2.id = association_types.child_group_id
    		  where 1 $filter_string
      ";
        $this->editor_obj->table = "association_types";
        $style = 'style="width: 220px;"';
        $style_small = 'style="width: 140px;"';
        $this->editor_obj->form_attributes = array(
            array("txtName", "selChildGroup", "selParentGroup"),
            array("Name", "Parent Group", "Child Group"),
            array("name", "parent_group_id", "child_group_id"),
            array("", $this->get_lookup('user_group'), $this->get_lookup('user_group')),
            array($style, $style, $style),
            array("c", "c", "c"),
            array(),
            array()
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
    					<div class="fl"><nobr>ttxtName</nobr><br />txtName</div>
    					<div class="fl"><nobr>tselChildGroup</nobr><br />selChildGroup</div>
    					<div style="float: left; height: 65px; padding-right: 20px;"><nobr>tselParentGroup</nobr><br />selParentGroup</div>
    					<div class="cl"></div>
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <table border="0" width="100%">
    						<tr>
    						<td valign="top" style="width: 720px;">
                <table class="standard_form">
                <tr><td class="form_header">Association Types</td>
    						</tr>
                <tr><td>editor_form
    						</td></tr>
                </table>
    						editor_list
    						</td>
    						<td valign="top">
    						<div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame"></iframe>
    						</div>
    						</td>
    						</tr>
    						</table>
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function JobAdEditor() {
        $current_job_ads = (isset($_POST['chkCurrentOnly']) ? $_POST['chkCurrentOnly'] : 0);
        $str .= '
    <script>
    function insert_image(img) {
      CKEDITOR.instances.cmsDescription.insertHtml(\'<img src="\' + img + \'"></a>\');
    }
    </script>';
        $filter_string = ($filter ? $filter : "filter_string");
        $this->list_obj->title = "Job Advertisements";
        $curr = new input_item;
        $this->list_obj->sql = "
          select job_ads.id as idin, job_ads.sort_order as `Ord`, states.item_name as `State`, job_ads.date_modified as `Date Modified`, job_ads.closing_date as `Closing Date`, CONCAT(users.name, ' ', users.surname) as `Modified By`,
          job_ads.title as `Title`, if(is_internal, 'Yes', 'No') as `Internal Ad Only`,
          'Edit' as `*`, 'Delete' as `!`
          FROM job_ads
          left join users on users.id = job_ads.modified_by_id
          left join states on states.id = job_ads.state_id
          where 1
          " . ($current_job_ads ? " and (closing_date = '0000-00-00' or closing_date >= now())" : "") . "
          $filter_string
          order by job_ads.sort_order
      ";
        $this->editor_obj->add_now = "date_modified";
        $this->editor_obj->update_now = "date_modified";
        $this->editor_obj->custom_field = "modified_by_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "job_ads";
        $lookup = "select 0 as id, '--- Select ---' as item_name union all select id, title as `item_name` from compliance where title LIKE '%job application%'";
        $style = 'style="width: 140px;"';
        $style_large = 'style="width: 360px;"';
        $this->editor_obj->form_attributes = array(
            array("calClosingDate", "txtTitle", "cmsDescription", "txtSortOrder", "selState", "chkIsInternal"),
            array("Closing Date", "Title", "Description", "Display Order", "State", "Internal Ad Only"),
            array("closing_date", "title", "description", "sort_order", "state_id", "is_internal"),
            array("", "", "", "", $this->get_simple_lookup('states'), ""),
            array($style, $style_large, "", "", $style, ""),
            array("c", "c", "n", "", "c", "n"),
            array("", "", "", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
              <div class="fl"><nobr>tselState</nobr><br />selState</div>
              <div class="fl"><nobr>tcalClosingDate</nobr><br />calClosingDate</div>
              <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div class="cl"></div>
              <div class="cl"></div>
              <div class="fl">tcmsDescription</div><div class="fr">chkIsInternal tchkIsInternal</div>
              <div class="cl"></div>
              cmsDescription
              <div class="cl"></div>
              <br /><br />
              ' . $this->editor_obj->button_list();
        if ($_POST['idin']) {
            $src = "JobAdsQuestions?show_min=1&lookup_id=" . $_POST['idin'];
        }
        $this->editor_obj->editor_template = '
    						<div class="fl" style="width: 900px; padding: 0px; margin-right: 10px;">
                <table border="0">
                <tr>
                <td valign="top">
                <table class="standard_form">
                <tr><td class="form_header">Job Advertisements Editor<div class="fr" style="color: #AAA !important;">' . $curr->chk("chkCurrentOnly", "Current Job Ads Only", "checked", "", "", "", "") . '</div></td>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                </td>
                <td valign="top">
                <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                </td>
                </tr>
                </table>
                editor_list
                </div>
    						<div class="fl" style="margin-left: 20px; width: 900px; padding: 0px; margin: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame" src="' . $src . '"></iframe>
    						</div>
                <div class="cl"></div>
    ';
        if ($filter) {
            $this->editor_obj->xtra_js = $this->editor_obj->js_wrapper('document.getElementById("hdnFilter").value = "' . $filter . '";');
        }
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function JobLetters() {
        $str .= '
    <script>
    function insert_image(img) {
      CKEDITOR.instances.cmsDescription.insertHtml(\'<img src="\' + img + \'"></a>\');
    }
    </script>
    ';
        $this->list_obj->title = "Job Letters";
        //, CONCAT(users.name, ' ', users.surname) as `Modified By`   ---   left join users on users.id = job_letters.modified_by_id
        $this->list_obj->sql = "
          select job_letters.id as idin, job_letters.sort_order as `Ord`, job_letters.date_modified as `Date Modified`,
          job_letters.title as `Title`, job_application_status.item_name as `Change Status On Send`,
          'Edit' as `*`, 'Delete' as `!`
          FROM job_letters
          left join job_application_status on job_application_status.id = job_letters.status_change_id
          where 1
          order by job_letters.sort_order
      ";
//          where user_id = ".$_SESSION['user_id']."

        $this->editor_obj->add_now = "date_modified";
        $this->editor_obj->update_now = "date_modified";
        $this->editor_obj->custom_field = "modified_by_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "job_letters";
        $style = 'style="width: 160px;"';
        $style_large = 'style="width: 360px;"';
        $this->editor_obj->form_attributes = array(
            array("txtTitle", "cmsDescription", "txtSortOrder", "selChangeStatus"),
            array("Title", "Description", "Display Order", "Change Status on Send"),
            array("title", "description", "sort_order", "status_change_id"),
            array("", "", "", $this->get_simple_lookup("job_application_status")),
            array($style_large, "", $style, $style),
            array("c", "", "", ""),
            array("", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
              <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
              <div class="fl"><nobr>tselChangeStatus</nobr><br />selChangeStatus</div>
              <div class="cl"></div>
              tcmsDescription<br />cmsDescription
              <br /><br />
              ' . $this->editor_obj->button_list();
        if ($_POST['idin']) {
            $src = "Edit/CommentEditor?lookup_id=" . $_POST['idin'];
        }
        $this->editor_obj->editor_template = '
    						<div class="fl" style="width: 1200px; padding: 0px; margin-right: 10px;">
                <table border="0">
                <tr>
                <td valign="top">
                <table class="standard_form">
                <tr><td class="form_header">Job Letter Editor</td>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                </td>
                <td valign="top">
                <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                </td>
                </tr>
                </table>
                editor_list
                </div>
                <!--
    						<div class="fl" style="margin-left: 20px; width: 900px; padding: 0px; margin: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame" src="' . $src . '"></iframe>
    						</div>
                -->
                <div class="cl"></div>
    ';
        if ($filter) {
            $this->editor_obj->xtra_js = $this->editor_obj->js_wrapper('document.getElementById("hdnFilter").value = "' . $filter . '";');
        }
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function JobAdQuestions() {
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $move = (isset($_POST["hdnMove"]) ? $_POST["hdnMove"] : null);
        $move_action = (isset($_POST["hdnMoveAction"]) ? $_POST["hdnMoveAction"] : null);

        $str .= '
      <input type="hidden" name="hdnMoveAction" id="hdnMoveAction">
      <input type="hidden" name="hdnMove" id="hdnMove">
      <script>
        function move(id, direction) {
          document.getElementById(\'hdnMoveAction\').value = direction
          document.getElementById(\'hdnMove\').value = id
          document.frmEdit.submit();
        }
      </script>
    ';

        if ($move && $move_action) {
            if ($move_action == "up") {
                $sql = "select sort_order from job_ad_questions where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ad_questions where sort_order = " . ($sort_order - 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ad_questions set sort_order = sort_order + 1 where id = $id2;");
                    $this->dbi->query("update job_ad_questions set sort_order = sort_order - 1 where id = $id1;");
                }
            } else if ($move_action == "down") {
                $sql = "select sort_order from job_ad_questions where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ad_questions where sort_order = " . ($sort_order + 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ad_questions set sort_order = sort_order + 1 where id = $id1;");
                    $this->dbi->query("update job_ad_questions set sort_order = sort_order - 1 where id = $id2;");
                }
            }
        }

        $this->list_obj->title = "Job Ad Questions";
        $this->list_obj->sql = "
    	select job_ad_questions.id as idin,
      concat(if(job_ad_questions.sort_order != 1, concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ad_questions.id, ', \'up\')\"><span data-uk-icon=\"icon: triangle-up\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-up\"></span>`,
      concat(if(job_ad_questions.sort_order < (select count(id) from job_ad_questions),
      concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ad_questions.id, ', \'down\')\"><span data-uk-icon=\"icon: triangle-down\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-down\"></span>`,
      lookup_fields1.item_name as `Type`, job_ad_questions.item_name as `Question`, if(compulsory = 0, 'No', 'Yes') as `Compulsory`, 'Edit' as `*`, 'Delete' as `!`
    	from job_ad_questions
    	left join lookup_fields1 on lookup_fields1.id = job_ad_questions.type
    	order by job_ad_questions.sort_order
      ";
        $this->list_obj->show_num_records = true;
        $this->editor_obj->table = "job_ad_questions";
        $style = 'style="width: 200px;"';
        $style_large = 'style="width: 400px;"';
        $this->editor_obj->form_attributes = array(
            array("txtItemName", "selInputType", "chkCompulsory"),
            array("Question", "Type", "Compulsory"),
            array("item_name", "type", "compulsory"),
            array("", $this->get_lookup('question_type'), ""),
            array($style_large, $style, ""),
            array("c", "c", "n"),
            array("", "", ""),
            array("", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>tselInputType</nobr><br />selInputType</div>
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName<br /></div>
              <div class="fl">chkCompulsory tchkCompulsory</div>
              <div class="cl"></div>
              <div class="cl"></div><br />
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div class="fl">
                <div class="form-wrapper" style="max-width: 700px;">
                <div class="form-header">Job Ad Questions</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
                editor_list
    						</div>
    ';
        if ($_POST['idin']) {
            $this->editor_obj->editor_template .= '
    						<div style="float: left; width: 800px; margin-left: 15px;">
    						<iframe style="border: none; width: 100%; height: 1000px;" src="' . $this->f3->get('main_folder') . 'Edit/JobAdQuestionChoices?show_min=1&lookup_id=' . $_POST['idin'] . '"></iframe>
    						</div>
    	';
        }
        $this->editor_obj->editor_template .= '
                <div class="cl"></div>
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        if ($action == "add_record") {
            $save_id = $this->editor_obj->last_insert_id;
            $max_sort = 0;
            $sql = "select max(sort_order) as `max_sort` from job_ad_questions where 1";
            if ($result = $this->dbi->query($sql)) {
                $myrow = $result->fetch_assoc();
                if ($result->num_rows) {
                    $max_sort = $myrow['max_sort'];
                }
            }
            $max_sort += 1;
            $this->dbi->query("update job_ad_questions set sort_order = $max_sort where id = $save_id");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        } else if ($action == "delete_record") {
            $this->dbi->query("UPDATE job_ad_questions e,
                         (SELECT @n := 0) m
                         SET e.sort_order = @n := @n + 1");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }
        return $str;
    }

    function JobAdQuestionChoices() {
        $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $move = (isset($_POST["hdnMove"]) ? $_POST["hdnMove"] : null);
        $move_action = (isset($_POST["hdnMoveAction"]) ? $_POST["hdnMoveAction"] : null);

        $str .= '
      <input type="hidden" name="hdnMoveAction" id="hdnMoveAction">
      <input type="hidden" name="hdnMove" id="hdnMove">
      <script>
        function move(id, direction) {
          document.getElementById(\'hdnMoveAction\').value = direction
          document.getElementById(\'hdnMove\').value = id
          document.frmEdit.submit();
        }
      </script>
    ';
        if ($move && $move_action) {
            if ($move_action == "up") {
                $sql = "select sort_order from job_ad_question_choices where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ad_question_choices where job_ad_question_id = $lookup_id and sort_order = " . ($sort_order - 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ad_question_choices set sort_order = sort_order + 1 where id = $id2;");
                    $this->dbi->query("update job_ad_question_choices set sort_order = sort_order - 1 where id = $id1;");
                }
            } else if ($move_action == "down") {
                $sql = "select sort_order from job_ad_question_choices where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ad_question_choices where job_ad_question_id = $lookup_id and sort_order = " . ($sort_order + 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ad_question_choices set sort_order = sort_order + 1 where id = $id1;");
                    $this->dbi->query("update job_ad_question_choices set sort_order = sort_order - 1 where id = $id2;");
                }
            }
        }

//    $filter_string = "filter_string";
        $filter_string = "";
        $this->list_obj->title = "Choices";
        $this->list_obj->sql = "
    	select job_ad_question_choices.id as idin,
      concat(if(sort_order != 1, concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ad_question_choices.id, ', \'up\')\"><span data-uk-icon=\"icon: triangle-up\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-up\"></span>`,
      concat(if(sort_order < (select count(id) from job_ad_question_choices where job_ad_question_id = $lookup_id),
      concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ad_question_choices.id, ', \'down\')\"><span data-uk-icon=\"icon: triangle-down\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-down\"></span>`,
      job_ad_question_choices.item_name as `Item Name`, 'Edit' as `*`, 'Delete' as `!` from job_ad_question_choices
      where job_ad_question_id = $lookup_id
      $filter_string
    	order by sort_order
      ";
        $list_top = "select 0 as id, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "job_ad_question_choices";
        $style = 'style="width: 250px;"';
        $style_small = 'style="width: 100px;"';
        $this->editor_obj->form_attributes = array(
            array("txtItemName"),
            array("Choice"),
            array("item_name"),
            array(""),
            array($style),
            array("c"),
            array(""),
            array("")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName</div>
              <div class="cl"></div>
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div class="form-wrapper" style="max-width: 350px;">
                <div class="form-header">Choices</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
    						<div class="cl"></div>
                editor_list
    ';
        $this->editor_obj->xtra_id_name = "job_ad_question_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';

        if ($action == "add_record") {
            $save_id = $this->editor_obj->last_insert_id;
            $max_sort = 0;
            $sql = "select max(sort_order) as `max_sort` from job_ad_question_choices where job_ad_question_id = $lookup_id";
            if ($result = $this->dbi->query($sql)) {
                $myrow = $result->fetch_assoc();
                if ($result->num_rows) {
                    $max_sort = $myrow['max_sort'];
                }
            }
            $max_sort += 1;
            $this->dbi->query("update job_ad_question_choices set sort_order = $max_sort where id = $save_id");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        } else if ($action == "delete_record") {
            $this->dbi->query("UPDATE job_ad_question_choices e,
                         (SELECT @n := 0) m
                         SET e.sort_order = @n := @n + 1 
                         WHERE e.job_ad_question_id = $lookup_id");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }
        return $str;
    }

    function JobAdsQuestions() {
        $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
        $action_id = (isset($_GET["action_id"]) ? $_GET["action_id"] : null);
        $action = (isset($_GET["action"]) ? $_GET["action"] : null);


        if ($action) {
            $sql['add'] = "insert into job_ads_questions (job_ad_id, question_id, sort_order) values ('$lookup_id', '$action_id', (select sort_order from job_ad_questions where id = '$action_id'));";
            $sql['remove'] = "delete from job_ads_questions where job_ad_id = '$lookup_id' and question_id = '$action_id'";
            $sql['add_all'] = "insert ignore into job_ads_questions (job_ad_id, question_id, sort_order) (select '$lookup_id', id, sort_order from job_ad_questions);";
            $sql['remove_all'] = "delete from job_ads_questions where job_ad_id = '$lookup_id';";
            //echo $sql[$action];
            $this->dbi->query($sql[$action]);
            $message['add'] = "Question Added";
            $message['remove'] = "Question Removed";
            $message['add_all'] = "All Questions Added";
            $message['remove_all'] = "All Questions Removed";
            $str .= $this->message($message[$action], 1500);
        }


        $this->list_obj->sql = "select id as idin, item_name as `Question`,
                            CONCAT('<a class=\"list_a\" href=\"JobAdsQuestions?show_min=1&lookup_id=$lookup_id&action=add&action_id=', id, '\">Add</a>') as `Add`
                            from job_ad_questions where id not in (select question_id from job_ads_questions where job_ad_id = '$lookup_id')";
        //echo "<textarea>{$this->list_obj->sql}</textarea>";
        //exit;
        $str .= "<div class=\"fl\" style=\"padding-right: 60px;\"><h3>Questions to Add</h3>";
        if ($tmp = $this->list_obj->draw_list()) {
            $str .= "<p><a class=\"list_a\" href=\"JobAdsQuestions?show_min=1&lookup_id=$lookup_id&action=add_all\">Add All</a></p>$tmp";
        } else {
            $str .= "<i>No more questions to add...</i>";
        }
        $str .= "</div>";

        $this->list_obj->sql = "select id as idin, item_name as `Question`,
                            CONCAT('<a class=\"list_a\" href=\"JobAdsQuestions?show_min=1&lookup_id=$lookup_id&action=remove&action_id=', id, '\">Remove</a>') as `Add`
                            from job_ad_questions where id in (select question_id from job_ads_questions where job_ad_id = '$lookup_id')";

        $str .= "<div class=\"fl\"><h3>Added Questions</h3>";
        if ($tmp = $this->list_obj->draw_list()) {
            $str .= "<p><a class=\"list_a\" href=\"JobAdsQuestions?show_min=1&lookup_id=$lookup_id&action=remove_all\">Remove All</a></p>$tmp";
        } else {
            $str .= "<i>No questions to added...</i>";
        }
        $str .= "</div>";

        return $str;
    }

    function JobAdsCategories() {
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $move = (isset($_POST["hdnMove"]) ? $_POST["hdnMove"] : null);
        $move_action = (isset($_POST["hdnMoveAction"]) ? $_POST["hdnMoveAction"] : null);

        $str .= '
      <input type="hidden" name="hdnMoveAction" id="hdnMoveAction">
      <input type="hidden" name="hdnMove" id="hdnMove">
      <script>
        function move(id, direction) {
          document.getElementById(\'hdnMoveAction\').value = direction
          document.getElementById(\'hdnMove\').value = id
          document.frmEdit.submit();
        }
      </script>
    ';

        if ($move && $move_action) {
            if ($move_action == "up") {
                $sql = "select sort_order from job_ads_categories where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ads_categories where sort_order = " . ($sort_order - 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ads_categories set sort_order = sort_order + 1 where id = $id2;");
                    $this->dbi->query("update job_ads_categories set sort_order = sort_order - 1 where id = $id1;");
                }
            } else if ($move_action == "down") {
                $sql = "select sort_order from job_ads_categories where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from job_ads_categories where and sort_order = " . ($sort_order + 1) . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update job_ads_categories set sort_order = sort_order + 1 where id = $id1;");
                    $this->dbi->query("update job_ads_categories set sort_order = sort_order - 1 where id = $id2;");
                }
            }
        }

        $this->list_obj->title = "Job Advertisement Categories";
        $this->list_obj->sql = "
    	select job_ads_categories.id as idin, 
      concat(if(job_ads_categories.sort_order != 1, concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ads_categories.id, ', \'up\')\"><span data-uk-icon=\"icon: triangle-up\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-up\"></span>`,
      concat(if(job_ads_categories.sort_order < (select count(id) from job_ads_categories),
      concat('<a class=\"list_a\" href=\"JavaScript:move(', job_ads_categories.id, ', \'down\')\"><span data-uk-icon=\"icon: triangle-down\"></span></a>'), ''))as `<span data-uk-icon=\"icon: triangle-down\"></span>`,
      job_ads_categories.item_name as `Item Name`, job_ads_categories.description as `Description`, 'Edit' as `*`, 'Delete' as `!` from job_ads_categories
    	order by sort_order, job_ads_categories.item_name
      ";
        //echo "<textarea>{$this->list_obj->sql}</textarea>";
        $this->list_obj->show_num_records = true;
        $this->editor_obj->table = "job_ads_categories";
        $style = 'style="width: 200px;"';
        $style_large = 'style="width: 400px;"';
        $style = 'style="width: 600px; margin-bottom: 10px;"';
        $this->editor_obj->form_attributes = array(
            array("txtItemName", "txaDescription"),
            array("Title", "Description"),
            array("item_name", "description"),
            array("", ""),
            array($style, 'style="width: 600px; height: 85px;"'),
            array("c", "n"),
            array("", ""),
            array("", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtItemName</nobr><br />txtItemName<br />' . $this->editor_obj->button_list() . '</div>
              <div class="fl"><nobr>ttxaDescription</nobr><br />txaDescription<br /></div>
              <div class="cl"></div>';
        $this->editor_obj->editor_template = '
                <div class="form-wrapper">
                <div class="form-header">Job Advertisement Categories</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
    						<div class="cl"></div>
                editor_list
                <div class="cl"></div>
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        if ($action == "add_record") {
            $save_id = $this->editor_obj->last_insert_id;
            $max_sort = 0;
            $sql = "select max(sort_order) as `max_sort` from job_ads_categories";
            if ($result = $this->dbi->query($sql)) {
                $myrow = $result->fetch_assoc();
                if ($result->num_rows) {
                    $max_sort = $myrow['max_sort'];
                }
            }
            $max_sort += 1;
            $this->dbi->query("update job_ads_categories set sort_order = $max_sort where id = $save_id");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        } else if ($action == "delete_record") {
            $this->dbi->query("UPDATE job_ads_categories e,
                         (SELECT @n := 0) m
                         SET e.sort_order = @n := @n + 1");
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }
        return $str;
    }

    function Assets() {
        $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);

        $edit_categories = (isset($_GET['edit_categories']) ? $_GET['edit_categories'] : null);
        $edit_makes = (isset($_GET['edit_makes']) ? $_GET['edit_makes'] : null);

        if ($edit_makes)
            return $this->LookupItems(128, "Asset Makes", "", 1);
        if ($edit_categories)
            return $this->LookupItems(26, "Asset Categories", "", 1);


        if ($download_xl) {
            $xl_obj = new data_list;
            $xl_obj->dbi = $this->dbi;



            $xl_obj->sql = "
            select assets.id as `ID`, 
            lookup_fields1.item_name as `Asset Category`, assets.description as `Name of Item`,
            lookup_fields2.item_name as `Make`,
            assets.model as `Model`,
            if(users.employee_id != '', users.employee_id, '') as `SID`,
            CONCAT(users.name, ' ', users.surname) as `Assigned To`,
            if(users2.client_id != '', users2.client_id, '') as `CID`,
            CONCAT(users2.name, ' ', users2.surname) as `Location`,
            assets.label_no as `Label ID`, 
            assets.parent_label_no as `Belongs To`,
            assets.serial_number as `Serial Number`,
            if(assets.quantity = 0, '', assets.quantity) as `Quantity`,
            if(assets.purchase_price > 0, CONCAT('$', FORMAT(assets.purchase_price, 2)), '') as `$`,
            if(assets.purchase_date = '0000-00-00', '', assets.purchase_date) as `Purchase Date`, assets.notes as `Notes`
            FROM assets
            left join lookup_fields1 on lookup_fields1.id = assets.category_id
            left join lookup_fields2 on lookup_fields2.id = assets.make_id
            left join users on users.id = assets.assigned_to_id
            left join users2 on users2.id = assets.location_id
      ";
            //return $xl_obj->sql;
            //$xl_obj->sql_xl('assets.xlsx');
            //$column_array = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ", "BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BW", "BX", "BY", "BZ", "CA", "CB", "CC", "CD", "CE", "CF", "CG", "CH", "CI", "CJ", "CK", "CL", "CM", "CN", "CO", "CP", "CQ", "CR", "CS", "CT", "CU", "CV", "CW", "CX", "CY", "CZ");
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->getStyle("A1:BZ1")->getFont()->setBold(true);
            for ($i = 2; $i <= 500; $i++) {
                $objValidation = $objPHPExcel->getActiveSheet()->getCell("B$i")->getDataValidation();
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('Input Error');
                $objValidation->setError('Value is not in list.');
                $objValidation->setPromptTitle('Categories');
                $objValidation->setPrompt('Choose a Category from the Dropdown List');
                $items = $this->lookup_list('asset_categories');
                $objValidation->setFormula1('"' . $items . '"');

                $objValidation = $objPHPExcel->getActiveSheet()->getCell("D$i")->getDataValidation();
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('Input Error');
                $objValidation->setError('Value is not in list.');
                $objValidation->setPromptTitle('Makes');
                $objValidation->setPrompt('Choose a Make from the Dropdown List');
                $items = $this->lookup_list('asset_make');
                $objValidation->setFormula1('"' . $items . '"');
            }
            $xl_obj->sql_xl('assets.xlsx', $objPHPExcel);
        } else {
            $site_sql = $this->user_dropdown(384);
            $order_by = $_POST['optOrderBy'];
            if ($order_by == "Location") {
                $loc_xtra = "checked";
                $cat_xtra = "";
                $sort_xtra = " order by users2.name ";
            } elseif ($order_by == "Category") {
                $loc_xtra = "";
                $cat_xtra = "checked";
                $sort_xtra = " order by lookup_fields1.item_name ";
            }
            $str .= '<div class="form-wrapper"><div class="form-header" style="height: 30px;"><h3 class="fl">Asset Register</h3>';
            $input_obj = new input_item;



            $str .= "<div style=\"padding-left: 50px;\" class=\"fl\"><a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Edit/Assets?download_xl=1\">Download Excel</a> <a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "Page/UploadAssets\">Upload Excel</a></div><div class=\"fl\" style=\"padding-left: 50px; display: inline;\"><b>Order By: </b></div><div class=\"fl\">" . $input_obj->opt("optCategories|optOrderBy", 'Category', $cat_xtra, '', '', '') . " " . $input_obj->opt("optLocations|optOrderBy", 'Location', $loc_xtra, '', '', '') . "</div>";
            $str .= '</div><div class="cl"></div>';
            $filter_string = "filter_string";
            $this->list_obj->title = "Assets";
            $this->list_obj->show_num_records = 1;
            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 100;
            $this->list_obj->nav_count = 20;
            $this->list_obj->sql = "
            select assets.id as idin, 
            lookup_fields1.item_name as `Asset Category`, assets.description as `Name of Item`,
            lookup_fields2.item_name as `Make`,
            assets.model as `Model`,
            CONCAT(users2.name, ' ', users2.surname) as `Location`, CONCAT(users3.name, ' ', users3.surname) as `Assigned To`,
            CONCAT('<a id=\"', assets.id, '\"></a>', assets.label_no) as `Label ID`,
            assets.parent_label_no as `Belongs To`,
            if(assets.purchase_price > 0, CONCAT('$', FORMAT(assets.purchase_price, 2)), '-') as `$`, assets.purchase_date as `Purchase Date`, assets.notes as `Notes`,
            CONCAT(assets.updated_date, '<br/>', users.name, ' ', users.surname) as `Updated On/By`,
            'Edit' as `*`, 'Delete' as `!`
            FROM assets
            left join lookup_fields1 on lookup_fields1.id = assets.category_id
            left join lookup_fields2 on lookup_fields2.id = assets.make_id
            left join users on users.id = assets.updated_by
            left join users2 on users2.id = assets.location_id
            left join users3 on users3.id = assets.assigned_to_id
            where 1 $filter_string
            $sort_xtra
        ";
            $this->editor_obj->add_now = "updated_date";
            $this->editor_obj->update_now = "updated_date";
            $this->editor_obj->custom_field = "updated_by";
            $this->editor_obj->custom_value = $_SESSION['user_id'];
            $this->editor_obj->table = "assets";
            $style = 'class="full_width"';
            $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"
            //$location_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' where users.name NOT LIKE '%ADHOC%') as a order by item_name";
            $this->editor_obj->form_attributes = array(
                array("selCategory", "txtDescription", "cmbLocation", "txtLabelNo", "txtParentLabelNo", "txtPurchasePrice", "calPurchaseDate", "txtNotes", "cmbMake", "txtSerialNumber", "txtQuantity", "txtSize", "cmbAssignedTo", "txtModel"),
                array("Category", "Name of Item", "Location", "Label ID", "Belongs To (Lbl ID)", "Purchase Price", "Purchase Date", "Asset Notes", "Make", "Serial No.", "Qty (Whole Nums)", "Size", "Assigned To", "Model"),
                array("category_id", "description", "location_id", "label_no", "parent_label_no", "purchase_price", "purchase_date", "notes", "make_id", "serial_number", "quantity", "size", "assigned_to_id", "model"),
                array($this->get_lookup('asset_categories'), "", $site_sql, "", "", "", "", "", $this->get_cmb_lookup('asset_make'), "", "", "", $this->user_dropdown(107, 108), ""),
                array($style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style),
                array("c", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset", "Filter"),
                array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
            );


            $this->editor_obj->form_template = '
                <div class="fl small_textbox"><nobr>tselCategory [<a tabindex="-1" target="_blank" href="' . $this->f3->get('main_folder') . 'Edit/Assets?edit_categories=1">Edit</a>]</nobr><br />selCategory</div>
                <div class="fl large_textbox"><nobr>ttxtDescription</nobr><br />txtDescription</div>
                <div class="fl small_textbox"><nobr>tcmbMake [<a tabindex="-1" target="_blank" href="' . $this->f3->get('main_folder') . 'Edit/Assets?edit_makes=1">Edit</a>]</nobr><br />cmbMake</div>
                <div class="fl small_textbox"><nobr>ttxtModel</nobr><br />txtModel</div>
                <div class="fl med_textbox"><nobr>tcmbLocation</nobr><br />cmbLocation</div>
                <div class="fl med_textbox"><nobr>tcmbAssignedTo</nobr><br />cmbAssignedTo</div>
                <div class="fl small_textbox"><nobr>ttxtLabelNo</nobr><br />txtLabelNo</div>
                <div class="fl small_textbox"><nobr>ttxtParentLabelNo</nobr><br />txtParentLabelNo</div>
                <div class="fl med_textbox"><nobr>ttxtSerialNumber</nobr><br />txtSerialNumber</div>
                <div class="fl small_textbox"><nobr>ttxtQuantity</nobr><br />txtQuantity</div>
                <div class="fl small_textbox"><nobr>ttxtSize</nobr><br />txtSize</div>
                <div class="fl small_textbox"><nobr>ttxtPurchasePrice</nobr><br />txtPurchasePrice</div>
                <div class="fl small_textbox"><nobr>tcalPurchaseDate</nobr><br />calPurchaseDate</div>
                <div class="fl large_textbox"><nobr>ttxtNotes</nobr><br />txtNotes</div>
                <div class="cl"></div>
                <br />
                <div class="cl"></div>
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

    function ComplianceEditor() {

        $filter_string = "filter_string";



        $this->list_obj->sql = "
          select compliance.id as idin,
          compliance.title as `Title`, lookup_fields1.item_name as `Category`, 
          user_level.item_name as `Min User Lvl`, compliance.logo_print as `Logo Print`, compliance.description as `Description`,
          CONCAT('<nobr><a class=\"list_a\" uk-tooltip=\"title: Report Questions\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Edit/ComplianceQuestions?lookup_id=', compliance.id, '\">Questions</a>
            <a class=\"list_a\" uk-tooltip=\"title: Compliance Auditors\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Page/ComplianceAccess?compliance_id=', compliance.id, '\">Auditors\</a>
            <a class=\"list_a\" uk-tooltip=\"title: Compliance Auditors\" target=\"_blank\" href=\"" . $this->f3->get('main_folder') . "Page/UploadCompliance?compliance_id=', compliance.id, '\">Upload Questions\</a></nobr>
            <a class=\"list_a\" uk-tooltip=\"title: Download the Template file here.\" href=\"" . $this->f3->get('main_folder') . "Compliance?template_id=', compliance.id, '&template_name=', compliance.title, '\">Download Template</a><a target=\"_blank\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "ComplianceResults?report_id=', compliance.id, '\">Summary</a><a target=\"_blank\" class=\"list_a\" uk-tooltip=\"title: View Summary\" href=\"" . $this->f3->get('main_folder') . "Reporting/Results?cid=', compliance.id, '\">Results Table</a>
            <a class=\"list_a\" target=\"_blank\" uk-tooltip=\"title: Show Reports.\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '\">Reports</a><a class=\"list_a\" target=\"_blank\" uk-tooltip=\"title: Start a New Report.\" href=\"" . $this->f3->get('main_folder') . "Compliance?report_id=', compliance.id, '&new_report=1\">New</a>
            <a class=\"list_a\" target=\"_blank\" uk-tooltip=\"title: Downloads for the Compliance Check.\" href=\"" . $this->f3->get('main_folder') . "Resources?show_upload_form=1&current_dir=compliance&current_subdir=downloads" . urlencode('/') . "', compliance.id, '\">Downloads</a>
            '
          )	as `**`,
          'Edit' as `*`, 'Delete' as `!`
          FROM compliance
    			left join lookup_fields1 on lookup_fields1.id = compliance.category_id
          left join user_level on user_level.id = compliance.min_user_level_id
          where 1 $filter_string
          order by title
      ";
        $this->editor_obj->table = "compliance";
        $style = 'style="width: 200px;"';
        $style_small = 'style="width: 150px;"';
        $style_large = 'style="width: 490px;"';
        $this->editor_obj->form_attributes = array(
            array("txtLogoPrint", "selMinUserLevel", "txtQuestionTitle", "calClosingDate", "selCategory", "txaDescription", "chlUserGroups", "chkShowEntities", "chkShowAnswers", "chkAllowSharing", "chkHideScore", "chkScoreOutOf", "chkAllAccess", "chkIsActive"),
            array("Logo Print", "Min User Lvl", "Title", "Survey Closing Date", "Category", "<b>Description</b>", "<b>User Groups for Report Subjects</b>", "Show All Entities", "Show Answers", "Allow Sharing", "Hide Score", "Completed/Score", "All Access", "Is Active"),
            array("logo_print", "min_user_level_id", "title", "closing_date", "category_id", "description", "id", "show_all_entities", "show_answers", "allow_sharing", "hide_score", "score_completed_only", "allow_all_access", "is_active"),
            array("", $this->get_simple_lookup('user_level', 'Select User Level'), "", "", $this->get_lookup('compliance_category'), "", $this->get_chl('user_group'), "1", "", "", "", "", "", ""),
            array($style_small, $style_small, $style_large, $style_small, $style_small, 'style="height: 100px; width: 1160px;"', "", "", "", "", "", "", "", ""),
            array("", "", "c", "", "", "", "", "", "", "", "", "", "", ""),
            array("", "", "", "", "", "", "user_group", "", "", "", "", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtQuestionTitle</nobr><br />txtQuestionTitle</div>
              <div class="fl"><nobr>tselCategory</nobr><br />selCategory</div>
              <div class="fl"><nobr>tcalClosingDate</nobr><br />calClosingDate</div>
              <div class="fl"><nobr>tselMinUserLevel</nobr><br />selMinUserLevel</div>
              <div class="fl"><nobr>ttxtLogoPrint</nobr><br />txtLogoPrint</div>
              <div class="cl"></div>
              <div style="padding-top: 8px;">
              <div class="fl">ttxaDescription &nbsp;</div>
              <div class="fl">&nbsp;chkShowEntities tchkShowEntities</div>
              <div class="fl">chkShowAnswers tchkShowAnswers</div>
              <div class="fl">chkHideScore tchkHideScore</div>
              <div class="fl">chkScoreOutOf tchkScoreOutOf</div>
              <div class="fl">chkAllAccess tchkAllAccess</div>
              <div class="fl">chkAllowSharing tchkAllowSharing</div>
              <div class="fl">chkIsActive tchkIsActive</div>
              </div>
              <div class="cl"></div>
              txaDescription
              <br />
              <div style="max-width: 1160px;">tchlUserGroups<br />chlUserGroups</div>
              <div class="cl"></div>
              <br /><br />
              ' . $this->editor_obj->button_list();

        if ($_POST['idin']) {
            $src = $this->f3->get('main_folder') . "Edit/ComplianceQuestions?show_min=1&lookup_id=" . $_POST['idin'];
        }

        $this->editor_obj->editor_template = '
    						<div class="fl" style="adding: 0px; margin-right: 10px;">
                <table class="standard_form" style="">
                <tr><td class="form_header">
                <div class="fl">Reports Editor</div>
                </td>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                editor_list
                </div>
    						<div class="fl" style="margin-left: 20px; width: 100%; padding: 0px; margin: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame" src="' . $src . '"></iframe>
    						</div>
                <div class="cl"></div>
    ';

        $str .= $this->editor_obj->draw_data_editor($this->list_obj);

        return $str;
    }

    function ComplianceQuestions() {
        $str .= '
    <script type="text/javascript">
    function copy_questions() {
      document.getElementById("hdnAddChoices").value = 1;
      document.frmEdit.submit();
    }
    </script>';
        $lookup_id = $_GET['lookup_id'];
        $sql = "select title from compliance where id = $lookup_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $title = $myrow['title'];
        }
        $selCopyQuestions = $_POST['selCopyQuestions'];
        $hdnAddChoices = $_POST['hdnAddChoices'];
        if ($hdnAddChoices && $selCopyQuestions) {
            $sql = "select id, sort_order, question_type, question_title, answer, choices_per_row from compliance_questions where compliance_id = $lookup_id";
            $result = $this->dbi->query($sql);
            $sql = "delete from compliance_questions where compliance_id = $selCopyQuestions";
            $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $sort_order = $myrow['sort_order'];
                $question_type = $myrow['question_type'];
                $answer = $myrow['answer'];
                $question_title = $myrow['question_title'];
                $choices_per_row = $myrow['choices_per_row'];
                $sql = "insert into compliance_questions (compliance_id, sort_order, question_type, answer, question_title, choices_per_row) values ('$selCopyQuestions', '$sort_order', '$question_type', '$answer', '$question_title', '$choices_per_row')";
                $this->dbi->query($sql);
                $iid = $this->dbi->insert_id;
                $sql = "insert into compliance_question_choices (compliance_question_id, sort_order, choice, choice_value, additional_text_required, colour_scheme_id)
                select '$iid', sort_order, choice, choice_value, additional_text_required, colour_scheme_id from compliance_question_choices where compliance_question_id = $id";
                $this->dbi->query($sql);
            }
        }
        $this->list_obj->sql = "
    			select compliance_questions.id as idin, compliance_questions.sort_order as `Srt`, compliance_questions.question_type as `Typ`, compliance_questions.question_title as `Title`,
          compliance_questions.choices_per_row as `||`, compliance_questions.parent as `P`, 'Edit' as `*`, 'Delete' as `!`,
          CONCAT('<nobr><a uk-tooltip=\"title: Complianc Question Choices\" class=\"list_a\" target=\"child_frame2\" href=\"" . $this->f3->get('main_folder') . "Edit/ComplianceChoices?show_min=1&lookup_id=', id, '\">Choices</a>')	as `**`
    			FROM compliance_questions
          where compliance_questions.compliance_id = $lookup_id
    			order by compliance_questions.sort_order
    		  $filter_string
      ";
        $this->editor_obj->xtra_id_name = "compliance_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $list_top = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "compliance_questions";
        $style = 'style="width: 125px;"';
        $style_small = 'style="width: 140px;"';
        $this->editor_obj->form_attributes = array(
            array("txtSortOrder", "txtQuestionType", "txaQuestionTitle", "txaAnswer", "txtChoicesPerRow", "txtParent"),
            array("<br />Sort", "<br />Type", "Question", "Answer (for Auditor)", "Choices/Row (Cols)<br />(Num Rows for Txt)", "<br />Parent"),
            array("sort_order", "question_type", "question_title", "answer", "choices_per_row", "parent"),
            array("", "", "", "", "", ""),
            array($style, $style, 'style="height: 100px; width: 530px;"', 'style="height: 100px; width: 530px;"', $style, $style),
            array("c", "c", "c", "n", "n", "n"),
            array("", "", "", "", "2", ""),
            array("", "", "", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
    					<div style="float: left; padding-right: 2px;"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
    					<div style="float: left; padding-right: 2px;"><nobr>ttxtQuestionType</nobr><br />txtQuestionType</div>
    					<div style="float: left; padding-right: 2px;"><nobr>ttxtChoicesPerRow</nobr><br />txtChoicesPerRow</div>
    					<div style="float: left; padding-right: 2px;"><nobr>ttxtParent</nobr><br />txtParent</div>
    					<div class="cl"></div>
    					<nobr>ttxaQuestionTitle</nobr><br />
              txaQuestionTitle<br />
    					<nobr>ttxaAnswer</nobr><br />
              txaAnswer
                <input type="hidden" id="hdnAddChoices" name="hdnAddChoices" />
              </div><h4>Copy All Questions and Choices To</h4><select style="width: 300px;" name="selCopyQuestions"><option value="0">-- Select --</option>';
        $sql = "select id, title from compliance where id != $lookup_id and id != 1 order by title";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $this->editor_obj->form_template .= '<option value="' . $myrow['id'] . '">' . $myrow['title'] . '</option>';
        }
        $this->editor_obj->form_template .= '</select><input type="button" onClick="copy_questions()" value="Go" /><br />' . $this->editor_obj->button_list();
        if ($_POST['idin']) {
            $src = "ComplianceChoices?show_min=1&lookup_id=" . $_POST['idin'];
        }
        $this->editor_obj->editor_template = '
    						<div class="fl" style="padding: 0px; margin: 0px;">
                <table border="0">
                <tr>
                <td valign="top">
                <table class="standard_form">
                <div class="form-header">Report Questions for ' . $title . '</div>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                </td>
                <td valign="top">
                <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                </td>
                </tr>
                </table>
                editor_list
                </div>
    						<div class="fl" style="margin-left: 20px; width: 100%; padding: 0px; margin: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame2" src="' . $src . '"></iframe>
    						</div>
                <div class="cl">';
        $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function ComplianceChoices() {
        $str .= '
    <script type="text/javascript">
    function add_choices() {
      document.getElementById("hdnAddChoices").value = 1;
      document.frmEdit.submit();
    }
    </script>';
        $lookup_id = $_GET['lookup_id'];
        $selAddFromTemplate = $_POST['selAddFromTemplate'];
        $hdnAddChoices = $_POST['hdnAddChoices'];
        if ($hdnAddChoices && $selAddFromTemplate) {
            $sql = "select id, sort_order, choice, additional_text_required, choice_value, colour_scheme_id from compliance_question_choices where compliance_question_id = $selAddFromTemplate";
            $result = $this->dbi->query($sql);
            $sql = "delete from compliance_question_choices where compliance_question_id = $lookup_id";
            $this->dbi->query($sql);
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $sort_order = $myrow['sort_order'];
                $choice = $myrow['choice'];
                $choice_value = $myrow['choice_value'];
                $additional_text_required = $myrow['additional_text_required'];
                $colour_scheme_id = $myrow['colour_scheme_id'];
                $sql = "insert into compliance_question_choices (compliance_question_id, sort_order, choice, choice_value, additional_text_required, colour_scheme_id) values ('$lookup_id', '$sort_order', '$choice', '$choice_value', '$additional_text_required', '$colour_scheme_id')";
                $this->dbi->query($sql);
            }
        }
        $sql = "select question_title from compliance_questions where id = $lookup_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $title = $myrow['question_title'];
        }
        $this->list_obj->sql = "
    			select compliance_question_choices.id as idin, compliance_question_choices.sort_order as `Srt`, compliance_question_choices.choice as `Choice`, compliance_question_choices.choice_value as `Value`, compliance_question_choices.additional_text_required as `Triggers Text`, lookup_fields1.item_name as `Colour on Select`, 'Edit' as `*`, 'Delete' as `!`
    			FROM compliance_question_choices
    			left join lookup_fields1 on lookup_fields1.id = compliance_question_choices.colour_scheme_id
          where compliance_question_choices.compliance_question_id = $lookup_id
    			order by compliance_question_choices.sort_order, compliance_question_choices.choice
    		  $filter_string
      ";
        $this->editor_obj->xtra_id_name = "compliance_question_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $list_top = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "compliance_question_choices";
        $style = 'style="width: 190px;"';
        $style_small = 'style="width: 140px;"';
        $this->editor_obj->form_attributes = array(
            array("txtSortOrder", "txtChoice", "txtValue", "txtTriggersText", "selColourScheme"),
            array("Sort", "Choice", "Value", "Triggers Text", "Colour On Select"),
            array("sort_order", "choice", "choice_value", "additional_text_required", "colour_scheme_id"),
            array("", "", "", "", $this->get_lookup("compliance_colour_scheme")),
            array($style, $style, $style, $style, $style),
            array("c", "c", "n", "n", "n"),
            array("", "", "", "", ""),
            array("", "", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
    					<div style="float: left; padding-right: 4px;"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
    					<div style="float: left; padding-right: 4px;"><nobr>ttxtChoice</nobr><br />txtChoice</div>
    					<div style="float: left; padding-right: 4px;"><nobr>ttxtValue</nobr><br />txtValue</div>
    					<div style="float: left; padding-right: 4px;"><nobr>ttxtTriggersText</nobr><br />txtTriggersText</div>
    					<div style="float: left; padding-right: 4px;"><nobr>tselColourScheme</nobr><br />selColourScheme</div>
    					<div class="cl"></div>
    					<input type="hidden" id="hdnAddChoices" name="hdnAddChoices" />
              ' . $this->editor_obj->button_list()
                . '<h4>Quick Add from Template</h4><select name="selAddFromTemplate">';
        $sql = "select id, question_title from compliance_questions where compliance_id = 1 order by sort_order";
        $result = $this->dbi->query($sql);
        while ($myrow = $result->fetch_assoc()) {
            $this->editor_obj->form_template .= '<option value="' . $myrow['id'] . '">' . $myrow['question_title'] . '</option>';
        }
        $this->editor_obj->form_template .= '</select><input type="button" onClick="add_choices()" value="Go" />';
        $this->editor_obj->editor_template = '
      <div class="form-wrapper" style="max-width: 650px;">
      <div class="form-header">Choices for: ' . $title . '</div>
      <div class="form-content">
      editor_form
      </div>
      </div>
      <div class="cl"></div>
      editor_list
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
        return $str;
    }

    function UserNotes() {
        $lookup_id = $_GET['lookup_id'];
        if (!$lookup_id)
            $lookup_id = $_SESSION['user_id'];
        $sql = "select employee_id, name, surname from users where id = $lookup_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $employee_id = $myrow['employee_id'];
            $name = $myrow['name'];
            $surname = $myrow['surname'];
        }
        //$this->list_obj->title = "Notes for $employee_id $name $surname";
        $this->list_obj->sql = "
    			select user_notes.id as idin,
          user_notes.date as `Date`,
          user_notes.description as `Description`, CONCAT(users.name, ' ', users.surname) as `Edited By`,
    			'Edit' as `*`, 'Delete' as `!`
    			FROM user_notes
          left join users on users.id = user_notes.edited_by
          where user_id = $lookup_id
      ";
        $this->editor_obj->custom_field = "edited_by";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->xtra_id_name = "user_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $this->editor_obj->table = "user_notes";
        $style_small = 'style="width: 120px;"';
        $this->editor_obj->form_attributes = array(
            array("calDate", "txaDescription"),
            array("Date", "Description"),
            array("date", "description"),
            array("", ""),
            array($style_small . 'value="' . date('d-M-Y') . '"', 'style="width: 500px; height: 60px;"'),
            array("c", "n"),
            array("", ""),
            array("", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
    					
    					<div class="fl"><nobr>tcalDate</nobr><br />calDate</div>
    					<div class="fl"><nobr>ttxaDescription</nobr><br />txaDescription</div>
              <div class="cl"></div>
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div class="fl">
                <div class="form-wrapper" style="max-width: 100%;">
                <div class="form-header">Notes for ' . "$employee_id $name $surname" . '</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
                editor_list
    						</div>
    						<div class="cl"></div>
    ';
        $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function OpeningClosing() {
        $timediffs = (isset($_POST['timediffs']) ? $_POST['timediffs'] : null);
        $copy_monday = (isset($_POST['copy_monday']) ? $_POST['copy_monday'] : null);
        $lookup_id = $_GET['lookup_id'];
        if (!$lookup_id)
            $lookup_id = $_SESSION['user_id'];
        if ($timediffs) {
            $sql = "
        update opening_closing set check_period = 
        ROUND(TIME_TO_SEC(TIMEDIFF(CONCAT('2010-01-0', if(TIME_TO_SEC(closing_time) - TIME_TO_SEC(opening_time) < 0, '2', '1'), ' ', closing_time), 
                              CONCAT('2010-01-01 ', opening_time))) / 60)
        where site_id = $lookup_id
       ";
            $result = $this->dbi->query($sql);
            $this->message('Items Copied');
        }
        if ($copy_monday) {
            $sql = "select opening_time, closing_time, check_period from opening_closing where site_id = $lookup_id and day_of_week_id = 91";
            //return $sql;
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $opening_time = $myrow['opening_time'];
                $closing_time = $myrow['closing_time'];
                $check_period = $myrow['check_period'];
            }
            $sql = "update opening_closing set opening_time = '$opening_time', closing_time = '$closing_time', check_period = '$check_period' where site_id = $lookup_id";
            $result = $this->dbi->query($sql);
        }
        $str .= '
    <input type="hidden" name="timediffs" id="timediffs" />
    <input type="hidden" name="copy_monday" id="copy_monday" />
    <script>
      function select_timediffs() {
        var confirmation;
        confirmation = "Clicking OK will setup all WFC Periods for Sign on/off and remove them from the Welfare Dashboard. Proceed?";
        if (confirm(confirmation)) {
         document.getElementById("timediffs").value = 1
          document.frmEdit.submit()
        }
      }
      function copy_from_monday() {
        var confirmation;
        confirmation = "Clicking OK will make all values the same as the value set for Monday. Proceed?";
        if (confirm(confirmation)) {
         document.getElementById("copy_monday").value = 1
          document.frmEdit.submit()
        }
      }
    </script>';

        $sql = "select client_id, name, surname from users where id = $lookup_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $client_id = $myrow['client_id'];
            $name = $myrow['name'];
            $surname = $myrow['surname'];
        }
        $this->list_obj->title = "Availability for $client_id $name $surname";
        $this->list_obj->sql = "
    			select opening_closing.id as idin, lookup_fields1.item_name as `Week Day`,
          DATE_FORMAT(opening_closing.opening_time, '%H:%i') as `Opening Time`,
          DATE_FORMAT(opening_closing.closing_time, '%H:%i') as `Closing Time`,
          opening_closing.check_period as `WFC Period`,
    			'Edit' as `*`, 'Delete' as `!`
    			FROM opening_closing
    			left join lookup_fields1 on lookup_fields1.id = opening_closing.day_of_week_id
          where site_id = $lookup_id
    			order by lookup_fields1.id, opening_closing.opening_time
    		  $filter_string
      ";
        $this->editor_obj->xtra_id_name = "site_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $this->editor_obj->table = "opening_closing";
        $style = 'style="width: 200px;"';
        $style_small = 'style="width: 120px;"';
        $this->editor_obj->form_attributes = array(
            array("selWeekDay", "ti2Opening", "ti2Closing", "txtWFCPeriod"),
            array("Week Day", "Opening Time", "Closing Time", "WFC Period (Minutes)"),
            array("day_of_week_id", "opening_time", "closing_time", "check_period"),
            array($this->get_lookup('week_days'), "", "", ""),
            array($style, $style_small, $style_small, $style_small . ' value="60"'),
            array("c", "c", "c", "n"),
            array("", "", "", ""),
            array("", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
    					<div class="fl"><nobr>tselWeekDay</nobr><br />selWeekDay</div>
    					<div class="fl"><nobr>tti2Opening</nobr><br />ti2Opening</div>
    					<div class="fl"><nobr>tti2Closing</nobr><br />ti2Closing</div>
    					<div class="fl"><nobr>ttxtWFCPeriod</nobr><br />txtWFCPeriod</div>
    					<div style="clear: left;"></div>
              ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
                <div class="form-wrapper" style="max-width: 680px;">
                <div class="form-header">Opening/Closing Times for ' . "$client_id $name $surname" . '</div>
    						<div class="form-content">
    						editor_form
    						</div>
    						</div>
                editor_list
    ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        $str .= '
    <input type="hidden" name="lookup_id" value="' . $lookup_id . '">
    <br /><p><b>Note: </b>To make the site appear on the welfare check dashboard, set the WFC period to be 120 minutes (2 hours) or less.<br />
    To make it appear on the sign on/off dashboard, set the WFC period to be the same as the time difference between the opening and closing times.<br />
    <br />*The opening/closing times can be set automatically for sign on/off by clicking the button below.</p>
    <input type="button" onClick="select_timediffs()" value="Setup All Items for Sign on/Off" /><div class="cl"></div>
    <p>Pressing the button below will copy all the times from Monday.</p>
    <input type="button" onClick="copy_from_monday()" value="Copy ALL from Monday" /><div class="cl"></div>';
        return $str;
    }

    function NewsTickerEditor() {
        $str = "<div class=\"form-wrapper\"><div class=\"form-header\"><h3>News Ticker Editor</h3>Add one news item per line...</div>";
        $txtItems = (isset($_POST['txtItems']) ? $_POST['txtItems'] : null);
        if ($txtItems) {
            $this->dbi->query("delete from notice_board where sort_order >= 500 and sort_order < 1000;");
            $items = explode("\r\n", $txtItems);
            $cnt = 510;
            foreach ($items as $item) {
                if ($item) {
                    $item = mysqli_real_escape_string($this->dbi, $item);
                    $sql = "insert into notice_board (sort_order, title, modified_by_id, date_modified) values ($cnt, '$item', '{$_SESSION['user_id']}', now());";
                    $this->dbi->query($sql);
                    $cnt++;
                }
            }
            $str .= $this->message("List Updated...", 3000);
        }
        $result = $this->dbi->query("select title from notice_board where sort_order >= 500 and sort_order < 1000");
        while ($myrow = $result->fetch_assoc()) {
            $titles .= $myrow['title'] . "\n";
        }
        $titles = substr($titles, 0, (strlen($titles) - 1));
        $str .= '<div align="center" class="pad10"><textarea name="txtItems" style="width: 99%; height: 300px;" >' . $titles . '</textarea></div><br /><input type="submit" value="Save Changes" /></div>';
        return $str;
    }

    function AppraisalEditor() {
        $this->list_obj->title = "Appraisals";
        $this->list_obj->sql = "
          select appraisals.id as idin, compliance.title as `Associated Appraisal`,
          appraisals.title as `Title`, appraisals.description as `Description`,
          CONCAT(if(appraisals.is_active, 'Yes', 'No')) as `Is Active`,
          CONCAT('<nobr><a class=\"list_a\" uk-tooltip=\"title: Appraisal Questions\" target=\"child_frame\" href=\"" . $this->f3->get('main_folder') . "Edit/AppraisalQuestions?show_min=1&lookup_id=', appraisals.id, '\">Questions</a>')	as `**`,
          'Edit' as `*`, 'Delete' as `!`
          FROM appraisals
    			left join compliance on compliance.id = appraisals.compliance_id
          order by is_active DESC, title
      ";
        $this->editor_obj->table = "appraisals";
        $lookup = "select 0 as id, '--- Select ---' as item_name union all select id, title as `item_name` from compliance where title LIKE '%performance appraisal%'";
        $style = 'style="width: 220px;"';
        $style_large = 'style="width: 440px;"';
        $this->editor_obj->form_attributes = array(
            array("txtQuestionTitle", "txaDescription", "chkIsActive", "selCompliance"),
            array("Title", "Description", "Is Active", "Associated Appraisal"),
            array("title", "description", "is_active", "compliance_id"),
            array("", "", "", $lookup),
            array($style_large, 'style="height: 100px; width: 440px;"', "", $style_large),
            array("c", "", "", ""),
            array("", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
              <div class="fl"><nobr>ttxtQuestionTitle</nobr><br />txtQuestionTitle</div>
              <div class="fl"><nobr>tselCompliance</nobr><br />selCompliance</div>
              <div class="cl"></div>
              <div style="float: left; height: 25px;">ttxaDescription</div>
              <div class="fr">chkIsActive tchkIsActive</div>
              <div class="cl"></div>
              txaDescription
              <div class="cl"></div>
              <br /><br />
              ' . $this->editor_obj->button_list();
        if ($_POST['idin']) {
            $src = $this->f3->get('main_folder') . "Edit/AppraisalQuestions?show_min=1&lookup_id=" . $_POST['idin'];
        }
        $this->editor_obj->editor_template = '
    						<div class="fl" style="width: 41%; padding: 0px; margin-right: 10px;">
                <table class="standard_form" style="width: 480px;">
                <tr><td class="form_header">
                <div class="fl">Appraisal Manager</div>
                </td>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                editor_list
                </div>
    						<div class="fl" style="margin-left: 20px; width: 57%; padding: 0px; margin: 0px;">
    						<iframe style="border: none; width: 100%; height: 750px;" name="child_frame" src="' . $src . '"></iframe>
    						</div>
                <div class="cl"></div>
    ';
        return $this->editor_obj->draw_data_editor($this->list_obj);
    }

    function AppraisalQuestions() {
        $str .= '
    <script type="text/javascript">
    function copy_questions() {
      document.getElementById("hdnAddChoices").value = 1;
      document.frmEdit.submit();
    }
    </script>';

        $lookup_id = $_GET['lookup_id'];
        $sql = "select title from appraisals where id = $lookup_id";
        $result = $this->dbi->query($sql);
        if ($myrow = $result->fetch_assoc()) {
            $title = $myrow['title'];
        }
        $this->list_obj->sql = "
    			select appraisal_questions.id as idin, appraisal_questions.sort_order as `Srt`, appraisal_questions.question as `Title`,
          CONCAT(if(appraisal_questions.is_manager, 'Manager Question', 'Self Question')) as `Question Type`,
          'Edit' as `*`, 'Delete' as `!`
    			FROM appraisal_questions
          where appraisal_questions.Appraisal_id = $lookup_id
    			order by appraisal_questions.sort_order
    		  $filter_string
      ";
        $this->editor_obj->xtra_id_name = "Appraisal_id";
        $this->editor_obj->xtra_id = $lookup_id;
        $list_top = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all";
        $this->editor_obj->table = "appraisal_questions";
        $style = 'style="width: 420px;"';
        $this->editor_obj->form_attributes = array(
            array("txtSortOrder", "txaQuestion", "chkIsManager"),
            array("<br />Sort", "Question", "Manager Question (Uncheck for Self Question)"),
            array("sort_order", "question", "is_manager"),
            array("", "", "", ""),
            array($style, 'style="height: 100px; width: 420px;"', ""),
            array("c", "c", "n"),
            array("", "", ""),
            array("", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
    					<div style="float: left; padding-right: 2px;"><nobr>ttxtSortOrder</nobr><br />txtSortOrder</div>
    					<div class="cl"></div>
    					<nobr>ttxaQuestion</nobr><br />
              txaQuestion<br />
    					<div style="float: left; padding-right: 12px;"><nobr>chkIsManager tchkIsManager</nobr></div>
    					<div class="cl"></div>
              <br />' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
    						<div class="fl" style="width: 470px; padding: 0px; margin: 0px;">
                <table border="0" style="width: 460px;">
                <tr>
                <td valign="top">
                <table class="standard_form">
                <div class="form-header">Appraisal Questions for ' . $title . '</div>
                </tr>
                <tr><td>editor_form</td></tr>
                </table>
                </td>
                <td valign="top">
                <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                </td>
                </tr>
                </table>
                editor_list
                </div>
                <div class="cl">
                <input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function StaffLicenceLookups() {
        $this->list_obj->title = "";
        $filter_string = "filter_string";
        $this->list_obj->sql = "
    SELECT staff_licence_lookups.id as `idin`, if(staff_licence_lookups.is_current=1, 'Yes', 'No') as `Is Current`, states.item_name as `State`, staff_licence_lookups.item_name as `Name`, staff_licence_lookups2.item_name as `Parent`, 'Edit' as `*`, 'Delete' as `!`
    FROM staff_licence_lookups
    left join states on states.id = staff_licence_lookups.state_id
    left join staff_licence_lookups2 on staff_licence_lookups2.id = staff_licence_lookups.parent_id
    where 1
    $filter_string
    order by staff_licence_lookups.parent_id, staff_licence_lookups.item_name
    ";
        $this->editor_obj->table = "staff_licence_lookups";
        $style = 'style="width: 190px"';
        $this->editor_obj->form_attributes = array(
            array("selState", "selParentID", "txtItemName", "chkIsCurrent"),
            array("State", "Parent", "Name", "Is Current"),
            array("state_id", "parent_id", "item_name", "is_current"),
            array("select 0 as id, '--- Select ---' as item_name union all select id, item_name from states order by item_name", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from staff_licence_lookups2 where parent_id = 0 order by item_name", "", ""),
            array($style, $style, $style, ""),
            array("n", "n", "c", "n"),
            array("", "", "", "")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset", "Filter"),
            array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
    <table class="standard_form">
    <tr>
    <td class="form_header">Staff Licence Lookups</td>
    </tr>
    <tr>
    <td>
    <div class="fl">tselState<br />selState</div>
    <div class="fl">tselParentID<br />selParentID</div>
    <div class="fl">ttxtItemName<br />txtItemName</div>
    <div class="fl">tchkIsCurrent<br />chkIsCurrent</div>
    <div class="cl"></div>
    <hr />
    button_list
    </tr>
    </td>
    </table>
    ';
        $this->editor_obj->editor_template = 'editor_form<hr />editor_list';
        $str = $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function StaffPositions() {
        $my_position = (isset($_GET['my_position']) ? $_GET['my_position'] : null);
        if ($my_position) {
            $sql = "
        SELECT staff_positions.title, staff_positions.description
               FROM users
               left join staff_positions on staff_positions.id = users.staff_position_id
               where users.id = " . $_SESSION['user_id'];
            if ($result = $this->dbi->query($sql)) {
                if ($myrow = $result->fetch_assoc()) {
                    $str = "<h3>Position Title: " . $myrow['title'] . "</h3><hr />" . $myrow['description'];
                } else {
                    $str = "<h3></h3>";
                }
            }
        } else {
            $this->list_obj->title = "";
            $filter_string = "filter_string";
            $this->list_obj->sql = "
      SELECT staff_positions.id as `idin`, staff_positions.title as `Title`,
      'Edit' as `*`, 'Delete' as `!`
      FROM staff_positions
      where 1
      $filter_string
      order by title
      ";
            $this->editor_obj->table = "staff_positions";
            $style = 'style="width: 600px"';
            $this->editor_obj->form_attributes = array(
                array("txtTitle", "cmsDescription"),
                array("Title", "Description"),
                array("title", "description"),
                array("", ""),
                array($style, 'height: "280",	width: "98%"',),
                array("c", "n")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset", "Filter"),
                array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
            );
            $this->editor_obj->form_template = '
      <table class="standard_form">
      <tr>
      <td class="form_header">Main Pages</td>
      </tr>
      <tr>
      <td>
      <div class="fl">ttxtTitle<br />txtTitle</div>
      <div class="cl"></div>
      <span class="field_header">tcmsDescription</span><br />
      cmsDescription
      <br />
      <div class="cl"></div>
      <hr />
      button_list
      </tr>
      </td>
      </table>
      ';
            $this->editor_obj->editor_template = 'editor_form<hr />editor_list';
            $str = $this->editor_obj->draw_data_editor($this->list_obj);
        }
        return $str;
    }

    function Newsletters() {

        $preview_id = $_GET['preview_id'];
        $send_id = $_GET['send_id'];
        if ($preview_id || $send_id) {
            if ($preview_id) {
                $to_send_id = $preview_id;
            } else {
                $to_send_id = $send_id;
            }
            $sql = "select date_written, title, description from newsletters where id = $to_send_id;";
            $result = $this->dbi->query($sql);
            if ($myrow = $result->fetch_assoc()) {
                $date_written = date('d-M-Y', strtotime($myrow['date_written']));
                $title = $myrow['title'];
                $mail_body = "<h3>$title</h3><p>$date_written</p><p>" . $myrow['description'] . "</p>";
            }
        }
        if ($preview_id) {
            $str .= "<h3>Newsletter Preview</h4><hr /><a href=\"" . $this->f3->get('main_folder') . "Edit/Newsletters?send_id=$to_send_id\">Click Here to Confirm and Send the Newsletter</a><hr />$mail_body";
        } else if ($send_id) {
            $sql = "update newsletters set sent_count = sent_count + 1 where id = $to_send_id;";
            $result = $this->dbi->query($sql);
            /* include("Classes/mailer/class.phpmailer.php");
              $mail=new PHPMailer();
              $mail->SetLanguage("en", 'Classes/mailer/language/');
              $mail->From       = "edgar@scgs.com.au";
              $mail->FromName   = "SCGS Newsletter";
              $mail->IsHTML(true);
              $mail->Subject    = "SCGS Newsletter - $title";
             */
            $sql = "select lookup_fields.id, lookup_fields.item_name as field
              from lookup_fields
              left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
              where lookup_answers.foreign_id = $send_id and lookup_answers.table_assoc = 'newsletters'
              ";
            $result2 = $this->dbi->query($sql);
            while ($myrow2 = $result2->fetch_assoc()) {
                $lid = $myrow2['id'];
                $sql = "select users.email, name, CONCAT(name, ' ', surname) as 'users_name' from users 
                left join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                where lookup_answers.lookup_field_id = $lid";
                $result3 = $this->dbi->query($sql);
                while ($myrow3 = $result3->fetch_assoc()) {
                    $email = $myrow3['email'];
                    $name = $myrow3['name'];
                    $name = strtoupper(substr($name, 0, 1)) . strtolower(substr($name, 1));
                    $send_body = str_replace("[name]", $name, $mail_body);
                    $mail->Body = $send_body;
                    $mail->ClearAddresses();
                    $mail->AddAddress($email, $name);
                    if (!$mail->Send()) {
                        $str .= "Mailer Error: " . $mail->ErrorInfo;
                    } else {
                        $str .= "<h3>Message Sent to: $name &lt;$email&gt;</h3>";
                    }
                }
            }
        } else {
            $filter_string = "filter_string";
            $this->list_obj->title = "Newsletters";
            $this->list_obj->sql = "
            select id as idin, date_written as `Date Written`,
            title as `Title`, 
            'Edit' as `*`, 'Delete' as `!`, CONCAT('[<a uk-tooltip=\"title: Preview and Send this Newsletter\" href=\"" . $this->f3->get('main_folder') . "Edit/Newsletters?preview_id=', id, '\">Preview/Send</a>]') as `?`
            FROM newsletters
            where 1 $filter_string
            order by date_written desc
        ";
            $this->editor_obj->table = "newsletters";
            $style = 'style="width: 220px;"';
            $style_large = 'style="width: 440px;"';
            $this->editor_obj->form_attributes = array(
                array("calDateWritten", "txtTitle", "cmsDescription", "chlUserGroups"),
                array("Date Written", "Title", "Description", "User Groups"),
                array("date_written", "title", "description", "id"),
                array("", "", "", $this->get_chl('user_group')),
                array($style, $style_large, 'height: "280",	width: "700"', ""),
                array("c", "c", "", ""),
                array("", "", "", "user_group")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset", "Filter"),
                array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
            );
            $this->editor_obj->form_template = '
                <div class="fl"><nobr>tcalDateWritten</nobr><br />calDateWritten</div>
                <div class="fl"><nobr>ttxtTitle</nobr><br />txtTitle</div>
                <div class="cl"></div>
                tcmsDescription<br />cmsDescription
                <br />
                <div style="max-width: 700px;">tchlUserGroups<br />chlUserGroups</div>
                <div class="cl"></div>
                <br /><br />
                ' . $this->editor_obj->button_list();
            $this->editor_obj->editor_template = '
                  <table border="0">
                  <tr>
                  <td valign="top">
                  <table class="standard_form">
                  <tr><td class="form_header">Newsletters</td>
                  </tr>
                  <tr><td>editor_form</td></tr>
                  </table>
                  </td>
                  <td valign="top">
                  <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">' . $page_content . '</div>
                  </td>
                  </tr>
                  </table>
                  editor_list
      ';
            $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        }
        return $str;
    }

//select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, item_name from $pfix"."lookup_fields where lookup_id in (select id from $pfix"."lookups where item_name = '$lookup_name')) a order by sort_order, item_name  


    function MyLinks() {
        $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
        $move = (isset($_POST["hdnMove"]) ? $_POST["hdnMove"] : null);
        $move_action = (isset($_POST["hdnMoveAction"]) ? $_POST["hdnMoveAction"] : null);

        if (!$_SESSION['u_level'])
            $_SESSION['u_level'] = 10;
        if ($_SESSION['lids'])
            $sql_xtra = "and main_pages2.id in (select foreign_id from lookup_answers where table_assoc = 'main_pages2' and lookup_field_id in (" . implode(",", $_SESSION['lids']) . "))";
        $page_sql = "
            select main_pages2.id as `id`, main_pages2.title as `item_name` from main_pages2
            left join page_types on page_types.id = main_pages2.page_type_id
            where parent_id != 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') and url != '#' and parent_id in (select id from main_pages2
            where page_type_id != 0 and page_type_id != 10000 and parent_id = 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') $sql_xtra) 
            $sql_xtra
           ";
        $in_sql = str_replace(', main_pages2.title as `item_name`', '', $page_sql);

        $page_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all $page_sql) a order by item_name";
        //return $page_sql;

        $str .= '
      <input type="hidden" name="hdnMoveAction" id="hdnMoveAction">
      <input type="hidden" name="hdnMove" id="hdnMove">
      <script>
        function move(id, direction) {
          document.getElementById(\'hdnMoveAction\').value = direction
          document.getElementById(\'hdnMove\').value = id
          document.frmEdit.submit();
        }
      </script>
    ';

        if ($move && $move_action) {
            if ($move_action == "up" || $move_action == "down") {
                $sql = "select sort_order from my_links where id = $move";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $sort_order = $myrow['sort_order'];
                    $id1 = $move;
                }
                $sql = "select id from my_links where sort_order = " . ($sort_order + ($move_action == "up" ? -1 : 1)) . " and user_id = " . $_SESSION['user_id'] . ";";
                if ($result = $this->dbi->query($sql)) {
                    $myrow = $result->fetch_assoc();
                    $id2 = $myrow['id'];
                }
                if ($id1 && $id2) {
                    $this->dbi->query("update my_links set sort_order = sort_order " . ($move_action == "up" ? "-" : "+") . " 1 where id = $id1;");
                    $this->dbi->query("update my_links set sort_order = sort_order " . ($move_action == "up" ? "+" : "-") . " 1 where id = $id2;");
                }
            }
        }

        $this->list_obj->title = "";
        $this->editor_obj->custom_field = "user_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->list_obj->sql = "
    select my_links.id as idin,
    concat(if(my_links.sort_order != 1, concat('<a class=\"list_a\" href=\"JavaScript:move(', my_links.id, ', \'up\')\"><span data-uk-icon=\"icon: triangle-up\"></span></a>'), '')) as `<span data-uk-icon=\"icon: triangle-up\"></span>`,
    concat(if(my_links.sort_order < (select count(id) from my_links where user_id = " . $_SESSION['user_id'] . "),
    concat('<a class=\"list_a\" href=\"JavaScript:move(', my_links.id, ', \'down\')\"><span data-uk-icon=\"icon: triangle-down\"></span></a>'), '')) as `<span data-uk-icon=\"icon: triangle-down\"></span>`,
    
    CONCAT('<a target=\"_top\" href=\"" . $this->f3->get('main_folder') . "', main_pages2.url, '\">', main_pages2.title, '</a>') as `Page`, 'Edit' as `*`, 'Delete' as `!`
    
    from my_links
    left join main_pages2 on main_pages2.id = my_links.page_id
    where my_links.user_id = " . $_SESSION['user_id'] . "
    order by my_links.sort_order";
        //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
        $this->list_obj->show_num_records = false;
        $this->editor_obj->table = "my_links";
        $style = 'style="width: 200px;"';
        $style_large = 'style="width: 400px;"';
        $this->editor_obj->form_attributes = array(
            array("cmbLink"),
            array("Page"),
            array("page_id"),
            array($page_sql),
            array($style),
            array("c"),
            array(""),
            array("")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
            <div class="fl"><nobr>tcmbLink</nobr><br />cmbLink</div>
            <div class="cl"></div><br />
            ' . $this->editor_obj->button_list();
        $this->editor_obj->editor_template = '
              <div class="fl">
              <div class="form-wrapper" style="max-width: 700px;">
              <div class="form-header">My Links</div>
              <div class="form-content">
              editor_form
              </div>
              </div>
              editor_list
              </div>
  ';
        $this->editor_obj->editor_template .= '
              <div class="cl"></div>
  ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        //echo $action . $_REQUEST['idin'];
        if ($action == "add_record") {
            $save_id = $this->editor_obj->last_insert_id;
            $this->dbi->multi_query("set @sort = (select max(sort_order)+1 from my_links where user_id = " . $_SESSION['user_id'] . ");update my_links set sort_order = if(@sort, @sort, 1) where id = $save_id;");
            //echo $this->redirect($_SERVER['REQUEST_URI']);
        } else if ($action == "delete_record") {
            $this->dbi->query("UPDATE my_links e,
                       (SELECT @n := 0) m
                       SET e.sort_order = @n := @n + 1 where user_id = " . $_SESSION['user_id'] . ";");
        }
        if ($action || $move_action) {
            $_SESSION['side_menu'] = NULL;
            $_SESSION['custom_menu'] = NULL;
            echo $this->redirect($_SERVER['REQUEST_URI']);
        }
//    $this->f3->reroute('/Login/?message=Password Reset...');

        return $str;
    }

    function TaskManager($view_mode = 0) {
        $tbl = "my_tasks";
        $complete = (isset($_REQUEST['complete']) ? $_REQUEST['complete'] : null);
        $incomplete = (isset($_REQUEST['incomplete']) ? $_REQUEST['incomplete'] : null);
        $mcomplete = (isset($_REQUEST['mcomplete']) ? $_REQUEST['mcomplete'] : null);
        $mincomplete = (isset($_REQUEST['mincomplete']) ? $_REQUEST['mincomplete'] : null);
        if ($complete || $incomplete) {
            $id = ($complete ? $complete : $incomplete);
            $date_set = ($complete ? "now()" : "'0000-00-00'");
            $sql = "update $tbl set date_completed = $date_set where id = $id";
            $this->dbi->query($sql);
            echo $this->redirect($this->f3->get('main_folder') . ($view_mode ? "Edit/MyTasks" : "Edit/TaskManager"));
        }
        if ($mcomplete || $mincomplete) {
            $id = ($mcomplete ? $mcomplete : $mincomplete);
            $date_set = ($mcomplete ? "now()" : "'0000-00-00'");
            $sql = "update meeting_agenda_action_items set date_completed = $date_set where id = $id";
            $this->dbi->query($sql);
            echo $this->redirect($this->f3->get('main_folder') . ($view_mode ? "Edit/MyTasks" : "Edit/TaskManager"));
        }
        $this->list_obj->num_per_page = 100;
        $this->list_obj->nav_count = 100;
        $this->list_obj->title = ($view_mode == 2 ? "" : ($view_mode ? "My Tasks" : "Task Management"));

        //$this->f3->set('is_mobile', 1);
        if ($this->f3->get('is_mobile') || $this->f3->get('is_tablet')) {
            $this->list_obj->sql = "
      select * from (
      select $tbl.id as `idin`,
      $tbl.item_name as `Action Item`,
      '' as `From Meeting`,
      DATE_FORMAT($tbl.date_due, '%a %d-%b-%Y') as `Date Due`,
      if($tbl.date_completed = '0000-00-00',
        concat('<div style=\"color: ',
          if(DATEDIFF($tbl.date_due, now()) <= 0,
            CONCAT('red\">Due ', if(DATEDIFF($tbl.date_due, now()) = 0,
              CONCAT('Today.'),
              CONCAT(IF(ABS(DATEDIFF($tbl.date_due, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF($tbl.date_due, now())), ' Days Ago'))))
            ),
            if(DATEDIFF($tbl.date_due, now()) <= 28,
              CONCAT('orange\">', DATEDIFF($tbl.date_due, now()), ' Days Remaining'),
              'green\">OK')), '</div>'),
        concat('<div style=\"color: ',
          CONCAT('#000099\">Completed ',
            if(DATEDIFF($tbl.date_completed, now()) = 0,
              CONCAT('Today'),
              CONCAT(IF(ABS(DATEDIFF($tbl.date_completed, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF($tbl.date_completed, now())), ' Days Ago')))
            )
          )
        )
      ) as `Due/Completed`, $tbl.date_completed as `Date Completed`
      , if($tbl.date_completed = '0000-00-00',
        CONCAT('<a class=\"list_a\" href=\"?complete=', $tbl.id, '\">Set to Completed</a>'),
        CONCAT('<a class=\"list_a\" href=\"?incomplete=', $tbl.id, '\">Set to Not Completed</a>')
      ) as `Change To`
      from $tbl
      where 
      $tbl.assigned_to1 = " . $_SESSION['user_id'] . " || $tbl.assigned_to2 = " . $_SESSION['user_id'] . " || $tbl.assigned_to3 = " . $_SESSION['user_id'] . "
      and ($tbl.date_completed = '0000-00-00' or ABS(DATEDIFF($tbl.date_completed, now())) < 7)
      ";
        } else {
            $this->list_obj->sql = "
      " . ($view_mode ? "select * from (" : "") . "
      select $tbl.id as `idin`,

      " . ($view_mode ? "CONCAT(users4.name, ' ', users4.surname) as `Assigned By`, " : "
      
      if(users.id IS NULL and users2.id IS NULL and users3.id IS NULL, 'Not Assigned', 
      CONCAT(
      if(users.id IS NULL, '', CONCAT('[', users.name, ' ', users.surname, '] ')),
      if(users2.id IS NULL, '', CONCAT('[', users2.name, ' ', users2.surname, '] ')),
      if(users3.id IS NULL, '', CONCAT('[', users3.name, ' ', users3.surname, ']'))
      )) as `Assigned To`,
      ") . "
      $tbl.item_name as `Action Item`,
      '' as `From Meeting`,
      DATE_FORMAT($tbl.date_due, '%a %d-%b-%Y') as `Date Due`,
      if($tbl.date_completed = '0000-00-00',
        concat('<div style=\"color: ',
          if(DATEDIFF($tbl.date_due, now()) <= 0,
            CONCAT('red\">Due ', if(DATEDIFF($tbl.date_due, now()) = 0,
              CONCAT('Today.'),
              CONCAT(IF(ABS(DATEDIFF($tbl.date_due, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF($tbl.date_due, now())), ' Days Ago'))))
            ),
            if(DATEDIFF($tbl.date_due, now()) <= 28,
              CONCAT('orange\">', DATEDIFF($tbl.date_due, now()), ' Days Remaining'),
              'green\">OK')), '</div>'),
        concat('<div style=\"color: ',
          CONCAT('#000099\">Completed ',
            if(DATEDIFF($tbl.date_completed, now()) = 0,
              CONCAT('Today'),
              CONCAT(IF(ABS(DATEDIFF($tbl.date_completed, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF($tbl.date_completed, now())), ' Days Ago')))
            )
          )
        )
      ) as `Due/Completed`, $tbl.date_completed as `Date Completed`
      " . ($view_mode ? "" : ", 'Edit' as `*`, 'Delete' as `!` ") . "
      
      " . ($view_mode == 2 ? "" : "
      , if($tbl.date_completed = '0000-00-00',
        CONCAT('<a class=\"list_a\" href=\"?complete=', $tbl.id, '\">Set to Completed</a>'),
        CONCAT('<a class=\"list_a\" href=\"?incomplete=', $tbl.id, '\">Set to Not Completed</a>')
      ) as `Change To`
      
      ") . "
      from $tbl
      left join users on users.id = $tbl.assigned_to1
      left join users2 on users2.id = $tbl.assigned_to2
      left join users3 on users3.id = $tbl.assigned_to3
      left join users4 on users4.id = $tbl.assigned_by_id
      where 
      " . ($view_mode ? "(users.id = " . $_SESSION['user_id'] . " || users2.id = " . $_SESSION['user_id'] . " || users3.id = " . $_SESSION['user_id'] . ")" : "$tbl.assigned_by_id = " . $_SESSION['user_id']) .
                    "
      and ($tbl.date_completed = '0000-00-00' or ABS(DATEDIFF($tbl.date_completed, now())) < 7)
      ";
        }

        if (!$view_mode) {
            $this->list_obj->sql .= " order by `Date Due` ASC, `Date Completed` ASC";
        } else {
//        $this->list_obj->table_class = "grid";
            $this->list_obj->sql .= " UNION ALL 
        select meeting_agenda_action_items.id as `idin`,
      " . ($view_mode ? "if(users4.id IS NOT NULL, CONCAT(users4.name, ' ', users4.surname), '&nbsp;') as `Assigned By`, " : "
        CONCAT(
          if(users.id IS NOT NULL, CONCAT('<nobr>', users.name, ' ', users.surname, '</nobr><br />'), ''),
          if(users2.id IS NOT NULL, CONCAT('<nobr>', users2.name, ' ', users2.surname, '</nobr><br />'), ''),
          if(users3.id IS NOT NULL, CONCAT('<nobr>', users3.name, ' ', users3.surname, '</nobr>'), '')
        ) as `Assigned To`,
      ") . "


        meeting_agenda_action_items.item_name as `Action Item`, 
        CONCAT(
          if(meetings.id IS NOT NULL, meetings.title, ''),
          if(meeting_templates.id IS NOT NULL, meeting_templates.title, '')
        ) as `From Meeting`, 
        DATE_FORMAT(meeting_agenda_action_items.date_due, '%a %d-%b-%Y') as `Date Due`,
        
      if(meeting_agenda_action_items.date_completed = '0000-00-00',
        concat('<div style=\"color: ',
          if(DATEDIFF(meeting_agenda_action_items.date_due, now()) <= 0,
            CONCAT('red\">Due ', if(DATEDIFF(meeting_agenda_action_items.date_due, now()) = 0,
              CONCAT('Today.'),
              CONCAT(IF(ABS(DATEDIFF(meeting_agenda_action_items.date_due, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF(meeting_agenda_action_items.date_due, now())), ' Days Ago'))))
            ),
            if(DATEDIFF(meeting_agenda_action_items.date_due, now()) <= 28,
              CONCAT('orange\">', DATEDIFF(meeting_agenda_action_items.date_due, now()), ' Days Remaining'),
              'green\">OK')), '</div>'),
        concat('<div style=\"color: ',
          CONCAT('#000099\">Completed ',
            if(DATEDIFF(meeting_agenda_action_items.date_completed, now()) = 0,
              CONCAT('Today'),
              CONCAT(IF(ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())) = 1, 'Yesterday', CONCAT(ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())), ' Days Ago')))
            )
          )
        )
      ) as `Due/Completed`, meeting_agenda_action_items.date_completed as `Date Completed`
        
        " . ($view_mode == 2 ? "" : "
          , if(meeting_agenda_action_items.date_completed = '0000-00-00',
            CONCAT('<a class=\"list_a\" href=\"?mcomplete=', meeting_agenda_action_items.id, '\">Set to Completed</a>'),
            CONCAT('<a class=\"list_a\" href=\"?mincomplete=', meeting_agenda_action_items.id, '\">Set to Not Completed</a>')
          ) as `Change To`
        
        ") . "


        from meeting_agenda_action_items
        inner join meeting_agenda_items on meeting_agenda_items.id = meeting_agenda_action_items.meeting_agenda_id
        left join meetings on meetings.id = meeting_agenda_items.meeting_id
        left join meeting_templates on meeting_templates.id = meeting_agenda_items.meeting_template_id
        left join users on users.id = meeting_agenda_action_items.assigned_to1
        left join users2 on users2.id = meeting_agenda_action_items.assigned_to2
        left join users3 on users3.id = meeting_agenda_action_items.assigned_to3
        left join users4 on users4.id = meeting_agenda_items.user_id
        where " . ($manager ? "users4.id=" . $_SESSION['user_id'] : "(meeting_agenda_action_items.assigned_to1 = " . $_SESSION['user_id'] . " or meeting_agenda_action_items.assigned_to2 = " . $_SESSION['user_id'] . " or meeting_agenda_action_items.assigned_to3 = " . $_SESSION['user_id'] . ")") . " 
        and (meeting_agenda_action_items.date_completed = '0000-00-00' or ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())) < 7)
        ) a order by `Date Due` ASC, `Date Completed` ASC";
        }

        //and (ABS(DATEDIFF(meeting_agenda_action_items.date_completed, now())) <= 14 or meeting_agenda_action_items.date_completed = '0000-00-00') 
//      order by meeting_agenda_action_items.date_completed, meeting_agenda_action_items.date_due
        // return "<textarea>{$this->list_obj->sql}</textarea>";
        /* $chk_obj = new input_item;
          $checks .= $chk_obj->chk("chkPending", "Show Pending", "checked", "chkPending", '', '');
          $checks .= $chk_obj->chk("chkCompleted", "Show Completed", "", "chkCompleted", '', ''); */

        if (!$view_mode) {
            $this->editor_obj->table = $tbl;
            $this->editor_obj->xtra_id_name = "assigned_by_id";
            $this->editor_obj->xtra_id = $_SESSION['user_id'];
            $style = 'style="width: 100%;"';
            $style_small = 'style="width: 120px;"';
            $tmp_sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users' order by item_name;";
            $this->editor_obj->form_attributes = array(
                array("cmbAssignedTo1", "cmbAssignedTo2", "cmbAssignedTo3", "txtItemName", "calDateDue", "calDateCompleted"),
                array("Assigned To", "Assigned To", "Assigned To", "Action Item", "Date Due", "Date Completed"),
                array("assigned_to1", "assigned_to2", "assigned_to3", "item_name", "date_due", "date_completed"),
                array($tmp_sql, $tmp_sql, $tmp_sql, "", "", ""),
                array("", "", "", $style, $style_small, $style_small),
                array("n", "n", "n", "c", "c", "n")
            );
            $this->editor_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );
            $this->editor_obj->form_template = '
          <div class="fl"><nobr>tcmbAssignedTo1</nobr><br />cmbAssignedTo1</div>
          <div class="fl"><nobr>tcmbAssignedTo2</nobr><br />cmbAssignedTo2</div>
          <div class="fl"><nobr>tcmbAssignedTo3</nobr><br />cmbAssignedTo3</div>
          <div class="fl"><nobr>tcalDateDue</nobr><br />calDateDue</div>
          <div class="fl"><nobr>tcalDateCompleted</nobr><br />calDateCompleted</div>          
          <div class="cl"></div>
          ttxtItemName<br />txtItemName
          <div class="cl"></div>
          <br /><div class="fl">' . $this->editor_obj->button_list() . '</div>
          <div class="cl"></div>';
            // <div class="fl"><b class="fl">&nbsp; &nbsp; &nbsp;On Filter: </b>' . $checks . '</div>
            $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
        }
        $str .= ($view_mode ? $this->list_obj->draw_list() : $this->editor_obj->draw_data_editor($this->list_obj));
        return $str;
    }

    function MyTasks($view_mode = 1) {

        return $this->TaskManager($view_mode);

        if ($_SESSION['lids'])
            $sql_xtra = "and main_pages2.id in (select foreign_id from lookup_answers where table_assoc = 'main_pages2' and lookup_field_id in (" . implode(",", $_SESSION['lids']) . "))";
        $page_sql = "
            select main_pages2.id as `id`, main_pages2.title as `item_name` from main_pages2
            left join page_types on page_types.id = main_pages2.page_type_id
            where parent_id != 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') and url != '#' and parent_id in (select id from main_pages2
            where page_type_id != 0 and page_type_id != 10000 and parent_id = 0 and user_level_id <= " . $_SESSION['u_level'] . " and max_user_level_id >= " . $_SESSION['u_level'] . " and (page_types.item_name = 'SIDE') $sql_xtra) 
            $sql_xtra
           ";
        $in_sql = str_replace(', main_pages2.title as `item_name`', '', $page_sql);
    }

    //schedule_frequency

    function ReportEmails() {
        $this->editor_obj->table = "compliance_email_to";
        $style = 'style="width: 100%;"';
        $style_small = 'style="width: 120px;"';

        $report_sql = "select * from (select 0 as id, '--- Select ---' as item_name) as a union all select * from (select id, title as `item_name` from compliance where title NOT LIKE '%MAIN TEMPLATE%' order by item_name) as b";


        $staff_sql = $this->user_dropdown(107, 504, 0, 0, 0, 1, 1);
        $site_sql = $this->user_dropdown(384, 107, 530, 0, 0, 1, 1);

        $this->list_obj->sql = "
      select compliance_email_to.id as `idin`, compliance.title as `Report`, CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Staff Member`, CONCAT(users2.name, ' ', users2.surname) as `Subject`, 'Edit' as `*`, 'Delete' as `!`
      from compliance_email_to
      left join compliance on compliance.id = compliance_email_to.compliance_id
      left join users on users.id = compliance_email_to.staff_id
      left join users2 on users2.id = compliance_email_to.site_id
      order by compliance.title
      ";

        $this->editor_obj->form_attributes = array(
            array("selReport", "cmbStaff", "cmbSite"),
            array("Report", "Staff Member to Email", "Performed On (Optional)"),
            array("compliance_id", "staff_id", "site_id"),
            array($report_sql, $staff_sql, $site_sql),
            array("", "", ""),
            array("n", "c", "n")
        );
        $this->editor_obj->button_attributes = array(
            array("Add New", "Save", "Reset"),
            array("cmdAdd", "cmdSave", "cmdReset"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
          <div class="fl"><nobr>tselReport</nobr><br />selReport</div>
          <div class="fl"><nobr>tcmbStaff</nobr><br />cmbStaff</div>
          <div class="fl"><nobr>tcmbSite</nobr><br />cmbSite</div>
          <div class="cl"></div>
          <br /><div class="fl">' . $this->editor_obj->button_list() . '</div>
          <div class="cl"></div>';
        // <div class="fl"><b class="fl">&nbsp; &nbsp; &nbsp;On Filter: </b>' . $checks . '</div>
        $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';

        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
        return $str;
    }

    function alliedDivision($divisionId) {


        $form_obj = new input_form;
        $form_obj->table = "users";
        $form_obj->dbi = $this->dbi;

        $divisionIdArray = explode(',', $divisionId);
        $group[0] = 0;
        $group[1] = 0;
        $group[2] = 0;
        $group[3] = 0;
        $group[4] = 0;

        foreach ($divisionIdArray as $key => $value) {

            $group[$key + 1] = $value;
        }
        if ($group[2] == 0) {
            $group[0] = 0;
        } else {
            $group[0] = 384;
        }

        //$divisionId  = implode(",",$divisionIdArray);
        // if($divisionId)

        $site_sql = $this->user_select_dropdown($group[0], $group[1], $group[2], $group[3], $group[4]);
        $styleMulti = 'style="width: 500px;height: 150px !important;"';
        $formAttributeArr[] = array("mslParentSite", "Main Location", "parent_site", $site_sql, "multiple " . $styleMulti, "n", "");
        $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
        //print_r($resultV); die;
        $form_obj->form_attributes = $resultV;


        $form_obj->form_template = '
               <nobr>tmslParentSite</nobr><span class="mslParentSign" style="display:none;color:green">
            <i class="fa fa-check" aria-hidden="true"></i>
       </span><br />mslParentSite<br />
      ';


        $str = $form_obj->display_attribute() . "<br />";


        return $str;
    }

}

?>
