@extends('layouts.admin')

@section('title', __('messages.sidebar.file_manager'))
@section('page-title', __('messages.sidebar.file_manager'))

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div id="fm" style="height: min(760px, calc(100vh - 210px));"></div>
    </div>
</div>
@endsection
