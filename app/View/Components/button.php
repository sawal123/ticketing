<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class button extends Component
{
    /**
     * Create a new component instance.
     */

     public $type;  
     public $text;  
     public $color;  
    public function __construct($type, $text, $color)
    {
        //
        $this->type = $type;
        $this->text = $text;
        $this->color = $color;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}
