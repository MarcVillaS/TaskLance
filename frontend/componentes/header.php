<?php
if (!isset($pageTitle)) {
    $pageTitle = "TaskLance";
}
include_once __DIR__ . '/../db.php';

$notificaciones = [];
$max_dias = 3;

if (!isset($_SESSION['read_notifs'])) {
    $_SESSION['read_notifs'] = [];
}

$hashes_leidas = [];
$hashes_borradas = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT notif_hash FROM freelancer_notif_leidas WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $hashes_leidas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT notif_hash FROM freelancer_notif_borradas WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $hashes_borradas = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$read_notifs = array_unique(array_merge($_SESSION['read_notifs'], $hashes_leidas));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida'])) {
    $hash = $_POST['marcar_leida'];
    if (!in_array($hash, $_SESSION['read_notifs'])) {
        $_SESSION['read_notifs'][] = $hash;
    }
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $pdo->prepare("INSERT IGNORE INTO freelancer_notif_leidas (user_id, notif_hash) VALUES (?, ?)")
            ->execute([$user_id, $hash]);
    }
    if (strpos($hash, 'aceptado_') === 0 && isset($_SESSION['user_id']) && isset($_POST['oferta_id'])) {
        $oferta_id = (int)$_POST['oferta_id'];
        $user_id = (int)$_SESSION['user_id'];
        $pdo->prepare("UPDATE postulaciones SET leido_aceptado=1 WHERE oferta_id=? AND user_id=?")->execute([$oferta_id, $user_id]);
    }
    echo "ok";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_leidas'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $notifs_borrar = array_unique(array_merge($_SESSION['read_notifs'], $hashes_leidas));
        if (!empty($notifs_borrar)) {
            foreach ($notifs_borrar as $hash) {
                $pdo->prepare("INSERT IGNORE INTO freelancer_notif_borradas (user_id, notif_hash) VALUES (?, ?)")
                    ->execute([$user_id, $hash]);
            }
        }
    }
    echo "ok";
    exit;
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $hoy = date('Y-m-d');
    $future = date('Y-m-d', strtotime("+$max_dias days"));

    $sqlTareas = "
        SELECT t.title, t.due_date, o.nombre as proyecto
        FROM tasks t
        JOIN ofertas o ON t.oferta_id = o.id
        JOIN postulaciones p ON p.oferta_id = o.id
        WHERE p.user_id = ? AND p.estado = 'aceptado'
            AND t.status != 'completada'
            AND t.due_date IS NOT NULL AND t.due_date != '0000-00-00'
            AND t.due_date BETWEEN ? AND ?
        ORDER BY t.due_date ASC
    ";
    $stmtT = $pdo->prepare($sqlTareas);
    $stmtT->execute([$user_id, $hoy, $future]);
    while ($row = $stmtT->fetch(PDO::FETCH_ASSOC)) {
        $dias = (strtotime($row['due_date']) - strtotime($hoy)) / 86400;
        $dias = (int)$dias;
        $msg = "La tarea <b>" . htmlspecialchars($row['title']) . "</b> del proyecto <b>" .
            htmlspecialchars($row['proyecto']) . "</b> vence en $dias día(s)";
        $hash = md5($msg);
        $notificaciones[] = ['msg' => $msg, 'hash' => $hash];
    }

    $sqlProyectos = "
        SELECT o.nombre, o.fecha_limite
        FROM ofertas o
        JOIN postulaciones p ON p.oferta_id = o.id
        WHERE p.user_id = ? AND p.estado = 'aceptado'
            AND o.fecha_limite BETWEEN ? AND ?
    ";
    $stmtP = $pdo->prepare($sqlProyectos);
    $stmtP->execute([$user_id, $hoy, $future]);
    while ($row = $stmtP->fetch(PDO::FETCH_ASSOC)) {
        $dias = (strtotime($row['fecha_limite']) - strtotime($hoy)) / 86400;
        $dias = (int)$dias;
        $msg = "El proyecto <b>" . htmlspecialchars($row['nombre']) . "</b> vence en $dias día(s)";
        $hash = md5($msg);
        $notificaciones[] = ['msg' => $msg, 'hash' => $hash];
    }

    $sqlAceptados = "
        SELECT o.id as oferta_id, o.nombre as proyecto, e.nombre as empresa
        FROM postulaciones p
        JOIN ofertas o ON p.oferta_id = o.id
        JOIN empresas e ON o.empresa_id = e.id
        WHERE p.user_id = ? AND p.estado = 'aceptado'
        AND (p.leido_aceptado IS NULL OR p.leido_aceptado = 0)
    ";
    $stmtA = $pdo->prepare($sqlAceptados);
    $stmtA->execute([$user_id]);
    while ($row = $stmtA->fetch(PDO::FETCH_ASSOC)) {
        $msg = "¡Tu postulación en <b>" . htmlspecialchars($row['proyecto']) . "</b> (<b>" . htmlspecialchars($row['empresa']) . "</b>) ha sido <span style='color:green;font-weight:600;'>aceptada</span>!";
        $hash = 'aceptado_' . $row['oferta_id'] . '_' . md5($msg);
        $notificaciones[] = [
            'msg' => $msg . '<br><a href="kanban.php?oferta_id=' . $row['oferta_id'] . '">Ir a planificación</a>',
            'hash' => $hash,
            'aceptada_link' => "kanban.php?oferta_id=" . $row['oferta_id'],
            'oferta_id' => $row['oferta_id']
        ];
    }

    $sqlChats = "
        SELECT m.id, m.sender_type, m.sender_id, m.message, m.sent_at, e.nombre as empresa, o.nombre as proyecto, o.id as oferta_id, m.oferta_id, m.sender_type, m.sender_id
        FROM chat_messages m
        JOIN ofertas o ON m.oferta_id = o.id
        JOIN empresas e ON o.empresa_id = e.id
        WHERE m.receiver_type = 'user' AND m.receiver_id = ? 
          AND m.id > IFNULL(
            (SELECT MAX(leido_msg_id) FROM chat_leidos WHERE user_id = ? AND oferta_id = m.oferta_id AND empresa_id = e.id), 0
          )
        ORDER BY m.sent_at DESC
    ";
    $stmtC = $pdo->prepare($sqlChats);
    $stmtC->execute([$user_id, $user_id]);
    while ($row = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $msg = "Nuevo mensaje de <b>" . htmlspecialchars($row['empresa']) . "</b> en <b>" .
            htmlspecialchars($row['proyecto']) . "</b>:<br>\"" .
            htmlspecialchars(mb_strimwidth($row['message'], 0, 50, "...")) . "\"";
        $hash = md5("msgchat_{$row['oferta_id']}_{$row['empresa']}_{$row['id']}");
        $notificaciones[] = [
            'msg' => $msg,
            'hash' => $hash,
            'chatlink' => "chat.php?oferta_id=" . $row['oferta_id'] . "&empresa_id=" . $row['sender_id']
        ];
    }
}

$notificaciones = array_filter($notificaciones, function($n) use ($hashes_borradas) {
    return !in_array($n['hash'], $hashes_borradas);
});

$numNotif = count(array_filter($notificaciones, function($n) use ($read_notifs) {
    return !in_array($n['hash'], $read_notifs);
}));
$hayLeidas = count(array_filter($notificaciones, function($n) use ($read_notifs) {
    return in_array($n['hash'], $read_notifs);
})) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        body { margin: 0!important; font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        header {
            background: #007bff;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            padding: 20px 30px;
        }
        .tituloheader {
            color: white;
            cursor: pointer;
        }
        .tituloheader a {
            color: white;
            text-decoration: none;
        }
        .tituloheader a:hover {
            text-decoration: underline;
        }
        .notif-bell {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-right: 16px;
            font-size: 1.08em;
            vertical-align: middle;
        }
        .notif-bell .bell-icon {
            font-size: 1.32em;
            vertical-align: middle;
            color: #fff;
            transition: color 0.2s;
        }
        .notif-bell:hover .bell-icon {
            color: #ffecb3;
        }
        .notif-bell .notif-count {
            position: absolute;
            top: -5px;
            right: -6px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            min-width: 16px;
            height: 16px;
            font-size: 0.73em;
            font-weight: bold;
            padding: 0px 4px;
            line-height: 16px;
            text-align: center;
            border: 2px solid #007bff;
            box-shadow: 0 1px 4px #0002;
            z-index: 2;
        }
        .notif-popup {
            display: none;
            position: absolute;
            right: 10px;
            top: 38px;
            background: #fff;
            color: #333;
            min-width: 295px;
            max-width: 360px;
            box-shadow: 0 4px 16px 2px rgba(0,0,0,0.16);
            border-radius: 8px;
            z-index: 999;
            padding: 0;
            font-size: 0.97em;
            animation: notiffade .18s;
        }
        @keyframes notiffade {
            from { opacity: 0; transform: translateY(-8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .notif-popup ul { list-style: none; padding: 0 0 4px 0; margin: 0; }
        .notif-popup li {
            padding: 12px 18px 10px 18px;
            border-bottom: 1px solid #f2f2f2;
            word-break: break-word;
            transition: background 0.17s, opacity 0.18s;
            cursor: pointer;
        }
        .notif-popup li a {
            color: #007bff;
            text-decoration: underline;
            font-weight: bold;
        }
        .notif-popup li.leida {
            color: #aaa;
            opacity: 0.62;
            text-decoration: line-through;
            background: #f8f9fa;
            cursor: default;
        }
        .notif-popup li:last-child { border-bottom: none; }
        .notif-popup .no-notif {
            padding: 16px 18px; color: #aaa; text-align: center;
            font-style: italic;
        }
        .notif-popup:before {
            content: "";
            position: absolute;
            top: -10px;
            right: 20px;
            border-width: 0 10px 10px 10px;
            border-style: solid;
            border-color: transparent transparent #fff transparent;
            filter: drop-shadow(0 -2px 2px #0001);
        }
        .notif-popup .borrar-leidas-btn {
            display: block;
            width: 100%;
            background: none;
            border: none;
            color: #dc3545;
            font-weight: bold;
            text-align: center;
            font-size: 1em;
            padding: 13px 0 12px 0;
            cursor: pointer;
            border-top: 1px solid #eee;
            transition: background 0.16s, color 0.16s;
        }
        .notif-popup .borrar-leidas-btn:hover {
            background: #ffe3e3;
            color: #a71d2a;
        }
        nav.\32 nav {
    display: none;
}

        @media (max-width: 440px) {
            .notif-popup { left: 0; right: auto; min-width: 180px; }
            header {
            background: #007bff;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        nav:not(.\32 nav) {
        display: none;
    }
    nav.\32 nav {
        display: block;
    }
    }           
        
    </style>
</head>
<body>
<header>
<h1 class="tituloheader"><a href="principal/index.php" style="color:white;text-decoration:none;">TaskLance</a></h1>
    <nav>
        <span class="notif-bell" id="notifBell" title="Notificaciones">
            <span class="bell-icon" style="font-family: 'Segoe UI Symbol', Arial, sans-serif;">&#128276;</span>
            <?php if ($numNotif > 0): ?>
                <span class="notif-count" id="notifCount"><?= $numNotif ?></span>
            <?php endif; ?>
            <div class="notif-popup" id="notifPopup">
                <?php if (count($notificaciones) > 0): ?>
                <ul>
                    <?php foreach ($notificaciones as $n):
                        $esLeida = in_array($n['hash'], $read_notifs);
                    ?>
                        <li
                          data-hash="<?= $n['hash'] ?>"
                          class="<?= $esLeida ? 'leida' : '' ?>"
                          <?php if (!$esLeida): ?>
                            onclick="marcarLeida('<?= $n['hash'] ?>', this, <?= isset($n['oferta_id']) ? (int)$n['oferta_id'] : 'null' ?>);"
                          <?php endif; ?>>
                          <?php
                            if (isset($n['chatlink'])) {
                                echo $n['msg'] . '<br><a href="' . htmlspecialchars($n['chatlink']) . '">Ir al chat</a>';
                            } elseif (isset($n['aceptada_link'])) {
                                echo $n['msg'];
                            } else {
                                echo $n['msg'];
                            }
                          ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <div class="no-notif">No tienes notificaciones</div>
                <?php endif; ?>
                <?php if ($hayLeidas): ?>
                    <button class="borrar-leidas-btn" onclick="borrarLeidas();">Borrar notificaciones leídas</button>
                <?php endif; ?>
            </div>
        </span>
        <a href="inicio_freelancer.php">Perfil</a>
        <a href="dashboard.php">Mis proyectos</a>
        <a href="ofertas.php">Ofertas</a>
        <a href="logout.php">Salir</a>
    </nav>
    <nav class="2nav">      
        <a href="inicio_freelancer.php">Perfil</a>
        <a href="dashboard.php">Proyectos</a>
        <a href="ofertas.php">Ofertas</a>
</nav>
</header>
<div class="container">

<script>
document.addEventListener('DOMContentLoaded', function(){
    var bell = document.getElementById('notifBell');
    var popup = document.getElementById('notifPopup');
    bell.addEventListener('click', function(e){
        e.stopPropagation();
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(){
        popup.style.display = 'none';
    });
});

function marcarLeida(hash, elem, oferta_id) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            elem.classList.add('leida');
            elem.onclick = null;
            var notifCount = document.getElementById('notifCount');
            if (notifCount) {
                let n = parseInt(notifCount.textContent);
                if (n>0) notifCount.textContent = n-1;
                if (n-1<=0) notifCount.style.display = 'none';
            }
            if (!document.querySelector('.borrar-leidas-btn') && document.querySelectorAll('.notif-popup li.leida').length > 0) {
                let btn = document.createElement('button');
                btn.className = 'borrar-leidas-btn';
                btn.setAttribute('onclick', 'borrarLeidas();');
                btn.textContent = 'Borrar notificaciones leídas';
                document.getElementById('notifPopup').appendChild(btn);
            }
        }
    };
    var data = 'marcar_leida=' + encodeURIComponent(hash);
    if (hash.startsWith('aceptado_') && oferta_id !== null) {
        data += '&oferta_id=' + encodeURIComponent(oferta_id);
    }
    xhr.send(data);
}

function borrarLeidas() {
    if (!confirm('¿Seguro que deseas borrar todas las notificaciones leídas?')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var popup = document.getElementById('notifPopup');
            var lis = popup.querySelectorAll('ul li.leida');
            lis.forEach(function(li){ li.remove(); });
            if (popup.querySelectorAll('ul li').length === 0) {
                popup.innerHTML = '<div class="no-notif">No tienes notificaciones</div>';
            } else {
                if (popup.querySelectorAll('li.leida').length === 0) {
                    let btn = popup.querySelector('.borrar-leidas-btn');
                    if (btn) btn.remove();
                }
            }
        }
    };
    xhr.send('borrar_leidas=1');
}
</script>