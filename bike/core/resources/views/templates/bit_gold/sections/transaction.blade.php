@php

    $latestDeposit = \App\Models\Deposit::with('user', 'gateway')->where('status', 1)->latest()->limit(10)->get();
    $fakeDeposit = \App\Models\Frontend::where('data_keys','transaction.element')->whereJsonContains('data_values->trx_type','deposit')->limit(10)->get();
    $deposits =  $latestDeposit->merge($fakeDeposit);
    $deposits = $deposits->sortByDesc('created_at')->take(10);


    $latestWithdraw = \App\Models\Withdrawal::with('user', 'method')->where('status', 1)->latest()->limit(10)->get();
    $fakeWithdraw = \App\Models\Frontend::where('data_keys','transaction.element')->whereJsonContains('data_values->trx_type','withdraw')->limit(10)->get();

    $withdrawals =  $latestWithdraw->merge($fakeWithdraw);
    $withdrawals = $withdrawals->sortByDesc('created_at')->take(10);
    $transactionContent = getContent('transaction.content',true);

@endphp
<section class="pt-120 pb-120">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 text-center">
        <div class="section-header">
          <h2 class="section-title"><span class="font-weight-normal">{{ __(@$transactionContent->data_values->heading_w) }}</span> <b class="base--color">{{ __(@$transactionContent->data_values->heading_c) }}</b></h2>
          <p>{{ __(@$transactionContent->data_values->sub_heading) }}</p>
        </div>
      </div>
    </div><!-- row end -->
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <ul class="nav nav-tabs custom--style-two justify-content-center" id="transactionTab" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active" id="deposit-tab" data-bs-toggle="tab" href="#deposit" role="tab" aria-controls="deposit" aria-selected="true">@lang('Último Depósito')</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link" id="withdraw-tab" data-bs-toggle="tab" href="#withdraw" role="tab" aria-controls="withdraw" aria-selected="false">@lang('Última retirada')</a>
          </li>
        </ul>

        <div class="tab-content mt-4" id="transactionTabContent">
          <div class="tab-pane fade show active" id="deposit" role="tabpanel" aria-labelledby="deposit-tab">
            <div class="table-responsive--sm">
              <table class="table style--two">
                <thead>
                  <tr>
                    <th>@lang('Nome')</th>
                    <th>@lang('Data')</th>
                    <th>@lang('Quantia')</th>
                    <th>@lang('Portal')</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($deposits as $data)
                  <tr>
                    @if(@$data->data_values)
                    <td data-label="@lang('Nome')">
                        {{__(@$data->data_values->name)}}
                    </td>
                    <td data-label="@lang('Data')">{{@$data->data_values->date}}</td>
                    <td data-label="@lang('Quantia')">{{@$data->data_values->amount}} {{ $general->cur_text }}</td>
                    <td data-label="@lang('Portal')">{{__(@$data->data_values->gateway)}}</td>
                    @else
                      <td data-label="@lang('Nome')">
                          {{ __(@$data->user->fullname) }}
                      </td>
                      <td data-label="@lang('Data')">{{showDateTime($data->created_at,'Y-m-d')}}</td>
                      <td data-label="@lang('Quantia')">{{getAmount($data->amount)}} {{ $general->cur_text }}</td>
                      <td data-label="@lang('Portal')">{{__($data->gateway->name)}}</td>
                    @endif
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="withdraw" role="tabpanel" aria-labelledby="withdraw-tab">
            <div class="table-responsive--md">
              <table class="table style--two">
                <thead>
                  <tr>
                    <th scope="col">@lang('Nome')</th>
                    <th scope="col">@lang('Data')</th>
                    <th scope="col">@lang('Quantia')</th>
                    <th scope="col">@lang('Método')</th>
                  </tr>
                </thead>
                <tbody>
                   @foreach($withdrawals as $data)
                  <tr>
                    @if(@$data->data_values)
                        <td data-label="@lang('Nome')">{{__(@$data->data_values->name)}}</td>
                        <td data-label="@lang('Data')">{{@$data->data_values->date}}</td>
                        <td data-label="@lang('Quantia')">{{@$data->data_values->amount}} {{ $general->cur_text }}</td>
                        <td data-label="@lang('Método')">{{__(@$data->data_values->gateway)}}</td>
                    @else
                        <td data-label="@lang('Nome')">{{ __($data->user->fullname) }}</td>
                        <td data-label="@lang('Data')">{{showDateTime($data->created_at,'Y-m-d')}}</td>
                        <td data-label="@lang('Quantia')">{{getAmount($data->amount)}} {{ $general->cur_text }}</td>
                        <td data-label="@lang('Método')">{{__($data->method->name)}}</td>
                    @endif
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div><!-- tab-content end -->
      </div>
    </div>
  </div>
</section>
