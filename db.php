<?php
$conn = new mysqli("localhost", "root", "", "cbymewng_pass_gsmserver_org");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
