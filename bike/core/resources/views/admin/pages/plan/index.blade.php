@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Planos</div>
                                <div><a href="{{route('admin.plan.create')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Criar Novo Plano </a> </div>
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
                                        <th>Nome</th>
                                        <th>Imagem</th>
                                        <th>Valor Fixo</th>
                                        <th>Juros</th>
                                        <th>Tipo</th>
                                        <th>Intervalo</th>
                                        <th>Repetições</th>
                                        <th>Capital</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($plans as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td><strong>{{$row->name}}</strong></td>
                                            <td>
                                                @if($row->image)
                                                    <img width="40" src="{{asset(view_image($row->image))}}" alt="{{$row->name}}">
                                                @else
                                                    <span class="text-muted">Sem imagem</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->fixed_amount > 0)
                                                    R$ {{number_format($row->fixed_amount, 2, ',', '.')}}
                                                @else
                                                    R$ {{number_format($row->minimum, 2, ',', '.')}} - R$ {{number_format($row->maximum, 2, ',', '.')}}
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->interest_type == 1)
                                                    {{$row->interest}}%
                                                @else
                                                    R$ {{number_format($row->interest, 2, ',', '.')}}
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->interest_type == 1)
                                                    <span class="badge badge-info">Percentual</span>
                                                @else
                                                    <span class="badge badge-success">Fixo</span>
                                                @endif
                                            </td>
                                            <td>{{$row->time}}h ({{$row->time_name ?? 'N/A'}})</td>
                                            <td>
                                                @if($row->lifetime)
                                                    <span class="badge badge-warning">Para sempre</span>
                                                @else
                                                    {{$row->repeat_time}}x
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->capital_back)
                                                    <span class="badge badge-success">Sim</span>
                                                @else
                                                    <span class="badge badge-danger">Não</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->status == 1)
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Bloqueado</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('admin.plan.status', $row->id)}}"
                                                   class="btn @if($row->status == 1) btn-danger @else btn-success @endif"
                                                   style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip"
                                                   title='@if($row->status == 1) Bloquear Plano @else Liberar Plano @endif'>
                                                    @if($row->status == 1)
                                                        <i class="bx bx-lock-alt"></i>
                                                    @else
                                                        <i class="bx bx-unlock"></i>
                                                    @endif
                                                </a>
                                                <a href="{{route('admin.plan.view', $row->id)}}"
                                                   class="btn btn-info" style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip" title='Visualizar'>
                                                    <i class="bx bx-notepad"></i></a>
                                                <a href="{{route('admin.plan.create', $row->id)}}"
                                                   class="btn btn-warning" style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip" title='Editar'>
                                                    <i class="bx bx-pencil"></i></a>
                                                <form method="POST" action="{{route('admin.plan.delete', $row->id)}}"
                                                      class="d-inline">@csrf
                                                    {{method_field('DELETE')}}
                                                    <button type="submit"
                                                            style="padding: 3px 7px;"
                                                            class="btn btn-icon btn-danger delete_confirm{{$row->id}}"
                                                            data-toggle="tooltip" title='Excluir'>
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                    @include('admin.partials.delete-confirmation')
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
