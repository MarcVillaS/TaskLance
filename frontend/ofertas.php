<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Ofertas";

include 'db.php';

$user_id = $_SESSION['user_id'];
$aceptadas = [];
$stmtAceptadas = $pdo->prepare("SELECT oferta_id FROM postulaciones WHERE user_id = ? AND estado = 'aceptado'");
$stmtAceptadas->execute([$user_id]);
while ($row = $stmtAceptadas->fetch(PDO::FETCH_ASSOC)) {
    $aceptadas[] = $row['oferta_id'];
}

$busqueda = '';
if (isset($_GET['skill_search'])) {
    $busqueda = trim($_GET['skill_search']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oferta_id'])) {
    $oferta_id = (int) $_POST['oferta_id'];

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM postulaciones WHERE user_id = ? AND oferta_id = ?");
    $stmt_check->execute([$user_id, $oferta_id]);
    if ($stmt_check->fetchColumn() == 0) {
        $stmt_insert = $pdo->prepare("INSERT INTO postulaciones (user_id, oferta_id, estado) VALUES (?, ?, 'pendiente')");
        $stmt_insert->execute([$user_id, $oferta_id]);
    }

    header("Location: ofertas.php");
    exit;
}

if ($busqueda !== '') {
    $words = array_filter(array_map('trim', preg_split('/[\s,]+/', $busqueda)));
    $where = [];
    $params = [];
    foreach ($words as $w) {
        $where[] = "ofertas.habilidades LIKE ?";
        $params[] = "%" . $w . "%";
    }
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT ofertas.*, empresas.nombre AS empresa_nombre FROM ofertas 
            JOIN empresas ON ofertas.empresa_id = empresas.id 
            $where_sql
            ORDER BY fecha_publicacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT ofertas.*, empresas.nombre AS empresa_nombre FROM ofertas 
                         JOIN empresas ON ofertas.empresa_id = empresas.id 
                         ORDER BY fecha_publicacion DESC");
}
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT oferta_id, estado FROM postulaciones WHERE user_id = ?");
$stmt2->execute([$user_id]);
$postulaciones = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $postulaciones[$row['oferta_id']] = $row['estado'];
}

include 'componentes/header.php';
?>

<h1>Ofertas Disponibles</h1>

<form method="get" style="max-width: 500px; margin: 0 auto 38px auto; display: flex; gap: 8px;">
    <input type="text" name="skill_search" value="<?= htmlspecialchars($busqueda) ?>"
           placeholder="Buscar por habilidades... (ej: PHP, JavaScript)"
           style="flex:1; border-radius:8px; border:1px solid #bbb; font-size:1.07em;">
    <button type="submit" style="padding:10px 20px; border:none; border-radius:8px; background:#007bff; color:#fff; font-weight:bold; font-size:1.06em; cursor:pointer;">
        Buscar
    </button>
</form>

<?php
$ofertas_disponibles = array_filter($ofertas, function($oferta) use ($aceptadas) {
    return !in_array($oferta['id'], $aceptadas);
});
?>

<?php if (count($ofertas_disponibles) === 0): ?>
    <p>No hay ofertas disponibles en este momento.</p>
<?php else: ?>
    <div class="ofertas-grid">
        <?php foreach ($ofertas_disponibles as $oferta):
            $estado = $postulaciones[$oferta['id']] ?? null;
            ?>
            <div class="oferta-card">
                <div class="oferta-nombre">
                    <?= htmlspecialchars($oferta['nombre']) ?>
                    <span class="empresa-nombre">(<?= htmlspecialchars($oferta['empresa_nombre']) ?>)</span>
                </div>
                <div class="oferta-desc"><?= nl2br(htmlspecialchars($oferta['descripcion'])) ?></div>
                <ul class="oferta-info-list">
                    <li><span class="icon">&#128197;</span> <b>Fecha entrega:</b> <?= date('d/m/Y', strtotime($oferta['fecha_limite'])) ?></li>
                    <li><span class="icon">&#128181;</span> <b>Precio/hora:</b> <?= number_format($oferta['precio_hora'], 2) ?> €</li>
                    <li>
                        <span class="icon">&#9881;&#65039;</span>
                        <b>Habilidades:</b>
                        <span>
                            <?php foreach (explode(',', $oferta['habilidades']) as $hab): ?>
                                <span class="habilidad"><?= htmlspecialchars(trim($hab)) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </li>
                </ul>
                <?php if ($estado !== null): ?>
                    <div class="estado estado-pendiente">✔ Ya te has postulado a esta oferta (Estado: <?= htmlspecialchars($estado); ?>)</div>
                    <button class="postular-btn" disabled>Postularme</button>
                <?php else: ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="oferta_id" value="<?= $oferta['id']; ?>">
                        <button type="submit" class="postular-btn">Postularme</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
* {
    box-sizing: border-box;
}

body {
    overflow-x: hidden;
}

h1 {
    text-align: center;
    margin-bottom: 33px;
}

.ofertas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 28px;
    margin: 0 auto 40px auto;
    max-width: 1200px;
    padding: 0 12px;
}

.oferta-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
    padding: 28px 24px 18px 24px;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s, transform 0.18s;
    border-left: 6px solid #007bff;
    position: relative;
    max-width: 100%;
}

.oferta-card:hover {
    box-shadow: 0 8px 36px 0 rgba(0,123,255,0.16);
    transform: translateY(-6px) scale(1.02);
}

.oferta-nombre {
    font-size: 1.22em;
    font-weight: 600;
    color: #007bff;
    margin-bottom: 8px;
}

.empresa-nombre {
    color: #888;
    font-size: 0.98em;
    font-weight: 400;
}

.oferta-desc {
    color: #333;
    margin-bottom: 14px;
    min-height: 46px;
}

.oferta-info-list {
    list-style: none;
    padding: 0;
    margin: 0 0 16px 0;
    font-size: 0.98em;
}

.oferta-info-list li {
    margin-bottom: 7px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

.oferta-info-list .icon {
    margin-right: 7px;
    font-size: 1.09em;
    color: #007bff;
    vertical-align: middle;
}

.habilidad {
    background: #f1f5ff;
    color: #0056b3;
    padding: 5px 13px;
    border-radius: 14px;
    font-size: 0.97em;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
    word-break: break-word;
}

.estado {
    font-weight: 600;
    margin: 9px 0 0 0;
    font-size: 1em;
    padding: 5px 0;
}

.estado-aceptado {
    color: #28a745;
}

.estado-pendiente {
    color: #ff9800;
}

.postular-btn, .oferta-card .postular-btn {
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: 9px 20px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    margin-top: 12px;
    transition: background 0.2s;
    text-decoration: none;
    min-width: 120px;
    text-align: center;
    display: inline-block;
}

.postular-btn[disabled] {
    background: #ccc;
    color: #666;
    cursor: not-allowed;
}

.postular-btn:hover:not([disabled]) {
    background: #0056b3;
}

@media (max-width: 700px) {
    .ofertas-grid {
        grid-template-columns: 1fr;
        gap: 13px;
        max-width: 97vw;
    }

    .oferta-card {
        padding: 18px 8px 10px 12px;
        width: 100%;
    }
    
    
}
</style>

<?php include 'componentes/footer.php'; ?>
