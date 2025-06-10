<?php
session_start();

$pageTitle = "Registro de Usuario";
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = trim($_POST['password']);
    $password2 = trim($_POST['password2']);

    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Introduce un correo electrónico válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $password2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ];
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents('http://localhost:3000/api/users/register', false, $context);
        if ($result === FALSE) {
            $error = "Error al conectar con el servidor. Intenta más tarde.";
        } else {
            $response = json_decode($result, true);
            if (isset($response['message'])) {
                $success = "¡Registro exitoso! Ya puedes iniciar sesión.";
            } else {
                $error = isset($response['error']) ? $response['error'] : "Error en el registro.";
            }
        }
    }
}

include 'componentes/header.php';
?>

<style>
.registro-container {
    max-width: 430px;
    margin: 60px auto;
    background: #fff;
    padding: 35px 30px 28px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.09);
    text-align: center;
}
.registro-container h1 {
    margin-bottom: 23px;
    color: #24344d;
    font-size: 1.7em;
}
.registro-container input {
    width: 94%;
    padding: 12px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1em;
}
.registro-container button {
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
    margin-bottom: 8px;
}
.registro-container button:hover {
    background:rgb(0, 66, 136);
}
.error-message {
    color: #d9534f;
    margin-bottom: 15px;
    font-weight: bold;
}
.success-message {
    color: #218838;
    margin-bottom: 15px;
    font-weight: bold;
}
.registro-container .login-link {
    display: block;
    margin-top: 15px;
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}
.registro-container .login-link:hover {
    text-decoration: underline;
}

</style>

<div class="registro-container">
    <h1>Registro de Usuario</h1>
    <?php if (!empty($error)) echo "<div class='error-message'>{$error}</div>"; ?>
    <?php if (!empty($success)) echo "<div class='success-message'>{$success}</div>"; ?>

    <form method="POST" novalidate>
        <input type="text" name="username" placeholder="Nombre de usuario" maxlength="40" required autocomplete="off" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
        <input type="email" name="email" placeholder="Correo electrónico" maxlength="100" required autocomplete="off" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="password" name="password2" placeholder="Repite la contraseña" required>
        <button type="submit">Registrarse</button>
    </form>
    <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión</a>
</div>