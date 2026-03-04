<?php

namespace App\Exports;

use App\Models\StudentProfile;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        protected ?string $search = null,
        protected ?int $filter_classroom_id = null,
        protected ?int $filter_level_id = null
    ) {}

    public function query()
    {
        return User::where('role', 'siswa')
            ->with(['latestProfile.profileable.classroom.level'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhereHas('latestProfile', fn ($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn ($sq) => $sq->where('nis', 'like', "%{$this->search}%")->orWhere('nisn', 'like', "%{$this->search}%"))))
            ->when($this->filter_classroom_id, fn ($q) => $q->whereHas('latestProfile', fn ($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn ($sq) => $sq->where('classroom_id', $this->filter_classroom_id))))
            ->when($this->filter_level_id, fn ($q) => $q->whereHas('latestProfile', fn ($pq) => $pq->whereHasMorph('profileable', [StudentProfile::class], fn ($sq) => $sq->whereHas('classroom', fn ($cq) => $cq->where('level_id', $this->filter_level_id)))))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Nama Lengkap',
            'Email',
            'NIS',
            'NISN',
            'Tingkat',
            'Kelas',
            'Status',
            'Alamat',
            'Nama Ayah',
            'Nama Ibu',
            'Telepon Orang Tua',
        ];
    }

    public function map($user): array
    {
        $profile = $user->latestProfile?->profileable;

        return [
            $user->name,
            $user->email,
            $profile?->nis ?? '-',
            $profile?->nisn ?? '-',
            $profile?->classroom?->level?->name ?? '-',
            $profile?->classroom?->name ?? '-',
            ucfirst($profile?->status ?? '-'),
            $profile?->address ?? '-',
            $profile?->father_name ?? '-',
            $profile?->mother_name ?? '-',
            $profile?->guardian_phone ?? ($profile?->phone ?? '-'),
        ];
    }
}
