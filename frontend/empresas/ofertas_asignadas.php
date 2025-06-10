<?php
session_start();

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';
$empresa_id = $_SESSION['empresa_id'];

try {
    $sql = "SELECT o.id, o.nombre, o.descripcion, o.fecha_limite, o.precio_hora, o.habilidades, u.username AS freelancer, u.id AS freelancer_id
            FROM ofertas o
            JOIN postulaciones p ON o.id = p.oferta_id
            JOIN users u ON p.user_id = u.id
            WHERE o.empresa_id = :empresa_id AND p.estado = 'aceptado'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empresa_id' => $empresa_id]);
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}

if (isset($_GET['export']) && $_GET['export'] === 'csv' && count($ofertas) > 0) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ofertas_asignadas.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Oferta', 'Nombre', 'Descripción', 'Fecha Límite', 'Pago (€)', 'Habilidades', 'Freelancer']);
    foreach ($ofertas as $oferta) {
        fputcsv($output, [
            $oferta['id'],
            $oferta['nombre'],
            $oferta['descripcion'],
            $oferta['fecha_limite'],
            $oferta['precio_hora'],
            $oferta['habilidades'],
            $oferta['freelancer']
        ]);
    }
    fclose($output);
    exit;
}

include 'componentes/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Ofertas Asignadas</title>
    <style>
body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f7f9fb; }
.container {
    max-width: 1120px;
    margin: 40px auto 0 auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 6px 32px rgba(0,0,0,0.08);
    padding: 38px 34px 44px 34px;
}
h2 {
    color: #24344d;
    font-weight: 700;
    margin-bottom: 22px;
    font-size: 1.6em;
    text-align: left;
}
.btn-export {
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 9px 20px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 18px;
    float: right;
    transition: background 0.2s;
    text-decoration: none;
}
.btn-export:hover {
    background: #0056b3;
    color: #fff;
}
.asignadas-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 1em;
    background: #fff;
}
.asignadas-table th, .asignadas-table td {
    padding: 13px 12px;
    border: 1px solid #e3e8ee;
}
.asignadas-table th {
    background: #f1f4fa;
    color: #24344d;
    font-size: 1.05em;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-align: left;
}
.asignadas-table tbody tr:hover {
    background: #f9fbfe;
}
.asignadas-table td {
    color: #2a3d5b;
    vertical-align: top;
}
.asignadas-table .freelancer-cell {
    font-weight: 600;
    color: #007bff;
}
.asignadas-table .habilidades-cell {
    font-size: 0.97em;
}
.asignadas-table .habilidad {
    display: inline-block;
    background: #e9f2ff;
    color: #0056b3;
    border-radius: 12px;
    padding: 3px 11px;
    margin: 2px 3px 2px 0;
    font-size: 0.97em;
}
.chat-btn {
    background: #0056b3;
    color: #fff;
    padding: 7px 17px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    font-size: 1em;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.16s;
}
.chat-btn:hover {
    background:rgb(2, 45, 92);
    color: #fff;
}
.no-ofertas-msg {
    text-align: center;
    color: #888;
    font-size: 1.13em;
    padding: 36px 0 44px 0;
}
.clearfix::after {
    content: "";
    display: table;
    clear: both;
}

/* RESPONSIVE TABLE TO CARDS */
@media (max-width: 950px) {
    .container { padding: 15px 6px 30px 6px; }
    .asignadas-table th, .asignadas-table td { font-size: 0.97em; padding: 10px 7px; }
}
@media (max-width: 700px) {
    .btn-export { float: none; width: 100%; margin-bottom: 12px;}
    .container { padding: 7px 2px 18px 2px; margin: 10px auto 0 auto; }
    .asignadas-table, .asignadas-table thead, .asignadas-table tbody, .asignadas-table tr, .asignadas-table th, .asignadas-table td {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box;
    }
    .asignadas-table thead tr {
        display: none !important;
    }
    .asignadas-table tbody tr {
        margin-bottom: 16px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        background: #fff;
        border: 1px solid #e4e8f0;
        overflow: hidden;
        padding: 8px 0 6px 0;
        display: block !important;
    }
    .asignadas-table td {
        border: none;
        border-bottom: 1px solid #f1f4fa;
        position: relative;
        padding-left: 50%;
        font-size: 1em;
        min-height: 38px;
        margin: 0;
        background: none;
        display: flex;
        align-items: center;
    }
    .asignadas-table td:last-child {
        border-bottom: none;
    }
    .asignadas-table td:before {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 47%;
        padding-right: 6px;
        white-space: pre-wrap;
        font-weight: 700;
        color: #24344d;
        font-size: 0.98em;
        text-align: left;
        content: attr(data-label);
    }
    .asignadas-table .habilidades-cell {
        flex-wrap: wrap;
    }
}
</style>
</head>

<body>

    <div class="container">
        <div class="clearfix">
            <h2 style="float:left;">Ofertas asignadas</h2>
            <?php if (count($ofertas) > 0): ?>
                <a href="?export=csv" class="btn-export" title="Exportar a CSV">&#128190; Exportar CSV</a>
            <?php endif; ?>
        </div>
        <?php if (count($ofertas) === 0): ?>
            <div class="no-ofertas-msg">No hay ofertas asignadas con freelancers actualmente.</div>
        <?php else: ?>
            <table class="asignadas-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Oferta</th>
                        <th>Descripción</th>
                        <th>Fecha límite</th>
                        <th>Pago (€)</th>
                        <th>Habilidades</th>
                        <th>Freelancer</th>
                        <th>Chat</th>
                    </tr>
                </thead>
                <tbody>
                <tbody>
                    <?php foreach ($ofertas as $oferta): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($oferta['id']) ?></td>
                            <td data-label="Oferta"><?= htmlspecialchars($oferta['nombre']) ?></td>
                            <td data-label="Descripción"><?= nl2br(htmlspecialchars($oferta['descripcion'])) ?></td>
                            <td data-label="Fecha límite"><?= date('d/m/Y', strtotime($oferta['fecha_limite'])) ?></td>
                            <td data-label="Pago (€)"><?= htmlspecialchars($oferta['precio_hora']) ?></td>
                            <td data-label="Habilidades" class="habilidades-cell">
                                <?php foreach (explode(',', $oferta['habilidades']) as $hab): ?>
                                    <span class="habilidad"><?= htmlspecialchars(trim($hab)) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td data-label="Freelancer" class="freelancer-cell"><?= htmlspecialchars($oferta['freelancer']) ?>
                            </td>
                            <td data-label="Chat">
                                <a class="chat-btn"
                                    href="chat.php?oferta_id=<?= $oferta['id'] ?>&user_id=<?= $oferta['freelancer_id'] ?>">
                                    Chatear
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>