<?php
class input_item extends Controller {
  //Common items: field_id, field_xtra, field_text
  //sel and opt specific: field_value
  var $button_attributes;
  var $link_attributes;
	var $chk_done;
  var $placeholder;
  var $hide_filter;
  var $blur_xtra;
  var $dbl_xtra;
  var $hide_duplicate;

  //Text Field
  function setup_txt() {
    $str = '
      <script type="text/javascript">
        function check_filter(event) {
          if(event.keyCode == 13) filter()
        }
      </script>
      ';
    return $str;
  }
  function txt($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }

    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    $str = '<input ' . ($this->hide_filter ? '' : 'onKeypress="check_filter(event)"') . ' type="text" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    return $str;
  } 

  function mtx($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    $str = '<input autocorrect="off" autocapitalize="off" spellcheck="false" type="text" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    return $str;
  }   

  //Email Field
  function eml($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str = '<input type="email" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    return $str;
  } 

  //Password Field
  function pwd($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str = '<input type="password" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    return $str;
  } 

  function opt($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
		$str = $this->setup_check();
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }
    $str .= "<span onClick=\"toggle('$field_id');\" class=\"sel\"><input onClick=\"toggle('$field_id')".'" type="radio" id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $field_text . '" /> ' . $field_text;
    if($field_text) $str .= "</span>";
    return $str;
  } 

  function chk($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
		$str = $this->setup_check();
    if(!$field_value) $field_value = 1;
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }
    $str .= "<span onClick=\"toggle('$field_id', 1);\" class=\"sel\">".'<input type="hidden" value="'.(trim($field_xtra) == "checked" ? "on" : "off").'"  id="h' . $field_id . '" name="h' . $field_name . '" />'."<input onClick=\"toggle('$field_id', 1)".'" type="checkbox" id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $field_value . '" /> ' . $field_text;
    //$str .= "<span onClick=\"toggle('$field_id');\" class=\"sel\"><input onClick=\"toggle('$field_id', 1)".'" type="checkbox" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="1" /> ' . $field_text;
    if($field_text) $str .= "</span>";
    return $str;
  } 

  function setup_check() {
	  if(!$this->chk_done) {
			$str = '
			<style>
				.sel {
					cursor: default;
					padding-right: 4px;
					padding-left: 4px;
					display: inline-block;
				}
				.sel:hover {
          background-color: #FFFFDD;
        }
			</style>
			<script type="text/javascript">
			function toggle(item_id, is_chk = 0) {
				if(document.getElementById(item_id).checked==true) {
          document.getElementById(item_id).checked=false;
          if(is_chk) document.getElementById("h" + item_id).value="off";
        } else {
          document.getElementById(item_id).checked=true;
          if(is_chk) document.getElementById("h" + item_id).value="on";
        }
        //if(is_chk) alert(document.getElementById("h" + item_id).value)
			}
			</script>
				';
			$this->chk_done = 1;
		}
    return $str;
  }
  
  
  //Text Area
  function txa($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    $str = '<textarea ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . '>' . $xtra . '</textarea>';
    return $str;
  } 

  //Hidden Field
  function hdn($field_id, $field_text = '', $field_xtra = '', $field_value = '', $dbi = '', $sql = '', $title = '') {
    $str = '<input type="hidden" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $field_text . '" />';
    return $str;
  } 

  //File Upload Field
  function setup_upl($form_name="frmEdit") {
    $str = '
      <script type="text/javascript">
        document.'.$form_name.'.enctype = "multipart/form-data"
      </script>
      ';
    return $str;
  }
  function upl($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str .= '
      <p><input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
      <label for="thefile">'.$field_xtra.'</label> <input type="file" name="' . $field_id . '" id="' . $field_id . '"> </p>
      ';
    return $str;
//      <input type="text" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />
  }

  //Select Field
  function sel($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str = '<select ' . ($this->placeholder ? ' title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $field_text . '">' . "\n";
    if ($result = $this->dbi->query($sql)) {
      $finfo = $result->fetch_fields();
      $num_fields = $result->field_count;
      while ($myrow = mysqli_fetch_object($result)) {
        foreach ($finfo as $val) {
          $itm = $val->name;
          if(strtoupper($itm) == "ID") {
            $str_val = 'value="' . $myrow->$itm . '"';
            $xtra = (($myrow->$itm == $_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? "selected" : "");
          } else {
            $str_item = strip_tags($myrow->$itm);
          }
        }
        $str .= "<option $xtra $str_val>$str_item</option>";
        $item_count++;
      }
    }
    $str .= '</select>';
    return $str;
  }
  
  //Select Field
  function msl($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
      
    $str = '<select ' . ($this->placeholder ? ' title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '[]" ' . $field_xtra . ' value="' . $field_text . '">' . "\n";
    if ($result = $this->dbi->query($sql)) {
      $finfo = $result->fetch_fields();
      $num_fields = $result->field_count;
      while ($myrow = mysqli_fetch_object($result)) {
        foreach ($finfo as $val) {
          $itm = $val->name;
          if(strtoupper($itm) == "ID") {
            $str_val = 'value="' . $myrow->$itm . '"';
            $xtra = (($myrow->$itm == $_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? "selected" : "");
          } else {
            $str_item = strip_tags($myrow->$itm);
          }
        }
        $str .= "<option $xtra $str_val>$str_item</option>";
        $item_count++;
      }
    }
    $str .= '</select>';    
    return $str;
  }

  //Select Field
  function usl($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str = '<select ' . ($this->placeholder ? ' title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $field_text . '">' . "\n";
    if ($result = $this->dbi->query($sql)) {
      $finfo = $result->fetch_fields();
      $num_fields = $result->field_count;
      while ($myrow = mysqli_fetch_object($result)) {
        foreach ($finfo as $val) {
          $itm = $val->name;
          if(strtoupper($itm) == "ID") {
            $str_val = 'value="' . $myrow->$itm . '"';
            $xtra = (($myrow->$itm == $_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? "selected" : "");
          } else {
            $str_item = strip_tags($myrow->$itm);
          }
        }
        $str .= "<option $xtra $str_val>$str_item</option>";
        $item_count++;
      }
    }
    $str .= '</select>';
    return $str;
  }

  //Combo Box Fields
  //Old Style
  function cm2($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    $str = '<input ' . ($this->hide_filter ? '' : 'onKeypress="check_filter(event)"') . ' type="text" autocomplete="off" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' list="l' . $field_id . '" id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    $str .= '<datalist id="l' . $field_id . '">';
    
    if($field_value) {
      if(strstr($field_value, "|") !== false) {
        $field_values = explode('|', $field_value);
        foreach ($field_values as $option) {
          $str .= '<option value="'.$option.'"/>';
        }
      } else {
        $str .= '<option value="'.$field_value.'"/>';
      }
    } else {
      $result = $this->dbi->query($sql);
     $i= 1;
      while($myrow = $result->fetch_assoc()) {
        if($title == 'staff_list_main'){
            if($myrow['total'] > 0){
                $rank = '('.$i.')';
                $i++;
            }else{
                $rank = "";
            }
          
        
             $str .= '<option value="'.$myrow['item_name'].$rank.'" />';
        }else{
            $str .= '<option value="'.$myrow['item_name'].'"/>'; 
        }
     
       // $str .= '<option value="'.$myrow['item_name'].'"/>';
      }
    }
    $str .= '</datalist>'; 
    
    return $str;
  }
  
  //New Style
  function cmb($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }
    $str = '<input type="hidden" id="hdn' . $field_name . '" name="hdn' . $field_name . '" />';
    //onChange="JavaScript:document.getElementById(hdn'.$field_name.').value=\'1\'; alert(document.getElementById(hdn'.$field_name.').value);" 
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    $str .= '<input ' . ($this->hide_filter ? '' : 'onKeypress="check_filter(event)"') . ' 
    onDblClick="clear_field(this);'.$this->dbl_xtra.'"
    type="text" autocomplete="off" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' list="l' . $field_id . '" id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    $str .= '<datalist id="l' . $field_id . '">';
    
    if($field_value) {
      if(strstr($field_value, "|") !== false) {
        $field_values = explode('|', $field_value);
        foreach ($field_values as $option) {
          $str .= '<option value="'.strip_tags(isset($myrow['item_name']) ? $myrow['item_name'] : $myrow['result']).'" />';
        }
      } else {
        $str .= '<option value="'.$field_value.'"/>';
      }
    } else {
      if($result = $this->dbi->query($sql)) {
        while($myrow = $result->fetch_assoc()) {
          //$str .= '<option id="' . $myrow['id'] . '" value="' . $myrow['idin'] . '">'.trim($myrow['item_name']).'</option>';
          $str .= '<option id="' . $myrow['idin'] . '" value="'.strip_tags(isset($myrow['item_name']) ? $myrow['item_name'] : $myrow['result']).'" />';
        }
      }
    }
    $str .= '</datalist>'; 
           //alert(' . json_encode($sql) . ');

        $str .= '
        <script>
          $("#' . $field_id . '").blur(function () { 
              var val = this.value;
              if(!val) document.getElementById("hdn' . $field_name . '").value = 0
          });

          $("#' . $field_id . '").on("input", function () {
              var val = this.value;
              if($("#l' . $field_id . ' option").filter(function(){ return this.value.toUpperCase() === val.toUpperCase(); }).length) {
                var id = $("#l' . $field_id . '").find(\'option[value="\' + val + \'"]\').attr("id");
                document.getElementById("hdn' . $field_name . '").value = id
                //alert(document.getElementById("hdn' . $field_name . '").value);
              }
          });

          function clear_field(field) {
            field.value = ""
            field.focus()
          }
        </script> 
    ';

    //$str .= '    ';
    return $str;
  }
  

  //Checkbox List -- A list of checkboxes with multiple selections
  function chl($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
		$str = $this->setup_check();
    if ($result = $this->dbi->query($sql)) {
      $finfo = $result->fetch_fields();
      $num_fields = $result->field_count;
      while ($myrow = mysqli_fetch_object($result)) {
  			$fid = $myrow->id;
        foreach ($finfo as $val) {
          $itm = $val->name;
          if(strtoupper($itm) == "ID") {
            $str_val = 'value="' . $myrow->$itm . '"';
          } else {
            $str_item = $myrow->$itm;
          }
					
        }
        //$xtra = $field_xtra;
        
        //$str .= "<nobr><span onClick=\"toggle('$field_id"."$fid');\" class=\"sel\"><input onClick=\"toggle('$field_id"."$fid');\" name=\"$field_id"."[]\" id=\"$field_id"."$fid\" type=\"checkbox\" $xtra $str_val> $str_item</nobr></span> ";
        $str .= "<nobr><span><input name=\"$field_id"."[]\" id=\"$field_id"."$fid\" type=\"checkbox\" $xtra $str_val> $str_item</nobr></span> ";
        $item_count++;
      }
    }
    return $str;
  }
	
	
  //Calendar Field
  
  function setup_cal() {
    $str = '
      <script type="text/javascript" src="'.$this->f3->get('js_folder').'datetimepicker_css.js"></script>
      ';
    return $str;
  }
  function cal($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);

    $str .= '<input onclick=\'javascript:NewCssCal("' . $field_id . '", "ddMMMyyyy")\' readonly type="text" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />';
    return $str;
  }

/*  OLD Calendar Code
function setup_clm() {
    $str = '
      <script type="text/javascript" src="'.$this->f3->get('js_folder').'datetimepicker_mobile.js"></script>
      ';
    return $str;
  }
  function clm($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);

    $str .= '
      <input onclick=\'javascript:NewCssCal("' . $field_id . '", "ddMMMyyyy")\' readonly type="text" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />
      ';
    return $str;
  }*/
  
  //Mobile Friendly Calendar Field
  function setup_clm() {
    $js = $this->f3->get('js_folder') . 'datepicker2/';
    $str = '
      <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
      <script type="text/javascript" src="'.$js.'picker.js"></script>
      <script type="text/javascript" src="'.$js.'picker.date.js"></script>
      <script type="text/javascript" src="'.$js.'legacy.js"></script>
      <link rel="stylesheet" href="'.$js.'themes/default.css" id="theme_base">
      <link rel="stylesheet" href="'.$js.'themes/default.date.css" id="theme_date">
      ';
    return $str;
  }
  function clm($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);

    /*$str .= '
      <input onclick=\'javascript:NewCssCal("' . $field_id . '", "ddMMMyyyy")\' readonly type="text" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />
      ';*/

      //$cls = ($field_xtra = 'class="compliance_text_box"' ? "compliance_text_box" : "datepicker");
       $cls = "datepicker";
        
      $str .= '
        <input id="' . $field_id . '" class="compliance_text_box ' . $cls . '" name="' . $field_id . '" type="text" value="' . $xtra . '">
        <div id="c' . $field_id . '"></div>
        <script type="text/javascript">';
      $str .= "
            var \$input = \$( '.$cls' ).pickadate({
                format: 'dd-mmm-yyyy',
                container: '#c$field_id',
                // editable: true,
                closeOnSelect: true,
                closeOnClear: true
            })
            var picker = \$input.pickadate('picker')
        </script>
      ";
    return $str;
  }
  

  function setup_cam() {
    $str = '
      <div id="container">
        <div class="select">
          <label for="videoSource">Video Source: </label><select id="videoSource"></select>
        </div>

        <video muted autoplay></video>

        <script type="text/javascript" src="js/camera.js"></script>
      </div>
    ';
    return $str;
  }

  function cam($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    return $str;
  }

  
function setup_tim() {
		$str = "
		<script>
		
			function inc_time(itm, inc) {
				curr_time = document.getElementById(itm).selectedIndex
				var mins, hours
				var d = new Date();
				
				if(inc == \"p\") {
					curr_time++
					if(curr_time >= 60) {
						curr_time = 0
					}
					document.getElementById(itm).selectedIndex = curr_time
				
					} else if(inc == \"m\") {
					curr_time--
					if(curr_time < 0) {
						curr_time = 59
					}
					document.getElementById(itm).selectedIndex = curr_time
				} else {
					document.getElementById(itm + '_minutes').selectedIndex = d.getMinutes()
					document.getElementById(itm + '_hours').selectedIndex = d.getHours()
					document.getElementById(itm).value = d.getHours()
				}
				
				if(inc == \"p\" || inc == \"m\") {
					mins = document.getElementById(itm).selectedIndex
					hours =	document.getElementById(itm.replace('_minutes', '_hours')).selectedIndex
					if(mins < 10) mins = '0' + mins
					if(hours < 10) mins = '0' + hours
					document.getElementById(itm.replace('_minutes', '')).value = hours + ':' + mins
				} else {
					mins = document.getElementById(itm + '_minutes').selectedIndex
					hours =	document.getElementById(itm + '_hours').selectedIndex
					if(mins < 10) mins = '0' + mins
					if(hours < 10) mins = '0' + hours
					document.getElementById(itm).value = hours + ':' + mins
				}
				
			}

		</script>
		";
		
		return $str;
	}
	 
function tim($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql, $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
		if(!$_REQUEST['hdnAction']) $xtra = "";
		$str .= '<input type="hidden" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />';

    $str .= '<select onChange="update_time_' . $field_id . '(\''.$field_id.'\')" style="width: 50px;" id="' . $field_id . '_hours" name="' . $field_id . '_hours" ' . $field_xtra . ' />';
		for($x = 0; $x < 24; $x++) {
		  if($x < 10) $x = "0$x";
		  $str .= "<option>$x</option>";
		}
		$str .= "</select>";
    $str .= '<select onChange="update_time_' . $field_id . '(\''.$field_id.'\')" style="width: 50px;" id="' . $field_id . '_minutes" name="' . $field_id . '_minutes" ' . $field_xtra . ' />';
		for($x = 0; $x < 60; $x++) {
		  if($x < 10) $x = "0$x";
		  $str .= "<option>$x</option>";
		}
		$str .= "</select> <input class=\"subtle_button\" type=\"button\" onClick=\"inc_time('$field_id"."_minutes', 'p')\"  value=\"+\" /><input class=\"subtle_button\" type=\"button\" onClick=\"inc_time('$field_id"."_minutes', 'm')\" value=\"-\" /><input class=\"subtle_button\" title=\"Click here to set to the current time.\" type=\"button\" onClick=\"inc_time('$field_id', 'n')\" value=\"T\" />";

    if(!$xtra) $xtra = date('H:i');
		$str .= "
			<script>

				str = '$xtra'
				document.getElementById('$field_id"."_hours').selectedIndex = str.substring(0, 2);
				document.getElementById('$field_id"."_minutes').selectedIndex = str.substring(3, 5);
				document.getElementById('$field_id').value = str;

				function update_time_$field_id(field_id) {
					document.getElementById(field_id).value = document.getElementById(field_id + '_hours').value + ':' + document.getElementById(field_id + '_minutes').value
					//alert('Val: ' + document.getElementById(field_id).value)
						//str = document.getElementById('hdn' + field_id).value;
				}
				function update_dropdowns_$field_id(field_id) {
					document.getElementById(field_id).value = document.getElementById(field_id + '_hours').value + ':' + document.getElementById(field_id + '_minutes').value
					//alert('Val: ' + document.getElementById(field_id).value)
				}
			</script>
		";


    return $str;
  }

  function setup_ti2(){
		$str .= "
			<script>
				function get_curr_time(itm) {
					var mins, hours
					var d = new Date();
				
					mins = d.getMinutes()
					hours = d.getHours()
					if(mins < 10) mins = '0' + mins.toString()
					if(hours < 10) hours = '0' + hours.toString()
					itm.value = hours + \":\" + mins
				}
				function validate_time(itm) {
					var vmsg = ''
					var intRegex = /^\d+$/;
					var timeRegex = /^([0-9]|0[0-9]|1?[0-9]|2[0-3]):[0-5]?[0-9]$/;
					ttime = itm.value
				  strtime = ttime.toString()
					
					if(intRegex.test(ttime) || timeRegex.test(ttime)) {
						if(intRegex.test(ttime)) {
							//ttime = parseInt(ttime)
							if(strtime.substring(0,1) == '0') {
								strtime = strtime.substring(1)
                ttime = strtime
              }

							if(strtime.substring(0,2) == '00') {
								ttime = strtime.substring(0,2) + ':' + strtime.substring(2,4)
								if(ttime.length < 5) ttime += '0';
								if(ttime.length < 5) ttime += '0';
							} else if(strtime.substring(0,1) == '0') {
								ttime = '0' + strtime.substring(0,1) + ':' + strtime.substring(1,3)
								if(ttime.length < 5) ttime += '0';
								if(ttime.length < 5) ttime += '0';
							}
							
							if(ttime < 10) {
								ttime = '0' + ttime + ':' + '00'
							} else if(ttime < 24) {
								ttime = ttime + ':' + '00'
							} else if(ttime < 100) {
								ttime = '0' + strtime.substring(0,1) + ':' + strtime.substring(1,2) + '0'
							} else if(ttime < 1000) {
								ttime = '0' + strtime.substring(0,1) + ':' + strtime.substring(1,3)
							} else if(ttime < 2360) {
								ttime = strtime.substring(0,2) + ':' + strtime.substring(2,4)
							}
							if(!timeRegex.test(ttime)) {
								vmsg = 'Please enter a valid time or number.'
							}
							
							if(!vmsg) itm.value = ttime
						}
					} else if(ttime) {
						vmsg = 'Please enter a valid time or number.'
					}
					if(vmsg) {
						alert(vmsg)
            itm.value = ''
						itm.focus()
					}
				}
				
			</script>
		";

    return $str;
	}
	
	//A text based time field
  function ti2($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    if(strstr($field_id, "|") !== false) {
      $field_ids = explode('|', $field_id);
      $field_id = $field_ids[0];
      $field_name = $field_ids[1];
    } else {
      $field_name = $field_id;
    }
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
    if($field_text == "yes") $xtra = date("H:i");
    //echo "<h3>$field_text, $field_value, $field_xtra</h3>";
    $str = '<input onBlur="validate_time(' . $field_id . '); '.$this->blur_xtra.'" type="text" ' . ($this->placeholder ? ' placeholder="'.$title.'" title="'.$title.'" ' : '') . ' id="' . $field_id . '" name="' . $field_name . '" ' . $field_xtra . ' value="' . $xtra . '" />
    ';
//    <a class="list_a" uk-tooltip="title: Click Here to Set to Current Time." style="margin-left: -4px; padding-left: 2px; padding-right: 6px;" href="JavaScript:get_curr_time(' . $field_id . ')">&lt;T</a>    
		
    return $str;
  } 
	
	
  //Directory drop-down list. The directory is defined in $field_value
  function dir($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $str = '<select id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $field_text . '">' . "\n";
    $dirFiles = array();
    if ($handle = opendir($sql)) {
      while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != "index.php" && $file != "Thumbnails") {
          $dirFiles[] = $file;
        }
      }
      closedir($handle);
    }
    sort($dirFiles);
    $str .= "<option value=\"\">--- Select ---</option>";
    foreach($dirFiles as $file) {
      $str .= "<option title=\"$file\">$file</option>\n";
    }
    $str .= '</select>';
    return $str;
  }

  function setup_cms() {
    $str = '<script type="text/javascript" src="'.$this->f3->get('js_folder').'ckeditor/ckeditor.js"></script>';
    return $str;
  }
  function cms($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    if(!$field_xtra) $field_xtra = 'height: "300",	width: "850"';
    $str .= $this->setup_cms();
    $str .= '
      <textarea id="' . $field_id . '" name="' . $field_id . '">' . $field_text . '</textarea>
      <script type="text/javascript">
        CKEDITOR.replace( "' . $field_id . '",
        {
        '.$field_xtra.'
        }
        );
      </script>
      ';

    return $str;
  }
	//A time and date field
//      <script type="text/javascript" src="'.$this->f3->get('js_folder').'/datetimepicker/jquery.js"></script>

  function setup_tad() {
    $str = '
      <link rel="stylesheet" type="text/css" href="'.$this->f3->get('js_folder').'datetimepicker/jquery.datetimepicker.css"/>
      <script type="text/javascript" src="'.$this->f3->get('js_folder').'datetimepicker/jquery.datetimepicker.js"></script>
      ';
    return $str;
  }
  function tad($field_id = "", $field_text = "", $field_xtra = "", $field_value = "", $dbi = "", $sql = "", $title = "") {
    $xtra = (($_REQUEST[$field_id] && $_REQUEST['hdnAction'] != "new_record") ? $_REQUEST[$field_id] : $field_text);
//readonly  //2015/04/15 05:06
//          value:\'\'
    if($field_text){
        $currentDateTime = $field_text; 
    }else{
        $currentDateTime = date("d-M-Y H:i"); 
    }
    
    $str .= '      
      <input autocomplete="off"  type="text" id="' . $field_id . '" name="' . $field_id . '" ' . $field_xtra . ' value="' . $xtra . '" />
      <script>
     
      
        $(\'#'.$field_id.'\').datetimepicker({
          format: \'d-M-Y H:i\',
          value: \''.$currentDateTime.'\'
          });
      </script>
      ';
    return $str;
  }

  //A List of Buttons
  function button_list() {
      
    $edit = (isset($_POST['idin']) ? $_POST['idin'] : null);
    for($x = 0; $x < count($this->button_attributes[0]); $x++) {
        
      $button_value = $this->button_attributes[0][$x];
      $button_name = $this->button_attributes[1][$x];
      if($this->button_attributes[2][$x]) {
//        if($this->button_attributes[2][$x] == "this.form.reset(); new_record(); this.form.submit();") $this->button_attributes[2][$x] = "window.location=window.location.href;";
        if($this->button_attributes[2][$x] == "this.form.reset(); new_record(); this.form.submit();") $this->button_attributes[2][$x] = "window.location=location.protocol + '//' + location.host + location.pathname + location.search;";
        $button_js = 'formnovalidate Onclick="' . $this->button_attributes[2][$x] . '"';
      } else {
        $button_js = " formnovalidate ";
      }
      $button_type = $this->button_attributes[4][$x];
      $button_class = (isset($this->button_attributes[5][$x]) ? $this->button_attributes[5][$x] : "");
//$str .= "bt: $button_type, test: " . count($this->button_attributes[0]);
      if(!$button_type) {
        $button_type = "button";
      }
			$tst = strtolower($button_name);
			$allow_view = 1;
                        
			if($_SESSION['page_access'] == 1 || $_SESSION['page_access'] == 3) {
				if($tst == "cmdadd" || $tst == "cmdsave") {
					$allow_view = 0;
				}
			}
			if($_SESSION['page_access'] == 5) {
				if($tst == "cmdadd" || ($tst == "cmdsave" && !isset($_REQUEST["idin"]))) {
					$allow_view = 0;
				}
			}

      //if($allow_view) $str .= '<input ' . $button_js . ' id="' . $button_name . '" type="' . $button_type . '" value="' . $button_value . '" class="' . $button_class . '" />';
			//echo $button_name;
      
      if($allow_view) {
        if($button_value == 'Add New' || $button_value == 'Save') {
          if($edit && $button_value == 'Add New') $button_value = 'Add as New';
          if(!$edit && $button_value == 'Add New') $button_value = 'Add';
          if(!$edit && $button_value == 'Save') $button_value = '';
          if($edit && $button_value == 'Add as New' && $this->hide_duplicate) $button_value = '';
        }
        $button_str = ($button_value ? '<input ' . $button_js . ' id="' . $button_name . '" type="' . $button_type . '" value="' . $button_value . '" class="' . $button_class . '" />' : '');
        if($button_value == 'Add New') {
          $add_btn = $button_str;
        } else if($button_value == 'Save') {
          $save_btn = $button_str;
        } else {
          //if(!($button_name == "cmdFilter" && $edit)) 
          $str .= $button_str;
        }
      }
    }    
    if($add_btn || $save_btn) {
      $str = $save_btn . $add_btn . $str;
    }
    return $str;
  } 

  //A List of Links
  function links_list() {
    $str = '<table style="border-spacing:0; border-collapse: collapse;"><tr>';
    for($x = 0; $x < count($this->link_attributes[0]); $x++) {
      $link_text = $this->link_attributes[0][$x];
      $link_name = $this->link_attributes[1][$x];
      $a_css = $this->link_attributes[2][$x];
      $css_default = $this->link_attributes[3][$x];
      $css_selected = $this->link_attributes[4][$x];
      $css = (($_REQUEST['pg']-1) == $x ? $css_selected : $css_default);
      $str .= '<td class="' . $css . '"><a class="' . $a_css . '" href="' . $link_name . '">' . $link_text . '</a></td>';
    }    
    $str .= "</tr></table>";
    return $str;
  } 

}

?>