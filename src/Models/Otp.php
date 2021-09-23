<?php

namespace HasanAlyazidi\Sendables\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use SendablesHelpers;

class Otp extends Model
{
    const EXPIRE_AFTER = 120;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'verified_at', 'otp_user_id', 'otp_user_token',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(SendablesHelpers::getOtpTableName());
    }

    public function isValid()
    {
        $now = Carbon::now();

        $startDate = $this->created_at;
        $endDate   = Carbon::parse($startDate)->addSeconds(self::EXPIRE_AFTER);

        return $now->between($startDate, $endDate);
    }

    public function isExpired()
    {
        return !$this->isValid();
    }
}
