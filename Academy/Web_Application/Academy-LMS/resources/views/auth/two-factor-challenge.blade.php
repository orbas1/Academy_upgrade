@extends('layouts.' . get_frontend_settings('theme'))
@push('title', get_phrase('Two-Factor Authentication'))
@section('content')
    <section class="login-area">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <form action="{{ route('two-factor.challenge.store') }}" method="POST" class="global-form login-form mt-25">
                        @csrf
                        <h4 class="g-title">{{ get_phrase('Verify your identity') }}</h4>
                        <p class="description">{{ get_phrase('Enter the 6-digit code from your authenticator app or a recovery code to continue.') }}</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <div class="form-group">
                            <label for="code" class="form-label">{{ get_phrase('Authenticator or recovery code') }}</label>
                            <input type="text" id="code" name="code" class="form-control" required autofocus autocomplete="one-time-code">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="remember_device" name="remember_device" value="1">
                            <label class="form-check-label" for="remember_device">
                                {{ get_phrase('Trust this device for future logins') }}
                            </label>
                        </div>

                        <button type="submit" class="eBtn gradient w-100">{{ get_phrase('Verify & Continue') }}</button>

                        <p class="mt-20">
                            {{ get_phrase('Lost access?') }}
                            <a href="{{ route('login') }}">{{ get_phrase('Return to login') }}</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
