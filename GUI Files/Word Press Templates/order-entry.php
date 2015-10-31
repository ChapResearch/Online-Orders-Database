<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Order Entry Custom Page
 *
 * @file           order-entry.php
 * @author         Eric Rothfus
 * @copyright      Chap Research
 */

get_header(); ?>

<div id="content-full" class="grid col-940">

<?php
   include_once("../DataBase/user.php");
   checkUserLoggedIn();
include_once ('../orderEntry.php');

?>

</div><!-- end of #content-full -->

<?php get_footer(); ?>
