<?php
$mypassword = "strongpass";

$pass = password_hash($mypassword, PASSWORD_DEFAULT);

echo ($pass);


?>