@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Investimentos dos Clientes</div>
                                <div>
                                    <a href="{{route('admin.search.user')}}" class="btn btn-success"><i class="bx bx-user"></i> Buscar um Usuário</a>
                                </div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>ID de Referência</th>
                                        <th>Telefone</th>
                                        <th>Valor Investido</th>
                                        <th>Data de Investimento</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($users as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>{{$row->ref_id}}</td>
                                            <td>{{$row->phone}}</td>
                                            <td>{{price($row->invest_balance)}}</td>
                                            <td>{{$row->created_at}}</td>
                                        </tr>
                                    @endforeach
                                </table>
                                {{$users->links("pagination::bootstrap-4")}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


