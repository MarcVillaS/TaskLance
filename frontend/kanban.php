<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Planificación";
include 'db.php'; 

$oferta_id = $_GET['oferta_id'] ?? 0;
if (!$oferta_id) {
    echo "Oferta no especificada.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, e.nombre AS empresa_nombre
    FROM ofertas o
    LEFT JOIN empresas e ON o.empresa_id = e.id
    WHERE o.id = ?
");
$stmt->execute([$oferta_id]);
$oferta = $stmt->fetch(PDO::FETCH_ASSOC);

function apiRequest($method, $url, $data = null)
{
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method,
        ],
    ];
    if ($data) {
        $options['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

if (isset($_POST['finalizar_oferta'])) {
    $stmt = $pdo->prepare("UPDATE ofertas SET estado = 'Finalizada' WHERE id = ?");
    $stmt->execute([$oferta_id]);
    header("Location: kanban.php?oferta_id=$oferta_id");
    exit;
}

if ($_POST && isset($_POST['title']) && !isset($_POST['finalizar_oferta'])) {
    if (!empty($_POST['task_id'])) {
        $task_id = intval($_POST['task_id']);

        $taskJson = file_get_contents("http://localhost:3000/api/tasks/oferta/$oferta_id");
        $tasks = json_decode($taskJson, true) ?? [];

        $currentTask = null;
        foreach ($tasks as $t) {
            if ($t['id'] == $task_id)
                $currentTask = $t;
        }

        if ($currentTask) {
            $data = [
                'title' => $_POST['title'],
                'description' => $_POST['description'] ?? '',
                'due_date' => $_POST['due_date'] ?? null,
                'status' => $currentTask['status'],
                'color' => $_POST['color'] ?? '#007bff'
            ];
            apiRequest('PUT', "http://localhost:3000/api/tasks/update/$task_id", $data);
        }

    } else {
        $data = [
            'oferta_id' => $oferta_id,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'due_date' => $_POST['due_date'] ?? null,
            'color' => $_POST['color'] ?? '#007bff'
        ];
        $result = apiRequest('POST', 'http://localhost:3000/api/tasks/create', $data);
        if ($result === false) {
            echo "Error creando la tarea. Respuesta backend: ";
            var_dump($http_response_header);
            exit;
        }
    }

    header("Location: kanban.php?oferta_id=$oferta_id");
    exit;
}

if (isset($_GET['update_task_id']) && isset($_GET['new_status'])) {
    $taskId = intval($_GET['update_task_id']);
    $newStatus = $_GET['new_status'];

    $taskJson = file_get_contents("http://localhost:3000/api/tasks/oferta/$oferta_id");
    $tasks = json_decode($taskJson, true) ?? [];

    $task = null;
    foreach ($tasks as $t) {
        if ($t['id'] == $taskId)
            $task = $t;
    }

    if ($task) {
        $data = [
            'title' => $task['title'],
            'description' => $task['description'],
            'status' => $newStatus,
            'due_date' => $task['due_date'] ?? null,
            'color' => $task['color'] ?? '#007bff'
        ];
        apiRequest('PUT', "http://localhost:3000/api/tasks/update/$taskId", $data);
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        http_response_code(200);
        exit;
    }
    header("Location: kanban.php?oferta_id=$oferta_id");
    exit;
}

include 'componentes/header.php';

$tasksJson = file_get_contents("http://localhost:3000/api/tasks/oferta/$oferta_id");
$tasks = json_decode($tasksJson, true);

if (!is_array($tasks))
    $tasks = [];

$pendientes = array_filter($tasks, fn($t) => $t['status'] === 'pendiente');
$en_progreso = array_filter($tasks, fn($t) => $t['status'] === 'en_progreso');
$completadas = array_filter($tasks, fn($t) => $t['status'] === 'completada');
?>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f4f4;
}
header {
    background: #007bff;
    color: #fff;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header h1 {
    margin: 0;
    font-size: 1.5em;
}
nav a {
    color: #fff;
    text-decoration: none;
    margin-left: 20px;
    font-weight: bold;
}
nav a:hover {
    text-decoration: underline;
}
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px 20px 0 20px;
}
.project-info-box {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    border-radius: 12px;
    margin: 10px auto 30px auto;
    max-width: 820px;
    padding: 30px 38px 18px 38px;
}
.project-info-title {
    font-size: 1.7em;
    margin: 0 0 7px 0;
    color: #007bff;
    font-weight: 700;
}
.project-info-details {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    font-size: 1.09em;
    color: #333;
}
.project-info-details > div {
    margin-bottom: 8px;
}
.project-info-details .label {
    color: #777;
    font-weight: 600;
    margin-right: 4px;
}
.project-info-desc {
    margin-top: 26px;
    color: #444;
    white-space: pre-line;
}
.finalizar-box {
    margin-top: 15px;
}
.finalizar-btn {
    background: #007bff; 
    color: #fff; 
    border: none; 
    border-radius: 7px; 
    padding: 10px 28px; 
    font-size: 1.04em; 
    font-weight: bold;
    cursor: pointer;
}
.finalizado-msg {
    margin-top: 20px; 
    font-weight: bold; 
    color:rgb(0, 61, 126);
}
h1 {
    margin-bottom: 20px;
}
.kanban-board {
    display: flex;
    flex-direction: row;
    gap: 20px;
    flex-wrap: nowrap;
    justify-content: space-between;
}
.column {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    width: 32%;
    min-width: 200px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    min-height: 300px;
    display: flex;
    flex-direction: column;
}
.column h2 {
    text-align: center;
    margin-bottom: 15px;
}
.task-card {
    position: relative;
    background: #ffffff;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 5px solid #007bff;
    border-radius: 8px;
    cursor: grab;
    transition: box-shadow 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}
.task-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}
.task-card:active {
    cursor: grabbing;
}
.task-card strong {
    font-size: 1.1em;
    display: block;
    margin-bottom: 5px;
}
.task-card small {
    display: block;
    margin-bottom: 5px;
    color: #666;
}
.task-card a {
    text-decoration: none;
    color: #007bff;
    margin-right: 5px;
    font-size: 0.9em;
}
.task-card .delete-task {
    position: absolute;
    top: 6px;
    right: 10px;
    font-size: 18px;
    color: #888;
    cursor: pointer;
    transition: color 0.2s ease;
}
.task-card .delete-task:hover {
    color: #dc3545;
}
.column.drag-over {
    background-color: #e0f7fa;
}
.due-soon {
    background-color: #fff3cd !important;
}
.due-sooner {
    background-color: #ffe5b4 !important;
}
.due-imminent {
    background-color: #f8d7da !important;
}
#modal,
#create-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
#modal-content,
#create-modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    position: relative;
}
#modal-content h2,
#create-modal-content h2 {
    margin-top: 0;
}
#modal-content label,
#create-modal-content label {
    display: block;
    margin-top: 10px;
}
#modal-content input[type="text"],
#modal-content textarea,
#modal-content input[type="date"],
#create-modal-content input[type="text"],
#create-modal-content textarea,
#create-modal-content input[type="date"] {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    box-sizing: border-box;
}
#modal-content button,
#create-modal-content button {
    margin-top: 15px;
    padding: 10px 20px;
    background: #007bff;
    border: none;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}
#modal-close,
#create-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    color: #666;
}
#open-create-modal-btn {
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1em;
}
@media (max-width: 700px) {
    .kanban-board {
        flex-direction: column;
        flex-wrap: nowrap;
    }
    .column {
        width: 92%;
        min-width: unset;
    }
    html,
body {
width:100%;
overflow-x: hidden;
}
}
</style>

<div class="project-info-box">
    <div class="project-info-title"><?= htmlspecialchars($oferta['nombre']) ?></div>
    <div class="project-info-details">
        <div><span class="label">Contratante:</span> <?= htmlspecialchars($oferta['empresa_nombre'] ?? 'N/A') ?></div>
        <div><span class="label">Fecha entrega:</span>
            <?= $oferta['fecha_limite'] ? (new DateTime($oferta['fecha_limite']))->format('d/m/Y') : 'No definida' ?>
        </div>
        <div>
            <span class="label">Estado:</span>
            <?= htmlspecialchars($oferta['estado'] ?? 'abierta') ?>
        </div>
    </div>
    <?php if ($oferta['descripcion']): ?>
        <div class="project-info-desc"><?= nl2br(htmlspecialchars($oferta['descripcion'])) ?></div>
    <?php endif; ?>
    <?php if ($oferta['estado'] !== 'Finalizada'): ?>
        <form method="POST" class="finalizar-box">
            <button type="submit" name="finalizar_oferta" class="finalizar-btn">
                Marcar como Finalizada
            </button>
        </form>
    <?php else: ?>
        <div class="finalizado-msg">
            ¡Trabajo finalizado!
        </div>
    <?php endif; ?>
</div>

<h1>Tareas</h1>

<button id="open-create-modal-btn">Crear nueva tarea</button>

<div class="kanban-board">
    <div class="column" data-status="pendiente">
        <h2>Pendientes</h2>
        <?php renderTasks($pendientes); ?>
    </div>
    <div class="column" data-status="en_progreso">
        <h2>En progreso</h2>
        <?php renderTasks($en_progreso); ?>
    </div>
    <div class="column" data-status="completada">
        <h2>Completadas</h2>
        <?php renderTasks($completadas); ?>
    </div>
</div>

<!-- Modal editar tarea -->
<div id="modal">
    <div id="modal-content">
        <span id="modal-close">&times;</span>
        <h2>Editar Tarea</h2>
        <form id="edit-task-form" method="POST">
            <label for="modal-color">Color</label>
            <input type="color" name="color" id="modal-color" value="#007bff">
            <input type="hidden" name="task_id" id="modal-task-id">
            <label for="modal-title">Título</label>
            <input type="text" name="title" id="modal-title" required>
            <label for="modal-description">Descripción</label>
            <textarea name="description" id="modal-description"></textarea>
            <label for="modal-due_date">Fecha límite</label>
            <input type="date" name="due_date" id="modal-due_date">
            <button type="submit">Guardar Cambios</button>
        </form>
    </div>
</div>

<!-- Modal crear tarea -->
<div id="create-modal">
    <div id="create-modal-content">
        <span id="create-modal-close">&times;</span>
        <h2>Crear nueva tarea</h2>
        <form id="create-task-form" method="POST">
            <label for="create-color">Color</label>
            <input type="color" name="color" id="create-color" value="#007bff">
            <label for="create-title">Título</label>
            <input type="text" name="title" id="create-title" required>
            <label for="create-description">Descripción</label>
            <textarea name="description" id="create-description"></textarea>
            <label for="create-due_date">Fecha límite</label>
            <input type="date" name="due_date" id="create-due_date">
            <button type="submit">Crear tarea</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>

document.querySelectorAll('.column').forEach(col => {
    new Sortable(col, {
        group: 'kanban',
        animation: 150,
        draggable: ".task-card",
        onEnd: function (evt) {
            const el = evt.item;
            const taskId = el.getAttribute('data-task-id');
            const newStatus = evt.to.getAttribute('data-status');
            if (evt.from !== evt.to) {
                fetch(`kanban.php?oferta_id=<?= $oferta_id; ?>&update_task_id=$
                {taskId}&new_status=${newStatus}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Error en la actualización');
                })
                .catch(err => {
                    alert('Error al actualizar la tarea');
                    location.reload();
                });
            }
            }
        });
    });

    document.querySelectorAll('.delete-task').forEach(icon => {
        icon.addEventListener('click', function (e) {
            e.stopPropagation();
            const taskId = this.getAttribute('data-task-id');

            if (confirm("¿Seguro que deseas eliminar esta tarea?")) {
                fetch(`http://localhost:3000/api/tasks/delete/${taskId}`, {
                    method: 'DELETE'
                })
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo eliminar');
                        location.reload();
                    })
                    .catch(err => {
                        alert("Error al eliminar la tarea.");
                        console.error(err);
                    });
            }
        });
    });

    // Modal editar tarea
    const tasks = document.querySelectorAll('.task-card');
    const modal = document.getElementById('modal');
    const modalClose = document.getElementById('modal-close');
    const form = document.getElementById('edit-task-form');
    const modalTaskId = document.getElementById('modal-task-id');
    const modalTitle = document.getElementById('modal-title');
    const modalDescription = document.getElementById('modal-description');
    const modalDueDate = document.getElementById('modal-due_date');
    const modalColor = document.getElementById('modal-color');

    tasks.forEach(task => {
        task.addEventListener('click', openEditModal);
    });

    function openEditModal(e) {
        const task = e.currentTarget;
        modalTaskId.value = task.getAttribute('data-task-id');
        modalTitle.value = task.getAttribute('data-title');
        modalDescription.value = task.getAttribute('data-description');
        modalDueDate.value = task.getAttribute('data-due_date') || '';
        modalColor.value = task.getAttribute('data-color') || '#007bff';

        modal.style.display = 'flex';
    }

    modalClose.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', e => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Modal crear tarea
    const createModal = document.getElementById('create-modal');
    const createModalClose = document.getElementById('create-modal-close');
    const openCreateBtn = document.getElementById('open-create-modal-btn');
    const createForm = document.getElementById('create-task-form');

    openCreateBtn.addEventListener('click', () => {
        createForm.reset();
        createModal.style.display = 'flex';
    });

    createModalClose.addEventListener('click', () => {
        createModal.style.display = 'none';
    });

    window.addEventListener('click', e => {
        if (e.target === createModal) {
            createModal.style.display = 'none';
        }
    });
</script>

<?php
function renderTasks($tasks)
{
    foreach ($tasks as $task):
        $dueDate = isset($task['due_date']) && $task['due_date'] !== null ? $task['due_date'] : null;
        $classDue = '';
        if ($dueDate) {
            $diffDays = (strtotime($dueDate) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            if ($diffDays < 0)
                $classDue = 'due-imminent';
            else if ($diffDays <= 1)
                $classDue = 'due-imminent';
            else if ($diffDays <= 3)
                $classDue = 'due-sooner';
            else if ($diffDays <= 7)
                $classDue = 'due-soon';
        }
        ?>
        <div class="task-card <?php echo $classDue; ?>" draggable="true" data-task-id="<?php echo $task['id']; ?>"
            data-status="<?php echo $task['status']; ?>" data-title="<?php echo htmlspecialchars($task['title']); ?>"
            data-description="<?php echo htmlspecialchars($task['description']); ?>"
            data-due_date="<?php echo htmlspecialchars($dueDate); ?>"
            data-color="<?php echo htmlspecialchars($task['color'] ?? '#007bff'); ?>"
            style="border-left-color: <?php echo htmlspecialchars($task['color'] ?? '#007bff'); ?>;">
            <span class="delete-task" data-task-id="<?php echo $task['id']; ?>">&times;</span>
            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
            <small><?php echo htmlspecialchars($task['description']); ?></small>
            <?php
            if ($dueDate) {
                $dateObj = new DateTime($dueDate);
                echo "<small><em>Fecha límite: " . $dateObj->format('Y-m-d') . "</em></small>";
            }
            ?>
        </div>
        <?php
    endforeach;
}
?>

<?php include 'componentes/footer.php'; ?>