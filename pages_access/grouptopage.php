<?php

global $PA_filename, $PA_folder;

include($PA_filename);
include($PA_folder."groups.php");


if(isset($_POST['groupCheckGroup']) && $_POST['groupCheckGroup'] !=""){
  if(isset($_POST['groupCheckPage']) && $_POST['groupCheckPage'] !=""){

    $gtp_group = htmlentities( $_POST['groupCheckGroup']);
    $gtp_page = htmlentities( $_POST['groupCheckPage']);
    $check_grp = 0;
    $removeFlag = 0; //user remove flag
    $removeStr = "";




    if(isset($_POST['groupRemove']) && $_POST['groupRemove'] !=""){
      $gtp_remove = htmlentities( $_POST['groupRemove']);
      $removeFlag = 1;
    }
    else{
      $removeFlag = 0;
    }

    //search for page in cats

    foreach ($cats as $ukey => $uvalue) {
      if($gtp_page==$ukey){
        if(is_array($uvalue)){


          //if remove variable not set to 1 then conduct search for adding users
          if($removeFlag === 0){
            //search for group in page
            foreach ($uvalue as $usr_key => $usr_val) {
              if($usr_val === $gtp_group){
                $check_grp = 1;
                echo "<p class='PA_alertWarning'>Group already authorised</p>";
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


          //now add group to page
          if($check_grp === 0 && $removeFlag === 0){
            echo "<p class='PA_alertSuccess'>".$gtp_group." added to ".$gtp_page."</p>";
            $tempCats = file_get_contents($PA_filename);
            $searchStr = "\"".$gtp_page."\"=>array(";
            $updateStr = str_replace($searchStr, $searchStr."'".$gtp_group."',", $tempCats);
            file_put_contents($PA_filename,$updateStr);
          }

          //remove group
          if($removeFlag === 1){

            $tempRemCats = file_get_contents($PA_filename);
            $searchStr = "\"".$gtp_page."\"=>array(";

            $userRemoveOrig = $searchStr.$removeStrFix.")";
            $comStr = "'".$gtp_group."',";
            $findComma = stripos($userRemoveOrig, $comStr);
            $userRemoveUpdate = "";

            if ($findComma === false) {
              $userRemoveUpdate = str_replace("'".$gtp_group."'", "", $userRemoveOrig);
            }
            else{
              $userRemoveUpdate = str_replace("'".$gtp_group."',", "", $userRemoveOrig);
            }

            //remove trailing comma if present
            $userRemoveUpdate = str_replace("',)","')",$userRemoveUpdate);



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

      <h2 class='PA_listH2'><i class="fa fa-tag" aria-hidden="true"></i> Adding groups to page</h2>
      <ul>
        <li><strong>Add / Remove groups:</strong> Select a group and a page then select the ADD GROUP or REMOVE GROUP button</li>
      </ul>
      <div class="PA_listContent">
      <h2 class="PA_listH2">Groups</h2>

       <!--form for user names ================================================== -->
        <?php
        foreach ($groups as $key => $value) {
          echo "
          <p class='PA_radioHolder'>
            <input class='PA_radioInput' type='radio' name='groupToPageCheck' data-grouptopage='".$key."'' value='".$key."' onclick='addData(this)'> ".$key."<br>
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



      <form id="groupToPageform" name="groupToPageform" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=pages_access&grouptopage">
          <input id="groupNameInput" type='hidden' name='groupCheckGroup' value="" />
          <input id="userPageInput" type='hidden' name='groupCheckPage' value="" />
          <input id="userRemove" type='hidden' name='groupRemove' value="" />
      </form>



      <input class='PA_buttonSpace' type="button" onclick="updateCats('groupToPageform')" value="Add Group"><br>
      <input class='PA_buttonSpace' type="button" onclick="updateRemove('groupToPageform')" value="Remove Group">






</div>
