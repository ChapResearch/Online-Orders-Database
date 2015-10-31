<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Show Customers Custom Page
 *
 * @file           show-customers.php
 * @author         Rachel Gardner
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">
   
<?php
//
// Present the customers in a list, sorted as per the tag on the URL
//
include('../DataBase/customerList.php');
include_once ("../DataBase/wordpress-helper-fns.php");
include("../DataBase/user.php");

function main()
{

  if (array_key_exists("reverse",$_GET)) {
    $reverse = true;
  } else {
    $reverse = false;
  }
  
  if (array_key_exists("sort",$_GET)) {
     $sort = $_GET["sort"];
  } else {
    $sort = "MetDate";
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
  populateListCustomersSummary("http://orders.thechapr.com/Admin/?page_id=146",$sort,$reverse);
}

checkUserLoggedIn();
main();

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
