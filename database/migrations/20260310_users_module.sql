-- Migración para módulo de usuarios (ejecutar una vez en instalaciones existentes)

ALTER TABLE usuarios
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER sucursal_id;

-- Ajuste opcional por seguridad para sucursal_id nullable
ALTER TABLE usuarios
    MODIFY sucursal_id INT UNSIGNED NULL;

-- Demo users (solo si no existen)
INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
SELECT 'Admin Demo', 'admin.demo@citas.local', '$2y$10$x6Dmt2QhLz48zi1vjcR0W.eJDRlS5kpQjB.7mWOjeR4AAjttJ6tw6', 'admin', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin.demo@citas.local');

INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
SELECT 'Call Center Demo', 'callcenter.demo@citas.local', '$2y$10$05jLZ99f/sHMlLhA58CDQeS73e23Mhtmcv4xFJ9wMDOY38QSEorE.', 'call_center', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'callcenter.demo@citas.local');

INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
SELECT 'Sucursal Demo', 'sucursal.demo@citas.local', '$2y$10$ZhULCyxjfIFUyblEWDtA2egH6yb0fh2uQWkgOzcQX1xRorQlMebIe', 'sucursal', (SELECT id FROM sucursales ORDER BY id ASC LIMIT 1), 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'sucursal.demo@citas.local');

-- Passwords de demos:
-- Admin123! / Call123! / Sucursal123!
