<div class="modal fade" id="formGenerateModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">@lang('Gerar formulário')</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
              <i class="las la-times"></i>
          </button>
        </div>
        <form class="{{ $formClassName ?? 'generate-form' }}">
            @csrf
              <div class="modal-body">
                <input type="hidden" name="update_id" value="">
                <div class="form-group">
                    <label>@lang('Tipo de formulário')</label>
                    <select name="form_type" class="form-control" required>
                        <option value="">@lang('Selecione um')</option>
                        <option value="text">@lang('Texto')</option>
                        <option value="textarea">@lang('Área de texto')</option>
                        <option value="select">@lang('Selecione')</option>
                        <option value="checkbox">@lang('Caixa de seleção')</option>
                        <option value="radio">@lang('Rádio')</option>
                        <option value="file">@lang('Arquivo')</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('É necessário')</label>
                    <select name="is_required" class="form-control" required>
                        <option value="">@lang('Selecione um')</option>
                        <option value="required">@lang('Obrigatório')</option>
                        <option value="optional">@lang('Opcional')</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('Etiqueta do formulário')</label>
                    <input type="text" name="form_label" class="form-control" required>
                </div>
                <div class="form-group extra_area">

                </div>
              </div>
              <div class="modal-footer">
                  <button type="submit" class="btn btn--primary w-100 h-45 generatorSubmit">@lang('Adicionar Adicionar')</button>
              </div>
          </form>
      </div>
    </div>
</div>


@push('script-lib')
<script src="{{ asset('assets/global/js/form_generator.js') }}"></script>
@endpush
