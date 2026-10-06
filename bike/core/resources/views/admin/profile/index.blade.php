@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-content">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 col-sm-3 text-center mb-1 mb-sm-0">
                                            <img src="@if(admin()->user()->photo) {{asset(admin()->user()->photo)}} @else {{asset(not_found_img())}} @endif" class="rounded" alt="group image" height="120" width="120">
                                        </div>
                                        <div class="col-12 col-sm-9">
                                            <div class="row">
                                                <div class="col-12 text-center text-sm-left">
                                                    <h6 class="media-heading mb-0">{{admin()->user()->name}}</h6>
                                                    <small class="text-muted align-top">Administrador</small>
                                                </div>
                                                <div class="col-12 text-center text-sm-left">
                                                    <div class="mb-1">
                                                        <span class="mr-1">122 <small>Publicações</small></span>
                                                        <span class="mr-1">4.7k <small>Seguidores</small></span>
                                                        <span class="mr-1">652 <small>Seguindo</small></span>
                                                    </div>
                                                    <p>
                                                        E-mail: {{admin()->user()->email}}
                                                    </p>
                                                    <p>
                                                        Telefone: {{admin()->user()->phone}}
                                                    </p>
                                                    <p>
                                                        Endereço: {{admin()->user()->address}}
                                                    </p>
                                                    <div>
                                                        <div class="badge badge-light-primary badge-round mr-1 mb-1" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="com 7% de crescimento @valintini_007 está no top 5k"><i class="cursor-pointer bx bx-check-shield"></i>
                                                        </div>
                                                        <div class="badge badge-light-warning badge-round mr-1 mb-1" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="últimos 55% de crescimento @valintini_007 está no weekday"><i class="cursor-pointer bx bx-badge-check"></i>
                                                        </div>
                                                        <div class="badge badge-light-success badge-round mb-1" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="ganhou perfil premium aqui"><i class="cursor-pointer bx bx-award"></i>
                                                        </div>
                                                    </div>
                                                    <a href="{{route('admin.profile.update')}}" class="btn btn-sm d-none d-sm-block float-right btn-light-primary">
                                                        <i class="cursor-pointer bx bx-edit font-small-3 mr-50"></i><span>Editar</span>
                                                    </a>
                                                    <a href="{{route('admin.profile.update')}}" class="btn btn-sm d-block d-sm-none btn-block text-center btn-light-primary">
                                                        <i class="cursor-pointer bx bx-edit font-small-3 mr-25"></i><span>Editar</span></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


