@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.notice.insert')}}" method="POST">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar'}} Aviso</div>
                                    <div><a href="{{route('admin.notice.index')}}" class="btn btn-primary btn-sm"><i class="bx bx-left-arrow"></i> Lista</a></div>
                                </div>
                            </h4>
                        </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <label>Tipo</label>
                                    <select name="notice_type" class="form-control" required>
                                        <option value="info" @if($data && $data->notice_type == 'info') selected @endif>Informação</option>
                                        <option value="warning" @if($data && $data->notice_type == 'warning') selected @endif>Aviso</option>
                                        <option value="danger" @if($data && $data->notice_type == 'danger') selected @endif>Crítico</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label>Título</label>
                                    <input type="text" class="form-control" name="notice_title" value="{{$data ? $data->notice_title : ''}}" required>
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <label>Descrição</label>
                                    <textarea class="form-control" name="notice_desc" rows="5" required>{{$data ? $data->notice_desc : ''}}</textarea>
                                </div>
                                    @if($data)
                                    <div class="col-sm-6 mt-2">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" @if($data->status == 1) selected @endif>Ativo</option>
                                            <option value="0" @if($data->status == 0) selected @endif>Inativo</option>
                                        </select>
                                    </div>
                                    @endif
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
