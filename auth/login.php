<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

// Función para limpiar y validar entradas
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$username = $password = "";
$errors = array();
// Simple in-session rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();
$_SESSION['login_rl'] = $_SESSION['login_rl'] ?? [];
$_SESSION['login_rl'][$ip] = $_SESSION['login_rl'][$ip] ?? ['count' => 0, 'first' => $now];
// Reset window if expired
if ($now - $_SESSION['login_rl'][$ip]['first'] > LOGIN_RATE_LIMIT_WINDOW_MIN * 60) {
    $_SESSION['login_rl'][$ip] = ['count' => 0, 'first' => $now];
}

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
    $password = clean_input($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $errors[] = "Todos los campos son obligatorios.";
    }
    // Rate limit check
    if ($_SESSION['login_rl'][$ip]['count'] >= LOGIN_RATE_LIMIT_MAX) {
        $errors[] = 'Demasiados intentos. Inténtalo más tarde.';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                regen_session();
                $_SESSION['user_id'] = (int)$id;
                $_SESSION['username'] = $username;
                // Reset rate limiter on success
                $_SESSION['login_rl'][$ip] = ['count' => 0, 'first' => $now];
                header("Location: /index.php");
                exit;
            } else {
                $errors[] = "Contraseña incorrecta.";
                $_SESSION['login_rl'][$ip]['count']++;
            }
        } else {
            $errors[] = "Usuario no encontrado.";
            $_SESSION['login_rl'][$ip]['count']++;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php';
 ?>
    <div class="container">
        <?php if (!isset($_SESSION['user_id'])) : ?>
        <h2 class="center">Iniciar sesión</h2>
        
        <?php
        if (!empty($errors)) {
            echo "<div class='errors'>";
            foreach ($errors as $error) {
                echo "<p>{$error}</p>";
            }
            echo "</div>";
        }
        ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_input(); ?>
            <div class="input-group">
                <label for="username">Nombre de usuario o Correo electrónico:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username">
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="input-group">
                <button type="submit" class="btn">Iniciar sesión</button>
            </div>
            <p style="font-size:1.5rem;">¿No tienes una cuenta? <a href="/auth/register.php">Regístrate aquí</a>.</p>
        </form>
        <?php else : ?>
        <p style="font-size:1.5rem;">Ya has iniciado sesión. <a href="/auth/logout.php">Cerrar sesión</a></p>
        <?php endif; ?>
    </div>
    <br>
    <br>
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
