@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Pacotes</div>
                                <div><a href="{{route('admin.package.create')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Adicionar Novo Item </a> </div>
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
                                        <th>Rótulo</th>
                                        <th>Aba</th>
                                        <th>Foto</th>
                                        <th>Preço</th>
                                        <th>Validade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($packages as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>{{$row->name}}</td>
                                            <td>{{$row->label}}</td>
                                            <td>{{$row->tab}}</td>
                                            <td>
                                                <img width="40" src="{{asset(view_image($row->photo))}}" alt="Foto do Pacote">
                                            </td>
                                            <td>{{$row->price}}</td>
                                            <td>{{$row->validity}} Dias</td>
                                            <td>
                                                @if($row->status == 'active')
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Bloqueado</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{route('admin.package.status', $row->id)}}"
                                                   class="btn @if($row->status == 'active') btn-danger @else btn-success @endif"
                                                   style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip"
                                                   title='@if($row->status == 'active') Bloquear Compra @else Liberar Compra @endif'>
                                                    @if($row->status == 'active')
                                                        <i class="bx bx-lock-alt"></i>
                                                    @else
                                                        <i class="bx bx-unlock"></i>
                                                    @endif
                                                </a>
                                                <a href="{{route('admin.package.view', $row->id)}}"
                                                   class="btn btn-info" style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip" title='Visualizar'>
                                                    <i class="bx bx-notepad"></i></a>
                                                <a href="{{route('admin.package.create', $row->id)}}"
                                                   class="btn btn-warning" style="padding: 3px 7px;font-size: 20px" data-toggle="tooltip" title='Editar'>
                                                    <i class="bx bx-pencil"></i></a>
                                                @if($row->id != 1)
                                                    <form method="POST" action="{{route('admin.package.delete', $row->id)}}"
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


