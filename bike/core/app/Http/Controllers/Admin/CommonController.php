<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CommonController extends Controller
{
    private array $allowedTables = [
        'plans', 'notices', 'tasks', 'bonuses', 'hirusliders', 'vipsliders',
        'packages', 'settings', 'gateways', 'fund_histories', 'payment_methods',
    ];

    public function status(Request $request)
    {
        $table = $request->input('table', '');
        if (!in_array($table, $this->allowedTables, true)) {
            return response()->json(['status' => false, 'msg' => 'Tabela não permitida.']);
        }

        $id = (int) $request->input('id', 0);
        $status = $request->input('status');

        $record = DB::table($table)->where('id', $id)->first();
        if (!$record) {
            return response()->json(['status' => false, 'msg' => 'Dados não encontrados.']);
        }

        DB::table($table)->where('id', $id)->update(['status' => $status]);
        return response()->json(['status' => true, 'msg' => 'Status alterado com sucesso.']);
    }
}
