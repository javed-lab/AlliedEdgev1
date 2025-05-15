<?php

class AssetController extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
    $div_id = $_COOKIE["AssetDivisionId"];
    $this->division_id = (isset($_GET['division_id']) ? $_GET['division_id'] : ($div_id ? $div_id : 0));
    if($this->division_id) setcookie("AssetDivisionId", $this->division_id, 2147483647);
  }

  function allocated_uniforms($uid="") {
    $uid = ($uid ? $uid : (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null));

    $this->list_obj->title = "Allocated Uniforms";

    $this->list_obj->sql = "select asset_allocation.id as `idin`,
    asset_allocation.date_issued as `Date Issued`,
    if(make_id != 0, lookup_fields.item_name, '') as `Make`,
    asset_allocation.item_name as `Item`,
    size as `Size`,
    if(return_date = '0000-00-00', CONCAT('<a class=\"list_a\" href=\"JavaScript:return_item(', asset_allocation.id, ')\">', if(date_issued = date(now()), 'Undo Allocation', 'Return'), '</a>'), DATE_FORMAT(return_date, '%d/%b/%Y')) as `Returns`
    from asset_allocation
    left join lookup_fields on lookup_fields.id = asset_allocation.make_id
    where asset_allocation.issued_to_id = $uid
    order by return_date ASC, date_issued DESC
    ";
    //$str .= $this->ta($this->list_obj->sql);
    $str .= $this->list_obj->draw_list();
    
    return $str;
  }

  //' . $this->f3->get('main_folder') . "Assets/AllocationSummary
  
  function Allocated($uid=0) {
    $uid = ($uid ? $uid : (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null));

    $str .= $this->allocated_uniforms($uid);

    $this->list_obj->title = "Allocated Assets";
//    CONCAT(users.name, ' ', users.surname, ' (', users.employee_id, ')') as `Issued By`,
//    CONCAT(users2.name, ' ', users2.surname, ' (', users2.employee_id, ')') as `Issued To`,
//    left join users on users.id = asset_allocation.issued_by_id
//    left join users2 on users2.id = asset_allocation.issued_to_id

//            CONCAT(users3.name, ' ', users3.surname) as `Assigned To`,

    $this->list_obj->sql = "
            select assets.id as idin, 
            lookup_fields1.item_name as `Category`,
            assets.description as `Item Name`,
            lookup_fields2.item_name as `Make`,
            assets.model as `Model`,
            CONCAT(users.name, ' ', users.surname) as `Location`,
            CONCAT('<a id=\"', assets.id, '\"></a>', assets.label_no) as `Label ID`,
            assets.notes as `Notes`
            FROM assets
            left join lookup_fields1 on lookup_fields1.id = assets.category_id
            left join lookup_fields2 on lookup_fields2.id = assets.make_id
            left join users on users.id = assets.location_id
            where assets.assigned_to_id = $uid
    ";
    
    $str .= $this->list_obj->draw_list();
    
    return $str;
  }
  
  
  function UniformAllocation() {
    $division_id = $this->division_id;
    $qty_id = (isset($_GET['qty_id']) ? $_GET['qty_id'] : null);
    $user_id = (isset($_GET['user_id']) ? $_GET['user_id'] : null);
    $return_id = (isset($_GET['return_id']) ? $_GET['return_id'] : null);

      //echo "<h3>qty_id: $qty_id, user_id: $user_id</h3>";

    if($qty_id || $return_id || $user_id) {
      if($qty_id) {
        $uid = (isset($_GET['uid']) ? $_GET['uid'] : null);
        $qty = (isset($_GET['qty']) ? $_GET['qty'] : 0);
        if($qty) {
          for($x == 0; $x < $qty; $x++) {
            $sql = "insert into asset_allocation (issued_by_id, issued_to_id, asset_id, date_issued, item_name, size, price, make_id) select '{$_SESSION['user_id']}', '$uid', '$qty_id', now(), description, size, purchase_price, make_id from assets where id = '$qty_id';";
            $this->dbi->query($sql);
            $sql = "update assets set quantity = quantity - 1 where id = '$qty_id';";
            $this->dbi->query($sql);
          }
        }
        echo $this->allocated_uniforms($uid);
      } else if($return_id) {
        $test = $this->get_sql_result("select if(date_issued = date(now()), 'del', '') as `result` from asset_allocation where id = $return_id");
        $sql = ($test == 'del' ? "delete from asset_allocation" : "update asset_allocation set return_date = now()") . " where id = $return_id;";
        $this->dbi->query($sql);
        $sql = "update assets set quantity = quantity + 1 where id = (select asset_id from asset_allocation where id = $return_id);";
        $this->dbi->query($sql);
        echo ($test == 'del' ? "Allocation Deleted and Re-stocked" : $this->get_sql_result("select date_format(now(), '%d/%b/%Y') as `result`"));
      } else if($user_id) {
        echo $this->Stocktake($user_id);
        echo '<div id="allocation_msg">'.$this->allocated_uniforms($user_id).'</div>';
      }
      exit;
    }
    
    
    
    $str = '
    <script>
    function add_subtract(id, uid, amt) {
      itm = document.getElementById("txtQty" + id).value
      if(itm == "") itm = 0
      itm = parseInt(itm)
      itm += amt
      document.getElementById("txtQty" + id).value = itm
      
      
    }
    function save_qty(id, uid) {
      qty = document.getElementById("txtQty" + id).value
      if(qty) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Assets/UniformAllocation",
              data:{ qty_id: id, qty: qty, uid: uid } ,
              success:function(msg) {
                document.getElementById("allocation_msg").innerHTML = msg
              }
        } );
      }
    }
    function return_item(id) {
      if(id) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Assets/UniformAllocation",
              data:{ return_id: id } ,
              success:function(msg) {
                document.getElementById(id + "-" + 5).innerHTML = msg
              }
        } );
      }
    }
    function select_user() {
      user_id = document.getElementById("hdncmbStaffSelect").value
      if(user_id) {
        $.ajax({
          type:"get",
              url:"'.$this->f3->get('main_folder').'Assets/UniformAllocation",
              data:{ user_id: user_id } ,
              success:function(msg) {
                document.getElementById("uniselect").innerHTML = msg
              }
        } );
      }
    }
    </script>
    ';
    $itm = new input_item;
    $itm->hide_filter = 1;
    $division_str = $this->show_divisions("UniformAllocation");
    
    $id_user = ($division_id == 'ALL' ? 0 : $this->get_sql_result("select id as `result` from companies where item_name LIKE '{$division_id}%'"));
    $itm->dbl_xtra = "document.getElementById('uniselect').innerHTML = '';";
    $str .= "
    <h3 class=\"fl\" style=\"padding-right: 20px;\">Uniform Allocation - $division_id</h3><div class=\"fl\">".$itm->cmb("cmbStaffSelect", "", "placeholder=\"Select a Staff Member\" onChange=\"select_user()\"  ", "", $this->dbi, $this->user_dropdown(107, $id_user), "")."</div><div class=\"fr\">$division_str</div><div class=\"cl\"></div>
    
    ";


    $str .= '
    <div class="cl"></div>
    <div id="uniselect"></div>
    
    ';
    //$str .= ;
    
    
    return $str;
  }
  
  function show_divisions($page) {
    $division_id = $this->division_id;
    $sql = "select 'ALL' as id, 'ALL' as item_name UNION select id, item_name from companies";
    if($result = $this->dbi->query($sql)) {
      while($myrow = $result->fetch_assoc()) {
        $division_name = $myrow['item_name'];
        $div_idin = strtoupper(substr($division_name, 0, 3));
        
        if(!$division_id) $division_id = $div_idin;
        if($div_idin == $division_id) $division_show = $division_name;
        $division_str .= '<a class="division_nav_item '.($div_idin == $division_id ? "division_nav_selected" : "").'" href="'.$this->f3->get('main_folder').'Assets/'.$page.'?division_id='.$div_idin.'">' . $division_name . '</a>';
      }
    }
    return $division_str;
  }
  
  function Stocktake($issue_id = 0) {
    $division_id = $this->division_id;

    $qty_id = (isset($_GET['qty_id']) ? $_GET['qty_id'] : null);
    $uid = (isset($_GET['uid']) ? $_GET['uid'] : null);
    
    if($qty_id) {
      $qty = (isset($_GET['qty']) ? $_GET['qty'] : null);
      $sql = "update assets set quantity = '$qty' where id = '$qty_id'";
      $this->dbi->query($sql);
      exit;
    }
    
    $itm = new input_item;
    $itm->hide_filter = 1;
    
    $num_sizes = 0;
    
    
    if($issue_id) {
      $str .= "<h3>Select Uniform Items Below</h3>";
    } else {
      $str .= "<h3 class=\"fl\">Uniform Stocktake - $division_id</h3>";
      $str .= '<div class="fr">' . $this->show_divisions("Stocktake") . '</div><div class="cl"></div>';
    }
    
    $str .= "
    <div id=\"msg\"></div>
    
    <style>
    table.grid {
      border-width: 1px;
      border-spacing: 0px;
      border-style: outset;
      border-color: #DDDDDD;
      border-collapse: collapse;
    }
    table.grid td input[type=text] {
      border: none !important;
      width: 40px;
      height: 25px !important;
    }
    table.grid td input[type=text]:hover {
      background-color: #FFFFCC !important;
    }
    
    
    table.grid th {
      border-width: 1px;
      padding: 4px;
      border-style: solid;
      border-color: #DDDDDD;
      background-color: white;
      font-weight: bold;
      text-align: left !important;
      vertical-align: bottom; 
    }
    table.grid td {
      border-width: 1px;
      padding: 1px;
      border-style: solid;
      border-color: #DDDDDD;
    }

    table.grid tr:hover, table.grid tr:nth-child(even):hover, table.grid tr:nth-child(odd):hover {
      background-color: #F0FFF5;
      
    }

    table.grid tr:nth-child(even) {background-color: #F9F9F9;}
    table.grid tr:nth-child(odd) {background-color: #FFFFFF;}

    .stock_item {
      font-size: 11pt;
      padding-left: 4px !important;
      padding-right: 4px !important;
    }
    </style>";
    $str .= '
    <script>
      function save_qty(id, uid) {
        qty = document.getElementById("txtQty" + id).value
        if(qty) {
          $.ajax({
            type:"get",
                url:"'.$this->f3->get('main_folder').'Assets/Stocktake",
                data:{ qty_id: id, qty: qty, uid: uid } ,
                success:function(msg) {
                  document.getElementById("msg").innerHTML = msg
                }
          } );
        }
      }
    </script>
    ';
    
    $sql = "select distinct(size) as `sz` from assets where category_id = 2233 and size != ''" . ($division_id != 'ALL' ? " and assets.description LIKE '%$division_id%' " : "");
    if($result = $this->dbi->query($sql)) {
      while($myrow = $result->fetch_assoc()) {
        $sz = $myrow['sz'];
        $size_arr[$sz] = $sz;
        if($sz == 'Small' || $sz == 'Medium' || $sz == 'Large') $sz = substr($sz, 0, 1);
        $sizes .= '<th>' . $sz . '</th>';
        $num_sizes++;
      }
    }
    
    $aid = 2233;
    
    $sql = "
    select assets.make_id, assets.id, lookup_fields.item_name as `make`, assets.description, assets.quantity, assets.reorder_level, assets.size, assets.notes
    FROM assets
    left join lookup_fields on lookup_fields.id = assets.make_id
    where assets.category_id = $aid
    " . ($division_id != 'ALL' ? " and assets.description LIKE '%$division_id%' " : "") . "
    group by assets.make_id, assets.description
    order by lookup_fields.item_name, assets.description;
    ";
    $str .= '<table class="grid"><tr><th>Make</th><th>Item</th>' . $sizes . '</tr>';
    
    if($result = $this->dbi->query($sql)) {
      $ids = array();  $sizes = array();  $quantities = array();
      while($myrow = $result->fetch_assoc()) {
        $id = $myrow['id'];
        $make = $myrow['make'];
        $make_id = $myrow['make_id'];
        $description = $myrow['description'];
        $description_show = ($division_id == 'ALL' ? $description : substr($description, 6));
        //$name_desc = preg_replace('/[^\w]/', '', $description); //How to replace all characters that are not a letter or number
        $quantity = $myrow['quantity'];
        $reorder_level = $myrow['reorder_level'];
        $size = $myrow['size'];
        $notes = $myrow['notes'];

        $new_item = $make . $description;
        
        $str .= "<tr><td class=\"stock_item\">$make</td><td class=\"stock_item\">$description_show</td>";
        
        $sql = "select id, size, quantity from assets where description = '$description' and make_id = '$make_id'";
        $size_tmp = "";
        if($result2 = $this->dbi->query($sql)) {
          unset($ids);          unset($sizes);          unset($quantities);
          while($myrow2 = $result2->fetch_assoc()) {
            $ids[] = $myrow2['id'];  $sizes[] = $myrow2['size'];  $quantities[] = $myrow2['quantity'];
          }
          $x = 0;
          foreach($size_arr as $sz) {
            $str .= "<td>";
            if(array_search($sz, $sizes) !== false) {
              $str .= $itm->txt("txtQty{$ids[$x]}", ($issue_id ? "" : $quantities[$x]), ($issue_id ? " onClick=\"add_subtract('{$ids[$x]}', $issue_id, 1)\"" : "") . " placeholder=\"".($issue_id ? $quantities[$x] : "0")."\" onBlur=\"save_qty('{$ids[$x]}', $issue_id);\" ", "", "", "");
              $x++;
            } else {
              $str .= "&nbsp;";
            }
            $str .= "</td>";
          }
          $str .= "</tr>";
        }
      }
    }
    $str .= '</table>';
    return $str;
  }
  
  function Manager() {
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);

    $edit_categories = (isset($_GET['edit_categories']) ? $_GET['edit_categories'] : null);
    $edit_makes = (isset($_GET['edit_makes']) ? $_GET['edit_makes'] : null);
    
    if($edit_makes) {
      $e = new EditController($this->f3);
      return $e->LookupItems(128, "Asset Makes", "", 1);
    }
    if($edit_categories) {
      $e = new EditController($this->f3);
      return $e->LookupItems(26, "Asset Categories", "", 1);
    }
    

    if($download_xl) {
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
            assets.parent_label_no as `Part Of`,
            assets.serial_number as `Serial Number`,
            if(assets.quantity = 0, '', assets.quantity) as `Quantity`,
            if(assets.reorder_level = 0, '', assets.quantity) as `Reorder`,
            assets.size as `Size`,
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
      $objPHPExcel->getActiveSheet()->freezePane('A2');
      
      for($i = 2; $i <= 500; $i++) {
        $objValidation = $objPHPExcel->getActiveSheet()->getCell("B$i")->getDataValidation();
        $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
        $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
        $objValidation->setShowInputMessage(true);    $objValidation->setShowErrorMessage(true);    $objValidation->setShowDropDown(true);
        $objValidation->setErrorTitle('Input Error');
        $objValidation->setError('Value is not in list.');
        $objValidation->setPromptTitle('Categories');
        $objValidation->setPrompt('Choose a Category from the Dropdown List');
        $items = $this->lookup_list('asset_categories');
        $objValidation->setFormula1('"' . $items . '"');

        $objValidation = $objPHPExcel->getActiveSheet()->getCell("D$i")->getDataValidation();
        $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
        $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
        $objValidation->setShowInputMessage(true);    $objValidation->setShowErrorMessage(true);    $objValidation->setShowDropDown(true);
        $objValidation->setErrorTitle('Input Error');
        $objValidation->setError('Value is not in list.');
        $objValidation->setPromptTitle('Makes');
        $objValidation->setPrompt('Choose a Make from the Dropdown List');
        $items = $this->lookup_list('asset_make');
        $objValidation->setFormula1('"' . $items . '"');
      }
      $xl_obj->sql_xl('assets.xlsx', $objPHPExcel);
      
      
    } else {
      
      if($sid) {
        $filter_string = " and assets.location_id = $sid";
      } else {
        $site_sql = $this->user_dropdown(384);
        $order_by = $_POST['optOrderBy'];
        if($order_by == "Location") {
          $loc_xtra = "checked";
          $cat_xtra = "";
          $sort_xtra = " order by users2.name ";
        } elseif ($order_by == "Category") {
          $loc_xtra = "";
          $cat_xtra = "checked";
          $sort_xtra = " order by lookup_fields1.item_name ";
        }
        $str .= '<div class="form-wrapper"><div class="form-header" style="height: 30px;"><h3 class="fl">Asset Manager</h3>';
        $input_obj = new input_item;
        $input_obj->hide_filter = 1;        
        
        
        $str .= "<div style=\"padding-left: 50px;\" class=\"fl\"><a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Assets/Manager?download_xl=1\">Download Excel</a> <a class=\"list_a\" href=\"".$this->f3->get('main_folder')."Assets/Upload\">Upload Excel</a></div><div class=\"fl\" style=\"padding-left: 50px; display: inline;\"><b>Order By: </b></div><div class=\"fl\">" . $input_obj->opt("optCategories|optOrderBy", 'Category', $cat_xtra, '', '', '') . " " . $input_obj->opt("optLocations|optOrderBy", 'Location', $loc_xtra, '', '', '') . "</div>";
        $str .= '</div><div class="cl"></div>';
        $filter_string = "filter_string";
      }
      $this->list_obj->title = "Assets" . ($sid ? " at " . $this->get_sql_result("select CONCAT(name, ' ', surname) as `result` from users where id = $sid") : "");
      $this->list_obj->show_num_records = 1;
      $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;
      $this->list_obj->nav_count = 20;
      $this->list_obj->sql = "
            select assets.id as idin, 
            " . ($sid ? "" : "'Edit' as `*`, 'Delete' as `!`,") . "
            lookup_fields1.item_name as `Asset Category`,
            companies.item_name as `Division Name`,
            training_allied_frequency.title as `Service Frequency`,
            assets.description as `Name of Item`,
            lookup_fields2.item_name as `Make`,
            assets.model as `Model`,
            " . ($sid ? "" : "CONCAT(users2.name, ' ', users2.surname) as `Location`,") . "
            CONCAT(users3.name, ' ', users3.surname) as `Assigned To`,
            CONCAT('<a id=\"', assets.id, '\"></a>', assets.label_no) as `Label ID`,
            assets.parent_label_no as `Part Of`,
            assets.serial_number as `Serial Number`,
            if(assets.quantity = 0, '', assets.quantity) as `Qty`,
            if(assets.reorder_level = 0, '', assets.reorder_level) as `Reorder`,
            assets.size as `Size`,
            if(assets.purchase_price > 0, CONCAT('$', FORMAT(assets.purchase_price, 2)), '-') as `$`, assets.purchase_date as `Purchase Date`, assets.notes as `Notes`,
            CONCAT(DATE_FORMAT(assets.updated_date, '%d-%b-%Y %H:%i'), '<br/>', users.name, ' ', users.surname) as `Updated On/By`
            FROM assets
            left join lookup_fields1 on lookup_fields1.id = assets.category_id
            left join companies on companies.id = assets.division_id
            left join training_allied_frequency on training_allied_frequency.id = assets.frequency_id
            left join lookup_fields2 on lookup_fields2.id = assets.make_id
            left join users on users.id = assets.updated_by
            " . ($sid ? "" : "left join users2 on users2.id = assets.location_id") . "
            left join users3 on users3.id = assets.assigned_to_id
            where 1 $filter_string
            $sort_xtra
        ";
//      echo $this->list_obj->sql;
//      die;
        //$str .= "<h3>{$this->list_obj->sql}</h3>";
      if($sid) {
        $str .= $this->list_obj->draw_list();
      } else {
        $this->editor_obj->add_now = "updated_date";
        $this->editor_obj->update_now = "updated_date";
        $this->editor_obj->custom_field = "updated_by";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "assets";
        $style = 'class="full_width"';
        $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"
        $division_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all select id, item_name from companies order by item_name) as abc";
        $frequency_sql = "select * from ((select 0 as id, '--- Select ---' as item_name) union all (select id, title as item_name from training_allied_frequency order by training_allied_frequency.frequency_number)) as abc";
        //$trainingAlliedFrequencyList = "SELECT id,title FROM training_allied_frequency ORDER BY title ASC"; 
            
            //$this->f3->set('trainingAlliedFrequencyList', $trainingAlliedFrequencyList);
        
        //$location_sql = "select * from (select 0 as id, '--- Select ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' where users.name NOT LIKE '%ADHOC%') as a order by item_name";
        $this->editor_obj->form_attributes = array(
                 array("selCategory", "txtDescription", "cmbLocation", "txtLabelNo", "txtParentLabelNo", "txtPurchasePrice", "calPurchaseDate", "txtNotes", "cmbMake", "txtSerialNumber", "txtQuantity", "txtReorderLevel", "txtSize", "cmbAssignedTo", "txtModel","selDivisionId","selFrequency"),
                 array("Category", "Name of Item", "Location", "Label ID", "Part Of (Lbl ID)", "Purchase Price", "Purchase Date", "Asset Notes", "Make", "Serial No.", "Qty (Whole Nums)", "Reorder Qty", "Size", "Assigned To", "Model","Division id","Service Frequency"),
                 array("category_id", "description", "location_id", "label_no", "parent_label_no", "purchase_price", "purchase_date", "notes", "make_id", "serial_number", "quantity", "reorder_level", "size", "assigned_to_id", "model","division_id","frequency_id"),
                 array($this->get_lookup('asset_categories'), "", $site_sql, "", "", "", "", "", $this->get_cmb_lookup('asset_make'), "", "", "", "", $this->user_dropdown(107,108),"",$division_sql,$frequency_sql),
                 array($style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style,$style,$style),
                 array("c", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n","n","n")
        );
        $this->editor_obj->button_attributes = array(
          array("Add New", "Save", "Reset", "Filter"),
          array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
          array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        
        
        $this->editor_obj->form_template = '
                  <div class="fl small_textbox"><nobr>tselCategory [<a tabindex="-1" target="_blank" href="'.$this->f3->get('main_folder').'Assets/Manager?edit_categories=1">Edit</a>]</nobr><br />selCategory</div>
                  <div class="fl small_textbox"><nobr>tselDivisionId <br />selDivisionId</div>
                  <div class="fl small_textbox"><nobr>tselFrequency <br />selFrequency</div>
                  <div class="fl large_textbox"><nobr>ttxtDescription</nobr><br />txtDescription</div>
                  <div class="fl small_textbox"><nobr>tcmbMake [<a tabindex="-1" target="_blank" href="'.$this->f3->get('main_folder').'Assets/Manager?edit_makes=1">Edit</a>]</nobr><br />cmbMake</div>
                  <div class="fl small_textbox"><nobr>ttxtModel</nobr><br />txtModel</div>
                  <div class="fl med_textbox"><nobr>tcmbLocation</nobr><br />cmbLocation</div>
                  <div class="fl med_textbox"><nobr>tcmbAssignedTo</nobr><br />cmbAssignedTo</div>
                  <div class="fl small_textbox"><nobr>ttxtLabelNo</nobr><br />txtLabelNo</div>
                  <div class="fl small_textbox"><nobr>ttxtParentLabelNo</nobr><br />txtParentLabelNo</div>
                  <div class="fl med_textbox"><nobr>ttxtSerialNumber</nobr><br />txtSerialNumber</div>
                  <div class="fl small_textbox"><nobr>ttxtQuantity</nobr><br />txtQuantity</div>
                  <div class="fl small_textbox"><nobr>ttxtReorderLevel</nobr><br />txtReorderLevel</div>
                  <div class="fl small_textbox"><nobr>ttxtSize</nobr><br />txtSize</div>
                  <div class="fl small_textbox"><nobr>ttxtPurchasePrice</nobr><br />txtPurchasePrice</div>
                  <div class="fl small_textbox"><nobr>tcalPurchaseDate</nobr><br />calPurchaseDate</div>
                  <div class="fl large_textbox"><nobr>ttxtNotes</nobr><br />txtNotes</div>
                  <div class="cl"></div>
                  <br />
                  <div class="cl"></div>
                  '.$this->editor_obj->button_list();
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
  }
 
  function pstr($dbi, $str) {
    return trim(mysqli_real_escape_string($dbi, $str));
  }
  function Upload() {
    require_once('app/controllers/PHPExcel.php');

    //128, "Asset Makes"
    //26, "Asset Categories"
    
    
    $str .= '
    <div style="padding-left: 10px; padding-right: 10px; padding-top: 0px; padding-bottom: 0px;">
    '.$page_content.'
    </div>
    </form>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
    <label for="thefile">Filename:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
    </form>';
    
    if ($_FILES["thefile"]["error"] > 0) {
    	$str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
    } else if($_FILES["thefile"]["name"]) {
      move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . $_FILES["thefile"]["name"]);
      //$inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
      $inputFileName = $this->f3->get('upload_folder') . $_FILES["thefile"]["name"];
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
      $highest_row = $sheet->getHighestRow();
      $highestColumn = $sheet->getHighestColumn();
      $num_updated = 0; $num_added = 0; $num_processed = 0; $num_deleted = 0;
      $sql = "truncate assets;";
      $this->dbi->query($sql);
      for ($row = 2; $row <= $highest_row; $row++) {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        $id = $this->pstr($this->dbi, $rowData[0][0]);
        $description = $this->pstr($this->dbi, $rowData[0][2]);
        $label_no = $this->pstr($this->dbi, $rowData[0][9]);
        $asset_category = $this->pstr($this->dbi, $rowData[0][1]);
        $asset_make = $this->pstr($this->dbi, $rowData[0][3]);
        $model = $this->pstr($this->dbi, $rowData[0][4]);
        $staff_id = $this->pstr($this->dbi, $rowData[0][5]);
        $client_id = $this->pstr($this->dbi, $rowData[0][7]);
        $quantity = $this->pstr($this->dbi, $rowData[0][12]);
        $reorder_level = $this->pstr($this->dbi, $rowData[0][13]);
        $size = $this->pstr($this->dbi, $rowData[0][14]);
        $parent_label_no = $this->pstr($this->dbi, $rowData[0][10]);
        $serial_number = $this->pstr($this->dbi, $rowData[0][11]);
        $purchase_price = str_replace(',', '', $rowData[0][15]);
        $purchase_date = $rowData[0][15];
        $purchase_date = ($purchase_date ? (is_numeric($purchase_date) ? gmdate("Y-m-d", (($purchase_date - 25569) * 86400)) : $purchase_date): "");
        $notes = $this->pstr($this->dbi, $rowData[0][16]);
        $division = $this->pstr($this->dbi, $rowData[0][17]);
        $division_id_in = ($division ? $this->get_sql_result("select id as `result` from companies where item_name = '$division';") : 0);
        $category_id = ($asset_category ? $this->get_sql_result("select id as `result` from lookup_fields where lookup_id = 26 and item_name = '$asset_category';") : 0);
        $make_id = ($asset_make ? $this->get_sql_result("select id as `result` from lookup_fields where lookup_id = 128 and item_name = '$asset_make';") : 0);
        $location_id = ($client_id ? $this->get_sql_result("select id as `result` from users where client_id = '$client_id';") : 0);
        $assigned_to_id = ($staff_id ? $this->get_sql_result("select id as `result` from users where employee_id = '$staff_id';") : 0);
        $num_processed++;
        /*if($id) {
          if($description || $label_no) {
            $sql = "update assets set category_id = '$category_id', description = '$description', location_id = '$location_id', label_no = '$label_no', parent_label_no = '$parent_label_no', purchase_price = '$purchase_price', purchase_date = '$purchase_date', notes = '$notes', make_id = '$make_id', serial_number = '$serial_number', quantity = '$quantity', reorder_level = '$reorder_level', size = '$size', assigned_to_id = '$assigned_to_id', model = '$model', division_id = $division_id_in where id = $id;";
          } else {
            $sql = "delete from assets where id = $id;";
            $num_deleted += 1;
          }
        } else {*/
          if($description || $label_no) {
            $sql = "insert into assets(updated_by, updated_date, category_id, description, location_id, label_no, parent_label_no, purchase_price, purchase_date, notes, make_id, serial_number, quantity, reorder_level, `size`, assigned_to_id, model, division_id) values ({$_SESSION['user_id']}, '" . Date('Y-m-d H:i') . "', '$category_id', '$description', '$location_id', '$label_no', '$parent_label_no', '$purchase_price', '$purchase_date', '$notes', '$make_id', '$serial_number', '$quantity', '$reorder_level', '$size', '$assigned_to_id', '$model', $division_id_in);";
          //$str .= "<h3>$sql</h3>";
          }
        //}

        $this->dbi->query($sql);
        if($this->dbi->affected_rows > 0) {
          if($id) {
            $sql = "update assets set updated_by = {$_SESSION['user_id']}, updated_date = '" . Date('Y-m-d H:i') . "' where id = $id;";
            $this->dbi->query($sql);
            $num_updated += 1;
          } else {
            $num_added += 1;
          }
        }
      }
      $str .= "
      <h3>$num_processed Asset".$this->pluralise($num_processed)." Processed.</h3>
      <!-- <h3>$num_added Asset".$this->pluralise($num_added)." Added.</h3>
      <h3>$num_updated Asset".$this->pluralise($num_updated)." Updated.</h3>
      <h3>$num_deleted Asset".$this->pluralise($num_deleted)." Deleted.</h3> -->
      ";
    }
    return $str;
  }
//      <h3 class=\"message\">Do NOT re-upload this file!!!<br /><br />To make further changes, download the file again and change the downloaded file.</h3>"; 

  function NotebookAllocation() {

    $this->list_obj->sql = "select notebook_allocation.id as `idin`, 
      CONCAT(users.name, ' ', users.surname) as `Received By`,
      CONCAT(users3.name, ' ', users3.surname) as `Issued By`,
      states.item_name as `State`,
      notebook_allocation.place_of_issue as `Place Of Issue`,
      notebook_allocation.notebook_number as `Notebook Number`,
      notebook_allocation.issue_date as `Issue Date`,
      notebook_allocation.return_date as `Return Date`,
      CONCAT(users2.name, ' ', users2.surname) as `Returned By`,
      CONCAT(users4.name, ' ', users4.surname) as `Returned To`,
      'Edit' as `*`, 'Delete' as `!` from notebook_allocation
      
      left join users on users.id = notebook_allocation.received_by_id
      left join users2 on users2.id = notebook_allocation.returned_by_id
      left join users3 on users3.id = notebook_allocation.issued_by_id
      left join users4 on users4.id = notebook_allocation.returned_to_id
      left join states on states.id = notebook_allocation.state_id
      
      ;";

    $staff_sql = $this->user_dropdown(107,108);

    $this->editor_obj->table = "notebook_allocation";
    $style = 'style="width: 125px;"';
    $style_large = 'style="width: 250px;"';
    $this->editor_obj->form_attributes = array(
      array("cmbReceivedBy", "cmbReturnedBy", "cmbIssuedBy", "cmbReturnedTo", "selState", "txtPlaceOfIssue", "txtNotebookNumber", "calIssueDate", "calReturnDate"),
      array("Received By", "Returned By", "Issued By", "Returned To", "State", "Place Of Issue", "Notebook Number", "Issue Date", "Return Date"),
      array("received_by_id", "returned_by_id", "issued_by_id", "returned_to_id", "state_id", "place_of_issue", "notebook_number", "issue_date", "return_date"),
      array($staff_sql, $staff_sql, $staff_sql, $staff_sql, $this->get_simple_lookup('states'), "", "", "", ""),
      array($style, $style, $style, $style, $style, $style, $style, $style, $style),
      array("n", "n", "n", "n", "n", "n", "n", "n", "n")
    );

    $this->editor_obj->button_attributes = array(
      array("Add New", "Save", "Reset", "Filter"),
      array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
      array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
      array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
    );
    $this->editor_obj->form_template = '
    <div class="form-wrapper" style="">
      <div class="form-header" style="">Notebook Allocation</div>
      <div class="form-content">
        <div class="fl"><nobr>tcmbReceivedBy</nobr><br />cmbReceivedBy</div>
        <div class="fl"><nobr>tcmbIssuedBy</nobr><br />cmbIssuedBy</div>
        <div class="fl"><nobr>tselState</nobr><br />selState</div>
        <div class="fl"><nobr>ttxtPlaceOfIssue</nobr><br />txtPlaceOfIssue</div>
        <div class="fl"><nobr>ttxtNotebookNumber</nobr><br />txtNotebookNumber</div>
        <div class="fl"><nobr>tcalIssueDate</nobr><br />calIssueDate</div>
        <div class="cl"></div>
        <hr />
        <div class="fl"><nobr>tcalReturnDate</nobr><br />calReturnDate</div>
        <div class="fl"><nobr>tcmbReturnedBy</nobr><br />cmbReturnedBy</div>
        <div class="fl"><nobr>tcmbReturnedTo</nobr><br />cmbReturnedTo</div>
        <div class="cl"></div>
        '.$this->editor_obj->button_list().'
      </div>
    </div>';
    
    
    $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
    $str .= $this->editor_obj->draw_data_editor($this->list_obj);

    if(!$_POST['idin']) {
      $str .= "
      <script>
      document.getElementById('cmbIssuedBy').value = '{$_SESSION['full_name']}'; 
      document.getElementById('hdncmbIssuedBy').value = '{$_SESSION['user_id']}';
      document.getElementById('selState').selectedIndex = 2;
      document.getElementById('txtPlaceOfIssue').value = 'Head Office';
      document.getElementById('calIssueDate').value = '".date('d-M-Y')."';

      </script>
      ";
    }
    
    return $str;
  }         


  function AllocationSummary() {

    $subject_id = (isset($_GET['subject_id']) ? $_GET['subject_id'] : null);
    $compliance_id = (isset($_GET['compliance_id']) ? $_GET['compliance_id'] : null);
    $assessor_id = (isset($_GET['assessor_id']) ? $_GET['assessor_id'] : null);
    $return_id = (isset($_GET['return_id']) ? $_GET['return_id'] : null);
    
    $nav_month = (isset($_GET['selDateMonth']) ? $_GET['selDateMonth'] : null);
    $nav_year = (isset($_GET['selDateYear']) ? $_GET['selDateYear'] : null);
    if(!$nav_month) {
      $def_month = 1;
      $nav_month = date("m");
      $nav_year = date("Y");
    }
    $selDateMonth = $nav_month;
    $selDateYear = $nav_year;
    $compare_date = "$nav_year-" . ($nav_month < 10 ? "0$nav_month" : $nav_month) . "-01";
    //$str .= $compare_date;
    if($nav_month > 0) {
      $nav1 = "and (MONTH(asset_allocation.date_issued) = $nav_month or DATE_ADD(CONCAT(YEAR(asset_allocation.date_issued), '-', MONTH(asset_allocation.date_issued), '-01'), interval -1 month) = '$compare_date')";
    } else {
      $nav_month = "ALL Months";
    }
    if($nav_year > 0) {
      $nav2 = "and YEAR(asset_allocation.date_issued) = $nav_year";
    } else {
      $nav_year = "ALL Years";
    }

    $url_str = ($hdnReportFilter ? "&hdnReportFilter=$hdnReportFilter" : "") . ($selDateMonth ? "&selDateMonth=$selDateMonth" : "") . ($selDateYear ? "&selDateYear=$selDateYear" : "") . ($division_id ? "&division_id=$division_id" : "");
    $url_str_xl = ($subject_id ? "&subject_id=$subject_id" : "") . ($compliance_id ? "&compliance_id=$compliance_id" : "") . ($assessor_id ? "&assessor_id=$assessor_id" : "");
    
    $xl = (isset($_GET['xl']) ? $_GET['xl'] : null);

    if($xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = "select 
      CONCAT(DATE_FORMAT(asset_allocation.date_issued, '%d/%b/%Y')) as `Date Issued`,
      CONCAT(users.name, ' ', users.surname, ' (', users.employee_id, ')') as `Issued To`,
      if(make_id != 0, lookup_fields.item_name, '') as `Make`,
      asset_allocation.item_name as `Item`,
      size as `Size`
      from asset_allocation
      left join lookup_fields on lookup_fields.id = asset_allocation.make_id
      left join users on users.id = asset_allocation.issued_to_id
      where 1
      $nav1 $nav2
      order by date_issued
      ";
      $xl_obj->sql_xl('allocation_summary.xlsx');
    }
    
    $nav = new navbar;
    $filter_box = '
      <script language="JavaScript">
        function report_filter() {
          document.getElementById("hdnReportFilter").value=1
          document.frmFilter.submit()
        }
      </script>
      </form>
      <form method="GET" name="frmFilter">
      <input type="hidden" name="hdnReportFilter" id="hdnReportFilter">
      <input type="hidden" name="division_id" value="'.$division_id.'">
      <div class="fl">
      <h3>Allocation Summary &nbsp; <a class="list_a" href="' . $this->f3->get('main_folder') . "Assets/AllocationSummary?xl=1$url_str$url_str_xl" . '">Download Excel</a></h3>
      </div>
      <div  style="padding: 10px;" class="fr">
      '.$nav->month_year(2016).'    <input onClick="report_filter()" type="button" value="Go" /> 
      </form>
    ';
    if($def_month) {
      $filter_box .= ' 
        <script language="JavaScript">
          change_selDate()
        </script>
      ';
    }
    
    $str .= $filter_box . '</div><div class="cl"></div><hr />';
    //$this->list_obj->title = 'Compliance Checks';
    $this->list_obj->show_num_records = 1;
//    $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;       $this->list_obj->nav_count = 20;
//    left join users2 on users2.id = asset_allocation.issued_by_id
    $this->list_obj->sql = "select 
    asset_allocation.date_issued as `Date Issued`,
    CONCAT(users.name, ' ', users.surname, ' (', users.employee_id, ')') as `Issued To`,
    if(make_id != 0, lookup_fields.item_name, '') as `Make`,
    asset_allocation.item_name as `Item`,
    size as `Size`
    from asset_allocation
    left join lookup_fields on lookup_fields.id = asset_allocation.make_id
    left join users on users.id = asset_allocation.issued_to_id
    where 1
    $nav1 $nav2
    order by date_issued
    ";

    // or lookup_fields2.item_name LIKE '%report%'

  //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
    $str .= $this->list_obj->draw_list();
    
    return $str;
    
  }


  function BulkMessage() {
      return true;
      die;
    $sms = new sms($this->dbi);

    $mail = new email_q($this->dbi);
    $mail->clear_all();

    $sql = "SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `name`, users.phone, users.phone2, users.email
               FROM users
               left join user_status on user_status.id = users.user_status_id
               inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 107 and lookup_answers.table_assoc = 'users'
                and users.user_status_id = (select id from user_status where item_name = 'ACTIVE')
               order by users.name";
    if($result = $this->dbi->query($sql)) {
      //$sms_message = "Please NOTE: Change of Address for Allied Security Management and Allied Facilities Management. New Address   Unit 19/55-61 Pine Road, Yennora 2161.";
      $sms_message = "To continue working with Allied Management, ALL Staff MUST read/sign safety (pg 3) and SOP (pg 28), found in the blue site compliance folders by Wed 20/Nov/2019";
      $hr_email_message = "";
      //$str .= "<h3>The SMS was Sent to:</h3>";
      //$str2 .= "<h3>The Email was Sent to:</h3>";

      $str .= "<h3>Phone Numbers:</h3>";
      $str2 .= "<h3>Email Addresses:</h3>";

      //$str .= "<p>$sms_message</p>";
      //$str .= "<h3>Sent To</h3>";
      $str .= '<table class="grid">';
      $str2 .= '<table class="grid">';
      $str .= "<tr><th>#</th><th>Phone</th><th>Staff Member</th></tr>";
      $str2 .= "<tr><th>#</th><th>Email</th><th>Staff Member</th></tr>";
      $count = 0;      $count2 = 0;
      $subject = "Change of Address: Allied Security/Facilities Management";
      while($myrow = $result->fetch_assoc()) {
        $phone = $myrow['phone'];
        $phone2 = $myrow['phone2'];
        $user_id = $myrow['idin'];
        $name = $myrow['name'];
        $email = $myrow['email'];
        if($this->validate_email($email)) {
          $count2++;
//          $email_message = "Hello $name,<br/><br/>This letter is to inform you that Allied Security Management and Allied Facilities have a <b>NEW address:</b><br /><br />Unit 19/55-61 Pine Road, Yennora 2161<br/><br/>Regards,<br/>Allied Management.";
          $email_message = "Hello $name,<br/><br/>It is a condition of working with Allied Management, that <b>ALL</b> Staff MUST read/sign safety (pg 3) and SOP (pg 28), found in the blue site compliance folders.<p><b>Due Date:</b> Wed, 20/Nov/2019</p><p>Regards,<br/>Allied Management.";
          /*$mail->clear_all();
          $mail->AddAddress($email);
          $mail->Subject = $subject;
          $mail->Body    = $email_message;
          $mail->queue_message();
          $mail->send();
          */
          $str2 .= "<tr><td>$count2</td><td>$email</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Email Address.\" target=\"_blank\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."UserCard?uid=$user_id\">Card</a> $name</td></tr>";
        } else {
          $str2 .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>".($email ? "Invalid" : "Missing")." Email Address".($email ? "<br/>$email" : "")."</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Email Address.\" target=\"_blank\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."UserCard?uid=$user_id\">Card</a> $name<br/>" . $myrow['phone'] . " " . $myrow['phone2'] . "</td></tr>";
        }
      
        if($phone = $sms->process_phones($phone, $phone2)) {
          $count++;
          //$sms->queue_message($phone, $sms_message);
          $str .= "<tr><td>$count</td><td>" . $myrow['phone'] . " " . $myrow['phone2'] . "</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."UserCard?uid=$user_id\">Card</a> $name</td></tr>";
        } else {
          $str .= "<tr style=\"border: 2px solid red !important;\"><td style=\"color: red;\">*</td><td>".($myrow['phone'] || $myrow['phone2'] ? "Invalid" : "Missing")." Phone Number".($myrow['phone'] || $myrow['phone2'] ? '<br/>' . $myrow['phone'] . ' ' . $myrow['phone2'] : "")."</td><td><a data-uk-tooltip title=\"Open Card to Add/Fix the Phone Number. The correct format of a phone number is 0412 345 678.\" target=\"_blank\" class=\"list_a\" href=\"".$this->f3->get('main_folder')."UserCard?uid=$user_id\">Card</a> $name</td></tr>";
        }
      }
      $str .= '</table>';
      $str2 .= '</table>';
      /*$mail->clear_all();
      $mail->AddAddress("edgar@alliedmanagement.com.au");
      $mail->AddAddress("compliance@alliedmanagement.com.au");
      $mail->Subject = $subject;
      $mail->Body    = $str . $str2;
      $mail->queue_message();*/
    }
    //return "<table cellpadding=\"12\"><tr><td valign=\"top\">$str2</td><td valign=\"top\">$str</td></tr></table>";
    return $str;
  }

}

/*
      for ($row = 2; $row <= $highest_row; $row++) {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        $asset_make = $this->pstr($this->dbi, $rowData[0][2]);
        $asset_category = $this->pstr($this->dbi, $rowData[0][2]);
        $location = $this->pstr($this->dbi, $rowData[0][5]);
        if($asset_category) {
          if($result = $this->dbi->query("SELECT id FROM `lookup_fields` where item_name = '$asset_category' and lookup_id = 26")) {
            if(!$result->num_rows) {
              $this->dbi->query("insert into `lookup_fields` (lookup_id, sort_order, item_name) values (26, 10, '$asset_category')");
              $categories[$asset_category] = $this->dbi->insert_id;
            } else {
              $myrow = $result->fetch_assoc();
              $categories[$asset_category] = $myrow['id'];
            }
          }
        }
        if($location) {
          if($result = $this->dbi->query("SELECT id FROM `lookup_fields` where item_name = '$location' and lookup_id = 25")) {
            if(!$result->num_rows) {
              $this->dbi->query("insert into `lookup_fields` (lookup_id, sort_order, item_name, value) values (25, 10, '$location', '$state')");
              $locations[$location] = $this->dbi->insert_id;
            } else {
              $myrow = $result->fetch_assoc();
              $locations[$location] = $myrow['id'];
            }
          }
        }
        if($asset_make) {
          if($result = $this->dbi->query("SELECT id FROM `lookup_fields` where item_name = '$asset_make' and lookup_id = 101")) {
            if(!$result->num_rows) {
              $this->dbi->query("insert into `lookup_fields` (lookup_id, sort_order, item_name) values (101, 10, '$asset_make')");
              $asset_makes[$asset_make] = $this->dbi->insert_id;
            } else {
              $myrow = $result->fetch_assoc();
              $asset_makes[$asset_make] = $myrow['id'];
            }
          }
        }
      }
*/
    

?>
