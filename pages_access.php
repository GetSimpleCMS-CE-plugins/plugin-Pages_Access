<?php
// Start the session

/*
* ---------------------------------------------------------------------------
* @plugin Name: Pages Access
*
* @description: Admin access control of users in backend
*		Allows only certain pages to be viewed in admin(backend) based on user name.
*   Allows admin to create groups and assign user to groups, assign groups to
*		pages and users to pages.
*
* @version: 1.0
* @author: Craig Adams
* @author URL: http://www.codecobber.co.uk/
* --------------------------------------------------------------------------
*/



# get correct id for plugin
$thisfile=basename(__FILE__, ".php");



# register plugin
register_plugin(
	$thisfile, //Plugin id
	'Pages Access', 	//Plugin name
	'1.0', 		//Plugin version
	'Craig Adams',  //Plugin author
	'http://www.codecobber.co.uk/', //author website
	'Allows only certain pages to be viewed in admin based on user name', //Plugin description
	'pages_access', //page type - on which admin tab to display
	'pages_access_show'  //main function (administration) called immediately on activation of plugin

);





//Globals______________________________________________________________

$PA_current_user = get_cookie('GS_ADMIN_USERNAME');
$PA_folder = GSDATAOTHERPATH . 'categories/';
$PA_filename = $PA_folder . 'cats.php';

$PA_match = 0;
$PA_editMatch = 0;
$PA_catData = file_get_contents($PA_filename);
$PA_page_url=$_SERVER['REQUEST_URI'];
$editURL="";
$qVal = strpos($PA_page_url, "=");
$localAddress = substr($PA_page_url,0,$qVal+1);
$PA_editPage =  strripos($PA_page_url, "/");
$PA_editBasicURL = substr($PA_page_url,$PA_editPage+1);
$firstAmpersand = strpos($PA_page_url, "&");
$pageNameforFile = substr($PA_page_url, $qVal+1, ($firstAmpersand-1)-$qVal);

$qm = strripos($PA_page_url, "?");
$stringQuery = substr($PA_page_url,$qm+4);


include(GSDATAOTHERPATH.'categories/groups.php');



//HOOKS________________________________________________________________

//set function for call in pages tab
add_action('pages-main','basicCheck');


# add a link in the admin tab 'pages'
foreach ($admin as $gVal) {

	//display page access tab if user is admin
	if($gVal === $PA_current_user){

		add_action( 'nav-tab', 'createNavTab', array( 'pages_access', $thisfile, 'Pages Access','overview' ) );
		add_action('pages_access-sidebar', 'createSideMenu', array($thisfile, '<i class="fa fa-eye" aria-hidden="true"></i> Overview', 'overview'));
		add_action('pages_access-sidebar', 'createSideMenu', array($thisfile, '<i class="fa fa-users" aria-hidden="true"></i> Groups user list', 'groupslist'));
		add_action('pages_access-sidebar', 'createSideMenu', array($thisfile, '<i class="fa fa-file-text" aria-hidden="true"></i> Add/remove user to page', 'usertopage'));
		add_action('pages_access-sidebar', 'createSideMenu', array($thisfile, ' <i class="fa fa-user-circle" aria-hidden="true"></i> Add/remove user to group', 'usertogroup'));
		add_action('pages_access-sidebar', 'createSideMenu', array($thisfile, '<i class="fa fa-tag" aria-hidden="true"></i> Add/remove group to page', 'grouptopage'));
	}
}

# set function for call in edit page
add_action('edit-extras','editCheck');

# Check for renaming of page via slug
add_action('changedata-aftersave','slugCheck');

# Update pages list on every save
add_action('changedata-save','checkNewPages');

# Delete page
add_action('page-delete','PAdeletePages');


//register and queue the css for the side menu in the plugins page
register_style('pageAccessCSS', $SITEURL.'plugins/pages_access/css/page_access.css','1', false);
queue_style('pageAccessCSS', GSBACK);







//Functions_____________________________________________________________

/*
*	===============================================================
* 	get_pages() - Explanation:
*
*	The get_pages function provides a method to extract a list of pages from the
*	data/pages folder. The resulting list is then passed back to the variable that called the
*	function.
*	===============================================================
*/


function get_pages(){

		$dir_handle = @opendir(GSDATAPAGESPATH) or exit('Unable to open ...getsimple/data/pages folder');
		$PA_filenames = array();
		while ($PA_filename = readdir($dir_handle))
		{
			$PA_filenames[] = $PA_filename;
		}

		$PA_pages = array();
		if (count($PA_filenames) != 0)
		{
			sort($PA_filenames);
			foreach ($PA_filenames as $PA_file)
			{
				if (!($PA_file == '.' || $PA_file == '..' || is_dir(GSDATAPAGESPATH.$PA_file) || $PA_file == '.htaccess'))
				{
					$thisfile = file_get_contents(GSDATAPAGESPATH.$PA_file);
					$PA_XMLdata = simplexml_load_string($thisfile);
					$PA_url = (string)$PA_XMLdata->url;
					$PA_title = (string)$PA_XMLdata->title;
					$PA_pages[$PA_url] = $PA_url;
				}
			}
		}
		return $PA_pages;
}





/*
*	===============================================================
* 	hidePageTitle() & hidePage() - Explanation:
*
*	Both functions are used by checkPages to check cats.php against
*	each page found in data/pages and are responisble for hiding scripts
*	===============================================================
*/

function basicCheck(){
	if(!isset($_POST['chk1'])){
		echo "<form id='chk' method='POST' action='".$_SERVER['PHP_SELF']."'>
		<input type='hidden' name='chk1' value='101'>
	</form>
	<script id='chkSend'>document.getElementById('chk').submit()</script>
	";
	die();
	}
	elseif(isset($_POST['chk1']) && $_POST['chk1'] === '101'){
		echo "<script>console.log('Javascript enabled');</script>";
	}
	//pass in the page url to check
	checkPages('pages.php');
	
}

function editCheck(){
	//pass in the page url to check
	checkPages('edit.php');
}




function hidePageTitle($pa_key){
	//delete list item from menu and hide all traces from browser inspector
				echo "<script id='deleteme1968'>$(document).ready(function(){
				        $('#tr-".$pa_key."').remove();
				        $('#deleteme1968').remove();
				});
				</script>";
}



function hidePage(){

	//delete list item from menu and hide all traces from browser inspector
				echo "<script id='deleteMain'>
				$(document).ready(function(){
				        $('.main').html('<h2>Access denied!</h2><p>Please speak nicely to the administrator</p><p><b>If you have just renamed a page then go back to \'View All Pages\' in the menu and select the required page.</b></p>');
				        $('#deleteMain').remove();
				});
				</script>";
}



/*
*	===============================================================
* 	checkPages() - Explanation:
*
*	On the edit.php page - Checks for current admin user and compares
*	with access list in 'data/other/categories' to provide a list
*	of pages for the user to access/edit. If user tries to edit
*	the url by substituting a new url query string then access is
*	denied and user is informed to contact the administrator.
*	===============================================================
*/

function checkPages($pg){
	

	
	global $stringQuery, $PA_current_user, $PA_folder, $PA_filename, $PA_folder, $PA_match, $PA_editMatch, $PA_page_url, $PA_editPage, $PA_editBasicURL,$USR;

	include($PA_filename); //include cats.php
	include($PA_folder."groups.php");
	


	$editPage = strpos($PA_page_url, "?");
	$query = substr($PA_page_url,$editPage+4);
	$editAddress = substr($PA_page_url,0,$editPage);// url upto '?''
	$editURL = substr($PA_page_url,$PA_editPage+1,$editPage-$PA_editPage-1);
	$thisEditPage=array();
	$adminAccess = 0;

	$p = get_pages(); 	   //get array of pages from data/pages




	//initialise admin array on installation
	if(empty($admin)){
		$adminInit = file_get_contents($PA_folder."groups.php");
		$adminInitUD = str_replace("\$admin = array()","\$admin = array('".$USR."')",$adminInit);
		file_put_contents($PA_folder."groups.php",$adminInitUD);
	}


	/*
	loop through data/pages and compare with page data in cats.php
	also check user groups for each page
	*/

	//for each page in data/pages
	foreach ($p as $pa_key => $pa_value) {

		//now loop through cats.php
		foreach ($cats as $catkey => $catvalue){
			//eg "t1"=>array('admin')

			if($catkey != $pa_value){
				continue;
			}else{

				//lastly, loop through cats.php and get user names
				if(is_array($catvalue)){

					foreach($catvalue as $cVal){
					    //create dynamic vaiable from the value
						@$groupx = ${$cVal};


						//if a group then check
						if(is_array($groupx)){
							foreach ($groupx as $xVal) {

								//check logged in user matches entry in cats
								if($xVal === $PA_current_user){
									//display list item
									$PA_match = 1;
									$PA_editMatch = 1;
									// create an array of pages user authoriased to view
									// and loop through this later
									array_push($thisEditPage,$catkey);

									// set admin flag if usr name located in admin array
									if($cVal === "admin"){
										$adminAccess = 1;
									}
								}
							}
						}
						else{
							// if just a single name rather than a group then check user match
							if($cVal === $PA_current_user){
								$PA_match = 1; //display list item
								$PA_editMatch = 1;
								array_push($thisEditPage,$catkey);
							}
						}
					}
				}
			}

		}
			if($PA_match === 0){
				// for pages page
				// redirect back to pages list
				if($pg === $PA_editBasicURL){
					hidePageTitle($pa_key);
				}
				elseif ($editPage > -1) {
					//stop unauthoraised access to other pages
					hidePageTitle($pa_key);
				}
				// if page doesn't exist then same
				elseif($stringQuery === "or=The+requested+page+does+not+exist"){
					hidePageTitle($pa_key);
				}
			}

			//reset variable to auto hide
			$PA_match = 0;
		}


		 //edit page test
		if($editURL === "edit.php"){
			if($PA_editMatch == 0){
				hidePage();
			}
			else{
				$editHide=1;
				foreach ($thisEditPage as $editValue) {
					if($stringQuery === $editValue || $adminAccess === 1){
						echo "Access granted for page: ".$editValue."<br>";
						$adminAccess = 0;
						$editHide=0;
						break;
					}
					else{
						$editHide=1;
					}

				}

				if($editHide === 1){
					hidePage();
				}

			}
		}

		
	
}





/*
*	===============================================================
* 	pages_access_show() - Explanation:
*
*	Description displayed in panel when link in sidebar is clicked
*	and the list of pages displayed.
*	===============================================================
*/

function pages_access_show() {
?>
	<script>

	//populate the form with user data

	var dataName="";
	var dataGroup="";
	var dataPage = "";

  //Update the user form with user name value from radio button
	function addData(e){
		var dataMatch = e.getAttribute("name");

		if(dataMatch==='userCheck'){
			dataName = e.getAttribute("data-name");
			var nameField = document.getElementById("userNameInput");
			nameField.setAttribute("value",dataName);
		}

		if(dataMatch==='pageCheck'){

			dataPage = e.getAttribute("data-page");
			var pageField = document.getElementById("userPageInput");
			pageField.setAttribute("value",dataPage);
		}

		if(dataMatch==='groupCheck'){
			dataGroup = e.getAttribute("data-group");
			var groupField = document.getElementById("userGroupInput");
			groupField.setAttribute("value",dataGroup);
		}

		if(dataMatch==='groupToPageCheck'){
			dataGroup = e.getAttribute("data-grouptopage");
			var groupToPageField = document.getElementById("groupNameInput");
			groupToPageField.setAttribute("value",dataGroup);
		}
	}

    //send the forms
	function updateCats(formID,e){
		document.getElementById(formID).submit();
	}

	function updateRemove(formID,e){
		var removeCheck = document.getElementById('userRemove');
		removeCheck.setAttribute("value","remove");
		document.getElementById(formID).submit();
	}

	</script>



<?php



	global $USR, $PA_folder, $PA_filename, $PA_current_user;


	$PA_admin = get_pages();//get pages list
	$PA_users = GSUSERSPATH;
	$userArray = array(); // hold list of pages without xml extension

    include($PA_filename); //include cats.php
	include($PA_folder."groups.php");
	


	// Open a data/user directory, and read its contents (get a list of users)
	if (is_dir($PA_users)){
	  if ($listDataPages = opendir($PA_users)){
	    while (($Pagefile = readdir($listDataPages )) !== false){

	    	if($Pagefile === 'autosave' || $Pagefile === '..' || $Pagefile === '.' || $Pagefile === '.htaccess'){
	    		continue;
	    	}
	        $stripXML = str_replace(".xml", "", $Pagefile);
	        array_push($userArray, $stripXML);
	    }
	    closedir($listDataPages);
	  }
	}



	//dynamically load content
	if( isset($_GET['overview'])){
		include(GSPLUGINPATH.'pages_access/overview.php');
	}elseif (isset($_GET['usertopage'])) {
		include(GSPLUGINPATH.'pages_access/usertopage.php');
	}elseif (isset($_GET['usertogroup'])) {
		include(GSPLUGINPATH.'pages_access/usertogroup.php');
	}elseif (isset($_GET['grouptopage'])) {
		include(GSPLUGINPATH.'pages_access/grouptopage.php');
	}elseif (isset($_GET['groupslist'])) {
		include(GSPLUGINPATH.'pages_access/groupslist.php');
	}

?>






<?php
}





/*
*	===============================================================
* 	slugCheck() - Explanation:
*
*	In changedata.php - On every save, grab the POST variables of url
*	and new slug name. Then perform a string replace in the cats.php list.
*	===============================================================
*/

function slugCheck(){

	global $PA_filename;

	$PA_pageDetails = file_get_contents($PA_filename);
	$PA_slug_pageURL = htmlentities($_POST['existing-url']);
	$PA_slug_id = htmlentities($_POST['post-id']);


	$PA_slugUpdate = str_replace("\"".$PA_slug_pageURL."\"=>array", "\"".$PA_slug_id."\"=>array", $PA_pageDetails);


	file_put_contents($PA_filename, $PA_slugUpdate);
	checkNewPages(); // check for new pages
}




/*
*	===============================================================
* 	checkNewPages() - Explanation:
*
*	In changedata.php - 
*	The function updates categories cats.php with a list of pages
*	cross referenced from data/pages with a list of pages. By default
*	the pages are only accessible by admin
*	===============================================================
*/


function checkNewPages(){

	global $PA_filename, $PA_folder;

	$PA_PagesList = GSDATAPAGESPATH;



	// Open a data/pages directory, and read its contents
	if (is_dir($PA_PagesList)){
	  if ($listDataPages = opendir($PA_PagesList)){
	    while (($Pagefile = readdir($listDataPages )) !== false){

	    	if($Pagefile === 'autosave' || $Pagefile === '..' || $Pagefile === '.' || $Pagefile === '.htaccess'){
	    		continue;
	    	}


			$catsCont = file_get_contents($PA_filename);

			//remove extension and ensure add  '=>array'to end to ensure accurate string is searched
	        $pageFileReady = str_replace(".xml","","\"".$Pagefile."\"")."=>array";
	        $catsPageFound = stripos($catsCont,$pageFileReady);



	        if($catsPageFound > -1){
	        	continue;
	        }
	        else{
	        	$pageFileReady = str_replace("=>array","",$pageFileReady);
	        	$newCatFile = str_replace("cats = array(","cats = array(".$pageFileReady."=>array('admin'),",$catsCont);
	        	file_put_contents($PA_filename,$newCatFile);

	        }

	    }
	    closedir($listDataPages);
	  }
	}

}








/*
*	===============================================================
* 	pageCheck() - Explanation:
*
*	Fired when a page is deleted
*	===============================================================
*/

function PAdeletePages($delPage){
	global	$PA_filename;

	$deletec = get_pages();
	$fileID = $_GET['id'];

	// delete user to cats.php

	$catsDelete = file_get_contents($PA_filename);

	// find cats start
	$catsStart = stripos($catsDelete, "\$cats = array("); //find start of cats array

	// find specific line and save start of array
	$deleteDataCheck = stripos($catsDelete, '"'.$fileID.'"=>array'); //find correct item line
	$deleteComma = stripos($catsDelete, ",", $deleteDataCheck); // get end of line

	if($deleteComma === false){
	  $semiColonCheck = stripos($catsDelete, ";",$deleteDataCheck);
	  $deleteString = substr($catsDelete, $deleteDataCheck-1, ($semiColonCheck-$deleteDataCheck));
	}
	else{
		$deleteString = substr($catsDelete, $deleteDataCheck, ($deleteComma-$deleteDataCheck)+1);
	}



	$readyDeleteData = str_replace($deleteString,"", $catsDelete);


	file_put_contents($PA_filename,$readyDeleteData);

}






?>
