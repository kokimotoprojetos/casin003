@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Detalhes do Pacote</div>
                                <div><a href="{{route('admin.package.index')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Lista de Pacotes </a> </div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <td>Nome do Pacote</td>
                                        <td>{{$data->name}}</td>
                                    </tr>

                                    <tr>
                                        <td>Título</td>
                                        <td>{{$data->title}}</td>
                                    </tr>

                                    <tr>
                                        <td>Foto</td>
                                        <td>
                                            <img src="{{asset($data->photo)}}" width="70">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Preço</td>
                                        <td>{{price($data->price)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Código</td>
                                        <td>{{$data->code}}</td>
                                    </tr>

                                    <tr>
                                        <td>Validade</td>
                                        <td>{{$data->validity}} Dias</td>
                                    </tr>

                                    <tr>
                                        <td>Horas de Validade</td>
                                        <td>{{$data->hours}}  Horas</td>
                                    </tr>

                                    <tr>
                                        <td>Comissão do Pacote</td>
                                        <td>{{price($data->package_commission)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Comissão com valor médio</td>
                                        <td>{{price($data->commission_with_avg_amount)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Renda de Patrocinador</td>
                                        <td>{{price($data->sponsor_income)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Primeira referência</td>
                                        <td>{{price($data->first_ref)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Segunda referência</td>
                                        <td>{{price($data->second_ref)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Terceira referência</td>
                                        <td>{{price($data->third_ref)}}</td>
                                    </tr>

                                    <tr>
                                        <td>Status</td>
                                        <td>{{$data->status}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


