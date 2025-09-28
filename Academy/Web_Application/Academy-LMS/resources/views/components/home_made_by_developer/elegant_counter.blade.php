{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="why-choose-section1 mb-80">
    <div class="container py-3">
        <div class="row">
            <div class="why-choose-area1">
                <h2 class="title mb-30 fw-500 builder-editable" builder-identity="1">{{ get_phrase('Why Choose Us') }}</h2>
                @php
                    $total_students = DB::table('users')->where('role', 'student')->get();
                    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
                    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
                    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
                @endphp
                <div class="why-choose-wrap1">
                    <div class="why-choose1-single">
                        <h1 class="total fw-500">{{ count($total_students) }} +</h1>
                        <p class="info builder-editable" builder-identity="2">{{ get_phrase('Happy student') }}</p>
                    </div>
                    <div class="why-choose1-single">
                        <h1 class="total fw-500">{{ count($total_instructors) }} +</h1>
                        <p class="info builder-editable" builder-identity="3">{{ get_phrase('Quality educators') }}</p>
                    </div>
                    <div class="why-choose1-single">
                        <h1 class="total fw-500">{{ count($premium_courses) }}+</h1>
                        <p class="info builder-editable" builder-identity="4">{{ get_phrase('Premium courses') }}</p>
                    </div>
                    <div class="why-choose1-single">
                        <h1 class="total fw-500">{{ count($free_courses) }}+</h1>
                        <p class="info builder-editable" builder-identity="5">{{ get_phrase('Cost-free course') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
