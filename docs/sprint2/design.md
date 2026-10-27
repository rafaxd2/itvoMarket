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
- Sin hover con elevación (regla de Sprint 1); la tarjeta no es un enlace en este sprint.

**Estado sin resultados:** en lugar de la cuadrícula, una tarjeta clay hundida (fondo `#F6F8F7`) con el título "No encontramos publicaciones" (Baloo 2, 22px), el texto "No hay coincidencias para “texto” en Categoría. Prueba con otra palabra o elimina los filtros." (si solo hay categoría: "No hay publicaciones en Categoría. Prueba con otra categoría o elimina los filtros.") y el botón "Limpiar filtros".

**Estado sin ninguna publicación en el sistema:** la misma tarjeta con el título "Aún no hay publicaciones" y el texto "Cuando los vendedores publiquen sus productos, los verás aquí."

**Comportamiento:** el formulario envía por GET (`index.php?accion=catalogo&q=texto&categoria=3`), así que el texto y la categoría se conservan en los campos después de buscar y el resultado se puede recargar o compartir. Sin paginación en este sprint: máximo 60 publicaciones por consulta, ordenadas de la más reciente a la más antigua.

**Animaciones:** ninguna de entrada para las tarjetas (regla de Sprint 1). Solo las microinteracciones ya definidas: botón "Buscar" que se hunde al presionar y anillo verde de enfoque en el campo y la lista.

### Pantallas de recuperación de contraseña (3 pantallas y aviso final)
Las pantallas de solicitud, código y error comparten el mismo marco que **Login**: logo grande centrado arriba, fondo con los objetos flotantes, tarjeta clay a la izquierda y **panel lateral verde clay "Recupera tu acceso"** a la derecha, con la misma animación de entrada de dos pasos (logo, luego tarjeta y panel). No llevan header de vistas internas. Pie de página fijo como en todas las vistas.

Texto del panel lateral (igual en las tres pantallas): se usa el texto de cada wireframe; el párrafo común es "Una misma cuenta para consultar como Comprador o publicar como Vendedor."

**1. "Olvidé mi contraseña"** (solicitud de código)
- Subtítulo: "Solicita un código temporal para recuperar el acceso a tu cuenta."
- Tarjeta "Recuperación de contraseña": campo "Correo registrado" (placeholder `tu.correo@ejemplo.com`), texto de ayuda "Si el correo está asociado a una cuenta, recibirás un código para restablecer tu contraseña.", botón primario "Solicitar código de recuperación", enlace "Volver a inicio de sesión".
- Panel: "Usa el correo con el que te registraste. El código te permitirá elegir una nueva contraseña."
- Error de formato o campo vacío: borde y sombra en rojo `#D64545`, mensaje de ayuda junto al campo, desplazamiento lateral único (regla de Sprint 1).
- **Después de enviar**: guardar el correo en la sesión y mostrar la pantalla de código con un aviso verde clay hundido: "Revisa tu correo. Si está asociado a una cuenta, recibirás un código que vence en 15 minutos. Si no lo ves, busca en spam." Mostrar el mismo aviso exista o no la cuenta.

**2. "Ingresa tu código"**
- Subtítulo: "Escribe el código de 6 dígitos que enviamos a tu correo para continuar."
- Tarjeta: aviso neutral de revisión del correo; campo "Código de 6 dígitos" (numérico, conserva ceros iniciales), campo "Nueva contraseña" (placeholder "Escribe una nueva contraseña"), caja informativa clay hundida con "Requisitos: Al menos 8 caracteres, con letras y números. Evita tu nombre y no reutilices otras contraseñas.", campo "Confirmar nueva contraseña" (placeholder "Repite la nueva contraseña"), botón primario "Guardar nueva contraseña", enlace "Solicitar un código nuevo" y enlace "Volver a inicio de sesión".
- Panel: "El código es temporal y de un solo uso. No lo compartas con nadie ni compartas tu nueva contraseña."
- Validar que el hash SHA-256 de `id_usuario . ':' . codigo` coincida y que `token_expira > NOW()`. El código vence en 15 minutos. El correo se toma de la sesión, nunca del formulario de código.
- Los errores (código incorrecto o vencido, campo vacío, contraseñas distintas o contraseña débil) se muestran junto al campo en rojo. Contar en sesión los errores de código; al quinto, invalidar el código y pedir uno nuevo.

**3. "Código incorrecto o vencido"**
- Subtítulo: "Solicita un código nuevo para continuar con la recuperación de tu cuenta."
- Tarjeta: caja clay hundida "Código incorrecto o vencido — Por seguridad, el código dejó de ser válido. Solicita uno nuevo para continuar.", botón primario "Solicitar un código nuevo" (lleva a la pantalla 1), enlace "Volver a inicio de sesión".
- Panel: "Los códigos de recuperación son temporales y de un solo uso. Si no se puede validar, comienza una nueva solicitud."
- No se permite continuar con un código incorrecto, vencido, usado o después del quinto intento fallido.

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