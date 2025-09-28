@extends('layouts.default')
@push('title', get_phrase('Tutor Booking'))
@push('meta')@endpush
@push('css')
<style>
    .lms1-video-player .plyr--video{
        height: 100%;
    }
</style>
@endpush
@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <!-- Breadcrumb Area Start -->
    <section class="lms1-breadcrumb-section mb-30px">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-14px lms1-breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="d-flex align-items-center gap-12px">
                                        <img src="{{ asset('assets/frontend/default/image/home-purple-16.svg') }}" alt="">
                                        <span>{{ get_phrase('Home') }}</span>
                                    </a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ get_phrase('Find A Tutor') }}</li>
                            </ol>
                        </nav>
                        <h1 class="in-title-44px">{{ get_phrase('Tutor Booking') }}</h1>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Area End -->


    <!-- Main Area Start -->
    <section>
        <div class="container">
            <div class="row gx-20px mb-80px">
                <div class="col-xl-3 col-lg-4">
                    @include('frontend.default.tutor_booking.filter')
                </div>
                <div class="col-xl-9 col-lg-8">
                    <div class="d-flex justify-content-end d-lg-none mb-3">
                        <button class="btn lms1-sidebar-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar"><span class=" fi-rr-bars-staggered"></span></button>
                    </div>
                    <div class="d-flex align-items-center gap-3 justify-content-between flex-wrap mb-3 flex-column flex-sm-row">
                        <p class="in-subtitle-14px">{{ get_phrase('Showing') . ' ' . count($tutors) . ' ' . get_phrase('of') . ' ' . $tutors->total() . ' ' . get_phrase('Results') }}</p>
                        <div class="d-flex align-items-center gap-14px flex-wrap flex-column flex-sm-row">
                            <form action="{{ route('tutor_list') }}" method="get">
                                <div class="lms1-search-wrap position-relative">
                                    <input type="text" class="form-control sm-search-input lms-w-298px" name="search" placeholder="Search instructor by name..." value="{{ request()->input('search') }}">
                                    <button type="submit" class="sm-search-btn">
                                        <img src="{{ asset('assets/frontend/default/image/search-white-14px.svg') }}" alt="">
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row g-20px mb-30px">
                        @foreach ($tutors as $key => $tutor)
                            @php
                                $index = ++$key;
                                // Get reviews for the specified tutor
                                $reviews = App\Models\TutorReview::where('tutor_id', $tutor->id)->get();
                                $averageRating = $reviews->avg('rating');

                                $lowestPrice = App\Models\TutorCanTeach::where('instructor_id', $tutor->id)->min('price');

                                // if($key > 0) continue;

                            @endphp

                            <div class="col-12">
                                <div class="tutor-bootcamp-card align-items-stretch">
                                    <div class="lms1-video-player tutor-bootcamp-video">
                                        <div class="plyr__video-embed lms-player{{ $index }}">
                                            <iframe src="{{ $tutor->video_url ?? 'https://www.youtube.com/watch?v=OHz0xIR8uwI' }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column justify-content-center">
                                        <div class="d-flex gap-3 justify-content-between mb-3 flex-column flex-sm-row">
                                            <div>
                                                <div class="d-flex gap-2 mb-14px align-items-center">
                                                    <div class="img-wrap-39px">
                                                        <img src="{{ get_image($tutor->photo) }}" alt="user">
                                                    </div>
                                                    <div>
                                                        <div class="mb-2 d-flex align-items-end column-gap-12px row-gap-2 flex-wrap">
                                                            <h4 class="in-title-18px">{{ $tutor->name }}</h4>
                                                            <div class="d-flex gap-1">
                                                                <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                                                <h6 class="in-title-14px">{{ number_format($averageRating, 1) }}</h6>
                                                            </div>
                                                        </div>
                                                        @if ($tutor->about)
                                                            <p class="in-subtitle-14px">
                                                                {{ $tutor->about }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <ul class="d-flex column-gap-3 row-gap-2 flex-wrap mt-4">
                                                    <li class="d-flex gap-1 align-items-center in-title-14px">
                                                        <img src="{{ asset('assets/frontend/default/image/chalkboard-user-gray-18.svg') }}" alt="">
                                                        <span>{{ total_schedule_by_tutor_id($tutor->id) . ' ' . get_phrase('Live schedule') }}</span>
                                                    </li>
                                                    <li class="d-flex gap-1 align-items-center in-title-14px">
                                                        <img src="{{ asset('assets/frontend/default/image/assientment-gray-18.svg') }}" alt="">
                                                        <span>{{ total_booked_schedule_by_tutor_id($tutor->id) . ' ' . get_phrase('Booked schedule') }}</span>
                                                    </li>
                                                    <li class="d-flex gap-1 align-items-center in-title-14px">
                                                        <img src="{{ asset('assets/frontend/default/image/memo-gray-18.svg') }}" alt="">
                                                        <span>{{ total_review_by_tutor_id($tutor->id) . ' ' . get_phrase('Reviews') }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div>
                                                <p class="in-subtitle-14px mb-1 text-start text-sm-end text-nowrap">{{ get_phrase('Session fee') }}</p>
                                                <h4 class="in-title-20px mb-2px text-start text-sm-end">{{ currency($lowestPrice) }}</h4>
                                            </div>
                                        </div>
                                        <p class="in-subtitle2-14px">
                                            {{ Str::limit(strip_tags($tutor->biography), 160) }}
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap pt-4">
                                            <a href="{{ route('tutor_schedule', [$tutor->id, slugify($tutor->name)]) }}" class="btn btn-purple-sm2">
                                                <span>{{ get_phrase('Book a session') }}</span>
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M5.33301 3.83325C5.05967 3.83325 4.83301 3.60659 4.83301 3.33325V1.33325C4.83301 1.05992 5.05967 0.833252 5.33301 0.833252C5.60634 0.833252 5.83301 1.05992 5.83301 1.33325V3.33325C5.83301 3.60659 5.60634 3.83325 5.33301 3.83325Z" fill="white" />
                                                    <path d="M10.667 3.83325C10.3937 3.83325 10.167 3.60659 10.167 3.33325V1.33325C10.167 1.05992 10.3937 0.833252 10.667 0.833252C10.9403 0.833252 11.167 1.05992 11.167 1.33325V3.33325C11.167 3.60659 10.9403 3.83325 10.667 3.83325Z" fill="white" />
                                                    <path d="M5.66667 9.66655C5.58 9.66655 5.49333 9.64656 5.41333 9.61322C5.32667 9.57989 5.26 9.53321 5.19333 9.47321C5.07333 9.34654 5 9.17988 5 8.99988C5 8.91322 5.02 8.82655 5.05333 8.74655C5.08667 8.66655 5.13333 8.59322 5.19333 8.52655C5.26 8.46655 5.32667 8.41987 5.41333 8.38654C5.65333 8.28654 5.95333 8.33989 6.14 8.52655C6.26 8.65322 6.33333 8.82655 6.33333 8.99988C6.33333 9.03988 6.32667 9.08656 6.32 9.13322C6.31333 9.17322 6.3 9.21322 6.28 9.25322C6.26667 9.29322 6.24667 9.33321 6.22 9.37321C6.2 9.40655 6.16667 9.43988 6.14 9.47321C6.01333 9.59321 5.84 9.66655 5.66667 9.66655Z" fill="white" />
                                                    <path d="M7.99967 9.66658C7.91301 9.66658 7.82634 9.64659 7.74634 9.61326C7.65967 9.57992 7.59301 9.53324 7.52634 9.47324C7.40634 9.34658 7.33301 9.17992 7.33301 8.99992C7.33301 8.91325 7.35301 8.82658 7.38634 8.74658C7.41968 8.66658 7.46634 8.59325 7.52634 8.52659C7.59301 8.46659 7.65967 8.41991 7.74634 8.38657C7.98634 8.27991 8.28634 8.33992 8.47301 8.52659C8.59301 8.65325 8.66634 8.82658 8.66634 8.99992C8.66634 9.03992 8.65967 9.08659 8.65301 9.13326C8.64634 9.17326 8.63301 9.21325 8.61301 9.25325C8.59967 9.29325 8.57968 9.33325 8.55301 9.37325C8.53301 9.40658 8.49967 9.43991 8.47301 9.47324C8.34634 9.59324 8.17301 9.66658 7.99967 9.66658Z" fill="white" />
                                                    <path d="M10.3337 9.66658C10.247 9.66658 10.1603 9.64659 10.0803 9.61326C9.99366 9.57992 9.92699 9.53324 9.86033 9.47324C9.83366 9.43991 9.80699 9.40658 9.78032 9.37325C9.75366 9.33325 9.73366 9.29325 9.72033 9.25325C9.70033 9.21325 9.68699 9.17326 9.68033 9.13326C9.67366 9.08659 9.66699 9.03992 9.66699 8.99992C9.66699 8.82658 9.74033 8.65325 9.86033 8.52659C9.92699 8.46659 9.99366 8.41991 10.0803 8.38657C10.327 8.27991 10.6203 8.33992 10.807 8.52659C10.927 8.65325 11.0003 8.82658 11.0003 8.99992C11.0003 9.03992 10.9937 9.08659 10.987 9.13326C10.9803 9.17326 10.967 9.21325 10.947 9.25325C10.9337 9.29325 10.9137 9.33325 10.887 9.37325C10.867 9.40658 10.8337 9.43991 10.807 9.47324C10.6803 9.59324 10.507 9.66658 10.3337 9.66658Z"
                                                        fill="white" />
                                                    <path d="M5.66667 12C5.58 12 5.49333 11.9801 5.41333 11.9467C5.33333 11.9134 5.26 11.8667 5.19333 11.8067C5.07333 11.68 5 11.5067 5 11.3334C5 11.2467 5.02 11.16 5.05333 11.08C5.08667 10.9934 5.13333 10.92 5.19333 10.86C5.44 10.6134 5.89333 10.6134 6.14 10.86C6.26 10.9867 6.33333 11.16 6.33333 11.3334C6.33333 11.5067 6.26 11.68 6.14 11.8067C6.01333 11.9267 5.84 12 5.66667 12Z" fill="white" />
                                                    <path d="M7.99967 12C7.82634 12 7.65301 11.9267 7.52634 11.8067C7.40634 11.68 7.33301 11.5067 7.33301 11.3334C7.33301 11.2467 7.35301 11.16 7.38634 11.08C7.41968 10.9934 7.46634 10.92 7.52634 10.86C7.77301 10.6134 8.22634 10.6134 8.47301 10.86C8.53301 10.92 8.57967 10.9934 8.61301 11.08C8.64634 11.16 8.66634 11.2467 8.66634 11.3334C8.66634 11.5067 8.59301 11.68 8.47301 11.8067C8.34634 11.9267 8.17301 12 7.99967 12Z" fill="white" />
                                                    <path d="M10.3337 11.9999C10.1603 11.9999 9.98699 11.9266 9.86033 11.8066C9.80033 11.7466 9.75366 11.6733 9.72033 11.5866C9.68699 11.5066 9.66699 11.4199 9.66699 11.3333C9.66699 11.2466 9.68699 11.1599 9.72033 11.0799C9.75366 10.9933 9.80033 10.9199 9.86033 10.8599C10.0137 10.7066 10.247 10.6333 10.4603 10.6799C10.507 10.6866 10.547 10.6999 10.587 10.7199C10.627 10.7333 10.667 10.7533 10.707 10.78C10.7403 10.8 10.7737 10.8333 10.807 10.8599C10.927 10.9866 11.0003 11.1599 11.0003 11.3333C11.0003 11.5066 10.927 11.6799 10.807 11.8066C10.6803 11.9266 10.507 11.9999 10.3337 11.9999Z" fill="white" />
                                                    <path d="M13.6663 6.56006H2.33301C2.05967 6.56006 1.83301 6.33339 1.83301 6.06006C1.83301 5.78673 2.05967 5.56006 2.33301 5.56006H13.6663C13.9397 5.56006 14.1663 5.78673 14.1663 6.06006C14.1663 6.33339 13.9397 6.56006 13.6663 6.56006Z" fill="white" />
                                                    <path d="M10.6667 15.1666H5.33333C2.9 15.1666 1.5 13.7666 1.5 11.3333V5.66658C1.5 3.23325 2.9 1.83325 5.33333 1.83325H10.6667C13.1 1.83325 14.5 3.23325 14.5 5.66658V11.3333C14.5 13.7666 13.1 15.1666 10.6667 15.1666ZM5.33333 2.83325C3.42667 2.83325 2.5 3.75992 2.5 5.66658V11.3333C2.5 13.2399 3.42667 14.1666 5.33333 14.1666H10.6667C12.5733 14.1666 13.5 13.2399 13.5 11.3333V5.66658C13.5 3.75992 12.5733 2.83325 10.6667 2.83325H5.33333Z" fill="white" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('message.inbox', $tutor->id) }}" class="btn btn-outline-purple-sm">
                                                <span>{{ get_phrase('Send Message') }}</span>
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M11.2663 12.6732C11.0597 12.6732 10.853 12.6132 10.673 12.4932L10.033 12.0732C9.85301 11.9532 9.76635 11.7265 9.82634 11.5199C9.87301 11.3665 9.89301 11.1865 9.89301 10.9865V8.27315C9.89301 7.18649 9.21301 6.50651 8.12634 6.50651H3.59967C3.51967 6.50651 3.44634 6.51319 3.37301 6.51986C3.23301 6.52652 3.09968 6.47985 2.99301 6.38652C2.88634 6.29318 2.83301 6.15986 2.83301 6.01986V4.17318C2.83301 2.21318 4.20634 0.839844 6.16634 0.839844H11.833C13.793 0.839844 15.1663 2.21318 15.1663 4.17318V7.57316C15.1663 8.53983 14.8397 9.39315 14.2397 9.97982C13.7597 10.4665 13.093 10.7798 12.333 10.8732V11.6132C12.333 12.0132 12.113 12.3732 11.7663 12.5599C11.6063 12.6332 11.433 12.6732 11.2663 12.6732ZM10.8663 11.4198L11.2997 11.6665C11.3397 11.6465 11.3397 11.6132 11.3397 11.6065V10.3998C11.3397 10.1265 11.5663 9.89982 11.8397 9.89982C12.5397 9.89982 13.133 9.67985 13.5397 9.26652C13.9597 8.85319 14.173 8.26649 14.173 7.56649V4.1665C14.173 2.7465 13.2597 1.83317 11.8397 1.83317H6.173C4.753 1.83317 3.83967 2.7465 3.83967 4.1665V5.49984H8.13301C9.75967 5.49984 10.8997 6.63985 10.8997 8.26652V10.9798C10.893 11.1332 10.8863 11.2798 10.8663 11.4198Z"
                                                        fill="#754FFE" />
                                                    <path
                                                        d="M4.04635 15.1667C3.89968 15.1667 3.74634 15.1334 3.60634 15.06C3.29301 14.8934 3.09967 14.5733 3.09967 14.2133V13.7067C2.513 13.6133 1.99301 13.3666 1.60634 12.98C1.09968 12.4733 0.833008 11.78 0.833008 10.98V8.26668C0.833008 6.76002 1.81967 5.65335 3.28634 5.51335C3.393 5.50668 3.493 5.5 3.59967 5.5H8.12634C9.75301 5.5 10.893 6.64002 10.893 8.26668V10.98C10.893 11.2733 10.8597 11.5467 10.7863 11.7933C10.4863 12.9933 9.46634 13.7467 8.12634 13.7467H6.46634L4.57967 15C4.41967 15.1133 4.23301 15.1667 4.04635 15.1667ZM3.59967 6.5C3.51967 6.5 3.44634 6.50668 3.37301 6.51335C2.41301 6.60001 1.83301 7.26002 1.83301 8.26668V10.98C1.83301 11.5133 1.99968 11.96 2.31301 12.2733C2.61968 12.58 3.06634 12.7467 3.59967 12.7467C3.873 12.7467 4.09967 12.9733 4.09967 13.2467V14.12L6.03301 12.8333C6.11301 12.78 6.21301 12.7467 6.31301 12.7467H8.12634C9.00634 12.7467 9.62634 12.3067 9.81967 11.5333C9.86634 11.3667 9.89301 11.18 9.89301 10.98V8.26668C9.89301 7.18002 9.21301 6.5 8.12634 6.5H3.59967Z"
                                                        fill="#754FFE" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- Pagination -->
                    @if (count($tutors) > 0)
                        <div class="entry-pagination">
                            <nav aria-label="Page navigation example">
                                {{ $tutors->links() }}
                            </nav>
                        </div>
                    @endif
                    <!-- Pagination -->
                </div>
            </div>
        </div>
    </section>
    <!-- Main Area End -->
@endsection
@push('js')

    <script>
        @foreach ($tutors as $key => $tutor)
            var tutorPlayer = new Plyr('.lms-player{{ ++$key }}');
        @endforeach
    </script>

@endpush
