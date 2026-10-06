@extends($activeTemplate.'layouts.frontend')
@section('content')

@php
    $banner = getContent('banner.content', true) ?? null;
    $howItWorks = getContent('how_it_work.content', true) ?? null;
    $howItWorksItems = getContent('how_it_work.element', null, false, true) ?? collect();
    $faqs = getContent('faq.element', null, false, true) ?? collect();
    $faqContent = getContent('faq.content', true) ?? null;
    $contact = getContent('contact.content', true) ?? null;
    $contactItems = getContent('contact.element', null, false, true) ?? collect();
    $logo = getImage(getFilePath('logoIcon').'/logo_2.webp');
@endphp

<style>
    .hero-section {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        min-height: 60vh;
        display: flex;
        align-items: center;
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }
    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 600px;
        height: 600px;
        border-radius: 50%;
        background: rgba(255,255,255,0.03);
    }
    .hero-title {
        font-size: 42px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
    }
    .hero-title span {
        color: #9400FF;
    }
    .hero-subtitle {
        font-size: 18px;
        color: rgba(255,255,255,0.7);
        margin-top: 20px;
    }
    .hero-btn {
        display: inline-block;
        padding: 14px 40px;
        background: linear-gradient(135deg, #9400FF, #7B2FBE);
        color: #fff;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        margin-top: 30px;
        transition: all 0.3s;
        border: none;
    }
    .hero-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(148,0,255,0.3);
        color: #fff;
        text-decoration: none;
    }
    .hero-btn-outline {
        display: inline-block;
        padding: 14px 40px;
        background: transparent;
        color: #fff;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        margin-top: 30px;
        transition: all 0.3s;
        border: 2px solid rgba(255,255,255,0.3);
        margin-left: 15px;
    }
    .hero-btn-outline:hover {
        border-color: #fff;
        color: #fff;
        text-decoration: none;
    }
    .section-title {
        font-size: 32px;
        font-weight: 700;
        color: #1a1a2e;
        text-align: center;
        margin-bottom: 15px;
    }
    .section-subtitle {
        font-size: 16px;
        color: #6c757d;
        text-align: center;
        margin-bottom: 50px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    .step-card {
        text-align: center;
        padding: 30px 20px;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s;
        height: 100%;
    }
    .step-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    .step-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
        overflow: hidden;
        background: #f0e6ff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .step-icon img {
        width: 50px;
        height: 50px;
        object-fit: contain;
    }
    .step-number {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #9400FF, #7B2FBE);
        color: #fff;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    .step-title {
        font-size: 18px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 10px;
    }
    .step-desc {
        font-size: 14px;
        color: #6c757d;
    }
    .faq-item {
        background: #fff;
        border-radius: 12px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .faq-question {
        padding: 18px 24px;
        cursor: pointer;
        font-weight: 600;
        color: #1a1a2e;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }
    .faq-question:hover {
        background: #f8f9fa;
    }
    .faq-question::after {
        content: '+';
        font-size: 22px;
        color: #9400FF;
        font-weight: 700;
    }
    .faq-question.active::after {
        content: '-';
    }
    .faq-answer {
        padding: 0 24px 18px;
        color: #6c757d;
        display: none;
        line-height: 1.8;
    }
    .faq-answer.show {
        display: block;
    }
    .contact-card {
        background: linear-gradient(135deg, #1a1a2e, #0f3460);
        border-radius: 16px;
        padding: 40px;
        color: #fff;
        text-align: center;
    }
    .contact-icon {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 24px;
    }
    .footer-section {
        background: #1a1a2e;
        color: rgba(255,255,255,0.6);
        padding: 30px 0;
        text-align: center;
        margin-top: 60px;
    }
    .feature-box {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .feature-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    .feature-box i {
        font-size: 36px;
        color: #9400FF;
        margin-bottom: 15px;
    }
    .cta-section {
        background: linear-gradient(135deg, #9400FF, #7B2FBE);
        border-radius: 20px;
        padding: 60px 40px;
        text-align: center;
        color: #fff;
        margin: 60px 0;
    }
    .cta-section h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .cta-section p {
        font-size: 18px;
        opacity: 0.9;
        margin-bottom: 30px;
    }
    .cta-btn {
        display: inline-block;
        padding: 14px 40px;
        background: #fff;
        color: #9400FF;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 700;
        font-size: 16px;
        transition: all 0.3s;
    }
    .cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        text-decoration: none;
    }
</style>

<!-- Hero Section -->
<section class="hero-section" @if(@$banner->data_values->image) style="background: linear-gradient(rgba(26,26,46,0.85), rgba(26,26,46,0.9)), url('{{ asset('assets/images/frontend/banner/'.@$banner->data_values->image) }}') center/cover no-repeat;" @endif>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">
                    {{ __(@$banner->data_values->heading_w ?? 'Invista no Futuro em uma Plataforma Estável') }}
                    <span>{{ __(@$banner->data_values->heading_c ?? 'e Ganhe Dinheiro Rápido') }}</span>
                </h1>
                <p class="hero-subtitle">{{ __(@$banner->data_values->sub_heading ?? 'Invista em uma empresa líder do setor, profissional e confiável.') }}</p>
                <a href="{{ __(@$banner->data_values->button_link ?? route('user.register')) }}" class="hero-btn">
                    {{ __(@$banner->data_values->button_name ?? 'Cadastrar') }}
                </a>
                @if(@$banner->data_values->button_two_name)
                <a href="{{ __(@$banner->data_values->button_two_link ?? route('user.login')) }}" class="hero-btn-outline">
                    {{ __(@$banner->data_values->button_two_name) }}
                </a>
                @endif
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ $logo }}" alt="Logo" style="max-width: 300px;">
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
@if($howItWorks && $howItWorksItems->count() > 0)
<section style="padding: 80px 0; background: #fff;">
    <div class="container">
        <h2 class="section-title">{!! __(@$howItWorks->data_values->title ?? 'Como Funciona') !!}</h2>
        <p class="section-subtitle">{!! __(@$howItWorks->data_values->subtitle ?? 'Siga estes passos simples para começar a investir') !!}</p>
        <div class="row">
            @foreach($howItWorksItems as $index => $item)
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    @if(@$item->data_values->image)
                    <div class="step-icon">
                        <img src="{{ asset('assets/images/frontend/how_it_work/'.@$item->data_values->image) }}" alt="{{ __($item->data_values->title ?? '') }}">
                    </div>
                    @else
                    <div class="step-number">{{ $index + 1 }}</div>
                    @endif
                    <h4 class="step-title">{{ __($item->data_values->title ?? '') }}</h4>
                    <p class="step-desc">{{ __($item->data_values->content ?? '') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Features -->
<section style="padding: 80px 0; background: #f8f9fa;">
    <div class="container">
        <h2 class="section-title">Por Que Nos Escolher</h2>
        <p class="section-subtitle">Oferecemos a melhor experiência de investimento</p>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Plataforma Segura</h4>
                    <p style="color: #6c757d;">Seus investimentos são protegidos com segurança de nível bancário</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-chart-line"></i>
                    <h4>Alto Retorno</h4>
                    <p style="color: #6c757d;">Obtenha retornos competitivos nos seus investimentos</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="fas fa-headset"></i>
                    <h4>Suporte 24/7</h4>
                    <p style="color: #6c757d;">Nossa equipe está sempre pronta para ajudá-lo</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
@if($faqs->count() > 0)
<section style="padding: 80px 0; background: #fff;">
    <div class="container">
        <h2 class="section-title">{{ __(@$faqContent->data_values->title ?? 'Perguntas Frequentes') }}</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @foreach($faqs as $faq)
                <div class="faq-item">
                    <div class="faq-question" onclick="this.classList.toggle('active'); this.nextElementSibling.classList.toggle('show');">
                        {{ __($faq->data_values->question) }}
                    </div>
                    <div class="faq-answer">
                        {{ __($faq->data_values->answer) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<!-- Contact -->
@if($contact)
<section style="padding: 80px 0; background: #f8f9fa;">
    <div class="container">
        <h2 class="section-title">{{ __(@$contact->data_values->title ?? 'Fale Conosco') }}</h2>
        <p class="section-subtitle">{{ __(@$contact->data_values->subtitle ?? 'Entre em contato conosco') }}</p>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row">
                    @foreach($contactItems as $item)
                    <div class="col-md-4 mb-4">
                        <div class="contact-card">
                            <div class="contact-icon">
                                <i class="{{ __($item->data_values->icon ?? 'fas fa-envelope') }}"></i>
                            </div>
                            <h5>{{ __($item->data_values->title ?? '') }}</h5>
                            <p>{{ __($item->data_values->content ?? '') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- CTA -->
<div class="container">
    <div class="cta-section">
        <h2>Pronto para Começar a Investir?</h2>
        <p>Junte-se a milhares de investidores que já estão lucrando</p>
        <a href="{{ route('user.register') }}" class="cta-btn">Criar Conta</a>
    </div>
</div>

@endsection
