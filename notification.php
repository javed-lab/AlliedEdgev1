<?php

class Notification extends Controller {
    
    
    
    function __construct() {
       
        parent::__construct();
    }


    public function sendWhiteBoardNotification($site_id,$hdnDivisionID,$txtNotes){
        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
          from associations
          left join users on users.id = associations.child_user_id
          left join users2 on users2.id = associations.parent_user_id
          where associations.association_type_id in (4,12)";
          
          $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
          $curr_divs_site = $this->get_divisions($site_id, 0, 1);
          
          //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");
              
          $sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID) and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");
          
          $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
          and associations.child_user_id != " . $_SESSION['user_id'];
          
          //return $hdnDivisionID;
          $div_str = $this->get_divisions($_SESSION['user_id'], 1);
          if($div_str) $div_str = " ($div_str) ";
          //echo $sql;
          
          $result = $this->dbi->query($sql);
          
//          prd($result);
//          die;
          
          $mail = new email_q($this->dbi);
          $x = 0;
          if($result){
            while($myrow = $result->fetch_assoc()) {
            $user_id = $myrow['user_id'];            
            if(!$this->get_sql_result("select meta_value as `result` from usermeta where meta_key = 135 and user_id = $user_id")) {               
              $name = $myrow['name'];
              $email = trim($myrow['email']);
              $email2 = trim($myrow['email2']);
              $email3 = trim($myrow['email3']);
              $site = $myrow['site'];
              //$mail->AddAddress("eggaweb@gmail.com");
              $mail->Subject = "Allied Edge Comment on Whiteboard";
              $mail_msg = "Hello $name,<br /><br />The following message was added by ".$_SESSION['name'].$div_str." to the $site Whiteboard:<br /><br />$txtNotes<br /><br />Regards,<br /><a href=\"".$this->f3->get('full_url')."\">".$this->f3->get('software_name')."</a>";
              $mail->Body    = $mail_msg;
              
              if($this->validate_email($email)) { $x++; $mail->AddAddress($email); }
              if($this->validate_email($email2)) { $x++; $mail->AddAddress($email2); }
              if($this->validate_email($email3)) { $x++; $mail->AddAddress($email3); }
              //return $mail_msg;

              $ok = 0;
              
               
              
              
              
              if($email) { if($this->validate_email($email)) { $x++; $mail->AddAddress($email); $working .= "$name - $email<br/>"; $ok = 1; } }
              if($email == 'vipin.dhiri@winning.com.au'){
                
                  
              }
              //die;
              if($email2) { if($this->validate_email($email2)) { $x++; $mail->AddAddress($email2); $working .= "$name - $email2<br/>"; $ok = 1; } }
              if($email3) { if($this->validate_email($email3)) { $x++; $mail->AddAddress($email3); $working .= "$name - $email3<br/>"; $ok = 1; } }
              //die("test");
              if($ok) {
                $mail->queue_message();
                //$mail->send();
                $mail->clear_all();
              }
             //$str .= "<p>1. $email 2. $email2 3. $email3</p>";

            }

          }
          }
    }
    
    public function sendWhiteBoardUserNotification($userId,$site_id,$hdnDivisionID,$txtNotes){
        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
          from associations
          left join users on users.id = associations.child_user_id
          left join users2 on users2.id = associations.parent_user_id
          where associations.association_type_id in (4,12)";
          
          $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
          $curr_divs_site = $this->get_divisions($site_id, 0, 1);
          
          //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");
              
          $sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID) and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");
          
          $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
          and associations.child_user_id = " . $userId;
        
        
        
//        $sql = "select users.id as `user_id`, users.name, users.surname, users.email, users.email2 as `email2`, users.preferred_name as `email3`, users2.name as `site` 
//          from users
//          where users.id = ".$userId;
          
          $curr_divs_user = $this->get_divisions($_SESSION['user_id'], 0, 1);
          $curr_divs_site = $this->get_divisions($site_id, 0, 1);
          
          //$sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID)) " : "");
              
//          $sql .= ($hdnDivisionID ? " and associations.child_user_id in (select user_id from users_user_division_groups where user_group_id = $hdnDivisionID) and (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site))) " : " and associations.child_user_id in (select user_id from users_user_division_groups where (user_group_id in ($curr_divs_user) and user_group_id in ($curr_divs_site)))");
//          
//          $sql .= " and associations.parent_user_id = $site_id  and users.name NOT LIKE '%generic%' and users.name NOT LIKE '%guard%' and users.user_status_id = 30
//          and associations.child_user_id != " . $_SESSION['user_id'];
          
          //return $hdnDivisionID;
          $div_str = $this->get_divisions($_SESSION['user_id'], 1);
          if($div_str) $div_str = " ($div_str) ";
          //echo $sql;
          
          $result = $this->dbi->query($sql);
          
//          prd($result);
//          die;
          
          $mail = new email_q($this->dbi);
          $x = 0;
          if($result){
            while($myrow = $result->fetch_assoc()) {
            $user_id = $myrow['user_id'];            
           // if(!$this->get_sql_result("select meta_value as `result` from usermeta where meta_key = 135 and user_id = $user_id")) {               
              $name = $myrow['name'];
              $email = trim($myrow['email']);
              $email2 = trim($myrow['email2']);
              $email3 = trim($myrow['email3']);
              $site = $myrow['site'];
              //$mail->AddAddress("eggaweb@gmail.com");
              $mail->Subject = "Allied Edge Comment on Whiteboard";
              $mail_msg = "Hello $name,<br /><br />The following message mentioned to you was added by ".$_SESSION['name'].$div_str." to the $site Whiteboard:<br /><br />$txtNotes<br /><br />Regards,<br /><a href=\"".$this->f3->get('full_url')."\">".$this->f3->get('software_name')."</a>";
              $mail->Body    = $mail_msg;
              
              if($this->validate_email($email)) { $x++; $mail->AddAddress($email); }
              if($this->validate_email($email2)) { $x++; $mail->AddAddress($email2); }
              if($this->validate_email($email3)) { $x++; $mail->AddAddress($email3); }
              //return $mail_msg;

              $ok = 0;
              
               
              
              
              
              if($email) { if($this->validate_email($email)) { $x++; $mail->AddAddress($email); $working .= "$name - $email<br/>"; $ok = 1; } }
             
              //die;
              if($email2) { if($this->validate_email($email2)) { $x++; $mail->AddAddress($email2); $working .= "$name - $email2<br/>"; $ok = 1; } }
              if($email3) { if($this->validate_email($email3)) { $x++; $mail->AddAddress($email3); $working .= "$name - $email3<br/>"; $ok = 1; } }
              //die("test");
              if($ok) {
                $mail->queue_message();
                //$mail->send();
                $mail->clear_all();
              }
             //$str .= "<p>1. $email 2. $email2 3. $email3</p>";

           // }

          }
          }
    }

}

?>