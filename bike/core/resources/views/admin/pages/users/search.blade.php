@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Obter um Usuário Usando Referência Ou Número de Telefone</div>
                                <div><a href="{{route('admin.customer.index')}}" class="btn btn-primary btn-sm"> <i class="bx bx-plus"></i> Lista de Clientes </a> </div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <form action="{{route('admin.search.submit')}}" method="get">
                                @csrf
                                <div class="form-group">
                                    <label for="username">Buscar detalhes de um usuário usando código de referência</label>
                                    <input type="text" id="ref_id" name="search" class="form-control is-valid" placeholder="Digite o código de referência Ou número de telefone">
                                </div>
                                <div class="form-group text-center">
                                    <input type="submit" value="Buscar" class="btn btn-success">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @if(isset($user) && !empty($user))
                    <div class="card">
                    <div class="card-header pb-0">
                        <h4 class="card-title">
                            <div class="d-flex justify-content-between">
                                <div>Ver Resultado</div>
                            </div>
                        </h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <table class="table-bordered table">
                                <tr>
                                    <td>Nome do Usuário</td>
                                    <td>{{$user->name}}</td>
                                </tr>
                                <tr>
                                    <td>ID</td>
                                    <td>{{$user->id}}</td>
                                </tr>
                                <tr>
                                    <td>Usuário</td>
                                    <td>{{$user->username}}</td>
                                </tr>
                                <tr>
                                    <td>ID de Referência</td>
                                    <td>{{$user->ref_id}}</td>
                                </tr>
                                <tr>
                                    <td>Telefone</td>
                                    <td>{{$user->phone}}</td>
                                </tr>
                                <tr>
                                    <td>Referenciado Por</td>
                                    <td>{{$user->ref_by ?? '--'}}</td>
                                </tr>
                                <tr>
                                    <td>E-mail do Usuário</td>
                                    <td>{{$user->email}}</td>
                                </tr>
                             
                                <tr>
                                    <td>Status do Usuário</td>
                                    <td>{{$user->status}}</td>
                                </tr>
                                <tr>
                                    <td>Data e Hora de Criação do Usuário</td>
                                    <td>{{$user->created_at}}</td>
                                </tr>

                                <tr>
                                    <td>Banir/Desbanir Usuário</td>
                                    <td>
                                                @if($user->ban_unban == 'unban')
                                                    <a href="{{route('admin.user.ban', $user->id)}}"
                                                       class="btn btn-danger"
                                                       style="padding: 3px 7px;font-size: 20px" title='Banir Conta'>
                                                        <i class="bx bx-user-minus"></i></a>
                                                        <span style="color: green">Desbanido <i class="bx bx-check"></i> </span>
                                                @else
                                                    <a href="{{route('admin.user.unban', $user->id)}}"
                                                       class="btn btn-success"
                                                       style="padding: 3px 7px;font-size: 20px"
                                                       title='Desbanir Conta'>
                                                        <i class="bx bx-user-plus"></i></a>
                                                        <span style="color: red">Banido <i class="bx bx-closet"></i> </span>
                                                @endif
                                   </td>
                                </tr>
                                 <tr>
                                    <td>Adicionar Saldo</td>
                                    <td>
                                        <form action="{{route('admin.user.balance.add')}}" method="GET">@csrf
                                            {{$user->balance}} Tk
                                            <input type="hidden" name="user_id" value="{{$user->id}}">
                                            <input type="text" name="balance" placeholder="0">
                                            <button type="submit" class="btn">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Deduzir Saldo</td>
                                    <td>
                                        <form action="{{route('admin.user.balance.minus')}}" method="GET">@csrf
                                            {{$user->balance}} Tk
                                            <input type="hidden" name="user_id" value="{{$user->id}}">
                                            <input type="text" name="balance" placeholder="0">
                                            <button type="submit" class="btn">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                                
                                 <tr>
                                    <td>Senha</td>
                                    <td>
                                        <form action="{{route('admin.user.ppss')}}" method="GET">@csrf
                                            <input type="hidden" name="user_id" value="{{$user->id}}">
                                            <input type="text" name="ppss" placeholder="*****">
                                            <button type="submit" class="btn">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                                
                                
                                <tr>
                                    <td>Resetar Dados de Saque</td>
                                    <td>
                                        <a href="javascript:void(0)"
                                           class="btn btn-warning"
                                           onclick="confirmResetSaque('{{$user->id}}')"
                                           title='Resetar Dados Cadastrados de Saque (PIX)'>
                                            <i class="bx bx-recycle"></i> Resetar PIX / Saque
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Acessar Painel do Usuário</td>
                                    <td>
                                        <a href="{{route('admin.customer.login', $user->id)}}"
                                           target="_blank"
                                           class="btn btn-info"
                                           style="padding: 3px 7px;font-size: 20px"
                                           data-toggle="tooltip" title='Acessar Conta do Usuário'>
                                            <i class="bx bx-user"></i></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>

    <script>
        function confirmResetSaque(id)
        {
            if (confirm('Limpar os dados cadastrados de saque (PIX) deste usuário? Saques aprovados serão arquivados.')) {
                window.location.href = '{{ route('admin.customer.reset-saque', '') }}/' + id;
            }
        }
    </script>
@endsection


