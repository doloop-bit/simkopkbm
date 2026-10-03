@props([
    'label' => null,
    'wireModel' => null,
    'placeholder' => 'Tulis isi teks / soal di sini...',
    'uploadProperty' => 'tempEditorImage',
    'minHeight' => '140px',
    'compact' => false,
    'required' => false,
])

@once
    @vite(['resources/js/rich-editor.js'])
@endonce

<div
    x-data="setupRichEditor({
        content: @entangle($wireModel).live,
        wireModel: '{{ $wireModel }}',
        uploadProperty: '{{ $uploadProperty }}',
    })"
    class="w-full space-y-1.5"
    x-cloak
>
    @if($label)
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden File Input for Image Upload --}}
    <input
        type="file"
        x-ref="fileInput"
        @change="handleImageFile($event)"
        accept="image/png,image/jpeg,image/webp,image/gif"
        class="hidden"
    />

    {{-- Editor Container --}}
    <div class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden shadow-sm focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all">
        
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-1 p-2 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-xs">
            
            {{-- Text Styling --}}
            <button
                type="button"
                @click="toggleBold()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('bold') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 font-bold"
                title="Tebal (Ctrl+B)"
            >
                B
            </button>
            <button
                type="button"
                @click="toggleItalic()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('italic') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 italic"
                title="Miring (Ctrl+I)"
            >
                I
            </button>
            <button
                type="button"
                @click="toggleUnderline()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('underline') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 underline"
                title="Garis Bawah (Ctrl+U)"
            >
                U
            </button>
            <button
                type="button"
                @click="toggleStrike()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('strike') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 line-through"
                title="Coret"
            >
                S
            </button>

            <span class="w-px h-4 bg-slate-300 dark:bg-slate-600 mx-1"></span>

            {{-- Script --}}
            <button
                type="button"
                @click="toggleSubscript()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('subscript') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700"
                title="Subskrip (x₂)"
            >
                x₂
            </button>
            <button
                type="button"
                @click="toggleSuperscript()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('superscript') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700"
                title="Superskrip (x²)"
            >
                x²
            </button>

            <span class="w-px h-4 bg-slate-300 dark:bg-slate-600 mx-1"></span>

            {{-- Lists --}}
            <button
                type="button"
                @click="toggleBulletList()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('bulletList') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700"
                title="Daftar Poin"
            >
                • List
            </button>
            <button
                type="button"
                @click="toggleOrderedList()"
                :class="{ 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': isActive('orderedList') }"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700"
                title="Daftar Nomor"
            >
                1. List
            </button>

            <span class="w-px h-4 bg-slate-300 dark:bg-slate-600 mx-1"></span>

            {{-- Table --}}
            <button
                type="button"
                @click="insertTable()"
                class="p-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700"
                title="Sisipkan Tabel 3x3"
            >
                ⊞ Tabel
            </button>

            <span class="w-px h-4 bg-slate-300 dark:bg-slate-600 mx-1"></span>

            {{-- Upload Gambar / Grafik --}}
            <button
                type="button"
                @click="triggerImageUpload()"
                :disabled="uploading"
                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/60"
                title="Upload Gambar atau Grafik (Maks 2MB)"
            >
                <span x-show="!uploading">🖼️ Sisip Gambar/Grafik</span>
                <span x-show="uploading" class="animate-pulse">Mengupload...</span>
            </button>

            {{-- Rumus Matematika (KaTeX) --}}
            <button
                type="button"
                @click="openLatexDialog()"
                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-medium hover:bg-blue-100 dark:hover:bg-blue-900/60"
                title="Sisipkan Rumus Matematika (KaTeX / LaTeX)"
            >
                ∑ Rumus LaTeX
            </button>
        </div>

        {{-- Editable Area --}}
        <div
            x-ref="editorElement"
            style="min-height: {{ $minHeight }};"
            class="max-h-[420px] overflow-y-auto px-3 py-2 leading-relaxed text-slate-800 dark:text-slate-100 {{ $compact ? 'text-xs' : 'text-sm' }}"
        ></div>
    </div>

    {{-- Dialog Modal Input Rumus LaTeX --}}
    <div
        x-show="latexModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        x-cloak
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>∑</span> Sisipkan Rumus Matematika (LaTeX)
            </h3>
            <p class="text-xs text-slate-500">
                Gunakan format formula LaTeX. Contoh: <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}</code> atau <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded">\int_{0}^{\infty} e^{-x^2} dx</code>.
            </p>
            <textarea
                x-model="latexFormula"
                rows="3"
                placeholder="Tulis kode rumus LaTeX..."
                class="w-full text-sm font-mono rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white p-2.5 focus:border-emerald-500 focus:ring-emerald-500"
            ></textarea>

            <div class="flex justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="latexModal = false"
                    class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="insertFormula()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                >
                    Sisipkan ke Editor
                </button>
            </div>
        </div>
    </div>
</div>
