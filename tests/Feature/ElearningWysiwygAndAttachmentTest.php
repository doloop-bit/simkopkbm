<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\Subject;
use App\Models\User;
use App\Services\HtmlSanitizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('HtmlSanitizerService cleans harmful tags and preserves images and math attributes', function () {
    $dirtyHtml = '<p>Soal Fisika: Hitung <script>alert("hack")</script><b>kecepatan</b>.</p>'
        .'<img src="/storage/exam-images/1/test.png" onerror="alert(1)" alt="Grafik" />'
        .'<span class="inline-math" data-latex="x^2 + y^2 = z^2">Formula</span>';

    $cleanHtml = HtmlSanitizerService::clean($dirtyHtml);

    expect($cleanHtml)->not->toContain('<script>')
        ->and($cleanHtml)->not->toContain('onerror')
        ->and($cleanHtml)->toContain('<b>kecepatan</b>')
        ->and($cleanHtml)->toContain('<img')
        ->and($cleanHtml)->toContain('src="/storage/exam-images/1/test.png"')
        ->and($cleanHtml)->toContain('data-latex=');
});

test('teacher can upload question with rich text and attachment', function () {
    Storage::fake('private');
    Storage::fake('public');

    $teacher = User::factory()->create(['role' => 'guru']);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();

    $exam = OnlineExam::create([
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'title' => 'Ulangan Harian Matematika',
        'exam_type' => 'daily',
        'passing_grade' => 75,
        'created_by' => $teacher->id,
        'is_published' => true,
    ]);

    $file = UploadedFile::fake()->create('soal_studi_kasus.pdf', 500, 'application/pdf');

    Livewire::actingAs($teacher)
        ->test('admin.elearning.exam-questions', ['examId' => $exam->id])
        ->call('createNew')
        ->set('question_type', 'essay')
        ->set('question_text', '<p>Perhatikan grafik berikut: <img src="/storage/exam-images/sample.png"></p>')
        ->set('points', 20)
        ->set('questionAttachmentFile', $file)
        ->call('save')
        ->assertHasNoErrors();

    $question = OnlineExamQuestion::where('online_exam_id', $exam->id)->first();
    expect($question)->not->toBeNull()
        ->and($question->question_text)->toContain('src="/storage/exam-images/sample.png"')
        ->and($question->attachment_path)->not->toBeNull()
        ->and($question->attachment_name)->toBe('soal_studi_kasus.pdf');

    Storage::disk('private')->assertExists($question->attachment_path);
});

test('exam attachment download authorization works correctly', function () {
    Storage::fake('private');

    $teacher = User::factory()->create(['role' => 'guru']);
    $student = User::factory()->create(['role' => 'siswa']);
    $otherStudent = User::factory()->create(['role' => 'siswa']);

    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $classroom = Classroom::factory()->create();
    $subject = Subject::factory()->create();

    // Setup student profile
    $studentProfile = \App\Models\StudentProfile::factory()->create([
        'classroom_id' => $classroom->id,
    ]);
    \App\Models\Profile::factory()->create([
        'user_id' => $student->id,
        'profileable_id' => $studentProfile->id,
        'profileable_type' => \App\Models\StudentProfile::class,
    ]);

    $path = Storage::disk('private')->put('exam-documents/ujian.pdf', 'dummy content');

    $exam = OnlineExam::create([
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $academicYear->id,
        'semester' => '1',
        'title' => 'Ujian Akhir',
        'exam_type' => 'final',
        'passing_grade' => 75,
        'attachment_path' => 'exam-documents/ujian.pdf',
        'attachment_name' => 'ujian.pdf',
        'created_by' => $teacher->id,
        'is_published' => true,
    ]);

    // Guru can download
    $this->actingAs($teacher)
        ->get(route('elearning.exams.download-attachment', $exam->id))
        ->assertOk();

    // Student in classroom can download
    $this->actingAs($student)
        ->get(route('elearning.exams.download-attachment', $exam->id))
        ->assertOk();

    // Other student (different class) cannot download
    $this->actingAs($otherStudent)
        ->get(route('elearning.exams.download-attachment', $exam->id))
        ->assertStatus(403);
});
