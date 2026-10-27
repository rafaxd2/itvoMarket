# Diseño del Sprint 2: catálogo con búsqueda y filtro, y recuperación de contraseña

## Sistema visual
Se reutiliza completo el de `docs/sprint1/design.md`: claymorfismo (fórmula de sombras y radios), paleta de colores, tipografía (Baloo 2 para títulos, Arial para cuerpo, escala de tamaños), logo y animaciones. Este documento solo define las vistas nuevas y lo que cambia.

Los wireframes de este sprint (grises, con botones negros) definen únicamente la **estructura y los textos**. Los colores, sombras y tipografía NO se copian de ellos: se aplica el sistema clay verde/naranja.

## Cambios en lo existente
- **Header de vistas internas:** se mantiene el de Sprint 1 (logo, pestañas, ícono de perfil con menú "Rol actual / Cambiar de rol / Cerrar sesión"). Lo que el wireframe llama "Mi cuenta" es ese menú de perfil.
- **Rol Comprador:** la pestaña única es **"Catálogo"** (activa, con la "píldora" clay). La pantalla "Bienvenido a ITVOmarket / Has iniciado sesión como comprador" del Sprint 1 se reemplaza por el catálogo. Siguen sin mostrarse "Mapa", "Publicar producto" ni "Mi ubicación" para el comprador.
- **Login:** debajo del botón "Iniciar sesión" se agrega el enlace "¿Olvidaste tu contraseña?" (texto verde `#2E7D5B`, 13px, centrado; el enlace "Crear una cuenta" se queda donde está).

## Vistas

### Catálogo de publicaciones (solo Comprador) — estados "catálogo inicial", "resultados" y "sin resultados"
Estructura de arriba hacia abajo:
1. Título de pantalla (Baloo 2, 32px): "Catálogo de publicaciones" sin criterios, "Resultados de búsqueda" con criterios.
2. Subtítulo (Arial 15px, `#6B7A72`): sin criterios "Encuentra lo que ofrecen otros estudiantes. Los precios son informativos."; con criterios y resultados "Se muestran publicaciones que coinciden con el texto y la categoría elegida."; sin resultados "Puedes cambiar el texto o elegir otra categoría para seguir buscando."
3. **Barra de filtros** dentro de una tarjeta clay: campo "Nombre o palabra clave" (placeholder "¿Qué estás buscando?", ancho flexible), lista desplegable "Categoría" (primera opción "Todas las categorías", el resto sale de la tabla `categorias`) y botón primario clay "Buscar". En celular los tres se apilan.
4. Con criterios aplicados, debajo de la barra: línea de resumen `Búsqueda: “texto” · Categoría: nombre` (12.5px, `#6B7A72`; si solo hay uno de los dos criterios, se muestra solo ese) y el enlace "Limpiar filtros" (subrayado, verde).
5. Contador (Baloo 2, 20px): "6 publicaciones disponibles" sin criterios; "2 publicaciones encontradas" con criterios; "0 publicaciones encontradas" sin resultados. En singular: "1 publicación encontrada".
6. **Cuadrícula de tarjetas:** 3 columnas en escritorio, 2 en tablet, 1 en celular; separación de 24px.

**Tarjeta de publicación** (superficie clay clara, radio 24px):
- Imagen arriba, proporción 4:3, `object-fit: cover`, esquinas superiores redondeadas con el mismo radio, `loading="lazy"`. Si no hay imagen o no carga, fondo `#F6F8F7` con el ícono `bi-image` en `#9AA6A0` centrado.
- Nombre del producto (Baloo 2, 18px, 600), máximo 2 líneas con puntos suspensivos.
- Precio como insignia en píldora: fondo `#F4A259`, texto `#1F2A24`, Baloo 2 20px 700, formato `$350.00 MXN` (el naranja solo como fondo, no como color de texto, por contraste).
- Categoría (Arial 12.5px, `#6B7A72`).
- "Vendedor: nombre" (Arial 12.5px, `#6B7A72`).
- Toda la tarjeta, incluida imagen y nombre, es un enlace al detalle de la publicación. El cursor es pointer y al hover/foco la tarjeta recibe una elevación clay sutil; el enlace tiene indicador de foco visible para teclado.

**Estado sin resultados:** en lugar de la cuadrícula, una tarjeta clay hundida (fondo `#F6F8F7`) con el título "No encontramos publicaciones" (Baloo 2, 22px), el texto "No hay coincidencias para “texto” en Categoría. Prueba con otra palabra o elimina los filtros." (si solo hay categoría: "No hay publicaciones en Categoría. Prueba con otra categoría o elimina los filtros.") y el botón "Limpiar filtros".

**Estado sin ninguna publicación en el sistema:** la misma tarjeta con el título "Aún no hay publicaciones" y el texto "Cuando los vendedores publiquen sus productos, los verás aquí."

**Comportamiento:** el formulario envía por GET (`index.php?accion=catalogo&q=texto&categoria=3`), así que el texto y la categoría se conservan en los campos después de buscar y el resultado se puede recargar o compartir. Sin paginación en este sprint: máximo 60 publicaciones por consulta, ordenadas de la más reciente a la más antigua.

**Animaciones:** ninguna de entrada para las tarjetas (regla de Sprint 1). Solo las microinteracciones ya definidas: botón "Buscar" que se hunde al presionar y anillo verde de enfoque en el campo y la lista.

### Detalle de publicación (solo Comprador, solo lectura)
Se accede desde cualquier punto de la tarjeta y usa `index.php?accion=producto&id=N`. Mantiene el header de vistas internas y el estilo clay del catálogo.

- En escritorio, presentar una tarjeta clay amplia con imagen grande a la izquierda y la información a la derecha; en móvil, apilar la imagen arriba del contenido.
- La imagen usa `object-fit: cover`; si no existe o no carga, muestra el mismo placeholder `bi-image` del catálogo.
- Mostrar el nombre del producto como título Baloo 2, el precio en la insignia naranja del catálogo, la categoría, la descripción completa y el nombre del vendedor.
- Mostrar estado y condición en un apartado secundario solo si existen sus datos relacionados.
- Incluir arriba el botón "← Volver al catálogo". Conservar en el enlace los parámetros `q` y `categoria` presentes al entrar al detalle.
- Ante un identificador inválido o sin producto, mostrar una tarjeta clay amable con "Esta publicación no está disponible" y el botón "Volver al catálogo".
- No incluir acciones de compra/edición, reseñas, mapa ni ubicación: corresponden a sprints posteriores.

### Pantallas de recuperación de contraseña (3 pantallas y aviso final)
Las pantallas de solicitud, código y nueva contraseña comparten el mismo marco que **Login**: logo grande centrado arriba, fondo con los objetos flotantes, tarjeta clay a la izquierda y **panel lateral verde clay "Recupera tu acceso"** a la derecha, con la misma animación de entrada de dos pasos (logo, luego tarjeta y panel). No llevan header de vistas internas. Pie de página fijo como en todas las vistas.

Texto del panel lateral: se usa el texto de cada wireframe; el párrafo común en las pantallas de recuperación es "Una misma cuenta para consultar como Comprador o publicar como Vendedor."

**1. "Olvidé mi contraseña"** (solicitud de código)
- Subtítulo: "Solicita un código temporal para recuperar el acceso a tu cuenta."
- Tarjeta "Recuperación de contraseña": campo "Correo registrado" (placeholder `nombre@gmail.com`), texto de ayuda "Usa el mismo correo Gmail con el que creaste tu cuenta.", botón primario "Solicitar código de recuperación", enlace "Volver a inicio de sesión".
- Panel: "Usa el correo con el que te registraste. El código te permitirá elegir una nueva contraseña."
- Error de formato o campo vacío: borde y sombra en rojo `#D64545`, mensaje de ayuda junto al campo, desplazamiento lateral único (regla de Sprint 1).
- Correo no registrado: conservar el valor y mostrar "Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta."; no enviar correo ni avanzar a la pantalla de código.
- Al exceder cinco solicitudes de código en quince minutos por sesión, mostrar "Demasiados intentos. Espera unos minutos e inténtalo de nuevo."
- Si falla la base de datos o el envío, mostrar un mensaje genérico sin detalles técnicos ni errores fatales.
- **Después de enviar**: consultar el correo; si existe, generar y enviar el código, guardar una solicitud válida en la sesión y pasar a "Revisa tu correo".

**2. "Revisa tu correo"** (verificación)
- Subtítulo: "Solicitud registrada. El siguiente paso es ingresar tu código de recuperación."
- Tarjeta: recuadro clay hundido "Correo indicado" que muestra el correo de la solicitud; texto "Enviamos un código de 6 dígitos a este correo. Ingrésalo aquí para crear una nueva contraseña."; etiqueta "Código de recuperación".
- Mostrar seis casillas individuales clay, cada una de un dígito (`inputmode=numeric`, `maxlength=1`); la primera usa `autocomplete=one-time-code` y recibe foco al cargar. Al escribir, solo aceptar números y avanzar el foco; Backspace vuelve a la casilla anterior; pegar seis dígitos los reparte en las casillas. Un campo oculto `codigo` combina las seis casillas antes de enviar. Sin JavaScript se muestra un campo de texto de seis dígitos como respaldo.
- Ayuda: "Ingresa los 6 dígitos que recibiste en tu correo." y "¿No lo encuentras? Revisa la carpeta de correo no deseado antes de solicitar otro código."
- Botón primario "Verificar código". Debajo, enlaces "Reenviar código", "Corregir correo" y "Volver a inicio de sesión".
- Reenvío: requiere una solicitud válida en sesión, correo registrado y al menos 60 segundos desde el envío anterior; además respeta el máximo de cinco solicitudes de código en quince minutos por sesión. Al reenvío permitido, reemplazar el código previo y mostrar "Te enviamos un código nuevo". Mientras dure la espera, indicar cuántos segundos faltan. "Corregir correo" regresa a la pantalla de solicitud con el correo previo y limpia el estado de recuperación; el correo corregido se valida y consulta antes de enviar.
- El acceso directo a la pantalla de código sin una solicitud válida guardada en la sesión regresa al formulario de solicitud.
- Código incorrecto o vencido: mostrar "Código incorrecto o vencido" en esta misma pantalla, conservar el correo y permitir solicitar un código nuevo. Contar fallos en sesión; al quinto, invalidar `token_reset` en la base.
- Panel: "Ingresa el código que enviamos a tu correo para comprobar que la cuenta es tuya. Después podrás elegir una nueva contraseña." Mantener el párrafo compartido de los paneles de autenticación.

**3. "Crea tu nueva contraseña"** (solo tras verificar el código)
- Subtítulo: "El código fue verificado. Ahora elige una contraseña segura para tu cuenta."
- Tarjeta: campos "Nueva contraseña" y "Confirmar contraseña", caja clay hundida "Requisitos: Al menos 8 caracteres, con letras y números.", botón "Guardar nueva contraseña", enlaces "Volver a ingresar el código" y "Volver a inicio de sesión".
- Antes de mostrar esta pantalla y al guardar, validar en el servidor que la sesión tenga el indicador de código verificado y que el hash siga vigente en la base (`token_expira > NOW()`). Si se entra sin verificación, redirigir a "Revisa tu correo". Si el código expiró, volver a la pantalla de código con "Código incorrecto o vencido".
- Errores de contraseña se muestran junto a sus campos en rojo.

**Aviso final "Contraseña actualizada"**
- Al guardar correctamente, redirigir a Login y mostrar el mensaje de éxito "Contraseña actualizada. Ya puedes iniciar sesión."
- No inicia sesión automáticamente.

### Correo de recuperación
- Asunto: "Tu código de recuperación de ITVOmarket"
- Remitente: "ITVOmarket" (con el correo configurado en `.env`).
- Cuerpo (HTML con estilos en línea y versión de texto plano): saludo "Hola,", "Recibimos una solicitud para restablecer tu contraseña.", código de 6 dígitos grande, visible y destacado, aviso "Este código vence en 15 minutos y solo se puede usar una vez.", "Si no lo solicitaste, ignora este mensaje; tu contraseña no cambia."
- Fuente Arial, ancho máximo 480px, sin imágenes (para que se vea igual en cualquier cliente de correo).

## Datos que usa este sprint
- Tabla `productos`: id_producto, id_vendedor, id_categoria, nombre, descripcion_producto, precio, imagen_producto
- Tabla `categorias`: id_categoria, tipo_categoria
- Tabla `vendedores`: id_vendedor, id_usuario (para obtener el nombre del vendedor)
- Tabla `usuario`: id_usuario, nombre, correo, contrasena, rol, fecha_registro, **token_reset** (VARCHAR 100, NULL: hash SHA-256 de `id_usuario . ':' . codigo`), **token_expira** (DATETIME, NULL)

Consulta del catálogo (una sola, con parámetros opcionales): `productos` unida con `categorias`, `vendedores` y `usuario` (nombre del vendedor). El filtro de texto busca en `productos.nombre` y `productos.descripcion_producto`; el de categoría compara `productos.id_categoria`. Las demás tablas del modelo (calificaciones, estados, condición) no se usan en este sprint.

(Ajusten los nombres exactos a los de su script SQL final si difieren.)