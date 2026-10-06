@extends($activeTemplate.'layouts.master')
@section('content')
<section class="pt-150 pb-150">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-bg">
                    <div class="card-body">
                        <form action="{{ route('ticket.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">@lang('Nome')</label>
                                    <input type="text" name="name" value="{{@$user->firstname . ' '.@$user->lastname}}" class="form-control form--control" required readonly>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">@lang('Endereço de email')</label>
                                    <input type="email"  name="email" value="{{@$user->email}}" class="form-control form--control" required readonly>
                                </div>

                                <div class="form-group col-md-6">
                                    <label class="form-label">@lang('Assunto')</label>
                                    <input type="text" name="subject" value="{{old('subject')}}" class="form-control form--control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">@lang('Prioridade')</label>
                                    <select name="priority" class="form-control form--control" required>
                                        <option value="3">@lang('Alto')</option>
                                        <option value="2">@lang('Médio')</option>
                                        <option value="1">@lang('Baixo')</option>
                                    </select>
                                </div>
                                <div class="col-12 form-group">
                                    <label class="form-label">@lang('Mensagem')</label>
                                    <textarea name="message" id="inputMessage" rows="6" class="form-control form--control" required>{{old('message')}}</textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary addFile">
                                        <i class="fa fa-plus"></i> @lang('Adicionar novo')
                                    </button>
                                </div>
                                <div class="file-upload">
                                    <label class="form-label">@lang('Anexos')</label> <small class="text-danger">@lang('Máximo de 5 arquivos podem ser carregados'). @lang('O tamanho máximo de upload é') {{ ini_get('upload_max_filesize') }}</small>
                                    <input type="file" name="attachments[]" id="inputAttachments" class="form-control form--control mb-2"/>
                                    <div id="fileUploadsContainer"></div>
                                    <p class="ticket-attachments-message text-muted">
                                        @lang('Extensões de arquivo permitidas'): .@lang('jpg'), .@lang('JPEG'), .@lang('png'), .@lang('pdf'), .@lang('documento'), .@lang('docx')
                                    </p>
                                </div>

                            </div>

                            <div class="form-group">
                                <button class="btn btn-primary w-100" type="submit"><i class="fa fa-paper-plane"></i>&nbsp;@lang('Enviar')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('style')
    <style>
        .input-group-text:focus{
            box-shadow: none !important;
        }
    </style>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";
            var fileAdded = 0;
            $('.addFile').on('click',function(){
                if (fileAdded >= 4) {
                    notify('error','You\'ve added maximum number of file');
                    return false;
                }
                fileAdded++;
                $("#fileUploadsContainer").append(`
                    <div class="input-group my-3">
                        <input type="file" name="attachments[]" class="form-control form--control" required />
                        <button type="button" class="input-group-text btn-danger text-white remove-btn"><i class="las la-times"></i></button>
                    </div>
                `)
            });
            $(document).on('click','.remove-btn',function(){
                fileAdded--;
                $(this).closest('.input-group').remove();
            });
        })(jQuery);
    </script>
@endpush
