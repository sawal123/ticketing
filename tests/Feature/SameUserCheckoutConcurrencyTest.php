<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Event;
use App\Models\Harga;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SameUserCheckoutConcurrencyTest extends TestCase
{
    private array $userUids = [];

    private array $eventUids = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'ticketing_test') {
            $this->markTestSkipped('Concurrency test requires the isolated MySQL/MariaDB ticketing_test database.');
        }
    }

    protected function tearDown(): void
    {
        if (DB::connection()->getDriverName() === 'mysql'
            && DB::connection()->getDatabaseName() === 'ticketing_test') {
            DB::table('harga_carts')->whereIn('event_uid', $this->eventUids)->delete();
            DB::table('carts')->whereIn('event_uid', $this->eventUids)->delete();
            DB::table('hargas')->whereIn('uid', $this->eventUids)->delete();
            DB::table('events')->whereIn('uid', $this->eventUids)->delete();
            DB::table('users')->whereIn('uid', $this->userUids)->delete();
        }

        parent::tearDown();
    }

    public function test_parallel_checkout_for_same_user_and_event_creates_one_reservation(): void
    {
        [$event, $harga, $users] = $this->fixture(10, 1);

        $results = $this->runWorkers($event, $harga, [
            ['user_uid' => $users[0]->uid, 'quantity' => 2],
            ['user_uid' => $users[0]->uid, 'quantity' => 2],
        ]);

        $this->assertSame([false, true], collect($results)->pluck('created')->sort()->values()->all());
        $this->assertCount(1, collect($results)->pluck('uid')->unique());
        $this->assertSame(1, Cart::where('event_uid', $event->uid)
            ->where('user_uid', $users[0]->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->count());
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(1, DB::table('harga_carts')->where('event_uid', $event->uid)->count());
    }

    public function test_parallel_checkout_for_different_users_can_reserve_without_overselling(): void
    {
        [$event, $harga, $users] = $this->fixture(2, 2);

        $results = $this->runWorkers($event, $harga, [
            ['user_uid' => $users[0]->uid, 'quantity' => 1],
            ['user_uid' => $users[1]->uid, 'quantity' => 1],
        ]);

        $this->assertSame([true, true], collect($results)->pluck('created')->all());
        $this->assertCount(2, collect($results)->pluck('uid')->unique());
        $this->assertSame(2, Cart::where('event_uid', $event->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->count());
        $this->assertSame(2, (int) $harga->fresh()->reserved_qty);
        $this->assertSame(0, (int) $harga->fresh()->remainingQty());
    }

    private function fixture(int $stock, int $userCount): array
    {
        $suffix = Str::lower(Str::random(12));
        $users = collect(range(1, $userCount))->map(function (int $index) use ($suffix) {
            $user = User::create([
                'uid' => "tx3-user-{$suffix}-{$index}",
                'user_uid' => 'root',
                'name' => "TX3 User {$index}",
                'email' => "tx3-{$suffix}-{$index}@example.test",
                'password' => bcrypt('password'),
                'role' => User::USER_ROLE,
            ]);
            $this->userUids[] = $user->uid;

            return $user;
        })->all();

        $event = Event::create([
            'uid' => "tx3-event-{$suffix}",
            'user_uid' => 'tx3-owner',
            'event' => 'TX3 Concurrency Event',
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->toDateTimeString(),
            'status' => 'active',
            'fee' => 0,
            'cover' => 'cover.jpg',
            'slug' => "tx3-event-{$suffix}",
            'konfirmasi' => '1',
            'deskripsi' => 'TX3 concurrency test',
            'map' => '-',
        ]);
        $this->eventUids[] = $event->uid;

        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'TX3 VIP',
            'qty' => $stock,
            'sold_qty' => 0,
            'reserved_qty' => 0,
            'harga' => 150000,
            'status' => 'active',
            'max_order_qty' => max(5, $stock),
        ]);

        return [$event, $harga, $users];
    }

    private function runWorkers(Event $event, Harga $harga, array $workers): array
    {
        $barrierDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tx3-'.Str::lower(Str::random(12));
        mkdir($barrierDirectory);
        $startPath = $barrierDirectory.DIRECTORY_SEPARATOR.'start';
        $processes = [];

        $workerCode = <<<'PHP'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
file_put_contents($argv[5], 'ready');
$deadline = microtime(true) + 10;
while (! file_exists($argv[6])) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('TX3 worker barrier timed out.');
    }
    usleep(20000);
}
$event = App\Models\Event::where('uid', $argv[2])->firstOrFail();
$result = app(App\Services\Tickets\TicketReservationService::class)->reserveForUserEvent(
    $event,
    $argv[3],
    [['harga_id' => (int) $argv[4], 'quantity' => (int) $argv[7], 'order_by' => 1]]
);
echo json_encode(['uid' => $result['cart']->uid, 'created' => $result['created']], JSON_THROW_ON_ERROR);
PHP;

        try {
            foreach ($workers as $index => $worker) {
                $readyPath = $barrierDirectory.DIRECTORY_SEPARATOR."ready-{$index}";
                $process = new Process([
                    PHP_BINARY,
                    '-r',
                    $workerCode,
                    base_path(),
                    $event->uid,
                    $worker['user_uid'],
                    (string) $harga->id,
                    $readyPath,
                    $startPath,
                    (string) $worker['quantity'],
                ], base_path(), [
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'mysql',
                    'DB_DATABASE' => 'ticketing_test',
                ]);
                $process->setTimeout(30);
                $process->start();
                $processes[] = ['process' => $process, 'ready' => $readyPath];
            }

            $deadline = microtime(true) + 15;
            while (collect($processes)->contains(fn (array $worker) => ! file_exists($worker['ready']))) {
                if (microtime(true) >= $deadline) {
                    $this->fail('TX3 workers did not reach the concurrency barrier.');
                }
                usleep(20000);
            }

            file_put_contents($startPath, 'start');

            return collect($processes)->map(function (array $worker) {
                $worker['process']->wait();
                $this->assertTrue(
                    $worker['process']->isSuccessful(),
                    $worker['process']->getErrorOutput()
                );

                return json_decode($worker['process']->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            })->all();
        } finally {
            foreach ($processes as $worker) {
                if ($worker['process']->isRunning()) {
                    $worker['process']->stop();
                }
                @unlink($worker['ready']);
            }
            @unlink($startPath);
            @rmdir($barrierDirectory);
        }
    }
}
