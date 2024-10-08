@extends('layouts.app')
@section('title')
    {{__('messages.page.edit_page')}}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <h1>@yield('title')</h1>
            <a class="btn btn-outline-primary float-end"
               href="{{ route('pages.index') }}">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
        <div class="container-fluid">
            @include('layouts.errors')
                    <div class="card">
                        <div class="card-body">
                            {{ Form::open(['route' => ['pages.update', $page->id], 'method' => 'put','id' => 'editPage']) }}
                            {{Form::hidden('isEdit', true, ['class' => 'isEdit'])}}
                                @include('page.edit_field')
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
@endsection
{{--@section('scripts')--}}
{{--    <script src="{{asset('/web/plugins/custom/tinymce/tinymce.bundle.js')}}"></script>--}}
{{--    <script src="{{mix('assets/js/page/creat-edit.js')}}"></script>--}}
{{--@endsection--}}
