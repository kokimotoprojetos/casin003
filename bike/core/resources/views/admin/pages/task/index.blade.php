@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Tarefas</div>
                                <div><a href="{{route('admin.task.create')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Criar Tarefa </a></div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>Título</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($tasks as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>{{$row->title}}</td>
                                            <td>R$ {{number_format($row->amount, 2, ',', '.')}}</td>
                                            <td>
                                                @if($row->status == 1)
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Inativo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('admin.task.create', $row->id)}}" class="btn btn-warning" style="padding: 3px 7px;font-size: 20px"><i class="bx bx-pencil"></i></a>
                                                <form method="POST" action="{{route('admin.task.delete', $row->id)}}" class="d-inline">@csrf {{method_field('DELETE')}}
                                                    <button type="submit" style="padding: 3px 7px;" class="btn btn-icon btn-danger"><i class="bx bx-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
