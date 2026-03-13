ALTER TABLE sucursales
    ADD COLUMN hora_apertura TIME NOT NULL DEFAULT '08:00:00' AFTER capacidad_simultanea,
    ADD COLUMN hora_cierre TIME NOT NULL DEFAULT '20:00:00' AFTER hora_apertura;
