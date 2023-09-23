@extends('frontend.index')

@section('content')
    <div class="" style="height: 150px; "></div>
    <div class="row">
        <div class="col">
            <div class="container">
                <h3>Term & Condition</h3>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        @foreach ($term as $terms)
                            <button class="nav-link {{$loop->first? 'active': ''}}" id="nav-{{ $terms->uid }}-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-{{ $terms->uid }}" type="button" role="tab"
                                aria-controls="nav-{{ $terms->uid }}" aria-selected="true">{{ $terms->title }}</button>
                        @endforeach


                    </div>
                </nav>
                <div class="tab-content pt-5" id="nav-tabContent">
                    @foreach ($term as $item)
                        <div class="tab-pane fade{{ $loop->first ? ' show active' : '' }}" id="nav-{{ $item->uid }}"
                            role="tabpanel" aria-labelledby="nav-{{ $item->uid }}-tab">
                            {!! $item->term !!}
                        </div>
                    @endforeach


                </div>
            </div>
        </div>
    </div>
@endsection
