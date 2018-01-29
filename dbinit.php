<?php
// Set via server / docker config
$host     = getenv('db_host');
$username = getenv('db_username');
$password = getenv('db_password');
$dbname   = getenv('db_name');

// Create connection
$con = mysqli_connect($host,$username,$password,$dbname);
// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error()."<br>";
}

?>