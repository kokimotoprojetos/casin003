@extends('admin.partials.master')
@section('admin_content')
    <style>
        label {
            text-transform: unset;
        }
    </style>
    <section id="dashboard-ecommerce">
        <div class="row">
            <div class="col-12">
                <form action="{{route('admin.setting.insert')}}" method="POST" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="id" value="{{$data ? $data->id : ''}}">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div>{{$data ? 'Atualizar' : 'Criar Novas'}} Configurações</div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-sm-12 mt-2">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label for="withdraw_notes">Taxa de Saque %</label>
                                                <input type="number" class="form-control is-valid"
                                                       name="withdraw_charge" id="withdraw_charge"
                                                       placeholder="Taxa de saque"
                                                       value="{{$data ? $data->withdraw_charge : old('withdraw_charge')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="minimum_withdraw">Saque Mínimo</label>
                                                <input type="number" class="form-control is-valid"
                                                       name="minimum_withdraw" id="minimum_withdraw"
                                                       placeholder="Saque mínimo"
                                                       value="{{$data ? $data->minimum_withdraw : old('minimum_withdraw')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="maximum_withdraw">Saque Máximo</label>
                                                <input type="number" class="form-control is-valid"
                                                       name="maximum_withdraw" id="maximum_withdraw"
                                                       placeholder="Saque máximo"
                                                       value="{{$data ? $data->maximum_withdraw : old('maximum_withdraw')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="site_title">Interruptor de Saque</label>
                                                <select class="form-control" name="w_time_status">
                                                    <option value="active" @if($data->w_time_status == 'active') selected @endif>Iniciar</option>
                                                    <option value="inactive" @if($data->w_time_status == 'inactive') selected @endif>Desligar</option>
                                                </select>
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é obrigatório
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="purchase_status">Bloquear Compra de Produtos</label>
                                                <select class="form-control" name="purchase_status">
                                                    <option value="active" @if($data->purchase_status == 'active') selected @endif>Liberar Compra</option>
                                                    <option value="inactive" @if($data->purchase_status == 'inactive') selected @endif>Bloquear Compra</option>
                                                </select>
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Bloqueado, mostra "EM BREVE" no lugar de "Comprar"
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="checkin">Valor de Check-in Diário</label>
                                                <input type="number" class="form-control is-valid"
                                                       name="checkin" id="checkin"
                                                       placeholder="Check-in diário"
                                                       value="{{$data ? $data->checkin : old('checkin')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="registration_bonus">Bônus de registro</label>
                                                <input type="number" class="form-control is-valid"
                                                       name="registration_bonus" id="registration_bonus"
                                                       placeholder="Bônus de registro"
                                                       value="{{$data ? $data->registration_bonus : old('registration_bonus')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="telegram">Telegram</label>
                                                <input type="text" class="form-control is-valid"
                                                       name="telegram" id="telegram"
                                                       placeholder="Telegram"
                                                       value="{{$data ? $data->telegram : old('telegram')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="whatsapp">WhatsApp</label>
                                                <input type="text" class="form-control is-valid"
                                                       name="whatsapp" id="whatsapp"
                                                       placeholder="WhatsApp"
                                                       value="{{$data ? $data->whatsapp : old('whatsapp')}}">
                                                <div class="valid-feedback">
                                                    <i class="bx bx-radio-circle"></i>
                                                    Nota: Este campo é opcional
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--<div class="card">-->
                    <!--    <div class="card-header">-->
                    <!--        <div class="row">-->
                    <!--            <div class="col-sm-6">-->
                    <!--                <label for="total_member_register_reword">Total Member Register Reword</label>-->
                    <!--                <input type="number" class="form-control is-valid"-->
                    <!--                       name="total_member_register_reword" id="total_member_register_reword"-->
                    <!--                       placeholder="Total Member Register Reword"-->
                    <!--                       value="{{$data ? $data->total_member_register_reword : old('total_member_register_reword')}}">-->
                    <!--                <div class="valid-feedback">-->
                    <!--                    <i class="bx bx-radio-circle"></i>-->
                    <!--                    Note: This is filed is optional-->
                    <!--                </div>-->
                    <!--            </div>-->

                    <!--            <div class="col-sm-6">-->
                    <!--                <label for="total_member_register_reword_amount">Total Member Register Reword Amount</label>-->
                    <!--                <input type="number" class="form-control is-valid"-->
                    <!--                       name="total_member_register_reword_amount" id="total_member_register_reword_amount"-->
                    <!--                       placeholder="Total Member Register Reword Amount"-->
                    <!--                       value="{{$data ? $data->total_member_register_reword_amount : old('total_member_register_reword_amount')}}">-->
                    <!--                <div class="valid-feedback">-->
                    <!--                    <i class="bx bx-radio-circle"></i>-->
                    <!--                    Note: This is filed is optional-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->

                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">
                                <div class="d-flex justify-content-between">
                                    <div style="margin-top: .7rem !important">
                                        Envie Suas Informações de Configuração
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
        function showPreview(event) {
            if (event.target.files.length > 0) {
                var src = URL.createObjectURL(event.target.files[0]);
                var preview = document.getElementById("file-ip-1-preview");
                preview.src = src;
                preview.style.display = "block";
            }
        }

    </script>
@endsection
