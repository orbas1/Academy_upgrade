@php
    $averageRating = $reviews->avg('rating');
    $fullStars = floor($averageRating); // Number of full stars
    $halfStar = $averageRating - $fullStars >= 0.5 ? 1 : 0; // Check if there's a half star
    $emptyStars = 5 - $fullStars - $halfStar; // Remaining stars are empty

    // Count the number of reviews for each rating
    $starCounts = [
        5 => $reviews->where('rating', 5)->count(),
        4 => $reviews->where('rating', 4)->count(),
        3 => $reviews->where('rating', 3)->count(),
        2 => $reviews->where('rating', 2)->count(),
        1 => $reviews->where('rating', 1)->count(),
    ];

    // Calculate the total number of reviews
    $totalReviews = $reviews->count();
@endphp
<div>
    <div class="d-flex align-items-center gap-30px mb-30px flex-column flex-md-row">
        <div class="tutor-review-rating">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h4 class="in-title-30px">{{ number_format($averageRating, 1) }}</h4>
                <ul class="tutor-rating-stars">
                    <!-- Display full stars -->
                    @for ($i = 0; $i < $fullStars; $i++)
                        <li>
                            <img src="{{ asset('assets/frontend/default/image/star-yellow-22.svg') }}" alt="Full Star">
                        </li>
                    @endfor
                    
                    <!-- Display half star if applicable -->
                    @if ($halfStar)
                        <li>
                            <img src="{{ asset('assets/frontend/default/image/star-half-yellow-22.svg') }}" alt="Half Star">
                        </li>
                    @endif
                    
                    <!-- Display empty stars for the remaining -->
                    @for ($i = 0; $i < $emptyStars; $i++)
                        <li>
                            <img src="{{ asset('assets/frontend/default/image/star-gray-22.svg') }}" alt="Empty Star">
                        </li>
                    @endfor
                </ul>
            </div>
            <p class="in-subtitle-16px lh-1">{{  $totalReviews.' '.get_phrase('Global Ratings') }}</p>
        </div>
        <div class="tutor-review-stars">
            <ul>
                @foreach ($starCounts as $star => $count)
                    @php
                        // Calculate the percentage for each star rating
                        $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    @endphp
                    <li class="tutor-rating-progress-wrap">
                        <h5 class="in-title-16px tutor-rating-progress-star">{{ get_phrase($star . ' Stars') }}</h5>
                        <div class="progress lms-progress tutor-rating-progress" role="progressbar" aria-label="Basic example" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                        </div>
                        <h5 class="in-title-16px">{{ $count }}</h5>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <!-- Comment Reply -->
    <div class="mb-30px">
        <div class="mb-30px">
            <div class="single-comment-wrap">
                @foreach($reviews as $review)
                @php
                    $rating = round($review->rating); // Round the rating to the nearest integer
                    $fullStars = $rating; // Number of full stars
                    $emptyStars = 5 - $fullStars; // Remaining stars will be empty
                @endphp
                <div class="single-comment">
                     <div class="d-flex align-items-center gap-12px mb-3">
                         <div class="commentator-profile">
                             <img src="{{ get_image($review->review_to_user->photo) }}" alt="">
                         </div>
                         <div>
                             <h5 class="in-title-16px mb-6px fw-semibold">{{ $review->review_to_user->name}}</h5>
                             <div class="comment-date-stars">
                                 <p class="in-subtitle-14px">{{ $review->created_at->format('d F Y') }}</p>
                                 <ul class="comment-stars-group">
                                    <!-- Display full stars -->
                                    @for ($i = 0; $i < $fullStars; $i++)
                                        <li>
                                            <img src="{{ asset('assets/frontend/default/image/star-yellow2-16.svg') }}" alt="Full Star">
                                        </li>
                                    @endfor

                                    <!-- Display empty stars for the remaining -->
                                    @for ($i = 0; $i < $emptyStars; $i++)
                                        <li>
                                            <img src="{{ asset('assets/frontend/default/image/star-gray-16.svg') }}" alt="Empty Star">
                                        </li>
                                    @endfor
                                 </ul>
                             </div>
                         </div>
                     </div>
                     <p class="in-subtitle-16px">{!! $review->review !!}</p>
                </div>
                @endforeach
             </div>
        </div>
    </div>
    @if(Auth::check() && auth()->user()->role != 'admin')
        @php
            // Check if the booking exists
            $bookingExists = App\Models\TutorBooking::where('tutor_id', $tutor_details->id)
                ->where('student_id', auth()->user()->id)
                ->exists();
            
            // Check if a review exists for this booking
            $existingReview = App\Models\TutorReview::where('tutor_id', $tutor_details->id)
                ->where('student_id', auth()->user()->id)
                ->first();
        @endphp
        @if ($bookingExists)
        <div>
            <form action="{{ route('tutor_review') }}" method="POST">
                @csrf
                <h3 class="in-title-20px mb-20px">{{ get_phrase('Student Review') }}</h3>
                <input type="hidden" name="tutor_id" value="{{ $tutor_details->id }}">
                <div class="mb-20px">
                    <label class="form-label lms-form-label mb-3">{{ get_phrase('Rating') }}</label>
                    <select class="lms-select lms-md-select lms-form-control max-w-469px" name="rating">
                        <option value="1" {{ (optional($existingReview)->rating == 1) ? 'selected' : '' }}>{{ get_phrase('One') }}</option>
                        <option value="2" {{ (optional($existingReview)->rating == 2) ? 'selected' : '' }}>{{ get_phrase('Two') }}</option>
                        <option value="3" {{ (optional($existingReview)->rating == 3) ? 'selected' : '' }}>{{ get_phrase('Three') }}</option>
                        <option value="4" {{ (optional($existingReview)->rating == 4) ? 'selected' : '' }}>{{ get_phrase('Four') }}</option>
                        <option value="5" {{ (optional($existingReview)->rating == 5) ? 'selected' : '' }}>{{ get_phrase('Five') }}</option>
                    </select>
                </div>
                <div class="mb-20px">
                    <label class="form-label lms-form-label mb-3" for="textarea">{{ get_phrase('Review') }}</label>
                    <textarea id="textarea" class="form-control lms-form-control" name="review">{{ old('review', optional($existingReview)->review) }}</textarea>
                </div>
                <button type="submit" class="btn btn-purple-md">
                    {{ $existingReview ? get_phrase('Update') : get_phrase('Submit') }}
                </button>
            </form>
        </div>
        @endif
    @endif
</div>