@auth
@php
    $promotionCount = App\Models\PromotionTool::count();
@endphp
<div class="user-sidebar style--xl">
    <button type="button" class="dashboard-side-menu-close"><i class="las la-window-close"></i></button>
    <ul class="user-sidebar-menu">
        <li><a href="{{route('user.home')}}" class="{{ menuActive('user.home') }}"> <i class="las la-tachometer-alt pr-1"></i>  @lang('Painel')</a></li>
        <li><a href="{{route('plan')}}" class="{{ menuActive(['plan', 'user.invest.log','user.invest.statistics']) }}"> <i class="las la-cubes pr-1"></i>  @lang('Investimento')</a></li>
        <li><a href="{{route('user.deposit.index')}}" class="{{ menuActive(['user.deposit','user.deposit.history']) }}"><i class="las la-credit-card pr-1" aria-hidden="true"></i>
                 @lang('Depósito')</a></li>

        <li><a href="{{route('user.withdraw')}}" class="{{ menuActive('user.withdraw') }}"><i class="las la-money-bill-wave pr-1"></i>
                @lang('Retirar')</a></li>

        @if($general->b_transfer)
            <li><a href="{{route('user.transfer.balance')}}" class="{{ menuActive('user.transfer.balance') }}"> <i class="las la-dollar-sign"></i>@lang('Saldo de transferência')</a></li>
        @endif
        <li><a href="{{route('user.transactions')}}" class="{{ menuActive('user.transactions') }}"><i class="las la-exchange-alt pr-1"></i> @lang('Registro de transações')</a></li>
        <li><a href="{{route('user.referrals')}}" class="{{ menuActive('user.referrals') }}"><i class="las la-users pr-1"></i> @lang('Usuários indicados')</a></li>
        @if($general->promotional_tool && $promotionCount)
            <li><a href="{{route('user.promotional.banner')}}" class="{{ menuActive('user.promotional.banner') }}"><i class="las la-ad pr-1"></i> @lang('Banner Promocional')</a></li>
        @endif
        <li><a href="{{route('ticket.index')}}" class="{{ menuActive('ticket') }}"><i class="las la-ticket-alt pr-1" ></i>@lang('Tíquete de suporte')</a></li>
        <li><a href="{{route('user.twofactor')}}" class="{{ menuActive('user.twofactor') }}"><i class="las la-user-secret pr-1" ></i> @lang('Segurança 2FA')</a></li>
        <li><a href="{{route('user.profile.setting')}}" class="{{ menuActive('user.profile.setting') }}"> <i class="las la-user pr-1"></i>@lang('Configuração de perfil')</a></li>
        <li><a href="{{route('user.change.password')}}" class="{{ menuActive('user.change.password') }}"><i class="las la-unlock-alt pr-1" ></i>@lang('Alterar a senha')</a></li>
        <li><a href="{{ route('user.logout') }}" class="{{ menuActive('user.logout') }}"> <i class="las la-sign-out-alt pr-1"></i> {{ __('Sair') }}</a></li>
    </ul>
</div><!-- user-sidebar end -->
@endauth
