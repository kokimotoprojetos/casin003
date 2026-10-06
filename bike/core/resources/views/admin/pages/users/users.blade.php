@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Lista de Clientes</div>
                                <div>
                                    <a href="{{route('admin.search.user')}}" class="btn btn-success"><i class="bx bx-user"></i> Buscar um Usuário</a>
                                </div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="mes">

                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped dataex-html5-selectors">
                                    <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>Foto</th>
                                        <th>Referenciado por</th>
                                        <th>ID de Referência</th>
                                        <th>Nome</th>
                                        <th>Telefone</th>
                                        <th>VIPs Ativos</th>
                                        <th>Saldo</th>
                                        <th>Status</th>
                                        <th>Banir/Desbanir</th>
                                        <th>Ações</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($users as $key => $row)
                                        <tr>
                                            <td>{{$key + 1}}</td>
                                            <td>
                                                <a href="{{asset($row->photo ? view_image($row->photo) : not_found_img())}}"
                                                   target="_blank">
                                                    <img width="40"
                                                         src="{{asset($row->photo ? view_image($row->photo) : not_found_img())}}"
                                                         alt="Foto do Pacote">
                                                </a>
                                            </td>
                                            <td>{{$row->ref_by ?? 'Não usado'}}</td>
                                            <td>{{$row->ref_id}}</td>
                                            <td>{{$row->name}}</td>
                                            <td>{{$row->phone}}</td>
                                            <td>
                                                @foreach(my_vips() as $id)
                                                    <div class="badge badge-secondary">
                                                        {{\App\Models\Package::find($id)->name ?? '---'}}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{number_format($row->balance, 2)}}</td>
                                            <td>{{$row->status}}</td>
                                            <td>
                                                @if($row->ban_unban == 'unban')
                                                    <a href="{{route('admin.user.ban', $row->id)}}"
                                                       class="btn btn-danger"
                                                       style="padding: 3px 7px;font-size: 20px" title='Banir Conta'>
                                                        <i class="bx bx-user-minus"></i></a>
                                                        <span style="color: green">Desbanido <i class="bx bx-check"></i> </span>
                                                @else
                                                    <a href="{{route('admin.user.unban', $row->id)}}"
                                                       class="btn btn-success"
                                                       style="padding: 3px 7px;font-size: 20px"
                                                       title='Desbanir Conta'>
                                                        <i class="bx bx-user-plus"></i></a>
                                                        <span style="color: red">Banido <i class="bx bx-closet"></i> </span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)"
                                                   class="btn btn-danger"
                                                   style="padding: 3px 7px;font-size: 20px"
                                                   data-target="#bonusModal{{$row->id}}"
                                                   data-toggle="modal" title='Presente de Bônus'>
                                                    <i class="bx bx-gift"></i></a>

                                                <a href="{{route('admin.customer.login', $row->id)}}"
                                                   target="_blank"
                                                   class="btn btn-info"
                                                   style="padding: 3px 7px;font-size: 20px"
                                                   data-toggle="tooltip" title='Acessar Conta do Usuário'>
                                                    <i class="bx bx-user"></i></a>

                                                <a href="javascript:void(0)"
                                                   class="btn btn-primary"
                                                   data-target="#myModal{{$row->id}}"
                                                   style="padding: 3px 7px;font-size: 20px"
                                                   data-toggle="modal" title='Alterar Senha'>
                                                    <i class="bx bx-lock"></i></a>

                                                <a href="javascript:void(0)"
                                                   class="btn btn-warning"
                                                   style="padding: 3px 7px;font-size: 20px"
                                                   onclick="confirmResetSaque('{{$row->id}}')"
                                                   title='Resetar Dados Cadastrados de Saque'>
                                                    <i class="bx bx-recycle"></i></a>

                                                <a href="{{route('admin.customer.status', $row->id)}}"
                                                   class="btn @if($row->status == 'active') btn-success @else btn-danger @endif"
                                                   style="padding: 3px 7px;font-size: 20px"
                                                   data-toggle="tooltip"
                                                   title='@if($row->status == 'active') Status do usuário inativo após clicar @else Status do usuário ativo após clicar @endif'>
                                                    <i class="bx @if($row->status == 'active') bx-up-arrow @else bx-down-arrow @endif"></i></a>
                                            </td>
                                        </tr>

                                        <form action="javascript:void(0)" method="POST">@csrf
                                            <div class="modal fade" id="myModal{{$row->id}}">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">

                                                        <!-- Modal Header -->
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Definir Nova Senha</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>

                                                        <!-- Modal body -->
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="password">Senha </label>
                                                                <input name="password" id="password{{$row->id}}" class="form-control is-valid" placeholder="Defina a Senha do Usuário Novamente">
                                                            </div>
                                                        </div>

                                                        <!-- Modal footer -->
                                                        <div class="modal-footer">
                                                            <input type="submit" value="Salvar" onclick="resetPassword('{{$row->id}}')" class="btn btn-primary">
                                                            <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </form>


                                        <form action="javascript:void(0)" method="POST">@csrf
                                            <div class="modal fade" id="bonusModal{{$row->id}}">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">

                                                        <!-- Modal Header -->
                                                        <div class="modal-header">
                                                            <h4 class="modal-title">Presentear com bônus</h4>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>

                                                        <!-- Modal body -->
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="bonus">Digite o código do bônus </label>
                                                                <input type="text" name="bonus" id="bonus" required class="form-control is-valid" placeholder="Código do bônus">
                                                            </div>
                                                        </div>

                                                        <!-- Modal footer -->
                                                        <div class="modal-footer">
                                                            <input type="submit" value="Salvar" onclick="submitBonus('{{$row->id}}')" class="btn btn-primary">
                                                            <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </form>
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
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script>
        function submitBonus(id)
        {
            var bonus = document.getElementById('bonus').value;
            console.log(bonus)
            var data = {
                id: id,
                bonus: bonus
            }
            fetch('{{route('admin.customer.bonus')}}',
                {
                    method:"POST",
                    body:JSON.stringify(data),
                    headers: {'Content-type': 'application/json; charset=UTF-8', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}})
                .then(response => response.json())
                .then(data => {
                    if (data.status === true){
                        document.querySelector('.mes').innerHTML = `<div class="alert alert-success">${data.message}</div>`
                        document.querySelector('#bonusModal'+id).style.display = 'none'
                        document.querySelector('.modal-backdrop.show').style.display = 'none'
                    }else {
                        document.querySelector('.mes').innerHTML = `<div class="alert alert-success">Algo deu errado</div>`
                    }
                }).catch();
        }
    </script>


    <script>
        function resetPassword(id)
        {
            var password = document.getElementById('password' + id).value;
            var data = {
                id: id,
                password: password
            }
            fetch('{{route('admin.customer.change-password')}}',
                {
                    method:"POST",
                    body:JSON.stringify(data),
                    headers: {'Content-type': 'application/json; charset=UTF-8', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}})
                .then(response => response.json())
                .then(data => {
                    if (data.status === true){
                        document.querySelector('.mes').innerHTML = `<div class="alert alert-success">${data.message}</div>`
                        window.location.reload();
                    }else {
                        document.querySelector('.mes').innerHTML = `<div class="alert alert-success">Algo deu errado</div>`
                    }
                });
        }
    </script>
<script>
        function confirmResetSaque(id)
        {
            if (confirm('Limpar os dados cadastrados de saque (PIX) deste usuário? Saques aprovados serão arquivados.')) {
                window.location.href = '{{ route('admin.customer.reset-saque', '') }}/' + id;
            }
        }
    </script>
@endsection





