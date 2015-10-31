<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Package Edit Custom Page
 *
 * @file           package-edit.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

   include_once ("../packageForm.php");
include_once ("../DataBase/dbFunctions.php");
include_once ("../DataBase/wordpress-helper-fns.php");

function takePackageFromDataBase($PKID)
{
  $dataBasePackage = dbGetPackage($PKID);

  $package = array();

  $package["PKID"] = $PKID;
  $package["packname"] = $dataBasePackage["PackageName"];
  $package["packprice"] = $dataBasePackage["Price"];
  $package["active"] = $dataBasePackage["Active"];
  $package["pieces"] = dbGetPVP($PKID);
  
  print_r($package["pieces"]);

  return $package;
}

function main()
{
  if (array_key_exists("PKID",$_GET)){
    $PKID = $_GET["PKID"];
  } else {
    print_r("ERROR: PKID NOT FOUND");
    $PKID = 1;
  }
  
  if (!array_key_exists("packageForm", $_GET)){
    $data = takePackageFromDataBase($PKID);
    showPackageForm($data, "", array()); // sends an empty array as the badFields
  }
  else {
      $badFields = packageValidate($_GET); // checks to make sure the info given is complete
      if (count($badFields) != 0){
	showPackageForm($_GET, "", $badFields);
      }
      else {
	$package = formatForDataBase($_GET);
	//	dbUpdate("packages", $package, "PKID", $_GET["PKID"]);
	//	$PKID = dbInsertNewPackage($package);
	$i = 1;
	while (array_key_exists("PID$i", $_GET)){
	  $PIDs[] = $_GET["PID$i"];
	  $i++;
	}
	//	dbInsertNewPVPs($PKID, $PIDs);
	// get the configuration information from the Wordpress page so we know where
	// our target "return to" pages are

	$config = getConfigData();	
	$backToPackage = getSpecialVariable("backToPackage",$config);

	//	backToWPPage($backToPackage,"pkid=$PKID");
      }
  }
}
main();

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
