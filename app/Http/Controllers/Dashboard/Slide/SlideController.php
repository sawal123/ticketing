<?php

namespace App\Http\Controllers\Dashboard\Slide;

use App\Models\Slider;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SlideController extends Controller
{

    public function addSlide(Request $request)
    {

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
            $file = $request->file('gambar');
            $fileName = $slider['uid_outlet'] . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/slide/', $fileName); // Simpan di direktori 'public/outlet/'
            $slider['gambar'] = $fileName; // Simpan nama file gambar di kolom 'gambar' pada tabel
        }
        $slider->save();
        return redirect()->back()->with('addSlide', 'Slide Berhasil Ditambah..');
    }
    public function deleteSlide($uid)
    {

        $slide = Slider::where('uid', $uid)->first();
        $imagePath = public_path() . '/storage/slide/' . $slide->gambar;
        if (file_exists($imagePath) === true) {
            unlink($imagePath);
        }
        $slide->delete();
        return redirect()->back()->with('deleteSlide', 'Slide Berhasil Dihapus');
    }

    public function editSlide(Request $request)
    {
        $slide = Slider::where('uid', $request->uid)->first();
        $slide->uid = $request->uid;
        $slide->title = $request->title;
        $slide->url = $request->url;
        $slide->sort = $request->sort;
        if ($request->hasFile('gambar')) {
            $imagePath = public_path() . '/storage/slide/' . $slide->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
            $file = $request->file('gambar');
            $fileName = $slide->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/slide/', $fileName);
            $slide->gambar = $fileName;
        }
        $slide->save();
        return redirect()->back()->with('editSlide', 'Slide Berhasil Diubah');
    }
}
