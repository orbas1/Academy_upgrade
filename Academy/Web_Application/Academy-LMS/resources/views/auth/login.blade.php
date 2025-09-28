@extends('layouts' . '.' . get_frontend_settings('theme'))
@push('title', get_phrase('Log In'))
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
    @if (get_frontend_settings('theme') == 'default')
        <!------------------- Login Area Start  ------>
        <section class="login-area">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7 col-md-6">
                        <div class="login-img">
                            <img src="{{ asset('assets/frontend/default/image/login.gif') }}" alt="...">
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <form action="{{ route('login') }}" class="global-form login-form mt-25" id="login-form" method="POST">
                            @csrf
                            <h4 class="g-title">{{ get_phrase('Login') }}</h4>
                            <p class="description">{{ get_phrase('See your growth and get consulting support!') }} </p>
                             <input type="hidden" id="user_agent" name="user_agent"  >
                            <div class="form-group">
                                <label for="email" class="form-label">{{ get_phrase('Email') }}</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="{{ get_phrase('Your Email') }}">
                            </div>
                            <div class="form-group">
                                <label for="" class="form-label">{{ get_phrase('Password') }}</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="*********">
                            </div>
                            <div class="form-group mb-25 d-flex justify-content-between align-items-center remember-me">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked>
                                    <label class="form-check-label" for="flexCheckChecked">{{ get_phrase('Remember Me') }}</label>
                                </div>
                                <a href="{{route('password.request')}}">{{ get_phrase('Forget Password?') }}</a>
                            </div>

                            @if(get_frontend_settings('recaptcha_status'))
                                <button class="eBtn gradient w-100 g-recaptcha" data-sitekey="{{ get_frontend_settings('recaptcha_sitekey') }}" data-callback='onLoginSubmit' data-action='submit'>{{ get_phrase('Login') }}</button>
                            @else
                                <button type="submit" class="eBtn gradient w-100">{{ get_phrase('Login') }}</button>
                            @endif

                            <p class="mt-20">{{ get_phrase('Not have an account yet?') }}
                                <a href="{{ route('register.form') }}">{{ get_phrase('Create Account') }}</a>
                            </p>

                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!------------------- Login Area End  ------>
    @endif
@endsection
@push('js')

    <script>
        "use strict";

        $(document).ready(function() {
            $('.custom-btn').on('click', function(e) {
                e.preventDefault();

                var role = $(this).attr('id');
                if (role == 'admin') {
                    $('#email').val('admin@example.com');
                    $('#password').val('12345678');
                } else if (role == 'student') {
                    $('#email').val('student@example.com');
                    $('#password').val('12345678');
                } else {
                    $('#email').val('instructor@example.com');
                    $('#password').val('12345678');
                }
                $('#login').trigger('click');
            });
        });

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

        function onLoginSubmit(token) {
            document.getElementById("login-form").submit();
        }

    </script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Check if the device token already exists
        if (!localStorage.getItem('device_token')) {
            localStorage.setItem('device_token', crypto.randomUUID());
        }
        const deviceToken = localStorage.getItem('device_token');
        document.getElementById('user_agent').value = deviceToken;
    });
</script>



@endpush
