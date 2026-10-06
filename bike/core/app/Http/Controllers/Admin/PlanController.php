<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public $route = 'admin.plan';

    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.pages.plan.index', compact('plans'));
    }

    public function create($id = null)
    {
        $data = null;
        if ($id) {
            $data = Plan::find($id);
        }
        return view('admin.pages.plan.insert', compact('data'));
    }

    public function view($id)
    {
        $data = Plan::find($id);
        return view('admin.pages.plan.view', compact('data'));
    }

    public function status($id)
    {
        $model = Plan::findOrFail($id);
        $model->status = $model->status == 1 ? 0 : 1;
        $model->update();
        return redirect()->route($this->route . '.index')->with('success', 'Status do plano atualizado.');
    }

    public function insert_or_update(Request $request)
    {
        $this->validate($request, [
            'name'         => 'required|string|max:40',
            'interest'     => 'required|numeric|gt:0',
            'interest_type'=> 'required|in:0,1',
            'time'         => 'required|numeric|min:1',
            'repeat_time'  => 'required|numeric|min:1',
        ]);

        if ($request->id) {
            $model = Plan::findOrFail($request->id);
            $model->status = $request->status ?? $model->status;
        } else {
            $model = new Plan();
            $model->status = 1;
        }

        $model->name         = $request->name;
        $model->interest     = $request->interest;
        $model->interest_type = $request->interest_type;
        $model->time         = $request->time;
        $model->time_name    = $request->time . 'h';
        $model->repeat_time  = $request->repeat_time;
        $model->fixed_amount = $request->fixed_amount ?? 0;
        $model->minimum      = $request->minimum ?? 0;
        $model->maximum      = $request->maximum ?? 0;
        $model->capital_back = $request->capital_back ?? 0;
        $model->lifetime     = $request->lifetime ?? 0;
        $model->featured     = $request->featured ?? 0;

        if ($request->hasFile('image')) {
            $path = uploadImage(false, $request, 'image', 'upload/plan/', 200, 200, $model->image);
            $model->image = $path ?? $model->image;
        }

        $model->save();

        return redirect()->route($this->route . '.index')->with('success', $request->id ? 'Plano atualizado com sucesso.' : 'Plano criado com sucesso.');
    }

    public function delete($id)
    {
        $model = Plan::find($id);
        if ($model) {
            if ($model->image) {
                deleteImage($model->image);
            }
            $model->delete();
        }
        return redirect()->route($this->route . '.index')->with('success', 'Plano excluído com sucesso.');
    }
}
