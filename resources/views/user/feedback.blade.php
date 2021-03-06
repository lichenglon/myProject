@extends('layouts.default')


@section('content')



    <div class="box">
        <div class="box-body">
            <div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
                <form action="{{ url('user/feedback') }}" method="post" id="SUBMIT">
                    <span class="select-box inline" style="width:100%;">
                        {{ csrf_field() }}

                        {{--<h4 class="bg-info" style="padding-top:10px; padding-bottom:10px; font-size:14px; overflow:hidden;">
                            <span style="line-height:34px;">订单列表</span>
                            <div style="float:right;"><a href="{{ url('order/order/exportOrderData') }}" type="button" class="btn btn-default">导出EXCEL</a></div>
                        </h4>--}}

                        <div class="row" style="padding:20px;">
                            <label><b>@lang("feedback.Keyword_search")</b></label>&nbsp;&nbsp;
                            <select class="form-control" name="kwd_k" id="kwd_k">
                                <option value="">@lang("feedback.Please_select_a")</option>
                                <option value="yourname">@lang("feedback.The_name")</option>
                                <option value="email">@lang("feedback.Email_address")</option>
                                <option value="phonenumber">@lang("feedback.The_phone_number")</option>
                                <option value="message">@lang("feedback.detailed_information")</option>
                            </select>&nbsp;&nbsp;
                            <input type="text" class="form-control" name="msg" id="msg" value="" placeholder="">&nbsp;&nbsp;&nbsp;&nbsp;


                            <label><b>@lang("feedback.Submission_date")：</b> </label>
                            <input type="text" name="stime" id="stime" class="form-control" value="" placeholder="" />&nbsp;&nbsp;@lang('order.to')&nbsp;&nbsp;
                            <input type="text" name="etime" id="etime" class="form-control" value="" placeholder=""/>&nbsp;&nbsp;&nbsp;&nbsp;

                            <input name="search" type="submit" class="btn btn-default" id="seek" value="@lang('order.search')">&nbsp;&nbsp;
                            <button type="reset"  class="btn btn-default" name="username" id="reset" >@lang('order.Reset')</button>&nbsp;&nbsp;
                        </div>
                    </span>
                </form>
                {{--<div class="row" style="margin-bottom:10px;"></div>--}}


                <div class="row" style="height:600px;">
                    <div class="col-sm-12">
                        <table id="example1" class="table table-bordered table-striped dataTable" role="grid"
                               aria-describedby="example1_info">
                            <thead>
                            <tr role="row">
                                <th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1"
                                    aria-sort="ascending"
                                    aria-label="Rendering engine: activate to sort column descending"
                                    style="width:10%;">@lang("feedback.The_name")
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1"
                                    aria-label="Browser: activate to sort column ascending" style="width:10%;">
                                    @lang("feedback.Email_address")
                                </th>

                                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1"
                                    aria-label="Platform(s): activate to sort column ascending" style="width:10%;">
                                    @lang("feedback.The_phone_number")
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1"
                                    aria-label="Platform(s): activate to sort column ascending" style="width:15%;">
                                    @lang("feedback.The_date_of")
                                </th>
                                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1"
                                    aria-label="Platform(s): activate to sort column ascending" style="width:15%;">
                                    @lang("feedback.detailed_information")
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($arr as $value)
                                    <tr>
                                        <td>{{$value->yourname}}</td>
                                        <td>{{$value->email}}</td>
                                        <td>{{$value->phonenumber}}</td>
                                        <td>{{date('Y-m-d H:i:s',$value->time)}}</td>
                                        <td>{{$value->message}}</td>
                                    </tr>
                                    @endforeach
                            </tbody>

                        </table>
                        <div class="page_list">
                            @if(!empty($arr))
                            {{$arr->appends(Request::input())->links()}}
                            @endif
                            <div style="display:inline-block; margin-bottom:25px;">
                                <span style="float:left; margin-left:30px;">@lang("feedback.A_total_of_data")：<strong>{{$total}} </strong> @lang("feedback.article")</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


@stop

@section('js')

    <script>

        //时间选择器
        laydate.render({elem: '#stime'});
        laydate.render({elem: '#etime'});

        document.getElementById('kwd_k').value="@if($kwd_k != '%'){{$kwd_k}}@endif";
        document.getElementById('msg').value="@if($msg != '%'){{$msg}}@endif";
        document.getElementById('stime').value="@if($stime != '%'){{$stime}}@endif";
        document.getElementById('etime').value="@if($etime != '%'){{$etime}}@endif";


    </script>
    <script>
        $('#reset').click(function(){
            document.getElementById('kwd_k').value="";
            document.getElementById('msg').value="";
            document.getElementById('stime').value="";
            document.getElementById('etime').value="";
            document.getElementById('SUBMIT').submit();
        })
    </script>

@stop


