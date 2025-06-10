<?php
session_start();

$pageTitle = "Mis Ofertas - TaskLance";
include '../db.php';

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_oferta_id'])) {
    $id_edit = (int) $_POST['editar_oferta_id'];
    $stmt_update = $pdo->prepare("UPDATE ofertas SET nombre=?, descripcion=?, fecha_limite=?, precio_hora=?, habilidades=? WHERE id=? AND empresa_id=?");
    $stmt_update->execute([
        $_POST['nombre'],
        $_POST['descripcion'],
        $_POST['fecha_limite'],
        $_POST['precio_hora'],
        $_POST['habilidades'],
        $id_edit,
        $empresa_id
    ]);
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completar_oferta_id'])) {
    $id_completar = (int) $_POST['completar_oferta_id'];
    $stmt_tasks = $pdo->prepare("DELETE FROM tasks WHERE oferta_id = ?");
    $stmt_tasks->execute([$id_completar]);
    $stmt_completar = $pdo->prepare("DELETE FROM ofertas WHERE id=? AND empresa_id=?");
    $stmt_completar->execute([$id_completar, $empresa_id]);
    header("Location: dashboard.php");
    exit;
}

include 'componentes/header.php';

$stmt = $pdo->prepare("SELECT * FROM ofertas WHERE empresa_id = ?");
$stmt->execute([$empresa_id]);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtAsignadas = $pdo->prepare("
    SELECT o.*, u.username as freelancer
    FROM ofertas o
    JOIN postulaciones p ON o.id = p.oferta_id
    JOIN users u ON p.user_id = u.id
    WHERE o.empresa_id = ? AND p.estado = 'aceptado'
");
$stmtAsignadas->execute([$empresa_id]);
$ofertas_asignadas = $stmtAsignadas->fetchAll(PDO::FETCH_ASSOC);
$ids_asignadas = array_column($ofertas_asignadas, 'id');
$ofertas_libres = array_filter($ofertas, function($o) use ($ids_asignadas) {
    return !in_array($o['id'], $ids_asignadas);
});
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <style>
body {
            margin: 0 !important;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            background: #eef2f7;
        }
        h1 {
            color: #007bff;
            margin-bottom: 25px;
            text-align: center;
        }
        a.crear-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 18px;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        a.crear-btn:hover {
            background-color: #0056b3;
        }
        ul.ofertas-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        ul.ofertas-list li {
            background: #fff;
            border-left: 6px solid #007bff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            width: 320px;
            box-sizing: border-box;
            transition: box-shadow 0.3s ease, transform 0.2s ease;
        }
        ul.ofertas-list li:hover {
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transform: translateY(-4px);
        }
        ul.ofertas-list strong {
            color: #0056b3;
            font-size: 1.2em;
        }
        p.precio {
            margin: 8px 0;
            font-weight: 600;
            color: #007bff;
        }
        p.fecha-limite {
            margin: 8px 0;
            font-weight: 600;
            color: #dc3545;
            font-size: 0.95em;
        }
        .freelancer-asignado {
            margin: 8px 0 0 0;
            color: #28a745;
            font-weight: bold;
            font-size: 1.05em;
        }
        div.acciones {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        div.acciones a,
        div.acciones button {
            flex: 1 1 auto;
            text-align: center;
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        div.acciones a:hover,
        div.acciones button:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-sizing: border-box;
            position: relative;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
        }
        .modal-content label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 600;
            color: #0056b3;
        }
        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content input[type="date"],
        .modal-content textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: inherit;
        }
        .modal-content textarea {
            resize: vertical;
            min-height: 80px;
        }
        .modal-content button {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .modal-content button:hover {
            background-color: #0056b3;
        }
        .close-btn {
            position: absolute;
            top: -10px;
            right: 12px;
            background: transparent;
            border: none;
            font-size: 28px;
            line-height: 1;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 51px !important;
            height: 46px;
            text-align: center;
        }
        .close-btn:hover {
            color: #007bff;
        }
        @media (max-width: 700px) {
            ul.ofertas-list {
                flex-direction: column;
                align-items: center;
            }
            ul.ofertas-list li {
                width: 90%;
            }
        }
        div.acciones {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        div.acciones button.edit-btn {
            font-size: 1em;
            background-color: #0056b3;
            color: white;
        }
        div.acciones button.edit-btn:hover {
            background-color: rgb(0, 55, 114);
        }
        div.acciones a.ver-postulantes {
            font-size: 1em;
            background-color: #17a2b8;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        div.acciones a.ver-postulantes:hover {
            background-color: #117a8b;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            margin: 30px auto 20px;
            padding: 0 15px;
        }
        .header-container h1 {
            margin: 0;
            color: #007bff;
        }
        a.crear-btn {
            padding: 10px 18px;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        a.crear-btn:hover {
            background-color: #0056b3;
        }
        .seccion-titulo {
            font-size: 1.19em;
            margin: 38px 0 12px 7%;
            color: #0056b3;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .ofertas-list.asignadas li {
            border-left: 6px solid #28a745;
        } 
        .estado-finalizada {
            background: #e4f7e5;
            color: #218838;
            border-left: 6px solid #28a745 !important;
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .completar-btn {
            background: #28a745 !important;
            border: none;
            color: #fff;
            font-weight: bold;
            border-radius: 6px;
            padding: 7px 13px;
            margin-top: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .completar-btn:hover {
            background: #218838 !important;
        }
    </style>
</head>

<body>

<div class="header-container">
    <h1>Mis Ofertas</h1>
    <a href="crear_oferta.php" class="crear-btn"> Crear Oferta</a>
</div>

<?php if (count($ofertas) === 0): ?>
    <p style="text-align:center;">No tienes ofertas creadas aún.</p>
<?php else: ?>
    <?php if (count($ofertas_libres) > 0): ?>
        <div class="seccion-titulo">Ofertas sin freelancer asignado</div>
        <ul class="ofertas-list">
            <?php foreach ($ofertas_libres as $oferta): ?>
                <li>
                    <strong><?php echo htmlspecialchars($oferta['nombre']); ?></strong>
                    <?php if (strtolower($oferta['estado']) == 'finalizada' || strtolower($oferta['estado']) == 'completada'): ?>
                        <div class="estado-finalizada">Finalizada</div>
                        <form method="POST" style="margin-top:8px;" onsubmit="return confirm('¿Seguro que quieres eliminar definitivamente esta oferta y todas sus tareas?');">
                            <input type="hidden" name="completar_oferta_id" value="<?php echo $oferta['id']; ?>">
                            <button type="submit" class="completar-btn">Completar</button>
                        </form>
                    <?php endif; ?>
                    <p class="precio"><?php echo number_format($oferta['precio_hora'], 2); ?> €</p>
                    <p class="fecha-limite">Fecha límite: <?php echo date('d/m/Y', strtotime($oferta['fecha_limite'])); ?></p>
                    <div class="acciones">
                        <button class="edit-btn" data-id="<?php echo $oferta['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($oferta['nombre'], ENT_QUOTES); ?>"
                            data-descripcion="<?php echo htmlspecialchars($oferta['descripcion'], ENT_QUOTES); ?>"
                            data-fecha_limite="<?php echo htmlspecialchars($oferta['fecha_limite'], ENT_QUOTES); ?>"
                            data-precio="<?php echo $oferta['precio_hora']; ?>"
                            data-habilidades="<?php echo htmlspecialchars($oferta['habilidades'], ENT_QUOTES); ?>"
                        >Editar</button>
                        <a class="ver-postulantes" href="ver_postulantes.php?id=<?php echo $oferta['id']; ?>">Ver postulantes</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (count($ofertas_asignadas) > 0): ?>
        <div class="seccion-titulo">Ofertas con freelancer asignado</div>
        <ul class="ofertas-list asignadas">
            <?php foreach ($ofertas_asignadas as $oferta): ?>
                <li>
                    <strong><?php echo htmlspecialchars($oferta['nombre']); ?></strong>
                    <?php if (strtolower($oferta['estado']) == 'finalizada' || strtolower($oferta['estado']) == 'completada'): ?>
                        <div class="estado-finalizada">Finalizada</div>
                        <form method="POST" style="margin-top:8px;" onsubmit="return confirm('¿Seguro que quieres eliminar definitivamente esta oferta?');">
                            <input type="hidden" name="completar_oferta_id" value="<?php echo $oferta['id']; ?>">
                            <button type="submit" class="completar-btn">Completar</button>
                        </form>
                    <?php endif; ?>
                    <p class="precio"><?php echo number_format($oferta['precio_hora'], 2); ?> €</p>
                    <p class="fecha-limite">Fecha límite: <?php echo date('d/m/Y', strtotime($oferta['fecha_limite'])); ?></p>
                    <div class="freelancer-asignado">Freelancer: <?php echo htmlspecialchars($oferta['freelancer']); ?></div>
                    <div class="acciones">
                        <button class="edit-btn" data-id="<?php echo $oferta['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($oferta['nombre'], ENT_QUOTES); ?>"
                            data-descripcion="<?php echo htmlspecialchars($oferta['descripcion'], ENT_QUOTES); ?>"
                            data-fecha_limite="<?php echo htmlspecialchars($oferta['fecha_limite'], ENT_QUOTES); ?>"
                            data-precio="<?php echo $oferta['precio_hora']; ?>"
                            data-habilidades="<?php echo htmlspecialchars($oferta['habilidades'], ENT_QUOTES); ?>"
                        >Editar</button>
                        <a class="ver-postulantes" href="ver_postulantes.php?id=<?php echo $oferta['id']; ?>">Ver postulantes</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal edición -->
<div class="modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-content">
        <button class="close-btn" id="closeModal" aria-label="Cerrar">&times;</button>
        <h2 id="modalTitle">Editar Oferta</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="editar_oferta_id" id="editar_oferta_id">

            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre" required>

            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="4"></textarea>

            <label for="fecha_limite">Fecha límite</label>
            <input type="date" name="fecha_limite" id="fecha_limite">

            <label for="precio_hora">Precio (€)</label>
            <input type="number" step="0.01" name="precio_hora" id="precio_hora">

            <label for="habilidades">Habilidades</label>
            <input type="text" name="habilidades" id="habilidades">

            <button type="submit">Guardar cambios</button>

            <button type="button" id="deleteBtn" style="background:#dc3545; margin-top: 10px;">
                Eliminar Oferta
            </button>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById('editModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');

            document.getElementById('editar_oferta_id').value = button.dataset.id;
            document.getElementById('nombre').value = button.dataset.nombre;
            document.getElementById('descripcion').value = button.dataset.descripcion;
            document.getElementById('fecha_limite').value = button.dataset.fecha_limite;
            document.getElementById('precio_hora').value = button.dataset.precio;
            document.getElementById('habilidades').value = button.dataset.habilidades;
        });
    });

    document.getElementById('closeModal').addEventListener('click', () => {
        const modal = document.getElementById('editModal');
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    });

    window.addEventListener('click', e => {
        const modal = document.getElementById('editModal');
        if (e.target === modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    document.getElementById('deleteBtn').addEventListener('click', () => {
        if (confirm('¿Seguro que quieres eliminar esta oferta? Esta acción no se puede deshacer.')) {
            const id = document.getElementById('editar_oferta_id').value;
            window.location.href = `eliminar_oferta.php?id=${id}`;
        }
    });

    window.addEventListener('keydown', e => {
        if (e.key === "Escape") {
            const modal = document.getElementById('editModal');
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    });
</script>

</body>
</html>

                