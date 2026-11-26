@extends('layouts.learning')
@section('title', 'Course module preview — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ auth()->user()->can('update', $course) ? route('courses.edit', $course) : route('course-reviews.show', $course) }}"><x-action-icon name="left"/> Course outline</a><h1>Preview saved module</h1><p>Preview wins stay local and save no progress or rewards.</p>@include('content.lesson', ['module' => null, 'preview' => true])</section>
@endsection
