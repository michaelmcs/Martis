<?php

namespace App\Exports;

use App\Models\Curso;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class CuadernoExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        public array $cuaderno,
        public Curso $curso,
    ) {}

    public function title(): string
    {
        return 'Cuaderno de notas';
    }

    public function headings(): array
    {
        $h = ['N°', 'Código', 'Estudiante'];
        foreach ($this->cuaderno['unidades'] as $u) {
            foreach ($u['evaluaciones'] as $e) {
                $h[] = 'U'.$u['numero'].' · '.$e['titulo'];
            }
            $h[] = 'Prom. U'.$u['numero'];
        }
        $h[] = 'Prom. académico';
        if ($this->cuaderno['usaAsistencia']) {
            $h[] = 'Asistencia %';
        }
        $h[] = 'NOTA FINAL';
        $h[] = 'Condición';

        return $h;
    }

    public function array(): array
    {
        $filas = [];
        $i = 1;
        foreach ($this->cuaderno['filas'] as $f) {
            $fila = [$i++, $f['codigo'] ?? '', $f['nombre']];
            foreach ($this->cuaderno['unidades'] as $u) {
                foreach ($u['evaluaciones'] as $e) {
                    $fila[] = $f['evals'][$e['id']] ?? '-';
                }
                $fila[] = $f['unidades'][$u['id']] ?? '-';
            }
            $fila[] = $f['academica'];
            if ($this->cuaderno['usaAsistencia']) {
                $fila[] = $f['asistencia_pct'];
            }
            $fila[] = $f['final'];
            $fila[] = $f['aprobado'] ? 'Aprobado' : 'Desaprobado';
            $filas[] = $fila;
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaCol = $sheet->getHighestColumn();
                $ultimaFila = $sheet->getHighestRow();

                $sheet->getStyle('A1:'.$ultimaCol.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                ]);
                $sheet->getStyle('A1:'.$ultimaCol.$ultimaFila)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
                ]);
                $sheet->freezePane('D2');
            },
        ];
    }
}
