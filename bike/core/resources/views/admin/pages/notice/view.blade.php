@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">Detalhes do Aviso</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><td>Título</td><td>{{$data->title}}</td></tr>
                                <tr><td>Detalhes</td><td>{{$data->details}}</td></tr>
                                <tr><td>Status</td><td>{{$data->status == 1 ? 'Ativo' : 'Inativo'}}</td></tr>
                            </table>
                            <a href="{{route('admin.notice.index')}}" class="btn btn-primary">Voltar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
