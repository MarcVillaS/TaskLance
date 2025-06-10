<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$oferta_id = $_GET['id'] ?? null;
if (!$oferta_id) {
    echo "Oferta no válida.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM postulaciones WHERE oferta_id = ? AND user_id = ?");
$stmt->execute([$oferta_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo "Ya te has postulado a esta oferta.";
    exit;
}

$stmt = $pdo->prepare("INSERT INTO postulaciones (oferta_id, user_id) VALUES (?, ?)");
$stmt->execute([$oferta_id, $_SESSION['user_id']]);

?>
<a href="ofertas.php">Volver a ofertas</a>
    