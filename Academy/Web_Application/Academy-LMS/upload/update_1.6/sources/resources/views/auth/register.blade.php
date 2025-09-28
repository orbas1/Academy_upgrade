@extends('layouts.' . get_frontend_settings('theme'))
@push('title', get_phrase('Sign Up'))
@push('meta')@endpush
@push('css')
    <style>
        .form-icons .right {
            right: 20px;
            cursor: pointer !important;
        }
    </style>
@endpush
@section('content')
    <section class="login-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 col-md-6">
                    <div class="login-img">
                        <img src="{{ asset('assets/frontend/' . get_frontend_settings('theme') . '/image/signup.gif') }}" alt="register-banner">
                    </div>
                </div>
                <div class="col-lg-5 col-md-6">
                    <form action="{{ route('register') }}" class="global-form login-form mt-25" id="login-form" method="post" enctype="multipart/form-data">@csrf
                        <h4 class="g-title">{{ get_phrase('Sign Up') }}</h4>
                        <p class="description">{{ get_phrase('See your growth and get consulting support! ') }}</p>
                        <div class="form-group mb-5">
                            <label for="" class="form-label">{{ get_phrase('Name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="Your Name">

                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group mb-5">
                            <label for="" class="form-label">{{ get_phrase('Email') }}</label>
                            <input type="email" name="email" class="form-control" placeholder="Your Email">

                            @error('email')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group mb-5">
                            <label for="" class="form-label">{{ get_phrase('Password') }}</label>
                            <input type="password" name="password" class="form-control" placeholder="*********">

                            @error('password')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        @if (get_settings('allow_instructor'))
                            <div class="form-group mb-5">
                                <input id="instructor" type="checkbox" name="instructor">
                                <label for="instructor">{{ get_phrase('Apply to Become an instructor') }}</label>
                            </div>

                            <div id="become-instructor-fields" class="d-none">
                                <div class="form-group mb-5">
                                    <label for="phone" class="form-label">{{ get_phrase('Phone') }}</label>
                                    <input class="form-control" id="phone" type="phone" name="phone" placeholder="{{ get_phrase('Enter your phone number') }}" required>
                                </div>
                                <div class="form-group mb-5">
                                    <label for="document" class="form-label">{{ get_phrase('Document') }} <small>(doc, docs, pdf, txt, png, jpg, jpeg)</small></label>
                                    <input class="form-control" id="document" type="file" name="document">
                                    <small>{{ get_phrase('Provide some documents about your qualifications') }}</small>
                                </div>
                                <div class="form-group mb-5">
                                    <label for="description" class="form-label">{{ get_phrase('Message') }}</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                            </div>
                        @endif

                        @if (get_frontend_settings('recaptcha_status'))
                            <button class="eBtn gradient w-100 g-recaptcha" data-sitekey="{{ get_frontend_settings('recaptcha_sitekey') }}" data-callback='onLoginSubmit' data-action='submit'>{{ get_phrase('Sign Up') }}</button>
                        @else
                            <button type="submit" class="eBtn gradient w-100">{{ get_phrase('Sign Up') }}</button>
                        @endif

                        <p class="mt-20">{{ get_phrase('Already have account?') }} <a href="{{ route('login') }}">{{ get_phrase('Sign in') }}</a></p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('js')
    <script>
        "use strict";

        $(document).ready(function() {
            $('#showpassword').on('click', function(e) {
                e.preventDefault();
                const type = $('#password').attr('type');

                if (type == 'password') {
                    $('#password').attr('type', 'text');
                } else {
                    $('#password').attr('type', 'password');
                }
            });
        });

        $(document).ready(function() {
            $('#showcpassword').on('click', function(e) {
                e.preventDefault();
                const type = $('#cpassword').attr('type');

                if (type == 'password') {
                    $('#cpassword').attr('type', 'text');
                } else {
                    $('#cpassword').attr('type', 'password');
                }
            });
        });

        function onLoginSubmit(token) {
            document.getElementById("login-form").submit();
        }

        $(document).ready(function() {
            $('#instructor').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#become-instructor-fields').removeClass('d-none');
                } else {
                    $('#become-instructor-fields').addClass('d-none');
                }
            });
        });
    </script>
@endpush
