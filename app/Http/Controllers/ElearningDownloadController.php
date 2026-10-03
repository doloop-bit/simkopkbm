<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElearningDownloadController extends Controller
{
    /**
     * Download lampiran berkas soal di level Ulangan (Word/PDF).
     */
    public function downloadExamAttachment(OnlineExam $exam): StreamedResponse
    {
        $user = auth()->user();

        // Otorisasi: Admin / Guru bebas unduh, Siswa harus terdaftar di kelas ulangan
        if ($user->isSiswa()) {
            $studentClassroomId = $user->studentProfile?->classroom_id ?? null;
            if (! $exam->is_published || ! $studentClassroomId || (int) $exam->classroom_id !== (int) $studentClassroomId) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengunduh berkas soal ini.');
            }
        }

        if (! $exam->attachment_path || ! Storage::disk('private')->exists($exam->attachment_path)) {
            abort(404, 'Berkas lampiran soal tidak ditemukan.');
        }

        $filename = $exam->attachment_name ?: basename($exam->attachment_path);

        return Storage::disk('private')->download($exam->attachment_path, $filename);
    }

    /**
     * Download lampiran berkas pendukung di level Butir Soal (Word/PDF).
     */
    public function downloadQuestionAttachment(OnlineExamQuestion $question): StreamedResponse
    {
        $user = auth()->user();
        $exam = $question->exam;

        if ($user->isSiswa()) {
            $studentClassroomId = $user->studentProfile?->classroom_id ?? null;
            if (! $exam || ! $exam->is_published || ! $studentClassroomId || (int) $exam->classroom_id !== (int) $studentClassroomId) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengunduh lampiran soal ini.');
            }
        }

        if (! $question->attachment_path || ! Storage::disk('private')->exists($question->attachment_path)) {
            abort(404, 'Berkas lampiran butir soal tidak ditemukan.');
        }

        $filename = $question->attachment_name ?: basename($question->attachment_path);

        return Storage::disk('private')->download($question->attachment_path, $filename);
    }
}
