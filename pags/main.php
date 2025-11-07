<?php
require __DIR__ . '/../src/nav/bootstrap.php';
start_secure_session();
require_once __DIR__ . '/../src/nav/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal - TAKEMASTER</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/styles.css">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
    <link rel="shortcut icon" href="<?= BASE_URL ?>/balas.ico" type="image/x-icon">
    <style>
        .main-hero {
            background: linear-gradient(90deg, #222 60%, var(--color-primary) 100%);
            color: #fff;
            padding: 48px 0 32px 0;
            text-align: center;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        .main-hero h1 {
            font-size: 2.8rem;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }
        .main-hero p {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 18px;
        }
        .main-cta {
            display: inline-block;
            background: var(--color-primary);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            transition: background 0.2s;
        }
        .main-cta:hover {
            background: var(--color-primary-hover, #339CFF);
        }
        .main-features {
            margin: 48px auto 0 auto;
            max-width: 900px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 32px;
        }
        .feature-card {
            background: #181818;
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
            padding: 32px 24px;
            text-align: left;
        }
        .feature-card h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--color-primary);
        }
        .feature-card p {
            font-size: 1rem;
            color: #ccc;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    <div class="container">
        <section class="main-hero">
            <h1>TAKEMASTER</h1>
            <p>La plataforma profesional para la gestión de jornadas, gastos y proyectos audiovisuales.<br>
            Optimiza tu trabajo, controla tus finanzas y presenta resultados con estilo.</p>
            <a href="/dashboard/dashboard.php" class="main-cta">Ir al Panel</a>
        </section>
        <div class="main-features">
            <div class="feature-card">
                <h2>Panel de Control Inteligente</h2>
                <p>Visualiza y organiza tus plantillas, jornadas y gastos en un entorno seguro y moderno.</p>
            </div>
            <div class="feature-card">
                <h2>Seguridad y Privacidad</h2>
                <p>Sesiones protegidas, contraseñas cifradas y protección CSRF en todos los formularios.</p>
            </div>
            <div class="feature-card">
                <h2>Estadísticas y Exportación</h2>
                <p>Obtén informes claros, exporta tus datos y comparte resultados fácilmente.</p>
            </div>
            <div class="feature-card">
                <h2>Soporte y Actualizaciones</h2>
                <p>Documentación completa, ayuda integrada y mejoras continuas para tu tranquilidad.</p>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
