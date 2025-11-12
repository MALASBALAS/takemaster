<?php
require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        http_response_code(400);
        die('CSRF inválido');
    }
    $dashboard_name = $_POST['dashboard_name'];
    $dashboard_content = $_POST['dashboard_content'];
    $username = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO dashboards (user_id, dashboard_name, dashboard_content) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $username, $dashboard_name, $dashboard_content);
    if ($stmt->execute()) {
        echo "Dashboard creado exitosamente";
    } else {
        echo "Error al crear dashboard";
    }
}
?>

    }
}
?>

<form method="POST">
    <?php echo csrf_input(); ?>
    <input type="text" name="dashboard_name" required placeholder="Nombre del Dashboard">
    <textarea name="dashboard_content" required placeholder="Contenido del Dashboard"></textarea>
    <button type="submit">Crear Dashboard</button>
</form>
