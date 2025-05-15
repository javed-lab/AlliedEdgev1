<?php
class MarketingController extends Controller {
  protected $f3;
  function __construct($f3) {
    $this->download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $this->f3 = $f3;
    $this->list_obj = new data_list;
    $this->editor_obj = new data_editor;
    $this->db_init();
  }
  function GetMessage() {
    $desc_id = (isset($_GET["desc_id"]) ? $_GET["desc_id"] : null);
    
    if($desc_id) {
      $sql = "select bd_leads.comments, bd_leads.company_name from bd_leads 
      where id = " . $desc_id;
      if($result = $this->dbi->query($sql)) {
        if($myrow = $result->fetch_assoc()) {
          echo '<h3 class="fl">Comments for ' . $myrow['company_name'] . '</h3><div class="fr"><a class="list_a" href="JavaScript:hide_description()">Hide Comments/Followup</a></div><div class="cl"></div>' . nl2br($myrow['comments']);
        }
      }
      echo "<hr />";
      $this->list_obj->title = "Followup for " . $myrow['company_name'];
          
      $this->list_obj->sql = "select
        lookup_fields1.item_name as `Type 1`, bd_leads.followup_date1 as `Date 1`,
        lookup_fields2.item_name as `Type 2`, bd_leads.followup_date2 as `Date 2`,
        lookup_fields3.item_name as `Type 3`, bd_leads.followup_date3 as `Date 3`,
        lookup_fields4.item_name as `Type 4`, bd_leads.followup_date4 as `Date 4`,
        lookup_fields5.item_name as `Type 5`, bd_leads.followup_date5 as `Date 5`
        from bd_leads 
        left join lookup_fields1 on lookup_fields1.id = bd_leads.followup_type1
        left join lookup_fields2 on lookup_fields2.id = bd_leads.followup_type2
        left join lookup_fields3 on lookup_fields3.id = bd_leads.followup_type3
        left join lookup_fields4 on lookup_fields4.id = bd_leads.followup_type4
        left join lookup_fields5 on lookup_fields5.id = bd_leads.followup_type5
        where bd_leads.id = " . $desc_id;

      echo $this->list_obj->draw_list();

    }
    
  }
  
  function Leads() {

    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    if($download_xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = "select bd_leads.id as `idin`, REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead Type`, lookup_fields1.item_name as `Industry Type`, CONCAT(users.name, ' ', users.surname) as `Sales Rep`, 
      lookup_fields3.item_name as `Approach Type`,
      if(bd_leads.approach_date != '0000-00-00', DATE_FORMAT(bd_leads.approach_date, '%d-%b-%Y'), '') as `Approach Date`,
      CONCAT(bd_leads.company_name, if(bd_leads.site_name != '', CONCAT(' / ', bd_leads.site_name), '')) as `Company Name/Site`, 
      bd_leads.address as `Address`, bd_leads.phone as `Phone`, bd_leads.website as `Website`, 
      bd_leads.client_rep_name as `Client Rep`, bd_leads.client_rep_position as `Rep Position`, bd_leads.client_rep_phone as `rep Phone`, bd_leads.client_rep_email as `Rep Email`, 
      bd_leads.provider_name as `Provider Name`,
      if(bd_leads.contract_start_date != '0000-00-00', DATE_FORMAT(bd_leads.contract_start_date, '%d-%b-%Y'), '') as `Contract Start`,
      if(bd_leads.contract_expiry_date != '0000-00-00', DATE_FORMAT(bd_leads.contract_expiry_date, '%d-%b-%Y'), '') as `Contract Expiry`,
      if(bd_leads.tender_due_date != '0000-00-00', DATE_FORMAT(bd_leads.tender_due_date, '%d-%b-%Y'), '') as `Tender Due Date`,
      DATE_FORMAT(bd_leads.tender_due_time, '%h:%i') as `Tender Due Time`
      from bd_leads
      left join lookup_fields on lookup_fields.id = bd_leads.division_id
      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
      left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
      left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
      left join users on users.id = bd_leads.rep_id
      where bd_leads.is_approached = 1
      ";
      $xl_obj->sql_xl('leads.xlsx');
    } else {
      
      $str = '
      <style>
        .description_area {
          padding: 5px;
          border: 1px solid #CCCCCC;
          background-color: #DDEEEE;
          display: none;
        }
        #xtras {
          display: none;
        }
      </style>
      <script>
      function show_description(idin) {
        document.getElementById("description_area").style.display = "block";
        $.ajax({
          type:"get",
              url:"GetMessage",
              data:{desc_id: idin, show_min: 1},
              success:function(msg){
                document.getElementById("description_area").innerHTML = msg
              }
       });
      }
      function hide_description() {
        document.getElementById("description_area").style.display = "none";
      }
      
      
      function show_hide_item(id, open_text, closed_text) {
        if(document.getElementById(id).style.display == "block") {
          document.getElementById(id).style.display = "none";
          document.getElementById("a_" + id).innerHTML = open_text;
          document.cookie = "status_" + id + "=closed; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        } else {
          document.getElementById(id).style.display = "block";
          document.getElementById("a_" + id).innerHTML = closed_text;
          document.cookie = "status_" + id + "=open; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        }
      }
      function get_cookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(";");
        for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == " ") {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
          }
        }
        return "";
      }
      
      </script>
      ';
      
      $this->list_obj->title = "Leads";
      $filter_string = "filter_string";
      $this->list_obj->sql = "select bd_leads.id as `idin`, REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead Type`, lookup_fields1.item_name as `Industry Type`, CONCAT(users.name, ' ', users.surname) as `Sales Rep`, CONCAT(lookup_fields3.item_name, if(bd_leads.approach_date != '0000-00-00', CONCAT('<br />', DATE_FORMAT(bd_leads.approach_date, '%d-%b-%Y')), '')) as `Approach<br />Type/Date`,
      CONCAT(bd_leads.company_name, if(bd_leads.site_name != '', CONCAT(' / ', bd_leads.site_name), '')) as `Company Name/Site`, 
      bd_leads.city,stat.item_name state,CONCAT(bd_leads.address, if(bd_leads.phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.phone, '</nobr>'), ''), if(bd_leads.website != '', CONCAT('<br /><a href=\"', bd_leads.website, '\">', bd_leads.website, '</a>'), '')) as `Contact`, 
      CONCAT(bd_leads.client_rep_name, if(bd_leads.client_rep_position != '', CONCAT('<br />Position: ', bd_leads.client_rep_position), ''), if(bd_leads.client_rep_phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.client_rep_phone, '<nobr>'), ''), if(bd_leads.client_rep_email != '', CONCAT('<br /><a href=\"mailto:', bd_leads.client_rep_email, '\">', bd_leads.client_rep_email, '</a>'), '')) as `Client Rep Details`, 
      bd_leads.provider_name as `Provider Name`, CONCAT(if(bd_leads.contract_start_date != '0000-00-00',  DATE_FORMAT(bd_leads.contract_start_date, '%d-%b-%Y'), ''), if(bd_leads.contract_expiry_date != '0000-00-00', CONCAT('<br />Exp: ',  DATE_FORMAT(bd_leads.contract_expiry_date, '%d-%b-%Y'))
      , '')) as `Contract<br />Start/Expiry`, CONCAT('<nobr>', if(bd_leads.tender_due_date != '0000-00-00', DATE_FORMAT(bd_leads.tender_due_date, '%a %d-%b-%Y'), ''), if(bd_leads.tender_due_time != '00:00', CONCAT('<br />@', DATE_FORMAT(bd_leads.tender_due_time, '%h:%i')), ''), '</nobr>',
      CONCAT('<br /><div style=\"color: ',
                    if(DATEDIFF(bd_leads.tender_due_date, now()) <= 0,
                      CONCAT('red\">Due ', if(DATEDIFF(bd_leads.tender_due_date, now()) = 0,
                        CONCAT('Today.'),
                        CONCAT(ABS(DATEDIFF(bd_leads.tender_due_date, now())), ' Days Ago'))),
                      if(DATEDIFF(bd_leads.tender_due_date, now()) <= 28,
                        CONCAT('orange\">', DATEDIFF(bd_leads.tender_due_date, now()), ' Days Remaining'),
                        CONCAT('green\">', ROUND(DATEDIFF(bd_leads.tender_due_date, now()) / 7), ' Weeks Remaining'))), '</div>')
      
      ) as `Tender Due`, if(bd_leads.comments != '', CONCAT('<a class=\"list_a\" href=\"JavaScript:show_description(', bd_leads.id, ')\">More &gt;&gt;</a>'), '<i>No Comments Made</i>') as `Comments`,
      'Edit' as `*`, 'Delete' as `!`
      from bd_leads
      left join states stat on stat.id = bd_leads.state 
      left join lookup_fields on lookup_fields.id = bd_leads.division_id
      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
      left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
      left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
      left join users on users.id = bd_leads.rep_id
      where bd_leads.is_approached = 1
      $filter_string
      ;";
      //return $this->list_obj->sql;
      $this->editor_obj->custom_field = "rep_id";
      $this->editor_obj->custom_value = $_SESSION['user_id'];
      $this->editor_obj->custom_field2 = "is_approached";
      $this->editor_obj->custom_value2 = 1;
      $this->editor_obj->table = "bd_leads";
      $style = 'style="width: 140px;"';
      $style_large = 'style="width: 200px;"';
      $style_comment = 'style="width: 99%; height: 80px;"';
      $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, REPLACE(item_name, ' - Lead', '') from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') and item_name LIKE('%- Lead')) a order by sort_order, item_name";
      $this->editor_obj->form_attributes = array(
        array("selDivision", "selIndustryType", "selLeadType", "selApproachType", "calApproachDate", "txtCompanyName", "txtSiteName", "txtAddress","txtCity","selState","txtPhone", "txtWebsite", "txtClientRepName", "txtClientRepPosition", "txtClientRepPhone", "txtProviderName", "calContractStartDate", "calContractExpiryDate", "txtClientRepEmail", "calTenderDueDate", "ti2TenderDueTime", "txaComment", "selFollowupType1", "calFollowupDate1", "selFollowupType2", "calFollowupDate2", "selFollowupType3", "calFollowupDate3", "selFollowupType4", "calFollowupDate4", "selFollowupType5", "calFollowupDate5"),
        array("Division", "Industry Type", "Lead Type", "Approach Type", "Approach Date", "Company Name", "Site Name", "Address","City","State", "Phone", "Website", "Client Rep Name", "Client Rep Position", "Client Rep Phone", "Provider Name", "Contract Started", "Contract Finishes", "Client Rep Email", "Tender Due Date", "Tender Due Time", "Comments", "Followup Type 1", "Followup Date 1", "Followup Type 2", "Followup Date 2", "Followup Type 3", "Followup Date 3", "Followup Type 4", "Followup Date 4", "Followup Type 5", "Followup Date 5"),
        array("division_id", "industry_type_id", "lead_type_id", "approach_type_id", "approach_date", "company_name", "site_name", "address","city","state","phone", "website", "client_rep_name", "client_rep_position", "client_rep_phone", "provider_name", "contract_start_date", "contract_expiry_date", "client_rep_email", "tender_due_date", "tender_due_time", "comments", "followup_type1", "followup_date1", "followup_type2", "followup_date2", "followup_type3", "followup_date3", "followup_type4", "followup_date4", "followup_type5", "followup_date5"),
        array($lookup_sql, $this->get_lookup("industry_type"), $this->get_lookup("lead_type"), $this->get_lookup("approach_type"),"", "", "", "","","select 0 as id,'--- Select ---' as item_name union all select id, item_name from states","","","", "", "", "", "", "", "", "", "", "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), ""),
        array($style, $style, $style, $style, $style, $style_large, $style_large, $style_large, $style, $style, $style, $style_large, $style, $style, $style, $style, $style, $style, $style,$style_large,$style_large, $style_comment, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style),
        array("c", "c", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset", "Filter"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template = '
      <div class="form-wrapper" style="">
        <div class="form-header" style="height: 23px;">
        <div class="fl">Business Development Leads</div>
        <div class="fr"><a class="list_a" href="'.$this->f3->get('main_folder').'BD/Leads?download_xl=1">Download Excel</a></div>
        </div>
        <div class="cl"></div>
        <div class="form-content">
          <div class="fl"><nobr>tselDivision</nobr><br />selDivision</div>
          <div class="fl"><nobr>tselLeadType</nobr><br />selLeadType</div>
          <div class="cl"></div>
          <div class="fl"><nobr>tselIndustryType</nobr><br />selIndustryType</div>
          <div class="fl"><nobr>tselApproachType</nobr><br />selApproachType</div>
          <div class="fl"><nobr>tcalApproachDate</nobr><br />calApproachDate</div>
          <div class="cl"></div>
          <div class="fl"><nobr>ttxtCompanyName</nobr><br />txtCompanyName</div>
          <div class="fl"><nobr>ttxtSiteName</nobr><br />txtSiteName</div>
          <div class="fl"><nobr>ttxtAddress</nobr><br />txtAddress</div>
          <div class="fl"><nobr>ttxtCity</nobr><br />txtCity</div>
          <div class="fl"><nobr>tselState</nobr><br />selState</div>          
<!--          <div class="fl"><nobr>ttxtPhone</nobr><br />txtPhone</div> -->
          <div class="fl"><nobr>ttxtWebsite</nobr><br />txtWebsite</div>
          <div class="cl"></div>
          <div class="fl"><nobr>ttxtClientRepName</nobr><br />txtClientRepName</div>
          <div class="fl"><nobr>ttxtClientRepPosition</nobr><br />txtClientRepPosition</div>
          <div class="fl"><nobr>ttxtClientRepPhone</nobr><br />txtClientRepPhone</div>
           <div class="fl"><nobr>ttxtClientRepEmail</nobr><br />txtClientRepEmail</div>
              <div class="cl"></div>
<!-- <div class="fl"><nobr>ttxtProviderName</nobr><br />txtProviderName</div>-->
          
          <div class="fl"><nobr>tcalContractStartDate</nobr><br />calContractStartDate</div>
          <div class="fl"><nobr>tcalContractExpiryDate</nobr><br />calContractExpiryDate</div>
         <div class="cl"></div>
          <div class="fl"><nobr>tcalTenderDueDate</nobr><br />calTenderDueDate</div>
          <div class="fl"><nobr>tti2TenderDueTime</nobr><br />ti2TenderDueTime</div>
          <div class="cl"></div>
          <a id="a_xtras" class="list_a" href="JavaScript:show_hide_item(\'xtras\', \'More &gt;&gt;\', \'&lt;&lt; Less\');">More &gt;&gt;</a>        
          <div id="xtras">
            <div class="fl"><nobr>tselFollowupType1</nobr><br />selFollowupType1</div>
            <div class="fl"><nobr>tcalFollowupDate1</nobr><br />calFollowupDate1</div>
            <div class="fl"><nobr>tselFollowupType2</nobr><br />selFollowupType2</div>
            <div class="fl"><nobr>tcalFollowupDate2</nobr><br />calFollowupDate2</div>
            <div class="fl"><nobr>tselFollowupType3</nobr><br />selFollowupType3</div>
            <div class="fl"><nobr>tcalFollowupDate3</nobr><br />calFollowupDate3</div>
            <div class="fl"><nobr>tselFollowupType4</nobr><br />selFollowupType4</div>
            <div class="fl"><nobr>tcalFollowupDate4</nobr><br />calFollowupDate4</div>
            <div class="fl"><nobr>tselFollowupType5</nobr><br />selFollowupType5</div>
            <div class="fl"><nobr>tcalFollowupDate5</nobr><br />calFollowupDate5</div>

            <div class="cl"></div>
            <nobr>ttxaComment</nobr><br />txaComment
          </div>
          <div class="cl"></div>
          '.$this->editor_obj->button_list().'
        </div>
      </div>
      
      ';
      $this->editor_obj->editor_template = 'editor_form<div class="cl"></div><div class="description_area" id="description_area"></div><div class="cl"></div>editor_list';


      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
      
      $str .= '
        <script>
        var test = get_cookie("status_xtras");
        if(test == "open") {
          show_hide_item(\'xtras\', \'More &gt;&gt;\', \'&lt;&lt; Less\');
        }
        </script>
      ';
    }
    return $str;
  }
  
  function LeadsNew() {

    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    if($download_xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = "select bd_leads.id as `idin`, REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead Type`, lookup_fields1.item_name as `Industry Type`, CONCAT(users.name, ' ', users.surname) as `Sales Rep`, 
      lookup_fields3.item_name as `Approach Type`,
      if(bd_leads.approach_date != '0000-00-00', DATE_FORMAT(bd_leads.approach_date, '%d-%b-%Y'), '') as `Approach Date`,
      CONCAT(bd_leads.company_name, if(bd_leads.site_name != '', CONCAT(' / ', bd_leads.site_name), '')) as `Company Name/Site`, 
      bd_leads.address as `Address`, bd_leads.phone as `Phone`, bd_leads.website as `Website`, 
      bd_leads.client_rep_name as `Client Rep`, bd_leads.client_rep_position as `Rep Position`, bd_leads.client_rep_phone as `rep Phone`, bd_leads.client_rep_email as `Rep Email`, 
      bd_leads.provider_name as `Provider Name`,
      if(bd_leads.contract_start_date != '0000-00-00', DATE_FORMAT(bd_leads.contract_start_date, '%d-%b-%Y'), '') as `Contract Start`,
      if(bd_leads.contract_expiry_date != '0000-00-00', DATE_FORMAT(bd_leads.contract_expiry_date, '%d-%b-%Y'), '') as `Contract Expiry`,
      if(bd_leads.tender_due_date != '0000-00-00', DATE_FORMAT(bd_leads.tender_due_date, '%d-%b-%Y'), '') as `Tender Due Date`,
      DATE_FORMAT(bd_leads.tender_due_time, '%h:%i') as `Tender Due Time`
      from bd_leads
      left join lookup_fields on lookup_fields.id = bd_leads.division_id
      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
      left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
      left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
      left join users on users.id = bd_leads.rep_id
      where bd_leads.is_approached = 1
      ";
      $xl_obj->sql_xl('leads.xlsx');
    } else {
      
      $str = '
      <style>
        .description_area {
          padding: 5px;
          border: 1px solid #CCCCCC;
          background-color: #DDEEEE;
          display: none;
        }
        #xtras {
          display: none;
        }
      </style>
      <script>
      function show_description(idin) {
        document.getElementById("description_area").style.display = "block";
        $.ajax({
          type:"get",
              url:"GetMessage",
              data:{desc_id: idin, show_min: 1},
              success:function(msg){
                document.getElementById("description_area").innerHTML = msg
              }
       });
      }
      function hide_description() {
        document.getElementById("description_area").style.display = "none";
      }
      
      
      function show_hide_item(id, open_text, closed_text) {
        if(document.getElementById(id).style.display == "block") {
          document.getElementById(id).style.display = "none";
          document.getElementById("a_" + id).innerHTML = open_text;
          document.cookie = "status_" + id + "=closed; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        } else {
          document.getElementById(id).style.display = "block";
          document.getElementById("a_" + id).innerHTML = closed_text;
          document.cookie = "status_" + id + "=open; expires=Tue, 19 Jan 2038 03:14:07 UTC; path=/"
        }
      }
      function get_cookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(";");
        for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == " ") {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
          }
        }
        return "";
      }
      
      </script>
      ';
      
      $this->list_obj->title = "Leads";
      $filter_string = "filter_string";
//      $this->list_obj->sql = "select bd_leads.id as `idin`, REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead Type`, lookup_fields1.item_name as `Industry Type`, CONCAT(users.name, ' ', users.surname) as `Sales Rep`, CONCAT(lookup_fields3.item_name, if(bd_leads.approach_date != '0000-00-00', CONCAT('<br />', DATE_FORMAT(bd_leads.approach_date, '%d-%b-%Y')), '')) as `Approach<br />Type/Date`,
//      CONCAT(bd_leads.company_name, if(bd_leads.site_name != '', CONCAT(' / ', bd_leads.site_name), '')) as `Company Name/Site`, 
//      bd_leads.city,stat.item_name state,CONCAT(bd_leads.address, if(bd_leads.phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.phone, '</nobr>'), ''), if(bd_leads.website != '', CONCAT('<br /><a href=\"', bd_leads.website, '\">', bd_leads.website, '</a>'), '')) as `Contact`, 
//      CONCAT(bd_leads.client_rep_name, if(bd_leads.client_rep_position != '', CONCAT('<br />Position: ', bd_leads.client_rep_position), ''), if(bd_leads.client_rep_phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.client_rep_phone, '<nobr>'), ''), if(bd_leads.client_rep_email != '', CONCAT('<br /><a href=\"mailto:', bd_leads.client_rep_email, '\">', bd_leads.client_rep_email, '</a>'), '')) as `Client Rep Details`, 
//      bd_leads.provider_name as `Provider Name`, CONCAT(if(bd_leads.contract_start_date != '0000-00-00',  DATE_FORMAT(bd_leads.contract_start_date, '%d-%b-%Y'), ''), if(bd_leads.contract_expiry_date != '0000-00-00', CONCAT('<br />Exp: ',  DATE_FORMAT(bd_leads.contract_expiry_date, '%d-%b-%Y'))
//      , '')) as `Contract<br />Start/Expiry`, CONCAT('<nobr>', if(bd_leads.tender_due_date != '0000-00-00', DATE_FORMAT(bd_leads.tender_due_date, '%a %d-%b-%Y'), ''), if(bd_leads.tender_due_time != '00:00', CONCAT('<br />@', DATE_FORMAT(bd_leads.tender_due_time, '%h:%i')), ''), '</nobr>',
//      CONCAT('<br /><div style=\"color: ',
//                    if(DATEDIFF(bd_leads.tender_due_date, now()) <= 0,
//                      CONCAT('red\">Due ', if(DATEDIFF(bd_leads.tender_due_date, now()) = 0,
//                        CONCAT('Today.'),
//                        CONCAT(ABS(DATEDIFF(bd_leads.tender_due_date, now())), ' Days Ago'))),
//                      if(DATEDIFF(bd_leads.tender_due_date, now()) <= 28,
//                        CONCAT('orange\">', DATEDIFF(bd_leads.tender_due_date, now()), ' Days Remaining'),
//                        CONCAT('green\">', ROUND(DATEDIFF(bd_leads.tender_due_date, now()) / 7), ' Weeks Remaining'))), '</div>')
//      
//      ) as `Tender Due`, if(bd_leads.comments != '', CONCAT('<a class=\"list_a\" href=\"JavaScript:show_description(', bd_leads.id, ')\">More &gt;&gt;</a>'), '<i>No Comments Made</i>') as `Comments`,
//      'Edit' as `*`, 'Delete' as `!`
//      from bd_leads
//      left join states stat on stat.id = bd_leads.state 
//      left join lookup_fields on lookup_fields.id = bd_leads.division_id
//      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
//      left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
//      left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
//      left join users on users.id = bd_leads.rep_id
//      where bd_leads.is_approached = 1
//      $filter_string
//      ;";
      
      //prd($_REQUEST);
      $where = "";
      $selDivision = $_REQUEST['selDivision'];
      $selLeadtype = $_REQUEST['selLeadType'];
      if($selDivision != "0" && $selLeadtype != 0){
          $leadSql = "select REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, "
                  . " lookup_fields2.item_name as `Lead_Type`,lookup_fields1.item_name as `industry_type`,count(bd_leads.industry_type_id) total_lead";
          $leadGroupBy = " group by bd_leads.division_id,bd_leads.lead_type_id,bd_leads.industry_type_id";
         
           $where .=  " and bd_leads.division_id = '".$selDivision."' and bd_leads.lead_type_id = '".$selLeadtype."'";
         
      }else if ($selDivision != "0"){
          $leadSql = "select REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead_Type`,count(bd_leads.lead_type_id) total_lead";
          $leadGroupBy = " group by bd_leads.division_id,bd_leads.lead_type_id";
          
           $where .=  " and bd_leads.division_id = '".$selDivision."'";
      
      }else{
          $leadSql = "select REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`,count(bd_leads.division_id) total_lead";
          $leadGroupBy = " group by bd_leads.division_id";
          $where .=  " and 1 ";
         // $leadGroupBy = "";
      }
      
      
      
      
       
      $leadSql .= "
      from bd_leads
      left join states stat on stat.id = bd_leads.state 
      left join lookup_fields on lookup_fields.id = bd_leads.division_id
      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
      left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
      left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
      left join users on users.id = bd_leads.rep_id
      where (bd_leads.division_id IS NOT NULL  AND bd_leads.division_id > 0) AND  bd_leads.is_approached = 1
     $filter_string
      $leadGroupBy
      ;";
      
      
   
      
      
      $this->list_obj->sql = $leadSql;
      //echo $leadSql;
      
      
      //$this->list_obj->sql = $leadSql;
      
      
      //return $this->list_obj->sql;
      $this->editor_obj->custom_field = "rep_id";
      $this->editor_obj->custom_value = $_SESSION['user_id'];
      $this->editor_obj->custom_field2 = "is_approached";
      $this->editor_obj->custom_value2 = 1;
      $this->editor_obj->table = "bd_leads";
      $style = 'style="width: 140px;"';
      $style_large = 'style="width: 200px;"';
      $style_comment = 'style="width: 99%; height: 80px;"';
      $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, REPLACE(item_name, ' - Lead', '') from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') and item_name LIKE('%- Lead')) a order by sort_order, item_name";
      $this->editor_obj->form_attributes = array(
        array("selDivision", "selIndustryType", "selLeadType", "selApproachType", "calApproachDate", "txtCompanyName", "txtSiteName", "txtAddress","txtCity","selState","txtPhone", "txtWebsite", "txtClientRepName", "txtClientRepPosition", "txtClientRepPhone", "txtProviderName", "calContractStartDate", "calContractExpiryDate", "txtClientRepEmail", "calTenderDueDate", "ti2TenderDueTime", "txaComment", "selFollowupType1", "calFollowupDate1", "selFollowupType2", "calFollowupDate2", "selFollowupType3", "calFollowupDate3", "selFollowupType4", "calFollowupDate4", "selFollowupType5", "calFollowupDate5"),
        array("Division", "Industry Type", "Lead Type", "Approach Type", "Approach Date", "Company Name", "Site Name", "Address","City","State", "Phone", "Website", "Client Rep Name", "Client Rep Position", "Client Rep Phone", "Provider Name", "Contract Started", "Contract Finishes", "Client Rep Email", "Tender Due Date", "Tender Due Time", "Comments", "Followup Type 1", "Followup Date 1", "Followup Type 2", "Followup Date 2", "Followup Type 3", "Followup Date 3", "Followup Type 4", "Followup Date 4", "Followup Type 5", "Followup Date 5"),
        array("division_id", "industry_type_id", "lead_type_id", "approach_type_id", "approach_date", "company_name", "site_name", "address","city","state","phone", "website", "client_rep_name", "client_rep_position", "client_rep_phone", "provider_name", "contract_start_date", "contract_expiry_date", "client_rep_email", "tender_due_date", "tender_due_time", "comments", "followup_type1", "followup_date1", "followup_type2", "followup_date2", "followup_type3", "followup_date3", "followup_type4", "followup_date4", "followup_type5", "followup_date5"),
        array($lookup_sql, $this->get_lookup("industry_type"), $this->get_lookup("lead_type"), $this->get_lookup("approach_type"),"", "", "", "","","select 0 as id,'--- Select ---' as item_name union all select id, item_name from states","","","", "", "", "", "", "", "", "", "", "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), "", $this->get_lookup("followup_type"), ""),
        array($style, $style, $style, $style, $style, $style_large, $style_large, $style_large, $style, $style, $style, $style_large, $style, $style, $style, $style, $style, $style, $style,$style_large,$style_large, $style_comment, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style),
        array("c", "c", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset", "Filter"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template = '
      <div class="form-wrapper" style="">
        <div class="form-header" style="height: 23px;">
        <div class="fl">Business Development Leads</div>
        <div class="fr"><a class="list_a" href="'.$this->f3->get('main_folder').'BD/Leads?download_xl=1">Download Excel</a></div>
        </div>
        <div class="cl"></div>
        <div class="form-content">
          <div class="fl"><nobr>tselDivision</nobr><br />selDivision</div>
          <div class="fl"><nobr>tselLeadType</nobr><br />selLeadType</div>
          <div class="cl"></div>
          <div class="fl"><nobr>tselIndustryType</nobr><br />selIndustryType</div>
          <div class="fl"><nobr>tselApproachType</nobr><br />selApproachType</div>
          <div class="fl"><nobr>tcalApproachDate</nobr><br />calApproachDate</div>
          <div class="cl"></div>
          <div class="fl"><nobr>ttxtCompanyName</nobr><br />txtCompanyName</div>
          <div class="fl"><nobr>ttxtSiteName</nobr><br />txtSiteName</div>
          <div class="fl"><nobr>ttxtAddress</nobr><br />txtAddress</div>
          <div class="fl"><nobr>ttxtCity</nobr><br />txtCity</div>
          <div class="fl"><nobr>tselState</nobr><br />selState</div>          
<!--          <div class="fl"><nobr>ttxtPhone</nobr><br />txtPhone</div> -->
          <div class="fl"><nobr>ttxtWebsite</nobr><br />txtWebsite</div>
          <div class="cl"></div>
          <div class="fl"><nobr>ttxtClientRepName</nobr><br />txtClientRepName</div>
          <div class="fl"><nobr>ttxtClientRepPosition</nobr><br />txtClientRepPosition</div>
          <div class="fl"><nobr>ttxtClientRepPhone</nobr><br />txtClientRepPhone</div>
           <div class="fl"><nobr>ttxtClientRepEmail</nobr><br />txtClientRepEmail</div>
              <div class="cl"></div>
<!-- <div class="fl"><nobr>ttxtProviderName</nobr><br />txtProviderName</div>-->
          
          <div class="fl"><nobr>tcalContractStartDate</nobr><br />calContractStartDate</div>
          <div class="fl"><nobr>tcalContractExpiryDate</nobr><br />calContractExpiryDate</div>
         <div class="cl"></div>
          <div class="fl"><nobr>tcalTenderDueDate</nobr><br />calTenderDueDate</div>
          <div class="fl"><nobr>tti2TenderDueTime</nobr><br />ti2TenderDueTime</div>
          <div class="cl"></div>
          <a id="a_xtras" class="list_a" href="JavaScript:show_hide_item(\'xtras\', \'More &gt;&gt;\', \'&lt;&lt; Less\');">More &gt;&gt;</a>        
          <div id="xtras">
            <div class="fl"><nobr>tselFollowupType1</nobr><br />selFollowupType1</div>
            <div class="fl"><nobr>tcalFollowupDate1</nobr><br />calFollowupDate1</div>
            <div class="fl"><nobr>tselFollowupType2</nobr><br />selFollowupType2</div>
            <div class="fl"><nobr>tcalFollowupDate2</nobr><br />calFollowupDate2</div>
            <div class="fl"><nobr>tselFollowupType3</nobr><br />selFollowupType3</div>
            <div class="fl"><nobr>tcalFollowupDate3</nobr><br />calFollowupDate3</div>
            <div class="fl"><nobr>tselFollowupType4</nobr><br />selFollowupType4</div>
            <div class="fl"><nobr>tcalFollowupDate4</nobr><br />calFollowupDate4</div>
            <div class="fl"><nobr>tselFollowupType5</nobr><br />selFollowupType5</div>
            <div class="fl"><nobr>tcalFollowupDate5</nobr><br />calFollowupDate5</div>

            <div class="cl"></div>
            <nobr>ttxaComment</nobr><br />txaComment
          </div>
          <div class="cl"></div>
          '.$this->editor_obj->button_list().'
        </div>
      </div>
      
      ';
      
      if($_REQUEST["hdnFilter"]){
        
         $filter_str = $this->editor_obj->create_search(1);
 
         $srarchSql = str_replace("filter_string", $filter_str, ($this->remove_from_filter ? str_replace($this->remove_from_filter, "",  $this->list_obj->sql) :  $this->list_obj->sql));
 
 
       $result = $this->dbi->query($srarchSql);
                while ($myrow = $result->fetch_assoc()) {
                  if($selDivision != "0" && $selLeadtype != 0){
                        $title = $myrow['Division']." ".$myrow['Lead_Type'];
                        $label = $myrow['industry_type'];
                        $value = $myrow['total_lead'];
                  }
                  else if($selDivision != "0")
                  {
                       $title = $myrow['Division'];
                       $label = $myrow['Lead_Type'];
                       $value = $myrow['total_lead'];
                  }else{
                       $title = "Divisions";
                       $label = $myrow['Division'];
                       $value = $myrow['total_lead'];
                  }
                    
                    if ($label) {
                        $labels .= "'$label',";
                        $data_items .= "$value,";
                    }
                }
                $labels = substr($labels, 0, strlen($labels) - 1);
                $data_items = substr($data_items, 0, strlen($data_items) - 1);
      
      
     // prd($data_items);
      
       $dataStat .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>';
      
       if ($title != "") {
                    $dataStat .= '<div class="fl"><h3>Graph of Results for ' . $title . '</h3>';
                   
                    $dataStat .= $this->draw_graph("cha" . preg_replace('/[^a-z0-9]+/i', '', $title), $title, $labels, $data_items);
                } else {
                    $str .= '</div><div class="fl"><h3> Record Not Found </h3>';
                }
                $dataStat .= '</div><div class="fl"></div>';
      
      }else{
          $dataStat = "";
      }
      
      
      
      
      $this->editor_obj->editor_template = 'editor_form <div class="cl"></div><div class="description_area" id="description_area"></div><div class="cl">'
              .'</div><div class="fl" style="margin-right:100px">'.$dataStat.'</div><div class="fl">editor_list</div><div class="cl"></div>';


      
      
      $str .= $this->editor_obj->draw_data_editor($this->list_obj);
      
       if(isset($_REQUEST['selDivision'])){

            $this->list_obj->sql = "select bd_leads.id as `idin`, REPLACE(lookup_fields.item_name, ' - Lead', '') as `Division`, lookup_fields2.item_name as `Lead Type`, lookup_fields1.item_name as `Industry Type`, CONCAT(users.name, ' ', users.surname) as `Sales Rep`, CONCAT(lookup_fields3.item_name, if(bd_leads.approach_date != '0000-00-00', CONCAT('<br />', DATE_FORMAT(bd_leads.approach_date, '%d-%b-%Y')), '')) as `Approach<br />Type/Date`,
            CONCAT(bd_leads.company_name, if(bd_leads.site_name != '', CONCAT(' / ', bd_leads.site_name), '')) as `Company Name/Site`, 
            bd_leads.city,stat.item_name state,CONCAT(bd_leads.address, if(bd_leads.phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.phone, '</nobr>'), ''), if(bd_leads.website != '', CONCAT('<br /><a href=\"', bd_leads.website, '\">', bd_leads.website, '</a>'), '')) as `Contact`, 
            CONCAT(bd_leads.client_rep_name, if(bd_leads.client_rep_position != '', CONCAT('<br />Position: ', bd_leads.client_rep_position), ''), if(bd_leads.client_rep_phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.client_rep_phone, '<nobr>'), ''), if(bd_leads.client_rep_email != '', CONCAT('<br /><a href=\"mailto:', bd_leads.client_rep_email, '\">', bd_leads.client_rep_email, '</a>'), '')) as `Client Rep Details`, 
            bd_leads.provider_name as `Provider Name`, CONCAT(if(bd_leads.contract_start_date != '0000-00-00',  DATE_FORMAT(bd_leads.contract_start_date, '%d-%b-%Y'), ''), if(bd_leads.contract_expiry_date != '0000-00-00', CONCAT('<br />Exp: ',  DATE_FORMAT(bd_leads.contract_expiry_date, '%d-%b-%Y'))
            , '')) as `Contract<br />Start/Expiry`, CONCAT('<nobr>', if(bd_leads.tender_due_date != '0000-00-00', DATE_FORMAT(bd_leads.tender_due_date, '%a %d-%b-%Y'), ''), if(bd_leads.tender_due_time != '00:00', CONCAT('<br />@', DATE_FORMAT(bd_leads.tender_due_time, '%h:%i')), ''), '</nobr>',
            CONCAT('<br /><div style=\"color: ',
                          if(DATEDIFF(bd_leads.tender_due_date, now()) <= 0,
                            CONCAT('red\">Due ', if(DATEDIFF(bd_leads.tender_due_date, now()) = 0,
                              CONCAT('Today.'),
                              CONCAT(ABS(DATEDIFF(bd_leads.tender_due_date, now())), ' Days Ago'))),
                            if(DATEDIFF(bd_leads.tender_due_date, now()) <= 28,
                              CONCAT('orange\">', DATEDIFF(bd_leads.tender_due_date, now()), ' Days Remaining'),
                              CONCAT('green\">', ROUND(DATEDIFF(bd_leads.tender_due_date, now()) / 7), ' Weeks Remaining'))), '</div>')

            ) as `Tender Due`, if(bd_leads.comments != '', CONCAT('<a class=\"list_a\" href=\"JavaScript:show_description(', bd_leads.id, ')\">More &gt;&gt;</a>'), '<i>No Comments Made</i>') as `Comments`,
            'Edit' as `*`, 'Delete' as `!`
            from bd_leads
            left join states stat on stat.id = bd_leads.state 
            left join lookup_fields on lookup_fields.id = bd_leads.division_id
            left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
            left join lookup_fields2 on lookup_fields2.id = bd_leads.lead_type_id
            left join lookup_fields3 on lookup_fields3.id = bd_leads.approach_type_id
            left join users on users.id = bd_leads.rep_id
            where bd_leads.is_approached = 1
            $filter_string
            ;";


            $this->editor_obj->form_template = "";

            $this->editor_obj->editor_template = '<div class="fl">editor_list</div><div class="cl"></div>';

            $str .= $this->editor_obj->draw_data_editor($this->list_obj);
       }
      
      
      
      $str .= '
        <script>
        var test = get_cookie("status_xtras");
        if(test == "open") {
          show_hide_item(\'xtras\', \'More &gt;&gt;\', \'&lt;&lt; Less\');
        }
        </script>
      ';
    }
    return $str;
  }
   function draw_graph($id, $title, $labels, $data_items) {
        $str .= '
      <div style="width:800px; text-align: centre;"><canvas id="' . $id . '"></canvas></div>
      <script>
      var ctx = document.getElementById(\'' . $id . '\').getContext(\'2d\');
      var ' . $id . ' = new Chart(ctx, {
          type: \'bar\',
          data: {
              responsive: true,
              maintainAspectRatio: false,

              labels: [' . $labels . '],
              datasets: [{
                  label: \'' . $title . '\',
                  data: [' . $data_items . '],
                  backgroundColor: [
                      \'rgba(255, 99, 132, 1)\',
                      \'rgba(54, 162, 235, 1)\',
                      \'rgba(255, 206, 86, 1)\',
                      \'rgba(75, 192, 192, 1)\',
                      \'rgba(153, 102, 255, 1)\',
                      \'rgba(255, 159, 64, 1)\',
                      \'rgba(255, 99, 132, 1)\',
                      \'rgba(54, 162, 235, 1)\',
                      \'rgba(255, 206, 86, 1)\',
                      \'rgba(75, 192, 192, 1)\',
                      \'rgba(153, 102, 255, 1)\',
                      \'rgba(255, 159, 64, 1)\'
                  ],
                  borderColor: [
                      \'rgba(255, 99, 132, 1)\',
                      \'rgba(54, 162, 235, 1)\',
                      \'rgba(255, 206, 86, 1)\',
                      \'rgba(75, 192, 192, 1)\',
                      \'rgba(153, 102, 255, 1)\',
                      \'rgba(255, 159, 64, 1)\',
                      \'rgba(255, 99, 132, 1)\',
                      \'rgba(54, 162, 235, 1)\',
                      \'rgba(255, 206, 86, 1)\',
                      \'rgba(75, 192, 192, 1)\',
                      \'rgba(153, 102, 255, 1)\',
                      \'rgba(255, 159, 64, 1)\'
                  ],
                  borderWidth: 1
              }]
          },
          options: {
              legend: {
                labels: {
                   boxWidth: 0,
                 }
              },
              scales: {
                  yAxes: [{
                      ticks: {
                          beginAtZero: true
                      }
                  }]
              }
          }
      });


      </script>      
       ';
        return $str;
    }
  function HitList() {
    
    $create_lead = (isset($_GET["create_lead"]) ? $_GET["create_lead"] : null);
    
    if($create_lead) {
      $sql = "update bd_leads set is_approached = 1 where id = $create_lead";
      //$str = "<h3>$sql</h3>";
      $this->dbi->query($sql);
      $str .= '
        <h3>Lead Created...</h3><br />
        <a class="list_a" href="HitList">&lt;&lt; Back to Hit List</a> &nbsp; &nbsp; &nbsp;
        <a class="list_a" href="Leads">Go to Lead Manager &gt;&gt;</a>
      
      ';
    } else {
      $this->list_obj->title = "Hit List";
      //$filter_string = "filter_string";
      $this->list_obj->sql = "select bd_leads.id as `idin`, 
      lookup_fields1.item_name as `Industry Type`,
      bd_leads.company_name as `Company Name`, 
      CONCAT(bd_leads.address, if(bd_leads.phone != '', CONCAT('<br /><nobr>Tel: ', bd_leads.phone, '</nobr>'), ''), if(bd_leads.website != '', CONCAT('<br /><a href=\"', bd_leads.website, '\">', bd_leads.website, '</a>'), '')) as `Contact`, CONCAT('<a class=\"list_a\" href=\"HitList?create_lead=', bd_leads.id, '\">Create Lead</a>') as `Create Lead`,
      'Edit' as `*`, 'Delete' as `!`
      from bd_leads
      left join lookup_fields1 on lookup_fields1.id = bd_leads.industry_type_id
      where bd_leads.is_approached = 0
      $filter_string
      ;";
      //return $this->list_obj->sql;
      $this->editor_obj->custom_field = "rep_id";
      $this->editor_obj->custom_value = $_SESSION['user_id'];
      $this->editor_obj->table = "bd_leads";
      $style = 'style="width: 140px;"';
      $style_large = 'style="width: 200px;"';
      $style_comment = 'style="width: 99%; height: 80px;"';
      $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, REPLACE(item_name, ' - Lead', '') from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') and item_name LIKE('%- Lead')) a order by sort_order, item_name";
      $this->editor_obj->form_attributes = array(
        array("selIndustryType", "txtCompanyName", "txtSiteName", "txtAddress", "txtPhone", "txtWebsite"),
        array("Industry Type", "Company Name", "Site Name", "Address", "Phone", "Website"),
        array("industry_type_id", "company_name", "site_name", "address", "phone", "website"),
        array($this->get_lookup("industry_type")),
        array($style, $style_large, $style_large, $style_large, $style, $style_large),
        array("c", "n", "n", "n", "n", "n")
      );
      $this->editor_obj->button_attributes = array(
        array("Add New", "Save", "Reset", "Filter"),
        array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
      );
      $this->editor_obj->form_template = '
      <div class="form-wrapper" style="">
        <div class="form-header" style="">Business Development Hit List</div>
        <div class="form-content">
          <div class="fl"><nobr>tselIndustryType</nobr><br />selIndustryType</div>
          <div class="fl"><nobr>ttxtCompanyName</nobr><br />txtCompanyName</div>
          <div class="fl"><nobr>ttxtSiteName</nobr><br />txtSiteName</div>
          <div class="fl"><nobr>ttxtAddress</nobr><br />txtAddress</div>
          <div class="fl"><nobr>ttxtPhone</nobr><br />txtPhone</div>
          <div class="fl"><nobr>ttxtWebsite</nobr><br />txtWebsite</div>
          <div class="cl"></div>
          '.$this->editor_obj->button_list().'
        </div>
      </div>';
      $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';
      $str = $this->editor_obj->draw_data_editor($this->list_obj);
    }
    return $str;
  }
  
  function get_tender_sql($id) {
    
    
    $sql = "select company_name as `Business Name`, site_name as `Site Name/Other Nicknames`,
        client_rep_name as `Client Rep`, client_rep_phone as `Client Rep Phone`, client_rep_email as `Client Rep Email`, " . 
        ($this->download_xl ? "DATE_FORMAT(tender_due_date, '%a %d-%m-%Y') as `Tender Due Date`" : "tender_due_date as `Tender Due Date`") . "
        ".(!$this->download_xl ? ",
        CONCAT('<div style=\"color: ',
              if(DATEDIFF(bd_tenders.tender_due_date, now()) <= 0,
                CONCAT('red\">Due ', if(DATEDIFF(bd_tenders.tender_due_date, now()) = 0,
                  CONCAT('Today.'),
                  CONCAT(ABS(DATEDIFF(bd_tenders.tender_due_date, now())), ' Days Ago'))),
                if(DATEDIFF(bd_tenders.tender_due_date, now()) <= 28,
                  CONCAT('orange\">', DATEDIFF(bd_tenders.tender_due_date, now()), ' Days Remaining'),
                  CONCAT('green\">', ROUND(DATEDIFF(bd_tenders.tender_due_date, now()) / 7), ' Weeks Remaining'))), '</div>') as `Time`" : "")."
        , comments as `Comments` from bd_tenders where company_id = $id and bd_tenders.tender_due_date >= now() order by bd_tenders.tender_due_date";
    return $sql;
  }
  
  function TendersDue() {
    $itm = new input_item;
    $itm->hide_filter = 1;
    $sql = "select 0 as id, '--- Select ---' as item_name union select id, item_name from companies";

    $company_id = (isset($_POST["selCompany"]) ? $_POST["selCompany"] : "");

    if($this->download_xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = $this->get_tender_sql($this->download_xl);
      $sql = "select id, item_name from companies where id = " . $this->download_xl;
      if($result = $this->dbi->query($sql)) {
        if($myrow = $result->fetch_assoc()) {
          $item_name = "Tenders For {$myrow['item_name']}";
        }
      }
      $xl_obj->sql_xl("$item_name.xlsx");
    }
    
    
    if ($_FILES["thefile"]["error"] > 0) {
      $str .= "Return Code: " . $_FILES["thefile"]["error"] . "<br>";
    } else if($_FILES["thefile"]["name"]) {
      
      //Remove when finished!!!
      $this->dbi->query("delete from bd_tenders where company_id = $company_id");
      
      require_once('app/controllers/PHPExcel.php');
      $base_dir = $this->f3->get('download_folder');
      $dl = "/excel_templates";
      move_uploaded_file($_FILES["thefile"]["tmp_name"], $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]));
      $inputFileName = $this->f3->get('upload_folder') . urlencode($_FILES["thefile"]["name"]);
      $excelReader = PHPExcel_IOFactory::createReaderForFile($inputFileName);
        try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
      } catch (Exception $e) {
        die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) 
        . '": ' . $e->getMessage());
      }
      $date_format = 'd/m/Y';
      
      $sheet = $objPHPExcel->getSheet(0);
      
      if(trim(strtoupper($sheet->getCell('A2')->getValue())) == 'DATE' || trim(strtoupper($sheet->getCell('A3')->getValue())) == 'DATE' || trim(strtoupper($sheet->getCell('A4')->getValue())) == 'DATE' || trim(strtoupper($sheet->getCell('A5')->getValue())) == 'DATE') {
        $sheet = $objPHPExcel->getSheet(1);
      }
      
      $highestRow = $sheet->getHighestRow();
      $highestColumn = $sheet->getHighestColumn();

      $field_list = array("company_name", "site_name", "client_rep_name", "client_rep_phone", "client_rep_email", "tender_due_date", "comments");
      $field_insert = implode(",", $field_list);
      
      for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        $empty_str = 1;
        $ins_str = "";
        $has_item = 0;
        foreach($rowData[0] as $k=>$v) {
          if($k < 7) {
            $ins_str .= ($k ? ", " : "");
            $v = mysqli_real_escape_string($this->dbi, trim($v));
            if($v) $has_item = 1;
            //if($k < 3) {
            if($k == 5) {  //Date
              $old_v = $v;
              $v = ($v && $v != "-" ? date("Y-m-d", strtotime($v)) : "");
  //            $str .= ($v && $v != "-" ? "<h3>$v -- $old_v</h3>" : "");
            }
            $ins_str .= "'$v'";
          }
        }
        if($has_item) $sqls .= "insert into bd_tenders (company_id, $field_insert) values ($company_id, $ins_str); ";
      }
      //$str .= "<h3>$sqls</h3>";
      $this->dbi->multi_query($sqls);
      echo $this->redirect($this->f3->get('main_folder') . "BD/TendersDue?msg=" . urlencode("Items Updated..."));
     //return $sqls;
          //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "2", $objPHPExcel->getActiveSheet()->getCell($col_from . "12")->getValue());
          //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "1", $objPHPExcel->getActiveSheet()->getCell($col_from . "11")->getValue());
          //$objPHPExcel2->getActiveSheet()->SetCellValue(chr($t) . "3", date($date_format, PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell($col_from . "13")->getValue())));
  
    }

    $str .= '
    <h3 class="fl">Tenders Due<span id="company_label"></span></h3><div class="fr" id="excel_wrap" style="display: none;"><a id="excel_link" class="list_a" href="">Download Excel</a></div><div class="cl"></div>
	  <input type="hidden" name="hdnCompanyId" id="hdnCompanyId" />
    </form>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
    ' . $itm->sel("selCompany", "", "onChange=\"show_items(this)\"", "", $this->dbi, $sql, "") . '
    <div id="file_upload" style="display: none;">
    <label for="thefile">&nbsp; &nbsp; Upload Due Tenders:</label> <input type="file" name="thefile" id="thefile"> <input type="submit" name="submit" value="Submit">
    </div>
    </form>
    
    
    ';

    $sql = "select id, item_name from companies";
    if($result = $this->dbi->query($sql)) {
      $js_arr = 'var company_ids = [';
      $id = "";
      while($myrow = $result->fetch_assoc()) {
        if($id) $js_arr .= ",";  
        $id = $myrow['id'];

        $js_arr .= "$id";

        $item_name = $myrow['item_name'];
        $str .= '
          <div id="list'.$id.'" style="display: none; margin-top: 20px;">
          <h3>Current Tenders for '.$item_name.'</h3>';
        $this->list_obj->sql = $this->get_tender_sql($id);
        //echo "<textarea>" . $this->list_obj->sql . '</textarea>';
        $str .= $this->list_obj->draw_list();
        
        $str .= '</div>';
        
      }
      $js_arr .= ']';
    }

//BD/TendersDue?download_xl=1

    $str .= '
    <script>
      function show_items(sel) {
        '.$js_arr.'
        for (var i = 0; i < company_ids.length; i++) {
          document.getElementById("list"+company_ids[i]).style.display = "none"
        }
        if(sel.selectedIndex) {
          document.getElementById("hdnCompanyId").value = sel.value
          document.getElementById("file_upload").style.display = "inline-block"
          document.getElementById("excel_wrap").style.display = "inline-block"
          document.getElementById("excel_link").href="' . $this->f3->get('main_folder') . 'BD/TendersDue?download_xl="+sel.value;
          document.getElementById("company_label").innerHTML = " for " + sel.options[sel.selectedIndex].text
          document.getElementById("list"+sel.value).style.display = "inline-block"
        } else {
          document.getElementById("company_label").innerHTML = ""
          document.getElementById("file_upload").style.display = "none"
          document.getElementById("excel_wrap").style.display = "none"
        }
      }
    </script>';

    //      ' . ($company_id ? "show_items(\"$company_id\");" : "") . '

    $str .= ($_GET['msg'] ? $this->message('Items Updated...', 2000) : "");

    return $str;
  }
  
  
  function MarketingCampaigns() {
    $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $show_notes = (isset($_GET['show_notes']) ? $_GET['show_notes'] : null);

    if($action == "save_record") {
      $save_id = $_POST['idin'];
      $sql = "select marketing_stages.id as `stage_id`, marketing_stages.item_name from marketing_campaigns 
            left join marketing_stages on marketing_stages.id = marketing_campaigns.marketing_stage_id
            where marketing_campaigns.id = $save_id";
      if($result = $this->dbi->query($sql)) {
        if($myrow = $result->fetch_assoc()) {
          $old_stage_id = $myrow['stage_id'];
          $old_stage = $myrow['item_name'];
        }
      }
    }


    if($download_xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = "";
      $xl_obj->sql_xl('marketing_campaigns.xlsx');
    } else {
      
      $str .= '';
      $filter_string = "filter_string";
      //$this->list_obj->title = "Marketing Campaigns";
      $this->list_obj->show_num_records = 0;
      $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;      $this->list_obj->nav_count = 20;
      $this->list_obj->sql = "
            select marketing_campaigns.id as idin,
            companies.item_name as `Division`, marketing_stages.item_name as `Marketing Stage`, CONCAT(users2.name, ' ', users2.surname) as `Lead`, marketing_campaigns.title as `Title`,
            " . ($show_notes ? "" : "'Edit' as `*`, 'Delete' as `!`, CONCAT('<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "BD/MarketingCampaigns?show_notes=', marketing_campaigns.id, '\">Manage Campaign</a>') as `Marketing Notes`,") . "
            DATE_FORMAT(marketing_campaigns.date_added, '%d-%b-%Y') as `Added On`, CONCAT(users.name, ' ', users.surname) as `BD Staff Member`
            FROM marketing_campaigns
            left join companies on companies.id = marketing_campaigns.company_id
            left join users on users.id = marketing_campaigns.staff_id
            left join users2 on users2.id = marketing_campaigns.lead_id
            left join marketing_stages on marketing_stages.id = marketing_campaigns.marketing_stage_id
            where " . ($show_notes ? "marketing_campaigns.id = $show_notes" : "1 $filter_string") . "
            $sort_xtra
        ";
      //$this->editor_obj->add_now = "updated_date";
      //$this->editor_obj->update_now = "updated_date";
      if($show_notes) {
        $str .= $this->list_obj->draw_list();
        $str .= '<iframe width="100%" height="1000" src="'.$this->f3->get('main_folder')."BD/MarketingNotes?lookup_id=$show_notes&show_min=1\"></iframe>";
      } else {
        $str .= '<div class="form-wrapper"><div class="form-header">Marketing Campaigns</div>';
      
        $this->editor_obj->custom_field = "staff_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "marketing_campaigns";
        $style = 'class="full_width"';
        $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"
        
        $lead_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 447 and lookup_answers.table_assoc = 'users'";
        $this->editor_obj->form_attributes = array(
                 array("selCompany", "selLead", "txtMarketingCampaignTitle", "selMarketingStage"),
                 array("Division", "Lead", "Description/Important Notes (Optional)", "Marketing Stage"),
                 array("company_id", "lead_id", "title", "marketing_stage_id"),
                 array($this->get_simple_lookup('companies'), $lead_sql, "", $this->get_simple_lookup('marketing_stages')),
                 array($style, $style, $style, $style),
                 array("c", "c", "n", "c")
        );
        $this->editor_obj->button_attributes = array(
          array("Add New", "Save", "Reset", "Filter"),
          array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
          array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
        );
        $this->editor_obj->form_template = '
                  <div class="fl med_textbox"><nobr>tselCompany</nobr><br />selCompany</div>
                  <div class="fl small_textbox"><nobr>tselMarketingStage</nobr><br />selMarketingStage</div>
                  <div class="fl med_textbox"><nobr>tselLead</nobr><br />selLead</div>
                  <div class="fl" style="width: 99%;"><nobr>ttxtMarketingCampaignTitle</nobr><br />txtMarketingCampaignTitle</div>
                  <div class="cl"></div>
                  <br />
                  '.$this->editor_obj->button_list();
                    $this->editor_obj->editor_template = '
              
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
        ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
      }      
      if($action == "add_record") {
        $save_id = $this->editor_obj->last_insert_id;
        $sql = " 
            select marketing_stages.id as `stage_id`, marketing_stages.item_name,
            marketing_stages.item_name as `stage`, CONCAT(users2.name, ' ', users2.surname) as `lead`
            FROM marketing_campaigns
            left join users2 on users2.id = marketing_campaigns.lead_id
            left join marketing_stages on marketing_stages.id = marketing_campaigns.marketing_stage_id
            where marketing_campaigns.id = $save_id";
        //return $sql;
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) {
            $save_text = "Added a Marketing Campaign for {$myrow['lead']} at the stage &quot;{$myrow['stage']}&quot;";
          }
        }
        $sql = "insert into marketing_notes (user_id, marketing_campaign_id, description) values ({$_SESSION['user_id']}, $save_id, '$save_text')";
        $this->dbi->query($sql);
      } else if($action == "save_record") {
        $save_id = $_POST['idin'];
        $sql = " 
            select marketing_stages.id as `stage_id`, marketing_stages.item_name,
            marketing_stages.item_name as `stage`, CONCAT(users2.name, ' ', users2.surname) as `lead`
            FROM marketing_campaigns
            left join users2 on users2.id = marketing_campaigns.lead_id
            left join marketing_stages on marketing_stages.id = marketing_campaigns.marketing_stage_id
            where marketing_campaigns.id = $save_id";
        if($result = $this->dbi->query($sql)) {
          if($myrow = $result->fetch_assoc()) {
            $new_stage_id = $myrow['stage_id'];
            $new_stage = $myrow['item_name'];
          }
        }
        if($old_stage && $new_stage && $old_stage != $new_stage) {
          $save_text = "Changed the marketing stage from &quot;$old_stage&quot; to &quot;$new_stage&quot;";
          $sql = "insert into marketing_notes (user_id, marketing_campaign_id, description) values ({$_SESSION['user_id']}, $save_id, '$save_text')";
          $this->dbi->query($sql);
          $sql = "insert into marketing_campaign_log (user_id, marketing_campaign_id, old_stage_id, new_stage_id) values ({$_SESSION['user_id']}, $save_id, $old_stage_id, $new_stage_id);";
          $this->dbi->query($sql);
        }
      }
      return $str;
    }
  }
  function MarketingNotes() {
    $lookup_id = (isset($_GET["lookup_id"]) ? $_GET["lookup_id"] : null);
    $this->editor_obj->xtra_id_name = "marketing_campaign_id";
    $this->editor_obj->xtra_id = $lookup_id;
    $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);
    $show_min = (isset($_GET['show_min']) ? $_GET['show_min'] : null);
    $action = (isset($_POST["hdnAction"]) ? $_POST["hdnAction"] : null);
    $summary_mode = ($lookup_id ? null : 1);

    $complete = (isset($_REQUEST['complete']) ? $_REQUEST['complete'] : null);
    $incomplete = (isset($_REQUEST['incomplete']) ? $_REQUEST['incomplete'] : null);
    $this->str = "";
    if($complete || $incomplete) {
      $id = ($complete ? $complete : $incomplete);
      $date_set = ($complete ? "now()" : "'0000-00-00'");
      $sql = "update marketing_notes set date_completed = $date_set where id = $id";
      $this->dbi->query($sql);
      $this->str = $this->message("Item Set to ".($complete ? "Completed" : "Incomplete")."...", 2000);
      //echo $this->redirect()
    }


    if($download_xl) {
      $xl_obj = new data_list;
      $xl_obj->dbi = $this->dbi;
      $xl_obj->sql = "";
      $xl_obj->sql_xl('marketing_notes.xlsx');
    } else {
      $this->list_obj->show_num_records = 0;
      $this->list_obj->form_nav = 1;   $this->list_obj->num_per_page = 100;      $this->list_obj->nav_count = 20;
      $this->list_obj->sql = "
            select marketing_notes.id as idin,

            " . ($summary_mode ? "
            companies.item_name as `Division`, marketing_stages.item_name as `Marketing Stage`, CONCAT(users2.name, ' ', users2.surname) as `Lead`, marketing_campaigns.title as `Campaign Title`,
            " : "") . "

            marketing_notes.description as `Notes`,
            DATE_FORMAT(marketing_notes.date_added, '%d-%b-%Y') as `Added On`, CONCAT(users3.name, ' ', users.surname) as `BD Staff Member`,
            DATE_FORMAT(marketing_notes.date_due, '%a %d/%b/%Y') as `Date Due`,
            if(marketing_notes.date_completed = '0000-00-00',
              CONCAT('<div style=\"color: ',
                  if(DATEDIFF(marketing_notes.date_due, now()) <= 0,
                    CONCAT('red\">Due ', if(DATEDIFF(marketing_notes.date_due, now()) = 0,
                      CONCAT('Today.'),
                      CONCAT(ABS(DATEDIFF(marketing_notes.date_due, now())), ' Days Ago'))),
                    if(DATEDIFF(marketing_notes.date_due, now()) <= 28,
                      CONCAT('orange\">', DATEDIFF(marketing_notes.date_due, now()), ' Days Remaining'),
                      CONCAT('green\">', ROUND(DATEDIFF(marketing_notes.date_due, now()) / 7), ' Weeks Remaining'))), '</div>'),
                concat('<div style=\"color: ',
                    CONCAT('#000099\">Completed ', if(DATEDIFF(marketing_notes.date_completed, now()) = 0,
                      CONCAT('Today'),
                      CONCAT(
                        IF(ABS(DATEDIFF(marketing_notes.date_completed, now())) = 1,
                          'Yesterday',
                          CONCAT(ABS(DATEDIFF(marketing_notes.date_completed, now())), ' Days Ago')
                        )
                      )))
                    )


              ) as `Remaining Time` 
            
            " . (!$this->download_xl ? ",
              " . ($summary_mode ? "" : "'Edit' as `*`, 'Delete' as `!`,")  . "
              if(marketing_notes.date_due != '0000-00-00',
              if(marketing_notes.date_completed = '0000-00-00',
                CONCAT('<a class=\"list_a\" href=\"?lookup_id=$lookup_id&show_min=$show_min&complete=', marketing_notes.id, '\">Set to Completed</a>'),
                CONCAT('<a class=\"list_a\" href=\"?lookup_id=$lookup_id&show_min=$show_min&incomplete=', marketing_notes.id, '\">Set to Not Completed</a>')
              ), '&nbsp;') as `Change To`
                  
                  
            " : "") . "
            , marketing_notes.date_completed as `Completion Date`
            
            FROM marketing_notes
            left join users on users.id = marketing_notes.user_id
            inner join marketing_campaigns on marketing_campaigns.id = marketing_notes.marketing_campaign_id
            left join companies on companies.id = marketing_campaigns.company_id
            left join users2 on users2.id = marketing_campaigns.lead_id
            left join users3 on users3.id = marketing_campaigns.staff_id
            left join marketing_stages on marketing_stages.id = marketing_campaigns.marketing_stage_id
            
            " . ($summary_mode ? "" : "where marketing_notes.marketing_campaign_id = $lookup_id")  . "
            $sort_xtra
            order by marketing_notes.date_due, users2.name
        ";
       // return "<textarea>" . $this->list_obj->sql . '</textarea>';
      $this->list_obj->title = ($summary_mode ? "Campaign Overview" : "");
      if($summary_mode) {
        $str .= $this->list_obj->draw_list();
      } else {
        //$this->list_obj->title = "Marketing Notes";
          
        //$this->editor_obj->add_now = "updated_date";
        //$this->editor_obj->update_now = "updated_date";
        $str .= '<div class="form-wrapper"><div class="form-header">Marketing Notes</div>';
        $this->editor_obj->custom_field = "user_id";
        $this->editor_obj->custom_value = $_SESSION['user_id'];
        $this->editor_obj->table = "marketing_notes";
        $style = 'class="full_width"';
        $style_notes = 'style="height: 100px; width: 95%;" '; //class="large_textbox"
        
        $lead_sql = "select 0 as id, '--- Select ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users
        inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 447 and lookup_answers.table_assoc = 'users'";
        $this->editor_obj->form_attributes = array(
                 array("calDueDate", "calDateCompleted", "txtMarketingNotes"),
                 array("Due Date", "Completion Date", "Notes"),
                 array("date_due", "date_completed", "description"),
                 array("", "", ""),
                 array($style, $style, $style),
                 array("n", "n", "c")
        );
        $this->editor_obj->button_attributes = array(
          array("Add New", "Save", "Reset"),
          array("cmdAdd", "cmdSave", "cmdReset"),
          array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
          array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
        );
        $this->editor_obj->form_template = '
                  <div class="fl huge_textbox"><nobr>ttxtMarketingNotes</nobr><br />txtMarketingNotes</div>
                  <div class="fl medium_textbox"><nobr>tcalDueDate (Optional)</nobr><br />calDueDate</div>
                  <div class="fl medium_textbox"><nobr>tcalDateCompleted (Optional)</nobr><br />calDateCompleted</div>
                  <div class="fl" style="margin-left: 5px; margin-top: 3px"><br />'.$this->editor_obj->button_list().'</div>
                  <div class="cl"></div>
                  ';
                  
                  $this->editor_obj->editor_template = '
              
                    <div class="form-content">
                    editor_form
                    </div>
                    </div>
                    editor_list
        ';
        $str .= $this->editor_obj->draw_data_editor($this->list_obj);
      }
      return $str;
    }
  }
  
}

?>