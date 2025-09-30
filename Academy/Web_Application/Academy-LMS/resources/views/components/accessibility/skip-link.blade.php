@once
    @push('css')
        <style>
            .skip-link {
                position: absolute;
                left: -999px;
                top: 1rem;
                padding: 0.75rem 1.25rem;
                background-color: #1a1a1a;
                color: #ffffff;
                z-index: 1000;
                border-radius: 0.5rem;
                transition: left 0.2s ease-in-out;
                text-decoration: none;
                box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.25);
            }

            .skip-link:focus {
                left: 1rem;
                outline: 3px solid #f3c623;
                outline-offset: 4px;
            }
        </style>
    @endpush
@endonce

<a class="skip-link" href="#main-content">{{ __('layout.skip_to_content') }}</a>
