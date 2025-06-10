<?php
session_start();

include '../db.php';

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$oferta_id = $_GET['id'] ?? null;
if (!$oferta_id) {
    echo "Oferta no válida.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ofertas WHERE id = ? AND empresa_id = ?");
$stmt->execute([$oferta_id, $_SESSION['empresa_id']]);
$oferta = $stmt->fetch();

if (!$oferta) {
    echo "No tienes permiso para ver esta oferta.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postulacion_id'], $_POST['nuevo_estado'])) {
    $postulacion_id = $_POST['postulacion_id'];
    $nuevo_estado = $_POST['nuevo_estado'];

    if (in_array($nuevo_estado, ['aceptado', 'rechazado', 'pendiente'])) {
        $update = $pdo->prepare("UPDATE postulaciones SET estado = ? WHERE id = ?");
        $update->execute([$nuevo_estado, $postulacion_id]);
        header("Location: ver_postulantes.php?id=" . $oferta_id);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT p.id, u.id as user_id, u.username, p.fecha_postulacion, p.estado
    FROM postulaciones p
    JOIN users u ON p.user_id = u.id
    WHERE p.oferta_id = ?
");
$stmt->execute([$oferta_id]);
$postulantes = $stmt->fetchAll();

include 'componentes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Postulantes para <?= htmlspecialchars($oferta['nombre']) ?></title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .container {
            padding: 20px 30px;
            max-width: 900px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 6px;
        }
        h2 {
            margin-top: 0;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #007bff;
            color: white;
        }
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        tbody tr:hover {
            background: #e6f0ff;
        }
        form {
            margin: 0;
        }
        button {
            padding: 6px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            transition: background-color 0.3s ease;
        }
        button.aceptar {
            background-color: #28a745;
        }
        button.aceptar:hover {
            background-color: #218838;
        }
        button.rechazar {
            background-color: #dc3545;
        }
        button.rechazar:hover {
            background-color: #c82333;
        }
        button.deshacer {
            background-color: #ffc107;
            color: #333;
        }
        button.deshacer:hover {
            background-color: #e0a800;
        }
        .chat-btn {
            background-color: #17a2b8;
            color: #fff;
            margin-right: 0;
            margin-top: 4px;
            padding: 6px 14px;
            border-radius: 3px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .chat-btn:hover {
            background-color: #117a8b;
        }
        .estado {
            text-transform: capitalize;
            font-weight: bold;
        }
        .estado.aceptado {
            color: #28a745;
        }
        .estado.pendiente {
            color: #ffc107;
        }
        .estado.rechazado {
            color: #dc3545;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* -- Responsive Table to Cards -- */
        @media (max-width: 700px) {
            .container {
                padding: 8px 1px;
                margin: 8px 2px;
            }
            table, thead, tbody, tr, th, td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
            }
            thead tr {
                display: none !important;
            }
            tbody tr {
                margin-bottom: 13px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.06);
                background: #fff;
                border: 1px solid #e4e8f0;
                overflow: hidden;
                padding: 8px 0 6px 0;
            }
            td {
                border: none;
                border-bottom: 1px solid #f1f4fa;
                position: relative;
                padding-left: 52%;
                font-size: 1em;
                min-height: 38px;
                margin: 0;
                background: none;
                display: flex;
                align-items: center;
            }
            td:last-child {
                border-bottom: none;
            }
            td:before {
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
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Postulantes para: <?= htmlspecialchars($oferta['nombre']) ?></h2>

    <?php if (count($postulantes) === 0): ?>
        <p>No hay postulantes aún.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Freelancer</th>
                    <th>Fecha de postulación</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($postulantes as $postulante): ?>
                    <tr>
                        <td data-label="Freelancer"><?= htmlspecialchars($postulante['username']) ?></td>
                        <td data-label="Fecha de postulación"><?= htmlspecialchars($postulante['fecha_postulacion']) ?></td>
                        <td data-label="Estado" class="estado <?= htmlspecialchars($postulante['estado']) ?>"><?= htmlspecialchars($postulante['estado']) ?></td>
                        <td data-label="Acciones">
                            <?php if ($postulante['estado'] === 'pendiente'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="postulacion_id" value="<?= $postulante['id'] ?>" />
                                    <input type="hidden" name="nuevo_estado" value="aceptado" />
                                    <button class="aceptar" type="submit">Aceptar</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="postulacion_id" value="<?= $postulante['id'] ?>" />
                                    <input type="hidden" name="nuevo_estado" value="rechazado" />
                                    <button class="rechazar" type="submit">Rechazar</button>
                                </form>
                            <?php elseif ($postulante['estado'] === 'rechazado'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="postulacion_id" value="<?= $postulante['id'] ?>" />
                                    <input type="hidden" name="nuevo_estado" value="pendiente" />
                                    <button class="deshacer" type="submit">Deshacer rechazo</button>
                                </form>
                            <?php elseif ($postulante['estado'] === 'aceptado'): ?>
                                <a class="chat-btn" href="chat.php?oferta_id=<?= $oferta_id ?>&user_id=<?= $postulante['user_id'] ?>">Chatear</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a class="back-link" href="dashboard.php">&larr; Volver</a>
</div>

</body>
</html>