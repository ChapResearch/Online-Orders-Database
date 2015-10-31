<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Show Packages Custom Page
 *
 * @file           show-packages.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php
//
// Present the customers in a list, sorted as per the tag on the URL
//

include('../DataBase/user.php');
include_once ("../DataBase/wordpress-helper-fns.php");
include('../DataBase/packagesList.php');

function main()
{
  if (array_key_exists("reverse",$_GET)) {
    $reverse = true;
  } else {
    $reverse = false;
  }

  if (array_key_exists("all",$_GET)) {
    $all = true;
  } else {
    $all = false;
  }

  if (array_key_exists("sort",$_GET)) {
    $sort = $_GET["sort"];
  } else {
    $sort = "PKID";
    $reverse = "false"; // starts with most recent metDate if no sort is specified
  }
  
  // go figure out which page number needs to be linked to
  // when someone clicks on the row - the get_the_contents()
  // call must be in the content loop.
  
  if (have_posts()) {
    while(have_posts()) {
      the_post();
      $config = get_the_content();
    }
  }
  
  if(preg_match("/rowClickLink[ ]*=[ ]*([0-9][0-9]*)/",$config,$matches)) {
    $link = $matches[1];
  } else {
    $link = 0;		// get_page_link(0) returns the current page
  }
  
  // fill the page from the database (passing the link to the customer edit landing page
  populateListPackagesSummary("http://orders.thechapr.com/Admin/?page_id=185",$sort,$reverse,$all);
}

checkUserLoggedIn();
main();

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
