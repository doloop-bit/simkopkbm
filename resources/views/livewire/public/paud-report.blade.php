<?php

declare(strict_types=1);

use App\Models\PaudReportCard;
use App\Models\SchoolProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.plain')] class extends Component {
    public string $token;
    
    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function with(): array
    {
        $report = PaudReportCard::with([
            'student',
            'student.profiles.profileable',
            'classroom.level',
            'academicYear',
        ])
        ->where('access_token', $this->token)
        ->where('status', 'published')
        ->firstOrFail();

        return [
            'report' => $report,
            'schoolProfile' => SchoolProfile::active(),
        ];
    }
}; ?>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">
        {{-- Header / Logo --}}
        <div class="text-center space-y-4">
            @if($schoolProfile && $schoolProfile->logo_path)
                <img src="{{ asset('storage/' . $schoolProfile->logo_path) }}" alt="Logo" class="mx-auto h-20 w-auto object-contain">
            @else
                <div class="mx-auto h-20 w-20 bg-indigo-100 dark:bg-indigo-900/50 rounded-2xl flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                    <x-ui.icon name="o-academic-cap" class="size-10 text-indigo-600 dark:text-indigo-400" />
                </div>
            @endif
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    {{ $schoolProfile->name ?? config('app.name') }}
                </h1>
                <p class="text-slate-500 font-medium">Portal Laporan Hasil Belajar (PAUD)</p>
            </div>
        </div>

        <x-ui.card shadow class="bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-8 md:p-12">
                {{-- Paper-like container --}}
                <div class="prose dark:prose-invert max-w-none">
                    @include('pdf._paud_rapor_content', [
                        'reportCard' => $report,
                        'student' => $report->student,
                        'studentProfile' => $report->student->profiles()->where('profileable_type', \App\Models\StudentProfile::class)->first()?->profileable,
                        'classroom' => $report->classroom,
                        'academicYear' => $report->academicYear,
                    ])
                </div>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-slate-500 font-medium text-center sm:text-left">
                    Rapor diterbitkan pada: <br class="sm:hidden">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $report->updated_at->translatedFormat('d F Y H:i') }}</span>
                </p>
                <x-ui.button 
                    label="Download PDF" 
                    icon="o-arrow-down-tray" 
                    class="btn-primary shadow-lg shadow-primary/20 w-full sm:w-auto" 
                    onclick="window.print()"
                />
            </div>
        </x-ui.card>

        <p class="text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            body { background: white; }
            .min-h-screen { py: 0; }
            .bg-slate-50 { background: white !important; }
            x-ui\.card, .shadow { box-shadow: none !important; }
            button, .btn-primary { display: none !important; }
            .bg-slate-50 { border: none !important; }
            /* Hide surrounding UI during print, only show the rapor content */
            body * {
                visibility: hidden;
            }
            .prose, .prose * {
                visibility: visible;
            }
            .prose {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</div>
