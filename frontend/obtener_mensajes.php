<?php
if (file_exists('db.php')) {
    include 'db.php';
} elseif (file_exists('../db.php')) {
    include '../db.php';
}

$oferta_id = isset($_GET['oferta_id']) ? intval($_GET['oferta_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'user';

$sql = "SELECT * FROM chat_messages WHERE oferta_id=? AND (
        (sender_type='user' AND sender_id=? AND receiver_type='empresa' AND receiver_id=?) OR 
        (sender_type='empresa' AND sender_id=? AND receiver_type='user' AND receiver_id=?)
    ) ORDER BY sent_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$oferta_id, $user_id, $empresa_id, $empresa_id, $user_id]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT nombre FROM empresas WHERE id=?");
$stmt->execute([$empresa_id]);
$nombre_empresa = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT username FROM users WHERE id=?");
$stmt->execute([$user_id]);
$nombre_user = $stmt->fetchColumn();

foreach ($mensajes as $msg) {
    if ($tipo === 'empresa') {
        $es_emisor = ($msg['sender_type'] === 'empresa');
        $clase = $es_emisor ? 'empresa' : '';
        $autor = $es_emisor ? 'Tú' : $nombre_user;
    } else {
        $es_emisor = ($msg['sender_type'] === 'user');
        $clase = $es_emisor ? 'user' : '';
        $autor = $es_emisor ? 'Tú' : $nombre_empresa;
    }
    echo '<div class="mensaje-burbuja ' . htmlspecialchars($clase) . '">';
    echo '<span class="autor-nombre">' . htmlspecialchars($autor) . '</span><br>';
    echo nl2br(htmlspecialchars($msg['message']));
    if (!empty($msg['file_path'])) {
        $file_name = basename($msg['file_path']);
        $icon = "&#128206;";
        echo '<br><a href="' . htmlspecialchars($msg['file_path']) . '" target="_blank" style="display:inline-block;margin-top:4px;font-size:1.09em;color:#007bff;text-decoration:underline;">' . $icon . ' ' . htmlspecialchars($file_name) . '</a>';
    }
    echo '<div class="meta">' . date("d/m/Y H:i", strtotime($msg['sent_at'])) . '</div>';
    echo '</div>';
}
?>