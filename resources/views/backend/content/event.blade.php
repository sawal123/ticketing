@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Event</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Tiket</a></li>
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
                  @include('backend.molecul.cardEventSearch')
                </div>
            </div>
            @if (session('deleteEvent'))
            <div class="alert alert-success">
                {{ session('deleteEvent') }}
            </div>
        @endif
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    <div class="row">
                        @include('backend.molecul.cardEvents')
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
