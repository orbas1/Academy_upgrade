{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

@php
    $total_students = DB::table('users')->where('role', 'student')->get();
    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
@endphp

<section>
    <div class="container">
        <div class="row mb-110">
            <div class="col-md-12">

                <div class="cooking-counter-area d-flex">
                    <div class="cooking-counter-single">
                        <h2 class="total fw-500"><span class="counter1">{{ count($total_students) }}</span>+</h2>
                        <p class="info builder-editable" builder-identity="1">{{ get_phrase('Enrolled Learners') }}</p>
                    </div>
                    <div class="cooking-counter-single">
                        <h2 class="total fw-500"><span class="counter1">{{ count($total_instructors) }}</span></h2>
                        <p class="info builder-editable" builder-identity="2">{{ get_phrase('Online Instructors') }}</p>
                    </div>
                    <div class="cooking-counter-single">
                        <h2 class="total fw-500"><span class="counter1">{{ count($premium_courses) }}</span>+</h2>
                        <p class="info builder-editable" builder-identity="3">{{ get_phrase('Premium courses') }}</p>
                    </div>
                    <div class="cooking-counter-single">
                        <h2 class="total fw-500"><span class="counter1">{{ count($free_courses) }}</span>+</h2>
                        <p class="info builder-editable" builder-identity="4">{{ get_phrase('Cost-free course') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
