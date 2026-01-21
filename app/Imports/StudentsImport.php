<?php

namespace App\Imports;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Classroom;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            $classroom = Classroom::where('name', $row['nama_kelas'])->first();

            // Handle empty email: if empty, generate a unique one based on NIS or unique ID
            $email = $row['email'];
            if (empty($email)) {
                $uniqueId = $row['nis'] ?: ($row['nik'] ?: uniqid());
                $email = 'student_' . $uniqueId . '@baitusyukur.id';
            }

            $user = User::create([
                'name'      => $row['nama_lengkap'],
                'email'     => $email,
                'password'  => Hash::make('password'),
                'role'      => 'siswa',
                'is_active' => true,
            ]);

            $dob = $row['tanggal_lahir'];
            if (is_numeric($dob)) {
                $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dob)->format('Y-m-d');
            }

            $studentProfile = StudentProfile::create([
                'nis'             => $row['nis'] ? (string) $row['nis'] : null,
                'nisn'            => $row['nisn'] ? (string) $row['nisn'] : null,
                'nik'             => $row['nik'] ? (string) $row['nik'] : null,
                'dob'             => $dob,
                'pob'             => $row['tempat_lahir'] ?: null,
                'address'         => $row['alamat'] ?: null,
                'phone'           => $row['no_telepon'] ? (string) $row['no_telepon'] : null,
                'father_name'     => $row['nama_ayah'] ?: null,
                'nik_ayah'        => $row['nik_ayah'] ? (string) $row['nik_ayah'] : null,
                'mother_name'     => $row['nama_ibu'] ?: null,
                'nik_ibu'         => $row['nik_ibu'] ? (string) $row['nik_ibu'] : null,
                'no_kk'           => $row['no_kk'] ? (string) $row['no_kk'] : null,
                'no_akta'         => $row['no_akta'] ?: null,
                'classroom_id'    => $classroom?->id,
                'status'          => 'baru',
            ]);

            $user->profiles()->create([
                'profileable_id'   => $studentProfile->id,
                'profileable_type' => StudentProfile::class,
            ]);

            return $user;
        });
    }

    public function prepareForValidation($data, $index)
    {
        // Convert potential numeric fields from Excel to strings to avoid 'validation.string'
        $data['nis'] = $data['nis'] !== null ? (string)$data['nis'] : null;
        $data['nisn'] = $data['nisn'] !== null ? (string)$data['nisn'] : null;
        $data['nik'] = $data['nik'] !== null ? (string)$data['nik'] : null;
        $data['no_telepon'] = $data['no_telepon'] !== null ? (string)$data['no_telepon'] : null;
        $data['nik_ayah'] = $data['nik_ayah'] !== null ? (string)$data['nik_ayah'] : null;
        $data['nik_ibu'] = $data['nik_ibu'] !== null ? (string)$data['nik_ibu'] : null;
        $data['no_kk'] = $data['no_kk'] !== null ? (string)$data['no_kk'] : null;

        return $data;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'max:255'],
            'email'        => ['nullable', 'email', 'unique:users,email'],
            'nis'          => ['nullable', 'unique:student_profiles,nis'],
            'nisn'         => ['nullable', 'unique:student_profiles,nisn'],
            'nik'          => ['required', 'unique:student_profiles,nik'],
            'tanggal_lahir' => ['required'],
            'nama_kelas'   => ['nullable', 'exists:classrooms,name'],
            'tempat_lahir' => ['nullable'],
            'alamat'       => ['nullable'],
            'no_telepon'   => ['nullable'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nama_lengkap' => 'Nama Lengkap',
            'email' => 'Email',
            'nik' => 'NIK',
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'tanggal_lahir' => 'Tanggal Lahir',
            'nama_kelas' => 'Nama Kelas',
            'tempat_lahir' => 'Tempat Lahir',
            'alamat' => 'Alamat',
            'no_telepon' => 'No Telepon',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            // Specific field messages
            'nama_lengkap.required' => 'Kolom Nama Lengkap wajib diisi.',
            'nik.required' => 'Kolom NIK wajib diisi.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'nis.unique' => 'NIS ini sudah terdaftar di sistem.',
            'nisn.unique' => 'NISN ini sudah terdaftar di sistem.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
            'email.email' => 'Format email tidak valid.',
            'tanggal_lahir.required' => 'Kolom Tanggal Lahir wajib diisi.',
            'nama_kelas.exists' => 'Kelas tidak ditemukan. Pastikan nama kelas sesuai.',
            
            // Generic fallback messages
            '*.required' => ':attribute wajib diisi.',
            '*.string' => ':attribute harus berupa teks.',
            '*.email' => 'Format :attribute tidak valid.',
            '*.unique' => ':attribute sudah terdaftar di sistem.',
            '*.exists' => ':attribute tidak ditemukan.',
            '*.max' => ':attribute tidak boleh lebih dari :max karakter.',
            '*.numeric' => ':attribute harus berupa angka.',
            '*.date' => ':attribute harus berupa tanggal yang valid.',
        ];
    }
}
