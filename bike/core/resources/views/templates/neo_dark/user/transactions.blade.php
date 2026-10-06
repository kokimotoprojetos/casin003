@extends($activeTemplate . 'layouts.master')
@section('content')
    <section class="pt-150 pb-150">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="show-filter mb-3 text-end">
                        <button type="button" class="showFilterBtn btn-sm btn btn-primary btn-small"><i class="las la-filter"></i> @lang('Filtro')</button>
                    </div>
                    <div class="card card-bg responsive-filter-card mb-4">
                        <div class="card-body">
                            <form action="">
                                <div class="d-flex flex-wrap gap-4">
                                    <div class="flex-grow-1">
                                        <label>@lang('Número da transação')</label>
                                        <input type="text" name="search" value="{{ request()->search }}" class="form-control">
                                    </div>
                                    <div class="flex-grow-1">
                                        <label>@lang('Tipo de carteira')</label>
                                        <select name="wallet_type" class="form-control form-select">
                                            <option value="">@lang('Todos')</option>
                                            <option value="deposit_wallet" @selected(request()->wallet_type == 'deposit_wallet')>@lang('Carteira de Depósito')</option>
                                            <option value="interest_wallet" @selected(request()->wallet_type == 'interest_wallet')>@lang('Carteira de juros')</option>
                                        </select>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label>@lang('Tipo')</label>
                                        <select name="trx_type" class="form-control form-select">
                                            <option value="">@lang('Todos')</option>
                                            <option value="+" @selected(request()->trx_type == '+')>@lang('Mais')</option>
                                            <option value="-" @selected(request()->trx_type == '-')>@lang('Menos')</option>
                                        </select>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label>@lang('Observação')</label>
                                        <select class="form-control form-select" name="remark">
                                            <option value="">@lang('Qualquer')</option>
                                            @foreach ($remarks as $remark)
                                                <option value="{{ $remark->remark }}" @selected(request()->remark == $remark->remark)>{{ __(keyToTitle($remark->remark)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-grow-1 align-self-end">
                                        <button class="btn btn-primary btn-small w-100"><i class="las la-filter"></i> @lang('Filtro')</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive--sm neu--table">
                        <table class="table text-white">
                            <thead>
                                <tr>
                                    <th>@lang('Trx')</th>
                                    <th>@lang('Transacionado')</th>
                                    <th>@lang('Quantia')</th>
                                    <th>@lang('Pós Saldo')</th>
                                    <th>@lang('Tipo de carteira')</th>
                                    <th>@lang('Detalhe')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr>
                                        <td>
                                            <strong>{{ $trx->trx }}</strong>
                                        </td>

                                        <td>
                                            {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                        </td>

                                        <td class="budget">
                                            <span class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount($trx->amount) }} {{ $general->cur_text }}
                                            </span>
                                        </td>

                                        <td class="budget">
                                            {{ showAmount($trx->post_balance) }} {{ __($general->cur_text) }}
                                        </td>

                                        <td>
                                            {{ __(keyToTitle($trx->wallet_type)) }}
                                        </td>

                                        <td>{{ __($trx->details) }}</tdalance>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($transactions->hasPages())
                        {{ $transactions->links() }}
                    @endif
                </div>
            </div>
        </div>
        </div>
    </section>
@endsection
@push('style')
    <style>
        .pagination {
            margin: 0
        }
    </style>
@endpush
@push('script')
    <script>
        (function($) {
            "use strict";
            $('.showFilterBtn').on('click', function() {
                $('.responsive-filter-card').slideToggle();
            });
        })(jQuery);
    </script>
@endpush
