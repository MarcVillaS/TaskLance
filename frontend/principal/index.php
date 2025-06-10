<?php
$pageTitle = "Bienvenido a TaskLance";
include 'header.php';
?>
<style>
    .hero {
        background: linear-gradient(120deg, #007bff 65%, #44c7f5 100%);
        color: #fff;
        text-align: center;
        padding: 60px 20px 50px 20px;
    }

    .hero h1 {
        font-size: 2.4em;
        margin-bottom: 18px;
    }

    .hero p {
        font-size: 1.32em;
        max-width: 650px;
        margin: 0 auto 32px auto;
        color: #e3f2fd;
    }

    .hero .btn-group {
        margin-top: 30px;
    }

    .hero .btn-main {
        display: inline-block;
        background: #fff;
        color: #007bff;
        font-weight: bold;
        font-size: 1.18em;
        padding: 15px 34px;
        border-radius: 10px;
        margin: 12px 18px;
        text-decoration: none;
        transition: background 0.18s, color 0.18s, box-shadow 0.2s;
        box-shadow: 0 4px 16px #0002;
        border: none;
    }

    .hero .btn-main:hover {
        background: #ffe066;
        color: #1a237e;
    }

    .section {
        background: #fff;
        margin: 0 auto;
        margin-top: -28px;
        border-radius: 15px;
        box-shadow: 0 2px 18px #0001;
        max-width: 970px;
        padding: 44px 28px 36px 28px;
    }

    .section h2 {
        color: #007bff;
        text-align: center;
        margin-bottom: 18px;
    }

    .features-flex {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 34px;
        margin-top: 30px;
    }

    .feature-card {
        flex: 1 1 260px;
        background: #f4f9ff;
        border-radius: 9px;
        box-shadow: 0 1px 7px #007bff09;
        padding: 24px 20px;
        text-align: center;
        min-width: 220px;
    }

    .feature-card h3 {
        color: #007bff;
        margin-bottom: 13px;
    }

    .feature-card span {
        font-size: 2.5em;
        display: block;
        margin-bottom: 10px;
    }

    @media (max-width:900px) {
        .features-flex {
            flex-direction: column;
            gap: 18px;
        }
    }

    #contacto {
        margin-top: 48px;
        text-align: center;
        font-size: 1.12em;
        color: #333;
    }
</style>
<div class="hero">
    <h1>TaskLance</h1>
    <p>
        ¡Bienvenido a TaskLance! La plataforma que conecta empresas con freelancers para gestionar proyectos y tareas de
        manera ágil, eficiente y transparente.<br>
        Publica tus ofertas, postúlate a trabajos, chatea en tiempo real, organiza tareas y haz seguimiento al avance de
        cada proyecto.
    </p>
    <div class="btn-group">
        <a class="btn-main" href="../inicio_freelancer.php">Acceso Freelancers</a>
        <a class="btn-main" href="../empresas/inicio_empresas.php">Acceso Empresas</a>
    </div>

</div>
<div class="section" id="como-funciona">
    <h2>¿Cómo funciona TaskLance?</h2>
    <div class="features-flex">
        <div class="feature-card">
            <span>🏢</span>
            <h3>Empresas</h3>
            <div>
                Publica tus ofertas, revisa postulantes, asigna tareas y haz seguimiento al rendimiento de los
                freelancers.
            </div>
        </div>
        <div class="feature-card">
            <span>🧑‍💻</span>
            <h3>Freelancers</h3>
            <div>
                Postúlate a ofertas, gestiona tus tareas, chatea con empresas y recibe notificaciones de avances y
                mensajes.
            </div>
        </div>
        <div class="feature-card">
            <span>💬</span>
            <h3>Comunicación</h3>
            <div>
                Sistema de chat integrado para cada proyecto. ¡Resuelve dudas y comparte archivos en tiempo real!
            </div>
        </div>
        <div class="feature-card">
            <span>✅</span>
            <h3>Gestión de tareas</h3>
            <div>
                Kanban de tareas, fechas límite, notificaciones automáticas y control de avances para cada oferta.
            </div>
        </div>
    </div>
</div>
<div id="contacto">
    <h2>Contacto</h2>
    <p>¿Dudas? ¿Sugerencias? Escríbenos a <a href="mailto:info@tasklance.com">info@tasklance.com</a></p>
</div>
<?php include 'footer.php'; ?>