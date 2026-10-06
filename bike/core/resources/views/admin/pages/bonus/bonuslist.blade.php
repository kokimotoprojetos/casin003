@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Bônus</div>
                                <div><a href="{{route('admin.bonus.create')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Adicionar Novo Item </a> </div>
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
                                        <th>Informações do Cliente</th>
                                        <th>Informações do Bônus</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($datas as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>
                                                <small>
                                                    Nome: {{$row->user->name ?? '--'}}  <br>
                                                    Usuário: {{$row->user->username ?? '--'}}  <br>
                                                    ID de Ref: {{$row->user->ref_id ?? '--'}} <br>
                                                    Saldo: {{price($row->user->balance) ?? '--'}} <br>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    Nome do Bônus: {{$row->bonus->bonus_name ?? '--'}}  <br>
                                                    Código do Bônus:  {{$row->bonus->code ?? '--'}} <br>
                                                    Valor do Bônus:  {{price($row->bonus->amount) ?? '--'}} <br>
                                                    Contagem de Usos:  <span class="badge badge-success">{{$row->bonus->counter ?? '--'}}</span> <br>
                                                </small>
                                            </td>
                                            <td>
                                                {{$row->bonus->status}}
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


