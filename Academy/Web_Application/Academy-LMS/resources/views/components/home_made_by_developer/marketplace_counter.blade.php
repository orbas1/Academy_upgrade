{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="counter-section-2">
    <div class="container">
        <div class="row mb-100px">
            <div class="col-md-12">
                @php
                    $total_students = DB::table('users')->where('role', 'student')->get();
                    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
                    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
                    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
                @endphp
                <div class="counter-area-wrap2">
                    <div class="counter-single-item2">
                        <h1 class="title-4 fs-82px lh-107px fw-semibold text-white text-center mb-5px"><span class="counter2">{{ count($total_students) }}</span>+</h1>
                        <p class="subtitle-3 fs-18px lh-25px fw-normal text-white text-center builder-editable" builder-identity="1">{{ get_phrase('Happy Student') }}</p>
                    </div>
                    <div class="counter-single-item2">
                        <h1 class="title-4 fs-82px lh-107px fw-semibold text-white text-center mb-5px"><span class="counter2">{{ count($total_instructors) }}</span></h1>
                        <p class="subtitle-3 fs-18px lh-25px fw-normal text-white text-center builder-editable" builder-identity="2">{{ get_phrase('Quality educators') }}+</p>
                    </div>
                    <div class="counter-single-item2">
                        <h1 class="title-4 fs-82px lh-107px fw-semibold text-white text-center mb-5px"><span class="counter2">{{ count($premium_courses) }}</span>+</h1>
                        <p class="subtitle-3 fs-18px lh-25px fw-normal text-white text-center builder-editable" builder-identity="3">{{ get_phrase('Premium courses') }}</p>
                    </div>
                    <div class="counter-single-item2">
                        <h1 class="title-4 fs-82px lh-107px fw-semibold text-white text-center mb-5px"><span class="counter2">{{ count($free_courses) }}</span>+</h1>
                        <p class="subtitle-3 fs-18px lh-25px fw-normal text-white text-center builder-editable" builder-identity="4">{{ get_phrase('Cost-free course') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
