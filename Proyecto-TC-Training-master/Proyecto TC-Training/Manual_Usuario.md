# Manual de Usuario

## 1. Introducción al proyecto

Este proyecto es una aplicación de gestión administrativa y de pago desarrollada en PHP con una interfaz web. Permite que administradores y usuarios regulares accedan a funciones específicas para registrar, consultar y controlar información de clientes, planes y pagos.

El objetivo principal del sistema es centralizar la administración de usuarios, proyectos y pagos, además de facilitar la generación de reportes, la exportación de documentos y el seguimiento del cumplimiento administrativo.

## 2. Requisitos técnicos para ejecutar el proyecto

- Servidor web local: XAMPP o similar.
- PHP 7.4+ instalado.
- MySQL / MariaDB.
- Un navegador moderno (Chrome, Firefox, Edge).
- Carpeta del proyecto ubicada en el directorio de servidor local (`htdocs` en XAMPP).
- Base de datos `tc_control` creada y accesible.
- Conexión de red local funcional si se usa intranet.

## 3. Guía paso a paso para la instalación

1. Copie la carpeta del proyecto en la ruta `c:\xampp\htdocs\Proyecto TC-Training`.
2. Abra XAMPP y active Apache y MySQL.
3. Abra `phpMyAdmin` y cree la base de datos `tc_control` si no existe.
4. Importe el archivo SQL `tc_control.sql` desde el directorio raíz del proyecto.
5. Verifique el archivo de configuración `Proyecto TC-Training/configuracion/conecxion.php` y ajuste los datos de conexión si es necesario.
6. Acceda desde el navegador a `http://localhost/Proyecto TC-Training/Proyecto TC-Training/`.
7. Inicie sesión con las credenciales de administrador registradas o use el formulario de registro si procede.

## 4. Instrucciones para el uso básico de la aplicación

1. Abrir la URL de la aplicación en el navegador.
2. Ingresar usuario y contraseña en la pantalla de login.
3. Si el usuario es administrador, podrá acceder a todas las secciones de configuración y gestión.
4. Si el usuario es regular, verá solo datos y opciones de consulta permitidas.
5. Navegar por el menú lateral para acceder a clientes, pagos, planes y ajustes.
6. Usar los filtros disponibles en cada lista para buscar y ordenar información.

## 5. Solución de problemas comunes

- Si no se carga la aplicación, verificar que Apache y MySQL estén activos en XAMPP.
- Si aparece error de conexión, revisar `configuracion/conecxion.php` y credenciales de base de datos.
- Si el login falla, comprobar que el usuario exista y que la contraseña sea correcta.
- Si no se muestran reportes, actualizar la página y revisar los filtros aplicados.
- Si la exportación a PDF o Excel no funciona, asegurarse de que JavaScript esté habilitado en el navegador.

## 6. Descripción funcional del sistema por perfil de usuario

### 6.1 Descripción general del sistema y objetivos

El sistema es una plataforma de control administrativo que facilita:

- Registro y administración de usuarios y clientes.
- Gestión de planes y pagos.
- Generación de reportes y gráficos.
- Exportación de datos a PDF y Excel.

Su objetivo es mejorar el control interno y apoyar la presentación de información para decisiones administrativas.

### 6.2 Acceso y autenticación paso a paso

1. Abrir la URL de la aplicación.
2. Ingresar el nombre de usuario en el campo "Usuario".
3. Ingresar la contraseña en el campo "Contraseña".
4. Presionar el botón "Iniciar sesión".
5. En caso de olvidar la contraseña, usar la opción de recuperación y responder la pregunta secreta.

### 6.3 Funciones disponibles para el administrador

El administrador tiene permisos para:

- Configurar el sistema y datos generales.
- Registrar nuevos usuarios y clientes.
- Editar datos existentes de clientes, planes y pagos.
- Eliminar registros cuando sea necesario.
- Generar reportes y exportarlos.
- Acceder a ajustes y modificar parámetros del sistema.

#### Cómo configurar el sistema por primera vez

1. Iniciar sesión con el usuario administrador.
2. Ir al módulo de ajustes o configuración.
3. Verificar datos de conexión y parámetros generales.
4. Registrar los primeros usuarios o clientes desde el formulario correspondiente.
5. Guardar cada registro con los datos obligatorios.

#### Cómo registrar, editar y eliminar usuarios

1. Ir al módulo de clientes o usuarios.
2. Seleccionar "Nuevo registro" o formulario de registro.
3. Completar los campos necesarios: nombre, usuario, plan y demás información.
4. Guardar.
5. Para editar, seleccionar el registro y usar la opción "Editar".
6. Para eliminar, usar el botón de eliminación y confirmar la acción.

### 6.4 Procedimiento para registrar y gestionar proyectos

En este sistema, los proyectos se gestionan como registros de clientes y pagos.

1. Ir a la sección de clientes.
2. Presionar "Registrar cliente".
3. Llenar los datos del cliente y asignar un plan.
4. Guardar el registro.
5. Para gestionar, usar las opciones de editar y actualizar datos.
6. Visualizar el estado del pago y el plan contratado.

### 6.5 Generación de reportes y gráficos

1. Ir a la sección de pagos o reportes.
2. Seleccionar el período o filtros deseados.
3. El sistema mostrará la lista de resultados.
4. Los gráficos se generan automáticamente con Chart.js para mostrar tendencias y comparativas.
5. Revisar las leyendas y etiquetas para entender cada gráfico.

### 6.6 Exportación y descarga de documentos PDF y Excel

1. En la sección de reportes, buscar los botones de exportación.
2. Hacer clic en "Exportar a Excel" para descargar el listado en formato `xlsx`.
3. Hacer clic en "Exportar a PDF" para generar un documento descargable.
4. Guardar el archivo en la carpeta local deseada.
5. Abrir el archivo con el software correspondiente (Excel o lector de PDF).

### 6.7 Resolución de problemas comunes y contacto de soporte interno

Problemas frecuentes:

- No carga la página: verificar servicio Apache y acceso a `localhost`.
- Error de conexión a la base de datos: revisar credenciales y nombre de base de datos en `configuracion/conecxion.php`.
- No recibe la descarga: comprobar que el navegador no bloquee ventanas emergentes.
- Recuperación de acceso: usar la opción de recuperar contraseña y responder la pregunta secreta.

Contacto de soporte interno:

- Administrador del sistema: responsable en la intranet.
- En caso de incidencias, notificar al equipo de TI o coordinador de la plataforma.

### 6.8 Recomendaciones para mantener la seguridad y buena práctica de uso

- No compartir credenciales con otras personas.
- Cerrar sesión al terminar el uso.
- Usar contraseñas seguras y cambiarlas periódicamente.
- No modificar la configuración del servidor sin autorización.
- Mantener actualizado el software del servidor local.

## 7. Sugerencias y recursos para la elaboración del manual

Para que el manual tenga un nivel profesional, se recomienda:

- Mantener un estilo claro, sin lenguaje técnico innecesario.
- Usar listas numeradas para procesos paso a paso.
- Incluir capturas de pantalla con flechas o recuadros para mostrar botones y menús.
- Enfocar las instrucciones al usuario final y no al código.
- Usar herramientas visuales como Canva o Documentos de Google para una presentación estética.

### 7.1 Enlaces de referencia

- https://greatlakesadvisory.com/blog/how-to-write-a-good-instruction-manual-in-steps/
- https://es.scribd.com/document/319491587/Plantilla-Manual-de-Usuario-1
- https://www.proprofskb.com/templates/user-manual/

### 7.2 Recomendaciones finales

- Validar cada paso con una prueba práctica en la aplicación.
- Organizar el manual en secciones fáciles de encontrar.
- Mantener el documento actualizado conforme cambie el sistema.
