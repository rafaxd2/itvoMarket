# Tareas del Sprint 2: catálogo con búsqueda y filtro, y recuperación de contraseña

Reglas: hacer una tarea a la vez, probarla en el navegador y hacer commit antes de pasar a la siguiente. No crear, modificar ni eliminar tablas de la base de datos `marketivo`. Capturar siempre las excepciones de PDO y de PHPMailer: el usuario nunca debe ver un error fatal de PHP, solo mensajes en el formulario.

## 1. Preparación
- [x] 1.1 Verificar, sin modificar nada, que la tabla `usuario` ya tenga las columnas `token_reset` y `token_expira`. Si faltan, detenerse y avisar (las agrega el responsable de la base de datos).
- [x] 1.2 Agregar al archivo `.env` las variables nuevas y cargarlas en `config/config.php`: `APP_URL` (por ejemplo `http://localhost:8000`), `MAIL_MODO` (`smtp` o `log`), `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_NAME`. Ningún valor real va en el código.
- [x] 1.3 Crear `.env.example` en la raíz con todas las variables del proyecto y valores de ejemplo (sin secretos reales), para que el equipo sepa qué llenar. Este archivo sí se sube a git.
- [x] 1.4 Confirmar que existan `libs/PHPMailer/PHPMailer.php`, `SMTP.php` y `Exception.php` (ya se copiaron a mano; no descargar nada ni usar Composer) y que `libs/` no esté en `.gitignore`.

## 2. Catálogo, búsqueda y filtro (HU-07, Requisito 7)
- [x] 2.1 Crear `models/Categoria.php` con `listarTodas()` (id y nombre desde la tabla `categorias`)
- [x] 2.2 Agregar a `models/Producto.php` el método `listarCatalogo($texto, $idCategoria)`: una sola consulta con parámetros preparados que une `productos` con `categorias`, `vendedores` y `usuario` (nombre del vendedor), busca el texto en `productos.nombre` y `productos.descripcion_producto`, filtra por `id_categoria` cuando se indique, combina ambos criterios con AND, ordena de la más reciente a la más antigua y limita a 60 resultados
- [x] 2.3 En esa consulta, tratar el texto como literal: escapar `%`, `_` y `\` antes de armar el `LIKE`
- [x] 2.4 Crear `controllers/CatalogoController.php` con `mostrar()`: leer `q` y `categoria` por GET, limpiar espacios, limitar el texto a 100 caracteres, aceptar la categoría solo si es un entero que existe, llamar a los modelos y pasar los datos a la vista
- [x] 2.5 Crear la vista `views/catalogo.php` según `design.md`: título y subtítulo según el estado, barra de filtros (texto, categoría, "Buscar"), resumen de criterios, contador con singular y plural, cuadrícula de tarjetas con imagen, nombre, precio, categoría y vendedor
- [x] 2.6 Mostrar los estados vacíos: "No encontramos publicaciones" con "Limpiar filtros" cuando hay criterios sin coincidencias, y "Aún no hay publicaciones" cuando la tabla está vacía
- [x] 2.7 Hacer que "Limpiar filtros" regrese al catálogo completo (texto vacío y "Todas las categorías")
- [x] 2.8 Crear una función auxiliar que devuelva la URL de la imagen de un producto: si `imagen_producto` empieza con `http` se usa tal cual; si es una ruta relativa se antepone la carpeta pública de uploads; si está vacía o falla, mostrar el ícono de imagen de respaldo
- [x] 2.9 Crear `public/assets/css/catalogo.css` con los estilos de la barra de filtros, la tarjeta de publicación y los estados vacíos, usando las clases de `clay.css`
- [x] 2.10 Agregar la ruta `accion=catalogo` en `public/index.php`; exigir sesión iniciada (sin sesión, al login)
- [x] 2.11 Hacer que el login y "Cambiar de rol" lleven al Comprador al catálogo (reemplaza la pantalla de bienvenida del Sprint 1) y que su menú muestre solo "Catálogo", sin "Mapa", "Publicar producto" ni "Mi ubicación" (Requisito 7, criterio 10)

## 3. Recuperación de contraseña (HU-10, Requisito 8)
- [x] 3.1 Agregar `generarCodigoRestablecimiento($correo)`: generar el código con `str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)`, guardar en `token_reset` solo `hash('sha256', id_usuario . ':' . codigo)` y calcular `token_expira` con `DATE_ADD(NOW(), INTERVAL 15 MINUTE)` en SQL; reemplazar el código anterior y devolver el código o `null` si no existe el correo
- [x] 3.2 Agregar `buscarPorCodigoRestablecimiento($correo, $codigo)`: comprobar el formato de seis dígitos, buscar por el hash que combina el id del usuario y el código, exigir `token_expira > NOW()` y devolver el usuario o `null`
- [x] 3.3 Adaptar `actualizarContrasena($idUsuario, $codigo, $contrasena)`: guardar con `password_hash()` y dejar `token_reset` y `token_expira` en NULL en la misma consulta. Agregar `invalidarCodigoRestablecimiento($correo)` para consumir el código tras cinco fallos
- [x] 3.4 Crear una función reutilizable `validarContrasenaSegura($contrasena)`: mínimo 8 caracteres, al menos una letra y un número; devuelve el mensaje de error o vacío
- [x] 3.5 Adaptar `services/CorreoServicio.php` con `enviarRecuperacion($correo, $nombre, $codigo)`: mantener el SMTP existente y el registro técnico de errores; usar el asunto "Tu código de recuperación de ITVOmarket", incluir el código grande en HTML y en texto plano, y avisar que vence en 15 minutos. Si `MAIL_MODO` es `log`, escribir el código con `error_log()`
- [x] 3.6 Adaptar `views/olvide_contrasena.php` para solicitar el correo y conservar la misma ayuda neutral; después de enviarlo, pasar a la pantalla de código y mostrar el mismo aviso exista o no la cuenta
- [x] 3.7 Adaptar `AuthController::solicitarRestablecimiento()`: validar el correo, guardarlo en `$_SESSION`, generar y enviar el código cuando exista la cuenta, y mostrar siempre el aviso neutral sin revelar existencia ni detalles del envío
- [x] 3.8 Crear la pantalla 1 "Revisa tu correo" en `views/restablecer_contrasena.php`: mostrar el correo indicado, texto informativo, seis casillas numéricas separadas, campo oculto `codigo`, respaldo de texto sin JS, ayuda, botón "Verificar código" y enlaces "Reenviar código", "Corregir correo" y "Volver a inicio de sesión"
- [x] 3.9 Crear `public/assets/js/recuperacion-codigo.js`: enfocar la primera casilla, aceptar solo números, avanzar el foco al escribir, regresar con Backspace, repartir pegado de seis dígitos y combinar las casillas en el campo oculto antes de enviar
- [x] 3.10 Crear la pantalla 2 `views/nueva_contrasena.php`, accesible solo con código verificado en sesión; adaptar `mostrarNuevaContrasena()` y `guardarNuevaContrasena()` para volver a comprobar el hash vigente en base, validar contraseñas, actualizarlas y limpiar tokens y sesión
- [x] 3.11 Adaptar el controlador y rutas públicas: `accion=verificar_codigo` valida código/expiración y cuenta fallos hasta cinco; `accion=reenviar_codigo` reemplaza código y limita reenvíos a uno cada 60 segundos; `accion=corregir_correo` limpia el estado y precarga el correo previo; `accion=nueva_contrasena` muestra el segundo paso. Incluir todas las rutas de recuperación entre las públicas
- [ ] 3.12 Probar el flujo completo con un correo real: solicitar el código, probar seis casillas, reenvío y corrección de correo, verificar el código y cambiar la contraseña; probar expiración, código incorrecto, quinto intento, código reutilizado y acceso directo a la segunda pantalla

Nota: 3.12 queda pendiente de la prueba manual integral con correo real y sus casos de expiración, intentos y acceso directo.

## 4. Pruebas unitarias (siguiendo el estilo de las pruebas que ya existen en `tests/`)
- [x] 4.1 Pruebas de búsqueda y filtrado: solo texto, solo categoría, ambos a la vez, sin resultados, `%`, `_` y `\` tratados como texto literal, texto vacío con "Todas las categorías" devuelve todo
- [x] 4.2 Pruebas de recuperación: seis casillas, generación/hash/expiración del código, uso único, código incorrecto/vencido, quinto intento invalida, reenvío espera 60 segundos, corregir correo limpia estado y conserva el valor, pantalla dos exige verificación y revalida el hash vigente, reglas de contraseña, contraseña válida guarda hash y limpia tokens/sesión y mensaje neutro con correo inexistente. Usar datos en memoria; no tocar datos reales.
- [x] 4.3 Ejecutar todas las pruebas y corregir lo que falle

## 5. Cierre del sprint
- [ ] 5.1 Probar manualmente todos los criterios de aceptación de `requirements.md`
- [x] 5.2 Actualizar el README: variables nuevas de `.env`, cómo colocar PHPMailer en `libs/PHPMailer/`, cómo crear la contraseña de aplicación de Gmail y cómo usar `MAIL_MODO=log` sin internet

Nota: 5.1 queda pendiente hasta completar la validación manual de todos los criterios de aceptación; las pruebas automatizadas no sustituyen esa revisión.

## 6. EXTRA (no forma parte de HU-07 ni HU-10; NO implementar a menos que se pida por nombre: "extra del bucket")
- [ ] 6.1 Agregar a `.env` y `.env.example`: `ALMACEN_IMAGENES` (`local` por defecto o `bucket`), `S3_ENDPOINT`, `S3_REGION`, `S3_BUCKET`, `S3_KEY_ID`, `S3_SECRET`, `S3_URL_PUBLICA`
- [ ] 6.2 Crear `services/AlmacenImagenes.php` con `subir($rutaTemporal, $nombreArchivo, $tipoMime)`: subir el archivo al bucket con una petición PUT por cURL firmada con AWS Signature V4 (compatible con S3), sin SDK ni Composer, y devolver la URL pública final
- [ ] 6.3 Hacer que `ProductoController::publicar()` use el servicio solo si `ALMACEN_IMAGENES=bucket`, guarde la URL completa en `productos.imagen_producto`, y si la subida falla o no hay internet, guarde la imagen localmente como hasta ahora