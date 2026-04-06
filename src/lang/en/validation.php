<?php

declare(strict_types=1);

return [

    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The :attribute must not exceed :max characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute must be a valid date.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'in' => 'The selected :attribute is invalid.',

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'phone' => 'phone',
        'document' => 'document',
        'check_in' => 'check-in',
        'check_out' => 'check-out',
        'room_type' => 'room type',
        'room_number' => 'room number',
        'reason' => 'reason',
        'description' => 'description',
        'floor' => 'floor',
        'capacity' => 'capacity',
        'price_per_night' => 'price per night',
        'loyalty_tier' => 'loyalty tier',
    ],

];
