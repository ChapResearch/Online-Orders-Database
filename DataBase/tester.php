<?php

include("paypalFns.php");
include("dbFunctions.php");

$invoice = paypalInvoiceSend(47);
if($invoice === null) {
     echo("returned false\n");
} else {
     echo("Invoice is: $invoice\n");
}


?>