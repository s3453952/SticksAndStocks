{{--/**--}}
{{--* Created by: Paul Davidson.--}}
{{--* Authors: Paul Davidson and Abnezer Yhannes--}}
{{--*/--}}
@include('layouts.header')
<body class="bg">
<div class=" bg ">
    <div class="title text-center errorpage col-md-4 col-md-offset-4">
        <div class="" >
            <div id="logo">
                @if(Auth::check())
                    <a href="{{url('/dashboard')}}">
                @else
                     <a href="{{url('/')}}">
                @endif
                    <img src="img/PineappleWC (1).gif" alt="logo" style="height: 200px; width:200px;"></a>
                <br/>404 Error <br/> Page Not Found
                <hr>
                <div> <h1>'Sorry, we are out of juice'<br/>:(</h1> </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.footer')