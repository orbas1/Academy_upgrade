@if(session()->has('locale.updated'))
    <div
        data-locale-flash
        class="locale-flash-message"
    >
        {{ session('locale.updated') }}
    </div>
    @once
        @push('css')
            <style>
                .locale-flash-message {
                    position: fixed;
                    bottom: 1.5rem;
                    inset-inline-start: 50%;
                    transform: translateX(-50%);
                    background-color: rgba(34, 197, 94, 0.95);
                    color: #0f172a;
                    padding: 0.75rem 1.5rem;
                    border-radius: 9999px;
                    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
                    font-weight: 600;
                    z-index: 1000;
                }

                [dir="rtl"] .locale-flash-message {
                    inset-inline-end: 50%;
                    inset-inline-start: auto;
                }
            </style>
        @endpush
    @endonce
@endif
