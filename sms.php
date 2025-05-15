<?php
//error_reporting E_ALL;

class sms  {
  var $dbi;

//

//https://api.smsbroadcast.com.au/api-adv.php?username=egga&password=SendaMessageN0wm8&to=61490101445&message=test&from=AlliedMgmt

//&to=0400111222,0400222333&from=MyCompany&message=Hello%20World&ref=112233&maxsplit=5&delay=15

  public function __construct($dbi) {
    $this->dbi = $dbi;
    $this->sms_url = 'https://api.smsbroadcast.com.au/api-adv.php?username=egga&password=SendaMessageN0wm8&from=AlliedMgmt';
  }
  
  function process_phones($phone, $phone2) {
    if(substr($phone, 0, 2) != "04") $phone = $phone2;
    if(substr($phone, 0, 2) == "04") {
      $phone = preg_replace('~\D~', '', $phone); //Replaces all non-numeric characters
      $phone = "61" . substr($phone, 1);
      if(strlen($phone) == 11) {
        return $phone;
      }
    }
  }
  
  function send_message($phone, $message, $id=0) {
    $message_enc = urlencode($message);
    $url_str = $this->sms_url . "&to=$phone&message=$message_enc" . ($id ? "&ref=$id" : "");    
    $receipt = file_get_contents($url_str);
    if($id) {
      $sql = "update sms_q  set date_time = now(), receipt = '$receipt' where id = $id;";
    } else {
      $sql = "insert into sms_q (date_time, phone, message, receipt) values (now(), '$phone', '$message', '$receipt');";
    }
    $result = $this->dbi->query($sql);
    //echo "<h3>$phone, $message, $url_str</h3>";
  }
  
  function queue_message($phone, $message) {
    //$message = $this->message;
    $sql = "insert into sms_q (date_time, phone, message) values (now(), '$phone', '$message');";
    $result = $this->dbi->query($sql);
  }
  
  function sms_from_q() {
    $sql = "select id, phone, message from sms_q where receipt = '' order by id LIMIT 10";
    if($result = $this->dbi->query($sql)) {
      while($myrow = $result->fetch_assoc()) {
        $id = $myrow['id'];
        $sql = "update sms_q set is_sent = 1 where id = $id;";
        $this->dbi->query($sql);
        $this->send_message($myrow['phone'], $myrow['message'], $id);
      }
    }
  }
  
}


?>