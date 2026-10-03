import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Underline from '@tiptap/extension-underline';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table';
import katex from 'katex';

window.setupRichEditor = function(config) {
    return {
        editor: null,
        content: config.content || '',
        uploading: false,
        latexModal: false,
        latexFormula: '',

        init() {
            const self = this;
            const element = this.$refs.editorElement;

            this.editor = new Editor({
                element: element,
                extensions: [
                    StarterKit.configure({
                        heading: {
                            levels: [1, 2, 3, 4],
                        },
                    }),
                    Underline,
                    Subscript,
                    Superscript,
                    Image.configure({
                        inline: true,
                        allowBase64: true,
                        HTMLAttributes: {
                            class: 'max-w-full rounded-lg shadow-sm my-2 border border-slate-200 dark:border-slate-700',
                        },
                    }),
                    Table.configure({
                        resizable: true,
                        HTMLAttributes: {
                            class: 'border-collapse table-auto w-full border border-slate-300 dark:border-slate-700 my-3 text-sm',
                        },
                    }),
                    TableRow,
                    TableHeader.configure({
                        HTMLAttributes: {
                            class: 'border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-2 font-semibold',
                        },
                    }),
                    TableCell.configure({
                        HTMLAttributes: {
                            class: 'border border-slate-300 dark:border-slate-700 p-2',
                        },
                    }),
                ],
                content: self.content,
                editorProps: {
                    attributes: {
                        class: 'prose-editor min-h-[140px] p-3 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-0',
                    },
                },
                onUpdate: ({ editor }) => {
                    const html = editor.getHTML();
                    self.content = html;
                    if (config.wireModel) {
                        self.$wire.set(config.wireModel, html);
                    }
                },
            });

            this.$watch('content', (val) => {
                if (self.editor && val !== self.editor.getHTML()) {
                    self.editor.commands.setContent(val || '', false);
                }
            });
        },

        toggleBold() { this.editor?.chain().focus().toggleBold().run(); },
        toggleItalic() { this.editor?.chain().focus().toggleItalic().run(); },
        toggleUnderline() { this.editor?.chain().focus().toggleUnderline().run(); },
        toggleStrike() { this.editor?.chain().focus().toggleStrike().run(); },
        toggleSubscript() { this.editor?.chain().focus().toggleSubscript().run(); },
        toggleSuperscript() { this.editor?.chain().focus().toggleSuperscript().run(); },
        toggleBulletList() { this.editor?.chain().focus().toggleBulletList().run(); },
        toggleOrderedList() { this.editor?.chain().focus().toggleOrderedList().run(); },
        toggleBlockquote() { this.editor?.chain().focus().toggleBlockquote().run(); },
        insertTable() {
            this.editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
        },
        deleteTable() {
            this.editor?.chain().focus().deleteTable().run();
        },

        isActive(type, opts = {}) {
            return this.editor ? this.editor.isActive(type, opts) : false;
        },

        triggerImageUpload() {
            this.$refs.fileInput.click();
        },

        handleImageFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Upload through Livewire upload trait
            this.uploading = true;
            this.$wire.upload(
                config.uploadProperty || 'tempEditorImage',
                file,
                (uploadedName) => {
                    this.uploading = false;
                    // Ask component to process uploaded image and return URL
                    this.$wire.processEditorImage().then((url) => {
                        if (url) {
                            this.editor.chain().focus().setImage({ src: url, alt: file.name }).run();
                        }
                    });
                },
                () => {
                    this.uploading = false;
                    alert('Gagal mengupload gambar.');
                }
            );
            event.target.value = '';
        },

        openLatexDialog() {
            this.latexFormula = '';
            this.latexModal = true;
        },

        insertFormula() {
            if (!this.latexFormula.trim()) {
                this.latexModal = false;
                return;
            }

            try {
                const renderedHtml = katex.renderToString(this.latexFormula.trim(), {
                    throwOnError: false,
                    displayMode: false,
                });

                // Wrap in span with data-latex attribute
                const formulaHtml = `<span class="inline-math inline-block px-1 align-baseline" data-latex="${encodeURIComponent(this.latexFormula.trim())}">${renderedHtml}</span>&nbsp;`;
                this.editor.chain().focus().insertContent(formulaHtml).run();
                this.latexModal = false;
                this.latexFormula = '';
            } catch (e) {
                alert('Rumus tidak valid: ' + e.message);
            }
        },

        destroy() {
            if (this.editor) {
                this.editor.destroy();
            }
        }
    };
};

window.renderMathInElement = function(el) {
    if (!el) return;
    const mathElements = el.querySelectorAll('[data-latex]');
    mathElements.forEach((mathEl) => {
        const formula = decodeURIComponent(mathEl.getAttribute('data-latex') || '');
        if (formula) {
            try {
                mathEl.innerHTML = katex.renderToString(formula, {
                    throwOnError: false,
                    displayMode: mathEl.classList.contains('block-math'),
                });
            } catch (err) {
                console.error('KaTeX error:', err);
            }
        }
    });
};

document.addEventListener('livewire:navigated', () => {
    document.querySelectorAll('.exam-content-render').forEach((el) => {
        window.renderMathInElement(el);
    });
});
