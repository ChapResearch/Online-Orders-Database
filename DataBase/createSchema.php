<?php
  //
  // createSchema.php
  //
  //	This file can be used from a blank start to create the schema for
  //	the entire database.  The only things that have to be configured are
  //	the host, username, and password.
  //
  //	NOTE that this file is run by itself, and doesn't make use of any
  //	other functions in this directory - just the configuration.
  //
  //    GOOD INFORMATION FOR TESTS:
  //    ---------------------------
  //    - to start from scratch, "drop" the chapr database
  //            login to mysql    --->    $ mysql -l chapr -p <RETURN>
  //            drop the database --->      drop database chapr; <RETURN>
  //            leave             --->      quit; <RETURN>
  //    - to look at what you've done
  //            login to mysql    --->    $ mysql -l chapr -p <RETURN>
  //            change to chapr   --->      user chapr; <RETURN>
  //            lists tables      --->      show tables; <RETURN>
  //            list table schema --->      describe pvp; <RETURN>  (or whatever table name instead of pvp)
  //            

include("config.php");
include("joshTable.php");
include("racheltables.php");

//
// execute() - a little function for convenience of running sql statements.
//		It takes the $statement, the $connection, and a $prompt that
//		is used to tell the user what went wrong, or right.
//
function execute($connection,$statement,$prompt)
{
     if (mysqli_query($connection,$statement)) {
	  echo "$prompt successful\n";
	  return(true);
     } else {
	  echo "Error during $prompt: " . mysqli_error($connection) . "\n";
	  return(false);
     }
}

function main()
{
     global $DB_HOST;
     global $DB_DATABASE;
     global $DB_USERNAME;
     global $DB_PASSWORD;

     // we want to know when we use variables that aren't defined

     error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

     // First, create a connection to the database with the given config information

     $con = mysqli_connect($DB_HOST,$DB_USERNAME,$DB_PASSWORD);
     if (mysqli_connect_errno()) {
	  echo "Failed to connect to MySQL: " . mysqli_connect_error() . "\n";
	  return;
     }

     // now that we have a connection create the database and then the tables

     $sql="CREATE DATABASE $DB_DATABASE";
     if(!execute($con,$sql,"Creating database '$DB_DATABASE'")) {
	  return;
     }

     // set that database for use for further table creation

     $sql="USE $DB_DATABASE";
     if(!execute($con,$sql,"Set database '$DB_DATABASE' current")) {
	  return;
     }

     // table "pvp" - the mapping from packages to pieces
     //    (note that you can put comments in the middle of SQL statements)

     $sql="CREATE TABLE pvp
                  (
                    PKID INT NOT NULL,		# foreign key to the packages database
                    PID  INT			# foreign key to the pieces database
                  )";
     if(!execute($con,$sql,"Table 'pvp' create")) {
	  return;
     }

     //

     joshMain($con);
     rachelMain($con);

     // finally close the connection

     mysqli_close($con);
}

main();
?>