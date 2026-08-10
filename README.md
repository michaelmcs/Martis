# Sistema Docentes

Sistema de gestión de asistencia y notas para docentes universitarios. Es una aplicación web en la que cada docente puede registrarse y llevar el control completo de sus cursos, sin importar la institución en la que trabaje. Funciona como un cuaderno digital de asistencia y notas, flexible y ordenado. Cada docente accede únicamente a su propia información.

## Características principales

- Registro y autenticación de docentes, con roles de docente y administrador.
- Gestión de instituciones y cursos, con régimen académico, año, periodo y duración en semanas.
- Gestión de estudiantes, con importación desde Excel, captura tipo hoja de cálculo y lectura de listas desde imágenes.
- Control de asistencia por sesión, con marcado masivo, tipos de día y feriados nacionales automáticos.
- Clases virtuales con enlace de reunión que sí cuentan para la asistencia.
- Unidades, evaluaciones y criterios definidos por el docente, con pesos personalizables.
- Cuaderno de notas tipo hoja de cálculo, con cálculo automático de promedios y nota final en escala vigesimal.
- Horario semanal y generación automática de las sesiones del ciclo, con calendario exportable.
- Asistente de inteligencia artificial para apoyar la revisión de exámenes y la generación de preguntas.
- Panel de administración con estadísticas y gestión de docentes.

## Tecnologías

- PHP 8.3 y Laravel 13
- Livewire 3 y Volt
- MySQL 8
- Tailwind CSS 3
- Laravel Excel para importar y exportar hojas de cálculo
- Tesseract.js para el reconocimiento óptico de caracteres

## Requisitos

- PHP 8.3 o superior con las extensiones BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, MySQL, Tokenizer, XML, Zip y GD.
- Composer.
- Node.js y npm.
- MySQL 8.

## Instalación

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar la base de datos en el archivo .env y crear las tablas
php artisan migrate

# 4. Compilar los recursos y arrancar el servidor
npm run build
php artisan serve
```

Para las funciones del asistente de inteligencia artificial, agregar la clave de acceso correspondiente en el archivo `.env`.

## Licencia

Proyecto académico. Todos los derechos reservados a sus autores.
