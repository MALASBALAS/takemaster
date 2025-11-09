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
$errors = array();
$success = "";

// Obtener los datos actuales del usuario
$stmt = $conn->prepare("SELECT email, role_id FROM users WHERE username = ?");
if ($stmt) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($email, $role_id);
    $stmt->fetch();
    $stmt->close();
} else {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$subscription_status = ($role_id == 1) ? "Suscriptor" : "No tienes ninguna suscripción activa";

// Actualizar datos del usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = clean_input($_POST['username']);
    $new_email = clean_input($_POST['email']);
    $new_password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);

    if (empty($new_username) || empty($new_email)) {
        $errors[] = "El nombre de usuario y el correo electrónico son obligatorios.";
    }

    if (!empty($new_password) && $new_password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    if (empty($errors)) {
        // Verificar si el nombre de usuario o el correo electrónico ya están en uso por otro usuario
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND username != ?");
        if ($stmt) {
            $stmt->bind_param('sss', $new_username, $new_email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "El nombre de usuario o correo electrónico ya están en uso.";
            } else {
                // Actualizar los datos del usuario
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE username = ?");
                    $stmt->bind_param('ssss', $new_username, $new_email, $hashed_password, $username);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE username = ?");
                    $stmt->bind_param('sss', $new_username, $new_email, $username);
                }

                if ($stmt->execute()) {
                    $_SESSION['username'] = $new_username;
                    $username = $new_username;
                    $email = $new_email;
                    $success = "Datos actualizados exitosamente.";
                } else {
                    $errors[] = "Error al actualizar los datos. Inténtelo de nuevo.";
                }
                $stmt->close();
            }
        } else {
            $errors[] = "Error en la preparación de la consulta de verificación: " . $conn->error;
        }
    }
}

// Función para limpiar y validar entradas
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <style>
        /* Make the configuration form responsive inside the account panel */
        form { max-width: 800px; width: 100%; margin: 0 auto; box-sizing: border-box; }
        .input-group { display:block; width:100%; margin-bottom:12px }
        .input-group label{display:block;margin-bottom:6px}
        input[type="text"], input[type="email"], input[type="password"]{width:100%;padding:10px;border-radius:6px;border:1px solid var(--color-border);box-sizing:border-box}
        .btn{display:inline-block;padding:10px 14px;border-radius:8px;background:var(--color-primary);color:#fff;border:none}
        @media (max-width:600px){
            form{padding:6px}
            .btn{width:100%}
        }
    </style>
</head>
<body>
    <h3>Configuración de la cuenta</h3>
    <p>Estado de suscripción: <?php echo $subscription_status; ?></p>



    <form method="POST" action="">
        <div class="input-group">
            <label for="username">Nombre de usuario:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        <div class="input-group">
            <label for="email">Correo electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="input-group">
            <label for="password">Contraseña (dejar en blanco para no cambiar):</label>
            <input type="password" id="password" name="password">
        </div>   
        <div class="input-group">
            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        <div class="input-group">
            <button type="submit" class="btn">Actualizar</button>
                    <?php
    if (!empty($errors)) {
        echo "<div class='errors'>";
        foreach ($errors as $error) {
            echo "<p>{$error}</p>";
        }
        echo "</div>";
    }
    if ($success) {
            echo "<p>{$success}</p>";
        }
        ?>
        </div>

    </form>
</body>
</html>
