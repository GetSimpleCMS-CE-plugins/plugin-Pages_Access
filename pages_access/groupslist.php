<?php

  global $PA_filename, $PA_folder;
  include($PA_folder."groups.php");

  $removeFlag = 0; //user remove flag

  if(isset($_POST['userRemove']) && $_POST['userRemove'] !=""){
      $removeFlag = 1;
    }

  if(isset($_POST['grpName']) && $_POST['grpName'] !=""){

    $grp=htmlentities($_POST['grpName']);
    $check_grp = 0;
    $removeStr = "";
    $addFlag = 0;




    if($removeFlag !== 1){
      if($addFlag === 0){
        //now add group to page
        foreach($groups as $groupieKey=>$groupieVal){
          if($grp === $groupieKey){
            echo "<p class='PA_alertWarning'> Sorry, group ".$grp." already exists</p>";
            $addFlag = 0;
            break;
          }
          else{
           $addFlag=1;
          }
        }
      }


      if($addFlag === 1){
          //now add group to page
          $tempCats = file_get_contents($PA_folder."groups.php");
          $searchStr = "\$groups = array(";
          $updateStr = str_replace($searchStr, $searchStr."\"".$grp."\"=>\$".$grp.",", $tempCats);
          $createArray = str_replace("<?php ", "<?php \$".$grp." = array();", $updateStr);
          file_put_contents($PA_folder."groups.php",$createArray);
           echo "<p class='PA_alertSuccess'>".$grp." group created</p>";
      }

    }

    if($addFlag == 0 && $removeFlag == 1){

      if($grp === "admin" || $grp === "page404"){
        echo "<p class='PA_alertError'>Cannot delete ".$grp." - this is a required group!</p>";
      }else{
        //remove entry
        $tempRemCats = file_get_contents($PA_folder."groups.php");
        $searchRemStr = "\"".$grp."\"=>"."\$".$grp.")";
        $searchRemStr2 = "\"".$grp."\"=>"."\$".$grp.",";
        $remStrPos = strpos($tempRemCats, $searchRemStr);

        if($remStrPos===false || $remStrPos===0){
          
          $remStrComPos = strpos($tempRemCats, $searchRemStr2);
          if($remStrComPos==false){
            
             echo "<p class='PA_alertWarning'> Group '".$grp."' not found</p>";
          }else{
            $removeStr = str_replace("\"".$grp."\"=>"."\$".$grp.",", "", $tempRemCats);
          }

        }else{
          $removeStr = str_replace("\"".$grp."\"=>"."\$".$grp.")", ")", $tempRemCats);
        }



        $rmArrayPos = strpos($removeStr, "\$".$grp." = array(");
        if($rmArrayPos !== false){
          $rmArrayStop = strpos($removeStr,");",$rmArrayPos)+2;
          $rmRemainingTxt = substr($removeStr, $rmArrayStop);



        //create string for deletion);
        $rmArrayStr = substr($removeStr, $rmArrayPos,($rmArrayStop-$rmArrayPos));
        $rmArray = str_replace($rmArrayStr,"",$removeStr);
        file_put_contents($PA_folder."groups.php",$rmArray);
        echo "<p class='PA_alertSuccess'> Group '".$grp."' now removed</p>";
        }
      }
    }



 }

?>



<div style="overflow:auto">

      <h2 class='PA_listH2'><i class="fa fa-users" aria-hidden="true"></i> Groups and users</h2>
      <p>Listed below is an output of the groups and each associated member</p>
      <ul>
        <li><strong>Create a group:</strong> Add a title to the text box then select the ADD GROUP button to create a group </li>
        <li><strong>Remove a group:</strong> Add the title of an existing group to the text box then select the DELETE GROUP button</li>
        <li><strong>Refresh list:</strong> To see a fresh updated list, select the REFRESH button after every action</li>

      </ul>
      <div class="PA_listContent">
      <?php
          foreach ($groups as $key => $value) {
          	echo "<div class='PA_listBotBord'>
          		<h4 class='PA_listH4'>".$key."</h4><p class='PA_listP'>";

          	if(is_array($value)){
          		foreach ($value as $valkey => $valvalue) {
          			echo " - " . $valvalue;
          		}
          	}
          	else{
          		echo " - " . $value;
          	}
          	echo "</p></div>";
          }
       ?>
          </div>

          <form id="groupCreateform" name="groupCreateform" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=pages_access&groupslist">
            <input name="grpName" class='PA_buttonSpace' type="text" value="" placeholder="add group name"><br>
            <input id="groupCreateInput" type='hidden' name='groupCreateInput' value="" />
            <input id="userRemove" type='hidden' name='userRemove' value="" />
          </form>


          <input class='PA_buttonSpace' type="button" onclick="updateCats('groupCreateform')" value="Add group"><br>
          <input class='PA_buttonSpace' type="button" onclick="updateRemove('groupCreateform')" value="Delete group">

          <form id="refreshform" name="refresh" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=pages_access&groupslist">
            <input class='PA_buttonSpace' type='submit' name='clearme' value="Refresh List" />
          </form>
</div>
