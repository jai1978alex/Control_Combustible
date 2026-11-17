-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA

-- ---------------------------------------------
-- INICIO 04_INSTALADOR_ESTRUCTURA_TABLAS.sql
-- ---------------------------------------------



-- ---------------------------------------------
-- TÍTULO: TABLA USUARIOS (LAS PERSONAS QUE PUEDEN ENTRAR AL SISTEMA)
-- Inicio de la tabla
CREATE TABLE IF NOT EXISTS usuarios (
  -- Número de identificación único; sube solo de 1 en 1
  id INT AUTO_INCREMENT PRIMARY KEY,
  -- Nombre de usuario (obligatorio, hasta 50 letras, no se puede repetir)
  username VARCHAR(50) NOT NULL UNIQUE,
  -- Correo (hasta 100 letras, no se puede repetir)
  email VARCHAR(100) UNIQUE,
  -- RUT de la persona (puede quedar vacío)
  rut VARCHAR(12) DEFAULT NULL,
  -- Contraseña guardada de forma protegida (obligatoria)
  password VARCHAR(255) NOT NULL,
  -- Tipo de usuario: administrador u operador (si no se indica, es operador)
  rol ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  -- Fecha y hora en que se creó el usuario (se anota sola)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Fecha y hora del último cambio (se actualiza sola)
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Si el usuario está activo o inactivo (por defecto activo)
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  -- Marca si debe cambiar su contraseña al entrar (0 = no, 1 = sí)
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
  -- Regla: no puede haber dos usuarios con el mismo RUT
  UNIQUE KEY uq_usuarios_rut (rut),
  -- Índice: sirve para encontrar más rápido a los usuarios por estado y tipo
  INDEX idx_estado_rol(estado,rol)
-- Fin de la tabla (con el motor que cuida que los datos no se dañen)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- TÍTULO: TABLA OPERADOR_CARGADOR (REGISTROS DE QUIENES CARGAN COMBUSTIBLE A LOS ESTANQUES)
-- Inicio de la tabla
CREATE TABLE IF NOT EXISTS operador_cargador (
  -- Número de identificación único; sube solo de 1 en 1
  id INT AUTO_INCREMENT PRIMARY KEY,
  -- Nombres de la persona (obligatorio)
  nombres VARCHAR(100) NOT NULL,
  -- Primer apellido (obligatorio)
  apellido_paterno VARCHAR(100) NOT NULL,
  -- Segundo apellido (obligatorio)
  apellido_materno VARCHAR(100) NOT NULL,
  -- RUT de la persona (obligatorio)
  rut VARCHAR(12) NOT NULL,
  -- Turno de trabajo, una sola letra (obligatorio)
  turno CHAR(1) NOT NULL,
  -- Código del equipo o estanque (obligatorio)
  codigo VARCHAR(50) NOT NULL,
  -- Lugar donde se hizo la carga (obligatorio)
  ubicacion VARCHAR(100) NOT NULL,
  -- Patente del vehículo (obligatoria)
  patente VARCHAR(10) NOT NULL,
  -- Lectura del contador de horas de la máquina (obligatoria)
  horometro DECIMAL(10,2) NOT NULL,
  -- Cantidad de litros cargados (obligatoria)
  litros DECIMAL(10,2) NOT NULL,
  -- Litros que quedaron en el estanque (si no se indica, 0)
  remanente DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Comentario libre (opcional)
  observacion TEXT,
  -- Fecha y hora en que se eliminó el registro (vacío si sigue vigente)
  eliminado_at DATETIME NULL,
  -- Quién lo eliminó (vacío si sigue vigente)
  eliminado_por INT NULL,
  -- Usuario que hizo el registro
  usuario_id INT NULL,
  -- Fecha y hora del registro (se anota sola)
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Índice: búsqueda rápida por RUT y fecha
  INDEX idx_cargador_rut_fecha(rut,fecha_registro),
  -- Índice: búsqueda rápida de registros vigentes por fecha
  INDEX idx_cargador_vigente_fecha(eliminado_at,fecha_registro),
  -- Índice: búsqueda rápida de registros vigentes por litros
  INDEX idx_cargador_vigente_litros(eliminado_at,litros),
  -- Índice: búsqueda rápida por usuario y fecha
  INDEX idx_cargador_usuario_fecha(usuario_id,fecha_registro),
  -- Índice: búsqueda rápida por patente y fecha
  INDEX idx_cargador_patente_fecha(patente,fecha_registro),
  -- Enlaza el registro con un usuario; si el usuario se borra, el registro se conserva
  CONSTRAINT fk_cargador_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  -- Enlaza "quién eliminó" con un usuario; si ese usuario se borra, el registro se conserva
  CONSTRAINT fk_cargador_eliminado_por FOREIGN KEY(eliminado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  -- Regla: los litros deben ser mayores que cero
  CONSTRAINT chk_cargador_litros CHECK (litros > 0),
  -- Regla: el contador de horas no puede ser negativo
  CONSTRAINT chk_cargador_horometro CHECK (horometro >= 0),
  -- Regla: el turno solo puede ser A, B, C o D
  CONSTRAINT chk_cargador_turno CHECK (turno IN ('A','B','C','D'))
-- Fin de la tabla (con el motor que cuida que los datos no se dañen)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- TÍTULO: TABLA OPERADOR (REGISTROS DE CARGA DE COMBUSTIBLE DE LAS MÁQUINAS)
-- Inicio de la tabla
CREATE TABLE IF NOT EXISTS operador (
  -- Número de identificación único; sube solo de 1 en 1
  id INT AUTO_INCREMENT PRIMARY KEY,
  -- Nombres de la persona (obligatorio)
  nombres VARCHAR(100) NOT NULL,
  -- Primer apellido (obligatorio)
  apellido_paterno VARCHAR(100) NOT NULL,
  -- Segundo apellido (obligatorio)
  apellido_materno VARCHAR(100) NOT NULL,
  -- RUT de la persona (obligatorio)
  rut VARCHAR(12) NOT NULL,
  -- Turno de trabajo, una sola letra (obligatorio)
  turno CHAR(1) NOT NULL,
  -- Lugar donde se hizo la carga (obligatorio)
  ubicacion VARCHAR(100) NOT NULL,
  -- Código de la maquinaria (obligatorio)
  codigo_maquinaria VARCHAR(50) NOT NULL,
  -- Patente del vehículo (obligatoria)
  patente VARCHAR(10) NOT NULL,
  -- Nombre o tipo del equipo (obligatorio)
  equipo VARCHAR(100) NOT NULL,
  -- Lectura del contador de horas de la máquina (puede quedar vacía)
  horometro DECIMAL(10,2) NULL,
  -- Lectura de kilómetros del vehículo (puede quedar vacía)
  kilometro DECIMAL(10,2) NULL,
  -- Cantidad de litros cargados (obligatoria)
  litros DECIMAL(10,2) NOT NULL,
  -- Consumo calculado (si no se indica, 0)
  consumo DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Comentario libre (opcional)
  observacion TEXT,
  -- Fecha y hora en que se eliminó el registro (vacío si sigue vigente)
  eliminado_at DATETIME NULL,
  -- Quién lo eliminó (vacío si sigue vigente)
  eliminado_por INT NULL,
  -- Usuario que hizo el registro
  usuario_id INT NULL,
  -- Fecha y hora del registro (se anota sola)
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Índice: búsqueda rápida por RUT y fecha
  INDEX idx_operador_rut_fecha(rut,fecha_registro),
  -- Índice: búsqueda rápida de registros vigentes por fecha
  INDEX idx_operador_vigente_fecha(eliminado_at,fecha_registro),
  -- Índice: búsqueda rápida de registros vigentes por litros
  INDEX idx_operador_vigente_litros(eliminado_at,litros),
  -- Índice: búsqueda rápida por usuario y fecha
  INDEX idx_operador_usuario_fecha(usuario_id,fecha_registro),
  -- Índice: búsqueda rápida por patente y fecha
  INDEX idx_operador_patente_fecha(patente,fecha_registro),
  -- Índice: búsqueda rápida por maquinaria y fecha
  INDEX idx_operador_maquinaria_fecha(codigo_maquinaria,fecha_registro),
  -- Enlaza el registro con un usuario; si el usuario se borra, el registro se conserva
  CONSTRAINT fk_operador_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  -- Enlaza "quién eliminó" con un usuario; si ese usuario se borra, el registro se conserva
  CONSTRAINT fk_operador_eliminado_por FOREIGN KEY(eliminado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  -- Regla: los litros deben ser mayores que cero
  CONSTRAINT chk_operador_litros CHECK (litros > 0),
  -- Regla: las lecturas de horas y kilómetros no pueden ser negativas
  CONSTRAINT chk_operador_lecturas CHECK ((horometro IS NULL OR horometro >= 0) AND (kilometro IS NULL OR kilometro >= 0)),
  -- Regla: el turno solo puede ser A, B, C o D
  CONSTRAINT chk_operador_turno CHECK (turno IN ('A','B','C','D'))
-- Fin de la tabla (con el motor que cuida que los datos no se dañen)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- TÍTULO: TABLA RECUPERACION_PASSWORD (SOLICITUDES PARA RECUPERAR UNA CONTRASEÑA OLVIDADA)
-- Inicio de la tabla
CREATE TABLE IF NOT EXISTS recuperacion_password (
  -- Número de identificación único; sube solo de 1 en 1
  id INT AUTO_INCREMENT PRIMARY KEY,
  -- Usuario que pidió recuperar su contraseña (obligatorio)
  usuario_id INT NOT NULL,
  -- Código secreto del enlace de recuperación, guardado de forma protegida (no se repite)
  token_hash CHAR(64) NOT NULL UNIQUE,
  -- Marca si el enlace ya se usó (0 = no, 1 = sí)
  usado TINYINT(1) NOT NULL DEFAULT 0,
  -- Fecha y hora en que se pidió (se anota sola)
  fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Fecha y hora en que el enlace deja de servir (obligatoria)
  expira_en DATETIME NOT NULL,
  -- Índice: búsqueda rápida por usuario
  INDEX idx_reset_usuario(usuario_id),
  -- Índice: búsqueda rápida por enlaces usados y vencimiento
  INDEX idx_reset_estado_expira(usado,expira_en),
  -- Enlaza la solicitud con un usuario; si el usuario se borra, su solicitud también
  CONSTRAINT fk_reset_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
-- Fin de la tabla (con el motor que cuida que los datos no se dañen)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- TÍTULO: TABLA AUDITORIA (DIARIO DONDE QUEDA ANOTADO QUIÉN HIZO QUÉ EN EL SISTEMA)
-- Inicio de la tabla
CREATE TABLE IF NOT EXISTS auditoria (
  -- Número de identificación único (admite cantidades enormes); sube solo de 1 en 1
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  -- Usuario que hizo la acción
  usuario_id INT NULL,
  -- Nombre de la acción realizada (obligatorio)
  accion VARCHAR(80) NOT NULL,
  -- Tabla donde ocurrió el cambio
  tabla_afectada VARCHAR(80) NULL,
  -- Número del registro que se cambió
  registro_id INT NULL,
  -- Explicación extra de lo que pasó
  detalle TEXT NULL,
  -- Dirección de internet desde donde se hizo la acción (obligatoria)
  ip VARCHAR(45) NOT NULL,
  -- Datos del navegador o dispositivo usado
  user_agent VARCHAR(500) NULL,
  -- Fecha y hora de la acción (se anota sola)
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Índice: búsqueda rápida por fecha
  INDEX idx_auditoria_fecha(fecha_registro),
  -- Índice: búsqueda rápida por tipo de acción
  INDEX idx_auditoria_accion(accion),
  -- Índice: búsqueda rápida por usuario y fecha
  INDEX idx_auditoria_usuario_fecha(usuario_id,fecha_registro),
  -- Índice: búsqueda rápida por tabla y número de registro
  INDEX idx_auditoria_tabla_registro(tabla_afectada,registro_id),
  -- Enlaza la acción con un usuario; si el usuario se borra, la anotación se conserva
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
-- Fin de la tabla (con el motor que cuida que los datos no se dañen)
) ENGINE=InnoDB;

-- ---------------------------------------------
-- FIN 04_INSTALADOR_ESTRUCTURA_TABLAS.sql
-- ---------------------------------------------

-- Sitio Web Creado por Jerez.
-- Direccion: Miraflorez #1280
-- Quintero - Chile
-- jaime.jerez1978@gmail.com
-- https://www.
-- Creado, Programado y Diseñado por Jerez.
-- JJA
