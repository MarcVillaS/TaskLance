<?php
session_start();
if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Inicio Empresa";
include 'componentes/header.php';
include '../db.php';

$empresa_id = $_SESSION['empresa_id'];

$stmt = $pdo->prepare("SELECT nombre, correo, avatar FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM ofertas WHERE empresa_id = ? AND estado = 'abierta') AS abiertas,
        (SELECT COUNT(*) FROM ofertas WHERE empresa_id = ? AND estado = 'asignada') AS asignadas
");
$stmt->execute([$empresa_id, $empresa_id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT id, nombre, fecha_limite, estado 
    FROM ofertas
    WHERE empresa_id = ?
    ORDER BY fecha_limite DESC
    LIMIT 5
");
$stmt->execute([$empresa_id]);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Avatar cache busting
$avatar_path = $empresa['avatar'] ?: 'img/default-avatar.jpg';
$avatar_version = file_exists($avatar_path) ? filemtime($avatar_path) : time();
?>

<style>
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
.empresa-box {
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 38px;
}
.empresa-avatar {
    width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid #007bff55; background: #eef;
    box-shadow: 0 2px 12px #007bff18;
}
.empresa-info h2 { margin: 0 0 7px 0; font-size: 2em; color: #232e40; }
.empresa-info p { margin: 0 0 8px; color: #666; font-size: 1.04em;}
.empresa-link {
    display: inline-block; margin-top: 9px; color: #0056b3; text-decoration: none; font-weight: bold; font-size: 1.01em;
    transition: color 0.18s;
}
.empresa-link:hover { text-decoration: underline; color: #007bff; }
.stats-box {
    display: flex; gap: 40px; margin-bottom: 30px; flex-wrap: wrap;
}
.stats-card {
    background: #f8fbff;
    border-left: 5px solid #007bff;
    border-radius: 10px;
    padding: 17px 26px;
    min-width: 170px;
    box-shadow: 0 1px 5px #007bff0a;
    font-size: 1.13em;
    font-weight: bold;
    color: #24344d;
    margin-bottom: 10px;
}
.stats-card span {
    font-size: 2em;
    color: #007bff;
    margin-right: 8px;
    vertical-align: bottom;
}
.dashboard-section-title {
    color: #007bff;
    font-size: 1.22em;
    font-weight: 600;
    margin-bottom: 18px;
    margin-top: 17px;
    letter-spacing: 0.5px;
}
.ofertas-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 7px;
    background: #fff;
}
.ofertas-table th, .ofertas-table td {
    padding: 10px 10px;
    border: 1px solid #e3e8ee;
}
.ofertas-table th {
    background: #f1f4fa;
    color: #24344d;
    font-size: 1.03em;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-align: left;
}
.ofertas-table tbody tr:hover {
    background: #f9fbfe;
}
.ofertas-table td {
    color: #2a3d5b;
    vertical-align: top;
}
.btn-nueva-oferta {
    display: inline-block;
    background: linear-gradient(90deg, #007bff 70%, #00c6fb 100%);
    color: #fff;
    padding: 10px 26px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1em;
    text-decoration: none;
    margin: 18px 0 18px 0;
    box-shadow: 0 3px 12px #007bff25;
    border: none;
    transition: background 0.18s, box-shadow 0.15s;
    letter-spacing: 0.5px;
}
.btn-nueva-oferta:hover {
    background: linear-gradient(90deg, #0056b3 80%, #009adc 100%);
    box-shadow: 0 6px 24px #007bff18;
    color: #fff;
}
@media (max-width:600px) {
    .empresa-box { flex-direction: column; gap: 13px; align-items: flex-start; }
    .stats-box { flex-direction: column; gap: 12px; }
}
</style>

<div class="dashboard-container">
    <div class="empresa-box">
        <img class="empresa-avatar" src="<?= htmlspecialchars($avatar_path) . '?v=' . $avatar_version ?>" alt="Avatar empresa">
        <div class="empresa-info">
            <h2><?= htmlspecialchars($empresa['nombre'] ?? 'Empresa') ?></h2>
            <p>Email: <?= htmlspecialchars($empresa['correo'] ?? '') ?></p>
            <a href="editar_perfil_empresa.php" class="empresa-link">Editar perfil</a>
        </div>
    </div>

    <div class="stats-box">
        <div class="stats-card"><span>&#128188;</span> Ofertas abiertas: <?= $stats['abiertas'] ?? 0 ?></div>
        <div class="stats-card"><span>&#9989;</span> Ofertas asignadas: <?= $stats['asignadas'] ?? 0 ?></div>
    </div>

    <a href="crear_oferta.php" class="btn-nueva-oferta">&#10010; Publicar nueva oferta</a>

    <div class="dashboard-section-title">Últimas ofertas publicadas</div>
    <?php if (count($ofertas) === 0): ?>
        <p style="color:#888;">Aún no has publicado ofertas.</p>
    <?php else: ?>
        <table class="ofertas-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha límite</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ofertas as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['nombre']) ?></td>
                    <td><?= htmlspecialchars($o['fecha_limite']) ?></td>
                    <td>
                        <?php
                            $st = $o['estado'];
                            $color = $st == 'abierta' ? '#007bff' : ($st == 'asignada' ? '#28a745' : '#888');
                        ?>
                        <span style="color:<?= $color ?>;font-weight:600"><?= ucfirst($st) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include 'componentes/footer.php'; ?>