<?php
session_start();
session_destroy();
header("Location: ../mainPages/index.php");
exit;
?>