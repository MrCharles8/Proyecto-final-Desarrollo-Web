<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="descripcion" content="Plataforma de oportunidades y seguimiento de prácticas profesionales">
<title>
<?= h($titulo) ?> · <?= h($config['nombre_aplicacion']) ?>
</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/main.js" defer>
</script>
</head>
<body>
<div class="shell">
<aside class="sidebar" id="barraNavegacion" aria-label="Navegación principal">
<a class="brand" href="index.php">
<span>Práctica<span class="brand-accent">360</span>
</span>
</a>
<div class="nav-caption">PLATAFORMA</div>
<nav>
<a class="nav-link" href="index.php?r=inicio">⌂ <span>Inicio</span>
</a>
<a class="nav-link" href="index.php?r=oportunidades">▦ <span>Oportunidades</span>
</a>
<?php if ($u && $u['rol'] === 'estudiante'): ?>
<a class="nav-link" href="index.php?r=mis-postulaciones">◉ <span>Mis postulaciones</span>
</a>
<?php endif; ?>
<?php if ($u && $u['rol'] === 'administrador'): ?>
<a class="nav-link" href="index.php?r=administrar-postulaciones">◉ <span>Postulaciones</span>
</a>
<a class="nav-link" href="index.php?r=formulario-oportunidad">＋ <span>Nueva oportunidad</span>
</a>
<a class="nav-link" href="index.php?r=empresas">▤ <span>Empresas</span>
</a>
<?php endif; ?>
</nav>
<div class="sidebar-bottom">UNIVERSIDAD · DESARROLLO WEB<br>
<span>Proyecto académico / Caso 3</span>
</div>
</aside>
<div class="workspace">
<header class="topbar">
<button class="mobile-menu" id="alternarMenu" aria-label="Abrir menú" aria-expanded="false">☰</button>
<div class="topbar-titulo">
<?= h($titulo) ?>
</div>
<div class="account">
<?php if ($u): ?>
<span class="avatar">
<?= h(mb_strtoupper(mb_substr($u['nombre_completo'], 0, 1))) ?>
</span>
<span class="account-name">
<?= h($u['nombre_completo']) ?>
<small>
<?= es_administrador() ? 'Administración' : 'Estudiante' ?>
</small>
</span>
<form method="post" action="index.php?r=salir">
<input type="hidden" name="_csrf" value="<?= h(csrf()) ?>">
<button class="link-button" type="submit">Salir</button>
</form>
<?php else: ?>
<a class="button subtle small" href="index.php?r=ingresar">Iniciar sesión</a>
<a class="button primary small" href="index.php?r=registro">Crear cuenta</a>
<?php endif; ?>
</div>
</header>
<main class="content">
<?php if ($mensaje_temporal): ?>
<div class="alert <?= h($mensaje_temporal['kind']) ?>" role="status">
<?= h($mensaje_temporal['message']) ?>
</div>
<?php endif; ?>
<?= $content ?>
</main>
<footer class="footer">Práctica360 · Sistema de prácticas profesionales <span>Proyecto académico · 2026</span>
</footer>
</div>
</div>
</body>
</html>
