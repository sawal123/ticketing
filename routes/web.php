<?php

use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\UserLoginController;
use App\Http\Controllers\Auth\UserRegisterController;
use App\Http\Controllers\AdminEventMouPdfController;
use App\Http\Controllers\AdminEventReviewFileController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BuyTicketController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CheckoutPaymentOtpController;
use App\Http\Controllers\Dashboard\addController;
use App\Http\Controllers\Dashboard\CashController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DeleteController;
use App\Http\Controllers\Dashboard\editController;
use App\Http\Controllers\Dashboard\EventTransactionPdfController;
use App\Http\Controllers\Dashboard\PaymentGatewayController;
use App\Http\Controllers\Dashboard\TController;
use App\Http\Controllers\Dashboard\TransaksiController;
use App\Http\Controllers\DashboardAgreementFileController;
use App\Http\Controllers\landingController;
use App\Http\Controllers\PenarikanTransferProofController;
use App\Http\Controllers\Penyewa\AddController as PenyewaAddController;
use App\Http\Controllers\Penyewa\Auth\LoginController;
use App\Http\Controllers\Penyewa\BeliCash\CashController as BeliCashCashController;
use App\Http\Controllers\Penyewa\DeleteController as PenyewaDelete;
use App\Http\Controllers\Penyewa\EditController as PenyewaEditController;
use App\Http\Controllers\Penyewa\PenyewaController;
use App\Http\Controllers\Penyewa\StaffController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\Admin\ActivityIndex;
use App\Livewire\Admin\CategoryIndex;
use App\Livewire\Admin\DashboardDemo;
use App\Livewire\Admin\EmailBlast;
use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\EventIndex;
use App\Livewire\Admin\FasilitasIndex;
use App\Livewire\Admin\MonitoringIndex;
use App\Livewire\Admin\PaymentGatewayIndex;
use App\Livewire\Admin\PenyewaDetail;
use App\Livewire\Admin\PenarikanIndex;
use App\Livewire\Admin\SettingIndex;
use App\Livewire\Admin\SliderIndex;
use App\Livewire\Admin\TermIndex;
use App\Livewire\Admin\TransaksiIndex;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\StaffVerify;
use App\Livewire\Dashboard\DemoIndex;
use App\Livewire\Dashboard\EventCreate;
use App\Livewire\Dashboard\EventDetail as DashboardEventDetail;
use App\Livewire\Dashboard\EventIndex as DashboardEventIndex;
use App\Livewire\Dashboard\PartnerIndex;
use App\Livewire\Dashboard\PenarikanIndex as DashboardPenarikanIndex;
use App\Livewire\Dashboard\SettingsIndex;
use App\Livewire\Dashboard\StaffIndex;
use App\Livewire\Dashboard\VoucherIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/api/slide', [SlideController::class, 'slide']);
// Route::post('/api/callback', [TransactionController::class, 'callback']);

// ============================================================================
// ============================================================================
// ============================================================================

Route::get('/', [landingController::class, 'home'])->name('home');
Route::get('/ticket/{event}', [landingController::class, 'ticket']);

Route::get('/register', Register::class)->name('register');
// Route::post('/registerUser', [UserRegisterController::class, 'create'])->name('register-user');

Route::get('/forgot-password', ForgotPassword::class)->name('forgot');
Route::post('/email', [UserLoginController::class, 'email'])->name('email');
Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
Route::post('/new-password', [UserLoginController::class, 'newPassword']);

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/login', Login::class)->name('login');
// Route::post('/loginUser', [UserLoginController::class, 'loginUser']);

// Route::get('/postEvent/{search?}', [landingController::class, 'cari']);
Route::get('/search/{cari?}/', [landingController::class, 'search']);
Route::get('/cari', [landingController::class, 'cari']);
Route::get('/term', [landingController::class, 'term']);

Route::get('/contact', [landingController::class, 'contact']);

Route::get('/invoice/{uid}', [Controller::class, 'invoice'])->middleware('auth');
Route::get('/dashboard/event/{uid}/export-pdf', EventTransactionPdfController::class)
    ->middleware('auth')
    ->name('event.transactions.pdf');
Route::get('/penarikan/{uid}/transfer-proof', PenarikanTransferProofController::class)
    ->middleware('auth')
    ->name('penarikan.transfer-proof.show');
Route::post('/api/callback', [TransactionController::class, 'callback'])
    ->withoutMiddleware([
        VerifyCsrfToken::class,
        GlobalDataMiddleware::class,
        LogActivityMiddleware::class,
    ]);

// Route::post('/generate-barcode', [BarcodeController::class, 'generateBarcode']);
Route::get('/generate-barcode/{data}/login', [BarcodeController::class, 'showLogin'])->name('barcode.login');
Route::post('/generate-barcode/{data}/login', [BarcodeController::class, 'login'])->name('barcode.login.submit');
Route::get('/cash-ticket/{uid}', [BarcodeController::class, 'showCashTicket'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('cash.ticket.show');
Route::get('/ticket-access/{uid}', [BarcodeController::class, 'showOnlineTicket'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('online.ticket.show');
Route::get('/generate-barcode/{data}', [BarcodeController::class, 'generateBarcode'])->name('barcode.generate');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [editController::class, 'profile']);
    Route::post('/profile/update-profile', [editController::class, 'editProfile']);
    Route::get('email/notif-email', [Controller::class, 'notif']);
    Route::get('/detail-ticket/{uid}/{user}', [BuyTicketController::class, 'index']);
    Route::post('/checkVoucer', [BuyTicketController::class, 'checkVoucher']);
    Route::post('/closeVoucher', [BuyTicketController::class, 'closeVoucher']);
    Route::post('/checkout', [BuyTicketController::class, 'checkout'])->middleware('throttle:checkout');
    Route::get('/transaksi', [landingController::class, 'listTransaksi']);

    Route::post('/checkout-payment-otp/send', [CheckoutPaymentOtpController::class, 'send'])->name('checkout-payment-otp.send');
    Route::post('/checkout-payment-otp/resend', [CheckoutPaymentOtpController::class, 'resend'])->name('checkout-payment-otp.resend');
    Route::post('/checkout-payment-otp/verify', [CheckoutPaymentOtpController::class, 'verify'])->name('checkout-payment-otp.verify');
    Route::post('/paynow', [TransactionController::class, 'paynow'])->middleware('throttle:paynow');
    Route::get('/detail-ticket/delete/{uid}/{user_uid}', [DeleteController::class, 'deteleListTransaksi']);
    Route::post('/profile/update-password', [editController::class, 'updateProfilePassword'])->name('profile.password.update');
    Route::post('/profile/email/request-otp', [editController::class, 'requestEmailChangeOtp'])->name('profile.email.request-otp');
    Route::post('/profile/email/verify-otp', [editController::class, 'verifyEmailChangeOtp'])->name('profile.email.verify-otp');
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
    Route::post('/out', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/signin');
    })->name('out');
});

Route::get('/signin', [PenyewaController::class, 'login'])->name('signIn');
Route::post('/signin/cekLogin', [LoginController::class, 'index'])->name('cekLogin');

Route::prefix('dashboard')
    ->group(function () {
        // =========================================================
        // NEW LIVEWIRE DASHBOARD (PRIMARY)
        // =========================================================
        Route::middleware(['roles:penyewa'])->group(function () {
            Route::get('/', DemoIndex::class)->name('dashboard');
            Route::get('/voucher', VoucherIndex::class)->name('dashboard.voucher');
            Route::get('/penarikan', DashboardPenarikanIndex::class)->name('dashboard.penarikan');
            Route::get('/staff-index', StaffIndex::class)->name('dashboard.staff');
            Route::get('/partner', PartnerIndex::class)->name('dashboard.partner');
            Route::get('/settings', SettingsIndex::class)->name('dashboard.settings');
            Route::get('/event/{uid}/mou/unsigned/{agreementUid?}', [DashboardAgreementFileController::class, 'unsigned'])
                ->name('dashboard.event.mou.unsigned');
            Route::get('/event/{uid}/mou/signed/{agreementUid?}', [DashboardAgreementFileController::class, 'signed'])
                ->name('dashboard.event.mou.signed');
        });

        Route::middleware(['roles:penyewa,staff'])->group(function () {
            Route::get('/event', DashboardEventIndex::class)->name('dashboard.event');
            Route::get('/event/create', EventCreate::class)->name('dashboard.event.create');
            Route::get('/event/edit/{uid}', EventCreate::class)->name('dashboard.event.edit');
            Route::get('/event/{uid}', DashboardEventDetail::class)->name('dashboard.event.detail');
        });

        // =========================================================
        // LEGACY DASHBOARD (MOVED TO /old)
        // =========================================================
        Route::prefix('old')->group(function () {
            Route::middleware(['roles:penyewa'])->group(function () {
                Route::get('/', [PenyewaController::class, 'index'])->name('dashboard.old');
                Route::get('/transaksi/{uid?}', [PenyewaController::class, 'transaksi'])->name('dashboard.old.transaksi');
                Route::get('/cash/{uid?}', [PenyewaController::class, 'cash'])->name('dashboard.old.cash');
                Route::get('/event/{addEvent?}/{uid?}', [PenyewaController::class, 'event']);
                Route::get('/ubahEvents/{uid}', [PenyewaController::class, 'ubahEvents']);
                Route::get('/voucher', [PenyewaController::class, 'voucher']);
                Route::delete('/staff/delete/{uid}', [StaffController::class, 'destroy'])->name('dashboard.old.staff.destroy');
                Route::resource('staff', StaffController::class);
                Route::post('/event/toggle-status/{uid}', [PenyewaController::class, 'toggleStatusEvent']);
                Route::post('/hargas/toggle-status/{id}', [PenyewaController::class, 'toggleStatusHarga']);
                Route::post('/updatePassword', [PenyewaEditController::class, 'updatePassword']);
            });

            Route::middleware(['roles:penyewa'])->group(function () {
                Route::get('/money', [PenyewaController::class, 'money']);
                Route::get('/profile', [PenyewaController::class, 'profile']);
                Route::post('/addPenarikan', [PenyewaAddController::class, 'addPenarikan']);
                Route::post('/editRekening', [PenyewaEditController::class, 'editRekening']);
                Route::post('/editProfile', [PenyewaEditController::class, 'editProfile']);
                Route::delete('/events/{id}', [PenyewaDelete::class, 'eventDelete'])->name('dashboard.old.events.destroy');
                Route::delete('/talents/{id}', [PenyewaDelete::class, 'deleteTalent'])->name('dashboard.old.talents.destroy');
                Route::delete('/hargas/{id}', [PenyewaDelete::class, 'deleteHarga'])->name('dashboard.old.hargas.destroy');
                Route::delete('/vouchers/{id}', [PenyewaDelete::class, 'deleteVoucher'])->name('dashboard.old.vouchers.destroy');
                Route::delete('/partners/{id}', [PenyewaDelete::class, 'deletePartner'])->name('dashboard.old.partners.destroy');
                Route::post('/addEvents', [PenyewaAddController::class, 'addEvent'])->name('dashboard.old.addEvent');
                Route::post('/addTalent', [PenyewaAddController::class, 'addTalent']);
                Route::post('/addHarga', [PenyewaAddController::class, 'addHarga']);
                Route::post('/addVoucher', [PenyewaAddController::class, 'addVoucher']);
                Route::post('/addCash', [BeliCashCashController::class, 'createCash'])->name('old.add.cash');
                Route::post('/addPartner', [PenyewaAddController::class, 'addPartner']);
                Route::post('/editTalent', [PenyewaEditController::class, 'editTalent']);
                Route::post('/editEventPenyewa', [PenyewaEditController::class, 'editEventPenyewa']);
                Route::post('/editEvent', [PenyewaEditController::class, 'editEvent']);
                Route::post('/editHarga', [PenyewaEditController::class, 'editHarga']);
                Route::post('/editPartner', [PenyewaEditController::class, 'editPartner']);
                Route::post('/updateVoucher', [PenyewaEditController::class, 'editVoucher']);
            });
        });
    });

Route::get('/staff/verify/{uid}', StaffVerify::class)->name('staff.verify');
// Route::post('/staff/complete-profile/{uid}', [StaffController::class, 'completeProfile']);

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        // =========================================================
        // NEW LIVEWIRE ADMIN (PRIMARY)
        // =========================================================
        Route::get('/', DashboardDemo::class)->name('admin');
        Route::get('/event', EventIndex::class)->name('admin.event');
        Route::get('/event/{uid}', EventDetail::class)->name('admin.event.detail');
        Route::get('/event/{uid}/review/bank-book', [AdminEventReviewFileController::class, 'bankBook'])
            ->name('admin.event.review.bank-book');
        Route::get('/event/{uid}/review/organizer-letter', [AdminEventReviewFileController::class, 'organizerLetter'])
            ->name('admin.event.review.organizer-letter');
        Route::get('/event/{uid}/review/responsible-identity', [AdminEventReviewFileController::class, 'responsibleIdentity'])
            ->name('admin.event.review.responsible-identity');
        Route::get('/event/{uid}/review/mou/unsigned/{agreementUid?}', [AdminEventMouPdfController::class, 'unsigned'])
            ->name('admin.event.review.mou.unsigned');
        Route::get('/event/{uid}/review/mou/signed/{agreementUid?}', [AdminEventMouPdfController::class, 'signed'])
            ->name('admin.event.review.mou.signed');
        Route::get('/transaksi', TransaksiIndex::class)->name('admin.transaksi');
        Route::get('/penarikan', PenarikanIndex::class)->name('admin.penarikan');
        Route::get('/payment-gateway', PaymentGatewayIndex::class)->name('admin.payments');
        Route::get('/setting', SettingIndex::class)->name('admin.setting');
        Route::get('/term', TermIndex::class)->name('admin.term');
        Route::get('/slider', SliderIndex::class)->name('admin.slider');
        Route::get('/activity', ActivityIndex::class)->name('admin.activity');
        Route::get('/monitoring', MonitoringIndex::class)->name('admin.monitoring');
        Route::get('/user', UserIndex::class)->name('admin.user');
        Route::get('/user/penyewa/{uid}', PenyewaDetail::class)->name('admin.user.penyewa.detail');
        Route::get('/category', CategoryIndex::class)->name('admin.category');
        Route::get('/fasilitas', FasilitasIndex::class)->name('admin.fasilitas');
        Route::get('/email-blast', EmailBlast::class)->name('admin.email-blast');

        // =========================================================
        // LEGACY ADMIN (MOVED TO /old)
        // =========================================================
        Route::prefix('old')
            ->namespace('Dashboard')
            ->group(function () {
                Route::get('/', [DashboardController::class, 'dashboard']);
                Route::get('/search', [DashboardController::class, 'event']);
                Route::get('/transaksi/{uid?}', [TransaksiController::class, 'transaksi']);
                Route::get('/cash/{uid?}', [CashController::class, 'cash']);
                Route::get('/editCash', [CashController::class, 'editCash'])->name('AEditCash');
                Route::get('t/online/{uid?}', [TController::class, 'tonline'])->name('tonline');
                Route::get('/user/{data?}', [DashboardController::class, 'user']);
                Route::get('/event/{addEvent?}/{uid?}', [DashboardController::class, 'event']);
                Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);
                Route::get('/penarikan', [DashboardController::class, 'penarikan']);
                Route::get('/setting/slide', [DashboardController::class, 'landing']);
                Route::get('/setting/seo', [DashboardController::class, 'seo']);
                Route::get('/setting/term', [DashboardController::class, 'term']);
                Route::get('/profile', [DashboardController::class, 'profile']);
                Route::post('/addEvents', [addController::class, 'addEvent']);
                Route::post('/addTalent', [addController::class, 'addTalent']);
                Route::post('/addHarga', [addController::class, 'addHarga']);
                Route::post('/addSlide', [addController::class, 'addSlide']);
                Route::post('/addTerm', [addController::class, 'addTerm']);
                Route::post('/addAdmin', [addController::class, 'addAdmin']);
                Route::post('/addContact', [addController::class, 'addContact']);
                Route::post('/editTalent', [editController::class, 'editTalent']);
                Route::post('/editEvent', [editController::class, 'editEvent']);
                Route::post('/editHarga', [editController::class, 'editHarga']);
                Route::post('/editSlide', [editController::class, 'editSlide']);
                Route::post('/editTerm', [editController::class, 'editTerm']);
                Route::post('/user/editUser', [editController::class, 'editUser']);
                Route::post('/user/editCashes', [editController::class, 'editCashes']);
                Route::get('/setujuiEvent/{data}', [editController::class, 'setujuiEvent']);
                Route::post('/editPenarikan', [editController::class, 'editStatusInvoice']);
                Route::post('/editContact', [editController::class, 'editContact']);
                Route::post('/editLogo', [editController::class, 'editLogo']);
                Route::post('/editIcon', [editController::class, 'editIcon']);
                Route::post('/edit/seoDeskripsi', [editController::class, 'editDeskripis']);
                Route::post('/edit/seoKeyword', [editController::class, 'editKeyword']);
                Route::post('/editTransaksi', [editController::class, 'editTransaksi']);
                Route::post('/editPro', [editController::class, 'editPro']);
                Route::post('/editRekening', [editController::class, 'editRekening']);
                Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
                Route::get('/deleteTransksi/{uid}', [DeleteController::class, 'deleteTransaksi']);
                Route::get('/landing/delete/{uid}', [DeleteController::class, 'deleteSlide']);
                Route::get('/events/delete/{uid}', [DeleteController::class, 'deleteEvent']);
                Route::get('/hargas/delete/{id}', [DeleteController::class, 'deleteHarga']);
                Route::get('/term/delete/{id}', [DeleteController::class, 'deleteTerm']);
                Route::get('/user/delete/{id}', [DeleteController::class, 'deleteUser']);
                Route::get('/cashes/delete/{id}', [DeleteController::class, 'deleteCashes']);
                Route::get('/deletePen/{data}', [DeleteController::class, 'deletePenarikan']);
                Route::get('/delete/contact/{data}', [DeleteController::class, 'deleteContact']);
                Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('old.payments');
                Route::post('/payment-gateway/store', [PaymentGatewayController::class, 'store'])->name('old.payments.store');
                Route::post('/payment-gateway/update/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('old.payments.update');
                Route::delete('/payment-gateway/delete/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('old.payments.destroy');
            });
    });

// ====================
