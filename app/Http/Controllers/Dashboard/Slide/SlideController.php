<?php

namespace App\Http\Controllers\Dashboard\Slide;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Services\SecureImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SlideController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function addSlide(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $slide = Slider::orderBy('sort', 'desc')->first();
        if ($slide === null) {
            $angka = 1;
        } else {
            $angka = $slide->sort + 1;
        }

        $slider = new Slider([
            'uid' => Str::uuid(),
            'sort' => $angka,
            'title' => $request->title,
            'url' => $request->url,
        ]);
        if ($request->hasFile('gambar')) {
            $slider['gambar'] = $this->images->storeBasename($request->file('gambar'), 'slide');
        }
        $slider->save();

        return redirect()->back()->with('addSlide', 'Slide Berhasil Ditambah..');
    }

    public function deleteSlide($uid)
    {

        $slide = Slider::where('uid', $uid)->first();
        $this->images->delete('slide', $slide->gambar);
        $slide->delete();

        return redirect()->back()->with('deleteSlide', 'Slide Berhasil Dihapus');
    }

    public function editSlide(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $slide = Slider::where('uid', $request->uid)->first();
        $slide->uid = $request->uid;
        $slide->title = $request->title;
        $slide->url = $request->url;
        $slide->sort = $request->sort;
        $oldImage = null;
        if ($request->hasFile('gambar')) {
            $oldImage = $slide->gambar;
            $slide->gambar = $this->images->storeBasename($request->file('gambar'), 'slide');
        }
        $slide->save();
        $this->images->delete('slide', $oldImage);

        return redirect()->back()->with('editSlide', 'Slide Berhasil Diubah');
    }
}
