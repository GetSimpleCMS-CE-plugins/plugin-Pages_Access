<?php
global $PA_filename, $PA_folder;


include($PA_filename);
include($PA_folder."groups.php");


$utg_user = "";
$utg_page = "";
$utg_remove = "";
$updateFlag = 0; //user update flag
$removeFlag = "";
$removeStr = "";


if(isset($_POST['userCheckName']) && $_POST['userCheckName'] !=""){
  if(isset($_POST['userCheckGroup']) && $_POST['userCheckGroup'] !=""){
    $utg_user = htmlentities( $_POST['userCheckName']);
    $utg_group = htmlentities( $_POST['userCheckGroup']);
    $grp = ${$utg_group};//dynamic variable


    //get user string from file
    function findUserStr($uFile,$ug){
      $countStartRem = strpos($uFile,"\$".$ug);
      $countStopRem = strpos($uFile,");",$countStartRem);
      $stringOrininal = substr($uFile, $countStartRem,($countStopRem-$countStartRem)+2);
      return $stringOrininal;
    }

    function updateFile($ud, $uRem, $gRem){

        file_put_contents($GLOBALS['PA_folder']."groups.php",$ud);
        echo "<p class='PA_alertSuccess'>".$uRem." removed from ".$gRem."</p>";
    }

    //check for user presence in file and remove
    function removeFromGroup($uFile, $uRem, $gRem){
     
      $removeStrOrig = findUserStr($uFile,$gRem); //the returned string is the array group with its users
      
      
      $findUcomma = strpos($removeStrOrig,"'".$uRem."',");//multiple users
      $findEnd = strpos($removeStrOrig,"'".$uRem."')"); //single user
      $findU = strpos($removeStrOrig,",'".$uRem."')"); //multiple user at end of array
      

      if($findUcomma >-1){
       
          $removeStrUpdate = str_replace("'".$uRem."',","",$removeStrOrig);
          $readyUpdate = str_replace($removeStrOrig,$removeStrUpdate,$uFile);
          updateFile($readyUpdate,$uRem, $gRem);
      }
      elseif($findU >-1){
        
          $removeStrUpdate = str_replace(",'".$uRem."')",")",$removeStrOrig);
          $readyUpdate = str_replace($removeStrOrig,$removeStrUpdate,$uFile);
          updateFile($readyUpdate,$uRem, $gRem);
      }
      elseif($findEnd >-1){ 
       
          $removeStrUpdate = str_replace("'".$uRem."')",")",$removeStrOrig);
          $readyUpdate = str_replace($removeStrOrig,$removeStrUpdate,$uFile);
          updateFile($readyUpdate,$uRem, $gRem);
      } 
      else{
        echo "<p class='PA_alertWarning'>".$uRem." not a member of ".$gRem."</p>";
      }
    }

    function addUserToGroup($u,$g,$f){

      $groupFilePath = $GLOBALS['PA_folder']."groups.php";

        //now add user to group
        $groupFile = file_get_contents($groupFilePath);
        $groupStrOrig = findUserStr($groupFile,$g);//get user string from file

        if($f==="loner"){
          $groupStrUpdate = str_replace("\$".$g." = array(", "\$".$g." = array('".$u."'", $groupStrOrig);
        }
        else {
          $groupStrUpdate = str_replace("\$".$g." = array(", "\$".$g." = array('".$u."',", $groupStrOrig);
        }

        echo "<p class='PA_alertSuccess'>".$u." added to ".$g."</p>";
        $groupFileReady = str_replace($groupStrOrig, $groupStrUpdate, $groupFile);

        file_put_contents($groupFilePath,$groupFileReady);
    }
  






    if(isset($_POST['userRemove']) && $_POST['userRemove'] !=""){
      $utp_remove = htmlentities( $_POST['userRemove']);
      $removeFlag = 1;
      $groupFilePath = $GLOBALS['PA_folder']."groups.php";

      //get file from folder
      $grpFile = file_get_contents($groupFilePath);
      removeFromGroup($grpFile , $utg_user, $utg_group);
    }
    else{
      $removeFlag = 0;
    }





    

    if($removeFlag === 0){
      //check if array is empty then add admin
      if(empty($grp)){
        addUserToGroup($utg_user,$utg_group,"loner");
      }else{
        foreach ($grp as $usrGr_val) {
          if($usrGr_val === $utg_user){
              //check if user exists in page array
                $updateFlag = 0;
                echo "<p class='PA_alertWarning'>".$utg_user." already authorised for ".$utg_group."</p>";
                break;
          }else{
            $updateFlag = 1;
          }
        }

        if($updateFlag === 1){
          addUserToGroup($utg_user,$utg_group,"users_exist");
        }
      }
    }

  }
}
?>

<div style="overflow:auto">

      <h2 class="PA_listH2"><i class="fa fa-user-circle" aria-hidden="true"></i> Adding users to group</h2>
      <ul>
        <li><strong>Add / Remove user:</strong> Select a user and a group then select the ADD USER or REMOVE USER button</li>
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
              <h2 class="PA_listH2">Groups</h2>

               <!--radio buttons for pages =============================================-->
                <?php
                foreach ($groups as $groupList_key => $groupList_value) {
                        echo "
                            <p class='PA_radioHolder'>
                              <input class='PA_radioInput' type='radio' name='groupCheck' data-group='".$groupList_key."'' value='".$groupList_key."' onclick='addData(this)'> ".$groupList_key."<br>
                            </p>";

                  } ?>

                  <!--form for user names =============================================-->

          </div>



<form id="groupPageform" name="groupPageform" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=pages_access&usertogroup">
                    <input id="userNameInput" type='hidden' name='userCheckName' value="" />
                    <input id="userGroupInput" type='hidden' name='userCheckGroup' value="" />
                    <input id="userRemove" type='hidden' name='userRemove' value="" />
                  </form>

                  <input class='PA_buttonSpace' type="button" onclick="updateCats('groupPageform')" value="Add User"><br>
                  <input class='PA_buttonSpace' type="button" onclick="updateRemove('groupPageform')" value="Remove User">






</div>
