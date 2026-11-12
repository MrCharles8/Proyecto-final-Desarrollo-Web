<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';

$rutaLocal = dirname(__DIR__) . '/config.local.php';
if (is_file($rutaLocal)) {
    $config = array_replace($config, require $rutaLocal);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function conexion_bd(): PDO
{
    global $config;
    static $conexion = null;

    if ($conexion === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['servidor_bd'],
            $config['puerto_bd'],
            $config['nombre_bd']
        );

        $conexion = new PDO(
            $dsn,
            $config['usuario_bd'],
            $config['contrasena_bd'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $conexion;
}

function consultar_todos(string $sql, array $parametros = []): array
{
    $consulta = conexion_bd()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

function consultar_uno(string $sql, array $parametros = []): array|false
{
    $consulta = conexion_bd()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetch();
}

function ejecutar(string $sql, array $parametros = []): int
{
    $consulta = conexion_bd()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->rowCount();
}

function h(mixed $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function es_administrador(): bool
{
    return (usuario_actual()['rol'] ?? '') === 'administrador';
}

function sesion_iniciada(): bool
{
    return usuario_actual() !== null;
}

function exigir_sesion(): void
{
    if (!sesion_iniciada()) {
        redirigir('ingresar');
    }
}

function exigir_administrador(): void
{
    exigir_sesion();
    if (!es_administrador()) {
        detener(403);
    }
}

function redirigir(string $ruta, array $parametros = []): never
{
    header(
        'Location: index.php?' . http_build_query(
            ['r' => $ruta] + $parametros
        )
    );
    exit;
}

function detener(int $codigo = 404): never
{
    http_response_code($codigo);
    mostrar_pagina(
        'Error',
        '<h2>Acceso no disponible</h2>' .
        '<p>La página no existe o no tienes permiso para verla.</p>'
    );
    exit;
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verificar_csrf(): void
{
    $tokenEnviado = (string) ($_POST['_csrf'] ?? '');
    if (!hash_equals(csrf(), $tokenEnviado)) {
        http_response_code(419);
        throw new RuntimeException(
            'La sesión del formulario expiró. Actualiza la página.'
        );
    }
}

function mensaje_temporal(string $tipo, string $mensaje): void
{
    $_SESSION['mensaje_temporal'] = [
        'kind' => $tipo,
        'message' => $mensaje,
    ];
}

function valor_previo(string $campo, string $predeterminado = ''): string
{
    return h((string) ($_POST[$campo] ?? $predeterminado));
}

function fecha_valida(string $fecha): bool
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}

function validar_oportunidad(array $datos): array
{
    $errores = [];

    foreach (['titulo' => 160, 'ubicacion' => 120] as $campo => $maximo) {
        $longitud = mb_strlen(trim((string) ($datos[$campo] ?? '')));
        if ($longitud < 3 || $longitud > $maximo) {
            $errores[] = "El campo $campo debe tener entre 3 y $maximo caracteres.";
        }
    }

    foreach (['descripcion', 'requisitos'] as $campo) {
        if (mb_strlen(trim((string) ($datos[$campo] ?? ''))) < 10) {
            $errores[] = "El campo $campo debe tener al menos 10 caracteres.";
        }
    }

    if (!in_array($datos['modalidad'] ?? '', ['Presencial', 'Híbrida', 'Remota'], true)) {
        $errores[] = 'Modalidad inválida.';
    }

    $vacantes = filter_var(
        $datos['vacantes'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 999]]
    );
    if ($vacantes === false) {
        $errores[] = 'Las vacantes deben estar entre 1 y 999.';
    }

    if (!fecha_valida((string) ($datos['fecha_limite'] ?? ''))) {
        $errores[] = 'Fecha límite inválida.';
    }

    if (!in_array($datos['estado'] ?? '', ['activa', 'cerrada'], true)) {
        $errores[] = 'Estado inválido.';
    }

    $idEmpresa = (int) ($datos['empresa_id'] ?? 0);
    if (!consultar_uno('SELECT id FROM empresas WHERE id = ?', [$idEmpresa])) {
        $errores[] = 'Selecciona una empresa válida.';
    }

    return $errores;
}

function mostrar_pagina(string $titulo, string $contenido): void
{
    global $config;

    $mensaje_temporal = $_SESSION['mensaje_temporal'] ?? null;
    unset($_SESSION['mensaje_temporal']);

    $u = usuario_actual();
    $route = h((string) ($_GET['r'] ?? 'inicio'));
    $content = $contenido;
    require __DIR__ . '/layout.php';
}
