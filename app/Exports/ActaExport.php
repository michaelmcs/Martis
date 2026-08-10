<?php

namespace App\Exports;

use App\Models\Curso;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ActaExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        public array $acta,
        public Curso $curso,
    ) {}

    public function title(): string
    {
        return 'Acta de notas';
    }

    public function headings(): array
    {
        $h = ['N°', 'Código', 'Estudiante'];
        foreach ($this->acta['unidades'] as $u) {
            $h[] = 'U'.$u['numero'].' ('.$u['peso'].'%)';
        }
        $h[] = 'Prom. académico';
        if ($this->acta['usaAsistencia']) {
            $h[] = 'Asistencia %';
            $h[] = 'Nota asistencia';
        }
        $h[] = 'NOTA FINAL';
        $h[] = 'Condición';

        return $h;
    }

    public function array(): array
    {
        $filas = [];
        $i = 1;
        foreach ($this->acta['filas'] as $f) {
            $fila = [$i++, $f['codigo'] ?? '', $f['nombre']];
            foreach ($this->acta['unidades'] as $u) {
                $fila[] = $f['unidades'][$u['id']] ?? '-';
            }
            $fila[] = $f['academica'];
            if ($this->acta['usaAsistencia']) {
                $fila[] = $f['asistencia_pct'];
                $fila[] = $f['nota_asistencia'];
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

                // Encabezado en negrita con fondo índigo
                $sheet->getStyle('A1:'.$ultimaCol.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                // Bordes en toda la tabla
                $sheet->getStyle('A1:'.$ultimaCol.$ultimaFila)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                // Columna NOTA FINAL centrada y en negrita
                $colFinal = chr(ord($ultimaCol) - 1);
                $sheet->getStyle($colFinal.'2:'.$colFinal.$ultimaFila)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->getStyle('A2:A'.$ultimaFila)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
