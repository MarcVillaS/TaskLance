<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$pageTitle = "Editar perfil";
include 'componentes/header.php';
include 'db.php';

$user_id = $_SESSION['user_id'];

$mensaje = '';
$error = '';
$stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $avatar = $user['avatar'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['avatar']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $permitidas)) {
            $dest = 'img/avatars/user_' . $user_id . '.' . $ext;
            move_uploaded_file($tmp, $dest);
            $avatar = $dest;
        } else {
            $error = "Formato de imagen no permitido.";
        }
    }

    $cambiar_contra = false;
    if (
        isset($_POST['password_actual']) && isset($_POST['password_nueva']) && isset($_POST['password_nueva2']) &&
        ($_POST['password_actual'] !== '' || $_POST['password_nueva'] !== '' || $_POST['password_nueva2'] !== '')
    ) {
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        $password_nueva2 = $_POST['password_nueva2'];

        if ($password_nueva !== $password_nueva2) {
            $error = "Las contraseñas nuevas no coinciden.";
        } elseif (strlen($password_nueva) < 6) {
            $error = "La nueva contraseña debe tener al menos 6 caracteres.";
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
            $stmt->execute([$user_id]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($password_actual, $hash)) {
                $error = "La contraseña actual no es correcta.";
            } else {
                $cambiar_contra = true;
            }
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, avatar=? WHERE id=?");
        $stmt->execute([$username, $email, $avatar, $user_id]);
        $mensaje = "¡Perfil actualizado!";
        $user = ['username'=>$username, 'email'=>$email, 'avatar'=>$avatar];

        if ($cambiar_contra) {
            $hash_nuevo = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash_nuevo, $user_id]);
            $mensaje .= " Contraseña cambiada correctamente.";
        }
    }
}

// Avatar cache busting
$avatar_path = $user['avatar'] ?: 'img/default-avatar.png';
$avatar_version = file_exists($avatar_path) ? filemtime($avatar_path) : time();
?>

<style>
.back-link {
    display: inline-block;
    margin: 14px 0 24px 0;
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
    font-size: 1.08em;
    transition: color 0.2s;
}
.back-link:hover {
    text-decoration: underline;
    color: #0056b3;
}
.form-perfil {
    max-width: 440px; margin: 32px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #0001;
    padding: 30px;
}
.form-perfil label { font-weight: bold; display: block; margin-top: 16px; }
.form-perfil input[type="text"], .form-perfil input[type="email"], .form-perfil input[type="password"] {
    width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #bbb; margin-top: 4px;
}
.form-perfil input[type="file"] { margin-top: 6px; }
.form-perfil .avatar-prev {
    width: 90px; height: 90px; border-radius: 50%; object-fit: cover; display: block; margin-bottom: 10px; margin-top: 7px;
    border: 2px solid #007bff; background: #eef;
}
.form-perfil button {
    margin-top: 18px; background: #007bff; color: #fff; border: none; border-radius: 7px; padding: 10px 25px;
    font-weight: bold; font-size: 1em; cursor: pointer;
}
.form-perfil .mensaje { margin-top: 14px; color: #218838; font-weight: 600; }
.form-perfil .error { margin-top: 14px; color: #d9534f; font-weight: 600; }
.form-perfil .separador {
    border-bottom: 1px solid #e3e3e3;
    margin: 24px 0 8px 0;
}
</style>

<a href="inicio_freelancer.php" class="back-link">&larr; Volver</a>

<div class="form-perfil">
    <h2>Editar perfil</h2>
    <?php if ($mensaje): ?><div class="mensaje"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        <label>Nombre de usuario:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required maxlength="40" />

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required maxlength="100" />

        <label>Avatar actual:</label>
        <img class="avatar-prev" src="<?= htmlspecialchars($avatar_path) . '?v=' . $avatar_version ?>" alt="Avatar actual" />

        <label>Cambiar avatar (jpg, png, webp):</label>
        <input type="file" name="avatar" accept="image/*" />

        <div class="separador"></div>
        <label style="margin-top:22px;">Cambiar contraseña:</label>
        <input type="password" name="password_actual" placeholder="Contraseña actual" autocomplete="off" />
        <input type="password" name="password_nueva" placeholder="Nueva contraseña" minlength="6" autocomplete="off" />
        <input type="password" name="password_nueva2" placeholder="Repite la nueva contraseña" minlength="6" autocomplete="off" />

        <button type="submit">Guardar cambios</button>
    </form>
</div>
<?php include 'componentes/footer.php'; ?>