
@foreach ($slide as $slides)
    <div class="col-md-6 col-xl-4 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-5">
                    <ul class="icons">
                        <li>
                            <a href="{{ url('admin/landing/' . $slides->uid) }}" class="btn btn-primary">
                                <i class="fe fe-edit"> </i> </a>
                        </li>
                        <li>
                            <a href="{{ url('admin/landing/' . $slides->uid) }}" class="btn btn-danger">
                                <i class="fe fe-trash"> </i> </a>
                        </li>
                        {{-- <li><a href="javascript:void(0)" class="btn btn-danger"><i
                            class="fe fe-x"></i></a></li> --}}
                    </ul>
                    <a href="shop-description.html">
                        <img class="img-fluid br-7 w-100" src="{{ asset('storage/slide/' . $slides->gambar) }}"
                            alt="img">
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="product-content text-center">
                        <div class="btn btn-success">{{$slides->sort}}</div>
                        <h1 class="title fw-bold fs-20"><a
                                href="{{ url($slides->url) }}">{{ $slides->title }}</a>
                        </h1>


                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach
