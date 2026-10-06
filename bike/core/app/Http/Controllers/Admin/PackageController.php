<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public $route = 'admin.package';
    public function index()
    {
        $packages = Package::get();
        return view('admin.pages.package.index', compact('packages'));
    }
    public function create($id=null)
    {
        $data = null;
        if ($id){
            $data = Package::find($id);
        }
        return view('admin.pages.package.insert', compact('data'));
    }

    public function view($id=null)
    {
        $data = Package::find($id);
        return view('admin.pages.package.view', compact('data'));
    }

    public function status($id)
    {
        $model = Package::findOrFail($id);
        $model->status = $model->status == 'active' ? 'inactive' : 'active';
        $model->update();
        return redirect()->route($this->route.'.index')->with('success', 'Status do pacote atualizado.');
    }

    public function insert_or_update(Request $request)
    {
        $this->validate($request,[
            'name'=> 'required',
            'label'=> 'required',
            'tab'=> 'required',
            'price'=> 'required|numeric',
            'validity'=> 'required|numeric',
            'commission_with_avg_amount'=> 'required|numeric',
            'ref1'=> 'required|numeric',
            'ref2'=> 'required|numeric',
            'ref3'=> 'required|numeric',
        ]);
        if ($request->id){
            $model = Package::findOrFail($request->id);
            $model->status = $request->status;
        }else{
            $model = new Package();
        }


        $packageExist = Package::where('id', $request->package_id)->first();


        $path = uploadImage(false ,$request, 'photo', 'upload/package/', 200, 200 ,$model->photo);
        $model->photo = $path ?? $model->photo;
        $model->name = $request->name;
        $model->package_id = $packageExist ? $packageExist->id : null;
        $model->label = $request->label;
        $model->tab = $request->tab;
        $model->price = $request->price;
        $model->validity = $request->validity;
        $model->commission_with_avg_amount = $request->commission_with_avg_amount;
        $model->ref1 = $request->ref1;
        $model->ref2 = $request->ref2;
        $model->ref3 = $request->ref3;
        $model->save();
        return redirect()->route($this->route.'.index')->with('success', $request->id ? 'Pacote atualizado com sucesso.' : 'Pacote criado com sucesso.');
    }

    public function images()
    {
        $packages = Package::where('tab', 'vip')->orderBy('id')->get();
        return view('admin.pages.package.images', compact('packages'));
    }

    public function updateImage(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:packages,id',
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $model = Package::findOrFail($request->id);
        $path = uploadImage(false, $request, 'photo', 'upload/package/', 200, 200, $model->photo);
        if ($path) {
            $model->photo = $path;
            $model->save();
        }

        return redirect()->route('admin.package.images')->with('success', 'Imagem do plano "' . $model->name . '" atualizada com sucesso.');
    }

    public function delete($id)
    {
        $model = Package::find($id);
        deleteImage($model->photo);
        $model->delete();
        return redirect()->route($this->route.'.index')->with('success','Item excluído com sucesso.');
    }

    public function set_bonus_vip($id)
    {
        return redirect()->route($this->route.'.index')->with('success','Função não disponível.');
    }
}
