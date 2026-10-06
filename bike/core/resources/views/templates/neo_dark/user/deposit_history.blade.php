@extends($activeTemplate.'layouts.master')
@section('content')
<section class="pt-150 pb-150">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <form action="">
                    <div class="mb-3 d-flex justify-content-end table-search">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" value="{{ request()->search }}" placeholder="@lang('Pesquisar por transações')">
                            <button class="input-group-text bg--dark-light text-white">
                                <i class="las la-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <div class="table-responsive--sm neu--table">
                    <table class="table text-white">
                        <thead>
                            <tr>
                                <th>@lang('Portal | Transação')</th>
                                <th>@lang('Iniciado')</th>
                                <th>@lang('Quantia')</th>
                                <th>@lang('Conversão')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Detalhes')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposits as $deposit)
                                <tr>
                                    <td>
                                        <span class="fw-bold"> <span class="text-primary">{{ __($deposit->gateway?->name) }}</span> </span>
                                        <br>
                                        <small> {{ $deposit->trx }} </small>
                                    </td>

                                    <td>
                                        {{ showDateTime($deposit->created_at) }}<br>{{ diffForHumans($deposit->created_at) }}
                                    </td>
                                    <td>
                                        {{ __($general->cur_sym) }}{{ showAmount($deposit->amount ) }} + <span class="text-danger" title="@lang('cobrar')">{{ showAmount($deposit->charge)}} </span>
                                        <br>
                                        <strong title="@lang('Valor com cobrança')">
                                        {{ showAmount($deposit->amount+$deposit->charge) }} {{ __($general->cur_text) }}
                                        </strong>
                                    </td>
                                    <td>
                                        1 {{ __($general->cur_text) }} =  {{ showAmount($deposit->rate) }} {{__($deposit->method_currency)}}
                                        <br>
                                        <strong>{{ showAmount($deposit->final_amo) }} {{__($deposit->method_currency)}}</strong>
                                    </td>
                                    <td>
                                        @php echo $deposit->statusBadge @endphp
                                    </td>
                                    @php
                                        $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
                                    @endphp

                                    <td>
                                        <a href="javascript:void(0)" class="btn btn-sm btn--base w-auto btn-sm text-white @if($deposit->method_code >= 1000) detailBtn @else disabled @endif"
                                            @if($deposit->method_code >= 1000)
                                                data-info="{{ $details }}"
                                            @endif
                                            @if ($deposit->status == 3)
                                            data-admin_feedback="{{ $deposit->admin_feedback }}"
                                            @endif
                                            >
                                            <i class="fa fa-desktop"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($deposits->hasPages())
                    {{ $deposits->links() }}
                @endif
            </div>
        </div>
    </div>
</section>

{{-- APPROVE MODAL --}}
<div id="detailModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-content-bg">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Detalhes')</h5>
                <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </span>
            </div>
            <div class="modal-body">
                <ul class="list-group userData mb-2">
                </ul>
                <div class="feedback text-white"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">@lang('Fechar')</button>
            </div>
        </div>
    </div>
</div>

@endsection


@push('script')
    <script>
        (function ($) {
            "use strict";
            $('.detailBtn').on('click', function () {
                var modal = $('#detailModal');

                var userData = $(this).data('info');
                var html = '';
                if(userData){
                    userData.forEach(element => {
                        if(element.type != 'file'){
                            html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${element.name}</span>
                                <span">${element.value}</span>
                            </li>`;
                        }
                    });
                }

                modal.find('.userData').html(html);

                if($(this).data('admin_feedback') != undefined){
                    var adminFeedback = `
                        <div class="my-3">
                            <strong>@lang('Feedback do administrador')</strong>
                            <p>${$(this).data('admin_feedback')}</p>
                        </div>
                    `;
                }else{
                    var adminFeedback = '';
                }

                modal.find('.feedback').html(adminFeedback);


                modal.modal('show');
            });
        })(jQuery);

    </script>
@endpush

@push('style')
    <style>
        .bg--dark-light {
            background-color: #ffffff1f; 
        }
    </style>
@endpush
