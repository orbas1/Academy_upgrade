@php
    $sections = App\Models\Section::where('course_id', $course_details->id)
        ->orderBy('sort')
        ->get();

    $completed_lesson = json_decode(
        App\Models\Watch_history::where('course_id', $course_details->id)
            ->where('student_id', Auth()->user()->id)
            ->value('completed_lesson'),
        true,
    ) ?? [];
    $active_section = App\Models\Lesson::where('id', $lesson_details->id ?? '')->value('section_id');

    $lesson_history = App\Models\Watch_history::where('course_id', $course_details->id)
        ->where('student_id', auth()->user()->id)
        ->firstOrNew();
    $completed_lesson_arr = json_decode($lesson_history->completed_lesson, true);
    $completed_lesson_arr = is_array($completed_lesson_arr) ? $completed_lesson_arr : array();
    $complated_lesson = is_array($completed_lesson_arr) ? count($completed_lesson_arr) : 0;
    $course_progress_out_of_100 = progress_bar($course_details->id);

    $user_id = Auth()->user()->id;
    $is_course_instructor = is_course_instructor($course_details->id, $user_id);

    $is_locked = 0;
    $locked_lesson_ids = array();
@endphp

<style>
    .checkbox-icon {
        color: #754ffe;
    }
</style>

<div class="course-content-playlist">
    <div class="row border-bottom pb-3">
        <div class="col-md-12">
            <h1 class="heading mb-2">{{ get_phrase('Course curriculum') }}</h1>
            <p class="info text-14px text-center mb-1">{{ $course_progress_out_of_100 }}% {{ get_phrase('Completed') }}
                ({{ $complated_lesson }}/{{ lesson_count($course_details->id) }})
            </p>
        </div>
    </div>

    <div class="course-playlist-accordion">
        <div class="accordion" id="coursePlay">
            @foreach ($sections as $section)
                @php
                    $lessons = App\Models\Lesson::where('section_id', $section->id)
                        ->orderBy('sort')
                        ->get();
                @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button @if ($active_section != $section->id) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $section->id }}" aria-expanded="@if ($section->id != $active_section) false @else true @endif" aria-controls="collapse_{{ $section->id }}">
                            {{ ucfirst($section->title) }}
                        </button>
                    </h2>
                    <div id="collapse_{{ $section->id }}" class="accordion-collapse collapse @if ($section->id == $active_section) show @endif" data-bs-parent="#coursePlay">
                        <div class="accordion-body">
                            <ul class="coourse-playlist-list">
                                @foreach ($lessons as $key => $lesson)
                                    @php $type = $lesson->lesson_type; @endphp
                                    <li class="coourse-playlist-item @if (isset($history->watching_lesson_id) && $lesson->id == $history->watching_lesson_id || $lesson->id == $lesson_details->id) active @else lock @endif">
                                        <div class="check-title-area align-items-center">
                                            @if($course_details->enable_drip_content)
                                                @if($is_locked)
                                                    <i class="fas fa-lock" title="<?php echo get_phrase('Complete previous lesson to unlock it'); ?>"></i>
                                                @else
                                                    @if(in_array($lesson->id, $completed_lesson_arr))
                                                        <i class="fas fa-check-circle checkbox-icon" title="<?php echo get_phrase('Lesson completed'); ?>"></i>
                                                    @elseif(in_array($type, ['video-url', 'system-video', 'vimeo-url', 'google_drive']))
                                                        <i class="form-check-input flexCheckChecked mt-0" title="<?php echo get_phrase('Play Now'); ?>"></i>
                                                    @else
                                                        <input class="form-check-input flexCheckChecked mt-0" @if (in_array($lesson->id, $completed_lesson)) checked @endif type="checkbox" id="{{ $lesson->id }}">
                                                    @endif
                                                @endif
                                                <div class="play-lock-number">
                                                    <span>
                                                        @if (in_array($type, ['text', 'document_type', 'iframe']))
                                                            <i class="fa-solid fa-file"></i>
                                                        @elseif (in_array($type, ['video-url', 'system-video', 'vimeo-url']))
                                                            <i class="fa-solid fa-video"></i>
                                                        @elseif ($type == 'image')
                                                            <i class="fa-solid fa-image"></i>
                                                        @elseif ($type == 'google_drive')
                                                            <i class="fa-brands fa-google-drive"></i>
                                                        @else
                                                            <i class="fa-solid fa-file"></i>
                                                        @endif
                                                    </span>
                                                </div>
                                                <p class="d-none">{{ $lesson->lesson_type }}</p>
                                                <a href="{{ route('course.player', ['slug' => $course_details->slug, 'id' => $lesson->id]) }}" class="video-title">{{ $lesson->title }}</a>
                                            @else
                                                <input class="form-check-input flexCheckChecked mt-0" @if (in_array($lesson->id, $completed_lesson)) checked @endif type="checkbox" id="{{ $lesson->id }}">
                                                <div class="play-lock-number">
                                                    @php $type = $lesson->lesson_type; @endphp
                                                    <span>
                                                        @if (in_array($type, ['text', 'document_type', 'iframe']))
                                                            <i class="fa-solid fa-file"></i>
                                                        @elseif (in_array($type, ['video-url', 'system-video', 'vimeo-url']))
                                                            <i class="fa-solid fa-video"></i>
                                                        @elseif ($type == 'image')
                                                            <i class="fa-solid fa-image"></i>
                                                        @elseif ($type == 'google_drive')
                                                            <i class="fa-brands fa-google-drive"></i>
                                                        @else
                                                            <i class="fa-solid fa-file"></i>
                                                        @endif
                                                    </span>
                                                </div>
                                                <p class="d-none">{{ $lesson->lesson_type }}</p>
                                                <a href="{{ route('course.player', ['slug' => $course_details->slug, 'id' => $lesson->id]) }}" class="video-title">{{ $lesson->title }}</a>
                                            @endif
                                        </div>

                                        @if (lesson_durations($lesson->id) != '00:00:00')
                                            <p class="duration">{{ lesson_durations($lesson->id) }}</p>
                                        @endif
                                    </li>
                                    @php
                                        if ($is_locked) {
                                            $locked_lesson_ids[] = $lesson->id;
                                        }

                                        if (
                                            !in_array($lesson->id, $completed_lesson_arr) &&
                                            !$is_locked &&
                                            $course_details->enable_drip_content == 1 &&
                                            auth()->user() &&  // Lowercase 'auth()' for consistency
                                            !$is_course_instructor
                                        ) {
                                            $is_locked = 1;
                                        }
                                    @endphp
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>