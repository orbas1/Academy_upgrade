{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="cooking-section-title">
                    <h3 class="title-5 fs-32px lh-42px fw-600 text-center mb-20 builder-editable" builder-identity="1">{{ get_phrase('Our Popular Instructor') }}</h3>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('Highlights our most sought-after educator, recognized for their engaging teaching style and exceptional course content. Discover their expertise and join the many students who have benefited from their classes!') }}</p>
                </div>
            </div>
        </div>
        <div class="row row-28 mb-110">
            @php
                $popular_instaructors = DB::table('courses')->select('enrollments.user_id', DB::raw('count(*) as enrol_number'))->join('enrollments', 'courses.id', '=', 'enrollments.course_id')->groupBy('enrollments.user_id')->orderBy('enrollments.user_id', 'DESC')->limit(10)->get();
            @endphp
            @foreach ($popular_instaructors as $key => $instructor)
                @php
                    $instructorDetails = App\Models\User::where('id', $instructor->user_id)->first();
                    if (!$instructorDetails) {
                        continue;
                    }
                @endphp
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="cooking-popular-instructor">
                        <div class="profile-img">
                            <img src="{{ get_image($instructorDetails->photo) }}" alt="">
                        </div>
                        <div class="details">
                            <a href="{{ route('instructor.details', ['name' => slugify($instructorDetails->name), 'id' => $instructorDetails->id]) }}" class="w-100">
                                <h5 class="name" style="line-height: 26px;">{{ $instructorDetails->name }}</h5>
                                <p class="role">{{ get_phrase('Instructor') }}</p>
                            </a>
                            <ul class="popular-instructor-socila d-flex align-items-center justify-content-center flex-wrap">
                                <li>
                                    <a href="{{ $instructorDetails->facebook }}">
                                        <svg width="8" height="15" viewBox="0 0 8 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <mask id="mask0_111_1949" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="8" height="15">
                                                <path d="M7.53321 0.875488H0V14.812H7.53321V0.875488Z" fill="white" />
                                            </mask>
                                            <g mask="url(#mask0_111_1949)">
                                                <path
                                                    d="M7.30463 0.875488C7.37937 0.902297 7.44278 0.953716 7.48445 1.02131C7.52612 1.0889 7.54357 1.16865 7.53395 1.24747C7.52782 1.9004 7.53395 2.5542 7.53395 3.20713C7.53395 3.47846 7.43679 3.57036 7.16284 3.57298C6.70509 3.57736 6.24646 3.58174 5.78871 3.59224C5.69363 3.59713 5.59968 3.61509 5.50951 3.64563C5.36852 3.68817 5.24635 3.77774 5.16338 3.89941C5.08041 4.02109 5.04163 4.16752 5.05351 4.31431C5.03863 4.83071 5.05351 5.34797 5.05351 5.88975H5.19705C5.82285 5.88975 6.44952 5.88975 7.07532 5.88975C7.13858 5.88831 7.2017 5.89627 7.26262 5.91338C7.30963 5.928 7.35136 5.95597 7.38275 5.99389C7.41414 6.03181 7.43383 6.07803 7.43942 6.12694C7.44422 6.16291 7.44627 6.19919 7.44555 6.23547C7.44555 6.95725 7.44555 7.67845 7.44555 8.39907C7.44712 8.46664 7.43429 8.53377 7.40791 8.596C7.37972 8.64837 7.33619 8.69086 7.28315 8.71777C7.23011 8.74468 7.17011 8.75472 7.1112 8.74654C6.53967 8.74654 5.96814 8.74654 5.39661 8.74654H5.04651V14.3341C5.04651 14.3656 5.04651 14.3971 5.04651 14.4295C5.04651 14.7131 4.94498 14.8137 4.66228 14.8137H2.47942C2.42509 14.8153 2.37075 14.8106 2.3175 14.7997C2.2613 14.7884 2.21035 14.759 2.17242 14.716C2.13449 14.673 2.11167 14.6188 2.10745 14.5616C2.10218 14.5076 2.10043 14.4532 2.10219 14.3989C2.10219 12.5748 2.10219 10.7511 2.10219 8.92771V8.74479H0.383219C0.337932 8.74605 0.292611 8.74429 0.247557 8.73954C0.185913 8.73335 0.128323 8.70598 0.0845949 8.66209C0.040867 8.6182 0.0137067 8.56051 0.0077405 8.49885C0.00184599 8.44918 -0.000785379 8.39919 -0.000136677 8.34918C-0.000136677 7.65949 -0.000136677 6.97009 -0.000136677 6.28098C-0.000136677 6.25385 -0.000136677 6.22672 -0.000136677 6.19958C-0.00334378 6.15743 0.00265811 6.11508 0.0174527 6.07548C0.0322472 6.03588 0.0554803 5.99997 0.0855401 5.97024C0.1156 5.94051 0.151767 5.91768 0.191532 5.90333C0.231298 5.88898 0.27371 5.88345 0.315826 5.88712C0.465492 5.88275 0.615158 5.88712 0.764825 5.88712H2.10044V5.73046C2.10044 5.19043 2.09519 4.65041 2.10832 4.11038C2.11458 3.5092 2.2736 2.91948 2.57045 2.39666C2.91731 1.78713 3.47374 1.32432 4.13626 1.0943C4.41868 0.992845 4.71273 0.927272 5.0115 0.89912C5.04158 0.893774 5.07116 0.885868 5.0999 0.875488L7.30463 0.875488Z"
                                                    fill="#00907F" />
                                            </g>
                                        </svg>
                                    </a>
                                </li>
                                <li><a href="{{ $instructorDetails->twitter }}">
                                        <svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M14.8394 4.24582C15.214 9.86399 11.0943 14.3584 5.85069 14.3584C4.12691 14.4184 2.41057 14.1043 0.819802 13.4376C0.751673 13.4056 0.696919 13.3507 0.665049 13.2825C0.63318 13.2143 0.626209 13.1371 0.645348 13.0643C0.664487 12.9914 0.708526 12.9276 0.769815 12.8839C0.831104 12.8402 0.905771 12.8193 0.980846 12.8249C2.48538 12.8467 3.94769 12.3274 5.10149 11.3615C1.04474 10.0093 0.344545 4.99502 1.071 2.37893C1.09419 2.31371 1.13619 2.25683 1.1917 2.21548C1.24721 2.17412 1.31373 2.15015 1.38285 2.14659C1.45198 2.14304 1.52061 2.16005 1.58007 2.19548C1.63953 2.23091 1.68716 2.28318 1.71692 2.34567C2.96414 4.47338 5.3308 5.66196 8.29612 5.38801C8.02006 4.62049 8.0316 3.77892 8.32861 3.01926C8.62562 2.25961 9.18795 1.63338 9.91139 1.25665C10.6348 0.879922 11.4703 0.778231 12.263 0.970427C13.0557 1.16262 13.7518 1.63567 14.2224 2.3019L15.5125 2.11723C15.5841 2.10695 15.6571 2.11761 15.7228 2.14795C15.7885 2.17828 15.844 2.22697 15.8826 2.28815C15.9212 2.34933 15.9412 2.42038 15.9403 2.49271C15.9394 2.56504 15.9176 2.63557 15.8775 2.69576L14.8394 4.24582Z"
                                                fill="#00907F" />
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ $instructorDetails->linkedin }}">
                                        <i class="fa-brands fa-linkedin"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</section>
