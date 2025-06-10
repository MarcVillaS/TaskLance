<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$oferta_id = isset($_GET['oferta_id']) ? intval($_GET['oferta_id']) : 0;
$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;

$stmt = $pdo->prepare("SELECT nombre FROM empresas WHERE id=?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT nombre FROM ofertas WHERE id=?");
$stmt->execute([$oferta_id]);
$oferta = $stmt->fetch(PDO::FETCH_ASSOC);

include 'componentes/header.php';
?>

<style>
body {
    background: #f3f6fb;
}
.chat-box {
    max-width: 660px;
    margin: 40px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.09);
    padding: 0 0 20px 0;
    display: flex;
    flex-direction: column;
    height: 80vh;
}
.chat-header {
    background: #007bff;
    color: #fff;
    padding: 24px 32px;
    border-radius: 12px 12px 0 0;
    margin-bottom: 0;
}
.chat-header .proyecto {
    font-size: 1.15em;
    margin-bottom: 3px;
    font-weight: 600;
}
.chat-header .info {
    font-size: 0.97em;
    opacity: 0.93;
}
#mensajes {
    flex: 1;
    overflow-y: auto;
    padding: 32px 32px 10px 32px;
    background: #f8fafb;
    display: flex;
    flex-direction: column;
    gap: 16px;
    scroll-behavior: smooth;
    max-height: 60vh;
    min-height: 0;
}
.mensaje-burbuja {
    max-width: 65%;
    padding: 12px 16px;
    border-radius: 16px;
    background: #eaf2fb;
    align-self: flex-start;
    position: relative;
    margin-bottom: 2px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    word-break: break-word;
}
.mensaje-burbuja.user {
    background: #007bff;
    color: white;
    align-self: flex-end;
}
.mensaje-burbuja .meta {
    font-size: 0.8em;
    color: #8ba0b5;
    margin-top: 4px;
    text-align: right;
}
.mensaje-burbuja.user .meta {
    color: #e6e6e6;
}
.mensaje-burbuja .autor-nombre {
    font-weight: 600;
    margin-right: 6px;
    font-size: 0.93em;
    color: #007bff;
}
.mensaje-burbuja.user .autor-nombre {
    color: #fff;
}
.enviar-mensaje {
    display: flex;
    gap: 12px;
    padding: 18px 32px 0 32px;
    background: #fff;
    border-radius: 0 0 12px 12px;
    border-top: 1px solid #f0f0f0;
    align-items: flex-end;
}
.enviar-mensaje textarea {
    flex: 1;
    resize: none;
    min-height: 32px;
    max-height: 120px;
    border-radius: 8px;
    border: 1px solid #c6d2e8;
    padding: 3px;
    font-size: 1em;
}
.enviar-mensaje button {
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0 24px;
    font-weight: bold;
    cursor: pointer;
    font-size: 1em;
    transition: background 0.2s;
    margin-left: 3px;
    height: 40px;
}
.enviar-mensaje button:hover {
    background: #0056b3;
}
.adjuntar-archivo-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.7em;
    color: #007bff;
    padding: 0 8px 0 4px;
    border-radius: 50%;
    transition: background 0.16s;
    position: relative;
}
.adjuntar-archivo-btn:hover {
    background: #eaf2fb;
}
input[type="file"].archivo-input {
    display: none;
}
.adjuntar-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}
.nombre-archivo-adjunto {
    font-size: 0.97em;
    margin-left: 6px;
    color: #007bff;
    white-space: nowrap;
    overflow: hidden;
    max-width: 120px;
    text-overflow: ellipsis;
}
@media (max-width: 700px) {
    .chat-box { max-width: 99vw; }
    .chat-header, .enviar-mensaje, #mensajes { padding-left: 10px; padding-right: 10px;}
    .enviar-mensaje textarea { font-size: 0.97em; }
    .adjuntar-archivo-btn { font-size: 1.3em;}
    .nombre-archivo-adjunto { font-size: 0.93em; max-width: 60px;}
}
</style>

<div class="chat-box">
    <div class="chat-header">
        <div class="proyecto">
            Chat con <b><?php echo htmlspecialchars($empresa['nombre']); ?></b>
        </div>
        <div class="info">
            Proyecto: <b><?php echo htmlspecialchars($oferta['nombre']); ?></b>
        </div>
    </div>

    <div id="mensajes">
    </div>

    <form action="enviar_mensaje.php" method="POST" enctype="multipart/form-data" class="enviar-mensaje" id="form-chat" autocomplete="off">
        <input type="hidden" name="oferta_id" value="<?php echo $oferta_id; ?>">
        <input type="hidden" name="empresa_id" value="<?php echo $empresa_id; ?>">
        <textarea name="mensaje" required placeholder="Escribe tu mensaje..." maxlength="1000"></textarea>
        <label class="adjuntar-label" title="Adjuntar archivo">
            <button type="button" class="adjuntar-archivo-btn" tabindex="-1">&#128206;</button>
            <input type="file" name="archivo" class="archivo-input" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
            <span class="nombre-archivo-adjunto"></span>
        </label>
        <button type="submit">Enviar</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
function cargarMensajes() {
    $.get('obtener_mensajes.php', {
        oferta_id: <?php echo $oferta_id; ?>,
        user_id: <?php echo $user_id; ?>,
        empresa_id: <?php echo $empresa_id; ?>,
        tipo: 'user'
    }, function(html) {
        let $mensajes = $('#mensajes');
        $mensajes.html(html);
        $mensajes.scrollTop($mensajes[0].scrollHeight); 
    });
}
$(function() {
    cargarMensajes();
    setInterval(cargarMensajes, 3000);
    $('.adjuntar-archivo-btn').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.adjuntar-label').find('input[type="file"]').click();
    });

    $('.archivo-input').on('change', function() {
        const file = this.files[0];
        const $label = $(this).closest('.adjuntar-label').find('.nombre-archivo-adjunto');
        if (file) {
            $label.text(file.name);
        } else {
            $label.text('');
        }
    });

    $('#form-chat').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                cargarMensajes();
                $('#form-chat textarea').val('');
                $('.archivo-input').val('');
                $('.nombre-archivo-adjunto').text('');
            }
        });
    });
});
</script>
<?php include 'componentes/footer.php'; ?>