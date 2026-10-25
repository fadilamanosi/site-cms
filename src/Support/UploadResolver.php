<?php

namespace SiteCms\Support;

class UploadResolver
{
    public function disk(): string
    {
        $configured = config('site-cms.disk');
        if (is_string($configured) && $configured !== '' && $this->diskExists($configured)) {
            return $configured;
        }

        $default = (string) config('filesystems.default', 'public');
        if ($default !== '' && $this->diskExists($default)) {
            return $default;
        }

        if ($this->diskExists('public')) {
            return 'public';
        }

        return $default !== '' ? $default : 'local';
    }

    public function directory(): string
    {
        $directory = config('site-cms.upload.directory', 'site-cms');

        return is_string($directory) && $directory !== '' ? trim($directory, '/') : 'site-cms';
    }

    /**
     * @return list<string>
     */
    public function imageMimes(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
    }

    /**
     * @return list<string>
     */
    public function fileMimes(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rtf'];
    }

    /**
     * @return list<string>
     */
    public function mimes(): array
    {
        $configured = config('site-cms.upload.mimes');
        $extra = is_array($configured)
            ? array_values(array_filter($configured, fn ($mime) => is_string($mime) && $mime !== ''))
            : [];

        return array_values(array_unique([...$this->imageMimes(), ...$this->fileMimes(), ...$extra]));
    }

    public function maxKilobytes(): int
    {
        $configured = config('site-cms.upload.max_kilobytes');
        if (is_numeric($configured) && (int) $configured > 0) {
            return (int) $configured;
        }

        $upload = $this->iniKilobytes('upload_max_filesize');
        $post = $this->iniKilobytes('post_max_size');
        $limit = min($upload, $post);

        return $limit > 0 ? $limit : 12288;
    }

    public function accept(string $mode = 'file'): string
    {
        $mimes = $mode === 'image' ? $this->imageMimes() : $this->mimes();
        $extensions = collect($mimes)->map(fn (string $mime) => '.'.$mime);

        return $mode === 'image'
            ? $extensions->prepend('image/*')->implode(',')
            : $extensions->implode(',');
    }

    private function diskExists(string $disk): bool
    {
        return is_array(config('filesystems.disks.'.$disk));
    }

    public function diskCanPublish(string $disk): bool
    {
        if (! $this->diskExists($disk)) {
            return false;
        }

        $config = config('filesystems.disks.'.$disk);
        if (! is_array($config)) {
            return false;
        }

        $driver = (string) ($config['driver'] ?? '');

        return in_array($driver, ['s3', 'gcs', 'ftp', 'sftp'], true);
    }

    private function iniKilobytes(string $key): int
    {
        $raw = ini_get($key);
        if (! is_string($raw) || $raw === '' || $raw === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($raw, -1));
        $size = (float) $raw;

        return (int) match ($unit) {
            'g' => $size * 1024 * 1024,
            'm' => $size * 1024,
            'k' => $size,
            default => ((int) $raw) / 1024,
        };
    }
}
