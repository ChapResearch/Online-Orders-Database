<?php

include("dbFunctions.php");

function main()
{
     echo "<br>In test driver<br>";
     echo "Database Host:  $CHAPRDB_HOST<br>";
     echo "Database Database:  $CHAPRDB_DATABASE<br>";

//     $rows = dbGetPackages();
//     $rows = dbGetPersonalities();
//     $rows = dbGetOrder(24);
//     $rows = dbGetItems(31);
     $rows = dbGetOrderCustomer(2);

     print_r($rows);
}

main();

?>