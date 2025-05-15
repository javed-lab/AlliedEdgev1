<?php

class draw extends Controller {
  var $target_dir, $target_dir2, $file_name;
  var $width, $height;

  function display_canvas() {
    $this->width = ($this->width ? $this->width : 640);
    $this->height = ($this->height ? $this->height : 480);
    $str .= '
    <script type = "text/javascript">
    var canvas, ctx;
    var mouseX, mouseY, mouseDown = 0;
    var touchX, touchY;
    var lastX, lastY = -1;
    function drawLine(ctx, x, y, size) {
      if(lastX == -1) {
        lastX = x;
        lastY = y;
      }
      r = 0; g = 0; b = 0; a = 255;
      ctx.strokeStyle = "rgba("+r+", "+g+", "+b+", "+(a/255)+")";
      ctx.lineCap = "round";
      ctx.beginPath();
      ctx.moveTo(lastX, lastY);
      ctx.lineTo(x, y);
      ctx.lineWidth = size;
      ctx.stroke();
      ctx.closePath();
      lastX = x;
      lastY = y;
    } 
    function clearCanvas(canvas, ctx) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    function sketchpad_mouseDown() {
      mouseDown = 1;
      drawLine(ctx, mouseX, mouseY, 4);
    }
    function sketchpad_mouseUp() {
      mouseDown = 0; lastX = -1; lastY = -1;
    }
    function sketchpad_mouseMove(e) { 
      getMousePos(e);
      if(mouseDown == 1) drawLine(ctx, mouseX, mouseY, 4);
    }
    function getMousePos(e) {
      if(!e) var e = event;
      if(e.offsetX) {
        mouseX = e.offsetX; mouseY = e.offsetY;
      } else if(e.layerX) {
        mouseX = e.layerX; mouseY = e.layerY;
      }
     }
    function sketchpad_touchStart() {
      getTouchPos();
      drawLine(ctx, touchX, touchY, 4);
      event.preventDefault();
    }
    function sketchpad_touchEnd() {
      lastX = -1; lastY = -1;
    }
    function sketchpad_touchMove(e) { 
      getTouchPos(e);
      drawLine(ctx, touchX, touchY, 4); 
      event.preventDefault();
    }
    function getTouchPos(e) {
      if (!e) var e = event;
      if (e.touches) {
          if (e.touches.length == 1) {
              var touch = e.touches[0];
              var rect = canvas.getBoundingClientRect();
              touchX = touch.clientX - rect.left;
              touchY = touch.clientY - rect.top;
          }
      }
  }
  
    function load_canvas() {
      canvas = document.getElementById("sketchpad");
      if(canvas.getContext) ctx = canvas.getContext("2d");
      if(ctx) {
        canvas.addEventListener("mousedown", sketchpad_mouseDown, false);
        canvas.addEventListener("mousemove", sketchpad_mouseMove, false);
        window.addEventListener("mouseup", sketchpad_mouseUp, false);
        canvas.addEventListener("touchstart", sketchpad_touchStart, false);
        canvas.addEventListener("touchend", sketchpad_touchEnd, false);
        canvas.addEventListener("touchmove", sketchpad_touchMove, false);
      }
    }
    function save_canvas() {
      var canvas = document.getElementById("sketchpad");
      var dataURL = canvas.toDataURL();
      var xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
      xmlhttp.open("POST", "'.$this->f3->get('main_folder').'SaveImage", true);
      xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xmlhttp.send("img=" + dataURL + "&target_dir=" + encodeURIComponent("' . $this->target_dir2 . '") + "&file_name=" + encodeURIComponent("' . $this->file_name . '"))
    }
    function save_test() {
      var canvas = document.getElementById("sketchpad");
      var dataURL = canvas.toDataURL();
      document.getElementById("frmEdit").action = "'.$this->f3->get('main_folder').'SaveImage"
      document.frmEdit.submit()
    }
    
    function load_image() {
      base_image = new Image();
      base_image.src = "'.$this->f3->get('main_folder').'Image?i=' . urlencode($this->encrypt($this->target_dir . "/" . $this->file_name)) . '";
      base_image.onload = function() {
        ctx.drawImage(base_image, 0, 0);
      }
    }
    </script>

    <style>
    /* Some CSS styling */
    #sketchpadapp {
      /* Prevent nearby text being highlighted when accidentally dragging mouse outside confines of the canvas */
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
  }
  
  #sketchpad {
      /* float: left; Remove this line */
      height: <?php echo $this->height; ?>px;
      width: <?php echo $this->width; ?>px;
      border: 2px solid #888;
      background-color: white;
      position: relative; /* Necessary for correct mouse co-ords in Firefox */
  }
  </style>
  <div id="sketchpadapp">
      <canvas id="sketchpad" height="<?php echo $this->height; ?>" width="<?php echo $this->width; ?>"></canvas><br />
  </div>
  <div class="cl"></div>
  <input type="button" value="Clear" id="clearbutton" onclick="clearCanvas(canvas, ctx);">
  <script>
      load_canvas();';
	
    
    if(file_exists($this->target_dir2 . "/" . $this->file_name)) {
      $str .= "load_image();\r\n";
    }
    $str .= '</script>';
    return $str;
  }
//Signature box styling
}

?>