<?php
session_start();

$pageTitle = "Iniciar Sesión";
$error = "";

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header('Location: inicio_freelancer.php');
            exit;
        } else {
            $error = "Credenciales inválidas.";
        }
    }
}

include 'componentes/header.php';
?>

<style>
    body {
        background: #f2f2f2;
        margin: 0;
        font-family: 'Helvetica Neue', sans-serif;
    }

    .login-container {
        max-width: 414px;
        margin: 60px auto;
        background: #fff;
        padding: 35px 20px;
        border-radius: 16px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .login-container h1 {
        margin-bottom: 30px;
        font-size: 2em;
        color: #333;
    }

    .login-container input {
        width: 90%;
        padding: 14px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 10px;
        font-size: 1.05em;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .login-container input:focus {
        border-color: #007bff;
    }

    .login-container button {
        width: 100%;
        padding: 14px;
        background: #007bff;
        border: none;
        color: #fff;
        border-radius: 10px;
        font-size: 1.05em;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .login-container button:hover {
        background: #0056b3;
    }

    .error-message {
        color: #d9534f;
        margin-bottom: 18px;
        font-weight: bold;
    }

    .login-container p {
        margin-top: 20px;
        font-size: 0.95em;
        color: #555;
    }

    .login-container a {
        color: #007bff;
        text-decoration: underline;
    }

    @media (max-width: 414px) {
        body {
            margin: 0;
            font-family: 'Helvetica Neue', sans-serif;
            background: #f5f5f5;
        }

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
    }
</style>
<head><meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<div class="login-container">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($error))
        echo "<div class='error-message'>{$error}</div>"; ?>

    <form method="POST" novalidate>
        <input type="text" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>
    <p>
        ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>.
    </p>
</div>
