@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.task.insert')}}" method="POST">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar'}} Melhoria</div>
                                    <div><a href="{{route('admin.task.index')}}" class="btn btn-primary btn-sm"><i class="bx bx-left-arrow"></i> Lista</a></div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>Código da Tarefa</label>
                                        <input type="text" class="form-control" name="task_code" value="{{$data ? $data->task_code : ''}}" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>Código Restante</label>
                                        <input type="number" class="form-control" name="remaining_code" value="{{$data ? $data->remaining_code : ''}}" required>
                                    </div>
                                    <div class="col-sm-6 mt-2">
                                        <label>Valor (R$)</label>
                                        <input type="number" step="0.01" class="form-control" name="amount" value="{{$data ? $data->amount : ''}}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div style="margin-top: .7rem !important">Salvar Informações</div>
                                    <div><button type="submit" class="btn btn-success"><i class="bx bx-plus"></i> {{$data ? 'Atualizar' : 'Salvar'}}</button></div>
                                </div>
                            </h6>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
