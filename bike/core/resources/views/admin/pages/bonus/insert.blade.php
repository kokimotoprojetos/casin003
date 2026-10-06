@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.bonus.insert')}}" method="POST" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar Novo'}} Bônus</div>
                                    <div>
                                        <a href="{{route('admin.bonus.index')}}" class="btn btn-primary btn-sm"> <i
                                                class="bx bx-left-arrow"></i> Lista de Bônus</a>
                                    </div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="bonus_name">Nome do Bônus</label>
                                        <input type="text" class="form-control is-valid"
                                               name="bonus_name" id="bonus_name"
                                               placeholder="Nome do bônus" value="{{$data ? $data->bonus_name : old('bonus_name')}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="set_service_counter">Definir contador de serviço</label>
                                        <input type="number" class="form-control is-valid"
                                               name="set_service_counter" id="set_service_counter"
                                               placeholder="Definir contador de serviço" value="{{$data ? $data->set_service_counter : old('set_service_counter')}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="price">Código <small>Ex:Ex:TB-0000001 </small> </label>
                                        <input type="text" class="form-control is-valid"
                                               name="code" id="price"
                                               placeholder="Ex:TB-0000001" value="{{$data ? $data->code : old('code')}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="winner">Vencedor (Contador de Pessoa que Ganhou)</label>
                                        <input type="text" class="form-control is-valid"
                                               name="winner" id="winner"
                                               placeholder="Ouvir: 0 a 7" value="{{$data ? $data->winner : old('winner')}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="amount">Valor Nota: 1 para obter valor aleatório</label>
                                        <input type="number" class="form-control is-valid"
                                               name="amount" id="amount"
                                               placeholder="Valor" value="{{$data ? $data->amount : old('amount')}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Submit Button -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div style="margin-top: .7rem !important">
                                        Envie Suas Informações de Bônus
                                    </div>
                                    <div>
                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-success"><i
                                                    class="bx bx-plus"></i>{{$data ? 'Atualizar' : 'Salvar'}} </button>
                                        </div>
                                    </div>
                                </div>
                            </h6>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script>
        function showPreview(event){
            if(event.target.files.length > 0){
                var src = URL.createObjectURL(event.target.files[0]);
                var preview = document.getElementById("file-ip-1-preview");
                preview.src = src;
                preview.style.display = "block";
            }
        }
        function calculateHour(_this){
            document.getElementById('hours').value = _this.value * 24
        }
    </script>
@endsection
