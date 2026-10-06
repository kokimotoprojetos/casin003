<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Improvment;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public $route = 'admin.task';
    public function index()
    {
        // A view admin/pages/task/index itera $tasks (@foreach($tasks ...)),
        // mas este controller passava $data = Task::first(), entao a pagina
        // dava "Undefined variable $tasks" (500).
        $tasks = Task::all();
        return view('admin.pages.task.index', compact('tasks'));
    }

    public function create($id=null)
    {
        $data = null;
        if ($id){
            $data = Task::find($id);
        }
        return view('admin.pages.improvement.insert', compact('data'));
    }

    public function insert_or_update(Request $request)
    {
        $this->validate($request,[
            'task_code'=> 'required',
            'remaining_code'=> 'required|numeric',
            'amount'=> 'required|numeric',
        ]);

        if ($request->id){
            $model = Task::findOrFail($request->id);
        }else{
            $model = new Task();
        }
        $model->task_code = $request->task_code;
        $model->remaining_code = $request->remaining_code;
        $model->amount = $request->amount;
        $model->save();
        return redirect()->route($this->route.'.index')->with('success', $request->id ? 'Melhoria atualizada com sucesso.' : 'Melhoria criada com sucesso.');
    }

    public function delete($id)
    {
        $model = Task::find($id);
        $model->delete();
        return redirect()->route($this->route.'.index')->with('success','Item excluído com sucesso.');
    }
}
