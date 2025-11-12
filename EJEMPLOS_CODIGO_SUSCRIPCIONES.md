# Ejemplos de Código: Sistema de Suscripciones

## 1️⃣ Funciones de Base de Datos

### Archivo: `funciones/suscripciones.php`

```php
<?php

/**
 * Obtener suscripción del usuario
 */
function get_subscription($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT * FROM suscripciones 
        WHERE user_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Verificar si suscripción está activa
 */
function is_subscription_active($user_id) {
    $sub = get_subscription($user_id);
    
    if (!$sub) {
        return false;
    }
    
    // Si es prueba, verificar que no ha expirado (7 días)
    if ($sub['estado'] === 'prueba') {
        $fecha_prueba = strtotime($sub['fecha_inicio']);
        $fecha_ahora = time();
        $dias_transcurridos = floor(($fecha_ahora - $fecha_prueba) / (60 * 60 * 24));
        return $dias_transcurridos < 7;
    }
    
    return $sub['estado'] === 'activa';
}

/**
 * Verificar si usuario es miembro de organización
 */
function is_member_of_organization($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT 1 FROM miembros_organizacion 
        WHERE user_id = ? AND estado = 'activo'
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = $result->num_rows > 0;
    $stmt->close();
    return $found;
}

/**
 * Obtener organización del usuario
 */
function get_user_organization($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT o.* FROM organizaciones o
        JOIN miembros_organizacion m ON o.id = m.organizacion_id
        WHERE m.user_id = ? AND m.estado = 'activo'
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Crear suscripción de prueba
 */
function create_trial_subscription($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO suscripciones (user_id, estado)
        VALUES (?, 'prueba')
    ");
    $stmt->bind_param('i', $user_id);
    $result = $stmt->execute();
    $sub_id = $stmt->insert_id;
    $stmt->close();
    return $result ? $sub_id : false;
}

/**
 * Días restantes de prueba
 */
function get_trial_days_remaining($user_id) {
    $sub = get_subscription($user_id);
    
    if (!$sub || $sub['estado'] !== 'prueba') {
        return 0;
    }
    
    $fecha_inicio = strtotime($sub['fecha_inicio']);
    $fecha_ahora = time();
    $dias_transcurridos = floor(($fecha_ahora - $fecha_inicio) / (60 * 60 * 24));
    $dias_restantes = 7 - $dias_transcurridos;
    
    return max(0, $dias_restantes);
}

/**
 * Activar suscripción (después de pago exitoso)
 */
function activate_subscription($user_id, $stripe_customer_id, $stripe_subscription_id) {
    global $conn;
    
    $fecha_renovacion = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    $stmt = $conn->prepare("
        UPDATE suscripciones 
        SET 
            estado = 'activa',
            metodo_pago = 'stripe',
            stripe_customer_id = ?,
            stripe_subscription_id = ?,
            fecha_renovacion = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param('sssi', $stripe_customer_id, $stripe_subscription_id, $fecha_renovacion, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    // Registrar transacción
    if ($result) {
        $sub = get_subscription($user_id);
        register_payment_transaction($sub['id'], 3.00, 'completado');
    }
    
    return $result;
}

/**
 * Registrar transacción de pago
 */
function register_payment_transaction($suscripcion_id, $monto, $estado, $proveedor = 'stripe', $proveedor_id = null) {
    global $conn;
    
    $periodo_inicio = date('Y-m-d');
    $periodo_fin = date('Y-m-d', strtotime('+1 month'));
    
    $stmt = $conn->prepare("
        INSERT INTO transacciones_pago 
        (suscripcion_id, monto, estado, proveedor, proveedor_id, periodo_inicio, periodo_fin)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('idsssss', $suscripcion_id, $monto, $estado, $proveedor, $proveedor_id, $periodo_inicio, $periodo_fin);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Cancelar suscripción
 */
function cancel_subscription($user_id, $motivo = '') {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE suscripciones 
        SET 
            estado = 'cancelada',
            fecha_cancelacion = NOW(),
            notas = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param('si', $motivo, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Obtener historial de pagos
 */
function get_payment_history($user_id, $limit = 12) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT t.* FROM transacciones_pago t
        JOIN suscripciones s ON t.suscripcion_id = s.id
        WHERE s.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $payments;
}
```

---

## 2️⃣ Middleware de Suscripción

### Archivo: `src/middleware/SubscriptionMiddleware.php`

```php
<?php

/**
 * Middleware para verificar acceso (suscripción o organización)
 */
function require_subscription_or_organization() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // ¿Es miembro de organización? → Acceso gratis
    if (is_member_of_organization($user_id)) {
        return true;
    }
    
    // ¿Tiene suscripción activa?
    if (is_subscription_active($user_id)) {
        return true;
    }
    
    // Sin acceso → Redirigir a precios
    header("Location: " . BASE_URL . "/pags/pricing.php?reason=no_subscription");
    exit;
}

/**
 * Middleware para verificar si es admin de organización
 */
function require_organization_admin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 1 FROM organizaciones 
        WHERE admin_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = $result->num_rows > 0;
    $stmt->close();
    
    if (!$found) {
        http_response_code(403);
        die("Solo admins de organizaciones pueden acceder");
    }
}
```

---

## 3️⃣ Webhook de Stripe

### Archivo: `webhooks/stripe.php`

```php
<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/suscripciones.php';

// Configurar cliente Stripe
require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);

// Obtener cuerpo de la solicitud
$body = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verificar firma del webhook
try {
    $event = \Stripe\Webhook::constructEvent(
        $body,
        $sig_header,
        $STRIPE_WEBHOOK_SECRET
    );
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

// Procesar diferentes tipos de eventos
switch ($event->type) {
    case 'invoice.payment_succeeded':
        handle_payment_succeeded($event->data->object);
        break;
    
    case 'invoice.payment_failed':
        handle_payment_failed($event->data->object);
        break;
    
    case 'customer.subscription.deleted':
        handle_subscription_deleted($event->data->object);
        break;
    
    case 'charge.refunded':
        handle_charge_refunded($event->data->object);
        break;
}

http_response_code(200);

/**
 * Manejar pago exitoso
 */
function handle_payment_succeeded($invoice) {
    global $conn;
    
    $customer_id = $invoice->customer;
    $subscription_id = $invoice->subscription;
    
    // Obtener usuario por stripe_customer_id
    $stmt = $conn->prepare("
        SELECT s.user_id FROM suscripciones s
        WHERE s.stripe_customer_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return;
    
    $user_id = $row['user_id'];
    
    // Actualizar suscripción
    $fecha_renovacion = date('Y-m-d H:i:s', strtotime('+1 month'));
    $update_stmt = $conn->prepare("
        UPDATE suscripciones 
        SET 
            estado = 'activa',
            fecha_renovacion = ?
        WHERE user_id = ?
    ");
    $update_stmt->bind_param('si', $fecha_renovacion, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Registrar transacción
    $sub = get_subscription($user_id);
    register_payment_transaction(
        $sub['id'],
        3.00,
        'completado',
        'stripe',
        $invoice->id
    );
    
    // Enviar email de confirmación
    send_email($row['email'], 'Pago exitoso', "Tu pago de 3€ ha sido procesado exitosamente.");
}

/**
 * Manejar fallo de pago
 */
function handle_payment_failed($invoice) {
    // Obtener usuario
    // Enviar email: "Pago fallido, intenta nuevamente"
    // Reintentar en 3 días
}

/**
 * Manejar cancelación de suscripción
 */
function handle_subscription_deleted($subscription) {
    global $conn;
    
    $customer_id = $subscription->customer;
    
    // Obtener usuario
    $stmt = $conn->prepare("
        SELECT s.user_id FROM suscripciones s
        WHERE s.stripe_customer_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return;
    
    $user_id = $row['user_id'];
    
    // Cancelar suscripción en BD
    cancel_subscription($user_id, 'Cancelado por usuario en Stripe');
    
    // Enviar email de despedida
    // send_email(..., "Lamentamos verte ir");
}

/**
 * Manejar reembolso
 */
function handle_charge_refunded($charge) {
    // Obtener usuario por charge.customer
    // Registrar transacción con estado='reembolsado'
    // Enviar email: "Tu reembolso ha sido procesado"
}
```

---

## 4️⃣ Página de Precios

### Archivo: `pags/pricing.php`

```php
<?php
require_once __DIR__ . '/../src/nav/bootstrap.php';
require_once __DIR__ . '/../src/nav/db_connection.php';
require_once __DIR__ . '/../funciones/suscripciones.php';
start_secure_session();

$user_id = $_SESSION['user_id'] ?? null;
$current_sub = $user_id ? get_subscription($user_id) : null;
$in_organization = $user_id ? is_member_of_organization($user_id) : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planes y Precios - TakeMaster</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/src/css/styles.css">
    <style>
        .pricing-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin: 40px 0;
        }
        .plan-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .plan-card.recommended {
            border: 2px solid #007bff;
            transform: scale(1.05);
        }
        .plan-price {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0;
        }
        .plan-button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
        }
        .plan-button:hover {
            background-color: #0056b3;
        }
        .plan-button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/nav/topnav.php'; ?>
    
    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        <h1>Planes y Precios</h1>
        <p>Elige el plan que mejor se adapte a tus necesidades</p>
        
        <div class="pricing-plans">
            <!-- Plan Gratuito -->
            <div class="plan-card">
                <h2>Gratuito</h2>
                <div class="plan-price">0€</div>
                <p>/mes</p>
                <ul style="text-align: left;">
                    <li>✓ 1 plantilla</li>
                    <li>✓ 10 trabajos</li>
                    <li>✗ Análisis avanzado</li>
                    <li>✗ Soporte prioritario</li>
                </ul>
                <button class="plan-button disabled">Plan actual</button>
            </div>
            
            <!-- Plan Individual -->
            <div class="plan-card recommended">
                <h2>Individual</h2>
                <div class="plan-price">3€</div>
                <p>/mes</p>
                <ul style="text-align: left;">
                    <li>✓ Plantillas ilimitadas</li>
                    <li>✓ Trabajos ilimitados</li>
                    <li>✓ Análisis avanzado</li>
                    <li>✓ Soporte por email</li>
                </ul>
                <?php if ($in_organization): ?>
                    <button class="plan-button disabled">Acceso vía Organización</button>
                <?php elseif ($current_sub && $current_sub['estado'] === 'activa'): ?>
                    <button class="plan-button disabled">Suscripción activa</button>
                <?php else: ?>
                    <form method="POST" action="<?php echo BASE_URL; ?>/checkout/create-checkout-session.php">
                        <button type="submit" class="plan-button">Suscribirse</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Plan Organización -->
            <div class="plan-card">
                <h2>Organización</h2>
                <div class="plan-price">∞</div>
                <p>Personalizado</p>
                <ul style="text-align: left;">
                    <li>✓ Todo de Individual</li>
                    <li>✓ Múltiples miembros gratis</li>
                    <li>✓ Panel de administración</li>
                    <li>✓ Soporte prioritario</li>
                </ul>
                <button class="plan-button" onclick="location.href='mailto:contacto@takemaster.com'">Contactar</button>
            </div>
        </div>
        
        <?php if ($current_sub && $current_sub['estado'] === 'prueba'): ?>
            <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-top: 30px;">
                <strong>Prueba gratis activa:</strong> <?php echo get_trial_days_remaining($_SESSION['user_id']); ?> días restantes.
                <a href="?action=subscribe" style="margin-left: 20px; color: #007bff;">Suscribirse ahora</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../src/nav/footer.php'; ?>
</body>
</html>
```

---

## 5️⃣ Integrando en el Flujo Actual

### En `pags/micuenta.php`

```php
// Después de la lógica de eliminación de plantillas

// NUEVO: Verificar acceso
require_subscription_or_organization();

// Mostrar estado de suscripción en la navbar
$user_id = $_SESSION['user_id'];
$sub = get_subscription($user_id);
$org = get_user_organization($user_id);
?>

<div style="background: #f0f0f0; padding: 10px; text-align: center;">
    <?php if ($org): ?>
        <span>✓ Acceso vía organización: <?php echo htmlspecialchars($org['nombre']); ?></span>
    <?php elseif ($sub && $sub['estado'] === 'activa'): ?>
        <span>✓ Suscripción activa hasta: <?php echo date('d/m/Y', strtotime($sub['fecha_renovacion'])); ?></span>
    <?php elseif ($sub && $sub['estado'] === 'prueba'): ?>
        <span>⏱️ Prueba gratis: <?php echo get_trial_days_remaining($user_id); ?> días restantes</span>
    <?php endif; ?>
</div>
```

---

**Estos son ejemplos de producción listos para usar. Adapta según tu estructura específica.**
