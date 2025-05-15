<?php

class Fileman extends Controller {

    function Show() {

        $target_dir = (isset($_GET['target_dir']) ? $_GET['target_dir'] : null);
        $main_target_dir = $target_dir;

        $target_subdir = (isset($_GET['target_subdir']) ? $_GET['target_subdir'] : null);
        $norename = (isset($_GET['norename']) ? $_GET['norename'] : null);

        $detect = new Mobile_Detect;
        /* if($detect->isTablet()){
          $standard_font = "
          font-size: 20pt;
          padding-top: 10px;
          padding-bottom: 10px;
          padding-left: 15px;
          padding-right: 15px;
          ";
          $header_font = "font-size: 24pt;";
          } else if($detect->isMobile()){
          $standard_font = "
          font-size: 26pt;
          padding-top: 15px;
          padding-bottom: 15px;
          padding-left: 15px;
          padding-right: 15px;
          ";
          $header_font = "font-size: 32pt;";
          } else { */
        $standard_font = "
        font-size: 12pt;
        padding-top: 5px;
        padding-bottom: 5px;
        padding-left: 15px;
        padding-right: 15px;
      ";
        $header_font = "font-size: 16pt;";
        //}

        $str .= '<style>
    body {
      background-color: #EEEEEE !important;
    }
    canvas {
      display: none;
    }
    .d_image {
      border: 1px solid #CCCCCC;
      width: 280px; padding: 7px;
      margin-right: 10px;
      ' . $header_font . '
    }
    .d_image:hover {
      background-color: #FFFFDD;
    }
    #uploadPreview {
      width: 480px;
    }
    .fileUpload {
        position: relative;
        overflow: hidden;
        margin: 10px;
    }
    .fileUpload input.upload {
        padding: 25px;
        ' . $header_font . '
        cursor: pointer;
        filter: alpha(opacity=0);
        color: blue;
    }
    .fileToUpload {
      -webkit-appearance: none; 
    }
    .btn {
      display: inline-block;
      text-align: center;
      ' . $standard_font . ';
      border-radius: 8px 8px 8px 8px;
      -moz-border-radius: 8px 8px 8px 8px;
      -webkit-border-radius: 8px 8px 8px 8px;
      margin-right: 3px;
      border: 1px solid #DDDDDD !important;
      color: #333333;
      background-color: #B2C0DD;
      white-space: nowrap;
    }
    .btn:visited {
      color: #333333;
    }
    .btn:hover {
      text-decoration: none;
      background-color: #C0D0EA;
      border-color: #FFFFCC;
      color: black;
    }
    .heading {
      padding: 25px;
      ' . $header_font . '
    }
    </style>
    <script type="text/javascript">
    var canvas, ctx
    var ori //Orientation
    
    
    function del_file(filename) {
      var confirmation;
      confirmation = "Are you sure about deleting this file?";
      if (confirm(confirmation)) {
        document.getElementById("del").value = filename;
        document.frmEdit.submit();
      }
    }
    function rotate_file(filename, direction) {
      var confirmation;
      confirmation = "Are you sure about rotating this file?";
      if (confirm(confirmation)) {
        document.getElementById("rotate").value = filename;
        document.getElementById("rotate_direction").value = direction;
        document.frmEdit.submit();
      }
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

    
    
    oFReader = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
    oFReader.onload = function (oFREvent) {
      var img=new Image();
      
      img.onload=function() {
          canvas = document.getElementById("myCanvas")
          ctx=canvas.getContext("2d")
          var MaxWidth = 1000
          if(img.width >= MaxWidth) {
            canvas.width = MaxWidth
            canvas.height = (img.height / img.width) * MaxWidth
          } else {
            canvas.width = img.width
            canvas.height = img.height
          }
          ctx.drawImage(img,0,0,img.width,img.height,0,0,canvas.width,canvas.height);
          document.getElementById("tester").value = canvas.toDataURL("image/jpeg",0.8);
      }
      img.src=oFREvent.target.result;
    };
    function submit_form() {
      document.getElementById("submit").click()
    }
    function loadImageFile() {
      if (document.getElementById("fileToUpload").files.length === 0) { return; }
      var oFile = document.getElementById("fileToUpload").files[0];
      if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }
      document.getElementById("hdnFileName").value = document.getElementById("fileToUpload").value
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
          document.getElementById("rotate_after").value = rotate_direction
        }
      });
      
      setTimeout(submit_form, 1000);
    }
    </script>
    <h3 style="padding: 20px; ' . $header_font . '">Upload Images</h3>
    <canvas id="myCanvas"></canvas>';

        $img = "";
        $msg = "";
        $img_list = "";
        $dir2 = "";

        $rotate_after = (isset($_POST['rotate_after']) ? $_POST['rotate_after'] : null);

        if ($target_dir || $rotate_after) {
            $rotate = (isset($_POST['rotate']) ? $_POST['rotate'] : null);
            $rotate_direction = (isset($_POST['rotate_direction']) ? $_POST['rotate_direction'] : ($rotate_after ? $rotate_after : null));
            $target_dir = urldecode($target_dir);
            $dir1 = $target_dir;
            //echo $dir1;
            if ($target_subdir) {
                $dir2 = "$dir1/" . $target_subdir;
                $target_dir = $dir2;
            }

            if (isset($_POST['tester']))
                $img = $_POST['tester'];
            if ($img) {
                $img = str_replace(' ', '+', $img);
                $img = substr($img, strpos($img, ",") + 1);
                $data = base64_decode($img);
                if ($norename) {
                    $img_name = basename($_POST['hdnFileName']);
                    $img_name = str_replace("C:\\fakepath\\", "", $img_name);
                } else {
                    $img_name = "tester.jpg";
                }
                $file = $target_dir . "/$img_name";
                $success = file_put_contents($file, $data);

                $occuranceImagesFld = "occurrence_log_images";
                $notesImagesFld = "notes_images";
                $reportImagesFld = "compliance";

                if (strpos($file, $occuranceImagesFld) !== false || strpos($file, $notesImagesFld) !== false || strpos($file, $reportImagesFld) !== false) {
                    if ($target_subdir != "" and strpos($file, $notesImagesFld) !== false) {

                        $getSiteDetail = $this->whiteBoardSiteDetail($target_subdir);
                    } else if ($target_subdir != "" and strpos($file, $occuranceImagesFld) !== false) {
                        $getSiteDetail = $this->occuranceLogSiteDetail($target_subdir);
                    }else if($target_subdir != "" and strpos($file, $reportImagesFld) !== false){
                        
                         $complainceCheckId = array_pop(explode('/',$main_target_dir));
//                         echo $complainceCheckId;
//                         die;
                         $getSiteDetail = $this->reportSiteDetail($complainceCheckId);
                    }
                    //prd($target_subdir);
                    if($getSiteDetail){
                        $GOOGLE_API_KEY = 'AIzaSyCAU7a7VH5tzUGqTjzDtID1ciDXgCyRHGo'; 
                        
                        $latitude = $getSiteDetail['latitude']; 
                        $longitude = $getSiteDetail['longitude'];
                        
                        if($latitude != null || $longitude != null) {
                          $formatted_latlng = trim($latitude).','.trim($longitude); 
                          
                          $geocodeFromLatLng = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng={$formatted_latlng}&key={$GOOGLE_API_KEY}"); 
                          
                          $apiResponse = json_decode($geocodeFromLatLng); 
                          
                          $formatted_address = $apiResponse->results[0]->formatted_address; 
                          $address_parts = explode(",", $formatted_address);
                          if(isset($address_parts) && $address_parts) {
                            $text1 = $address_parts[0];
                            $text2 = $address_parts[1];
                            //$postcode = $getSiteDetail['postcode'];
                            // $text3 = $address_parts[2];
                            $text4 = $getSiteDetail['latitude']." ".$getSiteDetail['longitude'];
                          }
                          else
                          {
                            $text1 = $getSiteDetail['address'];
                            $text2 = $getSiteDetail['suburb'] . " " . $getSiteDetail['postcode'];
                            //$postcode = $getSiteDetail['postcode'];
                            $text3 = $getSiteDetail['userstate'];
                            $text4 = $getSiteDetail['latitude']." ".$getSiteDetail['longitude'];
                          }
                        }
                        else{
                          $text1 = "";
                          $text2 = "";
                          //$postcode = $getSiteDetail['postcode'];
                          $text3 = "";
                          $text4 = "";
                        }
                        // $text1 = $getSiteDetail['address'];
                        // $text2 = $getSiteDetail['suburb'] . " " . $getSiteDetail['postcode'];
                        // //$postcode = $getSiteDetail['postcode'];
                        // $text3 = $getSiteDetail['userstate'];
                        // $text4 = $getSiteDetail['latitude']." ".$getSiteDetail['longitude'];
                        $this->waterMarkImageText($file, $text1, $text2, $text3,$text4);
                    }else{
                        $text1 = "";
                        $text2 = "";
                        //$postcode = $getSiteDetail['postcode'];
                        $text3 = "";
                        $text4 = "";
                       $this->waterMarkImageText($file, $text1, $text2, $text3,$text4);
                    }
                } else {
                    //$this->waterMarkImageText($file);
                }





                $msg .= "<h3>File Added...</h3>";
            }

            if ($rotate || ($rotate_after && $file)) {
                $rotate = ($rotate_after ? $file : $rotate);
//          echo $rotate;
//          die
                $rfile = $rotate;
                $degrees = (strtoupper($rotate_direction) == 'LEFT' ? 270 : ($rotate_direction == 'FLIP' ? 180 : 90));
                $source = @imagecreatefromjpeg($rotate);
                $rotate = @imagerotate($source, $degrees, 0);
                @imagejpeg($rotate, $rfile);
                if (!$rotate_after && @imagejpeg($rotate, $rfile)) {
                    $msg .= "<h3>File Rotated...</h3>";
                } else {
                    $msg .= "<h3>File Rotated not work with this image ...</h3>";
                }


                @imagedestroy($source);
                @imagedestroy($rotate);
            }
            if ($del = (isset($_POST['del']) ? $_POST['del'] : 0)) {
                unlink($del);
                $msg .= "<h3>File Deleted...</h3>";
            }
            if (!file_exists($dir1)) {
                mkdir($dir1);
                chmod($dir1, 0755);
            }
            if (!file_exists($dir2) && $dir2) {
                mkdir($dir2);
                chmod($dir2, 0755);
            }
            if ($msg && !$norename) {
                $d = new RecDir("$target_dir/", true);
                $x = 0;
                while (false !== ($entry = $d->read())) {
                    $file_name = explode("/", $entry);
                    $file_name = $file_name[count($file_name) - 1];
                    if ($file_name) {
                        $file_names[] = $file_name;
                    }
                }
                //echo count($file_names);
                if ($file_names) {
                    if (count($file_names)) {
                        sort($file_names);
                        foreach ($file_names as $file_name) {
                            $x++;
                            if ($x < 1000)
                                $t = "0$x";
                            if ($x < 100)
                                $t = "00$x";
                            if ($x < 10)
                                $t = "000$x";
                            rename("$target_dir/$file_name", "$target_dir/image$t" . ".jpg");
                        }
                    }
                }
                $d->close();
            }
            $str .= '<div class="fl" style="' . $header_font . '">
                <div class="fileUpload">
                <input type="file" class="upload" name="fileToUpload" id="fileToUpload" onchange="loadImageFile()">
                </div>
              </div><div class="cl"></div><img id="uploadPreview"/>';
            $str .= '<input type="hidden" name="del" id="del" /><input type="hidden" name="rotate" id="rotate" /><input type="hidden" name="rotate_direction" id="rotate_direction" /></form>';
            $str .= '<form name="frmImage" method="POST">
                <input type="hidden" name="rotate_after" id="rotate_after" />
                <input type="hidden" name="hdnFileName" id="hdnFileName" />
                <input type="hidden" name="tester" id="tester" />
                <input style="visibility: hidden;" type="submit" value="u" name="submit" id="submit">
        </form>';
            $str .= $msg . '<div class="cl"></div>';
            $d = new RecDir("$target_dir/", true);
            while (false !== ($entry = $d->read())) {

                $entries[] = $entry;
            }


            $d->close();
            if (is_array($entries)) {
                sort($entries);
                foreach ($entries as $entry) {
                    $file_name = explode("/", $entry);
                    $fileUseConst = 'download_folder';
                    if (isset($_GET['fileUseConst'])) {
                        $fileUseConst = $_GET['fileUseConst'];
                    }
                    $file_use = substr($entry, strlen($this->f3->get($fileUseConst)));
                    //echo $file_use;
                    $target_dir = $this->f3->get($fileUseConst);
//            echo strlen($this->f3->get('base_img_folder')).'</br>'.$entry."</br>".$file_use;
//            die;
                    $file_name = $file_name[count($file_name) - 1];

                    //echo $file_name;
                    // die;

                    $rand = rand(10000, 99999);
//            $img_list .= "<h3>$file_use -- $entry</h3>";
                    $img_list .= '<div class="fl d_image"><a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($file_use)) . '&alt_flder=' . urlencode("$target_dir") . '">'
                            . '<img style="max-width: 100%;" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($file_use)) . '&alt_flder=' . urlencode("$target_dir") . '"><br />' . $file_name . '</a><br /><button data-uk-tooltip="Delete Image" class="uk-button-danger pad5" onClick="del_file(\'' . $entry . '\')"><span data-uk-icon="icon: close"></span>Delete</button> <button data-uk-tooltip="Rotate Image to the Left" class="uk-button-primary pad5" type="button" onClick="rotate_file(\'' . $entry . '\', \'right\')"  /><span data-uk-icon="icon: reply"></span>Rotate</button> <button data-uk-tooltip="Rotate Image to the Right" class="uk-button-primary	pad5" onClick="rotate_file(\'' . $entry . '\', \'left\')"><span data-uk-icon="icon: forward"></span>Rotate</button></div>';
                }
            }
            $str .= $img_list . '<div class="cl"></div>';
        }

        /* $str .= '
          <script>
          var input = document.getElementById("fileToUpload");
          input.onchange = function(e) {
          getOrientation(input.files[0], function(orientation) {
          //alert("orientation: " + orientation);
          });
          }
          </script>
          '; */

        return $str;
    }

}

?>