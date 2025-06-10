<?php
session_start();
include '../db.php';

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM ofertas WHERE id = ? AND empresa_id = ?");
$stmt->execute([$id, $_SESSION['empresa_id']]);

header("Location: dashboard.php");
exit;
