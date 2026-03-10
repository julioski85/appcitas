-- Base de datos de ejemplo para Citas MultiSucursal

CREATE TABLE IF NOT EXISTS sucursales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    color_calendario VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','call_center','sucursal') NOT NULL DEFAULT 'sucursal',
    sucursal_id INT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    fecha_nacimiento DATE NULL,
    sexo VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    direccion VARCHAR(255) NULL,
    ciudad VARCHAR(120) NULL,
    origen VARCHAR(100) NULL,
    tiene_responsable TINYINT(1) NOT NULL DEFAULT 0,
    responsable_nombre VARCHAR(150) NULL,
    responsable_telefono VARCHAR(30) NULL,
    responsable_parentesco VARCHAR(100) NULL,
    estatus_cliente VARCHAR(50) NOT NULL DEFAULT 'prospecto',
    notas TEXT NULL,
    primera_cita_at DATETIME NULL,
    ultima_cita_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_clientes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NULL,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(30) NOT NULL,
    servicio VARCHAR(150) NOT NULL,
    codigo_promocion VARCHAR(100) NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estatus ENUM('agendada','confirmada','asistio','no_asistio','cancelada','reprogramada') NOT NULL DEFAULT 'agendada',
    creado_por INT UNSIGNED NOT NULL,
    origen ENUM('call_center','sucursal','web') NOT NULL DEFAULT 'web',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_sucursal_fecha (sucursal_id, fecha),
    INDEX idx_fecha_horas (fecha, hora_inicio, hora_fin),
    INDEX idx_citas_cliente (cliente_id),
    CONSTRAINT fk_citas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_citas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
