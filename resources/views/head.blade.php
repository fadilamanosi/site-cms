@php
    $seo = is_array($page['props']['seo'] ?? null) ? $page['props']['seo'] : [];
    $site = is_array($page['props']['siteCms']['site'] ?? null) ? $page['props']['siteCms']['site'] : [];
    $title = $seo['title'] ?? $site['title'] ?? config('app.name');
    $description = $seo['description'] ?? $site['description'] ?? '';
    $image = $seo['image'] ?? $site['image'] ?? '';
    $favicon = $site['favicon'] ?? '/favicon.svg';
    $absolute = static function (string $url): string {
        if ($url === '') {
            return $url;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    };
    $imageAbs = is_string($image) ? $absolute($image) : '';
    $canonical = url()->current();
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title }}</title>
@if ($description !== '')
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonical }}">
<link rel="icon" href="{{ $favicon }}">
<meta property="og:title" content="{{ $title }}">
@if ($description !== '')
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonical }}">
@if ($imageAbs !== '')
    <meta property="og:image" content="{{ $imageAbs }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
@if ($description !== '')
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if ($imageAbs !== '')
    <meta name="twitter:image" content="{{ $imageAbs }}">
@endif
