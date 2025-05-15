    <?php

    class UserController extends Controller {

        function __construct() {
            //$this->f3 = $f3;
            $this->list_obj = new data_list;

            $this->editor_obj = new data_editor;
            $this->filter_string = "filter_string";
            $this->db_init();
        }

        function ed_crypt($str, $id) {
            $salt = '$1$' . md5("$id") . '$';
            $hash = crypt($str, $salt);
            for ($x = 0; $x < 50; $x++) {
                $salt_val = $id + $x;
                $salt = '$1$' . md5("$salt_val") . '$';
                $hash = crypt($hash, $salt);
            }
            $hash = substr($hash, 12);
            return $hash;
        }

        function UserDetails() {
            $hr_user = $this->f3->get('hr_user');
        }

        function UserAdder(){
            //$this->waterMarkImage();
            //$this->waterMarkImageText();

            $main_folder = $this->f3->get('main_folder');
            $add_security = (isset($_GET['add_security']) ? $_GET['add_security'] : 0);
            $get_pw = (isset($_GET['get_pw']) ? $_GET['get_pw'] : null);
            $get_un = (isset($_GET['get_un']) ? $_GET['get_un'] : null);
            $get_divisions_ids = (isset($_GET['get_divisions_ids']) ? $_GET['get_divisions_ids'] : 0);
            if ($get_pw) {
                echo "alliedu" . $this->get_4dig_random();
                exit;
            }
            
            $deleteuser = $_REQUEST['action'];
        
            if($deleteuser == 'deleteuser'){
                
                
                $deleteUserId = $_REQUEST['deleteuserid'];

                $sitesql = "SELECT count(id) total FROM associations ass WHERE ass.association_type_id = 1 and ass.parent_user_id = ".$deleteUserId;
                $siteRec = $this->dbi->query($sitesql);
                $siteResult =  $siteRec->fetch_assoc();
            // prd($siteResult['total']);
                if($siteResult['total'] > 0) {
                    echo 'noclientdelete';
                }else
                {
                    $deleteSql = "delete from users where id= $deleteUserId";
                    $this->dbi->query($deleteSql);
                    echo 1;
                }
                die;
            }
            
            
            
            if (isset($_GET['get_divisions_ids'])) {
                $searchId = $get_divisions_ids;
                $html = $this->alliedDivision($get_divisions_ids);
                echo $html;
                exit();
            }
            // if($get_un){
            $x = 1;
            $finished = 0;
            $username_rnd = "alliedu" . $this->get_4dig_random();
            $password_rnd = "alliedu" . $this->get_4dig_random();
            while (!$finished) {
                $sql = "select id from users where username = '$username_rnd'";
                if ($result3 = $this->dbi->query($sql)) {
                    if ($result3->num_rows) {
                        $x++;
                        $username_rnd = "alliedu" . $x;
                        $password_rnd = "alliedu" . $x;
                    } else {
                        $finished = 1;
                    }
                }
            }
    //        echo $username_rnd;
    //        die;
            //}

            $form_action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
            $bd = (isset($_GET['bd']) ? $_GET['bd'] : null);

            /* $sql = "select id from users where client_id != ''";
            $result = $this->dbi->query($sql);
            $x = 100;
            while($myrow = $result->fetch_assoc()) {
            $x++;
            $idin = $myrow['id'];
            $new_id = "C$x";
            $tmp_sql .= "update users set client_id = '$new_id' where id = $idin;";
            }
            return "<textarea>$tmp_sql</textarea>"; */

            if($form_action == 'add_record') {
                $img = '';
                $docImg1 = "";
                $docImg2 = "";
                $docImg3 = "";
                $docImg4 = "";
                $docImg5 = "";
                $docImg6 = "";
                $docImg7 = "";
                $docImg8 = "";
                $docImg9 = "";
                $docImg10 = "";

                if (isset($_POST['hdnImage']))
                    $img = $_POST['hdnImage'];

                if (isset($_POST['hdnImage1']))
                    $docImg1 = $_POST['hdnImage1'];

                if (isset($_POST['hdnImage2']))
                    $docImg2 = $_POST['hdnImage2'];

                if (isset($_POST['hdnImage3']))
                    $docImg3 = $_POST['hdnImage3'];

                if (isset($_POST['hdnImage4']))
                    $docImg4 = $_POST['hdnImage4'];

                if (isset($_POST['hdnImage5']))
                    $docImg5 = $_POST['hdnImage5'];

                if (isset($_POST['hdnImage6']))
                    $docImg6 = $_POST['hdnImage6'];

                if (isset($_POST['hdnImage7']))
                    $docImg7 = $_POST['hdnImage7'];

                if (isset($_POST['hdnImage8']))
                    $docImg8 = $_POST['hdnImage8'];

                if (isset($_POST['hdnImage9']))
                    $docImg9 = $_POST['hdnImage9'];

                if (isset($_POST['hdnImage10']))
                    $docImg10 = $_POST['hdnImage10'];

                if (isset($_POST['hchkAbnNoRequired'])) {
                    if ($_POST['hchkAbnNoRequired']) {
                        $chkAbnNoRequired = "Y";
                    } else {
                        $chkAbnNoRequired = "N";
                    }
                } else {
                    $chkAbnNoRequired = "N";
                }


                /* sec1 */

                $selUserType = $_POST["selUserType"];
                //  $selUserSubType = $_POST["selUserSubType"];
                $selUserLevel = $_POST["selUserLevel"];
                if (!$selUserLevel) {
                    $selUserLevel = 10;
                }
                $txtName = $this->dbi->real_escape_string($_POST["txtName"]);
                $txtMiddleName = $this->dbi->real_escape_string($_POST["txtMiddleName"]);
                $txtSurname = $this->dbi->real_escape_string($_POST["txtSurname"]);
                $selSex = $this->dbi->real_escape_string($_POST["selSex"]);
                $txtPreferredName = $this->dbi->real_escape_string($_POST["txtPreferredName"]);
                $calDob = $this->dbi->real_escape_string($_POST["calDob"]);
                if ($calDob) {
                    if ($calDob) {
                        $obj_date = DateTime::createFromFormat('d-M-Y', $calDob);
                        $itm = $obj_date->format('Y-m-d');
                    } else {
                        $itm = "0000-00-00";
                    }
                    $calDob = $itm;
                }
                
                $cmbParentCompany = $_POST["cmbParentCompany"];

                /* sec2 */
                $txtPhone = $this->dbi->real_escape_string($_POST["txtPhone"]);
                $txtPhone2 = $this->dbi->real_escape_string($_POST["txtPhone2"]);
                $txtFax = $this->dbi->real_escape_string($_POST["txtFax"]);
                $txtEmail = $this->dbi->real_escape_string($_POST["txtEmail"]);
                $txtEmail2 = $this->dbi->real_escape_string($_POST["txtEmail2"]);
                $txtUrl = $this->dbi->real_escape_string($_POST["txtUrl"]);

                /* sec3 */
                $txtAbn = $this->dbi->real_escape_string($_POST["txtAbn"]);
                $txtAddress = $this->dbi->real_escape_string($_POST["txtAddress"]);
                $txtSuburb = $this->dbi->real_escape_string($_POST["txtSuburb"]);
                $txtPostcode = $this->dbi->real_escape_string($_POST["txtPostcode"]);
                $selState = $_POST["selState"];
                $txtLongitude = $_POST["txtLongitude"];
                $txtLatitude = $_POST["txtLatitude"];

                /* sec3 */
                $txtEmergencyContactFullName = $this->dbi->real_escape_string($_POST["txtEmergencyContactFullName"]);
                $txtEmergencyContactRelationship = $this->dbi->real_escape_string($_POST["txtEmergencyContactRelationship"]);
                $txtEmergencyContactMobile = $this->dbi->real_escape_string($_POST["txtEmergencyContactMobile"]);

                /* sec5 */
                $txtManagerInchargeName = $this->dbi->real_escape_string($_POST["txtManagerInchargeName"]);
                $txtManagerInchargeMobile = $this->dbi->real_escape_string($_POST["txtManagerInchargeMobile"]);
                $txtManagerInchargeEmail = $this->dbi->real_escape_string($_POST["txtManagerInchargeEmail"]);

                $txtManagerIncharge2Name = $this->dbi->real_escape_string($_POST["txtManagerIncharge2Name"]);
                $txtManagerIncharge2Mobile = $this->dbi->real_escape_string($_POST["txtManagerIncharge2Mobile"]);
                $txtManagerIncharge2Email = $this->dbi->real_escape_string($_POST["txtManagerIncharge2Email"]);

                /* ----- sec9 ----- */

                $selWorkingForProvider = $this->dbi->real_escape_string($_POST["selWorkingForProvider"]);
                $selProviderId = $this->dbi->real_escape_string($_POST["selProviderId"]);

                //$formAttributeArr[] = array("selWorkingForProvider","Working For Provider", "working_for_provider",$workinging_for_provider_sql, $style_med, "", "");
                //$formAttributeArr[] = array("selProviderId","Provider", "provider_id",$provider_sql, $style_med, "", "");



                /* sec11 */
                $txtTaxFileNumber = $this->dbi->real_escape_string($_POST["txtTaxFileNumber"]);
                $txtBankName = $this->dbi->real_escape_string($_POST["txtBankName"]);
                $txtBsbNumber = $this->dbi->real_escape_string($_POST["txtBsbNumber"]);
                $txtAccountNumber = $this->dbi->real_escape_string($_POST["txtAccountNumber"]);
                $txtSuperName = $this->dbi->real_escape_string($_POST["txtSuperName"]);
                $txtSuperNumber = $this->dbi->real_escape_string($_POST["txtSuperNumber"]);

                /* sec15 */

                $calCommencementDate = $this->dbi->real_escape_string($_POST["calCommencementDate"]);
                if ($calCommencementDate) {
                    if ($calCommencementDate) {
                        $obj_date = DateTime::createFromFormat('d-M-Y', $calCommencementDate);
                        $itm = $obj_date->format('Y-m-d');
                    } else {
                        $itm = "0000-00-00";
                    }
                    $calCommencementDate = $itm;
                }
                //$txtSurname = $this->dbi->real_escape_string($_POST["txtSurname"]);
                $cmbParentCompany = $_POST["hdncmbParentCompany"];
                $cmbParentSite = $_POST["hdncmbParentSite"];
                $mslParentSite = $_POST["mslParentSite"];
                $txtPostcode = $this->dbi->real_escape_string($_POST["txtPostcode"]);
                $chlDivisions = $_POST["chlDivisions"];

                $selWorkerClassification = $_POST["selWorkerClassification"];
                $cmbVisaType = $_POST["hdncmbVisaType"];

                /* Site contact Info sec12 */
                $txtSiteContactName1 = $this->dbi->real_escape_string($_POST["txtSiteContactName1"]);
                $txtSiteContactPosition1 = $this->dbi->real_escape_string($_POST["txtSiteContactPosition1"]);
                $txtSiteContactPhone1 = $this->dbi->real_escape_string($_POST["txtSiteContactPhone1"]);
                $txtSiteContactMobile1 = $this->dbi->real_escape_string($_POST["txtSiteContactMobile1"]);
                $txtSiteContactEmail1 = $this->dbi->real_escape_string($_POST["txtSiteContactEmail1"]);

                $txtSiteContactName2 = $this->dbi->real_escape_string($_POST["txtSiteContactName2"]);
                $txtSiteContactPosition2 = $this->dbi->real_escape_string($_POST["txtSiteContactPosition2"]);
                $txtSiteContactPhone2 = $this->dbi->real_escape_string($_POST["txtSiteContactPhone2"]);
                $txtSiteContactMobile2 = $this->dbi->real_escape_string($_POST["txtSiteContactMobile2"]);
                $txtSiteContactEmail2 = $this->dbi->real_escape_string($_POST["txtSiteContactEmail2"]);
                
                $txtSiteContactName3 = $this->dbi->real_escape_string($_POST["txtSiteContactName3"]);
                $txtSiteContactPosition3 = $this->dbi->real_escape_string($_POST["txtSiteContactPosition3"]);
                $txtSiteContactPhone3 = $this->dbi->real_escape_string($_POST["txtSiteContactPhone3"]);
                $txtSiteContactMobile3 = $this->dbi->real_escape_string($_POST["txtSiteContactMobile3"]);
                $txtSiteContactEmail3 = $this->dbi->real_escape_string($_POST["txtSiteContactEmail3"]);
                
                
                
                

                $txtUsername = $this->dbi->real_escape_string($_POST["txtUsername"]);
                $txtPassword = $this->dbi->real_escape_string($_POST["txtPassword"]);
                
                $txtCivilManagerId = $this->dbi->real_escape_string($_POST["civil_manager_id"]);
                $txtFacilitiesManagerId = $this->dbi->real_escape_string($_POST["facilities_manager_id"]);
                $txtPestManagerId = $this->dbi->real_escape_string($_POST["pest_manager_id"]);
                $txtSecurityManagerId = $this->dbi->real_escape_string($_POST["security_manager_id"]);
                $txtTrafficManagerId = $this->dbi->real_escape_string($_POST["traffic_manager_id"]);
                
                $txtCivilManagerId2 = $this->dbi->real_escape_string($_POST["civil_manager_id2"]);
                $txtFacilitiesManagerId2 = $this->dbi->real_escape_string($_POST["facilities_manager_id2"]);
                $txtPestManagerId2 = $this->dbi->real_escape_string($_POST["pest_manager_id2"]);
                $txtSecurityManagerId2 = $this->dbi->real_escape_string($_POST["security_manager_id2"]);
                $txtTrafficManagerId2 = $this->dbi->real_escape_string($_POST["traffic_manager_id2"]);
                
                $txtCivilManagerId3 = $this->dbi->real_escape_string($_POST["civil_manager_id3"]);
                $txtFacilitiesManagerId3 = $this->dbi->real_escape_string($_POST["facilities_manager_id3"]);
                $txtPestManagerId3 = $this->dbi->real_escape_string($_POST["pest_manager_id3"]);
                $txtSecurityManagerId3 = $this->dbi->real_escape_string($_POST["security_manager_id3"]);
                $txtTrafficManagerId3 = $this->dbi->real_escape_string($_POST["traffic_manager_id3"]);
                
                $txtCivilManagerName = $this->dbi->real_escape_string($_POST["txtCivilManagerName"]);
                $txtFacilitiesManagerName = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerName"]);
                $txtPestManagerName = $this->dbi->real_escape_string($_POST["txtPestManagerName"]);
                $txtSecurityManagerName = $this->dbi->real_escape_string($_POST["txtSecurityManagerName"]);
                $txtTrafficManagerName = $this->dbi->real_escape_string($_POST["txtTrafficManagerName"]);
                
            
                $txtCivilManagerName2 = $this->dbi->real_escape_string($_POST["txtCivilManagerName2"]);
                $txtFacilitiesManagerName2 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerName2"]);
                $txtPestManagerName2 = $this->dbi->real_escape_string($_POST["txtPestManagerName2"]);
                $txtSecurityManagerName2 = $this->dbi->real_escape_string($_POST["txtSecurityManagerName2"]);
                $txtTrafficManagerName2 = $this->dbi->real_escape_string($_POST["txtTrafficManagerName2"]);
                
                $txtCivilManagerName3 = $this->dbi->real_escape_string($_POST["txtCivilManagerName3"]);
                $txtFacilitiesManagerName3 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerName3"]);
                $txtPestManagerName3 = $this->dbi->real_escape_string($_POST["txtPestManagerName3"]);
                $txtSecurityManagerName3 = $this->dbi->real_escape_string($_POST["txtSecurityManagerName3"]);
                $txtTrafficManagerName3 = $this->dbi->real_escape_string($_POST["txtTrafficManagerName3"]);
                
                
                
                $txtCivilManagerEmail = $this->dbi->real_escape_string($_POST["txtCivilManagerEmail"]);
                $txtFacilitiesManagerEmail = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerEmail"]);
                $txtPestManagerEmail = $this->dbi->real_escape_string($_POST["txtPestManagerEmail"]);
                $txtSecurityManagerEmail = $this->dbi->real_escape_string($_POST["txtSecurityManagerEmail"]);
                $txtTrafficManagerEmail = $this->dbi->real_escape_string($_POST["txtTrafficManagerEmail"]);
                
                $txtCivilManagerEmail2 = $this->dbi->real_escape_string($_POST["txtCivilManagerEmail2"]);
                $txtFacilitiesManagerEmail2 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerEmail2"]);
                $txtPestManagerEmail2 = $this->dbi->real_escape_string($_POST["txtPestManagerEmail2"]);
                $txtSecurityManagerEmail2 = $this->dbi->real_escape_string($_POST["txtSecurityManagerEmail2"]);
                $txtTrafficManagerEmail2 = $this->dbi->real_escape_string($_POST["txtTrafficManagerEmail2"]);
                
                $txtCivilManagerEmail3 = $this->dbi->real_escape_string($_POST["txtCivilManagerEmail3"]);
                $txtFacilitiesManagerEmail3 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerEmail3"]);
                $txtPestManagerEmail3 = $this->dbi->real_escape_string($_POST["txtPestManagerEmail3"]);
                $txtSecurityManagerEmail3 = $this->dbi->real_escape_string($_POST["txtSecurityManagerEmail3"]);
                $txtTrafficManagerEmail3 = $this->dbi->real_escape_string($_POST["txtTrafficManagerEmail3"]);
                
                $txtCivilManagerMobile = $this->dbi->real_escape_string($_POST["txtCivilManagerMobile"]);
                $txtFacilitiesManagerMobile = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerMobile"]);
                $txtPestManagerMobile = $this->dbi->real_escape_string($_POST["txtPestManagerMobile"]);
                $txtSecurityManagerMobile = $this->dbi->real_escape_string($_POST["txtSecurityManagerMobile"]);
                $txtTrafficManagerMobile = $this->dbi->real_escape_string($_POST["txtTrafficManagerMobile"]);
                
                $txtCivilManagerMobile2 = $this->dbi->real_escape_string($_POST["txtCivilManagerMobile2"]);
                $txtFacilitiesManagerMobile2 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerMobile2"]);
                $txtPestManagerMobile2 = $this->dbi->real_escape_string($_POST["txtPestManagerMobile2"]);
                $txtSecurityManagerMobile2 = $this->dbi->real_escape_string($_POST["txtSecurityManagerMobile2"]);
                $txtTrafficManagerMobile2 = $this->dbi->real_escape_string($_POST["txtTrafficManagerMobile2"]);
                
                $txtCivilManagerMobile3 = $this->dbi->real_escape_string($_POST["txtCivilManagerMobile3"]);
                $txtFacilitiesManagerMobile3 = $this->dbi->real_escape_string($_POST["txtFacilitiesManagerMobile3"]);
                $txtPestManagerMobile3 = $this->dbi->real_escape_string($_POST["txtPestManagerMobile3"]);
                $txtSecurityManagerMobile3 = $this->dbi->real_escape_string($_POST["txtSecurityManagerMobile3"]);
                $txtTrafficManagerMobile3 = $this->dbi->real_escape_string($_POST["txtTrafficManagerMobile3"]);
                
                

                $sql = "select value from lookup_fields where id = $selUserType";

                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $values = $myrow['value'];
                }
                $ids = explode(",", $values);
                if ($chlDivisions) {

                    foreach ($chlDivisions as $division_id) {
                        $skip = 0;
                        foreach ($ids as $test) {
                            if ($test == $division_id)
                                $skip = 1;
                        }
                        if (!$skip) {
                            array_push($ids, $division_id);
                        }
                    }
                }

    //           print_r($ids);
    //           die;
                //return $values;
    //      return $str;
                // prd($ids);
                if (array_search(107, $ids) !== false) {

                    //If Employee
                    //        $uname = str_replace("'", "", $txtName);
                    //        $uname = current(explode(" ", $uname)); // getting the first name from the given name(s)
                    //        $usurname = str_replace("'", "", $txtSurname);
                    //        $usurname = str_replace(" ", "", $usurname);
                    //        $username = strtolower("$uname.$usurname");

                    $username_tmp = $txtUsername;
                    $x = 1;
                    $finished = 0;
                    while (!$finished) {
                        $sql = "select id from users where username = '$username_tmp'";
                        if ($result3 = $this->dbi->query($sql)) {
                            if ($result3->num_rows) {
                                $x++;
                                $username_tmp = $username . $x;
                            } else {
                                $finished = 1;
                            }
                        }
                    }
                    //$user_level = 300;
                    $user_level = $selUserLevel;
                    $user_main_type = '3';
                    $id_use = "employee_id";
                    $id_name = "Employee ID";
                    $pfix_use = "U";
                } else if (array_search(384, $ids) !== false) {
                    $selUserLevel = $username_tmp = "";
                    $user_level = 50;
                    $id_use = "client_id";
                    $id_name = "Site ID";
                    $user_main_type = '2';
                    $pfix_use = "L";
                } else if (array_search(105, $ids) !== false) {
                    $username_tmp = "";
                    $user_level = 10; //$selUserLevel;
                    $id_use = "supplier_id";
                    $id_name = "Supplier Id";
                    $user_main_type = '4';
                    $pfix_use = "S";
                } else {
                    $username_tmp = "";
                    $user_level = 10; //$selUserLevel;
                    $id_use = "client_id";
                    $id_name = "Client Id";
                    $user_main_type = '1';
                    $pfix_use = "C";
                }

                $str .= '
        <script>
            function edit_user(idin) {
                document.getElementById("idin").value = idin;
                document.frmEdit.action = "Edit/Users";
                document.frmEdit.submit();
            }
        </script>
        <input type="hidden" name="hdnFilter" id="hdnFilter" />
        <input type="hidden" name="idin" id="idin" />
        ';

                //$sql = "SELECT CONCAT('$pfix_use', max(substring(($id_use), 2) + 1) as new_code FROM users where LEFT($id_use, 1) = '$pfix_use' and substring($id_use, 2) REGEXP '^-?[0-9]+$'";
                $sql = "SELECT CONCAT('$pfix_use', max(CONVERT(substring(($id_use), 2),SIGNED)) + 1) as new_code "
                        . " FROM users where LEFT($id_use, 1) = '$pfix_use' and substring($id_use, 2) REGEXP '^-?[0-9]+$'";

                $result = $this->dbi->query($sql);
                $myrow = $result->fetch_assoc();

                if ($myrow['new_code']) {
                    $next_id = $myrow['new_code'];
                } else {
                    $next_id = $pfix_use . "1";
                }

                // $insert['user_subtype'] = $selUserSubType;
                $insert['name'] = $txtName;
                $insert['middle_name'] = $txtMiddleName;
                $insert['surname'] = $txtSurname;
                $insert['sex'] = $selSex;
                $insert['dob'] = $calDob;
                $insert['preferred_name'] = $txtPreferredName;
                $insert['company'] = $cmbParentCompany;

                $insert['phone'] = $txtPhone;
                $insert['phone2'] = $txtPhone2;
                $insert['fax'] = $txtFax;
                $insert['email'] = $txtEmail;
                $insert['email2'] = $txtEmail2;
                $insert['abn'] = $txtAbn;
                $insert['abn_no_required'] = $chkAbnNoRequired;

                $insert['url'] = $txtUrl;
                $insert['address'] = $txtAddress;
                $insert['state'] = $selState;
                $insert['suburb'] = $txtSuburb;
                $insert['latitude'] = $txtLatitude;
                $insert['longitude'] = $txtLongitude;

                $insert['postcode'] = $txtPostcode;
                $insert['commencement_date'] = $calCommencementDate;

                $insert['site_contact_name1'] = $txtSiteContactName1;
                $insert['site_contact_position1'] = $txtSiteContactPosition1;
                $insert['site_contact_phone1'] = $txtSiteContactPhone1;
                $insert['site_contact_mobile1'] = $txtSiteContactMobile1;
                $insert['site_contact_email1'] = $txtSiteContactEmail1;

                $insert['site_contact_name2'] = $txtSiteContactName2;
                $insert['site_contact_position2'] = $txtSiteContactPosition2;
                $insert['site_contact_phone2'] = $txtSiteContactPhone2;
                $insert['site_contact_mobile2'] = $txtSiteContactMobile2;
                $insert['site_contact_email2'] = $txtSiteContactEmail2;
                
                $insert['site_contact_name3'] = $txtSiteContactName3;
                $insert['site_contact_position3'] = $txtSiteContactPosition3;
                $insert['site_contact_phone3'] = $txtSiteContactPhone3;
                $insert['site_contact_mobile3'] = $txtSiteContactMobile3;
                $insert['site_contact_email3'] = $txtSiteContactEmail3;

                $insert['emergency_contact_full_name'] = $txtEmergencyContactFullName;
                $insert['emergency_contact_relationship'] = $txtEmergencyContactRelationship;
                $insert['emergency_contact_mobile'] = $txtEmergencyContactMobile;

                /* section 5 */
                $insert['manager_incharge_name'] = $txtManagerInchargeName;
                $insert['manager_incharge_mobile'] = $txtManagerInchargeMobile;
                $insert['manager_incharge_email'] = $txtManagerInchargeEmail;

                $insert['manager_incharge2_name'] = $txtManagerIncharge2Name;
                $insert['manager_incharge2_mobile'] = $txtManagerIncharge2Mobile;
                $insert['manager_incharge2_email'] = $txtManagerIncharge2Email;

                /* section 9 */
                $insert['working_for_provider'] = $selWorkingForProvider;
                $insert['provider_id'] = $selProviderId;

                $insert['tax_file_number'] = $txtTaxFileNumber;
                $insert['bank_name'] = $txtBankName;
                $insert['bsb_number'] = $txtBsbNumber;
                $insert['account_number'] = $txtAccountNumber;
                $insert['super_name'] = $txtSuperName;
                $insert['super_number'] = $txtSuperNumber;


                $insert['document1_type'] = $selDocument1Type;
                $insert['document2_type'] = $selDocument2Type;
                $insert['document3_type'] = $selDocument3Type;

                $insert['user_status_id'] = "30";
                $insert['user_level_id'] = $user_level;
                $insert['user_maintype'] = $user_main_type;
                $insert['mail_username'] = $username_tmp;
                $insert['mail_password']    = $txtPassword;
                
                
                
                $insert['civil_manager_id'] = $txtCivilManagerId;
                $insert['facilities_manager_id'] = $txtFacilitiesManagerId;
                $insert['pest_manager_id'] = $txtPestManagerId;
                $insert['security_manager_id'] = $txtSecurityManagerId;
                $insert['traffic_manager_id'] = $txtTrafficManagerId;
                
                $insert['civil_manager_id2'] = $txtCivilManagerId2;
                $insert['facilities_manager_id2'] = $txtFacilitiesManagerId2;
                $insert['pest_manager_id2'] = $txtPestManagerId2;
                $insert['security_manager_id2'] = $txtSecurityManagerId2;
                $insert['traffic_manager_id2'] = $txtTrafficManagerId2;
                
                $insert['civil_manager_id3'] = $txtCivilManagerId3;
                $insert['facilities_manager_id3'] = $txtFacilitiesManagerId3;
                $insert['pest_manager_id3'] = $txtPestManagerId3;
                $insert['security_manager_id3'] = $txtSecurityManagerId3;
                $insert['traffic_manager_id3'] = $txtTrafficManagerId3;
                
                
                
                
                $insert['civil_manager_name'] = $txtCivilManagerName;
                $insert['facilities_manager_name'] = $txtFacilitiesManagerName;
                $insert['pest_manager_name'] = $txtPestManagerName;
                $insert['security_manager_name'] = $txtSecurityManagerName;
                $insert['traffic_manager_name'] = $txtTrafficManagerName;
                
                $insert['civil_manager_name2'] = $txtCivilManagerName2;
                $insert['facilities_manager_name2'] = $txtFacilitiesManagerName2;
                $insert['pest_manager_name2'] = $txtPestManagerName2;
                $insert['security_manager_name2'] = $txtSecurityManagerName2;
                $insert['traffic_manager_name2'] = $txtTrafficManagerName2;
                
                $insert['civil_manager_name3'] = $txtCivilManagerName3;
                $insert['facilities_manager_name3'] = $txtFacilitiesManagerName3;
                $insert['pest_manager_name3'] = $txtPestManagerName3;
                $insert['security_manager_name3'] = $txtSecurityManagerName3;
                $insert['traffic_manager_name3'] = $txtTrafficManagerName3;
                            
                
                $insert['civil_manager_email'] = $txtCivilManagerEmail;
                $insert['facilities_manager_email'] = $txtFacilitiesManagerEmail;
                $insert['pest_manager_email'] = $txtPestManagerEmail;
                $insert['security_manager_email'] = $txtSecurityManagerEmail;
                $insert['traffic_manager_email'] = $txtTrafficManagerEmail;
                
                $insert['civil_manager_email2'] = $txtCivilManagerEmail2;
                $insert['facilities_manager_email2'] = $txtFacilitiesManagerEmail2;
                $insert['pest_manager_email2'] = $txtPestManagerEmail2;
                $insert['security_manager_email2'] = $txtSecurityManagerEmail2;
                $insert['traffic_manager_email2'] = $txtTrafficManagerEmail2;
                
                $insert['civil_manager_email3'] = $txtCivilManagerEmail3;
                $insert['facilities_manager_email3'] = $txtFacilitiesManagerEmail3;
                $insert['pest_manager_email3'] = $txtPestManagerEmail3;
                $insert['security_manager_email3'] = $txtSecurityManagerEmail3;
                $insert['traffic_manager_email3'] = $txtTrafficManagerEmail3;
                
                $insert['civil_manager_mobile'] = $txtCivilManagerMobile;
                $insert['facilities_manager_mobile'] = $txtFacilitiesManagerMobile;
                $insert['pest_manager_mobile'] = $txtPestManagerMobile;
                $insert['security_manager_mobile'] = $txtSecurityManagerMobile;
                $insert['traffic_manager_mobile'] = $txtTrafficManagerMobile;
                
                $insert['civil_manager_mobile2'] = $txtCivilManagerMobile2;
                $insert['facilities_manager_mobile2'] = $txtFacilitiesManagerMobile2;
                $insert['pest_manager_mobile2'] = $txtPestManagerMobile2;
                $insert['security_manager_mobile2'] = $txtSecurityManagerMobile2;
                $insert['traffic_manager_mobile2'] = $txtTrafficManagerMobile2;
                
                $insert['civil_manager_mobile3'] = $txtCivilManagerMobile3;
                $insert['facilities_manager_mobile3'] = $txtFacilitiesManagerMobile3;
                $insert['pest_manager_mobile3'] = $txtPestManagerMobile3;
                $insert['security_manager_mobile3'] = $txtSecurityManagerMobile3;
                $insert['traffic_manager_mobile3'] = $txtTrafficManagerMobile3;
                
                
                
                
                
            // $insert['create_date']      = date("Y-m-d h:i:s");
                
                //prd($_REQUEST['supplier_category']);
                

                $insert[$id_use] = $next_id;

    //      $sql = "insert into users (name,phone,fax, " . ($txtEmail ? "email," : "") . " " . ($txtEmail2 ? "email2," : "") . " url,abn,"
    //              . " address, state,suburb,commencement_date, user_status_id, user_level_id, $id_use) "
    //              . "values ('$txtName', '$txtPhone','$txtFax', " . ($txtEmail ? "'$txtEmail'," : "") . " " . ($txtEmail2 ? "'$txtEmail2'," : "") . " '$txtUrl','$txtAbn','$txtAddress','$selState','$txtSuburb','$calCommencementDate', 30, $user_level, '$next_id')";

                $last_id = $this->saveUserData($insert, $selUserType);
                if($last_id > 0){
                    $this->createDefaultSiteFolder($last_id);
                }
                if($selUserType == 2438 && $last_id && !empty($_REQUEST['supplier_category'])){                
                    $this->updateSupplierCategory($last_id,$_REQUEST['supplier_category']);                
                }

                if ($img) {
                    $flder = $this->f3->get('download_folder') . "user_files/";
                    $folder = "$flder$last_id";
                    if (!file_exists($folder)) {
                        mkdir($folder);
                        chmod($folder, 0755);
                    }
                    //save image
                    $img = str_replace(' ', '+', $img);
                    $img = substr($img, strpos($img, ",") + 1);
                    $data = base64_decode($img);
                    $img_name = basename($_POST['hdnFileName']);
                    $img_name = 'profile.jpg';
                    $file = "$folder/$img_name";
                    $success = file_put_contents($file, $data);
                    if ($success)
                        $sql = "update users set image = '$img_name' where ID = $last_id";
                    $this->dbi->query($sql);
                }

                if ($_POST["selLicenceType1"] && $last_id) {

                    /* sec10 */
                    $selLicenceType1 = $this->dbi->real_escape_string($_POST["selLicenceType1"]);
                    $txtLicenceNumber1 = $this->dbi->real_escape_string($_POST["txtLicenceNumber1"]);
                    $txtLicenceClass1 = $this->dbi->real_escape_string($_POST["txtLicenceClass1"]);
                    $calExpiryDate1 = $this->dbi->real_escape_string($_POST["calExpiryDate1"]);
                    $selLicenceState1 = $this->dbi->real_escape_string($_POST["selLicenceState1"]);
                    $selCompliance1 = $this->dbi->real_escape_string($_POST["selCompliance1"]);
                    $licence_last_id1 = $this->saveUserLicenseData($last_id, $selLicenceType1, $txtLicenceNumber1, $txtLicenceClass1, $calExpiryDate1,
                            $selLicenceState1, $selCompliance1, $docImg1, $docImg2, $selUserType);
                }
                if ($_POST["selLicenceType2"] && $last_id) {
                    $selLicenceType2 = $this->dbi->real_escape_string($_POST["selLicenceType2"]);
                    $txtLicenceNumber2 = $this->dbi->real_escape_string($_POST["txtLicenceNumber2"]);
                    $txtLicenceClass2 = $this->dbi->real_escape_string($_POST["txtLicenceClass2"]);
                    $calExpiryDate2 = $this->dbi->real_escape_string($_POST["calExpiryDate2"]);
                    $selLicenceState2 = $this->dbi->real_escape_string($_POST["selLicenceState2"]);
                    $selCompliance2 = $this->dbi->real_escape_string($_POST["selCompliance2"]);
                    $licence_last_id2 = $this->saveUserLicenseData($last_id, $selLicenceType2, $txtLicenceNumber2, $txtLicenceClass2, $calExpiryDate2,
                            $selLicenceState2, $selCompliance2, $docImg3, $docImg4, $selUserType);
                }
                if ($_POST["selLicenceType3"] && $last_id) {
                    $selLicenceType3 = $this->dbi->real_escape_string($_POST["selLicenceType3"]);
                    $txtLicenceNumber3 = $this->dbi->real_escape_string($_POST["txtLicenceNumber3"]);
                    $txtLicenceClass3 = $this->dbi->real_escape_string($_POST["txtLicenceClass3"]);
                    $calExpiryDate3 = $this->dbi->real_escape_string($_POST["calExpiryDate3"]);
                    $selLicenceState3 = $this->dbi->real_escape_string($_POST["selLicenceState3"]);
                    $selCompliance3 = $this->dbi->real_escape_string($_POST["selCompliance3"]);
                    $licence_last_id3 = $this->saveUserLicenseData($last_id, $selLicenceType3, $txtLicenceNumber3, $txtLicenceClass3, $calExpiryDate3,
                            $selLicenceState3, $selCompliance3, $docImg5, $docImg6, $selUserType);
                }

                if ($_POST["selLicenceType4"] && $last_id) {
                    $selLicenceType4 = $this->dbi->real_escape_string($_POST["selLicenceType4"]);
                    $txtLicenceNumber4 = $this->dbi->real_escape_string($_POST["txtLicenceNumber4"]);
                    $txtLicenceClass4 = $this->dbi->real_escape_string($_POST["txtLicenceClass4"]);
                    $calExpiryDate4 = $this->dbi->real_escape_string($_POST["calExpiryDate4"]);
                    $selLicenceState4 = $this->dbi->real_escape_string($_POST["selLicenceState4"]);
                    $selCompliance4 = $this->dbi->real_escape_string($_POST["selCompliance4"]);
                    $licence_last_id4 = $this->saveUserLicenseData($last_id, $selLicenceType4, $txtLicenceNumber4, $txtLicenceClass4, $calExpiryDate4,
                            $selLicenceState4, $selCompliance4, $docImg7, $docImg8, $selUserType);
                }

                if ($_POST["selLicenceType5"] && $last_id) {
                    $selLicenceType5 = $this->dbi->real_escape_string($_POST["selLicenceType5"]);
                    $txtLicenceNumber5 = $this->dbi->real_escape_string($_POST["txtLicenceNumber5"]);
                    $txtLicenceClass5 = $this->dbi->real_escape_string($_POST["txtLicenceClass5"]);
                    $calExpiryDate5 = $this->dbi->real_escape_string($_POST["calExpiryDate5"]);
                    $selLicenceState5 = $this->dbi->real_escape_string($_POST["selLicenceState5"]);
                    $selCompliance5 = $this->dbi->real_escape_string($_POST["selCompliance5"]);
                    $licence_last_id5 = $this->saveUserLicenseData($last_id, $selLicenceType5, $txtLicenceNumber5, $txtLicenceClass5, $calExpiryDate5,
                            $selLicenceState5, $selCompliance5, $docImg9, $docImg10, $selUserType);
                }

                if ($username_tmp) {
                    $pw = $this->ed_crypt(($txtPassword ? $txtPassword : $username_tmp), $last_id);
                    $sql = "update users set pw = '$pw', username = '$username_tmp' where ID = $last_id";
                    $this->dbi->query($sql);

                    $this->sendRegistrationEmail($insert['name'], $insert['email'], $insert['mail_username'], $insert['mail_password']);
                }
                $is_staff = 0;
                foreach ($ids as $id) {
                    $ins_str .= "($last_id, $id, 'users'),";
                    if ($id = 107)
                        $is_staff = 1;
                }
                //$userRoleId = $this->userRoleByLevel($insert['user_level_id']);

                $userRoleId = $this->updateUserRoleByLevel($last_id, $insert['user_level_id']);
                //echo $userRoleId;
                //die;



                $ins_str = substr($ins_str, 0, strlen($ins_str) - 1);
                $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values $ins_str;";
                //echo $sql;
                //die;
                //return "<h3>$sql</h3>";
                $this->dbi->query($sql);
                if ($selUserType == "2436" && $cmbParentCompany) {
                    $sql = "insert into associations (association_type_id, parent_user_id, child_user_id, added_by_id) values (1, $cmbParentCompany, $last_id, " . $_SESSION['user_id'] . ");";
                    //$str .= $sql;
                    $this->dbi->query($sql);
                }
                if($cmbParentSite) {
    //        $assoc_type = ($selUserType == 504 ? 12 : 4);
    //        $sql = "update users set main_site_id = $cmbParentSite where id = $last_id;";
    //        $this->dbi->query($sql);
    //        $sql = "insert into associations (association_type_id, parent_user_id, child_user_id, added_by_id) values ($assoc_type, $cmbParentSite, $last_id, " . $_SESSION['user_id'] . ");";
    //        $this->dbi->query($sql);
                }
                
                if ($mslParentSite) {
                    $assoc_type = ($selUserType == 504 ? 12 : 4);

                    $sql = "delete from users_sites where user_id = $last_id;";
                    $sql = "delete from associations where child_user_id = $last_id;";

                    $this->dbi->query($sql);
                    foreach ($mslParentSite as $sitekey => $mslSiteItem) {
                        if ($sitekey == 0) {
                            $sql = "update users set main_site_id = $mslSiteItem where id = $last_id;";
                            $this->dbi->query($sql);
                        }
                        $sql = "insert into users_sites (user_id,site_id) values ($last_id, $mslSiteItem)";
                        $this->dbi->query($sql);
                        $sql = "insert into associations (association_type_id, parent_user_id, child_user_id, added_by_id) values ($assoc_type, $mslSiteItem, $last_id, " . $_SESSION['user_id'] . ");";
                        $this->dbi->query($sql);
                    }
                }

                $str .= $this->message("User Added...", 2000);

                $meta_str = "";
                if ($selWorkerClassification) {
                    $meta_str .= "($last_id, 119, $selWorkerClassification),";
                }
                if ($cmbVisaType) {
                    $meta_str .= "($last_id, 129, $cmbVisaType),";
                }
                if ($meta_str) {
                    $meta_str = substr($meta_str, 0, strlen($meta_str) - 1);
                    $sql = "insert into usermeta (user_id, meta_key, meta_value) values $meta_str";
                    $this->dbi->query($sql);
                }
                $defaultSelectedDailyReport = 1; 
                $defaultSelectedWhiteBoardReport = 1; 
                
                if($defaultSelectedDailyReport && $selUserType == 2437) {                
                    $sqlR = "insert into usermeta (user_id, meta_key, meta_value) values ($last_id,116,1)";
    //                echo $sqlR;
    //                die;
                    $this->dbi->query($sqlR);
                }
                
                if($defaultSelectedWhiteBoardReport && $selUserType == 2437) {                
                    $sqlR = "insert into usermeta (user_id, meta_key, meta_value) values ($last_id,135,1)";
    //                echo $sqlR;
    //                die;
                    $this->dbi->query($sqlR);
                }
                
                if ($is_staff) {
                    $mat = new TrainingController($this->f3);
                    $mat->Matrix($last_id);
                }

                $sql = "select name as `Name`, surname as `Surname`, email as `Email`, phone as `Phone`, address as `Address`, suburb as `Suburb`, states.item_name as `State`, postcode as `Postcode`,
        CONCAT('<a class=\"list_a\" href=\"JavaScript:edit_user(', users.id, ')\">Edit User Details</a><a class=\"list_a\" href=\"UserCard?uid=', users.id, '\">Card</a>') as `*` 
        from users 
        left join states on states.id = users.state
        where users.id = $last_id";

                $view_details = new data_list;
                $view_details->dbi = $this->dbi;
                $view_details->title = "User Details";
                $view_details->sql = $sql;
                $str .= $view_details->draw_list();
                if ($add_security) {
                    $str .= '<br/><br/><a class="list_a" href="' . $main_folder . 'Patrols/PatrolManager">&lt;&lt; Back to Patrol Manager</a>';
                }



    //      $str .= "<textarea>{$sql}</textarea>";
            } else {

                /* view  section */
                $str .= '
        <input type="hidden" name="hdnExistingEmail" id="hdnExistingEmail">     
        ';
                $form_obj = new input_form;
                $form_obj->table = "users";
                $form_obj->dbi = $this->dbi;
                $style = 'style="width: 200px;"';
                $styleMulti = 'style="width: 500px;height: 150px !important;"';
                $style_med = 'style="width: 500px;"';
                $style_med = 'style="width: 170px;"';
                $style_small = 'style="width: 90px;"';
                $form_obj->xtra_validation = '
            function isDivisionSelected(){
                        var selectedDivisionIds = 0;
                        $(\'input[name="chlDivisions[]"]:checked\').each(function () {

                            selectedDivisionIds = 1;
                            //console.log(this.value);
                        });
                        //alert(selectedDivisionIds);
                        return selectedDivisionIds;
            }  
            var divisionSelected = isDivisionSelected();
            
            var middleName = document.getElementById("txtMiddleName").value;
            var lastName = document.getElementById("txtSurname").value;
            var caldob = document.getElementById("calDob").value;
            var txtphone2 = document.getElementById("txtPhone2").value;
            
    //           if($("#selUserType").val() == 2437 && middleName.trim() == "")
    //           {
    //                 err++;
    //                      error_msg += err + ". Please enter middle Name.\\n";
    //           }
            
                if($("#selUserType").val() == 2437 && lastName.trim() == "")
                {
                    err++;
                        error_msg += err + ". Please enter surname.\\n";
                }
                
                if($("#selUserType").val() == 2437 && caldob.trim() == "")
                {
                    err++;
                        error_msg += err + ". Please select date of birth.\\n";
                }
                
                if($("#selUserType").val() == 2437 && txtphone2.trim() == "")
                {
                    err++;
                    error_msg += err + ". Please select mobile no. \\n";
                }
        

                if($("#selUserType").val() == 2437 && divisionSelected == 0){
                        err++;
                        error_msg += err + ". Please select division.\\n";
                }




                if($("#selUserType").val() == 2437 && document.getElementById("txtEmail").value == "") {
                        err++;
                        error_msg += err + ". This Email address is missing.\\n";
                    }
            if(document.getElementById("hdnExistingEmail").value == "yes" && document.getElementById("txtEmail").value != "") {
            err++;
            error_msg += err + ". This Email address (" + document.getElementById("txtEmail").value + ") already exists, please try another email address.\\n";
            }';
                
                $userRoleName = $this->userRoleNameByUid($_SESSION['user_id']);
    //            if($userRoleName == "HR"){
    //                $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, " . ($bd ? "REPLACE(item_name, ' - Lead', '')" : "item_name") . " from lookup_fields where lookup_id = 107 AND item_name IN ('User') and item_name " . ($bd ? "" : "NOT") . " LIKE('%- Lead')) a order by sort_order, item_name";
    //            }else{
                    $lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, " . ($bd ? "REPLACE(item_name, ' - Lead', '')" : "item_name") . " from lookup_fields where lookup_id = 107 AND item_name IN ('Client','Location','User','Supplier') and item_name " . ($bd ? "" : "NOT") . " LIKE('%- Lead')) a order by sort_order, item_name";
            // }
            
                //$lookup_sql = "select * from (select 0 as id, -10000 as `sort_order`, '--- Select ---' as item_name union all select id, sort_order, " . ($bd ? "REPLACE(item_name, ' - Lead', '')" : "item_name") . " from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') and item_name " . ($bd ? "" : "NOT") . " LIKE('%- Lead')) a order by sort_order, item_name";
                
                //return $lookup_sql;
                $division_sql = "select id, item_name from companies order by item_name";
                $pfix = ($bd ? "Rep " : "");

                /* $parent_company_sql = "select * from (select 0 as id, 0 as `sort_order`, '--- Select ---' as item_name union all 
                SELECT users.id, users.name as `sort_order`, concat (users.client_id, ' - ', users.name, ' ', users.surname) as `item_name`
                FROM users
                left join user_status on user_status.id = users.user_status_id
                inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 104 and lookup_answers.table_assoc = 'users'
                and users.user_status_id = 30) a order by sort_order, item_name
                "; */

                $parent_company_sql = $this->user_dropdown(104);
                $site_sql = $this->user_select_dropdown(0); //$this->user_dropdown(384);
                $workinging_for_provider_sql = "select 'Y' as id, 'Yes' item_name union all select 'N', 'No';";
                //$provider_sql = $this->user_select_dropdown(481); //$this->user_dropdown(384);
                $provider_sql = $this->user_select_dropdown(105); //$this->user_dropdown(384);
                //
                $supplierCategory = $this->supplierCategory();
                
                
                
                
                
                //prd($provider_sql);
                $user_sub_type = "select 1 as id, '--- Select ---' as item_name union all select '2', 'Client Representative' union all select '3', 'Employee';";

                $sex_select = "select 0 as id, '--- Select ---' as item_name union all select 'M', 'Male' union all select 'F', 'Female';";
                //$sex_select = "select 'M', 'Male' union all select 'F', 'Female';";
                //prd($sex_select);
                $doctype_select = "select 'Qualification', 'Qualification' union all select 'License', 'License';";

                //$parent_company_sql = $division_sql;

                $formAttributeArr = array();
                /* section1 */
                $formAttributeArr[] = array("selUserType", ($bd ? "Division" : "Entity Type"), "name", $lookup_sql,
                    'onchange="change_fields()"', "c", "user_type");
    //             $formAttributeArr[] = array("selUserSubType","UserType", "user_subtype", $user_sub_type,
    //                '', "", "");
                $formAttributeArr[] = array("selUserLevel", "User Level", "user_level_id", $this->get_simple_lookup_access_level('user_level'), $style_med, "n", "");
                $formAttributeArr[] = array("txtName", ($bd ? "Company Name" : "Name"), "name", "", $style_med, "c", "");
                $formAttributeArr[] = array("txtMiddleName", " Middle Name", "middle_name", "", $style_med, "", "");
                $formAttributeArr[] = array("txtSurname", "Surname", "surname", "", $style_med, "", "");
                $formAttributeArr[] = array("txtPreferredName", " Short Name", "preferred_name", "", $style_med, "", "");
                $formAttributeArr[] = array("selSex", " Gender", "sex", $sex_select, $style_med, "", "");
                $formAttributeArr[] = array("calDob", "Date Of Birth", "calDob", "", $style, "", "");

                /* section2 */
                $formAttributeArr[] = array("txtPhone", $pfix . "Phone", "phone", "", $style_med, "", "");
                $formAttributeArr[] = array("txtPhone2", $pfix . "Mobile", "phone2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFax", $pfix . "Fax", "fax", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtEmail", $pfix . "Email Address", "email", "",
                    $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail').value)\"", "", "", "");
                $formAttributeArr[] = array("txtEmail2", $pfix . "Email Address2", "email2", "",
                    $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail2').value)\"", "n", "", "");
                $formAttributeArr[] = array("txtUrl", $pfix . "Website", "url", "", $style_med, "n", "");

                /* section3 */
                $formAttributeArr[] = array("txtAbn", "Abn", "abn", "", $style_med . " onChange=\"JavaScript:check_abn(document.getElementById('txtAbn').value)\"", "n", "", "");
                $formAttributeArr[] = array("chkAbnNoRequired", "No ABN is required, Invoice to be send on location", "abn_no_required", "", "", "n", "", "");
                $formAttributeArr[] = array("txtAddress", "Address", "address", "", $style, "");
                $formAttributeArr[] = array("selState", "State", "state", "select 0 as id,"
                    . " '--- Select ---' as item_name union all select id, item_name from states", "", "n", "");
                $formAttributeArr[] = array("txtSuburb", "Suburb", "suburb", "", $style, "n", "");
                $formAttributeArr[] = array("txtPostcode", "Postcode", "postcode", "", $style_small, "n", "");
                $formAttributeArr[] = array('txtLatitude', "Latitude", "latitude", "", $style, "n", "");
                $formAttributeArr[] = array('txtLongitude', "Longitude", "longitude", "", $style, "n", "");

                /* section4 */
                $formAttributeArr[] = array("txtEmergencyContactFullName", "Full Name", "emergency_contact_full_name", "", $style, "");
                $formAttributeArr[] = array("txtEmergencyContactRelationship", "Relationship", "emergency_contact_relationship", "", $style, "");
                $formAttributeArr[] = array("txtEmergencyContactMobile", "Mobile Number", "emergency_contact_mobile", "", $style, "");

                /* section5 */
                $formAttributeArr[] = array("txtManagerInchargeName", "Name", "manager_incharge_name", "", $style, "");
                $formAttributeArr[] = array("txtManagerInchargeMobile", "Mobile", "manager_incharge_mobile", "", $style, "");
                $formAttributeArr[] = array("txtManagerInchargeEmail", "Email", "manager_incharge_email", "", $style, "");

                $formAttributeArr[] = array("txtManagerIncharge2Name", "Name", "manager_incharge2_name", "", $style, "");
                $formAttributeArr[] = array("txtManagerIncharge2Mobile", "Mobile", "manager_incharge2_mobile", "", $style, "");
                $formAttributeArr[] = array("txtManagerIncharge2Email", "Email", "manager_incharge2_email", "", $style, "");

                /* section 7 */
                $formAttributeArr[] = array("selWorkingForProvider", "Working For Provider", "working_for_provider", $workinging_for_provider_sql, $style_med, "", "");
                $formAttributeArr[] = array("selProviderId", "Provider", "provider_id", $provider_sql, $styleLarge, "", "");

                /* section15 */
                $formAttributeArr[] = array("calCommencementDate", "Commencement Date", "commencement_date", "", $style, "n", "");

                /* section16 */
                $formAttributeArr[] = array("chlDivisions", "Select Allied Division:", "id", $division_sql, "", "c", "");
                
                /* section16.1 */            
    //             $formAttributeArr[] = array("txtCivilManagerName", "Civil Manager Name", "civil_manager_name", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtFacilitiesManagerName", "Facilities Manager Name", "facilities_manager_name", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtPestManagerName", "Pest Manager Name", "pest_manager_name", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtSecurityManagerName", "Security Manager Name", "security_manager_name", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtTrafficManagerName", "Traffic Manager Name", "traffic_manager_name", "", $style_med, "n", "");
    //             
    //             
    //              $formAttributeArr[] = array("txtCivilManagerEmail", "Civil Manager Email", "civil_manager_email", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtFacilitiesManagerEmail", "Facilities Manager Email", "facilities_manager_email", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtPestManagerEmail", "Pest Manager Email", "pest_manager_email", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtSecurityManagerEmail", "Security Manager Email", "security_manager_email", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtTrafficManagerEmail", "Traffic Manager Email", "traffic_manager_email", "", $style_med, "n", "");
    //            
    //             $formAttributeArr[] = array("txtCivilManagerMobile", "Civil Manager Mobile", "civil_manager_mobile", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtFacilitiesManagerMobile", "Facilities Manager Mobile", "facilities_manager_mobile", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtPestManagerMobile", "Pest Manager Mobile", "pest_manager_mobile", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtSecurityManagerMobile", "Security Manager Mobile", "security_manager_mobile", "", $style_med, "n", "");
    //             $formAttributeArr[] = array("txtTrafficManagerMobile", "Traffic Manager Mobile", "traffic_manager_mobile", "", $style_med, "n", "");
    //             
    //             
    //             /* section16.1 */
                
                // $formAttributeArr[] = array("selCivilManagerId", "Civil Manager", "civil_manager_id",$civilManagerList,$style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerName", "Civil Manager Name", "civil_manager_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerName", "Facilities Manager Name", "facilities_manager_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerName", "Pest Manager Name", "pest_manager_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerName", "Security Manager Name", "security_manager_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerName", "Traffic Manager Name", "traffic_manager_name", "", $style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerName2", "Civil Manager Name2", "civil_manager_name2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerName2", "Facilities Manager Name2", "facilities_manager_name2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerName2", "Pest Manager Name2", "pest_manager_name2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerName2", "Security Manager Name2", "security_manager_name2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerName2", "Traffic Manager Name2", "traffic_manager_name2", "", $style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerName3", "Civil Manager Name3", "civil_manager_name3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerName3", "Facilities Manager Name3", "facilities_manager_name3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerName3", "Pest Manager Name3", "pest_manager_name3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerName3", "Security Manager Name3", "security_manager_name3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerName3", "Traffic Manager Name3", "traffic_manager_name3", "", $style_med, "n", "");
                
                
                
                
                $formAttributeArr[] = array("txtCivilManagerEmail", "Civil Manager Email", "civil_manager_email", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerEmail", "Facilities Manager Email", "facilities_manager_email", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerEmail", "Pest Manager Email", "pest_manager_email", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerEmail", "Security Manager Email", "security_manager_email", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerEmail", "Traffic Manager Email", "traffic_manager_email", "", $style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerEmail2", "Civil Manager Email2", "civil_manager_email2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerEmail2", "Facilities Manager Email2", "facilities_manager_email2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerEmail2", "Pest Manager Email2", "pest_manager_email2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerEmail2", "Security Manager Email2", "security_manager_email2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerEmail2", "Traffic Manager Email2", "traffic_manager_email2", "", $style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerEmail3", "Civil Manager Email3", "civil_manager_email3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerEmail3", "Facilities Manager Email3", "facilities_manager_email3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerEmail3", "Pest Manager Email3", "pest_manager_email3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerEmail3", "Security Manager Email3", "security_manager_email3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerEmail3", "Traffic Manager Email3", "traffic_manager_email3", "", $style_med, "n", "");
                
                
                
                
                
                $formAttributeArr[] = array("txtCivilManagerMobile", "Civil Manager Mobile", "civil_manager_mobile", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerMobile", "Facilities Manager Mobile", "facilities_manager_mobile", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerMobile", "Pest Manager Mobile", "pest_manager_mobile", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerMobile", "Security Manager Mobile", "security_manager_mobile", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerMobile", "Traffic Manager Mobile", "traffic_manager_mobile", "", $style_med, "n", "");

                
                $formAttributeArr[] = array("txtCivilManagerMobile2", "Civil Manager Mobile2", "civil_manager_mobile2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerMobile2", "Facilities Manager Mobile2", "facilities_manager_mobile2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerMobile2", "Pest Manager Mobile2", "pest_manager_mobile2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerMobile2", "Security Manager Mobile2", "security_manager_mobile2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerMobile2", "Traffic Manager Mobile2", "traffic_manager_mobile2", "", $style_med, "n", "");
                
                $formAttributeArr[] = array("txtCivilManagerMobile3", "Civil Manager Mobile3", "civil_manager_mobile3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtFacilitiesManagerMobile3", "Facilities Manager Mobile3", "facilities_manager_mobile3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtPestManagerMobile3", "Pest Manager Mobile3", "pest_manager_mobile3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSecurityManagerMobile3", "Security Manager Mobile3", "security_manager_mobile3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtTrafficManagerMobile3", "Traffic Manager Mobile3", "traffic_manager_mobile3", "", $style_med, "n", "");
                
    //            

                /* section11 */
                $formAttributeArr[] = array("txtTaxFileNumber", "Tax File Number", "tax_file_number", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtBankName", "Bank Account Name", "bank_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtBsbNumber", "Bsb Number", "bsb_number", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtAccountNumber", "Account Number", "account_number", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSuperName", "Superannuation Fund", "super_name", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSuperNumber", "Superannuation Member Number", "super_number", "", $style_med, "n", "");

                /* section12 */
                $formAttributeArr[] = array("txtSiteContactName1", "Site Contact Name", "site_contact_name1", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPosition1", "Site Contact Position", "site_contact_position1", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPhone1", "Site Contact Phone", "site_contact_phone1", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactMobile1", "Site Contact Mobile", "site_contact_mobile1", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactEmail1", "Site Contact Email", "site_contact_email1", "", $style, "n", "");

                $formAttributeArr[] = array("txtSiteContactName2", "Site Contact Name", "site_contact_name2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPosition2", "Site Contact Position", "site_contact_position2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPhone2", "Site Contact Phone", "site_contact_phone2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactMobile2", "Site Contact Mobile", "site_contact_mobile2", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactEmail2", "Site Contact Email", "site_contact_email2", "", $style, "n", "");
                
                $formAttributeArr[] = array("txtSiteContactName3", "Site Contact Name", "site_contact_name3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPosition3", "Site Contact Position", "site_contact_position3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactPhone3", "Site Contact Phone", "site_contact_phone3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactMobile3", "Site Contact Mobile", "site_contact_mobile3", "", $style_med, "n", "");
                $formAttributeArr[] = array("txtSiteContactEmail3", "Site Contact Email", "site_contact_email3", "", $style, "n", "");


                $formAttributeArr[] = array("selLicenceType1", "Licence Type", "licence_type_id", $this->get_simple_lookup('licence_types'), $style . ' onChange="config_questions()"', "n", "");
                $formAttributeArr[] = array("txtLicenceNumber1", "Licence/Certificate Number", "licence_number", "", $style, "n", "");
                $formAttributeArr[] = array("txtLicenceClass1", "Licence Class (e.g. 1AC)", "licence_class", "", $style, "n", "");
                $formAttributeArr[] = array("calExpiryDate1", "Expiry Date", "expiry_date", "", $style_min, "n", "");
                $formAttributeArr[] = array("selLicenceState1", "State Issued", "state_id", $this->get_simple_lookup('states'), $style_mid, "n", "");
                $formAttributeArr[] = array("selCompliance1", "Compliance (Mandatory)", "licence_compliance_id", $this->get_lookup('licence_compliance'), $style_mid, "n", "");

                $formAttributeArr[] = array("selLicenceType2", "Licence Type", "licence_type_id", $this->get_simple_lookup('licence_types'), $style . ' onChange="config_questions()"', "n", "");
                $formAttributeArr[] = array("txtLicenceNumber2", "Licence/Certificate Number", "licence_number", "", $style, "n", "");
                $formAttributeArr[] = array("txtLicenceClass2", "Licence Class (e.g. 1AC)", "licence_class", "", $style, "n", "");
                $formAttributeArr[] = array("calExpiryDate2", "Expiry Date", "expiry_date", "", $style_min, "n", "");
                $formAttributeArr[] = array("selLicenceState2", "State Issued", "state_id", $this->get_simple_lookup('states'), $style_min, "n", "");
                $formAttributeArr[] = array("selCompliance2", "Compliance (Mandatory)", "licence_compliance_id", $this->get_lookup('licence_compliance'), $style_min, "n", "");

                $formAttributeArr[] = array("selLicenceType3", "Licence Type", "licence_type_id", $this->get_simple_lookup('licence_types'), $style . ' onChange="config_questions()"', "n", "");
                $formAttributeArr[] = array("txtLicenceNumber3", "Licence/Certificate Number", "licence_number", "", $style, "n", "");
                $formAttributeArr[] = array("txtLicenceClass3", "Licence Class (e.g. 1AC)", "licence_class", "", $style, "n", "");
                $formAttributeArr[] = array("calExpiryDate3", "Expiry Date", "expiry_date", "", $style_min, "n", "");
                $formAttributeArr[] = array("selLicenceState3", "State Issued", "state_id", $this->get_simple_lookup('states'), $style_min, "n", "");
                $formAttributeArr[] = array("selCompliance3", "Compliance (Mandatory)", "licence_compliance_id", $this->get_lookup('licence_compliance'), $style_min, "n", "");

                $formAttributeArr[] = array("selLicenceType4", "Licence Type", "licence_type_id", $this->get_simple_lookup('licence_types'), $style . ' onChange="config_questions()"', "n", "");
                $formAttributeArr[] = array("txtLicenceNumber4", "Licence/Certificate Number", "licence_number", "", $style, "n", "");
                $formAttributeArr[] = array("txtLicenceClass4", "Licence Class (e.g. 1AC)", "licence_class", "", $style, "n", "");
                $formAttributeArr[] = array("calExpiryDate4", "Expiry Date", "expiry_date", "", $style_min, "n", "");
                $formAttributeArr[] = array("selLicenceState4", "State Issued", "state_id", $this->get_simple_lookup('states'), $style_min, "n", "");
                $formAttributeArr[] = array("selCompliance4", "Compliance (Mandatory)", "licence_compliance_id", $this->get_lookup('licence_compliance'), $style_min, "n", "");

                $formAttributeArr[] = array("selLicenceType5", "Licence Type", "licence_type_id", $this->get_simple_lookup('licence_types'), $style . ' onChange="config_questions()"', "n", "");
                $formAttributeArr[] = array("txtLicenceNumber5", "Licence/Certificate Number", "licence_number", "", $style, "n", "");
                $formAttributeArr[] = array("txtLicenceClass5", "Licence Class (e.g. 1AC)", "licence_class", "", $style, "n", "");
                $formAttributeArr[] = array("calExpiryDate5", "Expiry Date", "expiry_date", "", $style_min, "n", "");
                $formAttributeArr[] = array("selLicenceState5", "State Issued", "state_id", $this->get_simple_lookup('states'), $style_min, "n", "");
                $formAttributeArr[] = array("selCompliance5", "Compliance (Mandatory)", "licence_compliance_id", $this->get_lookup('licence_compliance'), $style_min, "n", "");

    //      $formAttributeArr[] = array("selDocument1Type", "Upload Certs and License", "document1_type", $doctype_select,$style, "n", "");
    //      $formAttributeArr[] = array("selDocument2Type", "Upload Certs and License", "document2_type", $doctype_select,$style, "n", "");
    //      $formAttributeArr[] = array("selDocument3Type", "Upload Certs and License", "document3_type", $doctype_select,$style, "n", "");
                //$formAttributeArr[] = array("txtSiteContactEmail2", "Upload Certs and License", "document2_type", "",$style, "n", "");
                //$formAttributeArr[] = array("txtSiteContactEmail2", "Upload Certs and License", "document3_type", "",$style, "n", "");
    //      $formAttributeArr[] = array("txtSurname", ($bd ? "Name of Rep" : "Surname"), "surname", "", $style_med, "n", "");
    //      
    //      
    //      
    //      
    //      
    //      
    //      
    //      
                //$formAttributeArr[] = array("cmbParentSite", "Main Site", "parent_site", $site_sql, $style, "n", "");
                //  $formAttributeArr[] = array("mslParentSite", "Main Location", "parent_site", $site_sql, "multiple " . $styleMulti, "n", "");
                $formAttributeArr[] = array("cmbParentCompany", "Parent Client Name", "parent_company", $parent_company_sql, $style_med, "n", "");
                $formAttributeArr[] = array("txtUsername", "Username", "username", "", $style, "c", $username_rnd);
                $formAttributeArr[] = array("txtPassword", "Set a Password", "pw", "", $style, "c", $password_rnd);
    //      $formAttributeArr[] = array("selWorkerClassification", "Worker Classification", 
    //          "worker_classification",$this->get_lookup('worker_classification'), $style, "n", "");
    //
    //      $formAttributeArr[] = array("cmbVisaType", "Visa Type", "visa_type", $this->get_cmb_lookup('visa_type'), $style, "n", ""); 
                $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
                //print_r($resultV); die;
                $form_obj->form_attributes = $resultV;

                /*
                $form_obj->form_attributes = array(
                array("chlDivisions", "selUserType", "txtName", "txtSurname", "txtEmail", "txtPhone", "txtAddress", "txtSuburb", "selState", "txtPostcode", "cmbParentSite", "cmbParentCompany", "txtPassword", "selWorkerClassification", "cmbVisaType"),
                array("Select Optional Extra Divisions Here", ($bd ? "Division" : "Entity Type"), ($bd ? "Company Name" : "Name"), ($bd ? "Name of Rep" : "Surname"), $pfix . "Email", $pfix . "Phone", "Address", "Suburb", "State", "Postcode", "Main Site", "Parent Company", "Set a Password", "Worker Classification", "Visa Type"),
                array("id", "name", "name", "surname", "email", "phone", "address", "suburb", "state", "postcode", "parent_site", "parent_company", "pw", "worker_classification", "visa_type"),
                array($division_sql, $lookup_sql, "", "", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states", "", $site_sql, $parent_company_sql, "", $this->get_lookup('worker_classification'), $this->get_cmb_lookup('visa_type')),
                array("", 'onchange="change_fields()"', $style_med, $style_med, $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail').value)\"", $style_med, $style, $style, "", $style_small, $style_med, $style_med, $style, $style, $style),
                array("n", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n"),
                array("", "user_type", "", "", "", "", "", "", "", "", "", "")
                );
                print_r($form_obj->form_attributes); die;
                */
                $form_obj->button_attributes = array(
                    array("Add Item"),
                    array("cmdAdd"),
                    array("if(add()) this.form.submit()"),
                    array("js_function_new", "js_function_add")
                );
                /* $sql = "select id, sort_order, item_name from lookup_fields where lookup_id in (select id from lookups where item_name = 'user_type') order by sort_order, item_name";
                $result = $this->dbi->query($sql);
                $str .= "Quick Select &gt;&gt; ";
                while($myrow = $result->fetch_assoc()) {
                $idin = $myrow['id'];
                $str .= "<a class=\"list_a\" href=\"JavaScript:change_selection('".$idin."')\">" . $myrow['item_name'] . "</a>";
                } */
                // style="display: none;"
    // <div class="fl section1 user3 user4"><nobr>tselUserSubType</nobr><br />selUserSubType</div>
        
            
            $selectCategory = '<select id="supplier_category" name="supplier_category[]"  multiple style="width: 370px;height:150px !important;">';
            $selectCategory .= '<option value="">--- Select ---</option>';
                foreach($supplierCategory as $supvalue){             
                    $selectCategory .= '<option value="'.$supvalue['id'].'">'.$supvalue['category_name'].'</option>';
                }            
            $selectCategory .= '</select>';
            
            $civilManagerList = $this->divisionManagerList(2103);
            
            $selectCivilManager1 = '<select class="manager_id" id="civil_manager_id" name="civil_manager_id" manager_type="Civil" manager_num="" style="width: 150px !important;">';
            $selectCivilManager1 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($civilManagerList as $civilManager){  
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($civilManager['id'] == $_REQUEST['civil_manager_id']){
                    
                            $selected = "selected";
                        }                                  
                    }
                    $selectCivilManager1 .= '<option value="'.$civilManager['id'].'" '.$selected.' full_name="'.$civilManager['full_name'].'">'.$civilManager['item_name'].'</option>';
                }            
            $selectCivilManager1 .= '</select>';  
            
            $selectCivilManager2 = '<select class="manager_id" id="civil_manager_id2" name="civil_manager_id2" manager_type="Civil" manager_num="2" style="width:150px !important;">';
            $selectCivilManager2 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($civilManagerList as $civilManager){    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($civilManager['id'] == $_REQUEST['civil_manager_id2']){
                    
                            $selected = "selected";
                        }                                  
                    }
                    $selectCivilManager2 .= '<option value="'.$civilManager['id'].'" '.$selected.' full_name="'.$civilManager['full_name'].'">'.$civilManager['item_name'].'</option>';
                }            
            $selectCivilManager2 .= '</select>'; 
            
            $selectCivilManager3 = '<select class="manager_id" id="civil_manager_id3" name="civil_manager_id3" manager_type="Civil" manager_num="3" style="width: 150px !important;">';
            $selectCivilManager3 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($civilManagerList as $civilManager){ 
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($civilManager['id'] == $_REQUEST['civil_manager_id3']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                    $selectCivilManager3 .= '<option value="'.$civilManager['id'].'" '.$selected.' full_name="'.$civilManager['full_name'].'">'.$civilManager['item_name'].'</option>';
                }            
            $selectCivilManager3 .= '</select>'; 
            
            /* facilities */
            
            $facilitiesManagerList = $this->divisionManagerList(2100);
            
            $selectFacilitiesManager1 = '<select class="manager_id" id="facilities_manager_id" name="facilities_manager_id" manager_type="Facilities" manager_num="" style="width: 150px !important;">';
            $selectFacilitiesManager1 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($facilitiesManagerList as $facilitiesManagerManager){    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($facilitiesManagerManager['id'] == $_REQUEST['facilities_manager_id']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectFacilitiesManager1 .= '<option value="'.$facilitiesManagerManager['id'].'" '.$selected.' full_name="'.$facilitiesManagerManager['full_name'].'">'.$facilitiesManagerManager['item_name'].'</option>';
                }            
            $selectFacilitiesManager1 .= '</select>'; 
            
            $selectFacilitiesManager2 = '<select class="manager_id" id="facilities_manager_id" name="facilities_manager_id2" manager_type="Facilities" manager_num="2" style="width: 150px !important;">';
            $selectFacilitiesManager2 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($facilitiesManagerList as $facilitiesManagerManager){             
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($facilitiesManagerManager['id'] == $_REQUEST['facilities_manager_id2']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectFacilitiesManager2 .= '<option value="'.$facilitiesManagerManager['id'].'" '.$selected.' full_name="'.$facilitiesManagerManager['full_name'].'">'.$facilitiesManagerManager['item_name'].'</option>';
                }            
            $selectFacilitiesManager2 .= '</select>'; 
            
            $selectFacilitiesManager3 = '<select class="manager_id" id="facilities_manager_id3" name="facilities_manager_id3" manager_type="Facilities" manager_num="3" style="width: 150px !important;">';
            $selectFacilitiesManager3 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($facilitiesManagerList as $facilitiesManagerManager){   
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($facilitiesManagerManager['id'] == $_REQUEST['facilities_manager_id3']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                                
                    $selectFacilitiesManager3 .= '<option value="'.$facilitiesManagerManager['id'].'" '.$selected.' full_name="'.$facilitiesManagerManager['full_name'].'">'.$facilitiesManagerManager['item_name'].'</option>';
                }            
            $selectFacilitiesManager3 .= '</select>'; 
            
            
                /* Pest */
            
            $pestManagerList = $this->divisionManagerList(2102);
            
            $selectPestManager1 = '<select class="manager_id" id="pest_manager_id" name="pest_manager_id" manager_type="Pest" manager_num="" style="width: 150px !important;">';
            $selectPestManager1 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($pestManagerList as $pestManagerManager){  
                    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($pestManagerManager['id'] == $_REQUEST['pest_manager_id']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    
                    
                    
                    $selectPestManager1 .= '<option value="'.$pestManagerManager['id'].'" '.$selected.' full_name="'.$pestManagerManager['full_name'].'">'.$pestManagerManager['item_name'].'</option>';
                }            
            $selectPestManager1 .= '</select>'; 
            
            $selectPestManager2 = '<select class="manager_id" id="pest_manager_id2" name="pest_manager_id2" manager_type="Pest" manager_num="2" style="width: 150px !important;">';
            $selectPestManager2 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($pestManagerList as $pestManagerManager){             
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($pestManagerManager['id'] == $_REQUEST['pest_manager_id2']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectPestManager2 .= '<option value="'.$pestManagerManager['id'].'" '.$selected.' full_name="'.$pestManagerManager['full_name'].'">'.$pestManagerManager['item_name'].'</option>';
                }            
            $selectPestManager2 .= '</select>'; 
            
            $selectPestManager3 = '<select class="manager_id" id="pest_manager_id3"  name="pest_manager_id3" manager_type="Pest" manager_num="3" style="width: 150px !important;">';
            $selectPestManager3 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($pestManagerList as $pestManagerManager){ 
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($pestManagerManager['id'] == $_REQUEST['pest_manager_id3']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectPestManager3 .= '<option value="'.$pestManagerManager['id'].'" '.$selected.' full_name="'.$pestManagerManager['full_name'].'">'.$pestManagerManager['item_name'].'</option>';
                }            
            $selectPestManager3 .= '</select>'; 
            
            /* Security */
            
            $securityManagerList = $this->divisionManagerList(108);
            
            $selectSecurityManager1 = '<select class="manager_id" id="security_manager_id" name="security_manager_id" manager_type="Security" manager_num="" style="width: 150px !important;">';
            $selectSecurityManager1 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($securityManagerList as $securityManager){  
                    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($securityManager['id'] == $_REQUEST['security_manager_id']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectSecurityManager1 .= '<option value="'.$securityManager['id'].'" '.$selected.' full_name="'.$securityManager['full_name'].'">'.$securityManager['item_name'].'</option>';
                }            
            $selectSecurityManager1 .= '</select>'; 
            
            $selectSecurityManager2 = '<select class="manager_id" id="security_manager_id2" name="security_manager_id2" manager_type="Security" manager_num="2" style="width: 150px !important;">';
            $selectSecurityManager2 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($securityManagerList as $securityManager){  
                    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($securityManager['id'] == $_REQUEST['security_manager_id2']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectSecurityManager2 .= '<option value="'.$securityManager['id'].'" '.$selected.' full_name="'.$securityManager['full_name'].'">'.$securityManager['item_name'].'</option>';
                }                       
            $selectSecurityManager2 .= '</select>'; 
            
            
            
            
            $selectSecurityManager3 = '<select class="manager_id" id="security_manager_id3" name="security_manager_id3" manager_type="Security" manager_num="3" style="width: 150px !important;">';
            $selectSecurityManager3 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($securityManagerList as $securityManager){  
                    
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($securityManager['id'] == $_REQUEST['security_manager_id3']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                
                    $selectSecurityManager3 .= '<option value="'.$securityManager['id'].'" '.$selected.' full_name="'.$securityManager['full_name'].'">'.$securityManager['item_name'].'</option>';
                }                     
            $selectSecurityManager3 .= '</select>'; 
            
            
            /* traffic */
            
            $trafficManagerList = $this->divisionManagerList(2104);
            
            $selectTrafficManager1 = '<select class="manager_id" id="traffic_manager_id" name="traffic_manager_id" manager_type="Traffic" manager_num="" style="width: 150px !important;">';
            $selectTrafficManager1 .= '<option value="" full_name="">--- Select ---</option>';
                foreach($trafficManagerList as $trafficManager){  
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($trafficManager['id'] == $_REQUEST['traffic_manager_id']){
                    
                            $selected = "selected";
                        }
                                    
                    }                
                    
                    $selectTrafficManager1 .= '<option value="'.$trafficManager['id'].'" '.$selected.' full_name="'.$trafficManager['full_name'].'">'.$trafficManager['item_name'].'</option>';
                }            
            $selectTrafficManager1 .= '</select>'; 
            
            $trafficManagerList = $this->divisionManagerList(2104);
            
            $selectTrafficManager2 = '<select class="manager_id" id="traffic_manager_id2" name="traffic_manager_id2" manager_type="Traffic" manager_num="2" style="width: 150px !important;">';
            $selectTrafficManager2 .= '<option value="" full_name="">--- Select ---</option>';
            foreach($trafficManagerList as $trafficManager){  
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($trafficManager['id'] == $_REQUEST['traffic_manager_id2']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                    
                    
                    $selectTrafficManager2 .= '<option value="'.$trafficManager['id'].'" '.$selected.' full_name="'.$trafficManager['full_name'].'">'.$trafficManager['item_name'].'</option>';
                }                     
            $selectTrafficManager2 .= '</select>'; 
            
            $trafficManagerList = $this->divisionManagerList(2104);
            
            $selectTrafficManager3 = '<select class="manager_id" id="traffic_manager_id3" name="traffic_manager_id3" manager_type="Traffic" manager_num="3" style="width: 150px !important;">';
            $selectTrafficManager3 .= '<option value="" full_name="">--- Select ---</option>';
            foreach($trafficManagerList as $trafficManager){  
                    $selected = "";
                    if($_REQUEST)
                    {
                        if($trafficManager['id'] == $_REQUEST['traffic_manager_id3']){
                    
                            $selected = "selected";
                        }
                                    
                    }
                    
                    $selectTrafficManager3 .= '<option value="'.$trafficManager['id'].'" '.$selected.' full_name="'.$trafficManager['full_name'].'">'.$trafficManager['item_name'].'</option>';
                }                            
            $selectTrafficManager3 .= '</select>'; 
            
            
                
                
                

                $form_obj->form_template = '
        <div class="cl"></div>
        <h3 id="title">Add ' . ($bd ? "Leads" : " Stakeholders") . '</h3>
        <hr />
        <!--  section 1 -->
        
        <div class="fl usertype section1 user1 user2 user3 user4"><nobr>tselUserType</nobr><br />selUserType</div>     
        <div class="fl section1 user3"><nobr>tselUserLevel</nobr><br />selUserLevel</div>
        
        <div class="fl section1 user1 user2 user3 user4"><nobr id="name_label">ttxtName</nobr><br />txtName<br /></div>
        <div class="fl section1 user3"><nobr >ttxtMiddleName</nobr><br />txtMiddleName<br /></div>
        <div class="fl section1 user3"><nobr >ttxtSurname</nobr><br />txtSurname<br /></div>
        <div class="fl section1 user3"><nobr >tselSex</nobr><br />selSex<br /></div>
        <div class="fl section1 user2 user3"><nobr id="short_label">ttxtPreferredName</nobr><br />txtPreferredName<br /></div>  
        <div class="fl section1 user3"><nobr id="">tcalDob</nobr><br />calDob<br /></div>
        <div class="fl section1 user2" id="parent_company">
            <nobr>tcmbParentCompany</nobr>
            <br />cmbParentCompany<br />
        </div>


        
        <!--  section 2 -->
        <div class="fl section2 user1 user3 user4"><nobr>ttxtPhone</nobr><br />txtPhone</div>
        <div class="fl section2 user3 user4"><nobr>ttxtPhone2</nobr><br />txtPhone2</div>
        <div class="fl section2 user1"><nobr>ttxtFax</nobr><br />txtFax</div>
        <div class="fl section2 user1 user3 user4"><nobr>ttxtEmail</nobr><br />txtEmail</div>
        <div class="fl section2 user1"><nobr>ttxtEmail2</nobr><br />txtEmail2</div>
        <div class="fl section2 user1"><nobr>ttxtUrl</nobr><br />txtUrl</div>
        
        <!--  section 3 -->
        <div class="comdiv user1div">
            <div class="cl"></div><br />        
            <div class="fl section2 user1">chkAbnNoRequired <nobr>tchkAbnNoRequired</nobr></div>
            <div class="cl"></div><br />
        </div>   
        <div class="fl section3 user1 user2 user4"><nobr>ttxtAbn</nobr><span class="abnsign" style="display:none;color:green">
                <i class="fa fa-check" aria-hidden="true"></i>
        </span><br />txtAbn</div>
        <div class="fl section3 user1 user2 user3 user4"><nobr>ttxtAddress</nobr><br />txtAddress</div>
        <div class="fl section3 user1 user2 user3 user4"><nobr>tselState</nobr><br />selState</div>
        <div class="fl  section3 user1 user2 user3 user4"><nobr>ttxtSuburb</nobr><br />txtSuburb</div>
        <div class="fl section3 user1 user2 user3 user4"><nobr>ttxtPostcode</nobr><br />txtPostcode<br /></div>
        <div class="fl section3 user2 user3 user4"><nobr>ttxtLatitude</nobr><br />txtLatitude<br /></div>
        <div class="fl section3 user2 user3 user4"><nobr>ttxtLongitude</nobr><br />txtLongitude<br /></div>
        
        <!--  section 15 -->
        <div class="fl section5 user1 user2 user3 user4">tcalCommencementDate<br />calCommencementDate</div>
    <div id="xtras">     
        <div class="fl section5 user1 user2 user3 user4" style="padding-left: 10px;">
        
        <input type="hidden" style="padding: 0; margin: 0;" class="upload" name="fileToUpload" id="fileToUpload" onchange="loadImageFile()"><br /></br><canvas id="myCanvas" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img)) . '" /></a><br />' : '') . '
        </div>
        <div class="cl"></div><br />
        </div>
        <input type="hidden" name="hdnFileName" id="hdnFileName" />
        <input type="hidden" name="hdnImage" id="hdnImage" />
        
    <!--  section 4 -->
    <div class="comdiv user3div">
        <b>Emergency Contact Info </b>  <div class="cl"></div> 
        <div class="fl section3  user3"><nobr>ttxtEmergencyContactFullName</nobr><br />txtEmergencyContactFullName</div>
        <div class="fl section3  user3"><nobr>ttxtEmergencyContactRelationship</nobr><br />txtEmergencyContactRelationship</div>
        <div class="fl section3  user3"><nobr>ttxtEmergencyContactMobile</nobr><br />txtEmergencyContactMobile</div>
        
    </div>

    <!--  section 5 -->
    <div class="comdiv user4div">
            <div class="cl"></div><br />
            <b> Manager Incharge </b></br>
            
        <div class="fl section3  user4"><nobr>ttxtManagerInchargeName</nobr><br />txtManagerInchargeName</div>
        <div class="fl section3  user4"><nobr>ttxtManagerInchargeMobile</nobr><br />txtManagerInchargeMobile</div>
        <div class="fl section3  user4"><nobr>ttxtManagerInchargeEmail</nobr><br />txtManagerInchargeEmail</div>
        <div class="cl"></div><br />
        
        <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Name</nobr><br />txtManagerIncharge2Name</div>
        <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Mobile</nobr><br />txtManagerIncharge2Mobile</div>
        <div class="fl section3  user4"><nobr>ttxtManagerIncharge2Email</nobr><br />txtManagerIncharge2Email</div>
        <div class="cl"></div><br /><div class="cl"></div><br />
    </div>
    <div class="fl section1 user4"><nobr id="category_label"> Supplier Category *:</nobr><br />'.$selectCategory.'</div>
        <div class="comdiv user2div user3div user4div"> 
        <div class="cl"></div><br />
            <div class="fl section6 user2 user3 user4">
            tchlDivisions  <br />chlDivisions
            </div>
            <div class="cl"></div><br /> 
        </div> 
        
        <div class="comdiv user2">
            <div class="cl"></div><br />
            <b> Manager Division </b></br>
            <hr>
        <div class="fl section6.1 user2">
        <nobr id="category_label"> Civil Manager:</nobr><br />'.$selectCivilManager1.'
        </div>  
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerName</nobr><br />txtCivilManagerName</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerEmail</nobr><br />txtCivilManagerEmail</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerMobile</nobr><br />txtCivilManagerMobile</div>   
        <div class="fl section6.1 user2" style="width:170px">&nbsp;</div>
        <div class="fl section6.1 user2">
        <nobr id="category_label"> Civil Manager2:</nobr><br />'.$selectCivilManager2.'
        </div>  
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerName2</nobr><br />txtCivilManagerName2</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerEmail2</nobr><br />txtCivilManagerEmail2</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerMobile2</nobr><br />txtCivilManagerMobile2</div>
            <div class="cl"></div><br />
        <div class="fl section6.1 user2">
        <nobr id="category_label"> Civil Manager3:</nobr><br />'.$selectCivilManager3.'
        </div>  
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerName3</nobr><br />txtCivilManagerName3</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerEmail3</nobr><br />txtCivilManagerEmail3</div>
        <div class="fl section6.1 user2"><nobr>ttxtCivilManagerMobile3</nobr><br />txtCivilManagerMobile3</div> 
    
        <div class="cl"></div><br />
            <hr>

        <div class="fl section6.1 user2">
            <nobr id="category_label"> Facilities Manager:</nobr><br />'.$selectFacilitiesManager1.'
        </div> 
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerName</nobr><br />txtFacilitiesManagerName</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerEmail</nobr><br />txtFacilitiesManagerEmail</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerMobile</nobr><br />txtFacilitiesManagerMobile</div>
            <div class="fl section6.1 user2" style="width:170px">&nbsp;</div>
            <div class="fl section6.1 user2">
        <nobr id="category_label"> Facilities Manager2:</nobr><br />'.$selectFacilitiesManager2.'
        </div> 
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerName2</nobr><br />txtFacilitiesManagerName2</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerEmail2</nobr><br />txtFacilitiesManagerEmail2</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerMobile2</nobr><br />txtFacilitiesManagerMobile2</div>
        <div class="cl"></div><br />
        <div class="fl section6.1 user2">
        <nobr id="category_label"> Facilities Manager3:</nobr><br />'.$selectFacilitiesManager3.'
        </div> 
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerName3</nobr><br />txtFacilitiesManagerName3</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerEmail3</nobr><br />txtFacilitiesManagerEmail3</div>
        <div class="fl ssection6.1 user2"><nobr>ttxtFacilitiesManagerMobile3</nobr><br />txtFacilitiesManagerMobile3</div>
        <div class="cl"></div><br />
            <hr>
        <div class="fl section6.1 user2">
            <nobr id="category_label"> Pest Manager:</nobr><br />'.$selectPestManager1.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerName</nobr><br />txtPestManagerName</div>
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerEmail</nobr><br />txtPestManagerEmail</div>
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerMobile</nobr><br />txtPestManagerMobile</div>
        <div class="fl section6.1 user2" style="width:170px">&nbsp;</div>
            <div class="fl section6.1 user2">
            <nobr id="category_label"> Pest Manager2:</nobr><br />'.$selectPestManager2.'
        </div> 
            <div class="fl section6.1 user2"><nobr>ttxtPestManagerName2</nobr><br />txtPestManagerName2</div>
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerEmail2</nobr><br />txtPestManagerEmail2</div>
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerMobile2</nobr><br />txtPestManagerMobile2</div>
        <div class="cl"></div><br />  
            <div class="fl section6.1 user2">
            <nobr id="category_label"> Pest Manager3:</nobr><br />'.$selectPestManager3.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtPestManagerName3</nobr><br />txtPestManagerName3</div>
            <div class="fl section6.1 user2"><nobr>ttxtPestManagerEmail3</nobr><br />txtPestManagerEmail3</div>
            <div class="fl section6.1 user2"><nobr>ttxtPestManagerMobile3</nobr><br />txtPestManagerMobile3</div>
        <div class="cl"></div><br />  
            <hr>


            <div class="fl section6.1 user2">
            <nobr id="category_label"> Security Manager:</nobr><br />'.$selectSecurityManager1.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerName</nobr><br />txtSecurityManagerName</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerEmail</nobr><br />txtSecurityManagerEmail</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerMobile</nobr><br />txtSecurityManagerMobile</div>
            <div class="fl section6.1 user2" style="width:170px">&nbsp;</div>
            
            <div class="fl section6.1 user2">
                <nobr id="category_label"> Security Manager2:</nobr><br />'.$selectSecurityManager2.'
            </div> 
            <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerName2</nobr><br />txtSecurityManagerName2</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerEmail2</nobr><br />txtSecurityManagerEmail2</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerMobile2</nobr><br />txtSecurityManagerMobile2</div>
        <div class="cl"></div><br />
        <div class="fl section6.1 user2">
                <nobr id="category_label"> Security Manager3:</nobr><br />'.$selectSecurityManager3.'
            </div> 
            <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerName3</nobr><br />txtSecurityManagerName3</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerEmail3</nobr><br />txtSecurityManagerEmail3</div>
        <div class="fl section6.1 user2"><nobr>ttxtSecurityManagerMobile3</nobr><br />txtSecurityManagerMobile3</div>
        <div class="cl"></div><br />
            <hr>

        <div class="fl section6.1 user2">
            <nobr id="category_label"> Traffic Manager:</nobr><br />'.$selectTrafficManager1.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerName</nobr><br />txtTrafficManagerName</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerEmail</nobr><br />txtTrafficManagerEmail</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerMobile</nobr><br />txtTrafficManagerMobile</div>
        <div class="fl section6.1 user2" style="width:170px">&nbsp;</div>
            <div class="fl section6.1 user2">
            <nobr id="category_label"> Traffic Manager2:</nobr><br />'.$selectTrafficManager2.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerName2</nobr><br />txtTrafficManagerName2</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerEmail2</nobr><br />txtTrafficManagerEmail2</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerMobile2</nobr><br />txtTrafficManagerMobile2</div>      
        <div class="cl"></div><br /><div class="cl"></div><br />
        <div class="fl section6.1 user2">
            <nobr id="category_label"> Traffic Manager3:</nobr><br />'.$selectTrafficManager3.'
        </div> 
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerName3</nobr><br />txtTrafficManagerName3</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerEmail3</nobr><br />txtTrafficManagerEmail3</div>
        <div class="fl section6.1 user2"><nobr>ttxtTrafficManagerMobile3</nobr><br />txtTrafficManagerMobile3</div>
        
        <div class="cl"></div><br />
            <hr><div class="cl"></div><br />
        
    </div>

        





    <div class="comdiv user3div">     
        <div class="fl section9  user3"><nobr>tselWorkingForProvider</nobr><br />selWorkingForProvider</div>
        <div class="fl section9  user3 user4"><nobr>tselProviderId</nobr><br />selProviderId</div>
        <div class="cl"></div><br />
    </div>
    <!-- section11 -->
    <!--
    <div class="comdiv user3div user4div"> 
        <div class="fl section1 user3 user4" id="parent_site">
                <nobr>tmslParentSite</nobr><span class="mslParentSign" style="display:none;color:green">
                <i class="fa fa-check" aria-hidden="true"></i>
        </span><br />mslParentSite<br />
        </div>
    <div class="cl"></div><br /> 
    </div>
    -->

    <!--  section 11 -->
    <div class="comdiv user3div user4div">     
        <div class="fl section11  user3"><nobr>ttxtTaxFileNumber</nobr><br />txtTaxFileNumber</div>
        <div class="fl section11  user3 user4"><nobr>ttxtBankName</nobr><br />txtBankName</div>
        <div class="fl section11  user3 user4"><nobr>ttxtBsbNumber</nobr><br />txtBsbNumber</div>
        <div class="fl section11  user3 user4"><nobr>ttxtAccountNumber</nobr><br />txtAccountNumber</div>
        <div class="fl section11  user3 user4"><nobr>ttxtSuperName</nobr><br />txtSuperName</div>
        <div class="fl section11  user3 user4"><nobr>ttxtSuperNumber</nobr><br />txtSuperNumber</div>


        <div class="cl"></div><br />
    </div>      
    <!--  section 1 -->
    





    <!-- section selDocumentType -->
    <div class="comdiv user3div user4div">    
    <b>Upload <span id="cert_label">User </span> Compliance:</b><div class="cl"></div><br />



    <div id="certfi1">

    <div style="width:800;float:left">
            <div class="fl section11  user3 user4"><nobr>tselLicenceType1</nobr><br />selLicenceType1</div>
            <div class="fl section11  user3 user4"><nobr>ttxtLicenceNumber1</nobr><br />txtLicenceNumber1</div>
            <div class="fl section11  user3"><nobr>ttxtLicenceClass1</nobr><br />txtLicenceClass1</div>
            <div class="fl section11  user3 user4"><nobr>tcalExpiryDate1</nobr><br />calExpiryDate1</div>
            <div class="fl section11  user3 user4"><nobr>tselLicenceState1</nobr><br />selLicenceState1</div>
            <div class="fl section11"><nobr>tselCompliance1</nobr><br />selCompliance1</div>
            <div class="cl"></div><br />
            <div id="xtras">     
                <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo1</nobr><br />
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload1" id="fileToUpload1" onchange="loadImageFile(\'1\')"></br>
                <canvas id="myCanvas1" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file1) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img1)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img1)) . '" /></a><br />' : '') . '
                </div></div>
                <input type="hidden" name="hdnFileName1" id="hdnFileName1" />
                <input type="hidden" name="hdnImage1" id="hdnImage1" />
        
            <div id="xtras">     
                <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo2</nobr><br />
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload2" id="fileToUpload2" onchange="loadImageFile(\'2\')"></br><canvas id="myCanvas2" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file2) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img2)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img2)) . '" /></a><br />' : '') . '
                </div></div>
                <input type="hidden" name="hdnFileName2" id="hdnFileName2" />
                <input type="hidden" name="hdnImage2" id="hdnImage2" />
                <div class="cl"></div><br />
                </div>
        <div style="width:60px; padding:15px;float:left" id="certfi2_delete"><a href="javascript:void(0)" title="more" style="color:green;font-size:31px !important" onclick="showCert(2)">+</a></div>
        <div style="clear:both"></div>
    </div>     

    <div id="certfi2" style="display:none">
        <div style="width:800;float:left">
    <div class="fl section11  user3 user4"><nobr>tselLicenceType2</nobr><br />selLicenceType2</div>
    <div class="fl section11  user3 user4"><nobr>ttxtLicenceNumber2</nobr><br />txtLicenceNumber2</div>
    <div class="fl section11  user3"><nobr>ttxtLicenceClass2</nobr><br />txtLicenceClass2</div>
    <div class="fl section11  user3 user4"><nobr>tcalExpiryDate2</nobr><br />calExpiryDate2</div>
    <div class="fl section11  user3 user4"><nobr>tselLicenceState2</nobr><br />selLicenceState2</div>
    <div class="fl section11  "><nobr>tselCompliance2</nobr><br />selCompliance2</div>
    <div class="cl"></div><br />
    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo1</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload3" id="fileToUpload3" onchange="loadImageFile(\'3\')"></br><canvas id="myCanvas3" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file3) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img3)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img3)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName3" id="hdnFileName3" />
        <input type="hidden" name="hdnImage3" id="hdnImage3" />

    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo2</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload4" id="fileToUpload4" onchange="loadImageFile(\'4\')"></br><canvas id="myCanvas4" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file4) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img4)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img4)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName4" id="hdnFileName4" />
        <input type="hidden" name="hdnImage4" id="hdnImage4" />
        <div class="cl"></div><br />   
        </div>
        <div style="width:60px; padding:15px;float:left" id="certfi3_delete"><a href="javascript:void(0)" title="more" style="color:green;font-size:31px !important" onclick="showCert(3)">+</a></div>
        <div style="clear:both"></div>
    </div>     
    </div>      
    <div id="certfi3" style="display:none">
    <div style="width:800;float:left">
    <div class="fl section11  user3 user4"><nobr>tselLicenceType3</nobr><br />selLicenceType3</div>
    <div class="fl section11  user3 user4"><nobr>ttxtLicenceNumber3</nobr><br />txtLicenceNumber3</div>
    <div class="fl section11  user3"><nobr>ttxtLicenceClass3</nobr><br />txtLicenceClass3</div>
    <div class="fl section11  user3 user4"><nobr>tcalExpiryDate3</nobr><br />calExpiryDate3</div>
    <div class="fl section11  user3 user4"><nobr>tselLicenceState3</nobr><br />selLicenceState3</div>
    <div class="fl section11  "><nobr>tselCompliance3</nobr><br />selCompliance3</div>
    <div class="cl"></div><br />
    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo1</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload5" id="fileToUpload5" onchange="loadImageFile(\'5\')"></br><canvas id="myCanvas5" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file5) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img5)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img5)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName5" id="hdnFileName5" />
        <input type="hidden" name="hdnImage5" id="hdnImage5" />
        

            <div id="xtras">     
                <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo2</nobr><br />
                <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload6" id="fileToUpload6" onchange="loadImageFile(\'6\')"></br><canvas id="myCanvas6" style="max-width: 200px; height: 0px;"></canvas>
                ' . (file_exists($img_file6) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img6)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img6)) . '" /></a><br />' : '') . '
                </div></div>
                <input type="hidden" name="hdnFileName6" id="hdnFileName6" />
                <input type="hidden" name="hdnImage6" id="hdnImage6" />
                <div class="cl"></div><br /> 
                </div>
        <div style="width:60px; padding:15px;float:left" id="certfi4_delete"><a href="javascript:void(0)" title="more" style="color:green;font-size:31px !important" onclick="showCert(4)">+</a></div>
        <div style="clear:both"></div>
    </div> 
    <div id="certfi4" style="display:none">
        <div style="width:800;float:left">
    <div class="fl section11  user3 user4"><nobr>tselLicenceType4</nobr><br />selLicenceType4</div>
    <div class="fl section11  user3 user4"><nobr>ttxtLicenceNumber4</nobr><br />txtLicenceNumber4</div>
    <div class="fl section11  user3"><nobr>ttxtLicenceClass4</nobr><br />txtLicenceClass4</div>
    <div class="fl section11  user3 user4"><nobr>tcalExpiryDate4</nobr><br />calExpiryDate4</div>
    <div class="fl section11  user3 user4"><nobr>tselLicenceState4</nobr><br />selLicenceState4</div>
    <div class="fl section11  "><nobr>tselCompliance4</nobr><br />selCompliance4</div>
    <div class="cl"></div><br />
    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo1</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload7" id="fileToUpload7" onchange="loadImageFile(\'7\')"></br><canvas id="myCanvas7" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file7) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img7)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img7)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName7" id="hdnFileName7" />
        <input type="hidden" name="hdnImage7" id="hdnImage7" />

    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo2</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload8" id="fileToUpload8" onchange="loadImageFile(\'8\')"></br><canvas id="myCanvas8" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file8) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img8)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img8)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName8" id="hdnFileName8" />
        <input type="hidden" name="hdnImage8" id="hdnImage8" />
        <div class="cl"></div><br />   
        </div>
        <div style="width:60px; padding:15px;float:left" id="certfi5_delete"><a href="javascript:void(0)" title="more" style="color:green;font-size:31px !important" onclick="showCert(5)">+</a></div>
        <div style="clear:both"></div>
    
    </div> 
    <div id="certfi5" style="display:none">
        <div style="width:800;float:left">
    <div class="fl section11  user3 user4"><nobr>tselLicenceType5</nobr><br />selLicenceType5</div>
    <div class="fl section11  user3 user4"><nobr>ttxtLicenceNumber5</nobr><br />txtLicenceNumber5</div>
    <div class="fl section11  user3"><nobr>ttxtLicenceClass5</nobr><br />txtLicenceClass5</div>
    <div class="fl section11  user3 user4"><nobr>tcalExpiryDate5</nobr><br />calExpiryDate5</div>
    <div class="fl section11  user3 user4"><nobr>tselLicenceState5</nobr><br />selLicenceState5</div>
    <div class="fl section11  "><nobr>tselCompliance5</nobr><br />selCompliance5</div>
    <div class="cl"></div><br />
    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo1</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload9" id="fileToUpload9" onchange="loadImageFile(\'9\')"></br><canvas id="myCanvas9" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file9) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img9)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img9)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName9" id="hdnFileName9" />
        <input type="hidden" name="hdnImage9" id="hdnImage9" />

    <div id="xtras">     
        <div class="fl section5 user3 user4" style="padding-left: 10px;"><nobr>Photo2</nobr><br />
        <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload10" id="fileToUpload10" onchange="loadImageFile(\'10\')"></br><canvas id="myCanvas10" style="max-width: 200px; height: 0px;"></canvas>
        ' . (file_exists($img_file10) ? '<a target="_blank" href="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img10)) . '"><img style="max-width: 200px" src="' . $this->f3->get('main_folder') . 'Image?i=' . urlencode($this->encrypt($show_img10)) . '" /></a><br />' : '') . '
        </div></div>
        <input type="hidden" name="hdnFileName10" id="hdnFileName10" />
        <input type="hidden" name="hdnImage10" id="hdnImage10" />
        <div class="cl"></div><br />   
        </div>
        <div style="width:60px; padding:15px;float:left"></div>
        <div style="clear:both"></div>
    </div>     

    </div>     

    <div class="comdiv user2div">
    <b> Site Contact1 </b>  <div class="cl"></div>  
    <div class="fl section12 user2"><nobr>ttxtSiteContactName1</nobr><br />txtSiteContactName1</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPosition1</nobr><br />txtSiteContactPosition1</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPhone1</nobr><br />txtSiteContactPhone1</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactMobile1</nobr><br />txtSiteContactMobile1</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactEmail1</nobr><br />txtSiteContactEmail1</div>

    <div class="cl"></div><br />  <b> Site Contact2 </b>  <div class="cl"></div> 
    <div class="fl section12 user2"><nobr>ttxtSiteContactName2</nobr><br />txtSiteContactName2</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPosition2</nobr><br />txtSiteContactPosition2</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPhone2</nobr><br />txtSiteContactPhone2</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactMobile2</nobr><br />txtSiteContactMobile2</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactEmail2</nobr><br />txtSiteContactEmail2</div>
    <div class="cl"></div>

    <div class="cl"></div><br />  <b> Site Contact3 </b>  <div class="cl"></div> 
    <div class="fl section12 user2"><nobr>ttxtSiteContactName3</nobr><br />txtSiteContactName3</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPosition3</nobr><br />txtSiteContactPosition3</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactPhone3</nobr><br />txtSiteContactPhone3</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactMobile3</nobr><br />txtSiteContactMobile3</div>
    <div class="fl section12 user2"><nobr>ttxtSiteContactEmail3</nobr><br />txtSiteContactEmail3</div>
    <div class="cl"></div>
    </div>
    

    <div class="comdiv user3div">    
        <div class="fl section12 user3"><nobr>ttxtUsername</nobr><br />txtUsername</div>
        <div class="fl user3"><nobr>ttxtPassword</nobr><br />txtPassword<input type="button" value="Reset Password" onClick="get_pw()" /><br /></div>
        
        </div><div class="cl"></div></br>
        ' . $form_obj->button_list();
                
                
                $form_obj->xtra_validation .= '
                    
            if(document.getElementById("selUserType").value == "2438" && document.getElementById("supplier_category").value == "" ) {
            err++;
            error_msg += err + ". Please Select Supplier Category.\\n";
            }
            ';
                    



                /* $form_obj->form_template .= '<div class="fl" id="parent_company" style="display: none;">
                * <nobr>tcmbParentCompany</nobr><br />cmbParentCompany<br /></div>
                <div class="fl" id="surname"><nobr>ttxtSurname</nobr><br />txtSurname<br /></div>
                <div class="fl" id="parent_site" style="display: none;"><nobr>tcmbParentSite</nobr><br />cmbParentSite<br /></div>




                <div class="fl"><nobr>ttxtPostcode</nobr><br />txtPostcode<br /></div>
                <div class="fl" id="worker_details">
                <div class="fl"><nobr>tselWorkerClassification</nobr><br />selWorkerClassification<br /></div>
                <div class="fl"><nobr>tcmbVisaType</nobr><br />cmbVisaType<br /></div>

                </div>

                <div class="fl"><nobr>ttxtPassword</nobr><br />txtPassword<input type="button" value="Random PW" onClick="get_pw()" /><br /></div>
                <div class="cl"></div><br />
                tchlDivisions  <br />chlDivisions
                </div>
                <div class="cl"></div><br />
                '.$form_obj->button_list(); */
    //      $form_obj->editor_template = '
    //      <div style="float: left; ">
    //      <div class="form-wrapper" style="max-width: 700px;">
    //      <div class="form-header">Lookups</div>
    //      <div class="form-content">
    //      editor_form
    //      </div>
    //      </div>
    //      editor_list
    //      </div>
    //      <div class="cl"></div>';
                $str .= $form_obj->display_form() . "<br />";

                $str .= '
        
    ';

                $str .= '
        <script> 
            $( ".manager_id" ).on( "change", function() {
                var managerType = $(this).attr("manager_type");
                var managerNum = $(this).attr("manager_num");           
                var selectVal = $("option:selected", this).attr("full_name"); 
                var managerArray = selectVal.split("___");
                var managerPhone = managerArray.pop();
                var managerEmail = managerArray.pop();
                var managerName = managerArray.pop();
                
                $("#txt"+managerType+"ManagerName"+managerNum).val(managerName);
                $("#txt"+managerType+"ManagerEmail"+managerNum).val(managerEmail);
                $("#txt"+managerType+"ManagerMobile"+managerNum).val(managerPhone);
                
                
            } );
            var s = document.getElementById("selState");
            s.selectedIndex = 2;
            ';
                if ($add_security) {
                    $str .= '
            var e = document.getElementById("selUserType");
            e.selectedIndex = 8;
            change_fields();
            document.getElementById("txtName").focus()
            xtras.style.display="none"
            ';
                }
                $str .= '</script>';
            }

            return $str;
        }

        function AccountDetails() {

            $main_folder = $this->f3->get('main_folder');
            if ($_SESSION['u_level'] >= 1000)
                $uid = $_GET['uid'];
            if ($uid) {
                $sql = "select lookup_fields.id, lookup_fields.item_name from lookup_fields
                left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
                where foreign_id = '$uid' and table_assoc = 'users'
                ";
                //  prd($sql);
                $result = $this->dbi->query($sql);
                $num_rows = $result->num_rows;
                $x = 0;
                while ($myrow = $result->fetch_assoc()) {
                    $uids[$x] = $myrow['id'];
                    $x++;
                }
            } else {
                $uid = $_SESSION['user_id'];
                $uids = $this->f3->get('lids');
            }
            // prd($uids);
            $num_ids = count($uids);
            if ($num_ids) {
                for ($x = 0; $x < $num_ids; $x++) {
                    if ($uids[$x] == 110) {
                        $show_stu_msg = 1;
                    }
                }
            } else {
                if ($uids == 110) {
                    $show_stu_msg = 1;
                }
            }
            $str .= "<h3 style=\"padding-bottom: 15px; \">Further Details</h3>";
            if (isset($_SESSION['student_message'])) {
                $result = $this->dbi->query("SELECT * FROM `usermeta` where meta_key = (SELECT id FROM `lookups` where item_name = 'student_usi') and user_id = " . $_SESSION['user_id']);
                if ($result->num_rows) {
                    $sql = "select enrolments_payments.id as `payid`, enrolments.course_date_id as `cdid` from enrolments
                    left join enrolments_payments on enrolments_payments.enrolment_id = enrolments.id
                    where enrolment_id in (select id from enrolments where student_id = " . $_SESSION['user_id'] . ") and enrolments_payments.is_paid = 0 order by enrolments_payments.id LIMIT 1";
                    $result = $this->dbi->query($sql);
                    if ($result->num_rows) {
                        $myrow = $result->fetch_assoc();
                        $payid = $myrow['payid'];
                        $cdid = $myrow['cdid'];
                        $str .= $this->redirect('pay.php?payid=' . $payid . '&cdid=' . $cdid);
                    }
                } else {
                    $str .= '<div class="message">Please fill in your details below to complete your enrolment...</div><br />';
                }
                unset($_SESSION['student_message']);
            }
            if ($show_stu_msg) {
                $str .= "<div style=\"margin-bottom: 25px; padding: 25px; background-color: #FFFFEE; border: 1px solid #AA0000;\"><b>IMPORTANT!</b> Need a USI? <a target=\"_blank\" href=\"https://www.usi.gov.au/create-your-USI/Pages/default.aspx\">Click Here to Create a Unique Student Identifier (USI)</a></div>";
            }
            /* $view_details = new data_list;

            $view_details->dbi = $this->dbi;
            $view_details->title = "";
            $view_details->sql = "select id as idin, CONCAT(name, ' ', surname) as `Name`, CONCAT('<a href=\"mailto:', email, '\">', email, '</a>') as `Email`, phone as `Phone` from users  where id = ".$uid;
            $str .= $view_details->draw_list() . "<br /><br />"; */
            if (!$_POST) {
                $sql = "
            SELECT lookups.id as `lookup_id`, lookups.item_name as `lookup`, lookups.description, lookup_fields1.item_name as `type`, lookups.regex FROM `lookup_answers`
            left join lookups on lookup_answers.foreign_id = lookups.id
            left join lookup_fields1 on lookup_fields1.id = lookups.type
            WHERE lookup_answers.lookup_field_id in (" . implode(",", $uids) . ") and lookup_answers.table_assoc = 'lookups'
            group by lookups.id
            order by lookups.sort_order
        ";

    //            echo $sql;
    //            die;
                $result = $this->dbi->query($sql);
                $x = 0;
                while ($myrow = $result->fetch_assoc()) {
                    $lookup_id = $myrow['lookup_id'];
                    $lookup = $myrow['lookup'];
                    $description = $myrow['description'];
                    $type = $myrow['type'];
                    $validation = $myrow['regex'];
                    if ($validation != "v" || $_SESSION['u_level'] >= 1000 || $hr_user) {
                        $allow_view = 1;
                    } else {
                        $allow_view = 0;
                    }
                    if ($validation == "v" && $uid == $_SESSION['user_id'])
                        $allow_view = 0;
                    if ($allow_view) {
                        if ($type == "sel") {
                            $sel_sql = $this->get_lookup($lookup);
                        } else if ($type == "chl") {
                            $sel_sql = $this->get_chl($lookup);
                        } else {
                            $sel_sql = "";
                        }
                        //$str .= "<h3>$sel_sql</h3>";
                        if ($type == "txt" || $type == "sel" || $type == "chl" || $type == "chk" || $type == "cal" || $type == "ti2") {
                            $element_name = $type . $lookup_id;
                            if ($type == "chk") {
                                $style = "";
                            } else {
                                $style = 'class="full_width"';
                            }
                            //$str .= $element_name;
                            $this->editor_obj->form_attributes[0][$x] = $element_name;
                            $this->editor_obj->form_attributes[1][$x] = $description;
                            $this->editor_obj->form_attributes[2][$x] = "t";
                            $this->editor_obj->form_attributes[3][$x] = $sel_sql;
                            $this->editor_obj->form_attributes[4][$x] = $style;
                            if (!$validation)
                                $validation = "c";
                            $this->editor_obj->form_attributes[5][$x] = $validation;
                            if ($type == "txt" || $type == "cal" || $type == "ti2") {
                                $sql = "select meta_value from usermeta where meta_key = $lookup_id and user_id = $uid";
                                $result2 = $this->dbi->query($sql);
                                if ($myrow2 = $result2->fetch_assoc()) {
                                    $this->editor_obj->form_attributes[6][$x] = $myrow2['meta_value'];
                                }
                            }
                            if ($type == "sel") {
                                $sql = "select meta_key, meta_value from usermeta where meta_key = $lookup_id and user_id = $uid";
                                $result2 = $this->dbi->query($sql);
                                if ($myrow2 = $result2->fetch_assoc()) {
                                    $mkey = $myrow2['meta_key'];
                                    $mval = $myrow2['meta_value'];
                                    $js_string .= "document.getElementById('sel$mkey').value = $mval;\n";
                                }
                            }
                            if ($type == "chl") {
                                $this->editor_obj->form_attributes[6][$x] = $lookup;
                                $this->editor_obj->form_template .= '<div class="cl" style="padding-top: 10px;"></div>';
                                $sql = "select meta_key, meta_value from usermeta where meta_key = $lookup_id and user_id = $uid";
                                $result2 = $this->dbi->query($sql);
                                while ($myrow2 = $result2->fetch_assoc()) {
                                    $js_string .= "toggle('chl" . $myrow2['meta_key'] . $myrow2['meta_value'] . "');\n";
                                }
                            }
                            if($type == "chk") {
                                $sql = "select meta_value from usermeta where meta_key = $lookup_id and user_id = $uid";
                                
                                $result2 = $this->dbi->query($sql);
                                if ($myrow2 = $result2->fetch_assoc()) {
                                    if ($myrow2['meta_value'])
                                        $this->editor_obj->form_attributes[4][$x] .= " checked ";
                                }
                                $this->editor_obj->form_template .= '
                    <div class="fl med_textbox"><nobr>' . $element_name . ' t' . $element_name . '</span></nobr></div>
                    ';
                            } else {
                                $this->editor_obj->form_template .= '
                    <div class="fl med_textbox"><nobr>t' . $element_name . '</nobr><br />' . $element_name . '</div>
                ';
                            }
                            if ($type == "chl" || $type == "chk") {
                                $this->editor_obj->form_template .= '<div class="cl" style="padding-top: 25px;"></div>';
                                $item_count--;
                            }
                            $item_count++;
                            $x++;
                        }
                    }
                    $this->editor_obj->button_attributes = array(
                        array("Update Further Details"),
                        array("cmdSignup"),
                        array("if(check()) this.form.submit()"),
                        array("js_function_add")
                    );
                    $this->editor_obj->editor_template = 'editor_form<div class="cl" style="padding-top: 15px;"></div>' . $this->editor_obj->button_list();
                }
            } else {
                foreach ($_POST as $key => $value) {
                    $item_type = substr($key, 0, 3);
                    $meta_key = substr($key, ($item_type == 'hch' ? 4 : 3));
                    $c++;
                    if ($item_type != "chl" && $item_type != "chk") {
                        $sql = "select id from usermeta where user_id = " . $uid . " and meta_key = '$meta_key'";
                        $result = $this->dbi->query($sql);
                        if ($item_type == 'hch')
                            $value = ($value == 'on' ? 1 : 0);
                        if ($result->num_rows) {
                            $sql = "update usermeta set meta_value = '$value' where user_id = " . $uid . " and meta_key = '$meta_key'";
                            $result = $this->dbi->query($sql);
                        } else {
                            $sql = "insert into usermeta (user_id, meta_key, meta_value) values (" . $uid . ", '$meta_key', '$value')";
                            $result = $this->dbi->query($sql);
                        }
                    }
                    if ($item_type == "chl") {
                        $sql = "delete from usermeta where user_id = " . $uid . " and meta_key = '$meta_key'";
                        $result = $this->dbi->query($sql);
                        foreach ($value as $val) {
                            if ($val) {
                                $sql = "insert into usermeta (user_id, meta_key, meta_value) values (" . $uid . ", '$meta_key', '$val')";
                                $result = $this->dbi->query($sql);
                            }
                        }
                    }
                }
                $str .= '<div style="margin-bottom: 20px;" class="message">Details Updated...</div>';
                /*
                $sql = "select enrolments_payments.id as `payid`, enrolments.course_date_id as `cdid` from enrolments
                left join enrolments_payments on enrolments_payments.enrolment_id = enrolments.id
                where enrolment_id in (select id from enrolments where student_id = " . $_SESSION['user_id'] . ") and enrolments_payments.is_paid = 0 order by enrolments_payments.id LIMIT 1";
                $result = $this->dbi->query($sql);
                if($result->num_rows) {
                $myrow = $result->fetch_assoc();
                $payid = $myrow['payid'];
                $cdid = $myrow['cdid'];
                $str .= '<div class="message"><a href="pay.php?payid='.$payid.'&cdid='.$cdid.'">Click Here to Pay...</a></div>';
                $str .= $this->redirect('pay.php?payid='.$payid.'&cdid='.$cdid);
                } else { */
                $str .= '<div class="message"><a href="' . $main_folder . 'AccountDetails?a=1' . (isset($_GET['show_min']) ? "&show_min=1" : "") . ($uid ? "&uid=$uid" : "") . '">Click Here to Return...</a></div>';
                //}
            }
            $str .= '<div class="cl"></div>';
            if (!$_POST) {
                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
            } else {
                $str .= "<div class=\"cl\"></div>";
            }
            $str .= '<script type="text/javascript">' . "\n$js_string\n</script>";
            return $str;
        }

        function Login() {
            $main_folder = $this->f3->get('main_folder');
            //echo  "<h3> {$this->f3->get('POST.pwdPassword')} && Cat: {$_POST['txtCatsName']}  && Dog: {$_POST['txtDogsName']}  && {$_POST['init_ip']} == {$_SERVER['REMOTE_ADDR']}";
            //exit;
            //echo "Allied Edge is currently undergoing maintenance. It will return shortly.";
            //return 1;

            $user_logins = new Crud($this->db, 'user_logins');
            $test = $_COOKIE['AnInescapableFact'];
            if ($test) {
                $user_logins->getByField('str', $test);
                if (!$user_logins->dry())
                    $this->authenticate($user_logins->user_id);
            }


            $msg = (isset($_GET['message']) ? $_GET['message'] : "");
            $this->page_from = (isset($_GET['page_from']) ? $_GET['page_from'] : "");
            if ($msg) {
                $str .= "<h3 class=\"message\">$msg</h3>";
            }

            if ($this->f3->get('POST.txtUsername') && $this->f3->get('POST.pwdPassword') && $_POST['txtCatsName'] == "Felix Templeton" && $_POST['txtDogsName'] == "Fido Baggins" && $_POST['init_ip'] == $_SERVER['REMOTE_ADDR']) {
                $this->authenticate();
            }

            $form_obj = new data_editor;
            $list_obj = new data_list;
            $form_obj->hide_confirm = 1;
            $form_obj->bot_protect = 1;
            $form_obj->dbi = $this->dbi;
            $form_obj->hide_filter = 1;
            //$style = 'style="background-color: white !important; font-size: 30px; padding-top: 10px; padding-bottom: 10px; width: 300px;"';
            $style = ' class="uk-input uk-form-large" ';
            $form_obj->table = "users";
            $form_obj->form_attributes = array(
                array("txtUsername", "pwdPassword"),
                array("Username", "Password"),
                array("username", "pw"),
                array("", ""),
                array($style . ' placeholder="Username" ', $style . ' onKeypress="submit_on_enter(event)" placeholder="Password" '),
                array("c", "c"),
                array("", "")
            );
            //if(!$_SESSION['user_id'] && $_POST['txtCatsName'] == "Felix Templeton" && $_POST['txtDogsName'] == "Fido Baggins" && $_POST['init_ip'] == $_SERVER['REMOTE_ADDR']) {
            $str .= '
            <script>
            function validateEmail(email) {
                var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
                return re.test(email);
            }
        function submit_on_enter(event) {
            if(event.keyCode == 13) {
            if(check()) document.frmEdit.submit()
            }
        }
            </script>';

            $form_obj->xtra_validation = '
            /*if(validateEmail(document.getElementById("txtUsername").value) == false) {
            err++;
            error_msg += err + ". Please enter a valid email address.\\n";
            }*/
            ';
            $form_obj->button_attributes = array(
                array("Login"),
                array("cmdSignup"),
                array("if(check()) this.form.submit()"),
                array("js_function_add"),
                array(""),
                array("uk-width-1-1 uk-button uk-button-primary uk-button-medium company_colour")
            );
            //<a class="list_a" href="SignUp">Sign Up</a> 




            $form_obj->form_template = '
    <div class="uk-flex uk-flex-center uk-flex-middle uk-height-viewport">
                <div class="uk-width-medium uk-padding-small loginBox" style="border: 1px solid #DDDDDD; background-color: white;">      
        <div style="text-align: center; margin-top: -10px; padding-bottom: 6px;" class="loginTitle">' . $this->f3->get('software_name') . ' Login</div>
        <img src="' . $this->f3->get('img_folder') . 'logo.jpg" height="90" class="loginLogo"/><br /><br />

                        <fieldset class="uk-fieldset">
                            <div class="uk-margin">
                                <div class="uk-inline uk-width-1-1">
                                    <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: user"></span>
                                    txtUsername
                                </div>
                            </div>
                            <div class="uk-margin">
                                <div class="uk-inline uk-width-1-1">
                                    <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: lock"></span>
                                    pwdPassword
                                </div>
                            </div>
                            
                            <div class="uk-margin">
                ' . $form_obj->button_list() . '
                            </div>
                            <div class="uk-margin">
                <a class="uk-float-right uk-link uk-link-muted forgotPassword" href="ForgottenPassword">Forgotten Password?</a>
                </div>
                            <div class="uk-margin">
                <a class="" title="Medical Emergency" href="' . $main_folder . 'Emergency"><img class="" src="' . $this->f3->get('img_folder') . 'emergency.png" width="32" height="32" alt="000" /></a>
                            </div>

        </fieldset>
        
        </div>
        </div>

            ';

            $form_obj->editor_template = 'editor_form';
            $str .= $form_obj->draw_data_editor($list_obj);
            $this->f3->set('content', $str);
            $this->f3->set('title', "Login to " . $this->f3->get('software_name'));
            $template = new Template;
            echo $template->render('login.htm');
        }

        function sendEmailQueue() {
        //66666 die("test2");
            $mail = new email_q($this->dbi);
            //$mail->AddAddress($_POST['txtEmail']);
            //$mail->AddAddress("mahavir.jain@dotsquares.com");
            $mailObjs = $mail->get_q_message();

            if ($mailObjs) {
                
                foreach ($mailObjs as $mailObj) {
                    $emailNotExist = 1;
                    $mailNew = array();
                    $mailNew = new email_q($this->dbi);
                    $mailNew->clear_all();
                    $mailNew->Subject = $mailObj['email_subject'];
                    $mailNew->Body = $mailObj['email_body'];

                    foreach ($mailObj['email_to'] as $emailto) {
                        if ($emailto[0] != "") {
                            $mailNew->AddAddress($emailto[0]);
                            $emailNotExist = 0;
                            //$mailNew->AddAddress("m;ahavir.jain@dotsquares.com");
                        }
                    }

                    foreach ($mailObj['email_reply_to'] as $emailToReply) {
                        if (isset($emailToReply[0]) && $emailToReply[0] != "") {
                            $mailNew->AddReplyTo($emailToReply[0]);
                        }
                    }


                    if($mailNew->send() || $emailNotExist){
                        $mailNew->update_q_message($mailObj['id']);
                    }

    //                if (!$mail->Send()) {
    //                        $str .= "Mailer Error: " . $mail->ErrorInfo;
    //                    } else {
    //                        $str .= "<h3>Message Sent to: $name &lt;$email&gt;</h3>";
    //                    }
    //                    
                }
            }

    //        $mail->Subject = $this->f3->get('company_name') . " Password' Reset";
    //        $mail->Body = $email_msg;
    //        $mail->AddAddress("mahavir.jain@dotsquares.com");
    //                $mail->AddAddress("mahavir1.jain@dotsquares.com");
    //                $mail->AddReplyTo("mahavir2.jain@dotsquares.com");
    //                
    //                $mail->queue_message();
            //$mailObj->send();
        }

        function ForgottenPassword() {
            $main_folder = $this->f3->get('main_folder');

            $msg = (isset($_GET['message']) ? $_GET['message'] : "");

            $reset_form_submitted = (($this->f3->get('POST.pwdPassword') && $this->f3->get('POST.pwdConfirmPassword') && $_POST['txtCatsName'] == "Felix Templeton" && $_POST['txtDogsName'] == "Fido Baggins" && $_POST['init_ip'] == $_SERVER['REMOTE_ADDR']) ? 1 : 0);

            $cd = (isset($_GET['cd']) ? $_GET['cd'] : "");
            if ($cd && !$_SESSION['user_id']) {
                $sql = "SELECT user_id FROM pw_reset where code = '$cd'" . ($reset_form_submitted ? "" : " and expiry > now()");
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $u_idin = $myrow['user_id'];
                        if ($reset_form_submitted) {
                            $pw = $this->f3->get('POST.pwdPassword');
                            $sql = "update users set pw = '" . $this->ed_crypt($pw, $u_idin) . "' where ID = '$u_idin'";
                            $this->dbi->query($sql);
                            $this->f3->reroute('/Login/?message=Password Reset...');
                        }
                    } else {
                        $msg .= 'The password request has expired. Please fill out the Forgotten Password Form again.';
                        $cd = 0;
                    }
                }
            }



            if ($msg) {
                $str .= "<h3 class=\"message\">$msg</h3>";
            }

            if ($this->f3->get('POST.txtEmail') && $_POST['txtCatsName'] == "Felix Templeton" && $_POST['txtDogsName'] == "Fido Baggins" && $_POST['init_ip'] == $_SERVER['REMOTE_ADDR']) {

                $email = strtolower($this->f3->get('POST.txtEmail'));
                $user = new Crud($this->db, 'users');
                $user->getByField('email', $email);
                if (!$user->dry()) {

                    $date = new DateTime();
                    $code = md5(microtime());

                    $sql = "insert into pw_reset (user_id, code, expiry)  values ('{$user->ID}', '$code', DATE_ADD(NOW(), INTERVAL 2 HOUR));";
                    $this->dbi->query($sql);

                    $send_url = $this->f3->get('full_url') . "ForgottenPassword?cd=$code";
                    $msg .= "Password Reset Link Sent.<br /><br /><br />Please check your email for password reset instructions...";
                    $email_msg = "<p>Hello {$user->name},</p>
                    A request has been made to reset your password for " . $this->f3->get('company_name') . "</p>
                    <p>Please click the link below to reset your password:</p>
                    <p><a href=\"$send_url\">$send_url</a></p>
                    <p>If you didn't request a password change, please let us know.</p>
                    <p>Regards,<br />" . $this->f3->get('company_name') . "</p>
            ";

                    //prd($this->dbi);
                    $mail = new email_q($this->dbi);
                    $mail->AddAddress($_POST['txtEmail']);
                    //$mail->AddAddress("mahavir.jain@dotsquares.com");
                    //$mail->AddAddress("mahavir1.jain@dotsquares.com");
                    //$mail->AddReplyTo("mahavir2.jain@dotsquares.com");
                    $mail->Subject = $this->f3->get('company_name') . " Password' Reset";
                    $mail->Body = $email_msg;
                    //$mail->queue_message();

                    $mail->send();
    //          mail("", $subject, $css . "\n\n" . $email_msg, $headers);
                    //$str .= "<div style=\"color: white;\">$email_msg</div>";


                    $this->f3->reroute('/ForgottenPassword/?message=Password Reset Email Sent...');
                } else {
                    $this->f3->reroute('/ForgottenPassword/?message=This Email Address Does Not Exist on Our System.');
                }
            }


            $form_obj = new data_editor;
            $list_obj = new data_list;
            $form_obj->hide_confirm = 1;
            $form_obj->bot_protect = 1;
            $form_obj->dbi = $this->dbi;
            //$form_obj->bot_protect = 1;
            $form_obj->table = "users";

            $style = ' class="uk-input uk-form-large" ';
            $form_obj->table = "users";
            $form_obj->form_attributes = array(
                array("txtUsername", "pwdPassword"),
                array("Username", "Password"),
                array("username", "pw"),
                array("", ""),
                array("c", "c"),
                array("", "")
            );

            if ($u_idin) {
                $form_obj->form_attributes = array(
                    array("pwdPassword", "pwdConfirmPassword"),
                    array("Password", "Confirm"),
                    array("pw", "confirm"),
                    array("", ""),
                    array($style . ' placeholder="Password" ', $style . ' onKeypress="submit_on_enter(event)" placeholder="Confirm Password" '),
                    array("c", "c"),
                    array("", "")
                );
            } else {
                $form_obj->form_attributes = array(
                    array("txtEmail"),
                    array("Email Address"),
                    array("email"),
                    array(""),
                    array($style . ' placeholder="Email Address" '),
                    array("c"),
                    array("")
                );
            }


            $form_obj->xtra_validation = '
            ';
            if ($u_idin) {
                $form_obj->xtra_validation .= '
            if(document.getElementById("pwdPassword").value != document.getElementById("pwdConfirmPassword").value) {
            err++;
            error_msg += err + ". Passwords do not match, please re-type both passwords.\\n";
            } else if(document.getElementById("pwdPassword").value.length < 8 && document.getElementById("pwdPassword").value.length) {
            err++;
            error_msg += err + ". Please choose a password of at least 8 characters in length.\\n";
            }
        ';
            }
            $form_obj->button_attributes = array(
                array("Reset Password"),
                array("cmdSignup"),
                array("if(check()) this.form.submit()"),
                array("js_function_add"),
                array(""),
                array("uk-width-1-1 uk-button uk-button-primary uk-button-medium company_colour")
            );

            if ($u_idin) {
                $inputs = '
        *Password must contain at least 8 characters.<hr />
        <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: password"></span>pwdPassword<br />
        <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: password"></span>pwdConfirmPassword<br /><br />';
                $title = "Reset Password";
            } else {
                $inputs = '<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: mail"></span>txtEmail<br />';
                $title = "Login";
            }




            //<a class="list_a" href="SignUp">Sign Up</a>
            $form_obj->form_template = '
        <div class="uk-flex uk-flex-center uk-flex-middle uk-height-viewport">
                <div class="uk-width-medium uk-padding-small" style="border: 1px solid #DDDDDD; background-color: white;">      
        <img src="' . $this->f3->get('img_folder') . 'logo-medium.png" height="90" /><br /><br />

                        <fieldset class="uk-fieldset">
                            <div class="uk-margin">
                                <div class="uk-inline uk-width-1-1">
                                    ' . $inputs . '
                                </div>
                            </div>
                            
                            <div class="uk-margin">
                ' . $form_obj->button_list() . '
                            </div>
                            <div class="uk-margin">
                <a class="uk-float-right uk-link uk-link-muted" href="Login">Back to Login</a>
                </div>
                            <div class="uk-margin">
                <a class="" title="Medical Emergency" href="' . $main_folder . 'Emergency"><img class="" src="' . $this->f3->get('img_folder') . 'emergency.png" width="32" height="32" alt="000" /></a>
                            </div>

        </fieldset>
            </form>      
        
        </div>
        </div>

            ';

            $form_obj->editor_template = 'editor_form';
            $str .= $form_obj->draw_data_editor($list_obj);
            $this->f3->set('content', $str);
            $this->f3->set('title', "Edge - Password Reset");
            $template = new Template;
            echo $template->render('login.htm');
        }

        function beforeroute() {
            session_start();
        }

        function logout() {
            session_start();
            $cookie_name = 'AnInescapableFact';
            $sql = "delete from user_logins where user_id = '{$_SESSION['user_id']}'";
            unset($_COOKIE[$cookie_name]);
            setcookie($cookie_name, '', time() - 3600);
            session_destroy();
            $this->dbi->query($sql);

            $this->f3->reroute('/login');
        }


        function authenticate($user_id = "", $use_redirect = 1) {
            $main_folder = $this->f3->get('main_folder');
            $curr_date_time = date('Y-m-d H:i:s');
            $user = new Crud($this->db, 'users');

            if ($user_id) {
                $user->getByField('ID', $user_id);
            } else {
                $username = strtolower(str_replace(" ", "", $this->f3->get('POST.txtUsername')));
                $password = $this->f3->get('POST.pwdPassword');
                $user->getByField('username', $username);
            }

            $ok = 0;
            if (!$user->dry()) {
                if ($user->user_status_id == 40) {
                    $msg = "Your account is inactive. Please contact head office to reactivate it.";
                } else {
                    $msg = "Invalid Login Credentials!";
                    if (($user->pw == $this->ed_crypt($password, $user->ID) && $user->user_status_id == 30) || $user_id) {
                        $_SESSION['u_level'] = $user->user_level_id;
                        $_SESSION['user_id'] = $user->ID;
                        $_SESSION['name'] = $user->name;
                        $_SESSION['full_name'] = $user->name . ' ' . $user->surname;
                        $_SESSION['user_state'] = $user->state;
                        $_SESSION['email'] = $user->email;
                        $_SESSION['site_id'] = $user->main_site_id;
                        $_SESSION['username'] = $user->username;
                        $_SESSION['company_url'] = $user->url;
                        $_SESSION['company_logo'] = $user->image;

                        $test = $this->ed_crypt($user->username . $user->ID, $user->ID);
                        setcookie('AnInescapableFact', $test, 2147483647);

                        /* $sql = "select lookup_fields.id from lookup_fields
                        left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
                        where foreign_id = '" . $user->ID . "' and table_assoc = 'users'"; */
                        $sql = "select 'n' as type, user_group_id as `id` from users_user_groups where user_id = '" . $user->ID . "' union select 'c' as `type`, user_group_id as `id` from users_user_groups where user_id = '" . $user->ID . "' and user_group_id in (select id from companies);";
                        ;

                        //echo $sql;
                        //die;

                        $result = $this->dbi->query($sql);
                        $num_rows = $result->num_rows;
                        $x = 0;
                        $lids = array();
                        $companies = array();
                        //echo $sql;
                        //exit;
                        while ($myrow = $result->fetch_assoc()) {
                            if ($myrow['type'] == 'n') {
                                $lids[] = $myrow['id'];
                            } else {
                                $company_ids[] = $myrow['id'];
                            }
                            $x++;
                        }
    //                    $userType = $this->getUserType($user->ID);
    //                    $lids[] = $userType;
                    
                        $_SESSION['lids'] = $lids;
                        $company_id_tmp = "";
                        $x = 0;
                        if ($company_ids) {
                            foreach ($company_ids as $company_id) {
                                $company_id_tmp .= ($x ? "," : "") . $company_id;
                                $x++;
                            }
                        }
                        $_SESSION['company_ids'] = $company_id_tmp;

                        if (!$user_id) {
                            $sql = "insert into log_login (user_id, username, attempt_date_time, ip_address, is_successful) values ('{$user->ID}', '{$user->username}', '$curr_date_time', '" . $_SERVER['REMOTE_ADDR'] . "', 1)";
                            $this->dbi->query($sql);
                            $sql = "insert into user_logins (user_id, str) values ('{$user->ID}', '$test')";
                            $this->dbi->query($sql);
                            $_SESSION['last_user_log_id'] = $_SESSION['user_id'];
                            $_SESSION['user_last_log_check_date'] = date('Y-m-d');  
                        }
                        if ($use_redirect)
                            $this->f3->reroute(($this->page_from ? $this->f3->get('base_url') . $this->page_from : '/'));
                        $ok = 1;
                    }
                }
            }
            if (!$ok) {

                $sql = "insert into log_login (user_id, username, attempt_date_time, ip_address, is_successful) values ('{$user->ID}', '$username', '$curr_date_time', '" . $_SERVER['REMOTE_ADDR'] . "', 0)";
                $this->dbi->query($sql);
                $this->f3->reroute('/login/?message=' . $msg);
            }
        }

        function ManageSiteManager() {

            $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
            if ($lookup_id)
                return $this->MyAccount(0, $lookup_id);
        }

        function AssignSiteManager() {

        // prd($_REQUEST);
            $str = "";
            $lookup_id = trim((isset($_GET['uid']) ? $_GET['uid'] : null));
    
            if ($lookup_id && isset($_REQUEST['assign_states']) && $_REQUEST['userDataState'] != "") {
                $userStates = $_REQUEST['userDataState'];
                //prd($_REQUEST['userDataState']);
                $this->assignStates($lookup_id, $_REQUEST['userDataState']);
                $str .= "<b style='color:green'>State has been updated</b>";
            } else if ($lookup_id && (isset($_REQUEST['assign_locations']) || isset($_REQUEST['assign_locations_new']))) {
                if(isset($_REQUEST['userLocation'])){
                
                $userLocations = $_REQUEST['userLocation'];
                }else{
                    $userLocations = array();
                }
                if ($_REQUEST['assign_locations'] == 'Remove Location') {
                    $str .= "<b style='color:green'>Location has been removed</b>";
                    $this->removeLocation($lookup_id, $userLocations);
                } 
                else if ($_REQUEST['assign_locations_new'] == 'Remove Location') {
                    $str .= "<b style='color:green'>Location has been removed</b>";
                    $this->removeLocation($lookup_id, $userLocations);
                } else {
                    
                    $str .= "<b style='color:green'>Location has been updated</b>";
                    $this->assignLocations($lookup_id, $userLocations);
                }
            }
            $userDataAccessType = $this->userDataAccessType($lookup_id);
            $str .= '<form method="POST" name="frmEdit" id="frmEdit">      
        <input type="hidden" name="idin" id="idin"><input type="hidden" name="hdnFilter" id="hdnFilter">      
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script language="JavaScript">
        function edit_user(idin) {
                document.getElementById("idin").value = idin;
                document.frmEdit.action = "Edit/Users";
                document.frmEdit.submit();
        }
        $(document).ready(function() {
        $(".userLocationClass1").select2();
    });
        </script>
            </script><div class=\"fr\"><a class="list_a" target="_TOP" href="javascript:edit_user(' . $lookup_id . ')">Edit</a>';
            $str .= "<a class=\"list_a\" href=\"" . $this->f3->get('main_folder') . "UserCard?uid=$lookup_id\">Open Card</a></div>";

            if ($userDataAccessType == 1) {

                $str .= '<div style="padding:200px"> All States location allowed </div>';
            } else if ($userDataAccessType == 2) {
                $assignedStateList = $this->getAssignedStateIds($lookup_id);
                $selectedState = array();
                foreach ($assignedStateList as $key => $selStateValue) {
                    $selectedState[] = $selStateValue['id'];
                }
                //prd($assignedStateList);

                $stateList = $this->getStateIds();
                $str .= "<form method=\"POST\" name=\"frmEdit\">";
                $selState = "<select name='userDataState[]' id ='userDataState[]' multiple style=\"width: 500px;height: 250px !important;\"><option value=\"\"> Select State </option>";
                foreach ($stateList as $key => $svalue) {
                    if (in_array($svalue['id'], $selectedState)) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }

                    $selState .= '<option value="' . $svalue['id'] . '" ' . $selected . ' >' . $svalue['item_name'] . '</option>';
                }
                $selState .= '</select>';

                $str .= '</br></br><div class="states f1"> <b> Select State </b>   ' . $selState . '</div>';
                $str .= "<div style=\"clear:both\"></div><input type='submit' name='assign_states' value=\"Assign State\"> ";

                $str .= " <div style=\"margin-top: 66px;clear:both\"><b>Assigned State List</b> </br> </div> ";

                // prd($assignedStateList);
                foreach ($assignedStateList as $key => $value) {
                    $str .= $value['item_name'] . "</br>";
                }
            } else if ($userDataAccessType == 3) {

                $stateList = $this->getStateIds();
                $parentSites = $this->getUserSiteIds($lookup_id);
                $assignedSiteArray = array();
                if ($parentSites != "") {
                    $assignedSiteArray = explode(',', $parentSites);
                }

                // prd($assignedSiteArray);
                //$stateAssignedDataStr = implode(',',$stateAssignedDataArray);
                //$str .= '<div class="states"> <b> Select Location </b> </div>';
                //   $str .= "<div style=\"clear:both\"></div><input type='submit' name='assign_locations' id='assign_locations' value=\"Assign Location\"> ";

                foreach ($stateList as $key => $svalue) {
                    $locations = $this->getLocationOfState($svalue['id']);
                    $selLocation = "<div class='fl' style='padding-right:10px'> "
                            . "<select class='userLocationClass" . $key . "' name=userLocation[" . $key . "][]' id ='userLocation[" . $key . "][]' multiple style=\"width: 1000px;height: 300px !important;\"><option value=\"\"> Select location </option>";

                    foreach ($locations as $location) {
                        if (in_array($location['id'], $assignedSiteArray)) {
                            $selected = "selected";
                        } else {
                            $selected = "";
                        }
                        if ($location['client_name']) {
                            //$locationNameSelect = $location['item_name'];
                            $locationNameSelect = $location['item_name'] . " (" . $location['client_name'] . ")";
                        } else {
                            $locationNameSelect = $location['item_name'];
                        }
                        $selLocation .= '<option value="' . $location['id'] . '" ' . $selected . '>' . $locationNameSelect . '</option>';
                    }
                    $selLocation .= '</select></div>';
                    $selLocation .= ' <div class="fl"><button onclick="stateSelect(1,'.$key.')"> Select All </button> <button onclick="stateSelect(2,'.$key.')"> Remove All </button> </div>';
                    
                    
                    $str .= '<script language="JavaScript">
        
        $(document).ready(function() {
        $(".userLocationClass' . $key . '").select2();
    });

    function stateSelect(status, key){
    var classname = ".userLocationClass"+key;
        if(status == 1){
    // alert("hello");
            $(".userLocationClass"+key+" > option").prop("selected","selected");
            var bt_val = $("#assign_locations").attr("bt_data");
        $("#assign_locations_new").value(bt_data);
            $("#assign_locations").click();
        //  alert(bt_val);
        // $(".userLocationClass1 > option").prop("selected","selected"); 
        }else{
        $(".userLocationClass"+key+" > option").prop("selected",false);
            $("#assign_locations").click();
        }
        //alert("hello");
        
        
    }
        </script>';

                    $str .= '<div class="states_new"> <div class="fl">' . $svalue["item_name"] . ' </div> <br> ' . $selLocation . '</div> <div style="clear:both"></div><br>';
                }

                $str .= "<div style=\"clear:both\"></div>"
                        . "<input type='hidden' name='assign_locations_new' id='assign_locations_new' value=\"Assign Location\"> "
                        . "<input type='submit' name='assign_locations' id='assign_locations' bt_data=\"Assign Location\" value=\"Assign Location\"> "
                        . " <input type='submit' name='assign_locations' id='assign_locations'  bt_data=\"Remove Location\" value=\"Remove Location\"> "
                        . " <input type='button' name='assign_locations' id='assign_locations'  bt_data=\"Reset Location\" onClick='javascript:location.reload()' value=\"Reset Location\"> ";
            } else {
                $str = '<div style="padding:200px"> You have not Permission of this  </div>';
            }
            $str .= "</form>";
            return $str;
        }

        function MyAccount($select_manager = "", $user_idin = 0) {
            
            $main_folder = $this->f3->get('main_folder');
            $searchAllocatedSite = $_REQUEST['selectMainSite'];
        
        
            //$this->list_obj = new data_list;
            //$this->editor_obj = new data_editor;
            $user_idin = ($user_idin ? $user_idin : (isset($_POST['user_idin']) ? $_POST['user_idin'] : 0));
            $user_id = ($user_idin ? $user_idin : $_SESSION['user_id']);
            $added_by_id = $_SESSION['user_id'];

            $company_ids = $_SESSION['company_ids'];
    //return $str;    
            $show_warning = (isset($_GET['show_warning']) ? $_GET['show_warning'] : null);
            if ($select_manager) {
                $from_function = 1;
            } else {
                $select_manager = (isset($_GET['select_manager']) ? $_GET['select_manager'] : null);
            }
            $select_site = (isset($_GET['select_site']) ? $_GET['select_site'] : null);
            if(isset($_REQUEST['cmdAddSearch'])){
                $addAction = $_REQUEST['cmdAddSearch'];
                if($addAction == "Add Site"){
                    $selectedAddSites = $_REQUEST['selectAddSite'];
                    if($selectedAddSites){
                        foreach($selectedAddSites as $selectedAddSite){                    
                            $addsql = "insert into associations (association_type_id, child_user_id, parent_user_id, added_by_id)  (select id, '" . $user_id . "', '$selectedAddSite', '$added_by_id' from association_types where name = 'site_staff');";                        
                            $result = $this->dbi->query($addsql);
                            $siteAddedMsg = "Selected site has been added";
                        }
                    }
                }
                
            } 
            
            $str .= ($siteAddedMsg ? "<h3 class='inline_message' style='color:green'>$siteAddedMsg</h3>":"");

            if ($select_site) {
                $item_select = (isset($_POST['item_select']) ? $_POST['item_select'] : null);
                $item_delete = (isset($_POST['item_delete']) ? $_POST['item_delete'] : null);
                $make_main = (isset($_POST['make_main']) ? $_POST['make_main'] : null);

                if (!$_SESSION['site_id'] && !$item_select)
                    $str .= '<div class="message">Please select a site before proceeding...</div>';

                if ($item_delete) {
                    $sql = "select parent_user_id from associations where id = $item_delete";
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $site_id = $myrow['parent_user_id'];
                        }
                    }

                    $sql = "delete from associations where id = $item_delete";
                    $result = $this->dbi->query($sql);
                    if ($site_id == $_SESSION['site_id']) {
                        $sql = "update users set main_site_id = 0 where id = " . $user_id;
                        $result = $this->dbi->query($sql);
                        if (!$user_idin)
                            $_SESSION['site_id'] = 0;
                    }
                }
                if ($item_select) {
                    $sql = "insert into associations (association_type_id, child_user_id, parent_user_id, added_by_id)  (select id, '" . $user_id . "', '$item_select', '$added_by_id' from association_types where name = 'site_staff');";
                    $result = $this->dbi->query($sql);
                }
                if ($make_main) {
                    $sql = "update users set main_site_id = $make_main where id = " . $user_id;
                    $result = $this->dbi->query($sql);
                    if (!$user_idin)
                        $_SESSION['site_id'] = $make_main;
                }
                if ($item_delete || $item_select) {
                    $sql = "SELECT count(id) as num_items, parent_user_id from associations where association_type_id in (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $user_id;
                    $num_items = 0;
                    if ($result = $this->dbi->query($sql)) {
                        if ($myrow = $result->fetch_assoc()) {
                            $num_items = $myrow['num_items'];
                        }
                    }
                    $parent_user_id = $myrow['parent_user_id'];
                    if ($_SESSION['site_id'] == 0) {
                        $_SESSION['site_id'] = $parent_user_id;
                        $main_item_deleted = 1;
                    }
                    if ($num_items == 1 || isset($main_item_deleted)) {
                        $sql = "update users set main_site_id = $parent_user_id where id = " . $user_id;
                        $result = $this->dbi->query($sql);
                    }
                }
                $txtFindSite = (isset($_POST['txtFindSite']) ? $_POST['txtFindSite'] : null);
                $userDataState = (isset($_POST['userDataState']) ? $_POST['userDataState'] : 0);

                $userDataAccessType = $this->userDataAccessType($user_id);
                $assignedStateData = $this->getAssignedStateIds($user_id);
                if (is_array($assignedStateData)) {
                    $stateAssignedDataArray = array_column($assignedStateData, 'id');
                } else {
                    $stateAssignedDataArray = array();
                }

                $stateAssignedDataStr = implode(',', $stateAssignedDataArray);

                $stateSelect = $this->getStateSelectHtml($user_id, $userDataAccessType, $stateAssignedDataArray, $userDataState);         
            
                $str .= '
            
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
                
                
                
                
                if ($userDataAccessType != 3) {
                
                    $str .= '<div class="fl"><h3>Find a site to add or replace by entering all or part of their name below:</h3>';
    //                $str .= '<p>' . $stateSelect . ' <input maxlength="50" name="txtFindSite" id="search" value="' . $txtFindSite . '" placeholder="Type all/part of the site\'s name here..." type="text" class="search_box" />'
    //                        . '<input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />'
    //                        . '<input type="button" name="reset" value="Reset" onclick="resetSiteSearch()"class="search_button" /> ' . $search_msg
    //                        . '</p></div><div class="cl"></div>';
    //                
                
                    $allowedSiteCondition = "";
                    if($userDataAccessType == 2){
                        $allowedStateList = implode($stateAssignedDataArray);
                        if($allowedStateList){
                            $allowedSiteCondition = " and users.state_id in ($allowedStateList) ";
                        }
                    }
                    
                    $newsearch = "  where 1";
                    $newsearch .= " and users.user_status_id = 30
            and users.id != " . $user_id . "
            and users.id not in (select users.id
                            from users left join associations on associations.parent_user_id = users.id 
                            where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $user_id . ")";
                    $newsearch = "
                    select distinct(users.id) as id, CONCAT(users.name, ' ', users.surname, ' (', states.item_name, ')') as add_site_name
                    from users
                    left join states on states.id = users.state
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                    inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'SITE'
                    $newsearch
                    " . ($company_ids ? "  and users.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "") . "
                    and users.name not like '%adhoc%'
                    ";
                    $siteForAllowLists = array();
                    if ($result = $this->dbi->query($newsearch)) {
                        while ($myrow = $result->fetch_assoc()) {
                            $siteForAllowLists[] = $myrow;
                        }
                    }
                    
            

    $selAddLocation = "<div style='padding-right:10px;width:100%;'> "
                            . ""
            . " <select class='userAddLocationClass' name='selectAddSite[]' id ='selectAddSite[]' style=\"width: 90%;height:700px !important;\" multiple ><option value=\"\"> Select location </option>";
    //prd($assignedLists);
                    foreach ($siteForAllowLists as $siteForAllowList) {                   
                        
                        $selAddLocation .= '<option value="' . $siteForAllowList['id'] . '" >' . $siteForAllowList['add_site_name'] . '</option>';
                    }
                    $selAddLocation .= '</select></div>';
                    
                    
                    $str .= '<div class=""><h3>Add your site:</h3>
                    <p><div class="row"><div class="fl" style="width:100%">' . $selAddLocation . ''
                            . '</div><div class="cl">&nbsp;</div><div class="margin-top:15px;" style="width:30%"><input type="submit" name="cmdAddSearch" value="Add Site" class="search_button" /></div>'                       
                            . '</p></div></div><div class="cl"></div>'
                            . '<script language="JavaScript">
        
        $(document).ready(function() {
        $(".userAddLocationClass").select2();
    });</script>';
                    
                }
                if($txtFindSite || $userDataState) {
                    if ($txtFindSite){
                        $search = $txtFindSite;
                        $search = "
            where (users.name LIKE '%$search%'
            or users.surname LIKE '%$search%'
            or users.email LIKE '%$search%'
            or users.employee_id LIKE '%$search%'
            or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')";
                    }
    
                    if ($userDataState) {
                        $search .= " and users.state = '" . $userDataState . "' ";
                    }
                    
                    

                    $search .= " and users.user_status_id = 30
            and users.id != " . $user_id . "
            and users.id not in (select users.id
                            from users left join associations on associations.parent_user_id = users.id 
                            where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $user_id . ")";
                    $this->list_obj->sql = "
                    select distinct(users.id) as idin, CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"select_item(\'', users.id, '\', \'$user_idin\')\" value=\"', users.name, ' ', users.surname, ' (', states.item_name, ')\">') as `Select Site Below`
                    from users
                    left join states on states.id = users.state
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                    inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'SITE'
                    $search
                    " . ($company_ids ? " and users.id in (select user_id from users_user_groups where user_group_id in ($company_ids)) " : "") . "
                    and users.name not like '%adhoc%'
                    ";
                    
                

                    // echo  $this->list_obj->sql;
                    //die;
    //                and lookup_answers.lookup_field_id in (select user_group_id from users_user_groups where user_id = ".$user_id.")
    //                inner join companies on companies.id = lookup_fields.id
                    //return "<textarea>{$this->list_obj->sql}</textarea>";

                    $str .= ($this->list_obj->draw_list() ? $this->list_obj->draw_list() : "<h3 class='inline_message'>No sites found, please try again...</h3>");
                } 
    $str .= '
            <input type="hidden" name="item_select" id="item_select" />
            <input type="hidden" name="item_delete" id="item_delete" />
            <input type="hidden" name="make_main" id="make_main" />
            <input type="hidden" name="user_idin" id="user_idin" />
            <script>
            function item_delete(item_id, user_idin) {
                confirmation = "Are you sure about removing this site from ' . ($user_idin ? "their" : "your") . ' list?";
                if(confirm(confirmation)) {
                document.getElementById(\'user_idin\').value = user_idin
                document.getElementById(\'item_delete\').value = item_id
                document.frmEdit.submit()
                }
            }
            function resetSiteSearch(){
            //alert("hello");
                document.getElementById(\'userDataState\').value = "";
                document.getElementById(\'search\').value = "";
            }
            function select_item(item_id, user_idin) {
                document.getElementById(\'user_idin\').value = user_idin
                document.getElementById(\'item_select\').value = item_id
                document.frmEdit.submit()
            }
            function make_main(item_id, user_idin) {
                document.getElementById(\'user_idin\').value = user_idin
                document.getElementById(\'make_main\').value = item_id
                document.frmEdit.submit()
            }
            
            function setMainSite(){
                    var mainSite = $("#selectMainSite").val();
                    //if(mainSite){
                        //make_main(mainSite
                        document.frmEdit.submit();
    //                }else{
    //                    alert("Please Select Site");
    //                }
                    //alert("mainsite");
            }
            </script>
        ';

                //$this->list_obj->table_class = " ";

                if ($userDataAccessType != 3) {
                    $deleteAct = "<a class=\"list_a\" href=\"JavaScript:item_delete(', associations.id, ', \'$user_idin\')\">X</a>";
                }
                
                $queryAssignedSites = "select users.id as `idin`
        , CONCAT('$deleteAct ', users.name, ' ', users.surname, ' (', states.item_name, ')<br />', if(users2.main_site_id = associations.parent_user_id, CONCAT('<span style=\"font-weight: bold; color: green;\">Main Site</span> " . ($user_idin ? "" : "<a class=\"list_a\" href=\"UserCard?uid=', users.id, '\" style=\"font-weight: bold; color: green;\" >Site Card Page <span style=\"color:green;font-size: 20px;\">&#8658;</span></a> <a class=\"list_a\" href=\"Page/SiteNotes\">Open Whiteboard</a><a class=\"list_a\" href=\"Page/OccurrenceLog?show_min=\">Open Occurrence Log</a>") . "'), CONCAT('<span style=\"font-weight: bold; color: red;\">Not Main Site</span> <a href=\"JavaScript:make_main(', users.id, ', \'$user_idin\')\" class=\"list_a\">Make it " . ($user_idin ? "their" : "my") . " Main Site</a>'))) as `" . ($user_idin ? "Staff Member's" : "Your") . " Current Site(s) are Shown Below`
                            from users
                            left join states on states.id = users.state
                            left join associations on associations.parent_user_id = users.id
                            left join users2 on users2.id = associations.child_user_id
                            where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $user_id;

                if ($searchAllocatedSite) {
                        
                        $queryAssignedSites .= "  and users.id = '" . $searchAllocatedSite . "'";
                    }
                
    //            $assignedStateData = $this->getAssignedStateIds($user_id);
    //            if(is_array($assignedStateData)){
    //                 $stateAssignedDataArray = array_column($assignedStateData, 'id');
    //            }else{
    //                $stateAssignedDataArray =  array();
    //            }
    //            
                if ($userDataAccessType == 2) {
                    $queryAssignedSites .= " and users.state in (" . $stateAssignedDataStr . ")";
                }
                
                
                $queryAssignedSites .= ' ORDER BY (if(users2.main_site_id = associations.parent_user_id,1,0))  desc';

    //                echo $queryAssignedSites;
    //                die;
                
                
                
                $querySearchAssignedSites = "select users.id as `id`,
                    CONCAT(users.name, ' ', users.surname, ' (', states.item_name, ')') assignedSiteName 
                            from users
                            left join states on states.id = users.state
                            left join associations on associations.parent_user_id = users.id
                            left join users2 on users2.id = associations.child_user_id
                            where associations.association_type_id = (select id from association_types where name = 'site_staff') and associations.child_user_id = " . $user_id;

                
    //            $assignedStateData = $this->getAssignedStateIds($user_id);
    //            if(is_array($assignedStateData)){
    //                 $stateAssignedDataArray = array_column($assignedStateData, 'id');
    //            }else{
    //                $stateAssignedDataArray =  array();
    //            }
    //            
                if ($userDataAccessType == 2) {
                    $querySearchAssignedSites .= " and users.state in (" . $stateAssignedDataStr . ")";
                }
                
                
                $querySearchAssignedSites .= ' ORDER BY (if(users2.main_site_id = associations.parent_user_id,1,0)), users.name asc';

            
                $assignedLists = array();        
            if ($resultAssigned = $this->dbi->query($querySearchAssignedSites)) {
                while ($myrowAssigned = $resultAssigned->fetch_assoc()) {
                    $assignedLists[] = $myrowAssigned;
                }
            }    
                
                //$assignedLocation = 
                $selectedAssignedSiteArray = array();
            

    $selLocation = "<div class='fl' style='padding-right:10px'> Search  "
                            . "<select class='userLocationClass" . $key . "' name='selectMainSite' onChange='setMainSite()' id ='selectMainSite' style=\"width: 350px;height: 300px !important;\"><option value=\"\"> Select location </option>";
    //prd($assignedLists);
                    foreach ($assignedLists as $assignedList) {
                        if ($assignedList['id'] == $searchAllocatedSite) {
                            $selected = "selected";
                        } else {
                            $selected = "";
                        }
                        
                        $selLocation .= '<option value="' . $assignedList['id'] . '" ' . $selected . '>' . $assignedList['assignedSiteName'] . '</option>';
                    }
                    $selLocation .= '</select>';
                    // $selLocation .= '<input type="button" name="cmdFollowSearch" value="Make It Main Site" onClick="setMainSite()" class="search_button" />';



    
    $str .= $selLocation.'<div style="clear:both"></div><script language="JavaScript">
        
        $(document).ready(function() {
        $(".userLocationClass' . $key . '").select2();
    });</script>';
                

                $this->list_obj->sql =  $queryAssignedSites;
                //return "<textarea>{$this->list_obj->sql}</textarea>";

                if ($tmp = $this->list_obj->draw_list()) {
                    $str .= "$tmp";
                    $induction_obj = new UserController();
                    $induction_res = $induction_obj->induction(1, 1);
                    if ($induction_res != 'passed')
                    //echo $this->redirect($main_folder . "Induction");
                        $or_show = "Change";
                } else {
                    $or_show = "Find";
                }
            } else if ($select_manager) {

                $uname = ($select_manager ? "Manager" : "Site");
                $lname = strtolower($uname);
                $item_select = (isset($_POST['item_select']) ? $_POST['item_select'] : null);
                if ($item_select) {
                    $sql = "delete from associations where association_type_id in (select id from association_types where name = '" . $lname . "_staff') and associations.child_user_id = " . $user_id;
                    $result = $this->dbi->query($sql);
                    $sql = "insert into associations (association_type_id, child_user_id, parent_user_id, added_by_id)  (select id, '" . $user_id . "', '$item_select', '$added_by_id' from association_types where name = '" . $lname . "_staff')";
                    $result = $this->dbi->query($sql);
                    if ($select_manager) {
                        $sql = "update appraisal_checks set assessor_id = $item_select where subject_id = " . $user_id . " and year(date_added) = year(now())";
                        $result = $this->dbi->query($sql);
                        if ($from_function)
                            echo $this->redirect($this->f3->get('curr_page'));
                    }
                }

                $str .= '
            <input type="hidden" name="item_select" id="item_select" />
            <script>
            function select_item(item_id) {
                document.getElementById(\'item_select\').value = item_id
                document.frmEdit.submit()
            }
            </script>
        ';
                $this->list_obj->sql = "select users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `$uname`, states.item_name as `State`, phone as `Phone`, users.email as `Email`
                            from users
                            left join states on states.id = users.state
                            left join associations on associations.parent_user_id = users.id 
                            where associations.association_type_id = (select id from association_types where name = '" . $lname . "_staff') and associations.child_user_id = " . $user_id;
                
                
                
                
                
                $str .= '<div class="fl" style="padding-right: 20px;">';
                if ($tmp = $this->list_obj->draw_list()) {
                    $str .= "<h3>Your Current $uname is Shown Below</h3>$tmp";
                    $or_show = "Change";
                } else {
                    $or_show = "Find";
                }
                $txtFindManager = (isset($_POST['txtFindManager']) ? $_POST['txtFindManager'] : null);
                
                $str .= '</div><div class="fl"><h3>' . $or_show . ' your ' . $lname . ' by entering all or part of their name below:</h3>
                <p><input maxlength="50" name="txtFindManager" id="search" placeholder="Type all/part of your ' . $lname . '\'s name here..." type="text" class="search_box" /><input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg
                        . '</p></div><div class="cl"></div>';
                if ($txtFindManager) {
                    $search = $txtFindManager;
                    $search = "
            where (users.name LIKE '%$search%'
            or users.surname LIKE '%$search%'
            or users.email LIKE '%$search%'
            or users.employee_id LIKE '%$search%'
            or CONCAT(users.name, ' ', users.surname) LIKE '%$search%')
            and users.user_status_id = 30
            and users.id != " . $user_id . ";
            ";
                
                
                    $this->list_obj->sql = "
                    select distinct(users.id) as idin, CONCAT(users.name, ' ', users.surname) as `Name`, states.item_name as `State`,
                    phone as `Phone`, users.email as `Email`,
                    CONCAT('<input type=\"button\" class=\"list_a\" onClick=\"select_item(\'', users.id, '\')\" value=\"Select $uname\">') as `**` from users
                    left join states on states.id = users.state
                    inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                    inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = '" . ($select_manager ? "MANAGEMENT" : "SITE") . "'
                    $search";
                    $str .= $this->list_obj->draw_list();
                }
                
            
                
                
                
                if ($select_site && $select_site != 1)
                    $str .= '<br /><br /><br /><a class="list_a" href="' . $select_site . ($_GET['show_min'] ? '?show_min=1' : '') . '">&lt;&lt; Back to Whiteboard</a>';
            } else {

                $_REQUEST['idin'] = $user_id;
                $this->editor_obj->table = "users";
                $this->editor_obj->title = "My Settings";
                $style = 'class="full_width"';
                $this->editor_obj->form_attributes = array(
                    array("txtName", "txtSurname", "txtPreferredName", "txtEmail", "txtEmail2", "txtPhone", "txtTelephone2", "txtAddress", "txtSuburb", "selState", "txtPostcode", "txtMainSite"),
                    array("Name", "Surname", "Preferred Name", "Email", "Email 2", "Phone", "Phone 2", "Address", "Suburb", "State", "Postcode", "Website"),
                    array("name", "surname", "preferred_name", "email", "email2", "phone", "phone2", "address", "suburb", "state", "postcode", "company"),
                    array("", "", "", "", "", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states order by item_name", "", ""),
                    array($style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style, $style),
                    array("c", "c", "n", "c", "n", "c", "n", "c", "c", "c", "c", "n")
                );
                $this->editor_obj->button_attributes = array(
                    array("Update Above Details"),
                    array("cmdSave"),
                    array("if(save()) this.form.submit()"),
                    array("js_function_save")
                );
                $action = $_POST["hdnAction"];

                $this->editor_obj->editor_template = 'editor_form';
                $pw_saved = '';
                if ($action == "save_record") {
                    //$old_details = get_user_address($this->dbi);
                    $pw1 = $_POST['pw1'];
                    $pw2 = $_POST['pw2'];
                    $msg = "";
                    $save_id = $user_id;
                    if ($pw1 && $pw2) {
                        if ($pw1 != $pw2) {
                            $msg = "Passwords don't match. The password was NOT saved";
                        } else if (strlen($pw1) < 8) {
                            $msg = "The chosen password must be at least 8 characters long.  The password was NOT saved";
                        } else {
                            $pw = $this->ed_crypt($pw1, $save_id);
                            $sql = "update users set pw = '$pw' where id = $save_id;";
                            $this->dbi->query($sql);
                            $msg = "The password has been saved<br /><br /><a href=\"{$main_folder}\">Click Here to Continue</a>";
                            $sql = "insert into user_update_log (user_id, date_time, action) values (" . $user_id . ", now(), 'USER_PW_CHANGE')";
                            $this->dbi->query($sql);
                            $pw_saved = 1;
                        }
                    }
                    if ($msg)
                        $str .= '<br /><br /><div class="message">' . $msg . '</div><br />';
                }

                $this->editor_obj->form_template = '
                    <h3>My Account</h3>
                    ' . ($show_warning && $action != "save_record" ? '<div class="message">Please update your details and change your password before proceeding...</div>' : '') . '
                    Please choose your email address carefully. *Required Fields.<br /><br />
                    <div class="fl med_textbox"><nobr>ttxtName</nobr><br />txtName</div>
                    <div class="fl med_textbox"><nobr>ttxtSurname</nobr><br />txtSurname</div>
                    <div class="fl med_textbox"><nobr>ttxtPreferredName</nobr><br />txtPreferredName</div>
                    <div class="fl med_textbox"><nobr>ttxtEmail</nobr><br />txtEmail</div>
                    <div class="fl med_textbox"><nobr>ttxtEmail2</nobr><br />txtEmail2</div>
                    <div class="fl med_textbox"><nobr>ttxtPhone</nobr><br />txtPhone</div>
                    <div class="fl med_textbox"><nobr>ttxtTelephone2</nobr><br />txtTelephone2</div>
                    <div class="fl med_textbox"><nobr>ttxtAddress</nobr><br />txtAddress</div>
                    <div class="fl med_textbox"><nobr>ttxtSuburb</nobr><br />txtSuburb</div>
                    <div class="fl med_textbox"><nobr>tselState</nobr><br />selState</div>
                    <div class="fl med_textbox"><nobr>ttxtPostcode</nobr><br />txtPostcode</div>
                    <div class="fl med_textbox"><nobr>ttxtMainSite</nobr><br />txtMainSite</div>
                    <div class="cl"></div>
                    <b>Password, 8 Characters Minimum</b><br /><nobr>(Leave passwords blank if not changing)</nobr>
                    <div class="cl"></div>
                    <div class="fl med_textbox"><nobr>Password</nobr><br /><input name="pw1" type="password" class="full_width" autocomplete="off"></div>
                    <div class="fl med_textbox"><nobr>Confirm Password</nobr><br /><input name="pw2" type="password" class="full_width" autocomplete="off"></div>
                    <div class="cl"></div>
                    <br />
                    ' . $this->editor_obj->button_list() . "<br /><br />";

                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
                $str .= $this->editor_obj->sql;
                if (!$show_warning)
                    $str .= '<iframe width="100%" height="700" src="AccountDetails?show_min=1" frameborder="0"></iframe>';
                /*
                if($action == "save_record") {
                $new_details = get_user_address($this->dbi, $email, $user_name);
                if($old_details != $new_details) {
                $subject = "Edge: Change of Details Notice";
                $email_send = "megen.li@scgs.com.au";
                $cc = "<callcentre@scgs.com.au>, <hr.department@scgs.com.au>";
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $headers .= 'From: '.$user_name.' <'.$email.'>' . "\r\n";
                $headers .= 'To: <'.$email_send.'>' . "\r\n";
                $headers .= 'Cc: ' . $cc . "\r\n";
                include("print_css.php");
                $msg = "<h3>$user_name has changed some or all of their details in Edge</h3><br /><h4>Old Details:</h4>$old_details<br /><br /><h4>New Details:</h4>$new_details<br /><br /><p>Please make the appropriate changes in the other systems.</p>";
                $old_details = str_replace("<br />", " ", $old_details);    $old_details = str_replace("Employee ID:", "", $old_details);    $old_details = str_replace("Name:", "", $old_details);
                $old_details = str_replace("Address:", "", $old_details);    $old_details = str_replace("Phone:", "", $old_details);    $old_details = str_replace("   ", " ", $old_details);
                $new_details = str_replace("<br />", " ", $new_details);    $new_details = str_replace("Employee ID:", "", $new_details);    $new_details = str_replace("Name:", "", $new_details);
                $new_details = str_replace("Address:", "", $new_details);    $new_details = str_replace("Phone:", "", $new_details);    $new_details = str_replace("   ", " ", $new_details);
                $sql = "insert into user_update_log (user_id, date_time, action, old_details, new_details) values (".$user_id.", now(), 'USER_UPDATED', '$old_details', '$new_details')";
                $this->dbi->query($sql);
                mail("", $subject, $css . "\n\n" . $msg, $headers);
                }
                }
                */
            }
            return $str;
        }

        function SignUp() {
            $main_folder = $this->f3->get('main_folder');
            $page_from = (isset($_GET['page_from']) ? $_GET['page_from'] : "");
            $form_obj = new data_editor;
            $list_obj = new data_list;
            $form_obj->dbi = $this->dbi;
            $form_obj->bot_protect = 1;
            $style = 'style="background-color: white !important; font-size: 30px; padding-top: 10px; padding-bottom: 10px; width: 300px;"';
            $form_obj->table = "users";
            $sex_select = "select 0 as id, '--- Select ---' as item_name union all select 'M', 'Male' union all select 'F', 'Female';";
            $form_obj->form_attributes = array(
                array("txtFirstName", "txtSurname", "txtEmail", "txtPhone", "txtAddress", "txtSuburb", "txtPostcode", "selState", "calDOB", "selSex", "pwdPassword", "pwdConfirmPassword", "txtConfirmEmail"),
                array("Given Name(s)", "Family Name", "Email", "Phone", "Address", "Suburb", "Postcode", "State", "Date of Birth", "Sex", "Password", "Confirm", "Retype Email"),
                array("name", "surname", "email", "phone", "address", "suburb", "postcode", "state", "dob", "sex"),
                array("", "", "", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states where item_name NOT LIKE '%TAMIL%' order by item_name", "", $sex_select, "", ""),
                array($style, $style, $style . "onChange=\"JavaScript:check_email(document.getElementById('txtEmail').value)\"", $style, $style, $style, $style, 'style="width: 306px;"', $style, 'style="width: 306px;"', $style, $style, $style),
                array("c", "c", "c", "c", "c", "c", "c", "c", "c", "c", "c", "c", "c"),
                array("", "", "", "", "", "", "", "", "", "user_group", "", "", "")
            );

            if (!$_SESSION['user_id'] && $_POST['txtCatsName'] == "Felix Templeton" && $_POST['txtDogsName'] == "Fido Baggins" && $_POST['init_ip'] == $_SERVER['REMOTE_ADDR']) {
                // *************** DON'T FORGET TO REMOVE THE FOLLOWING 2 LINES WHEN THE SYSTEM GOES LIVE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!   ********************
                //$sql = "delete from users where id > 3";
                //$result = $this->dbi->query($sql);

                $form_obj->column_count = 10;
                $sql = $form_obj->add_record();
                $result = $this->dbi->query($sql);
                $iid = $this->dbi->insert_id;
                $curr_date_time = $this->curr_date_time;
                //$str .= $sql;
                $sql = "insert into lookup_answers (foreign_id, lookup_field_id, table_assoc) values ($iid, (select id from lookup_fields where item_name = 'Applicant' and value='HR'), 'users')";
                $result = $this->dbi->query($sql);
                $uname = str_replace("'", "", $_POST['txtFirstName']);
                $uname = str_replace(" ", "", $uname);
                $usurname = str_replace("'", "", $_POST['txtSurname']);
                $usurname = str_replace(" ", "", $usurname);
                $username = strtolower("$uname.$usurname");
                $username_tmp = $username;
                $x = 1;
                $finished = 0;
                while (!$finished) {
                    $sql = "select id from users where username = '$username_tmp'";
                    if ($result3 = $this->dbi->query($sql)) {
                        if ($result3->num_rows) {
                            $x++;
                            $username_tmp = $username . $x;
                        } else {
                            $finished = 1;
                        }
                    }
                }
                $sql = "update " . $form_obj->table . " set pw = '" . $this->ed_crypt($_POST['pwdPassword'], $iid) . "', user_level_id = 100, join_date = '$curr_date_time', user_status_id = 30, username = '$username_tmp' where id = $iid;";
                $result = $this->dbi->query($sql);

                $_SESSION['u_level'] = 100;
                $_SESSION['user_id'] = $iid;
                $_SESSION['name'] = $_POST['txtFirstName'];
                //$_SESSION['email'] = $user->email;
                $_SESSION['username'] = $username_tmp;
                $lids[0] = 2003;
                $userType = $this->getUserType($iid);
                $lids[] = $userType;
                $_SESSION['lids'] = $lids;

                echo $this->redirect($page_from);
            } else {
                $str .= '
        <style>
        .xtras{
        border:1px solid #000;
        
    }

        body {
            background: url("' . $this->f3->get('img_folder') . 'bg.jpg") no-repeat center center fixed; 
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }
        </style>
        <script>
            function validateEmail(email) {
            var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
            return re.test(email);
            }
            function check_email(email) {
            xmlhttp = (window.XMLHttpRequest ? xmlhttp = new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                document.getElementById("hdnExistingEmail").value = xmlhttp.responseText
                }
            };
            xmlhttp.open("GET","CheckEmail?eml="+email,true);
            xmlhttp.send();
            }
        </script>
        <input type="hidden" name="hdnExistingEmail" id="hdnExistingEmail">';

                $form_obj->xtra_validation = '
            check_email(document.getElementById("txtEmail").value)
            
            if(document.getElementById("pwdPassword").value != document.getElementById("pwdConfirmPassword").value) {
            err++;
            error_msg += err + ". Passwords do not match, please re-type both passwords.\\n";
            } else if(document.getElementById("pwdPassword").value.length < 8 && document.getElementById("pwdPassword").value.length) {
            err++;
            error_msg += err + ". Please choose a password of at least 8 characters in length.\\n";
            }
            if(document.getElementById("txtEmail").value != "") {
            if(validateEmail(document.getElementById("txtEmail").value) == false) {
                err++;
                error_msg += err + ". Please enter a valid email address.\\n";
            } else if(document.getElementById("txtEmail").value != document.getElementById("txtConfirmEmail").value) {
                err++;
                error_msg += err + ". Email addresses do not match, please ensure that they are correct.\\n";
            } else {
                if(document.getElementById("hdnExistingEmail").value == "yes") {
                err++;
                error_msg += err + ". This Email address (" + document.getElementById("txtEmail").value + ") already exists, please try another email address.\\n";
                }
            }
            }
        ';
                $form_obj->button_attributes = array(
                    array("Create Account"),
                    array("cmdSignup"),
                    array("if(check()) this.form.submit()"),
                    array("js_function_add")
                );
                $form_obj->form_template = '
            <div class="login_form">
            <img src="' . $this->f3->get('img_folder') . 'logo-medium.png" height="120"/><br />
            <div class="login_field_area">
            ttxtFirstName txtFirstName<br />
            ttxtSurname txtSurname<br />
            tcalDOB calDOB<br />
            tselSex selSex<br />
            ttxtEmail txtEmail<br />
            ttxtConfirmEmail txtConfirmEmail<br />
            ttxtPhone txtPhone<br />
            ttxtAddress txtAddress<br />
            ttxtSuburb txtSuburb<br />
            ttxtPostcode txtPostcode<br />
            tselState selState<br /><br />
            *Password must contain at least 8 characters.<br /><br />
            tpwdPassword pwdPassword<br />
            tpwdConfirmPassword* pwdConfirmPassword<br /><br />
            ' . $form_obj->button_list() . '
            </div>
            <div class="cl"></div>
            </div>
        ';
            }
            $form_obj->editor_template = 'editor_form';
            $str .= $form_obj->draw_data_editor($list_obj);
            $this->f3->set('title', "Sign Up to Edge");
            $this->f3->set('content', $str);
            $template = new Template;
            echo $template->render('employment_layout.htm');
        }

        function CheckEmail() {
            $eml = (isset($_GET['eml']) ? $_GET['eml'] : null);
            $result = $this->dbi->query("SELECT id from users where email = '$eml'");
            echo ($result->num_rows ? "yes" : "no");
        }

        function StaffPositions() {
            $main_folder = $this->f3->get('main_folder');

            /*
            $sql = "select id, pw, CONCAT(employee_id, ' - ', name, ' ', surname) as `name`, username from users where id not in (select user_id from log_login where is_successful = 1) and user_level_id >= 300 and username != '' and employee_id != ''";
            if($result = $this->dbi->query($sql)) {
            while($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $pw = $myrow['pw'];
            $name = $myrow['name'];
            $username = $myrow['username'];
            $new_pw = $this->ed_crypt($username, $id);
            $str .= "<h3>";
            if($new_pw != $pw) {
            $this->dbi->query("update users set pw = '$new_pw' where id = $id");
            $str .= "*";
            $str .= "$name ($username)</h3>";
            }


            }
            }
            return $str;
            $sql = "select id, pw, CONCAT(employee_id, ' - ', name, ' ', surname) as `name`, username from users where user_level_id >= 300
            and username in ('roshan.bhandari','sudarshan.dahal','neera.gauchansherchan','ivan.gauchan','subash.gauchan','suman.gauchan','sandip.gurung','jatinkumar.patel','resina.sitoula','sutharsan.suntharalingam','rabin.timsina','bikrant.gautam','birunthan.naganathan','elvi.contuzzi','leehwan.kim','drage.ristorski','ljuba.gorgierska','sajin.sharma','satish.karki','rabin.dahal','tinihang.limbu','rabina.basnet','nica.lay','anupam.dhakal','farouk.boubekeur','shushma.tamang','nissan.youkhna','nilesh.dahal')";
            if($result = $this->dbi->query($sql)) {
            while($myrow = $result->fetch_assoc()) {
            $id = $myrow['id'];
            $pw = $myrow['pw'];
            $name = $myrow['name'];
            $username = $myrow['username'];
            $new_pw = $this->ed_crypt($username, $id);
            $str .= "<h3>";
            if($new_pw != $pw) {
            $this->dbi->query("update users set pw = '$new_pw' where id = $id");
            $str .= "*";
            }

            $str .= "$name ($username)</h3>";

            }
            }

            return $str;
            */
            $this->list_obj = new data_list;
            $this->editor_obj = new data_editor;
            $my_position = (isset($_GET['my_position']) ? $_GET['my_position'] : null);
            if ($my_position) {
                $sql = "
            SELECT staff_positions.title, staff_positions.description
                FROM users
                left join staff_positions on staff_positions.id = users.staff_position_id
                where users.id = " . $_SESSION['user_id'];
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $str = "<h3>Position Title: " . $myrow['title'] . "</h3><hr />" . $myrow['description'];
                } else {
                    $str = "<h3></h3>";
                }
            } else {
                $this->list_obj->title = "";
                $filter_string = "filter_string";
                $this->list_obj->sql = "
        SELECT staff_positions.id as `idin`, staff_positions.title as `Title`,
        'Edit' as `*`, 'Delete' as `!`
        FROM staff_positions
        where 1
        $filter_string
        order by title
        ";
                $this->editor_obj->table = "staff_positions";
                $style = 'style="width: 600px"';
                $this->editor_obj->form_attributes = array(
                    array("txtTitle", "cmsDescription"),
                    array("Title", "Description"),
                    array("title", "description"),
                    array("", ""),
                    array($style, 'height: "280",	width: "98%"',),
                    array("c", "n")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset", "Filter"),
                    array("cmdAdd", "cmdSave", "cmdReset", "cmdFilter"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();", "filter()"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit", "js_function_filter")
                );
                $this->editor_obj->form_template = '
        <table class="standard_form">
        <tr>
        <td class="form_header">Staff Positions</td>
        </tr>
        <tr>
        <td>
        <div class="fl">ttxtTitle<br />txtTitle</div>
        <div class="cl"></div>
        <span class="field_header">tcmsDescription</span><br />
        cmsDescription
        <br />
        <div class="cl"></div>
        <hr />
        button_list
        </tr>
        </td>
        </table>
        ';
                $this->editor_obj->editor_template = 'editor_form<hr />editor_list';
                $str = $this->editor_obj->draw_data_editor($this->list_obj);
            }
            return $str;
        }

        function StaffLicences() {
            $main_folder = $this->f3->get('main_folder');

            return '';
            //$top_xtra = (isset($_GET['top_xtra']) ? $_GET['top_xtra'] : null);
            //require_once("top$top_xtra.php");
            $hr_user = $this->f3->get('hr_user');

            $show_warning = (isset($_GET['show_warning']) ? $_GET['show_warning'] : null);
            $manage_mode = (isset($_GET['manage_mode']) ? ($hr_user ? $_GET['manage_mode'] : null) : null);
            $lookup_id = (isset($_GET['lookup_id']) ? $_GET['lookup_id'] : null);
            $verify_id = (isset($_GET['verify_id']) ? $_GET['verify_id'] : null);
            $unverify_id = (isset($_GET['unverify_id']) ? $_GET['unverify_id'] : null);
            $state_id = (isset($_GET['state_id']) ? $_GET['state_id'] : null);
            $deactivate = (isset($_GET['deactivate']) ? $_GET['deactivate'] : null);
            $deactivateid = (isset($_GET['deactivateid']) ? $_GET['deactivateid'] : null);
            $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
            $edit_id = (isset($_POST['idin']) ? $_POST['idin'] : null);
            $licence_search = (isset($_GET['licence_search']) ? $_GET['licence_search'] : null);
            if ((!$hr_user || !$lookup_id) && !$manage_mode && !$deactivate && !$verify_id && !$unverify_id && $_SESSION['u_level'] < 700)
                $lookup_id = $_SESSION['user_id'];
            if (!$lookup_id && !$manage_mode && !$deactivate && !$verify_id && !$unverify_id)
                $lookup_id = $_SESSION['user_id'];
            $exp_view = (isset($_GET['exp_view']) ? $_GET['exp_view'] : 0);
            $active_view = (isset($_GET['active_view']) ? $_GET['active_view'] : 0);
            $verify_view = (isset($_GET['verify_view']) ? $_GET['verify_view'] : 0);
            $edit_as_new = (isset($_GET['edit_as_new']) ? $_GET['edit_as_new'] : null);
            if (isset($_POST['hdnImage1']))
                $img = $_POST['hdnImage1'];
            if (isset($_POST['hdnImage2']))
                $img2 = $_POST['hdnImage2'];
            if ($manage_mode == 2 && !isset($_GET['verify_view']))
                $verify_view = 1;
            $url_str = $main_folder . "StaffLicences?a=1" . ($manage_mode ? "&manage_mode=$manage_mode" : "") . ($lookup_id ? "&lookup_id=$lookup_id" : "") . ($state_id ? "&state_id=$state_id" : "") . ($exp_view ? "&exp_view=$exp_view" : "") . ($active_view ? "&active_view=$active_view" : "") . ($verify_view ? "&verify_view=$verify_view" : "") . ($licence_search ? "&licence_search=$licence_search" : "");
            $hidden_str = ($manage_mode ? "<input type=\"hidden\" name=\"manage_mode\" value=\"$manage_mode\" />" : "") . ($state_id ? "<input type=\"hidden\" name=\"state_id\" value=\"$state_id\" />" : "") . ($exp_view ? "<input type=\"hidden\" name=\"exp_view\" value=\"$exp_view\" />" : "") . ($active_view ? "<input type=\"hidden\" name=\"active_view\" value=\"$active_view\" />" : "") . ($verify_view ? "<input type=\"hidden\" name=\"verify_view\" value=\"$verify_view\" />" : "") . ($licence_search ? "<input type=\"hidden\" name=\"licence_search\" value=\"$licence_search\" />" : "");

            $flder = $this->f3->get('download_folder') . "licences/";

            $del_photo = (isset($_POST['del_photo']) ? $_POST['del_photo'] : null);

            $show_inactive = (isset($_GET['show_inactive']) ? $_GET['show_inactive'] : 0);

            if ($del_photo) {

                $parts = explode("_", $del_photo);
                $del = $flder . $parts[0] . "/licence" . ($parts[1] == 2 ? "2" : "") . ".jpg";
                if (file_exists($del)) {
                    unlink($del);
                    $str .= $this->message("Photo Deleted...", 2000);
                    if ($parts[1] == 1) {
                        $test = $flder . $parts[0] . "/licence2.jpg";
                        $rename_to = $flder . $parts[0] . "/licence.jpg";
                        if (file_exists($test))
                            rename($test, $rename_to);
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

        </style>
        <script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
        <script type="text/javascript">

        var canvas, ctx, pfix_in

        oFReader = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
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

        /*function loadImageFile(pfix=1) {
        pfix_in = pfix
        file_uploader = "fileToUpload" + pfix
        if (document.getElementById(file_uploader).files.length === 0) { return; }
        var oFile = document.getElementById(file_uploader).files[0];
        if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }
        document.getElementById("hdnFileName" + pfix).value = document.getElementById(file_uploader).value
        canvas = "myCanvas" + pfix
        oFReader.readAsDataURL(oFile);
        //setTimeout(submit_form, 1000);
        //document.frmImage.submit();
        }*/
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
        </script>
    ';
            if ($manage_mode) {
                $header[0] = "Expiration";
                $header[1] = "Is Active";
                $header[2] = "Is Verified";
                $view[0] = "exp";
                $view[1] = "active";
                $view[2] = "verify";
                $show_text[0][0] = "Current Only";
                $show_text[0][1] = "Expired Only";
                $show_text[0][2] = "Both";
                $show_text[1][0] = "Active Only";
                $show_text[1][1] = "Inactive Only";
                $show_text[1][2] = "Both";
                $show_text[2][0] = "Verified Only";
                $show_text[2][1] = "Unverified Only";
                $show_text[2][2] = "Both";
                for ($y = 0; $y <= 2; $y++) {
                    $str .= '<div class="tab_heading">' . $header[$y] . ' &gt;&gt;</div>';
                    $use_view = ($y == 0 ? $exp_view : ($y == 1 ? $active_view : $verify_view));
                    for ($x = 0; $x <= 2; $x++) {
                        $str .= '<div class="tab_item' . ($x == $use_view ? ' tab_selected' : '') . '"><a class="tab_a" href="' . $url_str . '&' . $view[$y] . '_view=' . $x . '">' . $show_text[$y][$x] . '</a></div>';
                    }
                }
                $str .= '<div class="cl"></div><br />
        <div class="help_message"><b>Please Note: </b> All inactive staff are now hidden. A button to show inactive staff will be added by the 24th of July.</div><br />';
            }
            if ($lookup_id) {
                $sql = "select users.employee_id, users.name, users.surname, states.item_name, users.state from users
                left join states on states.id = users.state
                where users.id = $lookup_id";
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $employee_id = $myrow['employee_id'];
                    $name = $myrow['name'];
                    $surname = $myrow['surname'];
                    $state = $myrow['item_name'];
                    if (!$state_id)
                        $state_id = $myrow['state'];
                }
            }
            if ($verify_id || $unverify_id) {
                if ($verify_id) {
                    $vby = $_SESSION['user_id'];
                    $pre = "V";
                    $undo = "<br /><br /><a class=\"list_a\" href=\"$url_str" . "&unverify_id=$verify_id\">Undo...</a>";
                } else {
                    $vby = "0";
                    $verify_id = $unverify_id;
                    $pre = "Unv";
                    $undo = "<br /><br /><a class=\"list_a\" href=\"$url_str" . "&verify_id=$verify_id\">Reverify...</a>";
                }
                $sql = "update licences set verified_by = " . $vby . " where id = $verify_id";
                $this->dbi->query($sql);
                $str .= "<h3>Licence $pre" . "erified.</h3>$undo<br /><br />";
                $str .= "<a class=\"list_a\" href=\"$url_str\">Back to Licence " . ($manage_mode == 1 ? "Management" : "Verification") . "...</a>";
            } else if ($deactivate) {
                if ($deactivate == 1) {
                    $sby = "1";
                    $pre = "Deactivated";
                } else {
                    $sby = "0";
                    $pre = "Activated";
                }
                $sql = "update licences set deactivated = " . $sby . " where id = $deactivateid";
                $this->dbi->query($sql);
                $str .= "<h3>Licence $pre</h3><br /><br />";
                $str .= "<a class=\"list_a\" href=\"$url_str\">Back to Licence " . ($manage_mode == 1 ? "Management" : "Verification") . "...</a>";
            } else {
                if ($licence_search) {
                    $search = $licence_search;
                    $search = "
            and (users.name LIKE '%$search%'
            or users.surname LIKE '%$search%'
            or CONCAT(users.name, ' ', users.surname) LIKE '%$search%'
            or licences.licence_number LIKE '%$search%'
            or states.item_name LIKE '$search'
            )
            ";
                }
                $show_edit = "";
                $del_string = "'<a id=\"', licences.id, '_1\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_1\')\">x</a>'";
                $del_string2 = "'<a id=\"', licences.id, '_2\" class=\"op_button\" href=\"JavaScript:del_photo(\'', licences.id, '_2\')\">x</a>'";
                if ($manage_mode) {
                    $additional_fields = ", CONCAT('<a href=\"StaffLicences?manage_mode=1&active_view=2&verify_view=2&exp_view=2&lookup_id=', users.id, '\">', users.name, ' ', users.surname, '</a>'"
                            . ($show_inactive ? ", '<br />', user_status.item_name" : "") . ") as `Licence Holder`, users.phone as `Phone`";
                } else {
                    $additional_fields = "";
                    $show_edit .= ", if(licences.verified_by, CONCAT('<a class=\"list_a\" href=\"{$main_folder}StaffLicences?edit_as_new=', licences.id , '\">Renew Date</a>'), 'Edit') as `*`";
                    $del_string = "if(licences.verified_by, '', CONCAT($del_string))";
                    $del_string2 = "if(licences.verified_by, '', CONCAT($del_string2))";
                }
                if ($hr_user && $manage_mode) {
                    $show_edit = ",
                        if(licences.deactivated, CONCAT('<a class=\"list_a\" href=\"{$main_folder}StaffLicences?deactivate=2&deactivateid=', licences.id, '&manage_mode=$manage_mode&lookup_id=$lookup_id\">Make Active</a>'),
                        CONCAT('<a class=\"list_a\" href=\"$url_str&deactivate=1&deactivateid=', licences.id, '\">Deactivate</a>')) as `Activation`,
                        if(licences.verified_by, CONCAT('<a class=\"list_a\" href=\"{$main_folder}StaffLicences?lookup_id=$lookup_id&unverify_id=', licences.id, '&manage_mode=$manage_mode&lookup_id=$lookup_id\">Unverify</a>'),
                        CONCAT('<a class=\"list_a\" href=\"{$main_folder}StaffLicences?lookup_id=$lookup_id&verify_id=', licences.id, '&manage_mode=$manage_mode&lookup_id=$lookup_id\">Verify</a>')) as `Verification`";
                }
                if ($hr_user && $manage_mode == 1) {
                    $show_edit .= ",'Edit' as `*`, 'Delete' as `!`";
                }
                if ($hr_user && $manage_mode == 2) {
                    $show_edit .= ", CONCAT('<a target=\"_blank\" class=\"list_a\" href=\"StaffLicences?manage_mode=1&active_view=2&verify_view=2&exp_view=2&lookup_id=',users.id,'\">Edit</a>') as `Edit`";
                }

                if ($manage_mode) {
                    $filter_string .= ($exp_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.expiry_date ' . ($exp_view == 1 ? '<' : '>=') . ' now() ' : '');
                    $filter_string .= ($active_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.deactivated = ' . ($active_view == 1 ? '1' : '0') : '');
                    $filter_string .= ($verify_view != 2 ? ($filter_string ? ' and ' : ' where ') . ' licences.verified_by ' . ($verify_view == 1 ? '=' : '!=') . '0' : '');
                }
                if ($lookup_id)
                    $filter_string .= ($filter_string ? ' and ' : ' where ') . " licences.user_id = $lookup_id and deactivated = 0";
                if (!$show_inactive)
                    $filter_string .= ($filter_string ? ' and ' : ' where ') . " user_status.item_name = 'ACTIVE'";

                if ($licence_search || $lookup_id || $manage_mode != 1) {
                    $this->list_obj->sql = "
                select licences.id as idin $additional_fields, licence_types.item_name as `Licence Type`, licences.licence_number as `Licence Number`, licences.licence_class as `Licence Class`, licences.expiry_date as `Expiry Date`, 
                concat('<div style=\"color: ',
                if(DATEDIFF(licences.expiry_date, now()) <= 0,
                    CONCAT('red\">Expired ', if(DATEDIFF(licences.expiry_date, now()) = 0,
                    CONCAT('Today!'),
                    CONCAT(ABS(DATEDIFF(licences.expiry_date, now())), ' Days Ago'))),
                    if(DATEDIFF(licences.expiry_date, now()) <= 28,
                    CONCAT('orange\">', DATEDIFF(licences.expiry_date, now()), ' Days Until Expiry'),
                    'green\">OK')), '</div>') as `Expiry Days`,
                states.item_name as `State Issued`, CONCAT(users2.name, ' ', users2.surname) as `Verified By`
                " . ($active_view == 2 ? ", if(licences.deactivated, 'No', 'Yes') as `Active`" : "") . "
                $show_edit

                , CONCAT('<a target=\"_blank\" href=\"ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence.jpg\"><div class=\"img_container\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_1\');\" style=\"width: 120px;\" src=\"Image?no_crypt=1&i=licences/', licences.id, '/licence.jpg\"></a><div class=\"topright\">', $del_string, '</div></div>') as `Photo 1`
                
                , CONCAT('<a target=\"_blank\" href=\"ShowImage?re_encrypt=1&no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"><div class=\"img_container\"><img onError=\"this.style.display=\'none\';hide_del(\'', licences.id, '_2\');\" style=\"width: 120px;\" src=\"Image?no_crypt=1&i=licences/', licences.id, '/licence2.jpg\"></a><div class=\"topright\">', $del_string2, '</div></div>') as `Photo 2`
                
                FROM licences
                left join users2 on users2.id = licences.verified_by
                left join licence_types on licence_types.id = licences.licence_type_id
                left join users on users.id = licences.user_id
                left join states on states.id = licences.state_id
                left join user_status on user_status.id = users.user_status_id 
                $filter_string
                $search
                order by users.user_status_id, DATEDIFF(licences.expiry_date, now())
            ";
                }
                //$str .= "<textarea>{$this->list_obj->sql}</textarea>";
                if ($manage_mode == 1 || !$manage_mode) {
                    if ($hr_user && $lookup_id != $_SESSION['user_id']) {
                        $this->editor_obj->custom_field = "verified_by";
                        $this->editor_obj->custom_value = $_SESSION['user_id'];
                    }
                    $this->editor_obj->xtra_id_name = "user_id";
                    $this->editor_obj->xtra_id = $lookup_id;
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
                }';
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
                </script>
            ';
                    $this->editor_obj->form_attributes = array(
                        array("selLicenceType", "txtLicenceNumber", "txtLicenceClass", "calExpiryDate", "selState"),
                        array("Licence Type", "Licence/Certificate Number", "Licence Class (e.g. 1AC)", "Expiry Date", "State Issued"),
                        array("licence_type_id", "licence_number", "licence_class", "expiry_date", "state_id"),
                        array($this->get_simple_lookup('licence_types'), "", "", "", $this->get_simple_lookup('states')),
                        array($style . ' onChange="config_questions()"', $style, $style, $style, $style),
                        array("c", "c", "n", "c", "c")
                    );
                    $this->editor_obj->hide_duplicate = 1;
                    $this->editor_obj->button_attributes = array(
                        array("Add New", "Save", "Reset"),
                        array("cmdAdd", "cmdSave", "cmdReset"),
                        array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "window.location=location.protocol + '//' + location.host + location.pathname" . ($manage_mode ? " + '?manage_mode=$manage_mode'" : "")),
                        array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
                    );
                    $state_lookup_filter = " where is_current = 1 ";
                    if ($state_id && $state_id != "ALL") {
                        $state_lookup_filter .= " and states.id = $state_id ";
                        $state_xtra = ", staff_licence_lookups.item_name";
                    } else {
                        $state_xtra = ", CONCAT(states.item_name, ' - ', staff_licence_lookups.item_name) as `item_name`";
                    }
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
                    <div class="fl med_textbox"><nobr>ttxtLicenceNumber</nobr><br />txtLicenceNumber</div>
                    <div class="fl med_textbox" id="licence_class"><nobr>ttxtLicenceClass</nobr><br />txtLicenceClass</div>
                    <div class="fl small_textbox"><nobr>tcalExpiryDate</nobr><br />calExpiryDate</div>
                    <div class="fl small_textbox"><nobr>tselState</nobr><br />selState</div>
                    <div class="fl" style="padding-left: 10px;"><nobr>Photo 1*</nobr><br />
                    <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload1" id="fileToUpload1" onchange="loadImageFile(\'1\')"><br /></br><canvas id="myCanvas1" style="max-width: 200px; height: 0px;"></canvas>
                    ' . (file_exists($img_file1) ? '<a target="_blank" href="Image?i=' . urlencode($this->encrypt($show_img1)) . '"><img style="max-width: 200px" src="Image?i=' . urlencode($this->encrypt($show_img1)) . '" /></a><br />' : '') . '
                    </div>
                    <div class="fl" style="padding-left: 10px;"><nobr>Photo 2 (Optional) <a title="If Applicable, Add a Photo of the Back of Your Licence." href="#">?</a></nobr><br />
                    <input type="file" style="padding: 0; margin: 0;" class="upload" name="fileToUpload2" id="fileToUpload2" onchange="loadImageFile(\'2\')"><br /></br><canvas id="myCanvas2" style="max-width: 200px; height: 0px;"></canvas>
                    ' . (file_exists($img_file2) ? '<a target="_blank" href="Image?i=' . urlencode($this->encrypt($show_img2)) . '"><img style="max-width: 200px" src="Image?i=' . urlencode($this->encrypt($show_img2)) . '" /></a><br />' : '') . '
                    </div>
                    <div class="cl"></div>';
                    $this->editor_obj->form_template .= '
                    <div class="cl"></div>' . $this->editor_obj->button_list();
                    if (!$edit_id && $manage_mode && !$lookup_id)
                        $is_visible = 'style="visibility: hidden; height: 0px"';
                    $this->editor_obj->editor_template = '
                        <div class="fl" ' . $is_visible . '>
                        ' . ($show_warning ? '<div class="message">Please add your security licence<br />and other relevant licences/certificates before proceeding...</div>' : '') . '
                        <div class="form-wrapper">
                        <div class="form-header">' . ($lookup_id == $_SESSION['user_id'] ? "My Licences" : ($employee_id ? "Licences for $employee_id $name $surname" : "Licence Management")) . '</div>
                        <div class="form-content">
                        editor_form
                        </div>
                        </div>
                        </div>
                        <div class="cl"></div><br />' .
                            ($manage_mode ? '</form><form method="get" action="' . $main_folder . 'StaffLicences">' . $hidden_str . '<input maxlength="60" name="licence_search" id="search" type="text" placeholder="Enter staff member\'s name..." class="search_box" value="' . $licence_search . '" /><input type="submit" value="Search" class="search_button" /><div class="cl"></div><br /></form>' : '');
                    $this->editor_obj->editor_template .= 'editor_list               
                        <div class="cl"></div>';
                    $tmp = $this->editor_obj->draw_data_editor($this->list_obj);
                    if ($action == "add_record" && !$edit_id)
                        $edit_id = $this->editor_obj->last_insert_id;
                    if ($edit_id) {
                        $iframe_item = '';
                        $tmp = str_replace("iframe_item", $iframe_item, $tmp);
                    } else {
                        $tmp = str_replace("iframe_item", "", $tmp);
                    }
                    $str .= $tmp;
                } else {
                    $str .= $this->list_obj->draw_list();
                }
                if ($lookup_id && $lookup_id != $_SESSION['user_id'])
                    $str .= " <p><a class=\"list_a\" href=\"{$main_folder}StaffLicences?manage_mode=$manage_mode\">Back to Licence " . ($manage_mode == 1 ? "Management" : "Verification") . "...</a></p>";
                if ($action == "add_record") {
                    $save_id = $this->editor_obj->last_insert_id;
                    $sql = "select licence_number, expiry_date from licences where id = $save_id";
                    $result = $this->dbi->query($sql);
                    if ($myrow = $result->fetch_assoc()) {
                        $licence_number = $myrow['licence_number'];
                        $expiry_date = $myrow['expiry_date'];
                        $sql = "update licences set deactivated = 1 where user_id = $lookup_id and deactivated = 0 and licence_number = '$licence_number' and expiry_date < '$expiry_date'";
                        $result = $this->dbi->query($sql);
                    }
                } else if ($action == "save_record") {
                    $save_id = $this->editor_obj->idin;
                    if ($lookup_id == $_SESSION['user_id']) {
                        $sql = "update licences set verified_by = 0 where id = $save_id";
                        $this->dbi->query($sql);
                    }
                } else if ($action == "delete_record") {
                    
                }
                if ($save_id) {
                    if ($img || $img2) {
                        $folder = "$flder$save_id";
                        if (!file_exists($folder)) {
                            mkdir($folder);
                            chmod($folder, 0755);
                        }
                    }
                    if ($img) {
                        $img = str_replace(' ', '+', $img);
                        $img = substr($img, strpos($img, ",") + 1);
                        $data = base64_decode($img);
                        if ($norename) {
                            $img_name = basename($_POST['hdnFileName1']);
                            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
                        } else {
                            $img_name = "licence.jpg";
                        }
                        $file1 = "$folder/$img_name";
                        $success = file_put_contents($file1, $data);
                    }
                    if ($img2) {
                        $img2 = str_replace(' ', '+', $img2);
                        $img2 = substr($img2, strpos($img2, ",") + 1);
                        $data = base64_decode($img2);
                        if ($norename) {
                            $img_name = basename($_POST['hdnFileName2']);
                            $img_name = str_replace("C:\\fakepath\\", "", $img_name);
                        } else {
                            $img_name = "licence2.jpg";
                        }
                        $file2 = "$folder/$img_name";
                        $success = file_put_contents($file2, $data);
                    }
                    $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);

                    if ($rotate1) {
                        $rotate1 = (isset($_POST['rotate1']) ? $_POST['rotate1'] : null);
                        if ($rotate1) {
                            $degrees = (strtoupper($rotate1) == 'LEFT' ? 270 : ($rotate1 == 'FLIP' ? 180 : 90));
                            $source = imagecreatefromjpeg($file1);
                            $rotate1 = imagerotate($source, $degrees, 0);
                            imagejpeg($rotate1, $file1);
                            imagedestroy($source);
                            imagedestroy($rotate1);
                        }
                    }
                    $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);

                    if ($rotate2) {
                        $rotate2 = (isset($_POST['rotate2']) ? $_POST['rotate2'] : null);
                        if ($rotate2) {
                            $degrees = (strtoupper($rotate2) == 'LEFT' ? 270 : ($rotate2 == 'FLIP' ? 180 : 90));
                            $source = imagecreatefromjpeg($file2);
                            $rotate2 = imagerotate($source, $degrees, 0);
                            imagejpeg($rotate2, $file2);
                            imagedestroy($source);
                            imagedestroy($rotate2);
                        }
                    }
                }


                if (file_exists($show_img)) {
                    
                }
                if ($action == 'add_record') {
                    $str .= "<script>edit_record($save_id)</script>";
                }
            }
            $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '">';
            if ($edit_as_new) {
                $sql = "select licence_number, expiry_date, state_id, licence_class, licence_type_id from licences where id = $edit_as_new";
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $licence_number = $myrow['licence_number'];
                    $expiry_date = $myrow['expiry_date'];
                    $state_id = $myrow['state_id'];
                    $licence_class = $myrow['licence_class'];
                    $licence_type_id = $myrow['licence_type_id'];
                    $str .= '<script>
            //array("selLicenceType", "txtLicenceNumber", "txtLicenceClass", "calExpiryDate", "selState"),
            document.getElementById("txtLicenceNumber").value = "' . $licence_number . '";
            document.getElementById("txtLicenceClass").value = "' . $licence_class . '";
            document.getElementById("calExpiryDate").focus();
            document.getElementById("selState").value = "' . $state_id . '";
            document.getElementById("selLicenceType").value = "' . $licence_type_id . '";
            
            </script>';
                }
            }


            return $str;
        }

        function LeaveRequests() {
            $main_folder = $this->f3->get('main_folder');
            $is_accounts = (array_search(422, $_SESSION['lids']) !== false || array_search(461, $_SESSION['lids']) !== false || array_search(113, $_SESSION['lids']) !== false ? 1 : 0);
            $action = (isset($_POST['hdnAction']) ? $_POST['hdnAction'] : null);
            $process_mode = (isset($_GET['process_mode']) ? ($is_accounts ? $_GET['process_mode'] : 1) : null);
            $process_id = (isset($_GET['process_id']) ? $_GET['process_id'] : null);
            $lookup_id = $_SESSION['user_id'];
            $pfix = ($process_mode == 1 ? 'manager' : 'payroll');
            $pfix_rev = ($process_mode == 1 ? 'payroll' : 'manager');
            $show_all = (isset($_GET['show_all']) ? $_GET['show_all'] : null);

            if ($process_id) {
                $sql = "select $pfix" . "_reason, $pfix" . "_status_id from leave_requests where id = $process_id;";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $reason = $myrow[$pfix . '_reason'];
                        $status_id = $myrow[$pfix . '_status_id'];
                    }
                }
            }

            $hdnChangeStatus = (isset($_POST['hdnChangeStatus']) ? substr($_POST['hdnChangeStatus'], 0, 1) : null);
            $txtComment = (isset($_POST['txtComment']) ? $_POST['txtComment'] : null);

            if ($hdnChangeStatus) {
                $status_change['P'] = 10;
                $status_change['A'] = 20;
                $status_change['C'] = 30;
                $status_change['S'] = $pfix . "_status_id";

                $sql = "update leave_requests set $pfix" . "_status_id = " . $status_change[$hdnChangeStatus] . ", $pfix" . "_reason = '$txtComment' where id = $process_id;";
                if ($status_change[$hdnChangeStatus] == 30 || ($status_id == 30 && $status_change[$hdnChangeStatus] == 10)) {
                    $sql .= "update leave_requests set $pfix_rev" . "_status_id = " . $status_change[$hdnChangeStatus] . " where id = $process_id;";
                }
                if ($status_id == 30 && $status_change[$hdnChangeStatus] == 20) {
                    $sql .= "update leave_requests set $pfix_rev" . "_status_id = 10 where id = $process_id;";
                }
                //echo $sql;
                $result = $this->dbi->multi_query($sql);
                $str .= $this->redirect("LeaveRequests?process_mode=$process_mode&process_id=$process_id");
            }
            $manager_id = 0;
            if (!$process_mode) {
                $sql = "select users.id, CONCAT(users.name, ' ', users.surname) as `manager`, users2.phone as `phone`, users.email as `Email` from users
                left join associations on associations.parent_user_id = users.id 
                left join users2 on users2.id = associations.child_user_id
                where associations.association_type_id = (select id from association_types where name = 'manager_staff') and associations.child_user_id = " . $_SESSION['user_id'];
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $manager_id = $myrow['id'];
                        $phone = $myrow['phone'];
                        $manager_name = $myrow['manager'];
                    }
                }
                if (!$manager_id)
                    $str .= '<h3>Please select your manager before proceeding.</h3>';
                $str .= $this->MyAccount(1);
                $str .= "<br />";
            }

            /*    CONCAT('<nobr>1. ', date_format(leave_requests.date_applied, '%d-%b-%Y'), '</nobr>', if(leave_requests.approval_date != '0000-00-00 00:00:00', CONCAT('<br /><nobr>2. ', date_format(leave_requests.approval_date, '%d-%b-%Y'), '</nobr>'), ''), if(leave_requests.payroll_approval_date != '0000-00-00 00:00:00', CONCAT('<br /><nobr>3. ', date_format(leave_requests.payroll_approval_date, '%d-%b-%Y'), ''), '')) as `1. Date Applied<br />2. Mgr Approval Date<br />3. Acc/HR Approval Date` */

            $this->list_obj->form_nav = 1;
            $this->list_obj->num_per_page = 30;
            $this->list_obj->nav_count = 78;
            $this->list_obj->sql = "select leave_requests.id as idin " . ($process_mode ? ", CONCAT('<a href=\"mailto:', users.email, '\">', users.name, ' ', users.surname, '</a>') as `Applicant`" : "") . ($process_mode != 1 ? ", CONCAT('<a href=\"mailto:', users2.email, '\">', users2.name, ' ', users2.surname, '</a>') as `Manager`" : "") . "
        
        , if(leave_status.id = 30 || leave_status2.id = 30, CONCAT('<span style=\"color:red;\">CANCELLED</span>'), CONCAT('<table border=\"0\"><tr><td><nobr><span style=\"color:', leave_status.colour, ';\">Manager<br />', leave_status.item_name, '</span></nobr></td><td><nobr><span style=\"color:', leave_status2.colour, ';\">Payroll/HR<br />', leave_status2.item_name, '</span></nobr></td></tr></table>')) as `Status`
        
        , CONCAT('<nobr>', leave_types.item_name, '</nobr>') as `Leave Type`, CONCAT('<nobr>', date_format(leave_requests.start_date, '%d-%b-%Y'), '</nobr>') as `Starts`, CONCAT('<nobr>', date_format(leave_requests.finish_date, '%d-%b-%Y'), '</nobr>') as `Finishes`, leave_requests.contact_details as `Contact`, leave_requests.further_comments as `Staff Comment`, leave_requests.manager_reason as `Manager Comment`, leave_requests.payroll_reason as `Payroll/HR Comment`


        " . (!$process_id ? (!$process_mode ? ", if(leave_status.id = 10, 'Edit', '&nbsp;') as `*`" : ", CONCAT('<a class=\"list_a\" href=\"LeaveRequests?process_mode=$process_mode&process_id=', leave_requests.id , '\">Process') as `Process`") : "") . "
        
        from leave_requests
        left join users on users.id = requester_id
        left join users2 on users2.id = manager_id
        left join leave_types on leave_types.id = leave_requests.leave_type_id
        left join leave_status on leave_status.id = leave_requests.manager_status_id
        left join leave_status2 on leave_status2.id = leave_requests.payroll_status_id
        where 1
        " . (!$process_id ? ($process_mode == 1 ? " and leave_requests.manager_id = $lookup_id" : ($process_mode == 2 ? "" : " and users.id = $lookup_id")) : " and leave_requests.id = $process_id ") .
                    ($show_all || $process_id ? "" : " and (leave_requests.manager_status_id = 10 or leave_requests.payroll_status_id = 10)")
                    . " ORDER BY leave_requests.$pfix" . "_status_id, leave_requests.$pfix_rev" . "_status_id, leave_requests.start_date DESC;";

            //`echo "<textarea>{$this->list_obj->sql}</textarea>";

            $this->editor_obj->custom_field = "requester_id";
            $this->editor_obj->custom_value = $lookup_id;

            if ($process_mode) {
                $str .= '<h3 class="fl">' . ($process_mode == 1 ? "Manager" : "Payroll/HR") . ' Leave Request Management</div><div class="fr"><a class="list_a" href="LeaveRequests?show_all=' . ($show_all ? 0 : 1) . ($process_mode ? "&process_mode=$process_mode" : "") . '">Show ' . ($show_all ? "Pending Only" : "All") . '</a></div><div class="cl"></div>' . $this->list_obj->draw_list();
                if ($process_id) {
                    $str .= '<style>
            .appraisal_btn {
            font-size: 11pt !important;
            font-weight: bold !important;
            text-align: center;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
            border-radius: 8px 8px 8px 8px;
            -moz-border-radius: 8px 8px 8px 8px;
            -webkit-border-radius: 8px 8px 8px 8px;
            border: 1px solid #DDDDDD !important;
            color: #333333;
            background-color: #5B9BC9;
            white-space: nowrap;
            }
            .pending {    background-color: #FD6A02 !important;    color: white !important;  }
            .approve {    background-color: #009900 !important;    color: white !important;  }
            .deny {    background-color: #990000 !important;    color: white !important;  }
            .cancel {    background-color: #330000 !important;    color: white !important;  }
            .pending:hover {    background-color: #F9A602 !important;  }
            .approve:hover {    background-color: #00AA00 !important;  }
            .deny:hover {    background-color: #BB0000 !important;  }
            .cancel:hover {    background-color: #660000 !important;  }
        .select_a {
            font-size: 20px;
            display: block;
            padding: 20px;
            margin-bottom: 10px;
            width: 50%;
            background-color: #DDDDDD;
            border-top: 1px solid #CCCCCC;
            border-left: 1px solid #CCCCCC;
            border-bottom: 1px solid #CCCCCC;
            border-right: 1px solid #CCCCCC;
            }
            .select_a:hover {
            text-decoration: none !important;
            background-color: #DDEEEE;
            /* border: 1px solid #DDDDDD; */
            }
        </style>
                <script language="JavaScript">
                function change_status(status_in) {
                    var confirmation;
                    confirmation = (status_in == "Save" ? "Are you sure about saving the comments?" : "Are you sure about changing the status to "+status_in+"?");
                    if (confirm(confirmation)) {
                    document.getElementById("hdnChangeStatus").value = status_in
                    document.frmEdit.submit()
                    }
                }
                </script>
                <input type="hidden" name="hdnChangeStatus" id="hdnChangeStatus" />';

                    $str .= '<h3>Comment (Optional)</h3><textarea name="txtComment" style="height: 100px; width: 100%;">' . $reason . '</textarea>';
                    if ($status_id == 10 || $status_id == 30)
                        $str .= '<input class="appraisal_btn approve" onClick="change_status(\'Approved\')" type="button" value="Approve Request">';
                    if ($status_id == 20 || $status_id == 30)
                        $str .= '<input class="appraisal_btn pending" onClick="change_status(\'Pending\')" type="button" value="Change Request to Pending">';

                    if ($status_id != 30)
                        $str .= '<input class="appraisal_btn cancel" onClick="change_status(\'Cancelled\')" type="button" value="Cancel Request">';
                    $str .= '<input class="appraisal_btn" onClick="change_status(\'Save\')" type="button" value="Save Comment">';
                    $str .= '<br /><br /><a class="list_a" href="LeaveRequests?process_mode=' . $process_mode . '">&lt;&lt; Back to Leave Request Management</a>';
                }
            } else if ($manager_id) {
                $this->editor_obj->table = "leave_requests";
                $style = 'class="full_width"';
                $style_notes = 'style="height: 70px; width: 100%;" '; //class="large_textbox"
                $this->editor_obj->form_attributes = array(
                    array("selLeaveType", "calStartDate", "calFinishDate", "txtContactDetails", "txaFurtherComments"),
                    array("Type of Leave", "Start Date", "Finish Date", "Phone Number Whilst on Leave", "Comments"),
                    array("leave_type_id", "start_date", "finish_date", "contact_details", "further_comments"),
                    array($this->get_simple_lookup('leave_types'), "", "", "", ""),
                    array($style, $style, $style, $style . "value=\"$phone\"", $style_notes),
                    array("c", "c", "c", "c", "n")
                );
                $this->editor_obj->button_attributes = array(
                    array("Add New", "Save", "Reset"),
                    array("cmdAdd", "cmdSave", "cmdReset"),
                    array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                    array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
                );

                // $str .= '<h3 class="fl">' . ($process_mode == 1 ? "Manager" : "Payroll/HR") . ' Leave Request Management</div><div class="fr"></div><div class="cl"></div>' . $this->list_obj->draw_list();

                $this->editor_obj->xtra_validation = '
            date1 = document.getElementById(\'calStartDate\').value
            date2 = document.getElementById(\'calFinishDate\').value
            if(date1 && date2) {
            dt1 = moment(date1, "DD-MMM-YYYY");
            dt2 = moment(date2, "DD-MMM-YYYY");
            if(moment(dt1).isAfter(moment(dt2)) === true) {
                err++;
                error_msg += err + ". Please select a finish date that is the same or after the start date.\n";
            }
            }
        ';

                $this->editor_obj->form_template = '
        <script type="text/javascript" src="' . $this->f3->get('js_folder') . 'moment.js"></script>
        <div class="form-wrapper" style="">
            <div class="form-header" style="height: 30px;">
            <div class="fl">Add/Edit Leave Requests</div>
            <div class="fr" style="margin-top: -3px; margin-right: 5px;"><a class="list_a" href="MyLeaveRequests?show_all=' . ($show_all ? 0 : 1) . ($process_mode ? "&process_mode=$process_mode" : "") . '">Show ' . ($show_all ? "Pending Only" : "All") . '</a></div>
            </div>
            <div class="cl"></div>
            <div class="form-content">
            <div class="fl small_textbox"><nobr>tselLeaveType</nobr><br />selLeaveType</div>
            <div class="fl small_textbox"><nobr>tcalStartDate</nobr><br />calStartDate</div>
            <div class="fl small_textbox"><nobr>tcalFinishDate</nobr><br />calFinishDate</div>
            <div class="fl large_textbox"><nobr>ttxtContactDetails</nobr><br />txtContactDetails</div>
            <div class="cl"></div>
            <div class="fl full_width"><nobr>ttxaFurtherComments</nobr><br />txaFurtherComments</div>
            <div class="cl"></div>
            ' . $this->editor_obj->button_list() . '
            </div>
        </div>';
                $this->editor_obj->editor_template = 'editor_form<div class="cl"></div>editor_list';

                $str .= $this->editor_obj->draw_data_editor($this->list_obj);
                if ($action == "add_record") {
                    $save_id = $this->editor_obj->last_insert_id;
                    $sql = "update leave_requests set date_applied = now(), manager_id = '$manager_id', manager_status_id = 10 where id = $save_id";
                    $this->dbi->query($sql);
                    $str .= "<script>edit_record($save_id)</script>";
                } else if ($action == "save_record") {
                    $save_id = $this->editor_obj->idin;
                }
                if (!isset($_POST['idin'])) {
                    $str .= "<script>document.getElementById('selLeaveType').selectedIndex = 1;</script>";
                }
            }

            return $str;
        }

        function induction($hide_download = 0, $get_result = 0) {
            $main_folder = $this->f3->get('main_folder');
            //https://alliededge.com.au/Compliance?report_id=18&site_id=169
            //".$_SESSION['user_id']."

        $inductionFolder = $this->f3->get('download_folder') . "induction_user_image/";
        //$inductionFolder = $this->f3->get('download_folder') . 'https://alliedge.projectstatus.in/edge/download/"induction_user_image/1267";
        
        $loginUserId = $_SESSION['user_id'];
        
        $inductionFolderPath = "$inductionFolder$loginUserId";
        $fileName = 'user_'.$loginUserId.'.png';
                $folderPath = $inductionFolderPath."/";
                $inductionFile = $folderPath . $fileName;
        
        if(isset($_POST['inductionimage']) && $_POST['inductionimage'] != ""){
        
            $inductionimgBase64 = $_POST['inductionimage'];        
            if(!file_exists($inductionFolderPath)){
            mkdir($inductionFolderPath,0755,true);
            //chmod($folder, 0755);
            }

    //                //save image
    //                $licImg1 = str_replace(' ', '+', $inductionimgBase64);
    //                $licImg1 = substr($licImg1, strpos($licImg1, ",") + 1);
    //                $data = base64_decode($licImg1);
    //                //$img_name = basename($_POST['hdnFileName']);
    //                $img_name = 'licence.jpg';
    //                $file = "$folder/$img_name";
    //                $success = file_put_contents($file, $data);
            
                $image_parts = explode(";base64,", $inductionimgBase64);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];

                $image_base64 = base64_decode($image_parts[1]);
                @unlink($inductionFile);
                file_put_contents($inductionFile, $image_base64);
        }    
        
        $str = "";
        
        
            $str .= "<h1>Allied Induction</h1><hr />";
            $loginUserId = $_SESSION['user_id'];
            $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
            $divisionIdReportArray = array(108 => 120, 2100 => 121, 2102 => 122, 2103 =>123,2104 =>124);
            $divisionNameReportArray = array(108 => 'Security', 2100 => 'Facility', 2102 => 'Pest', 2103 =>'Civil',2104 =>'Traffic');
            //prd($loginUserDivisionsArray);
            $inductionReportStatus = 1;
            $divisionExamStatus = array();
            $userIdCheck[108] = 'uncheck';
            $userIdCheck[2100] = 'uncheck';
            $userIdCheck[2102] = 'uncheck';
            $userIdCheck[2103] = 'uncheck';
            $userIdCheck[2104] = 'uncheck';
            if($loginUserDivisionsArray) {

                foreach ($loginUserDivisionsArray as $key => $divisionId) {

                    if (isset($divisionIdReportArray[$divisionId])) {
                        
                        $divisionExamStatus[$divisionId] = 0;

                        $inductionReportId = $divisionIdReportArray[$divisionId];
                        $inductionReportdivisionName = $divisionNameReportArray[$divisionId];
                        $sql = "select sum(compliance_check_answers.value) as `vals`, compliance_checks.total_out_of as `out_ofs`,  compliance_checks.id from compliance_check_answers
                    inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id
                    where compliance_checks.assessor_id = " . $_SESSION['user_id'] . "
                    and compliance.id = $inductionReportId
                    group by compliance_checks.id
                    ";
                        if($divisionId == 2100){
                        //echo "<br><br><br><br>".$sql;
                        }
                        
                        $test_done = 0;
                        $test_passed = 0;
                        if ($result = $this->dbi->query($sql)) {
                            while ($myrow = $result->fetch_assoc()) {
                                $test_done = 1;
                                $val = $myrow['vals'];
                                $out_of = $myrow['out_ofs'];
                                if ($val == $out_of){
                                    $test_passed = 1;
                                    $divisionExamStatus[$divisionId] = 1;
                                } 
                                $old_report_link = '<a class="list_a" href="' . $main_folder . 'Compliance?report_edit_id='.$myrow["id"].'">Click Here to Complete  Quiz</a>';
                            }
                        }
                        $start_link = '<a class="list_a" href="' . $main_folder . 'Compliance?report_id=' . $inductionReportId . '&site_id=' . $_SESSION['user_id'] . '">Click Here to Start Quiz</a>';
                        
                        // $str .= '<hr style="broder:5px" /><br><br>';
                        if ($key != 0) {
                            $str .= '<h1 style="padding-top:115px">' . ($key + 1) . ". " . $inductionReportdivisionName . ' Division</h1>';
                        } else {
                            $str .= '<h1>' . ($key + 1) . ". " . $inductionReportdivisionName . ' Division</h1>';
                        }
                        if ($test_done) {
                            if ($test_passed) {
                                $str .= "<h3> <i class=\"fas green fa fa-check\" style='color:green' ></i>" . $inductionReportdivisionName . " Induction Completed.</h3>";
                                // . '<p><a class="list_a" href="' . $main_folder . '">Click Here to Proceed to the Edge Dashboard...</a></p>'
                                $res[$divisionId] = "passed";
                                $userIdCheck[$divisionId] = "check";
                            } else {

                                $str .= "<h3><i class=\"fas fa fa-times\" style='color:red'></i> Please try again – you must answer all questions to complete  " . $inductionReportdivisionName . " Induction.</h3><br />$old_report_link";
                                $res[$divisionId] = "failed";
                                $userIdCheck[$divisionId] = "uncheck";
                            }
                        } else {
                            $str .= "<h3>You have not attempted the " . $inductionReportdivisionName . " Induction Quiz.</h3>$start_link";
                            $res[$divisionId] = "incomplete";
                            $userIdCheck[$divisionId] = "uncheck";
                        }

                        if (file_exists($this->f3->get('download_folder') . "compliance/$inductionReportId")) {
                            $str .= '<hr /><p>Please download the Induction Guide and read it carefully before completing the quiz.</p><iframe width="100%" height="180" src="' . $this->f3->get('main_folder') . 'Resources?show_min=1&current_dir=compliance&current_subdir=downloads' . urlencode('/') . $inductionReportId . '" frameborder="0"></iframe>';
                        }else{
                        $str .= '<hr /><p>I didn’t create any power point presentation, therefore no presentation is attached on Edge.</p>'; 
                        }

                        //$str .= (!$hide_download ? '<br /><br /><br /><hr /><p>Please download the Induction Guide and read it carefully before completing the quiz.</p><a class="list_a" href="' . $main_folder . 'DownloadFile?fl=8%2Fdy%2BeXc2ZIzGPFWqbF12FUmE29YOIn5KJWJwNnfeMc8a68HL9BopieHSPOHHSRL3zmjUiXUhF4XmBcn1jgeY83x1%2B8Ry4g5c%2BmItZOpjgA%3D&f=amDkX%2FyWkmakzJqKRr9gO4OLe4VqcCZA4rrrEav2ypiFY26YOx16MDJA7ytsKQl8JXCb%2FBQo1BFcJw1%2BKo7%2Bhvj8O068rbe35b1gaSLMG5Q%3D">Click Here To Download the Induction Guide</a></p><br />' : '');
                    }
                }
                
                
            }else{
                $inductionReportStatus = 0;
            }



            //$loginUserDivisionsStr = implode(',',$loginUserDivisionsArray);
            //prd($loginUserDivisions);
    //        $sql = "select sum(compliance_check_answers.value) as `vals`, compliance_checks.total_out_of as `out_ofs`,  compliance_checks.id from compliance_check_answers
    //                  inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
    //                  inner join compliance on compliance.id = compliance_checks.compliance_id
    //                  where compliance_checks.assessor_id = " . $_SESSION['user_id'] . "
    //                  and compliance.id = 27
    //                  group by compliance_checks.id
    //                  ";
    //        $test_done = 0;
    //        $test_passed = 0;
    //        if ($result = $this->dbi->query($sql)) {
    //            while($myrow = $result->fetch_assoc()) {
    //                $test_done = 1;
    //                $val = $myrow['vals'];
    //                $out_of = $myrow['out_ofs'];
    //                if ($val == $out_of)
    //                    $test_passed = 1;
    //            }
    //        }
    //        if ($test_done) {
    //            if ($test_passed) {
    //                $str .= "<h3>CONGRATULATIONS!! You have completed and passed the Allied Induction Quiz.</h3>" . '<p><a class="list_a" href="' . $main_folder . '">Click Here to Proceed to the Edge Dashboard...</a></p>';
    //                $res = "passed";
    //            } else {
    //                $str .= "<h3>You to answer all of the questions correctly to pass the induction.</h3><h3>Please try the Allied Induction Quiz Again.</h3><br />$start_link<br /><br />";
    //                $res = "failed";
    //            }
    //        } else {
    //            $str .= "<h3>You have not attempted the Induction Quiz.</h3>$start_link";
    //            $res = "incomplete";
    //        }
    //
    //        $str .= '<h3>Site Inductions</a>
    //    <p><a class="list_a" href="' . $main_folder . 'Compliance?report_id=33&site_id=' . $_SESSION['user_id'] . '">Kmart Induction</a></p>
    //    <p><a class="list_a" href="' . $main_folder . 'Compliance?report_id=34&site_id=' . $_SESSION['user_id'] . '">Zara Induction</a></p>
    //    <p><a class="list_a" href="' . $main_folder . 'Compliance?report_id=35&site_id=' . $_SESSION['user_id'] . '">Pickles Auctions Induction</a></p>
    //    <p><a class="list_a" href="' . $main_folder . 'Compliance?report_id=36&site_id=' . $_SESSION['user_id'] . '">Bonnyrigg Plaza Induction</a></p>
    //    ';
            
            foreach($divisionExamStatus as $dvalue){
                if($dvalue == 0 ){
                    $inductionReportStatus = 0; 
                }
            }
                $strCamera = "";
                if($inductionReportStatus){
            

    $strCamera .= '<style>
        video {
    -webkit-transform: scaleX(-1);
    transform: scaleX(-1);
    }
        .overall{ display: block; border:solid 1px #ddd; width:100%; padding:20px; max-width: 845px; margin-left: auto; margin-right: auto; }
        .end_cel{ align-self: flex-end; text-align: right}
        .topdiv{ display: flex; align-items: flex-start; }
        .bottomdiv{ clear:both; margin-top:20px; display: flex; justify-content: space-between; align-items: flex-start; }
        #results{ width:50%;}
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.25/webcam.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css" />
    <div class="" style="margin-left:250px; margin-top: 100px">
        <form method="POST" name="inductionwebform" id="inductionwebform" action="induction" class="overall">
            <div class="topdiv">
                <div class="div">
                    <div id="my_camera" ></div>
                </div>
        <div id="results" style="transform: rotateY(180deg);"></div>
            </div>
                <div class="bottomdiv">       
                    <input type=button value="Take Snapshot" onClick="take_snapshot()">
                    <input type="hidden" name="inductionimage" class="image-tag">
                    <button class="btn btn-success">Submit</button>
                </div>
            
        </form>
    </div>
    <script language="JavaScript">
        //navigator.mediaDevices.getUserMedia
    //    navigator.mediaDevices.getUserMedia  = ( 
    //                       navigator.webkitGetUserMedia ||
    //                       navigator.mozGetUserMedia ||
    //                       navigator.msGetUserMedia);
    //
    //navigator.mediaDevices.getUserMedia ({video: true}, function() {
    //    console.log("webcamera availble");
    //}, function() {
    //    console.log("webcamera not availble");
    //  // webcam is not available
    //});
        Webcam.on( "error", function(err) {
            alert("Please on your camera for catpure image");
        } );
        Webcam.set({
            width:400,
            height: 280,
            image_format: "jpeg",
            jpeg_quality: 100
        });  
        Webcam.attach( "#my_camera" );
    
        function take_snapshot() {
            Webcam.snap( function(data_uri) {
                $(".image-tag").val(data_uri);
                //$("#inductionwebform").submit();
                document.getElementById("results").innerHTML = \'<img src="\'+data_uri+\'"/>\';
            } );
        }
    </script>';


    if(file_exists($inductionFile) || 1){
        $userIdDetail = $this->getUserDetail($_SESSION['user_id']);
        $useridPhoneNumber = $userIdDetail['phone2']?$userIdDetail['phone2']:$userIdDetail['phone'];
        
        // prd($userIdDetail);
    //          $str .= '<div style="margin:100px"> '
    //                  . 'Induction User Image : </br>'
    //                  . '<img style="max-width: 200px" src="'.$main_folder.'edge/downloads/induction_user_image/'.$loginUserId.'/user_'.$loginUserId.'.png" />'
    //                  . '</div>';
            
            $strCamera .= '<div style="width: 100%;overflow: auto;display: inline-block;">
            <table style="width: 1500px;">
                <tr>
                    <td style="background-image: url(app/images_static/front.jpg); background-repeat: no-repeat; background-position: left top; width:750px; height:473px; vertical-align: top; margin-right:20px;display: inline-block;">
                        <table style="margin-top: 40px;margin-left: 208px;">
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 20px; font-weight: bold; color: #333; padding-top:150px;">'.$userIdDetail['name'].'</div>
                                </td>
                                <td>
                                    <div style="width: 218px;height: 218px;background: #fff; transform: rotateY(180deg);">
                                        <img src="'.$main_folder.'edge/downloads/induction_user_image/'.$loginUserId.'/user_'.$loginUserId.'.png" style="height:200px; width: 208;" width="218" height="270" alt="">
                                    </div>
                                </td>
                            </tr>
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 20px; font-weight: bold; color: #333; padding-top: 12px;">'.$loginUserDivisions.'</div>
                                </td>
                                <td>&nbsp;</td>
                            </tr>
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 20px; font-weight: bold; color: #333; padding-top: 32px;">'.$userIdDetail['employee_id'].'</div>
                                </td>
                                <td>&nbsp;</td>
                            </tr>
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 20px; font-weight: bold; color: #333; padding-top: 30px;">'.$useridPhoneNumber.'</div>
                                </td>
                                <td>&nbsp;</td>
                            </tr>
            
            
                    
                        </table>
                    </td>
            
            
                    <td style="background-image: url(app/images_static/back.jpg); background-repeat: no-repeat; background-position: left top; width:750px; height:473px; vertical-align: top; ">
                        <table style="margin-top: 60px;margin-left: 190px;">
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 52px;display: grid; ">
                                    <span><img src="app/images_static/check.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 5px;"></span>
                                    </div>
                                </td>
                                <td>
                                
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 36px;display: grid; padding-left: 84px;">
                                        <span><img src="app/images_static/'.$userIdCheck[2100].'.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 0px;"></span>
                                    </div>
                                </td>
                            </tr>
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    <div style="font-size: 26px;font-weight: bold;color: #333; 	padding-top: 20px;display: none;">
                                        <span><img src="app/images_static/uncheck.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 3px;"></span>
                                    </div>
                                </td>
                                <td>
                                
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 0px;display: grid; padding-left: 84px;">
                                        <span><img src="app/images_static/'.$userIdCheck[108].'.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 0px;"></span>
                                    </div>
                                </td>
                            </tr>
            
            
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    &nbsp;
                                </td>
                                <td>					
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 0;display: grid; padding-left: 84px;">
                                        <span><img src="app/images_static/'.$userIdCheck[2104].'.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 0px;"></span>
                                    </div>
                                </td>
                            </tr>
            
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    &nbsp;
                                </td>
                                <td>					
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 0;display: grid; padding-left: 84px;">
                                    <span><img src="app/images_static/'.$userIdCheck[2103].'.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 14px;"></span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 250px;vertical-align:middle;">
                                    &nbsp;
                                </td>
                                <td>					
                                    <div style="font-size: 26px;font-weight: bold;color: #333;	padding-top: 0;display: grid; padding-left: 84px;">
                                    <span><img src="app/images_static/'.$userIdCheck[2102].'.jpg" width="40" height="40" alt="" style="padding-left: 5px;padding-top: 20px;"></span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            </div>';
            
            
            }



        }
        
        
        
        
        
        if($inductionReportStatus)
        {          
            if(file_exists($inductionFile)){
                $inductionReportStatus = 1; 
            }else{
                $inductionReportStatus = 0; 
            }
        }
        
        $strNew = $strCamera;
        $strNew .= $str;

            return ($get_result ? $inductionReportStatus : $strNew);
        }
        
        
        
        function termCondition(){   
    //        die("hello");
            
            
    //        <section class="container">
    //  <img src="app/images/headerImg.png" style="width:100%;">
    //  <div class="" style="border-top:1px solid #ddd;" >
    //
    //  </div>
    //<p style="margin-top:20px;" >02 March 2020 </p>
    //<p style="margin-top:0px;" >Ms. W. Lovell</p>
    //<p style="margin-top:0px;" >Tomra Sorting Pty Ltd</p>
    //<p style="margin-top:0px;" >Unit 1/20 Anella Avenue</p>
    //<p style="margin-top:0px;" >Castle Hill NSW 2154</p>
    //
    //<p>Dear Wendy,</p>
    //<p><b>RE: Access to the Allied Integrated Management Communication & Management System - The EDGE </b></p>
    //<p>As per your request, Allied is pleased to provide you with the access to the online solution that will definitely assist you in all your operation and safety requests.</p>
            
            $str = '<style>

    body{font-family: Arial, Helvetica, sans-serif; padding: 20px;}

    .container img{max-width: 100%; margin-bottom: 20px;}
    .container h3{margin: 20px auto; font-size: 1.6rem; line-height: 30px; color: #00305f;}
    .container p{line-height: 16px; font-size: 13px; margin-top: 15px; color: #00305f;}
    .col-half { width: 50%; float: left; padding: 10px; min-height: 120px; margin-top: 30px; }
    .col-half p { margin: 0px; }
    </style>
    <body>


    <h3 style="text-align:center; margin-top: 30px;" >Terms and conditions</h3>

    <p>These terms and conditions ("Terms", "Agreement") are an agreement between Allied integrated Management ("Allied integrated Management", "us", "we" or "our") and you ("User", "you" or "your"). This Agreement sets forth the general terms and conditions of your use of the Allied EDGE mobile application and any of its products or services (collectively, "Mobile Application" or "Services").</p>

    <p><b>User content</b></p>
    <p>We do not own any data, information or material ("Content") that you submit in the Mobile Application in the course of using the Service. You shall have sole responsibility for the accuracy, quality, integrity, legality, reliability, appropriateness, and intellectual property ownership or right to use of all submitted Content. We may, but have no obligation to, monitor and review Content in the Mobile Application submitted or created using our Services by you. Unless specifically permitted by you, your use of the Mobile Application does not grant us the license to use, reproduce, adapt, modify, publish or distribute the Content created by you or stored in your user account for commercial, marketing or any similar purpose. But you grant us permission to access, copy, distribute, store, transmit, reformat, display and perform the Content of your user account solely as required for the purpose of providing the Services to you. Without limiting any of those representations or warranties, we have the right, though not the obligation, to, in our own sole discretion, refuse or remove any Content that, in our reasonable opinion, violates any of our policies or is in any way harmful or objectionable.</p>


    <p><b>Backups</b></p>
    <p>We perform regular backups of the Content and will do our best to ensure completeness and accuracy of these backups. In the event of the hardware failure or data loss we will restore backups automatically to minimize the impact and downtime.</p>


    <p><b>Links to other mobile applications</b></p>
    <p>Although this Mobile Application may link to other mobile applications, we are not, directly or indirectly, implying any approval, association, sponsorship, endorsement, or affiliation with any linked mobile application, unless specifically stated herein. Some of the links in the Mobile Application may be "affiliate links". This means if you click on the link and purchase an item, Allied integrated Management will receive an affiliate commission. We are not responsible for examining or evaluating, and we do not warrant the offerings of, any businesses or individuals or the content of their mobile applications. We do not assume any responsibility or liability for the actions, products, services, and content of any other third-parties. You should carefully review the legal statements and other conditions of use of any mobile application which you access through a link from this Mobile Application. Your linking to any other off-site mobile applications is at your own risk.</p>


    <p><b>Prohibited uses</b></p>
    <p>In addition to other terms as set forth in the Agreement, you are prohibited from using the Mobile Application or its Content: (a) for any unlawful purpose; (b) to solicit others to perform or participate in any unlawful acts; (c) to violate any international, federal, provincial or state regulations, rules, laws, or local ordinances; (d) to infringe upon or violate our intellectual property rights or the intellectual property rights of others; (e) to harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate based on gender, sexual orientation, religion, ethnicity, race, age, national origin, or disability; (f) to submit false or misleading information; (g) to upload or transmit viruses or any other type of malicious code that will or may be used in any way that will affect the functionality or operation of the Service or of any related mobile application, other mobile applications, or the Internet; (h) to collect or track the personal information of others; (i) to spam, phish, pharm, pretext, spider, crawl, or scrape; (j) for any obscene or immoral purpose; or (k) to interfere with or circumvent the security features of the Service or any related mobile application, other mobile applications, or the Internet. We reserve the right to terminate your use of the Service or any related mobile application for violating any of the prohibited uses.</p>

    <p><b>Intellectual property rights</b></p>
    <p>This Agreement does not transfer to you any intellectual property owned by Allied integrated Management or third-parties, and all rights, titles, and interests in and to such property will remain (as between the parties) solely with Allied integrated Management. All trademarks, service marks, graphics and logos used in connection with our Mobile Application or Services, are trademarks or registered trademarks of Allied integrated Management or Allied integrated Management licensors. Other trademarks, service marks, graphics and logos used in connection with our Mobile Application or Services may be the trademarks of other third-parties. Your use of our Mobile Application and Services grants you no right or license to reproduce or otherwise use any Allied integrated Management or third-party trademarks.</p>

    <p><b>Limitation of liability</b></p>
    <p>To the fullest extent permitted by applicable law, in no event will Allied integrated Management, its affiliates, officers, directors, employees, agents, suppliers or licensors be liable to any person for (a): any indirect, incidental, special, punitive, cover or consequential damages (including, without limitation, damages for lost profits, revenue, sales, goodwill, use or content, impact on business, business interruption, loss of anticipated savings, loss of business opportunity) however caused, under any theory of liability, including, without limitation, contract, tort, warranty, breach of statutory duty, negligence or otherwise, even if Allied integrated Management has been advised as to the possibility of such damages or could have foreseen such damages. To the maximum extent permitted by applicable law, the aggregate liability of Allied integrated Management and its affiliates, officers, employees, agents, suppliers and licensors, relating to the services will be limited to an amount greater of one dollar or any amounts actually paid in cash by you to Allied integrated Management for the prior one month period prior to the first event or occurrence giving rise to such liability. The limitations and exclusions also apply if this remedy does not fully compensate you for any losses or fails of its essential purpose.</p>


    <p><b>Severability</b></p>
    <p>All rights and restrictions contained in this Agreement may be exercised and shall be applicable and binding only to the extent that they do not violate any applicable laws and are intended to be limited to the extent necessary so that they will not render this Agreement illegal, invalid or unenforceable. If any provision or portion of any provision of this Agreement shall be held to be illegal, invalid or unenforceable by a court of competent jurisdiction, it is the intention of the parties that the remaining provisions or portions thereof shall constitute their agreement with respect to the subject matter hereof, and all such remaining provisions or portions thereof shall remain in full force and effect.</p>

    <p><b>Assignment</b></p>
    <p>You may not assign, resell, sub-license or otherwise transfer or delegate any of your rights or obligations hereunder, in whole or in part, without our prior written consent, which consent shall be at our own sole discretion and without obligation; any such assignment or transfer shall be null and void. We are free to assign any of its rights or obligations hereunder, in whole or in part, to any third-party as part of the sale of all or substantially all of its assets or stock or as part of a merger.</p>

    <p><b>Changes and amendments</b></p>
    <p>We reserve the right to modify this Agreement or its policies relating to the Mobile Application or Services at any time, effective upon posting of an updated version of this Agreement in the Mobile Application. When we do, we will revise the updated date at the bottom of this page. Continued use of the Mobile Application after any such changes shall constitute your consent to such changes.</p>

    <p><b>Acceptance of these terms</b></p>
    <p>You acknowledge that you have read this Agreement and agree to all its terms and conditions. By using the Mobile Application or its Services you agree to be bound by this Agreement. If you do not agree to abide by the terms of this Agreement, you are not authorized to use or access the Mobile Application and its Services.</p>

    <p>Do not hesitate in contacting me should you have any questions or require further information regarding the Terms and Conditions.</p>


    ';
            
                $lookup_id = $_SESSION['user_id'];
                $_POST['idin'] = $_SESSION['user_id'];
                $_REQUEST['idin'] = $_SESSION['user_id'];
                $_GET['idin'] = $_SESSION['user_id'];
                
            $termConditionAccept = $_REQUEST['term_condition_accept'];
                
            $this->list_obj->term_condition_accept = $term_condition_accept;
            
                $this->list_obj->sql = "
            select users.id as idin,jusers.term_condition_accept as `term_condition_accept` from users
        $filter_string
            order by users.id
        ";
            
            
        
                $form_obj = $this->editor_obj;
                $form_obj->hide_confirm = 1;
                $form_obj->table = "users";
                $form_obj->dbi = $this->dbi;
                $style = 'style="width: 200px;"';
                $styleMulti = 'style="width: 500px;height: 150px !important;"';
                $style_med = 'style="width: 500px;"';
                $style_med = 'style="width: 170px;"';
                $style_small = 'style="width: 90px;"';
                $form_obj->xtra_validation = '
            

            //  alert(document.getElementById("chkTermConditionAccept").checked);
            if(!(document.getElementById("chkTermConditionAccept").checked))
            {
                    err++;
                        error_msg += "Please accept term and condition.\\n";
            }
                ';
                
            
            $formAttributeArr[] = array("chkTermConditionAccept", "Accept Term and Condition", "term_condition_accept", "", "", "c", "", "");
            $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
                //print_r($resultV); die;
                $form_obj->form_attributes = $resultV;
                

                /*
                $form_obj->form_attributes = array(
                array("chlDivisions", "selUserType", "txtName", "txtSurname", "txtEmail", "txtPhone", "txtAddress", "txtSuburb", "selState", "txtPostcode", "cmbParentSite", "cmbParentCompany", "txtPassword", "selWorkerClassification", "cmbVisaType"),
                array("Select Optional Extra Divisions Here", ($bd ? "Division" : "Entity Type"), ($bd ? "Company Name" : "Name"), ($bd ? "Name of Rep" : "Surname"), $pfix . "Email", $pfix . "Phone", "Address", "Suburb", "State", "Postcode", "Main Site", "Parent Company", "Set a Password", "Worker Classification", "Visa Type"),
                array("id", "name", "name", "surname", "email", "phone", "address", "suburb", "state", "postcode", "parent_site", "parent_company", "pw", "worker_classification", "visa_type"),
                array($division_sql, $lookup_sql, "", "", "", "", "", "", "select 0 as id, '--- Select ---' as item_name union all select id, item_name from states", "", $site_sql, $parent_company_sql, "", $this->get_lookup('worker_classification'), $this->get_cmb_lookup('visa_type')),
                array("", 'onchange="change_fields()"', $style_med, $style_med, $style . " onChange=\"JavaScript:check_email(document.getElementById('txtEmail').value)\"", $style_med, $style, $style, "", $style_small, $style_med, $style_med, $style, $style, $style),
                array("n", "c", "c", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n", "n"),
                array("", "user_type", "", "", "", "", "", "", "", "", "", "")
                );
                print_r($form_obj->form_attributes); die;
                */
    //             $form_obj->button_attributes = array(
    //            array("Save"),
    //            array("cmdSave"),
    //            array("if(save()) this.form.submit()"),
    //            array("js_function_save","js_function_edit")
    //        );
    //             
                
                $form_obj->button_attributes = array(
                    array(" Accept"),
                    array("cmdSave"),
                    array( "if(save()) this.form.submit()"),
                    array("js_function_save", "js_function_edit")
                );
                
            $form_obj->form_template = '
        <div class="cl"></div>     
        <!--  section 3 -->
        <div class="comdiv user1div">
            <div class="cl"></div><br />        
            <div class="fl">chkTermConditionAccept <nobr>tchkTermConditionAccept</nobr></div>'. $form_obj->button_list().
            '<div class="cl"></div><br />
        </div> ';
                
                
                
                $str .= $form_obj->display_form() . "<br />";
            
                
            

        
                
                        
                
                $str .= $this->editor_obj->draw_data_editor($this->list_obj);

            
                $str .= '<input type="hidden" name="lookup_id" value="' . $lookup_id . '"><input type="hidden" name="show_max" value="' . $show_max . '">';
                
                $loginUserId = $_SESSION['user_id'];
        $sqlLogin = "select ID from users where term_condition_accept = '1' and ID = '$loginUserId';";
            

            if ($result = $this->dbi->query($sqlLogin)) {


                if ($result->num_rows > 0) {
                    echo $this->redirect($this->f3->get('main_folder') . "");
                }
            
            }

            return $str;        
        }

        function pandemic_questions() {
            //return "passed";
            $main_folder = $this->f3->get('main_folder');
            $sql = "select sum(compliance_check_answers.value) as `vals`, compliance_checks.total_out_of as `out_ofs`,  compliance_checks.id from compliance_check_answers
                    inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id
                    where compliance_checks.assessor_id = " . $_SESSION['user_id'] . "
                    and compliance_checks.check_date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    and compliance.id = 47
                    group by compliance_checks.id
                    ";
            $test_done = 0;
            $test_passed = 0;
            if ($result = $this->dbi->query($sql)) {
                while ($myrow = $result->fetch_assoc()) {
                    $test_done = 1;
                    $val = $myrow['vals'];
                    $out_of = $myrow['out_ofs'];
                    if ($val == $out_of)
                        $test_passed = 1;
                }
            }
            if ($test_done) {
                if ($test_passed) {
                    $res = "passed";
                } else {
                    $res = "failed";
                }
            } else {
                $res = "passed";
                // $res = "incomplete";
            }
            return $res;
        }

        function induction_report() {
            $main_folder = $this->f3->get('main_folder');
            $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);

            $category_id = (isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : 2101);

    //        $divisionIdReportArray = array(2101 => 120);
    //        $divisionNameReportArray = array(2101 => 'Security');
    //        
            $divisionIdReportArray = array(2101 => 120, 2007 => 121, 2008 => 122, 2009 =>123,2010 =>124);
            $divisionNameReportArray = array(2101 => 'Security', 2007 => 'Facility', 2008 => 'Pest', 2009 =>'Civil',2010 =>'Traffic');
            

            $str = '<h3 class="fl">Allied Induction - Completed Reports</h3></br></br/><div class="fl" style="margin-left: 0px;">';

            $str .= '<style>
        .ASM { color: #009DE0; }
        .AFM { color: #64B446; }
        .ACM { color: #E9C229; }
        .ATM { color: #F18D2B; }
        .APM { color: #DF3330; }
        .division{ color:#00008B; }

        .ASM_selected { background-color: #009DE0; color: white; }
        .AFM_selected { background-color: #64B446; color: white; }
        .ACM_selected { background-color: #E9C229; color: white; }
        .ATM_selected { background-color: #F18D2B; color: white; }
        .APM_selected { background-color: #DF3330; color: white; }
        .division_selected{ background-color: #00008B; color: white;}
        

    .ASM_selected:hover { background-color: #009DE0; color: white; }
        .AFM_selected:hover { background-color: #64B446; color: white; }
        .ACM_selected:hover { background-color: #E9C229; color: white; }
        .ATM_selected:hover { background-color: #F18D2B; color: white; }
        .APM_selected:hover { background-color: #DF3330; color: white; }
        .division_selected{ background-color: #00008B; color: white;}


        .division_selected:hover{ background-color: #00008B; color: white;}

        .ASM:hover { background-color: #009DE0; color: white; }
        .AFM:hover { background-color: #64B446; color: white; }
        .ACM:hover { background-color: #E9C229; color: white; }
        .ATM:hover { background-color: #F18D2B; color: white; }
        .APM:hover { background-color: #DF3330; color: white; }
        .division:hover{ background-color: #00008B; color: white;}
        

        .divisionbroder{
        -webkit-box-sizing: content-box;
    -moz-box-sizing: content-box;
    box-sizing: content-box;
    display: inline-block;
    text-align: center;

    width: 130px;
    border-radius: 8px 8px 8px 8px;
    -moz-border-radius: 8px 8px 8px 8px;
    -webkit-border-radius: 8px 8px 8px 8px;
    margin: 0px;

    white-space: nowrap;
    font-weight: normal !important;
    font-size: 11pt !important;
    border: 1px solid #DDDDDD;
        border-top-color: rgb(221, 221, 221);
        border-right-color: rgb(221, 221, 221);
        border-bottom-color: rgb(221, 221, 221);
        border-left-color: rgb(221, 221, 221);
        }
        
    .ASM, .AFM, .APM, .ACM, .ATM, .division {
        border-radius: 30px;
        margin-right: 5px;
        font-size: 11px;
        font-weight: bold;
        padding: 4px 4px !important;
        border: 1px solid #ccc;
    }

        
        </style>';
            //$str .= '<a class="divisionbroder division" '.($category_id ? '' : 'style="color: white !important; background-color: #242E48 !important;"').' href="Compliance">All Categories</a> ';
            $sql = "select lookup_fields.id as idin, lookup_fields.item_name, lookup_fields.value from lookup_fields where lookup_fields.item_name NOT LIKE '%request%' and lookup_fields.item_name NOT LIKE '%survey%'  and lookup_fields.item_name NOT LIKE '%induction%' and lookup_id = 100 and id in (select category_id from compliance) order by sort_order, lookup_fields.item_name";
            if ($result = $this->dbi->query($sql)) {
                $loginUserId = $_SESSION['user_id'];
                $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
                $loginUserDivisionsArray = explode(',', $loginUserDivisions);
                $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
                $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
                while ($myrow = $result->fetch_assoc()) {
                    $item_name = $myrow['item_name'];
                    $class = $this->divisionClass($item_name);
                    $cat_id = $myrow['idin'];

                    $checkCategoryIdArray = array(2101, 2007, 2008, 2009, 2010);
                    $allowedComplianceCategory = 0;

                    if (in_array($cat_id, $checkCategoryIdArray)) {
                        $allowedComplianceCategory = $this->allowedComplianceCategory($cat_id, $loginUserDivisionsArray);
                    }
                    if ($allowedComplianceCategory) {
                        if ($cat_id == $category_id) {
                            $style_xtra = "style=\"color: white !important; background-color: #242E48 !important;\"";
                            $class = $class . " " . $class . "_selected";
                            $heading = $myrow['item_name'];
                        } else {
                            $style_xtra = "";
                            $class = $class;
                        }

                        $value = $myrow['value'];
                        $str .= '<a class="divisionbroder ' . $class . '"  href="?category_id=' . $cat_id . '">' . $item_name . '</a> ';
                    }
                }
            }
            $str .= '</div><div class="fl" style="margin-left:180px"><a class="list_a" href="' . $main_folder . 'InductionReport?category_id=' . $category_id . '&download_xl=1">Download Excel</a></div><div class="cl"></div>';

            if (isset($divisionIdReportArray[$category_id])) {
                $complianceId = $divisionIdReportArray[$category_id];
                $view_details = new data_list;
                $view_details->dbi = $this->dbi;

                $view_details->sql = "select compliance_checks.id, concat(users.name, ' ', users.surname) as `Staff Member`,
                DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y') as `Test Date`, CONCAT(sum(compliance_check_answers.value), '/', compliance_checks.total_out_of) as `Result`, if(sum(compliance_check_answers.value) = compliance_checks.total_out_of, '<span style=\"color: green\">Yes</span>', '<span style=\"color: red\">No</span>') as `Passed`
                from compliance_check_answers
                    inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id
                    inner join users on users.id = compliance_checks.assessor_id
                    where compliance.id = '" . $complianceId . "'
                    group by compliance_checks.id";
                if ($download_xl) {
                    $view_details->sql_xl('induction_reports.xlsx');
                } else {



                    $str .= $view_details->draw_list();
                }
            }


            return $str;

            /*    $str .= '<table class="grid"><tr><th>Staff Member</th><th>Test Date</th><th>Result</th><th>Passed</th></tr>';
            if($result = $this->dbi->query($sql)) {
            while($myrow = $result->fetch_assoc()) {
            $test_done = 1;
            $val = $myrow['vals'];
            $out_of = $myrow['out_ofs'];
            $staff = $myrow['staff'];
            $test_date = $myrow['test_date'];
            $test_passed = ($val == $out_of ? 'Yes' : 'No');
            $str .= "<tr><td>$staff</td><td>$test_date</td><td>$val/$out_of</td><td>$test_passed</td></tr>";
            }
            } */
        }
        
        
        function prestart_checklist_report() {
            $main_folder = $this->f3->get('main_folder');
            $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);

            $category_id = (isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : 2101);
            
            $divisionId = $_REQUEST['srch_divisionid'];
            $divWhere = " ";
            if($divisionId){
                $divWhere = " and ros.division_id = '".$divisionId."' ";
            }
            $siteId = $_REQUEST['srch_siteid'];
            $siteWhere = " ";
            if($siteId){
                $siteWhere = " and ros.site_id = '".$siteId."' ";
            }
            
            $userId = $_REQUEST['srch_userid'];
            $userWhere = " ";
            if($userId){
                $userWhere = " and rst.staff_id = '".$userId."' ";
            }
        

    //        $divisionIdReportArray = array(2101 => 120);
    //        $divisionNameReportArray = array(2101 => 'Security');
    //        
            $divisionIdReportArray = array(2101 => 120, 2007 => 121, 2008 => 122, 2009 =>123,2010 =>124);
            $divisionNameReportArray = array(108 => 'Security', 2100 => 'Facility', 2102 => 'Pest', 2103 =>'Civil',2104 =>'Traffic');
            
            
            $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC");
            
            
            $divisionFilter = 'Division <select id="srch_divisionid" name="srch_divisionid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select Division </option>';
            
            foreach ($divisionList as $division) {
                                    if($division['id'] == $divisionId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $divisionFilter .= '<option value="'.$division['id'].'" '.$selected.'>'.$division['item_name'].'</option>';
            }
            
            $divisionFilter .= '<select>';
            
            $locationData = $this->getLocation();
            
            
            $siteFilter = 'Site <select id="srch_siteid" name="srch_siteid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select Site </option>';
            
            foreach ($locationData as $site) {
                                    if($site['id'] == $siteId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $siteFilter .= '<option value="'.$site['id'].'" '.$selected.'>'.$site['item_name'].'</option>';
            }
            
            $siteFilter .= '<select>';
            
            
            $userData = $this->getAllUser();
            
            
            $userFilter = 'User <select id="srch_userid" name="srch_userid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select User </option>';
            
            foreach ($userData as $userr) {
                                    if($userr['id'] == $userId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $userFilter .= '<option value="'.$userr['id'].'" '.$selected.'>'.$userr['item_name'].'</option>';
            }
            
            $userFilter .= '<select>';
            
            
            

            $str = '<h3 class="fl">Take 5’s Prestart Checklist - Completed Reports</h3></br><div class="cl"></div>'
                    . '<h4>Filter </h4><br>'
                    . '<form method="get" name="serarch_frm" id="search_frm">'
                    . $divisionFilter
                    . $siteFilter
                    . $userFilter
                    . '</form>'
                    . '<div class="cl"></div></br/><div class="fl" style="margin-left: 0px;">';

    //        $str .= '<style>
    //    .ASM { color: #009DE0; }
    //    .AFM { color: #64B446; }
    //    .ACM { color: #E9C229; }
    //    .ATM { color: #F18D2B; }
    //    .APM { color: #DF3330; }
    //    .division{ color:#00008B; }
    //
    //    .ASM_selected { background-color: #009DE0; color: white; }
    //    .AFM_selected { background-color: #64B446; color: white; }
    //    .ACM_selected { background-color: #E9C229; color: white; }
    //    .ATM_selected { background-color: #F18D2B; color: white; }
    //    .APM_selected { background-color: #DF3330; color: white; }
    //     .division_selected{ background-color: #00008B; color: white;}
    //     
    //
    //.ASM_selected:hover { background-color: #009DE0; color: white; }
    //    .AFM_selected:hover { background-color: #64B446; color: white; }
    //    .ACM_selected:hover { background-color: #E9C229; color: white; }
    //    .ATM_selected:hover { background-color: #F18D2B; color: white; }
    //    .APM_selected:hover { background-color: #DF3330; color: white; }
    //     .division_selected{ background-color: #00008B; color: white;}
    //
    //
    //      .division_selected:hover{ background-color: #00008B; color: white;}
    //
    //    .ASM:hover { background-color: #009DE0; color: white; }
    //    .AFM:hover { background-color: #64B446; color: white; }
    //    .ACM:hover { background-color: #E9C229; color: white; }
    //    .ATM:hover { background-color: #F18D2B; color: white; }
    //    .APM:hover { background-color: #DF3330; color: white; }
    //    .division:hover{ background-color: #00008B; color: white;}
    //     
    //
    //    .divisionbroder{
    //     -webkit-box-sizing: content-box;
    //-moz-box-sizing: content-box;
    //box-sizing: content-box;
    //display: inline-block;
    //text-align: center;
    //
    //width: 130px;
    //border-radius: 8px 8px 8px 8px;
    //-moz-border-radius: 8px 8px 8px 8px;
    //-webkit-border-radius: 8px 8px 8px 8px;
    //margin: 0px;
    //
    //white-space: nowrap;
    //font-weight: normal !important;
    //font-size: 11pt !important;
    //border: 1px solid #DDDDDD;
    //    border-top-color: rgb(221, 221, 221);
    //    border-right-color: rgb(221, 221, 221);
    //    border-bottom-color: rgb(221, 221, 221);
    //    border-left-color: rgb(221, 221, 221);
    //    }
    //    
    //.ASM, .AFM, .APM, .ACM, .ATM, .division {
    //	border-radius: 30px;
    //	margin-right: 5px;
    //	font-size: 11px;
    //	font-weight: bold;
    //	padding: 4px 4px !important;
    //	border: 1px solid #ccc;
    //}
    //
    //     
    //    </style>';
            //$str .= '<a class="divisionbroder division" '.($category_id ? '' : 'style="color: white !important; background-color: #242E48 !important;"').' href="Compliance">All Categories</a> ';
    //        $sql = "select lookup_fields.id as idin, lookup_fields.item_name, lookup_fields.value from lookup_fields where lookup_fields.item_name NOT LIKE '%request%' and lookup_fields.item_name NOT LIKE '%survey%'  and lookup_fields.item_name NOT LIKE '%induction%' and lookup_id = 100 and id in (select category_id from compliance) order by sort_order, lookup_fields.item_name";
    //        if ($result = $this->dbi->query($sql)) {
    //            $loginUserId = $_SESSION['user_id'];
    //            $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
    //            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
    //            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
    //            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
    //            while ($myrow = $result->fetch_assoc()) {
    //                $item_name = $myrow['item_name'];
    //                $class = $this->divisionClass($item_name);
    //                $cat_id = $myrow['idin'];
    //
    //                $checkCategoryIdArray = array(2101, 2007, 2008, 2009, 2010);
    //                $allowedComplianceCategory = 0;
    //
    //                if (in_array($cat_id, $checkCategoryIdArray)) {
    //                    $allowedComplianceCategory = $this->allowedComplianceCategory($cat_id, $loginUserDivisionsArray);
    //                }
    //                if ($allowedComplianceCategory) {
    //                    if ($cat_id == $category_id) {
    //                        $style_xtra = "style=\"color: white !important; background-color: #242E48 !important;\"";
    //                        $class = $class . " " . $class . "_selected";
    //                        $heading = $myrow['item_name'];
    //                    } else {
    //                        $style_xtra = "";
    //                        $class = $class;
    //                    }
    //
    //                    $value = $myrow['value'];
    //                    $str .= '<a class="divisionbroder ' . $class . '"  href="?category_id=' . $cat_id . '">' . $item_name . '</a> ';
    //                }
    //            }
    //        }
    //        $str .= '</div><div class="fl" style="margin-left:180px"><a class="list_a" href="' . $main_folder . 'InductionReport?category_id=' . $category_id . '&download_xl=1">Download Excel</a></div><div class="cl"></div>';

            //if (isset($divisionIdReportArray[$category_id])) {
                $complianceId = "132";
                $view_details = new data_list;
                $view_details->dbi = $this->dbi;
    /*
    * ,      
    CONCAT(sum(compliance_check_answers.value), '/', compliance_checks.total_out_of) as `Result`,
                if(sum(compliance_check_answers.value) = compliance_checks.total_out_of, '<span style=\"color: green\">Yes</span>', '<span style=\"color: red\">No</span>') as `Passed`
    */
                
                
                $view_details->sql = "select compliance_checks.id,
                    concat(users.name, ' ', users.surname) as `Staff Member`,
                    concat(sites.name, ' ', sites.surname) as `Sites`,
                    rosdiv.item_name as `Roster Division`,
                    (rt.start_time_date) roster_start, (rt.finish_time_date) roster_end,
                DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %h:%i') as `Report Date`
                ,concat('<a class=\"list_a\" target=\"_blank\" href=\"/CompliancePDF/',compliance_checks.id,'\">View Detail</a>') as report_info
                from compliance_check_answers
                    inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id                  
                    inner join users on users.id = compliance_checks.assessor_id
                    inner join roster_times_staff rst on rst.id = compliance_checks.subject_id
                    inner join roster_times rt on rt.id = rst.roster_time_id
                    inner join rosters ros on ros.id = rt.roster_id
                    left join companies rosdiv on rosdiv.id = ros.division_id
                    inner join users sites on sites.id = ros.site_id
                    where compliance.id = '" . $complianceId . "' 
                    $divWhere
                    $siteWhere
                    $userWhere
                    group BY compliance_checks.assessor_id,compliance_checks.compliance_id,compliance_checks.subject_id
                    ORDER BY compliance_checks.check_date_time desc";
                
                //echo $view_details->sql;
                //die;
                
    //            if ($download_xl) {
    //                $view_details->sql_xl('induction_reports.xlsx');
    //            } else {

    //echo $view_details->sql;
    //die;

                    $str .= $view_details->draw_list();
            // }
        // }


            return $str;

            /*    $str .= '<table class="grid"><tr><th>Staff Member</th><th>Test Date</th><th>Result</th><th>Passed</th></tr>';
            if($result = $this->dbi->query($sql)) {
            while($myrow = $result->fetch_assoc()) {
            $test_done = 1;
            $val = $myrow['vals'];
            $out_of = $myrow['out_ofs'];
            $staff = $myrow['staff'];
            $test_date = $myrow['test_date'];
            $test_passed = ($val == $out_of ? 'Yes' : 'No');
            $str .= "<tr><td>$staff</td><td>$test_date</td><td>$val/$out_of</td><td>$test_passed</td></tr>";
            }
            } */
        }
        
        function shift_ending_client_handover_report() {
            $main_folder = $this->f3->get('main_folder');
            $download_xl = (isset($_GET['download_xl']) ? $_GET['download_xl'] : null);

            $category_id = (isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : 2101);
            
            $divisionId = $_REQUEST['srch_divisionid'];
            $divWhere = " ";
            if($divisionId){
                $divWhere = " and ros.division_id = '".$divisionId."' ";
            }
            $siteId = $_REQUEST['srch_siteid'];
            $siteWhere = " ";
            if($siteId){
                $siteWhere = " and ros.site_id = '".$siteId."' ";
            }
            
            $userId = $_REQUEST['srch_userid'];
            $userWhere = " ";
            if($userId){
                $userWhere = " and rst.staff_id = '".$userId."' ";
            }
        

    //        $divisionIdReportArray = array(2101 => 120);
    //        $divisionNameReportArray = array(2101 => 'Security');
    //        
            $divisionIdReportArray = array(2101 => 120, 2007 => 121, 2008 => 122, 2009 =>123,2010 =>124);
            $divisionNameReportArray = array(108 => 'Security', 2100 => 'Facility', 2102 => 'Pest', 2103 =>'Civil',2104 =>'Traffic');
            
            
            $divisionList = $this->db->exec("SELECT id,item_name FROM companies ORDER BY item_name ASC");
            
            
            $divisionFilter = 'Division <select id="srch_divisionid" name="srch_divisionid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select Division </option>';
            
            foreach ($divisionList as $division) {
                                    if($division['id'] == $divisionId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $divisionFilter .= '<option value="'.$division['id'].'" '.$selected.'>'.$division['item_name'].'</option>';
            }
            
            $divisionFilter .= '<select>';
            
            $locationData = $this->getLocation();
            
            
            $siteFilter = 'Site <select id="srch_siteid" name="srch_siteid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select Site </option>';
            
            foreach ($locationData as $site) {
                                    if($site['id'] == $siteId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $siteFilter .= '<option value="'.$site['id'].'" '.$selected.'>'.$site['item_name'].'</option>';
            }
            
            $siteFilter .= '<select>';
            
            
            $userData = $this->getAllUser();
            
            
            $userFilter = 'User <select id="srch_userid" name="srch_userid" onchange="javascript:form.submit()" class="full_width" style="width:170px;">
    <option value=""> Select User </option>';
            
            foreach ($userData as $userr) {
                                    if($userr['id'] == $userId){
                                        $selected = "selected";
                                    }else{
                                        $selected = "";
                                    }
                $userFilter .= '<option value="'.$userr['id'].'" '.$selected.'>'.$userr['item_name'].'</option>';
            }
            
            $userFilter .= '<select>';
            
            
            

            $str = '<h3 class="fl"> Shift Handover Report </h3></br><div class="cl"></div>'
                    . '<h4>Filter </h4><br>'
                    . '<form method="get" name="serarch_frm" id="search_frm">'
                    . $divisionFilter
                    . $siteFilter
                    . $userFilter
                    . '</form>'
                    . '<div class="cl"></div></br/><div class="fl" style="margin-left: 0px;">';

    //        $str .= '<style>
    //    .ASM { color: #009DE0; }
    //    .AFM { color: #64B446; }
    //    .ACM { color: #E9C229; }
    //    .ATM { color: #F18D2B; }
    //    .APM { color: #DF3330; }
    //    .division{ color:#00008B; }
    //
    //    .ASM_selected { background-color: #009DE0; color: white; }
    //    .AFM_selected { background-color: #64B446; color: white; }
    //    .ACM_selected { background-color: #E9C229; color: white; }
    //    .ATM_selected { background-color: #F18D2B; color: white; }
    //    .APM_selected { background-color: #DF3330; color: white; }
    //     .division_selected{ background-color: #00008B; color: white;}
    //     
    //
    //.ASM_selected:hover { background-color: #009DE0; color: white; }
    //    .AFM_selected:hover { background-color: #64B446; color: white; }
    //    .ACM_selected:hover { background-color: #E9C229; color: white; }
    //    .ATM_selected:hover { background-color: #F18D2B; color: white; }
    //    .APM_selected:hover { background-color: #DF3330; color: white; }
    //     .division_selected{ background-color: #00008B; color: white;}
    //
    //
    //      .division_selected:hover{ background-color: #00008B; color: white;}
    //
    //    .ASM:hover { background-color: #009DE0; color: white; }
    //    .AFM:hover { background-color: #64B446; color: white; }
    //    .ACM:hover { background-color: #E9C229; color: white; }
    //    .ATM:hover { background-color: #F18D2B; color: white; }
    //    .APM:hover { background-color: #DF3330; color: white; }
    //    .division:hover{ background-color: #00008B; color: white;}
    //     
    //
    //    .divisionbroder{
    //     -webkit-box-sizing: content-box;
    //-moz-box-sizing: content-box;
    //box-sizing: content-box;
    //display: inline-block;
    //text-align: center;
    //
    //width: 130px;
    //border-radius: 8px 8px 8px 8px;
    //-moz-border-radius: 8px 8px 8px 8px;
    //-webkit-border-radius: 8px 8px 8px 8px;
    //margin: 0px;
    //
    //white-space: nowrap;
    //font-weight: normal !important;
    //font-size: 11pt !important;
    //border: 1px solid #DDDDDD;
    //    border-top-color: rgb(221, 221, 221);
    //    border-right-color: rgb(221, 221, 221);
    //    border-bottom-color: rgb(221, 221, 221);
    //    border-left-color: rgb(221, 221, 221);
    //    }
    //    
    //.ASM, .AFM, .APM, .ACM, .ATM, .division {
    //	border-radius: 30px;
    //	margin-right: 5px;
    //	font-size: 11px;
    //	font-weight: bold;
    //	padding: 4px 4px !important;
    //	border: 1px solid #ccc;
    //}
    //
    //     
    //    </style>';
            //$str .= '<a class="divisionbroder division" '.($category_id ? '' : 'style="color: white !important; background-color: #242E48 !important;"').' href="Compliance">All Categories</a> ';
    //        $sql = "select lookup_fields.id as idin, lookup_fields.item_name, lookup_fields.value from lookup_fields where lookup_fields.item_name NOT LIKE '%request%' and lookup_fields.item_name NOT LIKE '%survey%'  and lookup_fields.item_name NOT LIKE '%induction%' and lookup_id = 100 and id in (select category_id from compliance) order by sort_order, lookup_fields.item_name";
    //        if ($result = $this->dbi->query($sql)) {
    //            $loginUserId = $_SESSION['user_id'];
    //            $loginUserDivisions = $this->get_divisions($loginUserId, 0, 1);
    //            $loginUserDivisionsArray = explode(',', $loginUserDivisions);
    //            $loginUserDivisionsArray = array_map('trim', $loginUserDivisionsArray);
    //            $loginUserDivisionsStr = implode(',', $loginUserDivisionsArray);
    //            while ($myrow = $result->fetch_assoc()) {
    //                $item_name = $myrow['item_name'];
    //                $class = $this->divisionClass($item_name);
    //                $cat_id = $myrow['idin'];
    //
    //                $checkCategoryIdArray = array(2101, 2007, 2008, 2009, 2010);
    //                $allowedComplianceCategory = 0;
    //
    //                if (in_array($cat_id, $checkCategoryIdArray)) {
    //                    $allowedComplianceCategory = $this->allowedComplianceCategory($cat_id, $loginUserDivisionsArray);
    //                }
    //                if ($allowedComplianceCategory) {
    //                    if ($cat_id == $category_id) {
    //                        $style_xtra = "style=\"color: white !important; background-color: #242E48 !important;\"";
    //                        $class = $class . " " . $class . "_selected";
    //                        $heading = $myrow['item_name'];
    //                    } else {
    //                        $style_xtra = "";
    //                        $class = $class;
    //                    }
    //
    //                    $value = $myrow['value'];
    //                    $str .= '<a class="divisionbroder ' . $class . '"  href="?category_id=' . $cat_id . '">' . $item_name . '</a> ';
    //                }
    //            }
    //        }
    //        $str .= '</div><div class="fl" style="margin-left:180px"><a class="list_a" href="' . $main_folder . 'InductionReport?category_id=' . $category_id . '&download_xl=1">Download Excel</a></div><div class="cl"></div>';

            //if (isset($divisionIdReportArray[$category_id])) {
                $complianceId = "141";
                $view_details = new data_list;
                $view_details->dbi = $this->dbi;
    /*
    * ,      
    CONCAT(sum(compliance_check_answers.value), '/', compliance_checks.total_out_of) as `Result`,
                if(sum(compliance_check_answers.value) = compliance_checks.total_out_of, '<span style=\"color: green\">Yes</span>', '<span style=\"color: red\">No</span>') as `Passed`
    */
                
                
                $view_details->sql = "select compliance_checks.id,
                    concat(users.name, ' ', users.surname) as `Staff Member`,
                    concat(sites.name, ' ', sites.surname) as `Sites`,
                    rosdiv.item_name as `Roster Division`,
                    (rt.start_time_date) roster_start, (rt.finish_time_date) roster_end,
                DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y %h:%i') as `Report Date`
                ,concat('<a class=\"list_a\" target=\"_blank\" href=\"/CompliancePDF/',compliance_checks.id,'\">View Detail</a>') as report_info
                from compliance_check_answers
                    inner join compliance_checks on compliance_checks.id = compliance_check_answers.compliance_check_id
                    inner join compliance on compliance.id = compliance_checks.compliance_id                  
                    inner join users on users.id = compliance_checks.assessor_id
                    inner join roster_times_staff rst on rst.id = compliance_checks.subject_id
                    inner join roster_times rt on rt.id = rst.roster_time_id
                    inner join rosters ros on ros.id = rt.roster_id
                    left join companies rosdiv on rosdiv.id = ros.division_id
                    inner join users sites on sites.id = ros.site_id
                    where compliance.id = '" . $complianceId . "' 
                    $divWhere
                    $siteWhere
                    $userWhere
                    group BY compliance_checks.assessor_id,compliance_checks.compliance_id,compliance_checks.subject_id
                    ORDER BY compliance_checks.check_date_time desc";
                
                //echo $view_details->sql;
                //die;
                
    //            if ($download_xl) {
    //                $view_details->sql_xl('induction_reports.xlsx');
    //            } else {

    //echo $view_details->sql;
    //die;

                    $str .= $view_details->draw_list();
            // }
        // }


            return $str;

            /*    $str .= '<table class="grid"><tr><th>Staff Member</th><th>Test Date</th><th>Result</th><th>Passed</th></tr>';
            if($result = $this->dbi->query($sql)) {
            while($myrow = $result->fetch_assoc()) {
            $test_done = 1;
            $val = $myrow['vals'];
            $out_of = $myrow['out_ofs'];
            $staff = $myrow['staff'];
            $test_date = $myrow['test_date'];
            $test_passed = ($val == $out_of ? 'Yes' : 'No');
            $str .= "<tr><td>$staff</td><td>$test_date</td><td>$val/$out_of</td><td>$test_passed</td></tr>";
            }
            } */
        }
        

        function UserAction() {
            $main_folder = $this->f3->get('main_folder');
            $action = (isset($_GET['action']) ? $_GET['action'] : 0);
            $user_id = (isset($_GET['user_id']) ? $_GET['user_id'] : 0);
            if ($action == "status") {
                $sql = "SELECT user_status_id from users where id = $user_id";
                if ($result = $this->dbi->query($sql)) {
                    if ($myrow = $result->fetch_assoc()) {
                        $status = $myrow['user_status_id'];
                    }
                }
                $this->dbi->query("update users set user_status_id = " . ($status == 40 ? 30 : 40) . " where id = $user_id");
                echo ($status == 30 ? 'INACTIVE' : 'ACTIVE');
            } else {
                $str = (isset($_GET['str']) ? $this->dbi->real_escape_string(urldecode($_GET['str'])) : 0);
                $field = $action;
                $ok = 1;
                if ($action == 'pw') {
                    
                    $userDetail = $this->getUserDetail($user_id);
                    if($userDetail){
                        $this->sendPasswordChangeEmail($userDetail['name'], $userDetail['email'], $userDetail['username'], $str);
                    }
                    
                
                    $str = $this->ed_crypt($str, $user_id);
                    
                    echo "The password has been reset.";
                }
                else if ($action == 'pws') {
                    $str = $this->ed_crypt($str, $user_id);
                    echo "The password has been reset.";
                }
                else if ($action == 'email' || $action == 'username') {
                    $ok = ($this->get_sql_result("select $action as `result` from users where $action = '$str' and id != $user_id") ? 0 : 1);
                }

                if ($ok) {
                    $this->dbi->query("update users set $field = '$str' where id = $user_id");
                } else {
                    echo "The $action already exists, please try a different $action.";
                }
            }
        }

        function UserManager($uidin = 0, $site_idin = 0) {
            $main_folder = $this->f3->get('main_folder');
            $user_id = (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null);
            $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : null);
            $txtFind = (isset($_REQUEST['txtFind']) ? $_REQUEST['txtFind'] : null);
            $selSite = (isset($_REQUEST['selSite']) ? $_REQUEST['selSite'] : ($site_idin ? $site_idin : null));

            $str .= '
        <script>
        function user_action(action, id) {
            var str_in
            if(action != "status") {
            str_in = document.getElementById(action + \'-\' + id).value
            }
            if(action == "pw")  {
            document.getElementById("pw-" + id).value = ""
            }
            $.ajax({
            type:"get",
                url:"' . $main_folder . 'UserAction",
                data:{ user_id: id, action: action, str: str_in },

                success:function(msg){
                    var str = msg
                    if(action == "status") {
                    str += \'&nbsp;<a class="list_a" href="JavaScript:user_action(\' + String.fromCharCode(39) + \'status\' + String.fromCharCode(39) + \', \' + id + \')">\'
                    if(msg == "ACTIVE") {
                        str += \'DE\'
                        document.getElementById("s" + id).style.color = \'#0000CC\'
                    } else {
                        document.getElementById("s" + id).style.color = \'#CC0000\'
                    }
                    str += \'ACTIVATE</a>\'
                    document.getElementById("s" + id).innerHTML = str
                    } else if((action == "username" || action == "email" || action == "pw") && str) {
                        if(action == "pw"){
                        alert("Your password has beend reset");
                        }
                    document.getElementById("s" + id).innerHTML = str
                    }
                    
                }
            }); 
        }
        </script>';

            if (!$uidin && !$site_idin) {
                $str .= '
        <script>
            function clear_sel(sel) {
            sel.selectedIndex = 0
            }
        </script>';
                $itm = new input_item;
                $itm->hide_filter = 1;
                $site_sql = "select * from (select 0 as id, '--- Choose a Site (Optional) ---' as item_name union all SELECT users.id as `idin`, CONCAT(users.name, ' ', users.surname) as `item_name` FROM users inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.lookup_field_id = 384 and lookup_answers.table_assoc = 'users' where users.name NOT LIKE '%ADHOC%') as a order by item_name";

                $str .= '<div class="fl">
        <div class="form-wrapper">
        <div class="form-header">User Manager</div>
        <div class="form-content">';
            }
            if (!$uidin) {
                
                if (!$site_idin)
                    $str .= 'Search for a user by entering all or part of their name below and/or select a site to list staff members at a site. ';
                $str .= 'One Star (*) before the User\'s status means they have not yet logged in. Two stars (**) before the user status means they do not have a login Double click any field below to clear it.';
            }
            if (!$uidin && !$site_idin) {
                $str .= '<p><input placeholder="Enter all or part of User\'s Name" maxlength="70" name="txtFind" id="search" type="text" class="search_box" value="' . $_REQUEST['txtFind'] . '" />';
                $str .= $itm->sel("selSite", "", "onDblClick=\"clear_sel(this)\"", "", $this->dbi, $site_sql, "") . '<input type="submit" name="cmdFollowSearch" value="Search" class="search_button" />' . $search_msg . '</p>';
                $str .= '</div></div>';
                $str .= '<div class="cl"></div>';
            }


            if ($txtFind || $uidin || $selSite) {
                if ($uidin) {
                    $search = " where users.id = $uidin ";
                } else {
                    if ($selSite) {
                        $search = " where users.id in  
            (select child_user_id
            from associations where parent_user_id = $selSite)";
                    }
                    if ($txtFind) {
                        $search = ($selSite ? "$search and " : " where ") . "
                (
                users.name LIKE '%$txtFind%'
                or users.surname LIKE '%$txtFind%'
                or CONCAT(users.name, ' ', users.surname) LIKE '%$txtFind%'
                or users.email LIKE '%$txtFind%'
                or users.employee_id LIKE '%$txtFind%'
                or users.client_id LIKE '%$txtFind%'
                or CONCAT(users.name, ' ', users.surname) LIKE '%$txtFind%')
                ";
                    }
                }
                $this->list_obj->show_num_records = 1;
                $sql = "
                select distinct(users.id) as idin, 
                
                users.client_id, users.employee_id, users.name, users.surname,
                users.username, states.item_name as `state`,
                phone as `phone`, users.email as `email`, user_level.item_name as `user_level`,
                CONCAT('<span style=\"color: ', if(user_status.item_name = 'ACTIVE', '#0000CC', '#CC0000'), ';\">', user_status.item_name, '</span>', ' &nbsp; <a class=\"list_a\" href=\"JavaScript:user_action(''status'',', users.id, ', \'\')\">', if(user_status.item_name = 'ACTIVE', 'DE', ''), 'ACTIVATE</a>') as `status`, users.pw
                from users
                left join states on states.id = users.state
                left join user_level on user_level.id = users.user_level_id
                left join user_status on user_status.id = users.user_status_id
                inner join lookup_answers on lookup_answers.foreign_id = users.id and lookup_answers.table_assoc = 'users'
                $search
                and users.id != 2
                order by users.user_status_id, CONCAT(users.name, ' ', users.surname)";

    //              inner join lookup_fields on lookup_fields.id = lookup_answers.lookup_field_id and lookup_fields.value = 'HR' or lookup_fields.value = 'PERSON'
    //return "<textarea>" . $sql . "</textarea>";
                if (!$uidin && !$site_idin)
                    $str .= "</div></div><div class=\"cl\"></div>";

                if ($result = $this->dbi->query($sql)) {
                    $str .= '
            <script>
            function edit_user(idin) {
            document.getElementById("idin").value = idin;
            document.frmEdit.action = "Edit/Users";
            document.frmEdit.submit();
            }
            </script>
            <input type="hidden" name="hdnFilter" id="hdnFilter" />
            <input type="hidden" name="idin" id="idin" />
            <style>
            .small_txt {  width: 120px; }
            .med_txt {  width: 160px; }
            .large_txt {  width: 200px; }
            </style>
            <script>
            function clear_field(field) {
                field.value = ""
                field.focus()
            }
            </script> 
            <table class="grid" border="0" cellpadding="0">';
                    
                    
            $loginUserType = $this->getUserType($uidin);
                    
                    
            if($loginUserType != 384){                 
                $str .= "<tr><th>Name</th><th>Surname</th>" . ($uidin ? "" : "<th>Username</th>") . "<th>Phone</th><th>Email</th><th>User Level</th><th>Set Password</th><th>Status</th><th>Options</th></tr>";
            }else{
                $str .= "<tr><th>Status</th><th>Options</th></tr>";
            } 
                    while ($myrow = $result->fetch_assoc()) {
                        
                            
                        
                        
                        $idin = $myrow['idin'];
                        $name = $myrow['name'];
                        $surname = $myrow['surname'];
                        $username = $myrow['username'];
                        $phone = $myrow['phone'];
                        $email = $myrow['email'];
                        $state = $myrow['state'];
                        $user_level = $myrow['user_level'];
                        $status = $myrow['status'];
                        $pw = $myrow['pw'];

                        $pwc = $this->ed_crypt($username, $idin);
                        $pwc = ($pw ? ($pwc == $pw ? "*" : "") : "**");
    if($loginUserType != 384){  
                        $str .= "<tr>
            <td><nobr><input class=\"medium_txt\" onblur=\"user_action('name', $idin)\" type=\"text\" id=\"name-$idin\" value=\"$name\" /></td>
            <td><input class=\"small_txt\" onDblClick=\"clear_field(this)\" onblur=\"user_action('surname', $idin)\" type=\"text\" id=\"surname-$idin\" value=\"$surname\" /></td>
            " . ($uidin ? "" : "<td><input class=\"medium_txt\" onDblClick=\"clear_field(this)\" onblur=\"user_action('username', $idin)\" id=\"username-$idin\"type=\"text\" value=\"$username\" /></td>") . "
            <td><input class=\"small_txt\" onDblClick=\"clear_field(this)\" onblur=\"user_action('phone', $idin)\" id=\"phone-$idin\"type=\"text\" value=\"$phone\" /></td>
            <td><input class=\"medium_txt\" onDblClick=\"clear_field(this)\" onblur=\"user_action('email', $idin)\" id=\"email-$idin\"type=\"text\" value=\"$email\" /></td>
            <td>$user_level</td>
            <td><input class=\"small_txt\" onDblClick=\"clear_field(this)\" id=\"pw-$idin\"type=\"text\" value=\"$username\" /><input type=\"button\" value=\"Go\" onClick=\"user_action('pw', $idin)\"  /></td>
            <td id=\"s$idin\">$pwc $status</td><td>";

                        if (!$uidin)
                            $str .= "<a class=\"list_a\" target=\"_TOP\" href=\"UserCard?uid=$idin\">Card</a>";

                        $str .= "
            <script>
            document.frmEdit.target = '_top';
            </script>
            <a class=\"list_a\" target=\"_TOP\" href=\"javascript:edit_user($idin)\">Edit</a></td>
            </tr>";
                        
                        }else{
                        $str .= "<tr>
            
            <td id=\"s$idin\">$pwc $status</td><td>";

                        if (!$uidin)
                            $str .= "<a class=\"list_a\" target=\"_TOP\" href=\"UserCard?uid=$idin\">Card</a>";

                        $str .= "
            <script>
            document.frmEdit.target = '_top';
            </script>
            <a class=\"list_a\" target=\"_TOP\" href=\"javascript:edit_user($idin)\">Edit</a></td>
            </tr>";  
                        }
                        
                    }
                    $str .= '</table>';
                }
            }


            if (!$uidin) {
                $str .= "</div></div>";
                $str .= "<div class=\"cl\"></div>";
            }
            return $str;

    //              and users.user_level_id < " . $_SESSION['u_level']
        }

        function UserAccess($user_id) {
            $main_folder = $this->f3->get('main_folder');
            $u_level = $_SESSION['u_level'];

            $levels[0] = "N/A";
            $levels[1] = "Read";
            $levels[2] = "Read/Write";
            $levels[3] = "Read/Delete";
            $levels[4] = "Read/Write/Delete";
            $levels[5] = "Edit Only (No Adding)";

            $sql = "select lookup_fields.id, lookup_fields.item_name from lookup_fields
                left join lookup_answers on lookup_answers.lookup_field_id = lookup_fields.id
                where foreign_id = '$user_id' and lookup_id = 21 and table_assoc = 'users'";
            //$sql = "select user_group_id as `id` from users_user_groups where user_id = '$user_id';";
            $result = $this->dbi->query($sql);
            $num_rows = $result->num_rows;
            $x = 0;
            $lids = array();
            while ($myrow = $result->fetch_assoc()) {
                $id = $myrow['id'];
                $group = $myrow['item_name'];
                $group_str .= ($x ? ", " : "") . $group;
                $lids[] = $id;
                $x++;
            }
            if ($group_str)
                $str .= "<h3>Groups</h3><p>$group_str</p>";

            $lids = ($x > 0 ? implode(",", $lids) : '0');

            $sql = "
        select main_pages2.id as `id`, main_pages2.title as `title` from main_pages2
        left join page_types on page_types.id = main_pages2.page_type_id
        where parent_id != 0 and user_level_id <= $u_level and max_user_level_id >= $u_level and (page_types.item_name = 'SIDE') and parent_id in (select id from main_pages2 where page_type_id != 0 and page_type_id != 10000 and parent_id = 0 and user_level_id <= $u_level and max_user_level_id >= $u_level and (page_types.item_name = 'SIDE') $sql_xtra) 
        " . ($lids ? "and main_pages2.id in (select foreign_id from lookup_answers where table_assoc = 'main_pages2' and lookup_field_id in ($lids))" : "")
                    . " order by item_name ";
            //echo $sql;
            // die;
            $y = 0;
            if ($result = $this->dbi->query($sql)) {
                $str .= '<h3>Page Access</h3><table class="grid"><tr><th>Page</th><th>Access</th></tr>';
                while ($myrow = $result->fetch_assoc()) {
                    $title = strip_tags($myrow['title']);
                    $page_id = strip_tags($myrow['id']);
                    //$str .= ($y ? "</tr>" : "") . $title;
                    $level = ($this->get_sql_result("select max(access_level) as `result` from page_access where page_id = $page_id " . ($lids ? "and group_id in ($lids)" : "")));

                    if (!$level)
                        $level = 0;
                    //$level = ($level > $old_level ? $level : $old_level);
                    $str .= "<tr><td>$title</td><td>{$levels[$level]}</td></tr>";
                    $y++;
                }
                $str .= '</table>';
            }
            if (!$y)
                $str .= "<h4>This User has no access...</h4>";
            return $str;
        }

        function ViewCard($user_id_in = "") {
        
            $main_folder = $this->f3->get('main_folder');
            $user_id = ($user_id_in ? $user_id_in : (isset($_GET['uid']) ? $_GET['uid'] : null));
            $str .= $this->UserCard($user_id, 1);
            /*
            $user_access = (isset($_GET['user_access']) ? $_GET['user_access'] : null);
            $client_user = $this->f3->get('is_client');
            $site_user = $this->f3->get('is_site');
            $client_or_site_user = $client_user + $site_user;
            if($client_user) {
            $allow_access = $this->num_results("select child_user_id from associations where association_type_id = 1 and child_user_id = $user_id and parent_user_id = {$_SESSION['user_id']} ");
            } else if($site_user) {
            $allow_access = ($user_id == $_SESSION['user_id'] ? 1 : 0);
            } else {
            $allow_access = 1;
            }
            if($allow_access) {


            $view_details = new data_list;
            $view_details->dbi = $this->dbi;


            $str .= '
            <style>
            .cell_wrap {
            float: left;
            display: inline-block;
            padding: 0px;
            border: 1px solid #DDDDDD;
            }
            .cell_head {
            padding: 6px;
            background-color: white;
            }
            .cell_foot {
            padding: 6px;
            background-color: #F9F9F9;
            border-top: 1px solid #DDDDDD;
            }
            </style>

            ';


            $sql = "select users.name, users.surname, users.phone, users.phone2, users.user_level_id, users.email, users.email2, CONCAT(users.address, ' ', users.suburb, ' ', states.item_name, ' ', users.postcode) as `address`, states.item_name as `state`, users.state, users.dob from users left join states on states.id = users.state where users.id = $user_id";
            $result = $this->dbi->query($sql);
            if($myrow = $result->fetch_assoc()) {
            $employee_id = $myrow['employee_id'];
            $user_level = $myrow['user_level_id'];
            $name = $myrow['name'];
            $surname = $myrow['surname'];
            $phone = $myrow['phone'];
            $phone2 = $myrow['phone2'];
            $email = $myrow['email'];
            $email2 = $myrow['email2'];
            $address = $myrow['address'];
            $dob = $myrow['dob'];
            $state = $myrow['state'];

            $is_staff = ($user_level >= 300 ? 1 : 0);

            if($user_level < 300) {
            $is_client = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 104 and foreign_id = " . $user_id);
            $is_site = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 384 and foreign_id = " . $user_id);
            $has_sites = ($is_client ? $this->num_results("SELECT associations.id from associations where association_type_id = 1 and parent_user_id = " . $user_id) : 0);
            }


            //        $str .= "<h3>".($is_staff ? "Staff" : "Site")." Card for $name $surname</h4>";
            $str .= "<h3>Card Details for $name $surname</h4>";

            $this->f3->set('user_type', ($user_level >= 300 ? "STAFF" : "SITE"));


            if($phone) $str .= '<div class="cell_wrap"><div class="cell_head">Phone</div><div class="cell_foot">'.$phone.'</div></div>';
            if($phone2) $str .= '<div class="cell_wrap"><div class="cell_head">Phone 2</div><div class="cell_foot">'.$phone2.'</div></div>';
            if($email) $str .= '<div class="cell_wrap"><div class="cell_head">Email</div><div class="cell_foot"><a href="mailto:'.$email.'">'.$email.'</a></div></div>';
            if($email2) $str .= '<div class="cell_wrap"><div class="cell_head">Email 2</div><div class="cell_foot"><a href="mailto:'.$email2.'">'.$email2.'</a></div></div>';
            if($address) $str .= '<div class="cell_wrap"><div class="cell_head">Address</div><div class="cell_foot">'.$address.'</div></div>';
            if($is_staff && $dob && $dob != '0000-00-00') $str .= '<div class="cell_wrap"><div class="cell_head">DOB</div><div class="cell_foot">'.date('d-M-Y', strtotime($dob)).'</div></div>';

            $sql = "select user_group_id as `id` from users_user_groups where user_id = '$user_id';";
            if($result = $this->dbi->query($sql)) {
            $c = 0;
            while($myrow = $result->fetch_assoc()) {
            $card_lids .= ($c ? "," : "") . $myrow['id'];
            $c++;
            }
            }
            $sql = "
            SELECT lookups.id as `lookup_id`, lookups.item_name as `lookup`, lookups.description
            FROM `lookup_answers`
            left join lookups on lookup_answers.foreign_id = lookups.id
            WHERE lookup_answers.lookup_field_id in ($card_lids) and lookup_answers.table_assoc = 'lookups'
            group by lookups.id
            order by lookups.sort_order
            ";
            //return $sql;
            $result = $this->dbi->query($sql);
            $x = 0;
            while($myrow = $result->fetch_assoc()) {
            $lookup_id = $myrow['lookup_id'];
            $lookup = $myrow['lookup'];
            $description = $myrow['description'];
            $sql = "select meta_key, meta_value from usermeta where meta_key = $lookup_id and user_id = $user_id";
            $result2 = $this->dbi->query($sql);
            if($myrow2 = $result2->fetch_assoc()) {
            $mkey = $myrow2['meta_key'];
            $mval = $myrow2['meta_value'];
            if($mval && $mval != '0000-00-00') $str .= '<div class="cell_wrap"><div class="cell_head">'.$description.'</div><div class="cell_foot">'.$mval.'</div></div>';
            }
            }


            $str .= '<div class="cl"></div>';

            if($is_staff) {
            } else {
            if($is_client) {
            $sql = "select users.name from associations
            inner join users on users.id = associations.child_user_id
            where association_type_id = 1 and parent_user_id = $user_id and users.user_status_id = 30";
            //return $view_details->sql;
            $result = $this->dbi->query($sql);
            if($result = $this->dbi->query($sql)) {
            $c = 0;
            while($myrow = $result->fetch_assoc()) {
            $str .= ($c ? ", " : "<h3>Sites</h3>") . $myrow['name'];
            $c++;
            }
            }
            }
            if($is_site) {

            $str .= '<div class="uk-flex ">';
            $sql = "select rosters.id as `idin`, CONCAT('<nobr>', companies.item_name, '</nobr>') as `Division`, start_date as `Start Date`, CONCAT('<a target=\"_top\" class=\"list_a\" href=\"Rostering/ViewRoster?rid=', rosters.id, '\">Open</a>') as `Open` from rosters left join companies on companies.id = rosters.division_id
            where site_id = $user_id order by start_date DESC LIMIT 5";
            $view_details->sql = $sql;
            if($test = $view_details->draw_list()) $str .= '<div class="uk-padding-small uk-padding-remove-left"><h3>Latest Rosters</h3>' . $test . '</div>';

            $sql = "select CONCAT(users.name, ' ', users.surname) as `name` from associations
            inner join users on users.id = associations.child_user_id
            where association_type_id = 4 and parent_user_id = $user_id and users.user_status_id = 30";
            //return $sql;
            if($result = $this->dbi->query($sql)) {
            $c = 0;
            while($myrow = $result->fetch_assoc()) {
            $str .= ($c ? ", " : '<div class="uk-padding-small uk-padding-remove-right'.($test ? ' uk-padding-remove-left' : '').'"><h3>Allied Staff</h3>') . $myrow['name'];
            $c++;
            }
            if($c) $str .= '</div>';
            }

            $str .= '</div>';



            }
            }




            }

            }
            */
            return $str;
        }

        function UserCard($user_id_in = "", $view_mode = "") {


            $main_folder = $this->f3->get('main_folder');
            $user_id = ($user_id_in ? $user_id_in : (isset($_GET['uid']) ? $_GET['uid'] : null));
            $user_access = (isset($_GET['user_access']) ? $_GET['user_access'] : null);
            $sid = (isset($_GET['sid']) ? $_GET['sid'] : null);
            $parent_site_id = (isset($_GET['parent_site_id']) ? $_GET['parent_site_id'] : null);

            $client_user = $this->f3->get('is_client');
            $site_user = $this->f3->get('is_site');
            $client_or_site_user = $client_user + $site_user;
            $view_details = new data_list;
            $view_details->dbi = $this->dbi;

            if ($parent_site_id) {

                $str .= $this->UserManager(0, $parent_site_id);
            } else if ($sid) {
                $sql = "select rosters.id as `idin`, companies.item_name as `Division`, start_date as `Start Date`, CONCAT('<a target=\"_top\" class=\"list_a\" href=\"Rostering/EditRoster?rid=', rosters.id, '\">Open</a>') as `Open` from rosters
        left join companies on companies.id = rosters.division_id
        where site_id = $sid order by start_date DESC";
                //return "<textarea>$sql</textarea>";
                $x = 0;
                $str = "<h5>Rosters</h5>";

                $view_details->sql = $sql;
                $str .= $view_details->draw_list();
            } else if ($user_id && $user_access) {
                $str = $this->UserAccess($user_id);
            } else if ($user_id) {

                $divisions = $this->get_divisions($user_id);

                $str .= '
                    <style>
                        .cell_wrap {
                        float: left;
                        display: inline-block;
                        padding: 0px;
                        border: 1px solid #DDDDDD;
                        }
                        .cell_head {
                        padding: 6px;
                        background-color: white;
                        }
                        .cell_foot {
                        padding: 6px;
                        background-color: #F9F9F9;
                        border-top: 1px solid #DDDDDD;
                        }
                    </style> 
                    
                    ';

                $sql = "select users.id as `uid`, users.name, users.surname, users.phone, users.phone2, users.user_level_id, users.email, users.email2, CONCAT(users.address, ' ', users.suburb, ' ', states.item_name, ' ', users.postcode) as `address`, states.item_name as `state`, users.state, users.dob from users left join states on states.id = users.state where users.id = $user_id";
                $result = $this->dbi->query($sql);
                if ($myrow = $result->fetch_assoc()) {
                    $uid = $myrow['uid'];
                    $employee_id = $myrow['employee_id'];
                    $user_level = $myrow['user_level_id'];
                    $name = $myrow['name'];
                    $surname = $myrow['surname'];
                    $phone = $myrow['phone'];
                    $phone2 = $myrow['phone2'];
                    $email = $myrow['email'];
                    $email2 = $myrow['email2'];
                    $address = $myrow['address'];
                    $dob = $myrow['dob'];
                    $state = $myrow['state'];

                    // $is_staff = ($user_level >= 300 ? 1 : 0);

                    // Determine user type based on user_level
                    if ($user_level == 10) {
                        $user_type = "Client";
                    } elseif ($user_level >= 300) {
                        $user_type = "Staff";
                    } else {
                        $user_type = "Site";
                    }

                    if ($user_level < 300) {
                        $is_client = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 104 and foreign_id = " . $user_id);
                        $is_site = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 384 and foreign_id = " . $user_id);
                        $is_client_staff = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 504 and foreign_id = " . $user_id);
                        $is_mall = $this->num_results("SELECT id FROM `lookup_answers` where table_assoc = 'users' and lookup_field_id = 2152 and foreign_id = " . $user_id);
                        $has_sites = ($is_client ? $this->num_results("SELECT associations.id from associations where association_type_id = 1 and parent_user_id = " . $user_id) : 0);
                    }


                    // $this->f3->set('user_type', ($user_level >= 300 ? "STAFF" : "SITE"));
                    // $str .= "<h5>" . ($is_staff ? "Staff" : "Site") . ($view_mode ? " Details" : " Card") . (isset($_GET['show_min']) ? "" : " for $name $surname") . " ($divisions) </h5>";
                    // Set the user_type in the F3 instance
                    $this->f3->set('user_type', strtoupper($user_type));
                    $str .= "<h5>" . $user_type . ($view_mode ? " Details" : " Card") . (isset($_GET['show_min']) ? "" : " for $name $surname") . " ($divisions) </h5>";
                    //die($str);
                    //echo $view_mode;
                    //die;
                    if ($view_mode) {
                        if ($client_user) {

                            $allow_access = $this->num_results("select child_user_id from associations where association_type_id = 1 and child_user_id = $user_id and parent_user_id = {$_SESSION['user_id']} ");
                        } else if ($site_user) {

                            $allow_access = ($user_id == $_SESSION['user_id'] ? 1 : 0);
                        } else {
                            $allow_access = 1;
                        }
                        if ($allow_access) {

                            $flder = $this->f3->get('download_folder') . "user_files/";
                            $img_file = "$flder$uid/profile.jpg";
                            $show_img = "user_files/$uid/profile.jpg";

                            if (file_exists($img_file)) {
                                $str .= '<div class="cell_wrap"><div class="cell_head"><a target="_blank" href="' . $main_folder . 'Image?i=' . urlencode($this->encrypt($show_img)) . '"><img style="max-width: 200px" src="' . $main_folder . 'Image?i=' . urlencode($this->encrypt($show_img)) . '" /></a></div></div>';
                            }
                            if ($phone)
                                $str .= '<div class="cell_wrap"><div class="cell_head">Phone</div><div class="cell_foot">' . $phone . '</div></div>';
                            if ($phone2)
                                $str .= '<div class="cell_wrap"><div class="cell_head">Phone 2</div><div class="cell_foot">' . $phone2 . '</div></div>';
                            if ($email)
                                $str .= '<div class="cell_wrap"><div class="cell_head">Email</div><div class="cell_foot"><a href="mailto:' . $email . '">' . $email . '</a></div></div>';
                            if ($email2)
                                $str .= '<div class="cell_wrap"><div class="cell_head">Email 2</div><div class="cell_foot"><a href="mailto:' . $email2 . '">' . $email2 . '</a></div></div>';
                            if ($address)
                                $str .= '<div class="cell_wrap"><div class="cell_head">Address</div><div class="cell_foot">' . $address . '</div></div>';
                            if ($is_staff && $dob && $dob != '0000-00-00')
                                $str .= '<div class="cell_wrap"><div class="cell_head">DOB</div><div class="cell_foot">' . date('d-M-Y', strtotime($dob)) . '</div></div>';

                            $sql = "select user_group_id as `id` from users_user_groups where user_id = '$user_id';";
                            if ($result = $this->dbi->query($sql)) {
                                $c = 0;
                                while ($myrow = $result->fetch_assoc()) {
                                    $card_lids .= ($c ? "," : "") . $myrow['id'];
                                    $c++;
                                }
                            }
                            $sql = "
                SELECT lookups.id as `lookup_id`, lookups.item_name as `lookup`, lookups.description
                FROM `lookup_answers`
                left join lookups on lookup_answers.foreign_id = lookups.id
                WHERE lookup_answers.lookup_field_id in ($card_lids) and lookup_answers.table_assoc = 'lookups'
                group by lookups.id
                order by lookups.sort_order
                ";
                            //return $sql;
                            $result = $this->dbi->query($sql);
                            $x = 0;
                            if ($result) {
                                while ($myrow = $result->fetch_assoc()) {
                                    $lookup_id = $myrow['lookup_id'];
                                    $lookup = $myrow['lookup'];
                                    $description = $myrow['description'];
                                    $sql = "select meta_key, meta_value from usermeta where meta_key = $lookup_id and user_id = $user_id";
                                    $result2 = $this->dbi->query($sql);
                                    if ($myrow2 = $result2->fetch_assoc()) {
                                        $mkey = $myrow2['meta_key'];
                                        $mval = $myrow2['meta_value'];
                                        if ($mval && $mval != '0000-00-00')
                                            $str .= '<div class="cell_wrap"><div class="cell_head">' . $description . '</div><div class="cell_foot">' . $mval . '</div></div>';
                                    }
                                }
                            }

                            $str .= '<div class="cl"></div>';

                            if ($is_staff) {
                                $view_details->title = "Roster Times";
                                $view_details->sql = "
                select roster_times.id as `idin` ,
                CONCAT(users2.name, ' ', users2.surname) as `Site`,
                DATE_FORMAT(roster_times.start_time_date, '%a, %d-%b-%Y') as `Start Date`,
                DATE_FORMAT(roster_times.start_time_date, '%H:%i') as `Start Time`,
                DATE_FORMAT(roster_times.finish_time_date, '%H:%i') as `Finish Time`,
                CONCAT(ABS(ROUND(TIME_TO_SEC(TIMEDIFF(roster_times.start_time_date, roster_times.finish_time_date)) / 3600, 2)), if(roster_times.minutes_unpaid != 0, CONCAT(' (', roster_times.minutes_unpaid, 'm Unpaid)'), '')) as `Hours`
                from roster_times_staff
                inner join roster_times on roster_times.id = roster_times_staff.roster_time_id
                inner join rosters on rosters.id = roster_times.roster_id and rosters.is_published >= 1
                left join users2 on users2.id = rosters.site_id
                where roster_times_staff.staff_id = $user_id
                order by rosters.start_date DESC LIMIT 10
                ";
                                $str .= $view_details->draw_list();
                            } else {
                                if ($is_client) {
                                    $sql = "select users.name as `Site`, users.phone as `Phone`, users.email as `Email`, CONCAT(users.address, ' ', users.suburb, ' ', states.item_name, ' ', users.postcode) as `Address` from associations 
                    inner join users on users.id = associations.child_user_id
                    left join states on states.id = users.state
                    where association_type_id = 1 and parent_user_id = $user_id and users.user_status_id = 30";
                                    //return $view_details->sql;
                                    //$view_details->sql = $sql;
                                    //if($test = $view_details->draw_list()) $str .= '<div class="uk-padding-small uk-padding-remove-left"><h5>Sites</h5>' . $test . '</div>';
                                    /*                $result = $this->dbi->query($sql);
                                    if($result = $this->dbi->query($sql)) {
                                    $c = 0;
                                    while($myrow = $result->fetch_assoc()) {
                                    $str .= ($c ? ", " : "<h5>Sites</h5>") . $myrow['name'];
                                    $c++;
                                    }
                                    } */
                                }
                                if ($is_site) {
                                    $itm = new input_item;
                                    $itm->hide_filter = 1;
                                    $str .= $itm->setup_cal();
                                    $str .= '<input type="hidden" name="calByDate" id="calByDate" />';
                                    $str .= "
                    <script>
                    function cal_nav(url, id) {
                        date = document.getElementById('calDate' + id).value
                        //document.getElementById('calByDate').value = date
                        url += '&calByDate=' + date
                        window.open(url, '_blank')
                        //alert(url)
                    }
                    </script>";

                    
                    $sql = "SELECT clientTotalHours FROM maxhours WHERE staff_id = '$user_id'";
                    $result = $this->dbi->query($sql);
                    
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $clientTotalHours = $row['clientTotalHours'];
                    
                        $str .= '<div class="cl">
                                    <div class="cell_wrap">
                                        <div class="cell_head">
                                            Max Hours
                                        </div>
                                        <div class="cell_foot">
                                            <span class="total_max_hours">Total Max Hours: ' . $clientTotalHours . '</span>
                                            <a target="fraDetails" class="list_a" href="MaxHours?show_min=1&uid=' . $user_id . '">Edit Max Hours</a>

                                        </div>
                                    </div>
                                </div><br><br><br><br>';
                    } else {
                        // Handle the case where the query fails
                        $str .= '<div class="cl">
                                    <div class="cell_wrap">
                                        <div class="cell_head">
                                            Max Hours
                                        </div>
                                        <div class="cell_foot">
                                        <span class="total_max_hours">Error fetching total max hours</span>
                                        <a target="fraDetails" class="list_a" href="MaxHours?show_min=1&uid=' . $user_id . '">Edit Max Hours</a>
                                        </div>
                                    </div>
                                </div><br><br><br><br>';
                    
                        // Debugging statement
                        echo "Query failed: " . $this->dbi->error;
                    }
                

                                    $str .= '<div class="uk-flex ">';
                                    $sql = "select rosters.id as `idin`, CONCAT('<nobr>', companies.item_name, '</nobr>') as `Division`, start_date as `Start Date`, CONCAT('<a target=\"_top\" class=\"list_a\" href=\"Rostering/ViewRoster?rid=', rosters.id, '\">Open</a>') as `Open` from rosters left join companies on companies.id = rosters.division_id
                    where site_id = $user_id order by start_date DESC LIMIT 5";
                                    $view_details->sql = $sql;
                                    if ($test = $view_details->draw_list())
                                        $str .= '<div class="uk-padding-small uk-padding-remove-left"><h5>Latest Rosters</h5>' . $test . '</div>';

                                    if ($address) {
                                        $str .= '<div class="uk-padding-small uk-padding-remove-left"><h5>Map</h5>'
                                                . '<iframe width="400" height="300" id="gmap_canvas" src="https://maps.google.com/maps?q=' . urlencode($address) . '&t=&z=13&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe></div>';
                                    }
                                    $sql = "select CONCAT(users.name, ' ', users.surname) as `name` from associations 
                    inner join users on users.id = associations.child_user_id
                    where association_type_id = 4 and parent_user_id = $user_id and users.user_status_id = 30";
                                    //return $sql;
                                    if ($result = $this->dbi->query($sql)) {
                                        $c = 0;
                                        while ($myrow = $result->fetch_assoc()) {
                                            $str .= ($c ? ", " : '<div class="uk-padding-small uk-padding-remove-right uk-padding-remove-left"><h5>Allied Staff</h5>') . $myrow['name'];
                                            $c++;
                                        }
                                    }
                                    $sql = "select CONCAT(users.name, ' ', users.surname) as `name` from associations 
                    inner join users on users.id = associations.child_user_id
                    where association_type_id = 12 and parent_user_id = $user_id and users.user_status_id = 30";
                                    //return $sql;
                                    if ($result = $this->dbi->query($sql)) {
                                        $c = 0;
                                        while ($myrow = $result->fetch_assoc()) {
                                            $str .= ($c ? ", " : '<div class="uk-padding-small uk-padding-remove-right uk-padding-remove-left"><h5>Site Staff</h5>') . $myrow['name'];
                                            $c++;
                                        }
                                    }
                                    $day_count = 15;
                                    $titles = Array("Occurrence Log", "Whiteboard");
                                    $links = Array("OccurrenceLog", "GetSiteNotes");
                                    for ($x == 0; $x < 2; $x++) {

                                        $url_nav = 'Page/' . $links[$x] . '?pdf=1&lookup_id=' . $user_id;

                                        $date_nav = $itm->cal("calDate" . ($x + 1), "", ' onChange="cal_nav(\'' . $url_nav . '\', ' . ($x + 1) . ')" class="small_textbox" title="Select a Date for the ' . $titles[$x] . ' Here" placeholder="' . $titles[$x] . ' Date" ', "", "", "");

                                        $str .= "<h5>Download {$titles[$x]}s " . $date_nav . "</h5>";
                                        for ($i = 0; $i <= $day_count; $i++) {
                                            $str .= '<a class="division_nav_item" target="_blank" uk-tooltip="title: Download ' . $titles[$x] . ' from<br />' . ($i == 0 ? 'Today' : ($i == 1 ? 'Yesterday' : "$i Days Ago")) . '." href="Page/' . $links[$x] . '?pdf=1&days=' . -$i . '&lookup_id=' . $user_id . '">' . ($i == 0 ? 'Today' : ($i == 1 ? 'Yesterday' : $i)) . '</a>';
                                        }
                                    }

                                    $str .= '</div></div></div>';
                                    $str .= '<div class="cl"></div>';

                                    $view_details->title = "Latest Reports";
                                    $view_details->sql = "
                    select 
                    compliance_checks.id as idin,
                    CONCAT('<a target=\"_blank\" uk-tooltip=\"title: View as PDF\" class=\"list_a\" href=\"{$main_folder}CompliancePDF/', compliance_checks.id, '\">PDF</a>') as `***`,
                    CONCAT('<a uk-tooltip=\"title: View This Report Online\" class=\"list_a\" target=\"_blank\" href=\"{$main_folder}Compliance?report_view_id=', compliance_checks.id, '\">View Online</a>') as `View`,
                    CONCAT(if(users.employee_id != '', users.employee_id, ''), ' ', if(users.client_id != '', users.client_id, ''), ' ', users.name, ' ', users.surname) as `Assessor`,
                    CONCAT(users2.name, ' ', users2.surname) as `Site`,
                    CONCAT('<nobr>', DATE_FORMAT(compliance_checks.check_date_time, '%d-%b-%Y'), '</nobr>') as `Date`, lookup_fields1.item_name as `Status`,
                    if(compliance_checks.total_out_of = 0, 'N/A', CONCAT((select sum(value) from compliance_check_answers where compliance_check_id = compliance_checks.id), '/', compliance_checks.total_out_of)) as `Score`
                    FROM compliance_checks
                    left join lookup_fields1 on lookup_fields1.id = compliance_checks.status_id
                    left join compliance on compliance.id = compliance_checks.compliance_id
                    left join users on users.id = compliance_checks.assessor_id
                    left join users2 on users2.id = compliance_checks.subject_id
                    left join compliance_check_answers on compliance_check_answers.compliance_check_id = compliance_checks.id
                    where users2.id = $user_id and compliance.min_user_level_id <= " . $_SESSION['u_level'] . " and compliance.is_active = 1 and compliance.category_id not in (select id from lookup_fields where item_name LIKE '%request%')
                    or users2.id in (select child_user_id from associations where association_type_id = 1 and parent_user_id = $user_id)
                    group by compliance_checks.id
                    order by check_date_time desc
                    LIMIT 5
                    ";
                                    //return "<textarea>{$view_details->sql}</textarea>";
                                    $str .= $view_details->draw_list();
                                    $view_details->title = "Notes";
                                    $view_details->sql = "
                        select user_notes.id as idin,
                        user_notes.date as `Date`,
                        user_notes.description as `Description`, CONCAT(users.name, ' ', users.surname) as `Edited By`
                        FROM user_notes
                        left join users on users.id = user_notes.edited_by
                        where user_id = $user_id
                        order by date DESC
                        LIMIT 5
                    ";
                                    $str .= $view_details->draw_list();

                                    /* $str .= '
                                    <a target="fraDetails" class="dir_item" href="Rostering/RequiredLicences?show_min=1&lookup_id='.$user_id.'">Rqd Licences</a>
                                    <a target="fraDetails" class="dir_item" href="Page/GetSiteNotes?show_min=1&lookup_id='.$user_id.'">Whiteboard</a>
                                    <a target="fraDetails" class="dir_item" href="Page/Associations?show_min=1&lookup_id=4&parent_id='.$user_id.'">Staff On Site</a>
                                    <a target="fraDetails" class="dir_item" href="Edit/OpeningClosing?show_min=1&lookup_id='.$user_id.'">Welfare Start/Finish</a>
                                    <a target="fraDetails" class="dir_item" href="UserCard?show_min=1&sid='.$user_id.'">Rosters</a>
                                    '; */
                                }
                            }
                        }
                    } else {
                        
                        if(!$this->isClientUserLevel($_SESSION['u_level']))
                        {
                        $str .= $this->UserManager($user_id); 
                        }
                        
                        
    //          <a target="fraDetails" class="dir_item" href="Resources?show_min=1&show_upload_form=1&current_dir=user_files&current_subdir='.$user_id.'">Files</a>
                        $str .= '
            <div class="reverse_wrap" style="margin-top: 15px;">
            <a target="fraDetails" class="dir_item" href="ViewCard?show_min=1&uid=' . $user_id . '">View Card</a>
            <a target="fraDetails" class="dir_item" href="FileManager?show_min=1&uid=' . $user_id . '">Files</a> 
            <a target="fraDetails" class="dir_item" href="BookService?show_min=1&uid=' . $user_id . '">Guard Booking Service</a>
            <a target="fraDetails" class="dir_item" href="Ticket?show_min=1&uid=' . $user_id . '">Service Tickets</a>
            ';
                        
                    if(!$this->isClientUserLevel($_SESSION['u_level']))
                    {
                        $str .= '
            <a target="fraDetails" class="dir_item" href="Edit/UserNotes?show_min=1&lookup_id=' . $user_id . '">Site Visit Notes</a>
            ';
        
                        
                        if ($is_staff) {
                            $str .= '
                <a target="fraDetails" class="dir_item" href="ManageSiteManager?show_min=1&select_site=1&lookup_id=' . $user_id . '">Sites Working At</a>
                <a target="fraDetails" class="dir_item" href="PerformanceReview?show_min=1&uid=' . $user_id . '">Performance Review</a>
                <a target="fraDetails" class="dir_item" href="Training/Matrix?show_min=1&lookup_id=' . $user_id . '">Training Matrix</a>
                <a target="fraDetails" class="dir_item" href="Assets/Allocated?show_min=1&lookup_id=' . $user_id . '">Uniform/Equipment</a>
                ';
                            /* $l_folder = $this->f3->get('download_folder') . 'licences/' . $user_id;
                            if(!file_exists($l_folder)) {
                            mkdir($l_folder);
                            chmod($l_folder, 0755);
                            }
                            //$str .= "test";

                            if(file_exists($l_folder)) $str .= '<a target="fraDetails" class="dir_item" href="FileManager?show_min=1&pf=licences/'.$user_id.'">Licence Files</a>'; */
                        }
                        $str .= '<a target="fraDetails" class="dir_item" href="Licencing/LicenceManagement?show_min=1&lookup_id=' . $user_id . '">Licences</a>';

                            if ($is_client) {
                            $str .= '
                <a target="fraDetails" class="dir_item" href="Page/Associations?show_min=1&lookup_id=1&parent_id=' . $user_id . '">Associated Sites</a>
                ';
                        }
    //  <a target="fraDetails" class="dir_item" href="FileManager?show_min=1&uid=' . $user_id . '">Equipments</a>
                        if ($is_site) {
                            $str .= '
                <a target="fraDetails" class="dir_item" href="Rostering/RequiredLicences?show_min=1&lookup_id=' . $user_id . '">Rqd Licences</a>
                <a target="fraDetails" class="dir_item" href="FileManager?show_min=1&uid=' . $user_id .'/SWMS+%26+JAS">SWMS & JSA</a>
                <a target="fraDetails" class="dir_item" href="Page/GetSiteNotes?show_min=1&lookup_id=' . $user_id . '">Whiteboard</a>
                <a target="fraDetails" class="dir_item" href="Page/OccurrenceLog?show_min=1&lookup_id=' . $user_id . '">Occ Log</a>
                <a target="fraDetails" class="dir_item" href="Page/Associations?show_min=1&lookup_id=4&parent_id=' . $user_id . '">Allied Staff</a>
                <a target="fraDetails" class="dir_item" href="UserCard?show_min=1&parent_site_id=' . $user_id . '">Staff Details</a>
            
                <a target="fraDetails" class="dir_item" href="Page/Associations?show_min=1&lookup_id=12&parent_id=' . $user_id . '">Own Staff</a>
                <a target="fraDetails" class="dir_item" href="Page/Associations?show_min=1&lookup_id=11&parent_id=' . $user_id . '">Banned Staff</a>
                <a target="fraDetails" class="dir_item" href="Edit/OpeningClosing?show_min=1&lookup_id=' . $user_id . '">WFC</a>
                <a target="fraDetails" class="dir_item" href="Assets/Manager?show_min=1&sid=' . $user_id . '">Assets</a>
                <a target="fraDetails" class="dir_item" href="Reporting/OccurrenceReports?show_min=1&sid=' . $user_id . '">Occ Report</a>
                <a target="fraDetails" class="dir_item" href="Tasking/ViewPeriodicals?show_min=1&sid=' . $user_id . '">Periodicals</a>
                <a target="fraDetails" class="dir_item" href="UserCard?show_min=1&sid=' . $user_id . '">Rosters</a>
                <a target="fraDetails" class="dir_item" href="ContractHours?show_min=1&uid=' . $user_id . '">Contracted Hours</a>
                <a target="fraDetails" class="dir_item" href="PestPeriodicals?show_min=1&uid=' . $user_id . '">Pest Periodicals</a>
                <a target="fraDetails" class="dir_item" href="FacilitiesPeriodicals?show_min=1&uid=' . $user_id . '">Facilities Periodicals</a>
                <a target="fraDetails" class="dir_item" href="BookService?show_min=1&uid=' . $user_id . '">Guard Booking Service</a>
                <a target="fraDetails" class="dir_item" href="Ticket?show_min=1&uid=' . $user_id . '">Service Tickets</a>
                ';
                        }
                        $str .= '<a target="fraDetails" class="dir_item" href="UserCard?show_min=1&user_access=1&uid=' . $user_id . '">Access</a>';
                        $str .= '<a target="fraDetails" class="dir_item" href="AccountDetails?a=1&show_min=1&uid=' . $user_id . '">Further Details</a>';
                    }
                        $str .= '</div>';
                    }
                }

                $this->f3->set('user_id', $user_id);
            } else {
                $str = "";
            }
            //prd($str);
            return $str;
        }

        public function saveUserData($insertField, $selUserType) {

            if ($selUserType == '2435') {
                $clientField = array('name', 'phone', 'fax', 'user_maintype', 'email', 'email2', 'url', 'address', 'state', 'suburb', 'abn', 'abn_no_required', 'postcode', 'commencement_date', 'user_status_id', 'user_level_id', 'client_id');
                $fieldH = array();
                $valueA = array();

                foreach ($insertField as $keyh => $valueh) {
                    if (in_array($keyh, $clientField)) {
                        if ($valueh == "") {
                            
                        } else {
                            $fieldH[] = $keyh;
                            $valueA[] = "'" . $valueh . "'";
                        }
                    }
                }
            } else if ($selUserType == '2436') {
                
                $clientField = array('name', 'preferred_name', 'abn', 'user_maintype',
                    'address', 'state', 'suburb', 'latitude', 'longitude', 'postcode', 'commencement_date',
                    'site_contact_name1', 'site_contact_position1', 'site_contact_phone1', 'site_contact_mobile1', 'site_contact_email1',
                    'site_contact_name2', 'site_contact_position2', 'site_contact_phone2', 'site_contact_mobile2', 'site_contact_email2',
                    'site_contact_name3', 'site_contact_position3', 'site_contact_phone3', 'site_contact_mobile3', 'site_contact_email3',
                    'user_status_id', 'user_level_id', 'client_id',
                    'civil_manager_id', 'civil_manager_id2','civil_manager_id3',
                    'civil_manager_email','civil_manager_name','civil_manager_mobile',
                    'civil_manager_name2','civil_manager_mobile2','civil_manager_email2',
                    'civil_manager_name3','civil_manager_mobile3','civil_manager_email3',
                    'facilities_manager_id', 'facilities_manager_id2','facilities_manager_id3',
                    'facilities_manager_name','facilities_manager_email','facilities_manager_mobile',
                    'facilities_manager_name2','facilities_manager_email2','facilities_manager_mobile2',
                    'facilities_manager_name3','facilities_manager_email3','facilities_manager_mobile3',
                    'pest_manager_id','pest_manager_id2','pest_manager_id3',
                    'pest_manager_name','pest_manager_email','pest_manager_mobile',
                    'pest_manager_name2','pest_manager_email2','pest_manager_mobile2',
                    'pest_manager_name3','pest_manager_email3','pest_manager_mobile3',
                    'security_manager_id','security_manager_id2','security_manager_id3',
                    'security_manager_name','security_manager_email','security_manager_mobile',
                    'security_manager_name2','security_manager_email2','security_manager_mobile2',
                    'security_manager_name3','security_manager_email3','security_manager_mobile3',
                    'traffic_manager_id','traffic_manager_id2','traffic_manager_id3',
                    'traffic_manager_name','traffic_manager_email','traffic_manager_mobile',
                    'traffic_manager_name2','traffic_manager_email2','traffic_manager_mobile2',
                    'traffic_manager_name3','traffic_manager_email3','traffic_manager_mobile3');
                $fieldH = array();
                $valueA = array();

                foreach ($insertField as $keyh => $valueh) {
                    if (in_array($keyh, $clientField)) {
                        if ($valueh == "") {
                            
                        } else {
                            $fieldH[] = $keyh;
                            $valueA[] = "'" . $valueh . "'";
                        }
                    }
                }
            } else if ($selUserType == '2437') {

                $clientField = array('name', 'user_maintype', 'middle_name', 'surname', 'sex', 'dob', 'preferred_name', 'phone', 'phone2', 'email',
                    'address', 'state', 'suburb', 'postcode', 'latitude', 'longitude', 'commencement_date', 'emergency_contact_full_name', 'emergency_contact_relationship', 'emergency_contact_mobile',
                    'tax_file_number', 'bank_name', 'bsb_number', 'account_number', 'super_name', 'super_number', 'document1_type', 'document2_type', 'document3_type',
                    'working_for_provider', 'provider_id',
                    'user_status_id', 'user_level_id', 'employee_id');
                $fieldH = array();
                $valueA = array();

                foreach ($insertField as $keyh => $valueh) {
                    if (in_array($keyh, $clientField)) {
                        if ($valueh == "") {
                            
                        } else {
                            $fieldH[] = $keyh;
                            $valueA[] = "'" . $valueh . "'";
                        }
                    }
                }
            } else if ($selUserType == '2438') {
                $clientField = array('name', 'middle_name', 'surname', 'user_maintype', 'sex', 'dob', 'preferred_name', 'phone', 'phone2', 'email',
                    'address', 'state', 'suburb', 'latitude', 'longitude', 'postcode', 'commencement_date', 'emergency_contact_full_name', 'emergency_contact_relationship', 'emergency_contact_mobile',
                    'tax_file_number', 'bank_name', 'bsb_number', 'account_number', 'super_name', 'super_number', 'document1_type', 'document2_type', 'document3_type',
                    'manager_incharge_name', 'manager_incharge_mobile', 'manager_incharge_email', 'manager_incharge2_name', 'manager_incharge2_mobile', 'manager_incharge2_email',
                    'user_status_id', 'user_level_id', 'supplier_id');
                $fieldH = array();
                $valueA = array();

                foreach ($insertField as $keyh => $valueh) {
                    if (in_array($keyh, $clientField)) {
                        if ($valueh == "") {
                            
                        } else {
                            $fieldH[] = $keyh;
                            $valueA[] = "'" . $valueh . "'";
                        }
                    }
                }
            } else {
                $clientField = array('name', 'phone', 'fax', 'user_maintype', 'email', 'email2', 'url', 'address', 'state', 'suburb', 'postcode', 'commencement_date', 'user_status_id', 'user_level_id', 'client_id');
                $fieldH = array();
                $valueA = array();

                if (in_array($keyh, $clientField)) {
                    if ($valueh == "") {
                        
                    } else {
                        $fieldH[] = $keyh;
                        $valueA[] = "'" . $valueh . "'";
                    }
                }
            }

            $fieldHS = implode(',', $fieldH);
            $valueAS = implode(',', $valueA);
            $sql = "insert into users ($fieldHS) "
                    . "values ($valueAS)";

            $this->dbi->query($sql);
            //print_r($this->dbi->error);


            $last_id = $this->dbi->insert_id;
            //      print_r($last_id);
            // die;
            return $last_id;
        }

        function saveUserLicenseData($user_id, $selLicenceType1, $txtLicenceNumber1, $txtLicenceClass1, $calExpiryDate1, $selLicenceState1, $selCompliance1, $docImg1, $docImg2, $selUserType) {

            if ($calExpiryDate1) {
                $obj_date = DateTime::createFromFormat('d-M-Y', $calExpiryDate1);
                $exp_date = $obj_date->format('Y-m-d');
            } else {
                $exp_date = "0000-00-00";
            }
            //prd($selUserType." ".$selLicenceType1);
            if ($selUserType == 2437 && $selLicenceType1) {
                $sql = "insert into licences (user_id,licence_type_id,licence_number,licence_class,expiry_date,state_id,licence_compliance_id) "
                        . "values ('$user_id','$selLicenceType1','$txtLicenceNumber1','$txtLicenceClass1','$exp_date','$selLicenceState1','$selCompliance1')";
                $this->dbi->query($sql);
                //prd($this->dbi->error);
                $last_id = $this->dbi->insert_id;
            } else if ($selUserType == 2438 && $selLicenceType1) {
                $sql = "insert into licences (user_id,licence_type_id,licence_number,licence_class,expiry_date,state_id,licence_compliance_id) "
                        . "values ('$user_id','$selLicenceType1','$txtLicenceNumber1','$txtLicenceClass1','$exp_date','$selLicenceState1','$selCompliance1')";

                $this->dbi->query($sql);
                $last_id = $this->dbi->insert_id;
            } else {
                $last_id = 0;
            }

            if ($last_id) {
                $flder = $this->f3->get('download_folder') . "licences/";

                if ($docImg1) {
                    $folder = "$flder$last_id";
                    if (!file_exists($folder)) {
                        mkdir($folder);
                        chmod($folder, 0755);
                    }

                    //save image
                    $licImg1 = str_replace(' ', '+', $docImg1);
                    $licImg1 = substr($licImg1, strpos($licImg1, ",") + 1);
                    $data = base64_decode($licImg1);
                    //$img_name = basename($_POST['hdnFileName']);
                    $img_name = 'licence.jpg';
                    $file = "$folder/$img_name";
                    $success = file_put_contents($file, $data);
                }
                if ($docImg2) {
                    $folder = "$flder$last_id";
                    if (!file_exists($folder)) {
                        mkdir($folder);
                        chmod($folder, 0755);
                    }
                    //echo $docImg2;
                    //die;
                    //save image
                    $licImg2 = str_replace(' ', '+', $docImg2);
                    $licImg2 = substr($licImg2, strpos($licImg2, ",") + 1);
                    $data = base64_decode($licImg2);
                    //$img_name = basename($_POST['hdnFileName']);
                    $img_name = 'licence2.jpg';
                    $file = "$folder/$img_name";
                    $success = file_put_contents($file, $data);
                }
                //die;
            }
        }

        function alliedDivision($divisionId) {


            $form_obj = new input_form;
            $form_obj->table = "users";
            $form_obj->dbi = $this->dbi;

            $divisionIdArray = explode(',', $divisionId);
            $group[0] = 0;
            $group[1] = 0;
            $group[2] = 0;
            $group[3] = 0;
            $group[4] = 0;

            foreach ($divisionIdArray as $key => $value) {

                $group[$key + 1] = $value;
            }
            if ($group[2] == 0) {
                $group[0] = 0;
            } else {
                $group[0] = 384;
            }

            //$divisionId  = implode(",",$divisionIdArray);
            // if($divisionId)

            $site_sql = $this->user_select_dropdown($group[0], $group[1], $group[2], $group[3], $group[4]);
            $styleMulti = 'style="width: 500px;height: 150px !important;"';
            $formAttributeArr[] = array("mslParentSite", "Main Location", "parent_site", $site_sql, "multiple " . $styleMulti, "n", "");
            $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
            //print_r($resultV); die;
            $form_obj->form_attributes = $resultV;

            $form_obj->form_template = '
                <nobr>tmslParentSite</nobr><span class="mslParentSign" style="display:none;color:green">
                <i class="fa fa-check" aria-hidden="true"></i>
        </span><br />mslParentSite<br />
        </div><br />';

            $str = $form_obj->display_attribute() . "<br />";

            return $str;
        }

        function alliedDivisionOld($divisionId) {


            $form_obj = new input_form;
            $form_obj->table = "users";
            $form_obj->dbi = $this->dbi;

            $divisionIdArray = explode(',', $divisionId);
            $group[0] = 0;
            $group[1] = 0;
            $group[2] = 0;
            $group[3] = 0;
            $group[4] = 0;

            foreach ($divisionIdArray as $key => $value) {

                $group[$key + 1] = $value;
            }
            if ($group[2] == 0) {
                $group[0] = 0;
            } else {
                $group[0] = 384;
            }

            //$divisionId  = implode(",",$divisionIdArray);
            // if($divisionId)

            $site_sql = $this->user_select_dropdown($group[0], $group[1], $group[2], $group[3], $group[4]);
            $styleMulti = 'style="width: 500px;height: 150px !important;"';
            $formAttributeArr[] = array("mslParentSite", "Main Location", "parent_site", $site_sql, "multiple " . $styleMulti, "n", "");
            $resultV = $form_obj->formAttributesVerticle($formAttributeArr);
            //print_r($resultV); die;
            $form_obj->form_attributes = $resultV;
            $form_obj->button_attributes = array(
                array("Add New", "Save", "Reset"),
                array("cmdAdd", "cmdSave", "cmdReset"),
                array("if(add()) this.form.submit()", "if(save()) this.form.submit()", "this.form.reset(); new_record(); this.form.submit();"),
                array("js_function_new", "js_function_add", "js_function_save", "js_function_delete", "js_function_edit")
            );
            $form_obj->form_template = '<div class="fl section1 user3 user4" id="parent_site">
                <nobr>tmslParentSite</nobr><span class="mslParentSign" style="display:none;color:green">
                <i class="fa fa-check" aria-hidden="true"></i>
        </span><br />mslParentSite<br />
        </div>';

            $str = $form_obj->display_form() . "<br />";
    //           echo $str;
    //           die;

            return $str;
        }


        function Support() {
            $f3 = Base::instance();
            $template = new Template;
            echo $template->render('support.htm');
        }

        function ContractHours() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $timesheetData = $db->exec('SELECT * FROM contractedhours');
            $template = new Template;
            echo $template->render('manualtime.htm');
        }

        function BookService() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $serviceData = $db->exec('SELECT * FROM bookservice');
            $template = new Template;
            echo $template->render('bookservice.htm');
        }

        function MaxHours() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $maxHourData = $db->exec('SELECT * FROM maxhours');
            $template = new Template;
            echo $template->render('maxhours.htm');
        }

        function PestPeriodicals() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $pestPeriodicalData = $db->exec('SELECT * FROM pestperiodicals');
            $template = new Template;
            echo $template->render('pestPeriodicals.htm');
        }

        function FacilitiesPeriodicals() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $facilitiesPeriodicalData = $db->exec('SELECT * FROM facilitiesperiodicals');
            $template = new Template;
            echo $template->render('facilitiesPeriodicals.htm');
        }

        function ManualTimesheet() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $timesheetData = $db->exec('SELECT * FROM manual_timesheet');
            $template = new Template;
            echo $template->render('manualtime.htm');
        }

        function allPeriodicals() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $periodicalData = $db->exec('SELECT * FROM pestperiodicals');
            $template = new Template;
            echo $template->render('allPeriodicals.htm');
        }

        
        function ManualTimesheets() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
        
            // Build and execute the SQL query
            $sql = 'SELECT u.*, u.name
                    FROM users AS u
                    JOIN manual_timesheet AS m
                    ON u.client_id = m.staff_id';
            $timesheetData = $db->exec($sql);
        
            // Assuming you want to render the result, you might want to pass the data to the template
            $template = new Template;
            echo $template->render('manualtimesheet.htm');
        }

        function Ticket() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
        
            // Build and execute the SQL query
        /*  $sql = 'SELECT u.*, u.name
                    FROM users AS u
                    JOIN manual_timesheet AS m
                    ON u.client_id = m.staff_id';
            $timesheetData = $db->exec($sql);
            */
        
            // Assuming you want to render the result, you might want to pass the data to the template
            $template = new Template;
            echo $template->render('ticket.htm');
        }

        function PerformanceReview() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
        
            // Build and execute the SQL query
        /*  $sql = 'SELECT u.*, u.name
                    FROM users AS u
                    JOIN manual_timesheet AS m
                    ON u.client_id = m.staff_id';
            $timesheetData = $db->exec($sql);
            */
        
            // Assuming you want to render the result, you might want to pass the data to the template
            $template = new Template;
            // Assuming u_level is stored in the session
            $this->f3->set('u_level', $_SESSION['u_level']);
            echo $template->render('performance.htm');
        }

        function FleetManager() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('fleet.htm');
        }
        
        function Onboarding() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding.htm');
        }
        
        function SupplierOnboarding() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('suppliers/onboarding.htm');
        }

        function OnboardingDetails() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboardingDetails.htm');
        }

        function Onboarding1() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1.htm');
        }

        function Onboarding1a() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1a.htm');
        }

        function Onboarding1b() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1b.htm');
        }

        function Onboarding1c() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1c.htm');
        }
        
        function Onboarding1d() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1d.htm');
        }

        function Onboarding1e() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding1e.htm');
        }

        function Onboarding2() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2.htm');
        }

        function Onboarding2a() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2a.htm');
        }
        function Onboarding2b() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2b.htm');
        }
        function Onboarding2c() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2c.htm');
        }
        function Onboarding2d() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2d.htm');
        }
        function Onboarding2e() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2e.htm');
        }
        function Onboarding2f() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2f.htm');
        }
        function Onboarding2g() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2g.htm');
        }
        function Onboarding2h() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2h.htm');
        }
        function Onboarding2i() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2i.htm');
        }
        function Onboarding2j() {
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $template = new Template;
            echo $template->render('onboarding2j.htm');
        }

    /*
        function getSpecificPeriodical() {
            $f3 = Base::instance();
            $site_id = $f3->get('GET.site_id');
            
            // Logic to handle site_id and generate content accordingly
            if ($site_id) {
                // Connect to the database and retrieve data based on $site_id
                $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
                $data = $db->exec('SELECT * FROM users WHERE id = ?', $site_id);
            
                // Render the view with the fetched data
                $template = new Template;
                echo $template->render('layout.htm'); // Pass the data to the template
            } else {
                // If no site_id provided, display an error message or a default view
                echo "Please provide a site_id to view specific periodical details.";
            }
        }
        
        */

    /*
        function PushNotifications() {
            $f3 = Base::instance();
            $db = new DB\SQL('mysql:host=localhost;dbname=tnsmwdztaz', 'tnsmwdztaz', 'vzZ3mFxE2E');
            $timesheetData = $db->exec('SELECT * FROM push_notifications');
            $template = new Template;
            echo $template->render('notifications.htm');
        }
        */

    }

    ?>
