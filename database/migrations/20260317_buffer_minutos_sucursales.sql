ALTER TABLE sucursales
    ADD COLUMN buffer_minutos INT NOT NULL DEFAULT 5 AFTER hora_cierre;
