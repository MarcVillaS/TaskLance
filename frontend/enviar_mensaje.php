<?php
session_start();

$is_empresa = strpos(__DIR__, 'empresas') !== false || isset($_SESSION['empresa_id']);
if ($is_empresa) {
    if (!isset($_SESSION['empresa_id'])) {
        header("Location: login.php");
        exit;
    }
    include '../db.php';
    $empresa_id = $_SESSION['empresa_id'];
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
    $mensaje = trim($_POST['mensaje'] ?? '');
    $sender_type = 'empresa';
    $receiver_type = 'user';
    $sender_id = $empresa_id;
    $receiver_id = $user_id;
    $redirect = "chat.php?oferta_id=$oferta_id&user_id=$user_id";
} else {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    include 'db.php';
    $user_id = $_SESSION['user_id'];
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : 0;
    $oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
    $mensaje = trim($_POST['mensaje'] ?? '');
    $sender_type = 'user';
    $receiver_type = 'empresa';
    $sender_id = $user_id;
    $receiver_id = $empresa_id;
    $redirect = "chat.php?oferta_id=$oferta_id&empresa_id=$empresa_id";
}

$archivo_path = null;
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $extensiones_permitidas = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','txt','zip','rar'];
    $nombre_archivo = $_FILES['archivo']['name'];
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    if (in_array($extension, $extensiones_permitidas)) {
        $target_dir = (isset($is_empresa) && $is_empresa ? '../' : '') . 'uploads/chat/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $uniq = uniqid('f_').'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $nombre_archivo);
        $archivo_path = $target_dir . $uniq;
        move_uploaded_file($_FILES['archivo']['tmp_name'], $archivo_path);
        $archivo_path = 'uploads/chat/' . $uniq;
    }
}

if ($oferta_id && $receiver_id && ($mensaje !== '' || $archivo_path)) {
    $sql = "INSERT INTO chat_messages 
      (oferta_id, sender_type, sender_id, receiver_type, receiver_id, message, file_path) 
      VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $oferta_id, $sender_type, $sender_id, $receiver_type, $receiver_id, $mensaje, $archivo_path
    ]);
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    exit('ok');
}
header("Location: $redirect");
exit;
?>