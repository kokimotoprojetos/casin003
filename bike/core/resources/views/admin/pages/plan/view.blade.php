@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Detalhes do Plano</div>
                                <div><a href="{{route('admin.plan.index')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Lista de Planos </a> </div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <td>Nome do Plano</td>
                                        <td><strong>{{$data->name}}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Imagem</td>
                                        <td>
                                            @if($data->image)
                                                <img src="{{asset(view_image($data->image))}}" width="70">
                                            @else
                                                <span class="text-muted">Sem imagem</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Valor Fixo</td>
                                        <td>
                                            @if($data->fixed_amount > 0)
                                                R$ {{number_format($data->fixed_amount, 2, ',', '.')}}
                                            @else
                                                Variável
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mínimo</td>
                                        <td>R$ {{number_format($data->minimum, 2, ',', '.')}}</td>
                                    </tr>
                                    <tr>
                                        <td>Máximo</td>
                                        <td>R$ {{number_format($data->maximum, 2, ',', '.')}}</td>
                                    </tr>
                                    <tr>
                                        <td>Juros</td>
                                        <td>
                                            @if($data->interest_type == 1)
                                                {{$data->interest}}%
                                            @else
                                                R$ {{number_format($data->interest, 2, ',', '.')}}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tipo de Juros</td>
                                        <td>
                                            @if($data->interest_type == 1)
                                                Percentual
                                            @else
                                                Fixo
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Intervalo</td>
                                        <td>{{$data->time}} horas</td>
                                    </tr>
                                    <tr>
                                        <td>Nome do Intervalo</td>
                                        <td>{{$data->time_name ?? 'N/A'}}</td>
                                    </tr>
                                    <tr>
                                        <td>Repetições</td>
                                        <td>
                                            @if($data->lifetime)
                                                Para sempre (vitalício)
                                            @else
                                                {{$data->repeat_time}} vezes
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Retorno Total</td>
                                        <td>
                                            @if($data->lifetime)
                                                ∞ (Para sempre)
                                            @else
                                                @if($data->interest_type == 1)
                                                    {{$data->interest}}% x {{$data->repeat_time}}x
                                                @else
                                                    R$ {{number_format($data->interest * $data->repeat_time, 2, ',', '.')}}
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Devolver Capital</td>
                                        <td>
                                            @if($data->capital_back)
                                                <span class="badge badge-success">Sim</span>
                                            @else
                                                <span class="badge badge-danger">Não</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Destaque</td>
                                        <td>
                                            @if($data->featured)
                                                <span class="badge badge-warning">Sim</span>
                                            @else
                                                Não
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>
                                            @if($data->status == 1)
                                                <span class="badge badge-success">Ativo</span>
                                            @else
                                                <span class="badge badge-danger">Inativo</span>
                                            @endif
                                        </td>
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
