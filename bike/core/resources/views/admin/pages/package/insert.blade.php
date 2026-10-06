@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.package.insert')}}" method="POST" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar Novo'}} Pacote</div>
                                    <div>
                                        <a href="{{route('admin.package.index')}}" class="btn btn-primary btn-sm"> <i
                                                class="bx bx-left-arrow"></i> Lista de Pacotes</a>
                                    </div>
                                </div>
                            </h4>
                        </div>
                        <?php
                        $packages = \App\Models\Package::whereNull('package_id')->get();
                        ?>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    @if($data)
                                        <div class="col-sm-6">
                                            <label for="package_id">Categoria do Pacote</label>
                                            <select name="package_id" id="package_id" class="form-control is-valid">
                                                <option value="">Selecione uma opção</option>
                                                @foreach($packages as $package)
                                                    <option value="{{$package->id}}" @if($data && $data->package_id == $package->id) selected @endif>{{$package->name}} ({{$package->label}})</option>
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                Nota: Este campo é obrigatório
                                            </div>
                                        </div>
                                    @else
                                    <div class="col-sm-6">
                                        <label for="package_id">Categoria do Pacote</label>
                                        <select name="package_id" id="package_id" class="form-control is-valid">
                                            <option value="">Selecione uma opção</option>
                                            @foreach($packages as $package)
                                                <option value="{{$package->id}}">{{$package->name}} ({{$package->label}})</option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-sm-6">
                                        <label for="tab">Aba</label>
                                        <select name="tab" id="tab" class="form-control is-valid">
                                            <option value="">Selecione uma opção</option>
                                            <option value="vip" @if($data && $data->tab == 'vip') selected @endif>VIP</option>
                                            <option value="fixed" @if($data && $data->tab == 'fixed') selected @endif>FIXED</option>
                                            <option value="event" @if($data && $data->tab == 'event') selected @endif>EVENT</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="name">Nome do Pacote</label>
                                        <input type="text" class="form-control is-valid"
                                               name="name" id="name"
                                               placeholder="Nome" value="{{$data ? $data->name : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="label">Rótulo</label>
                                        <input type="text" class="form-control is-valid"
                                               name="label" id="label"
                                               placeholder="rótulo" value="{{$data ? $data->label : ''}}" required>
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
                                                            Nota: Imagem do pacote é obrigatória
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
                                        <label for="price">Preço</label>
                                        <input type="number" class="form-control is-valid"
                                               name="price" id="price"
                                               placeholder="Preço" value="{{$data ? $data->price : ''}}" required>
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
                                        <label for="commission_with_avg_amount">Comissão com valor médio</label>
                                        <input type="number" class="form-control is-valid"
                                               name="commission_with_avg_amount" id="commission_with_avg_amount"
                                               placeholder="Comissão com valor médio" value="{{$data ? $data->commission_with_avg_amount : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>


                                        <div class="col-sm-6">
                                            <label for="ref1">Comissão de Primeira Referência (%)</label>
                                            <input type="text" class="form-control is-valid"
                                                   name="ref1" id="ref1"
                                                   placeholder="" value="{{$data ? $data->ref1 : old('ref1')}}" required>
                                            <div class="valid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                Nota: Este campo é obrigatório
                                            </div>
                                        </div>



                                        <div class="col-sm-6">
                                            <label for="ref2">Comissão de Segunda Referência (%)</label>
                                            <input type="text" class="form-control is-valid"
                                                   name="ref2" id="ref2"
                                                   placeholder="" value="{{$data ? $data->ref2 : old('ref2')}}" required>
                                            <div class="valid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                Nota: Este campo é obrigatório
                                            </div>
                                        </div>



                                        <div class="col-sm-6">
                                            <label for="ref3">Comissão de Terceira Referência (%)</label>
                                            <input type="text" class="form-control is-valid"
                                                   name="ref3" id="ref3"
                                                   placeholder="" value="{{$data ? $data->ref3 : old('ref3')}}" required>
                                            <div class="valid-feedback">
                                                <i class="bx bx-radio-circle"></i>
                                                Nota: Este campo é obrigatório
                                            </div>
                                        </div>





                                    @if($data)
                                    <div class="col-sm-12">
                                        <label for="status">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" @if($data->status == 'active') selected @endif>Ativo</option>
                                                <option value="inactive" @if($data->status == 'inactive') selected @endif>Inativo</option>
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
                                        Envie Suas Informações de Pacote
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
