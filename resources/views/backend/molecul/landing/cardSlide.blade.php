@foreach ($slide as $slides)
    <div class="col-md-6 col-xl-4 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-5">
                    <ul class="icons">
                        <li>
                            <button type="submit" class="btn btn-primary" data-bs-target="#updateSlide"
                                data-bs-effect="effect-sign" data-bs-toggle="modal" data-uid="{{ $slides->uid }}"
                                data-title="{{ $slides->title }}" data-sort="{{ $slides->sort }}"
                                data-url="{{ $slides->url }}">
                                <i class="fe fe-edit"> </i> </button>
                        </li>
                        <li>
                            <a href="{{ url('admin/landing/' . $slides->uid) }}" class="btn btn-danger">
                                <i class="fe fe-trash"> </i> </a>
                        </li>
                        {{-- <li><a href="javascript:void(0)" class="btn btn-danger"><i
                            class="fe fe-x"></i></a></li> --}}
                    </ul>
                    <a href="shop-description.html">
                        <img class="img-fluid br-7 w-100" style="object-fit: cover; width: 1920px"
                            src="{{ asset('storage/slide/' . $slides->gambar) }}" alt="img">
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="product-content text-center">

                        <h1 class="title fw-bold fs-20"><a href="{{ url($slides->url) }}">{{ $slides->title }}</a>
                        </h1>
                        <div class="btn btn-success">{{ $slides->sort }}</div>


                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach
