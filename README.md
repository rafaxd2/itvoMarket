# ITVOmarket

Marketplace informativo para vendedores de comida y golosinas universitarios del Instituto Tecnológico del Valle de Oaxaca. El sistema permite publicar productos y compartir ubicación de venta; los compradores consultan catálogo y mapa sin incluir pagos ni compras dentro de la plataforma.

## Requisitos

- PHP 8+
- MariaDB/MySQL con la base de datos `marketitvo` ya creada
- Navegador moderno con acceso a geolocalización
- PHPMailer copiado manualmente en `libs/PHPMailer/` (sin Composer)

## Configuración rápida

1. Copia `.env.example` como `.env` en la raíz del proyecto y completa los datos de tu entorno. No subas `.env` a Git:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=marketitvo
DB_USER=root
DB_PASS=
APP_URL=http://localhost:8000
MAIL_MODO=log
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=
MAIL_FROM_NAME=ITVOmarket
ALMACEN_IMAGENES=local
S3_ENDPOINT=https://s3.us-west-2.idrivee2.com
S3_REGION=us-west-2
S3_BUCKET=itvomarket-imagenes
S3_KEY_ID=
S3_SECRET=
S3_URL_PUBLICA=https://itvomarket-imagenes.s3.us-west-2.idrivee2.com
```

La base de datos y sus tablas deben existir previamente; la aplicación no las crea ni las modifica.

Para usar Gmail, activa la verificación en dos pasos de la cuenta y genera una **contraseña de aplicación** desde la configuración de seguridad de Google. Usa esa contraseña, no la contraseña habitual de Gmail. Configura `MAIL_USERNAME` con la cuenta autorizada, `MAIL_PASSWORD` con la contraseña de aplicación, `MAIL_MODO=smtp`, `MAIL_HOST=smtp.gmail.com` y `MAIL_PORT=587`. No compartas ni guardes credenciales reales en `.env.example` o en Git.

Para desarrollar sin enviar correo ni requerir internet, deja `MAIL_MODO=log`. Los mensajes de recuperación se registran en el log de PHP del servidor local.

PHPMailer debe estar disponible en `libs/PHPMailer/PHPMailer.php`, `libs/PHPMailer/SMTP.php` y `libs/PHPMailer/Exception.php`; se incluye desde esos archivos y no requiere Composer.

Para almacenar las imágenes en IDrive e2, configura `ALMACEN_IMAGENES=s3` y agrega a `.env` el endpoint regional, región, nombre del bucket, URL pública, `S3_KEY_ID` y `S3_SECRET` de una clave con permisos para PutObject y GetObject. La extensión cURL de PHP debe estar habilitada. Las claves se mantienen exclusivamente en `.env`. El bucket se mantiene privado: la aplicación guarda la clave del objeto y sirve las imágenes mediante la ruta autenticada `index.php?accion=imagen&k=CLAVE`; las URL antiguas del bucket también se resuelven mediante esa ruta. Si la carga a S3 falla, la aplicación guarda la imagen en `public/assets/uploads/`. Usa `ALMACEN_IMAGENES=local` para desactivar el bucket.

2. Inicia el servidor local desde la raíz del proyecto con PHP de XAMPP:

```powershell
C:\xampp\php\php.exe -S localhost:8000 -t public
```

3. Abre en el navegador:

```text
http://localhost:8000/index.php?accion=login
```

## Rutas del Sprint 2

Las rutas se sirven mediante `public/index.php`:

- `?accion=catalogo` — catálogo con búsqueda por texto y filtro por categoría (requiere sesión de comprador).
- `?accion=olvide` — formulario para solicitar recuperación de contraseña.
- `?accion=restablecer` — pantalla para ingresar el código de seis dígitos.
- `?accion=verificar_codigo` — valida el código enviado.
- `?accion=reenviar_codigo` — solicita el reenvío del código, con espera mínima de 60 segundos.
- `?accion=corregir_correo` — vuelve al formulario para corregir el correo.
- `?accion=nueva_contrasena` — pantalla protegida para crear la contraseña nueva tras verificar el código.
- `?accion=guardar_contrasena` — guarda la nueva contraseña y regresa al login.

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
C:\xampp\php\php.exe tests\phpunit.phar --bootstrap tests\bootstrap.php tests
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
