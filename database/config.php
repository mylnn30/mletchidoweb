<?php

$db_server = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "mletchido";

//make connection


$conn = mysqli_connect($db_server, $db_user, $db_password, $db_name);

//
   if (!$conn) {
    die("could not connect: ". mysqli_connect_error());

   }

   


//if ($conn) {
    //echo "Connected!";
//}
 //else{
    //echo "Not Connected"
 //}
?>