<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();


// Función para limpiar y validar entradas
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$username = $email = $password = $confirm_password = "";
$errors = array();
$success = "";

// Verificar si el usuario ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        http_response_code(400);
        $errors[] = 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.';
    }
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Todos los campos son obligatorios.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }
    // Basic strong password policy
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres e incluir letras y números.';
    }
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "El nombre de usuario o correo electrónico ya están en uso.";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role_id = 2;
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $username, $email, $hashed_password, $role_id);
        
        if ($stmt->execute()) {
            $success = "Usuario registrado exitosamente.";
        } else {
            $errors[] = "Error al registrar usuario. Inténtelo de nuevo.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <link rel="stylesheet" href="/src/css/style-form.css">
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>

    <div class="container">
        <?php if (!isset($_SESSION['user_id'])) : ?>
        <h2 class="center">Registro</h2>

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

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_input(); ?>
            <div class="input-group">
                <label for="username">Nombre de usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username">
            </div>
            <div class="input-group">
                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            </div>

            <hr>
            <div class="input-group center">
                <label for="terminos" style="font-size:1.5rem;">Acepto los <a href="/pags/terms.php" target="_blank">términos y condiciones</a>. <br> <input type="checkbox" id="terminos" name="terminos" required></label>
            </div>
            <div class="center">
                <button type="submit" class="btn">Registrarse</button>
            </div>

            <hr>
            <p style="font-size:1.5rem;">¿Tienes ya una cuenta creada? <a href="/auth/login.php">Inicia sesión aquí</a>.</p>
        </form>
        <?php else : ?>
        <p>Ya has iniciado sesión. <a href="/auth/logout.php">Cerrar sesión</a></p>
        <?php endif; ?>
    </div>
    <br>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
