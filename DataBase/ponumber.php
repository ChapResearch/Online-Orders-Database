<?php
  //
  // ponumber.php
  //
  // This function simply finds the PO number in any of the applicable fields of the
  // given data.  Returns NULL if no PO number was found, otherwise the PO number
  // will be in there - with no adornment.
  //
  // Format for finding PO numbers is "[PO: <number>]" where the <number> can be
  // anything WITHOUT spaces or the square bracket.
  //
  // This routine looks at the $data array in the listed fields.  If multiple PO numbers
  // are seen as an array, which will have more if there are multiple POs.  NULL is
  // returned if no POs are seen.
  //

function findPONumber($data)
{
     $targetFields = array( "CustomerONotes", "AdminONotes", "CustomerCNotes", "AdminCNotes" );
     $pattern = "/\[PO:[ 	]+([^\] 	]+)\]/"; 
     $returnPOs = array();

     foreach($targetFields as $field) {
	  if(array_key_exists($field,$data)) {
	       if(preg_match_all($pattern,$data[$field],$matches)) {
		    $returnPOs = array_merge($returnPOs,$matches[1]);
	       }
	  }
     }

     if(count($returnPOs) == 0) {
	  return(NULL);
     } else {
	  return($returnPOs);
     }
}
?>
