<?php

namespace App\Filament\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait RendersAttachmentPreview
{
    protected function renderAttachmentsHtml(mixed $record, string $collection = 'attachments'): string
    {
        if (!$record || !method_exists($record, 'getMedia')) {
            return '<span class="text-gray-500">No attachments uploaded.</span>';
        }

        $mediaItems = $record->getMedia($collection);

        if ($mediaItems->isEmpty()) {
            return '<span class="text-gray-500">No attachments uploaded.</span>';
        }

        return $mediaItems
            ->map(fn(Media $media): string => $this->renderAttachmentItem($media))
            ->implode('');
    }

    protected function renderAttachmentItem(Media $media): string
    {
        $url = e($media->getUrl());
        $name = e($media->name ?: $media->file_name);
        $mime = strtolower((string)$media->mime_type);
        $isImage = str_starts_with($mime, 'image/');

        if ($isImage) {
            return <<<HTML
                <a href="{$url}" target="_blank" rel="noopener noreferrer" style="display:inline-block;margin:0 .75rem .75rem 0;">
                    <img src="{$url}" alt="{$name}" style="width:8rem;height:8rem;object-fit:cover;border-radius:.5rem;border:1px solid #e5e7eb;" />
                </a>
                HTML;
        }

        return <<<HTML
                <div style="margin:0 0 .5rem 0;">
                    <a href="{$url}" target="_blank" rel="noopener noreferrer" style="color:#2563eb;text-decoration:underline;">
                        {$name}
                    </a>
                </div>
                HTML;
    }
}
