<?php

// db_createCustomersTable() - creates the customers table, which includes
//                             all customers who have ordered ChapRs (including
//                             charity orders. Customers who have previously
//                              ordered a ChapR are still only listed onced.

function db_createCustomersTable($con)
{

  $sql="CREATE TABLE customers
                  (
                    CID            INT           NOT NULL PRIMARY KEY AUTO_INCREMENT, # key to the customers table
                    FirstName      CHAR(20)      NOT NULL,
                    LastName       CHAR(20)      NOT NULL,
                    Email          CHAR(40)      ,
                    Title          CHAR(15)      ,
                    Phone          CHAR(20)      ,
                    CustomerCNotes VARCHAR(1000) ,    
                    AdminCNotes    VARCHAR(1000) ,
                    MetDate        DATETIME      ,
                    Street1        CHAR(40)      ,
                    Street2        CHAR(40)      ,
                    City           CHAR(20)      ,                     
                    State          CHAR(20)      ,
                    Zip            CHAR(10)      ,
                    Country        CHAR(20)      
                  )";
     if(!execute($con,$sql,"Table 'customers' create")) {
       return (false);
     }
     return (true);
}

// db_createOrdersTable() - creates the orders table, which contains all the
//                          information on an order (multiple products, but one
//                          customer and one time).
function db_createOrdersTable($con)
{

  $sql="CREATE TABLE orders
                  (
                    OID          	INT           NOT NULL PRIMARY KEY AUTO_INCREMENT, # key to the orders table
                    CID         	INT           NOT NULL, # key to the customers table
                    OrderedDate  	DATETIME      ,
                    CustomerONotes   	VARCHAR(1000) ,
                    AdminONotes   	VARCHAR(1000) ,
                    IsExpedited  	SMALLINT      ,
                    RequestedPay 	DATETIME      ,
                    InvoiceNumber	SMALLINT      ,
                    InvoiceID           CHAR(25)      ,
                    InvoiceURL          CHAR(150)     ,
                    Charity             SMALLINT      ,
                    PaidDate     	DATETIME      ,
                    ShippedDate  	DATETIME      ,
                    ReleasedToShipping	DATETIME      ,
                    Carrier      	CHAR(30)      ,
                    TrackingNum  	CHAR(30)      ,
                    WasReceived  	SMALLINT      ,
                    WasCanceled  	SMALLINT      ,
                    ExpediteFee  	DECIMAL(6,2)  ,
                    ShippingFee  	DECIMAL(6,2)  ,
                    Discount		DECIMAL(6,2)
                  )";
     if(!execute($con,$sql,"Table 'orders' create")) {
       return (true);
     }
     return (false);
}

// db_createItemsTable() - creates the items table, which allows orders to
//                         have multiple packages associated. Each IID is unique,
//                         but each OID will be repeated based on how many packages
//                         it has.        
function db_createItemsTable($con)
{

  $sql="CREATE TABLE items
                  (
                    IID          INT           NOT NULL PRIMARY KEY AUTO_INCREMENT, # key to the items table
                    OID          INT           NOT NULL, # key to the orders table
                    PKID         INT           NOT NULL, # key to the packages table
                    Personality  INT           ,         # foreign key to the pieces table
                    Price        DECIMAL(6,2)  ,
                    Quantity     INT
                  )";
     if(!execute($con,$sql,"Table 'items' create")) {
       return (false);
     }
}

function rachelMain($con)
{
  db_createCustomersTable($con);
  db_createOrdersTable($con);
  db_createItemsTable($con);
}

?>