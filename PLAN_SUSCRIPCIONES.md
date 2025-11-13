# Plan de Implementación: Sistema de Suscripciones y Pagos Mensuales

## 📋 Resumen Ejecutivo

Sistema de suscripción mensual de **3€** con opción de acceso **gratuito para organizaciones (ADOMA)**.

- Usuarios normales: 3€/mes
- Miembros de organizaciones: Gratis o descuento
- Prueba gratuita: 7 (opcional)

---

## 🗄️ Modelo de Base de Datos

### 1. Tabla: `suscripciones`
```sql
CREATE TABLE `suscripciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `estado` enum('activa','cancelada','expirada','prueba') DEFAULT 'prueba',
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_renovacion` datetime,
  `fecha_cancelacion` datetime,
  `metodo_pago` enum('stripe','paypal','ninguno') DEFAULT NULL,
  `stripe_customer_id` varchar(255),
  `stripe_subscription_id` varchar(255),
  `notas` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `ux_user_suscripcion` (`user_id`)
);
```

### 2. Tabla: `organizaciones`
```sql
CREATE TABLE `organizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(100) UNIQUE NOT NULL,
  `descripcion` text,
  `admin_id` int(11) NOT NULL,
  `plan` enum('gratis','premium','enterprise') DEFAULT 'gratis',
  `max_miembros` int(11) DEFAULT 50,
  `suscripcion_id` int(11),
  `activa` boolean DEFAULT true,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE SET NULL
);
```

### 3. Tabla: `miembros_organizacion`
```sql
CREATE TABLE `miembros_organizacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizacion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rol` enum('admin','miembro') DEFAULT 'miembro',
  `estado` enum('activo','inactivo','invitado') DEFAULT 'activo',
  `fecha_invitacion` datetime,
  `fecha_aceptacion` datetime,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`organizacion_id`) REFERENCES `organizaciones` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `ux_org_user` (`organizacion_id`, `user_id`)
);
```

### 4. Tabla: `transacciones_pago` (Audit trail)
```sql
CREATE TABLE `transacciones_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suscripcion_id` int(11) NOT NULL,
  `monto` decimal(10, 2) NOT NULL,
  `moneda` varchar(3) DEFAULT 'EUR',
  `tipo` enum('pago','reembolso','ajuste') DEFAULT 'pago',
  `proveedor` enum('stripe','paypal','manual') DEFAULT 'stripe',
  `proveedor_id` varchar(255),
  `estado` enum('pendiente','completado','fallido','reembolsado') DEFAULT 'pendiente',
  `periodo_inicio` date,
  `periodo_fin` date,
  `notas` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE CASCADE
);
```

---

## 🔄 Flujos de Negocio

### Flujo 1: Usuario Normal (Pago 3€/mes)

```
1. Registro/Login
   ↓
2. ¿Tiene suscripción activa?
   ├─ NO → Mostrar "Prueba gratis 7 días" o "Suscribirse"
   │        ↓
   │        (a) Prueba gratis
   │            ├─ Crear suscripción con estado='prueba'
   │            ├─ Mostrar contador días restantes
   │            └─ Al día 7 → Mostrar "Expira pronto, suscribirse"
   │        ↓
   │        (b) Suscribirse (Stripe/PayPal)
   │            ├─ Redirigir a checkout
   │            ├─ Crear Stripe customer
   │            ├─ Crear Stripe subscription
   │            ├─ Guardar en BD con estado='activa'
   │            └─ Mostrar confirmación
   │
   └─ SÍ → Acceso completo a todas las features
             ├─ Mostrar "Suscripción activa hasta: fecha"
             └─ Botón para "Cambiar plan" o "Cancelar"
```

### Flujo 2: Usuario en Organización (Gratis)

```
1. Admin crea organización "ADOMA"
   ├─ Organización → suscripción_id (paga una suscripción por toda la org)
   └─ El admin paga 3€ por mes (o se asigna gratis si lo decides)

2. Admin invita miembros
   ├─ Envía email de invitación
   ├─ Miembro acepta
   └─ Se agrega a miembros_organizacion

3. Miembros de la org
   ├─ Tienen acceso gratis (heredan del plan de la org)
   ├─ Sin pago individual necesario
   └─ Ven "Acceso vía Organización ADOMA"
```

### Flujo 3: Renovación Automática

```
Stripe Webhook: invoice.payment_succeeded
├─ Buscar suscripción por stripe_subscription_id
├─ Actualizar estado='activa'
├─ Actualizar fecha_renovacion = próximo período
├─ Registrar transacción en transacciones_pago
└─ Enviar email de confirmación

Stripe Webhook: customer.subscription.deleted
├─ Buscar suscripción por stripe_subscription_id
├─ Actualizar estado='cancelada'
└─ Enviar email de despedida
```

---

## 🛠️ Componentes a Implementar

### 1. Backend: Middleware de Autenticación
```php
// src/middleware/SubscriptionMiddleware.php
function check_subscription() {
    if (!isset($_SESSION['user_id'])) {
        redirect_to_login();
    }
    
    // Verificar si está en organización
    if (is_member_of_organization($_SESSION['user_id'])) {
        return true; // Acceso gratis
    }
    
    // Verificar suscripción personal
    $sub = get_subscription($_SESSION['user_id']);
    
    if (!$sub || $sub['estado'] != 'activa') {
        redirect_to_pricing();
    }
}
```

### 2. Frontend: Componente de Estado Suscripción
```javascript
// src/js/components/subscription-banner.js
// Muestra en la navbar:
// - "Prueba: 5 días restantes"
// - "Suscripción activa hasta 31/12/2025"
// - "Acceso vía Organización"
```

### 3. Página de Precios
```
/pags/pricing.php
├─ Plan Gratuito: 0€ (1 plantilla, 10 trabajos)
├─ Plan Individual: 3€/mes (Plantillas ilimitadas)
└─ Plan Organización: Contactar (pricing especial)
```

### 4. Dashboard de Suscripción
```
/pags/suscripcion.php
├─ Estado actual
├─ Historial de pagos
├─ Método de pago
├─ Cambiar plan
└─ Cancelar suscripción
```

### 5. Panel de Admin de Organizaciones
```
/admin/organizaciones.php
├─ Lista de organizaciones
├─ Crear nueva organización
├─ Gestionar miembros
├─ Ver consumo de cuota
└─ Asignar plan
```

### 6. Integración Stripe
```php
// funciones/stripe-client.php
- create_customer($user_id, $email)
- create_subscription($customer_id, $plan_id)
- cancel_subscription($subscription_id)
- get_customer($stripe_customer_id)
```

---

## 📅 Fases de Implementación

### Fase 1: Estructura Base (1-2 semanas)
1. ✅ Crear tablas en BD
2. ✅ Crear funciones de BD (get_subscription, check_is_organization_member, etc)
3. ✅ Crear middleware check_subscription
4. ✅ Crear página pricing.php
5. ✅ Crear página suscripcion.php

### Fase 2: Integración Stripe (2-3 semanas)
1. ✅ Configurar API de Stripe
2. ✅ Crear checkout flow
3. ✅ Implementar webhooks
4. ✅ Pruebas con tarjetas de prueba Stripe

### Fase 3: Organizaciones (2-3 semanas)
1. ✅ Crear páginas de gestión de organizaciones
2. ✅ Sistema de invitaciones por email
3. ✅ Dashboard de admin
4. ✅ Lógica de cuota de miembros

### Fase 4: UX/Polish (1 semana)
1. ✅ Notificaciones
2. ✅ Emails transaccionales
3. ✅ Pruebas E2E
4. ✅ Documentación

---

## 💳 Integración con Stripe

### Paso 1: Crear Cuenta Stripe
- Ir a: https://dashboard.stripe.com
- Crear cuenta de test
- Obtener API keys (test y live)

### Paso 2: Productos y Precios en Stripe
```
Producto: TakeMaster Individual
  ├─ Precio: 3€/mes
  └─ ID: price_1AB...

Producto: TakeMaster Organization (precio especial)
  ├─ Precio: Personalizado
  └─ ID: price_2XY...
```

### Paso 3: Webhook Endpoints
```
POST /webhooks/stripe.php
Eventos a escuchar:
- invoice.payment_succeeded
- invoice.payment_failed
- customer.subscription.deleted
- charge.refunded
```

---

## 🔒 Seguridad

### Verificación de Suscripción
```php
// En cada página que requiera suscripción
if (!is_subscription_active($_SESSION['user_id'])) {
    header("Location: /pags/pricing.php");
    exit;
}
```

### Validación de Webhooks
```php
// Verificar firma de Stripe
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
try {
    $event = \Stripe\Webhook::constructEvent(
        $body,
        $sig_header,
        $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
}
```

---

## 📊 Casos de Uso Especiales

### 1. Usuario cambiar de plan
```
Usuario Individual → Organización
├─ Cancelar suscripción individual
├─ Agregar a miembros_organizacion
├─ Reembolsar período pendiente (opcional)
└─ Acceso se activa inmediatamente
```

### 2. Admin organización cancela miembro
```
Admin → Eliminar miembro
├─ Cambiar estado a 'inactivo'
├─ Usuario ya no puede acceder
├─ Datos se conservan (no eliminan plantillas)
└─ Puede ser re-invitado después
```

### 3. Pago fallido
```
Stripe: payment_failed
├─ Enviar email: "Pago fallido"
├─ Reintentar en 3 días
├─ Si falla nuevamente → suspender acceso
└─ Mostrar "Suscripción suspendida" en app
```

---

## 🎯 Próximos Pasos (cuando lo decidas)

1. **Decidir proveedor de pago**: Stripe (recomendado) o PayPal
2. **Crear cuenta de test**: Comienza con modo test
3. **Definir planes**: ¿Qué incluye cada plan? (número de plantillas, almacenamiento, etc)
4. **Notificaciones**: ¿Emails transaccionales? ¿SMS?
5. **Soporte**: ¿Portal de soporte para disputas de pago?

---

## 📝 SQL de Migración (cuando esté listo)

```sql
-- Ejecutar cuando decidas implementar suscripciones

ALTER TABLE `users` ADD COLUMN `plan` enum('gratuito','individual','organizacion') DEFAULT 'gratuito';
ALTER TABLE `users` ADD COLUMN `organizacion_id` int(11) DEFAULT NULL;

-- Crear tablas (ver arriba)

-- Índices para performance
CREATE INDEX idx_suscripciones_usuario ON suscripciones(user_id);
CREATE INDEX idx_suscripciones_estado ON suscripciones(estado);
CREATE INDEX idx_organizaciones_admin ON organizaciones(admin_id);
CREATE INDEX idx_miembros_organizacion_user ON miembros_organizacion(user_id);
CREATE INDEX idx_transacciones_suscripcion ON transacciones_pago(suscripcion_id);
```

---

## 🔗 Recursos Útiles

- Docs Stripe: https://stripe.com/docs/billing/subscriptions/fixed-price
- Docs Stripe Webhooks: https://stripe.com/docs/webhooks
- Tarjetas de prueba Stripe: https://stripe.com/docs/testing

---

**Nota**: Este es un plan inicial. Será refinado según tus necesidades específicas cuando decidas implementarlo.
