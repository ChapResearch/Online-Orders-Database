<?php

include("dbFunctions.php");

echo("note that a SQL zero time 0000-00-00 00:00:00 parses as\n");

var_export(strtotime('0000-00-00 00:00:00'));

$row = array( "WasCanceled" => 1,
              "IsExpedited" => 0,
              "OrderedDate" => '2014-10-10 10:38:44',
	      "PaidDate" => '0000-00-00 00:00:00');

echo("\nbefore:\n");

var_export($row);

echo("after:\n");

dbOrderNormalize2PHP($row);

var_export($row);

echo("and back again:\n");

dbOrderNormalize2SQL($row);

var_export($row);

echo("\nCheck Paypal date parsing:\n");

echo(strtotime("08:12:31 Oct 13, 2014 PDT"));
echo("\n");
echo(date('Y-m-d H:i:s',strtotime("08:12:31 Oct 13, 2014 PDT")));
echo("\n");

?>