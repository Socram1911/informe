# Sistema de informe de gestion (SIG)

SGI es una aplicación web desarrollada en PHP diseñada para facilitar la creación, colaboración y gestión de informes departamentales. Permite a los administradores definir la estructura de los informes, asignar secciones a diferentes usuarios y, finalmente, compilar y exportar los resultados a un documento de Word (.docx) utilizando una plantilla predefinida.

## Características Principales

-   **Gestión de Usuarios y Roles**:
    -   **Administrador**: Control total sobre usuarios, departamentos, informes y plantillas.
    -   **Supervisor**: Puede crear informes, asignar secciones, revisar y aprobar contenido.
    -   **Editor**: Responsable de redactar el contenido de las secciones que le son asignadas.

-   **Creación Dinámica de Informes**:
    -   Los supervisores y administradores pueden crear nuevos informes especificando un título y un período.
    -   Se pueden añadir capítulos y secciones a cada informe, asignando cada sección a un departamento o usuario específico.

-   **Editor de Contenido Enriquecido**:
    -   Los editores utilizan **CKEditor** para redactar el contenido, permitiendo el uso de formato de texto, tablas, listas e imágenes.
    -   Las imágenes subidas se almacenan en el servidor y se incrustan automáticamente en el documento final.

-   **Flujo de Aprobación**:
    -   Los editores envían sus secciones completadas para revisión.
    -   Los supervisores pueden aprobar o rechazar el contenido, dejando comentarios para guiar las correcciones.
    -   El estado de cada sección (Borrador, En Revisión, Aprobado, Rechazado) es visible en el panel.

-   **Generación de Documentos Word (.docx)**:
    -   Utiliza la librería **PhpOffice/PhpWord** para generar documentos de Word a partir de una plantilla `.docx`.
    -   Reemplaza marcadores de posición (`${periodo}`, `${año}`) con datos dinámicos.
    -   Inyecta el contenido HTML complejo (incluyendo tablas e imágenes) de cada sección en marcadores específicos (`${contenido1}`, `${contenido2}`, etc.), renderizándolo correctamente en el documento final.

## Tecnologías Utilizadas

-   **Backend**: PHP 8.x
-   **Base de Datos**: MySQL / MariaDB
-   **Dependencias de PHP (via Composer)**:
    -   `phpoffice/phpword`: Para la creación y manipulación de archivos `.docx`.
    -   `phpmailer/phpmailer`: Para el envío de notificaciones por correo (funcionalidad implícita).
-   **Frontend**: HTML5, CSS3, JavaScript (ESM).
-   **Librerías Frontend**:
    -   **Bootstrap 5**: Para el diseño y la interfaz de usuario.
    -   **CKEditor 5**: Como editor de texto enriquecido.

## Instalación y Configuración

1.  **Clonar el Repositorio**:
    ```bash
    git clone <url-del-repositorio>
    cd informe2
    ```

2.  **Configurar el Entorno**:
    -   Asegúrate de tener un servidor web local como XAMPP, WAMP o similar con PHP 8+ y MySQL.
    -   Copia los archivos del proyecto en el directorio `htdocs` (o `www`) de tu servidor.

3.  **Base de Datos**:
    -   Crea una nueva base de datos en tu gestor de MySQL (ej. `informes_db`).
    -   Importa la estructura y los datos iniciales desde el archivo `config/bd.sql`.

4.  **Configurar la Conexión**:
    -   Edita el archivo `config/database.php` y actualiza las credenciales de conexión a tu base de datos:
      ```php
      define('DB_HOST', 'localhost');
      define('DB_NAME', 'informes_db'); // Tu base de datos
      define('DB_USER', 'root');        // Tu usuario
      define('DB_PASS', '');            // Tu contraseña
      ```

5.  **Instalar Dependencias**:
    -   Asegúrate de tener [Composer](https://getcomposer.org/) instalado.
    -   Abre una terminal en la raíz del proyecto y ejecuta:
      ```bash
      composer install
      ```

6.  **Permisos de Carpetas**:
    -   Asegúrate de que el servidor web tenga permisos de escritura sobre la carpeta `uploads/` para poder guardar las imágenes subidas por los usuarios.

7.  **Acceder a la Aplicación**:
    -   Inicia tu servidor web.
    -   Abre tu navegador y ve a `http://localhost/informe2`.
    -   Puedes iniciar sesión con los usuarios de ejemplo definidos en `config/bd.sql`.

## Estructura del Proyecto

-   `index.php`: Punto de entrada de la aplicación.
-   `config/`: Carpeta que contiene archivos de configuración, como la conexión a la base de datos.
-   `public/`: Archivos públicos accesibles desde la web (CSS, JS, imágenes).
-   `src/`: Código fuente de la aplicación.
    -   `Controller/`: Controladores de la aplicación.
    -   `Model/`: Modelos de datos y lógica de negocio.
    -   `View/`: Vistas y plantillas HTML.
-   `vendor/`: Dependencias de Composer.

## Flujo de Trabajo Básico

1.  Un **Administrador** crea los usuarios y departamentos.
2.  Un **Supervisor** crea un nuevo informe para un período determinado (ej. "Informe Mensual - Septiembre").
3.  El **Supervisor** añade secciones al informe (ej. "Resumen de Ventas", "Actividades de Marketing") y las asigna a los **Editores** correspondientes.
4.  El **Editor** asignado accede a su panel, abre la sección y redacta el contenido usando el editor de texto. Guarda su progreso como borrador.
5.  Una vez finalizado, el **Editor** marca la sección como "Completada".
6.  El **Supervisor** recibe una notificación, revisa el contenido y lo **aprueba** o **rechaza** con comentarios.
7.  Cuando todas las secciones están aprobadas, el **Supervisor** va al listado de informes y hace clic en "Generar Word".
8.  El sistema invoca a `api/word_generator.php`, que compila todo el contenido en la plantilla y devuelve un archivo `.docx` para descargar.
