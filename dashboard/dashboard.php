<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
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
        $nombrePlantilla = $_POST['nombre_plantilla'];
        
        $stmt = $conn->prepare("INSERT INTO plantillas (username, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $nombrePlantilla);
        $stmt->execute();
        $plantillaId = $stmt->insert_id;
        $stmt->close();

        header("Location: view_dashboard.php?id=" . $plantillaId);
        exit;
    } 
    // Eliminar plantilla
    elseif (isset($_POST['eliminar_plantilla'])) {
        $idPlantilla = $_POST['eliminar_plantilla'];
        
        $stmt = $conn->prepare("DELETE FROM plantillas WHERE id = ? AND username = ?");
        $stmt->bind_param("is", $idPlantilla, $username);
        $stmt->execute();
        $stmt->close();

        header("Location: /../dashboard/dashboard.php");
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
    <link rel="stylesheet" href="/../src/css/style.css">
    <style>
        .container {
            display: flex;
        }
        .content {
            width: 80%;
            padding: 15px;
        }
        .plantillas-lista {
            list-style: none;
            padding: 0;
        }
        .plantillas-item {
            margin-bottom: 10px;
        }
        .plantillas-item a {
            text-decoration: none;
            color: #fff;
        }

        .plantillas-item:hover{
            color:#aaa;
        }
        .btn-crear-plantilla, button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-crear-plantilla:hover {
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
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h3>Crear Plantilla</h3>
            <form method="post">
                <input type="text" name="nombre_plantilla" placeholder="Nombre de la plantilla" required>
                <button type="submit" name="crear_plantilla" class="btn-crear-plantilla">Crear Nueva Plantilla</button>
            </form>
            <h3>Plantillas</h3>
            <ul class="plantillas-lista">
                <?php foreach ($plantillas as $plantilla) : ?>
                    <li class="plantillas-item">
                        <a href="/../dashboard/view_dashboard.php?id=<?php echo $plantilla['id']; ?>">
                            <?php echo htmlspecialchars($plantilla['nombre']); ?>
                        </a>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="eliminar_plantilla" value="<?php echo $plantilla['id']; ?>">Eliminar</button>
                        </form>
                        <button onclick="abrirPopup('<?php echo $plantilla['id']; ?>')">Compartir</button>
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
    xhr.open("POST", "compartir_plantilla.php", true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
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
