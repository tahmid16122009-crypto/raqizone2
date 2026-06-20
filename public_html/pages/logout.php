<?php
require_once __DIR__ . '/../auth.php';
rz_logout_user();
header('Location: /');
exit;