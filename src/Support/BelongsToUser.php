<?php

namespace Osoobe\LaravelTraits\Support;


trait BelongsToUser {

    /**
     * Get the user that owns the BelongsToUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}

?>
