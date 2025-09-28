<div class="pb-3 mb-20px lms2-border-bottom">
    <form action="">
        <div class="date-swiper-main">
            <div class="date-slider swiper">
                <div class="swiper-wrapper" id="date-swiper">
                    @foreach($dateSwiperData as $date)
                    <div class="swiper-slide">
                        <div class="date-check-single">
                            <!-- Include year in the value -->
                            <input type="radio" class="date-check-input" name="date-check" id="date{{ $date['day'] }}" value="{{ $date['day'] }} {{ $date['month'] }} {{ $date['year'] }}" {{ $date['isToday'] ? 'checked' : '' }}>
                            <label class="date-check-btn" for="date{{ $date['day'] }}">
                                <span class="date-checkbox-date">{{ $date['day'] }} {{ $date['month'] }}</span>
                                <span class="date-checkbox-day">{{ $date['dayName'] }}</span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <!-- Add Arrows -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </form>
</div>
<!-- Courses -->
<div id="schedules-container">
    <div class="row g-3">
        @include('frontend.default.tutor_booking.schedules')
    </div>
</div>

