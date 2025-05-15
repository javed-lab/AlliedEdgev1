<?php
class FileManager extends Controller {
  protected $f3;
  function __construct($f3) {
   
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
  }

  function Show() {
     
    $uid = (isset($_GET['uid']) ? $_GET['uid'] : null);
    
    $pf = (isset($_GET['pf']) ? $_GET['pf'] : null);
    $_SESSION['FM_ROOT_PATH'] = $this->f3->get('download_folder') . ($uid ? "user_files/$uid" : ($pf ? $pf : "resources"));
    $this->createDefaultSiteFolder($uid);
    $_SESSION['FM_ROOT_URL'] = $this->f3->get('full_url')."edge/downloads/". ($uid ? "user_files/$uid" : ($pf ? $pf : "resources"));
    
    
    $str = '<iframe width="100%" height="750" src="' . $this->f3->get('main_folder') . 'fileman.php'.($uid ? "?uid=$uid" : "").'"></iframe>';
    return $str;
    
  }
}
    
?>