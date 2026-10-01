<x-ui.menu activate-by-route>
    <x-ui.menu-item :title="__('Dashboard Utama')" icon="o-home" :link="route('student.dashboard')" />

    <x-ui.menu-item :title="__('Materi Pembelajaran')" icon="o-book-open" :link="route('student.materials')" />
    <x-ui.menu-item :title="__('Ulangan & Ujian')" icon="o-clipboard-document-check" :link="route('student.exams')" />
</x-ui.menu>
