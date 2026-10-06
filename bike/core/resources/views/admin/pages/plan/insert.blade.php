@extends('admin.partials.master')
@section('admin_content')
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.plan.insert')}}" method="POST" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar Novo'}} Plano</div>
                                    <div>
                                        <a href="{{route('admin.plan.index')}}" class="btn btn-primary btn-sm"> <i
                                                class="bx bx-left-arrow"></i> Lista de Planos</a>
                                    </div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="name">Nome do Plano</label>
                                        <input type="text" class="form-control is-valid"
                                               name="name" id="name"
                                               placeholder="Ex: VIP 1" value="{{$data ? $data->name : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Este campo é obrigatório
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="interest">Juros (Rendimento)</label>
                                        <input type="number" step="0.01" class="form-control is-valid"
                                               name="interest" id="interest"
                                               placeholder="Ex: 10" value="{{$data ? $data->interest : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Valor fixo (R$) ou percentual (%) por período
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="interest_type">Tipo de Juros</label>
                                        <select name="interest_type" id="interest_type" class="form-control is-valid">
                                            <option value="0" @if($data && $data->interest_type == 0) selected @endif>Fixo (R$)</option>
                                            <option value="1" @if($data && $data->interest_type == 1) selected @endif>Percentual (%)</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Selecione se o juros é valor fixo ou percentual
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="time">Intervalo (horas)</label>
                                        <input type="number" class="form-control is-valid"
                                               name="time" id="time"
                                               placeholder="Ex: 24 (diário)" value="{{$data ? $data->time : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: 24 = diário, 1 = a cada hora, 10 = a cada 10h
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="repeat_time">Repetições (vezes)</label>
                                        <input type="number" class="form-control is-valid"
                                               name="repeat_time" id="repeat_time"
                                               placeholder="Ex: 30" value="{{$data ? $data->repeat_time : ''}}" required>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Número de vezes que o juros será pago
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="fixed_amount">Valor Fixo (R$)</label>
                                        <input type="number" step="0.01" class="form-control is-valid"
                                               name="fixed_amount" id="fixed_amount"
                                               placeholder="0 = valor variável" value="{{$data ? $data->fixed_amount : '0'}}">
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Se > 0, o investidor deve pagar exatamente este valor
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="minimum">Valor Mínimo (R$)</label>
                                        <input type="number" step="0.01" class="form-control is-valid"
                                               name="minimum" id="minimum"
                                               placeholder="0" value="{{$data ? $data->minimum : '0'}}">
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Usado quando valor fixo = 0
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="maximum">Valor Máximo (R$)</label>
                                        <input type="number" step="0.01" class="form-control is-valid"
                                               name="maximum" id="maximum"
                                               placeholder="0" value="{{$data ? $data->maximum : '0'}}">
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Usado quando valor fixo = 0
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="capital_back">Devolver Capital?</label>
                                        <select name="capital_back" id="capital_back" class="form-control is-valid">
                                            <option value="0" @if($data && $data->capital_back == 0) selected @endif>Não</option>
                                            <option value="1" @if($data && $data->capital_back == 1) selected @endif>Sim</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Se sim, o capital investido é devolvido ao final
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="lifetime">Pagamento Vitalício?</label>
                                        <select name="lifetime" id="lifetime" class="form-control is-valid">
                                            <option value="0" @if($data && $data->lifetime == 0) selected @endif>Não</option>
                                            <option value="1" @if($data && $data->lifetime == 1) selected @endif>Sim</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Se sim, paga juros para sempre
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="featured">Destaque?</label>
                                        <select name="featured" id="featured" class="form-control is-valid">
                                            <option value="0" @if($data && $data->featured == 0) selected @endif>Não</option>
                                            <option value="1" @if($data && $data->featured == 1) selected @endif>Sim</option>
                                        </select>
                                        <div class="valid-feedback">
                                            <i class="bx bx-radio-circle"></i>
                                            Nota: Planos em destaque aparecem primeiro
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <fieldset class="form-group">
                                                    <label for="image">Imagem do Plano <small>(Sugestão: 200x200px)</small></label>
                                                    <div class="custom-file">
                                                        <input type="file" name="image"
                                                               class="custom-file-input" id="inputGroupFile01"
                                                               onchange="showPreview(event)">
                                                        <label class="custom-file-label" for="inputGroupFile01">Escolher arquivo</label>
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="image_preview">
                                                    <img
                                                        src="{{$data && $data->image ? asset(view_image($data->image)) : asset(not_found_img())}}"
                                                        id="file-ip-1-preview" class="rounded" alt="Pré-visualização"
                                                        style="width: 100px;height: 100px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($data)
                                    <div class="col-sm-6">
                                        <label for="status">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" @if($data->status == 1) selected @endif>Ativo</option>
                                            <option value="0" @if($data->status == 0) selected @endif>Inativo</option>
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

                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div style="margin-top: .7rem !important">
                                        Envie as Informações do Plano
                                    </div>
                                    <div>
                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-success"><i
                                                    class="bx bx-plus"></i> {{$data ? 'Atualizar' : 'Salvar'}} </button>
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
    </script>
@endsection
