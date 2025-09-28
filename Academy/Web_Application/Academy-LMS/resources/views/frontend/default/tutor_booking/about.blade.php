<div class="mb-30px">
    <h3 class="mb-12px in-title-20px lh-1">{{ get_phrase('About Me') }}</h3>
    <p class="mb-3 in-subtitle-16px">{{ Str::limit(strip_tags($tutor_details->biography), 400) }}</p>
    {{-- <a href="#" class="icontext-link">
        <span>See more</span>
        <span class="fi-rr-angle-small-right"></span>
    </a> --}}
</div>
@php
$educations = json_decode($tutor_details->educations, true);
@endphp
@if(isset($educations))
<div>
    <h3 class="mb-4 in-title-20px lh-1">{{get_phrase('Education')}}</h3>
    @foreach ($educations as $key => $education)
    <div class="pb-20px mb-20px lms-border-bottom">
        @php
            // Use Carbon to parse the dates
            $startDate = \Carbon\Carbon::parse($education['start_date']);
            $endDate = isset($education['end_date']) ? \Carbon\Carbon::parse($education['end_date']) : null;
            // Get the years
            $startYear = $startDate->format('Y');
            $endYear = $endDate ? $endDate->format('Y') : 'Present'; // Show 'Present' if end_date is null
        @endphp
        <h4 class="mb-3 in-title-16px">{{ $startYear }} - {{ $endYear }}</h4>
        <h5 class="mb-3 in-title-16px">{{ $education['title'] }}</h5>
        <ul class="d-flex flex-wrap gap-12px mb-12px">
            <li class="d-flex align-items-center gap-5px">
                <img src="{{ asset('assets/frontend/default/image/assientment-gray-18.svg') }}" alt="">
                <span class="in-title-14px">{{ $education['institute'] }}</span>
            </li>
            <li class="d-flex align-items-center gap-5px">
                <img src="{{ asset('assets/frontend/default/image/location-gray-18.svg') }}" alt="">
                <span class="in-title-14px">{{ $education['city'].', '.$education['country'] }}</span>
            </li>
        </ul>
        <p class="in-subtitle-16px mb-3">{!! $education['description'] !!}</p>
    </div>
    @endforeach
</div>
@endif