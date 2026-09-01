<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;use Illuminate\Support\Facades\DB;use Inertia\{Inertia,Response};
class AuditLogController extends Controller { public function __invoke():Response{return Inertia::render('Admin/Logs/Audit',['logs'=>DB::table('audit_logs')->leftJoin('users','users.id','=','audit_logs.user_id')->select('audit_logs.*','users.name as user_name')->latest('audit_logs.created_at')->paginate(50)]);} }
