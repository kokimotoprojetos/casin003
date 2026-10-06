@extends('admin.partials.master')
@section('admin_content')
<style>
    div#DataTables_Table_0_wrapper .btn {
    padding: 0 1.5rem;
    margin: 9px !important;
}
</style>
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>{{$title}} Lista de Pagamentos</div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="table-responsive">
                                <table class="table table-striped dataex-html5-selectors">
                                    <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>Informações do Usuário</th>
                                        <th>Informações de Pagamento</th>
                                        <th>Foto</th>
                                        <th>Valores de Pagamento</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payments as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>
                                                <small>
                                                    Usuário: {{$row->user->username ?? '--'}}  <br>
                                                    ID de Ref: {{$row->user->ref_id ?? '--'}} <br>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    Nome do Método: {{$row->method_name}} <br>
                                                    Data : {{$row->date}}
                                                </small>
                                            </td>
                                            <td>
                                                <a href="{{asset($row->photo)}}" >
                                                    <img src="{{asset($row->photo)}}" style="width: 100px" alt="">
                                                </a>
                                            </td>
                                            <td>
                                                <small>
                                                    Número: {{$row->number}}<br>
                                                    ID da Transação: <strong>{{$row->transaction_id}}</strong><br>
                                                    Valor Adicionado : {{number_format($row->final_amount, 2)}}
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    Status: <span class="badge @if($row->status == 2) badge-warning @elseif($row->status == 1) badge-success  @elseif($row->status == 3) badge-danger @endif" style="font-size: 8px">@if($row->status == 2)Pendente@elseif($row->status == 1)Aprovado@elseif($row->status == 3)Rejeitado@elseIniciado@endif</span> <br>
                                                </small>
                                            </td>
                                            <td>
                                                @if($row->status == 1)
                                                    <span style="color: green">Pagamento Aprovado</span>
                                                @elseif($row->status == 2)
                                                    <a href="{{route('payment.status.change.approved', $row->id)}}" class="btn btn-success">Aprovar</a>
                                                    <a href="{{route('payment.status.change.rejected', $row->id)}}" class="btn btn-danger">Rejeitar</a>
                                                @elseif($row->status == 3)
                                                    <span style="color: red">Pagamento Rejeitado</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


