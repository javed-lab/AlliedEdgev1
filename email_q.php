<?php
//error_reporting E_ALL;

require_once('PHPMailer/PHPMailerAutoload.php');

class email_q extends PHPMailer {
  var $dbi;

 public function __construct($dbi) {
    //include(dirname(dirname(__FILE__)) . '/db_connect.php');
//    include('db_connect.php');
    $this->dbi = $dbi;
    $this->clear_all();
    /*
    $this->Host       = 'smtp.office365.com';
    $this->Port       = 587;
    $this->SMTPSecure = 'tls';
    $this->SMTPAuth   = true;
    $this->Username   = 'alliededge@alliedmanagement.com.au';
    $this->Password   = 'theedge123?';
    $this->CharSet    = 'UTF-8';
    $this->IsHTML(true);
    $this->SetFrom('alliededge@alliedmanagement.com.au');
    */
    
    //apikey:  19af3a930330301ca16b6dc4022e0ed5
    //secret:  0a0a480570fb44f800db0d75efc0895b
    
    $this->isSMTP();
    $this->Host       = 'in-v3.mailjet.com';
    $this->Port       = 587;
    $this->SMTPSecure = 'tls';
    $this->SMTPAuth   = true;
    //$this->Username   = '06313b9f998be029b2585097c968cffc';
   // $this->Password   = '7f0fe46015dcd81b794c032d0d259378';
    
    
    $this->Username   = '19af3a930330301ca16b6dc4022e0ed5';
    $this->Password   = '0a0a480570fb44f800db0d75efc0895b';
    
    $this->CharSet    = 'UTF-8';
    $this->IsHTML(true);
    $this->SetFrom('notifications@alliedmanagement.com.au');
    
    
  }
 
  function queue_message_bkp() {
      //prd($this->Subject);
    $message_obj = mysqli_real_escape_string($this->dbi, serialize($this));
    $sql = "insert into email_q (date_time, message_obj, user_id) values (now(), \"$message_obj\", ".(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0).");";
    
    $result = $this->dbi->query($sql);
  }
  
  function get_q_message(){
    $sql = "select * from email_newq where is_sent = 0 order by id LIMIT 20";
    $result = $this->dbi->query($sql);
    
    $key = 0;
    $mailObjs = array();
    while($result && $myrow = $result->fetch_assoc()){
        $mailObjs[$key]['id']   = $myrow['id'];         
        $mailObjs[$key]['email_subject']   = $myrow['email_subject'];     
        $mailObjs[$key]['email_body']      = $myrow['email_body'];
        $mailObjs[$key]['email_to']        = unserialize($myrow['email_to']);
        $mailObjs[$key]['email_reply_to']  = unserialize($myrow['email_reply_to']);
        $key++;
        
    }
    
    return $mailObjs;
  }
  
  function update_q_message($id){
    
      $sql = "update email_newq set is_sent = 1 where id = $id;";
      $this->dbi->query($sql);
     
  }
  
  
  function queue_message(){
      //prd($this->to);
      $mailDetailSubject       = addslashes($this->Subject);
      $mailDetailBody          =  addslashes($this->Body);
      $mailDetailAddAddress    = serialize($this->to);
      $mailDetailAddReplyTo    = serialize($this->ReplyTo);
     
      // prd($mailDetailSubject);
      $sql = "insert into email_newq (date_time, email_subject,email_body,email_to,email_reply_to, user_id) "
              . "values (now(),'".$mailDetailSubject."','".$mailDetailBody."','".$mailDetailAddAddress."','".$mailDetailAddReplyTo."', ".(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0).");";
    
      
     
      $result = $this->dbi->query($sql);
  }
  
  function queue_message_sent(){
      //prd($this->to);
      $mailDetailSubject       = addslashes($this->Subject);
      $mailDetailBody          =  addslashes($this->Body);
      $mailDetailAddAddress    = serialize($this->to);
      $mailDetailAddReplyTo    = serialize($this->ReplyTo);
     
      // prd($mailDetailSubject);
      $sql = "insert into email_newq (date_time, email_subject,email_body,email_to,email_reply_to, user_id,is_sent) "
              . "values (now(),'".$mailDetailSubject."','".$mailDetailBody."','".$mailDetailAddAddress."','".$mailDetailAddReplyTo."', ".(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0).",'1');";
    
      
     
      $result = $this->dbi->query($sql);
  }
  
  
  
   function job_mail_logs($job_id,$supplier_id,$file_path_name,$to_email){      
      $mailDetailSubject       = addslashes($this->Subject);
      $mailDetailBody          = addslashes($this->Body);      
      $mailDetailAddAddress    = $to_email;
      $mailDetailAddReplyTo    = serialize($this->ReplyTo);         
      
      $sql = "insert into jobs_mail_logs (jobs_id, supplier_id, email_subject,email_body, email_to,attachement_file,email_reply_to, user_id) "
              . "values ($job_id,$supplier_id,'".$mailDetailSubject."','".$mailDetailBody."','".$mailDetailAddAddress."','".$file_path_name."','".$mailDetailAddReplyTo."', ".(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0).");";
    
      
     
      $result = $this->dbi->query($sql);
  }
  
  function clear_all() {
    $this->ClearReplyTos();
    $this->ClearAllRecipients();
    $this->ClearAttachments();
  }
  
}


?>