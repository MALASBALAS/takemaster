<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();


// Verificación de inicio de sesión
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$username = $_SESSION['username'];

// Manejo de la creación y eliminación de plantillas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear plantilla
    if (isset($_POST['crear_plantilla'])) {
        if (!validate_csrf()) {
            http_response_code(400);
            die('CSRF inválido');
        }
        $nombrePlantilla = $_POST['nombre_plantilla'];
        
        $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $nombrePlantilla);
        $stmt->execute();
        $plantillaId = $stmt->insert_id;
        $stmt->close();

    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard&open_id=" . $plantillaId);
        exit;
    } 
    // Eliminar plantilla
    elseif (isset($_POST['eliminar_plantilla'])) {
        if (!validate_csrf()) {
            http_response_code(400);
            die('CSRF inválido');
        }
        $idPlantilla = $_POST['eliminar_plantilla'];
        
        $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
        $stmt->bind_param("is", $idPlantilla, $username);
        $stmt->execute();
        $stmt->close();

    header("Location: " . BASE_URL . "/pags/micuenta.php?section=dashboard");
        exit;
    }
}

// Obtener las plantillas del usuario desde la base de datos
$stmt = $conn->prepare("SELECT * FROM plantillas WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$plantillas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Mi cuenta</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        .container {
            /* keep the page section stacked; the outer page controls sidebar placement */
            display: block;
            width: 100%;
        }
        .content {
            /* flexible width that respects a left sidebar if present (uses CSS variable from micuenta.php) */
            width: auto;
            max-width: calc(100% - var(--account-sidebar-width, 220px) - 40px);
            padding: 15px;
            box-sizing: border-box;
        }
        .plantillas-lista {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            /* Prefer columns of ~400px, wrapping as needed; fallback to flexible columns */
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 12px;
            align-items: start;
            justify-content: start;
        }
        .plantillas-item {
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.02));
            padding: 12px;
            border-radius: 8px;
            min-height: 64px;
            box-sizing: border-box;
            /* Fixed preferred width as requested, but allow shrinking on very small viewports */
            width: 400px;
            max-width: 100%;
        }
        .plantillas-item a {
            text-decoration: none;
            color: #0b69ff;
            font-weight: 600;
            display: inline-block;
            word-break: break-word;
            max-width: 100%;
        }

    /* Ensure buttons inside list items don't expand full width (override global form button rule) */
    .plantillas-item .actions { display: flex; gap: 8px; align-items: center; justify-content: flex-end; }
    .plantillas-item form { display: inline-block; margin: 0; }
    .plantillas-item form button { width: auto !important; display: inline-block !important; padding: 6px 10px; }
    .plantillas-item > button { width: auto; padding: 6px 10px; }

        .plantillas-item:hover{
            color:#aaa;
        }
        /* Only style specific buttons here to avoid affecting all buttons globally */
        .btn-crear-plantilla {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
        }
        .btn-crear-plantilla:hover {
            background-color: #0056b3;
        }

        /* Buttons inside plantilla items (Eliminar, Compartir) */
        .plantillas-item .actions button,
        .plantillas-item button,
        #popupCompartir button {
            background-color: #007bff;
            color: #fff;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-left: 8px;
            font-size: 0.95rem;
        }
        .plantillas-item button:hover,
        #popupCompartir button:hover {
            background-color: #0056b3;
        }
        /* Estilos para el popup */
        .popup {
            display: none; 
            position: fixed; 
            z-index: 100; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px; 
        }
        .popup-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        /* Responsive tweaks: on small screens use a single column and make actions stack */
        @media (max-width: 720px) {
            .plantillas-lista { grid-template-columns: 1fr; justify-content: stretch; }
            .plantillas-item { width: 100%; flex-direction: row; align-items: center; justify-content: space-between; }
            .plantillas-item .actions { justify-content: flex-end; }
            .content { max-width: 100%; padding: 12px; }
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    <div class="container">
        <div class="content">
            <h3>Crear Plantilla</h3>
            <form method="post">
                <?php echo csrf_input(); ?>
                <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required>
                <button type="submit" name="crear_plantilla" class="btn-crear-plantilla">Crear Nueva Plantilla</button>
            </form>
            <h3>Plantillas</h3>
            <ul class="plantillas-lista">
                <?php foreach ($plantillas as $plantilla) : ?>
                    <li class="plantillas-item">
                        <a href="<?php echo BASE_URL; ?>/plantillas/miplantilla.php?id=<?php echo $plantilla['id']; ?>">
                            <?php echo htmlspecialchars($plantilla['nombre']); ?>
                        </a>
                        <form method="post" style="display: inline;">
                            <?php echo csrf_input(); ?>
                            <button type="submit" name="eliminar_plantilla" value="<?php echo $plantilla['id']; ?>" style="margin-bottom: 3px;">Eliminar</button>
                            <button type="button" onclick="abrirPopup('<?php echo $plantilla['id']; ?>')">Compartir</button>

                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Popup para compartir -->
            <div id="popupCompartir" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:1px solid #ccc; z-index:1000;">
                <h3>Compartir Plantilla</h3>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <input type="email" id="email<?php echo $i; ?>" placeholder="Email <?php echo $i; ?>" <?php echo $i === 1 ? 'required' : ''; ?>>
                <?php endfor; ?>
                <button onclick="compartirPlantilla()">Compartir</button>
                <button onclick="cerrarPopup()">Cerrar</button>
            </div>
        </div>
    </div>

<script>
function abrirPopup(plantillaId) {
    document.getElementById('popupCompartir').style.display = 'block';
    window.currentPlantillaId = plantillaId; // Guardar el ID de la plantilla en una variable global
}

function cerrarPopup() {
    document.getElementById('popupCompartir').style.display = 'none';
}

function compartirPlantilla() {
    const emails = [];
    for (let i = 1; i <= 10; i++) {
        const email = document.getElementById('email' + i).value;
        if (email) emails.push(email);
    }
    const plantillaId = window.currentPlantillaId;

    // Realizar una petición AJAX para compartir la plantilla
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "<?php echo BASE_URL; ?>/plantillas/compartir_plantilla.php", true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    // Include CSRF token in header so server can validate
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    if (metaCsrf) {
        xhr.setRequestHeader('X-CSRF-Token', metaCsrf.getAttribute('content'));
    }
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert("Plantilla compartida con éxito.");
            cerrarPopup(); // Cerrar el popup
        } else {
            alert("Error al compartir la plantilla.");
        }
    };
    xhr.send(JSON.stringify({ plantillaId, emails }));
}
</script>

</body>
</html>
