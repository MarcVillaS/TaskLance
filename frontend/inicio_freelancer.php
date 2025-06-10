<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Inicio";
include 'componentes/header.php';

include 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT o.id, o.nombre, o.fecha_limite,
        (SELECT COUNT(*) FROM tasks t WHERE t.oferta_id = o.id) as total,
        (SELECT COUNT(*) FROM tasks t WHERE t.oferta_id = o.id AND t.status = 'completada') as completadas
    FROM ofertas o
    JOIN postulaciones p ON p.oferta_id = o.id
    WHERE p.user_id = ? AND p.estado='aceptado'
    ORDER BY o.fecha_limite
");
$stmt->execute([$user_id]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Avatar cache busting
$avatar_path = $user['avatar'] ?: 'img/default-avatar.png';
$avatar_version = file_exists($avatar_path) ? filemtime($avatar_path) : time();
?>

<style>
body {
    background: #f5f8fa;
}
.dashboard-container {
    max-width: 950px;
    margin: 36px auto 35px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 6px 32px rgba(0,0,0,0.09);
    padding: 36px 40px 34px 40px;
}
@media (max-width: 600px) {
    .dashboard-container { padding: 12px 2vw; }
}
.perfil-box {
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 38px;
}
.perfil-avatar {
    width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff55; background: #eef;
    box-shadow: 0 2px 12px #007bff18;
}
.perfil-info h2 { margin: 0 0 7px 0; font-size: 2em; color: #232e40; }
.perfil-info p { margin: 0 0 8px; color: #666; font-size: 1.04em;}
.perfil-link {
    display: inline-block; margin-top: 9px; color: #0056b3; text-decoration: none; font-weight: bold; font-size: 1.01em;
    transition: color 0.18s;
}
.perfil-link:hover { text-decoration: underline; color: #007bff; }
.btn-ir-ofertas {
    display: inline-block;
    background: linear-gradient(90deg, #007bff 70%, #00c6fb 100%);
    color: #fff;
    padding: 11px 32px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1.09em;
    text-decoration: none;
    margin: 10px 0 32px 0;
    box-shadow: 0 3px 12px #007bff25;
    border: none;
    transition: background 0.18s, box-shadow 0.15s;
    letter-spacing: 0.7px;
}
.btn-ir-ofertas:hover {
    background: linear-gradient(90deg, #0056b3 80%, #009adc 100%);
    box-shadow: 0 6px 24px #007bff18;
    color: #fff;
}
.dashboard-section-title {
    color: #007bff;
    font-size: 1.22em;
    font-weight: 600;
    margin-bottom: 18px;
    margin-top: 17px;
    letter-spacing: 0.5px;
}
.projects-list {
    margin-top: 7px;
}
.progress-project {
    margin-bottom: 32px;
    border-radius: 11px;
    background: #f8fbff;
    box-shadow: 0 1px 5px #007bff0a;
    padding: 20px 18px 12px 17px;
    border-left: 5px solid #007bff;
    transition: box-shadow 0.18s;
}
.progress-project:hover {
    box-shadow: 0 4px 18px #007bff22;
}
.progress-title {
    font-weight: 600; color: #24344d; font-size: 1.11em; margin-bottom: 5px;
}
.progress-bar-bg {
    width: 100%; background: #e9ecef; border-radius: 8px; height: 23px; margin-bottom: 7px; overflow: hidden;
}
.progress-bar-fill {
    background: linear-gradient(90deg,#007bff,#00c6fb); height: 100%; border-radius: 8px;
    transition: width 0.5s; color: #fff; font-weight: bold; text-align: center; line-height: 23px; font-size: 1em;
    box-shadow: 0 1px 8px #007bff1a;
}
.progress-info {
    font-size: 0.99em; color: #444; margin-top: 5px; display: flex; align-items: center; flex-wrap: wrap;
    justify-content: space-between;
}
.btn-kanban {
    display: inline-block; margin-top: 4px; background: #007bff; color: #fff; padding: 7px 17px; border-radius: 6px;
    text-decoration: none; font-weight: 600; transition: background 0.18s; font-size: 0.97em; margin-left: 5px;
    box-shadow: 0 1px 6px #007bff11;
}
.btn-kanban:hover { background: #0056b3; }
.no-projects-msg {
    color: #888; font-size: 1.08em; margin: 25px 0 35px 0; text-align: center;
}
@media (max-width:600px) {
    .perfil-box { flex-direction: column; gap: 13px; align-items: flex-start; }
    .progress-project { padding: 13px 7px 8px 7px; }
    .progress-info { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>

<div class="dashboard-container">
    <div class="perfil-box">
        <img class="perfil-avatar" src="<?= htmlspecialchars($avatar_path) . '?v=' . $avatar_version ?>" alt="Avatar">
        <div class="perfil-info">
            <h2>¡Hola, <?= htmlspecialchars($user['username']) ?>!</h2>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <a href="editar_perfil.php" class="perfil-link">Editar perfil</a>
        </div>
    </div>

    <div class="dashboard-section-title">Progreso de tus proyectos</div>
    <div class="projects-list">
    <?php if (count($proyectos) === 0): ?>
        <div class="no-projects-msg">Aún no tienes proyectos asignados.<br>¡<a href="ofertas.php" style="color:#007bff;font-weight:600;text-decoration:none;">Postúlate en una oferta</a> y comienza!</div>
        <?php else: ?>
        <?php foreach ($proyectos as $p): 
            $total = max(1, (int)$p['total']); 
            $completadas = (int)$p['completadas'];
            $porcentaje = intval(($completadas/$total) * 100);
        ?>
        <div class="progress-project">
            <div class="progress-title">
                <?= htmlspecialchars($p['nombre']) ?> 
                <span style="font-size:0.99em; color:#888;">(fecha límite: <?= htmlspecialchars($p['fecha_limite']) ?>)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width:<?= $porcentaje ?>%">
                    <?= $porcentaje ?>%
                </div>
            </div>
            <div class="progress-info">
                <span>
                    <?= $completadas ?> de <?= $total ?> tareas completadas
                </span>
                <a href="kanban.php?oferta_id=<?= $p['id'] ?>" class="btn-kanban">&#128204; Ir a planificación</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>
<?php include 'componentes/footer.php'; ?>