<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\BaseController;
use App\libraries\libs\pinyin;
use App\Models\TbuyDzd;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\TbuyLogistics;
use App\Models\TbuyOrderDetails;
use DB;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    public $payStatus = [
        0=>'等待支付',
        1=>'支付成功',
        2=>'支付失败',
        3=>'订单关闭',
    ];
    //订单状态
    public $orderStatus = [
        '1' => '未审核',
        '2' => '审核中',
        '3' => '订单驳回',
        '4' => '订单确认',
        '5' => '订单取消',
        '6' => '合同上传',
        '7' => '未付款'
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $order_status = $this->getOrderStatus();
        $where = [];
        /*$pay_status = $request->input('pay_status');
        $status = $request->input('status');
        $order_no = $request->input('order_no');
        unset($_REQUEST['_token']);
        //搜索
        if(isset($request->search)){

            if(!empty($order_no)){
                $where['tbuy_order.order_no'] = $order_no;
            }
            if(!empty($status)){
                $where['tbuy_order_details.status'] = $status;
            }
            if($request->is_balance !== null){
                $where['tbuy_order_details.is_balance'] = $request->is_balance ;
            }
            if($pay_status != ''){
                $where['tbuy_order.status'] = $pay_status;
            }
            if(!empty($request->keyword)){
                $where[] = [$request->keyword_type, 'like', '%'.$request->keyword.'%'];
            }
            $where[] = ['create_time', '>=', $request->begin_time];
            $where[] = ['create_time', '<=', $request->end_time];
        }*/

        $order_list = DB::table('order')->where($where)->orderBy('order_id', 'desc')->paginate(20);



        return view('order.order.index',['data'=>$order_list,'order_status'=>$order_status]);
    }


    /**
     * Excel导出订单数据
     * @param Request $request
     */
    public function exportOrderData(){

    }

    /**
     * 订单发货
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function ship(Request $request, $id){
        $details_id = (int)$id;

        $data = TbuyOrderDetails::where(['details_id' => $details_id])->first();
        if(empty($data)) exit('数据不存在');

        $express_company = [
            '邮政包裹'    => 'youzhengguonei',
            'EMS'         => 'ems',
            '顺丰'        => 'shunfeng',
            '申通'        => 'shentong',
            '圆通'        => 'yuantong',
            '中通'        => 'zhongtong',
            '汇通'        => 'huitongkuaidi',
            '韵达'        => 'yunda',
            '宅急送'      => 'zhaijisong',
            '天天'        => 'tiantian',
            '京东'        => 'jd',
            '优速'        => 'youshuwuliu',
            '自提'        => ''
        ];

        if($request->isMethod('post'))
        {
            $express_company = $request->input('express_company');
            $express_company_no = $request->input('express_company_no');
            $express_no = $request->input('express_no');

            unset($_REQUEST['express_company'],$_REQUEST['express_company_no'],$_REQUEST['express_no'],$_REQUEST['_token']);
            if($data->status != 1)
            {
                return $this->ajaxError('订单状态已变更，无法发货');
            }

            if(empty($express_company)){
                return $this->ajaxError('请选择快递公司');
            }
            if(empty($express_no)){
                return $this->ajaxError('请填写快递单号');
            }


            $logistics_data = array(
                'lid'           => TbuyLogistics::getNextSeq(),
                'details'       => $details_id,
                'logistics_type'    => 0,
                'express_company'   => $express_company,
                'express_no'        => $express_no,
                'express_company_no'    => $express_company_no,
            );
            $res = TbuyLogistics::insert($logistics_data);
            if(!$res) return $this->ajaxError();

            TbuyOrderDetails::where(['details_id' => $details_id])->update(['status' => 2]);


            $redirect_url = 'order/order';
            if(isset($request->_show)){
                $redirect_url = 'order/order/'.$data->order_id;
                unset($_REQUEST['_show']);
            }

            return $this->ajaxSuccess('发货成功！', url($redirect_url.'?'.http_build_query($_REQUEST)));
        }else{

            $consignee_info = DB::table('tbuy_order_consignee')->where(['order_id' => $data->order_id])->first();

            return view('order.order.ship', ['express_company' => $express_company, 'details_id' => $details_id, 'consignee_info' => $consignee_info]);
        }

    }

    /**
     * 订单详情
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {

        $orderDetail = DB::table('tbuy_order')
            ->join('tbuy_order_details', 'tbuy_order.order_id', '=', 'tbuy_order_details.order_id')
            ->join('tnet_reginfo', 'tbuy_order.node_id', '=', 'tnet_reginfo.nodeid')
            ->join('tbuy_order_consignee', 'tbuy_order.order_id', '=', 'tbuy_order_consignee.order_id')
            ->where(['tbuy_order.order_id'=>$id])
            ->first(['tbuy_order_details.status','tnet_reginfo.nodecode','tnet_reginfo.nodename','tbuy_order.order_id','tbuy_order.create_time','tbuy_order.pay_type_group','tbuy_order.pay_time','tbuy_order.remarks','tbuy_order_consignee.consignee_name','tbuy_order_consignee.mobile_no','tbuy_order_consignee.country','tbuy_order_consignee.province','tbuy_order_consignee.city','tbuy_order_consignee.region','tbuy_order_consignee.address','tbuy_order_details.product_id','tbuy_order_details.product_name','tbuy_order_details.buy_count','tbuy_order_details.price','tbuy_order_details.sku_id','tbuy_order_details.details_id']);

        $orderDetail->price = round($orderDetail->price, 2);
        $pay_type_group = json_decode($orderDetail->pay_type_group, true);
        $str = '';
        foreach ($pay_type_group as $k => $v) {
            $str.= $v['Remarks'] . ':' . $v['PayAmount']/ 100 . '，';
        }
        $orderDetail->pay_group = trim($str, '，');
        $supplier = DB::table('tbuy_product')->join('finance_supplier','tbuy_product.supplier_id','=','finance_supplier.supplier_id')->where('product_id',$orderDetail->product_id)->first(['finance_supplier.supplier_id','finance_supplier.supplier_name']);

        if(!empty($supplier)){
            $orderDetail->supplier_name = $supplier->supplier_name;
            $orderDetail->supplier_id = $supplier->supplier_id;
        }

        $orderDetail->sku = DB::table('tbuy_sku')->where('sku_id',$orderDetail->sku_id)->first(['hl_integral','pv','ga_integral']);

        $expressData = $this->logistics($orderDetail->details_id);

        return view('order.order.show',['orderDetail'=>$orderDetail,'orderStatus'=>$this->orderStatus,'expressData'=>$expressData]);
    }

    public function afterSale($id)
    {

        return view('order.order.after_sale');
    }

    /**
     * 订单的物流信息
     * @param $details_id
     * @return array
     */
    private function logistics($details_id){
        $logistics = DB::table('tbuy_logistics')->where('details', $details_id)->first();
        $expressData=['data'=>[], 'express_no'=>''];
        if($logistics){
            $result = kuaidi(pinyin::get($logistics->express_company), $logistics->express_no);
            $expressData['express_company']=$logistics->express_company;
            $expressData['data'] = $result['data']?:[];
            $expressData['express_no'] = $logistics->express_no;
        }
        return $expressData;
    }

    //订单详情
    public function detail($id) {
        $result = DB::table('order')->where('order_id', $id)->first();
        return view("order.order.detail",['result'=>$result,'orderStatus'=>$this->orderStatus]);
    }

    /**
     *导出详情
     */
    public function detail_excel($id)
    {
        $data = DB::table('order')->where('order_id', $id)->select('order_no','creat_time','name','house_no','house_name','house_position','house_price','rent_time','sign_time','sign_position','order_status','house_eva','intermediary_eva')->get()->toArray();
        $title = ['订单号','日期','租客姓名','房源编号','房源名称','房源位置','价格','租期','签约时间','签约地点','订单状态','房屋评价','中介评价'];
        exportData($title,$data,'房源信息'.date('Y-m-d'));
    }
}
