<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';
requireLogin();
requirePost();
verifyCsrf();

$uid = (int)$_SESSION['usuario_id'];
require_once __DIR__ . '/conexion.php';
audit($conn, $uid, 'LOGOUT', 'usuarios', $uid);
destroyCurrentSession();

header('Location: ' . appBasePath() . '/index.php');
exit;
