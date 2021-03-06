<?php
  //
  // These are helper functions for working with Wordpress
  //

//
// getConfigData() - get the configuration data from the current page
//		     Returns the config data or empty string if there
// 		     are any problems.
//
function getConfigData()
{
     if (have_posts()) {
	  while(have_posts()) {
	       the_post();
	       return(get_the_content());
	  }
     }

     return("");
}

//
// getSpecialVariable() - given the current page, grab the given string or number
//			  from the configuration area of the page.  Returns the
//			  string or number, and NULL if not found (use === to
//			  see if it is NULL).
//

function getSpecialVariable($name,$configData)
{
     $pattern = '+^\s*' . $name . '\s*=\s*(.*)\s*$+m';

     if(preg_match($pattern,$configData,$matches)) {
	  return($matches[1]);
     } else {
	  configError($name);
	  return(NULL);
     }
}


//
// configError() - generates an error message if the given config variable isn't set
//
function configError($name) {
     echo("\"$name\" not specified in " . get_page_link(0) . "<BR>");
}

//
// backToLink() - returns things back to the a particular link.  Uses
//		  javascript to get it done.  NOTE that data sent out
//		  from the page BEFORE this call will still show up.
//
function backToLink($link,$extra="")
{
     if($extra) {
	  $link = $link . "&" . $extra;
     }
     echo("\n<script type=\"text/javascript\" language=\"JavaScript\">\n");
     echo("window.location.replace(\"$link\");\n");
     echo("\n</script>\n");
}

//
// backToWPPage() - returns things back to a given Wordpress Page
//		    as specified by the ID.  You can also give
//		    some "extra" stuff that will be added to the end.
//
function backToWPPage($page_id,$extra)
{
     $page_id = intval($page_id);
     backToLink("?page_id=$page_id",$extra);
}

//
// getCurrentPageURL() - magically gets the current URL anytime you call it.
//			 It is necessary because Wordpress get_page_link()
//			 needs to be in "The Loop".
//			 
//
function getCurrentPageURL()
{
     if (have_posts()) {
	  while(have_posts()) {
	       the_post();
	       return(get_page_link());
	  }
     }
}

//
// getWordpressHiddenFormField() - this function returns a string that makes
//				   it easy to integrate forms within Wordpress.
//				   The problem is that the current spec'd
//				   behavior of form submission is to swallow
//				   any addtion ?params=XXX on a form URL.
//
function getWordpressHiddenFormField()
{
     if (have_posts()) {
	  while(have_posts()) {
	       the_post();
	       $value = get_the_ID();
	       break;
	  }
     }

     return("<input type=\"hidden\" name=\"page_id\" value=\"$value\">\n");
}

?>