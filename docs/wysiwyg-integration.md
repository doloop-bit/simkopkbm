# WYSIWYG Editor Integration Guide

## Current Implementation

The news article editor currently uses a standard textarea for content input. This provides basic functionality but lacks rich text formatting capabilities.

## Recommended WYSIWYG Editors

### 1. TinyMCE (Recommended)

**Pros:**
- Free and open-source
- Excellent documentation
- Works well with Livewire
- Supports image uploads
- Highly customizable

**Installation:**

```bash
npm install tinymce
```

**Integration with Livewire:**

Add to `resources/js/app.js`:

```javascript
import tinymce from 'tinymce';

// Initialize TinyMCE when Livewire loads
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el, component }) => {
        // Reinitialize TinyMCE after Livewire updates
        if (el.querySelector('textarea[data-tinymce]')) {
            initTinyMCE();
        }
    });
});

function initTinyMCE() {
    tinymce.init({
        selector: 'textarea[data-tinymce]',
        plugins: 'lists link image table code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
        height: 500,
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
                // Trigger Livewire update
                const textarea = editor.getElement();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
    });
}
```

**Update the form component:**

In `resources/views/livewire/admin/news/form.blade.php`, change the content textarea:

```blade
<textarea 
    wire:model="content" 
    data-tinymce
    id="content-editor"
    class="hidden"
>{{ $content }}</textarea>

@script
<script>
    // Initialize TinyMCE on component mount
    tinymce.init({
        selector: '#content-editor',
        plugins: 'lists link image table code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
        height: 500,
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
                @this.set('content', editor.getContent());
            });
        }
    });
</script>
@endscript
```

### 2. CKEditor

**Pros:**
- Feature-rich
- Good documentation
- Modern interface

**Installation:**

```bash
npm install @ckeditor/ckeditor5-build-classic
```

**Integration:**

```javascript
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

ClassicEditor
    .create(document.querySelector('#content-editor'))
    .then(editor => {
        editor.model.document.on('change:data', () => {
            @this.set('content', editor.getData());
        });
    })
    .catch(error => {
        console.error(error);
    });
```

### 3. Quill

**Pros:**
- Lightweight
- Modern and clean interface
- Easy to integrate

**Installation:**

```bash
npm install quill
```

## Image Upload Handling

When integrating a WYSIWYG editor with image upload capabilities, you'll need to:

1. Create an endpoint for image uploads
2. Configure the editor to use this endpoint
3. Store uploaded images in the appropriate directory

**Example route:**

```php
Route::post('/admin/news/upload-image', function (Request $request) {
    $request->validate([
        'image' => 'required|image|max:5120',
    ]);

    $path = $request->file('image')->store('news/content', 'public');

    return response()->json([
        'location' => Storage::url($path)
    ]);
})->middleware(['auth', 'role:admin']);
```

## Security Considerations

1. **Sanitize HTML Output**: Use a library like HTMLPurifier to sanitize user-generated HTML
2. **Validate File Uploads**: Always validate file types and sizes on the server
3. **XSS Prevention**: Ensure proper escaping when displaying content

## Testing

After integrating a WYSIWYG editor, update the tests to:

1. Test that content is properly saved
2. Test that HTML formatting is preserved
3. Test image uploads through the editor
4. Test XSS prevention

## Resources

- [TinyMCE Documentation](https://www.tiny.cloud/docs/)
- [CKEditor Documentation](https://ckeditor.com/docs/)
- [Quill Documentation](https://quilljs.com/docs/)
- [Livewire File Uploads](https://livewire.laravel.com/docs/uploads)
