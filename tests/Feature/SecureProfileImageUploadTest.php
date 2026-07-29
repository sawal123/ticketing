<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecureProfileImageUploadTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function ($table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('user_uid')->default('-');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nomor')->nullable();
            $table->string('role')->default('user');
            $table->string('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('kota')->nullable();
            $table->string('alamat')->nullable();
            $table->string('gambar')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Storage::fake('public');
        $this->withoutMiddleware([
            GlobalDataMiddleware::class,
            LogActivityMiddleware::class,
        ]);

        $this->user = User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'User Biasa',
            'email' => 'user@example.test',
            'nomor' => '08123456789',
            'role' => 'user',
            'birthday' => '2000-01-01',
            'gender' => 'pria',
            'kota' => 'Jakarta',
            'alamat' => 'Alamat awal',
            'gambar' => 'foto-lama.jpg',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);
    }

    public function test_dangerous_profile_uploads_are_rejected(): void
    {
        $jpeg = UploadedFile::fake()->image('source.jpg', 20, 20);
        $jpegContents = file_get_contents($jpeg->getRealPath());

        $cases = [
            'shell.php' => UploadedFile::fake()->createWithContent('shell.php', $jpegContents),
            'shell.phtml' => UploadedFile::fake()->createWithContent('shell.phtml', $jpegContents),
            'foto.jpg.php' => UploadedFile::fake()->createWithContent('foto.jpg.php', $jpegContents),
            'PHP dengan Content-Type image/jpeg' => UploadedFile::fake()
                ->createWithContent('payload.jpg', '<?php echo shell_exec($_GET["cmd"]);')
                ->mimeType('image/jpeg'),
            'image/PHP polyglot' => UploadedFile::fake()
                ->createWithContent('polyglot.jpg', $jpegContents.'<?php phpinfo();')
                ->mimeType('image/jpeg'),
            'file lebih dari 2 MB' => UploadedFile::fake()
                ->image('oversized.jpg', 20, 20)
                ->size(2049),
        ];

        foreach ($cases as $case => $file) {
            $response = $this->from('/profile')
                ->post('/profile/update-profile', $this->validPayload($file));

            $response->assertRedirect('/profile');
            $response->assertSessionHasErrors('gambar');
            $this->assertSame(
                [],
                Storage::disk('public')->allFiles('user'),
                "Payload {$case} tidak boleh tersimpan."
            );
            $this->assertSame('foto-lama.jpg', $this->user->fresh()->gambar);
        }
    }

    public function test_valid_jpeg_with_opening_tag_bytes_inside_metadata_is_accepted(): void
    {
        Storage::disk('public')->put('user/foto-lama.jpg', 'legacy image');

        $jpeg = UploadedFile::fake()->image('camera.jpg', 40, 40);
        $contents = file_get_contents($jpeg->getRealPath());

        // Add a valid JPEG COM segment containing the harmless byte sequence "<?".
        $withComment = substr($contents, 0, 2)
            ."\xFF\xFE\x00\x04<?"
            .substr($contents, 2);

        $file = UploadedFile::fake()
            ->createWithContent('camera.jpg', $withComment)
            ->mimeType('image/jpeg');

        $response = $this->from('/profile')->post(
            '/profile/update-profile',
            $this->validPayload($file)
        );

        $response->assertRedirect('/profile');
        $response->assertSessionDoesntHaveErrors();

        $storedName = $this->user->fresh()->gambar;
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.webp$/i',
            $storedName
        );
        Storage::disk('public')->assertExists('user/'.$storedName);
        Storage::disk('public')->assertMissing('user/foto-lama.jpg');
    }

    public function test_valid_image_is_reencoded_with_uuid_and_old_image_is_deleted(): void
    {
        Storage::disk('public')->put('user/foto-lama.jpg', 'legacy image');

        $response = $this->from('/profile')->post(
            '/profile/update-profile',
            $this->validPayload(UploadedFile::fake()->image('avatar.png', 40, 40))
        );

        $response->assertRedirect('/profile');
        $response->assertSessionDoesntHaveErrors();

        $storedName = $this->user->fresh()->gambar;
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.webp$/i',
            $storedName
        );
        $this->assertNotSame('avatar.png', $storedName);
        Storage::disk('public')->assertExists('user/'.$storedName);
        Storage::disk('public')->assertMissing('user/foto-lama.jpg');

        $storedContents = Storage::disk('public')->get('user/'.$storedName);
        $this->assertSame(
            'image/webp',
            (new \finfo(FILEINFO_MIME_TYPE))->buffer($storedContents)
        );
        $this->assertStringNotContainsString('<?php', $storedContents);
    }

    public function test_existing_photo_url_value_is_preserved_without_a_new_upload(): void
    {
        Storage::disk('public')->put('user/foto-lama.jpg', 'legacy image');

        $response = $this->from('/profile')->post(
            '/profile/update-profile',
            $this->validPayload()
        );

        $response->assertRedirect('/profile');
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('foto-lama.jpg', $this->user->fresh()->gambar);
        Storage::disk('public')->assertExists('user/foto-lama.jpg');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(?UploadedFile $image = null): array
    {
        return array_filter([
            'name' => 'User Biasa',
            'email' => 'user@example.test',
            'nomor' => '08123456789',
            'gender' => 'pria',
            'birthday' => '2000-01-01',
            'kota' => 'Jakarta',
            'alamat' => 'Alamat terbaru',
            'gambar' => $image,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
