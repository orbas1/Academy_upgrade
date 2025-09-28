{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}
@php
    $total_students = DB::table('users')->where('role', 'student')->get();
    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
@endphp
<section>
    <div class="container">
        <div class="row g-20px mb-100px align-items-center">
            <div class="col-lg-6">
                <div class="me-lg-3 signup-form-wrap">
                    <!-- Card -->
                    <div class="">
                        <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/learning_vector.jpg') }}" alt="">
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div>
                    <p class="text-bordered-1 mb-12px builder-editable" builder-identity="2">{{ get_phrase('WHY CHOOSE US') }}</p>
                    <h1 class="title-1 fs-32px lh-38px mb-20px builder-editable" builder-identity="3">{{ get_phrase('Free Resources Learning English for Beginner') }}</h1>
                    <p class="subtitle-1 fs-16px lh-24px mb-30px builder-editable" builder-identity="4">
                        {{ get_phrase('Far far away, behind the word mountains, far from the away countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.') }}
                    </p>
                    <div class="d-flex justify-content-center gap-20px flex-wrap flex-sm-nowrap">
                        <div class="bgcolor-card-1 bg-color-ffeff8-f6f0f4">
                            <h2 class="title-1 fs-44px lh-60px fw-semibold mb-1">{{ count($total_students) }}+</h2>
                            <p class="title-1 fs-16px lh-24px fw-normal builder-editable" builder-identity="5">{{ get_phrase('User already register and signing up for using it') }}</p>
                        </div>
                        <div class="bgcolor-card-1 bg-color-e8f7fc-f1f9fc">
                            <h2 class="title-1 fs-44px lh-60px fw-semibold mb-1">{{ count($total_instructors) }}+</h2>
                            <p class="title-1 fs-16px lh-24px fw-normal builder-editable" builder-identity="6">{{ get_phrase('Instructor have a new ideas every week.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
