@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard Event</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Event</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <!-- ROW-1 OPEN -->
    <div class="row row-cards">
       
        <div class="col-xl-12 col-lg-12">
            <div class="row">
                <div class="col-xl-12">
                  @include('penyewa.molecul.cardEventSearch')
                </div>
            </div>
            @if (session('hapus'))
            <div class="alert alert-success">
                {{ session('hapus') }}
            </div>
        @endif
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    <div class="row">
                        @include('penyewa.molecul.cardEvents')
                    </div>
                </div>
                <div class="tab-pane" id="tab-12">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                  <h2>On Progress..</h2>
                                </div>
                            </div>
                        </div>
                    
                    </div>
                </div>
            </div>
            <!-- COL-END -->
        </div>
        <!-- ROW-1 CLOSED -->
    </div>
@endsection
