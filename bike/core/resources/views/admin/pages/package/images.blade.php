@extends('admin.partials.master')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-primary">Editar Imagens dos Planos</h3>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="row">
            @foreach($packages as $pkg)
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $pkg->name }}</h5>
                        <p class="text-muted">Preço: R$ {{ number_format($pkg->price, 2, ',', '.') }} | Rend: R$ {{ number_format($pkg->daily_income ?? 0, 2, ',', '.') }}/dia</p>

                        <div class="text-center mb-3">
                            @if($pkg->photo)
                                <img src="{{ asset($pkg->photo) }}" alt="{{ $pkg->name }}" style="max-width:200px; max-height:200px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                            @else
                                <div class="bg-light p-5 text-muted">Sem imagem</div>
                            @endif
                        </div>

                        <form action="{{ route('admin.package.images.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" value="{{ $pkg->id }}">
                            <div class="form-group">
                                <label>Nova Imagem (jpg, png, webp - max 2MB)</label>
                                <input type="file" name="photo" class="form-control" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-upload"></i> Salvar Imagem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
