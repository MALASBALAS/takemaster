# Guía Rápida de Despliegue - Takemaster PDF Export

**Estado**: PDF export completamente implementado ✓  
**Servidor**: Linux Ubuntu 24.04 en 192.168.1.106  
**Dominio**: balbe.xyz  
**Branch**: MB-002-Compartir-antes-de-eliminar

---

## 🚀 PASO 1: Conectarse al Servidor

```powershell
# Desde PowerShell en Windows
ssh balas@192.168.1.106

# O usar el dominio
ssh balas@balbe.xyz
```

---

## 🔄 PASO 2: Actualizar Código

```bash
cd /var/www/takemaster

# Actualizar desde Git
sudo git pull origin MB-002-Compartir-antes-de-eliminar

# Cambiar propietario
sudo chown -R www-data:www-data /var/www/takemaster

# Permisos
sudo chmod -R 755 /var/www/takemaster
sudo chmod -R 775 /var/www/takemaster/dashboard

# Crear directorio temporal si no existe
sudo mkdir -p /var/www/takemaster/temp
sudo chown www-data:www-data /var/www/takemaster/temp
sudo chmod 775 /var/www/takemaster/temp

# Reiniciar PHP-FPM
sudo systemctl restart php-fpm
```

---

## ✅ PASO 3: Verificar que Todo Funciona

```bash
# Verificar wkhtmltopdf
which wkhtmltopdf
wkhtmltopdf --version

# Verificar export_pdf.php existe
ls -la /var/www/takemaster/dashboard/export_pdf.php

# Test rápido de wkhtmltopdf
echo '<html><body><h1>Test</h1></body></html>' > /tmp/test.html
wkhtmltopdf /tmp/test.html /tmp/test.pdf
ls -lh /tmp/test.pdf
```

**Resultado esperado**:
```
/usr/bin/wkhtmltopdf
wkhtmltopdf 0.12.6
-rw-rw-r-- 1 balas balas 14K /tmp/test.pdf
```

---

## 🧪 PASO 4: Probar desde el Navegador

1. Abre tu navegador: **https://balbe.xyz** (o tu dominio)
2. Navega a **Consultas** o **Dashboard**
3. Verifica que hay datos en la tabla
4. Haz clic en el botón **📥 Descargar PDF**
5. Deberías ver: `⏳ Generando PDF...`
6. El PDF se descargará automáticamente

**Si no funciona**, abre la consola (F12) y busca errores rojo.

---

## 🔍 PASO 5: Ver Logs si hay Problemas

```bash
# Nginx errors
sudo tail -f /var/log/nginx/takemaster_error.log

# PHP errors (en otra terminal)
sudo tail -f /var/log/php-fpm.log

# Sistema
top
```

---

## 📋 Archivos Clave

| Archivo | Propósito |
|---------|-----------|
| `dashboard/export_pdf.php` | Backend que genera PDFs |
| `dashboard/consultas.php` | Frontend con botón de descarga |
| `/var/www/takemaster/temp` | Carpeta para archivos temporales |
| `/var/log/nginx/takemaster_error.log` | Logs de errores |
| `/var/log/php-fpm.log` | Logs de PHP |

---

## 🛠️ Solución de Problemas Rápida

| Problema | Solución |
|----------|----------|
| "Unexpected token '<'" | Ver logs: `sudo tail /var/log/nginx/takemaster_error.log` |
| PDF no se descarga | Verificar sesión: `curl -v https://balbe.xyz/dashboard/consultas.php` |
| wkhtmltopdf no encontrado | `which wkhtmltopdf` - si no aparece, reinstalar: `sudo apt install wkhtmltopdf` |
| Permisos denegados | `sudo chown -R www-data:www-data /var/www/takemaster` |
| Servicio down | `sudo systemctl restart php-fpm nginx` |

---

## 📊 Flujo de Funcionamiento

```
Usuario en Frontend
        ↓
Click en "Descargar PDF"
        ↓
JavaScript recolecta datos de la tabla
        ↓
Envía JSON a export_pdf.php (POST)
        ↓
PHP valida sesión y datos (ob_start para errores)
        ↓
Genera HTML con formato profesional
        ↓
wkhtmltopdf convierte HTML → PDF
        ↓
PHP envía PDF como descarga
        ↓
Navegador descarga archivo
```

---

## 📈 Características Implementadas

✅ **Autenticación**: Solo usuarios logeados pueden descargar  
✅ **Error Handling**: JSON responses para errores  
✅ **Fallbacks**: wkhtmltopdf → FPDF → HTML  
✅ **Formatting**: Tabla profesional con estilos  
✅ **Resumen**: Totales de trabajos, takes, CGs e ingresos  
✅ **Timestamps**: Fecha/hora de generación  
✅ **Loading State**: Indicador visual mientras se genera  
✅ **Auto-cleanup**: Elimina archivos temporales automáticamente

---

## 🔐 Seguridad

- ✅ Validación de sesión requerida
- ✅ Validación de método POST
- ✅ Escape de HTML para XSS prevention
- ✅ JSON error responses (sin detalles técnicos al usuario)
- ✅ Output buffering para capturar errores

---

## 📱 Próximas Mejoras (Opcional)

- [ ] Agregar más filtros (fecha, tipo de trabajo)
- [ ] Exportar a Excel (XLSX)
- [ ] Exportar múltiples reportes en ZIP
- [ ] Cola de trabajos para PDFs grandes
- [ ] Caché de PDFs generados
- [ ] Historial de descargas

---

## 📞 Soporte

Si necesitas ayuda:

1. Revisa `LINUX_SETUP.md` - Guía de instalación completa
2. Revisa `DEPLOYMENT_COMMANDS.md` - Comandos útiles
3. Revisa `DEPLOYMENT_CHECKLIST.md` - Checklist y troubleshooting
4. Abre un issue en GitHub con los logs

---

**Última actualización**: 14 de Noviembre de 2025  
**Estado**: Listo para Producción ✅
