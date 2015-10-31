<?php

//
// user.php - this file abstracts user stuff for the ChapR site(s)
//		It is aware that it can be used within Wordpress
//		and outside of Wordpress like when embedding the
//		order form somewhere.

function inWordpress()
{
     // if we're running within Wordpress, this class will be defined

     return(class_exists("WP_User"));
}

//
// userLoggedIn() - returns TRUE if the user is logged in, and FALSE
//		    otherwise.  This is safe to call either within
//                  Wordpress or not.
//
function userLoggedIn()
{
     if(!inWordpress()) {
	  return(false);
     }

     // at this point, we must be running within Wordpress, so go
     // ahead and do the standard check to see if the user is logged
     // in (somewhat defensive programming here)

     $current_user = wp_get_current_user();
     if ( !($current_user instanceof WP_User) || $current_user->ID == 0) {
	  return(false);
     }

     // user is logged in

     return(true);
}

//
// checkUserLoggedIn() - will check if the user is logged in, if not,
//			 will do a warning message and and exit().
//
function checkUserLoggedIn()
{
     if(!userLoggedIn()) {
	  echo("<h2>You must be logged in to use this function. ");
	  switch(rand(0,5)) {
	  case 0:
	       echo "Press the BACK button and log in!  or else...\n";
	       break;
	  case 1:
	       echo("Press the BACK button and login, or just go away.</h2>");
	       break;
	  case 2:
	       echo("Press the BACK button and login, or Ben will come after you.</h2>");
	       break;
	  case 3:
	       echo("Press the BACK button and login, or Rachel may get mad at you.</h2>");
	       break;
	  case 4:
	       echo("Press the BACK button and login, pretty please....</h2>");
	       break;
	  case 5:
	       echo("Press the BACK button and login, or don't, or do,...</h2>");
	       break;
	  }
	  exit(1);
     }
}

?>