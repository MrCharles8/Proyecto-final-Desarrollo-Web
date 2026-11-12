<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$ruta = (string)($_GET['r'] ?? 'inicio');
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
function pagina(string $titulo, callable $funcion): void { ob_start(); $funcion(); mostrar_pagina($titulo, ob_get_clean()); }
function verificar_errores(array $errores): void { if ($errores) throw new RuntimeException(implode(' ', $errores)); }
function oportunidad(int $id): array { return consultar_uno('SELECT o.*,c.nombre empresa,c.sector,c.sitio_web FROM oportunidades o JOIN empresas c ON c.id=o.empresa_id WHERE o.id=?', [$id]) ?: detener();
    }
function texto_estado(string $estado): string {
    return match ($estado) {
        'activa' => 'Vigente',
        'cerrada' => 'Cerrada',
        default => $estado,
    }; }
function badge(string $estado): string { return '<span class="status '.(in_array($estado, ['activa','Aceptada','Preseleccionada'], true) ? 'good' : (in_array($estado,['cerrada','Rechazada','Retirada'],true) ? 'neutral' : 'warning')).'">'.h(texto_estado($estado)).'</span>';
    }
function tabla_oportunidades(array $filas): void { ?>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Práctica</th>
<th>Empresa</th>
<th>Ubicación</th>
<th>Modalidad</th>
<th>Fecha límite</th>
<th>Estado</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach ($filas as $o): ?>
<tr>
<td>
<?= h($o['titulo']) ?>
</td>
<td>
<?= h($o['empresa']) ?>
</td>
<td>
<?= h($o['ubicacion']) ?>
</td>
<td>
<?= h($o['modalidad']) ?>
</td>
<td>
<?= h($o['fecha_limite']) ?>
</td>
<td>
<?= badge($o['estado']) ?>
</td>
<td>
<a href="index.php?r=oportunidad&id=<?= (int)$o['id'] ?>">Ver detalle</a>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$filas): ?>
<tr>
<td colspan="7">No hay oportunidades disponibles.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php }
try {
 if ($metodo === 'POST') verificar_csrf();
 switch ($ruta) {
 case 'inicio':
  $resumen = ['activa'=>(int)(consultar_uno("SELECT COUNT(*) n FROM oportunidades WHERE estado='activa' AND fecha_limite>=CURDATE()")['n'] ?? 0), 'empresas'=>(int)(consultar_uno('SELECT COUNT(*) n FROM empresas')['n'] ?? 0)];
  if (sesion_iniciada()) $resumen['mine'] = (int)(consultar_uno('SELECT COUNT(*) n FROM postulaciones WHERE estudiante_id=?', [usuario_actual()['id']])['n'] ?? 0);
  $recientes = consultar_todos("SELECT o.*,c.nombre empresa FROM oportunidades o JOIN empresas c ON c.id=o.empresa_id WHERE o.estado='activa' AND o.fecha_limite>=CURDATE() ORDER BY o.fecha_creacion DESC LIMIT 3");
  pagina('Panel principal', function () use ($resumen,$recientes) { ?>
  <section class="hero">
<div>
<h1>Prácticas profesionales</h1>
<p>Explora prácticas profesionales, postúlate y sigue cada avance de tu proceso desde un solo lugar.</p>
<a class="button white" href="index.php?r=oportunidades">Explorar oportunidades →</a>
</div>
</section>
  <h2>Resumen general</h2>
<table class="summary-table">
<thead>
<tr>
<th>Concepto</th>
<th>Cantidad</th>
</tr>
</thead>
<tbody>
<tr>
<td>Oportunidades vigentes</td>
<td>
<?= $resumen['activa'] ?>
</td>
</tr>
<tr>
<td>Empresas vinculadas</td>
<td>
<?= $resumen['empresas'] ?>
</td>
</tr>
<?php if (sesion_iniciada() && !es_administrador()): ?>
<tr>
<td>Mis postulaciones</td>
<td>
<?= $resumen['mine'] ?>
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
  <div class="section-head">
<div>
<span class="eyebrow">DESCUBRE</span>
<h2>Oportunidades recientes</h2>
</div>
<a href="index.php?r=oportunidades">Ver todas →</a>
</div>
<?php tabla_oportunidades($recientes); ?>
  <?php }); break;
 case 'oportunidades':
  $q=trim((string)($_GET['q']??'')); $mod=(string)($_GET['modalidad']??''); $onlyActive= !es_administrador() || ($_GET['scope']??'') !== 'all';
  $where=[];$args=[];
  if ($q!=='') { $where[]='(o.titulo LIKE ? OR c.nombre LIKE ? OR o.ubicacion LIKE ?)'; $search='%'.$q.'%'; array_push($args,$search,$search,$search); }
  if (in_array($mod,['Presencial','Híbrida','Remota'],true)) { $where[]='o.modalidad=?'; $args[]=$mod; }
  if ($onlyActive) $where[]="o.estado='activa' AND o.fecha_limite>=CURDATE()";
  $sql='SELECT o.*,c.nombre empresa FROM oportunidades o JOIN empresas c ON c.id=o.empresa_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY o.fecha_creacion DESC';
  $filas=consultar_todos($sql,$args);
  pagina('Oportunidades',function() use ($filas,$q,$mod,$onlyActive) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">EXPLORA TU CAMINO</span>
<h1>Oportunidades de práctica</h1>
<p>Descubre experiencias que impulsarán tu desarrollo profesional.</p>
</div>
<?php if(es_administrador()): ?>
<a class="button primary" href="index.php?r=formulario-oportunidad">＋ Publicar oportunidad</a>
<?php endif; ?>
</div>
  <form class="panel filterbar" method="get">
<input type="hidden" name="r" value="oportunidades">
<label>Buscar<input name="q" value="<?= h($q) ?>" placeholder="Puesto, empresa o ubicación">
</label>
<label>Modalidad<select name="modalidad">
<option value="">Todas</option>
<?php foreach(['Presencial','Híbrida','Remota'] as $m): ?>
<option <?= $m===$mod?'selected':'' ?> value="<?= h($m) ?>">
<?= h($m) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<?php if(es_administrador()): ?>
<label>Visualización<select name="scope">
<option value="activa">Vigentes</option>
<option value="all" <?= !$onlyActive?'selected':'' ?>>Todas</option>
</select>
</label>
<?php endif; ?>
<button class="button primary" type="submit">Filtrar</button>
</form>
  <div class="result-titulo">
<?= count($filas) ?> resultado(s)</div>
<?php tabla_oportunidades($filas); ?>
<?php }); break;
 case 'oportunidad':
  $o=oportunidad((int)($_GET['id']??0));
  $applied=sesion_iniciada() && !es_administrador() ? consultar_uno('SELECT id,estado FROM postulaciones WHERE estudiante_id=? AND oportunidad_id=?',[usuario_actual()['id'],$o['id']]) : false;
  pagina('Detalle de oportunidad',function() use($o,$applied) { ?>
<a class="back" href="index.php?r=oportunidades">← Volver a oportunidades</a>
<div class="detail-grid">
<article class="panel detail">
<div class="card-row">
<span class="eyebrow">
<?= h($o['empresa']) ?> · <?= h($o['sector']) ?>
</span>
<?= badge($o['estado']) ?>
</div>
<h1>
<?= h($o['titulo']) ?>
</h1>
<div class="chips">
<span>⌖ <?= h($o['ubicacion']) ?>
</span>
<span>◈ <?= h($o['modalidad']) ?>
</span>
<span>◷ <?= h($o['fecha_limite']) ?>
</span>
<span>♧ <?= (int)$o['vacantes'] ?> vacante(s)</span>
</div>
<hr>
<h2>Acerca de la práctica</h2>
<p class="prewrap">
<?= h($o['descripcion']) ?>
</p>
<h2>Requisitos</h2>
<p class="prewrap">
<?= h($o['requisitos']) ?>
</p>
<?php if ($o['sitio_web']): ?>
<p>
<a href="<?= h($o['sitio_web']) ?>" rel="noopener noreferrer" target="_blank">Sitio web de la empresa ↗</a>
</p>
<?php endif; ?>
</article>
  <aside class="panel apply-box">
<div class="eyebrow">POSTULACIÓN</div>
<h2>Da el siguiente paso</h2>
<p>Fecha límite: <strong>
<?= h($o['fecha_limite']) ?>
</strong>
</p>
<?php if (es_administrador()): ?>
<a class="button primary full" href="index.php?r=formulario-oportunidad&id=<?= (int)$o['id'] ?>">Editar oportunidad</a>
<?php elseif (!$applied && $o['estado']==='activa' && $o['fecha_limite']>=date('Y-m-d') && sesion_iniciada()): ?>
<form method="post" action="index.php?r=postular">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<input type="hidden" name="oportunidad_id" value="<?= (int)$o['id'] ?>">
<label>¿Por qué te interesa esta práctica?<textarea name="motivacion" minlength="20" maxlength="2000" rows="5" required placeholder="Explica brevemente tu interés y habilidades relevantes.">
</textarea>
</label>
<button class="button primary full">Enviar postulación</button>
</form>
<?php elseif ($applied): ?>
<p>Ya te postulaste: <?= badge($applied['estado']) ?>
</p>
<a class="button outline full" href="index.php?r=mis-postulaciones">Ver seguimiento</a>
<?php elseif (!sesion_iniciada()): ?>
<p>Inicia sesión para presentar tu solicitud.</p>
<a class="button primary full" href="index.php?r=ingresar">Iniciar sesión</a>
<?php else: ?>
<p>Esta oportunidad ya no recibe postulaciones.</p>
<?php endif; ?>
</aside>
</div>
<?php }); break;
 case 'postular':
  exigir_sesion(); if (es_administrador() || $metodo!=='POST') detener(403);
  $id=(int)($_POST['oportunidad_id']??0); $motivacion=trim((string)($_POST['motivacion']??''));
  if(mb_strlen($motivacion)<20||mb_strlen($motivacion)>2000) throw new RuntimeException('La motivación debe contener entre 20 y 2000 caracteres.');
  $pdo=conexion_bd(); $pdo->beginTransaction();
  try {
   $op=consultar_uno('SELECT id FROM oportunidades WHERE id=? AND estado=? AND fecha_limite>=CURDATE() FOR UPDATE',[$id,'activa']);
   if(!$op) throw new RuntimeException('La oportunidad ya no está disponible.');
   ejecutar('INSERT INTO postulaciones(estudiante_id,oportunidad_id,motivacion) VALUES(?,?,?)',[usuario_actual()['id'],$id,$motivacion]);
   $idPostulacion=(int)$pdo->lastInsertId();
   ejecutar('INSERT INTO historial_postulaciones(postulacion_id,responsable_id,estado_nuevo,observacion) VALUES(?,?,?,?)',[$idPostulacion,usuario_actual()['id'],'En revisión','Postulación registrada']);
   $pdo->commit();
  } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
  mensaje_temporal('success','Postulación enviada correctamente.'); redirigir('mis-postulaciones');
 case 'mis-postulaciones':
  exigir_sesion(); if(es_administrador()) detener(403);
  $filas=consultar_todos('SELECT a.*,o.titulo,c.nombre empresa,o.fecha_limite FROM postulaciones a JOIN oportunidades o ON o.id=a.oportunidad_id JOIN empresas c ON c.id=o.empresa_id WHERE a.estudiante_id=? ORDER BY a.fecha_creacion DESC',[usuario_actual()['id']]);
  pagina('Mis postulaciones',function() use($filas) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">MI TRAYECTORIA</span>
<h1>Seguimiento de postulaciones</h1>
<p>Consulta el estado de cada solicitud y sus cambios.</p>
</div>
</div>
<div class="panel table-wrap">
<table>
<thead>
<tr>
<th>Oportunidad</th>
<th>Empresa</th>
<th>Enviada</th>
<th>Estado</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach($filas as $a): ?>
<tr>
<td>
<strong>
<?= h($a['titulo']) ?>
</strong>
</td>
<td>
<?= h($a['empresa']) ?>
</td>
<td>
<?= h(substr($a['fecha_creacion'],0,10)) ?>
</td>
<td>
<?= badge($a['estado']) ?>
</td>
<td>
<a href="index.php?r=postulacion&id=<?= (int)$a['id'] ?>">Ver detalle →</a>
</td>
</tr>
<?php endforeach; if(!$filas): ?>
<tr>
<td colspan="5" class="center">Todavía no has enviado postulaciones.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
<?php }); break;
 case 'postulacion':
  exigir_sesion(); $id=(int)($_GET['id']??0);
  $a=consultar_uno('SELECT a.*,u.nombre_completo estudiante,u.correo correo_estudiante,o.titulo,c.nombre empresa FROM postulaciones a JOIN usuarios u ON u.id=a.estudiante_id JOIN oportunidades o ON o.id=a.oportunidad_id JOIN empresas c ON c.id=o.empresa_id WHERE a.id=?',[$id]);
  if(!$a||(!es_administrador()&&(int)$a['estudiante_id']!==(int)usuario_actual()['id']))detener(404);
  $historial=consultar_todos('SELECT h.*,u.nombre_completo responsable FROM historial_postulaciones h JOIN usuarios u ON u.id=h.responsable_id WHERE h.postulacion_id=? ORDER BY h.id DESC',[$id]);
  pagina('Detalle de postulación',function() use($a,$historial) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">SEGUIMIENTO</span>
<h1>
<?= h($a['titulo']) ?>
</h1>
<p>
<?= h($a['estudiante']) ?> · <?= h($a['empresa']) ?>
</p>
</div>
<?= badge($a['estado']) ?>
</div>
<div class="detail-grid">
<div class="panel detail">
<h2>Presentación del estudiante</h2>
<p class="prewrap">
<?= h($a['motivacion']) ?>
</p>
<hr>
<h2>Historial de seguimiento</h2>
<ol class="timeline">
<?php foreach($historial as $h): ?>
<li>
<strong>
<?= h($h['estado_nuevo']) ?>
</strong>
<small>
<?= h($h['fecha_creacion']) ?> · <?= h($h['responsable']) ?>
</small>
<?php if($h['observacion']): ?>
<p>
<?= h($h['observacion']) ?>
</p>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ol>
</div>
<aside class="panel apply-box">
<h2>Estado actual</h2>
<?= badge($a['estado']) ?>
<?php if(es_administrador()&&!in_array($a['estado'],['Aceptada','Rechazada','Retirada'],true)): ?>
<form method="post" action="index.php?r=cambiar-estado" class="form-stack">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<input type="hidden" name="postulacion_id" value="<?= (int)$a['id'] ?>">
<label>Actualizar estado<select name="estado" required>
<?php foreach($a['estado']==='En revisión'?['Preseleccionada','Aceptada','Rechazada']:['Aceptada','Rechazada'] as $s): ?>
<option value="<?= h($s) ?>">
<?= h($s) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<label>Observación (opcional)<textarea maxlength="250" name="observacion" rows="3">
</textarea>
</label>
<button class="button primary">Guardar cambio</button>
</form>
<?php elseif(!es_administrador()&&!in_array($a['estado'],['Aceptada','Rechazada','Retirada'],true)): ?>
<form method="post" action="index.php?r=cambiar-estado">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<input type="hidden" name="postulacion_id" value="<?= (int)$a['id'] ?>">
<input type="hidden" name="estado" value="Retirada">
<button class="button danger full" data-confirm="¿Retirar esta postulación?">Retirar postulación</button>
</form>
<?php else: ?>
<p class="muted">La postulación ha alcanzado un estado final.</p>
<?php endif; ?>
</aside>
</div>
<?php }); break;
 default: detener();
 }
} catch (Throwable $e) {
 if($e instanceof PDOException){
  $mensaje=($e->getCode()==='23000')?'Ya existe un registro con esos datos o el registro está relacionado con otros elementos.':'No fue posible completar la operación de base de datos.';
 }else{$mensaje=$e->getMessage();}
 http_response_code(400);
 pagina('No se pudo completar la solicitud',function() use($mensaje){?>
<div class="panel error-pagina">
<h1>No se pudo completar la solicitud</h1>
<p>
<?= h($mensaje) ?>
</p>
<a class="button primary" href="javascript:history.back()">Volver</a>
<a class="button outline" href="index.php">Ir al inicio</a>
</div>
<?php });
}
