@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-inner">

        <div class="mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>@lang('Você pode usar os códigos HTML abaixo em seu site. Esses códigos HTML ajudarão a aumentar suas referências.')</p>
        </div>

        <hr>

        <div class="row">
            @foreach($banners as $banner)
                <div class="col-md-4">
                    <div class="card">
                        <div class="thumb__350px">
                            <img src="{{ getImage(fileManager()->promotions()->path.  '/'. @$banner->banner) }}" class="w-100">
                        </div>
                        <div class="referral-form mt-20 ">

                            @php
                                $string = '<a href="'.route('home').'?reference='.auth()->user()->username.'" target="_blank"> <img src="'.getImage(fileManager()->promotions()->path.  '/'. @$banner->banner) .'" alt="imagem"/></a>';
                            @endphp

                            <textarea type="url" id="reflink{{ $banner->id }}" class="form--control form-control from-control-lg refCode" rows="5" readonly>@php echo $string @endphp</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

@endsection
@push('style')
    <style>
        .referral-form{
            position: relative;
            cursor: pointer;
            margin-top: 10px;
        }
        .referral-form textarea{
            overflow: hidden;
            border-radius: 0;
        }
        .referral-form:before {
            content: 'Copiar';
            position: absolute;
            height: 0%;
            width: 100%;
            background: #222034d6;
            overflow: hidden;
            color: #fff;
            cursor: pointer;
            text-align: center;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 35px;
        }
        .referral-form:hover:before{
            height: 100%;
        }
        .referral-form.copied-referral:before{
            height: 100%;
            content: 'Copiado';
        }
    </style>
@endpush
@push('script')
<script>
    $('.referral-form').click(function () {
        var text = $('.referral-form').find('.refCode').val();
        var vInput = document.createElement("input");
        vInput.value = text;
        document.body.appendChild(vInput);
        vInput.select();
        document.execCommand("copy");
        document.body.removeChild(vInput);
        $(this).addClass('copied-referral');
        setTimeout(() => {
            $(this).removeClass('copied-referral');
        }, 1000);
    });

</script>
@endpush
