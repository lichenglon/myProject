<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 11:37
 */

namespace App\Http\Controllers\User;
use App\Http\Controllers\BaseController;
use DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

class FeedbackController extends BaseController{
	public function feedback(Request $request){
		$where = [];
		$search = isset($request->search) ? $request->search : false;
		$stime = isset($request->stime) ? $request->stime : '%';
		$etime = isset($request->etime) ? $request->etime : '%';
		$kwd_k = isset($request->kwd_k) ? $request->kwd_k : false;
		$msg = isset($request->msg) ? $request->msg : '%';
		if($kwd_k){
			$where[] = [$kwd_k,'like','%'.$msg.'%'];
		}else{
			$where[] = ['yourname','like','%'];
		}
		if($search )
		{
			$arr = DB::table('tb_email')->where('time','like',$stime)
										 ->where('time','like',$etime)
										 ->where($where)
										 ->paginate(5);
		}else{
			$arr = DB::table('tb_email')->paginate(5);
		}
		return view('user.feedback',[
										'arr' => $arr ,
										'total'=>DB::table('tb_email')->count(),
										'kwd_k' => $kwd_k,
										'msg' => $msg,
										'stime'=>$stime,
										'etime'=>$etime
									]);








		/*$stime = Input::get($request->stime) ? Input::get($request->stime) : '';    //开始时间
		$etime = Input::get($request->etime) ? Input::get($request->etime) : '';    //结束时间
		$kwd_k = Input::get($request->kwd_d) ? Input::get($request->kwd_k) : '';    //下拉框
		$kwd_v = Input::get($request->kwd_v) ? Input::get($request->kwd_v) : '';    //搜索值
		if($kwd_v != '' && $kwd_k !=''){
			$data = DB::table('tb_email')
						->where('tb_email'.$kwd_v,'like','%'.$kwd_v.'%')
						->where($stime,'like','%'.$etime.'%')
						->orderBy('id')
						->get()
						->toArray();
			var_dump($data);
			exit;

		}
		return view('user.feedback',
			 [
				 'stime'=>$stime,
				 'etime'=>$etime,
				 'kwd_k'=>$kwd_k,
				 'kwd_v'=>$kwd_v,
				'arr'=>DB::table('tb_email')->orderBy('id')->paginate(5),
				 'total'=>DB::table('tb_email')->count(),

			]);*/
	}

}