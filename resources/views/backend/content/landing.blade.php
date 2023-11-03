@extends('backend.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Landing</h1>
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
        @if (session('editLogo'))
            <div class="alert alert-primary">
                {{ session('editLogo') }}
            </div>
        @endif
        @if (session('editSlide'))
            <div class="alert alert-primary">
                {{ session('editSlide') }}
            </div>
        @endif
        <div class="col-xl-3 col-lg-4">


        </div>
        <!-- COL-END -->
        <div class="col-xl-12 col-lg-12">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card p-0">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-xl-3 col-lg-12">
                                    @include('backend.molecul.landing.modalSlide')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    @if (session('addSlide'))
                        <div class="alert alert-success">
                            {{ session('addSlide') }}
                        </div>
                    @endif

                    @if (session('deleteSlide'))
                        <div class="alert alert-success">
                            {{ session('deleteSlide') }}
                        </div>
                    @endif
                    <div class="row">
                        @include('backend.molecul.landing.cardSlide')
                    </div>
                </div>
            </div>
        </div>
        <!-- ROW-1 CLOSED -->
        
    </div>
@endsection
