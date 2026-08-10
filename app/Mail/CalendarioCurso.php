<?php

namespace App\Mail;

use App\Models\Curso;
use App\Support\IcsGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CalendarioCurso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Curso $curso,
        public string $estudianteNombre = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Calendario del curso: '.$this->curso->nombre,
        );
    }

    public function content(): Content
    {
        $this->curso->loadMissing('horarios', 'institucion', 'unidades.evaluaciones');

        $eventos = $this->curso->unidades
            ->flatMap->evaluaciones
            ->filter(fn ($e) => $e->fecha_limite)
            ->sortBy('fecha_limite')
            ->map(fn ($e) => [
                'fecha' => \Illuminate\Support\Carbon::parse($e->fecha_limite),
                'titulo' => $e->titulo,
                'tipo' => $e->tipo === 'examen' ? 'Examen' : 'Entrega',
            ])
            ->values();

        return new Content(
            markdown: 'mail.calendario-curso',
            with: [
                'curso' => $this->curso,
                'nombre' => $this->estudianteNombre,
                'horarios' => $this->curso->horarios,
                'eventos' => $eventos,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $ics = IcsGenerator::paraCurso($this->curso);

        return [
            Attachment::fromData(fn () => $ics, 'calendario_'.str($this->curso->nombre)->slug().'.ics')
                ->withMime('text/calendar'),
        ];
    }
}
