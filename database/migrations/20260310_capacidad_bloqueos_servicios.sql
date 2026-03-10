-- 1) Capacidad simultánea por sucursal
ALTER TABLE sucursales
ADD COLUMN capacidad_simultanea INT NOT NULL DEFAULT 1 AFTER color_calendario;

-- 2) Bloqueos de horario
CREATE TABLE IF NOT EXISTS bloqueos_horario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT UNSIGNED NOT NULL,
    tipo_bloqueo ENUM('fecha_especifica','recurrente_diario','recurrente_semanal') NOT NULL,
    fecha DATE NULL,
    dia_semana TINYINT UNSIGNED NULL COMMENT '1=Lunes ... 7=Domingo',
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
    INDEX idx_bloqueos_horas (hora_inicio, hora_fin),
    CONSTRAINT fk_bloqueos_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    CONSTRAINT chk_bloqueos_horas CHECK (hora_fin > hora_inicio),
    CONSTRAINT chk_bloqueos_fecha_tipo CHECK (
        (tipo_bloqueo = 'fecha_especifica' AND fecha IS NOT NULL AND dia_semana IS NULL)
        OR (tipo_bloqueo = 'recurrente_diario' AND fecha IS NULL AND dia_semana IS NULL)
        OR (tipo_bloqueo = 'recurrente_semanal' AND fecha IS NULL AND dia_semana BETWEEN 1 AND 7)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
