@extends('layouts.default')
@push('title', get_phrase('Tutor Booking'))
@push('meta')@endpush
@push('css')@endpush
@section('content')

@php
    use Illuminate\Support\Str;
@endphp

<!-- Breadcrumb Area Start -->
<section>
    <div class="container">
        <div class="row mb-20px">
            <div class="col-12">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb lms1-breadcrumb">
                          <li class="breadcrumb-item"><a href="#" class="d-flex align-items-center gap-12px">
                            <img src="{{ asset('assets/frontend/default/image/home-purple-16.svg') }}" alt="">
                            <span>{{ get_phrase('Home') }}</span>
                          </a></li>
                          <li class="breadcrumb-item active" aria-current="page">{{ get_phrase('Tutor Details') }}</li>
                        </ol>
                    </nav>                         
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
            <!-- Left -->
            <div class="col-xl-3 col-lg-4">
                <div class="offcanvas-lg offcanvas-start lms1-tutor-offcanvas" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
                    <div class="offcanvas-header">
                      <h5 class="offcanvas-title" id="offcanvasSidebarLabel"></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasSidebar" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="lms1-tutor-sidebar">
                            <div class="lms-border-bottom pb-3 mb-3">
                                <div class="tutor-profile-wrap mx-auto">
                                    <img src="{{ get_image($tutor_details->photo) }}" alt="tutor">
                                </div>
                                @php
                                    // Get reviews for the specified tutor
                                    $reviews = App\Models\TutorReview::where('tutor_id', $tutor_details->id)->get();
                                    $averageRating = $reviews->avg('rating');
                                @endphp
                                <div class="tutor-star-rating d-flex align-items-center gap-1 justify-content-center">
                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-12.svg') }}" alt="">
                                    <p class="in-title-14px">{{ number_format($averageRating, 1) }}</p>
                                </div>
                                <h3 class="in-title-16px text-center mb-2 fw-semibold">{{ $tutor_details->name }}</h3>
                                <p class="in-subtitle-14px text-break text-center fw-normal">{{ $tutor_details->email }}</p>
                            </div>

                            @if($tutor_details->about)
                                <div class="lms-border-bottom pb-3 mb-3">
                                    <p class="in-subtitle2-14px">{{ $tutor_details->about }}</p>
                                </div>
                            @endif
                            
                            <ul class="mb-3 d-flex flex-column gap-3">
                                <li class="d-flex align-items-center gap-1">
                                    <img class="me-2" src="{{ asset('assets/frontend/default/image/chalkboard-user-gray-20.svg') }}" alt="">
                                    <p class="in-title-14px">{{ total_schedule_by_tutor_id($tutor_details->id).' '.get_phrase('Live schedule') }}</p>
                                </li>
                                <li class="d-flex align-items-center gap-1">
                                    <img class="me-2" src="{{ asset('assets/frontend/default/image/assignment-gray-20.svg') }}" alt="">
                                    <p class="in-title-14px">{{ total_booked_schedule_by_tutor_id($tutor_details->id).' '.get_phrase('Booked schedule') }}</p>
                                </li>
                                <li class="d-flex align-items-center gap-1">
                                    <img class="me-2" src="{{ asset('assets/frontend/default/image/memo-gray-20.svg') }}" alt="">
                                    <p class="in-title-14px">{{ total_review_by_tutor_id($tutor_details->id).' '.get_phrase('Reviews') }}</p>
                                </li>
                            </ul>
                            <div class="mb-3">
                                <a href="{{ route('message.inbox',  $tutor_details->id) }}" class="btn btn-outline-purple-sm2 w-100">
                                    <span>Send Message</span>
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11.2663 12.6732C11.0597 12.6732 10.853 12.6132 10.673 12.4932L10.033 12.0732C9.85301 11.9532 9.76635 11.7265 9.82634 11.5199C9.87301 11.3665 9.89301 11.1865 9.89301 10.9865V8.27315C9.89301 7.18649 9.21301 6.50651 8.12634 6.50651H3.59967C3.51967 6.50651 3.44634 6.51319 3.37301 6.51986C3.23301 6.52652 3.09968 6.47985 2.99301 6.38652C2.88634 6.29318 2.83301 6.15986 2.83301 6.01986V4.17318C2.83301 2.21318 4.20634 0.839844 6.16634 0.839844H11.833C13.793 0.839844 15.1663 2.21318 15.1663 4.17318V7.57316C15.1663 8.53983 14.8397 9.39315 14.2397 9.97982C13.7597 10.4665 13.093 10.7798 12.333 10.8732V11.6132C12.333 12.0132 12.113 12.3732 11.7663 12.5599C11.6063 12.6332 11.433 12.6732 11.2663 12.6732ZM10.8663 11.4198L11.2997 11.6665C11.3397 11.6465 11.3397 11.6132 11.3397 11.6065V10.3998C11.3397 10.1265 11.5663 9.89982 11.8397 9.89982C12.5397 9.89982 13.133 9.67985 13.5397 9.26652C13.9597 8.85319 14.173 8.26649 14.173 7.56649V4.1665C14.173 2.7465 13.2597 1.83317 11.8397 1.83317H6.173C4.753 1.83317 3.83967 2.7465 3.83967 4.1665V5.49984H8.13301C9.75967 5.49984 10.8997 6.63985 10.8997 8.26652V10.9798C10.893 11.1332 10.8863 11.2798 10.8663 11.4198Z" fill="#754FFE"></path>
                                        <path d="M4.04635 15.1667C3.89968 15.1667 3.74634 15.1334 3.60634 15.06C3.29301 14.8934 3.09967 14.5733 3.09967 14.2133V13.7067C2.513 13.6133 1.99301 13.3666 1.60634 12.98C1.09968 12.4733 0.833008 11.78 0.833008 10.98V8.26668C0.833008 6.76002 1.81967 5.65335 3.28634 5.51335C3.393 5.50668 3.493 5.5 3.59967 5.5H8.12634C9.75301 5.5 10.893 6.64002 10.893 8.26668V10.98C10.893 11.2733 10.8597 11.5467 10.7863 11.7933C10.4863 12.9933 9.46634 13.7467 8.12634 13.7467H6.46634L4.57967 15C4.41967 15.1133 4.23301 15.1667 4.04635 15.1667ZM3.59967 6.5C3.51967 6.5 3.44634 6.50668 3.37301 6.51335C2.41301 6.60001 1.83301 7.26002 1.83301 8.26668V10.98C1.83301 11.5133 1.99968 11.96 2.31301 12.2733C2.61968 12.58 3.06634 12.7467 3.59967 12.7467C3.873 12.7467 4.09967 12.9733 4.09967 13.2467V14.12L6.03301 12.8333C6.11301 12.78 6.21301 12.7467 6.31301 12.7467H8.12634C9.00634 12.7467 9.62634 12.3067 9.81967 11.5333C9.86634 11.3667 9.89301 11.18 9.89301 10.98V8.26668C9.89301 7.18002 9.21301 6.5 8.12634 6.5H3.59967Z" fill="#754FFE"></path>
                                    </svg>
                                </a>
                            </div>
                            <ul class="d-flex align-items-center gap-3 justify-content-center flex-wrap">
                                <li>
                                    <a href="{{ $tutor_details->facebook }}" class="svg-link">
                                        <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_526_3022)">
                                            <path d="M14.7897 7C14.7897 3.134 11.4789 0 7.39486 0C3.31079 0 0 3.134 0 7C0 10.4938 2.70418 13.3898 6.23941 13.915V9.02344H4.36181V7H6.23941V5.45781C6.23941 3.70344 7.34344 2.73438 9.03256 2.73438C9.84138 2.73438 10.6879 2.87109 10.6879 2.87109V4.59375H9.75544C8.83686 4.59375 8.55031 5.13338 8.55031 5.6875V7H10.6012L10.2734 9.02344H8.55031V13.915C12.0855 13.3898 14.7897 10.4938 14.7897 7Z" fill="#929AA7"/>
                                            </g>
                                            <defs>
                                            <clipPath id="clip0_526_3022">
                                            <rect width="14.7897" height="14" fill="white"/>
                                            </clipPath>
                                            </defs>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ $tutor_details->twitter }}" class="svg-link">
                                        <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_526_3024)">
                                              <path d="M9.26792 5.93693L14.6914 0.0412598H13.4062L8.697 5.16039L4.93578 0.0412598H0.597656L6.28536 7.78229L0.597656 13.9648H1.88292L6.85595 8.55884L10.8281 13.9648H15.1662L9.2676 5.93693H9.26792ZM7.50758 7.85049L6.9313 7.07966L2.34602 0.946063H4.3201L8.02048 5.89607L8.59676 6.66691L13.4068 13.1012H11.4327L7.50758 7.85079V7.85049Z" fill="#929AA7"/>
                                            </g>
                                            <defs>
                                              <clipPath id="clip0_526_3024">
                                                <rect width="14.8887" height="13.9236" fill="white" transform="translate(0.4375 0.0412598)"/>
                                              </clipPath>
                                            </defs>
                                        </svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ $tutor_details->linkedin }}" class="svg-link">
                                        <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_526_3020)">
                                              <path d="M14.4664 0H1.86338C1.25966 0 0.771484 0.451172 0.771484 1.00898V12.9883C0.771484 13.5461 1.25966 14 1.86338 14H14.4664C15.0701 14 15.5612 13.5461 15.5612 12.991V1.00898C15.5612 0.451172 15.0701 0 14.4664 0ZM5.15929 11.9301H2.96395V5.24727H5.15929V11.9301ZM4.06162 4.33672C3.3568 4.33672 2.78774 3.79805 2.78774 3.13359C2.78774 2.46914 3.3568 1.93047 4.06162 1.93047C4.76355 1.93047 5.33261 2.46914 5.33261 3.13359C5.33261 3.79531 4.76355 4.33672 4.06162 4.33672ZM13.3745 11.9301H11.1821V8.68164C11.1821 7.90781 11.1676 6.90977 10.0411 6.90977C8.90005 6.90977 8.72674 7.75469 8.72674 8.62695V11.9301H6.53717V5.24727H8.64008V6.16055H8.66897C8.96072 5.63555 9.67709 5.08047 10.743 5.08047C12.9643 5.08047 13.3745 6.46406 13.3745 8.26328V11.9301Z" fill="#929AA7"/>
                                            </g>
                                            <defs>
                                              <clipPath id="clip0_526_3020">
                                                <rect width="14.7897" height="14" fill="white" transform="translate(0.771484)"/>
                                              </clipPath>
                                            </defs>
                                        </svg>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Right -->
            <div class="col-xl-9 col-lg-8">
                <div class="d-flex justify-content-end d-lg-none mb-3">
                    <button class="btn lms1-sidebar-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar"><span class=" fi-rr-bars-staggered"></span></button>
                </div>
                <!-- Tab Area -->
                 <div class="lms-content-card">
                    <ul class="nav nav-pills mb-20px lms-border-bottom column-gap-3 row-gap-2" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                          <button class="nav-link tutor-tab-link svg-fill active" id="pills-one-tab" data-bs-toggle="pill" data-bs-target="#pills-one" type="button" role="tab" aria-controls="pills-one" aria-selected="true">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.66602 4.7915C6.32435 4.7915 6.04102 4.50817 6.04102 4.1665V1.6665C6.04102 1.32484 6.32435 1.0415 6.66602 1.0415C7.00768 1.0415 7.29102 1.32484 7.29102 1.6665V4.1665C7.29102 4.50817 7.00768 4.7915 6.66602 4.7915Z" fill="#6E798A"/>
                                <path d="M13.334 4.7915C12.9923 4.7915 12.709 4.50817 12.709 4.1665V1.6665C12.709 1.32484 12.9923 1.0415 13.334 1.0415C13.6757 1.0415 13.959 1.32484 13.959 1.6665V4.1665C13.959 4.50817 13.6757 4.7915 13.334 4.7915Z" fill="#6E798A"/>
                                <path d="M7.08333 12.0834C6.975 12.0834 6.86667 12.0584 6.76667 12.0168C6.65833 11.9751 6.575 11.9168 6.49167 11.8418C6.34167 11.6834 6.25 11.4751 6.25 11.2501C6.25 11.1418 6.275 11.0334 6.31667 10.9334C6.35833 10.8334 6.41667 10.7418 6.49167 10.6584C6.575 10.5834 6.65833 10.5251 6.76667 10.4834C7.06667 10.3584 7.44167 10.4251 7.675 10.6584C7.825 10.8168 7.91667 11.0334 7.91667 11.2501C7.91667 11.3001 7.90833 11.3584 7.9 11.4168C7.89167 11.4668 7.875 11.5168 7.85 11.5668C7.83333 11.6168 7.80833 11.6668 7.775 11.7168C7.75 11.7584 7.70833 11.8001 7.675 11.8418C7.51667 11.9918 7.3 12.0834 7.08333 12.0834Z" fill="#6E798A"/>
                                <path d="M9.99935 12.0832C9.89102 12.0832 9.78268 12.0582 9.68268 12.0166C9.57435 11.9749 9.49102 11.9166 9.40768 11.8416C9.25768 11.6832 9.16602 11.4749 9.16602 11.2499C9.16602 11.1416 9.19102 11.0332 9.23268 10.9332C9.27435 10.8332 9.33268 10.7416 9.40768 10.6582C9.49102 10.5832 9.57435 10.5249 9.68268 10.4832C9.98268 10.3499 10.3577 10.4249 10.591 10.6582C10.741 10.8166 10.8327 11.0332 10.8327 11.2499C10.8327 11.2999 10.8243 11.3582 10.816 11.4166C10.8077 11.4666 10.791 11.5166 10.766 11.5666C10.7493 11.6166 10.7244 11.6666 10.691 11.7166C10.666 11.7582 10.6243 11.7999 10.591 11.8416C10.4327 11.9916 10.216 12.0832 9.99935 12.0832Z" fill="#6E798A"/>
                                <path d="M12.9173 12.0832C12.809 12.0832 12.7007 12.0582 12.6007 12.0166C12.4923 11.9749 12.409 11.9166 12.3257 11.8416C12.2923 11.7999 12.259 11.7582 12.2256 11.7166C12.1923 11.6666 12.1673 11.6166 12.1507 11.5666C12.1257 11.5166 12.109 11.4666 12.1007 11.4166C12.0923 11.3582 12.084 11.2999 12.084 11.2499C12.084 11.0332 12.1757 10.8166 12.3257 10.6582C12.409 10.5832 12.4923 10.5249 12.6007 10.4832C12.909 10.3499 13.2757 10.4249 13.509 10.6582C13.659 10.8166 13.7507 11.0332 13.7507 11.2499C13.7507 11.2999 13.7423 11.3582 13.734 11.4166C13.7257 11.4666 13.709 11.5166 13.684 11.5666C13.6673 11.6166 13.6423 11.6666 13.609 11.7166C13.584 11.7582 13.5423 11.7999 13.509 11.8416C13.3507 11.9916 13.134 12.0832 12.9173 12.0832Z" fill="#6E798A"/>
                                <path d="M7.08333 15C6.975 15 6.86667 14.975 6.76667 14.9333C6.66667 14.8917 6.575 14.8333 6.49167 14.7583C6.34167 14.6 6.25 14.3833 6.25 14.1667C6.25 14.0583 6.275 13.95 6.31667 13.85C6.35833 13.7417 6.41667 13.65 6.49167 13.575C6.8 13.2667 7.36667 13.2667 7.675 13.575C7.825 13.7333 7.91667 13.95 7.91667 14.1667C7.91667 14.3833 7.825 14.6 7.675 14.7583C7.51667 14.9083 7.3 15 7.08333 15Z" fill="#6E798A"/>
                                <path d="M9.99935 15C9.78268 15 9.56602 14.9083 9.40768 14.7583C9.25768 14.6 9.16602 14.3833 9.16602 14.1667C9.16602 14.0583 9.19102 13.95 9.23268 13.85C9.27435 13.7417 9.33268 13.65 9.40768 13.575C9.71602 13.2667 10.2827 13.2667 10.591 13.575C10.666 13.65 10.7243 13.7417 10.766 13.85C10.8077 13.95 10.8327 14.0583 10.8327 14.1667C10.8327 14.3833 10.741 14.6 10.591 14.7583C10.4327 14.9083 10.216 15 9.99935 15Z" fill="#6E798A"/>
                                <path d="M12.9173 14.9999C12.7007 14.9999 12.484 14.9083 12.3257 14.7583C12.2507 14.6833 12.1923 14.5916 12.1507 14.4833C12.109 14.3833 12.084 14.2749 12.084 14.1666C12.084 14.0583 12.109 13.9499 12.1507 13.8499C12.1923 13.7416 12.2507 13.6499 12.3257 13.5749C12.5173 13.3833 12.809 13.2916 13.0756 13.3499C13.134 13.3583 13.184 13.3749 13.234 13.3999C13.284 13.4166 13.334 13.4416 13.384 13.4749C13.4257 13.4999 13.4673 13.5416 13.509 13.5749C13.659 13.7333 13.7507 13.9499 13.7507 14.1666C13.7507 14.3833 13.659 14.5999 13.509 14.7583C13.3507 14.9083 13.134 14.9999 12.9173 14.9999Z" fill="#6E798A"/>
                                <path d="M17.0827 8.2002H2.91602C2.57435 8.2002 2.29102 7.91686 2.29102 7.5752C2.29102 7.23353 2.57435 6.9502 2.91602 6.9502H17.0827C17.4243 6.9502 17.7077 7.23353 17.7077 7.5752C17.7077 7.91686 17.4243 8.2002 17.0827 8.2002Z" fill="#6E798A"/>
                                <path d="M13.3333 18.9582H6.66667C3.625 18.9582 1.875 17.2082 1.875 14.1665V7.08317C1.875 4.0415 3.625 2.2915 6.66667 2.2915H13.3333C16.375 2.2915 18.125 4.0415 18.125 7.08317V14.1665C18.125 17.2082 16.375 18.9582 13.3333 18.9582ZM6.66667 3.5415C4.28333 3.5415 3.125 4.69984 3.125 7.08317V14.1665C3.125 16.5498 4.28333 17.7082 6.66667 17.7082H13.3333C15.7167 17.7082 16.875 16.5498 16.875 14.1665V7.08317C16.875 4.69984 15.7167 3.5415 13.3333 3.5415H6.66667Z" fill="#6E798A"/>
                            </svg>
                            <span>Schedules</span>
                          </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link tutor-tab-link" id="pills-two-tab" data-bs-toggle="pill" data-bs-target="#pills-two" type="button" role="tab" aria-controls="pills-two" aria-selected="false">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.1161 18.0166C14.3828 18.2333 13.5161 18.3333 12.4995 18.3333H7.49948C6.48281 18.3333 5.61615 18.2333 4.88281 18.0166C5.06615 15.85 7.29115 14.1416 9.99948 14.1416C12.7078 14.1416 14.9328 15.85 15.1161 18.0166Z" stroke="#6E798A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12.4993 1.6665H7.49935C3.33268 1.6665 1.66602 3.33317 1.66602 7.49984V12.4998C1.66602 15.6498 2.61602 17.3748 4.88268 18.0165C5.06602 15.8498 7.29102 14.1415 9.99935 14.1415C12.7077 14.1415 14.9327 15.8498 15.116 18.0165C17.3827 17.3748 18.3327 15.6498 18.3327 12.4998V7.49984C18.3327 3.33317 16.666 1.6665 12.4993 1.6665ZM9.99935 11.8082C8.34935 11.8082 7.01601 10.4665 7.01601 8.81652C7.01601 7.16652 8.34935 5.83317 9.99935 5.83317C11.6493 5.83317 12.9827 7.16652 12.9827 8.81652C12.9827 10.4665 11.6493 11.8082 9.99935 11.8082Z" stroke="#6E798A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12.9842 8.81684C12.9842 10.4668 11.6509 11.8085 10.0009 11.8085C8.35091 11.8085 7.01758 10.4668 7.01758 8.81684C7.01758 7.16684 8.35091 5.8335 10.0009 5.8335C11.6509 5.8335 12.9842 7.16684 12.9842 8.81684Z" stroke="#6E798A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>About</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                          <button class="nav-link tutor-tab-link" id="pills-three-tab" data-bs-toggle="pill" data-bs-target="#pills-three" type="button" role="tab" aria-controls="pills-three" aria-selected="false">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.9994 17.8083C10.4494 18.5416 9.54934 18.5416 8.99934 17.8083L7.74934 16.1416C7.60767 15.9583 7.30768 15.8083 7.08268 15.8083H6.66602C3.33268 15.8083 1.66602 14.9749 1.66602 10.8083V6.6416C1.66602 3.30827 3.33268 1.6416 6.66602 1.6416H13.3327C16.666 1.6416 18.3327 3.30827 18.3327 6.6416V10.8083" stroke="#6E798A" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15.1667 17.8333C16.6394 17.8333 17.8333 16.6394 17.8333 15.1667C17.8333 13.6939 16.6394 12.5 15.1667 12.5C13.6939 12.5 12.5 13.6939 12.5 15.1667C12.5 16.6394 13.6939 17.8333 15.1667 17.8333Z" stroke="#6E798A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.3333 18.3333L17.5 17.5" stroke="#6E798A" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.3301 9.16667H13.3375" stroke="#6E798A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.99607 9.16667H10.0036" stroke="#6E798A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6.66209 9.16667H6.66957" stroke="#6E798A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Reviews</span>
                          </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <!-- Shedule tab content -->
                        <div class="tab-pane fade show active" id="pills-one" role="tabpanel" aria-labelledby="pills-one-tab" tabindex="0">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-20px">
                                    <h3 class="in-title-20px lh-1">{{ get_phrase('Book a schedule') }}</h3>
                                    <form action="">
                                        <div class="d-flex flex-wrap gap-12px align-items-start">
                                            <input type="text" name="date" placeholder="Select date" class="form-control flat-date-picker date-picker-input w-277px" id="date" />
                                            <a href="#" onclick="scheduleUpdateByDate()" class="btn lms-btn-secondary">{{ get_phrase('Filter') }}</a>
                                        </div>
                                    </form>
                                </div>
                                <!-- Slider -->
                                <div id="schedule-list">
                                    @include('frontend.default.tutor_booking.schedules_tab')
                                </div>
                            </div>
                        </div>
                        <!-- About tab content -->
                        <div class="tab-pane fade" id="pills-two" role="tabpanel" aria-labelledby="pills-two-tab" tabindex="0">
                            @include('frontend.default.tutor_booking.about')
                        </div>
                        <!-- Reviews tab content -->
                        <div class="tab-pane fade" id="pills-three" role="tabpanel" aria-labelledby="pills-three-tab" tabindex="0">
                            @include('frontend.default.tutor_booking.reviews')
                        </div>
                    </div>
                 </div>
            </div>
        </div>
    </div>
</section>
<!-- Main Area End -->

@endsection
@push('js')

<script>
    function scheduleUpdateByDate(){
        // Get the value of the date input field
        const selectedDate = document.getElementById("date").value; // 2024-11-07

        // Check if a date is selected
        if (!selectedDate) {
            // If no date is selected, show an alert
            error("{{ get_phrase('Please select a date') }}");
            
        } else {
        
            // Reference the list where schedules will be displayed
            const schedulesList = $('#schedule-list');

            // Get the tutor ID from a hidden field or directly from the Blade variable
            const tutorId = "{{ $tutor_details->id }}";

            let url = "{{ route('tutor.getSchedulesByCalenderDate', ['date' => ':selectedDate', 'tutorId' => ':tutorId']) }}";
            url = url.replace(":selectedDate", selectedDate);
            url = url.replace(":tutorId", tutorId);
            
            // Make the AJAX request
            $.ajax({
                url : url,
                type: 'GET',
                success: function(response) {
                    // Replace the content of #schedules-list with the new HTML
                    schedulesList.html(response);
                },
                error: function(xhr, status, error) {
                    console.error("Error loading schedules:", error);
                }
            });
        }
    }

    $(document).on('change', 'input[name="date-check"]', function() {
        // Get the selected date value from the radio input
        const selectedDate = $(this).val(); // 08 Nov 2024

        // Get the tutor ID from a hidden field or directly from the Blade variable
        const tutorId = "{{ $tutor_details->id }}";
        
        // Reference the container where schedules will be displayed
        const schedulesContainer = $('#schedules-container');

        let url = "{{ route('tutor.getSchedulesForDate', ['date' => ':selectedDate', 'tutorId' => ':tutorId']) }}";
        url = url.replace(":selectedDate", selectedDate);
        url = url.replace(":tutorId", tutorId);
        
        // Make the AJAX request
        $.ajax({
            url : url,
            type: 'GET',
            success: function(response) {
                // Replace the content of #schedules-container with the new HTML
                schedulesContainer.html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error loading schedules:", error);
            }
        });
    });
    
</script>


@endpush