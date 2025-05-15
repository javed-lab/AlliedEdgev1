<?php
class Emergency extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->filter_string = "filter_string";
    $this->db_init();
  }

  function Show() {
    //$this->f3->set('content', "Fuck You Glen!");
    $this->f3->set('css', 'main.css');
    $this->f3->set('header', $header);
    $template = new Template;
    echo $template->render('emergency.htm');
    
  }
}
  
?>