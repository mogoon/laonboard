<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Popup extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    public $timestamps = false;

    public function __construct()
    {
        $this->table = 'popups';
    }

    public function getPopupData()
    {
        $popups = Popup::where('begin_time', '<=', Carbon::now())
            ->where('end_time', '>', Carbon::now())
            ->get();

        foreach($popups as $popup) {
            $popup->content = convertContent($popup->content, 1);
        }

        return $popups;
    }
}
