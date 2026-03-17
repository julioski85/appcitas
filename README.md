# Citas MultiSucursal - versión lista para hosting compartido

Aplicación web MVC en **PHP 8 + MySQL** pensada para **subir por FTP/cPanel** y correr en hosting compartido sin Composer ni SSH.

## Qué incluye

- Login con roles:
  - admin
  - call_center
  - sucursal
- Gestión de sucursales
- Gestión de citas
- Validación de conflictos de horario en servidor
- Dashboard con estadísticas
- Calendario web con vistas:
  - mensual
  - semanal
  - diaria
- API JSON:
  - `GET /api/citas`
  - `POST /api/citas`
  - `GET /api/sucursales`
  - `GET /api/horarios-disponibles`
- Modo claro / oscuro
- Instalador web
- Archivo SQL de referencia

## Requisitos

- PHP 8.0 o superior
- MySQL 5.7+ o MariaDB compatible
- Apache con `mod_rewrite`
- PDO MySQL habilitado

## Instalación rápida en hosting

### 1) Subir archivos
Sube **todo el contenido** de este proyecto a tu hosting, por ejemplo dentro de `public_html/`.

### 2) Editar configuración
Abre:

`config/config.php`

Y coloca tu contraseña real:

```php
'db' => [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'u801126150_citas',
    'username' => 'u801126150_citas',
    'password' => 'TU_PASSWORD_REAL',
],
```

También cambia la API KEY por una cadena segura.

### 3) Abrir el instalador
Entra a:

`tudominio.com/install`

Y da clic en **Instalar base de datos y usuarios demo**.

### 4) Iniciar sesión
Usuarios demo:

- Admin  
  `admin@citas.local`  
  `Admin123!`

- Call center  
  `callcenter@citas.local`  
  `Call123!`

- Sucursal demo  
  `sucursal1@citas.local`  
  `Sucursal123!`

## API

### GET /api/sucursales
Autenticación:
- sesión iniciada, o
- header `X-API-KEY`

### GET /api/citas
Parámetros opcionales:
- `start=2026-03-01`
- `end=2026-03-31`
- `sucursal_id=1`
- `estatus=agendada`

### POST /api/citas
JSON o form-data:

```json
{
  "sucursal_id": 1,
  "cliente_nombre": "María Pérez",
  "cliente_telefono": "5512345678",
  "servicio": "Valoración",
  "fecha": "2026-03-10",
  "hora_inicio": "10:00",
  "hora_fin": "10:30",
  "estatus": "agendada",
  "origen": "web"
}
```

### GET /api/horarios-disponibles
Ejemplo:

`/api/horarios-disponibles?sucursal_id=1&fecha=2026-03-10`

## Seguridad aplicada

- sesiones PHP
- `password_hash`
- CSRF en formularios
- validación backend
- restricción por rol
- bloqueo de encimado de citas por sucursal/fecha/hora

## Notas importantes

- Esta versión está optimizada para **hosting compartido**.
- No depende de Composer.
- Si quieres, después se puede escalar a una versión Laravel completa con panel más avanzado, jobs, notificaciones y módulos extra.
