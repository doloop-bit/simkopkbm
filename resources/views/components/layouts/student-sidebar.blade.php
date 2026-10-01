<x-ui.menu activate-by-route>
    <x-ui.menu-item title="Dashboard" icon="o-home" :link="route('student.dashboard')" />
    <x-ui.menu-item title="Materi" icon="o-book-open" :link="route('student.materials')" />
    <x-ui.menu-item title="Ulangan" icon="o-clipboard-document-check" :link="route('student.exams')" />
</x-ui.menu>
