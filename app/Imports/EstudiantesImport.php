<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EstudiantesImport implements ToArray, WithHeadingRow
{
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
