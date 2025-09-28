<script>
    "use strict";

    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": '{{ csrf_token() }}'
            }
        });

        $('.flexCheckChecked').on('change', function(e) {
            const id = $(this).attr('id');
            const Elem = $(this);

            Elem.attr('disabled', true);

            $.ajax({
                url: "{{ route('set.watch.history') }}", // Your route
                type: "post",
                data: {
                    lesson_id: id,
                    course_id: "{{ $course_details->id }}"
                },
                success: function(response) {
                    Elem.attr('disabled', false);
                },
                error: function(xhr, status, error) {
                    Elem.attr('disabled', false);
                    console.error("Error updating watch history:", xhr.responseText);
                }
            });
        });

        $('#fullscreen').on('click', function(e) {
            e.preventDefault();
            $('#player_content').toggleClass('col-lg-8 col-12');
            $('#player_side_bar').toggleClass('col-lg-4 col-12');
        });

        function initializeSummernote() {
            $('textarea#summernote').summernote({
                height: 180, // set editor height
                minHeight: null, // set minimum height of editor
                maxHeight: null, // set maximum height of editor
                focus: true, // set focus to editable area after initializing summernote
                toolbar: [
                    ['color', ['color']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['para', ['ul', 'ol']],
                    ['table', ['table']],
                    ['insert', ['link']]
                ]
            });
        }

        initializeSummernote();
    });

    var formElement;
    if ($('.ajaxForm:not(.initialized)').length > 0) {
        $('.ajaxForm:not(.initialized)').ajaxForm({
            beforeSend: function(data, form) {
                var formElement = $(form);
            },
            uploadProgress: function(event, position, total, percentComplete) {},
            complete: function(xhr) {

            },
            error: function(e) {
                console.log(e);
            }
        });
        $('.ajaxForm:not(.initialized)').addClass('initialized');
    }

    $('.tagify:not(.inited)').each(function(index, element) {
        var tagify = new Tagify(element, {
            placeholder: '{{ get_phrase('Enter your keywords') }}',
            delimiters: "~",
        });
        $(element).addClass('inited');
    });

    $(document).ready(function() {
        var iframeWidth = $('.embed-responsive-item').width();
        console.log(iframeWidth)
        var iframeHeight = (iframeWidth/100)*56;
        console.log(iframeHeight)
        $('.embed-responsive-item').height(iframeHeight+'px');
    });
</script>

@if (get_player_settings('watermark_type') == 'js')
    <script>
        // append watermark in player
        function prependWatermark() {
            $.ajax({
                type: "get",
                url: "{{ route('player.prepend.watermark') }}",
                success: function(response) {
                    if (response) {
                        $('.plyr__video-wrapper').prepend(response);
                    }
                }
            });
        }

        setInterval(() => {
            if($('.plyr__video-wrapper .watermark-container').length == 0){
                prependWatermark();
            }
        }, 5000);
    </script>
@endif
