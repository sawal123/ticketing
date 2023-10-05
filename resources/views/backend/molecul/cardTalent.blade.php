<div class="row">
    @if (count($talent) > 0)
        @foreach ($talent as $talents)
            <div class="col col-xl-3 col-lg-4 col-md-6">
                <div class="card w-100">
                    <div class="product-grid6">
                        <div class="product-image6 p-2">
                            <img class="img-fluid br-7 " style="height: 150px; object-fit: cover"
                                src="{{ asset('storage/talent/' . $talents->gambar) }}" alt="img">
                        </div>
                        <div class="card-body pt-0">
                            <div class="product-content text-center">
                                {{-- <h1 class="title fw-bold "><a href="{{url('/event/eventDetail/')}}"></a></h1> --}}
                                <div class="price">{{ $talents->talent }}</span>
                                </div>
                            </div>
                            <div class="btn-list mt-4 d-flex justify-content-center">
                                <button type="submit" class="btn ripple btn-primary me-2" data-bs-target="#updateTalent"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal" data-uid="{{$talents->uid}}" data-talent="{{$talents->talent}}"><i class="fe fe-edit"> </i>
                                </button>
                                <a href="{{url('dashboard/delete/'.$talents->uid)}}" class="delete btn ripple btn-danger"><i class="fe fe-trash"> </i></a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    @else
        <p>Tidak Ada Talent ...</p>
    @endif

</div>
