<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Apply;

class StampCorrectionRequestController extends Controller
{
    /**
     * 申請一覧画面表示
     */
    public function index(Request $request)
    {
        //管理者
        if ($request->is_admin) {
            $applies = Apply::with('user', 'attendance')->get();
            return view('admin.apply-list', compact('applies'));
        }

        //一般
        $applies = Apply::with('user', 'attendance')->where('user_id', auth()->id())->get();
        return view('attendance.apply', compact('applies'));
    }
}
