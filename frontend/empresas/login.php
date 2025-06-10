<?php
session_start();

$pageTitle = "Iniciar Sesión";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
    $password = trim($_POST['password']);

    if (empty($correo) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $data = [
            'correo' => $correo,
            'password' => $password
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents('http://localhost:3000/api/empresas/login', false, $context);

        if ($result === FALSE) {
            $error = "Error al conectar con el servidor. Intenta más tarde.";
        } else {
            $response = json_decode($result, true);

            if (isset($response['empresa'])) {
                $_SESSION['empresa_id'] = $response['empresa']['id'];
                $_SESSION['empresa_nombre'] = $response['empresa']['nombre'];

                header('Location: inicio_empresas.php');
                exit;
            } else {
                $error = isset($response['error']) ? $response['error'] : "Credenciales inválidas.";
            }
        }
    }
}

include 'componentes/header.php';
?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
    .login-container {
        max-width: 400px;
        margin: 60px auto;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .login-container h1 {
        margin-bottom: 25px;
        color: #333;
    }

    .login-container input {
        width: 93%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1em;
    }

    .login-container button {
        width: 100%;
        padding: 12px;
        background: #007bff;
        border: none;
        color: #fff;
        border-radius: 8px;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .login-container button:hover {
        background: #0056b3;
    }

    .error-message {
        color: #d9534f;
        margin-bottom: 15px;
        font-weight: bold;
    }
</style>

<div class="login-container">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($error))
        echo "<div class='error-message'>{$error}</div>"; ?>

    <form method="POST" novalidate>
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>
    <p style="margin-top: 18px; font-size: 0.95em;">
        ¿No tienes cuenta? <a href="register.php" style="color:#007bff; text-decoration:underline;">Regístrate aquí</a>.
    </p>

</div>