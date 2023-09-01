<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard(){
        return view('backend.content.dashboard',[
            'title' => 'Dashboard'
        ]);
    }
    public function event($addEvent = null){
        error_reporting(0);
        $event = Event::where('user_uid', Auth::user()->uid)->get();
        // dd($event);
        // dd($addEvent);
        if($addEvent === null){
            return view('backend.content.event',[
                'title' => 'Event',
                'event' => $event
            ]);
        }
        elseif($addEvent === 'addEvent'){
            
            return view('backend.semiPage.addEvent',[
                'title' => 'Add Event',
              
            ]);
        }
        elseif($addEvent === 'eventDetail'){
            $id = $_GET['id'];
            $eventDetail = Event::where('uid', $id)->first();
            $talent = Talent::where('uid', $id)->get();
            $harga = Harga::where('uid', $id)->get();
            return view('backend.semiPage.eventDetail',[
                'title' => 'Event Detail',
                'eventDetail' => $eventDetail,
                'talent'=> $talent,
                'harga'=> $harga
            ]);
        }
    }
    public function ubahEvents($uid){
        $ubahEvent = Event::where('uid', $uid)->first();
        return view('backend.semiPage.addEvent',[
            'title' => 'Ubah Event',
            'ubahEvent'=> $ubahEvent
        ]);
    }
}
