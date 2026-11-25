<?php

namespace Tests\Feature;

use App\Livewire\Admin\CategoryIndex;
use App\Livewire\Admin\FasilitasIndex;
use App\Livewire\Admin\SliderIndex;
use App\Livewire\Admin\TermIndex;
use App\Models\Category;
use App\Models\Fasilitas;
use App\Models\Slider;
use App\Models\Term;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminModalButtonsResponsivenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('cache.default', 'array');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_slider_index_renders_confirm_delete_button_and_alpine_modal_triggers_and_deletes_record(): void
    {
        $slider = Slider::create([
            'uid' => (string) Str::uuid(),
            'title' => 'Banner Promo',
            'url' => 'https://example.com',
            'sort' => 1,
            'gambar' => 'banner.webp',
        ]);

        Livewire::test(SliderIndex::class)
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'slider-modal' })\"")
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'delete-modal' })\"")
            ->assertSeeHtml('wire:click="delete"')
            ->assertSee('Ya, Hapus Sekarang')
            ->call('confirmDelete', $slider->uid)
            ->assertDispatched('open-modal', name: 'delete-modal')
            ->call('delete')
            ->assertDispatched('close-modal', name: 'delete-modal');

        $this->assertDatabaseMissing('sliders', ['uid' => $slider->uid]);
    }

    public function test_term_index_renders_confirm_delete_button_and_alpine_modal_triggers_and_deletes_record(): void
    {
        $term = Term::create([
            'uid' => (string) Str::uuid(),
            'title' => 'Ketentuan Umum',
            'term' => 'Isi ketentuan umum',
        ]);

        Livewire::test(TermIndex::class)
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'term-modal' })\"")
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'delete-modal' })\"")
            ->assertSeeHtml('wire:click="delete"')
            ->assertSee('Ya, Hapus Sekarang')
            ->call('confirmDelete', $term->uid)
            ->assertDispatched('open-modal', name: 'delete-modal')
            ->call('delete')
            ->assertDispatched('close-modal', name: 'delete-modal');

        $this->assertDatabaseMissing('terms', ['uid' => $term->uid]);
    }

    public function test_category_and_fasilitas_use_alpine_instant_modal_open_and_loading_guards(): void
    {
        Category::create([
            'name' => 'Musik',
            'slug' => 'musik',
        ]);

        Fasilitas::create([
            'name' => 'Parkir VIP',
            'icon' => null,
        ]);

        Livewire::test(CategoryIndex::class)
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'category-modal' })\"")
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'delete-modal' })\"")
            ->assertSeeHtml('wire:target="delete,confirmDelete"');

        Livewire::test(FasilitasIndex::class)
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'fasilitas-modal' })\"")
            ->assertSeeHtml("x-on:click=\"\$dispatch('open-modal', { name: 'delete-modal' })\"")
            ->assertSeeHtml('wire:target="delete,confirmDelete"');
    }

    public function test_modal_delete_component_supports_both_message_and_description_with_alpine_dialog_attrs(): void
    {
        $rendered = $this->blade(
            '<x-admin.modal-delete name="sample-delete" title="Hapus Item" description="Pesan deskripsi custom"><button type="button">Hapus</button></x-admin.modal-delete>'
        );

        $rendered->assertSee('Hapus Item');
        $rendered->assertSee('Pesan deskripsi custom');
        $rendered->assertSee('role="dialog"', false);
        $rendered->assertSee('aria-modal="true"', false);
        $rendered->assertSee('x-on:keydown.escape.window', false);
    }

    private function createSchema(): void
    {
        Schema::create('sliders', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('title');
            $table->string('url')->nullable();
            $table->integer('sort')->default(1);
            $table->string('gambar')->nullable();
            $table->timestamps();
        });

        Schema::create('terms', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('title');
            $table->text('term');
            $table->timestamps();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('uid')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fasilitas', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();
        });
    }
}
