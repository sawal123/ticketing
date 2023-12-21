@extends('frontend.index')

@section('content')
    <div class="row mt-5">
        <div class="col">

            <div class="container pt-lg-5">
                <div class="row mt-5 ">
                    <h3 class="text-center">Hubungi Kami</h3>
                    <div class=" col-md-12 col-sm-12 col-lg-6">
                        <iframe class="w-100 h-100"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.0937596428107!2d98.71027271531686!3d3.565890959091114!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x303130f12d391d9f%3A0xfd33802c14dff7eb!2sGg.%20Mangga%20No.10%2C%20Binjai%2C%20Kec.%20Medan%20Denai%2C%20Kota%20Medan%2C%20Sumatera%20Utara%2020226!5e0!3m2!1sen!2sid!4v1702439015336!5m2!1sen!2sid"
                             style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <div class=" col-md-12 col-sm-12 col-lg-6">
                        <div class="card mt-sm-3 mt-lg-0 mt-md-3 ">
                            <div class="card-header">
                                <h5>Contact</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @foreach ($contactus as $con)
                                    <li class="list-group-item">
                                        <i class="fe {{ $con->link }}  me-2"></i>
                                        {{ $con->name }}
                                    </li>
                                    @endforeach
                                  
                                    {{-- <li class="list-group-item">
                                        <i class="fe fe-phone me-2"></i>
                                        +62 852-9723-7151
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fe fe-mail me-2"></i>
                                        admin@go-tik.com
                                    </li> --}}

                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
