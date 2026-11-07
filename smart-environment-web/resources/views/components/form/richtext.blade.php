@props([
    'name',
    'label' => null,
    'required' => false,
    'help' => null,
    'editorType' => 'full', // 'full' or 'summary'
    'placeholder' => 'Tulis di sini...'
])

@php
    $id = $attributes->get('id', $name);
    $value = old($name, $slot ?? '');
    $hasError = $errors->has($name);
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div 
        data-quill-editor="{{ $id }}"
        data-editor-type="{{ $editorType }}"
        class="quill-editor-wrapper @error($name) border-red-500 @enderror"
    >
        <textarea 
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->except(['id', 'class']) }}
        >{{ $value }}</textarea>
    </div>

    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<style>
    .quill-editor-wrapper {
        @apply rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800;
    }

    .quill-editor-wrapper .ql-toolbar {
        @apply border-0 border-b border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 rounded-t-lg;
    }

    .quill-editor-wrapper .ql-container {
        @apply border-0 rounded-b-lg;
    }

    .quill-editor-wrapper .ql-editor {
        @apply min-h-[150px] text-gray-900 dark:text-gray-100;
        font-size: 0.875rem;
    }

    .quill-editor-wrapper .ql-editor.ql-blank::before {
        @apply text-gray-400 dark:text-gray-500 italic;
    }

    /* Toolbar button styling */
    .quill-editor-wrapper .ql-toolbar button {
        @apply hover:bg-gray-200 dark:hover:bg-gray-700 rounded;
    }

    .quill-editor-wrapper .ql-toolbar button.ql-active {
        @apply bg-blue-100 dark:bg-blue-900/30;
    }

    .quill-editor-wrapper .ql-toolbar .ql-stroke {
        @apply stroke-gray-700 dark:stroke-gray-300;
    }

    .quill-editor-wrapper .ql-toolbar .ql-fill {
        @apply fill-gray-700 dark:fill-gray-300;
    }

    .quill-editor-wrapper .ql-toolbar button:hover .ql-stroke {
        @apply stroke-gray-900 dark:stroke-white;
    }

    .quill-editor-wrapper .ql-toolbar button:hover .ql-fill {
        @apply fill-gray-900 dark:fill-white;
    }

    .quill-editor-wrapper .ql-toolbar button.ql-active .ql-stroke {
        @apply stroke-blue-600 dark:stroke-blue-400;
    }

    .quill-editor-wrapper .ql-toolbar button.ql-active .ql-fill {
        @apply fill-blue-600 dark:fill-blue-400;
    }

    /* Picker styling */
    .quill-editor-wrapper .ql-toolbar .ql-picker-label {
        @apply text-gray-700 dark:text-gray-300;
    }

    .quill-editor-wrapper .ql-toolbar .ql-picker-options {
        @apply bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700;
    }

    .quill-editor-wrapper .ql-toolbar .ql-picker-item:hover {
        @apply bg-gray-100 dark:bg-gray-700;
    }

    /* Error state */
    .quill-editor-wrapper.border-red-500 {
        @apply border-red-500;
    }
</style>
