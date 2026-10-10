<?php

namespace App\Services\Content;

use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use App\Models\ModuleRevision;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ModuleMedia
{
    public function retain(?ModuleRevision $previous, array $indices): array
    {
        $assets = $previous?->attachments ?? [];
        foreach ($indices as $index) {
            if (! isset($assets[$index])) {
                throw ValidationException::withMessages(['retain_attachments' => 'This attachment changed. Reload before saving.']);
            }
        }

        return array_values(array_intersect_key($assets, array_flip($indices)));
    }

    public function upload(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['pdf', 'docx', 'pptx'], true) || $file->getSize() > config('content-media.max_kilobytes') * 1024) {
            $this->invalid();
        }
        $pages = [];
        if ($extension === 'pdf') {
            $bytes = file_get_contents($file->getRealPath());
            if ($file->getMimeType() !== 'application/pdf' || ! str_starts_with($bytes, '%PDF-')
                || ! str_contains(substr($bytes, -2048), '%%EOF') || preg_match('~/\s*(JavaScript|JS|Launch|EmbeddedFile|OpenAction|Encrypt)\b~', $bytes)) {
                $this->invalid();
            }
        } else {
            $pages = $this->officePreview($file, $extension);
        }
        $disk = config('content-media.disk');
        if (! in_array($disk, ['local', 's3'], true) || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
            throw new \LogicException('Module media requires configured private local or S3 storage.');
        }
        $path = $file->storeAs('module-media', Str::uuid().'.'.$extension, ['disk' => $disk, 'visibility' => 'private']);
        if (! is_string($path)) {
            throw new \RuntimeException('The attachment could not be stored. Try again.');
        }
        $name = preg_replace('/[\x00-\x1f\x7f\/\\\\]/u', '', $file->getClientOriginalName());

        return ['disk' => $disk, 'path' => $path, 'name' => mb_substr($name, 0, 150), 'extension' => $extension,
            'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'pages' => $pages];
    }

    private function officePreview(UploadedFile $file, string $extension): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            $this->invalid();
        }
        try {
            if ($zip->numFiles > 1000 || $zip->locateName('[Content_Types].xml') === false) {
                $this->invalid();
            }
            $expanded = 0;
            $xmlEntries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $expanded += $entry['size'];
                if ($expanded > 50 * 1024 * 1024 || $entry['size'] > 10 * 1024 * 1024
                    || preg_match('~(?:^|/)(?:vbaProject|activeX|embeddings)|\.\./~i', $entry['name'])) {
                    $this->invalid();
                }
                if ($extension === 'docx' ? $entry['name'] === 'word/document.xml' : preg_match('~^ppt/slides/slide[0-9]+\.xml$~D', $entry['name'])) {
                    $xmlEntries[] = $entry['name'];
                }
            }
            if ($xmlEntries === [] || count($xmlEntries) > 100) {
                $this->invalid();
            }
            natsort($xmlEntries);
            $pages = [];
            $characters = 0;
            foreach ($xmlEntries as $entry) {
                $xml = $zip->getFromName($entry);
                if (! is_string($xml) || strlen($xml) > 5 * 1024 * 1024 || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
                    $this->invalid();
                }
                $document = new DOMDocument;
                $previous = libxml_use_internal_errors(true);
                try {
                    if (! $document->loadXML($xml, LIBXML_NONET)) {
                        $this->invalid();
                    }
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                }
                $xpath = new DOMXPath($document);
                $xpath->registerNamespace('text', $extension === 'docx' ? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main' : 'http://schemas.openxmlformats.org/drawingml/2006/main');
                $paragraphs = [];
                foreach ($xpath->query('//text:p') as $paragraph) {
                    $line = '';
                    foreach ($xpath->query('.//text:t', $paragraph) as $node) {
                        $line .= $node->textContent;
                    }
                    if (trim($line) !== '') {
                        $paragraphs[] = $line;
                        $characters += mb_strlen($line);
                    }
                    if ($characters > 100000) {
                        $this->invalid();
                    }
                }
                $pages[] = $paragraphs;
            }

            return $pages;
        } finally {
            $zip->close();
        }
    }

    public function queueRemoval(int $ownerId, array $asset): void
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new \LogicException('Media erasure requires the application database queue.');
        }
        $erasure = AccountFileErasure::firstOrCreate(['user_id' => $ownerId, 'disk' => $asset['disk'], 'path_digest' => hash('sha256', $asset['path'])],
            ['id' => (string) Str::uuid(), 'path' => $asset['path'], 'last_queued_at' => now()]);
        if ($erasure->wasRecentlyCreated) {
            $id = Queue::connection('database')->push((new EraseAccountFile($erasure->id))->beforeCommit());
            $erasure->update(['queued_job_id' => $id]);
        }
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['attachments' => 'Use a standard PDF, DOCX or PPTX up to 20 MB. Encrypted, executable, macro-enabled or oversized document packages are not supported.']);
    }
}
