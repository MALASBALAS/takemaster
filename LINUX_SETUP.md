# Instalación en Linux con Nginx

## Requisitos previos

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP y extensiones necesarias
sudo apt install -y php php-fpm php-mysqli php-gd php-curl php-mbstring php-xml

# Instalar wkhtmltopdf para generación de PDFs (RECOMENDADO)
sudo apt install -y wkhtmltopdf xvfb fontconfig fonts-liberation

# Instalar Nginx (si no está instalado)
sudo apt install -y nginx

# Instalar Git (para versionamiento)
sudo apt install -y git
```

## Configuración de Nginx

Crear archivo `/etc/nginx/sites-available/takemaster.conf`:

```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;

    root /var/www/takemaster;
    index index.php;

    # Logs
    access_log /var/log/nginx/takemaster_access.log;
    error_log /var/log/nginx/takemaster_error.log;

    # Tamaño máximo de upload
    client_max_body_size 100M;

    # Rewrite URLs amigables
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Denegar acceso a archivos sensibles
    location ~ /\. {
        deny all;
    }

    location ~ ~$ {
        deny all;
    }
}
```

Activar sitio y reiniciar Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/takemaster.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Configuración de PHP-FPM

Editar `/etc/php/8.x/fpm/php.ini`:

```ini
# Aumentar límite de memoria si es necesario
memory_limit = 256M

# Aumentar tiempo máximo de ejecución
max_execution_time = 300

# Permitir uploads grandes
upload_max_filesize = 100M
post_max_size = 100M

# Timezone
date.timezone = Europe/Madrid
```

Reiniciar PHP-FPM:

```bash
sudo systemctl restart php-fpm
```

## Permisos de archivos

```bash
# Navegar a la carpeta del proyecto
cd /var/www/takemaster

# Permisos correctos
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 dashboard/ plantillas/ funciones/

# Carpeta temporal para PDFs (opcional)
sudo mkdir -p /var/www/takemaster/temp
sudo chmod 775 /var/www/takemaster/temp
```

## Verificación de wkhtmltopdf

```bash
# Verificar que wkhtmltopdf está instalado correctamente
which wkhtmltopdf

# Probar generación básica
wkhtmltopdf http://example.com test.pdf
```

## Despliegue con Git

```bash
# Actualizar repositorio existente
cd /var/www/takemaster
sudo git pull origin MB-002-Compartir-antes-de-eliminar

# O clonar si no existe
# cd /var/www
# sudo git clone https://github.com/MALASBALAS/takemaster.git
# cd takemaster
# sudo git checkout MB-002-Compartir-antes-de-eliminar

# Cambiar propietario
sudo chown -R www-data:www-data /var/www/takemaster

# Asegurar permisos correctos
sudo chmod -R 755 /var/www/takemaster
sudo chmod -R 775 /var/www/takemaster/dashboard /var/www/takemaster/plantillas /var/www/takemaster/funciones

# Crear carpeta temporal para PDFs si no existe
sudo mkdir -p /var/www/takemaster/temp
sudo chmod 775 /var/www/takemaster/temp
sudo chown www-data:www-data /var/www/takemaster/temp
```

## SSL (HTTPS) con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com -d www.tu-dominio.com

# Renovación automática
sudo systemctl enable certbot.timer
```

## Monitoreo y Logs

```bash
# Ver logs de Nginx
tail -f /var/log/nginx/takemaster_error.log

# Ver logs de PHP-FPM
tail -f /var/log/php-fpm.log

# Ver logs de aplicación
tail -f /var/www/takemaster/logs/error.log
```

## Backup automático

```bash
# Crear script de backup
cat > /usr/local/bin/backup-takemaster.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/takemaster"
mkdir -p $BACKUP_DIR

# Backup de archivos
tar -czf $BACKUP_DIR/takemaster_$DATE.tar.gz /var/www/takemaster

# Backup de base de datos (si aplica)
# mysqldump -u usuario -p base_de_datos > $BACKUP_DIR/db_$DATE.sql

echo "Backup completado: $BACKUP_DIR/takemaster_$DATE.tar.gz"
EOF

chmod +x /usr/local/bin/backup-takemaster.sh

# Agregar a crontab
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-takemaster.sh") | crontab -
```

## Notas importantes

- **wkhtmltopdf** es la mejor solución para PDFs en Linux. Si falla, el sistema intentará usar FPDF o HTML como fallback.
- Asegúrate de que el usuario `www-data` tiene permisos en `/tmp` para archivos temporales.
- Monitorea el uso de memoria con `free -h` y `top`.
- Configura rotación de logs para evitar que llenen el disco.

## Verificación de Despliegue

Después de desplegar la aplicación, ejecuta estos comandos para verificar que todo funciona:

```bash
# 1. Verificar que wkhtmltopdf está disponible
which wkhtmltopdf
wkhtmltopdf --version

# 2. Verificar permisos del proyecto
ls -la /var/www/takemaster/dashboard/export_pdf.php
stat /var/www/takemaster/dashboard/export_pdf.php

# 3. Verificar permisos de usuario www-data
sudo -u www-data touch /var/www/takemaster/temp/test.txt
sudo -u www-data rm /var/www/takemaster/temp/test.txt

# 4. Probar wkhtmltopdf con un archivo HTML simple
echo '<html><body><h1>Test</h1></body></html>' > /tmp/test.html
wkhtmltopdf /tmp/test.html /tmp/test.pdf
ls -lh /tmp/test.pdf

# 5. Verificar que PHP-FPM está corriendo
sudo systemctl status php-fpm
sudo systemctl status php8.3-fpm

# 6. Verificar que Nginx está corriendo
sudo systemctl status nginx

# 7. Ver logs de errores
tail -f /var/log/nginx/takemaster_error.log
tail -f /var/log/php-fpm.log
```

## Prueba de PDF desde el Frontend

1. Accede a tu aplicación en el navegador: `https://tu-dominio.com`
2. Navega a la sección de **Consultas** o **Dashboard**
3. Carga algunos datos en la tabla
4. Haz clic en el botón **📥 Descargar PDF**
5. Deberías ver el indicador "⏳ Generando PDF..."
6. El PDF se descargará automáticamente

Si hay errores, revisa:
- La consola del navegador (F12) para ver mensajes de error
- Los logs de Nginx: `sudo tail -f /var/log/nginx/takemaster_error.log`
- Los logs de PHP-FPM: `sudo tail -f /var/log/php-fpm.log`

## Troubleshooting

### Error: "Unexpected token '<'"
- **Causa**: El backend está devolviendo HTML en lugar de JSON
- **Solución**: Revisa `/var/log/nginx/takemaster_error.log` para ver el error real de PHP
- **Verificación**: Asegúrate de que `export_pdf.php` tiene la línea `ob_start()` al inicio

### Error: "wkhtmltopdf: No such file or directory"
- **Causa**: wkhtmltopdf no está instalado o no está en el PATH
- **Solución**: 
  ```bash
  sudo apt install -y wkhtmltopdf xvfb fontconfig fonts-liberation
  which wkhtmltopdf
  ```

### PDF vacío o con errores de fuentes
- **Causa**: Faltan fuentes o XvFB no está disponible
- **Solución**:
  ```bash
  sudo apt install -y fonts-liberation fonts-liberation-sans-narrow
  sudo apt install -y xvfb
  ```

### Permisos denegados en `/var/www/takemaster/temp`
- **Causa**: El usuario `www-data` no tiene permisos de escritura
- **Solución**:
  ```bash
  sudo chown www-data:www-data /var/www/takemaster/temp
  sudo chmod 775 /var/www/takemaster/temp
  ```
