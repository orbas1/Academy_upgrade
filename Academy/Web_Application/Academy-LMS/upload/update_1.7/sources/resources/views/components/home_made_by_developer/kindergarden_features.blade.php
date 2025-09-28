{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="counter-area-wrap1 mb-100px">
            <div class="row g-28px row-cols-xl-4 row-cols-lg-3 row-cols-md-2 row-cols-1">
                <div class="col">
                    @php
                        $total_students = DB::table('users')->where('role', 'student')->get();
                        $total_instructors = DB::table('users')->where('role', 'instructor')->get();
                        $free_courses = DB::table('courses')->where('is_paid', 0)->get();
                        $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
                    @endphp
                    <div class="d-flex align-items-center gap-3">
                        <div class="image-box-md">
                            <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/kg-counter-img1.png') }}" alt="">
                        </div>
                        <div>
                            <h2 class="kg-counter-title mb-2px"><span class="counter2">{{ count($premium_courses) }}</span>+</h2>
                            <p class="title-3 fs-17px lh-23px builder-editable" builder-identity="2">{{ get_phrase('Premium courses') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="image-box-md">
                            <img class="builder-editable" builder-identity="3" src="{{ asset('assets/frontend/default/image/kg-counter-img2.png') }}" alt="">
                        </div>
                        <div>
                            <h2 class="kg-counter-title mb-2px"><span class="counter2">{{ count($total_instructors) }}</span>+</h2>
                            <p class="title-3 fs-17px lh-23px builder-editable" builder-identity="4">{{ get_phrase('Expert Mentors') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="image-box-md">
                            <img class="builder-editable" builder-identity="5" src="{{ asset('assets/frontend/default/image/kg-counter-img3.png') }}" alt="">
                        </div>
                        <div>
                            <h2 class="kg-counter-title mb-2px"><span class="counter2">{{ count($total_students) }}</span>+</h2>
                            <p class="title-3 fs-17px lh-23px builder-editable" builder-identity="6">{{ get_phrase('Students Globally') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="image-box-md">
                            <img class="builder-editable" builder-identity="7" src="{{ asset('assets/frontend/default/image/free.svg') }}" alt="">
                        </div>
                        <div>
                            <h2 class="kg-counter-title mb-2px"><span class="counter2">{{ count($free_courses) }}</span>+</h2>
                            <p class="title-3 fs-17px lh-23px builder-editable" builder-identity="8">{{ get_phrase('Cost Free Course') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
