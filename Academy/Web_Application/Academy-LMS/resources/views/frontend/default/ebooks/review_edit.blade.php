@php
    $review = App\Models\EbookReview::where('id', $id)
        ->where('user_id', auth()->user()->id)
        ->first();
@endphp
@if ($review)
    <div class="write-review-edit mb-5">
        <form action="{{ route('ebook.review.update', $id) }}" method="POST">@csrf
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="description">{{ get_phrase('Rate this course : ') }}</p>
                <div class="d-flex align-items-center justify-content-end gap-4">
                    <ul class="d-flex gap-1 rating-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <li>
                                <i class="@if ($i <= $review->rating) fa @else fa-regular @endif fa-star rating-star"
                                    id="star-{{ $i }}"></i>
                            </li>
                        @endfor
                    </ul>
                    <span class="gradient" id="remove-stars">{{ get_phrase('Remove all') }}</span>
                </div>
            </div>

            <input type="hidden" name="rating" value="0">
            <input type="hidden" name="ebook_id" value="{{ $review->ebook_id }}">
            <textarea type="text" name="review" class="form-control mb-3" rows="5"
                placeholder="{{ get_phrase('Write a reveiw ...') }}"required>{{ $review->review }}</textarea>
            <input type="submit" class="eBtn gradient border-none w-100" value="{{ get_phrase('Update') }}">
        </form>
    </div>
@else
    <p class="text-center">{{ get_phrase('Data not found.') }}</p>
@endif
<!-- Jquery Js -->
<script src="{{ asset('assets/global/course_player/js/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        let ratingStars = $('.rating-stars i');

        ratingStars.click(function(e) {
            e.preventDefault();
            let star = $(this).attr('id').substring(5);

            $('.write-review-edit input[name="rating"]').val(star);

            ratingStars.removeClass('fa').addClass('fa-regular');
            for (let i = 1; i <= star; i++) {
                $('#star-' + i).removeClass('fa-regular').addClass('fa');
            }
        });

        $('#remove-stars').click(function(e) {
            e.preventDefault();
            ratingStars.removeClass('fa').addClass('fa-regular');
            $('.write-review-edit input[name="rating"]').val(0);
        });
    });
</script>
