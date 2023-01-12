<?php
  

  include(GSDATAOTHERPATH.'categories/groups.php');

  if(empty($admin)){
    $initAdmin = file_get_contents(GSDATAOTHERPATH.'categories/groups.php');
    $initUpdate = str_replace("\$admin = array()","\$admin = array('".$PA_current_user."')",$initAdmin);
    file_put_contents(GSDATAOTHERPATH.'categories/groups.php', $initUpdate);
  }

?>

<div style="overflow:auto">
  <h2 class="PA_listH2"><i class="fa fa-eye" aria-hidden="true"></i> Pages, users and groups</h2>
  <p>Listed below is an output of the pages, associated users and groups</p>
  <div class='PA_listContent' id='overViewWidth'>

  <?php
  $tempCatsFile = file_get_contents($PA_filename);
  $addonPages = "";
  $pagePresent = 0;

  //quick way to check if pages exist in cats.php, if not then add page (with admin member)
  foreach ($PA_admin as $PA_admin_key => $PA_admin_value) {

          foreach ($cats as $key => $value) {
            if($key === $PA_admin_key){
              $pagePresent = 1;
            }
          }
          if($pagePresent === 0){
              $addonPages .= "\"".$PA_admin_key."\"=>array('admin'),";
          }
          $pagePresent = 0;
  }


  $ready = str_replace("<?php \$cats = array(","<?php \$cats = array(".$addonPages."",$tempCatsFile);

  file_put_contents($PA_filename,$ready);

  include($PA_filename); //include cats.php
  //output list
  foreach ($cats as $key => $value) {
    echo "<div class='PA_listBotBord'>
      <p class='PA_listPbold'>".$key."<span class='PA_listP'>";

    if(is_array($value)){
      foreach ($value as $valkey => $valvalue) {
        echo " - " . $valvalue;
      }
    }
    else{
      echo " - " . $value;
    }
    echo "</span></p></div>";
  }

       ?>
          </div>
</div>
