<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PlantillaEstudiantesExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function title(): string
    {
        return 'Estudiantes';
    }

    public function headings(): array
    {
        return ['nombres', 'apellidos', 'codigo', 'correo', 'celular'];
    }

    public function array(): array
    {
        return [
            ['Juan Carlos', 'Pérez Gómez', '20211234', 'juan.perez@correo.com', '987654321'],
            ['Ana María', 'Quispe Flores', '20211235', 'ana.quispe@correo.com', '987111222'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:E1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
                ]);
            },
        ];
    }
}
