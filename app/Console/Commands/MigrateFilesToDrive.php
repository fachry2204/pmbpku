<?php

namespace App\Console\Commands;

use App\Jobs\SyncApplicantDocumentToDrive;
use App\Models\ApplicantDocument;
use App\Services\RcloneStorageService;
use Illuminate\Console\Command;
use Throwable;

class MigrateFilesToDrive extends Command
{
    protected $signature = 'files:migrate-to-drive {--retry-failed : Tetap lanjut jika satu file gagal}';

    protected $description = 'Salin pas foto dan pindahkan dokumen lokal pendaftar ke Google Drive melalui rclone';

    public function handle(RcloneStorageService $drive): int
    {
        if (! $drive->enabled()) {
            $this->error('Aktifkan Google Drive terlebih dahulu di Pengaturan.');

            return self::FAILURE;
        }

        $failed = 0;
        $documents = ApplicantDocument::where('disk', 'local')->count();
        $bar = $this->output->createProgressBar($documents);

        ApplicantDocument::where('disk', 'local')->orderBy('id')->each(function (ApplicantDocument $document) use ($drive, &$failed, $bar): void {
            try {
                (new SyncApplicantDocumentToDrive($document->id))->handle($drive);
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->newLine();
                $this->error("{$document->id}: {$exception->getMessage()}");
                if (! $this->option('retry-failed')) {
                    throw $exception;
                }
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Migrasi selesai. Gagal: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
