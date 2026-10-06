@extends($activeTemplate . 'layouts.app')
@section('panel')

    @yield('content')

    @php
        $content = getContent('footer.content', true);
    @endphp

    @if($content)
    <div class="footer-section" style="background: #1a1a2e; color: rgba(255,255,255,0.6); padding: 30px 0; text-align: center;">
        <div class="container">
            {!! __(@$content->data_values->content ?? '') !!}
        </div>
    </div>
    @endif

@endsection
