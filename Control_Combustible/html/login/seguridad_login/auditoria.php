<?php
declare(strict_types=1);
require_once __DIR__.'/../../../backend/conexion.php';
require_once __DIR__.'/../../../backend/security.php';
requireAdmin();
$csrf=csrfToken();
$pagina=max(1,(int)($_GET['pagina']??1)); $limite=50; $offset=($pagina-1)*$limite;
$st=$conn->prepare('SELECT a.*,u.username FROM auditoria a LEFT JOIN usuarios u ON a.usuario_id=u.id ORDER BY a.fecha_registro DESC LIMIT ? OFFSET ?');
$st->bind_param('ii',$limite,$offset); $st->execute(); $rows=$st->get_result();
$totalFilas=(int)$conn->query('SELECT COUNT(*) c FROM auditoria')->fetch_assoc()['c'];
$totalPaginas=max(1,(int)ceil($totalFilas/$limite));
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Auditoría - <?=e(appName())?></title><link rel="stylesheet" href="../../../css/programa/superadmin.css"></head><body><main class="admin-container"><section class="busqueda-card"><div class="acciones"><a class="btn btn-limpiar" href="superadmin.php">← Volver</a></div><h1>Auditoría del sistema</h1><p>Registro de acciones administrativas y operacionales. Solo lectura.</p><div class="tabla-contenedor"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>ID</th><th>Detalle</th><th>IP</th></tr></thead><tbody><?php while($a=$rows->fetch_assoc()):?><tr><td><?=e($a['fecha_registro'])?></td><td><?=e($a['username']??'Sistema')?></td><td><?=e($a['accion'])?></td><td><?=e($a['tabla_afectada'])?></td><td><?=e($a['registro_id'])?></td><td><?=e($a['detalle'])?></td><td><?=e($a['ip'])?></td></tr><?php endwhile;?></tbody></table></div>
<?php if($totalPaginas>1): ?>
<nav class="paginador">
<?php if($pagina>1): ?><a class="btn btn-limpiar" href="auditoria.php?pagina=<?=$pagina-1?>">« Anterior</a><?php endif; ?>
<span class="paginador-info">Página <?=$pagina?> de <?=$totalPaginas?> (<?=$totalFilas?> registros)</span>
<?php if($pagina<$totalPaginas): ?><a class="btn btn-limpiar" href="auditoria.php?pagina=<?=$pagina+1?>">Siguiente »</a><?php endif; ?>
</nav>
<?php endif; ?>
</section></main></body></html>
