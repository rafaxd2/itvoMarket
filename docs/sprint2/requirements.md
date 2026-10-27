# Requisitos del Sprint 2: búsqueda y filtrado de publicaciones, y recuperación de contraseña

## Roles
- Comprador: consulta el catálogo de publicaciones, busca por texto y filtra por categoría
- Vendedor: sin cambios en este sprint (su vista queda fuera de este alcance)
- Usuario (cualquier rol): puede recuperar su contraseña por correo

## Fuera de alcance de este sprint
- Mapa de vendedores para el comprador (HU-06, Sprint 4): el comprador NO ve la opción de mapa
- Reseñas, calificaciones y moderación de publicaciones (HU-08, HU-09)
- Editar o eliminar productos (HU-05) y marcar estado activo/inactivo (HU-04)
- Pagos, carrito o compras: el sistema es solo informativo

## Requisito 7: Búsqueda y filtrado de publicaciones (HU-07)
**Historia:** Como Comprador, quiero ver, buscar y filtrar publicaciones por categoría para encontrar rápido lo que se me antoja.

Criterios de aceptación (EARS):

1. [Event-driven]
Cuando el comprador inicia sesión o entra al catálogo sin criterios, el sistema debe mostrar todas las publicaciones registradas con su imagen, nombre, precio, categoría y nombre del vendedor, e indicar cuántas hay ("N publicaciones disponibles").

2. [Event-driven]
Cuando el comprador escribe un texto y presiona "Buscar", el sistema debe mostrar las publicaciones cuyo nombre o descripción contengan ese texto, sin distinguir mayúsculas de minúsculas ni acentos.

3. [Event-driven]
Cuando el comprador elige una categoría y presiona "Buscar", el sistema debe mostrar solo las publicaciones de esa categoría.

4. [Event-driven]
Cuando el comprador escribe un texto Y elige una categoría, el sistema debe mostrar solo las publicaciones que cumplan ambos criterios a la vez.

5. [State-driven]
Mientras haya criterios aplicados, el sistema debe mostrar un resumen de la búsqueda ("Búsqueda: “texto” · Categoría: nombre"), el número de coincidencias ("N publicaciones encontradas") y el enlace "Limpiar filtros".

6. [Event-driven]
Cuando ninguna publicación coincide con los criterios, el sistema debe mostrar "0 publicaciones encontradas", el mensaje "No encontramos publicaciones" con una sugerencia de probar otra palabra o quitar filtros, y el botón "Limpiar filtros".

7. [Event-driven]
Cuando el comprador presiona "Limpiar filtros", el sistema debe borrar el texto, regresar la categoría a "Todas las categorías" y mostrar el catálogo completo.

8. [Event-driven]
Cuando todavía no existe ninguna publicación en el sistema, el sistema debe mostrar el mensaje "Aún no hay publicaciones".

9. [Ubiquitous]
El sistema debe tratar el texto de búsqueda como texto literal: los caracteres `%` y `_` no actúan como comodines, y la consulta usa siempre parámetros preparados.

10. [State-driven]
Mientras el rol actual sea Comprador, el sistema debe mostrar "Catálogo" como pantalla principal (reemplaza la pantalla de bienvenida del Sprint 1) y no debe mostrar la opción de mapa ni "Publicar producto" ni "Mi ubicación".

11. [Event-driven]
Cuando el comprador selecciona cualquier parte de una tarjeta de producto (incluidas la imagen y el nombre), el sistema debe abrir `index.php?accion=producto&id=N` con el detalle de esa publicación.

12. [Event-driven]
Cuando se muestra el detalle de una publicación existente, el sistema debe mostrar su imagen grande o el placeholder del catálogo, nombre, precio, categoría, descripción completa y nombre del vendedor. Debe mostrar estado y condición solo cuando estén registrados. Esta pantalla es de solo lectura y no incluye reseñas, mapa ni ubicación.

13. [Event-driven]
Cuando el identificador de producto no es numérico o no corresponde a una publicación, el sistema debe mostrar "Esta publicación no está disponible" y un botón para volver al catálogo, sin mostrar errores fatales.

14. [Event-driven]
Cuando el comprador regresa desde el detalle al catálogo, el sistema debe conservar los parámetros de búsqueda `q` y `categoria` que tenía al abrir la publicación.

## Requisito 8: Recuperación de contraseña (HU-10)
**Historia:** Como usuario, quiero recuperar mi contraseña mediante mi correo electrónico para poder volver a acceder a mi cuenta si la olvido.

Decisiones del equipo:
- El código de recuperación tiene **6 dígitos**, vence a los **15 minutos** y solo se puede usar una vez.
- El límite es de **5 intentos**; al quinto fallo el código se invalida y se debe solicitar uno nuevo.
- La nueva contraseña debe tener **mínimo 8 caracteres, con al menos una letra y un número**.
- Se envía un **código** (no un enlace) al correo.

Criterios de aceptación (EARS):

1. [Event-driven]
Cuando el usuario abre "¿Olvidaste tu contraseña?" desde el login, el sistema debe mostrar el formulario "Olvidé mi contraseña" con el campo "Correo registrado".

2. [Event-driven]
Cuando el correo está vacío o no tiene formato válido, el sistema debe mostrar la ayuda junto al campo, marcarlo en rojo y no enviar la solicitud. Antes de procesar la solicitud, el servidor debe normalizar el correo con `trim` y minúsculas, y validar que tenga formato de correo válido, dominio exactamente `gmail.com` y máximo 254 caracteres; si no cumple, debe conservar lo escrito y mostrar "Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)" sin consultar la base de datos ni enviar correo. La misma validación se aplica al registro, a "Corregir correo" y a "Reenviar código".

3. [Event-driven]
Cuando el usuario envía un correo con formato válido, el sistema debe consultar la tabla `usuario` con una consulta preparada. Si el correo no está registrado, no debe generar ni enviar un código ni avanzar a la pantalla de código; debe conservarlo en el formulario y mostrar "Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta." Si está registrado, debe generar el código, enviarlo y avanzar a "Revisa tu correo", donde se muestra "Enviamos un código de 6 dígitos a este correo. Ingrésalo aquí para crear una nueva contraseña." Si falla la base o el envío, debe mostrar un mensaje genérico, sin errores fatales ni detalles técnicos. El servidor permite un máximo de cinco solicitudes de código por sesión cada quince minutos y, al excederlo, muestra "Demasiados intentos. Espera unos minutos e inténtalo de nuevo."

4. [Event-driven]
Cuando el correo corresponde a una cuenta registrada, el sistema debe generar un código aleatorio de 6 dígitos, guardar en `token_reset` solo `hash('sha256', id_usuario . ':' . codigo)`, fijar `token_expira` a 15 minutos en SQL y enviar el código al correo.

5. [Event-driven]
Cuando el usuario selecciona "Reenviar código", el correo debe continuar registrado y deben haber pasado al menos 60 segundos desde el envío anterior; además, debe respetarse el máximo de cinco solicitudes por sesión cada quince minutos. Si se permite, el sistema debe reemplazar el código anterior, enviar uno nuevo y mostrar "Te enviamos un código nuevo". Antes de que pasen 60 segundos, debe informar cuánto falta y no generar ni enviar otro código.

6. [Event-driven]
Cuando el usuario ve "Revisa tu correo", el sistema debe mostrar el correo indicado y seis casillas de un dígito (`inputmode=numeric`, `maxlength=1`); la primera usa `autocomplete=one-time-code` y recibe el foco inicial. Al escribir un dígito, el foco avanza; Backspace vuelve a la casilla anterior; pegar seis dígitos los reparte entre las casillas. Solo se aceptan números; un campo oculto `codigo` combina las seis casillas y, sin JavaScript, hay un campo de texto de seis dígitos como respaldo. El texto explica "Ingresa los 6 dígitos que recibiste en tu correo" y recomienda revisar correo no deseado. La pantalla ofrece "Reenviar código", "Corregir correo" y "Volver a inicio de sesión".

7. [Event-driven]
Cuando el usuario selecciona "Corregir correo", el sistema debe regresar a "Olvidé mi contraseña" con el correo previamente escrito y limpiar el estado de recuperación. El correo corregido debe volver a validarse y consultarse; si no está registrado, se muestra el mensaje de correo no registrado y no se envía un código. La pantalla de código solo se permite con una solicitud válida guardada en la sesión. Cuando ingresa un código incorrecto o vencido, el sistema debe mostrar "Código incorrecto o vencido" y conservar el correo.

8. [Event-driven]
Cuando el código falla, el sistema debe contar el intento en `$_SESSION`; al quinto fallo debe poner `token_reset` en NULL y pedir un código nuevo. Cuando el código es válido y no venció (`token_expira > NOW()`), debe marcarlo como verificado en la sesión y mostrar "Crea tu nueva contraseña". Esa pantalla solo se permite con la verificación en sesión y el código todavía vigente en la base de datos. Cuando algún campo de contraseña está vacío, las contraseñas no coinciden o no cumplen la regla de seguridad (mínimo 8 caracteres, una letra y un número), debe mostrar el error junto al campo, marcarlo en rojo y no guardar nada.

9. [Event-driven]
Cuando la nueva contraseña es válida, el sistema debe volver a validar en el servidor que el código verificado siga vigente, guardar la contraseña con `password_hash()`, dejar `token_reset` y `token_expira` en NULL, limpiar el estado de recuperación de sesión y redirigir al login con "Contraseña actualizada". El código no se puede reutilizar.

10. [Event-driven]
Cuando se muestra "Contraseña actualizada", el sistema NO debe iniciar sesión automáticamente y debe dejar visible el formulario de inicio de sesión.

11. [Ubiquitous]
Las credenciales del correo (usuario y contraseña de aplicación) deben leerse solo del archivo `.env`; nunca aparecen en el código ni en git.