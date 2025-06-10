<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Proyectos";

$user_id = $_SESSION['user_id'];
$projects = file_get_contents(
    "http://localhost:3000/api/projects/assigned/$user_id");
$projects = json_decode($projects, true);

include 'componentes/header.php';
?>

<style>
    .projects-container {
        max-width: 900px;
        margin: 30px auto;
    }

    .projects-container h1 {
        color: #333;
        margin-bottom: 30px;
        text-align: center;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s ease;
        gap: 10px; 
        flex-wrap: wrap;
    }

    .card:hover {
        transform: translateY(-3px);
    }

    .project-info {
        display: grid;
        grid-template-columns: 180px 180px 120px;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }

    .project-info div {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .project-info div span.label {
        font-weight: 600;
        color: #666;
        font-size: 0.85em;
        margin-bottom: 4px;
    }

    .project-info div span.value {
        font-size: 1.1em;
        color: #007bff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .button-group {
        display: flex;
        flex-direction: row;
        gap: 8px;
        flex-shrink: 0;
    }

    .button, .chat-button {
        padding: 8px 14px;
        background: #007bff;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        transition: background 0.3s ease;
        white-space: nowrap;
        font-size: 0.95em;
    }

    .button:hover, .chat-button:hover {
        background: #0056b3;
    }
    @media (max-width: 700px) {
  .project-info {
    display: contents !important;
  }
  .button-group{
    gap:16vw;
  }
}


</style>
<div class="projects-container">
    <h1>Mis proyectos asignados</h1>
    <?php if (!empty($projects)): ?>
        <?php foreach ($projects as $project): ?>
            <div class="card">
                <div class="project-info">
                    <div>
                        <span class="label">Proyecto</span>
                        <span class="value"><?php echo htmlspecialchars($project['proyecto_nombre']); ?></span>
                    </div>
                    <div>
                        <span class="label">Contratante</span>
                        <span class="value"><?php echo htmlspecialchars($project['empresa_nombre']); ?></span>
                    </div>
                    <div>
                        <span class="label">Fecha límite</span>
                        <span class="value">
                            <?php
                            if (!empty($project['fecha_limite'])) {
                                echo date("d/m/Y", strtotime($project['fecha_limite']));
                            } else {
                                echo 'Sin fecha';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="button-group">
                    <a class="button" href="kanban.php?oferta_id=<?php echo $project['oferta_id']; ?>">Ver Planificación</a>
                    <a class="chat-button"
                        href="chat.php?empresa_id=<?php echo $project['empresa_id']; ?>&oferta_id=<?php echo $project['oferta_id']; ?>">
                        Chat
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <p>No tienes proyectos asignados aún.</p>
        </div>
    <?php endif; ?>
</div>