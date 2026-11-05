<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Converts every page of an uploaded PDF into a JPG File record,
 * then removes the original PDF.
 */
class ConvertPdfToImages implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public File $file) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');
        $directory = dirname($this->file->path);
        $basename = pathinfo($this->file->path, PATHINFO_FILENAME);
        $workDir = storage_path('app/private/pdf-'.$this->file->id.'-'.Str::random(8));

        mkdir($workDir, 0755, true);

        try {
            Process::timeout(60)
                ->run([
                    'pdftoppm', '-jpeg',
                    // Render straight at the max width; -1 keeps the aspect ratio.
                    '-scale-to-x', (string) File::MAX_IMAGE_WIDTH, '-scale-to-y', '-1',
                    $disk->path($this->file->path), $workDir.'/page',
                ])
                ->throw();

            // pdftoppm zero-pads page numbers based on page count, so natural sort keeps page order.
            $pages = glob($workDir.'/page-*.jpg') ?: [];
            natsort($pages);

            foreach (array_values($pages) as $index => $page) {
                $hash = md5_file($page);

                // Retries and re-uploads of the same PDF produce identical pages.
                if (File::where('hash', $hash)->exists()) {
                    continue;
                }

                $path = $disk->putFileAs($directory, new HttpFile($page), $basename.'-page-'.($index + 1).'.jpg');

                File::create([
                    'filename' => basename($path),
                    'path' => $path,
                    'mime_type' => 'image/jpeg',
                    'size' => $disk->size($path),
                    'hash' => $hash,
                ]);
            }

            $disk->delete($this->file->path);
            $this->file->delete();
        } finally {
            array_map('unlink', glob($workDir.'/*') ?: []);
            rmdir($workDir);
        }
    }
}
