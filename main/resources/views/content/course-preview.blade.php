@extends('layouts.learning')
@section('title', 'Course adventure preview — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ auth()->user()->can('update', $course) ? route('courses.edit', $course) : route('course-reviews.show', $course) }}">← Course outline</a><h1>Try the saved adventure.</h1><p>Preview wins stay local.</p>@include('content.lesson', ['module' => null, 'preview' => true])</section>
@endsection
