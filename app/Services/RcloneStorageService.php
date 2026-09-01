<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RcloneStorageService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('storage.google_drive_enabled', false);
    }

    public function destination(string $registrationNumber, string $filename): string
    {
        $root = trim((string) $this->settings->get('rclone.root_folder', 'PMB-PKU'), '/ ');
        $registration = preg_replace('/[^A-Za-z0-9_-]/', '_', $registrationNumber);
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename));

        return implode('/', array_filter([$root, $registration, $safeFilename]));
    }

    public function uploadLocal(string $localPath, string $remotePath): void
    {
        $absolutePath = Storage::disk('local')->path($localPath);
        $this->run(['copyto', $absolutePath, $this->remotePath($remotePath)]);

        $remoteSize = $this->size($remotePath);
        $localSize = filesize($absolutePath);
        if ($remoteSize !== $localSize) {
            throw new RuntimeException("Ukuran file Drive tidak sesuai ({$remoteSize} != {$localSize}).");
        }
    }

    public function downloadToTemporaryFile(string $remotePath): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'pmb-drive-');
        if ($temporary === false) {
            throw new RuntimeException('File sementara tidak dapat dibuat.');
        }

        try {
            $this->run(['copyto', $this->remotePath($remotePath), $temporary]);
        } catch (\Throwable $exception) {
            @unlink($temporary);
            throw $exception;
        }

        return $temporary;
    }

    public function delete(string $remotePath): void
    {
        $this->run(['deletefile', $this->remotePath($remotePath)]);
    }

    public function size(string $remotePath): int
    {
        $result = $this->run(['size', $this->remotePath($remotePath), '--json']);
        $json = json_decode($result->output(), true);

        return (int) ($json['bytes'] ?? -1);
    }

    public function testConnection(): void
    {
        $this->run(['about', $this->remote().':', '--json']);
    }

    private function run(array $arguments): ProcessResult
    {
        $command = [$this->binary()];
        $config = trim((string) $this->settings->get('rclone.config_path', ''));
        if ($config !== '') {
            array_push($command, '--config', $config);
        }
        array_push($command, ...$arguments);

        $result = Process::timeout(300)->run($command);
        if ($result->failed()) {
            throw new ProcessFailedException($result);
        }

        return $result;
    }

    private function binary(): string
    {
        return trim((string) $this->settings->get('rclone.binary_path', '/usr/local/bin/rclone')) ?: 'rclone';
    }

    private function remote(): string
    {
        $remote = trim((string) $this->settings->get('rclone.remote', 'gdrive'));
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $remote)) {
            throw new RuntimeException('Nama remote rclone tidak valid.');
        }

        return $remote;
    }

    private function remotePath(string $path): string
    {
        return $this->remote().':'.ltrim($path, '/');
    }
}
