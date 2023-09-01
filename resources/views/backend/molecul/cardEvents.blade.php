@foreach ($event as $events)
    <div class="col-md-6 col-xl-4 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-5">
                    <ul class="icons">
                        <li>
                            <a href="{{ url('/event/eventDetail/?id=' . $events->uid) }}" class="btn btn-primary"> <i
                                    class="fe fe-eye"> </i> </a>
                        </li>
                        {{-- <li><a href="javascript:void(0)" class="btn btn-danger"><i
                            class="fe fe-x"></i></a></li> --}}
                    </ul>
                    <a href="shop-description.html">
                        <img class="img-fluid br-7 w-100" src="{{ asset('storage/cover/' . $events->cover) }}"
                            alt="img">
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="product-content text-center">
                        <h1 class="title fw-bold fs-20"><a
                                href="{{ url('/admin/event/eventDetail/?id=' . $events->uid) }}">{{ $events->event }}</a></h1>
                        
                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach
