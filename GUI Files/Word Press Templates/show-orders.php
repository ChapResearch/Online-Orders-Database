<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Show Orders Custom Page
 *
 * @file           show-orders.php
 * @author         Eric Rothfus
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php

include_once("../DataBase/user.php");

// check to see if th euesr is logged in - if not, this routine DOES NOT return
checkUserLoggedIn();

//
// Present the orders in a list, sorted as per the tag on the URL.
// Orders can be shown including cancelled and including shipped,
// though normally it doesn't include them

include('../DataBase/orderList.php');

if (array_key_exists("sort",$_GET)) {
     $sort = $_GET["sort"];
} else {
     $sort = "OrderedDate";
}
if (array_key_exists("all",$_GET)) {
     $all = true;
} else {
     $all = false;
}
if (array_key_exists("reverse",$_GET)) {
     $reverse = true;
} else {
     $reverse = false;
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

populateListOrderSummary(get_page_link($link),$sort,$reverse,$all);
?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
