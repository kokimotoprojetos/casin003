@extends($activeTemplate.'layouts.master')
@section('content')
<div class="cmn-section">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="text-end mb-3">
                    <a href="{{ route('ticket.open') }}" class="btn--base btn-sm">@lang('Abrir ticket de suporte')</a>
                </div>
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>@lang('Assunto')</th>
                                        <th>@lang('Status')</th>
                                        <th>@lang('Prioridade')</th>
                                        <th>@lang('Última resposta')</th>
                                        <th>@lang('Ação')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supports as $support)
                                        <tr>
                                            <td> <a href="{{ route('ticket.view', $support->ticket) }}" class="fw-bold"> [@lang('Bilhete')#{{ $support->ticket }}] {{ __($support->subject) }} </a></td>
                                            <td>
                                                @php echo $support->statusBadge; @endphp
                                            </td>
                                            <td>
                                                @if($support->priority == 1)
                                                    <span class="badge badge--dark">@lang('Baixo')</span>
                                                @elseif($support->priority == 2)
                                                    <span class="badge badge--success">@lang('Médio')</span>
                                                @elseif($support->priority == 3)
                                                    <span class="badge badge--primary">@lang('Alto')</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($support->last_reply)->diffForHumans() }} </td>

                                            <td>
                                                <a href="{{ route('ticket.view', $support->ticket) }}" class="icon-btn base--bg text-white">
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
                    </div>
                    @if($supports->hasPages())
                        <div class="card-footer">
                            {{$supports->links()}}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        .badge--dark {
            color: #999;
            border-color: #999;
            background-color: rgba(153, 153, 153, 0.15);
        }
    </style>
@endpush