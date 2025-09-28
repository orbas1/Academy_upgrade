@extends('layouts.instructor')

@push('title', get_phrase('Manage Schedules'))

@push('meta')
@endpush

@push('css')
    <style>
        body {
            margin: 5px;
            padding: 0;
            font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
            font-size: 14px;
        }

        #calendar {
            margin: 0 auto;
        }
    </style>
@endpush



@section('content')
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-12px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('Manage Schedules') }}
                </h4>

                <a href="{{ route('instructor.add_schedule') }}" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px">
                    <span class="fi-rr-plus"></span>
                    <span>{{ get_phrase('Add schedule') }}</span>
                </a>

            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-12">
            <div class="ol-card p-4">
                <div class="ol-card-body">
                    <div class="col-md-12 pb-3">
                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')

    

<script type="text/javascript">
  
    "use strict";

    document.addEventListener('DOMContentLoaded', function() {
        var initialLocaleCode = 'en';
        var localeSelectorEl = document.getElementById('locale-selector');
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            // headerToolbar: {
            //     left: 'prev,next today',
            //     center: 'title',
            //     right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            // },
            initialDate: '{{ date('Y-m-d') }}',
            navLinks: true, // can click day/week names to navigate views
            selectable: true,
            selectMirror: true,
            select: function(arg) {
                
            },
            eventClick: function(arg) {

                // Format the date to '12-Feb-24'
                let options = { day: '2-digit', month: 'short', year: '2-digit' };
                let eventDate = arg.event.start.toLocaleDateString('en-GB', options).replace(/ /g, '-');

                // Generate the URL with the event date
                let url = "{{ route('instructor.manage_schedules_by_date', ['date' => ':eventDate']) }}";
                url = url.replace(":eventDate", eventDate);
                
                // Navigate to the URL
                window.location.href = url;
                
            },
            editable: false,
            dayMaxEvents: true, // allow "more" link when too many events
            events: <?php echo $schedules; ?>
        });

        calendar.render();

        // build the locale selector's options
        calendar.getAvailableLocaleCodes().forEach(function(localeCode) {
            var optionEl = document.createElement('option');
            optionEl.value = localeCode;
            optionEl.selected = localeCode == initialLocaleCode;
            optionEl.innerText = localeCode;
            localeSelectorEl.appendChild(optionEl);
        });

        // when the selected option changes, dynamically change the calendar option
        localeSelectorEl.addEventListener('change', function() {
            if (this.value) {
                calendar.setOption('locale', this.value);
            }
        });

    });

</script>
@endpush
