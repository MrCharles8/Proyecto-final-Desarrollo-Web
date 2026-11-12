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
