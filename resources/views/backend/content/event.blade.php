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
        <div class="col-xl-3 col-lg-4">
            <div class="row">
                <div class="col-md-12 col-lg-12">
                 @include('backend.molecul.cardTopSales')
                </div>
            </div>
        </div>
        <!-- COL-END -->
        <div class="col-xl-9 col-lg-8">
            <div class="row">
                <div class="col-xl-12">
                  @include('backend.molecul.cardEventSearch')
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="tab-11">
                    <div class="row">
                           @include('backend.molecul.cardEvents')
                        
                        {{-- <div class="mb-5">
                            <div class="float-end">
                                <ul class="pagination ">
                                    <li class="page-item page-prev disabled">
                                        <a class="page-link" href="javascript:void(0)" tabindex="-1">Prev</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="javascript:void(0)">1</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">2</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">4</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">5</a></li>
                                    <li class="page-item page-next">
                                        <a class="page-link" href="javascript:void(0)">Next</a>
                                    </li>
                                </ul>
                            </div>
                        </div> --}}
                    </div>
                </div>
                {{-- <div class="tab-pane" id="tab-12">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <div class="row g-0">
                                        <div class="col-xl-3 col-lg-12 col-md-12">
                                            <div class="product-list">
                                                <div class="product-image">
                                                    <ul class="icons">
                                                        <li><a href="shop-description.html" class="btn btn-primary"><i
                                                                    class="fe fe-eye text-white "></i></a></li>
                                                        <li><a href="add-product.html" class="btn btn-success"><i
                                                                    class="fe fe-edit text-white "></i></a></li>
                                                        <li><a href="wishlist.html" class="btn btn-danger"><i
                                                                    class="fe fe-x text-white"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="br-be-0 br-te-0">
                                                    <a href="shop-description.html" class="">
                                                        <img src="../assets/images/products/8.jpg" alt="img"
                                                            class="cover-image br-7 w-100">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-12 col-md-12 border-end my-auto">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <a href="shop-description.html" class="">
                                                        <h3 class="fw-bold fs-30 mb-3">Candy Pure Rose Water</h3>
                                                        <div class="mb-2 text-warning">
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star-half-o fs-18 text-warning"></i>
                                                            <i class="fa fa-star-o fs-18 text-warning"></i>
                                                        </div>
                                                    </a>
                                                    <p class="fs-16">Ut enim ad minim veniam, quis nostrud exercitation
                                                        ullamco laboris nisi ut aliquip commodo consequat,quis nostrud
                                                        exercitation ullamco laboris nisi ut aliquip commodo consequat </p>
                                                    <form class="shop__filter">
                                                        <div class="row gutters-xs">
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="azure"
                                                                        class="colorinput-input" checked>
                                                                    <span class="colorinput-color bg-azure"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="indigo"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-indigo"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="purple"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-purple"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-12 col-md-12 my-auto">
                                            <div class="card-body p-0">
                                                <div class="price h3 text-center mb-5 fw-bold">$599</div>
                                                <a href="cart.html" class="btn btn-primary btn-block"><i
                                                        class="fe fe-shopping-cart mx-2"></i>Add to cart</a>
                                                <a href="wishlist.html" class="btn btn-outline-primary btn-block mt-2"><i
                                                        class="fe fe-heart mx-2 wishlist-icon"></i>Add to wishlist</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <div class="row g-0">
                                        <div class="col-xl-3 col-lg-12 col-md-12">
                                            <div class="product-list">
                                                <div class="product-image">
                                                    <ul class="icons">
                                                        <li><a href="shop-description.html" class="btn btn-primary"><i
                                                                    class="fe fe-eye text-white "></i></a></li>
                                                        <li><a href="add-product.html" class="btn btn-success"><i
                                                                    class="fe fe-edit text-white "></i></a></li>
                                                        <li><a href="wishlist.html" class="btn btn-danger"><i
                                                                    class="fe fe-x text-white"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="br-be-0 br-te-0">
                                                    <a href="shop-description.html" class="">
                                                        <img src="../assets/images/products/9.jpg" alt="img"
                                                            class="cover-image br-7 w-100">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-12 col-md-12 border-end my-auto">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <a href="shop-description.html" class="">
                                                        <h3 class="fw-bold fs-30 mb-3">White Tshirt for Men</h3>
                                                        <div class="mb-2 text-warning">
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star-half-o fs-18 text-warning"></i>
                                                            <i class="fa fa-star-o fs-18 text-warning"></i>
                                                        </div>
                                                    </a>
                                                    <p class="fs-16">Ut enim ad minim veniam, quis nostrud exercitation
                                                        ullamco laboris nisi ut aliquip commodo consequat,quis nostrud
                                                        exercitation ullamco laboris nisi ut aliquip commodo consequat </p>
                                                    <form class="shop__filter">
                                                        <div class="row gutters-xs">
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="azure"
                                                                        class="colorinput-input" checked="">
                                                                    <span class="colorinput-color bg-azure"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="indigo"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-indigo"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="purple"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-purple"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-12 col-md-12 my-auto">
                                            <div class="card-body p-0">
                                                <div class="price h3 text-center mb-5 fw-bold">$1,399</div>
                                                <a href="cart.html" class="btn btn-primary btn-block"><i
                                                        class="fe fe-shopping-cart mx-2"></i>Add to cart</a>
                                                <a href="wishlist.html" class="btn btn-outline-primary btn-block mt-2"><i
                                                        class="fe fe-heart mx-2 wishlist-icon"></i>Add to wishlist</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <div class="row g-0">
                                        <div class="col-xl-3 col-lg-12 col-md-12">
                                            <div class="product-list">
                                                <div class="product-image">
                                                    <ul class="icons">
                                                        <li><a href="shop-description.html" class="btn btn-primary"><i
                                                                    class="fe fe-eye text-white "></i></a></li>
                                                        <li><a href="add-product.html" class="btn btn-success"><i
                                                                    class="fe fe-edit text-white "></i></a></li>
                                                        <li><a href="wishlist.html" class="btn btn-danger"><i
                                                                    class="fe fe-x text-white"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="br-be-0 br-te-0">
                                                    <a href="shop-description.html" class="">
                                                        <img src="../assets/images/products/7.jpg" alt="img"
                                                            class="cover-image br-7 w-100">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-12 col-md-12 border-end my-auto">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <a href="shop-description.html" class="">
                                                        <h3 class="fw-bold fs-30 mb-3">Stylish Rockerz 255 Ear Pods</h3>
                                                        <div class="mb-2 text-warning">
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star-half-o fs-18 text-warning"></i>
                                                            <i class="fa fa-star-o fs-18 text-warning"></i>
                                                        </div>
                                                    </a>
                                                    <p class="fs-16">Ut enim ad minim veniam, quis nostrud exercitation
                                                        ullamco laboris nisi ut aliquip commodo consequat,quis nostrud
                                                        exercitation ullamco laboris nisi ut aliquip commodo consequat </p>
                                                    <form class="shop__filter">
                                                        <div class="row gutters-xs">
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="azure"
                                                                        class="colorinput-input" checked="">
                                                                    <span class="colorinput-color bg-azure"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="indigo"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-indigo"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="purple"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-purple"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-12 col-md-12 my-auto">
                                            <div class="card-body p-0">
                                                <div class="price h3 text-center mb-5 fw-bold">$16,599</div>
                                                <a href="cart.html"><span class="btn btn-primary btn-block"><i
                                                            class="fe fe-shopping-cart mx-2"></i>Add to cart</span></a>
                                                <a href="wishlist.html"><span
                                                        class="btn btn-outline-primary btn-block mt-2"><i
                                                            class="fe fe-heart mx-2 wishlist-icon"></i>Add to
                                                        wishlist</span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <div class="row g-0">
                                        <div class="col-xl-3 col-lg-12 col-md-12">
                                            <div class="product-list">
                                                <div class="product-image">
                                                    <ul class="icons">
                                                        <li><a href="shop-description.html" class="btn btn-primary"><i
                                                                    class="fe fe-eye text-white "></i></a></li>
                                                        <li><a href="add-product.html" class="btn btn-success"><i
                                                                    class="fe fe-edit text-white "></i></a></li>
                                                        <li><a href="wishlist.html" class="btn btn-danger"><i
                                                                    class="fe fe-x text-white"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="br-be-0 br-te-0">
                                                    <a href="shop-description.html" class="">
                                                        <img src="../assets/images/products/3.jpg" alt="img"
                                                            class="cover-image br-7 w-100">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-12 col-md-12 border-end my-auto">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <a href="shop-description.html" class="">
                                                        <h3 class="fw-bold fs-30 mb-3">Flower Pot for Home Decor</h3>
                                                        <div class="mb-2 text-warning">
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star-half-o fs-18 text-warning"></i>
                                                            <i class="fa fa-star-o fs-18 text-warning"></i>
                                                        </div>
                                                    </a>
                                                    <p class="fs-16">Ut enim ad minim veniam, quis nostrud exercitation
                                                        ullamco laboris nisi ut aliquip commodo consequat,quis nostrud
                                                        exercitation ullamco laboris nisi ut aliquip commodo consequat </p>
                                                    <form class="shop__filter">
                                                        <div class="row gutters-xs">
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="azure"
                                                                        class="colorinput-input" checked="">
                                                                    <span class="colorinput-color bg-azure"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="indigo"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-indigo"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="purple"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-purple"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-12 col-md-12 my-auto">
                                            <div class="card-body p-0">
                                                <div class="price h3 text-center mb-5 fw-bold">$1,299</div>
                                                <a href="cart.html"><span class="btn btn-primary btn-block"><i
                                                            class="fe fe-shopping-cart mx-2"></i>Add to cart</span></a>
                                                <a href="wishlist.html"><span
                                                        class="btn btn-outline-primary btn-block mt-2"><i
                                                            class="fe fe-heart mx-2 wishlist-icon"></i>Add to
                                                        wishlist</span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div class="card overflow-hidden">
                                <div class="card-body">
                                    <div class="row g-0">
                                        <div class="col-xl-3 col-lg-12 col-md-12">
                                            <div class="product-list">
                                                <div class="product-image">
                                                    <ul class="icons">
                                                        <li><a href="shop-description.html" class="btn btn-primary"><i
                                                                    class="fe fe-eye text-white "></i></a></li>
                                                        <li><a href="add-product.html" class="btn btn-success"><i
                                                                    class="fe fe-edit text-white "></i></a></li>
                                                        <li><a href="wishlist.html" class="btn btn-danger"><i
                                                                    class="fe fe-x text-white"></i></a></li>
                                                    </ul>
                                                </div>
                                                <div class="br-be-0 br-te-0">
                                                    <a href="shop-description.html" class="">
                                                        <img src="../assets/images/products/2.jpg" alt="img"
                                                            class="cover-image br-7 w-100">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-lg-12 col-md-12 border-end my-auto">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <a href="shop-description.html" class="">
                                                        <h3 class="fw-bold fs-30 mb-3">Running Shoes for men</h3>
                                                        <div class="mb-2 text-warning">
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star fs-18 text-warning"></i>
                                                            <i class="fa fa-star-half-o fs-18 text-warning"></i>
                                                            <i class="fa fa-star-o fs-18 text-warning"></i>
                                                        </div>
                                                    </a>
                                                    <p class="fs-16">Ut enim ad minim veniam, quis nostrud exercitation
                                                        ullamco laboris nisi ut aliquip commodo consequat,quis nostrud
                                                        exercitation ullamco laboris nisi ut aliquip commodo consequat </p>
                                                    <form class="shop__filter">
                                                        <div class="row gutters-xs">
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="azure"
                                                                        class="colorinput-input" checked="">
                                                                    <span class="colorinput-color bg-azure"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="indigo"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-indigo"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-auto">
                                                                <label class="colorinput">
                                                                    <input type="checkbox" name="color" value="purple"
                                                                        class="colorinput-input">
                                                                    <span class="colorinput-color bg-purple"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-12 col-md-12 my-auto">
                                            <div class="card-body p-0">
                                                <div class="price h3 text-center mb-5 fw-bold">$6,599</div>
                                                <a href="cart.html"><span class="btn btn-primary btn-block"><i
                                                            class="fe fe-shopping-cart mx-2"></i>Add to cart</span></a>
                                                <a href="wishlist.html"><span
                                                        class="btn btn-outline-primary btn-block mt-2"><i
                                                            class="fe fe-heart mx-2 wishlist-icon"></i>Add to
                                                        wishlist</span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <div class="float-end">
                                <ul class="pagination ">
                                    <li class="page-item page-prev disabled">
                                        <a class="page-link" href="javascript:void(0)" tabindex="-1">Prev</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="javascript:void(0)">1</a>
                                    </li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">2</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">4</a></li>
                                    <li class="page-item"><a class="page-link" href="javascript:void(0)">5</a></li>
                                    <li class="page-item page-next">
                                        <a class="page-link" href="javascript:void(0)">Next</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
            <!-- COL-END -->
        </div>
        <!-- ROW-1 CLOSED -->
    </div>
@endsection
