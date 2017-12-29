<?php

namespace App\Http\Controllers\House;
use App\Http\Controllers\BaseController;
use App\Models\House_message;
use App\Models\House_image;
use App\Models\Landlord_message;
use App\Models\House_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use DB;
use App\libraries\libs\pinyin;
class HouseController extends BaseController {
	/**
	 * 房源列表
	 */
	public function houseLister() {
		$houseType = new House_type();
		$typeObject = $houseType->select('name')->get();
		$houseMessage = new House_message();
		$houseCount = $houseMessage->count();
		$gather = $houseMessage->orderBy('msgid','desc')->paginate(100);
		return view('house.houseLister',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
	}
	/**
	 *房源添加
	 */
	public function houseAdd() {
		$houseType = new House_type();
		$optionStr = $houseType->showOptionGetName();
		$nationArr = DB::table('nation')->get();
		return view('house.houseAdd',['optionStr'=>$optionStr,'nationArr'=>$nationArr]);
	}
	/**
	 *房源添加表单提交
	 */
	public function save(Request $param) {
		$houseData = Input::all();
		//国家
		$state = explode(',',$houseData['state']);
		$province =explode(',',$houseData['province']);
		//城市
		$city = explode(',',$houseData['city']);
		//实例化
		$houseMessage = new House_message();
		//查找数据
		$serialNumber = $houseMessage->select('serial_number')->orderBy('msgid', 'desc')->first();
		if(is_null($serialNumber)){
			//设置初始编号
			$serial_number = $state[2].$city[2].'11111111';
		} else {
			//取得上一次编号
			$str = $serialNumber->serial_number;
			//截取
			$intNum = substr($str,-8);
			//递增+1
			$serial_number = (int)$intNum+1;
			//拼接编号
			$serial_number = $state[2].$city[2].$serial_number;
		}
		$data = [
			//编号
			'serial_number' => $serial_number,
			//周边信息
			'rim_message' => isset($houseData['peripheral_information']) ? implode(',',$houseData['peripheral_information']) : '',
			//房屋设备
		    'house_facility' => isset($houseData['house_facility']) ? implode(',',$houseData['house_facility']) : '',
		    //房东身份ID
		    'landlord_id' => $houseData['landlord_identity'],
		    //中介ID
		    'intermediary_id' => Session::get('user_id') ? Session::get('user_id') : '',
			//房源位置
			'house_location' => $houseData['house_location'],
			//房源结构
			'house_structure' => $houseData['house_structure'],
			//房源价格
			'house_price' => $houseData['house_price'],
			//房源大小
			'house_size' => $houseData['house_size'],
			//房源类型
			'house_type' => $houseData['house_type'],
			//房源关键字
			'house_keyword' => $houseData['house_keyword'],
			//房源简介
			'house_brief' => $houseData['house_brief'],
			//起租期
			'house_rise' => $houseData['house_rise'] ? $houseData['house_rise'] : date('Y-m-d'),
			//最长租期
			'house_duration' => $houseData['house_duration'] ? $houseData['house_duration'] : date('Y-m-d'),
			//房屋状态
			'house_status' => $houseData['house_status'],
			//国家
			'state' => $state[0],
			//省份
			'province' => $province[0],
			//城市
			'city' => $city[0],
			//押金
			'cash_pledge' => $houseData['cash_pledge'],
			//预付款比例
			'payment_proportion' => $houseData['payment_proportion'],
			//结算方式
			'knot_way' => $houseData['knot_way']
		];
		$houseId = $houseMessage->insertGetId($data);  //保存
		//接收文件
		$files = $param->file('upload');
		if ($houseId) {
			$landlordMessage = new Landlord_message();
			$landlordDate = [
				//房源中介ID
				'intermediary_id' => Session::get('user_id') ? Session::get('user_id') : '',
				//房东姓名
				'landlord_name' => $houseData['landlord_name'],
				//房东证件ID
				'landlord_identity' => $houseData['landlord_identity'],
				//房东邮箱
				'landlord_email' => $houseData['landlord_email'] ? $houseData['landlord_email'] : '',
				//房东联系号码
				'landlord_phone' => $houseData['landlord_phone'],
				//房东性别
				'landlord_sex' => $houseData['landlord_sex'],
				//房东联系地址
				'landlord_site' => $houseData['landlord_site'],
				//房东备注
				'landlord_remark' => $houseData['landlord_remark'],
				//房源ID
				'house_id' => $houseId
			];
			//房东信息插入
			$landlordId = $landlordMessage->insertGetId($landlordDate);
			if ($landlordId && $files) {
				//遍历文件
				foreach ($files as $file) {
					//实力化文件模型类
					$houseImage = new House_image();
					//保存
					$imagename = $file->store('','local');
					if ($imagename) {
						//房源ID
						$houseImage->house_msg_id = $houseId;
						//图片名称
						$houseImage->house_imagename = $imagename;
						//保存
						$houseImage->save();
					}
				}
				return redirect('house/houseAdd')->with('success','新增房源成功！');
			} elseif ($landlordId) {
				return redirect('house/houseAdd')->with('success','新增房源成功！未上传房源图片');
			}
		} else {
			echo "<script>alert('添加失败');history.go(-1);</script>";
		}
	}
	/**
	 *房源更新列表
	 */
	public function updateList() {
		$houseType = new House_type();
		$typeObject = $houseType->select('name')->get();
		$houseMessage = new House_message();
		$houseCount = $houseMessage->count();
		$gather = $houseMessage->orderBy('msgid','desc')->paginate(100);
		return view('house.updateList',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
	}

	/**
	 *房源修改详细页
	 */
	public function detail($id) {
		$houseType = new House_type();
		$optionStr = $houseType->showOptionGetName();
		$houseMsg = DB::table('house_message')
				->join('landlord_message', 'house_message.msgid', '=', 'landlord_message.house_id')
				->select('house_message.*', 'landlord_message.*')
				->where('msgid',$id)
				->first();
		$houseImg = new House_image();
		$imgArr = $houseImg->where('house_msg_id','=',$id)->get();
		$nationArr = DB::table('nation')->get();

		return view('house.updateDetail',['houseMsg'=>$houseMsg,'imgArr'=>$imgArr,'optionStr'=>$optionStr,'nationArr'=>$nationArr]);
	}

	/**
	 *Ajax请求获取地区
	 */
	public function region() {
		if(isset($_GET['p_nation_ID'])){
			$p_nation_ID = $_GET['p_nation_ID'];
			$provinceArr = DB::table('province')->where('p_nation_ID',$p_nation_ID)->get()->toArray();
			return $provinceArr;
		}
		if(isset($_GET['c_province_ID'])){
			$c_province_ID = $_GET['c_province_ID'];
			$cityArr = DB::table('city')->where('c_province_ID',$c_province_ID)->get()->toArray();
			return $cityArr;
		}
	}


	/**
	 *Ajax请求删除图片
	 */
	public function del() {
		$id = $_GET['id'];
		$houseImg = new House_image();
		$houseImgs = $houseImg->where('imgid',$id)->first();
		$imagename = $houseImgs->house_imagename;
		@unlink('./uploads/'.$imagename);
		$re = $houseImg->where('imgid',$id)->delete();
		if ($re) {
			return '1';
		} else {
			return '0';
		}
	}
	/**
	 *房源信息修改
	 */
	public function uSave(Request $param) {
		$msgId = $param->msgId;
		$landId = $param->landId;
		$houseData = Input::all();
		$data = [
			//周边信息
				'rim_message' => isset($houseData['peripheral_information']) ? implode(',',$houseData['peripheral_information']) : '',
			//房屋设备
				'house_facility' => isset($houseData['house_facility']) ? implode(',',$houseData['house_facility']) : '',
			//房东身份ID
				'landlord_id' => $houseData['landlord_identity'],
			//房源位置
				'house_location' => $houseData['house_location'],
			//房源结构
				'house_structure' => $houseData['house_structure'],
			//房源价格
				'house_price' => $houseData['house_price'],
			//房源大小
				'house_size' => $houseData['house_size'],
			//房源类型
				'house_type' => $houseData['house_type'],
			//房源关键字
				'house_keyword' => $houseData['house_keyword'],
			//房源简介
				'house_brief' => $houseData['house_brief'],
			//起租期
				'house_rise' => $houseData['house_rise'] ? $houseData['house_rise'] : date('Y-m-d'),
			//最长租期
				'house_duration' => $houseData['house_duration'] ? $houseData['house_duration'] : date('Y-m-d'),
			//房屋状态
				'house_status' => $houseData['house_status'],
			//押金
				'cash_pledge' => $houseData['cash_pledge'],
			//预付款比例
				'payment_proportion' => $houseData['payment_proportion'],
			//结算方式
				'knot_way' => $houseData['knot_way']
		];
		DB::table('house_message')->where('msgid', $msgId)->update($data);
		$landlordDate = [
			//房源中介ID
				'intermediary_id' => Session::get('user_id') ? Session::get('user_id') : '',
			//房东姓名
				'landlord_name' => $houseData['landlord_name'],
			//房东证件ID
				'landlord_identity' => $houseData['landlord_identity'],
			//房东邮箱
				'landlord_email' => $houseData['landlord_email'] ? $houseData['landlord_email'] : '',
			//房东联系号码
				'landlord_phone' => $houseData['landlord_phone'],
			//房东性别
				'landlord_sex' => $houseData['landlord_sex'],
			//房东联系地址
				'landlord_site' => $houseData['landlord_site'],
			//房东备注
				'landlord_remark' => $houseData['landlord_remark'],
		];
		DB::table('landlord_message')->where('landid', $landId)->update($landlordDate);
		$files = $param->file('upload');
		if ($files) {
			foreach ($files as $file) {
				$houseImage = new House_image();
				$imageName = $file->store('','local');
				if ($imageName) {
					$houseImage->house_msg_id = $msgId;
					$houseImage->house_imagename = $imageName;
					$houseImage->save();
				}
			}
		}
		return redirect('house/updateList/detail/'.$msgId)->with('success','更新成功！');
	}
	/**
	 *房源详细信息
	 */
	public function houseDetail($id) {
		$houseMsg = DB::table('house_message')
				->join('landlord_message', 'house_message.msgid', '=', 'landlord_message.house_id')
				->select('house_message.*', 'landlord_message.*')
				->where('msgid',$id)
				->first();
		$houseImg = new House_image();
		$imgArr = $houseImg->where('house_msg_id','=',$id)->get();
		return view('house.houseDetail',['houseMsg'=>$houseMsg,'imgArr'=>$imgArr]);
	}

	/**
	 *房源检索 类型
	 *@param type
	 */
	public function findType(Request $request) {
		$type = $request->type;
		$houseType = new House_type();
		$typeObject = $houseType->select('name')->get();
		$houseMessage = new House_message();
		$houseCount = $houseMessage->count();
		$gather = $houseMessage->where('house_type',$type)->orderBy('msgid','desc')->paginate(100);
		if(isset($request->hidden)){
			return view('house.houseLister',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
		} else {
			return view('house.updateList',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
		}
	}

	/**
	 *房源检索 日期
	 *@param date
	 */
	public function findDate(Request $request) {
		$rise = $request->rise;
		$duration = $request->duration;
		$houseType = new House_type();
		$typeObject = $houseType->select('name')->get();
		$houseMessage = new House_message();
		$houseCount = $houseMessage->count();
		$gather = $houseMessage->where('house_rise',$rise)->where('house_duration',$duration)->orderBy('msgid','desc')->paginate(100);
		if(isset($request->hidden)){
			return view('house.houseLister',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
		} else {
			return view('house.updateList',['houseObj'=>$gather,'typeObject'=>$typeObject,'houseCount'=>$houseCount]);
		}
	}

	/**
	 *导出 Excel
	 */
	public function houseExcel() {
		$data = DB::table('house_message')->select()->get('serial_number','house_location')->toArray();
		$title = ['房源ID号','编号','房源位置','房源结构','房源价格','房源大小/平方','房源类型','房屋设备','关键字','房源简介','起租期','租期时长','状态','房东证件号','房源中介ID','国家','省','城市','周边信息','押金','预付款比例','结算方式'];
		exportData($title,$data,'房源信息'.date('Y-m-d'));
	}

	/**
	 *地图
	 */
	public function houseMap() {
		return view('house.houseMap');
	}

 }