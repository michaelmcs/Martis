# Sistema de gestión de asistencia y notas para docentes

Documento de diseño completo. Versión inicial para arrancar el desarrollo con Laravel y Livewire.

---

## 1. Visión del producto

Un servicio en la nube donde cualquier docente universitario, sin importar su institución, puede registrarse y llevar el control de sus cursos. Funciona como un cuaderno digital de notas y asistencia, flexible y con ayuda de inteligencia artificial. Cada docente ve únicamente su propia información.

El sistema resuelve tres necesidades principales. Primero, llevar la asistencia de forma rápida y confiable. Segundo, registrar y calcular notas con criterios que el docente define a su medida. Tercero, apoyarse en inteligencia artificial para revisar exámenes y trabajos sin perder el control final sobre la calificación.

---

## 2. Stack tecnológico

El backend es Laravel, que maneja toda la aplicación web, los usuarios, los cursos y las notas. La interfaz dinámica se construye con Livewire, que permite formularios que crecen y cambian en vivo sin necesidad de un frontend separado. También se podría usar Angular para la interfaz si más adelante quieres algo con más aspecto de aplicación, pero Livewire cubre bien lo que necesitas hoy y es más simple para empezar con una sola persona. La autenticación se maneja con Laravel Breeze. La importación y exportación de hojas de cálculo se hace con el paquete Laravel Excel de Maatwebsite. Los archivos como sílabos y trabajos entregados se guardan con el sistema de almacenamiento de Laravel. Los correos salen a través de colas para no bloquear la aplicación. La base de datos es MySQL.

Para leer los exámenes escaneados se usa un servicio aparte hecho en Python. Python es el mejor ecosistema para trabajar con imágenes y reconocimiento de texto. Este servicio recibe la foto o el escaneo del examen, extrae el texto con reconocimiento óptico de caracteres usando herramientas como Tesseract o PaddleOCR que son gratuitas, y devuelve el resultado a Laravel. Laravel y el servicio de Python se comunican por una interfaz interna. Así cada parte hace lo que mejor sabe hacer, Laravel la aplicación web y Python el procesamiento de imágenes.

---

## 3. Arquitectura y aislamiento de datos

Es un sistema donde muchos docentes independientes usan la misma aplicación. No se necesita aislamiento complejo por institución. Basta con que cada registro importante pertenezca a un docente y que las consultas siempre estén limitadas al docente que inició sesión.

La regla de oro es que ningún docente puede ver ni tocar los datos de otro. Esto se logra con tres capas. Primero, cada curso guarda el identificador del docente que lo creó. Segundo, se usan políticas de autorización de Laravel para verificar la propiedad antes de mostrar o modificar cualquier recurso. Tercero, se aplica un filtro global de consulta que agrega automáticamente la condición del docente actual, de modo que sea imposible olvidarlo por accidente.

---

## 4. Roles de usuario

En la primera versión hay un solo rol principal, el docente. El docente es dueño de todo lo que crea. Los estudiantes existen como registros dentro de los cursos, pero no inician sesión todavía. Más adelante se puede evaluar darles acceso para que vean sus notas o entreguen trabajos, pero eso se deja para una etapa posterior porque agrega bastante complejidad.

---

## 5. Modelo de datos completo

A continuación las tablas principales con sus campos más importantes.

### Docente
Es el usuario del sistema.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| nombres | texto | Nombres completos |
| dni | texto | Documento de identidad |
| correo | texto | Correo único para iniciar sesión |
| password | texto | Contraseña cifrada |
| creado_en | fecha | Fecha de registro |

### Institución
Los datos de la universidad que ingresa el docente.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| docente_id | uuid | A quién pertenece |
| nombre | texto | Nombre de la universidad |
| lugar | texto | Ciudad o localidad |
| direccion | texto | Dirección |

### Curso
La unidad central de trabajo.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| docente_id | uuid | Dueño del curso |
| institucion_id | uuid | Institución asociada |
| nombre | texto | Nombre del curso |
| ciclo | texto | Semestre o periodo |
| tope_faltas | número | Máximo de faltas permitidas |
| peso_asistencia | número | Cuánto pesa la asistencia en la nota final |

### Sílabo
Archivo del sílabo adjunto al curso.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| curso_id | uuid | Curso al que pertenece |
| archivo | texto | Ruta del archivo almacenado |
| nombre_original | texto | Nombre visible |

### Horario
El horario semanal de clases del curso. Un curso puede tener varios bloques.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| curso_id | uuid | Curso al que pertenece |
| dia_semana | texto | Lunes, martes y así |
| hora_inicio | hora | Hora de inicio |
| hora_fin | hora | Hora de fin |
| aula | texto | Aula o lugar, opcional |

### Estudiante
Registro de un alumno. Puede reutilizarse entre cursos.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| codigo | texto | Código universitario |
| nombres | texto | Nombres completos |
| correo | texto | Correo del estudiante |

### Matrícula
Conecta a un estudiante con un curso.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| curso_id | uuid | Curso |
| estudiante_id | uuid | Estudiante |
| estado | texto | Activo o retirado |

### Unidad
Cada curso puede tener dos, tres o más unidades.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| curso_id | uuid | Curso al que pertenece |
| numero | número | Número de unidad |
| nombre | texto | Nombre descriptivo |
| peso | número | Cuánto pesa en la nota final del curso |

### Sesión
Un día de clase para el registro de asistencia.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| curso_id | uuid | Curso |
| fecha | fecha | Día de la clase |
| tema | texto | Tema tratado, opcional |

### Asistencia
El estado de un estudiante en una sesión.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| sesion_id | uuid | Sesión |
| estudiante_id | uuid | Estudiante |
| estado | texto | Presente, ausente o justificado |

### Evaluación
Una actividad calificable. Es dinámica en su tipo.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| unidad_id | uuid | Unidad a la que pertenece |
| titulo | texto | Nombre, por ejemplo Trabajo 1 |
| tipo | texto | Examen, trabajo, participación u otro |
| descripcion | texto | Instrucciones y entregable esperado |
| tipo_entregable | texto | PDF, presentación u otro |
| peso | número | Cuánto pesa dentro de la unidad |
| fecha_limite | fecha | Fecha de entrega, opcional |

### Criterio
El detalle de calificación de una evaluación. Aquí está la flexibilidad total.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| evaluacion_id | uuid | Evaluación a la que pertenece |
| nombre | texto | Por ejemplo carátula o presentación |
| puntaje_max | número | Puntaje máximo del criterio |
| orden | número | Orden de aparición |

### Nota
El puntaje de un estudiante en un criterio.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| evaluacion_id | uuid | Evaluación |
| criterio_id | uuid | Criterio calificado |
| estudiante_id | uuid | Estudiante |
| valor | número | Puntaje obtenido |
| comentario | texto | Retroalimentación, opcional |

### Entrega
El archivo que un estudiante envía para una evaluación.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| evaluacion_id | uuid | Evaluación |
| estudiante_id | uuid | Estudiante |
| archivo | texto | Ruta del archivo |
| enviado_en | fecha | Fecha de entrega |

### Análisis de IA
El resultado de una revisión asistida por inteligencia artificial.

| Campo | Tipo | Descripción |
|---|---|---|
| id | uuid | Identificador único |
| entrega_id | uuid | Entrega analizada |
| resumen | texto | Análisis generado |
| nota_sugerida | número | Nota tentativa sugerida |
| aceptado | booleano | Si el docente aceptó la sugerencia |

---

## 6. Cómo funciona la calificación

Esta es la parte más importante de entender porque le da toda la potencia al sistema.

Un curso se divide en unidades. Cada unidad tiene un peso que indica cuánto aporta a la nota final del curso. Dentro de cada unidad hay evaluaciones. Cada evaluación tiene su propio peso dentro de la unidad. Y cada evaluación se compone de criterios, donde cada criterio tiene un puntaje máximo.

La nota de un estudiante en una evaluación es la suma de los puntajes que obtuvo en cada criterio. La nota de una unidad es el promedio ponderado de sus evaluaciones según el peso de cada una. La nota final del curso es el promedio ponderado de las unidades, y si el docente lo configura, la asistencia también puede sumar un porcentaje a esa nota final.

Este esquema permite que el docente arme lo que quiera. Por ejemplo, en el Trabajo 1 puede definir criterios como carátula, presentación y contenido, cada uno con su puntaje. En un examen puede definir criterios por pregunta. Todo es agregable y modificable en cualquier momento.

---

## 7. Módulos funcionales

### Registro e inicio de sesión
El docente se registra con correo, DNI y nombres. Luego completa los datos de su institución. Inicia sesión con correo y contraseña.

### Gestión de cursos
El docente crea cursos, edita sus datos, sube el sílabo y define el ciclo, el tope de faltas y el peso de la asistencia.

### Gestión de estudiantes
El docente importa estudiantes desde un archivo Excel o los agrega uno por uno de forma manual. Puede editar y retirar estudiantes de un curso.

### Asistencia
El docente crea sesiones por día. Para cada sesión marca a cada estudiante como presente, ausente o justificado. El sistema calcula el porcentaje de asistencia y avisa cuando un estudiante supera el tope de faltas.

### Evaluaciones y notas
El docente crea unidades y dentro de ellas evaluaciones. Para cada evaluación define sus criterios con puntaje. Registra las notas por estudiante y el sistema calcula automáticamente la nota de unidad y la nota final.

### Entregas y correo
El docente puede enviar una evaluación por correo a los estudiantes del curso. Los archivos entregados se guardan asociados a cada estudiante y evaluación.

### Horario y calendario
El docente define el horario semanal del curso con sus días, horas y aula. Cada evaluación tiene su fecha límite de entrega y cada examen su fecha. El sistema reúne el horario de clases, las fechas límite de trabajos y las fechas de exámenes en un solo calendario del curso.

Con un botón el docente envía este calendario al correo de los estudiantes. El correo incluye un archivo de calendario estándar en formato ICS que el estudiante abre y agrega a su propio calendario con un toque, sea Google Calendar, Outlook o el del celular. Cada evento lleva su recordatorio automático. Así los estudiantes nunca pierden de vista cuándo hay clase, cuándo entregan un trabajo y cuándo es el examen.

### Reportes
El docente exporta el acta de notas a Excel y a PDF, ve reportes por estudiante y revisa el resumen general del curso.

### Inteligencia artificial
El docente sube un examen o trabajo resuelto y la IA lo revisa comparándolo con los criterios definidos. La IA sugiere una nota con su justificación, pero el docente siempre decide la nota final. La IA también puede generar preguntas de examen a partir del sílabo.

---

## 8. Diseño de la revisión asistida con Python e inteligencia artificial

El flujo tiene dos pasos. Primero, el docente sube la foto o el escaneo del examen o del trabajo. El servicio de Python aplica reconocimiento óptico de caracteres para convertir la imagen en texto legible.

Segundo, ese texto se compara con los criterios de calificación de la evaluación y con la clave de respuestas si el docente la cargó. De esa comparación sale una nota sugerida con su justificación. Cuando el examen es de alternativas la comparación puede hacerse con reglas simples. Cuando las respuestas son abiertas y hay que interpretarlas conviene un modelo de inteligencia artificial. Si se usa un modelo de IA, la llamada se hace siempre desde el servidor para proteger la clave de acceso.

El principio central es que la herramienta asiste y el docente decide. Toda nota sugerida queda marcada como tentativa hasta que el docente la acepta o la corrige. Esto mantiene la responsabilidad académica en manos del profesor.

---

## 9. Consideraciones de seguridad

Las contraseñas se guardan cifradas. Cada consulta a la base de datos se limita al docente que inició sesión. Se usan políticas de autorización antes de mostrar o modificar cualquier recurso. Los archivos subidos se validan por tipo y tamaño. Las llamadas a la API de inteligencia artificial se hacen solo desde el servidor.

---

## 10. Hoja de ruta de construcción

La primera fase es el núcleo. Registro e inicio de sesión, gestión de cursos con institución, y gestión de estudiantes incluyendo la importación desde Excel.

La segunda fase es la asistencia completa con sesiones, marcado de estados y cálculo de porcentajes con alertas.

La tercera fase es el módulo académico. Unidades, evaluaciones dinámicas con criterios, registro de notas y cálculo automático de la nota final.

La cuarta fase son los extras. Horario del curso, calendario con fechas de trabajos y exámenes, envío por correo con archivo ICS para agregar al calendario, exportación de actas a Excel y PDF, y reportes por estudiante.

La quinta fase es la inteligencia artificial para revisión de trabajos y generación de preguntas.

---

## 11. Ideas adicionales para más adelante

Un tablero de inicio con el resumen de cada curso. Plantillas de curso para reutilizar la estructura entre semestres. Periodos académicos para organizar los cursos en el tiempo. Alertas de estudiantes en riesgo por baja asistencia o notas bajas. Un registro de cambios de notas para transparencia. Y un acta oficial lista para imprimir y firmar.
