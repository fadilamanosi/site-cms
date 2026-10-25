<?php

namespace SiteCms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SiteCms\Models\SiteResource;
use SiteCms\Repositories\SiteResourceRepository;
use SiteCms\Support\UploadResolver;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceService
{
    public function __construct(
        private readonly SiteResourceRepository $siteResourceRepository,
        private readonly UploadResolver $uploadResolver,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SiteResource>
     */
    public function list(int $page = 1, int $perPage = 24, ?string $type = null): LengthAwarePaginator
    {
        $paginator = $this->siteResourceRepository->paginate($page, $perPage, $type);
        $paginator->getCollection()->transform(function (SiteResource $row): SiteResource {
            $row->url = $this->displayUrl($row);

            return $row;
        });

        return $paginator;
    }

    public function store(UploadedFile $file, ?int $userId): SiteResource
    {
        $disk = $this->uploadResolver->disk();
        $directory = $this->uploadResolver->directory();
        $options = ['disk' => $disk];
        if ($this->uploadResolver->diskCanPublish($disk)) {
            $options['visibility'] = 'public';
        }
        $path = $file->store($directory, $options);

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Could not store the uploaded file.');
        }

        $payload = [
            'name' => $file->getClientOriginalName(),
            'url' => '',
            'type' => $file->getClientOriginalExtension() ?: null,
            'size' => (string) $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'user_id' => $userId,
        ];
        if (Schema::hasColumn('site_cms_resources', 'disk')) {
            $payload['disk'] = $disk;
            $payload['path'] = $path;
        }

        $resource = $this->siteResourceRepository->create($payload);

        return $this->siteResourceRepository->update($resource, [
            'url' => $this->displayUrl($resource, $disk, $path),
        ]);
    }

    public function stream(int $id): StreamedResponse
    {
        $resource = $this->siteResourceRepository->find($id);
        $located = $resource ? $this->locate($resource) : null;
        if (! $resource || $located === null) {
            abort(404);
        }

        [$disk, $path] = $located;
        if (
            Schema::hasColumn('site_cms_resources', 'disk')
            && ($resource->disk !== $disk || $resource->path !== $path)
        ) {
            $this->siteResourceRepository->update($resource, ['disk' => $disk, 'path' => $path]);
        }

        return Storage::disk($disk)->response(
            $path,
            $resource->name,
            [
                'Content-Type' => $resource->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'public, max-age=31536000',
            ],
        );
    }

    public function delete(int $id): bool
    {
        $resource = $this->siteResourceRepository->find($id);
        if (! $resource) {
            return false;
        }

        $located = $this->locate($resource);
        if ($located !== null) {
            Storage::disk($located[0])->delete($located[1]);
        }

        $this->siteResourceRepository->delete($resource);

        return true;
    }

    private function displayUrl(SiteResource $row, ?string $disk = null, ?string $path = null): string
    {
        $disk ??= is_string($row->disk) && $row->disk !== '' ? $row->disk : $this->uploadResolver->disk();
        $path ??= is_string($row->path) ? $row->path : null;

        if (is_string($path) && $path !== '' && $this->uploadResolver->diskCanPublish($disk)) {
            try {
                $url = Storage::disk($disk)->url($path);
                if (is_string($url) && $url !== '') {
                    return $this->sameOrigin($url);
                }
            } catch (\Throwable) {
                // Fall through to the package media route.
            }
        }

        if ($row->id) {
            return '/site-cms/media/'.$row->id.'/'.$this->publicName($row->name);
        }

        return is_string($row->url) && $row->url !== '' ? $this->sameOrigin($row->url) : '';
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function locate(SiteResource $row): ?array
    {
        $paths = [];
        if (is_string($row->path) && $row->path !== '') {
            $paths[] = $row->path;
        }
        $fromUrl = $this->pathFromUrl((string) $row->url);
        if ($fromUrl !== null) {
            $paths[] = $fromUrl;
        }

        $disks = [];
        if (is_string($row->disk) && $row->disk !== '') {
            $disks[] = $row->disk;
        }
        $disks[] = $this->uploadResolver->disk();
        $disks[] = 'public';
        $disks[] = 'local';

        foreach (array_unique($disks) as $disk) {
            foreach (array_unique($paths) as $path) {
                try {
                    if (Storage::disk($disk)->exists($path)) {
                        return [$disk, $path];
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    private function pathFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $url;
        }
        if (preg_match('#/site-cms/media/\d+(?:/.+)?$#', $path) === 1) {
            return null;
        }
        if (preg_match('#/storage/(.+)$#', $path, $matches) === 1) {
            return ltrim($matches[1], '/');
        }

        return null;
    }

    private function publicName(?string $name): string
    {
        $base = basename(str_replace('\\', '/', (string) $name));
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? 'file';
        $safe = trim($safe, '.-');

        return $safe !== '' ? $safe : 'file';
    }

    private function sameOrigin(string $url): string
    {
        $app = rtrim((string) config('app.url'), '/');
        if ($app !== '' && str_starts_with($url, $app)) {
            $path = substr($url, strlen($app)) ?: '/';

            return str_starts_with($path, '/') ? $path : '/'.$path;
        }

        return $url;
    }
}
