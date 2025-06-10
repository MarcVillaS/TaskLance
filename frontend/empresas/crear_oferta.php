<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

if ($_POST) {
    if (
        empty($_POST['nombre']) ||
        empty($_POST['descripcion']) ||
        empty($_POST['fecha_limite']) ||
        empty($_POST['precio_hora']) ||
        empty($_POST['habilidades'])
    ) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO ofertas (nombre, descripcion, fecha_limite, precio_hora, habilidades, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['fecha_limite'],
            $_POST['precio_hora'],
            $_POST['habilidades'],
            $_SESSION['empresa_id']
        ]);
        header("Location: dashboard.php");
        exit;
    }
}

include 'componentes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nueva Oferta</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px 40px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            display: block;
            width: 100%;
            padding: 14px;
            background: #007bff;
            color: #fff;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            margin-top: 25px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .error {
            background: #ffe5e5;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffa4a4;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Crear Nueva Oferta</h2>

    <?php if (!empty($error)) : ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="nombre">Nombre de la oferta *</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="descripcion">Descripción *</label>
        <textarea name="descripcion" id="descripcion" required></textarea>

        <label for="fecha_limite">Fecha límite *</label>
        <input type="date" name="fecha_limite" id="fecha_limite" required>

        <label for="precio_hora">Precio (€) *</label>
        <input type="number" step="0.01" name="precio_hora" id="precio_hora" required>

        <label for="habilidades">Habilidades (separadas por coma) *</label>
        <input type="text" name="habilidades" id="habilidades" required>

        <button type="submit">Crear Oferta</button>
    </form>
    <a class="back-link" href="dashboard.php">← Volver</a>
</div>

</body>
</html>