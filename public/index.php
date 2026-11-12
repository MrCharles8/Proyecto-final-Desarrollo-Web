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
 case 'ingresar':
  if ($metodo === 'POST') {
   $correo = mb_strtolower(trim((string)($_POST['correo'] ?? ''))); $u = consultar_uno('SELECT * FROM usuarios WHERE correo=?',[$correo]);
   if (!$u || !password_verify((string)($_POST['contrasena'] ?? ''), $u['contrasena_hash'])) throw new RuntimeException('Correo o contraseña incorrectos.');
   session_regenerate_id(true); $_SESSION['usuario']=['id'=>$u['id'],'nombre_completo'=>$u['nombre_completo'],'correo'=>$u['correo'],'rol'=>$u['rol']];
    mensaje_temporal('success','Bienvenido al portal.'); redirigir('inicio');
  }
  pagina('Iniciar sesión', function() { ?>
<div class="auth-wrap">
<div class="panel auth-card">
<div class="eyebrow">ACCESO A LA PLATAFORMA</div>
<h1>Bienvenido de nuevo</h1>
<p class="muted">Ingresa tus credenciales para continuar.</p>
<form method="post" class="form-stack">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<label>Correo electrónico<input required type="email" name="correo" autocomplete="username" placeholder="correo@universidad.edu">
</label>
<label>Contraseña<input required type="password" name="contrasena" autocomplete="current-password" placeholder="••••••••">
</label>
<button class="button primary full">Iniciar sesión</button>
</form>
<p class="center muted">¿Eres estudiante? <a href="index.php?r=registro">Crea una cuenta</a>
</p>
</div>
</div>
<?php }); break;
 case 'registro':
  if (sesion_iniciada()) redirigir('inicio');
  if ($metodo === 'POST') {
   $nombre=trim((string)($_POST['nombre']??'')); $correo=mb_strtolower(trim((string)($_POST['correo']??'')));
    $carnet=trim((string)($_POST['carnet']??'')); $contrasena=(string)($_POST['contrasena']??'');
   verificar_errores(array_filter([mb_strlen($nombre)<4||mb_strlen($nombre)>120?'El nombre debe tener entre 4 y 120 caracteres.':'', !filter_var($correo,FILTER_VALIDATE_EMAIL)||mb_strlen($correo)>190?'Correo electrónico inválido.':'', mb_strlen($carnet)<3||mb_strlen($carnet)>30?'El carné debe tener entre 3 y 30 caracteres.':'', strlen($contrasena)<10?'La contraseña debe tener al menos 10 caracteres.':'']));
   ejecutar("INSERT INTO usuarios(nombre_completo,correo,contrasena_hash,rol,carnet_estudiante) VALUES(?,?,?,'estudiante',?)",[$nombre,$correo,password_hash($contrasena,PASSWORD_DEFAULT),$carnet]);
   mensaje_temporal('success','Cuenta registrada. Ahora puedes iniciar sesión.'); redirigir('ingresar');
  }
  pagina('Crear cuenta', function() { ?>
<div class="auth-wrap">
<div class="panel auth-card">
<div class="eyebrow">REGISTRO ESTUDIANTIL</div>
<h1>Crea tu cuenta</h1>
<p class="muted">Comienza a explorar nuevas oportunidades.</p>
<form class="form-stack" method="post">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<label>Nombre completo<input required maxlength="120" name="nombre" value="<?= valor_previo('nombre') ?>">
</label>
<label>Correo electrónico<input required type="email" maxlength="190" name="correo" value="<?= valor_previo('correo') ?>">
</label>
<label>Carné universitario<input required maxlength="30" name="carnet" value="<?= valor_previo('code') ?>">
</label>
<label>Contraseña (mínimo 10 caracteres)<input required minlength="10" type="password" name="contrasena" autocomplete="new-password">
</label>
<button class="button primary full">Registrar estudiante</button>
</form>
</div>
</div>
<?php }); break;
 case 'salir':
  if ($metodo !== 'POST') detener(405); $_SESSION=[]; session_regenerate_id(true); mensaje_temporal('success','Sesión cerrada.'); redirigir('ingresar');
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
 case 'administrar-postulaciones':
  exigir_administrador(); $estado=(string)($_GET['estado']??'');$condiciones=[];$parametros=[];
  if(in_array($estado,['En revisión','Preseleccionada','Aceptada','Rechazada','Retirada'],true)){ $condiciones[]='a.estado=?';$parametros[]=$estado; }
  $filas=consultar_todos('SELECT a.*,u.nombre_completo estudiante,o.titulo,c.nombre empresa FROM postulaciones a JOIN usuarios u ON u.id=a.estudiante_id JOIN oportunidades o ON o.id=a.oportunidad_id JOIN empresas c ON c.id=o.empresa_id'.($condiciones?' WHERE '.implode(' AND ',$condiciones):'').' ORDER BY a.fecha_actualizacion DESC',$parametros);
  pagina('Gestión de postulaciones',function() use($filas,$estado) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">COORDINACIÓN ACADÉMICA</span>
<h1>Control de postulaciones</h1>
<p>Gestiona el avance de cada estudiante.</p>
</div>
</div>
<form class="filterbar panel" method="get">
<input type="hidden" name="r" value="administrar-postulaciones">
<label>Filtrar por estado<select name="estado">
<option value="">Todos los estados</option>
<?php foreach(['En revisión','Preseleccionada','Aceptada','Rechazada','Retirada'] as $s): ?>
<option value="<?= h($s) ?>" <?= $estado===$s?'selected':'' ?>>
<?= h($s) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<button class="button primary">Aplicar</button>
</form>
<div class="panel table-wrap">
<table>
<thead>
<tr>
<th>Estudiante</th>
<th>Oportunidad</th>
<th>Empresa</th>
<th>Estado</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach($filas as $a): ?>
<tr>
<td>
<?= h($a['estudiante']) ?>
</td>
<td>
<?= h($a['titulo']) ?>
</td>
<td>
<?= h($a['empresa']) ?>
</td>
<td>
<?= badge($a['estado']) ?>
</td>
<td>
<a href="index.php?r=postulacion&id=<?= (int)$a['id'] ?>">Gestionar →</a>
</td>
</tr>
<?php endforeach; if(!$filas): ?>
<tr>
<td colspan="5" class="center">No hay postulaciones.</td>
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
 case 'cambiar-estado':
  exigir_sesion(); if($metodo!=='POST') detener(405);
  $id=(int)($_POST['postulacion_id']??0);$estadoSiguiente=(string)($_POST['estado']??'');$observacion=trim((string)($_POST['observacion']??''));
  if(mb_strlen($observacion)>250)throw new RuntimeException('La observación es demasiado larga.');
  $pdo=conexion_bd();$pdo->beginTransaction();
  try {
   $a=consultar_uno('SELECT * FROM postulaciones WHERE id=? FOR UPDATE',[$id]); if(!$a)detener(404);
   if(!es_administrador()&&(int)$a['estudiante_id']!==(int)usuario_actual()['id'])detener(403);
   $allowed=es_administrador()?['En revisión'=>['Preseleccionada','Aceptada','Rechazada'],'Preseleccionada'=>['Aceptada','Rechazada']]:['En revisión'=>['Retirada'],'Preseleccionada'=>['Retirada']];
   if(!in_array($estadoSiguiente,$allowed[$a['estado']]??[],true))throw new RuntimeException('Esta transición de estado no está permitida.');
   ejecutar('UPDATE postulaciones SET estado=? WHERE id=?',[$estadoSiguiente,$id]);
   ejecutar('INSERT INTO historial_postulaciones(postulacion_id,responsable_id,estado_anterior,estado_nuevo,observacion) VALUES(?,?,?,?,?)',[$id,usuario_actual()['id'],$a['estado'],$estadoSiguiente,$observacion]);
   $pdo->commit();
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  mensaje_temporal('success','Estado actualizado; el historial conserva el cambio.');redirigir('postulacion',['id'=>$id]);
 case 'formulario-oportunidad':
  exigir_administrador(); $id=(int)($_GET['id']??0);$o=$id?oportunidad($id):null;$empresas=consultar_todos('SELECT * FROM empresas ORDER BY nombre');
  if($metodo==='POST'){
   $datos=array_map(static fn($v)=>is_string($v)?trim($v):$v,$_POST);
   verificar_errores(validar_oportunidad($datos));
   $campos=[$datos['empresa_id'],$datos['titulo'],$datos['descripcion'],$datos['requisitos'],$datos['ubicacion'],$datos['modalidad'],$datos['vacantes'],$datos['fecha_limite'],$datos['estado']];
   if($id){ejecutar('UPDATE oportunidades SET empresa_id=?,titulo=?,descripcion=?,requisitos=?,ubicacion=?,modalidad=?,vacantes=?,fecha_limite=?,estado=? WHERE id=?',[...$campos,$id]);
    }
   else{ejecutar('INSERT INTO oportunidades(empresa_id,titulo,descripcion,requisitos,ubicacion,modalidad,vacantes,fecha_limite,estado,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?)',[...$campos,usuario_actual()['id']]);
    $id=(int)conexion_bd()->lastInsertId();}
   mensaje_temporal('success','Oportunidad guardada correctamente.');redirigir('oportunidad',['id'=>$id]);
  }
  pagina($id?'Editar oportunidad':'Nueva oportunidad',function() use($o,$empresas,$id) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">ADMINISTRACIÓN</span>
<h1>
<?= $id?'Editar':'Publicar' ?> oportunidad</h1>
<p>Completa la información que verán los estudiantes.</p>
</div>
</div>
<div class="panel form-panel">
<form method="post" class="form-stack">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<div class="form-grid">
<label>Empresa<select name="empresa_id" required>
<?php foreach($empresas as $c): ?>
<option value="<?= (int)$c['id'] ?>" <?= (int)($o['empresa_id']??0)===(int)$c['id']?'selected':'' ?>>
<?= h($c['nombre']) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<label>Título de la práctica<input name="titulo" maxlength="160" required value="<?= h($o['titulo']??'') ?>">
</label>
</div>
<label>Descripción<textarea name="descripcion" required minlength="10" rows="5">
<?= h($o['descripcion']??'') ?>
</textarea>
</label>
<label>Requisitos<textarea name="requisitos" required minlength="10" rows="4">
<?= h($o['requisitos']??'') ?>
</textarea>
</label>
<div class="form-grid">
<label>Ubicación<input name="ubicacion" required maxlength="120" value="<?= h($o['ubicacion']??'') ?>">
</label>
<label>Modalidad<select name="modalidad">
<?php foreach(['Presencial','Híbrida','Remota'] as $m): ?>
<option <?= ($o['modalidad']??'')===$m?'selected':'' ?>>
<?= h($m) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<label>Vacantes<input type="number" name="vacantes" min="1" max="999" required value="<?= h($o['vacantes']??'1') ?>">
</label>
<label>Fecha límite<input type="date" name="fecha_limite" required value="<?= h($o['fecha_limite']??date('Y-m-d',strtotime('+1 month'))) ?>">
</label>
<label>Estado<select name="estado">
<option value="activa" <?= ($o['estado']??'activa')==='activa'?'selected':'' ?>>Vigente</option>
<option value="cerrada" <?= ($o['estado']??'')==='cerrada'?'selected':'' ?>>Cerrada</option>
</select>
</label>
</div>
<div class="form-actions">
<button class="button primary">Guardar oportunidad</button>
<a class="button outline" href="index.php?r=oportunidades">Cancelar</a>
</div>
</form>
<?php if($id): ?>
<form method="post" action="index.php?r=eliminar-oportunidad" class="delete-form">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<input type="hidden" name="id" value="<?= $id ?>">
<button class="button danger" data-confirm="¿Eliminar esta oportunidad? Solo será posible si no existen postulaciones.">Eliminar oportunidad</button>
</form>
<?php endif; ?>
</div>
<?php }); break;
 case 'eliminar-oportunidad':
  exigir_administrador();if($metodo!=='POST')detener(405);$id=(int)($_POST['id']??0);
  if(consultar_uno('SELECT id FROM postulaciones WHERE oportunidad_id=? LIMIT 1',[$id]))throw new RuntimeException('No puedes eliminar una oportunidad con postulaciones. Ciérrala para conservar la trazabilidad.');
  ejecutar('DELETE FROM oportunidades WHERE id=?',[$id]);mensaje_temporal('success','Oportunidad eliminada.');redirigir('oportunidades');
 case 'empresas':
  exigir_administrador();
  if($metodo==='POST'){
   $nombre=trim((string)($_POST['nombre']??''));$sector=trim((string)($_POST['sector']??''));
    $sitio_web=trim((string)($_POST['sitio_web']??''));$id=(int)($_POST['id']??0);
   if(mb_strlen($nombre)<3||mb_strlen($nombre)>150||mb_strlen($sector)<3||mb_strlen($sector)>100)throw new RuntimeException('Revisa el nombre y sector de la empresa.');
   if($sitio_web!==''&&(!filter_var($sitio_web,FILTER_VALIDATE_URL)||!in_array(parse_url($sitio_web,PHP_URL_SCHEME),['http','https'],true)||mb_strlen($sitio_web)>255))throw new RuntimeException('URL de empresa inválida.');
   if($id)ejecutar('UPDATE empresas SET nombre=?,sector=?,sitio_web=? WHERE id=?',[$nombre,$sector,$sitio_web?:null,$id]);
   else ejecutar('INSERT INTO empresas(nombre,sector,sitio_web) VALUES(?,?,?)',[$nombre,$sector,$sitio_web?:null]);
   mensaje_temporal('success','Empresa guardada.');redirigir('empresas');
  }
  $empresas=consultar_todos('SELECT * FROM empresas ORDER BY nombre');
  pagina('Empresas vinculadas',function() use($empresas) { ?>
<div class="pagina-heading">
<div>
<span class="eyebrow">DIRECTORIO</span>
<h1>Empresas vinculadas</h1>
<p>Administra las organizaciones que ofrecen prácticas.</p>
</div>
</div>
<div class="detail-grid">
<div class="panel table-wrap">
<table>
<thead>
<tr>
<th>Empresa</th>
<th>Sector</th>
<th>Acción</th>
</tr>
</thead>
<tbody>
<?php foreach($empresas as $c): ?>
<tr>
<td>
<?= h($c['nombre']) ?>
</td>
<td>
<?= h($c['sector']) ?>
</td>
<td>
<button type="button" class="link-button" data-empresa='<?= h(json_encode($c,JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)) ?>'>Editar</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<aside class="panel apply-box">
<h2>Agregar o editar empresa</h2>
<form method="post" class="form-stack" id="formularioEmpresa">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<input type="hidden" name="id" value="">
<label>Nombre<input required name="nombre" maxlength="150">
</label>
<label>Sector<input required name="sector" maxlength="100">
</label>
<label>Sitio web (opcional)<input type="url" name="sitio_web" maxlength="255" placeholder="https://...">
</label>
<button class="button primary">Guardar empresa</button>
<button type="reset" class="button outline">Limpiar</button>
</form>
</aside>
</div>
<?php });break;
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
