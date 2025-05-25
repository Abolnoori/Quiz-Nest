<?php
require_once 'config.php';

// پاک کردن اطلاعات نشست
session_unset();
session_destroy();

// انتقال به صفحه ورود
header('Location: login.php');
exit; 