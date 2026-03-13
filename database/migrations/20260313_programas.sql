-- Agrega catálogo dinámico de programas y lo relaciona con citas sin perder datos existentes.

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

ALTER TABLE citas
    ADD COLUMN programa_id INT UNSIGNED NULL AFTER codigo_promocion,
    ADD INDEX idx_citas_programa (programa_id),
    ADD CONSTRAINT fk_citas_programa FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL;
