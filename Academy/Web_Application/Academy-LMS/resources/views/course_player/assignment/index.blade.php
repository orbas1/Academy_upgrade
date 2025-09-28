<style>
    .text-color {
        color: #212534;
        font-weight: 500;
    }

    .text-color-2 {
        color: #4B5675;
        font-weight: 500;
    }

    .custom-btn {
        padding: 8px 12px !important;
        border-radius: 6px !important;
        font-size: 12px !important;
    }

    .download-button {
        color: #6D718C;
        border: 1px solid #E0E5F3;
        padding: 8px 14px;
        font-size: 12px;
        border-radius: 6px;
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    .download-button:hover {
        color: #007bff;
        border-color: #007bff;
    }

    .active-badge {
        color: #17C653 !important;
        background-color: #ECFDF3 !important;
        border: 1px solid #D5FDE5 !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }

    .completed-badge {
        color: #828AA0 !important;
        background-color: #E3E6EC !important;
        border: 1px solid #F1F1F1 !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }

    .submitted-badge {
        color: #F6C000 !important;
        background-color: #FFFCF1 !important;
        border: 1px solid #FFF6D8 !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }

    .expired-badge {
        color: #F8285A !important;
        background-color: #FFF1F4 !important;
        border: 1px solid #FFE1E8 !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }
</style>
<div class="tab-pane p-4 fade @if ($tab == 'assignment') show active @endif" id="pills-assignment" role="tabpanel"
    aria-labelledby="pills-assignment-tab" tabindex="0">

    <div class="row">
        <div class="col-md-12">
            <h6 class="title">{{ get_phrase('Assignments') }}:</h6>
        </div>
    </div>
    @php
        if (isset($course_details)) {
            $course_id = $course_details->id;
        }
    @endphp
    @foreach (App\Models\Assignment::where('course_id', $course_id)->orderBy('created_at', 'desc')->get() as $key => $assignment)
        <div class="row">
            <div class="col-md-10">
                @if ($assignment->status == 'active' && strtotime($assignment->deadline) >= time())
                    <div class="ol-card my-3" style="border: 1px solid #DBDFEB; border-radius: 6px">
                        <div class="ol-card-body p-4">
                            @php
                                $submitted_assignment = App\Models\SubmittedAssignment::where('assignment_id', $assignment->id)->where('user_id', auth()->id())->first();
                            @endphp
                            <div class="row align-items-center mb-4">
                                <div class="col-md-6">
                                    <h6><span class="fw-bold">{{ get_phrase('Title') }}:</span>
                                        {{ $assignment->title }}</h6>
                                </div>
                                <div class="col-md-6">
                                    @if (!$submitted_assignment || ($submitted_assignment && !$submitted_assignment->status))
                                        <button class="btn btn-sm custom-btn eBtn gradient float-lg-end"
                                            onclick="loadView('{{ route('view', ['path' => 'course_player.assignment.submit_assignment', 'id' => $assignment->id, 'course_id' => $course_id]) }}','#pills-assignment')">
                                            <i class="fi-rr-arrow-up-right-from-square"></i><span></span>
                                            <span>{{ get_phrase(($submitted_assignment ? 'Resubmit' : 'Submit') . ' assignment') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="title mb-2">{{ get_phrase('Questions') }}:</h6>
                                    <div class="ms-4 text-color">
                                        {!! $assignment->questions !!}
                                    </div>
                                    @if($assignment->question_file && file_exists(public_path($assignment->question_file)))
                                        <a href="{{ asset($assignment->question_file) }}" 
                                        class="btn btn-sm custom-btn eBtn gradient mt-2" 
                                        download>
                                        {{ get_phrase('Download question file') }}
                                        </a>
                                    @endif

                                </div>
                            </div>
                            <div class="row mb-4">
                                <p>
                                    <span class="text-color-2">{{ get_phrase('Total marks') }}</span>:
                                    <span class="fw-bold">{{ $assignment->total_marks }}</span>
                                    <span class="mx-2"></span>
                                    <span class="text-color-2">{{ get_phrase('Obtained marks') }}</span>:
                                    <span
                                        class="fw-bold">{{ $submitted_assignment ? $submitted_assignment->marks : '' }}</span>
                                </p>
                            </div>
                            <hr>
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    @if (time() > strtotime($assignment->deadline))
                                        <span class="expired-badge">{{ get_phrase('Expired') }}</span>
                                    @else
                                        @if ($submitted_assignment && $submitted_assignment->status)
                                            <span class="completed-badge">{{ get_phrase('Completed') }}</span>
                                            <span class="mx-1"></span>
                                            <span>
                                                <a class="btn eBtn gradient custom-btn btn-sm"
                                                    onclick="ajaxModal('{{ route('modal', ['view_path' => 'course_player.assignment.preview', 'id' => $assignment->id, 'user_id' => $submitted_assignment->user_id]) }}', '{{ get_phrase('Your submission:') }}')"><i></i>
                                                    {{ get_phrase('Preview answer') }}</a>
                                            </span>
                                            <span class="mx-1"></span>
                                            {{-- <span class="">
                                                <a class="btn  download-button" data-toggle="tooltip"
                                                    data-placement="bottom" title="Download result"
                                                    href="{{ route('assignment.download.result', $assignment->id) }}"><i
                                                        class="fi fi-rr-download"></i></a>
                                            </span> --}}
                                        @elseif ($submitted_assignment)
                                            <span class="submitted-badge">{{ get_phrase('Submitted') }}</span>
                                        @else
                                            <span class="active-badge">{{ get_phrase('Active') }}</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <span class="float-end text-color">{{ get_phrase('Deadline') }}:
                                        {{ $assignment->deadline }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
