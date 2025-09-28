@extends('layouts.instructor')

@push('title', get_phrase('My Subjects'))

@push('meta')
@endpush

@push('css')
@endpush



@section('content')

<div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-12px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('My Subjects') }}
                </h4>

                <a onclick="ajaxModal('{{ route('modal', ['instructor.tutor_booking.subject_add']) }}', '{{ get_phrase('Add new subject') }}')" href="#" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px">
                    <span class="fi-rr-plus"></span>
                    <span>{{ get_phrase('Add subject') }}</span>
                </a>

            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-12">
            <div class="ol-card p-4">
                <div class="ol-card-body">
                    <div class="col-md-12 pb-3">
                        <ul class="ol-my-accordion">
                            @php
                                $index = 0;
                            @endphp
                            @foreach ($categories as $category)
                            @php
                                $subjects = App\Models\TutorCanTeach::where('category_id', $category->category_id)->get();
                            @endphp
                            <li class="single-accor-item">
                                <div class="accordion-btn-wrap">
                                    <div class="accordion-btn-title d-flex align-items-center">
                                        <img src="assets/images/icons/firstline-gray-16.svg" alt="">
                                        <h4 class="title">{{ ++$index }}. {{ $category->category_to_tutorCategory->name }}</h4>
                                    </div>
                                    <div class="accordion-button-buttons">

                                        <a href="#" data-bs-toggle="tooltip" title="{{ get_phrase('Delete category') }}"
                                            onclick="confirmModal('{{ route('instructor.my_subject_category_delete', $category->category_id) }}'); event.stopPropagation();"
                                            class="delete">
                                            <span class="fi-rr-trash"></span>
                                        </a>
                                    </div>
                                </div>
                                <div class="accoritem-body d-hidden">
                                    <ul class="list-group-3">
                                        @if ($subjects->count() > 0)
                                            @foreach ($subjects as $subject)
                                                <li>
                                                    <h4 class="title">{{ $subject->category_to_tutorSubjects->name }}</h4>

                                                    <div class="buttons">

                                                        <a href="#" data-bs-toggle="tooltip"
                                                            title="{{ get_phrase('Edit subject') }}"
                                                            onclick="ajaxModal('{{ route('modal', ['instructor.tutor_booking.subject_edit', 'id' => $subject->id]) }}', '{{ get_phrase('Edit subject') }}')"
                                                            class="edit-delete">
                                                            <span class="fi-rr-pencil"></span>
                                                        </a>


                                                        <a href="#" data-bs-toggle="tooltip"
                                                            title="{{ get_phrase('Delete subject') }}"
                                                            onclick="confirmModal('{{ route('instructor.my_subject_delete', $subject->id) }}')"
                                                            class="edit-delete">
                                                            <span class="fi-rr-trash"></span>
                                                        </a>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @else
                                            <li>
                                                <h4 class="title">{{ get_phrase('No subjects are available.') }}</h4>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')

    <script type="text/javascript">
        "use strict";

        $(document).ready(function() {

            <?php if(isset($_GET['tab'])): ?>
            $('a[href="#<?php echo $_GET['tab']; ?>"]').trigger('click');
            <?php endif; ?>
        });

    </script>
@endpush