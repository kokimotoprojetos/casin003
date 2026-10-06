@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.fund.insert')}}" method="POST" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar Novo'}} Fundo</div>
                                    <div>
                                        <a href="{{route('admin.fund.index')}}" class="btn btn-primary btn-sm"> <i
                                                class="bx bx-left-arrow"></i> Lista de Fundos</a>
                                    </div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="name">Nome do Fundo</label>
                                        <input type="text" class="form-control is-valid"
                                               name="name" id="name"
                                               placeholder="Nome" value="{{$data ? $data->name : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="title">Título</label>
                                        <input type="text" class="form-control is-valid"
                                               name="title" id="title"
                                               placeholder="título" value="{{$data ? $data->title : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-12 mt-2">
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">Enviar Foto <small>{Sugestão:
                                                            tamanho 200X200(px)}</small> </label>
                                                    <div class="custom-file">
                                                        <input type="file" name="photo"
                                                               class="custom-file-input is-valid" id="inputGroupFile01"
                                                               @if(!$data) required
                                                               @else @endif onchange="showPreview(event)">
                                                        <label class="custom-file-label" for="inputGroupFile01">Escolher
                                                            arquivo</label>
                                                        <div class="valid-feedback">
                                                            <i class="bx bx-radio-circle"></i>
                                                            Nota: Imagem do fundo é obrigatória
                                                        </div>
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="image_preview">
                                                    <img
                                                        src="{{$data ? asset(view_image($data->photo)) :  asset(not_found_img())}}"
                                                        id="file-ip-1-preview" class="rounded" alt="Imagem de Pré-visualização"
                                                        style="width: 100px;height: 100px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="price">Investimento Mínimo</label>
                                        <input type="number" class="form-control is-valid"
                                               name="minimum_invest" id="minimum_invest"
                                               placeholder="Investimento mínimo" value="{{$data ? $data->minimum_invest : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="validity">Dias de validade</label>
                                        <input type="number" class="form-control is-valid"
                                               name="validity" id="validity"
                                               placeholder="Dias de validade" value="{{$data ? $data->validity : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="commission">Valor de retorno do fundo</label>
                                        <input type="number" class="form-control is-valid"
                                               name="commission" id="commission"
                                               placeholder="Comissão do fundo" value="{{$data ? $data->commission : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    @if($data)
                                    <div class="col-sm-6">
                                        <label for="commission">Status do fundo</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="upcoming" @if($data->status == 'upcoming') selected @endif>Em breve</option>
                                            <option value="active" @if($data->status == 'active') selected @endif>Ativo</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                    @endif
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
                                        Envie Suas Informações de Fundo
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
