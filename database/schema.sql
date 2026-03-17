-- Base de datos de ejemplo para Citas MultiSucursal

CREATE TABLE IF NOT EXISTS sucursales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    color_calendario VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
    capacidad_simultanea INT NOT NULL DEFAULT 1,
    hora_apertura TIME NOT NULL DEFAULT '08:00:00',
    hora_cierre TIME NOT NULL DEFAULT '20:00:00',
    buffer_minutos INT NOT NULL DEFAULT 0,
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

CREATE TABLE IF NOT EXISTS programas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    sucursal_id INT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_programas_sucursal_activo (sucursal_id, activo),
    CONSTRAINT fk_programas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NULL,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(30) NOT NULL,
    servicio VARCHAR(150) NOT NULL,
    codigo_promocion VARCHAR(100) NULL,
    programa_id INT UNSIGNED NULL,
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
    INDEX idx_citas_programa (programa_id),
    CONSTRAINT fk_citas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_citas_programa FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL,
    CONSTRAINT fk_citas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS bloqueos_horario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    tipo_bloqueo ENUM('fecha_especifica','recurrente_diario','recurrente_semanal') NOT NULL,
    fecha DATE NULL,
    dia_semana TINYINT UNSIGNED NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    motivo VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_bloqueos_sucursal (sucursal_id),
    INDEX idx_bloqueos_tipo (tipo_bloqueo),
    INDEX idx_bloqueos_fecha (fecha),
    INDEX idx_bloqueos_dia_semana (dia_semana),
    CONSTRAINT fk_bloqueos_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
