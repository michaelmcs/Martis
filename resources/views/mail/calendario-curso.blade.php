<x-mail::message>
# 📅 Calendario de {{ $curso->nombre }}

@if($nombre)Hola **{{ $nombre }}**,@else Hola,@endif

Aquí tienes el calendario del curso **{{ $curso->nombre }}**{{ $curso->institucion ? ' — '.$curso->institucion->nombre : '' }} ({{ $curso->periodo_academico }}).

@if($horarios->isNotEmpty())
## 🕐 Horario de clases

<x-mail::table>
| Día | Horario | Aula |
|:----|:--------|:-----|
@foreach($horarios as $h)
| {{ \App\Models\Horario::DIAS[$h->dia] ?? '—' }} | {{ \Illuminate\Support\Carbon::parse($h->hora_inicio)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($h->hora_fin)->format('H:i') }} | {{ $h->aula ?: 'Por definir' }} |
@endforeach
</x-mail::table>
@endif

@if($eventos->isNotEmpty())
## 📝 Próximas fechas

<x-mail::table>
| Fecha | Actividad | Tipo |
|:------|:----------|:-----|
@foreach($eventos as $ev)
| {{ $ev['fecha']->translatedFormat('d M Y') }} | {{ $ev['titulo'] }} | {{ $ev['tipo'] }} |
@endforeach
</x-mail::table>
@endif

Adjuntamos un archivo **.ics** con todo esto. Ábrelo desde tu celular o computadora y se agregará automáticamente a tu calendario (Google Calendar, Outlook o el del teléfono), con **recordatorios automáticos** para cada clase y entrega.

<x-mail::panel>
✅ Así nunca pierdes de vista cuándo hay clase, cuándo entregas un trabajo y cuándo es el examen.
</x-mail::panel>

Saludos,<br>
**{{ config('app.name') }}**
</x-mail::message>
