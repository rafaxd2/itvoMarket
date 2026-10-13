# ITVOmarket

Marketplace informativo para vendedores de comida y golosinas universitarios del Instituto Tecnológico del Valle de Oaxaca. El sistema permite publicar productos y compartir ubicación de venta; los compradores consultan catálogo y mapa sin incluir pagos ni compras dentro de la plataforma.

## Requisitos

- PHP 8+
- MariaDB/MySQL con la base de datos `itvomarket` ya creada
- Navegador moderno con acceso a geolocalización
- Una clave de Google Maps API configurada en `.env`

## Configuración rápida

1. Copia el archivo `.env.example` o crea un archivo `.env` en la raíz del proyecto con estas variables:

```env
DB_HOST=localhost
DB_NAME=itvomarket
DB_USER=root
DB_PASS=
GOOGLE_MAPS_API_KEY=TU_API_KEY_DE_GOOGLE_MAPS
```

2. Asegúrate de que la base de datos `itvomarket` tenga las tablas `usuario`, `vendedores`, `productos` y `zona_venta`.

3. Ejecuta el servidor local desde la raíz del proyecto:

```bash
php -S localhost:8000 -t public
```

4. Abre en el navegador:

```text
http://localhost:8000/index.php?accion=login
```

## Estructura principal

- `config/` — configuración y conexión PDO
- `controllers/` — controladores del flujo MVC
- `models/` — lógica de acceso a datos por entidad
- `views/` — vistas HTML con Bootstrap 5
- `public/` — punto de entrada del proyecto y archivos públicos
- `public/assets/js/` — JavaScript independiente
- `public/assets/uploads/` — imágenes de productos subidas localmente

## Pruebas unitarias

PHPUnit se distribuye como archivo PHAR independiente en `tests/phpunit.phar`; no se requiere Composer. Para ejecutar las pruebas de modelos se necesita PHP con la extensión `pdo_sqlite` habilitada:

```bash
php tests/phpunit.phar --configuration tests/phpunit.xml
```

La suite usa una base SQLite en memoria, por lo que no modifica la base de datos MariaDB del proyecto.

## Pruebas manuales del Sprint 1

Se recomienda validar cada criterio de aceptación en el navegador según la secuencia indicada.

### 1. Registro de usuario

- [ ] Accede a `index.php?accion=registro`.
- [ ] Completa correo, contraseña y rol, y verifica que la cuenta se crea correctamente.
- [ ] Intenta registrar el mismo correo dos veces; confirma que aparece: `Este correo ya está registrado`.
- [ ] Deja un campo vacío o sin rol; confirma que se marca en rojo y que el formulario no se envía.

### 2. Inicio de sesión

- [ ] Inicia sesión con correo y contraseña correctos.
- [ ] Verifica que el sistema redirige según el rol: comprador o vendedor.
- [ ] Prueba credenciales incorrectas y confirma que aparece: `Correo o contraseña incorrectos`.
- [ ] Intenta acceder directamente a una ruta interna sin sesión e inicia sesión nuevamente para comprobar la redirección al login.

### 3. Cierre de sesión

- [ ] Cierra la sesión desde la vista interna.
- [ ] Verifica que regresa al login.
- [ ] Usa el botón “atrás” del navegador y confirma que la aplicación no permite volver a la vista interna sin autenticación.

### 4. Publicar producto

- [ ] Inicia sesión como vendedor.
- [ ] Entra a la pantalla de publicación y envía nombre, precio y foto válidos.
- [ ] Comprueba que el producto se guarda y aparece en el catálogo del vendedor.
- [ ] Prueba que falta nombre o precio y confirma que se señala el campo faltante.
- [ ] Prueba un precio no positivo y confirma que se rechaza.
- [ ] Prueba una imagen con extensión distinta a jpg/png y confirma el mensaje del formato permitido.
- [ ] Verifica el mensaje de éxito tras publicar.

### 5. Ubicación en tiempo real

- [ ] Como vendedor, entra a "Mi ubicación".
- [ ] Haz clic en el mapa o arrastra el pin y verifica que se llenan latitud y longitud.
- [ ] Usa “Usar mi posición actual” y confirma que el mapa se centra en la ubicación del navegador.
- [ ] Si el navegador bloquea geolocalización, confirma que se muestra el mensaje correspondiente.
- [ ] Activa el toggle “Compartir mi ubicación” y guarda la ubicación.
- [ ] Verifica que la ubicación se registra con latitud, longitud y referencia válidas y que la fecha de actualización cambia.
- [ ] Inactiva el toggle y confirma que la zona deja de mostrarse como activa.

### 6. Cambiar de rol

- [ ] Inicia sesión con un usuario existente.
- [ ] Usa el selector de rol en el header para cambiar de Comprador a Vendedor o viceversa.
- [ ] Verifica que el rol se actualiza en sesión y que la navegación cambia según el nuevo rol.
- [ ] Si cambias a vendedor, confirma que se dirige directamente a la pantalla de publicación de productos.
- [ ] Si cambias a comprador, confirma que se redirige al panel del comprador.

## Nota final

El sprint 1 cumple con un flujo informativo sin pagos ni compras dentro del sistema. La transacción real entre estudiantes se realiza de manera presencial, como se establece en la descripción del proyecto.
