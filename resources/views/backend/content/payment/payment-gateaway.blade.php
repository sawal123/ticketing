@extends('backend.app')

@section('content')
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <h1 class="page-title">Payment</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment</li>
                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col">Biaya</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Icon</th>
                                    <th scope="col">Is Active</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payment as $index=>$item)
                                    <tr>
                                        <td>{{$index+1}}</td>
                                        <td>{{$item->payment}}</td>
                                        <td>{{$item->biaya}}</td>
                                        <td>{{$item->biaya_type}}</td>
                                        <td>{{$item->icon}}</td>
                                        <td>{{$item->is_active}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
