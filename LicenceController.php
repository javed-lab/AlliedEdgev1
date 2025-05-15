<?php
class LicenceController extends Controller {
  protected $f3;
  var $verify_mode = 0, $allow_edit = 1, $show_expired = 0, $show_deactivated = 0, $nearly_expired = 0, $my_licences = 0,$subString = 0;
  var $action;
  function __construct($f3) {
    $this->action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->db_init();
  }
  
  
  
  function MyLicences() {
    $this->my_licences = 1;
    $this->subString = 1;
    return $this->LicenceManagement();
  }
  
  function VerifyLicences() {    
    $this->verify_mode = 1;
    $this->subString = 1;
    return $this->LicenceManagement();
    //$this->
  }

  function InactiveLicences() {
    $this->show_deactivated = 1;
    $this->subString = 1;
    return $this->LicenceManagement();
  }

  function NearlyExpiredLicences() {
    $this->nearly_expired = 1;
    $this->subString = 1;
    return $this->LicenceManagement();
  } 

  function LicenceManagement(){
      
    
    $verify_mode = $this->verify_mode;
    $allow_edit = $this->allow_edit;
    $show_expired = $this->show_expired;
    $show_deactivated = $this->show_deactivated;
    $nearly_expired = $this->nearly_expired;
    $my_licences = $this->my_licences;
    $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
     $emailAction = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
    
    
    
    
    
    
    
    
    $flder = $this->f3->get('download_folder') . "licences/";
    $del_photo = (isset($_POST['del_photo']) ? $_POST['del_photo'] : null);
    $edit_id = (isset($_POST['idin']) ? $_POST['idin'] : null);
    $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : ($my_licences ? $_SESSION['user_id'] : null));
    if(isset($_POST['hdnImage1'])) $img = $_POST['hdnImage1'];
    if(isset($_POST['hdnImage2'])) $img2 = $_POST['hdnImage2'];
    
    if(isset($_REQUEST['show_min']) && $_REQUEST['show_min'] == 1){
          $ajaxlink = '?show_min=1&lookup_id='.$lookup_id;
      }else{
          $ajaxlink = "";
      }

    $hr_user = $this->f3->get('hr_user');
    $additional_fields = "";
  // pr($_REQUEST);
    $verify_id = (isset($_GET['verify_id']) ? $_GET['verify_id'] : null);
    $activation_id = (isset($_GET['activation_id']) ? $_GET['activation_id'] : null);

    if($verify_id) {        
        //$user_id = $this->get_sql_result("select user_id as `result` from licences where id = $verify_id;");
        
        if(!file_exists($flder.$verify_id."/licence.jpg")){
            echo "no_img";
            exit;
        }
        
      $is_verified = $this->get_sql_result("select verified_by as `result` from licences where id = $verify_id;");
      
      $sql = "update licences set verified_by = " . ($is_verified ? 0 : $_SESSION['user_id']) . " where id = $verify_id;";
      $this->dbi->query($sql);
      echo ($is_verified ? "" : $this->get_sql_result("select concat(name, ' ', surname) as `result` from users where id = " . $_SESSION['user_id']));
      exit;
    } else if($activation_id) {
      $is_deactivated = $this->get_sql_result("select deactivated as `result` from licences where id = $activation_id;");
      $sql = "update licences set deactivated = " . ($is_deactivated ? 0 : 1) . " where id = $activation_id;";
      $this->dbi->query($sql);
      echo ($is_deactivated ? "Disable Iicense" : "Enable License");
      exit;
    }
    
    if($del_photo) {
      
      $parts = explode("_", $del_photo);
      if($this->get_sql_result("select user_id as `result` from licences where id = {$parts[0]}") == $_SESSION['user_id']) {
        $sql = "update licences set verified_by = 0 where id = {$parts[0]}";
        $this->dbi->query($sql);
      }
      $del = $flder . $parts[0] . "/licence" . ($parts[1] == 2 ? "2" : "") . ".jpg";
      if(file_exists($del)) {
          $sql = "update licences set verified_by = 0 where id = {$parts[0]}";
          $this->dbi->query($sql);
            unlink($del);
        $str .= $this->message("Photo Deleted...", 2000);
        if($parts[1] == 1) {
          $test = $flder . $parts[0] . "/licence2.jpg";
          $rename_to = $flder . $parts[0] . "/licence.jpg";
          if(file_exists($test)) rename($test, $rename_to);
        }
      }
      
    }
   
   
    $str .= '
    <style>
    .tab_item {
      float: left;
      border: 2px solid black;
      padding: 0px;
      display: inline-block;
      margin-right: 10px;
      margin-top: 15px;
    }
    .tab_heading {
      float: left;
      padding-left: 12px;
      padding-right: 12px;
      padding-top: 6px;
      padding-bottom: 6px;
      display: inline-block;
      margin-right: 10px;
      margin-top: 15px;
    }
    .tab_item:hover {
      border-color: blue !important;
    }
    .tab_a {
      padding-left: 12px;
      padding-right: 12px;
      padding-top: 6px;
      padding-bottom: 6px;
      display: inline-block;
    }
    .tab_a:hover {
      background-color: #FFFFCC !important;
    }
    .tab_selected {
      border: 2px solid red;
      background-color: #FFFFEE;
    }
    
    .img_container {
        position: relative;
        width:120px!important;
        height:120px!important;
        overflow:hidden!important;
    }

    .img_container img {
      width: 100%!important; /* Make the image fill the container */
      height: auto!important; /* Maintain aspect ratio */
      display: block!important; /* Remove default inline behavior */
  }

  .list_a {
    -webkit-box-sizing: content-box;
    -moz-box-sizing: content-box;
    box-sizing: content-box;
    display: inline-block;
    width: 88%;
    height: 15%;
    position: absolute;
    top: 0;
    left: 0;
    text-align: center;
    padding-top: 3px;
    padding-bottom: 3px;
    justify-content: center!important;
    padding-left: 6px;
    padding-right: 6px;
    text-decoration: none;
    color: white;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    font-weight: normal !important;
    font-size: 10px !important;
    border: 1px solid #DDDDDD;
    object-position: center!important;
    text-align: center!important;
    align-items: center!important;
    align-content: center!important;
    margin-top: 35%!important;
}

/* Show the button when hovering over the container */
.img_container:hover .list_a {
    opacity: 1;
}

    .topright {
        position: absolute;
        top: 0px;
        right: 0px;
    }
    .op_button {
      color: black;
      padding: 2px;
      background-color: rgba(255,255,255,0.7);
      border: 1px solid rgba(128,128,128,0.7);
    }
    .op_button:hover {
      text-decoration: none;
      background-color: #FFFFCC;
      border: 1px solid #DDD;
    }
    .redBorder{
    border:2px solid red; 
}
 .greenBorder{
    border:2px solid green; 
}

input[type=button], input[type=submit], .button_a {
  -webkit-appearance: none;
  color: white;
  background-color: #242E48;
  border: 1px solid #242E48;
  margin-right: 15px;
  padding: 8px 14px 8px 14px;
  font-size: 11pt;
  font-weight: normal !important;
}


.addNewLicenceBtn {
  margin-left: 20px!important;
  padding: 7.5px!important;
  background-color: #fff!important;  
  border-radius: 7.5px!important;

}
}

    </style>
    <script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
    <script type="text/javascript">

    var canvas, ctx, pfix_in

    oFReader = new FileReader(), 
    rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
    oFReader.onload = function (oFREvent) {

      var img=new Image();
      img.onload=function(){
          canvas = document.getElementById(canvas);
          pfix = pfix_in
          canvas.style.height = "auto";
          ctx=canvas.getContext("2d");

          //alert("Width: " + img.width + ", Height: " + img.height)
          //alert("Nat Width: " + img.naturalWidth + ", Nat Height: " + img.naturalHeight)

          var MaxWidth = 1000
          //alert(img.width)
          if(img.width >= MaxWidth) {
            canvas.width = MaxWidth
            canvas.height = (img.height / img.width) * MaxWidth
          } else {
            canvas.width = img.width
            canvas.height = img.height
          }
          
          ctx.drawImage(img,0,0,img.width,img.height,0,0,canvas.width,canvas.height);

          //document.getElementById("uploadPreview").src = canvas.toDataURL();  <h3>Image Preview</h3>
          document.getElementById("hdnImage" + pfix).value = canvas.toDataURL("image/jpeg",0.8);

      }
      img.src=oFREvent.target.result;

    };

    function submit_form() {
      document.getElementById("submit").click()
    }

    function getOrientation(file, callback) {
      var reader = new FileReader();
      reader.onload = function(e) {

      var view = new DataView(e.target.result);
      if (view.getUint16(0, false) != 0xFFD8) {
        return callback(-2);
      }
      var length = view.byteLength, offset = 2;
      while (offset < length) {
        if (view.getUint16(offset+2, false) <= 8) return callback(-1);
        var marker = view.getUint16(offset, false);
        offset += 2;
        if (marker == 0xFFE1) {
          if (view.getUint32(offset += 2, false) != 0x45786966) {
            return callback(-1);
          }

          var little = view.getUint16(offset += 6, false) == 0x4949;
          offset += view.getUint32(offset + 4, little);
          var tags = view.getUint16(offset, little);
          offset += 2;
          for (var i = 0; i < tags; i++) {
            if (view.getUint16(offset + (i * 12), little) == 0x0112) {
                return callback(view.getUint16(offset + (i * 12) + 8, little));
            }
          }
        }
        else if ((marker & 0xFF00) != 0xFF00) {
          break;
        } else { 
          offset += view.getUint16(offset, false);
        }
      }
      return callback(-1);
      };
      reader.readAsArrayBuffer(file);
    }
    function loadImageFile(pfix="") {
      pfix_in = pfix
      file_uploader = "fileToUpload" + pfix
      if (document.getElementById(file_uploader).files.length === 0) { return; }
      var oFile = document.getElementById(file_uploader).files[0];
      if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }
      document.getElementById("hdnFileName" + pfix).value = document.getElementById(file_uploader).value
      canvas = "myCanvas" + pfix
      oFReader.readAsDataURL(oFile);
      getOrientation(oFile, function(orientation) {
        ori = orientation;
        if(ori == 3 || ori == 6 || ori == 8) {
          if(ori == 3) {
            rotate_direction = "FLIP"
          } else if(ori == 6) {
            rotate_direction = "LEFT"
          } else if(ori == 8) {
            rotate_direction = "RIGHT"
          }
          document.getElementById("rotate" + pfix).value = rotate_direction
        }
      });
    }


    function del_photo(id) {
      var confirmation;
      confirmation = "Are you sure about deleting this file?";
      if (confirm(confirmation)) {
        document.getElementById("del_photo").value = id
        document.frmEdit.submit();
      }
    }
    function hide_del(id) {
      element = document.getElementById(id);
      if (typeof(element) != "undefined" && element != null) {
        document.getElementById(id).style.display=\'none\';
      }
    }

    function verify_licence(id) {
      $.ajax({
        type:"get",
            url:"'.$this->f3->get('main_folder').'Licencing/VerifyLicences",
            data:{ verify_id: id } ,
            success:function(msg) {
            if(msg == "no_img"){
                alert("Document not exist for Verify");
            }else{
                  document.getElementById(id + "-7").innerHTML = msg
                 // document.getElementById("a" + id).innerHTML = (msg ? "Unverify Licence Details" : "Verify Licence Details")
                $("#a" + id).parent("div").removeClass("redBorder").addClass("greenBorder");
                document.getElementById("a" + id).remove();
            }
            
            }
      } );
    }

    function licence_activation(id) {
      $.ajax({
        type:"get",
            url:"'.$this->f3->get('main_folder').'Licencing/VerifyLicences",
            data:{ activation_id: id } ,
            success:function(msg) {
              document.getElementById("r" + id).innerHTML = msg
            }
      } );
    }
    

    </script>
    ';
    $del_string = "'<a id=\"', licences.id, '_1\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_1\')\">x</a>'";
    $del_string2 = "'<a id=\"', licences.id, '_2\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_2\')\">x</a>'";
    $additional_fields = "";
    //if($hr_user && $allow_edit) {
    if(1){    
      if(!$my_licences) {
        $show_edit = "
        , CONCAT('<a id=\"r', licences.id, '\" class=\"list_a\" href=\"JavaScript:licence_activation(', licences.id, ')\">', if(licences.deactivated = 0, 'Disa', 'Ena'), 'ble License</a>') as ` `
        ";
        
         $show_verify = "
       , CONCAT('<a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\">', if(licences.verified_by = 0, 'V', 'Unv'), 'erify Licence</a>') as `Licence Verification`
        ";
        
        
//         if(!$my_licences) {
//        $show_deactivated = "
//        , CONCAT('<a id=\"r', licences.id, '\" class=\"list_a\" href=\"JavaScript:licence_activation(', licences.id, ')\">', if(licences.deactivated = 0, 'Dea', 'A'), 'ctivate Licence</a>') as ` `
//        ";
//        
//        $show_verify = "
//        , CONCAT('<a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\">', if(licences.verified_by = 0, 'V', 'Unv'), 'erify Licence</a>') as `Licence Verification`
//        ";
        
      }
      $show_edit .= ",'Edit' as `*` , 'Delete' as `!`"; //
    }
    
    $srchUserDivision =  $_REQUEST['srch_userLicDivision'];
    
    
   if (isset($_POST['hdnFilter']) && $_POST['hdnFilter'] != "" ){
       //prd($_POST['hdnFilter']);
                $filter_string = "filter_string";
   }
   else{
       $filter_string = "and user_status.item_name = 'ACTIVE'";
   }
    
//    if($manage_mode) {
//      $filter_string .= ($exp_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.expiry_date ' . ($exp_view == 1 ? '<' : '>=') . ' now() ' : '');
//      $filter_string .= ($active_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.deactivated = ' . ($active_view == 1 ? '1' : '0') : '');
//      $filter_string .= ($verify_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.verified_by ' . ($verify_view == 1 ? '=' : '!=') . '0' : '');
//   }
    if($lookup_id) $filter_string .= ($filter_string ? ' and ' : ' where ') . " licences.user_id = $lookup_id and deactivated = 0";
    if(!$show_inactive) $filter_string .= ($filter_string ? ' and ' : ' where ') . " user_status.item_name = 'ACTIVE'";
    //$username = "amer";

   // if(file_exists)
  
    //die;
     $subqueryDivision = "(SELECT GROUP_CONCAT(' ',com.item_name) FROM lookup_answers la 
LEFT JOIN companies com ON com.id = la.lookup_field_id
WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `Division` ,";
     //and sla.lookup_field_id in (".$loginUserDivisions.") 
     if($srchUserDivision){
        $divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id  and sla.lookup_field_id = '".$srchUserDivision."'";
        $srchFilterSql = ' and (sla.id IS NOT NULL) ';
     }
     else{
         $divisionFilterSql = "";
        $srchFilterSql = ''; 
     }
     
     
     
     $this->list_obj->sql = "
     select 
         licences.id as idin,
         " . ($my_licences ? "" : "CONCAT('<a href=\"".$this->f3->get('main_folder')."Licencing/LicenceManagement?lookup_id=', users.id, '\">', users.name, ' ', users.surname, '</a>'" . ($show_inactive ? ", '<br />', user_status.item_name" : "") . ", '<br/>', CASE WHEN users.phone IS NULL then  '' else users.phone END) as `Licence Holder`, ") . "
         $subqueryDivision
         licence_types.item_name as `Licence Type`,
         licences.licence_number as `Licence Number`,
         CONCAT(licences.licence_class, if(licence_compliance_id = '', '', CONCAT('<br/>', lookup_fields.item_name))) as `Class/Compliance`,
         concat(licences.expiry_date, '<div style=\"color: ',
             if(DATEDIFF(licences.expiry_date, now()) <= 0,
                 CONCAT('red\">Expired ', if(DATEDIFF(licences.expiry_date, now()) = 0,
                     CONCAT('Today!'),
                     CONCAT(ABS(DATEDIFF(licences.expiry_date, now())), ' Days Ago'))),
                 if(DATEDIFF(licences.expiry_date, now()) > 0 and DATEDIFF(licences.expiry_date, now()) <= 30,
                     'orange\">',
                     'green\">')
             ), DATEDIFF(licences.expiry_date, now()), ' Days</div>') as `Expired`,
         states.item_name as `State Issued`,
         CONCAT(users2.name, ' ', users2.surname) as `Verified By`,
         CONCAT('
             <a target=\"_blank\" href=\"".$this->f3->get('main_folder')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_1\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('main_folder')."Image?no_crypt=1&i=licences/', licences.id, '/licence.jpg\"></a>"
             . "',if(licences.verified_by = 0,CONCAT('</br><a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\" >Verify Licence Details </a>',''), '') ," 
             . "'<div class=\"topright\">', $del_string, '</div></div>') as `Photo 1`,
         CONCAT('
             <a target=\"_blank\" href=\"".$this->f3->get('main_folder')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_2\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('main_folder')."Image?no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"></a>"
             . "<div class=\"topright\">', $del_string2, '</div></div>') as `Photo 2`
         $show_edit
     FROM licences
     left join users2 on users2.id = licences.verified_by
     left join licence_types on licence_types.id = licences.licence_type_id
     left join users on users.id = licences.user_id
     left join states on states.id = licences.state_id
     left join user_status on user_status.id = users.user_status_id
     left join lookup_fields on lookup_fields.id = licences.licence_compliance_id
     $divisionFilterSql
     where 1
         " . ($verify_mode ? " and licences.expiry_date >= now() " : "") . "
         " . ($my_licences ? " and users.id = " . $_SESSION['user_id'] : " and user_status.item_name = 'ACTIVE' ") . "
         " . ($show_deactivated ? " and licences.deactivated = 1 " : " and licences.deactivated = 0 ") . "
         " . ($nearly_expired ? " and DATEDIFF(licences.expiry_date, now()) <= 0 " : "") . "
         " . ($lookup_id ? " and users.id = " . $lookup_id : "") . "
         $srchFilterSql
         $filter_string
     order by users.name, DATEDIFF(licences.expiry_date, now())
 ";
 
 
    
//    echo  $this->list_obj->sql;
//    die;
    
$forMail= "
select 
    licences.id as idin,
    " . ($my_licences ? "" : "CONCAT(users.name, ' ', users.surname) as `Licence Holder1`, ") . "      
    licence_types.item_name as `Licence Type`, 
    licences.licence_number as `Licence Number`,
    CONCAT(licences.licence_class, if(licence_compliance_id = '', '', CONCAT('<br/>', lookup_fields.item_name))) as `Class/Compliance`, 
    concat(licences.expiry_date, 
        '<div style=\"color: ',
        if(DATEDIFF(licences.expiry_date, now()) <= 0,
            CONCAT('red\">Expired ', if(DATEDIFF(licences.expiry_date, now()) = 0,
                CONCAT('Today!'),
                CONCAT(ABS(DATEDIFF(licences.expiry_date, now())), ' Days Ago'))),
            if(DATEDIFF(licences.expiry_date, now()) > 0 and DATEDIFF(licences.expiry_date, now()) <= 30,
                'orange\">',
                'green\">')
        ), DATEDIFF(licences.expiry_date, now()), ' Days</div>') as `Expired`,
    states.item_name as `State Issued`, 
    CONCAT(users2.name, ' ', users2.surname) as `Verified By`,
    CONCAT('
        <a target=\"_blank\" href=\"".$this->f3->get('full_url')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\">
        <img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_1\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('full_url')."Image?no_crypt=1&i=licences/', licences.id, '/licence.jpg\"></a>"
        . "') as `Photo 1`,
    CONCAT('
        <a target=\"_blank\" href=\"".$this->f3->get('full_url')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_2\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('full_url')."Image?no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"></a>"
        . "') as `Photo 2`,
    $show_edit
FROM licences
left join users2 on users2.id = licences.verified_by
left join licence_types on licence_types.id = licences.licence_type_id
left join users on users.id = licences.user_id
left join states on states.id = licences.state_id
left join user_status on user_status.id = users.user_status_id
left join lookup_fields on lookup_fields.id = licences.licence_compliance_id
where 1       
" . ($verify_mode ? " and licences.expiry_date >= now() " : "") . "
" . ($my_licences ? " and users.id = " . $_SESSION['user_id'] : " and user_status.item_name = 'ACTIVE' ") . "
" . ($show_deactivated ? " and licences.deactivated = 1 " : " and licences.deactivated = 0 ") . "
" . ($nearly_expired ? " and DATEDIFF(licences.expiry_date, now()) <= 0 " : "") . "
" . ($lookup_id ? " and users.id = " . $lookup_id : "") . "
$filter_string
order by users.name, DATEDIFF(licences.expiry_date, now())

";

    
   
//   echo $forMail;
//   die;
    
    if($emailAction == "sendLicenseEmail"){
        $email = $_REQUEST['email'];
        $user_id = $_REQUEST['userId'];        
        $this->sendLicenseEmail($forMail,$email,$user_id);
        echo 1;
        die;
        
    }
    
   
    
    //  " . ($verify_mode ? " and licences.verified_by = 0 and licences.expiry_date >= now() " : "") . "
    
//    echo  $this->list_obj->sql;
//    die;
  
    //return "<textarea>{$this->list_obj->sql}</textarea>";
        /*if($hr_user && $lookup_id != $_SESSION['user_id']) {
          $this->editor_obj->custom_field = "verified_by";
          $this->editor_obj->custom_value = $_SESSION['user_id'];
        }*/
       // $this->editor_obj->xtra_id_name = "user_id";
       // $this->editor_obj->xtra_id = $lookup_id;
        $this->editor_obj->table = "licences";
          $style = 'class="full_width"';
          $this->editor_obj->xtra_validation = '
         

            var test_date;
            obj = document.getElementById(\'calExpiryDate\').value
            if(obj) {
              dt = moment(obj, "DD-MMM-YYYY");
              //today = moment().format("DD-MMM-YYYY")
              today = moment().format("YYYY-MM-DD")
              if(moment(dt).isAfter(moment(today)) === false || moment(dt).isSame(moment(today)) === true) {
                err++;
                error_msg += err + ". Please select a date that is after today.\n";
              }
            }
            

';
          $str .= '
            <script>
              function config_questions() {
                var d = document.getElementById("selLicenceType");
                var itm = document.getElementById("licence_class");
                var str = d.options[d.selectedIndex].text;
                var s = str.search(/licence/i);
                if(s !== -1) {
                  itm.style.display="inline"
                } else {
                  itm.style.display="none"
                }
              }
              function validateEmail(email) {
          var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
          return re.test(email);
        }
        function SendLicenseEmail(userId) {
          // Assuming you have the expiry date available in your HTML, adjust the selector accordingly
          var expiryDate = $("#expiry_date_" + userId).text();
          
          // Calculate days left to expiry
          var daysLeft = calculateDaysLeft(expiryDate);
      
          // Check if the license is 30 or 14 days away from expiry
          if (daysLeft === 30 || daysLeft === 14) {
              // Send email to hr@alliedmanagement.com.au
              $.ajax({
                  type: "get",
                  url: "' . $main_folder . '/Licencing/LicenceManagement",
                  data: { user_id: userId, action: "sendLicenseEmail", show_min: 1, lookup_id: userId, email: "hr@alliedmanagement.com.au" },
      
                  success: function (msg) {
                      if (msg == 1) {
                          alert("Email has been sent to hr@alliedmanagement.com.au");
                      }
                  }
              });
          }
      }
      // Function to calculate days left to expiry
function calculateDaysLeft(expiryDate) {
    var currentDate = new Date();
    var expiryDateObj = new Date(expiryDate);
    var timeDiff = expiryDateObj.getTime() - currentDate.getTime();
    var daysLeft = Math.ceil(timeDiff / (1000 * 3600 * 24));

    return daysLeft;
}
              
            </script>
          ';
          $this->editor_obj->form_attributes = array(
            array("selLicenceType", "txtLicenceNumber", "txtLicenceClass", "calExpiryDate", "selState", "selCompliance", "selUserIds"),
            array("Licence Type", "Licence/Certificate Number", "Licence Class (e.g. 1AC)", "Expiry Date", "State Issued", "Compliance (Mandatory)", "User"),
            array("licence_type_id", "licence_number", "licence_class", "expiry_date", "state_id", "licence_compliance_id", "user_id"),
            array($this->get_simple_lookup('licence_types'), "", "", "", $this->get_simple_lookup('states'), $this->get_lookup('licence_compliance'), $this->licenceUser($lookup_id)),
            array($style . ' onChange="config_questions()"', $style, $style, $style, $style, $style, $style),
            array("c", "c", "n", "c", "c", "c", 'c')
        );
        $this->editor_obj->hide_duplicate = 1;
        

          
          
          $this->editor_obj->button_attributes = array(
            array("Add New",    "Save",   "Reset",   "Filter"),
            array("cmdAdd",     "cmdSave", "cmdReset", "cmdFilter"),
            array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit()", "filter()"),
            array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        
      $img_file1 = "$flder$edit_id/licence.jpg";
      $img_file2 = "$flder$edit_id/licence2.jpg";
      $show_img1 = "licences/$edit_id/licence.jpg";
      $show_img2 = "licences/$edit_id/licence2.jpg";
      $this->editor_obj->form_template = '
      iframe_item
                <input type="hidden" name="del_photo" id="del_photo" />
                <input type="hidden" name="hdnFileName1" id="hdnFileName1" />
                <input type="hidden" name="hdnFileName2" id="hdnFileName2" />
                <input type="hidden" name="hdnImage1" id="hdnImage1" />
                <input type="hidden" name="hdnImage2" id="hdnImage2" />
                <input type="hidden" name="rotate1" id="rotate1" />
                <input type="hidden" name="rotate2" id="rotate2" />
                <div class="fl med_textbox"><nobr>tselLicenceType</nobr><br />selLicenceType</div>
                <div class="fl med_textbox"><nobr>tselUserIds</nobr><br />selUserIds</div>
               <div class="fl small_textbox">
               <nobr>User Division *</nobr>
               <select name="srch_userLicDivision" id="srch_userLicDivision">
                        <option value=""> Select Division </option>'; 
      
       $division_array = ['108' => 'ASM - Security','2100' => 'AFM - Facilities','2102' => 'APM - Pest Control','2103' => 'ACM - Civil','2104' => 'ATM - Traffic'];
       
       foreach($division_array as $key => $divisionValue){
//           echo $_REQUEST['srch_userLicDivision'];
//           die;
           if($_REQUEST['srch_userLicDivision'] == $key){
               $selected = "selected = 'selected'";
           }else{
               $selected = "";
           }
              $this->editor_obj->form_template .= '<option value="'.$key.'"  '.$selected.'>'.$divisionValue.'</option>';           
       }
                   
          $this->editor_obj->form_template .= '</select>  
                 </div>';

                 $this->editor_obj->form_template .='
                <div class="fl"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="fl med_textbox"><nobr>ttxtLicenceNumber</nobr><br />txtLicenceNumber</div>
                <div class="fl med_textbox" id="licence_class"><nobr>ttxtLicenceClass</nobr><br />txtLicenceClass</div>
                <div class="fl med_textbox"><nobr>tselCompliance</nobr><br />selCompliance</div>
                <div class="fl small_textbox"><nobr>tcalExpiryDate</nobr><br />calExpiryDate</div>
                 <div class="cl"></div></br>
                <div class="fl small_textbox"><nobr>tselState</nobr><br />selState</div>
                <div class="fl" style="padding-left: 10px;"><nobr>Photo 1*</nobr><br />
                <div class="cl"></div></br>
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload1" id="fileToUpload1" onchange="loadImageFile(\'1\')"><br /><canvas id="myCanvas1" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file1) ? '<a target="_blank" href="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img1)) . '"><img style="max-width: 200px;max-height:150px"  src="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img1)) . '" /></a><br />' : '') . '
                </div>
                <div class="fl" style="padding-left: 10px;"><nobr>Photo 2 (Optional) <a title="If Applicable, Add a Photo of the Back of Your Licence." href="#">?</a></nobr><br />
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload2" id="fileToUpload2" onchange="loadImageFile(\'2\')"><br /><canvas id="myCanvas2" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file2) ? '<a target="_blank" href="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img2)) . '"><img style="max-width: 200px;max-height:150px" src="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img2)) . '" /></a><br />' : '') . '
                </div>
                <div class="cl"></div>';
      $this->editor_obj->form_template .= '
                <div class="cl"></div>' . $this->editor_obj->button_list();
      if($_REQUEST['show_min'] == 1){
      $this->editor_obj->form_template .= '
                <div class="cl"></div><div class="fr"><input type="text" id="send_license_email" style="width:290px"/> <input type="button" value="Send Email" onClick="SendLicenseEmail('.$lookup_id.')" ></div><br>';
}
     if((!$edit_id && $manage_mode && !$lookup_id)) $is_visible = 'style="visibility: hidden; height: 0px"';
      if($_REQUEST['show_min'] == 1) $is_visible1 = ""; //'style="visibility: hidden; height: 0px"';
     $currMethod =  $this->f3->get('curr_method'); 
     
     if($currMethod == "VerifyLicences"){
         $verifyLic = "licbtnactivate";
     }
      if($currMethod == "NearlyExpiredLicences"){
         $licExpired = "licbtnactivate";
     }
      if($currMethod == "InactiveLicences"){
         $inActiveLic = "licbtnactivate";
     }
     
     if($this->subString == 0){
         $allLic = "licbtnactivate";
     }
     
    
      $this->editor_obj->editor_template = '
          <div class="fl" '.$is_visible1.'> 
                            <a class="sub-menu-a licbtn '.$allLic.' " href="/Licencing/LicenceManagement'.$ajaxlink.'" $verifyLic>All</a>
                       <a class="sub-menu-a licbtn '.$verifyLic.' " href="/Licencing/VerifyLicences'.$ajaxlink.'" $verifyLic>Active Licenses</a>
                       <a class="sub-menu-a licbtn '.$licExpired.'" href="/Licencing/NearlyExpiredLicences'.$ajaxlink.'" >Expired Licenses</a>
                       <a class="sub-menu-a licbtn '.$inActiveLic.'" href="/Licencing/InactiveLicences'.$ajaxlink.'">De-Activated Licenses</a>
                   </div> 
                    <div class="cl"></div><br />
                  <div class="fl" '.$is_visible.'>
                  ' . ($show_warning ? '<div class="message">Please add your security licence<br />and other relevant licences/certificates before proceeding...</div>' : '') . '
                  <div class="form-wrapper">
                  <div class="form-header">'.($lookup_id == $_SESSION['user_id'] ? "My Licences" : ($employee_id ? "Licences for $employee_id $name $surname" : "Licence Management")).'<button class="addNewLicenceBtn" id="addNewLicenceBtn">Add/Edit Licence</button>
                  </div>
                  <script>
                  document.getElementById("addNewLicenceBtn").addEventListener("click", function (event) {
                      event.preventDefault(); // Prevent page refresh
                      var newLicenceForm = document.getElementById("newLicenceForm");
                      if (newLicenceForm.style.display === "none") {
                          newLicenceForm.style.display = "block";
                      } else {
                          newLicenceForm.style.display = "none";
                      }
                  });
                  </script>
                  <style>
                      #newLicenceForm {
                          display: none; /* Hide on page load */
                      }

                      .img_container {
                        position: relative;
                        width: 50px!important;
                        height: 50px!important;
                        overflow: hidden!important;
                    }

                      .uk-table-hover tbody tr:hover, .uk-table-hover>tr:hover {
                        background: #CDEEFD!important;
                    }

                      thead {
                        display: table-header-group;
                        vertical-align: middle;
                        border-color: inherit;
                        color: white!important;
                        background-color: #242E49!important;
                    }

                      .form-header {
                        border-width: 0px;
                        color: white!important;
                        font-weight: normal !important;
                        font-size: 12pt;
                        background-color: #242E49;
                        padding: 10px 8px 10px 8px;
                        width: 181vh!important;
                        font-weight: bold!important;

                    }

                    td {
                    font-size: 14px!important;
                    }

                    td a {
                    font-size: 15px!important;
                    font-weight: bold!important;
                    color: black!important;
                    }

                  </style>
                  <div class="form-content" id="newLicenceForm">
                  editor_form
                  </div>
                  </div>
                  </div>
                  <div class="cl"></div><br />';
                  $this->editor_obj->editor_template .= 'editor_list               
                  <div class="cl"></div>';
      $tmp = $this->editor_obj->draw_data_editor($this->list_obj);
      if($action == "add_record" && !$edit_id) $edit_id = $this->editor_obj->last_insert_id;
      if($edit_id) {
        $iframe_item = '';
        $tmp = str_replace("iframe_item", $iframe_item, $tmp);
      } else {
        $tmp = str_replace("iframe_item", "", $tmp);
      }
      $str .= $tmp;

      if($action == "add_record"){
     $save_id = $this->editor_obj->last_insert_id;
    //  echo $save_id;
       // die;
        
//        $sql = "select licence_number, expiry_date from licences where id = $save_id";
//        $result = $this->dbi->query($sql);
//        if($myrow = $result->fetch_assoc()) {
//          $licence_number = $myrow['licence_number'];
//          $expiry_date = $myrow['expiry_date'];
//          $sql = "update licences set deactivated = 1 where user_id = $lookup_id and deactivated = 0 and licence_number = '$licence_number' and expiry_date < '$expiry_date'";
//          $result = $this->dbi->query($sql);
//        }
      } else if($action == "save_record") {
        $save_id = $this->editor_obj->idin;
        if($lookup_id == $_SESSION['user_id']) {
          $sql = "update licences set verified_by = 0 where id = $save_id";
          $this->dbi->query($sql);
        }
      } else if($action == "delete_record") {
          // echo $this->redirect($_SERVER['REQUEST_URI']);
      }
      if($save_id) {
        if($img || $img2) {
          $folder = "$flder$save_id";
          if (!file_exists($folder)) {
            mkdir($folder);
            chmod($folder, 0755);
          }
        }
        if($img) {
          $img = str_replace(' ', '+', $img);
          $img =  substr($img,strpos($img,",")+1);
          $data = base64_decode($img);
          if($norename) {
            $img_name = basename($_POST['hdnFileName1']);
            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
          } else {
            $img_name = "licence.jpg";
          }
          $file1 = "$folder/$img_name";
          $success = file_put_contents($file1, $data);
        }
        if($img2) {
          $img2 = str_replace(' ', '+', $img2);
          $img2 =  substr($img2,strpos($img2,",")+1);
          $data = base64_decode($img2);
          if($norename) {
            $img_name = basename($_POST['hdnFileName2']);
            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
          } else {
            $img_name = "licence2.jpg";
          }
          $file2 = "$folder/$img_name";
          $success = file_put_contents($file2, $data);
        }
        $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);
        
        if($rotate1) {
          $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);
          if($rotate1) {
            $degrees = (strtoupper($rotate1) == 'LEFT' ? 270 : ($rotate1 == 'FLIP' ? 180 : 90));
            $source = imagecreatefromjpeg($file1);
            $rotate1 = imagerotate($source, $degrees, 0);
            imagejpeg($rotate1,$file1);
            imagedestroy($source);
            imagedestroy($rotate1);
          }
        }
        $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);
        
        if($rotate2) {
          $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);
          if($rotate2) {
            $degrees = (strtoupper($rotate2) == 'LEFT' ? 270 : ($rotate2 == 'FLIP' ? 180 : 90));
            $source = imagecreatefromjpeg($file2);
            $rotate2 = imagerotate($source, $degrees, 0);
            imagejpeg($rotate2,$file2);
            imagedestroy($source);
            imagedestroy($rotate2);
          }
        }
      }
    //$str .= $this->list_obj->draw_list();
    return $str;
  }



  function LicenceManagement1(){
      
    
    $verify_mode = $this->verify_mode;
    $allow_edit = $this->allow_edit;
    $show_expired = $this->show_expired;
    $show_deactivated = $this->show_deactivated;
    $nearly_expired = $this->nearly_expired;
    $my_licences = $this->my_licences;
    $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
     $emailAction = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
    
    
    
    
    
    
    
    
    $flder = $this->f3->get('download_folder') . "licences/";
    $del_photo = (isset($_POST['del_photo']) ? $_POST['del_photo'] : null);
    $edit_id = (isset($_POST['idin']) ? $_POST['idin'] : null);
    $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : ($my_licences ? $_SESSION['user_id'] : null));
    if(isset($_POST['hdnImage1'])) $img = $_POST['hdnImage1'];
    if(isset($_POST['hdnImage2'])) $img2 = $_POST['hdnImage2'];
    
    if(isset($_REQUEST['show_min']) && $_REQUEST['show_min'] == 1){
          $ajaxlink = '?show_min=1&lookup_id='.$lookup_id;
      }else{
          $ajaxlink = "";
      }

    $hr_user = $this->f3->get('hr_user');
    $additional_fields = "";
  // pr($_REQUEST);
    $verify_id = (isset($_GET['verify_id']) ? $_GET['verify_id'] : null);
    $activation_id = (isset($_GET['activation_id']) ? $_GET['activation_id'] : null);

    if($verify_id) {        
        //$user_id = $this->get_sql_result("select user_id as `result` from licences where id = $verify_id;");
        
        if(!file_exists($flder.$verify_id."/licence.jpg")){
            echo "no_img";
            exit;
        }
        
      $is_verified = $this->get_sql_result("select verified_by as `result` from licences where id = $verify_id;");
      
      $sql = "update licences set verified_by = " . ($is_verified ? 0 : $_SESSION['user_id']) . " where id = $verify_id;";
      $this->dbi->query($sql);
      echo ($is_verified ? "" : $this->get_sql_result("select concat(name, ' ', surname) as `result` from users where id = " . $_SESSION['user_id']));
      exit;
    } else if($activation_id) {
      $is_deactivated = $this->get_sql_result("select deactivated as `result` from licences where id = $activation_id;");
      $sql = "update licences set deactivated = " . ($is_deactivated ? 0 : 1) . " where id = $activation_id;";
      $this->dbi->query($sql);
      echo ($is_deactivated ? "Disable Iicense" : "Enable License");
      exit;
    }
    
    if($del_photo) {
      
      $parts = explode("_", $del_photo);
      if($this->get_sql_result("select user_id as `result` from licences where id = {$parts[0]}") == $_SESSION['user_id']) {
        $sql = "update licences set verified_by = 0 where id = {$parts[0]}";
        $this->dbi->query($sql);
      }
      $del = $flder . $parts[0] . "/licence" . ($parts[1] == 2 ? "2" : "") . ".jpg";
      if(file_exists($del)) {
          $sql = "update licences set verified_by = 0 where id = {$parts[0]}";
          $this->dbi->query($sql);
            unlink($del);
        $str .= $this->message("Photo Deleted...", 2000);
        if($parts[1] == 1) {
          $test = $flder . $parts[0] . "/licence2.jpg";
          $rename_to = $flder . $parts[0] . "/licence.jpg";
          if(file_exists($test)) rename($test, $rename_to);
        }
      }
      
    }
   
   
    $str .= '
    <style>
    .tab_item {
      float: left;
      border: 2px solid black;
      padding: 0px;
      display: inline-block;
      margin-right: 10px;
      margin-top: 15px;
    }
    .tab_heading {
      float: left;
      padding-left: 12px;
      padding-right: 12px;
      padding-top: 6px;
      padding-bottom: 6px;
      display: inline-block;
      margin-right: 10px;
      margin-top: 15px;
    }
    .tab_item:hover {
      border-color: blue !important;
    }
    .tab_a {
      padding-left: 12px;
      padding-right: 12px;
      padding-top: 6px;
      padding-bottom: 6px;
      display: inline-block;
    }
    .tab_a:hover {
      background-color: #FFFFCC !important;
    }
    .tab_selected {
      border: 2px solid red;
      background-color: #FFFFEE;
    }
    
    .img_container {
        position: relative;
        width:120px!important;
        height:120px!important;
        overflow:hidden!important;
    }

    .img_container img {
      width: 100%!important; /* Make the image fill the container */
      height: auto!important; /* Maintain aspect ratio */
      display: block!important; /* Remove default inline behavior */
  }

  .list_a {
    -webkit-box-sizing: content-box;
    -moz-box-sizing: content-box;
    box-sizing: content-box;
    display: inline-block;
    width: 88%;
    height: 15%;
    position: absolute;
    top: 0;
    left: 0;
    text-align: center;
    padding-top: 3px;
    padding-bottom: 3px;
    justify-content: center!important;
    padding-left: 6px;
    padding-right: 6px;
    text-decoration: none;
    color: white;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    font-weight: normal !important;
    font-size: 10px !important;
    border: 1px solid #DDDDDD;
    object-position: center!important;
    text-align: center!important;
    align-items: center!important;
    align-content: center!important;
    margin-top: 35%!important;
}

/* Show the button when hovering over the container */
.img_container:hover .list_a {
    opacity: 1;
}

    .topright {
        position: absolute;
        top: 0px;
        right: 0px;
    }
    .op_button {
      color: black;
      padding: 2px;
      background-color: rgba(255,255,255,0.7);
      border: 1px solid rgba(128,128,128,0.7);
    }
    .op_button:hover {
      text-decoration: none;
      background-color: #FFFFCC;
      border: 1px solid #DDD;
    }
    .redBorder{
    border:2px solid red; 
}
 .greenBorder{
    border:2px solid green; 
}

input[type=button], input[type=submit], .button_a {
  -webkit-appearance: none;
  color: white;
  background-color: #242E48;
  border: 1px solid #242E48;
  margin-right: 15px;
  padding: 8px 14px 8px 14px;
  font-size: 11pt;
  font-weight: normal !important;
}


.addNewLicenceBtn {
  margin-left: 20px!important;
  padding: 7.5px!important;
  background-color: #fff!important;  
  border-radius: 7.5px!important;

}
}

    </style>
    <script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
    <script type="text/javascript">

    var canvas, ctx, pfix_in

    oFReader = new FileReader(), 
    rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
    oFReader.onload = function (oFREvent) {

      var img=new Image();
      img.onload=function(){
          canvas = document.getElementById(canvas);
          pfix = pfix_in
          canvas.style.height = "auto";
          ctx=canvas.getContext("2d");

          //alert("Width: " + img.width + ", Height: " + img.height)
          //alert("Nat Width: " + img.naturalWidth + ", Nat Height: " + img.naturalHeight)

          var MaxWidth = 1000
          //alert(img.width)
          if(img.width >= MaxWidth) {
            canvas.width = MaxWidth
            canvas.height = (img.height / img.width) * MaxWidth
          } else {
            canvas.width = img.width
            canvas.height = img.height
          }
          
          ctx.drawImage(img,0,0,img.width,img.height,0,0,canvas.width,canvas.height);

          //document.getElementById("uploadPreview").src = canvas.toDataURL();  <h3>Image Preview</h3>
          document.getElementById("hdnImage" + pfix).value = canvas.toDataURL("image/jpeg",0.8);

      }
      img.src=oFREvent.target.result;

    };

    function submit_form() {
      document.getElementById("submit").click()
    }

    function getOrientation(file, callback) {
      var reader = new FileReader();
      reader.onload = function(e) {

      var view = new DataView(e.target.result);
      if (view.getUint16(0, false) != 0xFFD8) {
        return callback(-2);
      }
      var length = view.byteLength, offset = 2;
      while (offset < length) {
        if (view.getUint16(offset+2, false) <= 8) return callback(-1);
        var marker = view.getUint16(offset, false);
        offset += 2;
        if (marker == 0xFFE1) {
          if (view.getUint32(offset += 2, false) != 0x45786966) {
            return callback(-1);
          }

          var little = view.getUint16(offset += 6, false) == 0x4949;
          offset += view.getUint32(offset + 4, little);
          var tags = view.getUint16(offset, little);
          offset += 2;
          for (var i = 0; i < tags; i++) {
            if (view.getUint16(offset + (i * 12), little) == 0x0112) {
                return callback(view.getUint16(offset + (i * 12) + 8, little));
            }
          }
        }
        else if ((marker & 0xFF00) != 0xFF00) {
          break;
        } else { 
          offset += view.getUint16(offset, false);
        }
      }
      return callback(-1);
      };
      reader.readAsArrayBuffer(file);
    }
    function loadImageFile(pfix="") {
      pfix_in = pfix
      file_uploader = "fileToUpload" + pfix
      if (document.getElementById(file_uploader).files.length === 0) { return; }
      var oFile = document.getElementById(file_uploader).files[0];
      if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }
      document.getElementById("hdnFileName" + pfix).value = document.getElementById(file_uploader).value
      canvas = "myCanvas" + pfix
      oFReader.readAsDataURL(oFile);
      getOrientation(oFile, function(orientation) {
        ori = orientation;
        if(ori == 3 || ori == 6 || ori == 8) {
          if(ori == 3) {
            rotate_direction = "FLIP"
          } else if(ori == 6) {
            rotate_direction = "LEFT"
          } else if(ori == 8) {
            rotate_direction = "RIGHT"
          }
          document.getElementById("rotate" + pfix).value = rotate_direction
        }
      });
    }


    function del_photo(id) {
      var confirmation;
      confirmation = "Are you sure about deleting this file?";
      if (confirm(confirmation)) {
        document.getElementById("del_photo").value = id
        document.frmEdit.submit();
      }
    }
    function hide_del(id) {
      element = document.getElementById(id);
      if (typeof(element) != "undefined" && element != null) {
        document.getElementById(id).style.display=\'none\';
      }
    }

    function verify_licence(id) {
      $.ajax({
        type:"get",
            url:"'.$this->f3->get('main_folder').'Licencing/VerifyLicences",
            data:{ verify_id: id } ,
            success:function(msg) {
            if(msg == "no_img"){
                alert("Document not exist for Verify");
            }else{
                  document.getElementById(id + "-7").innerHTML = msg
                 // document.getElementById("a" + id).innerHTML = (msg ? "Unverify Licence Details" : "Verify Licence Details")
                $("#a" + id).parent("div").removeClass("redBorder").addClass("greenBorder");
                document.getElementById("a" + id).remove();
            }
            
            }
      } );
    }

    function licence_activation(id) {
      $.ajax({
        type:"get",
            url:"'.$this->f3->get('main_folder').'Licencing/VerifyLicences",
            data:{ activation_id: id } ,
            success:function(msg) {
              document.getElementById("r" + id).innerHTML = msg
            }
      } );
    }
    

    </script>
    ';
    $del_string = "'<a id=\"', licences.id, '_1\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_1\')\">x</a>'";
    $del_string2 = "'<a id=\"', licences.id, '_2\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_2\')\">x</a>'";
    $additional_fields = "";
    //if($hr_user && $allow_edit) {
    if(1){    
      if(!$my_licences) {
        $show_edit = "
        , CONCAT('<a id=\"r', licences.id, '\" class=\"list_a\" href=\"JavaScript:licence_activation(', licences.id, ')\">', if(licences.deactivated = 0, 'Disa', 'Ena'), 'ble License</a>') as ` `
        ";
        
         $show_verify = "
       , CONCAT('<a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\">', if(licences.verified_by = 0, 'V', 'Unv'), 'erify Licence</a>') as `Licence Verification`
        ";
        
        
//         if(!$my_licences) {
//        $show_deactivated = "
//        , CONCAT('<a id=\"r', licences.id, '\" class=\"list_a\" href=\"JavaScript:licence_activation(', licences.id, ')\">', if(licences.deactivated = 0, 'Dea', 'A'), 'ctivate Licence</a>') as ` `
//        ";
//        
//        $show_verify = "
//        , CONCAT('<a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\">', if(licences.verified_by = 0, 'V', 'Unv'), 'erify Licence</a>') as `Licence Verification`
//        ";
        
      }
      $show_edit .= ",'Edit' as `*` , 'Delete' as `!`"; //
    }
    
    $srchUserDivision =  $_REQUEST['srch_userLicDivision'];
    
    
   if (isset($_POST['hdnFilter']) && $_POST['hdnFilter'] != "" ){
       //prd($_POST['hdnFilter']);
                $filter_string = "filter_string";
   }
   else{
       $filter_string = "and user_status.item_name = 'ACTIVE'";
   }
    
//    if($manage_mode) {
//      $filter_string .= ($exp_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.expiry_date ' . ($exp_view == 1 ? '<' : '>=') . ' now() ' : '');
//      $filter_string .= ($active_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.deactivated = ' . ($active_view == 1 ? '1' : '0') : '');
//      $filter_string .= ($verify_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.verified_by ' . ($verify_view == 1 ? '=' : '!=') . '0' : '');
//   }
    if($lookup_id) $filter_string .= ($filter_string ? ' and ' : ' where ') . " licences.user_id = $lookup_id and deactivated = 0";
    if(!$show_inactive) $filter_string .= ($filter_string ? ' and ' : ' where ') . " user_status.item_name = 'ACTIVE'";
    //$username = "amer";

   // if(file_exists)
  
    //die;
     $subqueryDivision = "(SELECT GROUP_CONCAT(' ',com.item_name) FROM lookup_answers la 
LEFT JOIN companies com ON com.id = la.lookup_field_id
WHERE la.foreign_id = users.id AND com.id IS NOT NULL) as `Division` ,";
     //and sla.lookup_field_id in (".$loginUserDivisions.") 
     if($srchUserDivision){
        $divisionFilterSql = " left join lookup_answers sla on sla.foreign_id = users.id  and sla.lookup_field_id = '".$srchUserDivision."'";
        $srchFilterSql = ' and (sla.id IS NOT NULL) ';
     }
     else{
         $divisionFilterSql = "";
        $srchFilterSql = ''; 
     }
     
     
     
     $this->list_obj->sql = "
     select 
         licences.id as idin,
         " . ($my_licences ? "" : "CONCAT('<a href=\"".$this->f3->get('main_folder')."Licencing/LicenceManagement?lookup_id=', users.id, '\">', users.name, ' ', users.surname, '</a>'" . ($show_inactive ? ", '<br />', user_status.item_name" : "") . ", '<br/>', CASE WHEN users.phone IS NULL then  '' else users.phone END) as `Licence Holder`, ") . "
         $subqueryDivision
         licence_types.item_name as `Licence Type`,
         licences.licence_number as `Licence Number`,
         CONCAT(licences.licence_class, if(licence_compliance_id = '', '', CONCAT('<br/>', lookup_fields.item_name))) as `Class/Compliance`,
         concat(licences.expiry_date, '<div style=\"color: ',
             if(DATEDIFF(licences.expiry_date, now()) <= 0,
                 CONCAT('red\">Expired ', if(DATEDIFF(licences.expiry_date, now()) = 0,
                     CONCAT('Today!'),
                     CONCAT(ABS(DATEDIFF(licences.expiry_date, now())), ' Days Ago'))),
                 if(DATEDIFF(licences.expiry_date, now()) > 0 and DATEDIFF(licences.expiry_date, now()) <= 30,
                     'orange\">',
                     'green\">')
             ), DATEDIFF(licences.expiry_date, now()), ' Days</div>') as `Expired`,
         states.item_name as `State Issued`,
         CONCAT(users2.name, ' ', users2.surname) as `Verified By`,
         CONCAT('
             <a target=\"_blank\" href=\"".$this->f3->get('main_folder')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_1\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('main_folder')."Image?no_crypt=1&i=licences/', licences.id, '/licence.jpg\"></a>"
             . "',if(licences.verified_by = 0,CONCAT('</br><a id=\"a', licences.id, '\" class=\"list_a\" href=\"JavaScript:verify_licence(', licences.id, ')\" >Verify Licence Details </a>',''), '') ," 
             . "'<div class=\"topright\">', $del_string, '</div></div>') as `Photo 1`,
         CONCAT('
             <a target=\"_blank\" href=\"".$this->f3->get('main_folder')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_2\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('main_folder')."Image?no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"></a>"
             . "<div class=\"topright\">', $del_string2, '</div></div>') as `Photo 2`
         $show_edit
     FROM licences
     left join users2 on users2.id = licences.verified_by
     left join licence_types on licence_types.id = licences.licence_type_id
     left join users on users.id = licences.user_id
     left join states on states.id = licences.state_id
     left join user_status on user_status.id = users.user_status_id
     left join lookup_fields on lookup_fields.id = licences.licence_compliance_id
     $divisionFilterSql
     where 1
         " . ($verify_mode ? " and licences.expiry_date >= now() " : "") . "
         " . ($my_licences ? " and users.id = " . $_SESSION['user_id'] : " and user_status.item_name = 'ACTIVE' ") . "
         " . ($show_deactivated ? " and licences.deactivated = 1 " : " and licences.deactivated = 0 ") . "
         " . ($nearly_expired ? " and DATEDIFF(licences.expiry_date, now()) <= 0 " : "") . "
         " . ($lookup_id ? " and users.id = " . $lookup_id : "") . "
         $srchFilterSql
         $filter_string
     order by users.name, DATEDIFF(licences.expiry_date, now())
 ";
 
 
    
//    echo  $this->list_obj->sql;
//    die;
    
$forMail= "
select 
    licences.id as idin,
    " . ($my_licences ? "" : "CONCAT(users.name, ' ', users.surname) as `Licence Holder1`, ") . "      
    licence_types.item_name as `Licence Type`, 
    licences.licence_number as `Licence Number`,
    CONCAT(licences.licence_class, if(licence_compliance_id = '', '', CONCAT('<br/>', lookup_fields.item_name))) as `Class/Compliance`, 
    concat(licences.expiry_date, 
        '<div style=\"color: ',
        if(DATEDIFF(licences.expiry_date, now()) <= 0,
            CONCAT('red\">Expired ', if(DATEDIFF(licences.expiry_date, now()) = 0,
                CONCAT('Today!'),
                CONCAT(ABS(DATEDIFF(licences.expiry_date, now())), ' Days Ago'))),
            if(DATEDIFF(licences.expiry_date, now()) > 0 and DATEDIFF(licences.expiry_date, now()) <= 30,
                'orange\">',
                'green\">')
        ), DATEDIFF(licences.expiry_date, now()), ' Days</div>') as `Expired`,
    states.item_name as `State Issued`, 
    CONCAT(users2.name, ' ', users2.surname) as `Verified By`,
    CONCAT('
        <a target=\"_blank\" href=\"".$this->f3->get('full_url')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\">
        <img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_1\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('full_url')."Image?no_crypt=1&i=licences/', licences.id, '/licence.jpg\"></a>"
        . "') as `Photo 1`,
    CONCAT('
        <a target=\"_blank\" href=\"".$this->f3->get('full_url')."ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"><div class=\"img_container ',if(licences.verified_by = 0,'redBorder','greenBorder'),'\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_2\');\" style=\"width: 120px;height:120px;\" src=\"".$this->f3->get('full_url')."Image?no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"></a>"
        . "') as `Photo 2`,
    $show_edit
FROM licences
left join users2 on users2.id = licences.verified_by
left join licence_types on licence_types.id = licences.licence_type_id
left join users on users.id = licences.user_id
left join states on states.id = licences.state_id
left join user_status on user_status.id = users.user_status_id
left join lookup_fields on lookup_fields.id = licences.licence_compliance_id
where 1       
" . ($verify_mode ? " and licences.expiry_date >= now() " : "") . "
" . ($my_licences ? " and users.id = " . $_SESSION['user_id'] : " and user_status.item_name = 'ACTIVE' ") . "
" . ($show_deactivated ? " and licences.deactivated = 1 " : " and licences.deactivated = 0 ") . "
" . ($nearly_expired ? " and DATEDIFF(licences.expiry_date, now()) <= 0 " : "") . "
" . ($lookup_id ? " and users.id = " . $lookup_id : "") . "
$filter_string
order by users.name, DATEDIFF(licences.expiry_date, now())

";

    
   
//   echo $forMail;
//   die;
    
    if($emailAction == "sendLicenseEmail"){
        $email = $_REQUEST['email'];
        $user_id = $_REQUEST['userId'];        
        $this->sendLicenseEmail($forMail,$email,$user_id);
        echo 1;
        die;
        
    }
    
   
    
    //  " . ($verify_mode ? " and licences.verified_by = 0 and licences.expiry_date >= now() " : "") . "
    
//    echo  $this->list_obj->sql;
//    die;
  
    //return "<textarea>{$this->list_obj->sql}</textarea>";
        /*if($hr_user && $lookup_id != $_SESSION['user_id']) {
          $this->editor_obj->custom_field = "verified_by";
          $this->editor_obj->custom_value = $_SESSION['user_id'];
        }*/
       // $this->editor_obj->xtra_id_name = "user_id";
       // $this->editor_obj->xtra_id = $lookup_id;
        $this->editor_obj->table = "licences";
          $style = 'class="full_width"';
          $this->editor_obj->xtra_validation = '
         

            var test_date;
            obj = document.getElementById(\'calExpiryDate\').value
            if(obj) {
              dt = moment(obj, "DD-MMM-YYYY");
              //today = moment().format("DD-MMM-YYYY")
              today = moment().format("YYYY-MM-DD")
              if(moment(dt).isAfter(moment(today)) === false || moment(dt).isSame(moment(today)) === true) {
                err++;
                error_msg += err + ". Please select a date that is after today.\n";
              }
            }
            

';
          $str .= '
            <script>
              function config_questions() {
                var d = document.getElementById("selLicenceType");
                var itm = document.getElementById("licence_class");
                var str = d.options[d.selectedIndex].text;
                var s = str.search(/licence/i);
                if(s !== -1) {
                  itm.style.display="inline"
                } else {
                  itm.style.display="none"
                }
              }
              function validateEmail(email) {
          var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
          return re.test(email);
        }
        function SendLicenseEmail(userId) {
          // Assuming you have the expiry date available in your HTML, adjust the selector accordingly
          var expiryDate = $("#expiry_date_" + userId).text();
          
          // Calculate days left to expiry
          var daysLeft = calculateDaysLeft(expiryDate);
      
          // Check if the license is 30 or 14 days away from expiry
          if (daysLeft === 30 || daysLeft === 14) {
              // Send email to hr@alliedmanagement.com.au
              $.ajax({
                  type: "get",
                  url: "' . $main_folder . '/Licencing/LicenceManagement",
                  data: { user_id: userId, action: "sendLicenseEmail", show_min: 1, lookup_id: userId, email: "hr@alliedmanagement.com.au" },
      
                  success: function (msg) {
                      if (msg == 1) {
                          alert("Email has been sent to hr@alliedmanagement.com.au");
                      }
                  }
              });
          }
      }
      // Function to calculate days left to expiry
function calculateDaysLeft(expiryDate) {
    var currentDate = new Date();
    var expiryDateObj = new Date(expiryDate);
    var timeDiff = expiryDateObj.getTime() - currentDate.getTime();
    var daysLeft = Math.ceil(timeDiff / (1000 * 3600 * 24));

    return daysLeft;
}
              
            </script>
          ';
      $this->editor_obj->form_attributes = array(
        array("selLicenceType", "txtLicenceNumber", "txtLicenceClass", "calExpiryDate", "selState", "selCompliance", "selUserIds"),
        array("Licence Type", "Licence/Certificate Number", "Licence Class (e.g. 1AC)", "Expiry Date", "State Issued", "Compliance (Mandatory)", "User"),
        array("licence_type_id", "licence_number", "licence_class", "expiry_date", "state_id", "licence_compliance_id", "user_id"),
        array($this->get_simple_lookup('licence_types'), "", "", "", $this->get_simple_lookup('states'), $this->get_lookup('licence_compliance'), $this->licenceUser($lookup_id)),
        array($style . ' onChange="config_questions()"', $style, $style, $style, $style, $style, $style),
        array("c", "c", "n", "c", "c", "c", 'c')
      );
      $this->editor_obj->hide_duplicate = 1;
        

          
          
      $this->editor_obj->button_attributes = array(
        array("Add New",    "Save",   "Reset",   "Filter"),
        array("cmdAdd",     "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit()", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
        
      $img_file1 = "$flder$edit_id/licence.jpg";
      $img_file2 = "$flder$edit_id/licence2.jpg";
      $show_img1 = "licences/$edit_id/licence.jpg";
      $show_img2 = "licences/$edit_id/licence2.jpg";
      $this->editor_obj->form_template = '
      iframe_item
                <input type="hidden" name="del_photo" id="del_photo" />
                <input type="hidden" name="hdnFileName1" id="hdnFileName1" />
                <input type="hidden" name="hdnFileName2" id="hdnFileName2" />
                <input type="hidden" name="hdnImage1" id="hdnImage1" />
                <input type="hidden" name="hdnImage2" id="hdnImage2" />
                <input type="hidden" name="rotate1" id="rotate1" />
                <input type="hidden" name="rotate2" id="rotate2" />
                <div class="fl med_textbox"><nobr>tselLicenceType</nobr><br />selLicenceType</div>
                <div class="fl med_textbox"><nobr>tselUserIds</nobr><br />selUserIds</div>
               <div class="fl small_textbox">
               <nobr>User Division *</nobr>
               <select name="srch_userLicDivision" id="srch_userLicDivision">
                  <option value=""> Select Division </option>'; 

                  $division_array = ['108' => 'ASM - Security','2100' => 'AFM - Facilities','2102' => 'APM - Pest Control','2103' => 'ACM - Civil','2104' => 'ATM - Traffic'];
                  
                  foreach($division_array as $key => $divisionValue){
                      if($_REQUEST['srch_userLicDivision'] == $key){
                          $selected = "selected = 'selected'";
                      }else{
                          $selected = "";
                      }
                          $this->editor_obj->form_template .= '<option value="'.$key.'"  '.$selected.'>'.$divisionValue.'</option>';           
                  }
                              
                      $this->editor_obj->form_template .= '</select>  
                </div>';

                 $this->editor_obj->form_template .='
                <div class="fl"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="fl med_textbox"><nobr>ttxtLicenceNumber</nobr><br />txtLicenceNumber</div>
                <div class="fl med_textbox" id="licence_class"><nobr>ttxtLicenceClass</nobr><br />txtLicenceClass</div>
                <div class="fl med_textbox"><nobr>tselCompliance</nobr><br />selCompliance</div>
                <div class="fl small_textbox"><nobr>tcalExpiryDate</nobr><br />calExpiryDate</div>
                 <div class="cl"></div></br>
                <div class="fl small_textbox"><nobr>tselState</nobr><br />selState</div>
                <div class="fl" style="padding-left: 10px;"><nobr>Photo 1*</nobr><br />
                <div class="cl"></div></br>
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload1" id="fileToUpload1" onchange="loadImageFile(\'1\')"><br /><canvas id="myCanvas1" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file1) ? '<a target="_blank" href="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img1)) . '"><img style="max-width: 200px;max-height:150px"  src="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img1)) . '" /></a><br />' : '') . '
                </div>
                <div class="fl" style="padding-left: 10px;"><nobr>Photo 2 (Optional) <a title="If Applicable, Add a Photo of the Back of Your Licence." href="#">?</a></nobr><br />
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload2" id="fileToUpload2" onchange="loadImageFile(\'2\')"><br /><canvas id="myCanvas2" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file2) ? '<a target="_blank" href="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img2)) . '"><img style="max-width: 200px;max-height:150px" src="'.$this->f3->get('main_folder').'Image?i='.urlencode($this->encrypt($show_img2)) . '" /></a><br />' : '') . '
                </div>
                <div class="cl"></div>';
      $this->editor_obj->form_template .= '
                <div class="cl"></div>' . $this->editor_obj->button_list();
      if($_REQUEST['show_min'] == 1){
      $this->editor_obj->form_template .= '
                <div class="cl"></div><div class="fr"><input type="text" id="send_license_email" style="width:290px"/> <input type="button" value="Send Email" onClick="SendLicenseEmail('.$lookup_id.')" ></div><br>';
}
     if((!$edit_id && $manage_mode && !$lookup_id)) $is_visible = 'style="visibility: hidden; height: 0px"';
      if($_REQUEST['show_min'] == 1) $is_visible1 = ""; //'style="visibility: hidden; height: 0px"';
     $currMethod =  $this->f3->get('curr_method'); 
     
     if($currMethod == "VerifyLicences"){
         $verifyLic = "licbtnactivate";
     }
      if($currMethod == "NearlyExpiredLicences"){
         $licExpired = "licbtnactivate";
     }
      if($currMethod == "InactiveLicences"){
         $inActiveLic = "licbtnactivate";
     }
     
     if($this->subString == 0){
         $allLic = "licbtnactivate";
     }
     
    
      $this->editor_obj->editor_template = '
          <div class="fl" '.$is_visible1.'> 
                            <a class="sub-menu-a licbtn '.$allLic.' " href="/Licencing/LicenceManagement'.$ajaxlink.'" $verifyLic>All</a>
                       <a class="sub-menu-a licbtn '.$verifyLic.' " href="/Licencing/VerifyLicences'.$ajaxlink.'" $verifyLic>Active Licenses</a>
                       <a class="sub-menu-a licbtn '.$licExpired.'" href="/Licencing/NearlyExpiredLicences'.$ajaxlink.'" >Expired Licenses</a>
                       <a class="sub-menu-a licbtn '.$inActiveLic.'" href="/Licencing/InactiveLicences'.$ajaxlink.'">De-Activated Licenses</a>
                   </div> 
                    <div class="cl"></div><br />
                  <div class="fl" '.$is_visible.'>
                  ' . ($show_warning ? '<div class="message">Please add your security licence<br />and other relevant licences/certificates before proceeding...</div>' : '') . '
                  <div class="form-wrapper">
                  <div class="form-header">'.($lookup_id == $_SESSION['user_id'] ? "My Licences" : ($employee_id ? "Licences for $employee_id $name $surname" : "Licence Management")).'<button class="addNewLicenceBtn" id="addNewLicenceBtn">Add/Edit Licence</button>
                  </div>
                  <script>
                  document.getElementById("addNewLicenceBtn").addEventListener("click", function (event) {
                      event.preventDefault(); // Prevent page refresh
                      var newLicenceForm = document.getElementById("newLicenceForm");
                      if (newLicenceForm.style.display === "none") {
                          newLicenceForm.style.display = "block";
                      } else {
                          newLicenceForm.style.display = "none";
                      }
                  });
                  </script>
                  <style>
                      #newLicenceForm {
                          display: none; /* Hide on page load */
                      }

                      .img_container {
                        position: relative;
                        width: 50px!important;
                        height: 50px!important;
                        overflow: hidden!important;
                    }

                      .uk-table-hover tbody tr:hover, .uk-table-hover>tr:hover {
                        background: #CDEEFD!important;
                    }

                      thead {
                        display: table-header-group;
                        vertical-align: middle;
                        border-color: inherit;
                        color: white!important;
                        background-color: #242E49!important;
                    }

                      .form-header {
                        border-width: 0px;
                        color: white!important;
                        font-weight: normal !important;
                        font-size: 12pt;
                        background-color: #242E49;
                        padding: 10px 8px 10px 8px;
                        width: 181vh!important;
                        font-weight: bold!important;

                    }

                    td {
                    font-size: 14px!important;
                    }

                    td a {
                    font-size: 15px!important;
                    font-weight: bold!important;
                    color: black!important;
                    }

                  </style>
                  <div class="form-content" id="newLicenceForm">
                  editor_form
                  </div>
                  </div>
                  </div>
                  <div class="cl"></div><br />';
                  $this->editor_obj->editor_template .= 'editor_list               
                  <div class="cl"></div>';
      $tmp = $this->editor_obj->draw_data_editor($this->list_obj);
      if($action == "add_record" && !$edit_id) $edit_id = $this->editor_obj->last_insert_id;
      if($edit_id) {
        $iframe_item = '';
        $tmp = str_replace("iframe_item", $iframe_item, $tmp);
      } else {
        $tmp = str_replace("iframe_item", "", $tmp);
      }
      $str .= $tmp;

      if($action == "add_record"){
     $save_id = $this->editor_obj->last_insert_id;
    //  echo $save_id;
       // die;
        
//        $sql = "select licence_number, expiry_date from licences where id = $save_id";
//        $result = $this->dbi->query($sql);
//        if($myrow = $result->fetch_assoc()) {
//          $licence_number = $myrow['licence_number'];
//          $expiry_date = $myrow['expiry_date'];
//          $sql = "update licences set deactivated = 1 where user_id = $lookup_id and deactivated = 0 and licence_number = '$licence_number' and expiry_date < '$expiry_date'";
//          $result = $this->dbi->query($sql);
//        }
      } else if($action == "save_record") {
        $save_id = $this->editor_obj->idin;
        if($lookup_id == $_SESSION['user_id']) {
          $sql = "update licences set verified_by = 0 where id = $save_id";
          $this->dbi->query($sql);
        }
      } else if($action == "delete_record") {
          // echo $this->redirect($_SERVER['REQUEST_URI']);
      }
      if($save_id) {
        if($img || $img2) {
          $folder = "$flder$save_id";
          if (!file_exists($folder)) {
            mkdir($folder);
            chmod($folder, 0755);
          }
        }
        if($img) {
          $img = str_replace(' ', '+', $img);
          $img =  substr($img,strpos($img,",")+1);
          $data = base64_decode($img);
          if($norename) {
            $img_name = basename($_POST['hdnFileName1']);
            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
          } else {
            $img_name = "licence.jpg";
          }
          $file1 = "$folder/$img_name";
          $success = file_put_contents($file1, $data);
        }
        if($img2) {
          $img2 = str_replace(' ', '+', $img2);
          $img2 =  substr($img2,strpos($img2,",")+1);
          $data = base64_decode($img2);
          if($norename) {
            $img_name = basename($_POST['hdnFileName2']);
            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
          } else {
            $img_name = "licence2.jpg";
          }
          $file2 = "$folder/$img_name";
          $success = file_put_contents($file2, $data);
        }
        $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);
        
        if($rotate1) {
          $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);
          if($rotate1) {
            $degrees = (strtoupper($rotate1) == 'LEFT' ? 270 : ($rotate1 == 'FLIP' ? 180 : 90));
            $source = imagecreatefromjpeg($file1);
            $rotate1 = imagerotate($source, $degrees, 0);
            imagejpeg($rotate1,$file1);
            imagedestroy($source);
            imagedestroy($rotate1);
          }
        }
        $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);
        
        if($rotate2) {
          $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);
          if($rotate2) {
            $degrees = (strtoupper($rotate2) == 'LEFT' ? 270 : ($rotate2 == 'FLIP' ? 180 : 90));
            $source = imagecreatefromjpeg($file2);
            $rotate2 = imagerotate($source, $degrees, 0);
            imagejpeg($rotate2,$file2);
            imagedestroy($source);
            imagedestroy($rotate2);
          }
        }
      }
    //$str .= $this->list_obj->draw_list();
    return $str;
  }
 


 
  function EditLicences() {
    $hr_user = $this->f3->get('hr_user');
    $user_id = ($hr_user ? (isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id']) : $_SESSION['user_id']);
    $licence_id = ($hr_user ? (isset($_GET['licence_id']) ? $_GET['licence_id'] : null) : null);
  }
  
  
  
  
  
  
  
}

?>