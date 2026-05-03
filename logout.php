<?php
session_start();
session_destroy();

header("Location: Userlogin.html");
exit();
?>