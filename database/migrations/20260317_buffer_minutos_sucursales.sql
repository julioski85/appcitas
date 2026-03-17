ALTER TABLE sucursales
    ADD COLUMN buffer_minutos INT NOT NULL DEFAULT 0 AFTER hora_cierre;
