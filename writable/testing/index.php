<?php 
$host = 'sql1391.db.hostpoint.internal';
$username = 'pofepave_c';
$password = 'LX*JDwxMZja?!KUnU?yj';
$database = 'pofepave_c';

$mysqli =  mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}else{
    echo "<pre>"; print_r($mysqli); die;
}

// Map language slugs to lan


// Perform query
