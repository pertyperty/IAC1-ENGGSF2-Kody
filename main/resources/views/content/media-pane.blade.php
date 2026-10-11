<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>{{ $attachment['name'] }} — Resource preview</title>@include('layouts.theme-head')@fonts @vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="learning-page resource-document"><main><h1 class="sr-only">{{ $attachment['name'] }}</h1>@include('content.media-body')</main></body></html>
