<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentsTemplateExport implements WithHeadings, ShouldAutoSize, WithTitle
{
    public function headings(): array
    {
        return [
            'nama_lengkap',
            'email',
            'nis',
            'nisn',
            'nik',
            'tempat_lahir',
            'tanggal_lahir',
            'nama_kelas',
            'alamat',
            'no_telepon',
            'nama_ayah',
            'nik_ayah',
            'nama_ibu',
            'nik_ibu',
            'no_kk',
            'no_akta',
        ];
    }

    public function title(): string
    {
        return 'Template Import Siswa';
    }
}
