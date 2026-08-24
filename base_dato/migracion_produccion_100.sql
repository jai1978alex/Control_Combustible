USE base_dato_formulario;

-- Migración segura para instalaciones existentes.
-- EJECUTAR CON RESPALDO PREVIO.

SET @c1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='debe_cambiar_password');
SET @q1 = IF(@c1=0, 'ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER estado', 'SELECT 1');
PREPARE s1 FROM @q1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @c2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='operador_cargador' AND COLUMN_NAME='eliminado_at');
SET @q2 = IF(@c2=0, 'ALTER TABLE operador_cargador ADD COLUMN eliminado_at DATETIME NULL AFTER observacion, ADD COLUMN eliminado_por INT NULL AFTER eliminado_at, ADD INDEX idx_cargador_eliminado(eliminado_at)', 'SELECT 1');
PREPARE s2 FROM @q2; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @c3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='operador' AND COLUMN_NAME='eliminado_at');
SET @q3 = IF(@c3=0, 'ALTER TABLE operador ADD COLUMN eliminado_at DATETIME NULL AFTER observacion, ADD COLUMN eliminado_por INT NULL AFTER eliminado_at, ADD INDEX idx_operador_eliminado(eliminado_at)', 'SELECT 1');
PREPARE s3 FROM @q3; EXECUTE s3; DEALLOCATE PREPARE s3;

CREATE TABLE IF NOT EXISTS auditoria (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NULL,
 accion VARCHAR(80) NOT NULL,
 tabla_afectada VARCHAR(80) NULL,
 registro_id INT NULL,
 detalle TEXT NULL,
 ip VARCHAR(45) NOT NULL,
 user_agent VARCHAR(500) NULL,
 fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_auditoria_fecha(fecha_registro),
 INDEX idx_auditoria_usuario(usuario_id),
 INDEX idx_auditoria_accion(accion),
 CONSTRAINT fk_auditoria_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;
