-- CRM clientes + integración con citas
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
    INDEX idx_clientes_sucursal (sucursal_id),
    INDEX idx_clientes_estatus (estatus_cliente),
    INDEX idx_clientes_telefono (telefono),
    CONSTRAINT fk_clientes_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE citas
    ADD COLUMN cliente_id INT UNSIGNED NULL AFTER sucursal_id,
    MODIFY COLUMN estatus ENUM('agendada','confirmada','asistio','no_asistio','cancelada','reprogramada') NOT NULL DEFAULT 'agendada';

ALTER TABLE citas
    ADD CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;

CREATE INDEX idx_citas_cliente ON citas(cliente_id);
