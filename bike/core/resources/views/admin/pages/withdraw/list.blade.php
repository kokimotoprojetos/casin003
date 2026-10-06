@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>{{$title}} Lista de Saques</div>
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
                                        <th>Informações de Saque</th>
                                        <th>Valores de Saque</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($withdraws as $key => $row)
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
                                                    Método de Saque: {{$row->method_name ? $row->method_name : '---'}} <br>
                                                    Endereço de Saque: {{$row->number}} <br>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    Valor do Saque: {{price($row->amount)}} <br>
                                                    Taxa de Saque: {{price($row->charge)}} <br>
                                                    Valor de Retorno : {{price($row->final_amount + \App\Services\PoseidonPayService::transferFee())}}
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    Status: <span class="badge @if($row->status == 2) badge-warning @elseif($row->status == 1) badge-success  @elseif($row->status == 3) badge-danger @endif" style="font-size: 8px">{{ $row->status == 2 ? 'Pendente' : ($row->status == 1 ? 'Aprovado' : ($row->status == 3 ? 'Rejeitado' : '')) }}</span> <br>
                                                </small>
                                            </td>
                                            <td>
                                                @if($row->status == 2)
                                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#myModal{{$row->id}}" class="btn btn-success">Ação</a>
                                                    <form action="{{route('withdraw.status.change', $row->id)}}" method="POST">@csrf
                                                        <div class="modal fade" id="myModal{{$row->id}}">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">

                                                                    <!-- Modal Header -->
                                                                    <div class="modal-header">
                                                                        <h4 class="modal-title">Ação para saque</h4>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>

                                                                    <!-- Modal body -->
                                                                    <div class="modal-body">
                                                                        <div class="form-group">
                                                                            <label for="status">Status <small class="text-info"> Você pode alterar o status do saque como aprovado, rejeitado, pendente é o padrão </small> </label>
                                                                            <select name="status" required id="status" class="form-control">
                                                                                <option value="approved">Aprovado</option>
                                                                                <option value="rejected">Rejeitado</option>
                                                                                <option value="pending" selected>Pendente</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Modal footer -->
                                                                    <div class="modal-footer">
                                                                        <input type="submit" value="Salvar" class="btn btn-primary">
                                                                        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    <div class="text-info">Ação já realizada</div>
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


