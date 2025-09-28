{{-- @php 
    $ebook = App\Models\Ebook::where('id', $id)->first();
    $file = $ebook->preview ?? null;
    $fileUrl = $file ? asset($file) : null;
@endphp

@if ($file && Str::endsWith($file, ['.pdf']))
    <iframe src="{{ $fileUrl }}#toolbar=0" width="100%" height="500px" frameborder="0"></iframe>
@elseif ($file && Str::endsWith($file, ['.jpg', '.jpeg', '.png', '.webp']))
    <img src="{{ $fileUrl }}" alt="Ebook Image" style="max-width: 100%; height: auto;">
@else
    <p>{{ get_phrase('No preview available.') }}</p>
@endif --}}
@php 
    $ebook = App\Models\Ebook::where('id', $id)->first();
    $file = $ebook->preview ?? null;
    $fileUrl = $file ? asset($file) : null;
@endphp

@if ($file && \Illuminate\Support\Str::endsWith($file, ['.pdf']))
    <iframe 
        src="{{ $fileUrl }}#toolbar=0" 
        width="100%" 
        height="500px" 
        frameborder="0"
        style="border:none;"
        >
    </iframe>
@elseif ($file && \Illuminate\Support\Str::endsWith($file, ['.jpg', '.jpeg', '.png', '.webp']))
    <img src="{{ $fileUrl }}" alt="Ebook Image" style="max-width: 100%; height: auto;">
@else
    <p>{{ get_phrase('No preview available.') }}</p>
@endif
