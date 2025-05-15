<?php

/*
0. Field Names
Attributes:
txt: textbox
opt: radio
chk: checkbox
chls[]: checkbox list
cal: textbox with calendar
clm: mobile friendly textbox with calendar
upl: upload
dir: directory list
lbx: listbox
oth: Drop Down List box with other choice
cam: Camera
cmb: Combo Box
cm2: Simple Combo Box
lbl: Label
chl: checkbox list


New:
usl: User Selector

1. Field Titles
2. Data Fields
3. Field Lookups - If there are 2 items, use the first one as the id, otherwise just use the text
4. Field Extra Attributes such as CSS
5. Field Regular Expressions (JavaScript), or simply use c for compulsory and u for no error checking.
*/


//  function txt($field_id, $field_text="", $field_xtra="", $field_value="",$dbi,$sql) {
class input_form extends input_item {
  var $dbi;
  var $form_attributes;
  var $form_template;
  var $table;
  var $insert_str, $del_str, $update_str;
  var $xtra_validation;
  var $hide_confirm;
  var $bot_protect;
  var $formAttributesVerticle;

 function formAttributesVerticle($fields_arr = array()){
  if(!empty($fields_arr) && count($fields_arr)>0){
     $arr1 = $arr2 = $arr3 = $arr4 = $arr5 = $arr6 = $arr7 = array();
     foreach ($fields_arr as $key => $value) {
        $arr1[] = $value[0];
        $arr2[] = $value[1];
        $arr3[] = $value[2];
        $arr4[] = $value[3];
        $arr5[] = $value[4];
        $arr6[] = $value[5];
        $arr7[] = $value[6]; 
     }
     return array($arr1,$arr2,$arr3,$arr4,$arr5,$arr6,$arr7);
   }
  }
  function display_attribute() {
//    $val_str .= "function check() {
//                      var err, confirmation, error_msg
//                      ".($this->bot_protect ? 'document.getElementById("txtCatsName").value = "Felix Templeton";' : "")."
//                      confirmation = \"\"
//                      error_msg = \"\"
//                      err = 0;
//                ";
             
    for($x = 0; $x < count($this->form_attributes[0]); $x++) {
        
      $field_type = substr($this->form_attributes[0][$x], 0, 3);
      if($field_type) {
				if($field_type == "txt" || $field_type == "cmb" || $field_type == "cm2" && !$txt_done) {
					$str .= $this->setup_txt();
					$txt_done = 1;
				}
				if($field_type == "upl" && !$upl_done) {
					$str .= $this->setup_upl();
					$upl_done = 1;
				}
				if($field_type == "usl" && !$usl_done) {
					$str .= $this->setup_usl();
					$usl_done = 1;
				}
				if($field_type == "cam" && !$cam_done) {
					$str .= $this->setup_cam();
					$cam_done = 1;
				}
				if($field_type == "tim" && !$tim_done) {
					$str .= $this->setup_tim();
					$tim_done = 1;
				}
				if($field_type == "ti2" && !$ti2_done) {
					$str .= $this->setup_ti2();
					$ti2_done = 1;
				}
				if($field_type == "cal" && !$cal_done) {
					$str .= $this->setup_cal();
					$cal_done = 1;
				}
				if($field_type == "clm" && !$clm_done) {
					$str .= $this->setup_clm();
					$clm_done = 1;
				}
				if($field_type == "	" && !$cms_done) {
					$str .= $this->setup_cms();
					$cms_done = 1;
				}
				if($field_type == "tad"){
					if(!$tad_done) $str .= $this->setup_tad();
          $tad_done = 1;
				}
      }
    }

//    if($this->bot_protect) {
//      $str .= '<div style="visibility: hidden; font-size: 1px; margin-bottom: -20px; padding: 0;"><input type="text" name="txtCatsName" id="txtCatsName" size="2"><input type="text" name="txtDogsName" size="2" value="Fido Baggins"></div>';
//    }
    $str .= $this->form_template;

    for($x = 0; $x < count($this->form_attributes[0]); $x++) {
      $field_type = substr($this->form_attributes[0][$x], 0, 3);


      $field_id = $this->form_attributes[0][$x];
      $field_title = $this->form_attributes[1][$x];
      $data_field = $this->form_attributes[2][$x];
      $field_lookup = $this->form_attributes[3][$x];
      $field_xtra = $this->form_attributes[4][$x];
      $field_regex = $this->form_attributes[5][$x];
      $field_value = $this->form_attributes[6][$x];
      $compulsory = ($field_regex == "c" ? "*" : "");
       
      if($field_type) {
          
        $tmp_str = $this->$field_type($field_id,$field_value,$field_xtra,$field_value,$this->dbi,$field_lookup,$field_title . $compulsory);
        
        $str = preg_replace('/\bt'.$field_id.'\b/', $field_title . $compulsory, $str);
        
        //$str = str_replace("button_list", $this->button_list(), $str);
        
        $str = preg_replace('/\b'.$field_id.'\b/', $tmp_str, $str);


        if($field_type == "txt" || $field_type == "cal" || $field_type == "cms" || $field_type == "clm" || $field_type == "txa" || $field_type == "cmb" || $field_type == "cm2" || $field_type == "pwd" || $field_type == "tad") {
          if($field_regex == "c") {
            //Compulsory text value
            $val_str .= "
            if(!document.getElementById(\"$field_id\").value.length) {
              err++;
              error_msg += err + \". $field_title is missing\\n\";
            }
            ";
            if($field_type != "cms") {
              //$val_str .= "confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").value + \"\\n\"\n";
            }
          } else {
            //Non compulsory text value
              //$val_str .= "if(document.getElementById(\"$field_id\").length > 0) confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").value + \"\\n\"\n";
          }
        }

        if($field_type == "msl" || $field_type == "sel" || $field_type == "dir") {
          if($field_regex == "c") {
            $val_str .= "
            if(!document.getElementById(\"$field_id\").selectedIndex) {
              err++;
              error_msg += err + \". $field_title is missing\\n\";
            }
            ";
          }
          //$val_str .= "if(document.getElementById(\"$field_id\").selectedIndex) confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").options[document.getElementById(\"$field_id\").selectedIndex].text + \"\\n\"\n";
        }
      }
    }

    $val_str .= $this->xtra_validation;

    
//$val_str .= "
//    if (!err){
//    ";
//    if(!$this->hide_confirm) {
//      $val_str .= "
//        confirmation = \"Are these the correct details?\\n\\n\" + confirmation;
//        if (confirm(confirmation)) {
//          return true;
//        } else {
//          return false;
//        }
//      ";
//    } else {
//      $val_str .= "return true;";
//    }
//    $val_str .= "
//    } else {
//      alert(error_msg);
//      return false;
//    }
//    } //end of function
//
//    ";

//    $str .= ;
   
    $wrapper_str .= $val_str;


    $str .= $this->js_wrapper($wrapper_str);

    return $str;

  }
  function display_form() {
    $val_str .= "function check() {
                      var err, confirmation, error_msg
                      ".($this->bot_protect ? 'document.getElementById("txtCatsName").value = "Felix Templeton";' : "")."
                      confirmation = \"\"
                      error_msg = \"\"
                      err = 0;
                ";
   if($this->form_attributes){          
    for($x = 0; $x < count($this->form_attributes[0]); $x++) {
        
      $field_type = substr($this->form_attributes[0][$x], 0, 3);
      if($field_type) {
				if($field_type == "txt" || $field_type == "cmb" || $field_type == "cm2" && !$txt_done) {
					$str .= $this->setup_txt();
					$txt_done = 1;
				}
				if($field_type == "upl" && !$upl_done) {
					$str .= $this->setup_upl();
					$upl_done = 1;
				}
				if($field_type == "usl" && !$usl_done) {
					$str .= $this->setup_usl();
					$usl_done = 1;
				}
				if($field_type == "cam" && !$cam_done) {
					$str .= $this->setup_cam();
					$cam_done = 1;
				}
				if($field_type == "tim" && !$tim_done) {
					$str .= $this->setup_tim();
					$tim_done = 1;
				}
				if($field_type == "ti2" && !$ti2_done) {
					$str .= $this->setup_ti2();
					$ti2_done = 1;
				}
				if($field_type == "cal" && !$cal_done) {
					$str .= $this->setup_cal();
					$cal_done = 1;
				}
				if($field_type == "clm" && !$clm_done) {
					$str .= $this->setup_clm();
					$clm_done = 1;
				}
				if($field_type == "	" && !$cms_done) {
					$str .= $this->setup_cms();
					$cms_done = 1;
				}
				if($field_type == "tad") {
					if(!$tad_done) $str .= $this->setup_tad();
          $tad_done = 1;
				}
      }
    }

    if($this->bot_protect) {
      $str .= '<div style="visibility: hidden; font-size: 1px; margin-bottom: -20px; padding: 0;"><input type="text" name="txtCatsName" id="txtCatsName" size="2"><input type="text" name="txtDogsName" size="2" value="Fido Baggins"></div>';
    }
    $str .= $this->form_template;

    for($x = 0; $x < count($this->form_attributes[0]); $x++) {
      $field_type = substr($this->form_attributes[0][$x], 0, 3);


      $field_id = $this->form_attributes[0][$x];
      $field_title = $this->form_attributes[1][$x];
      $data_field = $this->form_attributes[2][$x];
      $field_lookup = $this->form_attributes[3][$x];
      $field_xtra = $this->form_attributes[4][$x];
      $field_regex = $this->form_attributes[5][$x];
      $field_value = $this->form_attributes[6][$x];
      $compulsory = ($field_regex == "c" ? "*" : "");
       
      if($field_type) {
          
        $tmp_str = $this->$field_type($field_id,$field_value,$field_xtra,$field_value,$this->dbi,$field_lookup,$field_title . $compulsory);
        
        $str = preg_replace('/\bt'.$field_id.'\b/', $field_title . $compulsory, $str);
        
        $str = str_replace("button_list", $this->button_list(), $str);
        
        $str = preg_replace('/\b'.$field_id.'\b/', $tmp_str, $str);


        if($field_type == "txt" || $field_type == "cal" || $field_type == "cms" || $field_type == "clm" || $field_type == "txa" || $field_type == "cmb" || $field_type == "cm2" || $field_type == "pwd" || $field_type == "tad") {
          if($field_regex == "c") {
            //Compulsory text value
            $val_str .= "
            if(!document.getElementById(\"$field_id\").value.length) {
              err++;
              error_msg += err + \". $field_title is missing\\n\";
            }
            ";
            if($field_type != "cms") {
              $val_str .= "confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").value + \"\\n\"\n";
            }
          } else {
            //Non compulsory text value
              //$val_str .= "if(document.getElementById(\"$field_id\").length > 0) confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").value + \"\\n\"\n";
          }
        }

        if($field_type == "msl" && $field_type == "sel" || $field_type == "dir") {
          if($field_regex == "c") {
            $val_str .= "
            if(!document.getElementById(\"$field_id\").selectedIndex) {
              err++;
              error_msg += err + \". $field_title is missing\\n\";
            }
            ";
          }
          $val_str .= "if(document.getElementById(\"$field_id\").selectedIndex) confirmation += \"$field_title: \" + document.getElementById(\"$field_id\").options[document.getElementById(\"$field_id\").selectedIndex].text + \"\\n\"\n";
        }
      }
    }

    $val_str .= $this->xtra_validation;

    //The action field, operations are determined by the value of this field
    $str .= $this->hdn("hdnAction", "", "", "", "", "");
    $str .= $this->hdn("idin", "", "", "", "", "");
    $str .= $this->hdn("hdnFilter", "", "", "", "", "");
    $str .= $this->hdn("hdnAltFilter", "", "", "", "", "");
    $str .= $this->hdn("hdnJustAdded", "", "", "", "", "");

    $val_str .= "
    if (!err){
    ";
    if(!$this->hide_confirm) {
      $val_str .= "
        confirmation = \"Are these the correct details?\\n\\n\" + confirmation;
        if (confirm(confirmation)) {
          return true;
        } else {
          return false;
        }
      ";
    } else {
      $val_str .= "return true;";
    }
    $val_str .= "
    } else {
      alert(error_msg);
      return false;
    }
    } //end of function

    ";


//    $str .= ;
    if(count($this->button_attributes[3])) {
      foreach ($this->button_attributes[3] as $js_function) {
        $wrapper_str .= $this->$js_function();
      }
    }
    $wrapper_str .= "document.getElementById(\"hdnAction\").value=0;\n" . $val_str;


    $str .= $this->js_wrapper($wrapper_str);
  }
  else{
      $str = "";
  }    
    return $str;

  }

  function js_wrapper($js_str) {
    $str = '

            <script type="text/javascript">
            '.$js_str.'
            </script>
    ';
    return $str;
  }

  function js_function_new() {
    $str = "
    function new_record() {
      document.getElementById(\"hdnAction\").value = 'new_record';
    }
    ";
    return $str;
  }

  function js_function_add() {
    $str = "
    function add() {
      if(check()) {
        document.getElementById(\"hdnAction\").value = 'add_record';
        return true;
      }
    }
    ";
    return $str;
  }

  function js_function_save() {
    $str = "
    function save() {
      // Check if selWorkingForProvider is 'Yes' and selProviderId is not selected
      var selWorkingForProvider = $('#selWorkingForProvider').val();
      var selProviderId = $('#selProviderId').val();
      // alert(selProviderId);
      if (selWorkingForProvider == 'Y' && selProviderId == null) {
        alert('Please select a provider Name.');
        return false;
      }

      // Check if selWorkingForProvider is 'No' and selProviderId is selected
      if (selWorkingForProvider == 'N' && selProviderId != null) {
        alert('Provider selection is not required when No is selected.');
        return false;
      }
      //var clean_uri = location.protocol + \"//\" + location.host + location.pathname;
      //window.history.replaceState({}, document.title, clean_uri);
      if(document.getElementById(\"hdnAction\").value == 'new_record') {
        return add()
      } else {";
      if($_REQUEST["idin"]) {
        $str .= "
        if(check()) {
          document.getElementById(\"idin\").value = " . $_REQUEST["idin"] . ";
          document.getElementById(\"hdnAction\").value = 'save_record';
          var selWorkingForProvider = $('#selWorkingForProvider').val();
          var selProviderId = $('#selProviderId').val();
          // alert(selProviderId);
          if (selWorkingForProvider == 'Y' && selProviderId == null) {
            alert('Please select a provider Name.');
            return false;
          }

          // Check if selWorkingForProvider is 'No' and selProviderId is selected
          if (selWorkingForProvider == 'N' && selProviderId != null) {
            alert('Provider selection is not required when No is selected.');
            return false;
          }
          return true;
        }";
      } else {
        $str .= "
        return add()
        ";
      }
      $str .= "
      }
    }
    ";
    return $str;
  }

  function js_function_provider_check() {
    $str = "
    function updateProviderIdRequirement() {
        if ($('#selWorkingForProvider').val() === 'Y') {
            $('#selProviderId').prop('required', true);
        } else {
            $('#selProviderId').prop('required', false);
        }
    }

    function initializeProviderIdRequirement() {
        // Initial check
        updateProviderIdRequirement();

        // Event listener for changes in selWorkingForProvider
        $('#selWorkingForProvider').on('change', function() {
            updateProviderIdRequirement();
        });
    }

    // Call initialize function when document is ready
    $(function() {
        initializeProviderIdRequirement();
    });
    ";

    return $str;
  }

  function js_function_delete() {
    $str = "
    function delete_record(idin) {
      var confirmation;
      confirmation = 'Are you sure about deleting this record?';
      if (confirm(confirmation)) {
        document.getElementById(\"idin\").value = idin;
        document.getElementById(\"hdnAction\").value = 'delete_record';
        document.frmEdit.submit();
      }
    }
    ";
    return $str;
  }
  
  function js_function_selectall(){
      alert("hello");
  }

  function js_function_edit() {
    $str = "
    function edit_record(idin) {
      document.getElementById(\"idin\").value = idin;
      document.frmEdit.submit();
    }
    ";
    return $str;
  }

  function js_function_filter() {
    $str = "
    function filter() {
      document.getElementById(\"hdnFilter\").value = 1;
      //document.frmEdit.method = \"GET\";
      document.frmEdit.submit();
    }
    ";
    return $str;
  }


}

?>