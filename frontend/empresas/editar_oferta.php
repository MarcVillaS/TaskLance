<?php
session_start();
include '../db.php';

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM ofertas WHERE id = ? AND empresa_id = ?");
$stmt->execute([$id, $_SESSION['empresa_id']]);
$oferta = $stmt->fetch();

if (!$oferta) {
    echo "Oferta no encontrada.";
    exit;
}

if ($_POST) {
    $stmt = $pdo->prepare("UPDATE ofertas SET nombre=?, descripcion=?, duracion=?, precio_hora=?, habilidades=? WHERE id=?");
    $stmt->execute([
        $_POST['nombre'],
        $_POST['descripcion'],
        $_POST['duracion'],
        $_POST['precio_hora'],
        $_POST['habilidades'],
        $id
    ]);
    header("Location: dashboard.php");
    exit;
}
?>

<h1>Editar Oferta</h1>
<form method="POST">
    <input type="text" name="nombre" value="<?php echo $oferta['nombre']; ?>" required>
    <textarea name="descripcion"><?php echo $oferta['descripcion']; ?></textarea>
    <input type="text" name="duracion" value="<?php echo $oferta['duracion']; ?>">
    <input type="number" step="0.01" name="precio_hora" value="<?php echo $oferta['precio_hora']; ?>">
    <input type="text" name="habilidades" value="<?php echo $oferta['habilidades']; ?>">
    <button type="submit">Guardar cambios</button>
</form>
