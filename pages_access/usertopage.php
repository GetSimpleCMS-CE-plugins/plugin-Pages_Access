<?php

global $PA_filename, $PA_folder;

include($PA_filename);
include($PA_folder."groups.php");



$utp_user = "";
$utp_page = "";
$utp_remove = "";
$removeFlag = 0; //user remove flag
$removeStr = "";

if(isset($_POST['userCheckName']) && $_POST['userCheckName'] !=""){
  if(isset($_POST['userCheckPage']) && $_POST['userCheckPage'] !=""){
    $utp_user = htmlentities( $_POST['userCheckName']);
    $utp_page = htmlentities( $_POST['userCheckPage']);


    if(isset($_POST['userRemove']) && $_POST['userRemove'] !=""){
      $utp_remove = htmlentities( $_POST['userRemove']);
      $removeFlag = 1;
    }
    else{
      $removeFlag = 0;
    }

    //now add user page in cats

    foreach ($cats as $ukey => $uvalue) {
      if($utp_page==$ukey){

        //if a group then check
        if(is_array($uvalue)){

          $check_usr = 0; //user exists flag

          //if remove variable not set to 1 then conduct search for adding users
          if($removeFlag === 0){
            foreach ($uvalue as $usr_key => $usr_val) {
              if($usr_val === $utp_user){
                //check if user exists in page array
                $check_usr = 1;
                echo "<p class='PA_alertWarning'>User already authorised</p>";
                break;
              }
            }
          }

          //create string for remove
          foreach ($uvalue as $rem_key => $rem_val) {
            $removeStr .= "'".$rem_val."',";
          }

          $commaPos = strrpos($removeStr, ",");
          $removeStrFix = substr($removeStr, 0, $commaPos);



          //add user to page array
          if($check_usr === 0 && $removeFlag === 0){
            echo "<p class='PA_alertSuccess'>".$utp_user." added to ".$utp_page."</p>";
            $tempCats = file_get_contents($PA_filename);
            $searchStr = "\"".$utp_page."\"=>array(";
            $updateStr = str_replace($searchStr, $searchStr."'".$utp_user."',", $tempCats);
            file_put_contents($PA_filename,$updateStr);
          }

          //remove user
          if($removeFlag === 1){


            $tempRemCats = file_get_contents($PA_filename);
            $searchStr = "\"".$utp_page."\"=>array(";

            $userRemoveOrig = $searchStr.$removeStrFix.")";
            $comStr = "'".$utp_user."',";
            $findComma = stripos($userRemoveOrig, $comStr);
            $userRemoveUpdate = "";

            if ($findComma === false) {
              $userRemoveUpdate = str_replace("'".$utp_user."'", "", $userRemoveOrig);
            }
            else{
              $userRemoveUpdate = str_replace("'".$utp_user."',", "", $userRemoveOrig);
            }

            //remove trailing comma if present
            $userRemoveUpdate = str_replace("',)","')",$userRemoveUpdate);


            echo "<p class='PA_alertSuccess'>".$utp_user." removed from ".$utp_page."</p>";
            $updateStr = str_replace($userRemoveOrig,$userRemoveUpdate, $tempRemCats);
            file_put_contents($PA_filename,$updateStr);
          }
        }
      }
    }


  }
}

  ?>

<div style="overflow:auto">

      <h2 class="PA_listH2"><i class="fa fa-file-text" aria-hidden="true"></i> Adding users to page</h2>
      <ul>
        <li><strong>Add / Remove user:</strong> Select a user and a page then select the ADD USER or REMOVE USER button</li>
      </ul>
      <div class="PA_listContent">
      <h2 class="PA_listH2">Users</h2>

       <!--form for user names ================================================== -->
        <?php
          foreach ($userArray as $user_key => $user_value) {
            echo "
                <p class='PA_radioHolder'>
                  <input class='PA_radioInput' type='radio' name='userCheck' data-name='".$user_value."'' value='".$user_value."' onclick='addData(this)'> ".$user_value."<br>
                </p>";
        } ?>

      </div>

      <div class="PA_listContent">
              <h2 class="PA_listH2">Pages</h2>

               <!--radio buttons for pages =============================================-->
                <?php
                foreach ($PA_admin as $PA_admin_key => $PA_admin_value) {
                        echo "
                            <p class='PA_radioHolder'>
                              <input class='PA_radioInput' type='radio' name='pageCheck' data-page='".$PA_admin_key."'' value='".$PA_admin_key."' onclick='addData(this)'> ".$PA_admin_key."<br>
                            </p>";

                  } ?>

                  <!--form for user names =============================================-->

      </div>



      <form id="userPageform" name="userPageform" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=pages_access&usertopage">
          <input id="userNameInput" type='hidden' name='userCheckName' value="" />
          <input id="userPageInput" type='hidden' name='userCheckPage' value="" />
          <input id="userRemove" type='hidden' name='userRemove' value="" />
      </form>



      <input class='PA_buttonSpace' type="button" onclick="updateCats('userPageform')" value="Add user"><br>
      <input class='PA_buttonSpace' type="button" onclick="updateRemove('userPageform')" value="Remove user">






</div>
