<?php

declare(strict_types=1);

// Datos de demostración. Ejecución: php database/seed.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este archivo debe ejecutarse desde la terminal.');
}

require dirname(__DIR__) . '/src/bootstrap.php';

$correoAdministrador = 'administrador@practica360.test';
$correoEstudiante = 'estudiante@practica360.test';
$claveAdministrador = getenv('CLAVE_ADMIN_INICIAL') ?: 'AdminDemo2026!';
$claveEstudiante = getenv('CLAVE_ESTUDIANTE_INICIAL') ?: 'AlumnoDemo2026!';

$usuarios = [
    ['Coordinación de Prácticas', $correoAdministrador, $claveAdministrador, 'administrador', null],
    ['Ana Morales', $correoEstudiante, $claveEstudiante, 'estudiante', '2026-001'],
];

foreach ($usuarios as [$nombre, $correo, $clave, $rol, $carnet]) {
    $existe = consultar_uno('SELECT id FROM usuarios WHERE correo = ?', [$correo]);
    if ($existe) {
        continue;
    }

    ejecutar(
        'INSERT INTO usuarios
            (nombre_completo, correo, contrasena_hash, rol, carnet_estudiante)
         VALUES (?, ?, ?, ?, ?)',
        [$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT), $rol, $carnet]
    );
}

$empresas = [
    ['TecNova Guatemala', 'Tecnología', 'https://example.com'],
    ['Horizonte Consultores', 'Consultoría', null],
    ['Fundación Impulsa', 'Organización social', null],
    ['Nexo Creativo', 'Comunicación y diseño', null],
];

foreach ($empresas as [$nombre, $sector, $sitioWeb]) {
    if (consultar_uno('SELECT id FROM empresas WHERE nombre = ?', [$nombre])) {
        continue;
    }

    ejecutar(
        'INSERT INTO empresas (nombre, sector, sitio_web) VALUES (?, ?, ?)',
        [$nombre, $sector, $sitioWeb]
    );
}

$administrador = consultar_uno(
    'SELECT id FROM usuarios WHERE correo = ?',
    [$correoAdministrador]
)['id'];

$oportunidades = [
    [
        'TecNova Guatemala', 'Practicante de desarrollo web',
        'Apoyo en el desarrollo de módulos web internos y pruebas de funcionalidades.',
        'Conocimientos básicos de HTML, CSS, JavaScript y PHP.',
        'Ciudad de Guatemala', 'Híbrida', 2, 35,
    ],
    [
        'Horizonte Consultores', 'Auxiliar de análisis de datos',
        'Colaboración en la preparación de reportes e indicadores institucionales.',
        'Manejo de hojas de cálculo, SQL básico y comunicación efectiva.',
        'Mixco, Guatemala', 'Presencial', 1, 45,
    ],
    [
        'Fundación Impulsa', 'Apoyo en proyectos comunitarios',
        'Participación en gestión de actividades sociales y documentación de avances.',
        'Organización, redacción y trabajo en equipo.',
        'Ciudad de Guatemala', 'Presencial', 3, 60,
    ],
    [
        'Nexo Creativo', 'Asistente de diseño digital',
        'Creación de piezas visuales para canales institucionales y prototipos de interfaz.',
        'Conocimientos de diseño visual y uso básico de herramientas digitales.',
        'Guatemala', 'Remota', 2, 25,
    ],
];

foreach ($oportunidades as [
    $empresa, $titulo, $descripcion, $requisitos,
    $ubicacion, $modalidad, $vacantes, $dias,
]) {
    if (consultar_uno('SELECT id FROM oportunidades WHERE titulo = ?', [$titulo])) {
        continue;
    }

    $idEmpresa = consultar_uno(
        'SELECT id FROM empresas WHERE nombre = ?',
        [$empresa]
    )['id'];

    $fechaLimite = (new DateTimeImmutable("+$dias days"))->format('Y-m-d');

    ejecutar(
        'INSERT INTO oportunidades (
            empresa_id, titulo, descripcion, requisitos, ubicacion,
            modalidad, vacantes, fecha_limite, estado, creado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $idEmpresa, $titulo, $descripcion, $requisitos, $ubicacion,
            $modalidad, $vacantes, $fechaLimite, 'activa', $administrador,
        ]
    );
}

echo "Datos de demostración cargados.\n";
echo "Administrador: $correoAdministrador / $claveAdministrador\n";
echo "Estudiante: $correoEstudiante / $claveEstudiante\n";
echo "Cambia las contraseñas para cualquier uso no local.\n";
