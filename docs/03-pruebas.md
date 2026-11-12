# 03. Plan de pruebas funcionales

**Nota:** esta es una matriz de pruebas *propuestas*, no un acta de pruebas ejecutadas. El equipo debe registrar fecha, responsable, entorno, evidencias y resultado real.

| ID | Escenario | Procedimiento | Resultado esperado | Resultado real |
|---|---|---|---|---|
| CP-01 | Autenticación correcta | Iniciar sesión con cuenta de prueba | Entra al panel | Pendiente |
| CP-02 | Autenticación fallida | Utilizar clave incorrecta | Error genérico sin revelar datos | Pendiente |
| CP-03 | Registro estudiante | Registrar correo y carné nuevos | Cuenta creada | Pendiente |
| CP-04 | Correo repetido | Repetir correo registrado | Se impide duplicado | Pendiente |
| CP-05 | Crear oportunidad | Administrador publica oferta válida | Aparece en catálogo | Pendiente |
| CP-06 | Editar oportunidad | Administrador cambia descripción | Se guardan cambios | Pendiente |
| CP-07 | Eliminar oportunidad sin solicitudes | Usar eliminar | Registro borrado | Pendiente |
| CP-08 | Eliminar oferta con solicitudes | Intentar borrar oferta postulada | Mensaje de bloqueo | Pendiente |
| CP-09 | Filtrar ofertas | Buscar texto y modalidad | Se muestran coincidencias | Pendiente |
| CP-10 | Postular | Estudiante envía motivación válida | Estado inicial: En revisión | Pendiente |
| CP-11 | Postular dos veces | Repetir en misma oportunidad | Bloqueo de duplicado | Pendiente |
| CP-12 | Postular en oferta cerrada | Cerrar oferta e intentar enviar | Operación denegada | Pendiente |
| CP-13 | Cambiar estado | Administrador preselecciona solicitud | Nuevo estado e historial | Pendiente |
| CP-14 | Transición inválida | Intentar cambio final a estado previo | Operación denegada | Pendiente |
| CP-15 | Acceso indebido | Estudiante entra a URL administrativa | Acceso denegado | Pendiente |
| CP-16 | Datos ajenos | Estudiante intenta abrir postulación de otro | Acceso denegado | Pendiente |
| CP-17 | CSRF | Enviar POST sin token | Operación denegada | Pendiente |
| CP-18 | SQL injection | Escribir texto SQL en búsqueda/login | No altera la consulta | Pendiente |
| CP-19 | Adaptación móvil | Abrir en 375 px de ancho | Navegación usable | Pendiente |
| CP-20 | Cierre de sesión | Salir e intentar abrir panel privado | Solicita autenticación | Pendiente |

## Evidencias sugeridas

Capturas con fecha, URL, usuario de prueba y resultado; video corto por flujo; resultados de importación SQL; historial de commits autenticado. Para la entrega, conviertan los campos pendientes en resultados reales y registren defectos con su corrección.
