@extends('layouts.default')
@push('title', get_phrase('My profile'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <!------------ My profile area start  ------------>
    <section class="course-content">
        <div class="profile-banner-area"></div>
        <div class="container profile-banner-area-container">
            <div class="row">
                @include('frontend.default.student.left_sidebar')
                <div class="col-lg-9">

                    <div class="my-panel message-panel edit_profile mb-4">
                        <h4 class="g-title mb-5">{{ get_phrase('Personal Information') }}</h4>
                        <form action="{{ route('update.profile', $user_details->id) }}" method="POST">@csrf
                            <div class="row">
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label for="name" class="form-label">{{ get_phrase('Full Name') }}</label>
                                        <input type="text" class="form-control" name="name" value="{{ $user_details->name }}" id="name">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="email" class="form-label">{{ get_phrase('Email Address') }}</label>
                                        <input type="email" class="form-control" name="email" value="{{ $user_details->email }}" id="email">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">{{ get_phrase('Phone Number') }}</label>
                                        <input type="tel" class="form-control" name="phone" value="{{ $user_details->phone }}" id="phone">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="website" class="form-label">{{ get_phrase('Website') }}</label>
                                        <input type="text" class="form-control" name="website" value="{{ $user_details->website }}" id="website">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="facebook" class="form-label">{{ get_phrase('Facebook') }}</label>
                                        <input type="text" class="form-control" name="facebook" value="{{ $user_details->facebook }}" id="facebook">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="twitter" class="form-label">{{ get_phrase('Twitter') }}</label>
                                        <input type="text" class="form-control" name="twitter" value="{{ $user_details->twitter }}" id="twitter">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-20">
                                    <div class="form-group">
                                        <label for="linkedin" class="form-label">{{ get_phrase('Linkedin') }}</label>
                                        <input type="text" class="form-control" name="linkedin" value="{{ $user_details->linkedin }}" id="linkedin">
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label for="skills" class="form-label">{{ get_phrase('Skills') }}</label>
                                        <input type="text" class="form-control tagify" name="skills" data-role="tagsinput" value="{{ $user_details->skills }}" id="skills">
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label for="biography" class="form-label">{{ get_phrase('Biography') }}</label>
                                        <textarea name="biography" class="form-control" id="biography" cols="30" rows="5">{{ $user_details->biography }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <button class="eBtn btn gradient mt-10">{{ get_phrase('Save Changes') }}</button>
                        </form>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="my-panel message-panel edit_profile">
                        <h4 class="g-title mb-5">{{ get_phrase('Change Password') }}</h4>
                        <form action="{{ route('password.change') }}" method="POST">@csrf
                            <div class="row">
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label class="form-label">{{ get_phrase('Current password') }}</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label class="form-label">{{ get_phrase('New password') }}</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-20">
                                    <div class="form-group">
                                        <label class="form-label">{{ get_phrase('Confirm password') }}</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            <button class="eBtn btn gradient mt-10">{{ get_phrase('Update password') }}</button>
                        </form>
                    </div>

                    <div class="my-panel message-panel edit_profile">
                        <h4 class="g-title mb-4">{{ get_phrase('Account Security') }}</h4>

                        <div class="mb-4">
                            <h5 class="mb-3">{{ get_phrase('Two-Factor Authentication') }}</h5>

                            @if ($two_factor_enabled)
                                <p class="text-success">{{ get_phrase('Two-factor authentication is currently enabled for your account.') }}</p>

                                <form action="{{ route('two-factor.disable') }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        {{ get_phrase('Disable Two-Factor Authentication') }}
                                    </button>
                                </form>

                                <form action="{{ route('two-factor.recovery') }}" method="POST" class="d-inline ms-2">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        {{ get_phrase('Regenerate Recovery Codes') }}
                                    </button>
                                </form>

                                @if (!empty($two_factor_recovery_codes))
                                    <div class="alert alert-warning mt-3">
                                        <p class="mb-2">{{ get_phrase('Store these recovery codes safely. Each code can be used once if you lose access to your authenticator device.') }}</p>
                                        <ul class="mb-0 list-unstyled recovery-codes">
                                            @foreach ($two_factor_recovery_codes as $recoveryCode)
                                                <li><code>{{ $recoveryCode }}</code></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @else
                                @if ($two_factor_secret)
                                    <p class="mb-3">{{ get_phrase('Scan the QR code with your authenticator app or enter the secret key manually. Then confirm using a 6-digit code.') }}</p>

                                    @if ($two_factor_qr)
                                        <div class="mb-3">
                                            <img src="https://chart.googleapis.com/chart?chs=200x200&amp;cht=qr&amp;chl={{ urlencode($two_factor_qr) }}" alt="{{ get_phrase('Authenticator QR code') }}" class="img-fluid border rounded">
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <span class="d-block fw-semibold">{{ get_phrase('Manual entry key') }}:</span>
                                        <code>{{ $two_factor_secret }}</code>
                                    </div>

                                    <form action="{{ route('two-factor.confirm') }}" method="POST" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-8">
                                            <label for="two_factor_code" class="form-label">{{ get_phrase('Authentication code') }}</label>
                                            <input type="text" name="code" id="two_factor_code" class="form-control" required autofocus>
                                            @error('code')
                                                <span class="text-danger small">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="eBtn btn gradient w-100">{{ get_phrase('Confirm & Enable') }}</button>
                                        </div>
                                    </form>

                                    <form action="{{ route('two-factor.disable') }}" method="POST" class="mt-3">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">{{ get_phrase('Cancel setup') }}</button>
                                    </form>
                                @else
                                    <p>{{ get_phrase('Add an extra layer of security by enabling time-based one-time passwords (TOTP).') }}</p>
                                    <form action="{{ route('two-factor.prepare') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="eBtn btn gradient">{{ get_phrase('Enable Two-Factor Authentication') }}</button>
                                    </form>
                                @endif
                            @endif
                        </div>

                        <div>
                            <h5 class="mb-3">{{ get_phrase('Trusted Devices & Active Sessions') }}</h5>
                            <p class="text-muted">{{ get_phrase('Devices marked as trusted will skip two-factor prompts for :days days unless you revoke trust or reset your settings.', ['days' => $trusted_ttl_days]) }}</p>

                            @if ($device_sessions->isEmpty())
                                <p class="text-muted mb-0">{{ get_phrase('No active devices found for your account.') }}</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>{{ get_phrase('Device Token') }}</th>
                                                <th>{{ get_phrase('IP Address') }}</th>
                                                <th>{{ get_phrase('Last Active') }}</th>
                                                <th>{{ get_phrase('Trusted') }}</th>
                                                <th class="text-end">{{ get_phrase('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($device_sessions as $session)
                                                <tr>
                                                    <td>
                                                        <span class="d-block fw-semibold">{{ $session->label ?? get_phrase('Device') . ' #' . $loop->iteration }}</span>
                                                        <small class="text-muted">{{ $session->user_agent }}</small>
                                                    </td>
                                                    <td>{{ $session->ip_address ?? get_phrase('Unknown') }}</td>
                                                    <td>
                                                        @if ($session->last_seen_at)
                                                            {{ $session->last_seen_at->diffForHumans() }}
                                                        @else
                                                            {{ get_phrase('N/A') }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($session->trusted_at)
                                                            <span class="badge bg-success">{{ get_phrase('Trusted') }}</span>
                                                            <small class="d-block text-muted">{{ get_phrase('Since :time', ['time' => $session->trusted_at->diffForHumans()]) }}</small>
                                                        @else
                                                            <span class="badge bg-secondary">{{ get_phrase('Not trusted') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex gap-2">
                                                            <form action="{{ route('devices.destroy', $session->id) }}" method="POST" onsubmit="return confirm('{{ get_phrase('Sign out this device?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">{{ get_phrase('Sign out') }}</button>
                                                            </form>

                                                            <form action="{{ route('devices.trust', $session->id) }}" method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="trusted" value="{{ $session->trusted_at ? 0 : 1 }}">
                                                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                                    {{ $session->trusted_at ? get_phrase('Revoke trust') : get_phrase('Trust device') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!------------ My profile area end  ------------>
@endsection
@push('js')

@endpush
