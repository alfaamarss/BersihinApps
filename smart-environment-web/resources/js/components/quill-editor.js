import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Initialize Quill editors
document.addEventListener('DOMContentLoaded', function() {
    const editors = document.querySelectorAll('[data-quill-editor]');
    
    editors.forEach(editorElement => {
        const textareaId = editorElement.dataset.quillEditor;
        const textarea = document.getElementById(textareaId);
        
        if (!textarea) return;

        // Create editor container
        const editorContainer = document.createElement('div');
        editorContainer.className = 'quill-editor-container';
        editorElement.appendChild(editorContainer);

        // Hide original textarea
        textarea.style.display = 'none';

        // Configure toolbar based on editor type
        const editorType = editorElement.dataset.editorType || 'full';
        let toolbarOptions;

        if (editorType === 'summary') {
            // Simpler toolbar for summary
            toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                ['link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ];
        } else {
            // Full toolbar for content
            toolbarOptions = [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                ['clean']
            ];
        }

        // Initialize Quill
        const quill = new Quill(editorContainer, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            },
            placeholder: textarea.placeholder || 'Tulis di sini...'
        });

        // Set initial content
        if (textarea.value) {
            quill.root.innerHTML = textarea.value;
        }

        // Sync Quill content to textarea
        quill.on('text-change', function() {
            textarea.value = quill.root.innerHTML;
        });

        // Handle form submission
        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                textarea.value = quill.root.innerHTML;
            });
        }
    });
});
